<?php
/** Detached intent only: cannot be submitted to Snapshot_Store. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;

final class Import_Planner {
	public function prepare( $json, $snapshot ) {
		$parsed = ( new Theme_JSON() )->parse( $json );
		if ( $parsed['errors'] ) { return $parsed; }
		$valid = ( new Schema() )->validate( $snapshot );
		if ( $valid['errors'] ) { return $valid; }
		if ( PHP_INT_MAX === $snapshot['revision'] ) { return array( 'data' => null, 'errors' => array( 'revision_exhausted' ) ); }
		$ids = array(); $publication = array();
		foreach ( $parsed['data'] as $record ) {
			$id = $record['id'];
			if ( array_key_exists( $id, $snapshot['definitions'] ) ) { return array( 'data' => null, 'errors' => array( 'destination_unavailable' ) ); }
			$ids[] = $id; $publication['custom:' . $id] = false;
		}
		return array( 'data' => array(
			'kind' => 'detached_color_import_plan', 'directly_persistable' => false,
			'expected_revision' => $snapshot['revision'],
			'snapshot_context' => hash( 'sha256', serialize( array( $snapshot, ( new Preset_Catalog() )->all()['data'] ) ) ),
			'input_digest' => hash( 'sha256', $json ), 'records' => $parsed['data'],
			'collisions' => array( 'checked_ids' => $ids, 'conflicts' => array() ),
			'publication_intents' => $publication,
		), 'errors' => array() );
	}
}
