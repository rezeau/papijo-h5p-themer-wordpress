<?php
/** Lazily loaded only inside WordPress, when an explicit storage operation runs. */
namespace Papijo\H5PThemerWordPress\Storage;

if ( ! class_exists( 'wpdb', false ) ) { return; }

/**
 * A private session avoids joining/committing another plugin's transaction.
 * Disable WordPress's fatal connection-error page; callers receive diagnostics.
 * @license GPL-3.0-or-later
 */
final class Owned_WPDB extends \wpdb {
	private $query_without_reconnect = false;
	public function db_connect( $allow_bail = true ) { return parent::db_connect( false ); }
	public function check_connection( $allow_bail = true ) {
		return $this->query_without_reconnect ? false : parent::check_connection( false );
	}
	/** Installer DDL must not be replayed on a new session with different SQL mode. */
	public function query_once( $query ) {
		$previous = $this->query_without_reconnect;
		$this->query_without_reconnect = true;
		try { return parent::query( $query ); }
		finally { $this->query_without_reconnect = $previous; }
	}
	public function bail( $message, $error_code = '500' ) {
		try { $this->close(); } catch ( \Throwable $ignored ) { /* Preserve a constant diagnostic. */ }
		throw new Storage_Exception( 'storage_read_failed' );
	}
	public function thread_id() {
		try { return $this->dbh instanceof \mysqli ? mysqli_thread_id( $this->dbh ) : 0; }
		catch ( \Throwable $error ) { return 0; }
	}
	public function error_number() {
		try { return $this->dbh instanceof \mysqli ? mysqli_errno( $this->dbh ) : 0; }
		catch ( \Throwable $error ) { return 0; }
	}
	public function close() {
		// wpdb::prepare() registers this session's own placeholder filter.
		remove_filter( 'query', array( $this, 'remove_placeholder_escape' ), 0 );
		return parent::close();
	}
}
