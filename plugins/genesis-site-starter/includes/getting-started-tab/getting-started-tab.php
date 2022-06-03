<?php
/**
 * Set up the getting started page.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\GettingStartedPage;

/**
 * Check whether demo content should be initiated, based on the variables in the URL.
 *
 * @since 0.0.1
 * @return bool
 */
function should_initiate_demo_content(): bool {
	$url_nonce = filter_input( INPUT_GET, 'gss-spin-up-demo-content-nonce', FILTER_SANITIZE_STRING );

	if ( ! wp_verify_nonce( $url_nonce, 'gss_spin_up_demo_content_nonce' ) ) {
		return false;
	}

	// Check if initiate_gss_content=1 is in the URL. This only happens once.
	$initiate_demo_content = filter_input( INPUT_GET, 'gss-initiate-demo-content', FILTER_SANITIZE_STRING ) === '1' ? true : false;

	// Check if gss-force-initiate-demo-content=1 is in the url. This can be used over and over to regenerate demo content at any time.
	$force_regenerate_demo_content = filter_input( INPUT_GET, 'gss-force-regenerate-demo-content', FILTER_SANITIZE_STRING ) === '1' ? true : false;

	// Do not re-initiate demo content. For example, if the page is refreshed, or a new admin logs in 2 years later for the first time (and gets redirected to Getting Started).
	if ( get_option( 'gss_demo_content_already_initiated' ) ) {
		$initiate_demo_content = false;
	}

	// But if force is in the URL, regenerate it no matter what has already happened.
	if ( $force_regenerate_demo_content ) {
		$initiate_demo_content = true;
	}

	return $initiate_demo_content;
}

/**
 * Check whether a "Regenerate Demo Content" button should be shown to the user on the Getting Started page.
 *
 * @since 0.0.1
 * @return bool
 */
function should_show_regenerate_demo_content_button(): bool {

	// Check if gss-regenerate-demo-content-option=1 is in the URL.
	return filter_input( INPUT_GET, 'gss-regenerate-demo-content-option', FILTER_SANITIZE_STRING ) === '1' ? true : false;
}

/**
 * Get the URL for regenerating the demo content, with the nonce.
 *
 * @since 0.0.1
 * @return string
 */
function get_force_regenerate_demo_content_endpoint_url(): string {
	return add_query_arg(
		array(
			'gss_spin_up_demo_content'       => true,
			'gss_force_spin_up_demo_content' => true,
			'gss_spin_up_demo_content_nonce' => wp_create_nonce( 'gss_spin_up_demo_content_nonce' ),
		),
		admin_url( 'edit.php' )
	);
}

/**
 * Check whether demo content should be initiated, based on the variables in the URL.
 *
 * @since 0.0.1
 */
function maybe_enqueue_initiate_demo_content_js(): void {
	$url_nonce = filter_input( INPUT_GET, 'gss-spin-up-demo-content-nonce', FILTER_SANITIZE_STRING );

	// If initiate_gss_content is in the URL, send a call via javascript to gss_spin_up_demo_content. This has to happen here because the WP Engine platform appears to be caching database results until after the first wp-admin page load.
	if ( should_initiate_demo_content() ) {
		wp_enqueue_script( 'gss_initiate_demo_content', GENESIS_SITE_STARTER_URL . 'includes/getting-started-tab/assets/initiate-demo-content.js', array(), GENESIS_SITE_STARTER_VERSION, true );
		wp_localize_script(
			'gss_initiate_demo_content',
			'gss_initiate_demo_content',
			array(
				'spin_up_demo_content_endpoint_url' => add_query_arg(
					array(
						'gss_spin_up_demo_content'       => true,
						'gss_spin_up_demo_content_nonce' => $url_nonce,
					),
					admin_url( 'edit.php' )
				),
			)
		);
	}
}

/**
 * Adds the getting started tab to the genesis blocks getting started tabs.
 *
 * @param array $tabs The array of tabs to show on the Genesis Getting Started page.
 * @since 1.0.1
 */
function add_getting_started_tab( $tabs ) {
	array_unshift(
		$tabs,
		array(
			'tab_label'    => __( 'Getting Started', 'genesis-site-starter' ),
			'tab_slug'     => 'genesis-getting-started',
			'tab_callback' => __NAMESPACE__ . '\render_getting_started_tab',
		)
	);

	return $tabs;
}
add_filter( 'genesis_blocks_setting_started_tabs', __NAMESPACE__ . '\add_getting_started_tab' );

/**
 * Render the contents of the getting started tab.
 *
 * @since 0.0.1
 */
