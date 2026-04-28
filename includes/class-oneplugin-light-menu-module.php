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
            'menu_gap' => '24',
            'submenu_width' => '240',
            'submenu_radius' => '16',
            'item_padding_y' => '14',
            'item_padding_x' => '18',
            'submenu_padding_y' => '12',
            'submenu_padding_x' => '16',
            'class' => '',
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
            'oneplugin-menu--' . $atts['layout'],
            'oneplugin-menu--align-' . $atts['align'],
            'oneplugin-menu--mobile-' . $atts['mobile_style'],
            'oneplugin-menu--mobile-side-' . $atts['mobile_side'],
            'oneplugin-menu--hover-' . $atts['hover_effect'],
        ];

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
            data-show-indicator="<?php echo esc_attr($atts['show_submenu_indicator']); ?>"
            aria-label="<?php echo esc_attr__('Primary menu', 'oneplugin-light-site-tools'); ?>"
            style="<?php echo esc_attr($style); ?>"
        >
            <div class="oneplugin-menu__bar">
                <button
                    id="<?php echo esc_attr($toggle_id); ?>"
                    type="button"
                    class="oneplugin-menu__toggle"
                    aria-expanded="false"
                    aria-controls="<?php echo esc_attr($panel_id); ?>"
                >
                    <span class="oneplugin-menu__toggle-icon" aria-hidden="true">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                    <span class="oneplugin-menu__toggle-label"><?php esc_html_e('Menu', 'oneplugin-light-site-tools'); ?></span>
                </button>
            </div>
            <button type="button" class="oneplugin-menu__overlay" tabindex="-1" aria-hidden="true"></button>
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
            'show_submenu_indicator',
            'close_on_outside_click',
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
        $atts['class'] = isset($atts['class']) ? $this->sanitize_class_names((string) $atts['class']) : '';

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

        $atts['show_submenu_indicator'] = $atts['show_submenu_indicator'] === 'off' ? 'off' : 'on';
        $atts['close_on_outside_click'] = $atts['close_on_outside_click'] === 'off' ? 'off' : 'on';

        return $atts;
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
            'menu_class' => 'oneplugin-menu__list',
            'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
        ];

        if (class_exists('OnePlugin_Menu_Walker')) {
            $args['walker'] = new OnePlugin_Menu_Walker();
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
            '--oneplugin-menu-gap:' . $atts['menu_gap'] . 'px',
            '--oneplugin-submenu-width:' . $atts['submenu_width'] . 'px',
            '--oneplugin-submenu-radius:' . $atts['submenu_radius'] . 'px',
            '--oneplugin-item-padding-y:' . $atts['item_padding_y'] . 'px',
            '--oneplugin-item-padding-x:' . $atts['item_padding_x'] . 'px',
            '--oneplugin-submenu-padding-y:' . $atts['submenu_padding_y'] . 'px',
            '--oneplugin-submenu-padding-x:' . $atts['submenu_padding_x'] . 'px',
        ];

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

            $item_output = '<div class="oneplugin-menu__item-inner">';
            $item_output .= '<a' . $attributes . '>';
            $item_output .= '<span class="oneplugin-menu__link-text">' . esc_html($label) . '</span>';
            $item_output .= '</a>';

            if (!empty($item->oneplugin_has_children)) {
                $item_output .= '<button type="button" class="oneplugin-menu__submenu-toggle" aria-expanded="false" aria-label="' . esc_attr(sprintf(__('Toggle submenu for %s', 'oneplugin-light-site-tools'), $label)) . '">';
                $item_output .= '<span class="oneplugin-menu__submenu-toggle-icon" aria-hidden="true"></span>';
                $item_output .= '</button>';
            }

            $item_output .= '</div>';
            $output .= $item_output;
        }

        public function end_el(&$output, $item, $depth = 0, $args = null) {
            $output .= "</li>\n";
        }
    }
}
