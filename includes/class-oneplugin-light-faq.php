<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_FAQ {
    const POST_TYPE = 'oneplugin2_faq';
    const TAXONOMY = 'oneplugin2_faq_group';
    const META_INCLUDE_SCHEMA = '_oneplugin2_include_schema';

    private static $instance = null;
    private $assets_enqueued = false;
    private $fontawesome_enqueued = false;

    private $defaults = [
        'faq_open_icon' => '+',
        'faq_close_icon' => '-',
        'faq_use_same_icon' => '0',
        'faq_animation' => 'slide',
        'faq_animation_duration' => '220',
        'faq_accordion_mode' => 'single',
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() {
        add_action('init', [$this, 'register_content_types']);
        add_action('admin_menu', [$this, 'register_admin_menu'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [$this, 'save_meta_box']);
        add_filter('wp_insert_post_data', [$this, 'filter_quick_edit_post_data'], 10, 2);
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'filter_admin_columns']);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [$this, 'filter_sortable_admin_columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'render_admin_column'], 10, 2);
        add_action('quick_edit_custom_box', [$this, 'render_quick_edit_custom_box'], 10, 2);
        add_filter('post_row_actions', [$this, 'filter_row_actions'], 10, 2);
        add_action('restrict_manage_posts', [$this, 'render_admin_filters'], 10, 2);
        add_action('pre_get_posts', [$this, 'filter_admin_query']);
        add_filter('posts_clauses', [$this, 'filter_admin_query_clauses'], 10, 2);
        add_filter('parent_file', [$this, 'filter_parent_file']);
        add_filter('submenu_file', [$this, 'filter_submenu_file']);
        add_action('admin_notices', [$this, 'render_import_export_tools']);
        add_action('admin_post_oneplugin_light_export_faq', [$this, 'handle_export_faq']);
        add_action('admin_post_oneplugin_light_import_faq', [$this, 'handle_import_faq']);
        add_action('admin_post_oneplugin_light_add_faq', [$this, 'handle_add_faq']);
    }

    public function register_content_types() {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => __('FAQs', 'oneplugin-light-site-tools'),
                'singular_name' => __('FAQ', 'oneplugin-light-site-tools'),
                'add_new_item' => __('Add New FAQ', 'oneplugin-light-site-tools'),
                'edit_item' => __('Edit FAQ', 'oneplugin-light-site-tools'),
                'new_item' => __('New FAQ', 'oneplugin-light-site-tools'),
                'view_item' => __('View FAQ', 'oneplugin-light-site-tools'),
                'search_items' => __('Search FAQs', 'oneplugin-light-site-tools'),
                'not_found' => __('No FAQs found.', 'oneplugin-light-site-tools'),
                'menu_name' => __('FAQ', 'oneplugin-light-site-tools'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'menu_position' => 59,
            'menu_icon' => 'dashicons-editor-help',
            'supports' => ['title', 'editor', 'page-attributes'],
            'rewrite' => false,
        ]);

        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], [
            'labels' => [
                'name' => __('FAQ Groups', 'oneplugin-light-site-tools'),
                'singular_name' => __('FAQ Group', 'oneplugin-light-site-tools'),
                'search_items' => __('Search FAQ Groups', 'oneplugin-light-site-tools'),
                'all_items' => __('All FAQ Groups', 'oneplugin-light-site-tools'),
                'edit_item' => __('Edit FAQ Group', 'oneplugin-light-site-tools'),
                'update_item' => __('Update FAQ Group', 'oneplugin-light-site-tools'),
                'add_new_item' => __('Add New FAQ Group', 'oneplugin-light-site-tools'),
                'new_item_name' => __('New FAQ Group Name', 'oneplugin-light-site-tools'),
                'menu_name' => __('Groups', 'oneplugin-light-site-tools'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'hierarchical' => true,
            'rewrite' => false,
        ]);
    }

    public function register_admin_menu() {
        if (!class_exists('OnePlugin_Light_Site_Tools')) {
            return;
        }

        add_submenu_page(
            OnePlugin_Light_Site_Tools::MENU_SLUG,
            __('FAQ', 'oneplugin-light-site-tools'),
            __('FAQ', 'oneplugin-light-site-tools'),
            'edit_posts',
            'edit.php?post_type=' . self::POST_TYPE
        );

        add_submenu_page(
            OnePlugin_Light_Site_Tools::MENU_SLUG,
            __('FAQ Groups', 'oneplugin-light-site-tools'),
            __('FAQ Groups', 'oneplugin-light-site-tools'),
            'manage_categories',
            'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=' . self::POST_TYPE
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        if (!$this->is_faq_admin_screen() && !$this->is_faq_taxonomy_screen()) {
            return;
        }

        wp_enqueue_style(
            'oneplugin-light-faq-admin',
            ONEPLUGIN_LIGHT_URL . 'assets/css/faq-admin.css',
            [],
            ONEPLUGIN_LIGHT_VERSION
        );

        if (!$this->is_faq_list_screen() && !$this->is_faq_taxonomy_screen()) {
            return;
        }

        $deps = ['jquery'];
        if ($this->is_faq_list_screen()) {
            $deps[] = 'inline-edit-post';
        }

        wp_enqueue_script(
            'oneplugin-light-faq-admin',
            ONEPLUGIN_LIGHT_URL . 'assets/js/faq-admin.js',
            $deps,
            ONEPLUGIN_LIGHT_VERSION,
            true
        );

        wp_localize_script('oneplugin-light-faq-admin', 'OnePluginLightFAQAdmin', [
            'questionLabel' => __('Question', 'oneplugin-light-site-tools'),
            'answerLabel' => __('Answer', 'oneplugin-light-site-tools'),
            'exportSelectedEmpty' => __('Select at least one FAQ before exporting selected items.', 'oneplugin-light-site-tools'),
        ]);
    }

    public function filter_parent_file($parent_file) {
        global $pagenow;

        if ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && sanitize_key(wp_unslash($_GET['taxonomy'])) === self::TAXONOMY) {
            return class_exists('OnePlugin_Light_Site_Tools') ? OnePlugin_Light_Site_Tools::MENU_SLUG : $parent_file;
        }

        if (($pagenow === 'edit.php' || $pagenow === 'post.php' || $pagenow === 'post-new.php') && $this->is_faq_admin_screen()) {
            return class_exists('OnePlugin_Light_Site_Tools') ? OnePlugin_Light_Site_Tools::MENU_SLUG : $parent_file;
        }

        return $parent_file;
    }

    public function filter_submenu_file($submenu_file) {
        global $pagenow;

        if ($pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && sanitize_key(wp_unslash($_GET['taxonomy'])) === self::TAXONOMY) {
            return 'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=' . self::POST_TYPE;
        }

        if (($pagenow === 'edit.php' || $pagenow === 'post.php' || $pagenow === 'post-new.php') && $this->is_faq_admin_screen()) {
            return 'edit.php?post_type=' . self::POST_TYPE;
        }

        return $submenu_file;
    }

    private function is_faq_admin_screen() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && isset($screen->post_type) && $screen->post_type === self::POST_TYPE) {
            return true;
        }

        return isset($_GET['post_type']) && sanitize_key(wp_unslash($_GET['post_type'])) === self::POST_TYPE;
    }

    private function is_faq_list_screen() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && isset($screen->id)) {
            return $screen->id === 'edit-' . self::POST_TYPE;
        }

        global $pagenow;
        return $pagenow === 'edit.php' && isset($_GET['post_type']) && sanitize_key(wp_unslash($_GET['post_type'])) === self::POST_TYPE;
    }

    private function is_faq_taxonomy_screen() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && isset($screen->taxonomy)) {
            return $screen->taxonomy === self::TAXONOMY;
        }

        global $pagenow;
        return $pagenow === 'edit-tags.php' && isset($_GET['taxonomy']) && sanitize_key(wp_unslash($_GET['taxonomy'])) === self::TAXONOMY;
    }

    public function render_import_export_tools() {
        $is_list_screen = $this->is_faq_list_screen();
        $is_taxonomy_screen = $this->is_faq_taxonomy_screen();

        if (!$is_list_screen && !$is_taxonomy_screen) {
            return;
        }

        if ($is_list_screen && !current_user_can('edit_posts')) {
            return;
        }

        if ($is_taxonomy_screen && !current_user_can('manage_options')) {
            return;
        }

        $status = isset($_GET['oneplugin_light_faq_transfer']) ? sanitize_key(wp_unslash($_GET['oneplugin_light_faq_transfer'])) : '';
        $add_status = isset($_GET['oneplugin_light_faq_added']) ? sanitize_key(wp_unslash($_GET['oneplugin_light_faq_added'])) : '';
        if ($status === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('FAQ import completed successfully.', 'oneplugin-light-site-tools') . '</p></div>';
        } elseif ($status === 'error') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('FAQ import failed. Please use a valid FAQ JSON export.', 'oneplugin-light-site-tools') . '</p></div>';
        }

        if ($add_status === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('FAQ added successfully.', 'oneplugin-light-site-tools') . '</p></div>';
        } elseif ($add_status === 'error') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('FAQ could not be added. Question and answer are required.', 'oneplugin-light-site-tools') . '</p></div>';
        }

        $export_url = wp_nonce_url(admin_url('admin-post.php?action=oneplugin_light_export_faq'), 'oneplugin_light_export_faq');
        $groups = $this->get_faq_group_terms();
        $can_transfer = current_user_can('manage_options');
        ?>
        <?php if ($can_transfer) : ?>
            <div class="notice oneplugin-faq-admin-tools oneplugin-faq-transfer-tools">
                <div class="oneplugin-faq-admin-tools__bar">
                    <a class="button button-secondary" href="<?php echo esc_url($export_url); ?>"><?php esc_html_e('Export all', 'oneplugin-light-site-tools'); ?></a>
                    <?php if ($is_list_screen) : ?>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="oneplugin-faq-export-selected-form" data-faq-export-selected-form>
                            <input type="hidden" name="action" value="oneplugin_light_export_faq" />
                            <input type="hidden" name="oneplugin_light_faq_export_ids" value="" data-faq-export-selected-ids />
                            <?php wp_nonce_field('oneplugin_light_export_faq'); ?>
                            <button type="submit" class="button button-secondary" data-faq-export-selected disabled><?php esc_html_e('Export selected', 'oneplugin-light-site-tools'); ?></button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="oneplugin-faq-import-form" data-faq-import-form>
                        <input type="hidden" name="action" value="oneplugin_light_import_faq" />
                        <?php wp_nonce_field('oneplugin_light_import_faq'); ?>
                        <input type="file" name="oneplugin_light_faq_import_file" accept=".json,application/json" required hidden data-faq-import-file />
                        <button type="button" class="button button-secondary" data-faq-import-button><?php esc_html_e('Import JSON', 'oneplugin-light-site-tools'); ?></button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($is_list_screen) : ?>
            <div class="notice oneplugin-faq-admin-tools oneplugin-faq-quick-add-panel" data-faq-quick-add-panel>
                <button type="button" class="button button-primary oneplugin-faq-quick-add-toggle" data-faq-quick-add-toggle aria-expanded="false">
                    <?php esc_html_e('Quick add FAQ', 'oneplugin-light-site-tools'); ?>
                </button>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="oneplugin-faq-quick-add" data-faq-quick-add-form hidden>
                <input type="hidden" name="action" value="oneplugin_light_add_faq" />
                <?php wp_nonce_field('oneplugin_light_add_faq'); ?>
                <div class="oneplugin-faq-quick-add__fields">
                    <div class="oneplugin-faq-quick-add__main">
                        <label>
                            <span><?php esc_html_e('Question', 'oneplugin-light-site-tools'); ?></span>
                            <input type="text" name="oneplugin_light_faq_question" required />
                        </label>
                        <label>
                            <span><?php esc_html_e('Answer', 'oneplugin-light-site-tools'); ?></span>
                            <textarea name="oneplugin_light_faq_answer" rows="5" required></textarea>
                        </label>
                    </div>
                    <div class="oneplugin-faq-quick-add__side">
                        <fieldset>
                            <legend><?php esc_html_e('Groups', 'oneplugin-light-site-tools'); ?></legend>
                            <div class="oneplugin-faq-group-checklist">
                                <?php if (empty($groups)) : ?>
                                    <span class="oneplugin-faq-group-checklist__empty"><?php esc_html_e('No FAQ groups yet.', 'oneplugin-light-site-tools'); ?></span>
                                <?php else : ?>
                                    <?php foreach ($groups as $group) : ?>
                                        <label>
                                            <input type="checkbox" name="oneplugin_light_faq_groups[]" value="<?php echo esc_attr((string) $group->term_id); ?>" />
                                            <span><?php echo esc_html($group->name); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </fieldset>
                        <button type="submit" class="button button-primary"><?php esc_html_e('Add FAQ', 'oneplugin-light-site-tools'); ?></button>
                    </div>
                </div>
                </form>
            </div>
        <?php endif; ?>
        <?php
    }

    public function handle_export_faq() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'oneplugin-light-site-tools'));
        }

        check_admin_referer('oneplugin_light_export_faq');

        $selected_ids = $this->get_requested_export_ids();
        $payload = [
            'plugin' => 'oneplugin-light-site-tools',
            'type' => 'faq',
            'version' => defined('ONEPLUGIN_LIGHT_VERSION') ? ONEPLUGIN_LIGHT_VERSION : '1.0.0',
            'exported_at' => current_time('mysql'),
            'selection' => empty($selected_ids) ? 'all' : 'selected',
            'faq' => $this->get_faq_export_data($selected_ids),
        ];

        nocache_headers();
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Content-Disposition: attachment; filename="oneplugin-faq-' . (empty($selected_ids) ? '' : 'selected-') . gmdate('Y-m-d-His') . '.json"');

        echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function handle_import_faq() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'oneplugin-light-site-tools'));
        }

        check_admin_referer('oneplugin_light_import_faq');

        $file_key = 'oneplugin_light_faq_import_file';
        $raw = $this->read_uploaded_json_file($file_key, 10 * MB_IN_BYTES);
        if ($raw === false) {
            $this->redirect_faq_transfer_status('error');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['faq']) || !is_array($decoded['faq'])) {
            $this->redirect_faq_transfer_status('error');
        }

        $faq_data = $decoded['faq'];
        $groups = isset($faq_data['groups']) && is_array($faq_data['groups']) ? $faq_data['groups'] : [];
        $items = isset($faq_data['items']) && is_array($faq_data['items']) ? $faq_data['items'] : [];

        if (!$this->import_faq_data($groups, $items)) {
            $this->redirect_faq_transfer_status('error');
        }

        $this->redirect_faq_transfer_status('success');
    }

    private function read_uploaded_json_file($file_key, $max_bytes) {
        if (empty($_FILES[$file_key]) || !is_array($_FILES[$file_key])) {
            return false;
        }

        $file = $_FILES[$file_key];
        if (!empty($file['error']) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }

        $size = isset($file['size']) ? absint($file['size']) : 0;
        if ($size <= 0 || $size > $max_bytes) {
            return false;
        }

        $name = isset($file['name']) ? sanitize_file_name((string) $file['name']) : '';
        if ($name === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
            return false;
        }

        return file_get_contents($file['tmp_name']);
    }

    public function handle_add_faq() {
        if (!current_user_can('edit_posts')) {
            wp_die(esc_html__('Unauthorized request.', 'oneplugin-light-site-tools'));
        }

        check_admin_referer('oneplugin_light_add_faq');

        $question = isset($_POST['oneplugin_light_faq_question']) ? sanitize_text_field(wp_unslash($_POST['oneplugin_light_faq_question'])) : '';
        $answer = isset($_POST['oneplugin_light_faq_answer']) ? wp_kses_post(wp_unslash($_POST['oneplugin_light_faq_answer'])) : '';

        if ($question === '' || $answer === '') {
            $this->redirect_faq_add_status('error');
        }

        $post_id = wp_insert_post(wp_slash([
            'post_type' => self::POST_TYPE,
            'post_title' => $question,
            'post_content' => $answer,
            'post_status' => 'publish',
        ]), true);

        if (is_wp_error($post_id) || !$post_id) {
            $this->redirect_faq_add_status('error');
        }

        $group_ids = isset($_POST['oneplugin_light_faq_groups']) ? wp_parse_id_list(wp_unslash($_POST['oneplugin_light_faq_groups'])) : [];
        if (!empty($group_ids)) {
            wp_set_object_terms($post_id, $group_ids, self::TAXONOMY, false);
        }

        update_post_meta($post_id, self::META_INCLUDE_SCHEMA, '1');
        $this->redirect_faq_add_status('success');
    }

    private function redirect_faq_transfer_status($status) {
        $referer = wp_get_referer();
        $fallback = admin_url('edit.php?post_type=' . self::POST_TYPE);
        $target = is_string($referer) && $referer !== '' ? $referer : $fallback;

        wp_safe_redirect(add_query_arg('oneplugin_light_faq_transfer', $status, $target));
        exit;
    }

    private function redirect_faq_add_status($status) {
        $referer = wp_get_referer();
        $fallback = admin_url('edit.php?post_type=' . self::POST_TYPE);
        $target = is_string($referer) && $referer !== '' ? $referer : $fallback;

        wp_safe_redirect(add_query_arg('oneplugin_light_faq_added', $status, $target));
        exit;
    }

    public function register_meta_box() {
        add_meta_box(
            'oneplugin2_faq_options',
            __('FAQ Options', 'oneplugin-light-site-tools'),
            [$this, 'render_meta_box'],
            self::POST_TYPE,
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('oneplugin2_faq_options', 'oneplugin2_faq_options_nonce');
        $include_schema = get_post_meta($post->ID, self::META_INCLUDE_SCHEMA, true);
        ?>
        <p>
            <label for="oneplugin2_include_schema">
                <input type="checkbox" id="oneplugin2_include_schema" name="oneplugin2_include_schema" value="1" <?php checked($include_schema !== '0'); ?> />
                <?php esc_html_e('Include this FAQ in schema output.', 'oneplugin-light-site-tools'); ?>
            </label>
        </p>
        <p><?php esc_html_e('Use the title as the question and the editor content as the answer. The Order field controls accordion order.', 'oneplugin-light-site-tools'); ?></p>
        <?php
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['oneplugin2_faq_options_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oneplugin2_faq_options_nonce'])), 'oneplugin2_faq_options')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        update_post_meta($post_id, self::META_INCLUDE_SCHEMA, isset($_POST['oneplugin2_include_schema']) ? '1' : '0');
    }

    public function filter_quick_edit_post_data($data, $postarr) {
        if (!is_array($data) || empty($data['post_type']) || $data['post_type'] !== self::POST_TYPE) {
            return $data;
        }

        if (empty($_POST['oneplugin_light_faq_quick_edit_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oneplugin_light_faq_quick_edit_nonce'])), 'oneplugin_light_faq_quick_edit')) {
            return $data;
        }

        $post_id = isset($postarr['ID']) ? absint($postarr['ID']) : 0;
        if ($post_id && !current_user_can('edit_post', $post_id)) {
            return $data;
        }

        $data['post_content'] = isset($_POST['oneplugin_light_faq_answer']) ? wp_kses_post(wp_unslash($_POST['oneplugin_light_faq_answer'])) : '';
        $data['post_password'] = '';

        return $data;
    }

    public function filter_admin_columns($columns) {
        $title = isset($columns['title']) ? $columns['title'] : __('Question', 'oneplugin-light-site-tools');
        $date = isset($columns['date']) ? $columns['date'] : __('Date', 'oneplugin-light-site-tools');

        return [
            'cb' => isset($columns['cb']) ? $columns['cb'] : '',
            'title' => $title,
            self::TAXONOMY => __('Group', 'oneplugin-light-site-tools'),
            'menu_order' => __('Order', 'oneplugin-light-site-tools'),
            'schema' => __('Schema', 'oneplugin-light-site-tools'),
            'date' => $date,
        ];
    }

    public function filter_sortable_admin_columns($columns) {
        $columns['title'] = 'title';
        $columns[self::TAXONOMY] = 'faq_group';
        $columns['menu_order'] = 'menu_order';
        $columns['date'] = 'date';

        return $columns;
    }

    public function render_admin_filters($post_type, $which = '') {
        if ($post_type !== self::POST_TYPE || $which !== 'top') {
            return;
        }

        $selected_group = isset($_GET['oneplugin_light_faq_group']) ? absint(wp_unslash($_GET['oneplugin_light_faq_group'])) : 0;
        $selected_orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
        $selected_order = isset($_GET['order']) ? strtoupper(sanitize_key(wp_unslash($_GET['order']))) : '';
        if (!in_array($selected_order, ['ASC', 'DESC'], true)) {
            $selected_order = '';
        }

        wp_dropdown_categories([
            'taxonomy' => self::TAXONOMY,
            'name' => 'oneplugin_light_faq_group',
            'id' => 'oneplugin-light-faq-group-filter-' . sanitize_html_class((string) $which),
            'class' => 'postform oneplugin-faq-admin-filter',
            'show_option_all' => __('All FAQ groups', 'oneplugin-light-site-tools'),
            'hide_empty' => false,
            'hierarchical' => true,
            'show_count' => false,
            'orderby' => 'name',
            'selected' => $selected_group,
            'value_field' => 'term_id',
        ]);

        $sort_options = [
            '' => __('Default order', 'oneplugin-light-site-tools'),
            'date' => __('Date', 'oneplugin-light-site-tools'),
            'title' => __('Name', 'oneplugin-light-site-tools'),
            'menu_order' => __('Order number', 'oneplugin-light-site-tools'),
            'faq_group' => __('Group', 'oneplugin-light-site-tools'),
        ];
        ?>
        <select name="orderby" id="<?php echo esc_attr('oneplugin-light-faq-orderby-' . $which); ?>" class="postform oneplugin-faq-admin-filter">
            <?php foreach ($sort_options as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($selected_orderby, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="order" id="<?php echo esc_attr('oneplugin-light-faq-order-' . $which); ?>" class="postform oneplugin-faq-admin-filter">
            <option value="" <?php selected($selected_order, ''); ?>><?php esc_html_e('Default direction', 'oneplugin-light-site-tools'); ?></option>
            <option value="ASC" <?php selected($selected_order, 'ASC'); ?>><?php esc_html_e('Ascending', 'oneplugin-light-site-tools'); ?></option>
            <option value="DESC" <?php selected($selected_order, 'DESC'); ?>><?php esc_html_e('Descending', 'oneplugin-light-site-tools'); ?></option>
        </select>
        <?php
    }

    public function filter_admin_query($query) {
        if (!$this->is_faq_admin_main_query($query)) {
            return;
        }

        $group_id = isset($_GET['oneplugin_light_faq_group']) ? absint(wp_unslash($_GET['oneplugin_light_faq_group'])) : 0;
        if ($group_id > 0) {
            $tax_query = $query->get('tax_query');
            $tax_query = is_array($tax_query) ? $tax_query : [];
            $tax_query[] = [
                'taxonomy' => self::TAXONOMY,
                'field' => 'term_id',
                'terms' => [$group_id],
            ];
            $query->set('tax_query', $tax_query);
        }

        $orderby = $query->get('orderby');
        $orderby = is_scalar($orderby) ? sanitize_key((string) $orderby) : '';
        if ($orderby === 'name') {
            $orderby = 'title';
        }

        if (in_array($orderby, ['date', 'title', 'menu_order', 'faq_group'], true)) {
            $query->set('orderby', $orderby);
        }

        $order = $query->get('order');
        $order = is_scalar($order) ? strtoupper(sanitize_key((string) $order)) : '';
        if (in_array($order, ['ASC', 'DESC'], true)) {
            $query->set('order', $order);
        }
    }

    public function filter_admin_query_clauses($clauses, $query) {
        if (!$this->is_faq_admin_main_query($query) || $query->get('orderby') !== 'faq_group') {
            return $clauses;
        }

        global $wpdb;

        $order = strtoupper((string) $query->get('order')) === 'DESC' ? 'DESC' : 'ASC';
        if (strpos($clauses['join'], 'oneplugin_faq_group_terms') === false) {
            $clauses['join'] .= $wpdb->prepare(
                " LEFT JOIN {$wpdb->term_relationships} AS oneplugin_faq_group_rel ON {$wpdb->posts}.ID = oneplugin_faq_group_rel.object_id
                LEFT JOIN {$wpdb->term_taxonomy} AS oneplugin_faq_group_tax ON oneplugin_faq_group_rel.term_taxonomy_id = oneplugin_faq_group_tax.term_taxonomy_id AND oneplugin_faq_group_tax.taxonomy = %s
                LEFT JOIN {$wpdb->terms} AS oneplugin_faq_group_terms ON oneplugin_faq_group_tax.term_id = oneplugin_faq_group_terms.term_id",
                self::TAXONOMY
            );
        }

        if (empty($clauses['groupby'])) {
            $clauses['groupby'] = "{$wpdb->posts}.ID";
        } elseif (strpos($clauses['groupby'], "{$wpdb->posts}.ID") === false) {
            $clauses['groupby'] .= ", {$wpdb->posts}.ID";
        }

        $clauses['orderby'] = "MIN(oneplugin_faq_group_terms.name) {$order}, {$wpdb->posts}.post_title ASC";

        return $clauses;
    }

    private function is_faq_admin_main_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return false;
        }

        $post_type = $query->get('post_type');
        if (is_array($post_type)) {
            return in_array(self::POST_TYPE, $post_type, true);
        }

        return $post_type === self::POST_TYPE;
    }

    public function render_admin_column($column, $post_id) {
        if ($column === 'menu_order') {
            echo esc_html((string) get_post_field('menu_order', $post_id));
            $this->render_inline_data($post_id);
            return;
        }

        if ($column === self::TAXONOMY) {
            $terms = get_the_terms($post_id, self::TAXONOMY);
            if (empty($terms) || is_wp_error($terms)) {
                echo '&mdash;';
                return;
            }

            $term_links = [];
            foreach ($terms as $term) {
                $link = get_edit_term_link($term->term_id, self::TAXONOMY, self::POST_TYPE);
                if (is_wp_error($link) || empty($link)) {
                    $term_links[] = esc_html($term->name);
                    continue;
                }

                $term_links[] = '<a href="' . esc_url($link) . '">' . esc_html($term->name) . '</a>';
            }

            echo wp_kses_post(implode(', ', $term_links));
            $this->render_inline_data($post_id);
            return;
        }

        if ($column === 'schema') {
            $include_schema = get_post_meta($post_id, self::META_INCLUDE_SCHEMA, true);
            echo $include_schema === '0' ? esc_html__('No', 'oneplugin-light-site-tools') : esc_html__('Yes', 'oneplugin-light-site-tools');
            $this->render_inline_data($post_id);
        }
    }

    private function render_inline_data($post_id) {
        echo $this->get_inline_data_markup($post_id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    private function get_inline_data_markup($post_id) {
        return '<span class="hidden oneplugin-faq-inline-data" data-answer="' . esc_attr((string) get_post_field('post_content', $post_id)) . '"></span>';
    }

    public function filter_row_actions($actions, $post) {
        if (!$post || empty($post->post_type) || $post->post_type !== self::POST_TYPE) {
            return $actions;
        }

        $inline_data = $this->get_inline_data_markup($post->ID);
        if (isset($actions['edit'])) {
            $actions['edit'] .= $inline_data;
        } else {
            $actions['oneplugin_faq_inline_data'] = $inline_data;
        }

        return $actions;
    }

    public function render_quick_edit_custom_box($column_name, $post_type) {
        static $rendered = false;
        if ($rendered || $post_type !== self::POST_TYPE || !in_array($column_name, [self::TAXONOMY, 'menu_order', 'schema'], true)) {
            return;
        }

        $rendered = true;
        ?>
        <fieldset class="inline-edit-col-left oneplugin-faq-inline-edit-fields">
            <div class="inline-edit-col">
                <?php wp_nonce_field('oneplugin_light_faq_quick_edit', 'oneplugin_light_faq_quick_edit_nonce', false); ?>
                <label class="inline-edit-group">
                    <span class="title"><?php esc_html_e('Answer', 'oneplugin-light-site-tools'); ?></span>
                    <span class="input-text-wrap">
                        <textarea name="oneplugin_light_faq_answer" rows="5" data-oneplugin-faq-quick-answer></textarea>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    private function get_faq_export_data($post_ids = []) {
        $post_ids = wp_parse_id_list($post_ids);
        $query_args = [
            'post_type' => self::POST_TYPE,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby' => [
                'menu_order' => 'ASC',
                'date' => 'DESC',
            ],
            'order' => 'ASC',
        ];

        if (!empty($post_ids)) {
            $query_args['post__in'] = $post_ids;
            $query_args['orderby'] = 'post__in';
        }

        $faq_posts = get_posts($query_args);

        $items = [];
        $used_term_ids = [];
        foreach ($faq_posts as $faq_post) {
            $terms = get_the_terms($faq_post->ID, self::TAXONOMY);
            $group_slugs = [];

            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $used_term_ids[] = (int) $term->term_id;
                }
                $group_slugs = array_values(array_filter(array_map(static function ($term) {
                    return !empty($term->slug) ? $term->slug : '';
                }, $terms)));
            }

            $items[] = [
                'title' => get_the_title($faq_post),
                'slug' => $faq_post->post_name,
                'content' => $faq_post->post_content,
                'status' => $faq_post->post_status,
                'menu_order' => (int) $faq_post->menu_order,
                'groups' => $group_slugs,
                'meta' => [
                    'include_schema' => get_post_meta($faq_post->ID, self::META_INCLUDE_SCHEMA, true) === '0' ? '0' : '1',
                ],
            ];
        }

        return [
            'groups' => $this->get_faq_export_groups(empty($post_ids) ? null : $used_term_ids),
            'items' => $items,
        ];
    }

    private function get_faq_export_groups($term_ids = null) {
        $args = [
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
        ];

        if (is_array($term_ids)) {
            $term_ids = $this->include_parent_term_ids($term_ids);
            if (empty($term_ids)) {
                return [];
            }
            $args['include'] = $term_ids;
        }

        $terms = get_terms($args);
        $groups = [];
        if (!empty($terms) && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $parent_term = !empty($term->parent) ? get_term($term->parent, self::TAXONOMY) : null;
                $groups[] = [
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'description' => $term->description,
                    'parent_slug' => ($parent_term && !is_wp_error($parent_term)) ? $parent_term->slug : '',
                ];
            }
        }

        return $groups;
    }

    private function include_parent_term_ids($term_ids) {
        $term_ids = wp_parse_id_list($term_ids);
        $all_ids = $term_ids;

        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, self::TAXONOMY);
            while ($term && !is_wp_error($term) && !empty($term->parent)) {
                $parent_id = (int) $term->parent;
                $all_ids[] = $parent_id;
                $term = get_term($parent_id, self::TAXONOMY);
            }
        }

        return array_values(array_unique(array_filter(array_map('absint', $all_ids))));
    }

    private function get_requested_export_ids() {
        if (empty($_REQUEST['oneplugin_light_faq_export_ids'])) {
            return [];
        }

        return wp_parse_id_list(wp_unslash($_REQUEST['oneplugin_light_faq_export_ids']));
    }

    private function get_faq_group_terms() {
        $terms = get_terms([
            'taxonomy' => self::TAXONOMY,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        return (!empty($terms) && !is_wp_error($terms)) ? $terms : [];
    }

    private function import_faq_data($groups, $items) {
        $allowed_statuses = ['publish', 'draft', 'pending', 'private'];
        $term_map = [];

        foreach ($groups as $group) {
            if (!is_array($group) || empty($group['name'])) {
                continue;
            }

            $slug = !empty($group['slug']) ? sanitize_title($group['slug']) : sanitize_title($group['name']);
            if ($slug === '') {
                continue;
            }

            $term_map[$slug] = [
                'name' => sanitize_text_field($group['name']),
                'slug' => $slug,
                'description' => isset($group['description']) ? sanitize_textarea_field($group['description']) : '',
                'parent_slug' => !empty($group['parent_slug']) ? sanitize_title($group['parent_slug']) : '',
            ];
        }

        foreach ($term_map as $slug => $group) {
            $existing = get_term_by('slug', $slug, self::TAXONOMY);
            if ($existing && !is_wp_error($existing)) {
                $result = wp_update_term($existing->term_id, self::TAXONOMY, [
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'slug' => $group['slug'],
                ]);
            } else {
                $result = wp_insert_term($group['name'], self::TAXONOMY, [
                    'description' => $group['description'],
                    'slug' => $group['slug'],
                ]);
            }

            if (is_wp_error($result)) {
                return false;
            }
        }

        foreach ($term_map as $slug => $group) {
            if (empty($group['parent_slug'])) {
                continue;
            }

            $child = get_term_by('slug', $slug, self::TAXONOMY);
            $parent = get_term_by('slug', $group['parent_slug'], self::TAXONOMY);
            if (!$child || is_wp_error($child) || !$parent || is_wp_error($parent)) {
                continue;
            }

            $result = wp_update_term($child->term_id, self::TAXONOMY, [
                'parent' => (int) $parent->term_id,
            ]);
            if (is_wp_error($result)) {
                return false;
            }
        }

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['title'])) {
                continue;
            }

            $slug = !empty($item['slug']) ? sanitize_title($item['slug']) : sanitize_title($item['title']);
            if ($slug === '') {
                $slug = sanitize_title(wp_generate_uuid4());
            }

            $status = !empty($item['status']) && in_array($item['status'], $allowed_statuses, true) ? $item['status'] : 'publish';
            $postarr = [
                'post_type' => self::POST_TYPE,
                'post_title' => sanitize_text_field($item['title']),
                'post_name' => $slug,
                'post_content' => isset($item['content']) ? wp_kses_post($item['content']) : '',
                'post_status' => $status,
                'menu_order' => isset($item['menu_order']) ? absint($item['menu_order']) : 0,
            ];

            $existing = get_page_by_path($slug, OBJECT, self::POST_TYPE);
            if ($existing) {
                $postarr['ID'] = $existing->ID;
                $post_id = wp_update_post(wp_slash($postarr), true);
            } else {
                $post_id = wp_insert_post(wp_slash($postarr), true);
            }

            if (is_wp_error($post_id) || !$post_id) {
                return false;
            }

            $group_ids = [];
            if (!empty($item['groups']) && is_array($item['groups'])) {
                foreach ($item['groups'] as $group_slug) {
                    $group_slug = sanitize_title($group_slug);
                    if ($group_slug === '') {
                        continue;
                    }

                    $term = get_term_by('slug', $group_slug, self::TAXONOMY);
                    if ($term && !is_wp_error($term)) {
                        $group_ids[] = (int) $term->term_id;
                    }
                }
            }

            wp_set_object_terms($post_id, $group_ids, self::TAXONOMY, false);

            $include_schema = isset($item['meta']['include_schema']) && $item['meta']['include_schema'] === '0' ? '0' : '1';
            update_post_meta($post_id, self::META_INCLUDE_SCHEMA, $include_schema);
        }

        return true;
    }

    public function render($atts = []) {
        $settings = $this->get_settings();
        if (!apply_filters('oneplugin_light_faq_frontend_enabled', true, $settings)) {
            return '';
        }

        $atts = shortcode_atts([
            'group' => '',
            'limit' => -1,
            'columns' => '1',
            'rows' => '0',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'schema' => 'true',
            'open_icon' => '',
            'close_icon' => '',
            'use_same_icon' => '',
            'animation' => '',
            'animation_duration' => '',
            'accordion_mode' => '',
            'open_first' => 'false',
            'class' => '',
        ], is_array($atts) ? $atts : [], 'oneplugin_divi5_faq');

        $settings = $this->merge_shortcode_settings($settings, $atts);

        $columns = $this->sanitize_columns($atts['columns']);
        $rows = $this->sanitize_rows($atts['rows']);
        $limit = $this->resolve_query_limit($this->sanitize_limit($atts['limit']), $columns, $rows);
        $orderby = $this->sanitize_orderby($atts['orderby']);
        $order = $this->sanitize_order($atts['order']);

        $query_args = [
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'no_found_rows' => true,
        ];

        if ($orderby === 'rand') {
            $query_args['orderby'] = 'rand';
        } elseif ($orderby === 'date') {
            $query_args['orderby'] = 'date';
            $query_args['order'] = $order;
        } else {
            $query_args['orderby'] = [
                'menu_order' => 'ASC',
                'title' => 'ASC',
            ];
            $query_args['order'] = 'ASC';
        }

        $groups = $this->sanitize_group_slugs($atts['group']);
        if (!empty($groups)) {
            $query_args['tax_query'] = [[
                'taxonomy' => self::TAXONOMY,
                'field' => 'slug',
                'terms' => $groups,
                'operator' => 'IN',
            ]];
        }

        $query = new WP_Query($query_args);
        if (!$query->have_posts()) {
            return '';
        }

        $wrapper_classes = ['oneplugin2-faq', 'oneplugin2-faq--columns-' . $columns];
        $custom_classes = $this->sanitize_class_names((string) $atts['class']);
        if ($custom_classes !== '') {
            $wrapper_classes[] = $custom_classes;
        }

        $schema_enabled = !in_array(strtolower((string) $atts['schema']), ['0', 'false', 'no', 'off'], true);
        $schema_items = [];
        $instance_id = wp_unique_id('oneplugin2-faq-');
        $open_icon = $this->normalize_icon_value($settings['faq_open_icon'], '+');
        $close_icon = $settings['faq_use_same_icon'] === '1' ? $open_icon : $this->normalize_icon_value($settings['faq_close_icon'], '-');
        $open_first = !in_array(strtolower((string) $atts['open_first']), ['0', 'false', 'no', 'off'], true);
        $faq_columns = $this->split_posts_into_columns($query->posts, $columns);

        if ($schema_enabled) {
            foreach ($query->posts as $faq_post) {
                if (get_post_meta($faq_post->ID, self::META_INCLUDE_SCHEMA, true) === '0') {
                    continue;
                }

                $schema_items[] = [
                    '@type' => 'Question',
                    'name' => wp_strip_all_tags(get_the_title($faq_post)),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => wp_strip_all_tags(strip_shortcodes($faq_post->post_content)),
                    ],
                ];
            }
        }

        $this->enqueue_assets($settings, $this->icon_uses_fontawesome($open_icon) || $this->icon_uses_fontawesome($close_icon));

        global $post;

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>" data-oneplugin2-faq data-columns="<?php echo esc_attr((string) $columns); ?>" data-rows="<?php echo esc_attr((string) $rows); ?>">
            <?php foreach ($faq_columns as $column_index => $column_posts) : ?>
                <div class="oneplugin2-faq__column" data-oneplugin2-faq-column="<?php echo esc_attr((string) ($column_index + 1)); ?>">
                    <?php foreach ($column_posts as $column_item) : ?>
                        <?php
                        $faq_post = $column_item['post'];
                        $index = $column_item['index'];
                        $post = $faq_post;
                        setup_postdata($post);
                        $question = get_the_title($faq_post);
                        $answer_html = apply_filters('the_content', $faq_post->post_content);
                        $panel_id = $instance_id . '-panel-' . $index;
                        $button_id = $instance_id . '-button-' . $index;
                        $is_open = $open_first && $index === 0;
                        ?>
                        <article class="oneplugin2-faq__item" style="--oneplugin-faq-index: <?php echo esc_attr((string) $index); ?>;">
                            <div class="oneplugin2-faq__heading">
                                <button
                                    type="button"
                                    id="<?php echo esc_attr($button_id); ?>"
                                    class="oneplugin2-faq__trigger"
                                    data-accordion-mode="<?php echo esc_attr($settings['faq_accordion_mode']); ?>"
                                    aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
                                    aria-controls="<?php echo esc_attr($panel_id); ?>"
                                >
                                    <span class="oneplugin2-faq__question"><?php echo esc_html($question); ?></span>
                                    <span class="oneplugin2-faq__icon" aria-hidden="true">
                                        <?php echo $this->render_icon_state('open', $open_icon, $is_open); ?>
                                        <?php echo $this->render_icon_state('close', $close_icon, !$is_open); ?>
                                    </span>
                                </button>
                            </div>
                            <div
                                id="<?php echo esc_attr($panel_id); ?>"
                                class="oneplugin2-faq__panel"
                                data-animation="<?php echo esc_attr($settings['faq_animation']); ?>"
                                data-duration="<?php echo esc_attr((string) $settings['faq_animation_duration']); ?>"
                                role="region"
                                aria-labelledby="<?php echo esc_attr($button_id); ?>"
                                <?php echo $is_open ? '' : 'hidden'; ?>
                            >
                                <div class="oneplugin2-faq__answer">
                                    <?php echo wp_kses_post($answer_html); ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php

        if (!empty($schema_items)) {
            $schema_json = wp_json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $schema_items,
            ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

            if (is_string($schema_json) && $schema_json !== '') {
                printf(
                    '<script type="application/ld+json">%s</script>',
                    $schema_json
                );
            }
        }

        wp_reset_postdata();

        return (string) ob_get_clean();
    }

    private function split_posts_into_columns($posts, $columns) {
        $columns = $this->sanitize_columns($columns);
        $split_posts = array_fill(0, $columns, []);

        foreach (array_values($posts) as $index => $faq_post) {
            $column_index = $columns > 1 ? $index % $columns : 0;
            $split_posts[$column_index][] = [
                'index' => $index,
                'post' => $faq_post,
            ];
        }

        return array_values(array_filter($split_posts));
    }

    private function sanitize_limit($value) {
        $value = trim((string) $value);
        if ($value === '-1' || $value === '') {
            return -1;
        }

        $limit = intval($value);
        return $limit > 0 ? $limit : -1;
    }

    private function sanitize_columns($value) {
        $columns = absint($value);

        if ($columns < 1) {
            return 1;
        }

        return min($columns, 3);
    }

    private function sanitize_rows($value) {
        $rows = absint($value);

        return $rows > 0 ? min($rows, 50) : 0;
    }

    private function resolve_query_limit($limit, $columns, $rows) {
        $row_limit = $rows > 0 ? $columns * $rows : -1;

        if ($limit > 0 && $row_limit > 0) {
            return min($limit, $row_limit);
        }

        if ($row_limit > 0) {
            return $row_limit;
        }

        return $limit;
    }

    private function sanitize_orderby($value) {
        $orderby = sanitize_key((string) $value);

        if (in_array($orderby, ['date', 'menu_order', 'rand'], true)) {
            return $orderby;
        }

        return 'menu_order';
    }

    private function sanitize_order($value) {
        $order = strtoupper(sanitize_key((string) $value));

        return in_array($order, ['ASC', 'DESC'], true) ? $order : 'ASC';
    }

    private function sanitize_group_slugs($value) {
        if (is_array($value)) {
            $raw_groups = $value;
        } else {
            $raw_groups = preg_split('/[\s,]+/', (string) $value);
        }

        if (!is_array($raw_groups)) {
            return [];
        }

        $groups = [];
        foreach ($raw_groups as $group) {
            $group = sanitize_title((string) $group);
            if ($group !== '') {
                $groups[] = $group;
            }
        }

        return array_values(array_unique($groups));
    }

    private function enqueue_assets($settings, $needs_fontawesome = false) {
        if ($needs_fontawesome && !$this->fontawesome_enqueued) {
            wp_enqueue_style(
                'oneplugin2-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                [],
                '6.5.1'
            );
            $this->fontawesome_enqueued = true;
        }

        if ($this->assets_enqueued) {
            return;
        }

        wp_enqueue_style(
            'oneplugin-light-faq',
            ONEPLUGIN_LIGHT_URL . 'assets/css/faq.css',
            [],
            ONEPLUGIN_LIGHT_VERSION
        );
        wp_enqueue_script(
            'oneplugin-light-faq',
            ONEPLUGIN_LIGHT_URL . 'assets/js/faq.js',
            [],
            ONEPLUGIN_LIGHT_VERSION,
            true
        );

        $this->assets_enqueued = true;
    }

    private function merge_shortcode_settings($settings, $atts) {
        $map = [
            'open_icon' => 'faq_open_icon',
            'close_icon' => 'faq_close_icon',
            'use_same_icon' => 'faq_use_same_icon',
            'animation' => 'faq_animation',
            'animation_duration' => 'faq_animation_duration',
            'accordion_mode' => 'faq_accordion_mode',
        ];

        foreach ($map as $attr_key => $setting_key) {
            if (!isset($atts[$attr_key]) || (string) $atts[$attr_key] === '') {
                continue;
            }

            $settings[$setting_key] = (string) $atts[$attr_key];
        }

        $settings['faq_animation_duration'] = $this->sanitize_number($settings['faq_animation_duration'], 220, 0, 2000);
        $settings['faq_animation'] = in_array($settings['faq_animation'], ['none', 'slide'], true) ? $settings['faq_animation'] : 'slide';
        $settings['faq_accordion_mode'] = in_array($settings['faq_accordion_mode'], ['single', 'independent'], true) ? $settings['faq_accordion_mode'] : 'single';
        $settings['faq_use_same_icon'] = $settings['faq_use_same_icon'] === '1' || $settings['faq_use_same_icon'] === 'true' || $settings['faq_use_same_icon'] === 'on' ? '1' : '0';

        return $settings;
    }

    private function get_settings() {
        $settings = $this->defaults;

        $settings['faq_animation_duration'] = $this->sanitize_number($settings['faq_animation_duration'], 220, 0, 2000);
        $settings['faq_animation'] = in_array($settings['faq_animation'], ['none', 'slide'], true) ? $settings['faq_animation'] : 'slide';
        $settings['faq_accordion_mode'] = in_array($settings['faq_accordion_mode'], ['single', 'independent'], true) ? $settings['faq_accordion_mode'] : 'single';
        $settings['faq_use_same_icon'] = $settings['faq_use_same_icon'] === '1' ? '1' : '0';

        return $settings;
    }

    private function render_icon_state($state, $icon_value, $hidden) {
        $state = $state === 'close' ? 'close' : 'open';
        $classes = [
            'oneplugin2-faq__icon-state',
            'oneplugin2-faq__icon-state--' . $state,
        ];

        return sprintf(
            '<span class="%1$s" data-oneplugin2-faq-icon-state="%2$s" aria-hidden="true"%3$s>%4$s</span>',
            esc_attr(implode(' ', $classes)),
            esc_attr($state),
            $hidden ? ' hidden' : '',
            $this->render_icon_inner($icon_value)
        );
    }

    private function normalize_icon_value($value, $fallback) {
        if (is_array($value)) {
            return $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return $fallback;
        }

        if ($value[0] === '{' || $value[0] === '[') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $value;
    }

    private function render_icon_inner($icon_value) {
        if (is_array($icon_value)) {
            return $this->render_divi_icon_inner($icon_value);
        }

        $icon_value = trim((string) $icon_value);
        if ($icon_value === '') {
            return '';
        }

        if ($this->looks_like_icon_class($icon_value)) {
            $icon_class = $this->sanitize_icon_class($icon_value);
            if ($icon_class !== '') {
                return '<i class="' . esc_attr($icon_class) . '" aria-hidden="true"></i>';
            }
        }

        return '<span class="oneplugin2-faq__icon-text">' . esc_html($icon_value) . '</span>';
    }

    private function render_divi_icon_inner($icon_value) {
        $glyph = '';
        if (class_exists('\ET\Builder\Packages\IconLibrary\IconFont\Utils')) {
            $glyph = \ET\Builder\Packages\IconLibrary\IconFont\Utils::process_font_icon($icon_value);
        }

        if ($glyph === '' && isset($icon_value['unicode']) && is_scalar($icon_value['unicode'])) {
            $glyph = html_entity_decode((string) $icon_value['unicode'], ENT_QUOTES, 'UTF-8');
        }

        if ($glyph === '') {
            return '';
        }

        $font_family = isset($icon_value['type']) && $icon_value['type'] === 'fa' ? 'FontAwesome' : 'ETmodules';

        return sprintf(
            '<span class="oneplugin2-faq__icon-glyph et-pb-icon" style="%1$s">%2$s</span>',
            esc_attr('font-family:' . $font_family . ' !important;'),
            esc_html($glyph)
        );
    }

    private function icon_uses_fontawesome($icon_value) {
        if (is_array($icon_value)) {
            return isset($icon_value['type']) && $icon_value['type'] === 'fa';
        }

        return $this->looks_like_icon_class((string) $icon_value) && strpos((string) $icon_value, 'fa-') !== false;
    }

    private function looks_like_icon_class($value) {
        $value = trim((string) $value);

        return $value !== '' && preg_match('/(^|\s)(fa-|fa[srldb]?|fa-solid|fa-regular|fa-brands|et-pb-icon)(\s|$)/', $value) === 1;
    }

    private function sanitize_number($value, $default, $min, $max) {
        $number = absint($value);
        if ($number < $min || $number > $max) {
            return (string) $default;
        }

        return (string) $number;
    }

    private function sanitize_icon_class($value) {
        $classes = preg_split('/\s+/', trim((string) $value));
        $classes = array_filter(array_map('sanitize_html_class', is_array($classes) ? $classes : []));

        return implode(' ', $classes);
    }

    private function sanitize_class_names($value) {
        $classes = preg_split('/\s+/', $value);
        $classes = array_filter(array_map('sanitize_html_class', is_array($classes) ? $classes : []));

        return implode(' ', $classes);
    }
}
