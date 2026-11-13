<?php
/**
 * Pure publication rules adapted from Backdrop v0.5.0 visibility contracts.
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Publication;

use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Validator;

final class Publication_Policy {
	public static function fixed_tokens() {
		return array( 'site_default', 'core', 'builtin:daylight' );
	}

	public static function valid_token( $token ) {
		return is_string( $token ) && ( in_array( $token, self::fixed_tokens(), true ) ||
			1 === preg_match( '/\A(?:builtin|custom):[a-z][a-z0-9_]{0,63}\z/', $token ) );
	}

	public function validate( $policy ) {
		$invalid = array( 'data' => null, 'errors' => array( 'invalid_publication_policy' ) );
		if ( ! is_array( $policy ) || ! isset( $policy['schema_version'], $policy['themes'] ) || 1 !== $policy['schema_version'] ||
			! is_array( $policy['themes'] ) || count( $policy ) !== 2 ) {
			return $invalid;
		}
		foreach ( $policy['themes'] as $token => $visible ) {
			if ( ! self::valid_token( $token ) || in_array( $token, self::fixed_tokens(), true ) || ! is_bool( $visible ) ) {
				return $invalid;
			}
		}
		return array( 'data' => $policy, 'errors' => array() );
	}

	/** Catalog is presentation data only; malformed definitions are omitted. */
	public function catalog( $definitions, $policy ) {
		if ( ! is_array( $definitions ) ) {
			return array( 'data' => null, 'errors' => array( 'invalid_definition_map' ) );
		}
		$result = array(
			'site_default' => array( 'label' => 'Site default', 'reference' => array( 'kind' => 'site_default' ) ),
			'core' => array( 'label' => 'H5P Core defaults', 'reference' => null ),
		);
		$presets = ( new Preset_Catalog() )->all()['data'];
		foreach ( $presets as $id => $preset ) {
			$result[ 'builtin:' . $id ] = array( 'label' => $preset['label'], 'reference' => array( 'kind' => 'builtin', 'id' => $id, 'version' => $preset['version'] ) );
		}
		$customs = array();
		foreach ( $definitions as $id => $definition ) {
			$reference = array( 'kind' => 'custom', 'id' => $id );
			$resolved  = ( new Palette_Resolver() )->resolve( $reference, $definitions );
			if ( ! $resolved['errors'] ) {
				$customs[ $id ] = array( 'label' => $resolved['data']['label'], 'reference' => $reference );
			}
		}
		uksort( $customs, static function ( $a, $b ) use ( $customs ) {
			$order = strnatcasecmp( $customs[ $a ]['label'], $customs[ $b ]['label'] );
			return $order ? $order : strcmp( $a, $b );
		} );
		foreach ( $customs as $id => $entry ) {
			$result[ 'custom:' . $id ] = $entry;
		}
		$validated = $this->validate( $policy );
		foreach ( $result as $token => $entry ) {
			if ( ! in_array( $token, self::fixed_tokens(), true ) && ( $validated['errors'] ||
				! isset( $policy['themes'][ $token ] ) || true !== $policy['themes'][ $token ] ) ) {
				unset( $result[ $token ] );
			}
		}
		return array( 'data' => $result, 'errors' => array() );
	}

	/** Global defaults need valid existence, never learner publication. */
	public function global_eligible( $reference, $definitions ) {
		return ( new Palette_Resolver() )->resolve( $reference, $definitions );
	}

	/** Explicit fallback: unavailable personal -> global; broken global -> Core. */
	public function resolve_learner( $token, $global, $definitions, $policy ) {
		$catalog  = $this->catalog( $definitions, $policy );
		$resolver = new Palette_Resolver();
		$warnings = array();
		if ( is_string( $token ) && 'site_default' !== $token && ! $catalog['errors'] && isset( $catalog['data'][ $token ] ) ) {
			$resolved = $resolver->resolve( $catalog['data'][ $token ]['reference'], $definitions );
			if ( ! $resolved['errors'] ) {
				return $resolved + array( 'source' => 'personal', 'warnings' => array() );
			}
		}
		if ( 'site_default' !== $token ) {
			$warnings[] = 'personal_theme_unavailable';
		}
		$resolved = $resolver->resolve( $global, $definitions );
		if ( ! $resolved['errors'] ) {
			return $resolved + array( 'source' => 'global', 'warnings' => $warnings );
		}
		$warnings[] = 'global_theme_unavailable';
		return $resolver->resolve( null ) + array( 'source' => 'core', 'warnings' => $warnings );
	}

	/** Protect both stored and any future effective global reference. */
	public function deletion_errors( $reference, $definitions, $global_references ) {
		$valid = ( new Theme_Validator() )->reference( $reference );
		if ( $valid['errors'] || 'custom' !== $valid['data']['kind'] ) {
			return array( 'theme_not_mutable' );
		}
		if ( ( new Palette_Resolver() )->resolve( $reference, $definitions )['errors'] ) {
			return array( 'custom_theme_unavailable' );
		}
		if ( ! is_array( $global_references ) || ! $global_references ) {
			return array( 'global_state_unverified' );
		}
		foreach ( $global_references as $global ) {
			$default = ( new Palette_Resolver() )->resolve( $global, $definitions );
			if ( $default['errors'] ) {
				return array( 'global_state_unverified' );
			}
			if ( 'custom' === $default['data']['kind'] && $valid['data']['id'] === $default['data']['id'] ) {
				return array( 'theme_is_global_default' );
			}
		}
		return array();
	}
}
