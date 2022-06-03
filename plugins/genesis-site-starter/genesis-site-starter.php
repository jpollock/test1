<?php
/**
 * Plugin Name: Genesis Site Starter
 * Plugin URI: https://wpengine.com/
 * Description: Genesis Site Starter plugin on WP Engine.
 * Author: WPEngine
 * Author URI: https://wpengine.com/
 * Text Domain: genesis-site-starter
 * Domain Path: /languages
 * Version: 1.1.0
 *
 * @package Genesis_Site_Starter
 */

namespace GenesisSiteStarter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GENESIS_SITE_STARTER_FILE', __FILE__ );
define( 'GENESIS_SITE_STARTER_DIR', __DIR__ );
define( 'GENESIS_SITE_STARTER_URL', plugin_dir_url( __FILE__ ) );
define( 'GENESIS_SITE_STARTER_PATH', plugin_basename( GENESIS_SITE_STARTER_FILE ) );
define( 'GENESIS_SITE_STARTER_SLUG', dirname( plugin_basename( GENESIS_SITE_STARTER_FILE ) ) );
define( 'GENESIS_SITE_STARTER_VERSION', get_plugin_version() );

/**
 * Bootstraps the plugin.
 *
 * @since 0.0.1
 */
function plugin_loader(): void {
	// Ensure dependencies exist.
	require_once __DIR__ . '/includes/admin-bar-link/admin-bar-link.php';
	require_once __DIR__ . '/includes/default-pages/default-pages.php';
	require_once __DIR__ . '/includes/login-redirect/login-redirect.php';
	require_once __DIR__ . '/includes/spin-up-demo-content/spin-up-demo-content.php';
	require_once __DIR__ . '/includes/getting-started-tab/getting-started-tab.php';
	require_once __DIR__ . '/includes/default-customizer/default-customizer.php';
	require_once __DIR__ . '/includes/permalink-settings/permalink-settings.php';
	require_once __DIR__ . '/includes/updates/updates.php';
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin_loader' );

/**
 * Bootstraps the plugin.
 *
 * @since 0.0.1
 * @return array The array of dependencies.
 */
function plugin_dependencies(): array {
	return [
		'genesis_blocks_pro' => [
			'is_active'         => function_exists( 'genesis_blocks_get_layouts' ),
			'version_required'  => '1.2.2',
			'version_installed' => get_plugin_data( get_dir_and_filename_of_active_plugin( 'genesis-blocks-pro.php' ) )['Version'],
		],
	];
}

/**
 * Get the current version of the plugin.
 *
 * @since 0.0.1
 * @return string
 */
function get_plugin_version(): string {
	$version = get_file_data( __FILE__, [ 'Version' ] )[0];

	// If SCRIPT_DEBUG is enabled, break the browser cache.
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		return $version . '-' . time();
	} else {
		return $version;
	}
}

/**
 * Helper function which allows us to get the directory name of this plugin, regardless of whether it was manually renamed by someone along the way.
 *
 * @param string $plugin_filename The main filename of the plugin in question.
 * @return string The directory and name of the plugin separated by a slash. For example: 'akismet-directory/akismet.php'
 */
function get_dir_and_filename_of_active_plugin( $plugin_filename ) {
	$plugin_dir_and_filename = false;
	$active_plugins          = get_option( 'active_plugins' );

	if ( ! is_array( $active_plugins ) ) {
		return false;
	}

	foreach ( $active_plugins as $active_plugin ) {
		if ( false !== strpos( $active_plugin, $plugin_filename ) ) {
			$plugin_dir_and_filename = $active_plugin;
			break;
		}
	}
	return $plugin_dir_and_filename;
}
