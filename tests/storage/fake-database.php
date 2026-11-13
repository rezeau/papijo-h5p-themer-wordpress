<?php
/** Isolated wpdb-shape double. SQL parsing is not a substitute for MySQL tests. */
namespace Papijo\H5PThemerWordPress\Tests;

final class Fake_Database {
	public $rows = array();
	public $next_id = 1;
	public $connections = 0;
	public $closed = 0;
	public $engine = 'InnoDB';
	public $status_rows;
	public $collation = 'utf8mb4_unicode_ci';
	public $unique = true;
	public $triggers = false;
	public $autoload = 1;
	public $fault;
	public $before_write;
	public $log = array();
	public function open() { return array( new Fake_Connection( $this ), 'pjh5pwp_test_options' ); }
}
final class Fake_Connection {
	public $last_error = '';
	private $state;
	private $thread;
	private $errno = 0;
	public function __construct( Fake_Database $state ) { $this->state = $state; $this->thread = ++$state->connections; }
	public function prepare( $sql, ...$args ) {
		return vsprintf( $sql, array_map( static function ( $value ) { return "'" . str_replace( "'", "\\'", (string) $value ) . "'"; }, $args ) );
	}
	public function get_results( $sql, $format ) {
		$this->state->log[] = $sql;
		if ( 'read' === $this->state->fault ) { $this->last_error = 'injected'; return null; }
		if ( 0 === strpos( $sql, 'SHOW TABLE STATUS' ) ) { return $this->state->status_rows ?? array( array( 'Engine' => $this->state->engine ) ); }
		if ( 0 === strpos( $sql, 'SHOW TRIGGERS' ) ) { return $this->state->triggers ? array( array( 'Trigger' => 'foreign_trigger' ) ) : array(); }
		if ( 0 === strpos( $sql, 'SHOW FULL COLUMNS' ) ) { return array( array( 'Field' => 'option_value', 'Type' => 'longtext', 'Collation' => $this->state->collation ), array( 'Field' => 'option_id', 'Type' => 'bigint unsigned', 'Collation' => null, 'Extra' => 'auto_increment' ) ); }
		if ( 0 === strpos( $sql, 'SHOW INDEX' ) ) { return $this->state->unique ? array( array( 'Non_unique' => 0, 'Key_name' => 'option_name', 'Column_name' => 'option_name', 'Sub_part' => null ), array( 'Non_unique' => 0, 'Key_name' => 'PRIMARY', 'Column_name' => 'option_id', 'Sub_part' => null ) ) : array(); }
		if ( 0 === strpos( $sql, 'SELECT @@session.autocommit' ) ) { return array( array( 'pjh5pwp_autocommit' => $this->state->autoload ) ); }
		if ( preg_match( "/^SELECT option_id, option_value .* WHERE option_name = '([^']+)' LIMIT 2(?: LOCK IN SHARE MODE)?$/", $sql, $match ) ) {
			return isset( $this->state->rows[ $match[1] ] ) ? array( $this->state->rows[ $match[1] ] ) : array();
		}
		throw new \RuntimeException( 'Unexpected read SQL' );
	}
	public function query( $sql ) {
		$this->state->log[] = $sql;
		if(in_array($sql,array('START TRANSACTION','ROLLBACK'),true)){return 0;}
		if ( $this->state->before_write ) { $callback = $this->state->before_write; $this->state->before_write = null; $callback(); }
		if ( 'write' === $this->state->fault ) { $this->errno = 1142; return false; }
		if ( preg_match( "/^INSERT .* VALUES \('([^']+)', UNHEX\('([a-f0-9]+)'\), 'off'\)$/", $sql, $m ) ) {
			if ( isset( $this->state->rows[ $m[1] ] ) ) { $this->errno = 1062; return false; }
			$this->state->rows[ $m[1] ] = array( 'option_id' => (string) $this->state->next_id++, 'option_value' => hex2bin( $m[2] ), 'autoload' => 'off' );
			$result = 1;
		} elseif ( preg_match( "/^UPDATE .* SET option_value = UNHEX\('([a-f0-9]+)'\), autoload = 'off' WHERE option_name = '([^']+)' AND option_id = '([0-9]+)' AND BINARY option_value = UNHEX\('([a-f0-9]+)'\)$/", $sql, $m ) ) {
			$row = isset( $this->state->rows[ $m[2] ] ) ? $this->state->rows[ $m[2] ] : null;
			if ( ! $row || $row['option_id'] !== $m[3] || $row['option_value'] !== hex2bin( $m[4] ) ) { return 0; }
			$this->state->rows[ $m[2] ]['option_value'] = hex2bin( $m[1] );
			$this->state->rows[ $m[2] ]['autoload'] = 'off';
			$result = 1;
		} else { throw new \RuntimeException( 'Unexpected write SQL' ); }
		if ( 'lost_ack' === $this->state->fault ) { $this->thread += 10000; return 0; }
		if ( 'after_commit' === $this->state->fault ) { throw new \RuntimeException( 'Connection failed after application' ); }
		return $result;
	}
	public function thread_id() { return $this->thread; }
	public function error_number() { return $this->errno; }
	public function close() { ++$this->state->closed; }
}
