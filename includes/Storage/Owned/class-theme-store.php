<?php
/** Production Theme adapter selected by the composition root.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;

use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;

final class Theme_Store implements Snapshot_Store {
	private $table;
	public function __construct(?State_Table $table = null) { $this->table = $table ?: State_Connections::table(); }
	public function read() { return $this->table->read(State_Schema::THEME); }
	public function compare_and_swap($expected_revision, array $next) { return $this->table->compare_and_swap(State_Schema::THEME, $expected_revision, $next); }
}
