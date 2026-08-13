<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Form_Performance {
    const DB_VERSION = '1';
    const DB_VERSION_OPTION = 'oneplugin_light_form_performance_db_version';
    const RETENTION_DAYS = 90;
    const REPORTING_WINDOW_DAYS = 90;

    private static $instance = null;
    private $current_submission_context = [];
    private $current_email_context = [];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'ensure_schema'], 1);
        add_action('frm_entry_form', [$this, 'render_source_fields'], 10, 3);
        add_action('frm_after_create_entry', [$this, 'record_form_submission'], 5, 3);
        add_action('frm_trigger_email_action', [$this, 'begin_form_email_context'], 1, 4);
        add_action('frm_trigger_email_action', [$this, 'end_form_email_context'], 100, 4);
        add_action('wp_mail_failed', [$this, 'record_mail_failure'], 10, 1);
    }

    public static function activate() {
        self::install_schema();
    }

    public function ensure_schema() {
        if (get_option(self::DB_VERSION_OPTION, '') === self::DB_VERSION) {
            return;
        }

        self::install_schema();
    }

    private static function install_schema() {
        global $wpdb;

        if (!isset($wpdb) || !is_object($wpdb)) {
            return;
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            stat_date date NOT NULL,
            dimension_key char(32) NOT NULL,
            metric varchar(32) NOT NULL,
            form_id bigint(20) unsigned NOT NULL DEFAULT 0,
            page_id bigint(20) unsigned NOT NULL DEFAULT 0,
            form_name varchar(191) NOT NULL DEFAULT '',
            page_title varchar(191) NOT NULL DEFAULT '',
            page_path varchar(255) NOT NULL DEFAULT '',
            metric_count bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY stat_dimension (stat_date,dimension_key),
            KEY stat_date (stat_date),
            KEY metric (metric)
        ) {$charset_collate};";

        dbDelta($sql);
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, false);
    }

    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'oneplugin_form_performance';
    }

    /**
     * Adds page metadata to the submitted request without saving it in the Formidable entry.
     */
    public function render_source_fields($form, $form_action = 'create', $errors = []) {
        unset($errors);

        if (!$this->is_enabled() || $form_action !== 'create') {
            return;
        }

        $page_id = (int) get_queried_object_id();
        $page_path = $this->get_page_path($page_id);

        echo '<input type="hidden" name="oneplugin_light_source_page_id" value="' . esc_attr((string) $page_id) . '">';
        echo '<input type="hidden" name="oneplugin_light_source_page_path" value="' . esc_attr($page_path) . '">';
    }

    public function record_form_submission($entry_id, $form_id, $args = []) {
        if (!$this->is_enabled() || !empty($args['is_child']) || $this->is_draft_submission($entry_id)) {
            return;
        }

        $context = $this->build_form_context($form_id);
        $context['entry_id'] = (int) $entry_id;
        $this->current_submission_context = $context;

        $this->increment_metric('submission', $context);
    }

    public function begin_form_email_context($action, $entry, $form, $event = '') {
        unset($action, $event);

        if (!$this->is_enabled()) {
            $this->current_email_context = [];
            return;
        }

        $form_id = isset($form->id) ? (int) $form->id : (isset($entry->form_id) ? (int) $entry->form_id : 0);
        $entry_id = isset($entry->id) ? (int) $entry->id : 0;
        $context = $this->build_form_context($form_id, $form);

        if (
            $entry_id > 0 &&
            !empty($this->current_submission_context) &&
            (int) $this->current_submission_context['entry_id'] === $entry_id
        ) {
            $context = $this->current_submission_context;
        }

        $context['entry_id'] = $entry_id;
        $this->current_email_context = $context;
    }

    public function end_form_email_context($action = null, $entry = null, $form = null, $event = '') {
        unset($action, $entry, $form, $event);
        $this->current_email_context = [];
    }

    public function record_mail_failure($error) {
        unset($error);

        if (!$this->is_enabled()) {
            return;
        }

        $context = !empty($this->current_email_context)
            ? $this->current_email_context
            : $this->empty_context();

        $this->increment_metric('email_failed', $context);
    }

    public function get_report_payload() {
        $end_date = current_time('Y-m-d');
        $timezone = wp_timezone();
        $start = new DateTimeImmutable($end_date, $timezone);
        $start_date = $start->modify('-' . (self::REPORTING_WINDOW_DAYS - 1) . ' days')->format('Y-m-d');

        return [
            'schema_version' => 1,
            'generated_at' => gmdate('c'),
            'timezone' => wp_timezone_string(),
            'period_start' => $start_date,
            'period_end' => $end_date,
            'reporting_window_days' => self::REPORTING_WINDOW_DAYS,
            'retention_days' => self::RETENTION_DAYS,
            'contains_personal_data' => false,
            'integrations' => [
                'formidable_active' => class_exists('FrmEntry'),
                'fluent_smtp_active' => defined('FLUENTMAIL_PLUGIN_VERSION') || function_exists('fluentMailGetSettings'),
            ],
            'retained_totals' => $this->get_totals(),
            'records' => $this->get_records($start_date, $end_date),
        ];
    }

    private function get_records($start_date, $end_date) {
        global $wpdb;

        $table_name = self::get_table_name();
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT stat_date, metric, form_id, page_id, form_name, page_title, page_path, metric_count
                FROM {$table_name}
                WHERE stat_date BETWEEN %s AND %s
                ORDER BY stat_date ASC, form_id ASC, page_id ASC, page_path ASC",
                $start_date,
                $end_date
            ),
            ARRAY_A
        );

        $records = [];
        foreach ((array) $rows as $row) {
            $record_key = md5(
                (string) $row['stat_date'] . '|' .
                (string) $row['form_id'] . '|' .
                (string) $row['page_id'] . '|' .
                (string) $row['page_path']
            );

            if (!isset($records[$record_key])) {
                $records[$record_key] = [
                    'record_key' => $record_key,
                    'date' => (string) $row['stat_date'],
                    'form_id' => (int) $row['form_id'],
                    'form_name' => (string) $row['form_name'],
                    'page_id' => (int) $row['page_id'],
                    'page_title' => (string) $row['page_title'],
                    'page_path' => (string) $row['page_path'],
                    'submissions' => 0,
                    'email_failures' => 0,
                ];
            }

            if ($row['metric'] === 'submission') {
                $records[$record_key]['submissions'] = (int) $row['metric_count'];
            } elseif ($row['metric'] === 'email_failed') {
                $records[$record_key]['email_failures'] = (int) $row['metric_count'];
            }

            // Prefer the latest non-empty labels when metrics were recorded at different times.
            foreach (['form_name', 'page_title', 'page_path'] as $label_key) {
                if ($records[$record_key][$label_key] === '' && (string) $row[$label_key] !== '') {
                    $records[$record_key][$label_key] = (string) $row[$label_key];
                }
            }
        }

        return array_values($records);
    }

    private function get_totals() {
        global $wpdb;

        $table_name = self::get_table_name();
        $rows = $wpdb->get_results(
            "SELECT metric, IF(form_id > 0, 1, 0) AS attributed, SUM(metric_count) AS total
            FROM {$table_name}
            GROUP BY metric, IF(form_id > 0, 1, 0)",
            ARRAY_A
        );

        $totals = [
            'submissions' => 0,
            'email_failures' => 0,
            'attributed_email_failures' => 0,
            'unattributed_email_failures' => 0,
        ];

        foreach ((array) $rows as $row) {
            $total = (int) $row['total'];
            if ($row['metric'] === 'submission') {
                $totals['submissions'] += $total;
            } elseif ($row['metric'] === 'email_failed') {
                $totals['email_failures'] += $total;
                if (!empty($row['attributed'])) {
                    $totals['attributed_email_failures'] += $total;
                } else {
                    $totals['unattributed_email_failures'] += $total;
                }
            }
        }

        return $totals;
    }

    private function increment_metric($metric, $context) {
        global $wpdb;

        $metric = in_array($metric, ['submission', 'email_failed'], true) ? $metric : '';
        if ($metric === '') {
            return;
        }

        $context = wp_parse_args(is_array($context) ? $context : [], $this->empty_context());
        $stat_date = current_time('Y-m-d');
        $dimension_key = md5(
            $metric . '|' .
            (int) $context['form_id'] . '|' .
            (int) $context['page_id'] . '|' .
            (string) $context['page_path']
        );
        $table_name = self::get_table_name();

        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table_name}
                    (stat_date, dimension_key, metric, form_id, page_id, form_name, page_title, page_path, metric_count, updated_at)
                VALUES (%s, %s, %s, %d, %d, %s, %s, %s, 1, %s)
                ON DUPLICATE KEY UPDATE
                    metric_count = metric_count + 1,
                    form_name = IF(VALUES(form_name) <> '', VALUES(form_name), form_name),
                    page_title = IF(VALUES(page_title) <> '', VALUES(page_title), page_title),
                    updated_at = VALUES(updated_at)",
                $stat_date,
                $dimension_key,
                $metric,
                (int) $context['form_id'],
                (int) $context['page_id'],
                (string) $context['form_name'],
                (string) $context['page_title'],
                (string) $context['page_path'],
                gmdate('Y-m-d H:i:s')
            )
        );

        $this->maybe_prune_old_records();
    }

    private function maybe_prune_old_records() {
        if (get_transient('oneplugin_light_form_performance_pruned')) {
            return;
        }

        global $wpdb;
        $cutoff = new DateTimeImmutable(current_time('Y-m-d'), wp_timezone());
        $cutoff_date = $cutoff->modify('-' . self::RETENTION_DAYS . ' days')->format('Y-m-d');
        $table_name = self::get_table_name();

        $wpdb->query($wpdb->prepare("DELETE FROM {$table_name} WHERE stat_date < %s", $cutoff_date));
        set_transient('oneplugin_light_form_performance_pruned', '1', DAY_IN_SECONDS);
    }

    private function build_form_context($form_id, $form = null) {
        $form_id = (int) $form_id;

        if (!$form && $form_id > 0 && class_exists('FrmForm')) {
            $form = FrmForm::getOne($form_id);
        }

        $page_id = isset($_POST['oneplugin_light_source_page_id'])
            ? absint(wp_unslash($_POST['oneplugin_light_source_page_id']))
            : 0;
        $page_path = isset($_POST['oneplugin_light_source_page_path'])
            ? $this->sanitize_page_path(wp_unslash($_POST['oneplugin_light_source_page_path']))
            : '';

        if ($page_id > 0) {
            $canonical_path = $this->get_page_path($page_id);
            if ($canonical_path !== '') {
                $page_path = $canonical_path;
            }
        } elseif ($page_path === '') {
            $page_path = $this->get_referer_path();
            $page_id = $this->page_id_from_path($page_path);
        }

        return [
            'entry_id' => 0,
            'form_id' => $form_id,
            'form_name' => $form && isset($form->name) ? $this->truncate_text(sanitize_text_field((string) $form->name), 191) : '',
            'page_id' => $page_id,
            'page_title' => $page_id > 0 ? $this->truncate_text(sanitize_text_field((string) get_the_title($page_id)), 191) : '',
            'page_path' => $page_path,
        ];
    }

    private function empty_context() {
        return [
            'entry_id' => 0,
            'form_id' => 0,
            'form_name' => '',
            'page_id' => 0,
            'page_title' => '',
            'page_path' => '',
        ];
    }

    private function is_draft_submission($entry_id) {
        if (!empty($_POST['frm_saving_draft'])) {
            return true;
        }

        if (!class_exists('FrmEntry')) {
            return false;
        }

        $entry = FrmEntry::getOne((int) $entry_id);
        return $entry && !empty($entry->is_draft);
    }

    private function get_page_path($page_id) {
        $page_id = (int) $page_id;
        if ($page_id <= 0) {
            return '';
        }

        $permalink = get_permalink($page_id);
        return $permalink ? $this->sanitize_page_path((string) wp_parse_url($permalink, PHP_URL_PATH)) : '';
    }

    private function get_referer_path() {
        $referer = wp_get_referer();
        if (!$referer || !$this->is_local_url($referer)) {
            return '';
        }

        $path = $this->sanitize_page_path((string) wp_parse_url($referer, PHP_URL_PATH));
        $admin_path = $this->sanitize_page_path((string) wp_parse_url(admin_url('/'), PHP_URL_PATH));

        if (
            ($admin_path !== '' && strpos($path, $admin_path) === 0) ||
            strpos($path, '/wp-login.php') === 0
        ) {
            return '';
        }

        return $path;
    }

    private function page_id_from_path($path) {
        if ($path === '') {
            return 0;
        }

        return (int) url_to_postid(home_url($path));
    }

    private function sanitize_page_path($path) {
        $path = sanitize_text_field((string) $path);
        if ($path === '' || substr($path, 0, 1) !== '/') {
            return '';
        }

        return $this->truncate_text($path, 255);
    }

    private function is_local_url($url) {
        $home_host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
        $url_host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));

        return $home_host !== '' && $home_host === $url_host;
    }

    private function is_enabled() {
        if (function_exists('oneplugin_light_is_extension_enabled')) {
            return oneplugin_light_is_extension_enabled('form_performance');
        }

        return true;
    }

    private function truncate_text($value, $length) {
        if (function_exists('mb_substr')) {
            return mb_substr((string) $value, 0, (int) $length);
        }

        return substr((string) $value, 0, (int) $length);
    }
}
