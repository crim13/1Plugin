<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Divi5_Logo_Server {
    private static $style_attr_names = ['module', 'logoImage'];

    public static function init() {
        add_action('init', [__CLASS__, 'register_module'], 20);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_styles']);
    }

    public static function register_module() {
        if (
            !class_exists('\ET\Builder\Packages\ModuleLibrary\ModuleRegistration') ||
            !function_exists('et_builder_d5_enabled') ||
            !et_builder_d5_enabled()
        ) {
            return;
        }

        $module_json_folder_path = dirname(__DIR__, 1);

        \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::register_module(
            $module_json_folder_path,
            [
                'render_callback' => [__CLASS__, 'render_callback'],
            ]
        );
    }

    public static function enqueue_styles() {
        $css_path = ONEPLUGIN_LIGHT_PATH . 'assets/css/logo.css';
        if (!file_exists($css_path)) {
            return;
        }

        wp_enqueue_style(
            'oneplugin-logo',
            ONEPLUGIN_LIGHT_URL . 'assets/css/logo.css',
            [],
            (string) filemtime($css_path)
        );
    }

    public static function module_styles($args) {
        $elements = $args['elements'];
        $styles   = [];

        foreach (self::$style_attr_names as $attr_name) {
            $style_args = ['attrName' => $attr_name];

            if ($attr_name === 'module') {
                $style_args['styleProps'] = [
                    'disabledOn' => [
                        'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                    ],
                ];
            }

            $styles[] = $elements->style($style_args);
        }

        \ET\Builder\FrontEnd\Module\Style::add([
            'id'            => $args['id'],
            'name'          => $args['name'],
            'orderIndex'    => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles'        => $styles,
        ]);
    }

    public static function module_script_data($args) {
        $args['elements']->script_data(['attrName' => 'module']);
    }

    public static function module_classnames($args) {
        $classnames_instance = $args['classnamesInstance'];
        $attrs               = $args['attrs'];

        $classnames_instance->add(
            \ET\Builder\Packages\Module\Options\Element\ElementClassnames::classnames([
                'attrs' => $attrs['module']['decoration'] ?? [],
            ])
        );
    }

    public static function render_callback($attrs, $content, $block, $elements) {
        if (
            !class_exists('\ET\Builder\Framework\Utility\HTMLUtility') ||
            !class_exists('\ET\Builder\Packages\Module\Module')
        ) {
            return '';
        }

        $logo_html = self::render_logo_html($attrs);
        if ($logo_html === '') {
            return '';
        }

        $module_inner = \ET\Builder\Framework\Utility\HTMLUtility::render([
            'tag'                => 'div',
            'attributes'         => ['class' => 'et_pb_module_inner'],
            'childrenSanitizer'  => 'et_core_esc_previously',
            'children'           => $logo_html,
        ]);

        $module_elements = '';
        foreach (self::$style_attr_names as $attr_name) {
            $module_elements .= $elements->style_components(['attrName' => $attr_name]);
        }

        return \ET\Builder\Packages\Module\Module::render([
            'orderIndex'        => $block->parsed_block['orderIndex'],
            'storeInstance'     => $block->parsed_block['storeInstance'],
            'attrs'             => $attrs,
            'elements'          => $elements,
            'id'                => $block->parsed_block['id'],
            'moduleClassName'   => 'oneplugin_divi5_logo_module',
            'name'              => $block->block_type->name,
            'classnamesFunction' => [__CLASS__, 'module_classnames'],
            'moduleCategory'    => $block->block_type->category,
            'stylesComponent'   => [__CLASS__, 'module_styles'],
            'scriptDataComponent' => [__CLASS__, 'module_script_data'],
            'children'          => $module_elements . $module_inner,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function render_logo_html($attrs) {
        $logo_settings = self::resolve_attr_value($attrs['logoSettings']['innerContent'] ?? null, []);
        $image_content = self::resolve_attr_value($attrs['logoImage']['innerContent'] ?? null, []);

        $logo_variant = self::get_value($logo_settings, 'logoVariant', 'auto');
        $logo_link    = self::get_value($logo_settings, 'logoLink', '');
        $logo_new_tab = self::get_value($logo_settings, 'logoNewTab', 'off');
        $custom_src   = self::get_value($image_content, 'src', '');
        $custom_alt   = self::get_value($image_content, 'alt', '');

        // Resolve logo URL — custom override takes priority over plugin settings.
        if ($custom_src !== '') {
            $logo_url   = esc_url($custom_src);
            $is_custom  = true;
        } else {
            $logo_urls  = self::get_plugin_logo_urls();
            $logo_url   = self::pick_logo_url($logo_urls, $logo_variant);
            $is_custom  = false;
        }

        if ($logo_url === '') {
            return '';
        }

        // Alt text: custom override > site name.
        $alt = $custom_alt !== '' ? $custom_alt : get_bloginfo('name');

        // Link URL: field value > homepage.
        $href   = $logo_link !== '' ? $logo_link : home_url('/');
        $target = ($logo_new_tab === 'on')
            ? ' target="_blank" rel="noopener noreferrer"'
            : '';

        // Only the auto variant opts into the scoped header logo swap.
        $img_classes = (!$is_custom && $logo_variant === 'auto')
            ? 'oneplugin-custom-logo oneplugin-logo-img'
            : 'oneplugin-logo-img';

        $img = sprintf(
            '<img src="%s" class="%s" alt="%s" loading="eager" data-oneplugin-logo-variant="%s" />',
            esc_url($logo_url),
            esc_attr($img_classes),
            esc_attr($alt),
            esc_attr($logo_variant)
        );

        return sprintf(
            '<a href="%s" class="oneplugin-logo-link" aria-label="%s"%s>%s</a>',
            esc_url($href),
            esc_attr($alt),
            $target,
            $img
        );
    }

    /**
     * Reads logo attachment IDs from plugin settings (with all fallbacks)
     * and returns resolved public URLs keyed as main/sticky/mobile.
     *
     * @return array{main: string, sticky: string, mobile: string}
     */
    private static function get_plugin_logo_urls() {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $settings    = get_option('oneplugin_light_site_tools_settings', []);
        $legacy      = null;
        if (!is_array($settings) || empty($settings)) {
            $legacy   = get_option('oneplugin_light_site_tools_settings_legacy', []);
            $settings = is_array($legacy) ? $legacy : [];
        }

        // Main logo: plugin setting → WP custom_logo theme mod → Divi logo.
        $main_id = absint($settings['site_logo_id'] ?? 0);
        if (!$main_id) {
            $main_id = absint(get_theme_mod('custom_logo', 0));
        }
        if (!$main_id) {
            $main_id = absint(self::get_divi_logo_id());
        }

        $sticky_id = absint($settings['sticky_header_logo_id'] ?? 0) ?: $main_id;
        $mobile_id = absint($settings['mobile_logo_id'] ?? 0) ?: $main_id;

        $main_url   = $main_id   ? (wp_get_attachment_url($main_id) ?: '')   : '';
        $sticky_url = $sticky_id ? (wp_get_attachment_url($sticky_id) ?: '') : '';
        $mobile_url = $mobile_id ? (wp_get_attachment_url($mobile_id) ?: '') : '';

        $cache = [
            'main'   => $main_url,
            'sticky' => $sticky_url ?: $main_url,
            'mobile' => $mobile_url ?: $main_url,
        ];

        return $cache;
    }

    private static function pick_logo_url(array $logo_urls, $variant) {
        switch ($variant) {
            case 'sticky': return $logo_urls['sticky'];
            case 'mobile': return $logo_urls['mobile'];
            case 'main':
            case 'auto':
            default:       return $logo_urls['main'];
        }
    }

    /**
     * Mirrors the Divi logo detection from OnePlugin_Light_Site_Tools.
     * Returns 0 if Divi is not active or no logo is found.
     */
    private static function get_divi_logo_id() {
        if (!function_exists('et_get_option')) {
            return 0;
        }

        $divi_logo = et_get_option('divi_logo');
        if (!$divi_logo) {
            return 0;
        }

        $id = attachment_url_to_postid($divi_logo);
        return $id ?: 0;
    }

    /**
     * Resolves a Divi responsive attribute value (desktop default).
     * If the stored value is already an array of item values (innerContent),
     * returns that array; otherwise returns $fallback.
     *
     * @param mixed $attr
     * @param mixed $fallback
     * @return mixed
     */
    private static function resolve_attr_value($attr, $fallback) {
        if (!is_array($attr)) {
            return $fallback;
        }

        if (isset($attr['desktop']['value'])) {
            return is_array($attr['desktop']['value'])
                ? $attr['desktop']['value']
                : $fallback;
        }

        if (isset($attr['value'])) {
            return is_array($attr['value']) ? $attr['value'] : $fallback;
        }

        return $fallback;
    }

    /**
     * Reads a scalar value from an array, with a fallback.
     * Handles nested Divi responsive structures transparently.
     */
    private static function get_value(array $data, $key, $fallback = '') {
        if (!array_key_exists($key, $data)) {
            return $fallback;
        }

        $value = $data[$key];

        if (is_scalar($value) && (string) $value !== '') {
            return (string) $value;
        }

        if (is_array($value)) {
            $v = $value['desktop']['value'] ?? ($value['value'] ?? null);
            if (is_scalar($v) && (string) $v !== '') {
                return (string) $v;
            }
        }

        return $fallback;
    }
}

OnePlugin_Light_Divi5_Logo_Server::init();
