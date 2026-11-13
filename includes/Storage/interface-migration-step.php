<?php
/** @package Papijo\H5PThemerWordPress @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Storage;

/** Trusted, shipped code only; never register migrations from request input. */
interface Migration_Step {
	public function from_version();
	public function to_version();
	/** Validate the complete old schema and return data/errors. No I/O. */
	public function validate_source( $snapshot );
	/** Preserve the revision; return a complete next-version snapshot. No I/O. */
	public function convert( array $snapshot );
}
