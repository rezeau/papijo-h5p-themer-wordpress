<?php
/** Production creation/verification of empty owned state.
 * No migration, schema repair or record initialization.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;

use Papijo\H5PThemerWordPress\Storage\Storage_Exception;

final class State_Installation {
	private $connections;
	public function __construct(?callable $connections = null) { $factory = new State_Connections(); $this->connections = $connections ?: array($factory, 'open'); }
	public function status() { return $this->run(false); }
	public function ensure_installed() { return $this->run(true); }
	private function run($create) {
		try { return array('state' => $this->inspect($create), 'errors' => array(), 'outcome' => $create ? 'confirmed' : 'not_attempted'); }
		catch (\Throwable $error) {
			$codes = array('owned_state_legacy_unsupported'=>'legacy_unsupported', 'owned_state_legacy_unverified'=>'unavailable', 'owned_state_engine_unsupported'=>'incompatible', 'owned_state_schema_unsupported'=>'incompatible', 'owned_state_key_unsupported'=>'incompatible', 'owned_state_version_unsupported'=>'incompatible', 'malformed_storage'=>'incompatible', 'role_state_invalid'=>'incompatible', 'owned_state_installation_unknown'=>'unknown', 'owned_state_installation_failed'=>'failed', 'owned_state_metadata_unavailable'=>'unavailable', 'owned_state_environment_unsupported'=>'unavailable', 'owned_state_innodb_unavailable'=>'unavailable', 'owned_state_installation_required'=>'unavailable', 'storage_environment_unsupported'=>'unavailable', 'storage_read_failed'=>'unavailable');
			$code = $error instanceof Storage_Exception && isset($codes[$error->getMessage()]) ? $error->getMessage() : 'storage_read_failed';
			$unknown = $error instanceof Storage_Exception && $error->outcome() === 'unknown';
			return array('state'=>$unknown?'unknown':$codes[$code], 'errors'=>array($code), 'outcome'=>$unknown?'unknown':'not_committed');
		}
	}
	private function inspect($create) {
		$db = null; $attempted = false;
		try {
			list($db, $table) = call_user_func($this->connections);
			State_Schema::identifier($table);
			$thread = $db->thread_id();
			$session = State_Schema::rows($db, 'SELECT @@session.autocommit AS owned_state_autocommit');
			if (count($session) !== 1 || (string)($session[0]['owned_state_autocommit'] ?? '') !== '1') { throw new Storage_Exception('owned_state_environment_unsupported'); }
			$status = State_Schema::rows($db, $db->prepare('SHOW TABLE STATUS WHERE Name = %s', $table));
			if (!$status) {
				try { $probe = $db->query_once("SELECT 1 FROM `$table` LIMIT 0"); }
				catch (\Throwable $error) { throw new Storage_Exception('owned_state_metadata_unavailable'); }
				if ($probe !== false || $db->error_number() !== 1146) { throw new Storage_Exception('owned_state_metadata_unavailable'); }
				$this->session($db, $thread);
				if (!$create) { return 'absent'; }
				$available = false;
				foreach (State_Schema::rows($db, 'SHOW ENGINES') as $engine) {
					if (strcasecmp($engine['Engine'] ?? '', 'InnoDB') === 0 && in_array(strtoupper($engine['Support'] ?? ''), array('YES', 'DEFAULT'), true)) { $available = true; }
				}
				if (!$available) { throw new Storage_Exception('owned_state_innodb_unavailable'); }
				if ($db->query_once("SET SESSION sql_mode = CONCAT_WS(',', NULLIF(@@session.sql_mode,''), 'NO_ENGINE_SUBSTITUTION')") === false) { throw new Storage_Exception('owned_state_environment_unsupported'); }
				$this->session($db, $thread); $attempted = true;
				try { $result = $db->query_once(State_Schema::create_sql($table)); }
				catch (\Throwable $error) { throw new Storage_Exception('owned_state_installation_unknown', 'unknown'); }
				$this->session($db, $thread, true);
				if ($result === false) {
					if (in_array($db->error_number(), array(0, 2006, 2013, 2055), true)) { throw new Storage_Exception('owned_state_installation_unknown', 'unknown'); }
					throw new Storage_Exception('owned_state_installation_failed');
				}
			}
			try { State_Schema::verify($db, $table); (new State_Table($this->connections))->verify_records($db, $table); $this->session($db, $thread, $attempted); }
			catch (\Throwable $error) {
				if ($attempted) { throw new Storage_Exception('owned_state_installation_unknown', 'unknown'); }
				throw $error;
			}
			return 'ready';
		} catch (Storage_Exception $error) { throw $error; }
		catch (\Throwable $error) { throw new Storage_Exception($attempted ? 'owned_state_installation_unknown' : 'storage_read_failed', $attempted ? 'unknown' : 'not_committed'); }
		finally { if ($db !== null) { try { $db->close(); } catch (\Throwable $ignored) {} } }
	}
	private function session($db, $thread, $unknown = false) {
		if (!$thread || $db->thread_id() !== $thread) { throw new Storage_Exception($unknown ? 'owned_state_installation_unknown' : 'owned_state_metadata_unavailable', $unknown ? 'unknown' : 'not_committed'); }
	}
}
