<?php
/** Explicit versioned migrations; no historical persisted version predates 1. */
namespace Papijo\H5PThemerWordPress\Storage;

/** @license GPL-3.0-or-later */
final class Schema_Migrations {
	private $steps;
	public function __construct( array $steps = array() ) {
		$this->steps = array();
		foreach ( $steps as $step ) {
			if ( ! $step instanceof Migration_Step || ! is_int( $step->from_version() ) || $step->from_version() < 0 ||
				$step->to_version() !== $step->from_version() + 1 || $step->to_version() > Schema::VERSION || isset( $this->steps[ $step->from_version() ] ) ) {
				throw new \InvalidArgumentException( 'invalid_migration_registration' );
			}
			$this->steps[ $step->from_version() ] = $step;
		}
	}
	public function prepare( $before ) {
		if ( ! is_array( $before ) || ! isset( $before['schema_version'], $before['revision'] ) || ! is_int( $before['schema_version'] ) ||
			! is_int( $before['revision'] ) || $before['revision'] < 0 || $before['revision'] === PHP_INT_MAX ) { return $this->error( 'malformed_storage' ); }
		if ( $before['schema_version'] > Schema::VERSION ) { return $this->error( 'newer_storage_schema' ); }
		if ( $before['schema_version'] === Schema::VERSION ) { return ( new Schema() )->validate( $before ); }
		$current = $before;
		try {
			while ( $current['schema_version'] < Schema::VERSION ) {
				$version = $current['schema_version'];
				if ( ! isset( $this->steps[ $version ] ) ) { return $this->error( 'unsupported_storage_schema' ); }
				$step = $this->steps[ $version ];
				$valid = $step->validate_source( $current );
				if ( ! is_array( $valid ) || ! isset( $valid['errors'] ) || $valid['errors'] || ! array_key_exists( 'data', $valid ) || $valid['data'] !== $current ) { return $this->error( 'migration_source_invalid' ); }
				$current = $step->convert( $current );
				if ( ! is_array( $current ) || ! isset( $current['schema_version'], $current['revision'] ) ||
					$current['schema_version'] !== $version + 1 || $current['revision'] !== $before['revision'] ) { return $this->error( 'migration_failed' ); }
			}
		} catch ( \Throwable $error ) { return $this->error( 'migration_failed' ); }
		++$current['revision'];
		return ( new Schema() )->validate( $current );
	}
	public function transition( $before, $next ) {
		if ( null === $before || ( isset( $before['schema_version'] ) && Schema::VERSION === $before['schema_version'] ) ) {
			return ( new Schema() )->transition( $before, $next );
		}
		$plan = $this->prepare( $before );
		if ( $plan['errors'] ) { return $plan; }
		return $plan['data'] === $next ? $plan : $this->error( 'migration_plan_mismatch' );
	}
	private function error( $code ) { return array( 'data' => null, 'errors' => array( $code ) ); }
}
