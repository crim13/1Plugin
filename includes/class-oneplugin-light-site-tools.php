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
    const DIVI_LOGO_ATTACHMENT_TRANSIENT = 'oneplugin_light_divi_logo_attachment_id';

    private static $instance = null;
    private $menu_module = null;
    private $upgrades_ran = false;

    private $defaults = [
        'company_name' => '',
        'street_address' => '',
        'postal_code' => '',
        'city' => '',
        'phone_primary' => '',
        'organization_number' => '',
        'email' => '',
        'site_title' => '',
        'site_icon_id' => 0,
        'site_logo_id' => 0,
        'sticky_header_logo_id' => 0,
        'mobile_logo_id' => 0,
        'logo_white_filter_enabled' => '0',
        'site_logo_white_filter_enabled' => '0',
        'sticky_header_logo_white_filter_enabled' => '0',
        'mobile_logo_white_filter_enabled' => '0',
        'custom_header_class' => 'oneplugin-custom-header',
        'custom_logo_class' => 'oneplugin-custom-logo',
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
        'sticky_width' => '90%',
        'sticky_radius_top' => '20px',
        'sticky_font_size' => '12px',
        'sticky_icon_size' => '18px',
        'sticky_social_media' => 'none',
        'sticky_item_1' => 'phone',
        'sticky_item_2' => 'email',
        'sticky_item_3' => 'none',
        'sticky_custom_1_text' => '',
        'sticky_custom_1_link' => '',
        'sticky_custom_1_icon' => '',
        'sticky_custom_2_text' => '',
        'sticky_custom_2_link' => '',
        'sticky_custom_2_icon' => '',
        'sticky_custom_3_text' => '',
        'sticky_custom_3_link' => '',
        'sticky_custom_3_icon' => '',
        'hide_image_alt_text' => '0',
        'fix_image_alt_text' => '0',
        'header_enabled' => '1',
        'transparent_fixed_header' => '0',
        'transparent_fixed_header_mobile' => '0',
        'transparent_fixed_header_home_only' => '0',
        'transparent_header_invert_logo' => '0',
        'transparent_header_menu_text_color' => '',
        'header_animation_enabled' => '0',
        'header_transition_duration' => '180ms',
        'header_transition_easing' => 'ease',
        'cover_images' => '0',
        'apply_cover_to_tabs_image' => '0',
        'masonry_gallery_enabled' => '0',
        'masonry_gallery_layout' => 'square',
        'active_menu_item_by_section' => '0',
        'hide_default_footer' => '0',
        'style_formidable' => '1',
        'formidable_accent_color' => '#fa1e9a',
        'formidable_background_color' => '#ffffff',
        'formidable_text_color' => '#000000',
        'formidable_checked_text_color' => '#ffffff',
        'formidable_checked_background_color' => '#ffffff',
        'formidable_border_radius' => '8px',
        'formidable_border_width' => '1px',
        'formidable_padding' => '10px 5px 10px 10px',
        'scrollbar_enabled' => '0',
        'scrollbar_bg_color' => '#f1f5f9',
        'scrollbar_handle_color' => '#0f172a',
        'scrollbar_radius' => '10px',
        'scrollbar_width' => '10px',
        'extension_faq_enabled' => '1',
        'module_menu_enabled' => '1',
        'module_faq_enabled' => '1',
        'module_logo_enabled' => '1',
        'github_auto_updates_enabled' => '0',
        'custom_php_enabled' => '0',
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
        'site_title' => [],
        'site_icon_id' => [],
        'site_logo_id' => [],
        'sticky_header_logo_id' => [],
        'mobile_logo_id' => [],
        'custom_header_class' => [],
        'custom_logo_class' => [],
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
        add_action('admin_init', [$this, 'run_upgrade_routines'], 1);
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
        add_action('wp_footer', [$this, 'render_header_controller_js'], 35);
        add_action('wp_footer', [$this, 'render_logo_behavior_js'], 40);
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

    public function run_upgrade_routines() {
        if ($this->upgrades_ran) {
            return;
        }

        $this->upgrades_ran = true;
        $this->ensure_faq_settings_exist();
        $this->sync_github_auto_updates_setting($this->get_settings());
    }

    private function ensure_faq_settings_exist() {
        if (get_option('oneplugin_light_faq_module_enabled_migrated', '0') === '1') {
            return;
        }

        $settings = get_option(self::OPTION_KEY, []);
        if (!is_array($settings) || empty($settings)) {
            update_option('oneplugin_light_faq_module_enabled_migrated', '1');
            return;
        }

        if (!array_key_exists('extension_faq_enabled', $settings)) {
            $settings['extension_faq_enabled'] = '1';
        }

        if (!array_key_exists('module_faq_enabled', $settings)) {
            $settings['module_faq_enabled'] = '1';
        }

        update_option(self::OPTION_KEY, array_intersect_key(wp_parse_args($settings, $this->defaults), $this->defaults));

        update_option('oneplugin_light_faq_module_enabled_migrated', '1');
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
        $palette_css = $this->build_project_palette_css();
        if ($palette_css !== '') {
            wp_add_inline_style('oneplugin-light-admin', $palette_css);
        }

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
            'formidable_background_color',
            'formidable_text_color',
            'formidable_checked_text_color',
            'formidable_checked_background_color',
            'scrollbar_bg_color',
            'scrollbar_handle_color',
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
        $output['sticky_header_logo_id'] = isset($input['sticky_header_logo_id']) ? absint($input['sticky_header_logo_id']) : 0;
        $output['mobile_logo_id'] = isset($input['mobile_logo_id']) ? absint($input['mobile_logo_id']) : 0;
        $output['logo_white_filter_enabled'] = !empty($input['logo_white_filter_enabled']) ? '1' : '0';
        $output['site_logo_white_filter_enabled'] = !empty($input['site_logo_white_filter_enabled']) ? '1' : $output['logo_white_filter_enabled'];
        $output['sticky_header_logo_white_filter_enabled'] = !empty($input['sticky_header_logo_white_filter_enabled']) ? '1' : '0';
        $output['mobile_logo_white_filter_enabled'] = !empty($input['mobile_logo_white_filter_enabled']) ? '1' : '0';
        $output['custom_header_class'] = isset($input['custom_header_class']) ? $this->sanitize_css_class_setting($input['custom_header_class'], $this->defaults['custom_header_class']) : $this->defaults['custom_header_class'];
        $output['custom_logo_class'] = isset($input['custom_logo_class']) ? $this->sanitize_css_class_setting($input['custom_logo_class'], $this->defaults['custom_logo_class']) : $this->defaults['custom_logo_class'];
        $output['sticky_enabled'] = !empty($input['sticky_enabled']) ? '1' : '0';
        $output['sticky_width'] = $this->sanitize_css_length_value(isset($input['sticky_width']) ? $input['sticky_width'] : '', $this->defaults['sticky_width']);
        $output['sticky_radius_top'] = $this->sanitize_css_length_value(isset($input['sticky_radius_top']) ? $input['sticky_radius_top'] : '', $this->defaults['sticky_radius_top'], true);
        $output['sticky_font_size'] = $this->sanitize_css_length_value(isset($input['sticky_font_size']) ? $input['sticky_font_size'] : '', $this->defaults['sticky_font_size']);
        $output['sticky_icon_size'] = $this->sanitize_css_length_value(isset($input['sticky_icon_size']) ? $input['sticky_icon_size'] : '', $this->defaults['sticky_icon_size']);
        $output['sticky_social_media'] = isset($input['sticky_social_media']) ? sanitize_key($input['sticky_social_media']) : 'none';
        foreach ([1, 2, 3] as $position) {
            $key = 'sticky_item_' . $position;
            $output[$key] = isset($input[$key]) ? sanitize_key($input[$key]) : $this->defaults[$key];
            $output['sticky_custom_' . $position . '_text'] = isset($input['sticky_custom_' . $position . '_text']) ? sanitize_text_field($input['sticky_custom_' . $position . '_text']) : '';
            $output['sticky_custom_' . $position . '_link'] = isset($input['sticky_custom_' . $position . '_link']) ? $this->sanitize_sticky_footer_link($input['sticky_custom_' . $position . '_link']) : '';
            $output['sticky_custom_' . $position . '_icon'] = isset($input['sticky_custom_' . $position . '_icon']) ? sanitize_text_field($input['sticky_custom_' . $position . '_icon']) : '';
        }
        $output['hide_image_alt_text'] = !empty($input['hide_image_alt_text']) ? '1' : '0';
        $output['fix_image_alt_text'] = !empty($input['fix_image_alt_text']) ? '1' : '0';
        $output['header_enabled'] = !empty($input['header_enabled']) ? '1' : '0';
        $output['transparent_fixed_header'] = !empty($input['transparent_fixed_header']) ? '1' : '0';
        $output['transparent_fixed_header_mobile'] = !empty($input['transparent_fixed_header_mobile']) ? '1' : '0';
        $output['transparent_fixed_header_home_only'] = !empty($input['transparent_fixed_header_home_only']) ? '1' : '0';
        $output['transparent_header_invert_logo'] = !empty($input['transparent_header_invert_logo']) ? '1' : '0';
        $output['transparent_header_menu_text_color'] = isset($input['transparent_header_menu_text_color']) ? (sanitize_hex_color($input['transparent_header_menu_text_color']) ?? '') : '';
        $output['header_animation_enabled'] = !empty($input['header_animation_enabled']) ? '1' : '0';
        $output['header_transition_duration'] = $this->sanitize_css_time_value(isset($input['header_transition_duration']) ? $input['header_transition_duration'] : '', $this->defaults['header_transition_duration']);
        $output['header_transition_easing'] = isset($input['header_transition_easing']) ? sanitize_key($input['header_transition_easing']) : $this->defaults['header_transition_easing'];
        if (!array_key_exists($output['header_transition_easing'], $this->get_header_transition_easing_options())) {
            $output['header_transition_easing'] = $this->defaults['header_transition_easing'];
        }
        $output['cover_images'] = !empty($input['cover_images']) ? '1' : '0';
        $output['apply_cover_to_tabs_image'] = !empty($input['apply_cover_to_tabs_image']) ? '1' : '0';
        $output['masonry_gallery_enabled'] = !empty($input['masonry_gallery_enabled']) ? '1' : '0';
        $output['masonry_gallery_layout'] = isset($input['masonry_gallery_layout']) ? sanitize_key($input['masonry_gallery_layout']) : 'square';
        if (!in_array($output['masonry_gallery_layout'], ['square', 'asymetric'], true)) {
            $output['masonry_gallery_layout'] = 'square';
        }
        $output['active_menu_item_by_section'] = !empty($input['active_menu_item_by_section']) ? '1' : '0';
        $output['hide_default_footer'] = !empty($input['hide_default_footer']) ? '1' : '0';
        $output['style_formidable'] = !empty($input['style_formidable']) ? '1' : '0';
        $output['formidable_border_radius'] = $this->sanitize_css_length_value(isset($input['formidable_border_radius']) ? $input['formidable_border_radius'] : '', $this->defaults['formidable_border_radius'], true);
        $output['formidable_border_width'] = $this->sanitize_css_length_value(isset($input['formidable_border_width']) ? $input['formidable_border_width'] : '', $this->defaults['formidable_border_width'], true);
        $output['formidable_padding'] = $this->sanitize_css_box_value(isset($input['formidable_padding']) ? $input['formidable_padding'] : '', $this->defaults['formidable_padding']);
        $output['scrollbar_enabled'] = !empty($input['scrollbar_enabled']) ? '1' : '0';
        $output['scrollbar_radius'] = $this->sanitize_css_length_value(isset($input['scrollbar_radius']) ? $input['scrollbar_radius'] : '', $this->defaults['scrollbar_radius'], true);
        $output['scrollbar_width'] = $this->sanitize_css_length_value(isset($input['scrollbar_width']) ? $input['scrollbar_width'] : '', $this->defaults['scrollbar_width']);
        $output['extension_faq_enabled'] = !empty($input['extension_faq_enabled']) ? '1' : '0';
        $output['module_menu_enabled'] = !empty($input['module_menu_enabled']) ? '1' : '0';
        $output['module_faq_enabled'] = !empty($input['module_faq_enabled']) ? '1' : '0';
        $output['module_logo_enabled'] = !empty($input['module_logo_enabled']) ? '1' : '0';
        $output['github_auto_updates_enabled'] = !empty($input['github_auto_updates_enabled']) ? '1' : '0';
        $output['custom_php_enabled'] = !empty($input['custom_php_enabled']) ? '1' : '0';
        $output['project_palette'] = $this->sanitize_project_palette(isset($input['project_palette']) ? $input['project_palette'] : []);
        $output['custom_code_css'] = $this->sanitize_code_snippet(isset($input['custom_code_css']) ? $input['custom_code_css'] : '');
        $output['custom_code_js'] = $this->sanitize_code_snippet(isset($input['custom_code_js']) ? $input['custom_code_js'] : '');
        $output['custom_code_php_head'] = $this->sanitize_code_snippet(isset($input['custom_code_php_head']) ? $input['custom_code_php_head'] : '');
        $output['custom_code_php_body'] = $this->sanitize_code_snippet(isset($input['custom_code_php_body']) ? $input['custom_code_php_body'] : '');
        $output['custom_code_php_footer'] = $this->sanitize_code_snippet(isset($input['custom_code_php_footer']) ? $input['custom_code_php_footer'] : '');

        $available_choices = $this->get_available_sticky_item_choices($input);
        if (!array_key_exists($output['sticky_social_media'], $available_choices)) {
            $output['sticky_social_media'] = $this->get_preferred_sticky_social_media($input);
        }
        foreach ([1, 2, 3] as $position) {
            $key = 'sticky_item_' . $position;
            if (!array_key_exists($output[$key], $available_choices)) {
                $output[$key] = 'none';
            }
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
        $sticky_item_choices = $this->get_available_sticky_item_choices($settings);
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

                <div class="oneplugin-tabs oneplugin-tabs--admin" data-tabs>
                    <div class="oneplugin-tabs__nav oneplugin-tabs__nav--admin" role="tablist" aria-label="<?php esc_attr_e('1Plugin settings sections', 'oneplugin-light-site-tools'); ?>">
                        <button type="button" class="oneplugin-tabs__tab is-active" data-tab-trigger="admin-project-data" role="tab" aria-selected="true"><?php esc_html_e('Project Data', 'oneplugin-light-site-tools'); ?></button>
                        <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="admin-styles" role="tab" aria-selected="false"><?php esc_html_e('Styles', 'oneplugin-light-site-tools'); ?></button>
                        <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="admin-modules" role="tab" aria-selected="false"><?php esc_html_e('Modules', 'oneplugin-light-site-tools'); ?></button>
                        <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="admin-extensions" role="tab" aria-selected="false"><?php esc_html_e('Extensions', 'oneplugin-light-site-tools'); ?></button>
                        <button type="button" class="oneplugin-tabs__tab" data-tab-trigger="admin-code" role="tab" aria-selected="false"><?php esc_html_e('Code', 'oneplugin-light-site-tools'); ?></button>
                    </div>
                    <div class="oneplugin-tabs__panels oneplugin-tabs__panels--admin">
                        <div class="oneplugin-tabs__panel is-active" data-tab-panel="admin-project-data" role="tabpanel">
                <div class="postbox oneplugin-card oneplugin-card--identity">
                    <div class="inside oneplugin-card__inside">
                        <div class="oneplugin-project-data-grid">
                            <div class="oneplugin-project-data-main">
                                <section class="oneplugin-project-data-group">
                                    <h3><?php esc_html_e('Identity', 'oneplugin-light-site-tools'); ?></h3>
                                    <div class="oneplugin-project-field-grid">
                                        <?php
                                        $this->render_compact_field('site_title', __('Site title', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_field('company_name', __('Company name', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_field('organization_number', __('Organization number', 'oneplugin-light-site-tools'), $settings);
                                        ?>
                                    </div>
                                </section>
                                <section class="oneplugin-project-data-group">
                                    <h3><?php esc_html_e('Contact', 'oneplugin-light-site-tools'); ?></h3>
                                    <div class="oneplugin-project-field-grid">
                                        <?php
                                        $this->render_compact_field('website', __('Website', 'oneplugin-light-site-tools'), $settings, 'url');
                                        $this->render_compact_field('phone_primary', __('Phone', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_field('email', __('Email', 'oneplugin-light-site-tools'), $settings, 'email');
                                        $this->render_compact_field('street_address', __('Street address', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_field('postal_code', __('Postal code', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_field('city', __('City', 'oneplugin-light-site-tools'), $settings);
                                        ?>
                                    </div>
                                </section>
                                <section class="oneplugin-project-data-group">
                                    <?php $this->render_social_links_section($settings); ?>
                                </section>
                            </div>
                            <aside class="oneplugin-project-data-side">
                                <section class="oneplugin-project-data-group">
                                    <h3><?php esc_html_e('Fav & Logo', 'oneplugin-light-site-tools'); ?></h3>
                                    <div class="oneplugin-brand-assets-grid">
                                        <?php
                                        $this->render_site_icon_field($settings);
                                        $this->render_site_logo_field($settings);
                                        $this->render_logo_media_field('sticky_header_logo_id', __('Sticky', 'oneplugin-light-site-tools'), $settings, '', 'sticky_header_logo_white_filter_enabled');
                                        $this->render_logo_media_field('mobile_logo_id', __('Mobile', 'oneplugin-light-site-tools'), $settings, '', 'mobile_logo_white_filter_enabled');
                                        ?>
                                    </div>
                                    <div class="oneplugin-brand-assets-settings">
                                        <?php
                                        $this->render_compact_field('custom_header_class', __('Custom header class', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_field('custom_logo_class', __('Custom logo class', 'oneplugin-light-site-tools'), $settings);
                                        ?>
                                    </div>
                                </section>
                            </aside>
                        </div>
                    </div>
                </div>
                <?php $this->render_shortcodes_help_card(); ?>
                        </div>

                        <div class="oneplugin-tabs__panel" data-tab-panel="admin-styles" role="tabpanel" hidden>
                <div class="oneplugin-tabs oneplugin-tabs--subtabs" data-tabs>
                    <div class="oneplugin-tabs__nav" role="tablist" aria-label="<?php esc_attr_e('Style sections', 'oneplugin-light-site-tools'); ?>">
                        <button type="button" class="oneplugin-tabs__tab is-active" data-tab-trigger="styles-settings" role="tab" aria-selected="true"><?php esc_html_e('General', 'oneplugin-light-site-tools'); ?></button>
                        <button type="button" class="oneplugin-tabs__tab oneplugin-tabs__tab--with-toggle" data-tab-trigger="styles-header" role="tab" aria-selected="false">
                            <input
                                name="<?php echo esc_attr(self::OPTION_KEY . '[header_enabled]'); ?>"
                                id="header_enabled"
                                type="checkbox"
                                value="1"
                                <?php checked(!empty($settings['header_enabled'])); ?>
                            />
                            <span><?php esc_html_e('Header', 'oneplugin-light-site-tools'); ?></span>
                        </button>
                        <button type="button" class="oneplugin-tabs__tab oneplugin-tabs__tab--with-toggle" data-tab-trigger="styles-mobile-footer" role="tab" aria-selected="false">
                            <input
                                name="<?php echo esc_attr(self::OPTION_KEY . '[sticky_enabled]'); ?>"
                                id="sticky_enabled"
                                type="checkbox"
                                value="1"
                                <?php checked(!empty($settings['sticky_enabled'])); ?>
                            />
                            <span><?php esc_html_e('Mobile Footer', 'oneplugin-light-site-tools'); ?></span>
                        </button>
                        <button type="button" class="oneplugin-tabs__tab oneplugin-tabs__tab--with-toggle" data-tab-trigger="styles-formular" role="tab" aria-selected="false">
                            <input
                                name="<?php echo esc_attr(self::OPTION_KEY . '[style_formidable]'); ?>"
                                id="style_formidable"
                                type="checkbox"
                                value="1"
                                <?php checked(!empty($settings['style_formidable'])); ?>
                            />
                            <span><?php esc_html_e('Formular', 'oneplugin-light-site-tools'); ?></span>
                        </button>
                        <button type="button" class="oneplugin-tabs__tab oneplugin-tabs__tab--with-toggle" data-tab-trigger="styles-scrollbar" role="tab" aria-selected="false">
                            <input
                                name="<?php echo esc_attr(self::OPTION_KEY . '[scrollbar_enabled]'); ?>"
                                id="scrollbar_enabled"
                                type="checkbox"
                                value="1"
                                <?php checked(!empty($settings['scrollbar_enabled'])); ?>
                            />
                            <span><?php esc_html_e('Scrollbar', 'oneplugin-light-site-tools'); ?></span>
                        </button>
                    </div>
                    <div class="oneplugin-tabs__panels">
                        <div class="oneplugin-tabs__panel is-active" data-tab-panel="styles-settings" role="tabpanel">
                            <div class="postbox oneplugin-card">
                                <div class="inside oneplugin-card__inside">
                                    <div class="oneplugin-style-groups">
                                        <section class="oneplugin-style-group">
                                            <?php $this->render_project_palette_fields($settings); ?>
                                        </section>
                                        <section class="oneplugin-style-group">
                                            <h3><?php esc_html_e('Images', 'oneplugin-light-site-tools'); ?></h3>
                                            <div class="oneplugin-option-grid">
                                            <?php
                                            $this->render_compact_checkbox_field('hide_image_alt_text', __('Hide Image Alt-text', 'oneplugin-light-site-tools'), $settings, __('Remove visible alt text output.', 'oneplugin-light-site-tools'));
                                            $this->render_compact_checkbox_field('fix_image_alt_text', __('Fix Image Alt-text', 'oneplugin-light-site-tools'), $settings, __('Clean missing image alt text.', 'oneplugin-light-site-tools'));
                                            $this->render_compact_checkbox_field('cover_images', __('Cover images (.cover-img)', 'oneplugin-light-site-tools'), $settings, __('Makes marked images cover.', 'oneplugin-light-site-tools'));
                                            $this->render_compact_checkbox_field('apply_cover_to_tabs_image', __('Apply cover to tabs image', 'oneplugin-light-site-tools'), $settings, __('Fits images inside tab modules.', 'oneplugin-light-site-tools'));
                                            $this->render_compact_checkbox_field('masonry_gallery_enabled', __('Masonry Gallery layout', 'oneplugin-light-site-tools'), $settings, __('Enables masonry gallery styling.', 'oneplugin-light-site-tools'));
                                            ?>
                                            <div id="oneplugin-masonry-gallery-layout-wrap" class="oneplugin-option-grid__item oneplugin-option-grid__item--select" <?php echo empty($settings['masonry_gallery_enabled']) ? 'hidden' : ''; ?>>
                                                <?php
                                                $this->render_compact_select_field('masonry_gallery_layout', __('Masonry layout type', 'oneplugin-light-site-tools'), $settings, [
                                                    'square' => __('Square', 'oneplugin-light-site-tools'),
                                                    'asymetric' => __('Asymetric', 'oneplugin-light-site-tools'),
                                                ]);
                                                ?>
                                            </div>
                                            </div>
                                        </section>
                                        <section class="oneplugin-style-group">
                                            <h3><?php esc_html_e('Layout', 'oneplugin-light-site-tools'); ?></h3>
                                            <div class="oneplugin-option-grid">
                                            <?php
                                            $this->render_compact_checkbox_field('hide_default_footer', __('Hide default footer', 'oneplugin-light-site-tools'), $settings, __('Hides the theme footer.', 'oneplugin-light-site-tools'));
                                            ?>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="oneplugin-tabs__panel" data-tab-panel="styles-mobile-footer" role="tabpanel" hidden>
                            <div class="postbox oneplugin-card oneplugin-card--footer">
                                <div class="inside oneplugin-card__inside">
                                    <div class="oneplugin-sticky-layout">
                                        <div class="oneplugin-sticky-preview-column">
                                            <?php $this->render_admin_footer_preview($settings); ?>
                                        </div>
                                        <div class="oneplugin-sticky-settings-column">
                                            <div class="oneplugin-sticky-fields">
                                                <?php
                                                $this->render_compact_select_field('sticky_item_1', __('Position 1', 'oneplugin-light-site-tools'), $settings, $sticky_item_choices);
                                                $this->render_compact_select_field('sticky_item_2', __('Position 2', 'oneplugin-light-site-tools'), $settings, $sticky_item_choices);
                                                $this->render_compact_select_field('sticky_item_3', __('Position 3', 'oneplugin-light-site-tools'), $settings, $sticky_item_choices);
                                                $this->render_sticky_custom_fields(1, $settings);
                                                $this->render_sticky_custom_fields(2, $settings);
                                                $this->render_sticky_custom_fields(3, $settings);
                                                $this->render_compact_color_field('sticky_bg_color', __('Background color', 'oneplugin-light-site-tools'), $settings);
                                                $this->render_compact_color_field('sticky_icon_color', __('Icon color', 'oneplugin-light-site-tools'), $settings);
                                                $this->render_compact_color_field('sticky_text_color', __('Text color', 'oneplugin-light-site-tools'), $settings);
                                                $this->render_dimension_control('sticky_width', __('Module width', 'oneplugin-light-site-tools'), $settings, ['%' => '%', 'px' => 'px', 'rem' => 'rem', 'vw' => 'vw']);
                                                $this->render_dimension_control('sticky_radius_top', __('Top corner radius', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem', 'em' => 'em']);
                                                $this->render_dimension_control('sticky_font_size', __('Text size', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem', 'em' => 'em']);
                                                $this->render_dimension_control('sticky_icon_size', __('Icon size', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem', 'em' => 'em']);
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="oneplugin-tabs__panel" data-tab-panel="styles-formular" role="tabpanel" hidden>
                            <div class="postbox oneplugin-card">
                                <div class="inside oneplugin-card__inside">
                                    <div class="oneplugin-option-grid">
                                    <div id="oneplugin-formidable-accent-wrap" class="oneplugin-option-grid__item oneplugin-option-grid__item--color oneplugin-option-grid__item--full" <?php echo empty($settings['style_formidable']) ? 'hidden' : ''; ?>>
                                        <div class="oneplugin-formidable-color-grid">
                                            <?php
                                            $this->render_compact_color_field('formidable_accent_color', __('Formidable accent/border', 'oneplugin-light-site-tools'), $settings);
                                            $this->render_compact_color_field('formidable_background_color', __('Formidable background', 'oneplugin-light-site-tools'), $settings);
                                            $this->render_compact_color_field('formidable_text_color', __('Formidable unchecked text', 'oneplugin-light-site-tools'), $settings);
                                            $this->render_compact_color_field('formidable_checked_text_color', __('Formidable checked text', 'oneplugin-light-site-tools'), $settings);
                                            $this->render_compact_color_field('formidable_checked_background_color', __('Checked background', 'oneplugin-light-site-tools'), $settings);
                                            $this->render_dimension_control('formidable_border_radius', __('Border radius', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem', 'em' => 'em']);
                                            $this->render_dimension_control('formidable_border_width', __('Border width', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem', 'em' => 'em']);
                                            $this->render_compact_field('formidable_padding', __('Padding', 'oneplugin-light-site-tools'), $settings);
                                            ?>
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="oneplugin-tabs__panel" data-tab-panel="styles-scrollbar" role="tabpanel" hidden>
                            <div class="postbox oneplugin-card">
                                <div class="inside oneplugin-card__inside">
                                    <div class="oneplugin-option-grid">
                                        <?php
                                        $this->render_compact_color_field('scrollbar_bg_color', __('Background color', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_compact_color_field('scrollbar_handle_color', __('Handle color', 'oneplugin-light-site-tools'), $settings);
                                        $this->render_dimension_control('scrollbar_radius', __('Border radius', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem', 'em' => 'em']);
                                        $this->render_dimension_control('scrollbar_width', __('Width', 'oneplugin-light-site-tools'), $settings, ['px' => 'px', 'rem' => 'rem']);
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="oneplugin-tabs__panel" data-tab-panel="styles-header" role="tabpanel" hidden>
                            <div class="postbox oneplugin-card">
                                <div class="inside oneplugin-card__inside">
                                    <div class="oneplugin-header-settings">
                                        <section class="oneplugin-header-group">
                                            <h3><?php esc_html_e('Features', 'oneplugin-light-site-tools'); ?></h3>
                                            <div class="oneplugin-header-feature">
                                                <?php $this->render_compact_checkbox_field('transparent_fixed_header', __('Transparent fixed header', 'oneplugin-light-site-tools'), $settings, __('Applies before scroll and pulls the first section under the header.', 'oneplugin-light-site-tools')); ?>
                                                <div id="oneplugin-transparent-header-options-wrap" class="oneplugin-header-suboptions" <?php echo empty($settings['transparent_fixed_header']) ? 'hidden' : ''; ?>>
                                                    <div class="oneplugin-option-grid oneplugin-header-options-grid">
                                                        <?php
                                                        $this->render_compact_checkbox_field('transparent_fixed_header_mobile', __('Apply on mobile too', 'oneplugin-light-site-tools'), $settings);
                                                        $this->render_compact_checkbox_field('transparent_fixed_header_home_only', __('Homepage only', 'oneplugin-light-site-tools'), $settings);
                                                        $this->render_compact_checkbox_field('transparent_header_invert_logo', __('Invert logo on transparent header', 'oneplugin-light-site-tools'), $settings);
                                                        $this->render_compact_color_field('transparent_header_menu_text_color', __('Menu text color (transparent)', 'oneplugin-light-site-tools'), $settings);
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                        <section class="oneplugin-header-group">
                                            <h3><?php esc_html_e('Behavior', 'oneplugin-light-site-tools'); ?></h3>
                                            <div class="oneplugin-option-grid oneplugin-header-options-grid">
                                                <?php $this->render_compact_checkbox_field('active_menu_item_by_section', __('Active menu item by section', 'oneplugin-light-site-tools'), $settings, __('Highlights menu by section.', 'oneplugin-light-site-tools')); ?>
                                            </div>
                                        </section>
                                        <section class="oneplugin-header-group">
                                            <h3><?php esc_html_e('Animation', 'oneplugin-light-site-tools'); ?></h3>
                                            <div class="oneplugin-header-feature">
                                                <?php $this->render_compact_checkbox_field('header_animation_enabled', __('Enable header animation', 'oneplugin-light-site-tools'), $settings); ?>
                                                <div id="oneplugin-header-animation-options-wrap" class="oneplugin-header-suboptions" <?php echo empty($settings['header_animation_enabled']) ? 'hidden' : ''; ?>>
                                                    <div class="oneplugin-option-grid oneplugin-header-options-grid">
                                                        <?php
                                                        $this->render_dimension_control('header_transition_duration', __('Duration', 'oneplugin-light-site-tools'), $settings, ['ms' => 'ms', 's' => 's']);
                                                        $this->render_compact_select_field('header_transition_easing', __('Easing', 'oneplugin-light-site-tools'), $settings, $this->get_header_transition_easing_options());
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>

                        <div class="oneplugin-tabs__panel" data-tab-panel="admin-modules" role="tabpanel" hidden>
                <div class="postbox oneplugin-card oneplugin-modules-card">
                    <div class="inside oneplugin-card__inside">
                        <h2><?php esc_html_e('Modules', 'oneplugin-light-site-tools'); ?></h2>
                        <p><?php esc_html_e('Enable or disable native Divi 5 modules from 1Plugin.', 'oneplugin-light-site-tools'); ?></p>
                        <div class="oneplugin-modules-grid">
                            <?php foreach ($this->get_divi5_module_definitions() as $module_key => $module) : ?>
                                <?php
                                $field_key = 'module_' . $module_key . '_enabled';
                                $is_enabled = !empty($settings[$field_key]);
                                $is_locked = $module_key === 'faq' && empty($settings['extension_faq_enabled']);
                                $is_placeholder = !empty($module['placeholder']);
                                ?>
                                <div class="oneplugin-module-toggle <?php echo ($is_locked || $is_placeholder) ? 'is-inactive' : ''; ?>">
                                    <div class="oneplugin-module-toggle__content">
                                        <h3><?php echo esc_html($module['title']); ?></h3>
                                        <p><?php echo esc_html($module['description']); ?></p>
                                        <?php if ($is_locked) : ?>
                                            <p class="oneplugin-module-toggle__status"><?php esc_html_e('FAQ extension is disabled.', 'oneplugin-light-site-tools'); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($is_placeholder) : ?>
                                        <span class="oneplugin-module-toggle__status"><?php esc_html_e('Coming soon', 'oneplugin-light-site-tools'); ?></span>
                                    <?php elseif ($is_locked) : ?>
                                        <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY . '[' . $field_key . ']'); ?>" value="<?php echo esc_attr($is_enabled ? '1' : '0'); ?>" />
                                    <?php endif; ?>
                                    <?php if (!$is_placeholder) : ?>
                                    <label class="oneplugin-module-toggle__switch" for="<?php echo esc_attr($field_key); ?>">
                                        <input
                                            name="<?php echo esc_attr(self::OPTION_KEY . '[' . $field_key . ']'); ?>"
                                            id="<?php echo esc_attr($field_key); ?>"
                                            type="checkbox"
                                            value="1"
                                            <?php checked($is_enabled); ?>
                                            <?php disabled($is_locked); ?>
                                        />
                                    </label>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                        </div>

                        <div class="oneplugin-tabs__panel" data-tab-panel="admin-extensions" role="tabpanel" hidden>
                <div class="postbox oneplugin-card oneplugin-modules-card">
                    <div class="inside oneplugin-card__inside">
                        <h2><?php esc_html_e('Extensions', 'oneplugin-light-site-tools'); ?></h2>
                        <p><?php esc_html_e('Enable or disable complete plugin extensions.', 'oneplugin-light-site-tools'); ?></p>
                        <div class="oneplugin-modules-grid">
                            <?php foreach ($this->get_extension_definitions() as $extension_key => $extension) : ?>
                                <?php
                                $field_key = 'extension_' . $extension_key . '_enabled';
                                $is_enabled = !empty($settings[$field_key]);
                                ?>
                                <div class="oneplugin-module-toggle">
                                    <div class="oneplugin-module-toggle__content">
                                        <h3><?php echo esc_html($extension['title']); ?></h3>
                                        <p><?php echo esc_html($extension['description']); ?></p>
                                    </div>
                                    <label class="oneplugin-module-toggle__switch" for="<?php echo esc_attr($field_key); ?>">
                                        <input
                                            name="<?php echo esc_attr(self::OPTION_KEY . '[' . $field_key . ']'); ?>"
                                            id="<?php echo esc_attr($field_key); ?>"
                                            type="checkbox"
                                            value="1"
                                            <?php checked($is_enabled); ?>
                                        />
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="oneplugin-release-options">
                            <div class="oneplugin-module-toggle">
                                <div class="oneplugin-module-toggle__content">
                                    <h3><?php esc_html_e('GitHub auto-updates', 'oneplugin-light-site-tools'); ?></h3>
                                    <p><?php esc_html_e('Allow WordPress to install 1Plugin updates automatically when a newer GitHub release is available.', 'oneplugin-light-site-tools'); ?></p>
                                </div>
                                <label class="oneplugin-module-toggle__switch" for="github_auto_updates_enabled">
                                    <input
                                        name="<?php echo esc_attr(self::OPTION_KEY . '[github_auto_updates_enabled]'); ?>"
                                        id="github_auto_updates_enabled"
                                        type="checkbox"
                                        value="1"
                                        <?php checked(!empty($settings['github_auto_updates_enabled'])); ?>
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                        </div>

                        <div class="oneplugin-tabs__panel" data-tab-panel="admin-code" role="tabpanel" hidden>
                <div class="postbox oneplugin-card">
                    <div class="inside oneplugin-card__inside">
                        <h2><?php esc_html_e('Custom Code', 'oneplugin-light-site-tools'); ?></h2>
                        <div class="oneplugin-dev-mode" data-dev-mode>
                            <div class="oneplugin-dev-mode__locked" data-dev-mode-locked>
                                <h3><?php esc_html_e('Developer Mode', 'oneplugin-light-site-tools'); ?></h3>
                                <p><?php esc_html_e('Custom code can break the site if invalid CSS, JavaScript, or PHP is saved. Unlock it only when you intentionally need to edit snippets.', 'oneplugin-light-site-tools'); ?></p>
                                <button type="button" class="button button-secondary" data-dev-mode-unlock><?php esc_html_e('Unlock Custom Code', 'oneplugin-light-site-tools'); ?></button>
                            </div>
                            <div class="oneplugin-dev-mode__content" data-dev-mode-content hidden>
                        <div class="oneplugin-risk-option">
                            <?php
                            $this->render_compact_checkbox_field(
                                'custom_php_enabled',
                                __('Enable Custom PHP execution', 'oneplugin-light-site-tools'),
                                $settings,
                                __('PHP snippets stay saved while this is off, but they will not run on the frontend.', 'oneplugin-light-site-tools')
                            );
                            ?>
                            <p><?php esc_html_e('Only enable this on sites where trusted admins intentionally maintain the PHP snippets below.', 'oneplugin-light-site-tools'); ?></p>
                        </div>
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
                    </div>
                </div>

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

    private function render_compact_field($key, $label, $settings, $type = 'text', $description = '') {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        ?>
        <div style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <?php if ($description !== '') : ?>
                <p style="margin:0 0 8px; color:#50575e;"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
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

    private function render_dimension_control($key, $label, $settings, $units) {
        $value = isset($settings[$key]) ? (string) $settings[$key] : (isset($this->defaults[$key]) ? (string) $this->defaults[$key] : '');
        $number = '';
        $unit = '';

        if (preg_match('/^(\d+(?:\.\d+)?)(px|rem|em|%|vw)$/', $value, $matches)) {
            $number = $matches[1];
            $unit = $matches[2];
        }

        if ($unit === '' || !array_key_exists($unit, $units)) {
            $unit_keys = array_keys($units);
            $unit = isset($unit_keys[0]) ? $unit_keys[0] : 'px';
        }

        ?>
        <div class="oneplugin-option-grid__item oneplugin-dimension-field" data-dimension-field style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key . '_number'); ?>"><?php echo esc_html($label); ?></label>
            <input
                name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                id="<?php echo esc_attr($key); ?>"
                type="hidden"
                value="<?php echo esc_attr($value); ?>"
                data-dimension-value
            />
            <div class="oneplugin-dimension-field__control">
                <input
                    id="<?php echo esc_attr($key . '_number'); ?>"
                    type="number"
                    min="0"
                    step="1"
                    value="<?php echo esc_attr($number); ?>"
                    data-dimension-number
                    aria-label="<?php echo esc_attr($label); ?>"
                />
                <select data-dimension-unit aria-label="<?php echo esc_attr(sprintf(__('Unit for %s', 'oneplugin-light-site-tools'), $label)); ?>">
                    <?php foreach ($units as $unit_value => $unit_label) : ?>
                        <option value="<?php echo esc_attr($unit_value); ?>" <?php selected($unit, $unit_value); ?>><?php echo esc_html($unit_label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
    }

    private function render_shortcodes_help_card() {
        $shortcodes = [
            '[foretag]', '[gata]', '[postkod]', '[ort]', '[mobil1]', '[orgnr]', '[mail]',
            '[kontakt]', '[kundens_mail]',
            '[kundens_foretag]', '[kundens_adress]', '[kundens_telefon]', '[karta]',
            '[hemsida]', '[kundens_hemsida]', '[kundens_facebook]', '[kundens_instagram]',
            '[kundens_linkedin]', '[kundens_youtube]', '[kundens_x]', '[kundens_reddit]',
            '[kundens_bokadirekt]',
            '[sokordets_tjanst_rubrik]', '[sokordets_ort_rubrik]',
            '[sokordets_tjanst_brodtext]', '[sokordets_ort_brodtext]',
        ];
        ?>
        <div class="postbox oneplugin-card oneplugin-collapsible">
            <div class="inside oneplugin-card__inside">
                <div class="oneplugin-collapsible__header">
                    <button type="button" class="oneplugin-collapsible__toggle" data-collapsible-toggle aria-expanded="false">
                        <span><?php esc_html_e('Shortcodes', 'oneplugin-light-site-tools'); ?></span>
                        <span class="oneplugin-collapsible__chevron" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="oneplugin-collapsible__content" data-collapsible-content hidden>
                    <p><?php esc_html_e('Click any shortcode to copy it.', 'oneplugin-light-site-tools'); ?></p>
                    <div class="oneplugin-shortcodes">
                        <?php foreach ($shortcodes as $shortcode) : ?>
                            <button type="button" class="oneplugin-shortcode-copy" data-shortcode="<?php echo esc_attr($shortcode); ?>"><?php echo esc_html($shortcode); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
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
            'booking_url' => __('Booking', 'oneplugin-light-site-tools'),
        ];
    }

    private function get_divi5_module_definitions() {
        return [
            'menu' => [
                'title' => __('Menu', 'oneplugin-light-site-tools'),
                'description' => __('Native Divi 5 menu module using WordPress menus and Divi layout controls.', 'oneplugin-light-site-tools'),
            ],
            'faq' => [
                'title' => __('FAQ', 'oneplugin-light-site-tools'),
                'description' => __('Native Divi 5 FAQ accordion module using the FAQ posts and groups managed under 1Plugin.', 'oneplugin-light-site-tools'),
            ],
            'logo' => [
                'title' => __('Logo', 'oneplugin-light-site-tools'),
                'description' => __('Native Divi 5 logo module using the main, sticky, and mobile logo variants from 1Plugin.', 'oneplugin-light-site-tools'),
            ],
            'tabs' => [
                'title' => __('Tabs', 'oneplugin-light-site-tools'),
                'description' => __('In development', 'oneplugin-light-site-tools'),
                'placeholder' => true,
            ],
            'image' => [
                'title' => __('Image', 'oneplugin-light-site-tools'),
                'description' => __('In development', 'oneplugin-light-site-tools'),
                'placeholder' => true,
            ],
            'video' => [
                'title' => __('Video', 'oneplugin-light-site-tools'),
                'description' => __('In development', 'oneplugin-light-site-tools'),
                'placeholder' => true,
            ],
            'carousel' => [
                'title' => __('Carousel', 'oneplugin-light-site-tools'),
                'description' => __('In development', 'oneplugin-light-site-tools'),
                'placeholder' => true,
            ],
        ];
    }

    private function get_extension_definitions() {
        return [
            'faq' => [
                'title' => __('FAQ', 'oneplugin-light-site-tools'),
                'description' => __('FAQ posts, groups, schema output, shortcode rendering, and FAQ module data.', 'oneplugin-light-site-tools'),
            ],
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

    private function render_compact_checkbox_field($key, $label, $settings, $description = '') {
        $checked = !empty($settings[$key]);
        ?>
        <div class="oneplugin-option-grid__item oneplugin-option-grid__item--checkbox" style="margin-bottom:14px;">
            <label class="oneplugin-toggle-field" for="<?php echo esc_attr($key); ?>">
                <input
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    type="checkbox"
                    value="1"
                    <?php checked($checked); ?>
                />
                <span class="oneplugin-toggle-field__text">
                    <span class="oneplugin-toggle-field__label"><?php echo esc_html($label); ?></span>
                    <?php if ($description !== '') : ?>
                        <span class="oneplugin-toggle-field__description"><?php echo esc_html($description); ?></span>
                    <?php endif; ?>
                </span>
            </label>
        </div>
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

    private function render_sticky_custom_fields($position, $settings) {
        $item_key = 'sticky_item_' . $position;
        $text_key = 'sticky_custom_' . $position . '_text';
        $link_key = 'sticky_custom_' . $position . '_link';
        $icon_key = 'sticky_custom_' . $position . '_icon';
        $is_custom = isset($settings[$item_key]) && $settings[$item_key] === 'custom';
        ?>
        <div class="oneplugin-sticky-custom-fields" data-sticky-custom-position="<?php echo esc_attr((string) $position); ?>" <?php echo $is_custom ? '' : 'hidden'; ?>>
            <?php
            $this->render_compact_field($text_key, sprintf(__('%d. Text', 'oneplugin-light-site-tools'), $position), $settings);
            $this->render_compact_field($link_key, sprintf(__('%d. Link', 'oneplugin-light-site-tools'), $position), $settings, 'text');
            $this->render_compact_field($icon_key, sprintf(__('%d. Icon', 'oneplugin-light-site-tools'), $position), $settings);
            ?>
        </div>
        <?php
    }

    private function render_compact_color_field($key, $label, $settings) {
        $value = isset($settings[$key]) ? $settings[$key] : '';
        $picker_value = $this->get_admin_color_picker_value($value, $settings);
        $swatch_value = $this->get_admin_color_swatch_value($value, $settings);
        $current_variable = $this->extract_css_variable_name($value);
        $is_custom = $current_variable === '';
        $source_value = $is_custom ? $value : 'var(' . $current_variable . ')';
        ?>
        <div style="margin-bottom:14px;">
            <label for="<?php echo esc_attr($key); ?>" style="display:block; margin-bottom:6px; font-weight:600;"><?php echo esc_html($label); ?></label>
            <div class="oneplugin-color-field" data-color-field>
                <input
                    type="color"
                    class="oneplugin-color-field__picker"
                    value="<?php echo esc_attr($picker_value); ?>"
                    data-color-picker
                    tabindex="-1"
                    aria-hidden="true"
                />
                <button
                    type="button"
                    class="oneplugin-color-field__swatch"
                    data-color-swatch
                    style="<?php echo esc_attr('--oneplugin-admin-swatch:' . $swatch_value . ';'); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Choose color for %s', 'oneplugin-light-site-tools'), $label)); ?>"
                ></button>
                <input
                    name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                    id="<?php echo esc_attr($key); ?>"
                    type="hidden"
                    data-color-value
                    value="<?php echo esc_attr($value); ?>"
                />
                <input
                    type="text"
                    class="regular-text"
                    data-color-mode
                    value="<?php echo esc_attr($source_value); ?>"
                    <?php echo $is_custom ? '' : 'readonly'; ?>
                    aria-label="<?php echo esc_attr(sprintf(__('Color source for %s', 'oneplugin-light-site-tools'), $label)); ?>"
                />
            </div>
        </div>
        <?php
    }

    private function get_admin_color_swatch_value($value, $settings = null) {
        $value = trim((string) $value);
        if ($value === '') {
            return '#000000';
        }

        $variable = $this->extract_css_variable_name($value);
        if ($variable !== '') {
            $resolved = $this->resolve_project_palette_variable($variable, $settings);
            $resolved = $this->sanitize_color_value($resolved, '');
            if ($resolved !== '') {
                return 'var(' . $variable . ', ' . $resolved . ')';
            }
        }

        $sanitized = $this->sanitize_color_value($value, '');
        if ($sanitized !== '') {
            return $sanitized;
        }

        return '#000000';
    }

    private function get_admin_color_picker_value($value, $settings = null) {
        $value = trim((string) $value);
        $hex = $this->normalize_hex_color($value);
        if ($hex !== '') {
            return $hex;
        }

        $variable = $this->extract_css_variable_name($value);
        if ($variable !== '') {
            $resolved = $this->resolve_project_palette_variable($variable, $settings);
            $hex = $this->normalize_hex_color($resolved);
            if ($hex !== '') {
                return $hex;
            }

            $hex = $this->normalize_hex_color($this->extract_css_variable_fallback($value));
            if ($hex !== '') {
                return $hex;
            }
        }

        return '#000000';
    }

    private function normalize_hex_color($value) {
        $value = strtolower(trim((string) $value));
        if (preg_match('/^#[0-9a-f]{6}$/', $value)) {
            return $value;
        }

        if (preg_match('/^#[0-9a-f]{3}$/', $value)) {
            return '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
        }

        return '';
    }

    private function extract_css_variable_name($value) {
        $value = trim((string) $value);
        if (preg_match('/^--[a-zA-Z0-9_-]+$/', $value)) {
            return $value;
        }

        if (preg_match('/^var\(\s*(--[a-zA-Z0-9_-]+)(?:\s*,\s*.+)?\s*\)$/', $value, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function extract_css_variable_fallback($value) {
        $value = trim((string) $value);
        if (preg_match('/^var\(\s*--[a-zA-Z0-9_-]+\s*,\s*(.+)\s*\)$/', $value, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function resolve_project_palette_variable($variable, $settings = null) {
        $palette = is_array($settings) ? $this->get_project_color_palette_from_settings($settings) : $this->get_project_color_palette();
        foreach ($palette as $swatch) {
            if (!empty($swatch['variable']) && $swatch['variable'] === $variable && !empty($swatch['value'])) {
                return (string) $swatch['value'];
            }
        }

        return '';
    }

    private function render_site_icon_field($settings) {
        $site_icon_id = isset($settings['site_icon_id']) ? absint($settings['site_icon_id']) : 0;
        $site_icon_url = $site_icon_id ? wp_get_attachment_image_url($site_icon_id, 'thumbnail') : '';
        ?>
        <div class="oneplugin-brand-asset oneplugin-brand-asset--icon" style="margin-bottom:14px;">
            <div class="oneplugin-brand-asset__header">
                <label for="site_icon_id"><?php esc_html_e('Favicon', 'oneplugin-light-site-tools'); ?></label>
            </div>
            <div class="oneplugin-media-field">
                <button type="button" id="oneplugin-site-icon-preview" class="oneplugin-media-field__preview oneplugin-media-field__preview--icon" data-media-preview="site_icon_id" aria-label="<?php esc_attr_e('Choose or remove favicon', 'oneplugin-light-site-tools'); ?>">
                    <?php if ($site_icon_url) : ?>
                        <img src="<?php echo esc_url($site_icon_url); ?>" alt="" style="max-width:100%; max-height:100%;" />
                    <?php else : ?>
                        <span><?php esc_html_e('Fav', 'oneplugin-light-site-tools'); ?></span>
                    <?php endif; ?>
                </button>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY . '[site_icon_id]'); ?>" id="site_icon_id" value="<?php echo esc_attr($site_icon_id); ?>" data-media-input data-media-size="thumbnail" data-media-empty="Fav" />
            </div>
        </div>
        <?php
    }

    private function render_site_logo_field($settings) {
        $site_logo_id = isset($settings['site_logo_id']) ? absint($settings['site_logo_id']) : 0;
        $site_logo_url = $site_logo_id ? wp_get_attachment_image_url($site_logo_id, 'medium') : '';
        $white_enabled = !empty($settings['site_logo_white_filter_enabled']);
        ?>
        <div class="oneplugin-brand-asset oneplugin-brand-asset--logo" style="margin-bottom:14px;">
            <div class="oneplugin-brand-asset__header">
                <label for="site_logo_id"><?php esc_html_e('Main', 'oneplugin-light-site-tools'); ?></label>
                <?php $this->render_inline_white_logo_toggle('site_logo_white_filter_enabled', $settings); ?>
            </div>
            <div class="oneplugin-media-field">
                <button type="button" id="oneplugin-site-logo-preview" class="oneplugin-media-field__preview oneplugin-media-field__preview--logo <?php echo $white_enabled ? 'is-white-preview' : ''; ?>" data-media-preview="site_logo_id" data-brand-white-preview="site_logo_white_filter_enabled" aria-label="<?php esc_attr_e('Choose or remove logo', 'oneplugin-light-site-tools'); ?>">
                    <?php if ($site_logo_url) : ?>
                        <img src="<?php echo esc_url($site_logo_url); ?>" alt="" style="max-width:100%; max-height:100%;" />
                    <?php else : ?>
                        <span><?php esc_html_e('Logo', 'oneplugin-light-site-tools'); ?></span>
                    <?php endif; ?>
                </button>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY . '[site_logo_id]'); ?>" id="site_logo_id" value="<?php echo esc_attr($site_logo_id); ?>" data-media-input data-media-size="medium" data-media-empty="Logo" />
            </div>
        </div>
        <?php
    }

    private function render_logo_media_field($key, $label, $settings, $description = '', $white_filter_key = '') {
        $attachment_id = isset($settings[$key]) ? absint($settings[$key]) : 0;
        $image_url = $attachment_id ? wp_get_attachment_image_url($attachment_id, 'medium') : '';
        $white_enabled = $white_filter_key !== '' && !empty($settings[$white_filter_key]);
        ?>
        <div class="oneplugin-brand-asset oneplugin-brand-asset--logo" style="margin-bottom:14px;">
            <div class="oneplugin-brand-asset__header">
                <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label>
                <?php if ($white_filter_key !== '') : ?>
                    <?php $this->render_inline_white_logo_toggle($white_filter_key, $settings); ?>
                <?php endif; ?>
            </div>
            <?php if ($description !== '') : ?>
                <p style="margin:0 0 8px; color:#50575e;"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
            <div class="oneplugin-media-field">
                <button type="button" class="oneplugin-media-field__preview oneplugin-media-field__preview--logo <?php echo $white_enabled ? 'is-white-preview' : ''; ?>" data-media-preview="<?php echo esc_attr($key); ?>" <?php echo $white_filter_key !== '' ? 'data-brand-white-preview="' . esc_attr($white_filter_key) . '"' : ''; ?> aria-label="<?php echo esc_attr(sprintf(__('Choose or remove %s', 'oneplugin-light-site-tools'), $label)); ?>">
                    <?php if ($image_url) : ?>
                        <img src="<?php echo esc_url($image_url); ?>" alt="" style="max-width:100%; max-height:100%;" />
                    <?php else : ?>
                        <span><?php esc_html_e('Default', 'oneplugin-light-site-tools'); ?></span>
                    <?php endif; ?>
                </button>
                <input type="hidden" name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($attachment_id); ?>" data-media-input data-media-size="medium" data-media-empty="Default" />
            </div>
        </div>
        <?php
    }

    private function render_inline_white_logo_toggle($key, $settings) {
        $checked = !empty($settings[$key]);
        ?>
        <label class="oneplugin-mini-toggle" for="<?php echo esc_attr($key); ?>" title="<?php esc_attr_e('Invert', 'oneplugin-light-site-tools'); ?>">
            <input
                name="<?php echo esc_attr(self::OPTION_KEY . '[' . $key . ']'); ?>"
                id="<?php echo esc_attr($key); ?>"
                type="checkbox"
                value="1"
                data-brand-white-toggle="<?php echo esc_attr($key); ?>"
                <?php checked($checked); ?>
            />
            <span class="screen-reader-text"><?php esc_html_e('Invert', 'oneplugin-light-site-tools'); ?></span>
        </label>
        <?php
    }

    private function render_project_palette_fields($settings) {
        $palette = $this->get_project_color_palette_from_settings($settings);
        $fields = $this->get_project_palette_fields();
        ?>
        <div class="oneplugin-palette-fields" data-project-palette data-option-key="<?php echo esc_attr(self::OPTION_KEY); ?>" style="margin-bottom:14px;">
            <div class="oneplugin-palette-fields__header">
                <label><?php esc_html_e('Color palette', 'oneplugin-light-site-tools'); ?></label>
                <button type="button" class="button button-secondary oneplugin-palette-add" data-palette-add><?php esc_html_e('Add color', 'oneplugin-light-site-tools'); ?></button>
            </div>
            <div class="oneplugin-palette-fields__grid">
                <?php foreach ($palette as $key => $swatch) : ?>
                    <?php $this->render_project_palette_row($key, $swatch, isset($fields[$key])); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    private function render_project_palette_row($key, $swatch, $is_locked) {
        $key = sanitize_key($key);
        $name = isset($swatch['name']) ? (string) $swatch['name'] : '';
        $variable = isset($swatch['variable']) ? (string) $swatch['variable'] : '';
        $value = isset($swatch['value']) ? (string) $swatch['value'] : '#000000';
        $picker_value = $this->get_admin_color_picker_value($value);
        $swatch_value = $this->get_admin_color_swatch_value($value);
        ?>
        <div class="oneplugin-palette-field<?php echo $is_locked ? ' is-locked' : ''; ?>" data-palette-row>
            <input
                type="color"
                class="oneplugin-palette-field__picker"
                value="<?php echo esc_attr($picker_value); ?>"
                data-palette-color
                tabindex="-1"
                aria-hidden="true"
                aria-label="<?php echo esc_attr(sprintf(__('Color picker for %s', 'oneplugin-light-site-tools'), $name)); ?>"
            />
            <div class="oneplugin-palette-field__top">
                <button
                    type="button"
                    class="oneplugin-palette-field__swatch"
                    data-palette-swatch
                    style="<?php echo esc_attr('--oneplugin-admin-swatch:' . $swatch_value . ';'); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Choose color for %s', 'oneplugin-light-site-tools'), $name)); ?>"
                ></button>
                <input
                    type="text"
                    name="<?php echo esc_attr(self::OPTION_KEY . '[project_palette][' . $key . '][name]'); ?>"
                    value="<?php echo esc_attr($name); ?>"
                    class="oneplugin-palette-field__name"
                    <?php echo $is_locked ? 'readonly' : ''; ?>
                    aria-label="<?php esc_attr_e('Color name', 'oneplugin-light-site-tools'); ?>"
                />
            </div>
            <div class="oneplugin-palette-field__meta">
                <input
                    type="text"
                    name="<?php echo esc_attr(self::OPTION_KEY . '[project_palette][' . $key . '][value]'); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    class="oneplugin-palette-field__value"
                    data-palette-value
                    aria-label="<?php esc_attr_e('Color value', 'oneplugin-light-site-tools'); ?>"
                />
                <input
                    type="hidden"
                    name="<?php echo esc_attr(self::OPTION_KEY . '[project_palette][' . $key . '][variable]'); ?>"
                    value="<?php echo esc_attr($variable); ?>"
                    class="oneplugin-palette-field__variable"
                    aria-label="<?php esc_attr_e('CSS variable', 'oneplugin-light-site-tools'); ?>"
                />
            </div>
            <button
                type="button"
                class="oneplugin-palette-copy"
                data-palette-copy="<?php echo esc_attr($variable); ?>"
                title="<?php echo esc_attr($variable); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('Copy %s', 'oneplugin-light-site-tools'), $variable)); ?>"
            ><?php esc_html_e('Copy', 'oneplugin-light-site-tools'); ?></button>
            <?php if (!$is_locked) : ?>
                <button type="button" class="button-link-delete oneplugin-palette-remove" data-palette-remove aria-label="<?php esc_attr_e('Remove color', 'oneplugin-light-site-tools'); ?>">&times;</button>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_sticky_item_choices() {
        return [
            'none' => __('None', 'oneplugin-light-site-tools'),
            'custom' => __('Custom', 'oneplugin-light-site-tools'),
            'phone' => __('Phone', 'oneplugin-light-site-tools'),
            'email' => __('Email', 'oneplugin-light-site-tools'),
            'website' => __('Website', 'oneplugin-light-site-tools'),
            'facebook' => __('Facebook', 'oneplugin-light-site-tools'),
            'instagram' => __('Instagram', 'oneplugin-light-site-tools'),
            'linkedin' => __('LinkedIn', 'oneplugin-light-site-tools'),
            'youtube' => __('YouTube', 'oneplugin-light-site-tools'),
            'x' => __('X', 'oneplugin-light-site-tools'),
            'reddit' => __('Reddit', 'oneplugin-light-site-tools'),
            'booking' => __('Booking', 'oneplugin-light-site-tools'),
        ];
    }

    private function get_available_sticky_item_choices($settings) {
        $all_choices = $this->get_sticky_item_choices();
        $available = [
            'none' => $all_choices['none'],
            'custom' => $all_choices['custom'],
        ];
        $field_map = [
            'phone' => 'phone_primary',
            'email' => 'email',
            'website' => 'website',
            'facebook' => 'facebook_url',
            'instagram' => 'instagram_url',
            'linkedin' => 'linkedin_url',
            'youtube' => 'youtube_url',
            'x' => 'x_url',
            'reddit' => 'reddit_url',
            'booking' => 'booking_url',
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
            return $this->sanitize_project_palette([]);
        }

        return $this->sanitize_project_palette($saved['project_palette']);
    }

    private function get_project_color_palette_from_settings($settings) {
        $settings = is_array($settings) ? $settings : [];
        return $this->sanitize_project_palette(isset($settings['project_palette']) ? $settings['project_palette'] : []);
    }

    private function get_project_palette_fields() {
        return [
            'primary' => [
                'label' => __('Primary', 'oneplugin-light-site-tools'),
                'variable' => '--1pcv-primary',
                'default' => '#fa1e9a',
            ],
            'secondary' => [
                'label' => __('Secondary', 'oneplugin-light-site-tools'),
                'variable' => '--1pcv-secondary',
                'default' => '#111827',
            ],
            'background' => [
                'label' => __('Background', 'oneplugin-light-site-tools'),
                'variable' => '--1pcv-background',
                'default' => '#ffffff',
            ],
            'background_alt' => [
                'label' => __('Background alt', 'oneplugin-light-site-tools'),
                'variable' => '--1pcv-background-alt',
                'default' => '#f3f4f6',
            ],
        ];
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

        $fields = $this->get_project_palette_fields();
        $has_fixed_keys = false;
        foreach (array_keys($fields) as $key) {
            if (array_key_exists($key, $palette)) {
                $has_fixed_keys = true;
                break;
            }
        }

        $palette_keys = array_keys($palette);
        $is_legacy_list = $palette_keys === range(0, count($palette_keys) - 1);
        if (!$has_fixed_keys && !empty($palette) && $is_legacy_list) {
            $legacy_values = array_values($palette);
            $palette = [];
            $index = 0;
            foreach (array_keys($fields) as $key) {
                if (!isset($legacy_values[$index])) {
                    break;
                }

                $legacy_value = $legacy_values[$index];
                $palette[$key] = is_array($legacy_value) && isset($legacy_value['value']) ? $legacy_value['value'] : $legacy_value;
                $index++;
            }
        }

        $sanitized = [];
        foreach ($fields as $key => $field) {
            $swatch = isset($palette[$key]) ? $palette[$key] : null;
            $value = '';

            if (is_array($swatch) && isset($swatch['value'])) {
                $value = strtolower(trim((string) $swatch['value']));
            } elseif (is_string($swatch)) {
                $value = strtolower(trim($swatch));
            }

            if (!preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $value)) {
                $value = $field['default'];
            }

            if (strlen($value) === 4) {
                $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
            }

            $sanitized[$key] = [
                'name' => $field['label'],
                'variable' => $field['variable'],
                'value' => $value,
            ];
        }

        foreach ($palette as $raw_key => $swatch) {
            $key = sanitize_key((string) $raw_key);
            if ($key === '' || isset($fields[$key])) {
                continue;
            }

            if (!is_array($swatch) || !empty($swatch['_delete'])) {
                continue;
            }

            $name = isset($swatch['name']) ? sanitize_text_field((string) $swatch['name']) : '';
            $variable = isset($swatch['variable']) ? trim((string) $swatch['variable']) : '';
            $value = isset($swatch['value']) ? trim((string) $swatch['value']) : '';
            $value = $this->sanitize_color_value($value, '');

            if (!preg_match('/^--1pcv-[a-zA-Z0-9_-]+$/', $variable)) {
                $source = $name !== '' ? $name : $key;
                $variable = '--1pcv-' . sanitize_key(str_replace(' ', '-', strtolower($source)));
            }

            if ($variable === '--1pcv-' || $value === '') {
                continue;
            }

            if ($name === '') {
                $name = ucwords(str_replace(['-', '_'], ' ', preg_replace('/^--(?:1pcv-)?/', '', $variable)));
            }

            $base_key = $key;
            $index = 2;
            while (isset($sanitized[$key])) {
                $key = $base_key . '_' . $index;
                $index++;
            }

            $sanitized[$key] = [
                'name' => $name,
                'variable' => $variable,
                'value' => $value,
            ];
        }

        return $sanitized;
    }

    private function render_admin_footer_preview($settings) {
        $bg = isset($settings['sticky_bg_color']) ? $settings['sticky_bg_color'] : '#0f0f0f';
        $icon = isset($settings['sticky_icon_color']) ? $settings['sticky_icon_color'] : '#ffffff';
        $text = isset($settings['sticky_text_color']) ? $settings['sticky_text_color'] : '#ffffff';
        $width = isset($settings['sticky_width']) ? $settings['sticky_width'] : $this->defaults['sticky_width'];
        $radius = isset($settings['sticky_radius_top']) ? $settings['sticky_radius_top'] : $this->defaults['sticky_radius_top'];
        $font_size = isset($settings['sticky_font_size']) ? $settings['sticky_font_size'] : $this->defaults['sticky_font_size'];
        $icon_size = isset($settings['sticky_icon_size']) ? $settings['sticky_icon_size'] : $this->defaults['sticky_icon_size'];
        $items = $this->get_sticky_footer_items($settings);
        ?>
        <div id="oneplugin-preview-root" class="oneplugin-phone-preview">
            <div class="oneplugin-phone-preview__device">
                <div class="oneplugin-phone-preview__speaker" aria-hidden="true"></div>
                <div class="oneplugin-phone-preview__screen">
                    <iframe
                        class="oneplugin-phone-preview__iframe"
                        src="<?php echo esc_url(home_url('/')); ?>"
                        title="<?php esc_attr_e('Homepage mobile footer preview', 'oneplugin-light-site-tools'); ?>"
                        loading="lazy"
                        scrolling="no"
                    ></iframe>
                    <div id="oneplugin-preview-bar" class="oneplugin-phone-preview__bar" style="width:<?php echo esc_attr($width); ?>; max-width:100%; margin:0 auto; border-radius:<?php echo esc_attr($radius); ?> <?php echo esc_attr($radius); ?> 0 0; background:<?php echo esc_attr($bg); ?>;">
                        <div style="display:flex; gap:10px; justify-content:space-around; align-items:center;">
                        <?php foreach ($items as $item) : ?>
                            <a href="<?php echo esc_attr($this->escape_sticky_footer_href($item['link'])); ?>" class="oneplugin-preview-item" title="<?php echo esc_attr($item['link']); ?>" style="text-decoration:none; display:flex; flex-direction:column; gap:6px; align-items:center; justify-content:center; min-width:64px;">
                                <i class="<?php echo esc_attr($item['icon']); ?>" aria-hidden="true" style="font-size:<?php echo esc_attr($icon_size); ?>; line-height:1; color:<?php echo esc_attr($icon); ?>;"></i>
                                <span style="font-size:<?php echo esc_attr($font_size); ?>; line-height:1; color:<?php echo esc_attr($text); ?>;"><?php echo esc_html($item['text']); ?></span>
                            </a>
                        <?php endforeach; ?>
                        </div>
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
                'custom' => ['linkField' => '', 'text' => 'Link', 'icon' => 'fa-solid fa-link', 'custom' => true],
                'phone' => ['linkField' => 'phone_primary', 'text' => 'Call', 'icon' => 'fa-solid fa-phone', 'linkPrefix' => 'tel:'],
                'email' => ['linkField' => 'email', 'text' => 'Email', 'icon' => 'fa-solid fa-envelope', 'linkPrefix' => 'mailto:'],
                'facebook' => ['linkField' => 'facebook_url', 'text' => 'Like', 'icon' => 'fa-brands fa-facebook'],
                'instagram' => ['linkField' => 'instagram_url', 'text' => 'Follow', 'icon' => 'fa-brands fa-instagram'],
                'linkedin' => ['linkField' => 'linkedin_url', 'text' => 'Follow', 'icon' => 'fa-brands fa-linkedin'],
                'youtube' => ['linkField' => 'youtube_url', 'text' => 'Watch', 'icon' => 'fa-brands fa-youtube'],
                'x' => ['linkField' => 'x_url', 'text' => 'Follow', 'icon' => 'fa-brands fa-x-twitter'],
                'reddit' => ['linkField' => 'reddit_url', 'text' => 'Join', 'icon' => 'fa-brands fa-reddit'],
                'booking' => ['linkField' => 'booking_url', 'text' => 'Book', 'icon' => 'fa-solid fa-calendar-check'],
                'website' => ['linkField' => 'website', 'text' => 'Visit', 'icon' => 'fa-solid fa-globe'],
            ],
            'stickyItemChoices' => $this->get_sticky_item_choices(),
            'projectPalette' => $this->get_project_color_palette(),
        ];
    }

    private function sanitize_color_value($value, $default) {
        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        if ($value === 'transparent') {
            return $value;
        }

        if (preg_match('/^--[a-zA-Z0-9_-]+$/', $value)) {
            return 'var(' . $value . ')';
        }

        if (preg_match('/^var\(\s*--[a-zA-Z0-9_-]+\s*\)$/', $value)) {
            return preg_replace('/^var\(\s*(--[a-zA-Z0-9_-]+)\s*\)$/', 'var($1)', $value);
        }

        if (preg_match('/^var\(\s*(--[a-zA-Z0-9_-]+)\s*,\s*(transparent|#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})|rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\))\s*\)$/', $value, $matches)) {
            return 'var(' . $matches[1] . ', ' . $matches[2] . ')';
        }

        if (preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value)) {
            return $value;
        }

        if (preg_match('/^rgba?\(\s*(?:\d{1,3}\s*,\s*){2}\d{1,3}(?:\s*,\s*(?:0|1|0?\.\d+))?\s*\)$/', $value)) {
            return $value;
        }

        return $default;
    }

    private function sanitize_css_class_setting($value, $default) {
        $value = trim((string) wp_unslash($value));
        $value = ltrim($value, '.');
        $value = preg_replace('/\s+/', '-', $value);
        $value = sanitize_html_class($value);

        return $value !== '' ? $value : $default;
    }

    private function sanitize_css_length_value($value, $default, $allow_zero = false) {
        $value = strtolower(trim((string) wp_unslash($value)));

        if ($value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            $value .= 'px';
        }

        if (!preg_match('/^(\d+(?:\.\d+)?)(px|rem|em|%|vw)$/', $value, $matches)) {
            return $default;
        }

        $number = (float) $matches[1];
        if ($number < 0 || (!$allow_zero && $number <= 0)) {
            return $default;
        }

        if ($matches[2] === '%' && $number > 100) {
            return $default;
        }

        if ($matches[2] === 'vw' && $number > 100) {
            return $default;
        }

        if ($matches[2] === 'px' && $number > 2400) {
            return $default;
        }

        if (($matches[2] === 'rem' || $matches[2] === 'em') && $number > 40) {
            return $default;
        }

        $normalized_number = strpos($matches[1], '.') === false ? $matches[1] : rtrim(rtrim($matches[1], '0'), '.');

        return $normalized_number . $matches[2];
    }

    private function sanitize_css_time_value($value, $default) {
        $value = strtolower(trim((string) wp_unslash($value)));

        if ($value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            $value .= 'ms';
        }

        if (!preg_match('/^(\d+(?:\.\d+)?)(ms|s)$/', $value, $matches)) {
            return $default;
        }

        $number = (float) $matches[1];
        if ($number < 0) {
            return $default;
        }

        if ($matches[2] === 'ms' && $number > 3000) {
            return $default;
        }

        if ($matches[2] === 's' && $number > 3) {
            return $default;
        }

        $normalized_number = strpos($matches[1], '.') === false ? $matches[1] : rtrim(rtrim($matches[1], '0'), '.');

        return $normalized_number . $matches[2];
    }

    private function sanitize_css_box_value($value, $default) {
        $value = strtolower(trim((string) wp_unslash($value)));

        if ($value === '') {
            return $default;
        }

        $parts = preg_split('/\s+/', $value);
        if (!is_array($parts) || count($parts) < 1 || count($parts) > 4) {
            return $default;
        }

        $clean = [];
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                $part .= 'px';
            }

            if (!preg_match('/^(\d+(?:\.\d+)?)(px|rem|em|%)$/', $part, $matches)) {
                return $default;
            }

            $number = (float) $matches[1];
            $unit = $matches[2];
            if ($number < 0) {
                return $default;
            }

            if ($unit === '%' && $number > 100) {
                return $default;
            }

            if ($unit === 'px' && $number > 2400) {
                return $default;
            }

            if (($unit === 'rem' || $unit === 'em') && $number > 40) {
                return $default;
            }

            $normalized_number = strpos($matches[1], '.') === false ? $matches[1] : rtrim(rtrim($matches[1], '0'), '.');
            $clean[] = $normalized_number . $unit;
        }

        return implode(' ', $clean);
    }

    private function get_header_transition_easing_options() {
        return [
            'ease' => __('Ease', 'oneplugin-light-site-tools'),
            'ease-out' => __('Ease out', 'oneplugin-light-site-tools'),
            'ease-in-out' => __('Ease in-out', 'oneplugin-light-site-tools'),
            'linear' => __('Linear', 'oneplugin-light-site-tools'),
        ];
    }

    private function sanitize_sticky_footer_link($value) {
        $value = trim((string) wp_unslash($value));

        if ($value === '') {
            return '';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $value)) {
            return '';
        }

        if (strpos($value, '#') === 0) {
            return preg_match('/^#[A-Za-z0-9_-]+$/', $value) ? $value : '';
        }

        if (strpos($value, '//') === 0) {
            return '';
        }

        if (strpos($value, '/') === 0) {
            return preg_match('#^/[A-Za-z0-9._~!$&\'()*+,;=:@%/-]*(?:\\?[A-Za-z0-9._~!$&\'()*+,;=:@%/?-]*)?(?:#[A-Za-z0-9_-]+)?$#', $value) ? $value : '';
        }

        $scheme = wp_parse_url($value, PHP_URL_SCHEME);
        if ($scheme !== null && $scheme !== false) {
            $scheme = strtolower((string) $scheme);
            if (!in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                return '';
            }

            return esc_url_raw($value, ['http', 'https', 'mailto', 'tel']);
        }

        return '';
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

        if (empty($settings['sticky_header_logo_id'])) {
            $settings['sticky_header_logo_id'] = $settings['site_logo_id'];
        }

        if (empty($settings['mobile_logo_id'])) {
            $settings['mobile_logo_id'] = $settings['site_logo_id'];
        }

        if (!empty($settings['logo_white_filter_enabled']) && empty($settings['site_logo_white_filter_enabled']) && empty($settings['sticky_header_logo_white_filter_enabled']) && empty($settings['mobile_logo_white_filter_enabled'])) {
            $settings['site_logo_white_filter_enabled'] = '1';
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
        $saved_settings = is_array($saved) ? $saved : [];
        if (!array_key_exists('sticky_item_1', $saved_settings) || empty($settings['sticky_item_1'])) {
            $settings['sticky_item_1'] = 'phone';
        }
        if (!array_key_exists('sticky_item_2', $saved_settings) || empty($settings['sticky_item_2'])) {
            $settings['sticky_item_2'] = 'email';
        }
        if (!array_key_exists('sticky_item_3', $saved_settings) || empty($settings['sticky_item_3'])) {
            $settings['sticky_item_3'] = $settings['sticky_social_media'];
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
        delete_transient(self::DIVI_LOGO_ATTACHMENT_TRANSIENT);

        if (function_exists('et_update_option')) {
            et_update_option('divi_logo', $logo_url);
            et_update_option('logo', $logo_url);
            return;
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
        $cached = get_transient(self::DIVI_LOGO_ATTACHMENT_TRANSIENT);
        if ($cached !== false) {
            return absint($cached);
        }

        $options = get_option('et_divi', []);
        if (!is_array($options)) {
            set_transient(self::DIVI_LOGO_ATTACHMENT_TRANSIENT, 0, HOUR_IN_SECONDS);
            return 0;
        }

        $logo_url = '';
        if (!empty($options['divi_logo'])) {
            $logo_url = $options['divi_logo'];
        } elseif (!empty($options['logo'])) {
            $logo_url = $options['logo'];
        }

        if (!$logo_url) {
            set_transient(self::DIVI_LOGO_ATTACHMENT_TRANSIENT, 0, HOUR_IN_SECONDS);
            return 0;
        }

        $attachment_id = attachment_url_to_postid($logo_url);
        $attachment_id = $attachment_id ? absint($attachment_id) : 0;
        set_transient(self::DIVI_LOGO_ATTACHMENT_TRANSIENT, $attachment_id, HOUR_IN_SECONDS);

        return $attachment_id;
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
        if (!array_key_exists('sticky_item_1', $settings) || empty($settings['sticky_item_1'])) {
            $settings['sticky_item_1'] = 'phone';
            $has_changes = true;
        }
        if (!array_key_exists('sticky_item_2', $settings) || empty($settings['sticky_item_2'])) {
            $settings['sticky_item_2'] = 'email';
            $has_changes = true;
        }
        if (!array_key_exists('sticky_item_3', $settings) || empty($settings['sticky_item_3'])) {
            $settings['sticky_item_3'] = $settings['sticky_social_media'];
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
            'args' => $this->get_rest_menu_preview_args(),
        ]);

        register_rest_route($namespace, '/faq-groups', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'handle_rest_get_faq_groups'],
            'permission_callback' => [$this, 'rest_builder_permission'],
        ]);

        register_rest_route($namespace, '/faq-preview', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'handle_rest_faq_preview'],
            'permission_callback' => [$this, 'rest_builder_permission'],
        ]);

        register_rest_route($namespace, '/logo-data', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'handle_rest_get_logo_data'],
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

    private function get_rest_menu_preview_args() {
        $text_args = [
            'menu_id',
            'source_type',
            'menu_location',
            'layout',
            'align',
            'use_native_layout',
            'mobile_style',
            'mobile_side',
            'mobile_breakpoint',
            'submenu_trigger',
            'hover_effect',
            'layout_class',
            'show_submenu_indicator',
            'close_on_outside_click',
            'close_on_link_click',
            'toggle_label',
            'menu_text_color',
            'menu_hover_text_color',
            'menu_hover_background_color',
            'item_active_color',
            'item_active_background_color',
            'submenu_background_color',
            'submenu_text_color',
            'submenu_hover_text_color',
            'submenu_hover_background_color',
            'toggle_color',
            'toggle_background_color',
            'border_color',
            'shadow_color',
            'submenu_width',
            'submenu_radius',
            'item_padding_y',
            'item_padding_x',
            'submenu_padding_y',
            'submenu_padding_x',
            'mobile_panel_width',
            'mobile_panel_offset',
            'submenu_indicator_icon',
        ];

        $args = [];
        foreach ($text_args as $arg_name) {
            $args[$arg_name] = [
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
            ];
        }

        return $args;
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

    public function handle_rest_get_logo_data(WP_REST_Request $request) {
        $logo_urls = $this->get_logo_urls();

        return rest_ensure_response([
            'success' => true,
            'data'    => [
                'mainLogo' => esc_url_raw($logo_urls['main']),
                'stickyLogo' => esc_url_raw($logo_urls['sticky']),
                'mobileLogo' => esc_url_raw($logo_urls['mobile']),
                'mainLogoWhite' => $this->get_setting('site_logo_white_filter_enabled', '0') === '1' || $this->get_setting('logo_white_filter_enabled', '0') === '1',
                'stickyLogoWhite' => $this->get_setting('sticky_header_logo_white_filter_enabled', '0') === '1',
                'mobileLogoWhite' => $this->get_setting('mobile_logo_white_filter_enabled', '0') === '1',
                'transparentHeader' => $this->should_apply_transparent_fixed_header(),
                'transparentHeaderMobile' => $this->get_setting('transparent_fixed_header_mobile', '0') === '1',
                'invertOnTransparentHeader' => $this->get_setting('transparent_header_invert_logo', '0') === '1',
                'siteName' => get_bloginfo('name'),
            ],
        ]);
    }

    /**
     * Returns resolved logo URLs (with all fallbacks) for main, sticky and
     * mobile variants.  Used by the Logo module server and REST endpoint.
     *
     * @return array{main: string, sticky: string, mobile: string}
     */
    public function get_logo_urls() {
        $settings   = $this->get_settings();
        $main_url   = $this->get_attachment_source_url(absint($settings['site_logo_id'] ?? 0));
        $sticky_url = $this->get_attachment_source_url(absint($settings['sticky_header_logo_id'] ?? 0));
        $mobile_url = $this->get_attachment_source_url(absint($settings['mobile_logo_id'] ?? 0));

        return [
            'main'   => $main_url ?: '',
            'sticky' => ($sticky_url ?: $main_url) ?: '',
            'mobile' => ($mobile_url ?: $main_url) ?: '',
        ];
    }

    public function handle_rest_get_menus(WP_REST_Request $request) {
        $menus = wp_get_nav_menus();
        $menu_data = [];
        $registered_locations = get_registered_nav_menus();
        $assigned_locations = get_nav_menu_locations();
        $location_data = [];

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

        foreach ($registered_locations as $slug => $label) {
            $assigned_menu_id = isset($assigned_locations[$slug]) ? absint($assigned_locations[$slug]) : 0;
            $assigned_menu = $assigned_menu_id ? wp_get_nav_menu_object($assigned_menu_id) : null;

            $location_data[] = [
                'slug' => (string) $slug,
                'label' => (string) $label,
                'menuId' => $assigned_menu_id,
                'menuName' => $assigned_menu ? (string) $assigned_menu->name : '',
            ];
        }

        return $this->rest_success_response([
            'menus' => $menu_data,
            'locations' => $location_data,
        ]);
    }

    public function handle_rest_menu_preview(WP_REST_Request $request) {
        $menu_id = $request->get_param('menu_id');
        $source_type = $request->get_param('source_type');
        $menu_location = $request->get_param('menu_location');
        $layout = $request->get_param('layout');
        $align = $request->get_param('align');
        $mobile_style = $request->get_param('mobile_style');
        $mobile_side = $request->get_param('mobile_side');
        $mobile_breakpoint = $request->get_param('mobile_breakpoint');
        $submenu_trigger = $request->get_param('submenu_trigger');
        $hover_effect = $request->get_param('hover_effect');
        $layout_class = $request->get_param('layout_class');
        $use_native_layout = $request->get_param('use_native_layout');
        $show_submenu_indicator = $request->get_param('show_submenu_indicator');
        $close_on_outside_click = $request->get_param('close_on_outside_click');
        $close_on_link_click = $request->get_param('close_on_link_click');
        $toggle_label = $request->get_param('toggle_label');
        $menu_text_color = $request->get_param('menu_text_color');
        $menu_hover_text_color = $request->get_param('menu_hover_text_color');
        $menu_hover_background_color = $request->get_param('menu_hover_background_color');
        $item_active_color = $request->get_param('item_active_color');
        $item_active_background_color = $request->get_param('item_active_background_color');
        $submenu_background_color = $request->get_param('submenu_background_color');
        $submenu_text_color = $request->get_param('submenu_text_color');
        $submenu_hover_text_color = $request->get_param('submenu_hover_text_color');
        $submenu_hover_background_color = $request->get_param('submenu_hover_background_color');
        $toggle_color = $request->get_param('toggle_color');
        $toggle_background_color = $request->get_param('toggle_background_color');
        $border_color = $request->get_param('border_color');
        $shadow_color = $request->get_param('shadow_color');
        $submenu_width = $request->get_param('submenu_width');
        $submenu_radius = $request->get_param('submenu_radius');
        $item_padding_y = $request->get_param('item_padding_y');
        $item_padding_x = $request->get_param('item_padding_x');
        $submenu_padding_y = $request->get_param('submenu_padding_y');
        $submenu_padding_x = $request->get_param('submenu_padding_x');
        $mobile_panel_width = $request->get_param('mobile_panel_width');
        $mobile_panel_offset = $request->get_param('mobile_panel_offset');
        $submenu_indicator_icon = $request->get_param('submenu_indicator_icon');

        $menu_id = is_scalar($menu_id) ? sanitize_text_field((string) $menu_id) : '';
        $source_type = is_scalar($source_type) ? sanitize_key((string) $source_type) : 'menu';
        $menu_location = is_scalar($menu_location) ? sanitize_key((string) $menu_location) : '';
        $layout = is_scalar($layout) ? sanitize_key((string) $layout) : 'horizontal';
        $align = is_scalar($align) ? sanitize_key((string) $align) : 'center';
        $mobile_style = is_scalar($mobile_style) ? sanitize_key((string) $mobile_style) : 'offcanvas';
        $mobile_side = is_scalar($mobile_side) ? sanitize_key((string) $mobile_side) : 'right';
        $mobile_breakpoint = is_scalar($mobile_breakpoint) ? sanitize_text_field((string) $mobile_breakpoint) : '980';
        $submenu_trigger = is_scalar($submenu_trigger) ? sanitize_key((string) $submenu_trigger) : 'hover';
        $hover_effect = is_scalar($hover_effect) ? sanitize_key((string) $hover_effect) : 'underline';
        $layout_class = is_scalar($layout_class) ? sanitize_html_class((string) $layout_class) : 'et_flex_module';
        $use_native_layout = is_scalar($use_native_layout) ? sanitize_key((string) $use_native_layout) : '0';
        $show_submenu_indicator = is_scalar($show_submenu_indicator) ? sanitize_key((string) $show_submenu_indicator) : 'on';
        $close_on_outside_click = is_scalar($close_on_outside_click) ? sanitize_key((string) $close_on_outside_click) : 'on';
        $close_on_link_click = is_scalar($close_on_link_click) ? sanitize_key((string) $close_on_link_click) : 'on';
        $toggle_label = is_scalar($toggle_label) ? sanitize_text_field((string) $toggle_label) : 'Menu';
        $menu_text_color = is_scalar($menu_text_color) ? sanitize_text_field((string) $menu_text_color) : '#111827';
        $menu_hover_text_color = is_scalar($menu_hover_text_color) ? sanitize_text_field((string) $menu_hover_text_color) : '#111827';
        $menu_hover_background_color = is_scalar($menu_hover_background_color) ? sanitize_text_field((string) $menu_hover_background_color) : 'rgba(17,24,39,.08)';
        $item_active_color = is_scalar($item_active_color) ? sanitize_text_field((string) $item_active_color) : '#111827';
        $item_active_background_color = is_scalar($item_active_background_color) ? sanitize_text_field((string) $item_active_background_color) : 'rgba(17,24,39,.12)';
        $submenu_background_color = is_scalar($submenu_background_color) ? sanitize_text_field((string) $submenu_background_color) : '#ffffff';
        $submenu_text_color = is_scalar($submenu_text_color) ? sanitize_text_field((string) $submenu_text_color) : '#111827';
        $submenu_hover_text_color = is_scalar($submenu_hover_text_color) ? sanitize_text_field((string) $submenu_hover_text_color) : '#111827';
        $submenu_hover_background_color = is_scalar($submenu_hover_background_color) ? sanitize_text_field((string) $submenu_hover_background_color) : 'rgba(17,24,39,.08)';
        $toggle_color = is_scalar($toggle_color) ? sanitize_text_field((string) $toggle_color) : '#111827';
        $toggle_background_color = is_scalar($toggle_background_color) ? sanitize_text_field((string) $toggle_background_color) : '#ffffff';
        $border_color = is_scalar($border_color) ? sanitize_text_field((string) $border_color) : 'rgba(17,24,39,.10)';
        $shadow_color = is_scalar($shadow_color) ? sanitize_text_field((string) $shadow_color) : 'rgba(17,24,39,.16)';
        $submenu_width = is_scalar($submenu_width) ? sanitize_text_field((string) $submenu_width) : '240';
        $submenu_radius = is_scalar($submenu_radius) ? sanitize_text_field((string) $submenu_radius) : '16';
        $item_padding_y = is_scalar($item_padding_y) ? sanitize_text_field((string) $item_padding_y) : '14';
        $item_padding_x = is_scalar($item_padding_x) ? sanitize_text_field((string) $item_padding_x) : '18';
        $submenu_padding_y = is_scalar($submenu_padding_y) ? sanitize_text_field((string) $submenu_padding_y) : '12';
        $submenu_padding_x = is_scalar($submenu_padding_x) ? sanitize_text_field((string) $submenu_padding_x) : '16';
        $mobile_panel_width = is_scalar($mobile_panel_width) ? sanitize_text_field((string) $mobile_panel_width) : '360';
        $mobile_panel_offset = is_scalar($mobile_panel_offset) ? sanitize_text_field((string) $mobile_panel_offset) : '16';
        $submenu_indicator_icon = is_scalar($submenu_indicator_icon) ? sanitize_text_field((string) $submenu_indicator_icon) : '▾';

        if (!in_array($hover_effect, ['none', 'underline', 'fill', 'lift'], true)) {
            $hover_effect = 'underline';
        }
        if (!in_array($source_type, ['menu', 'location'], true)) {
            $source_type = 'menu';
        }
        if (!in_array($layout_class, ['et_flex_module', 'et_grid_module', 'et_block_module'], true)) {
            $layout_class = 'et_flex_module';
        }

        $html = OnePlugin_Light_Menu_Module::instance()->render_shortcode([
            'source_type' => $source_type,
            'menu_slug' => $menu_id,
            'menu_location' => $menu_location,
            'layout' => $layout,
            'align' => $align,
            'use_native_layout' => $use_native_layout,
            'mobile_style' => $mobile_style,
            'mobile_side' => $mobile_side,
            'mobile_breakpoint' => $mobile_breakpoint,
            'submenu_trigger' => $submenu_trigger,
            'hover_effect' => $hover_effect,
            'show_submenu_indicator' => $show_submenu_indicator,
            'close_on_outside_click' => $close_on_outside_click,
            'close_on_link_click' => $close_on_link_click,
            'toggle_label' => $toggle_label,
            'menu_text_color' => $menu_text_color,
            'menu_hover_text_color' => $menu_hover_text_color,
            'menu_hover_bg_color' => $menu_hover_background_color,
            'menu_active_text_color' => $item_active_color,
            'menu_active_bg_color' => $item_active_background_color,
            'submenu_bg_color' => $submenu_background_color,
            'submenu_text_color' => $submenu_text_color,
            'submenu_hover_text_color' => $submenu_hover_text_color,
            'submenu_hover_bg_color' => $submenu_hover_background_color,
            'toggle_color' => $toggle_color,
            'toggle_bg_color' => $toggle_background_color,
            'border_color' => $border_color,
            'shadow_color' => $shadow_color,
            'submenu_width' => $submenu_width,
            'submenu_radius' => $submenu_radius,
            'item_padding_y' => $item_padding_y,
            'item_padding_x' => $item_padding_x,
            'submenu_padding_y' => $submenu_padding_y,
            'submenu_padding_x' => $submenu_padding_x,
            'mobile_panel_width' => $mobile_panel_width,
            'mobile_panel_offset' => $mobile_panel_offset,
            'submenu_indicator_icon' => $submenu_indicator_icon,
            'list_class' => $layout_class,
            'class' => $layout_class,
        ]);

        $styles = '';
        if (wp_style_is('oneplugin2-menu-inline', 'enqueued') || wp_style_is('oneplugin2-menu-inline', 'registered')) {
            ob_start();
            wp_print_styles('oneplugin2-menu-inline');
            $styles = (string) ob_get_clean();
        }

        return $this->rest_success_response([
            'html' => $this->sanitize_builder_preview_html($styles . (is_string($html) ? $html : '')),
        ]);
    }

    public function handle_rest_get_faq_groups(WP_REST_Request $request) {
        $groups = [];

        if ($this->is_faq_extension_enabled() && class_exists('OnePlugin_Light_FAQ')) {
            $terms = get_terms([
                'taxonomy' => OnePlugin_Light_FAQ::TAXONOMY,
                'hide_empty' => false,
            ]);

            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $groups[] = [
                        'id' => (int) $term->term_id,
                        'slug' => (string) $term->slug,
                        'name' => (string) $term->name,
                        'count' => (int) $term->count,
                    ];
                }
            }
        }

        return $this->rest_success_response([
            'groups' => $groups,
        ]);
    }

    public function handle_rest_faq_preview(WP_REST_Request $request) {
        if (!$this->is_faq_extension_enabled() || !class_exists('OnePlugin_Light_FAQ')) {
            return $this->rest_success_response([
                'html' => '',
            ]);
        }

        $params = [];
        foreach ([
            'group',
            'limit',
            'columns',
            'rows',
            'orderby',
            'order',
            'schema',
            'use_same_icon',
            'animation',
            'animation_duration',
            'accordion_mode',
            'open_first',
        ] as $key) {
            $value = $request->get_param($key);
            if (is_scalar($value)) {
                $params[$key] = sanitize_text_field((string) $value);
            }
        }

        foreach (['open_icon', 'close_icon'] as $key) {
            $value = $request->get_param($key);
            if (is_scalar($value)) {
                $params[$key] = (string) $value;
            }
        }

        $params['schema'] = 'false';

        $html = OnePlugin_Light_FAQ::instance()->render($params);
        $styles = '';

        foreach (['oneplugin2-fontawesome', 'oneplugin-light-faq'] as $handle) {
            if (wp_style_is($handle, 'enqueued') || wp_style_is($handle, 'registered')) {
                ob_start();
                wp_print_styles($handle);
                $styles .= (string) ob_get_clean();
            }
        }

        return $this->rest_success_response([
            'html' => $this->sanitize_builder_preview_html($styles . (is_string($html) ? $html : '')),
        ]);
    }

    private function sanitize_builder_preview_html($html) {
        $html = is_string($html) ? $html : '';
        if ($html === '') {
            return '';
        }

        $global_attrs = [
            'id' => true,
            'class' => true,
            'style' => true,
            'role' => true,
            'title' => true,
            'hidden' => true,
            'tabindex' => true,
            'aria-label' => true,
            'aria-labelledby' => true,
            'aria-controls' => true,
            'aria-expanded' => true,
            'aria-haspopup' => true,
            'aria-hidden' => true,
            'data-oneplugin-menu' => true,
            'data-oneplugin-submenu-link' => true,
            'data-submenu-trigger' => true,
            'data-mobile-style' => true,
            'data-mobile-side' => true,
            'data-mobile-breakpoint' => true,
            'data-close-outside' => true,
            'data-close-on-link-click' => true,
            'data-show-indicator' => true,
            'data-oneplugin2-faq' => true,
            'data-columns' => true,
            'data-rows' => true,
            'data-oneplugin2-faq-column' => true,
            'data-accordion-mode' => true,
            'data-animation' => true,
            'data-duration' => true,
            'data-oneplugin2-faq-icon-state' => true,
        ];

        $allowed = [
            'a' => array_merge($global_attrs, [
                'href' => true,
                'target' => true,
                'rel' => true,
            ]),
            'article' => $global_attrs,
            'b' => $global_attrs,
            'br' => [],
            'button' => array_merge($global_attrs, [
                'type' => true,
                'disabled' => true,
            ]),
            'div' => $global_attrs,
            'em' => $global_attrs,
            'h1' => $global_attrs,
            'h2' => $global_attrs,
            'h3' => $global_attrs,
            'h4' => $global_attrs,
            'h5' => $global_attrs,
            'h6' => $global_attrs,
            'i' => $global_attrs,
            'img' => array_merge($global_attrs, [
                'src' => true,
                'alt' => true,
                'width' => true,
                'height' => true,
                'loading' => true,
                'srcset' => true,
                'sizes' => true,
            ]),
            'li' => $global_attrs,
            'link' => [
                'id' => true,
                'rel' => true,
                'href' => true,
                'media' => true,
                'type' => true,
            ],
            'nav' => $global_attrs,
            'ol' => $global_attrs,
            'p' => $global_attrs,
            'span' => $global_attrs,
            'strong' => $global_attrs,
            'style' => [
                'id' => true,
                'type' => true,
            ],
            'ul' => $global_attrs,
        ];

        add_filter('safe_style_css', [$this, 'filter_builder_preview_safe_css']);
        $sanitized = wp_kses($html, $allowed);
        remove_filter('safe_style_css', [$this, 'filter_builder_preview_safe_css']);

        return $sanitized;
    }

    public function filter_builder_preview_safe_css($properties) {
        $properties = is_array($properties) ? $properties : [];

        return array_values(array_unique(array_merge($properties, [
            'align-items',
            'color',
            'display',
            'flex-direction',
            'font-family',
            'font-size',
            'gap',
            'height',
            'justify-content',
            'line-height',
            'min-width',
            'overflow',
            'text-decoration',
            'transition',
            '--oneplugin-border-color',
            '--oneplugin-faq-index',
            '--oneplugin-item-padding-x',
            '--oneplugin-item-padding-y',
            '--oneplugin-menu-active-bg',
            '--oneplugin-menu-active-text',
            '--oneplugin-menu-bg',
            '--oneplugin-menu-gap',
            '--oneplugin-menu-hover-bg',
            '--oneplugin-menu-hover-text',
            '--oneplugin-menu-text',
            '--oneplugin-mobile-panel-offset',
            '--oneplugin-mobile-panel-width',
            '--oneplugin-shadow-color',
            '--oneplugin-submenu-bg',
            '--oneplugin-submenu-hover-bg',
            '--oneplugin-submenu-hover-text',
            '--oneplugin-submenu-padding-x',
            '--oneplugin-submenu-padding-y',
            '--oneplugin-submenu-radius',
            '--oneplugin-submenu-text',
            '--oneplugin-submenu-width',
            '--oneplugin-toggle-bg',
            '--oneplugin-toggle-color',
        ])));
    }

    private function is_faq_extension_enabled() {
        return $this->get_setting('extension_faq_enabled', '1') === '1';
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
        $settings = array_intersect_key(wp_parse_args(is_array($value) ? $value : [], $this->defaults), $this->defaults);
        update_option(self::LEGACY_OPTION_KEY, $settings);
        $this->sync_github_auto_updates_setting($settings);
    }

    public function mirror_legacy_option_on_add($option, $value) {
        $this->mirror_legacy_option([], $value);
    }

    private function persist_settings($settings) {
        $settings = array_intersect_key(wp_parse_args(is_array($settings) ? $settings : [], $this->defaults), $this->defaults);
        update_option(self::OPTION_KEY, $settings);
        update_option(self::LEGACY_OPTION_KEY, $settings);
        $this->sync_github_auto_updates_setting($settings);
    }

    private function sync_github_auto_updates_setting($settings) {
        if (!class_exists('OnePlugin_Light_GitHub_Updater')) {
            return;
        }

        $updater = OnePlugin_Light_GitHub_Updater::instance();
        if (!$updater || !method_exists($updater, 'set_native_auto_updates_enabled')) {
            return;
        }

        $updater->set_native_auto_updates_enabled(is_array($settings) && !empty($settings['github_auto_updates_enabled']));
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

        $raw = $this->read_uploaded_json_file('oneplugin_light_import_file', 2 * MB_IN_BYTES);
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
            $sticky_items = $this->get_sticky_footer_items();
            if ($this->sticky_footer_items_need_fontawesome($sticky_items)) {
                wp_enqueue_style(
                    'oneplugin2-fontawesome',
                    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
                    [],
                    '6.5.1'
                );
            }

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

        if ($this->is_header_features_enabled() && $this->get_setting('active_menu_item_by_section', '0') === '1') {
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
        $palette_css = $this->build_project_palette_css();
        if ($palette_css !== '') {
            $generated_css[] = $palette_css;
        }

        if ($this->get_setting('hide_image_alt_text', '0') === '1') {
            $generated_css[] = 'img {pointer-events: none!important;}';
        }
        $custom_header_selector = $this->get_custom_header_selector();
        $custom_logo_selector = $this->get_custom_logo_selector();
        if ($this->is_header_features_enabled()) {
            $generated_css[] = $custom_logo_selector . ',
' . $custom_logo_selector . ' img {
    will-change: filter;
}';
        }
        if ($this->is_header_features_enabled() && $this->should_apply_transparent_fixed_header()) {
            $header_transition_duration = '300ms';
            $header_transition_easing   = 'ease';
            if ($this->get_setting('header_animation_enabled', '0') === '1') {
                $header_transition_duration = $this->get_setting('header_transition_duration', $this->defaults['header_transition_duration']);
                $header_transition_easing   = $this->get_setting('header_transition_easing', $this->defaults['header_transition_easing']);
                if (!array_key_exists($header_transition_easing, $this->get_header_transition_easing_options())) {
                    $header_transition_easing = $this->defaults['header_transition_easing'];
                }
            }
            $generated_css[] = '#main-header.oneplugin-header-managed:not(.et_pb_sticky_placeholder),
.et-l--header.oneplugin-header-managed:not(.et_pb_sticky_placeholder),
' . $custom_header_selector . '.oneplugin-header-managed:not(.et_pb_sticky_placeholder) {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    z-index: 99999 !important;
    transition: background-color ' . $header_transition_duration . ' ' . $header_transition_easing . ', background ' . $header_transition_duration . ' ' . $header_transition_easing . ', box-shadow ' . $header_transition_duration . ' ' . $header_transition_easing . ';
}';
        } elseif ($this->is_header_features_enabled() && $this->get_setting('header_animation_enabled', '0') === '1') {
            $header_transition_duration = $this->get_setting('header_transition_duration', $this->defaults['header_transition_duration']);
            $header_transition_easing   = $this->get_setting('header_transition_easing', $this->defaults['header_transition_easing']);
            if (!array_key_exists($header_transition_easing, $this->get_header_transition_easing_options())) {
                $header_transition_easing = $this->defaults['header_transition_easing'];
            }
            $generated_css[] = '#main-header.oneplugin-header-managed:not(.et_pb_sticky_placeholder),
.et-l--header.oneplugin-header-managed:not(.et_pb_sticky_placeholder),
' . $custom_header_selector . '.oneplugin-header-managed:not(.et_pb_sticky_placeholder) {
    transition: background-color ' . $header_transition_duration . ' ' . $header_transition_easing . ', background ' . $header_transition_duration . ' ' . $header_transition_easing . ', box-shadow ' . $header_transition_duration . ' ' . $header_transition_easing . ';
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
        $generated_css[] = '.oneplugin-logo-filter-white {
    filter: brightness(0) invert(1);
}';
        if ($this->is_header_features_enabled()) {
            $generated_css[] = $custom_logo_selector . '.et_pb_image .et_pb_image_wrap img[src*=".svg"],
' . $custom_logo_selector . ' .et_pb_image_wrap img[src*=".svg"],
' . $custom_logo_selector . ' img[src*=".svg"],
img' . $custom_logo_selector . '[src*=".svg"] {
    width: 100% !important;
    max-width: 100%;
    height: auto;
}';
        }
        if ($this->is_header_features_enabled() && ($this->get_setting('site_logo_white_filter_enabled', '0') === '1' || $this->get_setting('logo_white_filter_enabled', '0') === '1')) {
            $generated_css[] = '#main-header.oneplugin-header-managed:not(.et-fixed-header) #logo,
#main-header.oneplugin-header-managed:not(.et-fixed-header) .logo_container img,
' . $custom_header_selector . '.oneplugin-header-managed:not(.oneplugin-header-is-sticky) ' . $custom_logo_selector . ',
' . $custom_header_selector . '.oneplugin-header-managed:not(.oneplugin-header-is-sticky) ' . $custom_logo_selector . ' img {
    filter: brightness(0) invert(1);
}';
        }
        if ($this->is_header_features_enabled() && $this->should_apply_transparent_fixed_header()) {
            $transparent_css = '#main-header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder),
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder),
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_builder_inner_content,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_section,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_row,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_column,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_module,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_menu,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_menu_inner_container,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder),
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_builder_inner_content,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_section,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_row,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_column,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_module,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_menu,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_menu_inner_container {
    background: transparent !important;
    background-color: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
}
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_section::before,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_section::after,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_row::before,
.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_row::after,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_section::before,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_section::after,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_row::before,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .et_pb_row::after {
    background: transparent !important;
    background-color: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
}';
            if ($this->get_setting('transparent_fixed_header_mobile', '0') !== '1') {
                $transparent_css = '@media (min-width: 981px) {' . "\n" . $transparent_css . "\n" . '}';
            }
            $generated_css[] = $transparent_css;

            $menu_text_color = $this->get_setting('transparent_header_menu_text_color', '');
            if ($menu_text_color !== '') {
                $menu_color_css = '.et-l--header.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .oneplugin-menu,
' . $custom_header_selector . '.oneplugin-header-is-transparent:not(.et_pb_sticky_placeholder) .oneplugin-menu {
    --oneplugin-menu-text: ' . esc_attr($menu_text_color) . ' !important;
    --oneplugin-menu-hover-text: ' . esc_attr($menu_text_color) . ' !important;
    --oneplugin-menu-active-text: ' . esc_attr($menu_text_color) . ' !important;
    --oneplugin-menu-hover-bg: rgba(255,255,255,0.12) !important;
    --oneplugin-menu-active-bg: rgba(255,255,255,0.18) !important;
    --oneplugin-toggle-color: ' . esc_attr($menu_text_color) . ' !important;
    --oneplugin-toggle-bg: transparent !important;
    --oneplugin-border-color: rgba(255,255,255,0.25) !important;
    --oneplugin-shadow-color: rgba(0,0,0,0.08) !important;
}';
                if ($this->get_setting('transparent_fixed_header_mobile', '0') !== '1') {
                    $menu_color_css = '@media (min-width: 981px) {' . "\n" . $menu_color_css . "\n" . '}';
                }
                $generated_css[] = $menu_color_css;
            }
        }
        if ($this->get_setting('scrollbar_enabled', '0') === '1') {
            $scrollbar_bg = $this->get_setting('scrollbar_bg_color', $this->defaults['scrollbar_bg_color']);
            $scrollbar_handle = $this->get_setting('scrollbar_handle_color', $this->defaults['scrollbar_handle_color']);
            $scrollbar_radius = $this->get_setting('scrollbar_radius', $this->defaults['scrollbar_radius']);
            $scrollbar_width = $this->get_setting('scrollbar_width', $this->defaults['scrollbar_width']);
            $generated_css[] = 'html {
    scrollbar-color: ' . $scrollbar_handle . ' ' . $scrollbar_bg . ';
    scrollbar-width: thin;
}
::-webkit-scrollbar {
    width: ' . $scrollbar_width . ';
    height: ' . $scrollbar_width . ';
}
::-webkit-scrollbar-track {
    background: ' . $scrollbar_bg . ';
}
::-webkit-scrollbar-thumb {
    background: ' . $scrollbar_handle . ';
    border-radius: ' . $scrollbar_radius . ';
}';
        }
        if ($this->get_setting('style_formidable', '0') === '1') {
            $formidable_accent = $this->get_setting('formidable_accent_color', '#fa1e9a');
            $formidable_background = $this->get_setting('formidable_background_color', '#ffffff');
            $formidable_text = $this->get_setting('formidable_text_color', '#000000');
            $formidable_checked_text = $this->get_setting('formidable_checked_text_color', '#ffffff');
            $formidable_checked_background = $this->get_setting('formidable_checked_background_color', '#ffffff');
            $formidable_border_radius = $this->get_setting('formidable_border_radius', '8px');
            $formidable_border_width = $this->get_setting('formidable_border_width', '1px');
            $formidable_padding = $this->get_setting('formidable_padding', '10px 5px 10px 10px');
            $generated_css[] = '.frm_form_field .frm_checkbox {
    margin-top: 0;
    margin-bottom: 10px;
    background-color: ' . $formidable_background . ';
    padding: ' . $formidable_padding . ';
    border-radius: ' . $formidable_border_radius . ';
    border: ' . $formidable_border_width . ' solid ' . $formidable_accent . ';
    transition: background-color 0.3s ease;
}

.with_frm_style .frm_checkbox label {
    font-size: var(--check-font-size);
    font-weight: var(--check-weight);
    line-height: 1.3;
}

.with_frm_style .frm_checkbox:not(:has(input:checked)) label {
    color: ' . $formidable_text . ';
}

.with_frm_style .frm_checkbox input[type=checkbox] {
    appearance: none;
    -webkit-appearance: none;
    background-color: ' . $formidable_text . ';
    flex: none;
    display: inline-block !important;
    width: 16px !important;
    min-width: 16px !important;
    height: 16px !important;
    color: ' . $formidable_text . ';
    border: 1px solid ' . $formidable_text . ';
    vertical-align: middle;
    position: initial;
    padding: 0;
    margin: 0;
}

.with_frm_style .frm_checkbox input[type=checkbox]:checked {
    background-color: ' . $formidable_checked_text . ';
    border-color: ' . $formidable_checked_text . ';
    color: ' . $formidable_checked_text . ';
}

.with_frm_style .frm_checkbox:has(input:checked) {
    background-color: ' . $formidable_checked_background . ';
}

.with_frm_style .frm_checkbox:has(input:checked) label {
    color: ' . $formidable_checked_text . ';
}

.frm_style_formidables-stilmall.with_frm_style .frm_error,
.frm_style_formidables-stilmall.with_frm_style .frm_limit_error {
    font-weight: bold;
    color: ' . $formidable_checked_text . ';
}

@media only screen and (max-width: 980px) {
    .frm_form_field.frm_two_col .frm_opt_container {
        grid-template-columns: repeat(1, 1fr);
    }
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

    private function build_project_palette_css() {
        $palette = $this->get_project_color_palette();
        if (empty($palette)) {
            return '';
        }

        $variables = [];
        foreach ($palette as $swatch) {
            if (empty($swatch['variable']) || empty($swatch['value'])) {
                continue;
            }

            $variables[] = '    ' . $swatch['variable'] . ': ' . $swatch['value'] . ';';
        }

        if (empty($variables)) {
            return '';
        }

        return ":root {\n" . implode("\n", $variables) . "\n}";
    }

    private function get_custom_header_selector() {
        $class = $this->sanitize_css_class_setting($this->get_setting('custom_header_class', $this->defaults['custom_header_class']), $this->defaults['custom_header_class']);
        return '.' . $class;
    }

    private function get_custom_logo_selector() {
        $class = $this->sanitize_css_class_setting($this->get_setting('custom_logo_class', $this->defaults['custom_logo_class']), $this->defaults['custom_logo_class']);
        return '.' . $class;
    }

    private function should_apply_transparent_fixed_header() {
        if (!$this->is_header_features_enabled()) {
            return false;
        }

        if ($this->get_setting('transparent_fixed_header', '0') !== '1') {
            return false;
        }

        if ($this->get_setting('transparent_fixed_header_home_only', '0') === '1' && !is_front_page()) {
            return false;
        }

        return true;
    }

    private function is_header_features_enabled() {
        return $this->get_setting('header_enabled', '1') === '1';
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

    public function render_header_controller_js() {
        if (is_admin() || !$this->is_header_features_enabled()) {
            return;
        }

        $config = [
            'headerSelector' => '#main-header, .et-l--header, ' . $this->get_custom_header_selector(),
            'stickyClass' => 'oneplugin-header-is-sticky',
            'transparentHeader' => $this->should_apply_transparent_fixed_header(),
            'transparentHeaderMobile' => $this->get_setting('transparent_fixed_header_mobile', '0') === '1',
        ];
        ?>
        <script id="oneplugin-header-controller">
        (function(config) {
            var state = {};
            var scheduled = false;
            var observers = [];
            var uniqueNodes = function(nodes) {
                var seen = [];
                return nodes.filter(function(node) {
                    if (!node || seen.indexOf(node) !== -1) { return false; }
                    if (node.classList && node.classList.contains('et_pb_sticky_placeholder')) { return false; }
                    if (node.hasAttribute && node.hasAttribute('data-sticky-placeholder-id')) { return false; }
                    seen.push(node);
                    return true;
                });
            };
            var getHeaders = function() {
                return uniqueNodes(Array.prototype.slice.call(document.querySelectorAll(config.headerSelector)));
            };
            var hasFixedHeader = function(headers) {
                return headers.some(function(header) {
                    return header && header.classList && (
                        header.classList.contains('et-fixed-header') ||
                        header.classList.contains('et_pb_sticky') ||
                        header.classList.contains('et_pb_sticky--top')
                    );
                });
            };
            var updateHeaderHeight = function(headers) {
                if (!headers.length) {
                    document.documentElement.style.removeProperty('--oneplugin-header-height');
                    return;
                }

                var height = 0;
                headers.forEach(function(header) {
                    if (!header || !header.getBoundingClientRect) { return; }
                    height = Math.max(height, Math.round(header.getBoundingClientRect().height || 0));
                });
                document.documentElement.style.setProperty('--oneplugin-header-height', height + 'px');
            };
            var applyState = function() {
                scheduled = false;
                var headers = getHeaders();
                var isMobile = window.matchMedia && window.matchMedia('(max-width: 980px)').matches;
                var transparentActive = !!config.transparentHeader && (!isMobile || !!config.transparentHeaderMobile);
                var isSticky = hasFixedHeader(headers) || window.scrollY > 2;
                var transparentState = transparentActive && !isSticky;

                headers.forEach(function(header) {
                    header.classList.add('oneplugin-header-managed');
                    header.classList.toggle(config.stickyClass, isSticky);
                    header.classList.toggle('oneplugin-header-is-transparent', transparentState);
                    header.setAttribute('data-oneplugin-header-sticky', isSticky ? '1' : '0');
                    header.setAttribute('data-oneplugin-header-transparent', transparentState ? '1' : '0');
                    header.setAttribute('data-oneplugin-header-mobile', isMobile ? '1' : '0');
                });

                document.documentElement.classList.add('oneplugin-header-controller-ready');
                document.documentElement.classList.toggle('oneplugin-has-transparent-header', transparentState && headers.length > 0);
                updateHeaderHeight(headers);

                state = {
                    isSticky: isSticky,
                    isMobile: !!isMobile,
                    transparentActive: transparentActive,
                    transparentState: transparentState,
                    headerCount: headers.length
                };

                if (typeof window.CustomEvent === 'function') {
                    window.dispatchEvent(new CustomEvent('oneplugin:header-state', { detail: state }));
                }
            };
            var schedule = function() {
                if (scheduled) { return; }
                scheduled = true;
                window.requestAnimationFrame ? window.requestAnimationFrame(applyState) : window.setTimeout(applyState, 16);
            };
            var refreshObservers = function() {
                if (!window.MutationObserver) { return; }
                observers.forEach(function(observer) { observer.disconnect(); });
                observers = getHeaders().map(function(header) {
                    var observer = new MutationObserver(schedule);
                    observer.observe(header, { attributes: true, attributeFilter: ['class', 'style'] });
                    return observer;
                });
            };

            applyState();
            refreshObservers();
            window.addEventListener('scroll', schedule, { passive: true });
            window.addEventListener('resize', schedule);
            window.addEventListener('load', function() {
                refreshObservers();
                schedule();
            });
            window.setTimeout(function() {
                refreshObservers();
                schedule();
            }, 300);
            window.setTimeout(schedule, 800);
        })(<?php echo wp_json_encode($config, JSON_UNESCAPED_SLASHES); ?>);
        </script>
        <?php
    }

    public function render_logo_behavior_js() {
        if (is_admin() || !$this->is_header_features_enabled()) {
            return;
        }

        $main_logo_url = $this->get_attachment_source_url(absint($this->get_setting('site_logo_id', 0)));
        $sticky_logo_url = $this->get_attachment_source_url(absint($this->get_setting('sticky_header_logo_id', 0)));
        $mobile_logo_url = $this->get_attachment_source_url(absint($this->get_setting('mobile_logo_id', 0)));

        $config = [
            'mainLogo' => esc_url_raw($main_logo_url ?: ''),
            'stickyLogo' => esc_url_raw($sticky_logo_url ?: $main_logo_url ?: ''),
            'mobileLogo' => esc_url_raw($mobile_logo_url ?: $main_logo_url ?: ''),
            'mainLogoWhite' => $this->get_setting('site_logo_white_filter_enabled', '0') === '1' || $this->get_setting('logo_white_filter_enabled', '0') === '1',
            'stickyLogoWhite' => $this->get_setting('sticky_header_logo_white_filter_enabled', '0') === '1',
            'mobileLogoWhite' => $this->get_setting('mobile_logo_white_filter_enabled', '0') === '1',
            'headerSelector' => '#main-header, .et-l--header, ' . $this->get_custom_header_selector(),
            'logoSelector' => '#logo, .logo_container img, .custom-logo, ' . $this->get_custom_logo_selector(),
            'stickyClass' => 'oneplugin-header-is-sticky',
            'invertOnTransparentHeader' => $this->get_setting('transparent_header_invert_logo', '0') === '1',
        ];
        ?>
        <script id="oneplugin-logo-behavior">
        (function(config) {
            var imageCache = {};
            var preload = function(url, callback) {
                if (!url) { return null; }
                if (imageCache[url]) {
                    if (callback) {
                        if (imageCache[url].ready) {
                            callback();
                        } else {
                            imageCache[url].callbacks.push(callback);
                        }
                    }
                    return imageCache[url];
                }
                var img = new Image();
                imageCache[url] = { ready: false, loading: true, callbacks: callback ? [callback] : [] };
                var done = function() {
                    imageCache[url].ready = true;
                    imageCache[url].loading = false;
                    imageCache[url].callbacks.splice(0).forEach(function(fn) { fn(); });
                };
                img.onload = done;
                img.onerror = done;
                img.src = url;
                return imageCache[url];
            };
            [config.mainLogo, config.stickyLogo, config.mobileLogo].forEach(preload);
            var applyLogoFilter = function(logo, useFilter) {
                var has = logo.classList.contains('oneplugin-logo-filter-white');
                if (!!has === !!useFilter) { return; }
                logo.style.transition = logo.style.transition || 'filter 160ms ease, opacity 160ms ease';
                logo.classList.toggle('oneplugin-logo-filter-white', !!useFilter);
            };
            var cleanupLogoClones = function(logo) {
                var parent = logo.parentNode;
                if (!parent) { return; }
                Array.prototype.slice.call(
                    parent.querySelectorAll('img[aria-hidden="true"]')
                ).forEach(function(clone) {
                    if (clone.parentNode) { clone.parentNode.removeChild(clone); }
                });
                if (logo.style.zIndex) { logo.style.zIndex = ''; }
                if (logo.style.position === 'relative') { logo.style.position = ''; }
            };
            var resolveLogo = function(node) {
                if (!node) { return null; }
                if (node.matches && node.matches('img')) { return node; }
                return node.querySelector ? node.querySelector('img') : null;
            };
            var uniqueNodes = function(nodes) {
                var seen = [];
                return nodes.filter(function(node) {
                    if (!node || seen.indexOf(node) !== -1) { return false; }
                    if (node.classList && node.classList.contains('et_pb_sticky_placeholder')) { return false; }
                    if (node.hasAttribute && node.hasAttribute('data-sticky-placeholder-id')) { return false; }
                    seen.push(node);
                    return true;
                });
            };
            var getHeaders = function() {
                return uniqueNodes(Array.prototype.slice.call(document.querySelectorAll(config.headerSelector)));
            };
            var getLogos = function(headers) {
                var logos = [];
                headers.forEach(function(header) {
                    if (!header || !header.querySelectorAll) { return; }
                    if (header.matches && header.matches(config.logoSelector)) {
                        logos.push(resolveLogo(header));
                    }
                    Array.prototype.slice.call(header.querySelectorAll(config.logoSelector)).forEach(function(node) {
                        logos.push(resolveLogo(node));
                    });
                });
                return uniqueNodes(logos.filter(Boolean));
            };
            var hasStickyHeader = function(headers) {
                return headers.some(function(header) {
                    return header && header.classList && (
                        header.classList.contains(config.stickyClass) ||
                        header.classList.contains('et-fixed-header') ||
                        header.classList.contains('et_pb_sticky') ||
                        header.classList.contains('et_pb_sticky--top') ||
                        header.getAttribute('data-oneplugin-header-sticky') === '1'
                    );
                });
            };
            var hasTransparentHeader = function(headers) {
                return headers.some(function(header) {
                    return header && header.classList && header.classList.contains('oneplugin-header-is-transparent');
                });
            };
            var setLogo = function() {
                var headers = getHeaders();
                var isMobile = window.matchMedia && window.matchMedia('(max-width: 980px)').matches;
                var isSticky = hasStickyHeader(headers) || (!headers.length && window.scrollY > 0);
                var transparentState = hasTransparentHeader(headers);
                if (!config.mainLogo) { return; }
                var next = isMobile ? config.mobileLogo : (isSticky ? config.stickyLogo : config.mainLogo);
                var useWhiteFilter = isMobile ? config.mobileLogoWhite : (isSticky ? config.stickyLogoWhite : config.mainLogoWhite);
                if (transparentState && config.invertOnTransparentHeader) {
                    useWhiteFilter = true;
                }
                if (!next) { return; }
                getLogos(headers).forEach(function(logo) {
                    cleanupLogoClones(logo);
                    if (logo.getAttribute('src') !== next) {
                        logo.setAttribute('src', next);
                        logo.setAttribute('srcset', '');
                    }
                    applyLogoFilter(logo, !!useWhiteFilter);
                });
            };
            setLogo();
            window.addEventListener('scroll', setLogo, { passive: true });
            window.addEventListener('resize', setLogo);
            window.addEventListener('oneplugin:header-state', setLogo);
            window.setTimeout(setLogo, 300);
            window.setTimeout(setLogo, 800);
            if (window.MutationObserver) {
                getHeaders().forEach(function(header) {
                    new MutationObserver(function() { setLogo(); })
                        .observe(header, { attributes: true, attributeFilter: ['class', 'data-oneplugin-header-sticky', 'data-oneplugin-header-transparent'] });
                });
            }
        })(<?php echo wp_json_encode($config, JSON_UNESCAPED_SLASHES); ?>);
        </script>
        <?php
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
        if ($this->get_setting('custom_php_enabled', '0') !== '1') {
            return;
        }

        $code = trim((string) $this->get_setting($setting_key, ''));
        if ($code === '') {
            return;
        }

        try {
            eval("?>$code");
        } catch (Throwable $error) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('1Plugin custom PHP error in %s: %s', $setting_key, $error->getMessage()));
            }
        }
    }

    public function render_mobile_footer() {
        if (is_admin() || $this->get_setting('sticky_enabled', '1') !== '1') {
            return;
        }

        $items = $this->get_sticky_footer_items();

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
            '--oneplugin-mobile-footer-bg:%s;--oneplugin-mobile-footer-icon:%s;--oneplugin-mobile-footer-text:%s;--oneplugin-mobile-footer-width:%s;--oneplugin-mobile-footer-radius-top:%s;--oneplugin-mobile-footer-font-size:%s;--oneplugin-mobile-footer-icon-size:%s;',
            esc_attr($this->get_setting('sticky_bg_color', '#0f0f0f')),
            esc_attr($this->get_setting('sticky_icon_color', '#ffffff')),
            esc_attr($this->get_setting('sticky_text_color', '#ffffff')),
            esc_attr($this->get_setting('sticky_width', $this->defaults['sticky_width'])),
            esc_attr($this->get_setting('sticky_radius_top', $this->defaults['sticky_radius_top'])),
            esc_attr($this->get_setting('sticky_font_size', $this->defaults['sticky_font_size'])),
            esc_attr($this->get_setting('sticky_icon_size', $this->defaults['sticky_icon_size']))
        );

        echo '<div class="oneplugin-mobile-footer" role="navigation" aria-label="' . esc_attr__('Mobile footer', 'oneplugin-light-site-tools') . '" style="' . $footer_style . '">';
        echo '<div class="oneplugin-mobile-footer__inner">';

        foreach ($items as $item) {
            if (empty($item['link'])) {
                continue;
            }

            echo '<a class="footer-item" href="' . esc_attr($this->escape_sticky_footer_href($item['link'])) . '"' . $item['target'] . '>';
            echo $this->render_sticky_footer_icon($item);
            echo '<span>' . esc_html($item['text']) . '</span>';
            echo '</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    private function render_sticky_footer_icon($item) {
        $icon_key = isset($item['icon_key']) ? sanitize_key($item['icon_key']) : '';
        $svg = $this->get_sticky_footer_svg_icon($icon_key);
        if ($svg !== '') {
            return '<span class="oneplugin-mobile-footer__icon" aria-hidden="true">' . $svg . '</span>';
        }

        $icon_class = isset($item['icon']) ? $this->sanitize_icon_class((string) $item['icon']) : '';
        if ($icon_class === '') {
            return '';
        }

        return '<i class="' . esc_attr($icon_class) . '" aria-hidden="true"></i>';
    }

    private function sticky_footer_items_need_fontawesome($items) {
        foreach ($items as $item) {
            $icon_key = isset($item['icon_key']) ? sanitize_key($item['icon_key']) : '';
            if ($this->get_sticky_footer_svg_icon($icon_key) !== '') {
                continue;
            }

            $icon = isset($item['icon']) ? (string) $item['icon'] : '';
            if ($icon !== '' && preg_match('/(^|\s)(fa-|fa[srldb]?|fa-solid|fa-regular|fa-brands)(\s|$)/', $icon) === 1) {
                return true;
            }
        }

        return false;
    }

    private function get_sticky_footer_svg_icon($icon_key) {
        $icons = [
            'phone' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8c1.5 3 3.7 5.2 6.6 6.6l2.2-2.2c.3-.3.8-.4 1.2-.3 1.3.4 2.6.6 4 .6.7 0 1.2.5 1.2 1.2v3.5c0 .7-.5 1.2-1.2 1.2C10.3 22 2 13.7 2 3.4 2 2.7 2.5 2.2 3.2 2.2h3.6c.7 0 1.2.5 1.2 1.2 0 1.4.2 2.8.6 4 .1.4 0 .9-.3 1.2l-1.7 2.2z"/></svg>',
            'email' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M3.8 5h16.4c1 0 1.8.8 1.8 1.8v10.4c0 1-.8 1.8-1.8 1.8H3.8c-1 0-1.8-.8-1.8-1.8V6.8C2 5.8 2.8 5 3.8 5zm8.2 7.6 7.1-5.5H4.9l7.1 5.5zm0 2.3L4 8.7v8.1h16V8.7l-8 6.2z"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M7 2h2v3h6V2h2v3h2.5c.8 0 1.5.7 1.5 1.5v13c0 .8-.7 1.5-1.5 1.5h-15C4.7 21 4 20.3 4 19.5v-13C4 5.7 4.7 5 5.5 5H7V2zm12 8H6v9h13v-9zM6 8h13V7H6v1zm3.2 5.7 1.5-1.5 1.7 1.7 3.9-3.9 1.5 1.5-5.4 5.4-3.2-3.2z"/></svg>',
            'globe' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20zm6.9 9a7 7 0 0 0-2-4.1h-2.3c.4 1.2.7 2.6.8 4.1h3.5zm-5.5 0c-.1-1.6-.4-3-.9-4.1h-1c-.5 1.1-.8 2.5-.9 4.1h2.8zm-4 0c.1-1.5.4-2.9.8-4.1H7.9A7 7 0 0 0 5.9 11h3.5zm-3.5 2a7 7 0 0 0 2 4.1h2.3c-.4-1.2-.7-2.6-.8-4.1H5.9zm4.7 0c.1 1.6.4 3 .9 4.1h1c.5-1.1.8-2.5.9-4.1h-2.8zm4.8 0c-.1 1.5-.4 2.9-.8 4.1h2.3a7 7 0 0 0 2-4.1h-3.5z"/></svg>',
            'link' => '<svg viewBox="0 0 24 24" focusable="false"><path d="M9.4 14.6a1.2 1.2 0 0 1 0-1.7l3.5-3.5a1.2 1.2 0 1 1 1.7 1.7l-3.5 3.5a1.2 1.2 0 0 1-1.7 0zm-1.7 4.1 3.2-3.2a1.2 1.2 0 1 0-1.7-1.7L6 17a2.8 2.8 0 0 1-4-4l3.2-3.2a1.2 1.2 0 1 0-1.7-1.7L.3 11.3a5.2 5.2 0 0 0 7.4 7.4zm12-6 3.2-3.2a5.2 5.2 0 0 0-7.4-7.4l-3.2 3.2A1.2 1.2 0 1 0 14 7l3.2-3.2a2.8 2.8 0 0 1 4 4L18 11a1.2 1.2 0 1 0 1.7 1.7z"/></svg>',
        ];

        return isset($icons[$icon_key]) ? $icons[$icon_key] : '';
    }

    private function sanitize_icon_class($value) {
        $classes = preg_split('/\s+/', trim((string) $value));
        $classes = array_filter(array_map('sanitize_html_class', is_array($classes) ? $classes : []));

        return implode(' ', $classes);
    }

    private function get_sticky_footer_items($settings = null) {
        $settings = is_array($settings) ? $settings : $this->get_settings();
        $items = [];

        foreach ([1, 2, 3] as $position) {
            $key = 'sticky_item_' . $position;
            $item_key = isset($settings[$key]) ? sanitize_key($settings[$key]) : 'none';
            $item = $this->get_sticky_footer_item($item_key, $settings, $position);
            if (!empty($item['link'])) {
                $items[] = $item;
            }
        }

        return $items;
    }

    private function escape_sticky_footer_href($link) {
        $link = (string) $link;

        if ($link === '') {
            return '';
        }

        if ($link[0] === '#' || $link[0] === '/') {
            return $link;
        }

        return esc_url($link, ['http', 'https', 'mailto', 'tel']);
    }

    private function get_sticky_footer_item($item_key, $settings, $position = 0) {
        $custom_icon = $position ? $this->get_custom_sticky_footer_value($settings, $position, 'icon', 'fa-solid fa-link') : 'fa-solid fa-link';
        $presets = [
            'none' => [
                'link' => '',
                'text' => '',
                'icon' => '',
                'icon_key' => '',
                'target' => '',
            ],
            'custom' => [
                'link' => $position ? $this->get_custom_sticky_footer_value($settings, $position, 'link') : '',
                'text' => $position ? $this->get_custom_sticky_footer_value($settings, $position, 'text', 'Länk') : 'Länk',
                'icon' => $custom_icon,
                'icon_key' => $custom_icon === 'fa-solid fa-link' ? 'link' : '',
                'target' => '',
            ],
            'phone' => [
                'link' => !empty($settings['phone_primary']) ? 'tel:' . preg_replace('/\s+/', '', $settings['phone_primary']) : '',
                'text' => 'Ring',
                'icon' => 'fa-solid fa-phone',
                'icon_key' => 'phone',
                'target' => '',
            ],
            'email' => [
                'link' => !empty($settings['email']) ? 'mailto:' . $settings['email'] : '',
                'text' => 'Maila',
                'icon' => 'fa-solid fa-envelope',
                'icon_key' => 'email',
                'target' => '',
            ],
            'facebook' => [
                'link' => isset($settings['facebook_url']) ? $settings['facebook_url'] : '',
                'text' => 'Följ',
                'icon' => 'fa-brands fa-facebook',
                'icon_key' => '',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'instagram' => [
                'link' => isset($settings['instagram_url']) ? $settings['instagram_url'] : '',
                'text' => 'Följ',
                'icon' => 'fa-brands fa-instagram',
                'icon_key' => '',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'linkedin' => [
                'link' => isset($settings['linkedin_url']) ? $settings['linkedin_url'] : '',
                'text' => 'Följ',
                'icon' => 'fa-brands fa-linkedin',
                'icon_key' => '',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'youtube' => [
                'link' => isset($settings['youtube_url']) ? $settings['youtube_url'] : '',
                'text' => 'Titta',
                'icon' => 'fa-brands fa-youtube',
                'icon_key' => '',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'x' => [
                'link' => isset($settings['x_url']) ? $settings['x_url'] : '',
                'text' => 'Följ',
                'icon' => 'fa-brands fa-x-twitter',
                'icon_key' => '',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'reddit' => [
                'link' => isset($settings['reddit_url']) ? $settings['reddit_url'] : '',
                'text' => 'Gå med',
                'icon' => 'fa-brands fa-reddit',
                'icon_key' => '',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'booking' => [
                'link' => isset($settings['booking_url']) ? $settings['booking_url'] : '',
                'text' => 'Boka',
                'icon' => 'fa-solid fa-calendar-check',
                'icon_key' => 'calendar',
                'target' => ' target="_blank" rel="noopener"',
            ],
            'website' => [
                'link' => isset($settings['website']) ? $settings['website'] : '',
                'text' => 'Besök',
                'icon' => 'fa-solid fa-globe',
                'icon_key' => 'globe',
                'target' => ' target="_blank" rel="noopener"',
            ],
        ];

        return isset($presets[$item_key]) ? $presets[$item_key] : $presets['none'];
    }

    private function get_custom_sticky_footer_value($settings, $position, $field, $default = '') {
        $key = 'sticky_custom_' . absint($position) . '_' . $field;
        $value = isset($settings[$key]) ? trim((string) $settings[$key]) : '';

        return $value !== '' ? $value : $default;
    }

}
