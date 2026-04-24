<?php

if (!defined('ABSPATH')) {
    exit;
}

final class OnePlugin_Light_GitHub_Updater {
    const TRANSIENT_KEY = 'oneplugin_light_github_release';
    const CACHE_TTL = 6 * HOUR_IN_SECONDS;

    private static $instance = null;
    private $plugin_file;
    private $plugin_basename;
    private $slug;

    public static function instance($plugin_file = null) {
        if (self::$instance === null && $plugin_file !== null) {
            self::$instance = new self($plugin_file);
        }

        return self::$instance;
    }

    private function __construct($plugin_file) {
        $this->plugin_file = $plugin_file;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->slug = dirname($this->plugin_basename);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'filter_update_plugins_transient']);
        add_filter('plugins_api', [$this, 'filter_plugins_api'], 20, 3);
        add_filter('auto_update_plugin', [$this, 'filter_auto_update_plugin'], 10, 2);
        add_filter('http_request_args', [$this, 'add_github_auth_header'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'clear_release_cache'], 10, 2);
        add_action('admin_init', [$this, 'clear_cache_on_forced_update_check']);
    }

    public function filter_update_plugins_transient($transient) {
        if (!is_object($transient)) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if (!$release || empty($release['version']) || empty($release['package'])) {
            return $transient;
        }

        $payload = $this->build_update_payload($release);

        if (!version_compare($release['version'], ONEPLUGIN_LIGHT_VERSION, '>')) {
            unset($transient->response[$this->plugin_basename]);
            $transient->no_update[$this->plugin_basename] = $payload;
            return $transient;
        }

        $transient->response[$this->plugin_basename] = $payload;
        unset($transient->no_update[$this->plugin_basename]);

        return $transient;
    }

    public function filter_auto_update_plugin($update, $item) {
        if (!is_object($item) || empty($item->plugin) || $item->plugin !== $this->plugin_basename) {
            return $update;
        }

        return true;
    }

    public function filter_plugins_api($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $release = $this->get_latest_release();
        if (!$release) {
            return $result;
        }

        return (object) [
            'name' => '1Plugin Light',
            'slug' => $this->slug,
            'version' => isset($release['version']) ? $release['version'] : ONEPLUGIN_LIGHT_VERSION,
            'author' => '<a href="https://github.com/' . esc_attr($this->get_owner()) . '">Cristian</a>',
            'homepage' => isset($release['html_url']) ? $release['html_url'] : '',
            'download_link' => isset($release['package']) ? $release['package'] : '',
            'requires' => isset($release['requires']) ? $release['requires'] : '',
            'tested' => isset($release['tested']) ? $release['tested'] : '',
            'requires_php' => isset($release['requires_php']) ? $release['requires_php'] : '',
            'last_updated' => isset($release['published_at']) ? $release['published_at'] : '',
            'sections' => [
                'description' => 'Lightweight site tools plugin with company data, shortcodes, sticky mobile footer, page keyword fields, and custom code tools.',
                'changelog' => !empty($release['body']) ? wp_kses_post(wpautop($release['body'])) : 'See the latest GitHub release for changes.',
            ],
        ];
    }

    public function add_github_auth_header($args, $url) {
        $token = $this->get_token();
        if ($token === '' || strpos($url, 'github.com') === false) {
            return $args;
        }

        if (empty($args['headers']) || !is_array($args['headers'])) {
            $args['headers'] = [];
        }

        $args['headers']['Authorization'] = 'Bearer ' . $token;
        $args['headers']['X-GitHub-Api-Version'] = '2022-11-28';

        return $args;
    }

    public function clear_release_cache($upgrader, $hook_extra) {
        if (empty($hook_extra['plugins']) || !is_array($hook_extra['plugins'])) {
            return;
        }

        if (in_array($this->plugin_basename, $hook_extra['plugins'], true)) {
            delete_site_transient(self::TRANSIENT_KEY);
        }
    }

    public function clear_cache_on_forced_update_check() {
        if (!is_admin() || !current_user_can('update_plugins')) {
            return;
        }

        if (!isset($_GET['force-check']) || !isset($_GET['_wpnonce'])) {
            return;
        }

        delete_site_transient(self::TRANSIENT_KEY);
    }

    private function get_latest_release() {
        if (!$this->is_configured()) {
            return null;
        }

        $cached = get_site_transient(self::TRANSIENT_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/releases/latest',
            rawurlencode($this->get_owner()),
            rawurlencode($this->get_repo())
        );

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github+json',
                'User-Agent' => '1Plugin-Light-Updater/' . ONEPLUGIN_LIGHT_VERSION,
            ],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['tag_name'])) {
            return null;
        }

        $release = $this->normalize_release_data($data);
        if ($release) {
            set_site_transient(self::TRANSIENT_KEY, $release, self::CACHE_TTL);
        }

        return $release;
    }

    private function normalize_release_data($data) {
        $version = ltrim((string) $data['tag_name'], 'vV');
        $asset_url = $this->find_package_asset_url(isset($data['assets']) && is_array($data['assets']) ? $data['assets'] : []);

        if ($version === '' || $asset_url === '') {
            return null;
        }

        return [
            'version' => $version,
            'package' => $asset_url,
            'html_url' => isset($data['html_url']) ? esc_url_raw($data['html_url']) : '',
            'body' => isset($data['body']) ? (string) $data['body'] : '',
            'published_at' => isset($data['published_at']) ? (string) $data['published_at'] : '',
            'requires' => '',
            'tested' => '',
            'requires_php' => '',
        ];
    }

    private function build_update_payload($release) {
        return (object) [
            'id' => $this->plugin_basename,
            'slug' => $this->slug,
            'plugin' => $this->plugin_basename,
            'new_version' => $release['version'],
            'url' => $release['html_url'],
            'package' => $release['package'],
            'tested' => isset($release['tested']) ? $release['tested'] : '',
            'requires' => isset($release['requires']) ? $release['requires'] : '',
            'requires_php' => isset($release['requires_php']) ? $release['requires_php'] : '',
        ];
    }

    private function find_package_asset_url($assets) {
        $asset_name = $this->get_asset_name();

        foreach ($assets as $asset) {
            if (!is_array($asset) || empty($asset['name']) || empty($asset['browser_download_url'])) {
                continue;
            }

            if ((string) $asset['name'] === $asset_name) {
                return esc_url_raw($asset['browser_download_url']);
            }
        }

        return '';
    }

    private function is_configured() {
        return $this->get_owner() !== '' && $this->get_repo() !== '';
    }

    private function get_owner() {
        $owner = defined('ONEPLUGIN_LIGHT_GITHUB_OWNER') ? ONEPLUGIN_LIGHT_GITHUB_OWNER : '';
        return sanitize_text_field((string) apply_filters('oneplugin_light_github_owner', $owner));
    }

    private function get_repo() {
        $repo = defined('ONEPLUGIN_LIGHT_GITHUB_REPO') ? ONEPLUGIN_LIGHT_GITHUB_REPO : '';
        return sanitize_text_field((string) apply_filters('oneplugin_light_github_repo', $repo));
    }

    private function get_asset_name() {
        $asset = defined('ONEPLUGIN_LIGHT_GITHUB_ASSET') ? ONEPLUGIN_LIGHT_GITHUB_ASSET : '1plugin-light.zip';
        return sanitize_file_name((string) apply_filters('oneplugin_light_github_asset', $asset));
    }

    private function get_token() {
        $token = defined('ONEPLUGIN_LIGHT_GITHUB_TOKEN') ? ONEPLUGIN_LIGHT_GITHUB_TOKEN : '';
        return trim((string) apply_filters('oneplugin_light_github_token', $token));
    }
}
