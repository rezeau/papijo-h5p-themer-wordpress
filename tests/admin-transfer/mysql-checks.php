<?php
/** Included only inside the validated disposable import DB harness (--admin-ui). */
require dirname( __DIR__, 2 ) . '/includes/administration.php';
function current_user_can( $cap ) { return 'manage_options' === $cap; }
function wp_create_nonce( $action ) { return hash( 'sha256', 'disposable-user-session:' . $action ); }
function wp_verify_nonce( $nonce, $action ) { return hash_equals( wp_create_nonce( $action ), $nonce ); }
function wp_salt( $scheme ) { return str_repeat( 'disposable-test-secret-', 3 ); }
function get_current_user_id() { return 19; }
function wp_get_session_token() { return 'disposable-session'; }
$ui_start = $checks;
$ui_file = tempnam( sys_get_temp_dir(), 'pjh5pwp-ui-db-' );
try {
	$uploads = new \Papijo\H5PThemerWordPress\Admin\Transfer_Upload( static function ( $path ) use ( $ui_file ) { return $path === $ui_file; } );
	$new_ui = static function () use ( $store, $uploads ) { return new \Papijo\H5PThemerWordPress\Admin\Transfer_Admin( $store, null, $uploads ); };
	$preview_form = static function () use ( $store ) {
		$state = $store->read(); $revision = (string) $state['revision']; $context = \Papijo\H5PThemerWordPress\Admin\Admin_Service::context( $state );
		return array( 'pjh5pwp_op' => 'import_preview', 'revision' => $revision, 'context' => $context, 'nonce' => wp_create_nonce( \Papijo\H5PThemerWordPress\Admin\Transfer_Admin::nonce_action( 'import_preview', '', $revision, $context ) ) );
	};
	$confirm_form = static function ( $review ) { return array( 'pjh5pwp_op' => 'import_commit', 'review' => $review, 'confirm' => '1', 'nonce' => wp_create_nonce( \Papijo\H5PThemerWordPress\Admin\Transfer_Admin::nonce_action( 'import_commit', $review['signature'], $review['revision'], $review['context'] ) ) ); };
	$upload = static function ( $json ) use ( $ui_file ) { file_put_contents( $ui_file, $json ); return array( 'theme_json' => array( 'error' => UPLOAD_ERR_OK, 'size' => strlen( $json ), 'tmp_name' => $ui_file ) ); };
	$before = $store->read(); $json = mysql_wire( array( mysql_record( 'ui_first' ), mysql_record( 'ui_second' ) ) );
	$preview = $new_ui()->submit( $preview_form(), $upload( $json ) ); mysql_check( ! $preview['errors'] && isset( $preview['review'], $preview['plan'] ), 'UI builds authenticated preview using real store' );
	mysql_check( $before === $store->read(), 'UI preview leaves actual SQL aggregate unchanged' );
	$bad = $confirm_form( $preview['review'] ); $bad['review']['payload'] .= 'A'; $result = $new_ui()->submit( $bad, array() ); mysql_check( array( 'invalid_review' ) === $result['errors'] && $before === $store->read(), 'Forged UI review cannot write SQL' );
	$result = $new_ui()->submit( $confirm_form( $preview['review'] ), array() ); $after = $store->read();
	mysql_check( ! $result['errors'] && 2 === $result['summary']['count'] && $before['revision'] + 1 === $after['revision'], 'Cross-request UI confirmation writes one revision in actual database' );
	mysql_check( false === $after['publication']['themes']['custom:ui_first'] && false === $after['publication']['themes']['custom:ui_second'], 'UI confirmed bundle initially unpublished' );
	mysql_check( $json === mysql_ok( ( new \Papijo\H5PThemerWordPress\Transfer\Theme_JSON() )->export( $after, array( 'ui_first', 'ui_second' ) ) ), 'UI persistence/export colors roundtrip exact' );
	$preview = $new_ui()->submit( $preview_form(), $upload( mysql_wire( array( mysql_record( 'ui_stale' ) ) ) ) );
	$competing = new \Papijo\H5PThemerWordPress\Transfer\Import_Service( $store, static function () { return true; } ); mysql_ok( $competing->commit( mysql_ok( $competing->prepare( mysql_wire( array( mysql_record( 'ui_competing' ) ) ) ) ) ) );
	$result = $new_ui()->submit( $confirm_form( $preview['review'] ), array() ); mysql_check( array( 'storage_conflict' ) === $result['errors'] && ! isset( $store->read()['definitions']['ui_stale'] ), 'UI rejects genuine concurrent change after preview' );
	$preview = $new_ui()->submit( $preview_form(), $upload( mysql_wire( array( mysql_record( 'ui_fail_first' ), mysql_record( 'ui_fail_tail' ) ) ) ) ); $before = $store->read();
	mysql_check( $db->query( "ALTER TABLE pjh5pwp_options ADD CONSTRAINT ui_fail_tail CHECK (LOCATE('ui_fail_tail', option_value)=0)" ) !== false, 'Disposable SQL tail-failure constraint' );
	$result = $new_ui()->submit( $confirm_form( $preview['review'] ), array() ); mysql_check( array( 'import_write_failed' ) === $result['errors'] && $before === $store->read(), 'UI SQL failure cannot partially import first member' );
	$db->query( 'ALTER TABLE pjh5pwp_options DROP CHECK ui_fail_tail' );
	mysql_check( 'Theme=existing;Density=medium' === $db->get_var( "SELECT option_value FROM pjh5pwp_options WHERE option_name='profile_sentinel'" ) && 'unchanged' === $db->get_var( "SELECT option_value FROM pjh5pwp_options WHERE option_name='pjh5pwp_role_permissions'" ), 'UI import leaves preference and role sentinels unchanged' );
	echo 'PASS disposable administration transfer: ' . ( $checks - $ui_start ) . " additional assertions.\n";
} finally { if ( is_file( $ui_file ) ) { unlink( $ui_file ); } }
