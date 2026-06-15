<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Divi5_Menu_Server {
    private static function get_style_attr_names() {
        return ['module', 'submenuIndicator', 'menuItem', 'submenuPanel', 'submenuItem', 'mobileToggle'];
    }

    public static function init() {
        add_action('init', [__CLASS__, 'register_module'], 20);
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

    public static function module_styles($args) {
        $elements = $args['elements'];
        $styles = [];

        foreach (self::get_style_attr_names() as $attr_name) {
            $style_args = [
                'attrName' => $attr_name,
            ];

            if ($attr_name === 'module') {
                $style_args['styleProps'] = [
                    'disabledOn' => [
                        'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
                    ],
                ];
            }

            if ($attr_name === 'submenuIndicator') {
                $style_args['styleProps'] = [
                    'advancedStyles' => [
                        [
                            'componentName' => 'divi/common',
                            'props' => [
                                'attr' => $args['attrs']['submenuIndicator']['advanced']['size'] ?? [],
                                'property' => 'font-size',
                            ],
                        ],
                    ],
                ];
            }

            $styles[] = $elements->style($style_args);
        }

        \ET\Builder\FrontEnd\Module\Style::add([
            'id' => $args['id'],
            'name' => $args['name'],
            'orderIndex' => $args['orderIndex'],
            'storeInstance' => $args['storeInstance'],
            'styles' => $styles,
        ]);
    }

    public static function module_script_data($args) {
        $args['elements']->script_data(
            [
                'attrName' => 'module',
            ]
        );
    }

    public static function module_classnames($args) {
        $classnames_instance = $args['classnamesInstance'];
        $attrs = $args['attrs'];

        $classnames_instance->add(
            \ET\Builder\Packages\Module\Options\Element\ElementClassnames::classnames(
                [
                    'attrs' => $attrs['module']['decoration'] ?? [],
                ]
            )
        );
    }

    public static function render_callback($attrs, $content, $block, $elements) {
        if (
            !class_exists('\ET\Builder\Framework\Utility\HTMLUtility') ||
            !class_exists('\ET\Builder\Packages\Module\Module')
        ) {
            return '';
        }

        $menu_html = OnePlugin_Light_Menu_Module::instance()->render_shortcode(self::flatten_menu_props($attrs));
        if (!is_string($menu_html) || $menu_html === '') {
            return '';
        }

        $module_inner = \ET\Builder\Framework\Utility\HTMLUtility::render(
            [
                'tag' => 'div',
                'attributes' => [
                    'class' => 'et_pb_module_inner',
                ],
                'childrenSanitizer' => 'et_core_esc_previously',
                'children' => $menu_html,
            ]
        );

        $module_elements = '';
        foreach (self::get_style_attr_names() as $attr_name) {
            $module_elements .= $elements->style_components(
                [
                    'attrName' => $attr_name,
                ]
            );
        }

        return \ET\Builder\Packages\Module\Module::render(
            [
                'orderIndex' => $block->parsed_block['orderIndex'],
                'storeInstance' => $block->parsed_block['storeInstance'],
                'attrs' => $attrs,
                'elements' => $elements,
                'id' => $block->parsed_block['id'],
                'moduleClassName' => 'oneplugin_divi5_menu_module',
                'name' => $block->block_type->name,
                'classnamesFunction' => [__CLASS__, 'module_classnames'],
                'moduleCategory' => $block->block_type->category,
                'stylesComponent' => [__CLASS__, 'module_styles'],
                'scriptDataComponent' => [__CLASS__, 'module_script_data'],
                'children' => $module_elements . $module_inner,
            ]
        );
    }

    private static function flatten_menu_props($attrs) {
        $menu_settings = [];

        if (
            isset($attrs['menuSettings']['innerContent']['desktop']['value']) &&
            is_array($attrs['menuSettings']['innerContent']['desktop']['value'])
        ) {
            $menu_settings = $attrs['menuSettings']['innerContent']['desktop']['value'];
        }

        $menu_id = self::get_menu_setting_value($menu_settings, 'menuId', '');
        $source_type = self::get_menu_setting_value($menu_settings, 'sourceType', 'menu');
        $menu_location = self::get_menu_setting_value($menu_settings, 'menuLocation', '');
        $mobile_style = self::get_menu_setting_value($menu_settings, 'mobileStyle', 'offcanvas');
        $mobile_side = self::get_menu_setting_value($menu_settings, 'mobileSide', 'right');
        $mobile_breakpoint = self::get_menu_setting_value($menu_settings, 'mobileBreakpoint', '980');
        $submenu_trigger = self::get_menu_setting_value($menu_settings, 'submenuTrigger', 'hover');
        $hover_effect = self::get_menu_setting_value($menu_settings, 'hoverEffect', 'underline');
        $show_submenu_indicator = self::get_menu_setting_value($menu_settings, 'showSubmenuIndicator', 'on');
        $close_on_outside_click = self::get_menu_setting_value($menu_settings, 'closeOnOutsideClick', 'on');
        $close_on_link_click = self::get_menu_setting_value($menu_settings, 'closeOnLinkClick', 'on');
        $menu_text_color = self::get_menu_setting_value($menu_settings, 'menuTextColor', '#111827');
        $menu_hover_text_color = self::get_menu_setting_value($menu_settings, 'menuHoverTextColor', '#111827');
        $menu_hover_background_color = self::get_menu_setting_value($menu_settings, 'menuHoverBackgroundColor', 'rgba(17,24,39,.08)');
        $item_active_color = self::get_menu_setting_value($menu_settings, 'itemActiveColor', '#111827');
        $item_active_background_color = self::get_menu_setting_value($menu_settings, 'itemActiveBackgroundColor', 'rgba(17,24,39,.12)');
        $submenu_background_color = self::get_menu_setting_value($menu_settings, 'submenuBackgroundColor', '#ffffff');
        $submenu_text_color = self::get_menu_setting_value($menu_settings, 'submenuTextColor', '#111827');
        $submenu_hover_text_color = self::get_menu_setting_value($menu_settings, 'submenuHoverTextColor', '#111827');
        $submenu_hover_background_color = self::get_menu_setting_value($menu_settings, 'submenuHoverBackgroundColor', 'rgba(17,24,39,.08)');
        $toggle_color = self::get_menu_setting_value($menu_settings, 'toggleColor', '#111827');
        $toggle_background_color = self::get_menu_setting_value($menu_settings, 'toggleBackgroundColor', 'transparent');
        $border_color = self::get_menu_setting_value($menu_settings, 'borderColor', 'rgba(17,24,39,.10)');
        $shadow_color = self::get_menu_setting_value($menu_settings, 'shadowColor', 'rgba(17,24,39,.16)');
        $submenu_width = self::get_menu_setting_value($menu_settings, 'submenuWidth', '240');
        $submenu_radius = self::get_menu_setting_value($menu_settings, 'submenuRadius', '16');
        $item_padding_y = self::get_menu_setting_value($menu_settings, 'itemPaddingY', '14');
        $item_padding_x = self::get_menu_setting_value($menu_settings, 'itemPaddingX', '18');
        $submenu_padding_y = self::get_menu_setting_value($menu_settings, 'submenuPaddingY', '12');
        $submenu_padding_x = self::get_menu_setting_value($menu_settings, 'submenuPaddingX', '16');
        $mobile_panel_width = self::get_menu_setting_value($menu_settings, 'mobilePanelWidth', '360');
        $mobile_panel_offset = self::get_menu_setting_value($menu_settings, 'mobilePanelOffset', '16');
        $mobile_overlay_color = self::get_menu_setting_value($menu_settings, 'mobileOverlayColor', 'rgba(17,24,39,.5)');
        $submenu_indicator_icon = self::get_icon_value($attrs, 'submenuIndicator', '▾');
        $list_class = self::get_layout_class($attrs);

        if (!in_array($hover_effect, ['none', 'underline', 'fill', 'lift'], true)) {
            $hover_effect = 'underline';
        }

        if (!in_array($source_type, ['menu', 'location'], true)) {
            $source_type = 'menu';
        }

        return [
            'source_type' => $source_type,
            'menu_slug' => $menu_id,
            'menu_location' => $menu_location,
            'use_native_layout' => '1',
            'mobile_style' => $mobile_style,
            'mobile_side' => $mobile_side,
            'mobile_breakpoint' => $mobile_breakpoint,
            'submenu_trigger' => $submenu_trigger,
            'hover_effect' => $hover_effect,
            'show_submenu_indicator' => $show_submenu_indicator,
            'close_on_outside_click' => $close_on_outside_click,
            'close_on_link_click' => $close_on_link_click,
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
            'mobile_overlay_color' => $mobile_overlay_color,
            'submenu_indicator_icon' => $submenu_indicator_icon,
            'list_class' => $list_class,
            'class' => $list_class,
        ];
    }

    private static function get_layout_class($attrs) {
        $layout_settings = $attrs['module']['decoration']['layout'] ?? [];
        $layout_value = self::find_layout_value($layout_settings);

        if ($layout_value === 'grid') {
            return 'et_grid_module';
        }

        if ($layout_value === 'block') {
            return 'et_block_module';
        }

        return 'et_flex_module';
    }

    private static function find_layout_value($value) {
        if (is_scalar($value)) {
            $value = strtolower((string) $value);
            return in_array($value, ['flex', 'grid', 'block'], true) ? $value : '';
        }

        if (!is_array($value)) {
            return '';
        }

        foreach (['desktop', 'value', 'layout', 'display', 'mode', 'type'] as $key) {
            if (!array_key_exists($key, $value)) {
                continue;
            }

            $found = self::find_layout_value($value[$key]);
            if ($found !== '') {
                return $found;
            }
        }

        foreach ($value as $child_value) {
            $found = self::find_layout_value($child_value);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    private static function get_menu_setting_value($settings, $key, $fallback = '') {
        if (!isset($settings[$key])) {
            return $fallback;
        }

        $value = $settings[$key];
        if (is_scalar($value) && (string) $value !== '') {
            return (string) $value;
        }

        if (is_array($value)) {
            if (isset($value['desktop']['value']) && is_scalar($value['desktop']['value']) && (string) $value['desktop']['value'] !== '') {
                return (string) $value['desktop']['value'];
            }

            if (isset($value['value']) && is_scalar($value['value']) && (string) $value['value'] !== '') {
                return (string) $value['value'];
            }
        }

        return $fallback;
    }

    private static function get_icon_value($attrs, $attr_name, $fallback = '') {
        $value = $attrs[$attr_name]['innerContent'] ?? null;
        $resolved = self::resolve_setting_value($value);

        if ($resolved === null || $resolved === '') {
            return $fallback;
        }

        if (is_scalar($resolved)) {
            return (string) $resolved;
        }

        $encoded = wp_json_encode($resolved);

        return is_string($encoded) ? $encoded : $fallback;
    }

    private static function resolve_setting_value($value) {
        if (is_array($value)) {
            if (isset($value['desktop']) && is_array($value['desktop']) && array_key_exists('value', $value['desktop'])) {
                return $value['desktop']['value'];
            }

            if (array_key_exists('value', $value)) {
                return $value['value'];
            }
        }

        return $value;
    }
}

OnePlugin_Light_Divi5_Menu_Server::init();
