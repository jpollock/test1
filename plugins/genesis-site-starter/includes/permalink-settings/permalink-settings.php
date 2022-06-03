<?php
/**
 * This changes the permalink settings to 'Post name'.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\PermalinkSettings;

/**
 * Changes the permalink settings to 'Post name' and flushes the rules.
 *
 * Runs on content import.
 *
 * @since 1.0
 */
function set_permalink_settings() {
	$GLOBALS['wp_rewrite']->set_permalink_structure( '/%postname%/' );
	$GLOBALS['wp_rewrite']->flush_rules();
}

add_action( 'gss_spin_up_demo_content', __NAMESPACE__ . '\set_permalink_settings' );
