<?php
/** Single-row, byte-exact CAS on a standard WordPress options table. */
namespace Papijo\H5PThemerWordPress\Storage;

/** @license GPL-3.0-or-later */
final class WordPress_Snapshot_Store implements Snapshot_Store {
	private $connections;
	private $invalidate;
	private $migrations;
	public function __construct( $connections = null, $invalidate = null, ?Schema_Migrations $migrations = null ) {
		$factory = new WordPress_Connections();
		$this->connections = $connections ?: array( $factory, 'open' );
		$this->invalidate = $invalidate ?: static function () {
			foreach ( array( Schema::OPTION, 'alloptions', 'notoptions' ) as $key ) { wp_cache_delete( $key, 'options' ); }
		};
		$this->migrations = $migrations ?: new Schema_Migrations();
	}
	public function read() {
		list( $db, $table ) = $this->open();
		try {
			$row = $this->row( $db, $table );
			return null === $row ? null : ( new Snapshot_Codec() )->decode( $row['option_value'] );
		} finally { $this->close( $db ); }
	}
	public function compare_and_swap( $expected_revision, array $next ) {
		if ( null !== $expected_revision && ( ! is_int( $expected_revision ) || $expected_revision < 0 || PHP_INT_MAX === $expected_revision ) ) { return false; }
		if ( ( new Schema() )->validate( $next )['errors'] ) { return false; }
		list( $db, $table ) = $this->open();
		try {
			$row = $this->row( $db, $table );
			$before = null === $row ? null : ( new Snapshot_Codec() )->decode( $row['option_value'] );
			if ( ( null === $row ) !== ( null === $expected_revision ) ) { return false; }
			if ( null !== $before && ( ! isset( $before['revision'] ) || $before['revision'] !== $expected_revision ) ) { return false; }
			if ( $this->migrations->transition( $before, $next )['errors'] ) { return false; }
			$bytes = ( new Snapshot_Codec() )->encode( $next );
			// Hex transport prevents connection-charset rewriting of serialized lengths.
			if ( null === $row ) {
				$sql = $db->prepare( "INSERT INTO `$table` (option_name, option_value, autoload) VALUES (%s, UNHEX(%s), 'off')", Schema::OPTION, bin2hex( $bytes ) );
			} else {
				$sql = $db->prepare( "UPDATE `$table` SET option_value = UNHEX(%s), autoload = 'off' WHERE option_name = %s AND option_id = %s AND BINARY option_value = UNHEX(%s)",
					bin2hex( $bytes ), Schema::OPTION, $row['option_id'], bin2hex( $row['option_value'] ) );
			}
			$thread = $db->thread_id();
			try { $affected = $db->query( $sql ); }
			catch ( \Throwable $error ) { $this->unknown(); }
			if ( 1 !== $affected ) {
				// wpdb may reconnect and repeat a query after an acknowledgement is lost.
				if ( ! $thread || $db->thread_id() !== $thread ) { $this->unknown(); }
				if ( 0 === $affected || ( null === $row && false === $affected && 1062 === $db->error_number() ) ) { return false; }
				if ( false === $affected ) { throw new Storage_Exception( 'storage_write_failed' ); }
				$this->unknown();
			}
			try { call_user_func( $this->invalidate ); }
			catch ( \Throwable $error ) { throw new Storage_Exception( 'storage_cache_failed', 'committed' ); }
			return true;
		} finally { $this->close( $db ); }
	}
	private function open() {
		try { list( $db, $table ) = call_user_func( $this->connections ); }
		catch ( Storage_Exception $error ) { throw $error; }
		catch ( \Throwable $error ) { throw new Storage_Exception( 'storage_read_failed' ); }
		try {
			if ( ! is_string( $table ) || ! preg_match( '/\A[A-Za-z0-9_]+\z/', $table ) ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
			$status = $this->rows( $db, $db->prepare( 'SHOW TABLE STATUS WHERE Name = %s', $table ) );
			if ( count( $status ) !== 1 || ! isset( $status[0]['Engine'] ) || ! is_string( $status[0]['Engine'] ) || '' === $status[0]['Engine'] ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
			if ( 'InnoDB' !== $status[0]['Engine'] ) { throw new Storage_Exception( 'storage_options_engine_unsupported' ); }
			if ( $this->rows( $db, $db->prepare( 'SHOW TRIGGERS WHERE `Table` = %s', $table ) ) ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
			$columns = $this->rows( $db, "SHOW FULL COLUMNS FROM `$table`" );
			$value_ok = false;
			$id_ok = false;
			foreach ( $columns as $column ) {
				if ( 'option_value' === $column['Field'] && 'longtext' === strtolower( $column['Type'] ) && 0 === strpos( $column['Collation'], 'utf8mb4_' ) ) { $value_ok = true; }
				if ( 'option_id' === $column['Field'] && isset( $column['Extra'] ) && false !== strpos( $column['Extra'], 'auto_increment' ) ) { $id_ok = true; }
			}
			$indexes = array();
			foreach ( $this->rows( $db, "SHOW INDEX FROM `$table`" ) as $index ) {
				if ( 0 === (int) $index['Non_unique'] ) { $indexes[ $index['Key_name'] ][] = $index; }
			}
			$unique = false;
			$primary = false;
			foreach ( $indexes as $index ) {
				if ( 1 === count( $index ) && 'option_name' === $index[0]['Column_name'] && null === $index[0]['Sub_part'] ) { $unique = true; }
				if ( 1 === count( $index ) && 'PRIMARY' === $index[0]['Key_name'] && 'option_id' === $index[0]['Column_name'] && null === $index[0]['Sub_part'] ) { $primary = true; }
			}
			$session = $this->rows( $db, 'SELECT @@session.autocommit AS pjh5pwp_autocommit' );
			if ( ! $value_ok || ! $id_ok || ! $unique || ! $primary || count( $session ) !== 1 || 1 !== (int) $session[0]['pjh5pwp_autocommit'] ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
			return array( $db, $table );
		} catch ( \Throwable $error ) {
			$this->close( $db );
			if ( $error instanceof Storage_Exception ) { throw $error; }
			throw new Storage_Exception( 'storage_read_failed' );
		}
	}
	private function row( $db, $table ) {
		$rows = $this->rows( $db, $db->prepare( "SELECT option_id, option_value FROM `$table` WHERE option_name = %s LIMIT 2", Schema::OPTION ) );
		if ( count( $rows ) > 1 ) { throw new Storage_Exception( 'storage_environment_unsupported' ); }
		if ( ! $rows ) { return null; }
		if ( ! isset( $rows[0]['option_id'], $rows[0]['option_value'] ) || ! preg_match( '/\A[1-9][0-9]*\z/', (string) $rows[0]['option_id'] ) ) { throw new Storage_Exception( 'malformed_storage' ); }
		return $rows[0];
	}
	private function rows( $db, $sql ) {
		try { $rows = $db->get_results( $sql, 'ARRAY_A' ); }
		catch ( \Throwable $error ) { throw new Storage_Exception( 'storage_read_failed' ); }
		if ( ! is_array( $rows ) || ! empty( $db->last_error ) ) { throw new Storage_Exception( 'storage_read_failed' ); }
		return $rows;
	}
	private function unknown() {
		try { call_user_func( $this->invalidate ); } catch ( \Throwable $ignored ) { /* The write outcome is still unknown. */ }
		throw new Storage_Exception( 'storage_write_unknown', 'unknown' );
	}
	private function close( $db ) {
		try { $db->close(); } catch ( \Throwable $ignored ) { /* Do not mask an acknowledged write or its diagnostic. */ }
	}
}
