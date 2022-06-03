<?php
/**
 * Set up the default customizer values.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\DefaultCustomizer;

/**
 * Create a new page.
 *
 * Runs on plugin install
 *
 * @since 1.0
 *
 * @param mixed $context Plugin context.
 */
function set_customizer_defaults( $context = null ) {
	$theme = get_option( 'stylesheet' );

	if ( 'genesis-block-theme' !== $theme ) {
		return;
	}

	// Theme mods (aka customizer settings) pulled from the demo: https://developer.wpengine.com/ecom/wp-admin/?get_theme_mods.
	$mods = json_decode( '{"0":false,"nav_menu_locations":[],"custom_css_post_id":-1}', true );
	return update_option( "theme_mods_$theme", $mods );
}
add_action( 'gss_spin_up_demo_content', __NAMESPACE__ . '\set_customizer_defaults' );
