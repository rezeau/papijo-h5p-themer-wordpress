<?php
/**
 * Administrator-only, escaped diagnostics; no forms or mutations.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress\Admin;

use Papijo\H5PThemerWordPress\Compatibility\Runtime_Policy;

defined( 'ABSPATH' ) || exit;

final class Diagnostic_Notices {

	private $policy;

	public function __construct( Runtime_Policy $policy ) {
		$this->policy = $policy;
	}

	public function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		// WordPress derives the parent prefix from its translated menu title.
		$page_suffix = '_page_' . Admin_Controller::SLUG;
		$theme_page = $screen && substr( $screen->base, -strlen( $page_suffix ) ) === $page_suffix;
		$status = $this->policy->evaluate();
		foreach ( $status['blockers'] as $code ) {
			if ( 'snordian_active' === $code && ! $theme_page ) { continue; }
			$message = $this->message( $code );
			if ( '' !== $message ) {
				echo '<div class="notice notice-warning pjh5pwp-diagnostic"><p><strong>' . esc_html__( 'Papi Jo H5P Themer for WordPress', 'papijo-h5p-themer-wordpress' ) . ':</strong> ' . esc_html( $message ) . '</p></div>';
			}
		}
		if ( $screen && 'plugins' === $screen->base ) {
			// Inspect only; never initialize or migrate from an administrator notice.
			if ( class_exists( 'wpdb', false ) && isset( $GLOBALS['wpdb'] ) && ! is_multisite() ) {
				$storage = \Papijo\H5PThemerWordPress\Plugin::instance()->storage_installation()->status();
				$this->storage_notice( $storage['state'] );
			}
		}
	}

	private function storage_notice( $state ) {
		if ( 'ready' === $state ) { return; }
		if ( 0 === strpos( $state, 'owned_state_' ) ) {
			$message = Admin_Messages::text( $state );
			echo '<div class="notice notice-warning pjh5pwp-diagnostic"><p>' . esc_html__( 'Papi Jo state storage', 'papijo-h5p-themer-wordpress' ) . ': ' . esc_html( $message ) . '</p></div>';
			return;
		}
		switch ( $state ) {
			case 'absent':
				$message = __( 'Theme storage is not initialized. No data was changed. Initialization requires an explicitly authorized installation step.', 'papijo-h5p-themer-wordpress' ); break;
			case 'upgrade_required':
				$message = __( 'Theme storage requires an explicit schema upgrade. Existing data is preserved; no migration runs from this notice.', 'papijo-h5p-themer-wordpress' ); break;
			case 'newer_storage_schema':
				$message = __( 'Theme storage belongs to a newer plugin schema. Writes are blocked and existing data is preserved.', 'papijo-h5p-themer-wordpress' ); break;
			case 'unsupported_storage_schema':
				$message = __( 'No approved migration is registered for this theme-storage schema. Existing data is preserved; do not reinstall or reset it.', 'papijo-h5p-themer-wordpress' ); break;
			case 'malformed_storage':
				$message = __( 'Theme storage is malformed. Reads do not repair it and writes are blocked. Preserve the existing option before investigating.', 'papijo-h5p-themer-wordpress' ); break;
			case 'storage_write_unknown':
				$message = __( 'The theme-storage write outcome is uncertain. Reload and inspect the stored revision before retrying. Do not automatically restore an older snapshot.', 'papijo-h5p-themer-wordpress' ); break;
			case 'storage_cache_failed':
				$message = __( 'The theme-storage write committed, but cache invalidation failed. Read the stored state again before another action; do not repeat the old write.', 'papijo-h5p-themer-wordpress' ); break;
			case 'storage_write_failed':
				$message = __( 'The theme-storage write failed without committing. Existing data is preserved.', 'papijo-h5p-themer-wordpress' ); break;
			case 'storage_conflict':
				$message = __( 'Theme storage changed concurrently. Reload the current revision before preparing another change.', 'papijo-h5p-themer-wordpress' ); break;
			case 'migration_failed':
				$message = __( 'The theme-storage migration could not be prepared. Existing data is preserved; no destructive recovery was attempted.', 'papijo-h5p-themer-wordpress' ); break;
			case 'storage_options_engine_unsupported':
				$message = Admin_Messages::text( $state ); break;
			case 'storage_environment_unsupported':
				$message = __( 'Papi Jo storage requires a supported single-site private WordPress database connection. Its own state and Profile tables require InnoDB; WordPress options may use MyISAM. No WordPress table was changed.', 'papijo-h5p-themer-wordpress' ); break;
			default:
				$message = __( 'Theme storage could not be verified. Existing data is preserved; initialization and recovery are not performed automatically.', 'papijo-h5p-themer-wordpress' );
		}
		echo '<div class="notice notice-warning pjh5pwp-diagnostic"><p>' . esc_html__( 'Papi Jo theme storage', 'papijo-h5p-themer-wordpress' ) . ': ' . esc_html( $message ) . '</p></div>';
	}

	private function message( $code ) {
		switch ( $code ) {
			case 'h5p_missing':
				return __( 'The official H5P plugin is missing or unreadable. H5P-specific functionality is unavailable; existing settings are preserved.', 'papijo-h5p-themer-wordpress' );
			case 'h5p_inactive':
				return __( 'The official H5P plugin is installed but inactive. H5P-specific functionality is unavailable; existing settings are preserved.', 'papijo-h5p-themer-wordpress' );
			case 'h5p_core_unsupported':
				return __( 'This foundation supports H5P Core API exactly 1.28. The installed API is unsupported or malformed; H5P-specific functionality is unavailable.', 'papijo-h5p-themer-wordpress' );
			case 'h5p_capabilities_unverified':
				return __( 'The active H5P integration could not be verified. Its loaded classes, public methods and script/style action emitters must match the supported official integration. H5P-specific functionality is unavailable.', 'papijo-h5p-themer-wordpress' );
			case 'snordian_active':
				return __( "SNORDIAN's H5P Themer is active or its runtime markers are loaded. Papi Jo runtime colors and Density are suspended to avoid simultaneous application. Administration remains available. Neither plugin nor its settings have been changed.", 'papijo-h5p-themer-wordpress' );
			case 'multisite_deferred':
				return __( 'Multisite support is deferred. Papi Jo runtime colors and Density remain suspended on this installation.', 'papijo-h5p-themer-wordpress' );
		}
		return '';
	}
}
