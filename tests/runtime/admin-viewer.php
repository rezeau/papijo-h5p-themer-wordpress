<?php
/** Official class provenance + real appearance services; no CMS bootstrap/database. */
if ( 'cli' !== PHP_SAPI || empty( $argv[1] ) ) { exit( 1 ); }
ob_start(); require __DIR__ . '/run.php'; ob_end_clean();
require dirname( __DIR__, 2 ) . '/includes/runtime-wordpress.php';
use Papijo\H5PThemerWordPress\Runtime\WordPress_Context as Context;
use Papijo\H5PThemerWordPress\Runtime\Runtime_Admission;
use Papijo\H5PThemerWordPress\Runtime\Playback_Registry;
use Papijo\H5PThemerWordPress\Runtime\Theme_Attachment;
use Papijo\H5PThemerWordPress\Runtime\Appearance_Endpoint;
use Papijo\H5PThemerWordPress\Runtime\Appearance_Service;

define( 'WP_PLUGIN_DIR', dirname( realpath( $argv[1] ) ) );
define( 'PJH5PWP_FILE', dirname( __DIR__, 2 ) . '/papijo-h5p-themer-wordpress.php' );
$checks = 0; $viewer = 2; $admin_context = true; $ajax_context = false;
$screen = 'toplevel_page_h5p'; $hooks = array(); $assets = array();
$caps = array( 1 => array('view_h5p_contents','view_others_h5p_contents'), 2 => array('view_h5p_contents'), 3 => array() );
function plugin_dir_path($file) { return dirname($file) . '/'; }
require $argv[1] . '/autoloader.php';
foreach ( array('H5PCore','H5P_Plugin','H5P_Plugin_Admin','H5PContentAdmin') as $class ) { check(class_exists($class),'Official class loads without constructing services'); }
function is_admin() { return $GLOBALS['admin_context']; }
function wp_doing_ajax() { return $GLOBALS['ajax_context']; }
function get_current_user_id() { return $GLOBALS['viewer']; }
function current_user_can($cap) { return in_array($cap,$GLOBALS['caps'][get_current_user_id()]??array(),true); }
function get_current_screen() { return (object) array('id'=>$GLOBALS['screen']); }
function admin_url($path='') { return 'https://wp-test/wp-admin/' . $path; }
function home_url($path='/') { return 'https://wp-test' . $path; }
function wp_create_nonce($action) { return substr(hash('sha256',get_current_user_id().'|fixture-session|'.$action),0,10); }
function wp_verify_nonce($nonce,$action) { return hash_equals(wp_create_nonce($action),$nonce); }
function did_action($hook) { return is_admin()?0:1; }
function is_preview() { return false; } function is_customize_preview() { return false; }
function is_embed() { return false; } function is_feed() { return false; }
function add_action($hook,$callback,$priority=10,$args=1) { $GLOBALS['hooks'][$hook][]=array($callback,$priority,$args); }
function add_filter($hook,$callback,$priority=10,$args=1) { add_action($hook,$callback,$priority,$args); }
function wp_enqueue_script(...$args) { $GLOBALS['assets'][]=$args; }
function wp_enqueue_style(...$args) { $GLOBALS['assets'][]=$args; }
function plugins_url($path,$file) { return 'https://wp-test/papijo/'.$path; }
function esc_attr($value) { return htmlspecialchars($value,ENT_QUOTES,'UTF-8'); }
function esc_html__($text,$domain) { return esc_attr($text); }
function esc_attr__($text,$domain) { return esc_attr($text); }
function wp_json_encode($value) { return json_encode($value); }
function update_option() { throw new RuntimeException('No writes'); }
function update_user_meta() { throw new RuntimeException('No writes'); }
$wpdb = new class {
	public $prefix='wp_'; public $last_error=''; public $fail=false; public $reads=0;
	public $owners=array('10'=>2,'11'=>1,'12'=>2);
	public function prepare($sql,$id) { check(strpos($sql,'SELECT id, user_id FROM wp_h5p_contents WHERE id = %d LIMIT 1')===0,'Only fixed read-only identity query'); return (string)$id; }
	public function get_row($id) { ++$this->reads;if($this->fail){throw new RuntimeException('Unavailable database');}return isset($this->owners[$id])?(object)array('id'=>$id,'user_id'=>$this->owners[$id]):null; }
};
$GLOBALS['pagenow']='admin.php'; $GLOBALS['plugin_page']='h5p';
function page_request($id='10') {
	$_GET=array('page'=>'h5p','task'=>'show','id'=>$id);
	$_SERVER=array('REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/wp-admin/admin.php?page=h5p&task=show&id='.$id,'HTTP_SEC_FETCH_DEST'=>'document');
}
page_request();
same('10',Context::admin_viewer_id(),'Subscriber own official viewer admitted');
$viewer=1;same('10',Context::admin_viewer_id(),'Administrator with H5P capabilities admitted');
$viewer=3;same(null,Context::admin_viewer_id(),'Unauthorized Subscriber denied');
$viewer=0;same(null,Context::admin_viewer_id(),'Guest denied');
$viewer=2;
same(false,Context::admin_content_allowed('11'),'Other content denied without capability');
$caps[2][]='view_others_h5p_contents';same(true,Context::admin_content_allowed('11'),'Explicit other-content capability honored');
array_pop($caps[2]);
foreach(array('','0','01','-1','1.0','1e1','10%00','99999999999') as $id) { page_request($id);same(null,Context::admin_viewer_id(),'Invalid ID denied'); }
page_request('999');same(null,Context::admin_viewer_id(),'Missing content denied');page_request();
foreach(array('/wp-admin/profile.php','/wp-admin/admin.php?page=h5p','/wp-admin/admin.php?page=h5p&task=results&id=10',
	'/wp-admin/admin.php?page=h5p_new&task=show&id=10','/wp-admin/admin-ajax.php?action=h5p_embed&id=10',
	'/wp-admin/admin.php?page=h5p&task=show&id=10&id=11','/wp-admin/admin.php?page=h5p&task=show&id[]=10',
	'/wp-admin/admin.php?%70age=h5p&task=show&id=10','/wp-admin/admin.php?page=h5p&task=show&id=10&extra=1') as $uri) {
	$_SERVER['REQUEST_URI']=$uri;same(null,Context::admin_viewer_id(),'Unrelated or ambiguous route denied');
}
page_request();$screen='profile';same(null,Context::admin_viewer_id(),'Forged query on wrong actual screen denied');$screen='toplevel_page_h5p';
$GLOBALS['plugin_page']='foreign';same(null,Context::admin_viewer_id(),'Wrong registered plugin page denied');$GLOBALS['plugin_page']='h5p';
$ajax_context=true;same(null,Context::admin_viewer_id(),'AJAX cannot be a page attachment');$ajax_context=false;
$_SERVER['HTTP_SEC_FETCH_DEST']='iframe';same(null,Context::admin_viewer_id(),'Framed parent denied');page_request();
$wpdb->fail=true;same(null,Context::admin_viewer_id(),'Database read failure fails closed');$wpdb->fail=false;
$wpdb->last_error='failure';same(null,Context::admin_viewer_id(),'Database error fails closed');$wpdb->last_error='';
$api=H5PCore::$coreApi;H5PCore::$coreApi['minorVersion']=29;same(null,Context::admin_viewer_id(),'Unsupported Core denied');H5PCore::$coreApi=$api;

