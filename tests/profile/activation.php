<?php
/** Activation/retry/notice contracts through real plugin composition; memory fixtures only. */
if(PHP_SAPI!=='cli'){exit;}
$argv=array(__FILE__,'compatible');ob_start();require dirname(__DIR__).'/worker.php';ob_end_clean();
require __DIR__.'/installation-fixture.php';
use Papijo\H5PThemerWordPress\Plugin;
use Papijo\H5PThemerWordPress\Profile\Profile_Store;
use Papijo\H5PThemerWordPress\Profile\Profile_Installation;
use Papijo\H5PThemerWordPress\Profile\Profile_Storage_Admin;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
function esc_attr($s){return esc_html($s);}function esc_url($s){return esc_html($s);}
function admin_url($s){return 'https://fixture.invalid/wp-admin/'.$s;}
$actor=1;function wp_create_nonce($s){global $actor;return hash('sha256',$actor.':'.$s);}function wp_verify_nonce($n,$s){return hash_equals(wp_create_nonce($s),$n);}
function wp_unslash($s){return stripslashes($s);}function add_query_arg($a,$u){return $u.(strpos($u,'?')===false?'?':'&').http_build_query($a);}
final class Setup_Denied extends RuntimeException {public $status;public function __construct($s){$this->status=$s;}}
final class Setup_Redirect extends RuntimeException {public $url,$status;public function __construct($u,$s){$this->url=$u;$this->status=$s;}}
function wp_die($m,$title='',$args=array()){throw new Setup_Denied($args['response']);}
function wp_safe_redirect($u,$s){throw new Setup_Redirect($u,$s);}
$checks=0;function ccheck($v,$m){global $checks;++$checks;if(!$v){throw new RuntimeException($m);}}
function service_for($f){return new Profile_Installation(new Profile_Store(array($f,'open')));}
function render_setup($admin){ob_start();$admin->render();return ob_get_clean();}
function assert_readonly($f){foreach($f->log as $sql){ccheck(preg_match('/\A(?:SELECT|SHOW)\b/',$sql)===1,'Ordinary request SQL is read-only');}}
$f=new Installation_Fixture();$service=service_for($f);$property=new ReflectionProperty(Plugin::class,'profile_installation');$property->setAccessible(true);$property->setValue(Plugin::instance(),$service);
$GLOBALS['pjh5pwp_options']['active_plugins'][]='snordians-h5p-themer/snordians-h5p-themer.php';$savedOptions=$GLOBALS['pjh5pwp_options'];
$callback=$GLOBALS['pjh5pwp_activations'][PJH5PWP_FILE];ob_start();$result=call_user_func($callback);$activationOutput=ob_get_clean();
ccheck($result['state']==='ready'&&$f->creates===1&&$f->exists&&$f->records===array(),'Activation creates one empty table even with SNORDIAN');ccheck($activationOutput==='','Activation never emits output');ccheck(Plugin::instance()->compatibility()->evaluate()['runtime_suspended'],'SNORDIAN remains suspended');
$f->records[7]=array('schema_version'=>1,'revision'=>73,'state'=>serialize(array('schema_version'=>1,'revision'=>73,'values'=>array_replace(Profile_State::defaults(),array('theme'=>'builtin:mint','density'=>'large')))));
$saved=serialize($f->records);$f->log=array();ccheck(Plugin::activate()['state']==='ready'&&$f->creates===1,'Repeated activation has no CREATE');ccheck(serialize($f->records)===$saved,'Activation preserves exact revisions and bytes');assert_readonly($f);
foreach(array('denied','metadata','engine') as $fault){$f=new Installation_Fixture();if($fault==='denied'){$f->fault='denied';}if($fault==='metadata'){$f->metadata=false;}if($fault==='engine'){$f->exists=true;$f->engine='MyISAM';}$service=service_for($f);$property->setValue(Plugin::instance(),$service);ob_start();$r=Plugin::activate();$out=ob_get_clean();ccheck(in_array($r['state'],array('failed','unavailable','incompatible'),true)&&$out==='','Activation remains degraded without output/throw: '.$fault);ccheck($f->records===array()&&($fault!=='engine'||$f->creates===0),'Failure does not repair/seed data');}
$factoryCalls=0;$admin=new Profile_Storage_Admin(static function()use(&$service,&$factoryCalls){++$factoryCalls;return $service;});
$f=new Installation_Fixture();$service=service_for($f);$GLOBALS['pjh5pwp_screen']='plugins';$_GET=array();$_POST=array();
Plugin::boot();$html=render_setup($admin);ccheck(strpos($html,'Retry Profile storage setup')!==false&&!$f->exists&&$f->creates===0,'Update without activation offers explicit retry, never installs');assert_readonly($f);
foreach(array('settings_page_pjh5pwp-themes','h5p_page_pjh5pwp-themes','contenu-h5p_page_pjh5pwp-themes','plugins') as $screen){$GLOBALS['pjh5pwp_screen']=$screen;ccheck(strpos(render_setup($admin),'pjh5pwp-profile-storage')!==false,'Relevant admin scope: '.$screen);}
foreach(array('dashboard','profile','plugins_page_other','settings_page_pjh5pwp-themes-other',null) as $screen){$before=$factoryCalls;$GLOBALS['pjh5pwp_screen']=$screen;ccheck(render_setup($admin)===''&&$before===$factoryCalls,'Unrelated screens perform no setup checks');}
$GLOBALS['pjh5pwp_screen']='plugins';$GLOBALS['pjh5pwp_can_manage']=false;$before=$factoryCalls;ccheck(render_setup($admin)===''&&$before===$factoryCalls,'Learners receive no diagnostic or metadata reads');$GLOBALS['pjh5pwp_can_manage']=true;
$_GET=array(Profile_Storage_Admin::RESULT=>'ready');$html=render_setup($admin);ccheck(strpos($html,'notice-success')===false&&strpos($html,'Retry Profile storage setup')!==false,'Forged success cannot override current absence');
$_GET=array(Profile_Storage_Admin::RESULT=>array('<script>SECRET</script>'));ccheck(strpos(render_setup($admin),'SECRET')===false,'Array result parameter ignored');$_GET=array();
function expect_denied($admin,$status){global $factoryCalls;$before=$factoryCalls;try{$admin->retry();throw new RuntimeException('Expected setup denial');}catch(Setup_Denied $e){ccheck($e->status===$status,'Correct denied HTTP status');}ccheck($factoryCalls===$before,'Unauthorized request cannot reach installation factory');}
$_SERVER['REQUEST_METHOD']='GET';$_POST=array(Profile_Storage_Admin::NONCE=>wp_create_nonce(Profile_Storage_Admin::ACTION));expect_denied($admin,405);
$_SERVER['REQUEST_METHOD']='POST';foreach(array(null,'bad',array('bad'),9) as $nonce){$_POST=array(Profile_Storage_Admin::NONCE=>$nonce);expect_denied($admin,403);}
$_POST=array(Profile_Storage_Admin::NONCE=>wp_create_nonce(Profile_Storage_Admin::ACTION));$GLOBALS['pjh5pwp_can_manage']=false;expect_denied($admin,403);$GLOBALS['pjh5pwp_can_manage']=true;
$actor=2;expect_denied($admin,403);$actor=1;
ccheck(!isset($GLOBALS['pjh5pwp_hooks']['admin_post_nopriv_'.Profile_Storage_Admin::ACTION]),'No anonymous retry route');
foreach(array('themes','https://evil.invalid/',array('themes')) as $return){
    $_POST=array(Profile_Storage_Admin::NONCE=>wp_create_nonce(Profile_Storage_Admin::ACTION),'pjh5pwp_profile_setup_return'=>$return);
    try{$admin->retry();throw new RuntimeException('Expected redirect');}catch(Setup_Redirect $r){ccheck($r->status===303&&strpos($r->url,'https://fixture.invalid/wp-admin/')===0,'POST uses local 303 redirect');ccheck(strpos($r->url,$return==='themes'?'admin.php?page=pjh5pwp-themes':'plugins.php')!==false,'Allowlisted return destination');}
}
ccheck($f->exists&&$f->creates===1&&$f->records===array(),'Authorized retries create once, no user rows');$_GET=array(Profile_Storage_Admin::RESULT=>'ready');$f->log=array();$html=render_setup($admin);ccheck(strpos($html,'notice-success')!==false&&strpos($html,'<form')===false,'Confirmed ready state after retry');assert_readonly($f);
$f=new Installation_Fixture();$f->fault='lost_ack';$service=service_for($f);$_POST=array(Profile_Storage_Admin::NONCE=>wp_create_nonce(Profile_Storage_Admin::ACTION));try{$admin->retry();}catch(Setup_Redirect $r){ccheck(strpos($r->url,'=unknown')!==false,'Uncertain retry result is explicit');}ccheck($f->creates===1&&$f->exists,'Unknown does not retry/drop');$f->fault='';$_GET=array(Profile_Storage_Admin::RESULT=>'unknown');ccheck(strpos(render_setup($admin),'notice-success')!==false&&$f->creates===1,'Read-only status reconciles uncertain completed setup');
$bad=new Profile_Storage_Admin(static function(){throw new RuntimeException('SECRET credentials <script>');});$_GET=array();$html=render_setup($bad);ccheck(strpos($html,'SECRET')===false&&strpos($html,'<script>')===false,'Factory failures sanitized');
$GLOBALS['pjh5pwp_multisite']=true;$before=$factoryCalls;$html=render_setup($admin);ccheck(strpos($html,'not supported on multisite')!==false&&strpos($html,'<form')===false&&$factoryCalls===$before,'Multisite warning never opens storage');expect_denied($admin,403);
$property->setValue(Plugin::instance(),$service);$connections=$f->connections;ccheck(Plugin::activate()['outcome']==='not_attempted'&&$f->connections===$connections,'Multisite activation skips installer');$GLOBALS['pjh5pwp_multisite']=false;ccheck(Plugin::activate(true)['outcome']==='not_attempted'&&$f->connections===$connections,'Network activation skips installer');
ccheck($savedOptions===$GLOBALS['pjh5pwp_options'],'Theme/role/activation/SNORDIAN options never changed by our handlers');
foreach(array_keys($GLOBALS['pjh5pwp_hooks']) as $hook){ccheck(strpos($hook,'deactivate_')!==0,'No destructive deactivation callback');}
echo 'Stage C Profile lifecycle: '.$checks." assertions passed; real composition, disposable memory fixtures, no CMS/database.\n";
