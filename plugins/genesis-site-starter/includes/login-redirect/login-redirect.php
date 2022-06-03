<?php
/**
 * Redirects the admin user to the Getting Started Page upon their first login.
 *
 * @package Genesis_Site_Starter
 */

namespace GenesisSiteStarter\LoginRedirect;

/**
 * Conditionally gets the URL to the 'Getting Started' page.
 *
 * If a user has never been redirected, this returns that URL.
 * Otherwise, it returns the URL it's passed.
 *
 * @since 0.0.1
 */
function maybe_redirect_to_getting_started() {

	// Don't do this if it's WP_CLI. Wait for a real human.
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	// We only want to show the Getting Started page to site administrators.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$current_user_id = get_current_user_id();

	// Don't do this if the SSO (single sign on) nonce exists. This comes from the WPE Single Sign on MU plugin:
	// https://github.com/wpengine/wpe-wp-sign-on-plugin.
	$wpesso_nonce = get_user_meta( $current_user_id, 'WPE_NONCE', true );

	if ( ! empty( $wpesso_nonce ) ) {
		return;
	}

	if ( ! is_admin() ) {
		return;
	}

	$gss_user_has_redirected_to_gs = 'gss_user_has_redirected_to_gs';

	// Check the user meta for this user to see if they have ever been redirected to the getting started page before.
	if ( get_user_meta( $current_user_id, $gss_user_has_redirected_to_gs, true ) ) {
		// If they have been redirected before, don't redirect them again.
		return;
	}

	// This user has never been redirected to the Getting Started Page. Set the user meta so we know they have been redirected.
	update_user_meta( $current_user_id, $gss_user_has_redirected_to_gs, true );

	// Generate a nonce for the action of spinning up demo content.
	$nonce = wp_create_nonce( 'gss_spin_up_demo_content_nonce' );

	// Redirect the user to the Getting Started Page, with the gss-initiate-demo-content action in the URL, and corresponding nonce.
	if ( wp_safe_redirect(
		add_query_arg(
			array(
				'page'                           => 'genesis-blocks-getting-started',
				'tab'                            => 'genesis-getting-started',
				'gss-initiate-demo-content'      => true,
				'gss-spin-up-demo-content-nonce' => $nonce,
			),
			admin_url( 'admin.php' )
		)
	) ) {
		// If the redirect was successfull, exit this page.
		exit;
	}
}
add_filter( 'current_screen', __NAMESPACE__ . '\maybe_redirect_to_getting_started' );
