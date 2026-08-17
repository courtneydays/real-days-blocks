<?php
/**
 * Real Days Blocks — self-contained updater.
 *
 * Deliberately NOT hooked into WordPress's global update-checking
 * system (site_transient_update_plugins) — everything here only runs
 * when you click a button on this plugin's own settings page, so a
 * problem here can't affect any other part of wp-admin.
 *
 * "Check for Updates" reads the Version: header straight off the
 * plugin's main file on GitHub. "Update Now" downloads the current
 * main branch as a zip and installs it over the existing plugin,
 * in place, without deactivating anything.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch the version number declared in the plugin header on GitHub.
 *
 * @return string|WP_Error
 */
function rdb_check_remote_version() {
	$url = sprintf(
		'https://raw.githubusercontent.com/%s/%s/%s/real-days-blocks.php',
		RDB_GH_USER,
		RDB_GH_REPO,
		RDB_GH_BRANCH
	);

	$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( $code !== 200 ) {
		return new WP_Error( 'rdb_fetch_failed', sprintf( 'GitHub returned HTTP %d fetching the plugin file. Check the repo name and that it\'s public.', $code ) );
	}

	$body = wp_remote_retrieve_body( $response );
	if ( preg_match( '/Version:\s*([0-9.]+)/', $body, $m ) ) {
		return trim( $m[1] );
	}

	return new WP_Error( 'rdb_parse_failed', 'Fetched the file from GitHub but could not find a "Version:" line in it.' );
}

/**
 * Download the current main branch and install it over the existing
 * plugin folder, in place. Uses Automatic_Upgrader_Skin for a silent
 * (no HTML output) install, and a upgrader_source_selection filter to
 * rename GitHub's "real-days-blocks-main" extraction folder to match
 * this plugin's actual folder name before WordPress moves it into
 * place — without this, WordPress won't recognise it as the same plugin.
 *
 * @return true|WP_Error
 */
function rdb_do_update() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		return new WP_Error( 'rdb_no_permission', 'You don\'t have permission to install plugins.' );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';

	$package_url = sprintf(
		'https://github.com/%s/%s/archive/refs/heads/%s.zip',
		RDB_GH_USER,
		RDB_GH_REPO,
		RDB_GH_BRANCH
	);

	$rename_filter = function ( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		if ( ! $wp_filesystem || ! is_dir( $source ) ) {
			return $source;
		}

		$expected = trailingslashit( $remote_source ) . RDB_SLUG;

		if ( untrailingslashit( $source ) !== untrailingslashit( $expected ) ) {
			if ( $wp_filesystem->move( $source, $expected, true ) ) {
				return trailingslashit( $expected );
			}
		}

		return $source;
	};

	add_filter( 'upgrader_source_selection', $rename_filter, 10, 4 );

	$skin     = new Automatic_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );

	$result = $upgrader->install( $package_url, array(
		'overwrite_package' => true,
	) );

	remove_filter( 'upgrader_source_selection', $rename_filter, 10 );

	if ( is_wp_error( $result ) ) {
		return $result;
	}
	if ( is_wp_error( $skin->result ) ) {
		return $skin->result;
	}
	if ( $result === false || is_null( $result ) ) {
		return new WP_Error( 'rdb_update_failed', 'The update did not complete. This usually means WordPress couldn\'t write to the plugins folder directly (no direct filesystem access) — check your host\'s filesystem method / FTP credentials setting.' );
	}

	// Re-activate in case the install process deactivated it during the swap.
	if ( ! is_plugin_active( RDB_SLUG . '/real-days-blocks.php' ) ) {
		activate_plugin( RDB_SLUG . '/real-days-blocks.php' );
	}

	return true;
}

/* ---------------------------------------------------------------------
   admin-post.php handlers
   --------------------------------------------------------------------- */

add_action( 'admin_post_rdb_check_update', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'rdb_check_update' );

	$remote = rdb_check_remote_version();

	if ( is_wp_error( $remote ) ) {
		set_transient( 'rdb_update_check_error', $remote->get_error_message(), 60 );
		delete_transient( 'rdb_update_available' );
	} else {
		delete_transient( 'rdb_update_check_error' );
		if ( version_compare( $remote, RDB_VERSION, '>' ) ) {
			set_transient( 'rdb_update_available', $remote, DAY_IN_SECONDS );
		} else {
			delete_transient( 'rdb_update_available' );
			set_transient( 'rdb_update_uptodate', '1', 60 );
		}
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=' . RDB_SLUG ) );
	exit;
} );

add_action( 'admin_post_rdb_do_update', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'rdb_do_update' );

	$result = rdb_do_update();

	if ( is_wp_error( $result ) ) {
		set_transient( 'rdb_update_error', $result->get_error_message(), 60 );
	} else {
		delete_transient( 'rdb_update_available' );
		set_transient( 'rdb_update_success', '1', 60 );
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=' . RDB_SLUG ) );
	exit;
} );
