<?php
/**
 * Updates the plugin to the latest version via the product info service.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\Updates;

use stdClass;

/**
 * Gets the data required to update this plugin.
 *
 * @param object $data WordPress update object.
 * @return object $data An updated object if an update exists, default object if not.
 */
function check_for_updates( $data ) {
	if ( empty( $data ) ) {
		return $data;
	}

	$response = get_product_info();

	if ( empty( $response->requires_at_least ) || empty( $response->stable_tag ) ) {
		return $data;
	}

	$current_plugin_data = get_plugin_data( GENESIS_SITE_STARTER_FILE );
	$meets_wp_req        = version_compare( get_bloginfo( 'version' ), $response->requires_at_least, '>=' );

	// Only update the response if there's a newer version, otherwise WP shows an update notice for the same version.
	if ( $meets_wp_req && version_compare( $current_plugin_data['Version'], $response->stable_tag, '<' ) ) {
		$response->plugin = plugin_basename( GENESIS_SITE_STARTER_FILE );
		$data->response[ plugin_basename( GENESIS_SITE_STARTER_FILE ) ] = $response;
	}

	return $data;
}
add_filter( 'pre_set_site_transient_update_plugins', __NAMESPACE__ . '\check_for_updates' );

/**
 * Returns a custom API response for updating the plugin.
 *
 * @param object|array|false $api The result object or array, default false.
 * @param string             $action The type of information being requested from the Plugin Installation API.
 * @param object             $args Plugin API arguments.
 * @return false|stdClass $api Plugin API arguments.
 */
function custom_plugins_api( $api, $action, $args ) {
	unset( $action );

	if ( empty( $args->slug ) || $args->slug !== dirname( plugin_basename( GENESIS_SITE_STARTER_FILE ) ) ) {
		return $api;
	}

	$product_info = get_product_info();

	if ( empty( $product_info ) || is_wp_error( $product_info ) ) {
		return $api;
	}

	$current_plugin_data = get_plugin_data( GENESIS_SITE_STARTER_FILE );
	$meets_wp_req        = version_compare( get_bloginfo( 'version' ), $product_info->requires_at_least, '>=' );

	$api                        = new stdClass();
	$api->author                = 'Genesis Site Starter';
	$api->homepage              = 'https://studiopress.com';
	$api->name                  = $product_info->name;
	$api->requires              = isset( $product_info->requires_at_least ) ? $product_info->requires_at_least : $current_plugin_data['RequiresWP'];
	$api->sections['changelog'] = isset( $product_info->sections->changelog ) ? $product_info->sections->changelog : '<h4>1.0</h4><ul><li>Initial release.</li></ul>';
	$api->slug                  = $args->slug;

	// Only pass along the update info if the requirements are met and there's actually a newer version.
	if ( $meets_wp_req && version_compare( $current_plugin_data['Version'], $product_info->stable_tag, '<' ) ) {
		$api->version       = $product_info->stable_tag;
		$api->download_link = $product_info->download_link;
	}

	return $api;
}
add_filter( 'plugins_api', __NAMESPACE__ . '\custom_plugins_api', 10, 3 );

/**
 * Fetches and returns the plugin info from the WPE product info API.
 *
 * @return stdClass The product info data.
 */
