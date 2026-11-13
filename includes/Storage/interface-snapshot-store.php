<?php
/** @package Papijo\H5PThemerWordPress @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Storage;

/**
 * Single-site option boundary. Implementations must document backend requirements.
 *
 * read(): raw detached snapshot or null if absent; throw on backend failure.
 * compare_and_swap(): atomically validate and replace the entire snapshot only
 * when its current revision equals $expected_revision (null means absent).
 * Require revision 0 on initialization, otherwise expected + 1. Return bool;
 * false means no write. No partial definition/publication writes are permitted.
 * Enforce Schema::transition() against the snapshot inside the atomic operation.
 * Readers must validate raw snapshots; never repair or persist on read.
 * Storage_Exception distinguishes not_committed, committed (e.g. cache failure),
 * and unknown outcomes. An exception is not a false/no-write result. Do not blindly
 * retry or roll back unknown outcomes. Schema upgrades use approved migration
 * transitions; normal domain transitions remain unchanged.
 */
interface Snapshot_Store {
	public function read();
	public function compare_and_swap( $expected_revision, array $next );
}
