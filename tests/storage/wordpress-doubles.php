<?php
/** Minimal host functions for real wpdb tests; never bootstrap a WordPress site. */
$GLOBALS['pjh5pwp_test_filters'] = array();
$GLOBALS['pjh5pwp_test_cache'] = array();
function is_multisite() { return false; }
function get_current_blog_id() { return 1; }
function did_action( $hook ) { return 0; }
function wp_debug_backtrace_summary() { return 'pjh5pwp isolated database test'; }
function wp_load_translations_early() {}
function __( $text ) { return $text; }
function _doing_it_wrong( $function, $message, $version ) { throw new RuntimeException( 'wpdb contract violation: ' . $function ); }
function add_filter( $hook, $callback, $priority = 10, $arguments = 1 ) { $GLOBALS['pjh5pwp_test_filters'][$hook][$priority][] = $callback; }
function has_filter( $hook, $callback ) {
	foreach ( isset($GLOBALS['pjh5pwp_test_filters'][$hook]) ? $GLOBALS['pjh5pwp_test_filters'][$hook] : array() as $priority=>$callbacks ) {
		foreach($callbacks as $registered){if($registered===$callback){return $priority;}}
	}
	return false;
}
function remove_filter( $hook, $callback, $priority = 10 ) {
	if(!isset($GLOBALS['pjh5pwp_test_filters'][$hook][$priority])){return false;}
	foreach($GLOBALS['pjh5pwp_test_filters'][$hook][$priority] as $key=>$registered){if($registered===$callback){unset($GLOBALS['pjh5pwp_test_filters'][$hook][$priority][$key]);return true;}}
	return false;
}
function apply_filters( $hook, $value ) {
	$groups=isset($GLOBALS['pjh5pwp_test_filters'][$hook])?$GLOBALS['pjh5pwp_test_filters'][$hook]:array();ksort($groups);
	foreach($groups as $callbacks){foreach($callbacks as $callback){$value=call_user_func($callback,$value);}}
	return $value;
}
function wp_cache_delete( $key, $group = '' ) { $GLOBALS['pjh5pwp_test_cache'][]=array($key,$group); return true; }
