<?php
/** Phase 2A: real appearance/publication services, in-memory WP seams; no database. */
ob_start(); require __DIR__.'/run.php'; ob_end_clean();
require dirname(__DIR__,2).'/includes/runtime-wordpress.php';
require dirname(__DIR__,2).'/includes/Anonymous/class-permission.php';
use Papijo\H5PThemerWordPress\Anonymous\Permission;
use Papijo\H5PThemerWordPress\Anonymous\Public_Appearance;
use Papijo\H5PThemerWordPress\Anonymous\Player_Grants;
use Papijo\H5PThemerWordPress\Anonymous\Appearance_Endpoint;
use Papijo\H5PThemerWordPress\Runtime\Delivery_Mode;
$checks=0; $option=false; $writes=0; $cap=true;
function get_option($key,$default=false){global $option; same(Permission::OPTION,$key,'Dedicated option only');return $option;}
function update_option($key,$value,$autoload){global $option,$writes;same(Permission::OPTION,$key,'Dedicated write');same(false,$autoload,'Not autoloaded');$writes++;$option=$value;return true;}
function current_user_can($capability){global $cap;same('manage_options',$capability,'Administrator boundary');return $cap;}
function wp_create_nonce($action){return substr(hash('sha256',$action),0,10);}
function wp_verify_nonce($nonce,$action){return $nonce===wp_create_nonce($action);}
function wp_json_encode($value){return json_encode($value);}
function __($s,$d){return $s;}
function esc_html($s){return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return esc_html($s);}
$p=new Permission();same(false,$p->enabled(),'Default off');
foreach(array(null,0,1,'true',array(true)) as $bad){$option=$bad;same(false,$p->enabled(),'Malformed permission fails closed');}
$option='1';same(true,$p->enabled(),'WordPress database boolean representation');
$option=false;
$post=array('pjh5pwp_op'=>'anonymous_permission','allow_anonymous'=>'1','anonymous_nonce'=>wp_create_nonce('pjh5pwp_anonymous_permission'),'anonymous_complete'=>'1');
$cap=false;same('forbidden',$p->submit($post),'Unauthorized');$cap=true;
$multisite=true;same('forbidden',$p->submit($post),'Multisite');$multisite=false;
same('invalid_nonce',$p->submit(array_replace($post,array('anonymous_nonce'=>'bad'))),'Nonce');
foreach(array(true,false,1,0,array('1'),'yes',null) as $bad){same('invalid_submission',$p->submit(array_replace($post,array('allow_anonymous'=>$bad))),'Strict input');}
$partial=$post;unset($partial['anonymous_complete']);same('invalid_submission',$p->submit($partial),'Truncated form');
same('invalid_submission',$p->submit($post+array('user_id'=>'2')),'Unknown field');same(0,$writes,'No rejected writes');
same('saved',$p->submit($post),'Enable');same(true,$p->enabled(),'Enabled boolean');
same('saved',$p->submit(array_replace($post,array('allow_anonymous'=>'0'))),'Disable');same(false,$p->enabled(),'Disabled boolean');
$html=$p->render('https://example.test/');check(strpos($html,Permission::LABEL)!==false,'Label');check(strpos($html,'starts collapsed')!==false,'Collapsed selector explained');
foreach(array(false,true) as $permission){same('authenticated',Delivery_Mode::select(2,$permission),'Authenticated always isolated');same($permission?'anonymous':'default',Delivery_Mode::select(0,$permission),'Permission selects exclusive runtime');}
same('none',Delivery_Mode::select(-1,true),'Invalid identity');
$state=\Papijo\H5PThemerWordPress\Storage\Schema::initial();
$state['definitions']['example']=definition();$state['settings']['default_theme']=array('kind'=>'custom','id'=>'example');
$policy=array('future_runtime_permitted'=>true,'runtime_suspended'=>false,'blockers'=>array());
$reads=$profile_store->reads;$before=serialize($profile_store->values);
$public=new Public_Appearance(static function()use(&$state){return $state;},static function()use(&$policy){return $policy;});
$result=$public->resolve('site_default','large');same(array(),$result['errors'],'Unpublished global usable');same(null,$result['appearance']['theme']['id'],'Global identity redacted');
check(strpos(json_encode($result),'Private label')===false,'Unpublished label absent');
$result=$public->resolve('custom:example','small');same('site_default',$result['selection']['theme'],'Unpublished selection falls back');
$state['publication']['themes']['custom:example']=true;
$result=$public->resolve('custom:example','small');same('custom:example',$result['selection']['theme'],'Published custom selectable');
$state['publication']['themes']['custom:example']=false;
same(true,$public->resolve('custom:example','original')['selection']['theme_fallback'],'Unpublication respected');
same($reads,$profile_store->reads,'Anonymous never reads profiles');same($before,serialize($profile_store->values),'No profile mutation');
$server=array('REQUEST_METHOD'=>'POST','HTTP_ORIGIN'=>'https://example.test','HTTP_REFERER'=>'https://example.test/lesson/?p=2','HTTP_X_PJH5PWP_REQUEST'=>'appearance-v1','CONTENT_TYPE'=>'application/x-www-form-urlencoded');
$player=array('content_id'=>'12','embed'=>'iframe','library'=>'H5P.TrueFalse 1.8');$page=Player_Grants::page($server['HTTP_REFERER']);
$token=Player_Grants::issue($page,array($player))[0]['nonce'];
same(null,Player_Grants::issue($page,array($player,$player)),'Repeated ID refused');
$post=array('action'=>Appearance_Endpoint::ACTION,'request_id'=>str_repeat('a',32),'player'=>json_encode($player),'nonce'=>$token,'theme'=>'site_default','density'=>'original');
$enabled=false;$runtime=true;$viewer=0;
$endpoint=new Appearance_Endpoint($public,static function()use(&$enabled){return $enabled;},static function()use(&$runtime){return $runtime;},static function()use(&$viewer){return $viewer;});
function request_anon($server_patch=array(),$post_patch=array(),$get=array()){global $endpoint,$server,$post;return $endpoint->respond(array_replace($server,$server_patch),$get,array_replace($post,$post_patch),'https://example.test/','https://example.test/wp-admin/');}
same('anonymous_disabled',request_anon()['body']['reason'],'Permission boundary');$enabled=true;
same(200,request_anon()['status'],'Valid request');same(null,request_anon()['body']['appearance']['theme']['id'],'Transport redaction');
foreach(array(array('nonce'=>'aaaaaaaaaa'),array('player'=>json_encode(array_replace($player,array('content_id'=>'13'))))) as $patch){same(403,request_anon(array(),$patch)['status'],'Bound nonce');}
foreach(array(array('theme'=>array('core')),array('density'=>'huge'),array('user_id'=>'2'),array('player'=>str_repeat('x',513)),array('request_id'=>'bad')) as $patch){same(400,request_anon(array(),$patch)['status'],'Strict bounded request');}
foreach(array(array('HTTP_ORIGIN'=>'https://foreign.test'),array('HTTP_REFERER'=>'https://example.test/wp-admin/'),array('HTTP_X_PJH5PWP_REQUEST'=>'bad')) as $patch){same(403,request_anon($patch)['status'],'Origin/context');}
same(405,request_anon(array('REQUEST_METHOD'=>'GET'))['status'],'POST only');same(400,request_anon(array(),array(),array('x'=>'1'))['status'],'GET rejected');
$viewer=2;$post['nonce']='aaaaaaaaaa';same(409,request_anon()['status'],'Identity precedes nonce');same(null,request_anon()['body']['appearance'],'No authenticated data');$viewer=0;$post['nonce']=$token;
$runtime=false;same(403,request_anon()['status'],'Fresh runtime suspension');$runtime=true;
$policy['blockers']=array('snordian_active');$policy['runtime_suspended']=true;$policy['future_runtime_permitted']=false;same(403,request_anon()['status'],'Shared SNORDIAN policy');
$production=new Appearance_Endpoint($public,static function(){return true;},static function(){return true;},static function(){return 0;});
same('appearance_unavailable',$production->respond($server,array(),$post,'https://example.test/','https://example.test/wp-admin/')['body']['reason'],'Production uses shared suspended policy');
echo "Phase 2A: $checks assertions passed; no WordPress bootstrap or database.\n";
