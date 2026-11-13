<?php
/** Pure, atomic change plans. No persistence, authorization, forms or callbacks. */
namespace Papijo\H5PThemerWordPress\Theme;

use Papijo\H5PThemerWordPress\Publication\Publication_Policy;
use Papijo\H5PThemerWordPress\Storage\Schema;

/** @license GPL-3.0-or-later */
final class Library_Changes {
	public function create( $state, $definition ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		$added = $this->append( $state, $definition );
		return $added['errors'] ? $added : $this->finish( $added['data'] );
	}

	/** One detached next snapshot and one revision for the entire create-only batch. */
	public function create_many( $state, $definitions ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		if ( ! is_array( $definitions ) || ! $definitions || array_keys( $definitions ) !== range( 0, count( $definitions ) - 1 ) ) { return $this->error( 'invalid_definition_batch' ); }
		foreach ( $definitions as $definition ) {
			$added = $this->append( $state, $definition );
			if ( $added['errors'] ) { return $added; }
			$state = $added['data'];
		}
		return $this->finish( $state );
	}

	private function append( array $state, $definition ) {
		$id = is_array( $definition ) && isset( $definition['id'] ) ? $definition['id'] : null;
		$valid = ( new Theme_Validator() )->definition( $id, $definition );
		if ( $valid['errors'] ) { return $valid; }
		if ( array_key_exists( $id, $state['definitions'] ) ) { return $this->error( 'destination_unavailable' ); }
		// Clear stale publication even when a deleted identity is reused.
		$state['publication']['themes'][ 'custom:' . $id ] = false;
		$state['definitions'][ $id ] = $definition;
		return array( 'data' => $state, 'errors' => array() );
	}

	public function replace( $state, $reference, $definition ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		$ref = ( new Theme_Validator() )->reference( $reference );
		if ( $ref['errors'] || 'custom' !== $ref['data']['kind'] ) { return $this->error( 'theme_not_mutable' ); }
		$id = $ref['data']['id'];
		if ( ! array_key_exists( $id, $state['definitions'] ) ) { return $this->error( 'missing_custom_theme' ); }
		$valid = ( new Theme_Validator() )->definition( $id, $definition );
		if ( $valid['errors'] ) { return $valid; }
		$state['definitions'][ $id ] = $definition;
		return $this->finish( $state );
	}

	public function publish( $state, $token, $visible ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		if ( ! Publication_Policy::valid_token( $token ) || in_array( $token, Publication_Policy::fixed_tokens(), true ) || ! is_bool( $visible ) ) {
			return $this->error( 'publication_not_editable' );
		}
		list( $kind, $id ) = explode( ':', $token, 2 );
		$reference = array( 'kind' => $kind, 'id' => $id );
		if ( 'builtin' === $kind ) { $reference['version'] = Preset_Catalog::VERSION; }
		$resolved = ( new Palette_Resolver() )->resolve( $reference, $state['definitions'] );
		if ( $resolved['errors'] ) { return $resolved; }
		$state['publication']['themes'][ $token ] = $visible;
		return $this->finish( $state );
	}

	public function set_default( $state, $reference ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		$eligible = ( new Publication_Policy() )->global_eligible( $reference, $state['definitions'] );
		if ( $eligible['errors'] ) { return $eligible; }
		$state['settings']['default_theme'] = $reference;
		return $this->finish( $state );
	}

	/** One overview submission, one revision; validate each change using publish(). */
	public function publish_many( $state, $visibility ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		if ( ! is_array( $visibility ) ) { return $this->error( 'publication_not_editable' ); }
		foreach ( $visibility as $token => $visible ) {
			$plan = $this->publish( $state, $token, $visible );
			if ( $plan['errors'] ) { return $plan; }
			$state['publication'] = $plan['data']['publication'];
		}
		return $this->finish( $state );
	}

	public function delete( $state, $reference, $additional_globals = array() ) {
		$before = ( new Schema() )->validate( $state );
		if ( $before['errors'] ) { return $before; }
		if ( ! is_array( $additional_globals ) ) { return $this->error( 'global_state_unverified' ); }
		$errors = ( new Publication_Policy() )->deletion_errors( $reference, $state['definitions'], array_merge( array( $state['settings']['default_theme'] ), $additional_globals ) );
		if ( $errors ) { return array( 'data' => null, 'errors' => $errors ); }
		$id = $reference['id'];
		$state['publication']['themes'][ 'custom:' . $id ] = false;
		unset( $state['definitions'][ $id ] );
		return $this->finish( $state );
	}

	private function finish( array $state ) {
		if ( PHP_INT_MAX === $state['revision'] ) { return $this->error( 'revision_exhausted' ); }
		++$state['revision'];
		return ( new Schema() )->validate( $state );
	}

	private function error( $code ) {
		return array( 'data' => null, 'errors' => array( $code ) );
	}
}