$policy=array('future_runtime_permitted'=>true,'runtime_suspended'=>false,'blockers'=>array());
$gate=new Runtime_Admission(static function()use(&$policy){return $policy;});
$registry=new Playback_Registry($gate,array(Context::class,'playback'),array(Context::class,'player_allowed'));
$content=array('id'=>10,'embedType'=>'div','library'=>array('id'=>2,'name'=>'H5P.Accordion','majorVersion'=>1,'minorVersion'=>0,'embedTypes'=>'div, iframe'),'metadata'=>array('title'=>'Fixture'));
$html='<div class="h5p-content" data-content-id="10"></div>';
$registry->observe($html,$content);
$second=$content;$second['id']=12;
$registry->observe('<div class="h5p-content" data-content-id="12"></div>',$second);
$forbidden=$content;$forbidden['id']=11;$registry->observe('<div class="h5p-content" data-content-id="11"></div>',$forbidden);
same(2,count($registry->candidates()),'Only distinct authorized players registered');
$attachment=new Theme_Attachment($registry,static function(){return true;});$attachment->register();$attachment->admin_enqueue();
same(2,count($assets),'Only viewer queues existing readiness CSS/controller');
$manifest=$attachment->manifest();same('10',$manifest['admin_viewer']['content_id'],'Nonce bound to actual route');
same(wp_create_nonce(Context::admin_nonce_action('10')),$manifest['admin_viewer']['nonce'],'Server-issued session nonce');
same(true,strpos($attachment->tag('<script src="fixture.js"></script>',Theme_Attachment::HANDLE),'data-pjh5pwp-admin-nonce=')!==false,'Head attachment supplies context proof');
ob_start();$attachment->admin_footer();$footer=ob_get_clean();same(true,strpos($footer,'admin_viewer')!==false,'Authorized footer manifest emitted');
$screen='profile';$assets=array();$attachment->admin_enqueue();same(array(),$assets,'Unrelated admin page queues nothing');$screen='toplevel_page_h5p';

