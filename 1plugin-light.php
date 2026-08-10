<?php
/**
 * Plugin Name: 1Plugin
 * Description: Site tools plugin with company data, shortcodes, Divi 5 modules, sticky mobile footer, page keyword fields, and custom code tools.
 * Version: 2.9.79
 * Author: Cristian
 * Text Domain: oneplugin-light-site-tools
 * Update URI: https://github.com/crim13/1Plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ONEPLUGIN_LIGHT_VERSION', '2.9.79');
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

function oneplugin_light_is_divi5_module_enabled($module_key) {
    $defaults = [
        'menu' => '1',
        'faq' => '0',
    ];

    $module_key = sanitize_key($module_key);
    if (!array_key_exists($module_key, $defaults)) {
        return false;
    }

    $settings = get_option('oneplugin_light_site_tools_settings', []);
    if (!is_array($settings) || empty($settings)) {
        $settings = get_option('oneplugin_light_site_tools_settings_legacy', []);
    }

    if ($module_key === 'faq') {
        $faq_extension = is_array($settings) && array_key_exists('extension_faq_enabled', $settings) ? $settings['extension_faq_enabled'] : '0';
        if (!($faq_extension === '1' || $faq_extension === 1 || $faq_extension === true)) {
            return false;
        }
    }

    $setting_key = 'module_' . $module_key . '_enabled';
    $value = is_array($settings) && array_key_exists($setting_key, $settings) ? $settings[$setting_key] : $defaults[$module_key];

    return $value === '1' || $value === 1 || $value === true;
}

function oneplugin_light_is_extension_enabled($extension_key) {
    $defaults = [
        'faq' => '0',
    ];

    $extension_key = sanitize_key($extension_key);
    if (!array_key_exists($extension_key, $defaults)) {
        return false;
    }

    $settings = get_option('oneplugin_light_site_tools_settings', []);
    if (!is_array($settings) || empty($settings)) {
        $settings = get_option('oneplugin_light_site_tools_settings_legacy', []);
    }

    $setting_key = 'extension_' . $extension_key . '_enabled';
    $value = is_array($settings) && array_key_exists($setting_key, $settings) ? $settings[$setting_key] : $defaults[$extension_key];

    return $value === '1' || $value === 1 || $value === true;
}

require_once __DIR__ . '/includes/class-oneplugin-light-menu-module.php';
require_once __DIR__ . '/includes/class-oneplugin-light-divi-shortcode-support.php';
require_once __DIR__ . '/includes/class-oneplugin-light-divi-compatibility.php';
require_once __DIR__ . '/includes/class-oneplugin-light-keyword-meta.php';
require_once __DIR__ . '/includes/class-oneplugin-light-shortcodes.php';
require_once __DIR__ . '/includes/class-oneplugin-light-faq.php';
require_once __DIR__ . '/includes/class-oneplugin-light-site-tools.php';
require_once __DIR__ . '/includes/class-oneplugin-light-github-updater.php';
$oneplugin_light_divi5_server = __DIR__ . '/modules/menu/server/index.php';
if (oneplugin_light_is_divi5_module_enabled('menu') && file_exists($oneplugin_light_divi5_server)) {
    require_once $oneplugin_light_divi5_server;
}

$oneplugin_light_divi5_faq_server = __DIR__ . '/modules/faq/server/index.php';
if (oneplugin_light_is_divi5_module_enabled('faq') && file_exists($oneplugin_light_divi5_faq_server)) {
    require_once $oneplugin_light_divi5_faq_server;
}

function oneplugin_light_can_enqueue_divi5_visual_builder_assets($module_key) {
    if (
        !oneplugin_light_is_divi5_module_enabled($module_key) ||
        !function_exists('et_core_is_fb_enabled') ||
        !function_exists('et_builder_d5_enabled') ||
        !et_core_is_fb_enabled() ||
        !et_builder_d5_enabled() ||
        !class_exists('\ET\Builder\VisualBuilder\Assets\PackageBuildManager')
    ) {
        return false;
    }

    return true;
}

function oneplugin_light_enqueue_divi5_menu_visual_builder_assets() {
    if (!oneplugin_light_can_enqueue_divi5_visual_builder_assets('menu')) {
        return;
    }

    $script_path = ONEPLUGIN_LIGHT_PATH . 'modules/menu/visual-builder/build/oneplugin-divi5-menu.js';
    if (!file_exists($script_path)) {
        return;
    }

    $script_version = (string) filemtime($script_path);

    \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
        'name' => 'oneplugin-light-divi5-menu-visual-builder',
        'version' => $script_version,
        'script' => [
            'src' => ONEPLUGIN_LIGHT_URL . 'modules/menu/visual-builder/build/oneplugin-divi5-menu.js',
            'deps' => [
                'react',
                'jquery',
                'divi-module-library',
                'wp-hooks',
                'divi-rest',
            ],
            'enqueue_top_window' => false,
            'enqueue_app_window' => true,
        ],
    ]);
}

function oneplugin_light_enqueue_divi5_faq_visual_builder_assets() {
    if (!oneplugin_light_can_enqueue_divi5_visual_builder_assets('faq')) {
        return;
    }

    $script_path = ONEPLUGIN_LIGHT_PATH . 'modules/faq/visual-builder/build/oneplugin-divi5-faq.js';
    if (!file_exists($script_path)) {
        return;
    }

    $script_version = (string) filemtime($script_path);

    \ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build([
        'name' => 'oneplugin-light-divi5-faq-visual-builder',
        'version' => $script_version,
        'script' => [
            'src' => ONEPLUGIN_LIGHT_URL . 'modules/faq/visual-builder/build/oneplugin-divi5-faq.js',
            'deps' => [
                'react',
                'jquery',
                'divi-module-library',
                'wp-hooks',
                'divi-rest',
            ],
            'enqueue_top_window' => false,
            'enqueue_app_window' => true,
        ],
    ]);
}

add_action('divi_visual_builder_assets_before_enqueue_scripts', 'oneplugin_light_enqueue_divi5_menu_visual_builder_assets');
add_action('divi_visual_builder_assets_before_enqueue_scripts', 'oneplugin_light_enqueue_divi5_faq_visual_builder_assets');

register_activation_hook(__FILE__, ['OnePlugin_Light_Site_Tools', 'activate']);
register_deactivation_hook(__FILE__, ['OnePlugin_Light_Site_Tools', 'deactivate']);

OnePlugin_Light_GitHub_Updater::instance(__FILE__);
if (oneplugin_light_is_extension_enabled('faq')) {
    OnePlugin_Light_FAQ::instance()->init();
}
OnePlugin_Light_Site_Tools::instance();
