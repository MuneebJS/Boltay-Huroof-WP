<?php
/**
 * Plugin Name: WP GitHub Push
 * Description: Push and pull WordPress themes/plugins code directly to/from a GitHub repository using a Personal Access Token.
 * Version: 0.1.0
 * Author: Insynia
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

defined('ABSPATH') || exit;

define('WPGP_VERSION', '0.1.0');
define('WPGP_PLUGIN_FILE', __FILE__);
define('WPGP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPGP_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once WPGP_PLUGIN_DIR . 'includes/class-security.php';
require_once WPGP_PLUGIN_DIR . 'includes/class-settings.php';
require_once WPGP_PLUGIN_DIR . 'includes/class-api-client.php';
require_once WPGP_PLUGIN_DIR . 'includes/class-github-api.php';
require_once WPGP_PLUGIN_DIR . 'includes/class-file-scanner.php';
require_once WPGP_PLUGIN_DIR . 'includes/class-push-service.php';
require_once WPGP_PLUGIN_DIR . 'admin/class-admin-page.php';

function wpgp_activate(): void {
    WPGP_Settings::ensure_defaults();
}

register_activation_hook(WPGP_PLUGIN_FILE, 'wpgp_activate');

add_action('plugins_loaded', static function () {
    WPGP_Settings::init();
    WPGP_Push_Service::init();
    WPGP_Admin_Page::init();
});

