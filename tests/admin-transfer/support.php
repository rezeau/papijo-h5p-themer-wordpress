<?php
/** Disposable store and fixtures shared by UI and local HTTP tests. */
use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
final class Transfer_Test_Store implements Snapshot_Store {
	public $state; public $reads = 0; public $writes = 0; public $race; public $fault;
	public function __construct() {
		$this->state = Schema::initial();
		$this->state['definitions']['existing'] = array( 'schema_version' => 2, 'id' => 'existing', 'label' => 'Existing <theme>', 'density' => 'small', 'base_preset' => array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ), 'colors' => array() );
		$this->state['publication']['themes']['custom:existing'] = false;
	}
	public function read() { ++$this->reads; return $this->state; }
	public function compare_and_swap( $revision, array $next ) {
		if ( $this->race ) { $f = $this->race; $this->race = null; $f( $this ); }
		if ( $this->fault ) { throw new \Papijo\H5PThemerWordPress\Storage\Storage_Exception( 'private SQL credential', $this->fault ); }
		if ( $revision !== $this->state['revision'] || ( new Schema() )->transition( $this->state, $next )['errors'] ) { return false; }
		$this->state = $next; ++$this->writes; return true;
	}
}
function transfer_record( $id = 'ocean' ) { return array( 'id' => $id, 'label' => 'Ocean <script>alert("x")</script>', 'description' => "Description\n</td><script>bad()</script>", 'base_preset' => array( 'id' => 'mint', 'version' => Preset_Catalog::VERSION ), 'colors' => array( '--h5p-theme-background' => '#aBc' ) ); }
function transfer_json( $records = null ) { return ( new \Papijo\H5PThemerWordPress\Transfer\Theme_JSON() )->serialize( $records ?? array( transfer_record(), transfer_record( 'second' ) ) )['data']; }
function transfer_post( $store, $op, $extra = array() ) {
	$revision = (string) $store->state['revision']; $context = \Papijo\H5PThemerWordPress\Admin\Admin_Service::context( $store->state );
	$source = 'export' === $op ? ( 'single' === ( $extra['mode'] ?? '' ) ? $extra['id'] : 'bulk' ) : '';
	return array_merge( array( 'pjh5pwp_op' => $op, 'revision' => $revision, 'context' => $context, 'nonce' => wp_create_nonce( \Papijo\H5PThemerWordPress\Admin\Transfer_Admin::nonce_action( $op, $source, $revision, $context ) ) ), $extra );
}
function transfer_commit( $review ) { return array( 'pjh5pwp_op' => 'import_commit', 'confirm' => '1', 'review' => $review, 'nonce' => wp_create_nonce( \Papijo\H5PThemerWordPress\Admin\Transfer_Admin::nonce_action( 'import_commit', $review['signature'], $review['revision'], $review['context'] ) ) ); }
