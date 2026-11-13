<?php
/** Bounded JSON syntax decoding with duplicate member rejection. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Transfer;

final class JSON_Decoder {
	const MAX_BYTES = 1048576;
	const MAX_DEPTH = 16;
	public function decode( $json ) {
		if ( ! is_string( $json ) || strlen( $json ) > self::MAX_BYTES ) { return array( 'data' => null, 'errors' => array( 'json_size_or_type' ) ); }
		try {
			// JSON creates only data objects, never arbitrary PHP classes.
			$data = json_decode( $json, false, self::MAX_DEPTH, JSON_THROW_ON_ERROR );
			$offset = 0;
			$this->scan( $json, $offset );
			return array( 'data' => $data, 'errors' => array() );
		} catch ( \Throwable $error ) {
			return array( 'data' => null, 'errors' => array( $error instanceof \LogicException ? 'duplicate_json_member' : 'invalid_json' ) );
		}
	}
	private function whitespace( $json, &$offset ) {
		$length = strlen( $json );
		while ( $offset < $length && false !== strpos( " \r\n\t", $json[$offset] ) ) { ++$offset; }
	}
	/** The native decoder already verified grammar, Unicode and bounded depth. */
	private function string_token( $json, &$offset ) {
		$start = $offset++;
		while ( '"' !== $json[$offset] ) {
			if ( '\\' === $json[$offset] ) { ++$offset; }
			++$offset;
		}
		++$offset;
		return json_decode( substr( $json, $start, $offset - $start ), true, 2, JSON_THROW_ON_ERROR );
	}
	private function scan( $json, &$offset ) {
		$this->whitespace( $json, $offset );
		$char = $json[$offset];
		if ( '"' === $char ) { $this->string_token( $json, $offset ); return; }
		if ( '{' !== $char && '[' !== $char ) {
			$length = strlen( $json );
			while ( $offset < $length && false === strpos( " \r\n\t,]}", $json[$offset] ) ) { ++$offset; }
			return;
		}
		$object = '{' === $char; $end = $object ? '}' : ']'; $seen = array(); ++$offset;
		$this->whitespace( $json, $offset );
		if ( $end === $json[$offset] ) { ++$offset; return; }
		while ( true ) {
			$this->whitespace( $json, $offset );
			if ( $object ) {
				$key = ':' . $this->string_token( $json, $offset );
				if ( isset( $seen[$key] ) ) { throw new \LogicException(); }
				$seen[$key] = true;
				$this->whitespace( $json, $offset ); ++$offset; // colon
			}
			$this->scan( $json, $offset ); $this->whitespace( $json, $offset );
			if ( $end === $json[$offset] ) { ++$offset; return; }
			++$offset; // comma
		}
	}
}
