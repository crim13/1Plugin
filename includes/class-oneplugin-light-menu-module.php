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

        $css = '
            .oneplugin-menu{
                --oneplugin-menu-bg:transparent;
                --oneplugin-menu-text:#111827;
                --oneplugin-menu-hover-text:#111827;
                --oneplugin-menu-hover-bg:rgba(17,24,39,.08);
                --oneplugin-menu-active-text:#111827;
                --oneplugin-menu-active-bg:rgba(17,24,39,.12);
                --oneplugin-submenu-bg:#ffffff;
                --oneplugin-submenu-text:#111827;
                --oneplugin-submenu-hover-text:#111827;
                --oneplugin-submenu-hover-bg:rgba(17,24,39,.08);
                --oneplugin-toggle-color:#111827;
                --oneplugin-toggle-bg:#ffffff;
                --oneplugin-border-color:rgba(17,24,39,.10);
                --oneplugin-shadow-color:rgba(17,24,39,.16);
                --oneplugin-menu-gap:24px;
                --oneplugin-submenu-width:240px;
                --oneplugin-submenu-radius:16px;
                --oneplugin-item-padding-y:14px;
                --oneplugin-item-padding-x:18px;
                --oneplugin-submenu-padding-y:12px;
                --oneplugin-submenu-padding-x:16px;
                position:relative;
                width:100%;
                background:var(--oneplugin-menu-bg);
            }
            .oneplugin-menu *,
            .oneplugin-menu *::before,
            .oneplugin-menu *::after{
                box-sizing:border-box;
            }
            .oneplugin-menu__bar{
                display:none;
            }
            .oneplugin-menu__panel{
                position:relative;
            }
            .oneplugin-menu__list,
            .oneplugin-menu__submenu{
                list-style:none;
                margin:0;
                padding:0;
            }
            .oneplugin-menu__list{
                display:flex;
                flex-wrap:wrap;
                align-items:center;
                gap:var(--oneplugin-menu-gap);
            }
            .oneplugin-menu--vertical .oneplugin-menu__list{
                flex-direction:column;
                align-items:stretch;
                gap:8px;
            }
            .oneplugin-menu--align-left .oneplugin-menu__list{
                justify-content:flex-start;
            }
            .oneplugin-menu--align-center .oneplugin-menu__list{
                justify-content:center;
            }
            .oneplugin-menu--align-right .oneplugin-menu__list{
                justify-content:flex-end;
            }
            .oneplugin-menu--align-space-between .oneplugin-menu__list{
                justify-content:space-between;
            }
            .oneplugin-menu__item{
                position:relative;
                margin:0;
                list-style:none !important;
            }
            .oneplugin-menu__item::marker,
            .oneplugin-menu__submenu > .oneplugin-menu__item::marker,
            .oneplugin-menu__list > .oneplugin-menu__item::marker{
                content:"" !important;
                font-size:0 !important;
            }
            .oneplugin-menu__item::before,
            .oneplugin-menu__item::after,
            .oneplugin-menu__item-inner::before,
            .oneplugin-menu__item-inner::after,
            .oneplugin-menu__link-text::before,
            .oneplugin-menu__link-text::after,
            .oneplugin-menu__list > .oneplugin-menu__item + .oneplugin-menu__item::before,
            .oneplugin-menu__list > .oneplugin-menu__item + .oneplugin-menu__item::after,
            .oneplugin-menu__submenu > .oneplugin-menu__item + .oneplugin-menu__item::before,
            .oneplugin-menu__submenu > .oneplugin-menu__item + .oneplugin-menu__item::after{
                content:none !important;
                display:none !important;
            }
            .oneplugin-menu__item-inner{
                display:flex;
                align-items:center;
                gap:8px;
            }
            .oneplugin-menu--vertical .oneplugin-menu__item-inner{
                width:100%;
            }
            .oneplugin-menu__link{
                position:relative;
                display:inline-flex;
                align-items:center;
                min-height:44px;
                padding:var(--oneplugin-item-padding-y) var(--oneplugin-item-padding-x);
                border-radius:999px;
                color:var(--oneplugin-menu-text);
                text-decoration:none;
                transition:color .2s ease, background-color .2s ease, transform .2s ease;
            }
            .oneplugin-menu--vertical .oneplugin-menu__link{
                width:100%;
                border-radius:calc(var(--oneplugin-submenu-radius) - 4px);
            }
            .oneplugin-menu__link:focus-visible,
            .oneplugin-menu__submenu-toggle:focus-visible,
            .oneplugin-menu__toggle:focus-visible{
                outline:2px solid currentColor;
                outline-offset:2px;
            }
            .oneplugin-menu__item:hover > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu__item:focus-within > .oneplugin-menu__item-inner > .oneplugin-menu__link{
                color:var(--oneplugin-menu-hover-text);
            }
            .oneplugin-menu--hover-fill .oneplugin-menu__item:hover > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu--hover-fill .oneplugin-menu__item:focus-within > .oneplugin-menu__item-inner > .oneplugin-menu__link{
                background:var(--oneplugin-menu-hover-bg);
            }
            .oneplugin-menu--hover-lift .oneplugin-menu__item:hover > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu--hover-lift .oneplugin-menu__item:focus-within > .oneplugin-menu__item-inner > .oneplugin-menu__link{
                transform:translateY(-2px);
            }
            .oneplugin-menu--hover-underline .oneplugin-menu__link::after{
                content:"";
                position:absolute;
                left:var(--oneplugin-item-padding-x);
                right:var(--oneplugin-item-padding-x);
                bottom:8px;
                height:2px;
                border-radius:999px;
                background:currentColor;
                transform:scaleX(0);
                transform-origin:center;
                transition:transform .2s ease;
            }
            .oneplugin-menu--hover-underline .oneplugin-menu__item:hover > .oneplugin-menu__item-inner > .oneplugin-menu__link::after,
            .oneplugin-menu--hover-underline .oneplugin-menu__item:focus-within > .oneplugin-menu__item-inner > .oneplugin-menu__link::after,
            .oneplugin-menu--hover-underline .oneplugin-menu__item.current-menu-item > .oneplugin-menu__item-inner > .oneplugin-menu__link::after,
            .oneplugin-menu--hover-underline .oneplugin-menu__item.current-menu-ancestor > .oneplugin-menu__item-inner > .oneplugin-menu__link::after{
                transform:scaleX(1);
            }
            .oneplugin-menu__item.current-menu-item > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu__item.current-menu-ancestor > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu__item.current_page_item > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu__item.current_page_ancestor > .oneplugin-menu__item-inner > .oneplugin-menu__link{
                color:var(--oneplugin-menu-active-text);
                background:var(--oneplugin-menu-active-bg);
            }
            .oneplugin-menu__submenu-toggle{
                display:inline-flex;
                align-items:center;
                justify-content:center;
                width:36px;
                height:36px;
                padding:0;
                border:0;
                border-radius:999px;
                background:transparent;
                color:var(--oneplugin-menu-text);
                cursor:pointer;
                transition:background-color .2s ease, color .2s ease, transform .2s ease;
            }
            .oneplugin-menu__submenu-toggle:hover{
                background:var(--oneplugin-menu-hover-bg);
                color:var(--oneplugin-menu-hover-text);
            }
            .oneplugin-menu__submenu-toggle-icon{
                display:inline-flex;
                width:10px;
                height:10px;
                border-right:2px solid currentColor;
                border-bottom:2px solid currentColor;
                transform:rotate(45deg) translateY(-1px);
                transition:transform .2s ease;
            }
            .oneplugin-menu[data-show-indicator="off"] .oneplugin-menu__submenu-toggle{
                width:28px;
            }
            .oneplugin-menu[data-show-indicator="off"] .oneplugin-menu__submenu-toggle-icon{
                opacity:.5;
                transform:rotate(45deg) translateY(-1px) scale(.85);
            }
            .oneplugin-menu__item.is-submenu-open > .oneplugin-menu__item-inner > .oneplugin-menu__submenu-toggle .oneplugin-menu__submenu-toggle-icon{
                transform:rotate(-135deg) translateY(-1px);
            }
            .oneplugin-menu__submenu{
                position:absolute;
                top:calc(100% + 10px);
                left:0;
                z-index:20;
                display:none;
                width:min(90vw, var(--oneplugin-submenu-width));
                padding:10px;
                background:var(--oneplugin-submenu-bg);
                border:1px solid var(--oneplugin-border-color);
                border-radius:var(--oneplugin-submenu-radius);
                box-shadow:0 24px 60px var(--oneplugin-shadow-color);
            }
            .oneplugin-menu__submenu .oneplugin-menu__item{
                width:100%;
            }
            .oneplugin-menu__submenu .oneplugin-menu__item-inner{
                width:100%;
                justify-content:space-between;
            }
            .oneplugin-menu__submenu .oneplugin-menu__link{
                width:100%;
                min-height:0;
                padding:var(--oneplugin-submenu-padding-y) var(--oneplugin-submenu-padding-x);
                border-radius:calc(var(--oneplugin-submenu-radius) - 6px);
                color:var(--oneplugin-submenu-text);
            }
            .oneplugin-menu__submenu .oneplugin-menu__item:hover > .oneplugin-menu__item-inner > .oneplugin-menu__link,
            .oneplugin-menu__submenu .oneplugin-menu__item:focus-within > .oneplugin-menu__item-inner > .oneplugin-menu__link{
                color:var(--oneplugin-submenu-hover-text);
                background:var(--oneplugin-submenu-hover-bg);
            }
            .oneplugin-menu__submenu .oneplugin-menu__submenu-toggle{
                color:var(--oneplugin-submenu-text);
            }
            .oneplugin-menu__submenu .oneplugin-menu__submenu{
                top:0;
                left:calc(100% + 8px);
            }
            .oneplugin-menu[data-submenu-trigger="hover"]:not(.is-mobile-view) .oneplugin-menu__item:hover > .oneplugin-menu__submenu,
            .oneplugin-menu[data-submenu-trigger="hover"]:not(.is-mobile-view) .oneplugin-menu__item:focus-within > .oneplugin-menu__submenu{
                display:block;
            }
            .oneplugin-menu .oneplugin-menu__item.is-submenu-open > .oneplugin-menu__submenu{
                display:block;
            }
            .oneplugin-menu__overlay{
                display:none;
                appearance:none;
                border:0;
                padding:0;
                background:rgba(17,24,39,.25);
            }
            .oneplugin-menu__toggle{
                display:inline-flex;
                align-items:center;
                gap:12px;
                min-height:48px;
                padding:12px 16px;
                border:1px solid var(--oneplugin-border-color);
                border-radius:999px;
                background:var(--oneplugin-toggle-bg);
                color:var(--oneplugin-toggle-color);
                cursor:pointer;
                box-shadow:0 12px 28px rgba(17,24,39,.08);
            }
            .oneplugin-menu__toggle-icon{
                display:inline-flex;
                flex-direction:column;
                justify-content:center;
                gap:4px;
                width:18px;
            }
            .oneplugin-menu__toggle-icon span{
                display:block;
                width:18px;
                height:2px;
                border-radius:999px;
                background:currentColor;
                transition:transform .2s ease, opacity .2s ease;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__bar{
                display:flex;
                justify-content:flex-end;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__list{
                display:flex;
                flex-direction:column;
                align-items:stretch;
                gap:8px;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__panel{
                display:none;
            }
            .oneplugin-menu.is-mobile-view.is-open .oneplugin-menu__panel{
                display:block;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__overlay{
                position:fixed;
                inset:0;
                z-index:9997;
            }
            .oneplugin-menu.is-mobile-view.is-open .oneplugin-menu__overlay{
                display:block;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__submenu{
                position:static;
                display:none;
                width:100%;
                margin-top:6px;
                box-shadow:none;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__submenu .oneplugin-menu__submenu{
                margin-top:10px;
                margin-left:10px;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__item-inner{
                width:100%;
                justify-content:space-between;
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__link{
                width:100%;
                border-radius:calc(var(--oneplugin-submenu-radius) - 4px);
            }
            .oneplugin-menu.is-mobile-view .oneplugin-menu__panel{
                z-index:9998;
                width:min(92vw, 360px);
                padding:20px;
                background:var(--oneplugin-submenu-bg);
                border:1px solid var(--oneplugin-border-color);
                border-radius:calc(var(--oneplugin-submenu-radius) + 2px);
                box-shadow:0 30px 70px var(--oneplugin-shadow-color);
            }
            .oneplugin-menu.is-mobile-view.oneplugin-menu--mobile-dropdown .oneplugin-menu__panel{
                position:absolute;
                top:calc(100% + 12px);
                right:0;
                left:auto;
            }
            .oneplugin-menu.is-mobile-view.oneplugin-menu--mobile-offcanvas .oneplugin-menu__panel{
                position:fixed;
                top:16px;
                bottom:16px;
                overflow:auto;
            }
            .oneplugin-menu.is-mobile-view.oneplugin-menu--mobile-side-right.oneplugin-menu--mobile-offcanvas .oneplugin-menu__panel{
                right:16px;
            }
            .oneplugin-menu.is-mobile-view.oneplugin-menu--mobile-side-left.oneplugin-menu--mobile-offcanvas .oneplugin-menu__panel{
                left:16px;
            }
            .oneplugin-menu--empty{
                padding:16px 18px;
                border:1px dashed var(--oneplugin-border-color);
                border-radius:16px;
                color:#6b7280;
                background:#f9fafb;
            }
            @media (prefers-reduced-motion: reduce){
                .oneplugin-menu__link,
                .oneplugin-menu__submenu-toggle,
                .oneplugin-menu__toggle,
                .oneplugin-menu__toggle-icon span,
                .oneplugin-menu__link::after{
                    transition:none !important;
                }
            }
        ';

        $script = '
            (function() {
                var selectors = {
                    nav: "[data-oneplugin-menu]",
                    toggle: ".oneplugin-menu__toggle",
                    overlay: ".oneplugin-menu__overlay",
                    submenuToggle: ".oneplugin-menu__submenu-toggle",
                    itemsWithChildren: ".menu-item-has-children"
                };

                var findDirectToggle = function(item) {
                    var inner = item ? item.querySelector(".oneplugin-menu__item-inner") : null;
                    if (!inner) {
                        return null;
                    }

                    for (var i = 0; i < inner.children.length; i++) {
                        if (inner.children[i].classList && inner.children[i].classList.contains("oneplugin-menu__submenu-toggle")) {
                            return inner.children[i];
                        }
                    }

                    return null;
                };

                var closeNested = function(root, exceptItem) {
                    root.querySelectorAll(".oneplugin-menu__item.is-submenu-open").forEach(function(item) {
                        if (exceptItem && (item === exceptItem || item.contains(exceptItem))) {
                            return;
                        }
                        item.classList.remove("is-submenu-open");
                        var button = findDirectToggle(item);
                        if (button) {
                            button.setAttribute("aria-expanded", "false");
                        }
                    });
                };

                var closeMenu = function(menu) {
                    menu.classList.remove("is-open");
                    closeNested(menu);
                    var toggle = menu.querySelector(selectors.toggle);
                    if (toggle) {
                        toggle.setAttribute("aria-expanded", "false");
                    }
                };

                var openMenu = function(menu) {
                    menu.classList.add("is-open");
                    var toggle = menu.querySelector(selectors.toggle);
                    if (toggle) {
                        toggle.setAttribute("aria-expanded", "true");
                    }
                };

                var syncMode = function(menu) {
                    var breakpoint = parseInt(menu.getAttribute("data-mobile-breakpoint") || "980", 10);
                    if (!breakpoint || breakpoint < 320) {
                        breakpoint = 980;
                    }

                    if (window.innerWidth <= breakpoint) {
                        menu.classList.add("is-mobile-view");
                        return;
                    }

                    menu.classList.remove("is-mobile-view");
                    menu.classList.remove("is-open");
                    closeNested(menu);
                    var toggle = menu.querySelector(selectors.toggle);
                    if (toggle) {
                        toggle.setAttribute("aria-expanded", "false");
                    }
                };

                var setupMenu = function(menu) {
                    if (menu.__onepluginReady) {
                        syncMode(menu);
                        return;
                    }

                    menu.__onepluginReady = true;

                    menu.querySelectorAll(selectors.itemsWithChildren).forEach(function(item) {
                        var button = findDirectToggle(item);
                        if (button) {
                            button.setAttribute("aria-expanded", "false");
                        }
                    });

                    var toggle = menu.querySelector(selectors.toggle);
                    if (toggle) {
                        toggle.addEventListener("click", function() {
                            if (menu.classList.contains("is-open")) {
                                closeMenu(menu);
                                return;
                            }
                            openMenu(menu);
                        });
                    }

                    var overlay = menu.querySelector(selectors.overlay);
                    if (overlay) {
                        overlay.addEventListener("click", function() {
                            closeMenu(menu);
                        });
                    }

                    menu.addEventListener("click", function(event) {
                        var button = event.target.closest(selectors.submenuToggle);
                        if (!button || !menu.contains(button)) {
                            return;
                        }

                        var item = button.closest(".oneplugin-menu__item");
                        if (!item) {
                            return;
                        }

                        event.preventDefault();
                        var expanded = button.getAttribute("aria-expanded") === "true";
                        var allowMultiple = item.closest(".oneplugin-menu__submenu") !== null;
                        if (!allowMultiple) {
                            closeNested(menu, item);
                        }

                        item.classList.toggle("is-submenu-open", !expanded);
                        button.setAttribute("aria-expanded", expanded ? "false" : "true");
                    });

                    document.addEventListener("click", function(event) {
                        if (menu.getAttribute("data-close-outside") === "off") {
                            return;
                        }

                        if (menu.contains(event.target)) {
                            return;
                        }

                        closeMenu(menu);
                    });

                    document.addEventListener("keydown", function(event) {
                        if (event.key === "Escape") {
                            closeMenu(menu);
                        }
                    });

                    syncMode(menu);
                };

                var boot = function() {
                    document.querySelectorAll(selectors.nav).forEach(setupMenu);
                };

                document.addEventListener("DOMContentLoaded", boot);
                window.addEventListener("load", boot);
                window.addEventListener("resize", function() {
                    document.querySelectorAll(selectors.nav).forEach(syncMode);
                });
                if (window.MutationObserver) {
                    var observer = new MutationObserver(function() {
                        boot();
                    });
                    observer.observe(document.documentElement, { childList: true, subtree: true });
                }
            })();
        ';

        wp_register_style('oneplugin2-menu-inline', false, [], self::VERSION);
        wp_enqueue_style('oneplugin2-menu-inline');
        wp_add_inline_style('oneplugin2-menu-inline', $css);

        wp_register_script('oneplugin2-menu-inline', false, [], self::VERSION, true);
        wp_enqueue_script('oneplugin2-menu-inline');
        wp_add_inline_script('oneplugin2-menu-inline', $script);

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
