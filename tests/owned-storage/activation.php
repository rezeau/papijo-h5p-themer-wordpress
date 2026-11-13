<?php
/** F2 production setup/legacy/diagnostic contracts; no native database or CMS. */
if(PHP_SAPI!=='cli'){exit;}
ob_start();require dirname(__DIR__).'/profile/activation.php';ob_end_clean();
require __DIR__.'/fake-database.php';
use Papijo\H5PThemerWordPress\Plugin;
use Papijo\H5PThemerWordPress\Storage\Owned\State_Installation;
use Papijo\H5PThemerWordPress\Storage\Owned\State_Storage_Admin;
use Papijo\H5PThemerWordPress\Storage\Owned\Legacy_Guard;
use Papijo\H5PThemerWordPress\Tests\OwnedStorage\Database;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
$f2_checks=0;function f2_check($v,$m){global $f2_checks;++$f2_checks;if(!$v){throw new RuntimeException($m);}}
function f2_guarded($f){return static function()use($f){list($db,$table)=$f->open();try{Legacy_Guard::verify($db,'wp_options');return array($db,$table);}catch(Throwable $e){$db->close();throw $e;}};}
$state_property=new ReflectionProperty(Plugin::class,'state_installation');$state_property->setAccessible(true);
$f=new Database();$f->exists=false;$state=new State_Installation(f2_guarded($f));$state_property->setValue(Plugin::instance(),$state);
f2_check($state->status()['state']==='absent'&&!$f->exists,'Ordinary status confirms absence without creating');
$saved_options=$GLOBALS['pjh5pwp_options'];Plugin::activate();f2_check($f->exists&&$f->records===array(),'Actual activation installs empty state without preference initialization');
$before=$f->log;Plugin::activate();f2_check(count(array_filter($f->log,static function($sql){return strpos($sql,'CREATE TABLE')===0;}))===1,'Repeated activation validates without CREATE');
$admin=new State_Storage_Admin(static function()use(&$state){return $state;});$GLOBALS['pjh5pwp_screen']='plugins';$_GET=array();f2_check(render_setup($admin)==='','Ready state has no setup notice');
foreach(array('pjh5pwp_theme_library','pjh5pwp_role_permissions') as $key){
	$f=new Database();$f->exists=false;$f->legacy=array($key);$state=new State_Installation(f2_guarded($f));$state_property->setValue(Plugin::instance(),$state);
	f2_check($state->ensure_installed()['state']==='legacy_unsupported'&&!$f->exists,'Either legacy option blocks automatic creation');Plugin::activate();f2_check(!$f->exists&&$f->records===array()&&$f->legacy===array($key),'Activation neither migrates nor clears old state');
	$html=render_setup($admin);f2_check(strpos($html,'does not migrate legacy state')!==false&&strpos($html,'<form')===false,'Legacy refusal has explicit diagnosis and no reset/retry action');
}
$f=new Database();$f->exists=false;$f->fault='metadata';$state=new State_Installation(f2_guarded($f));f2_check($state->ensure_installed()['errors']===array('owned_state_legacy_unverified')&&!$f->exists,'Unverified legacy absence cannot authorize creation');
foreach(array('create_denied'=>'failed','create_ack'=>'unknown','create_throw'=>'unknown','engine_unavailable'=>'unavailable') as $fault=>$expected){$f=new Database();$f->exists=false;$f->fault=$fault;$state=new State_Installation(f2_guarded($f));f2_check($state->ensure_installed()['state']===$expected,'Classified production setup failure: '.$fault);f2_check($f->records===array(),'Failed setup never initializes state');$html=render_setup($admin);f2_check(strpos($html,'SECRET')===false&&strpos($html,'convert wp_options')===false,'Sanitized accurate state-storage diagnostics');}
foreach(array('engine','schema','version','payload','unknown_key') as $fault){
	$f=new Database();if($fault==='engine'){$f->engine='MyISAM';}if($fault==='schema'){$f->columns[4]['Type']='longtext';}
	if(in_array($fault,array('version','payload','unknown_key'),true)){$key=$fault==='unknown_key'?'foreign':'theme_library';$s=\Papijo\H5PThemerWordPress\Storage\Schema::initial();if($fault==='version'){$s['schema_version']=99;}$f->records[$key]=array('record_id'=>'1','schema_version'=>(string)$s['schema_version'],'revision'=>'0','payload'=>$fault==='payload'?'N;':serialize($s));}
	$state=new State_Installation(f2_guarded($f));$snapshot=serialize(array($f->records,$f->columns,$f->engine));f2_check($state->ensure_installed()['state']==='incompatible','Incompatible owned state rejected: '.$fault);f2_check(serialize(array($f->records,$f->columns,$f->engine))===$snapshot,'No incompatible storage repair/reset');
}
$f=new Database();$f->exists=false;$state=new State_Installation(f2_guarded($f));$_SERVER['REQUEST_METHOD']='GET';$_POST=array(State_Storage_Admin::NONCE=>wp_create_nonce(State_Storage_Admin::ACTION));try{$admin->retry();throw new RuntimeException('Expected method refusal');}catch(Setup_Denied $e){f2_check($e->status===405&&!$f->exists,'GET cannot install state');}
$_SERVER['REQUEST_METHOD']='POST';$_POST=array(State_Storage_Admin::NONCE=>'bad');try{$admin->retry();throw new RuntimeException('Expected nonce refusal');}catch(Setup_Denied $e){f2_check($e->status===403&&!$f->exists,'Bad nonce cannot install');}
$_POST=array(State_Storage_Admin::NONCE=>wp_create_nonce(State_Storage_Admin::ACTION));$GLOBALS['pjh5pwp_can_manage']=false;try{$admin->retry();throw new RuntimeException('Expected capability refusal');}catch(Setup_Denied $e){f2_check($e->status===403&&!$f->exists,'Unauthorized user cannot install');}$GLOBALS['pjh5pwp_can_manage']=true;
try{$admin->retry();throw new RuntimeException('Expected local redirect');}catch(Setup_Redirect $e){f2_check($e->status===303&&strpos($e->url,'https://fixture.invalid/wp-admin/')===0&&$f->exists,'Authorized explicit retry creates empty state');}
$GLOBALS['pjh5pwp_multisite']=true;$before=$f->opened;f2_check(strpos(render_setup($admin),'not supported on multisite')!==false&&$f->opened===$before,'Multisite notice does not open storage');$GLOBALS['pjh5pwp_multisite']=false;
f2_check($GLOBALS['pjh5pwp_options']===$saved_options,'No WordPress or SNORDIAN options changed');
echo 'PASS F2 production state lifecycle: '.$f2_checks.' assertions; memory fixtures; no site/database bootstrap.'.PHP_EOL;
