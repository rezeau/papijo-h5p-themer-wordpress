<?php
/** WordPress authorization/form boundary. No HTML, redirects or runtime hooks.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Admin;

use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Storage_Installation;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Publication\Publication_Policy;

final class Admin_Service {
	private $store;
	public function __construct( Snapshot_Store $store ) { $this->store = $store; }
	public static function allowed() { return current_user_can( 'manage_options' ) && ! is_multisite(); }
	public function status() {
		if ( ! self::allowed() ) { return array( 'state' => 'forbidden', 'data' => null, 'errors' => array( 'forbidden' ) ); }
		return ( new Storage_Installation( $this->store ) )->status();
	}
	/** Bind the full captured snapshot and immutable preset data, not just a revision. */
	public static function context( $state ) {
		return hash( 'sha256', serialize( array( $state, ( new Preset_Catalog() )->all()['data'] ) ) );
	}
	public static function nonce_action( $op, $source, $revision, $context ) {
		return 'pjh5pwp:' . hash( 'sha256', serialize( array( $op, $source, $revision, $context ) ) );
	}
	public static function reference( $token ) {
		if ( 'core' === $token ) { return null; }
		if ( ! Publication_Policy::valid_token( $token ) || 'site_default' === $token ) { return false; }
		list( $kind, $id ) = explode( ':', $token, 2 );
		$ref = array( 'kind' => $kind, 'id' => $id );
		if ( 'builtin' === $kind ) { $ref['version'] = Preset_Catalog::VERSION; }
		return $ref;
	}
	/** Complete management catalog, including unpublished themes; never learner choices. */
	public static function catalog( array $state ) {
		$policy = $state['publication'];
		foreach ( ( new Preset_Catalog() )->all()['data'] as $id => $unused ) {
			if ( 'daylight' !== $id ) { $policy['themes'][ 'builtin:' . $id ] = true; }
		}
		foreach ( $state['definitions'] as $id => $unused ) { $policy['themes'][ 'custom:' . $id ] = true; }
		$result = ( new Publication_Policy() )->catalog( $state['definitions'], $policy )['data'];
		unset( $result['site_default'] );
		return $result;
	}
	/** Input has already had WordPress request slashes removed exactly once. */
	public function submit( $input ) {
		if ( ! self::allowed() ) { return $this->error( 'forbidden' ); }
		if ( ! is_array( $input ) ) { return $this->error( 'malformed_submission' ); }
		$ops = array( 'initialize', 'create', 'edit', 'duplicate', 'delete', 'default', 'visibility' );
		foreach ( array( 'pjh5pwp_op', 'source', 'revision', 'pjh5pwp_context', 'pjh5pwp_nonce' ) as $key ) {
			if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) || strlen( $input[$key] ) > 256 ) { return $this->error( 'malformed_submission' ); }
		}
		$op = $input['pjh5pwp_op']; $source = $input['source'];
		if ( ! in_array( $op, $ops, true ) ) { return $this->error( 'malformed_submission' ); }
		$keys = array( 'pjh5pwp_op', 'source', 'revision', 'pjh5pwp_context', 'pjh5pwp_nonce' );
		$extra = array( 'initialize' => array(), 'create' => array('id','label','description','colors'),
			'edit' => array('id','label','description','colors'), 'duplicate' => array('id','label','description','colors'),
			'delete' => array('confirm'), 'default' => array('default_theme'), 'visibility' => array('visible') );
		if ( array_diff( array_keys( $input ), array_merge( $keys, $extra[$op] ) ) ) { return $this->error( 'malformed_submission' ); }
		if ( ! wp_verify_nonce( $input['pjh5pwp_nonce'], self::nonce_action( $op, $source, $input['revision'], $input['pjh5pwp_context'] ) ) ) { return $this->error( 'invalid_nonce' ); }
		$status = $this->status();
		if ( ! in_array( $status['state'], array('absent','ready'), true ) ) { return $this->error( $status['errors'] ?: array('storage_unavailable') ); }
		$state = $status['data'];
		$revision = null === $state ? 'absent' : (string) $state['revision'];
		if ( $input['revision'] !== $revision || ! hash_equals( self::context( $state ), $input['pjh5pwp_context'] ) ) { return $this->error( 'storage_conflict' ); }
		if ( 'initialize' === $op ) {
			if ( null !== $state || '' !== $source ) { return $this->error( 'malformed_submission' ); }
			$plan = array( 'data' => Schema::initial(), 'errors' => array() ); $expected = null;
		} else {
			if ( null === $state ) { return $this->error( 'storage_absent' ); }
			$expected = $state['revision']; $changes = new Library_Changes();
			$reference = self::reference( $source );
			if ( in_array( $op, array('edit','duplicate','delete'), true ) && false === $reference ) { return $this->error( 'invalid_theme_reference' ); }
			if ( in_array( $op, array('create','default','visibility'), true ) && '' !== $source ) { return $this->error( 'malformed_submission' ); }
			if ( in_array( $op, array('create','edit','duplicate'), true ) ) {
				$plan = $this->definition_plan( $op, $reference, $input, $state );
			} elseif ( 'delete' === $op ) {
				$plan = isset( $input['confirm'] ) && '1' === $input['confirm'] ? $changes->delete( $state, $reference ) : $this->error( 'delete_confirmation_required' );
			} elseif ( 'default' === $op ) {
				$ref = isset( $input['default_theme'] ) ? self::reference( $input['default_theme'] ) : false;
				$plan = false === $ref ? $this->error( 'invalid_theme_reference' ) : $changes->set_default( $state, $ref );
			} else {
				$visible = array_key_exists( 'visible', $input ) ? $input['visible'] : array();
				if ( ! is_array( $visible ) ) { return $this->error( 'malformed_submission' ); }
				$map = array();
				foreach ( self::catalog( $state ) as $token => $entry ) {
					if ( ! in_array( $token, Publication_Policy::fixed_tokens(), true ) ) { $map[$token] = isset( $visible[$token] ); }
				}
				foreach ( $visible as $token => $value ) {
					if ( ! array_key_exists( $token, $map ) || '1' !== $value ) { return $this->error( 'publication_not_editable' ); }
				}
				$plan = $changes->publish_many( $state, $map );
			}
		}
		if ( $plan['errors'] ) { return $this->error( $plan['errors'] ); }
		try {
			if ( ! $this->store->compare_and_swap( $expected, $plan['data'] ) ) { return $this->error( 'storage_conflict' ); }
		} catch ( Storage_Exception $error ) { return $this->error( $error->getMessage(), $error->outcome() ); }
		return array( 'data' => $plan['data'], 'errors' => array(), 'outcome' => 'committed' );
	}
	private function definition_plan( $op, $reference, array $input, array $state ) {
		foreach ( array('id','label','description') as $key ) {
			if ( ! isset( $input[$key] ) || ! is_string( $input[$key] ) ) { return $this->error( 'malformed_submission' ); }
		}
		$colors = array_key_exists( 'colors', $input ) ? $input['colors'] : array();
		if ( ! is_array( $colors ) || count( $colors ) > count( Color_Properties::supported() ) ) { return $this->error( 'invalid_override_input' ); }
		$builtin_copy = 'duplicate' === $op && is_array( $reference ) && 'builtin' === $reference['kind'];
		if ( ! $builtin_copy && ( count( $colors ) !== count( Color_Properties::supported() ) || array_diff( Color_Properties::supported(), array_keys( $colors ) ) ) ) { return $this->error( 'invalid_override_input' ); }
		$operations = new Theme_Operations();
		if ( 'edit' === $op ) {
			if ( ! is_array( $reference ) || 'custom' !== $reference['kind'] || $input['id'] !== $reference['id'] || ! isset( $state['definitions'][$reference['id']] ) ) { return $this->error( 'theme_not_mutable' ); }
			$definition = $state['definitions'][$reference['id']];
		} else {
			$prepared = 'create' === $op ? $operations->create_from_default( $state['settings']['default_theme'], $input['id'], $input['label'], $state['definitions'] ) : $operations->duplicate( $reference, $input['id'], $input['label'], $state['definitions'] );
			if ( $prepared['errors'] ) { return $prepared; }
			$definition = $prepared['data'];
			if ( 'duplicate' === $op && 'builtin' === $reference['kind'] && $colors ) { return $this->error( 'theme_not_mutable' ); }
		}
		// On Add, blank means keep the captured starting colors. On Edit/Copy it clears an override.
		if ( 'create' === $op ) {
			foreach ( $colors as $property => $value ) {
				if ( ! in_array( $property, Color_Properties::supported(), true ) ) { return $this->error( 'unsupported_color_property' ); }
				if ( '' !== $value ) { $definition['colors'][$property] = $value; }
			}
			$colors = $definition['colors'];
		} elseif ( 'duplicate' === $op && 'builtin' === $reference['kind'] ) { $colors = array(); }
		$definition['label'] = $input['label']; $definition['description'] = $input['description'];
		$valid = $operations->with_overrides( $definition, $colors );
		if ( $valid['errors'] ) { return $valid; }
		$changes = new Library_Changes();
		return 'edit' === $op ? $changes->replace( $state, $reference, $valid['data'] ) : $changes->create( $state, $valid['data'] );
	}
	private function error( $codes, $outcome = 'not_committed' ) {
		return array( 'data' => null, 'errors' => (array) $codes, 'outcome' => $outcome );
	}
}
