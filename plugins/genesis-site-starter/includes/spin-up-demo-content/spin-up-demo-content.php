<?php
/**
 * Set up the getting started page.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\SpinUpDemoContent;

/**
 * Spin up the GSS demo content.
 *
 * @since 0.4.0
 */
function spin_up_demo_content(): void {

	// Only spin up demo content if if "?gss_spin_up_demo_content=1" is in the URL.
	if ( filter_input( INPUT_GET, 'gss_spin_up_demo_content', FILTER_SANITIZE_STRING ) !== '1' ) {
		// Exit early.
		return;
	}

	$should_initiate_demo_content = should_initiate_demo_content();

	if ( isset( $should_initiate_demo_content['error'] ) ) {
		// Output a JSON object indicating the reason for failure.
		header( 'Content-Type: application/json' );
		echo wp_json_encode(
			array(
				'error' => $should_initiate_demo_content['error'],
			)
		);
		die();
	}

	// Add a flag so we know not to do this again (like if the user refreshes this page, for example).
	update_option( 'gss_demo_content_already_initiated', true );

	// Run the gss_spin_up_demo_content functions.
	do_action( 'gss_spin_up_demo_content' );

	header( 'Content-Type: application/json' );
	echo wp_json_encode(
		array(
			'success' => __( 'Demo content successfully created', 'genesis-site-starter' ),
		)
	);
	die();
}
add_action( 'admin_init', __NAMESPACE__ . '\spin_up_demo_content' );

/**
 * Check whether demo content should be initiated.
 *
 * @since 0.6.0
 * @return array
 */
function should_initiate_demo_content(): array {

	// Only spin up demo content if if "?gss_spin_up_demo_content=1" is in the URL.
	if ( filter_input( INPUT_GET, 'gss_spin_up_demo_content', FILTER_SANITIZE_STRING ) !== '1' ) {
		return array( 'error' => 'gss_spin_up_demo_content-was-not-in-the-url' );
	}

	// Check if we got a nonce in the GET data.
	$nonce = filter_input( INPUT_GET, 'gss_spin_up_demo_content_nonce', FILTER_SANITIZE_STRING );

	if ( ! wp_verify_nonce( $nonce, 'gss_spin_up_demo_content_nonce' ) ) {
		return array( 'error' => 'nonce-failure' );
	}

	// Check if gss_force_spin_up_demo_content=1 is in the GET data. This can be used over and over to regenerate demo content at any time.
	$force_regenerate_demo_content = filter_input( INPUT_GET, 'gss_force_spin_up_demo_content', FILTER_SANITIZE_STRING ) === '1' ? true : false;

	// If force is in the URL, regenerate it no matter what has already happened.
	if ( $force_regenerate_demo_content ) {
		return array( 'success' => 'gss_force_spin_up_demo_content' );
	}

	// No force? Okay, then check if the demo content was already initiated.
	if ( get_option( 'gss_demo_content_already_initiated' ) ) {
		// Do not re-initiate demo content. For example, if the page is refreshed, or a new admin logs in 2 years later for the first time (and gets redirected to Getting Started).
		return array( 'error' => 'demo-content-was-already-initiated' );
	}

	return array( 'success' => 'demo-content-generating-for-first-time' );
}
