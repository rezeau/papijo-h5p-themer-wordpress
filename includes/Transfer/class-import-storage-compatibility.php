<?php
/** Explicit wire-to-existing-storage adaptation. No preferences or writes. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

use Papijo\H5PThemerWordPress\Theme\Theme_Validator;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;

final class Import_Storage_Compatibility {
	public function definition( $record ) {
		$validator = new Theme_Validator();
		$valid = $validator->color_record( $record );
		if ( $valid['errors'] ) { return $valid; }
		// Match the established builtin-copy default. Incoming Density was discarded
		// before planning; legacy metadata is ignored by palette/profile playback.
		$definition = $record;
		$definition['schema_version'] = null === $record['base_preset'] ? 1 : 2;
		$definition['density'] = Theme_Operations::LEGACY_DENSITY_DEFAULT;
		if ( 1 === $definition['schema_version'] ) { unset( $definition['base_preset'] ); }
		return $validator->definition( $record['id'], $definition );
	}
}
