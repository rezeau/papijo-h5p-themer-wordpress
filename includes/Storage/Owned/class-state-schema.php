<?php
/** Exact owned-state schema. No automatic upgrade or WordPress-owned table operations.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;

use Papijo\H5PThemerWordPress\Storage\Storage_Exception;

final class State_Schema {
	const SUFFIX = 'pjh5pwp_state';
	const THEME = 'theme_library';
	const ROLES = 'role_permissions';

	public static function table_name($prefix) {
		if (!is_string($prefix) || !preg_match('/\A[A-Za-z0-9_]*\z/', $prefix)) { throw new Storage_Exception('owned_state_environment_unsupported'); }
		return self::identifier($prefix . self::SUFFIX);
	}
	public static function identifier($table) {
		if (!is_string($table) || strlen($table) > 64 || !preg_match('/\A[A-Za-z0-9_]*pjh5pwp_state\z/', $table)) { throw new Storage_Exception('owned_state_environment_unsupported'); }
		return $table;
	}
	public static function key($key) {
		if (!in_array($key, array(self::THEME, self::ROLES), true)) { throw new Storage_Exception('owned_state_key_unsupported'); }
	}
	public static function create_sql($table) {
		self::identifier($table);
		return "CREATE TABLE IF NOT EXISTS `$table` (record_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, state_key VARBINARY(32) NOT NULL, schema_version INT UNSIGNED NOT NULL, revision BIGINT UNSIGNED NOT NULL, payload LONGBLOB NOT NULL, PRIMARY KEY (record_id), UNIQUE KEY state_key (state_key)) ENGINE=InnoDB";
	}
	public static function rows($db, $sql) {
		try { $rows = $db->get_results($sql, 'ARRAY_A'); }
		catch (\Throwable $error) { throw new Storage_Exception('storage_read_failed'); }
		if (!is_array($rows) || !empty($db->last_error)) { throw new Storage_Exception('storage_read_failed'); }
		return $rows;
	}
	/** Metadata only. The caller owns and pins the private autocommit session. */
	public static function verify($db, $table) {
		self::identifier($table);
		$status = self::rows($db, $db->prepare('SHOW TABLE STATUS WHERE Name = %s', $table));
		if (count($status) !== 1) { throw new Storage_Exception('owned_state_installation_required'); }
		if (($status[0]['Engine'] ?? null) !== 'InnoDB') { throw new Storage_Exception('owned_state_engine_unsupported'); }
		if (self::rows($db, $db->prepare('SHOW TRIGGERS WHERE `Table` = %s', $table))) { throw new Storage_Exception('owned_state_schema_unsupported'); }
		$expected = array('record_id' => 'bigint unsigned', 'state_key' => 'varbinary(32)', 'schema_version' => 'int unsigned', 'revision' => 'bigint unsigned', 'payload' => 'longblob');
		$columns = self::rows($db, "SHOW FULL COLUMNS FROM `$table`");
		if (count($columns) !== count($expected)) { throw new Storage_Exception('owned_state_schema_unsupported'); }
		foreach ($columns as $column) {
			$name = $column['Field'] ?? '';
			$type = strtolower($column['Type'] ?? '');
			if ($name !== 'state_key') { $type = preg_replace('/\([0-9]+\)/', '', $type); }
			if (!isset($expected[$name]) || $type !== $expected[$name] || ($column['Null'] ?? '') !== 'NO' || ($column['Extra'] ?? '') !== ($name === 'record_id' ? 'auto_increment' : '') || ($column['Default'] ?? null) !== null) { throw new Storage_Exception('owned_state_schema_unsupported'); }
			unset($expected[$name]);
		}
		if ($expected) { throw new Storage_Exception('owned_state_schema_unsupported'); }
		$indexes = self::rows($db, "SHOW INDEX FROM `$table`");
		$found = array();
		foreach ($indexes as $index) {
			$name = $index['Key_name'] ?? '';
			$column = $name === 'PRIMARY' ? 'record_id' : ($name === 'state_key' ? 'state_key' : '');
			if (!$column || isset($found[$name]) || ($index['Column_name'] ?? '') !== $column || (int)($index['Non_unique'] ?? 1) !== 0 || (int)($index['Seq_in_index'] ?? 0) !== 1 || ($index['Sub_part'] ?? null) !== null || ($index['Index_type'] ?? '') !== 'BTREE') { throw new Storage_Exception('owned_state_schema_unsupported'); }
			$found[$name] = true;
		}
		if (count($found) !== 2) { throw new Storage_Exception('owned_state_schema_unsupported'); }
		$session = self::rows($db, 'SELECT @@session.autocommit AS owned_state_autocommit');
		if (count($session) !== 1 || (string)($session[0]['owned_state_autocommit'] ?? '') !== '1') { throw new Storage_Exception('owned_state_environment_unsupported'); }
	}
}
