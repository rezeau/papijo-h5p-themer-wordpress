<?php
/** Versioned single-site WordPress data design; no option reads or writes. */
namespace Papijo\H5PThemerWordPress\Storage;

use Papijo\H5PThemerWordPress\Theme\Theme_Validator;
use Papijo\H5PThemerWordPress\Publication\Publication_Policy;

/** @license GPL-3.0-or-later */
final class Schema {
	const VERSION = 1;
	const OPTION  = 'pjh5pwp_theme_library';

	/** Unpersisted seed only; activation does not call or save this. */
	public static function initial() {
		return array( 'schema_version' => self::VERSION, 'revision' => 0,
			'settings' => array( 'schema_version' => 2, 'default_theme' => null ),
			'definitions' => array(), 'publication' => array( 'schema_version' => 1, 'themes' => array(
				'builtin:dark' => true, 'builtin:lavender' => true, 'builtin:mint' => true, 'builtin:sunset' => true,
			) ) );
	}

	public function validate( $state ) {
		$invalid = array( 'data' => null, 'errors' => array( 'invalid_library_schema' ) );
		if ( ! is_array( $state ) || count( $state ) !== 5 || ! isset( $state['schema_version'], $state['revision'], $state['settings'], $state['definitions'], $state['publication'] ) ||
			self::VERSION !== $state['schema_version'] || ! is_int( $state['revision'] ) || $state['revision'] < 0 ||
			! is_array( $state['settings'] ) || ! is_array( $state['definitions'] ) ) {
			return $invalid;
		}
		$settings = $state['settings'];
		if ( count( $settings ) !== 2 || ! isset( $settings['schema_version'] ) || 2 !== $settings['schema_version'] || ! array_key_exists( 'default_theme', $settings ) ) {
			return $invalid;
		}
		$errors    = array();
		$validator = new Theme_Validator();
		foreach ( $state['definitions'] as $id => $definition ) {
			$errors = array_merge( $errors, $validator->definition( $id, $definition )['errors'] );
		}
		$publication = new Publication_Policy();
		$errors = array_merge( $errors, $publication->validate( $state['publication'] )['errors'],
			$publication->global_eligible( $settings['default_theme'], $state['definitions'] )['errors'] );
		return array( 'data' => $errors ? null : $state, 'errors' => array_values( array_unique( $errors ) ) );
	}

	/** Storage adapters must enforce this on the exact revision being replaced. */
	public function transition( $before, $next ) {
		$valid = $this->validate( $next );
		if ( $valid['errors'] ) { return $valid; }
		$previous = array();
		if ( null === $before ) {
			if ( 0 !== $next['revision'] ) { return array( 'data' => null, 'errors' => array( 'invalid_initial_revision' ) ); }
		} else {
			$valid_before = $this->validate( $before );
			if ( $valid_before['errors'] ) { return $valid_before; }
			if ( PHP_INT_MAX === $before['revision'] || $next['revision'] !== $before['revision'] + 1 ) {
				return array( 'data' => null, 'errors' => array( 'invalid_next_revision' ) );
			}
			$previous = $before['definitions'];
		}
		$changed_ids = array_merge( array_diff( array_keys( $next['definitions'] ), array_keys( $previous ) ),
			array_diff( array_keys( $previous ), array_keys( $next['definitions'] ) ) );
		foreach ( $changed_ids as $id ) {
			$token = 'custom:' . $id;
			if ( ! array_key_exists( $token, $next['publication']['themes'] ) || false !== $next['publication']['themes'][ $token ] ) {
				return array( 'data' => null, 'errors' => array( 'creation_or_deletion_must_unpublish' ) );
			}
		}
		return $valid;
	}
}
