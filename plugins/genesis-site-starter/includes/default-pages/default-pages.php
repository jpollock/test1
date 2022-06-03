<?php
/**
 * Set up the default pages installation.
 *
 * @package Genesis_Site_Starter
 */

declare(strict_types=1);

namespace GenesisSiteStarter\DefaultPages;

/**
 * Create a new page.
 *
 * Runs on plugin install
 *
 * @since 1.0
 */
function create_default_pages() {
	$all_patterns = get_registered_patterns();

	// Define the page we want to create.
	$pages = [
		'gss_home'     => [
			'title'         => __( 'Home', 'genesis-site-starter' ),
			'content'       => $all_patterns['gb_layout_homepage']['content'],
			'add_to_menu'   => true,
			'menu_position' => 1,
			'is_front_page' => true,
			'post_type'     => 'page',
			'page_template' => 'templates/full-width.php',
			'other_meta'    => [
				'_genesis_block_theme_hide_title' => 1,
			],
		],
		'gss_features' => [
			'title'         => __( 'Features and Services', 'genesis-site-starter' ),
			'content'       => $all_patterns['gb_slate_layout_features']['content'],
			'add_to_menu'   => true,
			'menu_position' => 2,
			'post_type'     => 'page',
			'page_template' => 'templates/full-width.php',
			'other_meta'    => [
				'_genesis_block_theme_hide_title' => 1,
			],
		],
		'gss_about'    => [
			'title'         => __( 'About', 'genesis-site-starter' ),
			'content'       => $all_patterns['gb_slate_layout_about']['content'],
			'add_to_menu'   => true,
			'menu_position' => 3,
			'post_type'     => 'page',
			'page_template' => 'templates/full-width.php',
			'other_meta'    => [
				'_genesis_block_theme_hide_title' => 1,
			],
		],
		'gss_blog'     => [
			'title'         => __( 'Blog', 'genesis-site-starter' ),
			'content'       => '',
			'add_to_menu'   => true,
			'menu_position' => 4,
			'is_blogs_page' => true,
			'post_type'     => 'page',
		],
		'gss_contact'  => [
			'title'         => __( 'Contact', 'genesis-site-starter' ),
			'content'       => $all_patterns['gb_slate_layout_contact']['content'],
			'add_to_menu'   => true,
			'menu_position' => 5,
			'post_type'     => 'page',
			'page_template' => 'templates/full-width.php',
			'other_meta'    => [
				'_genesis_block_theme_hide_title' => 1,
			],
		],
	];

	$menu_name = __( 'Main Navigation', 'genesis-site-starter' );

	// Check if a menu already exists with this name.
	$possible_existing_menu = get_term_by( 'name', $menu_name, 'nav_menu' );

	// This makes sure we don't create tons of duplicate menus, but only one. If we want to change that, bypass this if statement.
	if (
		$possible_existing_menu &&
		! is_wp_error( $possible_existing_menu ) &&
		isset( $possible_existing_menu->term_id )
	) {
		// If this menu exists, set the menu id to the existing.
		$menu_id = $possible_existing_menu->term_id;
	} else {
		// Spin up a new navigation menu.
		$menu_id = wp_create_nav_menu( __( 'Main Navigation', 'genesis-site-starter' ) );
	}

	// Set this menu to appear in the default location.
	$locations            = get_theme_mod( 'nav_menu_locations' );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );

	foreach ( $pages as $page_key => $page ) {

		// If this page already exists, skip it.
		$page_id_already_exists = get_option( $page_key );
		$page_already_exists    = get_post( $page_id_already_exists );

		// (Re)Create this page if it doesn't exist.
		if ( empty( $page_already_exists->ID ) ) {
			$page_creation_args = array(
				'post_author'    => '1',
				'post_content'   => $page['content'],
				'post_title'     => $page['title'],
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_type'      => $page['post_type'],
			);

			// Insert the post into the database.
			$page_id = wp_insert_post( $page_creation_args );

			// Save the ID so we know not to overwrite it with subsequent runs.
			update_option( $page_key, $page_id );
		} else {
			$page_id = $page_already_exists->ID;
		}

		// Make sure we have an integer.
		$page_id = absint( $page_id );

		// Set the page template.
		if ( isset( $page['page_template'] ) && ! empty( $page['page_template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', $page['page_template'] );
		}

		// Set any other meta defined.
		if ( isset( $page['other_meta'] ) && is_array( $page['other_meta'] ) ) {
			foreach ( $page['other_meta'] as $meta_key => $meta_value ) {
				update_post_meta( $page_id, $meta_key, $meta_value );
			}
		}

		// If this page should be added to a menu, add it to a menu.
		if ( $page['add_to_menu'] && ! empty( $page_id ) ) {
			$existing_menu_items  = wp_get_nav_menu_items( $menu_id );
			$page_already_on_menu = false;

			foreach ( $existing_menu_items as $existing_menu_item ) {
				// Check if this page is already on the menu. If it is, don't duplicate it on the menu.
				if ( absint( $existing_menu_item->object_id ) === absint( $page_id ) ) {
					$page_already_on_menu = true;
				}
			}

			// Add this page to the menu, if it doesn't already exist on the menu.
			if ( ! $page_already_on_menu ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'     => $page['title'],
						'menu-item-object-id' => $page_id,
						'menu-item-object'    => 'page',
						'menu-item-status'    => 'publish',
						'menu-item-position'  => $page['menu_position'],
						'menu-item-type'      => 'post_type',
					)
				);
			}
		}

		// If this page is the homepage, set it to show on the front of the site.
		if ( isset( $page['is_front_page'] ) && $page['is_front_page'] ) {
			update_option( 'show_on_front', 'page', true );
			update_option( 'page_on_front', $page_id, true );
		}

		// If this page is the blogs page, set it to show posts.
		if ( isset( $page['is_blogs_page'] ) && $page['is_blogs_page'] ) {
			update_option( 'page_for_posts', $page_id, true );
		}
	}
}
add_action( 'gss_spin_up_demo_content', __NAMESPACE__ . '\create_default_pages', 11 );

/**
 * Get all patterns available in Genesis Blocks.
 *
 * @since  1.0.0
 * @return array
 */
function get_registered_patterns() {
	$layouts            = genesis_blocks_get_layouts();
	$sections           = genesis_blocks_get_sections();
	$additional_layouts = apply_filters( 'genesis_blocks_additional_layout_components', [] ); //phpcs:ignore
	$patterns           = array_merge( $layouts, $sections, $additional_layouts );

	// Apply the key to each pattern so they are easier to reference.
	foreach ( $patterns as $pattern ) {
		$patterns_with_keys[ $pattern['key'] ] = $pattern;
	}

	return $patterns_with_keys;
}
