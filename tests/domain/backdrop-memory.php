<?php
/**
 * Memory-only collaborators for unmodified Backdrop domain functions.
 * No Backdrop bootstrap, initialization, CMS writes, or database connection.
 */
class ConfigException extends RuntimeException {}
class ConfigValidateException extends RuntimeException {}
function t( $text, array $args = array() ) { return strtr( $text, $args ); }
class Config {
	private $name;
	public function __construct( $name, $storage = null ) { $this->name = $name; }
	public function isNew() { return ! array_key_exists( $this->name, $GLOBALS['pjh5pwp_backdrop_config'] ); }
	public function load() {}
	public function get( $key = null ) { return $this->getOriginal( $key ); }
	public function getOriginal( $key = null ) {
		$data = $this->isNew() ? array() : $GLOBALS['pjh5pwp_backdrop_config'][ $this->name ];
		return null === $key ? $data : ( array_key_exists( $key, $data ) ? $data[ $key ] : null );
	}
	public function getName() { return $this->name; }
	public function save() { throw new RuntimeException( 'Oracle may not write' ); }
}
function config( $name, $type = 'active' ) { return new Config( $name ); }
function config_get_names_with_prefix( $prefix ) {
	return array_values( array_filter( array_keys( $GLOBALS['pjh5pwp_backdrop_config'] ), static function ( $name ) use ( $prefix ) { return 0 === strpos( $name, $prefix ); } ) );
}
function config_get_config_storage() {
	return new class {
		public function exists( $name ) { return array_key_exists( $name, $GLOBALS['pjh5pwp_backdrop_config'] ); }
		public function read( $name ) { return $GLOBALS['pjh5pwp_backdrop_config'][ $name ]; }
		public function write() { throw new RuntimeException( 'Oracle may not write' ); }
	};
}
function backdrop_get_installed_schema_version( $module, $reset ) { return 1003; }
function state_initialize() { return $GLOBALS['pjh5pwp_backdrop_states']; }
function pjh5pwp_backdrop_snapshot( array $definitions, $default, $policy ) {
	$GLOBALS['pjh5pwp_backdrop_config'] = array( 'h5p_themer.settings' => array( 'schema_version' => 2, 'default_theme' => $default ) );
	foreach ( $definitions as $id => $definition ) { $GLOBALS['pjh5pwp_backdrop_config'][ 'h5p_themer.theme.' . $id ] = $definition; }
	if ( null !== $policy ) { $GLOBALS['pjh5pwp_backdrop_config']['h5p_themer.learner_visibility'] = $policy; }
	$snapshot = array( 'schema_version' => 1, 'themes' => array() );
	$GLOBALS['pjh5pwp_backdrop_states'] = array( 'h5p_themer.visibility_activation' => array(
		'schema_version' => 1, 'status' => 'complete', 'mode' => 'fresh', 'operation' => str_repeat( 'a', 32 ),
		'snapshot' => $snapshot, 'snapshot_hash' => hash( 'sha256', serialize( $snapshot ) ),
		'catalog_hash' => str_repeat( 'b', 64 ), 'cache_pending' => false, 'failure' => '',
	) );
}
