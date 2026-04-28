<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Site_Tools {
    const VERSION = ONEPLUGIN_LIGHT_VERSION;
    const API_VERSION = '1';
    const OPTION_KEY = 'oneplugin_light_site_tools_settings';
    const SITE_UUID_OPTION_KEY = 'oneplugin_light_site_tools_uuid';
    const MENU_SLUG = 'oneplugin-light-site-tools';
    const LEGACY_OPTION_KEY = 'oneplugin_light_site_tools_settings_legacy';
    const LEGACY_SITE_UUID_OPTION_KEY = 'oneplugin_light_site_tools_uuid_legacy';

    private static $instance = null;
    private $menu_module = null;

    private $defaults = [
        'company_name' => '',
        'street_address' => '',
        'postal_code' => '',
        'city' => '',
        'phone_primary' => '',
        'organization_number' => '',
        'email' => '',
        'form_email' => '',
        'site_title' => '',
        'site_icon_id' => 0,
        'site_logo_id' => 0,
        'website' => '',
        'facebook_url' => '',
        'instagram_url' => '',
        'linkedin_url' => '',
        'youtube_url' => '',
        'x_url' => '',
        'reddit_url' => '',
        'booking_url' => '',
        'sticky_enabled' => '1',
        'sticky_bg_color' => '#0f0f0f',
        'sticky_icon_color' => '#ffffff',
        'sticky_text_color' => '#ffffff',
        'sticky_social_media' => 'none',
        'hide_image_alt_text' => '0',
        'fix_image_alt_text' => '0',
        'header_glass_effect' => '0',
        'cover_images' => '0',
        'apply_cover_to_tabs_image' => '0',
        'masonry_gallery_enabled' => '0',
        'masonry_gallery_layout' => 'square',
        'active_menu_item_by_section' => '0',
        'hide_default_footer' => '0',
        'balance_advanced_tabs_items' => '0',
        'style_formidable' => '1',
        'formidable_accent_color' => '#fa1e9a',
        'project_palette' => [],
        'custom_code_css' => '',
        'custom_code_js' => '',
        'custom_code_php_head' => '',
        'custom_code_php_body' => '',
        'custom_code_php_footer' => '',
    ];

    private $legacy_sources = [
        'company_name' => ['tcx_company', 'custom_foretag'],
        'street_address' => ['tcx_adress', 'custom_gata'],
        'postal_code' => ['tcx_postnr', 'custom_postkod'],
        'city' => ['tcx_ort', 'custom_ort'],
        'phone_primary' => ['tcx_telefon', 'custom_mobil1'],
        'organization_number' => ['custom_orgnr'],
        'email' => ['tcx_email', 'custom_mail'],
        'form_email' => ['tcx_epost'],
        'site_title' => [],
        'site_icon_id' => [],
        'site_logo_id' => [],
        'website' => ['tcx_web'],
        'facebook_url' => ['tcx_fb'],
        'instagram_url' => ['tcx_ig'],
        'linkedin_url' => [],
        'youtube_url' => [],
        'x_url' => [],
        'reddit_url' => [],
        'booking_url' => ['tcx_bd'],
        'sticky_bg_color' => ['mobile_footer_bg_color'],
        'sticky_icon_color' => ['mobile_footer_icon_color'],
        'sticky_text_color' => ['mobile_footer_text_color'],
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_post_oneplugin_light_export_settings', [$this, 'handle_export_settings']);
        add_action('admin_post_oneplugin_light_import_settings', [$this, 'handle_import_settings']);
        add_action('update_option_' . self::OPTION_KEY, [$this, 'mirror_legacy_option'], 10, 2);
        add_action('add_option_' . self::OPTION_KEY, [$this, 'mirror_legacy_option_on_add'], 10, 2);
        add_action('init', [$this, 'enable_shortcodes_in_divi_modules']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_head', [$this, 'render_custom_php_head'], 1);
        add_action('wp_head', [$this, 'render_custom_css'], 100);
        add_action('wp_body_open', [$this, 'render_custom_php_body'], 1);
        add_action('wp_footer', [$this, 'render_custom_php_footer'], 20);
        add_action('wp_footer', [$this, 'render_custom_js'], 100);
        add_action('wp_footer', [$this, 'render_mobile_footer'], 9999);
        add_filter('wp_get_attachment_image_attributes', [$this, 'filter_attachment_image_alt'], 20, 2);
        add_filter('the_content', [$this, 'replace_image_alt_in_html'], 20);
        add_filter('post_thumbnail_html', [$this, 'replace_image_alt_in_html'], 20);

        $this->menu_module = OnePlugin_Light_Menu_Module::instance();
        $this->menu_module->init();
        OnePlugin_Light_Shortcodes::instance(function ($key, $default = '') {
            return $this->get_setting($key, $default);
        })->init();
        OnePlugin_Light_Divi_Compatibility::instance()->init();
        OnePlugin_Light_Keyword_Meta::instance()->init();

        if (!class_exists('DBDSE_EnableShortcodesInModuleFields')) {
            $divi_shortcode_support = new OnePlugin_Light_Divi_Shortcode_Support();
            $divi_shortcode_support->init();
        }
    }

    public static function activate() {
        $instance = self::instance();
        $instance->migrate_legacy_settings();
        $instance->ensure_site_uuid();
    }

    public function register_admin_menu() {
        add_menu_page(
            __('1Plugin', 'oneplugin-light-site-tools'),
            __('1Plugin', 'oneplugin-light-site-tools'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page'],
            'dashicons-editor-ol',
            58
        );
    }

    public function register_settings() {
        register_setting(
            'oneplugin_light_site_tools_group',
            self::OPTION_KEY,
            [$this, 'sanitize_settings']
        );
    }

    public function enqueue_admin_assets($hook_suffix) {
        $allowed_hooks = [
            'toplevel_page_' . self::MENU_SLUG,
        ];

        if (!in_array($hook_suffix, $allowed_hooks, true)) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style(
            'oneplugin2-fontawesome-admin',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            [],
            '6.5.1'
        );
        wp_enqueue_style(
            'oneplugin-light-admin',
            ONEPLUGIN_LIGHT_URL . 'assets/css/admin.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'oneplugin2-admin-preview',
            ONEPLUGIN_LIGHT_URL . 'assets/js/admin.js',
            [],
            self::VERSION,
            true
        );
        wp_localize_script('oneplugin2-admin-preview', 'OnePluginLightAdmin', $this->get_admin_script_config());
    }

    public function sanitize_settings($input) {
        $output = $this->defaults;
        $input = is_array($input) ? $input : [];

        $text_fields = [
            'company_name',
            'street_address',
            'postal_code',
            'city',
            'phone_primary',
            'organization_number',
            'form_email',
            'site_title',
        ];

        $url_fields = [
            'website',
            'facebook_url',
            'instagram_url',
            'linkedin_url',
            'youtube_url',
            'x_url',
            'reddit_url',
            'booking_url',
        ];

        $color_fields = [
            'sticky_bg_color',
            'sticky_icon_color',
            'sticky_text_color',
            'formidable_accent_color',
        ];

        foreach ($text_fields as $field) {
            $output[$field] = isset($input[$field]) ? sanitize_text_field($input[$field]) : '';
        }

        foreach ($url_fields as $field) {
            $output[$field] = isset($input[$field]) ? esc_url_raw($input[$field]) : '';
        }

        foreach ($color_fields as $field) {
            $value = isset($input[$field]) ? trim((string) $input[$field]) : $this->defaults[$field];
            $output[$field] = $this->sanitize_color_value($value, $this->defaults[$field]);
        }

        $output['email'] = isset($input['email']) ? sanitize_email($input['email']) : '';
        $output['site_icon_id'] = isset($input['site_icon_id']) ? absint($input['site_icon_id']) : 0;
        $output['site_logo_id'] = isset($input['site_logo_id']) ? absint($input['site_logo_id']) : 0;
        $output['sticky_enabled'] = !empty($input['sticky_enabled']) ? '1' : '0';
        $output['sticky_social_media'] = isset($input['sticky_social_media']) ? sanitize_key($input['sticky_social_media']) : 'none';
        $output['hide_image_alt_text'] = !empty($input['hide_image_alt_text']) ? '1' : '0';
        $output['fix_image_alt_text'] = !empty($input['fix_image_alt_text']) ? '1' : '0';
        $output['header_glass_effect'] = !empty($input['header_glass_effect']) ? '1' : '0';
        $output['cover_images'] = !empty($input['cover_images']) ? '1' : '0';
        $output['apply_cover_to_tabs_image'] = !empty($input['apply_cover_to_tabs_image']) ? '1' : '0';
        $output['masonry_gallery_enabled'] = !empty($input['masonry_gallery_enabled']) ? '1' : '0';
        $output['masonry_gallery_layout'] = isset($input['masonry_gallery_layout']) ? sanitize_key($input['masonry_gallery_layout']) : 'square';
        if (!in_array($output['masonry_gallery_layout'], ['square', 'asymetric'], true)) {
            $output['masonry_gallery_layout'] = 'square';
        }
        $output['active_menu_item_by_section'] = !empty($input['active_menu_item_by_section']) ? '1' : '0';
        $output['hide_default_footer'] = !empty($input['hide_default_footer']) ? '1' : '0';
        $output['balance_advanced_tabs_items'] = !empty($input['balance_advanced_tabs_items']) ? '1' : '0';
        $output['style_formidable'] = !empty($input['style_formidable']) ? '1' : '0';
        $output['project_palette'] = $this->sanitize_project_palette(isset($input['project_palette']) ? $input['project_palette'] : []);
        $output['custom_code_css'] = $this->sanitize_code_snippet(isset($input['custom_code_css']) ? $input['custom_code_css'] : '');
        $output['custom_code_js'] = $this->sanitize_code_snippet(isset($input['custom_code_js']) ? $input['custom_code_js'] : '');
        $output['custom_code_php_head'] = $this->sanitize_code_snippet(isset($input['custom_code_php_head']) ? $input['custom_code_php_head'] : '');
        $output['custom_code_php_body'] = $this->sanitize_code_snippet(isset($input['custom_code_php_body']) ? $input['custom_code_php_body'] : '');
        $output['custom_code_php_footer'] = $this->sanitize_code_snippet(isset($input['custom_code_php_footer']) ? $input['custom_code_php_footer'] : '');

        $available_choices = $this->get_available_social_media_choices($input);
        if (!array_key_exists($output['sticky_social_media'], $available_choices)) {
            $output['sticky_social_media'] = $this->get_preferred_sticky_social_media($input);
        }

        update_option('blogname', $output['site_title']);
        update_option('site_icon', $output['site_icon_id']);
        set_theme_mod('custom_logo', $output['site_logo_id']);
        $this->sync_divi_logo($output['site_logo_id']);

        return $output;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->get_settings();
        $social_media_choices = $this->get_available_social_media_choices($settings);
        $import_status = isset($_GET['oneplugin2_import']) ? sanitize_text_field(wp_unslash($_GET['oneplugin2_import'])) : '';
        ?>
        <div class="wrap">
            <div class="oneplugin-admin-shell">
                <div class="oneplugin-admin-hero">
                    <div>
                        <h1>
                            <?php esc_html_e('1Plugin', 'oneplugin-light-site-tools'); ?>
                            <span><?php echo esc_html('v' . self::VERSION); ?></span>
                        </h1>
                    </div>
                    <div class="oneplugin-admin-hero__actions">
                        <a class="button button-secondary oneplugin-hero-button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=oneplugin_light_export_settings'), 'oneplugin_light_export_settings')); ?>">
                            <?php esc_html_e('Export', 'oneplugin-light-site-tools'); ?>
                        </a>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" class="oneplugin-hero-import-form">
                            <input type="hidden" name="action" value="oneplugin_light_import_settings" />
                            <?php wp_nonce_field('oneplugin_light_import_settings'); ?>
                            <input type="file" name="oneplugin_light_import_file" id="oneplugin-light-import-file" accept=".json,application/json" hidden />
                            <button type="button" class="button button-secondary oneplugin-hero-button" id="oneplugin-light-import-button">
                                <?php esc_html_e('Import', 'oneplugin-light-site-tools'); ?>
                            </button>
                        </form>
                    </div>
                </div>

            <?php if ($import_status === 'success') : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings imported successfully.', 'oneplugin-light-site-tools'); ?></p></div>
            <?php elseif ($import_status === 'error') : ?>
                <div class="notice notice-error is-dismissible"><p><?php esc_html_e('Import failed. Please use a valid JSON export from this plugin.', 'oneplugin-light-site-tools'); ?></p></div>
            <?php endif; ?>

            <form method="post" action="options.php" class="oneplugin-admin-form">
                <?php settings_fields('oneplugin_light_site_tools_group'); ?>

                <div class="postbox oneplugin-card oneplugin-card--identity">
                    <div class="inside oneplugin-card__inside">
                        <h2><?php esc_html_e('Project Identity', 'oneplugin-light-site-tools'); ?></h2>
                        <div class="oneplugin-project-identity">
                            <div class="oneplugin-project-identity__top">
                                <?php
                                $this->render_site_icon_field($settings);
                                $this->render_site_logo_field($settings);
                                $this->render_compact_field('site_title', __('Site title', 'oneplugin-light-site-tools'), $settings);
                                ?>
                            </div>
                            <div class="oneplugin-project-identity__details">
                                <div class="oneplugin-project-field-grid">
                                    <?php
                                    $this->render_compact_field('company_name', __('Foretagsnamn', 'oneplugin-light-site-tools'), $settings);
                                    $this->render_compact_field('organization_number', __('Organisationsnummer', 'oneplugin-light-site-tools'), $settings);
                                    $this->render_compact_field('website', __('Hemsida', 'oneplugin-light-site-tools'), $settings, 'url');
                                    $this->render_compact_field('street_address', __('Adress', 'oneplugin-light-site-tools'), $settings);
                                    $this->render_compact_field('postal_code', __('Postnummer', 'oneplugin-light-site-tools'), $settings);
                                    $this->render_compact_field('city', __('Ort', 'oneplugin-light-site-tools'), $settings);
                                    $this->render_compact_field('phone_primary', __('Telefon', 'oneplugin-light-site-tools'), $settings);
                                    $this->render_compact_field('email', __('E-post', 'oneplugin-light-site-tools'), $settings, 'email');
                                    $this->render_compact_field('form_email', __('Mail formular', 'oneplugin-light-site-tools'), $settings);
                                    ?>
                                </div>
                            </div>
                            <div class="oneplugin-project-identity__social">
                                <?php $this->render_social_links_section($settings); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="oneplugin-admin-two-column">
                    <div class="postbox oneplugin-card">
                        <div class="inside oneplugin-card__inside">
                            <h2><?php esc_html_e('Image & Layout Tweaks', 'oneplugin-light-site-tools'); ?></h2>
                            <div class="oneplugin-option-grid">
                            <?php
                            $this->render_compact_checkbox_field('hide_image_alt_text', __('Hide Image Alt-text', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('fix_image_alt_text', __('Fix Image Alt-text', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('header_glass_effect', __('Header glass effect', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('cover_images', __('Cover images (.cover-img)', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('apply_cover_to_tabs_image', __('Apply cover to tabs image', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('masonry_gallery_enabled', __('Masonry Gallery layout', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('active_menu_item_by_section', __('Active menu item by section', 'oneplugin-light-site-tools'), $settings);
                            ?>
                            <div id="oneplugin-masonry-gallery-layout-wrap" class="oneplugin-option-grid__item oneplugin-option-grid__item--select" <?php echo empty($settings['masonry_gallery_enabled']) ? 'hidden' : ''; ?>>
                                <?php
                                $this->render_compact_select_field('masonry_gallery_layout', __('Masonry layout type', 'oneplugin-light-site-tools'), $settings, [
                                    'square' => __('Square', 'oneplugin-light-site-tools'),
                                    'asymetric' => __('Asymetric', 'oneplugin-light-site-tools'),
                                ]);
                                ?>
                            </div>
                            <?php
                            $this->render_compact_checkbox_field('hide_default_footer', __('Hide default footer', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('balance_advanced_tabs_items', __('Balance AdvancedTabs Items', 'oneplugin-light-site-tools'), $settings);
                            $this->render_compact_checkbox_field('style_formidable', __('Style Formidable', 'oneplugin-light-site-tools'), $settings);
                            ?>
                            <div id="oneplugin-formidable-accent-wrap" class="oneplugin-option-grid__item oneplugin-option-grid__item--color oneplugin-option-grid__item--full" <?php echo empty($settings['style_formidable']) ? 'hidden' : ''; ?>>
                                <?php $this->render_compact_color_field('formidable_accent_color', __('Formidable accent color', 'oneplugin-light-site-tools'), $settings); ?>
                            </div>
                            </div>
                        </div>
                    </div>

                    <div class="postbox oneplugin-card oneplugin-card--footer">
                        <div class="inside oneplugin-card__inside">
                            <div class="oneplugin-sticky-header">
                                <div class="oneplugin-sticky-title-row">
                                    <h2><?php esc_html_e('Sticky Mobile Footer', 'oneplugin-light-site-tools'); ?></h2>
                                    <label class="oneplugin-collapsible__switch" for="sticky_enabled">
                                        <input
                                            name="<?php echo esc_attr(self::OPTION_KEY . '[sticky_enabled]'); ?>"
                                            id="sticky_enabled"
                                            type="checkbox"
                                            value="1"
                                            <?php checked(!empty($settings['sticky_enabled'])); ?>
                                        />
                                        <span><?php esc_html_e('Enabled', 'oneplugin-light-site-tools'); ?></span>
                                    </label>
                                </div>
                                <?php $this->render_admin_footer_preview($settings); ?>
                            </div>
                            <div class="oneplugin-sticky-fields">
                                <?php
                                $this->render_compact_select_field('sticky_social_media', __('Social media', 'oneplugin-light-site-tools'), $settings, $social_media_choices);
                                $this->render_compact_color_field('sticky_bg_color', __('Bakgrundsfarg', 'oneplugin-light-site-tools'), $settings);
                                $this->render_compact_color_field('sticky_icon_color', __('Ikonfarg', 'oneplugin-light-site-tools'), $settings);
                                $this->render_compact_color_field('sticky_text_color', __('Textfarg', 'oneplugin-light-site-tools'), $settings);
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="postbox oneplugin-card">
                    <div class="inside oneplugin-card__inside">
                        <h2><?php esc_html_e('Custom Code', 'oneplugin-light-site-tools'); ?></h2>
                        <div class="oneplugin-tabs oneplugin-tabs--sidebar" data-tabs>
                            <div class="oneplugin-tabs__nav" role="tablist" aria-label="<?php esc_attr_e('Custom code tabs', 'oneplugin-light-site-tools'); ?>">
                                <button type="button" class="oneplugin-tabs__tab is-active" data-tab-trigger="custom-code-css" role="tab" aria-selected="true"><?php esc_html_e('CSS', 'oneplugin-light-site-tools'); ?></button>
                                <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="custom-code-js" role="tab" aria-selected="false"><?php esc_html_e('JavaScript', 'oneplugin-light-site-tools'); ?></button>
                                <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="custom-code-php-head" role="tab" aria-selected="false"><?php esc_html_e('PHP Head', 'oneplugin-light-site-tools'); ?></button>
                                <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="custom-code-php-body" role="tab" aria-selected="false"><?php esc_html_e('PHP Body', 'oneplugin-light-site-tools'); ?></button>
                                <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="custom-code-php-footer" role="tab" aria-selected="false"><?php esc_html_e('PHP Footer', 'oneplugin-light-site-tools'); ?></button>
                            </div>
                            <div class="oneplugin-tabs__panels">
                                <div class="oneplugin-tabs__panel is-active" data-tab-panel="custom-code-css" role="tabpanel">
                                    <?php $this->render_compact_textarea_field('custom_code_css', '', $settings, 14); ?>
                                </div>
                                <div class="oneplugin-tabs__panel" data-tab-panel="custom-code-js" role="tabpanel" hidden>
                                    <?php $this->render_compact_textarea_field('custom_code_js', '', $settings, 14); ?>
                                </div>
                                <div class="oneplugin-tabs__panel" data-tab-panel="custom-code-php-head" role="tabpanel" hidden>
                                    <?php $this->render_compact_textarea_field('custom_code_php_head', '', $settings, 14); ?>
                                </div>
                                <div class="oneplugin-tabs__panel" data-tab-panel="custom-code-php-body" role="tabpanel" hidden>
                                    <?php $this->render_compact_textarea_field('custom_code_php_body', '', $settings, 14); ?>
                                </div>
                                <div class="oneplugin-tabs__panel" data-tab-panel="custom-code-php-footer" role="tabpanel" hidden>
                                    <?php $this->render_compact_textarea_field('custom_code_php_footer', '', $settings, 14); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="postbox oneplugin-card oneplugin-collapsible">
                    <div class="inside oneplugin-card__inside">
                        <div class="oneplugin-collapsible__header">
                            <button type="button" class="oneplugin-collapsible__toggle" data-collapsible-toggle aria-expanded="false">
                                <span><?php esc_html_e('Help', 'oneplugin-light-site-tools'); ?></span>
                                <span class="oneplugin-collapsible__chevron" aria-hidden="true"></span>
                            </button>
                        </div>
                        <div class="oneplugin-collapsible__content" data-collapsible-content hidden>
                            <p><?php esc_html_e('Click any shortcode or helper class to copy it.', 'oneplugin-light-site-tools'); ?></p>
                            <h3><?php esc_html_e('Shortcodes', 'oneplugin-light-site-tools'); ?></h3>
                            <div class="oneplugin-shortcodes">
                                <?php
                                $shortcodes = [
                                    '[foretag]', '[gata]', '[postkod]', '[ort]', '[mobil1]', '[orgnr]', '[mail]',
                                    '[kontakt]', '[formular id="123"]', '[kundens_mail]', '[kundens_epost]',
                                    '[kundens_foretag]', '[kundens_adress]', '[kundens_telefon]', '[karta]',
                                    '[hemsida]', '[kundens_hemsida]', '[kundens_facebook]', '[kundens_instagram]',
                                    '[kundens_linkedin]', '[kundens_youtube]', '[kundens_x]', '[kundens_reddit]',
                                    '[kundens_bokadirekt]',
                                    '[sokordets_tjanst_rubrik]', '[sokordets_ort_rubrik]',
                                    '[sokordets_tjanst_brodtext]', '[sokordets_ort_brodtext]',
                                ];
                                foreach ($shortcodes as $shortcode) {
                                    echo '<button type="button" class="oneplugin-shortcode-copy" data-shortcode="' . esc_attr($shortcode) . '">' . esc_html($shortcode) . '</button> ';
                                }
                                ?>
                            </div>
                            <h3 style="margin-top:22px;"><?php esc_html_e('Helper Classes', 'oneplugin-light-site-tools'); ?></h3>
                            <div class="oneplugin-shortcodes">
                                <button type="button" class="oneplugin-shortcode-copy" data-shortcode=".cover-img">.cover-img</button>
                            </div>
                            <p style="margin-top:10px;"><?php esc_html_e('Use .cover-img on an image module to make the image cover the full available space without distortion. When "Apply cover to tabs image" is enabled, this class is added automatically to img.dipi-at-panel-image.', 'oneplugin-light-site-tools'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="oneplugin-savebar">
                    <div class="oneplugin-savebar__inner">
                        <span class="oneplugin-savebar__hint"><?php esc_html_e('Press Ctrl+S to save changes quickly.', 'oneplugin-light-site-tools'); ?></span>
                        <?php submit_button(__('Save Changes', 'oneplugin-light-site-tools'), 'primary', 'submit', false, ['id' => 'oneplugin2-save-button']); ?>
                    </div>
                </div>
            </form>
            </div>
        </div>
        <?php
    }

    private function render_text_row($key, $label, $settings, $type = 'text') {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <input
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    type="<?php echo esc_attr($type); ?>"
                    class="regular-text"
                    value="<?php echo esc_attr($value); ?>"
                />
            </td>
        </tr>
        <?php
    }

    private function render_compact_field($key, $label, $settings, $type = 'text') {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <div style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <input
                name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                id="<?php echo esc_attr($key); ?>"
                type="<?php echo esc_attr($type); ?>"
                class="regular-text"
                style="width:100%;"
                value="<?php echo esc_attr($value); ?>"
            />
        </div>
        <?php
    }

    private function render_social_links_section($settings) {
        $social_fields = $this->get_social_link_fields();
        ?>
        <div class="oneplugin-social-panel">
            <div class="oneplugin-social-panel__header">
                <h3><?php esc_html_e('Social Links', 'oneplugin-light-site-tools'); ?></h3>
                <select id="oneplugin-add-social-link" class="oneplugin-social-panel__add" aria-label="<?php esc_attr_e('Add social link', 'oneplugin-light-site-tools'); ?>">
                    <option value=""><?php esc_html_e('Add social link', 'oneplugin-light-site-tools'); ?></option>
                    <?php foreach ($social_fields as $key => $label) : ?>
                        <?php if (empty($settings[$key])) : ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="oneplugin-social-fields">
                <?php foreach ($social_fields as $key => $label) : ?>
                    <?php
                    $value = isset($settings[$key]) ? $settings[$key] : '';
                    $is_visible = $value !== '';
                    ?>
                    <div class="oneplugin-social-field" data-social-field="<?php echo esc_attr($key); ?>" <?php echo $is_visible ? '' : 'hidden'; ?>>
                        <?php $this->render_compact_field($key, $label, $settings, 'url'); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function get_social_link_fields() {
        return [
            'facebook_url' => __('Facebook', 'oneplugin-light-site-tools'),
            'instagram_url' => __('Instagram', 'oneplugin-light-site-tools'),
            'linkedin_url' => __('LinkedIn', 'oneplugin-light-site-tools'),
            'youtube_url' => __('YouTube', 'oneplugin-light-site-tools'),
            'x_url' => __('X', 'oneplugin-light-site-tools'),
            'reddit_url' => __('Reddit', 'oneplugin-light-site-tools'),
            'booking_url' => __('BokaDirekt', 'oneplugin-light-site-tools'),
        ];
    }

    private function render_compact_textarea_field($key, $label, $settings, $rows = 10, $description = '') {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <div style="margin-bottom:14px;">
            <?php if ($label !== '') : ?>
                <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <?php endif; ?>
            <?php if ($description !== '') : ?>
                <p style="margin:0 0 8px; color:#50575e;"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            <textarea
                name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                id="<?php echo esc_attr($key); ?>"
                class="large-text code"
                rows="<?php echo esc_attr((string) $rows); ?>"
                spellcheck="false"
                style="width:100%; font-family:Consolas, Monaco, monospace;"
            ><?php echo esc_textarea($value); ?></textarea>
        </div>
        <?php
    }

    private function render_textarea_row($key, $label, $settings, $rows = 6) {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <textarea
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    class="large-text"
                    rows="<?php echo esc_attr($rows); ?>"
                ><?php echo esc_textarea($value); ?></textarea>
            </td>
        </tr>
        <?php
    }

    private function render_checkbox_row($key, $label, $settings) {
        $checked = !empty($settings[$key]);
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <label for="<?php echo esc_attr($key); ?>">
                    <input
                        name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                        id="<?php echo esc_attr($key); ?>"
                        type="checkbox"
                        value="1"
                        <?php checked($checked); ?>
                    />
                    <?php esc_html_e('Enabled', 'oneplugin-light-site-tools'); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    private function render_compact_checkbox_field($key, $label, $settings) {
        $checked = !empty($settings[$key]);
        ?>
        <div class="oneplugin-option-grid__item oneplugin-option-grid__item--checkbox" style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <label class="oneplugin-compact-checkbox">
                <input
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    type="checkbox"
                    value="1"
                    <?php checked($checked); ?>
                />
                <?php esc_html_e('Enabled', 'oneplugin-light-site-tools'); ?>
            </label>
        </div>
        <?php
    }

    private function render_select_row($key, $label, $settings, $options) {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
            <td>
                <select
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    class="regular-text"
                >
                    <?php foreach ($options as $option_value => $option_label) : ?>
                        <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>><?php echo esc_html($option_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    private function render_compact_select_field($key, $label, $settings, $options) {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <div class="oneplugin-option-grid__item oneplugin-option-grid__item--select" style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <select
                name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                id="<?php echo esc_attr($key); ?>"
                class="regular-text"
                style="width:100%;"
            >
                <?php foreach ($options as $option_value => $option_label) : ?>
                    <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>><?php echo esc_html($option_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private function render_compact_color_field($key, $label, $settings) {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        $swatch_value = preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value) ? $value : '#000000';
        $css_variable_choices = $this->get_css_variable_color_choices();
        ?>
        <div style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <div style="display:grid; grid-template-columns:32px minmax(0,1fr) minmax(150px,180px); align-items:center; gap:8px;">
                <input
                    type="color"
                    value="<?php echo esc_attr($swatch_value); ?>"
                    oninput="var field=document.getElementById('<?php echo esc_attr($key); ?>'); field.value=this.value; field.dispatchEvent(new Event('input', {bubbles:true}));"
                    style="width:32px; min-width:32px; height:32px; padding:0; border:0; background:none;"
                />
                <input
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    type="text"
                    class="regular-text"
                    style="flex:1;"
                    value="<?php echo esc_attr($value); ?>"
                />
                <select
                    aria-label="<?php echo esc_attr(sprintf(__('CSS variable for %s', 'oneplugin-light-site-tools'), $label)); ?>"
                    onchange="if(this.value){var field=document.getElementById('<?php echo esc_attr($key); ?>'); field.value=this.value; field.dispatchEvent(new Event('input', {bubbles:true}));}"
                    style="width:100%;"
                >
                    <option value=""><?php esc_html_e('CSS variable', 'oneplugin-light-site-tools'); ?></option>
                    <?php foreach ($css_variable_choices as $variable) : ?>
                        <option value="<?php echo esc_attr('var(' . $variable . ')'); ?>" <?php selected($value, 'var(' . $variable . ')'); ?>><?php echo esc_html($variable); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    private function get_css_variable_color_choices() {
        return [
            '--gcid-primary-color',
            '--gcid-secondary-color',
            '--gcid-heading-color',
            '--gcid-link-color',
        ];
    }

    private function render_site_icon_field($settings) {
        $site_icon_id = isset($settings['site_icon_id']) ? absint($settings['site_icon_id']) : 0;
        $site_icon_url = $site_icon_id ? wp_get_attachment_image_url($site_icon_id, 'thumbnail') : '';
        ?>
        <div style="margin-bottom:14px;">
            <label for="site_icon_id" style="display:block; margin-bottom:6px; font-weight:600;"><?php esc_html_e('Favicon', 'oneplugin-light-site-tools'); ?></label>
            <div class="oneplugin-media-field">
                <button type="button" id="oneplugin-site-icon-preview" class="oneplugin-media-field__preview oneplugin-media-field__preview--icon" aria-label="<?php esc_attr_e('Choose or remove favicon', 'oneplugin-light-site-tools'); ?>">
                    <?php if ($site_icon_url) : ?>
                        <img src="<?php echo esc_url($site_icon_url); ?>" alt="" style="max-width:100%; max-height:100%;" />
                    <?php else : ?>
                        <span><?php esc_html_e('Fav', 'oneplugin-light-site-tools'); ?></span>
                    <?php endif; ?>
                </button>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY . '[site_icon_id]'); ?>" id="site_icon_id" value="<?php echo esc_attr($site_icon_id); ?>" />
            </div>
        </div>
        <?php
    }

    private function render_site_logo_field($settings) {
        $site_logo_id = isset($settings['site_logo_id']) ? absint($settings['site_logo_id']) : 0;
        $site_logo_url = $site_logo_id ? wp_get_attachment_image_url($site_logo_id, 'medium') : '';
        ?>
        <div style="margin-bottom:14px;">
            <label for="site_logo_id" style="display:block; margin-bottom:6px; font-weight:600;"><?php esc_html_e('Logo', 'oneplugin-light-site-tools'); ?></label>
            <div class="oneplugin-media-field">
                <button type="button" id="oneplugin-site-logo-preview" class="oneplugin-media-field__preview oneplugin-media-field__preview--logo" aria-label="<?php esc_attr_e('Choose or remove logo', 'oneplugin-light-site-tools'); ?>">
                    <?php if ($site_logo_url) : ?>
                        <img src="<?php echo esc_url($site_logo_url); ?>" alt="" style="max-width:100%; max-height:100%;" />
                    <?php else : ?>
                        <span><?php esc_html_e('Logo', 'oneplugin-light-site-tools'); ?></span>
                    <?php endif; ?>
                </button>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY . '[site_logo_id]'); ?>" id="site_logo_id" value="<?php echo esc_attr($site_logo_id); ?>" />
            </div>
        </div>
        <?php
    }

    private function render_project_palette_swatches() {
        $palette = $this->get_project_color_palette();
        ?>
        <div style="margin-top:18px;">
            <h3 style="margin-bottom:8px;"><?php esc_html_e('Project Color Palette', 'oneplugin-light-site-tools'); ?></h3>
            <?php if (empty($palette)) : ?>
                <p><?php esc_html_e('No project colors detected yet.', 'oneplugin-light-site-tools'); ?></p>
            <?php else : ?>
                <div style="display:flex; flex-wrap:wrap; gap:10px;">
                    <?php foreach ($palette as $swatch) : ?>
                        <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                            <span style="display:block; width:34px; height:34px; border-radius:999px; border:1px solid rgba(15,23,42,.12); background:<?php echo esc_attr($swatch['value']); ?>;"></span>
                            <code style="font-size:11px;"><?php echo esc_html($swatch['value']); ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }



    private function get_social_media_choices() {
        return [
            'none' => 'None',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'x' => 'X',
            'reddit' => 'Reddit',
            'booking' => 'BokaDirekt',
            'website' => 'Website',
        ];
    }

    private function get_available_social_media_choices($settings) {
        $all_choices = $this->get_social_media_choices();
        $available = ['none' => $all_choices['none']];
        $field_map = [
            'facebook' => 'facebook_url',
            'instagram' => 'instagram_url',
            'linkedin' => 'linkedin_url',
            'youtube' => 'youtube_url',
            'x' => 'x_url',
            'reddit' => 'reddit_url',
            'booking' => 'booking_url',
            'website' => 'website',
        ];

        foreach ($field_map as $choice => $field) {
            if (!empty($settings[$field])) {
                $available[$choice] = $all_choices[$choice];
            }
        }

        return $available;
    }

    private function get_preferred_sticky_social_media($settings) {
        $settings = is_array($settings) ? $settings : [];

        if (!empty($settings['instagram_url'])) {
            return 'instagram';
        }

        if (!empty($settings['facebook_url'])) {
            return 'facebook';
        }

        return 'none';
    }

    private function get_project_color_palette() {
        $saved = get_option(self::OPTION_KEY, []);
        if ((!is_array($saved) || empty($saved)) && is_array(get_option(self::LEGACY_OPTION_KEY, []))) {
            $saved = get_option(self::LEGACY_OPTION_KEY, []);
        }

        if (!is_array($saved) || empty($saved['project_palette']) || !is_array($saved['project_palette'])) {
            return [];
        }

        return $this->sanitize_project_palette($saved['project_palette']);
    }

    private function sanitize_code_snippet($value) {
        if (!is_string($value)) {
            return '';
        }

        $value = str_replace(["\r\n", "\r"], "\n", wp_unslash($value));
        return trim($value);
    }

    private function sanitize_project_palette($palette) {
        if (!is_array($palette)) {
            return [];
        }

        $sanitized = [];
        foreach ($palette as $swatch) {
            if (is_string($swatch)) {
                $swatch = [
                    'name' => '',
                    'value' => $swatch,
                ];
            }

            if (!is_array($swatch)) {
                continue;
            }

            $value = isset($swatch['value']) ? strtolower(trim((string) $swatch['value'])) : '';
            if (!preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $value)) {
                continue;
            }

            $name = isset($swatch['name']) ? sanitize_text_field($swatch['name']) : strtoupper($value);
            $sanitized[] = [
                'name' => $name !== '' ? $name : strtoupper($value),
                'value' => $value,
            ];

            if (count($sanitized) >= 12) {
                break;
            }
        }

        return $sanitized;
    }

    private function render_admin_footer_preview($settings) {
        $phone = isset($settings['phone_primary']) ? $settings['phone_primary'] : '';
        $email = isset($settings['email']) ? $settings['email'] : '';
        $bg = isset($settings['sticky_bg_color']) ? $settings['sticky_bg_color'] : '#0f0f0f';
        $icon = isset($settings['sticky_icon_color']) ? $settings['sticky_icon_color'] : '#ffffff';
        $text = isset($settings['sticky_text_color']) ? $settings['sticky_text_color'] : '#ffffff';
        $social = $this->get_sticky_social_item($settings);
        $items = [
            [
                'link' => $phone ? 'tel:' . preg_replace('/\s+/', '', $phone) : '',
                'text' => 'Ring',
                'icon' => 'fa-solid fa-phone',
            ],
        ];

        if (!empty($social['link'])) {
            $items[] = $social;
        }

        $items[] = [
            'link' => $email ? 'mailto:' . $email : '',
            'text' => 'Maila',
            'icon' => 'fa-solid fa-envelope',
        ];
        ?>
        <div id="oneplugin-preview-root" style="border:1px solid #dcdcde; border-radius:12px; padding:16px; background:#fff;">
            <div id="oneplugin-preview-links" style="margin-bottom:10px;">
                <strong><?php esc_html_e('Links used', 'oneplugin-light-site-tools'); ?></strong>
                <?php foreach ($items as $item) : ?>
                    <div><code><?php echo esc_html($item['link']); ?></code></div>
                <?php endforeach; ?>
            </div>
            <div style="width:100%; margin-top:12px;">
                <div id="oneplugin-preview-bar" style="border:1px solid rgba(0,0,0,.08); border-radius:20px 20px 0 0; padding:10px 8px; background:<?php echo esc_attr($bg); ?>;">
                    <div style="display:flex; gap:10px; justify-content:space-around; align-items:center;">
                        <?php foreach ($items as $item) : ?>
                            <a href="<?php echo esc_url($item['link']); ?>" class="oneplugin-preview-item" style="text-decoration:none; display:flex; flex-direction:column; gap:6px; align-items:center; justify-content:center; min-width:64px;">
                                <i class="<?php echo esc_attr($item['icon']); ?>" aria-hidden="true" style="font-size:18px; line-height:1; color:<?php echo esc_attr($icon); ?>;"></i>
                                <span style="font-size:12px; line-height:1; color:<?php echo esc_attr($text); ?>;"><?php echo esc_html($item['text']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_admin_script_config() {
        return [
            'presets' => [
                'none' => ['linkField' => '', 'text' => '', 'icon' => ''],
                'facebook' => ['linkField' => 'facebook_url', 'text' => 'Gilla', 'icon' => 'fa-brands fa-facebook'],
                'instagram' => ['linkField' => 'instagram_url', 'text' => 'Folj', 'icon' => 'fa-brands fa-instagram'],
                'linkedin' => ['linkField' => 'linkedin_url', 'text' => 'Connect', 'icon' => 'fa-brands fa-linkedin'],
                'youtube' => ['linkField' => 'youtube_url', 'text' => 'Watch', 'icon' => 'fa-brands fa-youtube'],
                'x' => ['linkField' => 'x_url', 'text' => 'Follow', 'icon' => 'fa-brands fa-x-twitter'],
                'reddit' => ['linkField' => 'reddit_url', 'text' => 'Join', 'icon' => 'fa-brands fa-reddit'],
                'booking' => ['linkField' => 'booking_url', 'text' => 'Boka', 'icon' => 'fa-solid fa-calendar-check'],
                'website' => ['linkField' => 'website', 'text' => 'Besok', 'icon' => 'fa-solid fa-globe'],
            ],
            'socialMediaChoices' => $this->get_social_media_choices(),
        ];
    }

    private function sanitize_color_value($value, $default) {
        $value = trim($value);

        if ($value === '') {
            return $default;
        }

        if (preg_match('/^--[a-zA-Z0-9_-]+$/', $value)) {
            return 'var(' . $value . ')';
        }

        if (preg_match('/^var\(\s*--[a-zA-Z0-9_-]+\s*\)$/', $value)) {
            return preg_replace('/^var\(\s*(--[a-zA-Z0-9_-]+)\s*\)$/', 'var($1)', $value);
        }

        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return $value;
        }

        if (preg_match('/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value)) {
            return $value;
        }

        return $default;
    }

    private function get_settings() {
        $saved = get_option(self::OPTION_KEY, []);
        if (!$this->has_meaningful_settings($saved)) {
            $legacy_saved = get_option(self::LEGACY_OPTION_KEY, []);
            if (is_array($legacy_saved)) {
                $saved = $legacy_saved;
            }
        }

        $settings = array_intersect_key(wp_parse_args(is_array($saved) ? $saved : [], $this->defaults), $this->defaults);
        if (is_array($saved) && !empty($saved['square_images']) && empty($settings['masonry_gallery_enabled'])) {
            $settings['masonry_gallery_enabled'] = '1';
            $settings['masonry_gallery_layout'] = 'square';
        }

        if (empty($settings['site_title'])) {
            $settings['site_title'] = get_option('blogname', '');
        }

        if (empty($settings['site_icon_id'])) {
            $settings['site_icon_id'] = absint(get_option('site_icon', 0));
        }

        if (empty($settings['site_logo_id'])) {
            $settings['site_logo_id'] = absint(get_theme_mod('custom_logo', 0));
        }

        if (empty($settings['site_logo_id'])) {
            $settings['site_logo_id'] = $this->get_divi_logo_attachment_id();
        }

        foreach ($settings as $key => $value) {
            if ($value === '' && isset($this->legacy_sources[$key])) {
                $legacy_value = $this->get_legacy_value($key);
                if ($legacy_value !== '') {
                    $settings[$key] = $legacy_value;
                }
            }
        }

        if (empty($settings['sticky_social_media'])) {
            $settings['sticky_social_media'] = $this->get_preferred_sticky_social_media($settings);
        }

        return $settings;
    }

    private function get_setting($key, $default = '') {
        $settings = $this->get_settings();
        return array_key_exists($key, $settings) ? $settings[$key] : $default;
    }

    private function get_legacy_value($key) {
        if (empty($this->legacy_sources[$key])) {
            return '';
        }

        foreach ($this->legacy_sources[$key] as $theme_mod_key) {
            $value = get_theme_mod($theme_mod_key, '');
            if ($value !== '' && $value !== null) {
                return $value;
            }
        }

        return '';
    }

    private function sync_divi_logo($attachment_id) {
        $logo_url = $this->get_attachment_source_url($attachment_id);
        $logo_url = $logo_url ? esc_url_raw($logo_url) : '';

        if (function_exists('et_update_option')) {
            et_update_option('divi_logo', $logo_url);
            et_update_option('logo', $logo_url);
        }

        $options = get_option('et_divi', []);
        if (!is_array($options)) {
            $options = [];
        }

        $options['divi_logo'] = $logo_url;
        $options['logo'] = $logo_url;
        update_option('et_divi', $options);
    }

    private function get_divi_logo_attachment_id() {
        $options = get_option('et_divi', []);
        if (!is_array($options)) {
            return 0;
        }

        $logo_url = '';
        if (!empty($options['divi_logo'])) {
            $logo_url = $options['divi_logo'];
        } elseif (!empty($options['logo'])) {
            $logo_url = $options['logo'];
        }

        if (!$logo_url) {
            return 0;
        }

        $attachment_id = attachment_url_to_postid($logo_url);
        return $attachment_id ? absint($attachment_id) : 0;
    }

    private function get_attachment_source_url($attachment_id) {
        $attachment_id = absint($attachment_id);
        if (!$attachment_id) {
            return '';
        }

        $url = wp_get_attachment_url($attachment_id);
        if ($url) {
            return $url;
        }

        $attachment = get_post($attachment_id);
        if ($attachment && !empty($attachment->guid)) {
            return $attachment->guid;
        }

        return '';
    }

    private function migrate_legacy_settings() {
        $settings = get_option(self::OPTION_KEY, []);
        $settings = is_array($settings) ? $settings : [];
        $has_changes = false;

        if (empty($settings)) {
            $legacy_settings = get_option(self::LEGACY_OPTION_KEY, []);
            if (is_array($legacy_settings) && !empty($legacy_settings)) {
                $settings = array_intersect_key(wp_parse_args($legacy_settings, $this->defaults), $this->defaults);
                $has_changes = true;
            }
        }

        foreach ($this->defaults as $key => $default) {
            if (!array_key_exists($key, $settings) || $settings[$key] === '') {
                $legacy_value = $this->get_legacy_value($key);
                if ($legacy_value !== '') {
                    $settings[$key] = $legacy_value;
                    $has_changes = true;
                }
            }
        }

        if (empty($settings['sticky_social_media'])) {
            $settings['sticky_social_media'] = !empty($settings['instagram_url']) ? 'instagram' : 'none';
            $has_changes = true;
        }

        if ($has_changes) {
            update_option(self::OPTION_KEY, array_intersect_key(wp_parse_args($settings, $this->defaults), $this->defaults));
        }
    }

    public function register_rest_routes() {
        $this->register_rest_namespace('oneplugin2/v1');
        $this->register_rest_namespace('oneplugin/v1');
    }

    private function register_rest_namespace($namespace) {
        register_rest_route($namespace, '/status', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'handle_rest_status'],
            'permission_callback' => [$this, 'rest_manage_options_permission'],
        ]);

        register_rest_route($namespace, '/menus', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'handle_rest_get_menus'],
            'permission_callback' => [$this, 'rest_builder_permission'],
        ]);

        register_rest_route($namespace, '/menu-preview', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'handle_rest_menu_preview'],
            'permission_callback' => [$this, 'rest_builder_permission'],
        ]);

        register_rest_route($namespace, '/settings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'handle_rest_get_settings'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'handle_rest_update_settings'],
                'permission_callback' => [$this, 'rest_manage_options_permission'],
            ],
        ]);
    }

    public function rest_manage_options_permission() {
        if (current_user_can('manage_options')) {
            return true;
        }

        return $this->rest_error_response(
            'forbidden',
            __('You do not have permission to access this endpoint.', 'oneplugin-light-site-tools'),
            [],
            rest_authorization_required_code()
        );
    }

    public function rest_builder_permission() {
        if (current_user_can('edit_posts')) {
            return true;
        }

        return $this->rest_error_response(
            'forbidden',
            __('You do not have permission to access this endpoint.', 'oneplugin-light-site-tools'),
            [],
            rest_authorization_required_code()
        );
    }

    public function handle_rest_status(WP_REST_Request $request) {
        $theme = wp_get_theme();

        return $this->rest_success_response([
            'plugin_version' => self::VERSION,
            'api_version' => self::API_VERSION,
            'site_uuid' => $this->ensure_site_uuid(),
            'capabilities' => $this->get_capabilities(),
            'environment' => [
                'home_url' => home_url('/'),
                'site_url' => site_url('/'),
                'wp_version' => get_bloginfo('version'),
                'php_version' => PHP_VERSION,
                'is_multisite' => is_multisite(),
                'locale' => get_locale(),
            ],
            'theme' => [
                'name' => $theme->get('Name'),
                'stylesheet' => $theme->get_stylesheet(),
                'template' => $theme->get_template(),
                'version' => $theme->get('Version'),
            ],
            'project_palette' => $this->get_project_color_palette(),
        ]);
    }

    public function handle_rest_get_settings(WP_REST_Request $request) {
        return $this->rest_success_response([
            'settings' => $this->get_settings(),
            'project_palette' => $this->get_project_color_palette(),
        ]);
    }

    public function handle_rest_get_menus(WP_REST_Request $request) {
        $menus = wp_get_nav_menus();
        $menu_data = [];

        foreach ($menus as $menu) {
            $items = wp_get_nav_menu_items($menu->term_id, [
                'update_post_term_cache' => false,
            ]);

            $indexed_items = [];
            foreach ((array) $items as $item) {
                $indexed_items[(int) $item->ID] = [
                    'id' => (int) $item->ID,
                    'parent' => (int) $item->menu_item_parent,
                    'label' => wp_strip_all_tags((string) $item->title),
                    'url' => !empty($item->url) ? esc_url_raw($item->url) : '',
                    'children' => [],
                ];
            }

            $tree = [];
            foreach ($indexed_items as $item_id => $item) {
                if ($item['parent'] > 0 && isset($indexed_items[$item['parent']])) {
                    $indexed_items[$item['parent']]['children'][] = &$indexed_items[$item_id];
                    continue;
                }

                $tree[] = &$indexed_items[$item_id];
            }

            $menu_data[] = [
                'id' => (int) $menu->term_id,
                'slug' => (string) $menu->slug,
                'name' => (string) $menu->name,
                'items' => array_values($tree),
            ];
        }

        return $this->rest_success_response([
            'menus' => $menu_data,
        ]);
    }

    public function handle_rest_menu_preview(WP_REST_Request $request) {
        $menu_id = $request->get_param('menu_id');
        $hover_effect = $request->get_param('hover_effect');
        $menu_gap = $request->get_param('menu_gap');
        $item_active_color = $request->get_param('item_active_color');
        $item_active_background_color = $request->get_param('item_active_background_color');

        $menu_id = is_scalar($menu_id) ? sanitize_text_field((string) $menu_id) : '';
        $hover_effect = is_scalar($hover_effect) ? sanitize_key((string) $hover_effect) : 'underline';
        $menu_gap = is_scalar($menu_gap) ? sanitize_text_field((string) $menu_gap) : '24';
        $item_active_color = is_scalar($item_active_color) ? sanitize_text_field((string) $item_active_color) : '#111827';
        $item_active_background_color = is_scalar($item_active_background_color) ? sanitize_text_field((string) $item_active_background_color) : 'rgba(17,24,39,.12)';

        if (!in_array($hover_effect, ['none', 'underline', 'fill', 'lift'], true)) {
            $hover_effect = 'underline';
        }

        $html = OnePlugin_Light_Menu_Module::instance()->render_shortcode([
            'source_type' => 'menu',
            'menu_slug' => $menu_id,
            'hover_effect' => $hover_effect,
            'menu_gap' => $menu_gap,
            'menu_active_text_color' => $item_active_color,
            'menu_active_bg_color' => $item_active_background_color,
        ]);

        $styles = '';
        if (wp_style_is('oneplugin2-menu-inline', 'enqueued') || wp_style_is('oneplugin2-menu-inline', 'registered')) {
            ob_start();
            wp_print_styles('oneplugin2-menu-inline');
            $styles = (string) ob_get_clean();
        }

        return $this->rest_success_response([
            'html' => $styles . (is_string($html) ? $html : ''),
        ]);
    }

    public function handle_rest_update_settings(WP_REST_Request $request) {
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            $payload = $request->get_body_params();
        }

        if (!is_array($payload) || !isset($payload['settings']) || !is_array($payload['settings'])) {
            return $this->rest_error_response(
                'invalid_payload',
                __('The request body must include a settings object.', 'oneplugin-light-site-tools'),
                []
            );
        }

        $current_settings = $this->get_settings();
        $merged_settings = array_replace($current_settings, $payload['settings']);
        if (isset($payload['project_palette']) && is_array($payload['project_palette'])) {
            $merged_settings['project_palette'] = $payload['project_palette'];
        }
        $sanitized_settings = $this->sanitize_settings($merged_settings);

        $this->persist_settings($sanitized_settings);

        return $this->rest_success_response([
            'settings' => $this->get_settings(),
            'project_palette' => $this->get_project_color_palette(),
        ]);
    }

    private function get_site_uuid() {
        $site_uuid = get_option(self::SITE_UUID_OPTION_KEY, '');

        if (!is_string($site_uuid) || $site_uuid === '') {
            $site_uuid = get_option(self::LEGACY_SITE_UUID_OPTION_KEY, '');
        }

        return is_string($site_uuid) ? $site_uuid : '';
    }

    private function ensure_site_uuid() {
        $site_uuid = $this->get_site_uuid();

        if ($site_uuid !== '') {
            return $site_uuid;
        }

        $site_uuid = wp_generate_uuid4();
        update_option(self::SITE_UUID_OPTION_KEY, $site_uuid, false);
        update_option(self::LEGACY_SITE_UUID_OPTION_KEY, $site_uuid, false);

        return $site_uuid;
    }

    private function get_capabilities() {
        return [
            'settings_read' => true,
            'settings_write' => true,
            'divi_sync' => true,
            'keyword_meta' => true,
        ];
    }

    public function mirror_legacy_option($old_value, $value) {
        update_option(self::LEGACY_OPTION_KEY, array_intersect_key(wp_parse_args(is_array($value) ? $value : [], $this->defaults), $this->defaults));
    }

    public function mirror_legacy_option_on_add($option, $value) {
        $this->mirror_legacy_option([], $value);
    }

    private function persist_settings($settings) {
        $settings = array_intersect_key(wp_parse_args(is_array($settings) ? $settings : [], $this->defaults), $this->defaults);
        update_option(self::OPTION_KEY, $settings);
        update_option(self::LEGACY_OPTION_KEY, $settings);
    }

    private function has_meaningful_settings($settings) {
        if (!is_array($settings) || empty($settings)) {
            return false;
        }

        foreach ($settings as $value) {
            if ($value !== '' && $value !== null && $value !== 0 && $value !== '0') {
                return true;
            }
        }

        return false;
    }

    private function rest_success_response($data, $status = 200) {
        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    private function rest_error_response($code, $message, $details = [], $status = 400) {
        return new WP_REST_Response([
            'success' => false,
            'code' => (string) $code,
            'message' => (string) $message,
            'details' => is_array($details) ? $details : [],
        ], $status);
    }

    public function handle_export_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'oneplugin-light-site-tools'));
        }

        check_admin_referer('oneplugin_light_export_settings');

        $payload = [
            'plugin' => 'oneplugin-light-site-tools',
            'version' => self::VERSION,
            'exported_at' => current_time('mysql'),
            'settings' => $this->get_settings(),
        ];

        nocache_headers();
        header('Content-Type: application/json; charset=' . get_option('blog_charset'));
        header('Content-Disposition: attachment; filename="oneplugin-light-site-tools-settings-' . gmdate('Y-m-d-His') . '.json"');

        echo wp_json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function handle_import_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized request.', 'oneplugin-light-site-tools'));
        }

        check_admin_referer('oneplugin_light_import_settings');

        if (empty($_FILES['oneplugin_light_import_file']['tmp_name']) || !is_uploaded_file($_FILES['oneplugin_light_import_file']['tmp_name'])) {
            $this->redirect_import_status('error');
        }

        $raw = file_get_contents($_FILES['oneplugin_light_import_file']['tmp_name']);
        if ($raw === false) {
            $this->redirect_import_status('error');
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || empty($decoded['settings']) || !is_array($decoded['settings'])) {
            $this->redirect_import_status('error');
        }

        $sanitized = $this->sanitize_settings($decoded['settings']);
        $this->persist_settings($sanitized);

        $this->redirect_import_status('success');
    }

    private function redirect_import_status($status) {
        wp_safe_redirect(
            add_query_arg(
                ['page' => self::MENU_SLUG, 'oneplugin2_import' => $status],
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function enable_shortcodes_in_divi_modules() {
        $modules = [
            'et_pb_text',
            'et_pb_button',
            'et_pb_blurb',
            'et_pb_call_to_action',
            'et_pb_code',
            'et_pb_slider',
            'et_pb_fullwidth_header',
        ];

        foreach ($modules as $module) {
            add_filter('et_builder_render_module_content_' . $module, 'do_shortcode');
        }
    }

    public function enqueue_frontend_assets() {
        if ($this->get_setting('sticky_enabled', '1') === '1') {
            wp_enqueue_style(
                'oneplugin2-fontawesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                [],
                '6.5.1'
            );
            wp_enqueue_style(
                'oneplugin2-mobile-footer-inline',
                ONEPLUGIN_LIGHT_URL . 'assets/css/mobile-footer.css',
                [],
                self::VERSION
            );
        }

        $company_name = $this->get_fixed_image_alt_text_value();
        if ($this->get_setting('fix_image_alt_text', '0') === '1' && $company_name !== '') {
            wp_enqueue_script(
                'oneplugin-light-image-alt-fix',
                ONEPLUGIN_LIGHT_URL . 'assets/js/image-alt-fix.js',
                [],
                self::VERSION,
                true
            );
            wp_localize_script('oneplugin-light-image-alt-fix', 'OnePluginLightImageAltFix', [
                'companyName' => $company_name,
            ]);
        }

        if ($this->get_setting('apply_cover_to_tabs_image', '0') === '1') {
            wp_enqueue_script(
                'oneplugin-light-tabs-image-cover',
                ONEPLUGIN_LIGHT_URL . 'assets/js/tabs-image-cover.js',
                [],
                self::VERSION,
                true
            );
        }

        if ($this->get_setting('masonry_gallery_enabled', '0') === '1' && $this->get_setting('masonry_gallery_layout', 'square') === 'asymetric') {
            wp_enqueue_script(
                'oneplugin-light-masonry-gallery-layout',
                ONEPLUGIN_LIGHT_URL . 'assets/js/masonry-gallery-layout.js',
                [],
                self::VERSION,
                true
            );
        }

        if ($this->get_setting('active_menu_item_by_section', '0') === '1') {
            wp_enqueue_script(
                'oneplugin-light-active-menu-item-by-section',
                ONEPLUGIN_LIGHT_URL . 'assets/js/active-menu-item-by-section.js',
                [],
                self::VERSION,
                true
            );
        }
    }

    public function render_custom_css() {
        if (is_admin()) {
            return;
        }

        $generated_css = [];
        if ($this->get_setting('hide_image_alt_text', '0') === '1') {
            $generated_css[] = 'img {pointer-events: none!important;}';
        }
        if ($this->get_setting('header_glass_effect', '0') === '1') {
            $generated_css[] = 'header#main-header.et-fixed-header {
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}';
        }
        if ($this->get_setting('cover_images', '0') === '1' || $this->get_setting('apply_cover_to_tabs_image', '0') === '1') {
            $generated_css[] = '.cover-img,
.cover-img .et_pb_image_wrap {
    height: 100%;
}

.cover-img img,
img.cover-img {
    height: 100%;
    width: 100%;
    object-fit: cover;
}';
        }
        if ($this->get_setting('masonry_gallery_enabled', '0') === '1') {
            $masonry_layout = $this->get_setting('masonry_gallery_layout', 'square');
            if ($masonry_layout === 'asymetric') {
                $generated_css[] = '.dipi_masonry_gallery_container img {
    display: block;
    width: 100%;
    object-fit: cover;
}

.dipi_masonry_gallery_container img.oneplugin-masonry-outer-pair {
    aspect-ratio: 3 / 2;
}

.dipi_masonry_gallery_container img.oneplugin-masonry-inner-pair {
    aspect-ratio: 4 / 5;
}';
            } else {
                $generated_css[] = '.dipi_masonry_gallery_container img {
    display: block;
    width: 100%;
    aspect-ratio: 1 / 1;
    object-fit: cover;
}';
            }
        }
        if ($this->get_setting('hide_default_footer', '0') === '1') {
            $generated_css[] = 'footer#main-footer {
    display: none !important;
}';
        }
        if ($this->get_setting('style_formidable', '0') === '1') {
            $formidable_accent = $this->get_setting('formidable_accent_color', '#fa1e9a');
            $generated_css[] = '.frm_form_field .frm_checkbox {
    margin-top: 0;
    margin-bottom: 10px;
    background-color: #ffffff;
    padding: 10px 5px 10px 10px;
    border-radius: 8px;
    border: 1px solid ' . $formidable_accent . ';
    transition: background-color 0.3s ease;
}

.with_frm_style .frm_checkbox label {
    font-size: var(--check-font-size);
    color: #000000;
    font-weight: var(--check-weight);
    line-height: 1.3;
}

.with_frm_style .frm_checkbox input[type=checkbox] {
    appearance: none;
    background-color: ' . $formidable_accent . ';
    flex: none;
    display: inline-block !important;
    width: 16px !important;
    min-width: 16px !important;
    height: 16px !important;
    color: var(--border-color);
    border: 1px solid currentColor;
    border-color: var(--border-color);
    vertical-align: middle;
    position: initial;
    padding: 0;
    margin: 0;
}

.with_frm_style .frm_checkbox:has(input:checked) {
    background-color: ' . $formidable_accent . ';
}

.with_frm_style .frm_checkbox:has(input:checked) label {
    color: #ffffff;
}

.frm_style_formidables-stilmall.with_frm_style .frm_error,
.frm_style_formidables-stilmall.with_frm_style .frm_limit_error {
    font-weight: bold;
    color: #ffffff;
}';
        }

        $css = implode("\n", $generated_css);
        $custom_css = trim((string) $this->get_setting('custom_code_css', ''));
        if ($custom_css !== '') {
            $css = trim($css . "\n" . $custom_css);
        }

        if ($css === '') {
            return;
        }

        echo "<style id=\"oneplugin-custom-css\">\n" . $css . "\n</style>\n";
    }

    public function filter_attachment_image_alt($attr, $attachment) {
        if (is_admin() || $this->get_setting('fix_image_alt_text', '0') !== '1') {
            return $attr;
        }

        $company_name = $this->get_fixed_image_alt_text_value();
        if ($company_name === '') {
            return $attr;
        }

        $attr['alt'] = $company_name;
        $attr['title'] = $company_name;

        return $attr;
    }

    public function replace_image_alt_in_html($html) {
        if (!is_string($html) || $html === '' || is_admin() || $this->get_setting('fix_image_alt_text', '0') !== '1') {
            return $html;
        }

        $company_name = $this->get_fixed_image_alt_text_value();
        if ($company_name === '' || stripos($html, '<img') === false) {
            return $html;
        }

        $escaped_alt = esc_attr($company_name);
        $escaped_title = esc_attr($company_name);

        return preg_replace_callback('/<img\b[^>]*>/i', static function ($matches) use ($escaped_alt, $escaped_title) {
            $tag = $matches[0];
            $closing = substr($tag, -2) === '/>' ? '/>' : '>';
            $tag_body = substr($tag, 0, -strlen($closing));

            if (preg_match('/\salt\s*=\s*(["\']).*?\1/i', $tag_body)) {
                $tag_body = preg_replace('/\salt\s*=\s*(["\']).*?\1/i', ' alt="' . $escaped_alt . '"', $tag_body, 1);
            } else {
                $tag_body .= ' alt="' . $escaped_alt . '"';
            }

            if (preg_match('/\stitle\s*=\s*(["\']).*?\1/i', $tag_body)) {
                $tag_body = preg_replace('/\stitle\s*=\s*(["\']).*?\1/i', ' title="' . $escaped_title . '"', $tag_body, 1);
            } else {
                $tag_body .= ' title="' . $escaped_title . '"';
            }

            return $tag_body . $closing;
        }, $html);
    }

    private function get_fixed_image_alt_text_value() {
        return trim((string) $this->get_setting('company_name', ''));
    }

    public function render_custom_js() {
        if (is_admin()) {
            return;
        }

        $js = trim((string) $this->get_setting('custom_code_js', ''));
        if ($js === '') {
            return;
        }

        echo "<script id=\"oneplugin-custom-js\">\n" . $js . "\n</script>\n";
    }

    public function render_custom_php_head() {
        if (is_admin()) {
            return;
        }

        $this->execute_custom_php_snippet('custom_code_php_head');
    }

    public function render_custom_php_body() {
        if (is_admin()) {
            return;
        }

        $this->execute_custom_php_snippet('custom_code_php_body');
    }

    public function render_custom_php_footer() {
        if (is_admin()) {
            return;
        }

        $this->execute_custom_php_snippet('custom_code_php_footer');
    }

    private function execute_custom_php_snippet($setting_key) {
        $code = trim((string) $this->get_setting($setting_key, ''));
        if ($code === '') {
            return;
        }

        try {
            eval("?>$code");
        } catch (ParseError $error) {
            error_log(sprintf('1Plugin custom PHP error in %s: %s', $setting_key, $error->getMessage()));
        }
    }

    public function render_mobile_footer() {
        if (is_admin() || $this->get_setting('sticky_enabled', '1') !== '1') {
            return;
        }

        $phone = $this->get_setting('phone_primary');
        $email = $this->get_setting('email');
        $social_item = $this->get_sticky_social_item();
        $items = [
            [
                'link' => $phone ? 'tel:' . preg_replace('/\s+/', '', $phone) : '',
                'text' => 'Ring',
                'icon' => 'fa-solid fa-phone',
                'target' => '',
            ],
        ];

        if (!empty($social_item['link'])) {
            $items[] = $social_item;
        }

        $items[] = [
            'link' => $email ? 'mailto:' . $email : '',
            'text' => 'Maila',
            'icon' => 'fa-solid fa-envelope',
            'target' => '',
        ];

        $has_items = false;
        foreach ($items as $item) {
            if (!empty($item['link'])) {
                $has_items = true;
                break;
            }
        }

        if (!$has_items) {
            return;
        }

        $footer_style = sprintf(
            '--oneplugin-mobile-footer-bg:%s;--oneplugin-mobile-footer-icon:%s;--oneplugin-mobile-footer-text:%s;',
            esc_attr($this->get_setting('sticky_bg_color', '#0f0f0f')),
            esc_attr($this->get_setting('sticky_icon_color', '#ffffff')),
            esc_attr($this->get_setting('sticky_text_color', '#ffffff'))
        );

        echo '<div class="oneplugin-mobile-footer" role="navigation" aria-label="' . esc_attr__('Mobile footer', 'oneplugin-light-site-tools') . '" style="' . $footer_style . '">';
        echo '<div class="oneplugin-mobile-footer__inner">';

        foreach ($items as $item) {
            if (empty($item['link'])) {
                continue;
            }

            echo '<a class="footer-item" href="' . esc_url($item['link']) . '"' . $item['target'] . '>';
            echo '<i class="' . esc_attr($item['icon']) . '" aria-hidden="true"></i>';
            echo '<span>' . esc_html($item['text']) . '</span>';
            echo '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    private function get_sticky_social_item($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        $platform = isset($settings['sticky_social_media']) ? $settings['sticky_social_media'] : 'instagram';
        $presets = [
            'facebook' => [
                'link' => isset($settings['facebook_url']) ? $settings['facebook_url'] : '',
                'text' => 'Gilla',
                'icon' => 'fa-brands fa-facebook',
            ],
            'instagram' => [
                'link' => isset($settings['instagram_url']) ? $settings['instagram_url'] : '',
                'text' => 'Folj',
                'icon' => 'fa-brands fa-instagram',
            ],
            'linkedin' => [
                'link' => isset($settings['linkedin_url']) ? $settings['linkedin_url'] : '',
                'text' => 'Connect',
                'icon' => 'fa-brands fa-linkedin',
            ],
            'youtube' => [
                'link' => isset($settings['youtube_url']) ? $settings['youtube_url'] : '',
                'text' => 'Watch',
                'icon' => 'fa-brands fa-youtube',
            ],
            'x' => [
                'link' => isset($settings['x_url']) ? $settings['x_url'] : '',
                'text' => 'Follow',
                'icon' => 'fa-brands fa-x-twitter',
            ],
            'reddit' => [
                'link' => isset($settings['reddit_url']) ? $settings['reddit_url'] : '',
                'text' => 'Join',
                'icon' => 'fa-brands fa-reddit',
            ],
            'booking' => [
                'link' => isset($settings['booking_url']) ? $settings['booking_url'] : '',
                'text' => 'Boka',
                'icon' => 'fa-solid fa-calendar-check',
            ],
            'website' => [
                'link' => isset($settings['website']) ? $settings['website'] : '',
                'text' => 'Besok',
                'icon' => 'fa-solid fa-globe',
            ],
            'none' => [
                'link' => '',
                'text' => '',
                'icon' => '',
            ],
        ];

        $item = isset($presets[$platform]) ? $presets[$platform] : $presets['instagram'];

        return [
            'link' => $item['link'],
            'text' => $item['text'],
            'icon' => $item['icon'],
            'target' => ' target="_blank" rel="noopener"',
        ];
    }
}
