<?php
/**
 * Verify action emitters without invoking H5P services or altering callbacks.
 *
 * @package Papijo\H5PThemerWordPress
 * @license GPL-3.0-or-later
 */

namespace Papijo\H5PThemerWordPress\Compatibility;

defined( 'ABSPATH' ) || exit;

/** has_action() detects consumers; it cannot prove an integration emits a hook. */
final class Hook_Inspector {

	/** Find literal do_action_ref_array() calls in the actual loaded method. */
	public function emits_asset_actions( \ReflectionMethod $method ) {
		$file = $method->getFileName();
		if ( ! $file || ! is_readable( $file ) ) {
			return false;
		}
		$lines = file( $file );
		if ( false === $lines ) {
			return false;
		}
		$source = implode( '', array_slice( $lines, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1 ) );
		$tokens = array();
		foreach ( token_get_all( "<?php\n" . $source ) as $token ) {
			if ( is_array( $token ) && in_array( $token[0], array( T_OPEN_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$tokens[] = $token;
		}
		$found = array();
		foreach ( $tokens as $index => $token ) {
			if ( ! is_array( $token ) || T_STRING !== $token[0] || 'do_action_ref_array' !== strtolower( $token[1] ) ) {
				continue;
			}
			$previous = $index > 0 ? $tokens[ $index - 1 ] : null;
			if ( is_array( $previous ) && in_array( $previous[0], array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ), true ) ) {
				continue;
			}
			$argument = isset( $tokens[ $index + 2 ] ) ? $tokens[ $index + 2 ] : null;
			if ( ! isset( $tokens[ $index + 1 ], $tokens[ $index + 3 ] ) || '(' !== $tokens[ $index + 1 ] || ',' !== $tokens[ $index + 3 ] ||
				! is_array( $argument ) || T_CONSTANT_ENCAPSED_STRING !== $argument[0] ) {
				continue;
			}
			// Hook identifiers are literal ASCII; no evaluation/unescaping is needed.
			$found[] = substr( $argument[1], 1, -1 );
		}
		return in_array( 'h5p_alter_library_scripts', $found, true ) && in_array( 'h5p_alter_library_styles', $found, true );
	}
}
