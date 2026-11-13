<?php
/** Production private-session, exact-byte state CAS and transactional permission locking.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;

use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Storage\Snapshot_Codec;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Role\Role_State;

final class State_Table {
	private $connections;
	/** Factory must provide a NEW owned session per call, plus the prefixed table. */
	public function __construct(callable $connections) { $this->connections = $connections; }
	public function available() { list($db) = $this->open(); $this->close($db); return true; }
	public function verify_records($db, $table) {
		$rows = State_Schema::rows($db, "SELECT state_key, record_id, schema_version, revision, payload FROM `$table` LIMIT 3");
		if (count($rows) > 2) { throw new Storage_Exception('owned_state_schema_unsupported'); }
		foreach ($rows as $row) { $key = $row['state_key'] ?? null; State_Schema::key($key); $this->decode($row, $key); }
	}
	public function read($key) {
		State_Schema::key($key);
		list($db, $table, $thread) = $this->open();
		try {
			$row = $this->row($db, $table, $key);
			$this->same_session($db, $thread, 'storage_read_failed');
			return $row === null ? null : $this->decode($row, $key);
		} finally { $this->close($db); }
	}
	public function compare_and_swap($key, $expected_revision, array $next) {
		State_Schema::key($key);
		if (($expected_revision !== null && (!is_int($expected_revision) || $expected_revision < 0 || $expected_revision === PHP_INT_MAX)) || !$this->valid($key, $next)) { return false; }
		$bytes = (new Snapshot_Codec())->encode($next);
		list($db, $table, $thread) = $this->open();
		try {
			$row = $this->row($db, $table, $key);
			$this->same_session($db, $thread, 'storage_read_failed');
			$before = $row === null ? null : $this->decode($row, $key);
			if (($row === null) !== ($expected_revision === null) || ($before !== null && $before['revision'] !== $expected_revision)) { return false; }
			if ($key === State_Schema::THEME) {
				if ((new Schema())->transition($before, $next)['errors']) { return false; }
			} elseif ($next['revision'] !== ($before === null ? 0 : $before['revision'] + 1)) { return false; }
			if ($row === null) {
				$sql = $db->prepare("INSERT INTO `$table` (state_key, schema_version, revision, payload) VALUES (%s, %d, %d, UNHEX(%s))", $key, $next['schema_version'], $next['revision'], bin2hex($bytes));
			} else {
				$sql = $db->prepare("UPDATE `$table` SET schema_version = %d, revision = %d, payload = UNHEX(%s) WHERE state_key = %s AND record_id = %s AND schema_version = %d AND revision = %d AND BINARY payload = UNHEX(%s)", $next['schema_version'], $next['revision'], bin2hex($bytes), $key, $row['record_id'], $before['schema_version'], $before['revision'], bin2hex($row['payload']));
			}
			// A lost acknowledgement must never cause wpdb to reconnect/replay this write.
			try { $affected = $db->query_once($sql); }
			catch (\Throwable $error) { throw new Storage_Exception('storage_write_unknown', 'unknown'); }
			$this->same_session($db, $thread, 'storage_write_unknown', 'unknown');
			if ($affected === 1) { return true; }
			if ($affected === 0 || ($row === null && $affected === false && $db->error_number() === 1062)) { return false; }
			if ($affected === false && !in_array($db->error_number(), array(0, 2006, 2013, 2055), true)) { throw new Storage_Exception('storage_write_failed'); }
			throw new Storage_Exception('storage_write_unknown', 'unknown');
		} finally { $this->close($db); }
	}
	/** Same bounded live-session lock contract as production Role_Store. */
	public function with_locked_roles(callable $callback) {
		list($db, $table, $thread) = $this->open();
		try {
			try { $started = $db->query_once('START TRANSACTION'); }
			catch (\Throwable $error) { throw new Storage_Exception('owned_state_lock_failed'); }
			if ($started === false) { throw new Storage_Exception('owned_state_lock_failed'); }
			$this->same_session($db, $thread, 'owned_state_lock_failed');
			$row = $this->row($db, $table, State_Schema::ROLES, true);
			$state = $row === null ? null : $this->decode($row, State_Schema::ROLES);
			$this->same_session($db, $thread, 'owned_state_lock_failed');
			return $callback($state);
		} finally {
			// Read-only role transaction: cleanup cannot mask an acknowledged Profile CAS.
			try { $db->query_once('ROLLBACK'); } catch (\Throwable $ignored) {}
			$this->close($db);
		}
	}
	private function open() {
		$db = null;
		try {
			list($db, $table) = call_user_func($this->connections);
			State_Schema::identifier($table);
			$thread = $db->thread_id();
			if (!$thread) { throw new Storage_Exception('storage_read_failed'); }
			State_Schema::verify($db, $table);
			$this->same_session($db, $thread, 'storage_read_failed');
			return array($db, $table, $thread);
		} catch (\Throwable $error) {
			if ($db !== null) { $this->close($db); }
			if ($error instanceof Storage_Exception) { throw $error; }
			throw new Storage_Exception('storage_read_failed');
		}
	}
	private function row($db, $table, $key, $lock = false) {
		$rows = State_Schema::rows($db, $db->prepare("SELECT record_id, schema_version, revision, payload FROM `$table` WHERE state_key = %s LIMIT 2" . ($lock ? ' LOCK IN SHARE MODE' : ''), $key));
		if (count($rows) > 1) { throw new Storage_Exception('owned_state_schema_unsupported'); }
		return $rows ? $rows[0] : null;
	}
	private function decode(array $row, $key) {
		if (!isset($row['record_id'], $row['schema_version'], $row['revision'], $row['payload']) || !preg_match('/\A[1-9][0-9]*\z/', (string)$row['record_id'])) { throw new Storage_Exception('malformed_storage'); }
		$state = (new Snapshot_Codec())->decode($row['payload']);
		if (!isset($state['schema_version'], $state['revision']) || !is_int($state['schema_version']) || !is_int($state['revision']) || (string)$state['schema_version'] !== (string)$row['schema_version'] || (string)$state['revision'] !== (string)$row['revision']) { throw new Storage_Exception('malformed_storage'); }
		if ($state['schema_version'] !== 1) { throw new Storage_Exception('owned_state_version_unsupported'); }
		if (!$this->valid($key, $state)) { throw new Storage_Exception($key === State_Schema::ROLES ? 'role_state_invalid' : 'malformed_storage'); }
		return $state;
	}
	private function valid($key, array $state) {
		return $key === State_Schema::THEME ? !(new Schema())->validate($state)['errors'] : Role_State::valid($state);
	}
	private function same_session($db, $thread, $code, $outcome = 'not_committed') {
		if (!$thread || $db->thread_id() !== $thread) { throw new Storage_Exception($code, $outcome); }
	}
	private function close($db) { try { $db->close(); } catch (\Throwable $ignored) {} }
}