function render_getting_started_tab(): void {

	// Check whether we should initiate the demo content.
	$gss_initiate_demo_content = should_initiate_demo_content();

	maybe_enqueue_initiate_demo_content_js();

	if ( function_exists( 'wpe_site' ) ) {
		$golive_url = 'https://my.wpengine.com/installs/' . wpe_site() . '/go_live_checklist';
	} else {
		$golive_url = 'https://wpengine.com/support/go-live-checklist/';
	}

	?>

	<div class="gb-admin-plugin-container">
		<div class="gb-admin-plugin-grid-2 center">
			<div>
				<h2><?php esc_html_e( 'Welcome to your new website!', 'genesis-site-starter' ); ?></h2>
				<p><?php esc_html_e( 'Your site comes with the Genesis Blocks plugin, Genesis Block Theme, and example pages with content already installed to get you up and running faster. In this section and the tabs above, we’ve provided you with a handy step-by-step guide and just the right amount of information to help you build your site easily and quickly.', 'genesis-site-starter' ); ?></p>
				<div class="gb-admin-button-controls">
					<a class="gb-admin-button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Edit your site', 'genesis-site-starter' ); ?></a>
					<a class="gb-admin-button-secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_html_e( 'Preview your site (opens in a new tab)', 'genesis-site-starter' ); ?>"><?php esc_html_e( 'Preview your site', 'genesis-site-starter' ); ?></a>
				</div>
				<div id="gss-importing-notifier" class="gss-importing" style="<?php echo esc_attr( $gss_initiate_demo_content ? 'display:block;' : 'display:none;' ); ?>">
					<div id="gss-importing">
						<span class="spinner is-active"></span>
						<p><?php esc_html_e( 'Your site demo content is being added.', 'genesis-site-starter' ); ?></p>
					</div>
					<div id="gss-import-success" style="display:none;">
						<p><?php esc_html_e( 'Your site demo content was successfully added.', 'genesis-site-starter' ); ?></p>
					</div>
				</div>
				<?php
				// If we should show a "Regenerate Demo Content" button.
				if ( should_show_regenerate_demo_content_button() ) {
					?>
					<div id="gss-import-demo-content-option" class="gss-import-demo-content-option">
						<script type="text/javascript">
							function gss_initiate_demo_content_regeneration() {
								// Hide the content regeneration button.
								document.getElementById("gss-import-demo-content-option").style.display = "none";
								// Show the importing notifier.
								document.getElementById("gss-importing-notifier").style.display = "block";

								// Send the fetch call to the server to regenerate the demo content.
								fetch( "<?php echo esc_url_raw( get_force_regenerate_demo_content_endpoint_url() ); ?>", {
									method: "POST",
									mode: "same-origin",
									credentials: "same-origin",
								} ).then(function() {
									// When fetch is complete, hide the importing notifier.
									document.getElementById("gss-importing").style.display = "none";
									// Show the success message.
									document.getElementById("gss-import-success").style.display = "block";
									// Show the View site button.
									document.getElementById("gss-view-website-button").style.display = "block";
								} );
							}
						</script>
						<button class="gb-admin-button-link aligned gss-regenerate" onclick="gss_initiate_demo_content_regeneration()"><?php esc_html_e( 'Regenerate Demo Content', 'genesis-site-starter' ); ?></button>
					</div>
					<?php
				}
				?>
			</div>
			<div>
				<iframe src="https://player.vimeo.com/video/461087817?h=b07cb512ea" title="<?php esc_html_e( 'Genesis Blocks Demo', 'genesis-site-starter' ); ?>" width="100%" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
			</div>
		</div>
	</div>
	<div class="gb-admin-plugin-container gb-site-building-steps">
		<h2 class="centered"><?php esc_html_e( 'Site Building Steps', 'genesis-site-starter' ); ?></h2>
		<div class="gb-admin-plugin-grid-4">
			<div class="gb-admin-column">
				<div>
					<img src="<?php echo esc_url( plugin_dir_url( genesis_blocks_main_plugin_file() ) . 'lib/Settings/assets/images/step-1.svg' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Genesis 101', 'genesis-site-starter' ); ?></h3>
					<p><?php esc_html_e( 'Take a look at our tutorial page full of instructions on how to use block editing to build and edit your website.', 'genesis-site-starter' ); ?></p>
				</div>
				<div class="gb-admin-button-controls centered">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=genesis-blocks-getting-started&tab=genesis-101' ) ); ?>"><?php esc_html_e( 'Go to Genesis 101', 'genesis-site-starter' ); ?></a>
				</div>
			</div>
			<div class="gb-admin-column">
				<div>
					<img src="<?php echo esc_url( plugin_dir_url( genesis_blocks_main_plugin_file() ) . 'lib/Settings/assets/images/step-2.svg' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Edit pages', 'genesis-site-starter' ); ?></h3>
					<p><?php esc_html_e( 'Once you’re familiar with blocks, you can edit the example pages and content we created for you or create your own pages from scratch.', 'genesis-site-starter' ); ?></p>
				</div>
				<div class="gb-admin-button-controls centered">
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>"><?php esc_html_e( 'Edit your pages', 'genesis-site-starter' ); ?></a>
				</div>
			</div>
			<div class="gb-admin-column">
				<div>
					<img src="<?php echo esc_url( plugin_dir_url( genesis_blocks_main_plugin_file() ) . 'lib/Settings/assets/images/step-3.svg' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Build', 'genesis-site-starter' ); ?></h3>
					<p><?php esc_html_e( 'Build your site with Collections — groups of fully designed page layouts that make it easy to give your site a cohesive look and feel.', 'genesis-site-starter' ); ?></p>
				</div>
				<div class="gb-admin-button-controls centered">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=genesis-blocks-getting-started&tab=collections' ) ); ?>"><?php esc_html_e( 'Learn about Collections', 'genesis-site-starter' ); ?></a>
				</div>
			</div>
			<div class="gb-admin-column">
				<div>
					<img src="<?php echo esc_url( plugin_dir_url( genesis_blocks_main_plugin_file() ) . 'lib/Settings/assets/images/step-4.svg' ); ?>" alt="" />
					<h3><?php esc_html_e( 'Go Live!', 'genesis-site-starter' ); ?></h3>
					<p><?php esc_html_e( 'Complete WP Engine’s Go Live Checklist so you can make sure you have everything necessary to launch your brand new site! &#128640;', 'genesis-site-starter' ); ?></p>
				</div>
				<div class="gb-admin-button-controls centered">
					<a href="<?php echo esc_url( $golive_url ); ?>" target="_blank" rel="noopener" aria-label="<?php esc_html_e( 'View the Go Live Checklist (opens in a new tab)', 'genesis-site-starter' ); ?>"><?php esc_html_e( 'View the Go Live Checklist', 'genesis-site-starter' ); ?></a>
				</div>
			</div>
		</div>
	</div>
	<?php
}
