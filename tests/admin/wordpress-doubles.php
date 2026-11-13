<?php
/** Isolated WP API doubles: never load WordPress or access a database. */
$GLOBALS['pjh5pwp_test_cap']=true;
$GLOBALS['pjh5pwp_test_h5p_cap']=true;
$GLOBALS['pjh5pwp_test_multisite']=false;
$GLOBALS['pjh5pwp_test_hooks']=array();
$GLOBALS['pjh5pwp_test_assets']=array();
$GLOBALS['pjh5pwp_test_menus']=array();
function current_user_can($cap) { if('view_h5p_contents'===$cap){return $GLOBALS['pjh5pwp_test_h5p_cap'];}if('manage_options'!==$cap){throw new RuntimeException('Wrong capability');} return $GLOBALS['pjh5pwp_test_cap']; }
function is_multisite() { return $GLOBALS['pjh5pwp_test_multisite']; }
function wp_create_nonce($action) { return hash('sha256','test-user-session:' . $action); }
function wp_verify_nonce($nonce,$action) { return hash_equals(wp_create_nonce($action),$nonce); }
function __($text,$domain) { if('papijo-h5p-themer-wordpress'!==$domain){throw new RuntimeException('Wrong text domain');} return $text; }
function esc_html($text) { return htmlspecialchars($text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function esc_attr($text) { return esc_html($text); }
function esc_url($text) { return esc_html($text); }
function esc_html__($text,$domain) { return esc_html(__($text,$domain)); }
function wp_json_encode($value,$flags=0) { return json_encode($value,$flags); }
function get_bloginfo($what) { if('language'!==$what){throw new RuntimeException('Unexpected blog lookup');} return 'en-US'; }
function add_query_arg($args,$url) { $parts=explode('?',$url,2);$query=array();if(isset($parts[1])){parse_str($parts[1],$query);}return $parts[0] . '?' . http_build_query(array_merge($query,$args)); }
function add_action($hook,$callback,$priority=10,$args=1) { $GLOBALS['pjh5pwp_test_hooks'][$hook][]=array($callback,$priority,$args); }
function add_submenu_page($parent,$title,$label,$cap,$slug,$callback) { $GLOBALS['pjh5pwp_test_menus'][]=func_get_args();return $parent . '_page_' . $slug; }
function add_options_page($title,$label,$cap,$slug,$callback) { return add_submenu_page('options-general.php',$title,$label,$cap,$slug,$callback); }
function menu_page_url($slug,$echo=true) { return 'http://isolated.invalid/wp-admin/admin.php?page=' . $slug; }
function plugins_url($path,$file) { return 'http://isolated.invalid/pjh5pwp/' . $path; }
function wp_enqueue_style($handle,$src,$deps,$version) { $GLOBALS['pjh5pwp_test_assets'][]=array('style',$handle,$src); }
function wp_enqueue_script($handle,$src,$deps,$version,$footer) { $GLOBALS['pjh5pwp_test_assets'][]=array('script',$handle,$src); }
function wp_add_inline_script($handle,$data,$position='after') { $GLOBALS['pjh5pwp_test_inline']=array($handle,$data,$position); }
function wp_unslash($value) { return is_array($value)?array_map('wp_unslash',$value):stripslashes($value); }
function nocache_headers() { $GLOBALS['pjh5pwp_test_nocache']=true; }
function wp_die($message,$title='',$args=array()) { throw new RuntimeException('wp_die:' . (isset($args['response'])?$args['response']:'')); }
final class Pjh5pwp_Test_Redirect extends RuntimeException {}
function wp_safe_redirect($url,$status=302) { $GLOBALS['pjh5pwp_test_redirect']=array($url,$status);throw new Pjh5pwp_Test_Redirect(); }
function update_option(){throw new RuntimeException('No Options API writes');}
function get_option(){throw new RuntimeException('No foreign/options reads');}
function update_user_meta(){throw new RuntimeException('No profiles');}
function remove_action(){throw new RuntimeException('No foreign callback changes');}
