<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Divi5_FAQ_Server {
    private static function get_style_attr_names() {
        return ['module', 'faqItem', 'faqQuestion', 'faqAnswer', 'faqIcon'];
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

        \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::register_module(
            dirname(__DIR__, 1),
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

            if ($attr_name === 'faqIcon') {
                $style_args['styleProps'] = [
                    'advancedStyles' => [
                        [
                            'componentName' => 'divi/common',
                            'props' => [
                                'attr' => $args['attrs']['faqIcon']['advanced']['color'] ?? [],
                                'property' => 'color',
                            ],
                        ],
                        [
                            'componentName' => 'divi/common',
                            'props' => [
                                'attr' => $args['attrs']['faqIcon']['advanced']['size'] ?? [],
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
        $args['elements']->script_data([
            'attrName' => 'module',
        ]);
    }

    public static function module_classnames($args) {
        $args['classnamesInstance']->add(
            \ET\Builder\Packages\Module\Options\Element\ElementClassnames::classnames([
                'attrs' => $args['attrs']['module']['decoration'] ?? [],
            ])
        );
    }

    public static function render_callback($attrs, $content, $block, $elements) {
        if (
            !class_exists('OnePlugin_Light_FAQ') ||
            !class_exists('\ET\Builder\Framework\Utility\HTMLUtility') ||
            !class_exists('\ET\Builder\Packages\Module\Module')
        ) {
            return '';
        }

        $faq_html = OnePlugin_Light_FAQ::instance()->render(self::flatten_faq_props($attrs));
        if (!is_string($faq_html) || $faq_html === '') {
            return '';
        }

        $module_inner = \ET\Builder\Framework\Utility\HTMLUtility::render([
            'tag' => 'div',
            'attributes' => [
                'class' => 'et_pb_module_inner',
            ],
            'childrenSanitizer' => 'et_core_esc_previously',
            'children' => $faq_html,
        ]);

        $module_elements = '';
        foreach (self::get_style_attr_names() as $attr_name) {
            $module_elements .= $elements->style_components([
                'attrName' => $attr_name,
            ]);
        }

        return \ET\Builder\Packages\Module\Module::render([
            'orderIndex' => $block->parsed_block['orderIndex'],
            'storeInstance' => $block->parsed_block['storeInstance'],
            'attrs' => $attrs,
            'elements' => $elements,
            'id' => $block->parsed_block['id'],
            'moduleClassName' => 'oneplugin_divi5_faq_module',
            'name' => $block->block_type->name,
            'classnamesFunction' => [__CLASS__, 'module_classnames'],
            'moduleCategory' => $block->block_type->category,
            'stylesComponent' => [__CLASS__, 'module_styles'],
            'scriptDataComponent' => [__CLASS__, 'module_script_data'],
            'children' => $module_elements . $module_inner,
        ]);
    }

    private static function flatten_faq_props($attrs) {
        $settings = self::get_faq_settings($attrs);

        return [
            'group' => self::get_setting_value($settings, 'group', ''),
            'limit' => self::get_setting_value($settings, 'limit', '-1'),
            'columns' => self::get_setting_value($settings, 'columns', '1'),
            'rows' => self::get_setting_value($settings, 'rows', '0'),
            'orderby' => self::get_setting_value($settings, 'orderby', 'menu_order'),
            'order' => self::get_setting_value($settings, 'order', 'ASC'),
            'schema' => self::get_setting_value($settings, 'schema', 'true'),
            'open_icon' => self::get_icon_value($attrs, 'faqOpenIcon', self::get_setting_value($settings, 'openIcon', '')),
            'close_icon' => self::get_icon_value($attrs, 'faqCloseIcon', self::get_setting_value($settings, 'closeIcon', '')),
            'use_same_icon' => self::get_setting_value($settings, 'useSameIcon', '0'),
            'animation' => self::get_setting_value($settings, 'animation', 'slide'),
            'animation_duration' => self::get_setting_value($settings, 'animationDuration', '220'),
            'accordion_mode' => self::get_setting_value($settings, 'accordionMode', 'single'),
            'open_first' => self::get_setting_value($settings, 'openFirst', 'false'),
        ];
    }

    private static function get_faq_settings($attrs) {
        if (
            isset($attrs['faqSettings']['innerContent']['desktop']['value']) &&
            is_array($attrs['faqSettings']['innerContent']['desktop']['value'])
        ) {
            return $attrs['faqSettings']['innerContent']['desktop']['value'];
        }

        return [];
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

    private static function get_setting_value($settings, $key, $fallback = '') {
        if (!isset($settings[$key])) {
            return $fallback;
        }

        $value = self::resolve_setting_value($settings[$key]);
        if (is_scalar($value) && (string) $value !== '') {
            return (string) $value;
        }

        if (is_array($value)) {
            $values = self::flatten_setting_values($value);
            if (!empty($values)) {
                return implode(',', $values);
            }
        }

        return $fallback;
    }

    private static function flatten_setting_values($value) {
        $values = [];

        if (is_scalar($value) && (string) $value !== '') {
            return [(string) $value];
        }

        if (!is_array($value)) {
            return [];
        }

        foreach (['desktop', 'value'] as $key) {
            if (array_key_exists($key, $value)) {
                $values = array_merge($values, self::flatten_setting_values($value[$key]));
            }
        }

        foreach ($value as $key => $child_value) {
            if ($key === 'desktop' || $key === 'value') {
                continue;
            }

            $values = array_merge($values, self::flatten_setting_values($child_value));
        }

        return array_values(array_unique($values));
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

OnePlugin_Light_Divi5_FAQ_Server::init();
