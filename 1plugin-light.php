<?php
/**
 * Plugin Name: 1Plugin Light
 * Description: Lightweight site tools plugin with company data, shortcodes, sticky mobile footer, page keyword fields, and custom code tools.
 * Version: 2.5.6
 * Author: Cristian
 * Text Domain: oneplugin-light-site-tools
 * Update URI: https://github.com/crim13/1Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ONEPLUGIN_LIGHT_VERSION', '2.5.6');
define('ONEPLUGIN_LIGHT_PATH', plugin_dir_path(__FILE__));
define('ONEPLUGIN_LIGHT_URL', plugin_dir_url(__FILE__));
if (!defined('ONEPLUGIN_LIGHT_GITHUB_OWNER')) {
    define('ONEPLUGIN_LIGHT_GITHUB_OWNER', 'crim13');
}

if (!defined('ONEPLUGIN_LIGHT_GITHUB_REPO')) {
    define('ONEPLUGIN_LIGHT_GITHUB_REPO', '1Plugin');
}

if (!defined('ONEPLUGIN_LIGHT_GITHUB_ASSET')) {
    define('ONEPLUGIN_LIGHT_GITHUB_ASSET', '1plugin-light.zip');
}

require_once __DIR__ . '/includes/class-oneplugin-light-menu-module.php';
require_once __DIR__ . '/includes/class-oneplugin-light-site-tools.php';
require_once __DIR__ . '/includes/class-oneplugin-light-github-updater.php';

register_activation_hook(__FILE__, ['OnePlugin_Light_Site_Tools', 'activate']);

OnePlugin_Light_GitHub_Updater::instance(__FILE__);
OnePlugin_Light_Site_Tools::instance();
