<?php
/**
 * Set up the admin bar menu links.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\AdminBarLink;

/**
 * Add the admin bar menu links.
 *
 * Runs admin_bar_menu.
 *
 * @since 1.0
 *
 * @param mixed $admin_bar Admin bar parameters.
 */
function set_admin_bar( $admin_bar ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$admin_bar->add_menu(
		array(
			'id'    => 'gss-help-link',
			'title' => '<span class="ab-icon dashicons dashicons-info-outline"></span>' . __( 'Genesis Site Starter Help', 'genesis-site-starter' ),
			'href'  => admin_url( 'admin.php?page=genesis-blocks-getting-started&tab=genesis-getting-started' ),
			'meta'  => array(
				'title' => __( 'Genesis Site Starter Help', 'genesis-site-starter' ),
			),
		)
	);
	$admin_bar->add_menu(
		array(
			'id'     => 'gss-getting-started',
			'parent' => 'gss-help-link',
			'title'  => __( 'Getting Started', 'genesis-site-starter' ),
			'href'   => admin_url( 'admin.php?page=genesis-blocks-getting-started&tab=genesis-getting-started' ),
			'meta'   => array(
				'title' => __( 'Getting Started', 'genesis-site-starter' ),
			),
		)
	);
	$admin_bar->add_menu(
		array(
			'id'     => 'gss-view-website',
			'parent' => 'gss-help-link',
			'title'  => __( 'View Your Website', 'genesis-site-starter' ),
			'href'   => home_url( '/' ),
			'meta'   => array(
				'title' => __( 'View Your Website', 'genesis-site-starter' ),
			),
		)
	);
}

add_action( 'admin_bar_menu', __NAMESPACE__ . '\set_admin_bar', 500 );
