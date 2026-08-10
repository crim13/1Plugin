<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Divi_Shortcode_Support {
    public static function supported_fields() {
        return apply_filters(
            'dbdsp_fields_to_process',
            [
                'et_pb_accordion_item' => ['title'],
                'et_pb_blurb' => ['title', 'url', 'image', 'alt'],
                'et_pb_button' => ['button_url', 'button_text'],
                'et_pb_circle_counter' => ['title', 'number'],
                'et_pb_cta' => ['title', 'button_text', 'button_url'],
                'et_pb_image' => ['url', 'src', 'title_text', 'alt'],
                'et_pb_number_counter' => ['title', 'number'],
                'et_pb_counter' => ['percent'],
                'et_pb_pricing_table' => ['title', 'subtitle', 'currency', 'sum', 'button_text', 'button_url'],
                'et_pb_tab' => ['title'],
                'et_pb_toggle' => ['title'],
                'et_pb_slide' => ['heading', 'button_text', 'button_link', 'image_alt', 'title_text'],
                'db_pb_slide' => ['button_text_2', 'button_link_2'],
                'et_pb_fullwidth_header' => ['title', 'subhead', 'button_one_text', 'button_two_text', 'button_one_url', 'button_two_url'],
                'et_pb_fullwidth_image' => ['src', 'title_text', 'alt'],
                'et_pb_contact_field' => ['field_title'],
                'dipi_dual_heading' => ['first_heading', 'second_heading'],
                'dipi_text_highlighter' => ['text_highlighter_prefix', 'text_highlighter_text', 'text_highlighter_suffix'],
            ]
        );
    }

    public function init() {
        add_filter('the_content', [$this, 'process_shortcodes']);
        add_filter('et_builder_render_layout', [$this, 'process_shortcodes']);
        add_filter('dbdse_et_pb_layout_content', [$this, 'process_shortcodes']);
        add_filter('et_pb_module_shortcode_attributes', [$this, 'prevent_shortcode_encoding_in_module_settings'], 11, 3);
    }

    public function process_shortcodes($content) {
        if (!is_string($content) || $this->is_async_editor_request()) {
            return $content;
        }

        if ($content === '' || strpos($content, '[') === false || strpos($content, '="') === false) {
            return $content;
        }

        $supported_fields = (array) self::supported_fields();
        if (!$this->content_has_supported_module($content, $supported_fields)) {
            return $content;
        }

        do_action('dbdsp_pre_shortcode_processing');

        foreach ($supported_fields as $module => $fields) {
            foreach ($fields as $field) {
                $regex = '#\[' . preg_quote($module, '#') . '\s+[^]]*?\b' . preg_quote($field, '#') . '="([^"]+)"#';
                $content = preg_replace_callback($regex, [$this, 'process_matched_attribute'], $content);
            }
        }

        do_action('dbdsp_post_shortcode_processing');

        return $content;
    }

    private function content_has_supported_module($content, $supported_fields) {
        foreach (array_keys($supported_fields) as $module) {
            if (is_string($module) && $module !== '' && strpos($content, '[' . $module) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function process_matched_attribute($matches) {
        if (!is_array($matches) || !isset($matches[0])) {
            return '';
        }

        if (!isset($matches[1])) {
            return $matches[0];
        }

        $encoded = ['%22', '%91', '%93'];
        $decoded = ['"', '[', ']'];

        $value = str_replace($encoded, $decoded, $matches[1]);
        $value = do_shortcode($value);
        $value = str_replace($decoded, $encoded, $value);

        return str_replace($matches[1], $value, $matches[0]);
    }

    public function prevent_shortcode_encoding_in_module_settings($props, $attrs, $render_slug) {
        if (!is_array($props)) {
            return $props;
        }

        if (!empty($_REQUEST['et_fb']) && $render_slug === 'et_pb_image' && !empty($attrs['url']) && strpos($attrs['url'], '[') !== false && strpos($attrs['url'], ']') !== false) {
            $props['url'] = $attrs['url'];
        }

        return $props;
    }

    private function is_async_editor_request() {
        if ((function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('DOING_AJAX') && DOING_AJAX)) {
            return true;
        }

        if ((defined('REST_REQUEST') && REST_REQUEST) || (function_exists('wp_is_json_request') && wp_is_json_request())) {
            return true;
        }

        if (!empty($_REQUEST['action']) && is_string($_REQUEST['action']) && strpos((string) $_REQUEST['action'], 'et_fb') !== false) {
            return true;
        }

        return false;
    }
}

