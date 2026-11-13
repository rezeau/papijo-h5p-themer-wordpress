<?php
/** Context adapter over the single compatibility policy. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Runtime;

final class Runtime_Admission {

	private $policy;
	public function __construct( callable $policy ) { $this->policy = $policy; }

	/** Preserve policy diagnostics and implementation status; add only context blockers. */
	public function evaluate( $frontend ) {
		$policy = call_user_func( $this->policy );
		if ( ! is_array( $policy ) || ! is_array( $policy['blockers'] ?? null ) ||
			true !== ( $policy['future_runtime_permitted'] ?? null ) || false !== ( $policy['runtime_suspended'] ?? null ) || $policy['blockers'] ) {
			return $policy;
		}
		if ( true !== $frontend ) {
			$policy['blockers'][] = 'unsupported_context';
			$policy['future_runtime_permitted'] = false;
			$policy['runtime_suspended'] = true;
			$policy['runtime_state'] = 'suspended';
		}
		return $policy;
	}
}
