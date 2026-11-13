<?php
/** F1 creation boundary: ONLY a random disposable pjh5pwp_f1_* database.
 * No production installation hook, migration, schema repair or user initialization.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Foundation\OwnedStorage;

use Papijo\H5PThemerWordPress\Storage\Storage_Exception;

final class Disposable_Installation {
	private $connections;
	public function __construct(callable $connections) { $this->connections = $connections; }
	public function status() { return $this->inspect(false); }
	public function ensure_installed() { return $this->inspect(true); }
	private function inspect($create) {
		$db = null; $attempted = false;
		try {
			list($db, $table) = call_user_func($this->connections);
			State_Schema::identifier($table);
			$thread = $db->thread_id();
			$database = State_Schema::rows($db, 'SELECT DATABASE() AS owned_state_database');
			if (count($database) !== 1 || !preg_match('/\Apjh5pwp_f1_[a-f0-9]{16}\z/', $database[0]['owned_state_database'] ?? '')) { throw new Storage_Exception('owned_state_disposable_database_required'); }
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
			try { State_Schema::verify($db, $table); $this->session($db, $thread, $attempted); }
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