$registered['subscriber']=array('name'=>'Subscriber','capabilities'=>array());$users[2]->roles=array('subscriber');
$role_store->grants=array('subscriber'=>true);save_fixture('builtin:mint','small',2);
$state=\Papijo\H5PThemerWordPress\Storage\Schema::initial();$state['settings']['default_theme']=builtin('lavender');
$service=new Appearance_Service(static function()use($gate){return $gate->evaluate(true);},array($profiles,'effective'));
$endpoint=new Appearance_Endpoint($service,'get_current_user_id');
$server=array('REQUEST_METHOD'=>'POST','HTTP_ORIGIN'=>'https://wp-test','HTTP_REFERER'=>'https://wp-test/wp-admin/admin.php?page=h5p&task=show&id=10',
	'HTTP_X_PJH5PWP_REQUEST'=>'appearance-v1','HTTP_SEC_FETCH_SITE'=>'same-origin','HTTP_SEC_FETCH_DEST'=>'empty','HTTP_SEC_FETCH_MODE'=>'same-origin');
$post=array('action'=>Appearance_Endpoint::ACTION,'request_id'=>str_repeat('a',32),'admin_content_id'=>'10',
	'admin_nonce'=>$manifest['admin_viewer']['nonce'],'admin_players'=>'10,12');
$request=static function($s=array(),$p=array(),$g=array())use($endpoint,&$server,&$post){return $endpoint->respond(array_replace($server,$s),$g,array_replace($post,$p),home_url(),admin_url());};
$r=$request();same(200,$r['status'],'Authorized admin transport succeeds');same('mint',$r['body']['appearance']['theme']['id'],'Existing personal Theme resolution');same('small',$r['body']['appearance']['density']['selection'],'Existing personal Density resolution');
$saved=$profile_store->values;$role_store->grants=array();$r=$request();same('lavender',$r['body']['appearance']['theme']['id'],'Revoked personal permission falls back globally');same('original',$r['body']['appearance']['density']['selection'],'Revocation preserves native Density');same($saved,$profile_store->values,'Revocation never rewrites saved preferences');
$state['settings']['default_theme']=null;same('core',$request()['body']['appearance']['theme']['kind'],'Core fallback retained');$role_store->grants=array('subscriber'=>true);
foreach(array(array('admin_nonce'=>'deadbeef00'),array('admin_content_id'=>'11'),array('admin_players'=>'10,11'),array('admin_players'=>'10,10'),array('admin_players'=>'12'),array('admin_players'=>'10,012'),array('admin_players'=>array()),array('user_id'=>1),array('theme'=>'builtin:dark')) as $bad) { same(403,$request(array(),$bad)['status'],'Forged grant/player/schema denied'); }
$viewer=1;same(403,$request()['status'],'Another session cannot reuse Subscriber nonce');
$viewer=0;same(403,$request()['status'],'Anonymous cannot reuse grant');
$viewer=2;$caps[2]=array();same(403,$request()['status'],'Revoked H5P viewing capability immediately denied');$caps[2]=array('view_h5p_contents');
$wpdb->owners['12']=1;same(403,$request()['status'],'Fresh authorization checks every player');$wpdb->owners['12']=2;
foreach(array(array('HTTP_ORIGIN'=>'https://other.test'),array('HTTP_SEC_FETCH_SITE'=>'cross-site'),array('HTTP_SEC_FETCH_DEST'=>'iframe'),
	array('HTTP_SEC_FETCH_MODE'=>'navigate'),array('HTTP_REFERER'=>'https://wp-test/wp-admin/profile.php')) as $bad) { same(403,$request($bad)['status'],'Transport origin/Fetch Metadata/context gates retained'); }
same(400,$request(array(),array(),array('extra'=>'1'))['status'],'Query injection denied');
$policy=array('future_runtime_permitted'=>false,'runtime_suspended'=>true,'blockers'=>array('snordian_active'));
same('snordian_active',$request()['body']['appearance']['admission']['reason'],'Fresh SNORDIAN policy suspends same appearance service');
same(null,$attachment->manifest(),'SNORDIAN suppresses admin attachment');
$policy=array('future_runtime_permitted'=>true,'runtime_suspended'=>false,'blockers'=>array());
$registry->observe($html,$content);same(1,count($registry->candidates()),'Duplicate ID revokes every occurrence');
same(null,$attachment->manifest(),'Missing main player prevents an admin grant manifest');
echo 'G2 admin viewer: '.$checks." assertions passed; official class files, in-memory access/profile stores; no WordPress bootstrap/database.\n";
