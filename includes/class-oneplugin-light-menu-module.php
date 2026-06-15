<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Menu_Module {
    const VERSION = ONEPLUGIN_LIGHT_VERSION;

    private static $instance = null;
    private $assets_enqueued = false;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() {
        add_shortcode('oneplugin2_menu', [$this, 'render_shortcode']);
        add_shortcode('oneplugin_menu', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts = []) {
        $defaults = [
            'source_type' => 'location',
            'menu_slug' => '',
            'menu_location' => '',
            'layout' => 'horizontal',
            'align' => 'center',
            'mobile_style' => 'offcanvas',
            'mobile_side' => 'right',
            'mobile_breakpoint' => '980',
            'submenu_trigger' => 'hover',
            'hover_effect' => 'underline',
            'show_submenu_indicator' => 'on',
            'close_on_outside_click' => 'on',
            'close_on_link_click' => 'on',
            'toggle_label' => 'Menu',
            'aria_label' => 'Primary menu',
            'menu_bg_color' => 'transparent',
            'menu_text_color' => '#111827',
            'menu_hover_text_color' => '#111827',
            'menu_hover_bg_color' => 'rgba(17,24,39,.08)',
            'menu_active_text_color' => '#111827',
            'menu_active_bg_color' => 'rgba(17,24,39,.12)',
            'submenu_bg_color' => '#ffffff',
            'submenu_text_color' => '#111827',
            'submenu_hover_text_color' => '#111827',
            'submenu_hover_bg_color' => 'rgba(17,24,39,.08)',
            'toggle_color' => '#111827',
            'toggle_bg_color' => 'transparent',
            'border_color' => 'rgba(17,24,39,.10)',
            'shadow_color' => 'rgba(17,24,39,.16)',
            'menu_gap' => '24',
            'submenu_width' => '240',
            'submenu_radius' => '16',
            'item_padding_y' => '14',
            'item_padding_x' => '18',
            'submenu_padding_y' => '12',
            'submenu_padding_x' => '16',
            'mobile_panel_width' => '360',
            'mobile_panel_offset' => '16',
            'mobile_overlay_color' => 'rgba(17,24,39,.5)',
            'submenu_indicator_icon' => '▾',
            'list_class' => '',
            'class' => '',
            'use_native_layout' => '0',
        ];

        $atts = shortcode_atts($defaults, is_array($atts) ? $atts : [], 'oneplugin2_menu');
        $atts = $this->sanitize_atts($atts);

        return $this->render_component($atts);
    }

    public function render_component($atts) {
        $menu_args = $this->build_wp_nav_menu_args($atts);
        if ($menu_args === null) {
            return $this->render_missing_menu_notice();
        }

        $menu_html = wp_nav_menu($menu_args);
        if (!is_string($menu_html) || trim($menu_html) === '') {
            return $this->render_missing_menu_notice();
        }

        $this->enqueue_assets();

        $wrapper_classes = [
            'oneplugin-menu',
            'oneplugin-menu--mobile-' . $atts['mobile_style'],
            'oneplugin-menu--mobile-side-' . $atts['mobile_side'],
            'oneplugin-menu--hover-' . $atts['hover_effect'],
        ];

        if ($atts['use_native_layout'] === '1') {
            $wrapper_classes[] = 'oneplugin-menu--native-layout';
        } else {
            $wrapper_classes[] = 'oneplugin-menu--' . $atts['layout'];
            $wrapper_classes[] = 'oneplugin-menu--align-' . $atts['align'];
        }

        if ($atts['class'] !== '') {
            $wrapper_classes[] = $atts['class'];
        }

        $instance_id = wp_unique_id('oneplugin-menu-');
        $panel_id = $instance_id . '-panel';
        $toggle_id = $instance_id . '-toggle';
        $style = $this->build_inline_style($atts);

        ob_start();
        ?>
        <nav
            id="<?php echo esc_attr($instance_id); ?>"
            class="<?php echo esc_attr(implode(' ', $wrapper_classes)); ?>"
            data-oneplugin-menu
            data-submenu-trigger="<?php echo esc_attr($atts['submenu_trigger']); ?>"
            data-mobile-style="<?php echo esc_attr($atts['mobile_style']); ?>"
            data-mobile-side="<?php echo esc_attr($atts['mobile_side']); ?>"
            data-mobile-breakpoint="<?php echo esc_attr((string) $atts['mobile_breakpoint']); ?>"
            data-close-outside="<?php echo esc_attr($atts['close_on_outside_click']); ?>"
            data-close-on-link-click="<?php echo esc_attr($atts['close_on_link_click']); ?>"
            data-show-indicator="<?php echo esc_attr($atts['show_submenu_indicator']); ?>"
            aria-label="<?php echo esc_attr($atts['aria_label']); ?>"
            style="<?php echo esc_attr($style); ?>"
        >
            <div class="oneplugin-menu__bar">
                <button
                    id="<?php echo esc_attr($toggle_id); ?>"
                    type="button"
                    class="oneplugin-menu__toggle"
                    aria-label="<?php echo esc_attr($atts['toggle_label']); ?>"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($panel_id); ?>"
                >
                    <span class="oneplugin-menu__toggle-icon" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>
            </div>
            <button type="button" class="oneplugin-menu__overlay" tabindex="-1" aria-hidden="true" aria-label="<?php esc_attr_e('Close menu', 'oneplugin-light-site-tools'); ?>"></button>
            <div
                id="<?php echo esc_attr($panel_id); ?>"
                class="oneplugin-menu__panel"
                aria-labelledby="<?php echo esc_attr($toggle_id); ?>"
            >
                <?php echo $menu_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        </nav>
        <?php

        return (string) ob_get_clean();
    }

    private function sanitize_atts($atts) {
        $text_fields = [
            'source_type',
            'menu_slug',
            'menu_location',
            'layout',
            'align',
            'mobile_style',
            'mobile_side',
            'submenu_trigger',
            'hover_effect',
            'toggle_label',
            'aria_label',
        ];

        $color_fields = [
            'menu_bg_color' => 'transparent',
            'menu_text_color' => '#111827',
            'menu_hover_text_color' => '#111827',
            'menu_hover_bg_color' => 'rgba(17,24,39,.08)',
            'menu_active_text_color' => '#111827',
            'menu_active_bg_color' => 'rgba(17,24,39,.12)',
            'submenu_bg_color' => '#ffffff',
            'submenu_text_color' => '#111827',
            'submenu_hover_text_color' => '#111827',
            'submenu_hover_bg_color' => 'rgba(17,24,39,.08)',
            'toggle_color' => '#111827',
            'toggle_bg_color' => '#ffffff',
            'border_color' => 'rgba(17,24,39,.10)',
            'shadow_color' => 'rgba(17,24,39,.16)',
            'mobile_overlay_color' => 'rgba(17,24,39,.5)',
        ];

        foreach ($text_fields as $field) {
            $atts[$field] = isset($atts[$field]) ? sanitize_text_field((string) $atts[$field]) : '';
        }

        foreach ($color_fields as $field => $default) {
            $atts[$field] = $this->sanitize_color_value(isset($atts[$field]) ? $atts[$field] : '', $default);
        }

        $atts['mobile_breakpoint'] = $this->sanitize_number($atts['mobile_breakpoint'], 980, 320, 1600);
        $atts['menu_gap'] = $this->sanitize_number($atts['menu_gap'], 24, 0, 120);
        $atts['submenu_width'] = $this->sanitize_number($atts['submenu_width'], 240, 160, 640);
        $atts['submenu_radius'] = $this->sanitize_number($atts['submenu_radius'], 16, 0, 60);
        $atts['item_padding_y'] = $this->sanitize_number($atts['item_padding_y'], 14, 4, 40);
        $atts['item_padding_x'] = $this->sanitize_number($atts['item_padding_x'], 18, 4, 48);
        $atts['submenu_padding_y'] = $this->sanitize_number($atts['submenu_padding_y'], 12, 4, 40);
        $atts['submenu_padding_x'] = $this->sanitize_number($atts['submenu_padding_x'], 16, 4, 48);
        $atts['mobile_panel_width'] = $this->sanitize_number($atts['mobile_panel_width'], 360, 220, 720);
        $atts['mobile_panel_offset'] = $this->sanitize_number($atts['mobile_panel_offset'], 16, 0, 80);
        $atts['submenu_indicator_icon'] = $this->normalize_indicator_icon_value(isset($atts['submenu_indicator_icon']) ? $atts['submenu_indicator_icon'] : '', '▾');
        $atts['list_class'] = isset($atts['list_class']) ? $this->sanitize_class_names((string) $atts['list_class']) : '';
        $atts['class'] = isset($atts['class']) ? $this->sanitize_class_names((string) $atts['class']) : '';
        $atts['use_native_layout'] = $this->normalize_on_off_value(isset($atts['use_native_layout']) ? $atts['use_native_layout'] : '0', 'off') === 'on' ? '1' : '0';

        if (!in_array($atts['source_type'], ['location', 'menu'], true)) {
            $atts['source_type'] = 'location';
        }

        if (!in_array($atts['layout'], ['horizontal', 'vertical'], true)) {
            $atts['layout'] = 'horizontal';
        }

        if (!in_array($atts['align'], ['left', 'center', 'right', 'space-between'], true)) {
            $atts['align'] = 'center';
        }

        if (!in_array($atts['mobile_style'], ['offcanvas', 'dropdown'], true)) {
            $atts['mobile_style'] = 'offcanvas';
        }

        if (!in_array($atts['mobile_side'], ['left', 'right'], true)) {
            $atts['mobile_side'] = 'right';
        }

        if (!in_array($atts['submenu_trigger'], ['hover', 'click'], true)) {
            $atts['submenu_trigger'] = 'hover';
        }

        if (!in_array($atts['hover_effect'], ['none', 'underline', 'fill', 'lift'], true)) {
            $atts['hover_effect'] = 'underline';
        }

        $atts['show_submenu_indicator'] = $this->normalize_on_off_value($atts['show_submenu_indicator'], 'on');
        $atts['close_on_outside_click'] = $this->normalize_on_off_value($atts['close_on_outside_click'], 'on');
        $atts['close_on_link_click'] = $this->normalize_on_off_value($atts['close_on_link_click'], 'on');
        $atts['toggle_label'] = $atts['toggle_label'] !== '' ? $atts['toggle_label'] : __('Menu', 'oneplugin-light-site-tools');
        $atts['aria_label'] = $atts['aria_label'] !== '' ? $atts['aria_label'] : __('Primary menu', 'oneplugin-light-site-tools');

        return $atts;
    }

    private function normalize_on_off_value($value, $default = 'on') {
        if (is_bool($value)) {
            return $value ? 'on' : 'off';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 0 ? 'off' : 'on';
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['off', 'false', 'no', '0'], true)) {
            return 'off';
        }

        if (in_array($normalized, ['on', 'true', 'yes', '1'], true)) {
            return 'on';
        }

        return $default === 'off' ? 'off' : 'on';
    }

    private function normalize_indicator_icon_value($value, $fallback) {
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

        return wp_html_excerpt(wp_strip_all_tags($value), 16, '');
    }

    private function sanitize_number($value, $default, $min, $max) {
        $number = absint($value);
        if ($number < $min || $number > $max) {
            $number = $default;
        }

        return $number;
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

    private function sanitize_class_names($value) {
        $classes = preg_split('/\s+/', trim((string) $value));
        $classes = array_filter(array_map('sanitize_html_class', is_array($classes) ? $classes : []));

        return implode(' ', $classes);
    }

    private function build_wp_nav_menu_args($atts) {
        $args = [
            'container' => false,
            'echo' => false,
            'fallback_cb' => false,
            'menu_class' => trim('oneplugin-menu__list ' . $atts['list_class']),
            'items_wrap' => '<ul class="%2$s">%3$s</ul>',
        ];

        if (class_exists('OnePlugin_Menu_Walker')) {
            $args['walker'] = new OnePlugin_Menu_Walker($atts['submenu_indicator_icon']);
        }

        if ($atts['source_type'] === 'location') {
            if ($atts['menu_location'] === '') {
                return null;
            }

            $args['theme_location'] = $atts['menu_location'];
            return $args;
        }

        if ($atts['menu_slug'] === '') {
            return null;
        }

        $menu_value = $atts['menu_slug'];
        $args['menu'] = ctype_digit((string) $menu_value) ? absint($menu_value) : $menu_value;

        return $args;
    }

    private function build_inline_style($atts) {
        $vars = [
            '--oneplugin-menu-bg:' . $atts['menu_bg_color'],
            '--oneplugin-menu-text:' . $atts['menu_text_color'],
            '--oneplugin-menu-hover-text:' . $atts['menu_hover_text_color'],
            '--oneplugin-menu-hover-bg:' . $atts['menu_hover_bg_color'],
            '--oneplugin-menu-active-text:' . $atts['menu_active_text_color'],
            '--oneplugin-menu-active-bg:' . $atts['menu_active_bg_color'],
            '--oneplugin-submenu-bg:' . $atts['submenu_bg_color'],
            '--oneplugin-submenu-text:' . $atts['submenu_text_color'],
            '--oneplugin-submenu-hover-text:' . $atts['submenu_hover_text_color'],
            '--oneplugin-submenu-hover-bg:' . $atts['submenu_hover_bg_color'],
            '--oneplugin-toggle-color:' . $atts['toggle_color'],
            '--oneplugin-toggle-bg:' . $atts['toggle_bg_color'],
            '--oneplugin-border-color:' . $atts['border_color'],
            '--oneplugin-shadow-color:' . $atts['shadow_color'],
            '--oneplugin-submenu-width:' . $atts['submenu_width'] . 'px',
            '--oneplugin-submenu-radius:' . $atts['submenu_radius'] . 'px',
            '--oneplugin-item-padding-y:' . $atts['item_padding_y'] . 'px',
            '--oneplugin-item-padding-x:' . $atts['item_padding_x'] . 'px',
            '--oneplugin-submenu-padding-y:' . $atts['submenu_padding_y'] . 'px',
            '--oneplugin-submenu-padding-x:' . $atts['submenu_padding_x'] . 'px',
            '--oneplugin-mobile-panel-width:' . $atts['mobile_panel_width'] . 'px',
            '--oneplugin-mobile-panel-offset:' . $atts['mobile_panel_offset'] . 'px',
            '--oneplugin-overlay-bg:' . $atts['mobile_overlay_color'],
        ];

        if ($atts['use_native_layout'] !== '1') {
            $vars[] = '--oneplugin-menu-gap:' . $atts['menu_gap'] . 'px';
        }

        return implode(';', $vars);
    }

    private function render_missing_menu_notice() {
        if (!current_user_can('edit_theme_options')) {
            return '';
        }

        return '<div class="oneplugin-menu oneplugin-menu--empty">' . esc_html__('OnePlugin Menu: choose an existing menu or a theme location before using the module.', 'oneplugin-light-site-tools') . '</div>';
    }

    private function enqueue_assets() {
        if ($this->assets_enqueued) {
            return;
        }

        wp_enqueue_style(
            'oneplugin2-menu-inline',
            ONEPLUGIN_LIGHT_URL . 'assets/css/menu.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'oneplugin2-menu-inline',
            ONEPLUGIN_LIGHT_URL . 'assets/js/menu.js',
            [],
            self::VERSION,
            true
        );

        $this->assets_enqueued = true;
    }
}

if (class_exists('Walker_Nav_Menu')) {
    final class OnePlugin_Menu_Walker extends Walker_Nav_Menu {
        private $submenu_indicator_icon = '▾';

        public function __construct($icon_value = '▾') {
            if (is_array($icon_value)) {
                $this->submenu_indicator_icon = $icon_value;
                return;
            }

            $icon_value = trim((string) $icon_value);
            if ($icon_value !== '') {
                $this->submenu_indicator_icon = $icon_value;
            }
        }

        public function display_element($element, &$children_elements, $max_depth, $depth, $args, &$output) {
            if (!$element) {
                return;
            }

            $id_field = $this->db_fields['id'];
            $element->oneplugin_has_children = !empty($children_elements[$element->$id_field]);

            parent::display_element($element, $children_elements, $max_depth, $depth, $args, $output);
        }

        public function start_lvl(&$output, $depth = 0, $args = null) {
            $indent = str_repeat("\t", $depth);
            $output .= "\n$indent<ul class=\"oneplugin-menu__submenu sub-menu\">\n";
        }

        public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
            $indent = $depth ? str_repeat("\t", $depth) : '';
            $classes = empty($item->classes) ? [] : (array) $item->classes;
            $classes[] = 'oneplugin-menu__item';
            $classes[] = 'oneplugin-menu__item--depth-' . $depth;

            if (!empty($item->oneplugin_has_children)) {
                $classes[] = 'oneplugin-menu__item--has-children';
            }

            $class_names = implode(' ', array_filter(array_map('sanitize_html_class', $classes)));
            $output .= $indent . '<li class="' . esc_attr($class_names) . '">';

            $atts = [
                'title' => !empty($item->attr_title) ? $item->attr_title : '',
                'target' => !empty($item->target) ? $item->target : '',
                'rel' => !empty($item->xfn) ? $item->xfn : '',
                'href' => !empty($item->url) ? $item->url : '',
                'class' => 'oneplugin-menu__link',
            ];

            if ($depth > 0) {
                $atts['class'] .= ' oneplugin-menu__link--submenu';
            }

            if (!empty($item->oneplugin_has_children)) {
                $atts['aria-haspopup'] = 'true';
                $atts['aria-expanded'] = 'false';
                $atts['data-oneplugin-submenu-link'] = 'true';
            }

            $attributes = '';
            foreach ($atts as $attr => $value) {
                if ($value === '') {
                    continue;
                }

                $value = $attr === 'href' ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }

            $title = apply_filters('the_title', $item->title, $item->ID);
            $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);
            $label = wp_strip_all_tags($title);

            $item_inner_class = 'oneplugin-menu__item-inner';
            if (!empty($item->oneplugin_has_children)) {
                $item_inner_class .= ' oneplugin-menu__item-inner--has-submenu';
            }

            $item_output = '<div class="' . esc_attr($item_inner_class) . '">';
            $item_output .= '<a' . $attributes . '>';
            $item_output .= '<span class="oneplugin-menu__link-text">' . esc_html($label) . '</span>';
            $item_output .= '</a>';

            if (!empty($item->oneplugin_has_children)) {
                $item_output .= '<button type="button" class="oneplugin-menu__submenu-toggle" aria-expanded="false" aria-label="' . esc_attr(sprintf(__('Toggle submenu for %s', 'oneplugin-light-site-tools'), $label)) . '">';
                $item_output .= '<span class="oneplugin-menu__submenu-toggle-icon" aria-hidden="true">' . $this->render_indicator_icon_inner() . '</span>';
                $item_output .= '</button>';
            }

            $item_output .= '</div>';
            $output .= $item_output;
        }

        public function end_el(&$output, $item, $depth = 0, $args = null) {
            $output .= "</li>\n";
        }

        private function render_indicator_icon_inner() {
            if (is_array($this->submenu_indicator_icon)) {
                return $this->render_divi_icon_inner($this->submenu_indicator_icon);
            }

            $icon_value = trim((string) $this->submenu_indicator_icon);
            if ($icon_value === '') {
                return '';
            }

            if ($this->looks_like_icon_class($icon_value)) {
                $icon_class = $this->sanitize_icon_class($icon_value);
                if ($icon_class !== '') {
                    return '<i class="' . esc_attr($icon_class) . '" aria-hidden="true"></i>';
                }
            }

            return '<span class="oneplugin-menu__submenu-toggle-icon-text">' . esc_html($icon_value) . '</span>';
        }

        private function render_divi_icon_inner($icon_value) {
            $glyph = '';
            if (class_exists('\ET\Builder\Packages\IconLibrary\IconFont\Utils')) {
                $glyph = \ET\Builder\Packages\IconLibrary\IconFont\Utils::process_font_icon($icon_value);
            }

            if ($glyph === '') {
                $glyph = $this->extract_divi_icon_glyph($icon_value);
            }

            if ($glyph === '') {
                return '';
            }

            $font_family = $this->get_divi_icon_font_family($icon_value);
            $font_weight = strpos(strtolower($font_family), 'awesome') !== false ? 'font-weight:900;' : '';

            return sprintf(
                '<span class="oneplugin-menu__submenu-toggle-icon-glyph et-pb-icon" style="%1$s">%2$s</span>',
                esc_attr('font-family:' . $font_family . ' !important;' . $font_weight),
                esc_html($glyph)
            );
        }

        private function extract_divi_icon_glyph($icon_value) {
            if (is_scalar($icon_value)) {
                return $this->normalize_divi_icon_glyph((string) $icon_value);
            }

            if (!is_array($icon_value)) {
                return '';
            }

            foreach (['unicode', 'iconUnicode', 'icon_unicode', 'code', 'content', 'glyph', 'icon'] as $key) {
                if (isset($icon_value[$key]) && is_scalar($icon_value[$key])) {
                    $glyph = $this->normalize_divi_icon_glyph((string) $icon_value[$key]);
                    if ($glyph !== '') {
                        return $glyph;
                    }
                }
            }

            foreach ($icon_value as $value) {
                if (!is_array($value)) {
                    continue;
                }

                $glyph = $this->extract_divi_icon_glyph($value);
                if ($glyph !== '') {
                    return $glyph;
                }
            }

            return '';
        }

        private function normalize_divi_icon_glyph($value) {
            $value = trim($value);
            if ($value === '') {
                return '';
            }

            $decoded = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            if ($decoded !== $value && $decoded !== '') {
                return $decoded;
            }

            if (preg_match('/^\\\\?([a-fA-F0-9]{4,6})$/', $value, $matches)) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_QUOTES, 'UTF-8');
            }

            if (preg_match('/^u\\+([a-fA-F0-9]{4,6})$/i', $value, $matches)) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_QUOTES, 'UTF-8');
            }

            return preg_match('/^.{1,3}$/us', $value) === 1 ? $value : '';
        }

        private function get_divi_icon_font_family($icon_value) {
            if (is_array($icon_value)) {
                foreach (['fontFamily', 'font_family', 'iconFontFamily', 'icon_font_family', 'family'] as $key) {
                    if (isset($icon_value[$key]) && is_scalar($icon_value[$key]) && trim((string) $icon_value[$key]) !== '') {
                        return (string) $icon_value[$key];
                    }
                }

                if (isset($icon_value['type']) && is_scalar($icon_value['type']) && strtolower((string) $icon_value['type']) === 'fa') {
                    return 'Font Awesome 6 Free';
                }
            }

            return 'ETmodules';
        }

        private function looks_like_icon_class($value) {
            return $value !== '' && preg_match('/(^|\s)(fa-|fa[srldb]?|fa-solid|fa-regular|fa-brands|et-pb-icon)(\s|$)/', $value) === 1;
        }

        private function sanitize_icon_class($value) {
            $classes = preg_split('/\s+/', trim((string) $value));
            $classes = array_filter(array_map('sanitize_html_class', is_array($classes) ? $classes : []));

            return implode(' ', $classes);
        }
    }
}
