<?php
/**
 * Real Days Blocks — settings page.
 *
 * One page under Settings → Real Days Blocks with:
 *   - Update checker status + "Check for Updates" / "Update Now" buttons
 *     (the actual update logic lives in includes/updater.php; this file
 *     just renders the current status and posts to its handlers).
 *   - A CSS textarea bound to the rdb_custom_css option. Saving here
 *     applies instantly to every post, old and new — no reinstall.
 *   - A revision history of the last 5 saved CSS values, each with a
 *     one-click Restore, so a bad edit while stress-testing is never
 *     more than a click to undo.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =====================================================================
   Admin menu
   ===================================================================== */
add_action( 'admin_menu', function () {
	add_options_page(
		'Real Days Blocks',
		'Real Days Blocks',
		'manage_options',
		RDB_SLUG,
		'rdb_render_settings_page'
	);
} );

/* =====================================================================
   Save CSS handler
   ===================================================================== */
add_action( 'admin_post_rdb_save_css', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'rdb_save_css' );

	$new_css = isset( $_POST['rdb_custom_css'] ) ? wp_unslash( $_POST['rdb_custom_css'] ) : '';
	// Strip anything that could close out of the <style> tag this gets printed inside.
	$new_css = str_ireplace( '</style', '', $new_css );

	$current_css = get_option( 'rdb_custom_css', '' );

	if ( $new_css !== $current_css ) {
		$history = get_option( 'rdb_css_history', array() );
		array_unshift( $history, array(
			'css'  => $current_css,
			'time' => time(),
		) );
		$history = array_slice( $history, 0, 5 );
		update_option( 'rdb_css_history', $history );
		update_option( 'rdb_custom_css', $new_css );
		set_transient( 'rdb_css_saved', '1', 60 );
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=' . RDB_SLUG ) );
	exit;
} );

/* =====================================================================
   Restore CSS handler
   ===================================================================== */
add_action( 'admin_post_rdb_restore_css', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Not allowed.' );
	}
	check_admin_referer( 'rdb_restore_css' );

	$index   = isset( $_POST['history_index'] ) ? (int) $_POST['history_index'] : -1;
	$history = get_option( 'rdb_css_history', array() );

	if ( isset( $history[ $index ] ) ) {
		$current_css = get_option( 'rdb_custom_css', '' );
		$restored    = $history[ $index ]['css'];

		// Put the current (pre-restore) value into history so restoring is
		// itself undoable, then drop the entry we just restored from its
		// old slot so it isn't sitting in history twice.
		array_unshift( $history, array(
			'css'  => $current_css,
			'time' => time(),
		) );
		unset( $history[ $index + 1 ] );
		$history = array_slice( array_values( $history ), 0, 5 );

		update_option( 'rdb_css_history', $history );
		update_option( 'rdb_custom_css', $restored );
		set_transient( 'rdb_css_restored', '1', 60 );
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=' . RDB_SLUG ) );
	exit;
} );

/* =====================================================================
   Render
   ===================================================================== */
function rdb_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$css     = get_option( 'rdb_custom_css', '' );
	$history = get_option( 'rdb_css_history', array() );

	$update_available = get_transient( 'rdb_update_available' );
	$check_error       = get_transient( 'rdb_update_check_error' );
	$uptodate          = get_transient( 'rdb_update_uptodate' );
	$update_error      = get_transient( 'rdb_update_error' );
	$update_success    = get_transient( 'rdb_update_success' );
	$css_saved         = get_transient( 'rdb_css_saved' );
	$css_restored      = get_transient( 'rdb_css_restored' );
	?>
	<div class="wrap">
		<h1>Real Days Blocks</h1>

		<?php if ( $update_success ) : ?>
			<div class="notice notice-success"><p>Updated successfully — now on version <?php echo esc_html( RDB_VERSION ); ?>.</p></div>
			<?php delete_transient( 'rdb_update_success' ); ?>
		<?php endif; ?>

		<?php if ( $update_error ) : ?>
			<div class="notice notice-error"><p><strong>Update failed:</strong> <?php echo esc_html( $update_error ); ?></p></div>
			<?php delete_transient( 'rdb_update_error' ); ?>
		<?php endif; ?>

		<?php if ( $check_error ) : ?>
			<div class="notice notice-error"><p><strong>Couldn't check for updates:</strong> <?php echo esc_html( $check_error ); ?></p></div>
			<?php delete_transient( 'rdb_update_check_error' ); ?>
		<?php endif; ?>

		<?php if ( $uptodate ) : ?>
			<div class="notice notice-success"><p>You're up to date — version <?php echo esc_html( RDB_VERSION ); ?> is the latest.</p></div>
			<?php delete_transient( 'rdb_update_uptodate' ); ?>
		<?php endif; ?>

		<?php if ( $css_saved ) : ?>
			<div class="notice notice-success"><p>Styling saved — it's live on every post now.</p></div>
			<?php delete_transient( 'rdb_css_saved' ); ?>
		<?php endif; ?>

		<?php if ( $css_restored ) : ?>
			<div class="notice notice-success"><p>Restored that version of the styling — it's live now.</p></div>
			<?php delete_transient( 'rdb_css_restored' ); ?>
		<?php endif; ?>

		<h2>Updates</h2>
		<p>Installed version: <strong><?php echo esc_html( RDB_VERSION ); ?></strong></p>

		<?php if ( $update_available ) : ?>
			<p>Version <strong><?php echo esc_html( $update_available ); ?></strong> is available.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:8px;">
				<?php wp_nonce_field( 'rdb_do_update' ); ?>
				<input type="hidden" name="action" value="rdb_do_update" />
				<?php submit_button( 'Update Now', 'primary', 'submit', false ); ?>
			</form>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
			<?php wp_nonce_field( 'rdb_check_update' ); ?>
			<input type="hidden" name="action" value="rdb_check_update" />
			<?php submit_button( 'Check for Updates', 'secondary', 'submit', false ); ?>
		</form>

		<hr />

		<h2>Styling</h2>
		<p>This CSS applies to every post — old and new — the moment you save. No reinstall needed.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'rdb_save_css' ); ?>
			<input type="hidden" name="action" value="rdb_save_css" />
			<textarea name="rdb_custom_css" rows="24" style="width:100%;max-width:900px;font-family:Consolas,Monaco,monospace;font-size:13px;"><?php echo esc_textarea( $css ); ?></textarea>
			<p><?php submit_button( 'Save Styling', 'primary', 'submit', false ); ?></p>
		</form>

		<?php if ( ! empty( $history ) ) : ?>
			<h2>Styling history</h2>
			<p>The last <?php echo count( $history ); ?> saved version<?php echo count( $history ) === 1 ? '' : 's'; ?>. Restoring swaps the current styling back to that point (and saves what you currently have as a new history entry, so it's never a one-way trip).</p>
			<table class="widefat" style="max-width:900px;">
				<thead>
					<tr>
						<th>Saved</th>
						<th style="width:120px;">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $history as $i => $entry ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( 'M j, Y g:ia', $entry['time'] ) ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'rdb_restore_css' ); ?>
									<input type="hidden" name="action" value="rdb_restore_css" />
									<input type="hidden" name="history_index" value="<?php echo esc_attr( $i ); ?>" />
									<?php submit_button( 'Restore', 'secondary small', 'submit', false ); ?>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
