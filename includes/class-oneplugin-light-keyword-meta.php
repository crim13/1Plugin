<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Keyword_Meta {
    private static $instance = null;

    private $fields = [
        'sokordets_tjanst_rubrik' => 'Sokordets Tjanst (Rubrik) [sokordets_tjanst_rubrik]',
        'sokordets_ort_rubrik' => 'Sokordets Ort (Rubrik) [sokordets_ort_rubrik]',
        'sokordets_tjanst_brodtext' => 'Sokordets Tjanst (Brodtext) [sokordets_tjanst_brodtext]',
        'sokordets_ort_brodtext' => 'Sokordets Ort (Brodtext) [sokordets_ort_brodtext]',
    ];

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() {
        add_action('add_meta_boxes', [$this, 'register_meta_box']);
        add_action('save_post', [$this, 'save_meta_box']);
        add_action('init', [$this, 'register_shortcodes']);
    }

    public function register_shortcodes() {
        add_shortcode('sokordets_tjanst_rubrik', [$this, 'shortcode_service_title']);
        add_shortcode('sokordets_ort_rubrik', [$this, 'shortcode_city_title']);
        add_shortcode('sokordets_tjanst_brodtext', [$this, 'shortcode_service_text']);
        add_shortcode('sokordets_ort_brodtext', [$this, 'shortcode_city_text']);
    }

    public function register_meta_box() {
        add_meta_box(
            'oneplugin_keyword_meta_box',
            __('Sokordens falt', 'oneplugin-light-site-tools'),
            [$this, 'render_meta_box'],
            'page',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('oneplugin_keyword_meta_box', 'oneplugin_keyword_meta_box_nonce');

        foreach ($this->fields as $meta_key => $label) {
            $value = get_post_meta($post->ID, $meta_key, true);
            ?>
            <p>
                <label for="<?php echo esc_attr($meta_key); ?>"><?php echo esc_html($label); ?></label><br />
                <input type="text" class="widefat" name="<?php echo esc_attr($meta_key); ?>" id="<?php echo esc_attr($meta_key); ?>" value="<?php echo esc_attr($value); ?>" />
            </p>
            <?php
        }
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['oneplugin_keyword_meta_box_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oneplugin_keyword_meta_box_nonce'])), 'oneplugin_keyword_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        foreach (array_keys($this->fields) as $field) {
            $value = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
            update_post_meta($post_id, $field, $value);
        }
    }

    public function shortcode_service_title() {
        return $this->render_field_shortcode('sokordets_tjanst_rubrik');
    }

    public function shortcode_city_title() {
        return $this->render_field_shortcode('sokordets_ort_rubrik');
    }

    public function shortcode_service_text() {
        return $this->render_field_shortcode('sokordets_tjanst_brodtext');
    }

    public function shortcode_city_text() {
        return $this->render_field_shortcode('sokordets_ort_brodtext');
    }

    private function render_field_shortcode($field) {
        return esc_html((string) get_post_meta(get_the_ID(), $field, true));
    }
}
