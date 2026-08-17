<?php
/**
 * Plugin Name:       Real Days Blocks
 * Description:       Reusable review-article components for The Real Days. Styling lives on one settings page (no reinstall needed); new/updated components are pulled with a single "Check for Updates" button on that same page.
 * Version:           1.0.1
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            The Real Days
 * Text Domain:       real-days-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RDB_PATH', plugin_dir_path( __FILE__ ) );
define( 'RDB_URL', plugin_dir_url( __FILE__ ) );
define( 'RDB_VERSION', '1.0.1' );
define( 'RDB_SLUG', 'real-days-blocks' );

define( 'RDB_GH_USER', 'courtneydays' );
define( 'RDB_GH_REPO', 'real-days-blocks' );
define( 'RDB_GH_BRANCH', 'main' );

/**
 * List of block folder names under /blocks/.
 * Add a new folder here (and ship the code via "Check for Updates")
 * to register a new component.
 */
function rdb_block_list() {
	return array(
		'buy-card',
	);
}

/* =====================================================================
   Activation — seed the CSS option from the bundled default the first
   time this plugin is ever installed, so there's a working starting
   point on the settings page.
   ===================================================================== */
register_activation_hook( __FILE__, function () {
	if ( get_option( 'rdb_custom_css', false ) === false ) {
		$default_css = @file_get_contents( RDB_PATH . 'assets/style.css' );
		update_option( 'rdb_custom_css', $default_css ? $default_css : '' );
	}
} );

/* =====================================================================
   Block category + block registration
   ===================================================================== */
add_filter( 'block_categories_all', function ( $categories ) {
	array_unshift( $categories, array(
		'slug'  => 'real-days',
		'title' => __( 'Real Days Components', 'real-days-blocks' ),
		'icon'  => 'layout',
	) );
	return $categories;
} );

add_action( 'init', function () {
	wp_register_script(
		'rdb-helpers',
		RDB_URL . 'assets/rdb-helpers.js',
		array( 'wp-element', 'wp-components' ),
		RDB_VERSION,
		true
	);

	foreach ( rdb_block_list() as $slug ) {
		$dir = RDB_PATH . 'blocks/' . $slug;
		if ( ! file_exists( $dir . '/block.json' ) ) {
			continue;
		}

		$render_file = $dir . '/render.php';
		$args        = array();

		if ( file_exists( $render_file ) ) {
			require_once $render_file;
			$callback = 'rdb_render_' . str_replace( '-', '_', $slug );
			if ( function_exists( $callback ) ) {
				$args['render_callback'] = $callback;
			}
		}

		$edit_handle = 'rdb-edit-' . $slug;
		$edit_file   = $dir . '/edit.js';
		if ( file_exists( $edit_file ) ) {
			wp_register_script(
				$edit_handle,
				RDB_URL . 'blocks/' . $slug . '/edit.js',
				array(
					'wp-blocks',
					'wp-element',
					'wp-block-editor',
					'wp-components',
					'wp-server-side-render',
					'wp-i18n',
					'rdb-helpers',
				),
				RDB_VERSION,
				true
			);
			$args['editor_script'] = $edit_handle;
		}

		register_block_type( $dir, $args );
	}
} );

/* =====================================================================
   Shared stylesheet — served from the saved option (editable on the
   settings page), not a static file. Falls back to the bundled
   default if nothing has been saved yet.
   ===================================================================== */
function rdb_get_css() {
	$css = get_option( 'rdb_custom_css', false );
	if ( $css === false ) {
		$css = @file_get_contents( RDB_PATH . 'assets/style.css' );
	}
	return $css ? $css : '';
}

/**
 * enqueue_block_assets (not enqueue_block_editor_assets) is the hook
 * Gutenberg actually pulls into the block editor's iframed canvas as
 * well as the frontend — enqueue_block_editor_assets only reaches the
 * outer admin page, which is outside that iframe, so styles added
 * there never touch the block preview. This one hook covers both.
 */
add_action( 'enqueue_block_assets', function () {
	if ( ! is_admin() && ! is_singular() ) {
		return;
	}
	wp_register_style( 'rdb-style', false, array(), RDB_VERSION );
	wp_enqueue_style( 'rdb-style' );
	wp_add_inline_style( 'rdb-style', rdb_get_css() );
} );

/* =====================================================================
   Settings page: CSS editor (with revision history) + update checker.
   ===================================================================== */
require_once RDB_PATH . 'includes/settings-page.php';
require_once RDB_PATH . 'includes/updater.php';
