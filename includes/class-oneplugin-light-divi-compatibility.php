<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_Divi_Compatibility {
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init() {
        add_action('init', [$this, 'remove_test_cookies']);
        add_filter('the_posts', [$this, 'filter_global_modules']);
    }

    public function remove_test_cookies() {
        if ($this->is_async_editor_request() || headers_sent()) {
            return;
        }

        $cookies = [
            'et_pb_ab_read_page_1521false',
            'et_pb_ab_view_page_244384',
            'et_pb_ab_read_page_244384false',
        ];

        foreach ($cookies as $cookie_name) {
            if (isset($_COOKIE[$cookie_name])) {
                unset($_COOKIE[$cookie_name]);
                setcookie($cookie_name, '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/');
            }
        }
    }

    public function filter_global_modules($posts) {
        if (!$this->is_frontend() || empty($posts) || count($posts) !== 1 || empty($posts[0]->post_type) || empty($posts[0]->post_content)) {
            return $posts;
        }

        if ($posts[0]->post_type === 'et_pb_layout') {
            $posts[0]->post_content = apply_filters('dbdse_et_pb_layout_content', $posts[0]->post_content);
        }

        return $posts;
    }

    private function is_frontend() {
        return !is_admin() && !$this->is_async_editor_request();
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