function get_product_info() {
	$option_name_api_error       = 'genesis_site_starter_product_info_api_error';
	$transient_name_product_info = 'genesis_site_starter_product_info';
	$current_plugin_data         = get_plugin_data( GENESIS_SITE_STARTER_FILE );

	// Check for a cached response before making an API call.
	$response = get_transient( $transient_name_product_info );

	if ( false === $response ) {
		$request_args = [
			'timeout'    => defined( 'DOING_CRON' ) && DOING_CRON ? 30 : 3,
			'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			'body'       => [
				'version' => $current_plugin_data['Version'],
			],
		];

		$response = wp_remote_get( 'https://wp-product-info.wpesvc.net/v1/plugins/genesis-site-starter', $request_args );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			// Save the error code so we can use it elsewhere to display messages.
			if ( is_wp_error( $response ) ) {
				update_option( $option_name_api_error, $response->get_error_code(), false );
			} else {
				$response_body = json_decode( wp_remote_retrieve_body( $response ), false );
				$error_code    = ! empty( $response_body->error_code ) ? $response_body->error_code : 'unknown';
				update_option( $option_name_api_error, $error_code, false );
			}

			// Cache an empty object for 5 minutes to give the product info API time to recover.
			$response = new stdClass();
			set_transient( $transient_name_product_info, $response, MINUTE_IN_SECONDS * 5 );

			return $response;
		}

		// Delete any existing API error codes since we have a valid API response.
		delete_option( $option_name_api_error );

		$response                = json_decode( wp_remote_retrieve_body( $response ) );
		$current_plugin_name     = isset( $response->name ) ? $response->name : 'Genesis Site Starter';
		$response->name          = $current_plugin_name;
		$response->author        = $current_plugin_name;
		$response->stable_tag    = isset( $response->stable_tag ) ? $response->stable_tag : $current_plugin_data['Version'];
		$response->new_version   = $response->stable_tag;
		$response->download_link = isset( $response->download_link ) ? $response->download_link : '';
		$response->package       = $response->download_link;
		$response->slug          = 'genesis-site-starter';

		// Cache the response for 12 hours.
		set_transient( $transient_name_product_info, $response, HOUR_IN_SECONDS * 12 );
	}

	return $response;
}

/**
 * Checks for plugin update API errors and shows a message on the Plugins page if errors exist.
 */
function add_plugins_page_notice() {
	if ( empty( get_option( 'genesis_site_starter_product_info_api_error', false ) ) ) {
		return;
	}

	add_action(
		'admin_notices',
		static function() {
			$plugin_basename = plugin_basename( GENESIS_SITE_STARTER_FILE );
			remove_action( "after_plugin_row_{$plugin_basename}", 'wp_plugin_update_row' );
			add_action( "after_plugin_row_{$plugin_basename}", __NAMESPACE__ . '\show_plugin_row_notice' );
		}
	);
}
add_action( 'load-plugins.php', __NAMESPACE__ . '\add_plugins_page_notice', 0 );

/**
 * Checks for plugin update API errors and conditionally shows a message on Dashboard > Updates.
 */
function add_dashboard_update_notice() {
	$api_error = get_option( 'genesis_site_starter_product_info_api_error', false );
	if ( empty( $api_error ) ) {
		return;
	}

	add_action(
		'admin_notices',
		static function() use ( $api_error ) {
			echo wp_kses_post( sprintf( '<div class="error"><p>%s</p></div>', api_error_notice_text( $api_error ) ) );
		}
	);
}
add_action( 'load-update-core.php', __NAMESPACE__ . '\add_dashboard_update_notice', 0 );

/**
 * Shows a notice on the Plugins page when there is an issue with the update service.
 *
 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
 */
function show_plugin_row_notice( $plugin_file ) {
	unset( $plugin_file );

	$api_error = get_option( 'genesis_site_starter_product_info_api_error', false );

	if ( empty( $api_error ) ) {
		return;
	}

	?>
	<tr class="plugin-update-tr active" id="genesis-site-starter-update" data-slug="genesis-site-starter" data-plugin="genesis-site-starter/genesis-site-starter.php">
		<td colspan="3" class="plugin-update">
			<div class="update-message notice inline notice-error notice-alt">
				<p><?php echo wp_kses_post( api_error_notice_text( $api_error ) ); ?></p>
			</div>
		</td>
	</tr>
	<?php
}

/**
 * Returns the text to be displayed to the user based on the
 * error code received from the Product Info Service API.
 *
 * @param string $reason The reason/error code received the API.
 * @return string
 */
function api_error_notice_text( $reason ) {
	switch ( $reason ) {
		case 'product-unknown':
			return __( 'The product you requested information for is unknown. Please contact support.', 'genesis-site-starter' );

		default:
			/* translators: %1$s: Link to account portal. %2$s: The text that is linked. */
			return sprintf( __( 'There was an unknown error connecting to the update service. Please ensure the key you have saved in the Genesis Pro settings page matches the key in your <a href="%1$s" target="_blank" rel="noreferrer noopener">%2$s</a>. This issue could be temporary. Please contact support if this error persists.', 'genesis-site-starter' ), 'https://my.wpengine.com/products/genesis_pro', esc_html__( 'WP Engine Account Portal', 'genesis-site-starter' ) );
	}
}
