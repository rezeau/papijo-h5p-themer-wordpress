<?php
/** Pure colors-only wire codec; no CMS APIs or persistence. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

use Papijo\H5PThemerWordPress\Theme\Theme_Validator;
use Papijo\H5PThemerWordPress\Storage\Schema;

final class Theme_JSON {
	const FORMAT = 'papijo-h5p-theme-colors';
	const VERSION = 1;
	const MAX_THEMES = 100;
	public function record( $object ) {
		if ( ! $object instanceof \stdClass ) { return $this->error( 'invalid_color_record' ); }
		$data = get_object_vars( $object );
		if ( ! ( $data['colors'] ?? null ) instanceof \stdClass ) { return $this->error( 'invalid_colors' ); }
		$data['colors'] = get_object_vars( $data['colors'] );
		if ( array_key_exists( 'base_preset', $data ) && null !== $data['base_preset'] ) {
			if ( ! $data['base_preset'] instanceof \stdClass ) { return $this->error( 'invalid_preset_reference' ); }
			$data['base_preset'] = get_object_vars( $data['base_preset'] );
		}
		return ( new Theme_Validator() )->color_record( $data );
	}
	public function parse( $json ) {
		$decoded = ( new JSON_Decoder() )->decode( $json );
		if ( $decoded['errors'] ) { return $decoded; }
		$root = $decoded['data'];
		if ( ! $root instanceof \stdClass ) { return $this->error( 'invalid_transfer_format' ); }
		$fields = get_object_vars( $root );
		if ( array_key_exists( 'format', $fields ) ) {
			if ( count( $fields ) !== 3 || ( $fields['format'] ?? null ) !== self::FORMAT || ( $fields['format_version'] ?? null ) !== self::VERSION ||
				! isset( $fields['themes'] ) || ! is_array( $fields['themes'] ) ) { return $this->error( 'invalid_transfer_format' ); }
			$objects = $fields['themes']; $source = 'bundle';
		} elseif ( array_key_exists( '_config_name', $fields ) ) {
			$objects = array( $root ); $source = 'backdrop';
		} else { return $this->error( 'invalid_transfer_format' ); }
		if ( ! $objects || count( $objects ) > self::MAX_THEMES ) { return $this->error( 'invalid_theme_count' ); }
		$records = array(); $seen = array();
		foreach ( $objects as $object ) {
			if ( ! $object instanceof \stdClass ) { return $this->error( 'invalid_color_record' ); }
			$valid = 'backdrop' === $source ? ( new Backdrop_Adapter() )->project( $object ) : $this->record( $object );
			if ( $valid['errors'] ) { return $valid; }
			$id = $valid['data']['id'];
			if ( isset( $seen[$id] ) ) { return $this->error( 'duplicate_theme_id' ); }
			$seen[$id] = true; $records[] = $valid['data'];
		}
		return array( 'data' => $records, 'errors' => array() );
	}
	/** Serialize canonical detached records, never arbitrary stored snapshots. */
	public function serialize( $records ) {
		if ( ! is_array( $records ) || ! $records || count( $records ) > self::MAX_THEMES || array_keys( $records ) !== range( 0, count( $records ) - 1 ) ) { return $this->error( 'invalid_theme_count' ); }
		$sorted = array(); $validator = new Theme_Validator();
		foreach ( $records as $record ) {
			$valid = $validator->color_record( $record );
			if ( $valid['errors'] ) { return $valid; }
			$id = $record['id'];
			if ( isset( $sorted[$id] ) ) { return $this->error( 'duplicate_theme_id' ); }
			$item = array( 'id' => $id, 'label' => $record['label'] );
			if ( array_key_exists( 'description', $record ) ) { $item['description'] = $record['description']; }
			$base = $record['base_preset'];
			$item['base_preset'] = null === $base ? null : array( 'id' => $base['id'], 'version' => $base['version'] );
			$colors = $record['colors']; ksort( $colors, SORT_STRING );
			$item['colors'] = (object) $colors; $sorted[$id] = $item;
		}
		ksort( $sorted, SORT_STRING );
		try { $json = json_encode( array( 'format' => self::FORMAT, 'format_version' => self::VERSION, 'themes' => array_values( $sorted ) ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR, JSON_Decoder::MAX_DEPTH ) . "\n"; }
		catch ( \Throwable $error ) { return $this->error( 'json_encoding_failed' ); }
		if ( strlen( $json ) > JSON_Decoder::MAX_BYTES ) { return $this->error( 'json_size_or_type' ); }
		return array( 'data' => $json, 'errors' => array() );
	}
	/** Export only selected customs from an already supplied detached snapshot. */
	public function export( $snapshot, $ids ) {
		$valid = ( new Schema() )->validate( $snapshot );
		if ( $valid['errors'] ) { return $valid; }
		if ( ! is_array( $ids ) || ! $ids || count( $ids ) > self::MAX_THEMES ) { return $this->error( 'invalid_theme_count' ); }
		$records = array();
		foreach ( $ids as $id ) {
			if ( ! Theme_Validator::valid_id( $id ) || ! array_key_exists( $id, $snapshot['definitions'] ) ) { return $this->error( 'missing_custom_theme' ); }
			$definition = $snapshot['definitions'][$id];
			$record = array();
			foreach ( array( 'id', 'label', 'description', 'colors' ) as $key ) { if ( array_key_exists( $key, $definition ) ) { $record[$key] = $definition[$key]; } }
			$record['base_preset'] = 2 === $definition['schema_version'] ? $definition['base_preset'] : null;
			$records[] = $record;
		}
		return $this->serialize( $records );
	}
	private function error( $code ) { return array( 'data' => null, 'errors' => array( $code ) ); }
}
