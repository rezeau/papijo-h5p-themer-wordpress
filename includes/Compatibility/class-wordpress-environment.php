<?php
/**
 * Read-only WordPress environment adapter.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress\Compatibility;

defined( 'ABSPATH' ) || exit;

/** Keep WordPress discovery separate from compatibility decisions. */
final class WordPress_Environment {

	/** Resolve only fixed, internal plugin basenames. */
	public function plugin_file( $basename ) {
		return WP_PLUGIN_DIR . '/' . $basename;
	}

	/** Installation is distinct from activation and loaded runtime markers. */
	public function installed( $basename ) {
		return is_readable( $this->plugin_file( $basename ) );
	}

	/** Read activation records without initializing either dependent plugin. */
	public function active( $basename ) {
		$plugins = get_option( 'active_plugins', array() );
		if ( is_array( $plugins ) && in_array( $basename, $plugins, true ) ) {
			return true;
		}
		if ( is_multisite() ) {
			$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
			return is_array( $network_plugins ) && isset( $network_plugins[ $basename ] );
		}
		return false;
	}

	/** Read an untranslated header; a version is information, not admission. */
	public function version( $basename ) {
		$file = $this->plugin_file( $basename );
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$data = get_file_data( $file, array( 'version' => 'Version' ), 'plugin' );
		return isset( $data['version'] ) ? $data['version'] : null;
	}
}
