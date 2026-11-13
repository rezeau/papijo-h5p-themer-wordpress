<?php
/** Bounded temporary HTTP-upload reader; no move, include or permanent file. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Admin;
use Papijo\H5PThemerWordPress\Transfer\JSON_Decoder;
final class Transfer_Upload {
	private $uploaded;
	public function __construct( ?callable $uploaded = null ) { $this->uploaded = $uploaded ?: 'is_uploaded_file'; }
	public function read( $files ) {
		if ( ! is_array( $files ) || array_keys( $files ) !== array( 'theme_json' ) || ! is_array( $files['theme_json'] ) ) { return $this->error( 'upload_missing' ); }
		$file = $files['theme_json'];
		if ( ! isset( $file['error'] ) || ! is_int( $file['error'] ) ) { return $this->error( 'upload_failed' ); }
		if ( UPLOAD_ERR_INI_SIZE === $file['error'] || UPLOAD_ERR_FORM_SIZE === $file['error'] ) { return $this->error( 'upload_too_large' ); }
		if ( UPLOAD_ERR_NO_FILE === $file['error'] ) { return $this->error( 'upload_missing' ); }
		if ( UPLOAD_ERR_OK !== $file['error'] || ! isset( $file['tmp_name'], $file['size'] ) || ! is_string( $file['tmp_name'] ) || ! is_int( $file['size'] ) || $file['size'] < 0 ) { return $this->error( 'upload_failed' ); }
		if ( $file['size'] > JSON_Decoder::MAX_BYTES ) { return $this->error( 'upload_too_large' ); }
		try {
			if ( true !== call_user_func( $this->uploaded, $file['tmp_name'] ) ) { return $this->error( 'upload_failed' ); }
			$json = @file_get_contents( $file['tmp_name'], false, null, 0, JSON_Decoder::MAX_BYTES + 1 );
			if ( ! is_string( $json ) ) { return $this->error( 'upload_failed' ); }
			if ( strlen( $json ) > JSON_Decoder::MAX_BYTES ) { return $this->error( 'upload_too_large' ); }
			if ( strlen( $json ) !== $file['size'] ) { return $this->error( 'upload_failed' ); }
			return array( 'data' => $json, 'errors' => array() );
		} catch ( \Throwable $error ) { return $this->error( 'upload_failed' ); }
	}
	private function error( $code ) { return array( 'data' => null, 'errors' => array( $code ) ); }
}
