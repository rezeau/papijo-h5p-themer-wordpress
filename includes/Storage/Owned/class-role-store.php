<?php
/** Production Role_Service-compatible owned-state adapter.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;

final class Role_Store {
	private $table;
	public function __construct(?State_Table $table = null) { $this->table = $table ?: State_Connections::table(); }
	public function available() { return $this->table->available(); }
	public function read() { return $this->table->read(State_Schema::ROLES); }
	public function compare_and_swap($expected_revision, array $next) { return $this->table->compare_and_swap(State_Schema::ROLES, $expected_revision, $next); }
	public function with_locked_snapshot(callable $callback) { return $this->table->with_locked_roles($callback); }
}
