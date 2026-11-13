<?php
/**
 * Foundation composition root. Future features must explicitly use Runtime_Policy.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress;

use Papijo\H5PThemerWordPress\Admin\Diagnostic_Notices;
use Papijo\H5PThemerWordPress\Compatibility\H5P_Dependency;
use Papijo\H5PThemerWordPress\Compatibility\Runtime_Policy;
use Papijo\H5PThemerWordPress\Compatibility\Snordian_Compatibility;
use Papijo\H5PThemerWordPress\Compatibility\WordPress_Environment;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	private static $instance;
	private $policy;
	private $booted = false;
	private $theme_store;
	private $playback;
	private $profile_installation;
	private $state_installation;

	private function __construct() {
		$environment  = new WordPress_Environment();
		$this->policy = new Runtime_Policy( new H5P_Dependency( $environment ), new Snordian_Compatibility( $environment ) );
	}

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function boot() {
		$plugin = self::instance();
		if ( $plugin->booted ) {
			return;
		}
		$plugin->booted = true;
		add_action( 'init', array( $plugin, 'load_translations' ), 0 );
		$notices = new Diagnostic_Notices( $plugin->policy );
		add_action( 'admin_notices', array( $notices, 'render' ) );
		add_action( 'network_admin_notices', array( $notices, 'render' ) );
		$profile_storage = new \Papijo\H5PThemerWordPress\Profile\Profile_Storage_Admin( array( $plugin, 'profile_installation' ) );
		$profile_storage->register();
		$state_storage = new \Papijo\H5PThemerWordPress\Storage\Owned\State_Storage_Admin( array( $plugin, 'state_installation' ) );
		$state_storage->register();
		$admin = new \Papijo\H5PThemerWordPress\Admin\Admin_Controller( static function () use ( $plugin ) {
			return new \Papijo\H5PThemerWordPress\Admin\Admin_Service( $plugin->theme_store() );
		}, null, static function () use ( $plugin ) {
			return new \Papijo\H5PThemerWordPress\Admin\Transfer_Admin( $plugin->theme_store() );
		} );
		$admin->register();
		$profiles = new \Papijo\H5PThemerWordPress\Profile\Profile_Service( static function () use ( $plugin ) {
			return $plugin->theme_store()->read();
		} );
		$profile = new \Papijo\H5PThemerWordPress\Profile\Profile_Controller( $profiles, null, array( $plugin->policy, 'evaluate' ) );
		$profile->register();
		$admission = new \Papijo\H5PThemerWordPress\Runtime\Runtime_Admission( array( $plugin->policy, 'evaluate' ) );
		$plugin->playback = new \Papijo\H5PThemerWordPress\Runtime\Playback_Registry( $admission,
			array( \Papijo\H5PThemerWordPress\Runtime\WordPress_Context::class, 'frontend' ) );
		$plugin->playback->register();
		$theme_attachment = new \Papijo\H5PThemerWordPress\Runtime\Theme_Attachment( $plugin->playback );
		$theme_attachment->register();
		$appearance = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Service( static function () use ( $admission ) {
			// The endpoint separately validates the same-origin frontend caller. is_admin()
			// is true for admin-ajax itself and must not be used as the caller context.
			return $admission->evaluate( true );
		}, array( $profiles, 'effective' ) );
		$endpoint = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint( $appearance, static function () { return get_current_user_id(); } );
		$endpoint->register();
	}

	/** Native activation may install empty Profile storage; failures remain degraded. */
	public static function activate( $network_wide = false ) {
		self::instance()->compatibility()->evaluate();
		if ( $network_wide || is_multisite() ) {
			return array( 'state' => 'unavailable', 'errors' => array( 'profile_environment_unsupported' ), 'outcome' => 'not_attempted' );
		}
		// Independent of H5P/SNORDIAN admission. Never initialize user records or options.
		// WordPress alone manages activation records; no failure deactivates the plugin.
		$profile = self::instance()->profile_installation()->ensure_installed();
		self::instance()->state_installation()->ensure_installed();
		return $profile;
	}

	public function compatibility() {
		return $this->policy;
	}

	/** Dormant future parent-page attachment seam; no automatic output or assets. */
	public function playback_candidates() { return $this->playback ? $this->playback->candidates() : array(); }

	/** Inert until an explicit read or write; no automatic installation. */
	public function theme_store() {
		if ( null === $this->theme_store ) {
			$this->theme_store = new \Papijo\H5PThemerWordPress\Storage\Owned\Theme_Store();
		}
		return $this->theme_store;
	}

	public function storage_installation() {
		return new \Papijo\H5PThemerWordPress\Storage\Storage_Installation( $this->theme_store() );
	}
	public function state_installation() {
		if ( null === $this->state_installation ) { $this->state_installation = new \Papijo\H5PThemerWordPress\Storage\Owned\State_Installation(); }
		return $this->state_installation;
	}

	/** Access/construction are inert; callers choose read-only status or explicit setup. */
	public function profile_installation() {
		if ( null === $this->profile_installation ) {
			$this->profile_installation = new \Papijo\H5PThemerWordPress\Profile\Profile_Installation( new \Papijo\H5PThemerWordPress\Profile\Profile_Store() );
		}
		return $this->profile_installation;
	}

	public function load_translations() {
		load_plugin_textdomain( 'papijo-h5p-themer-wordpress', false, dirname( plugin_basename( PJH5PWP_FILE ) ) . '/languages' );
	}
}
