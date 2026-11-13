<?php
/**
 * Conservative capability detection for the official WordPress H5P integration.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress\Compatibility;

defined( 'ABSPATH' ) || exit;

/** Never constructs H5P services, loads content, or changes the integration. */
final class H5P_Dependency {

	const BASENAME = 'h5p/h5p.php';

	private $environment;

	public function __construct( WordPress_Environment $environment ) {
		$this->environment = $environment;
	}

	/** Return fresh data, with no persistent or cross-user cache. */
	public function inspect() {
		$result = array(
			'installed' => $this->environment->installed( self::BASENAME ),
			'active'    => $this->environment->active( self::BASENAME ),
			'supported' => false,
			'version'   => $this->environment->version( self::BASENAME ),
			'core_api'  => null,
			'code'      => 'h5p_missing',
		);
		if ( ! $result['installed'] ) {
			return $result;
		}
		$result['code'] = 'h5p_inactive';
		if ( ! $result['active'] ) {
			return $result;
		}
		$result['code'] = 'h5p_capabilities_unverified';
		try {
			// Use only the active plugin's registered loader; never require H5P files.
			if ( ! class_exists( 'H5P_Plugin' ) || ! class_exists( 'H5PCore' ) ) {
				return $result;
			}
			$root = dirname( $this->environment->plugin_file( self::BASENAME ) );
			if ( ! $this->same_file( ( new \ReflectionClass( 'H5P_Plugin' ) )->getFileName(), $root . '/public/class-h5p-plugin.php' ) ||
				! $this->same_file( ( new \ReflectionClass( 'H5PCore' ) )->getFileName(), $root . '/h5p-php-library/h5p.classes.php' ) ) {
				return $result;
			}
			$property = new \ReflectionProperty( 'H5PCore', 'coreApi' );
			if ( ! $property->isPublic() || ! $property->isStatic() ) {
				return $result;
			}
			$api = $property->getValue();
			$result['code'] = 'h5p_core_unsupported';
			if ( ! is_array( $api ) || ! isset( $api['majorVersion'], $api['minorVersion'] ) ||
				! is_int( $api['majorVersion'] ) || ! is_int( $api['minorVersion'] ) ) {
				return $result;
			}
			$result['core_api'] = $api['majorVersion'] . '.' . $api['minorVersion'];
			if ( 1 !== $api['majorVersion'] || 28 !== $api['minorVersion'] ) {
				return $result;
			}
			$result['code'] = 'h5p_capabilities_unverified';
			foreach ( array( 'get_instance' => 0, 'get_h5p_instance' => 1, 'get_content' => 1, 'add_assets' => 1, 'get_core_settings' => 0 ) as $name => $arguments ) {
				if ( ! $this->method_accepts( 'H5P_Plugin', $name, $arguments ) ) {
					return $result;
				}
			}
			if ( ! ( new \ReflectionMethod( 'H5P_Plugin', 'get_instance' ) )->isStatic() ||
				! $this->method_accepts( 'H5PCore', 'getDependenciesFiles', 1 ) || ! $this->method_accepts( 'H5PCore', 'getAssetsUrls', 1 ) ||
				! $this->method_accepts( 'H5P_Plugin', 'alter_assets', 3 ) ) {
				return $result;
			}
			$alter      = new \ReflectionMethod( 'H5P_Plugin', 'alter_assets' );
			$parameters = $alter->getParameters();
			if ( ! $parameters[0]->isPassedByReference() || ! $parameters[1]->isPassedByReference() ||
				! ( new Hook_Inspector() )->emits_asset_actions( $alter ) ) {
				return $result;
			}
			$result['supported'] = true;
			$result['code']      = 'h5p_supported';
		} catch ( \Throwable $error ) {
			// Broken autoloaders, unreadable source, and changed APIs disable admission.
			$result['code'] = 'h5p_capabilities_unverified';
		}
		return $result;
	}

	private function same_file( $actual, $expected ) {
		return $actual && realpath( $expected ) && realpath( $actual ) === realpath( $expected );
	}

	private function method_accepts( $class, $name, $arguments ) {
		if ( ! method_exists( $class, $name ) ) {
			return false;
		}
		$method = new \ReflectionMethod( $class, $name );
		return $method->isPublic() && ! $method->isAbstract() && $method->getNumberOfRequiredParameters() <= $arguments &&
			( $method->isVariadic() || $method->getNumberOfParameters() >= $arguments );
	}
}
