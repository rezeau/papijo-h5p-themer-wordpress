<?php
/** Profile installation contracts; disposable memory/fault fixtures, no CMS/DB. */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
require dirname(__DIR__,2).'/includes/theme-domain.php';require dirname(__DIR__,2).'/includes/wordpress-storage.php';require dirname(__DIR__,2).'/includes/profiles.php';require __DIR__.'/installation-fixture.php';
use Papijo\H5PThemerWordPress\Profile\Profile_Installation;
use Papijo\H5PThemerWordPress\Profile\Profile_Store;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
$checks=0;function install_check($value,$message){global $checks;++$checks;if(!$value){throw new RuntimeException($message);}}
function installer($fixture){return new Profile_Installation(new Profile_Store(array($fixture,'open')));}
function readonly_log($fixture){foreach($fixture->log as $sql){install_check(preg_match('/\A(?:SELECT|SHOW)\b/',$sql)===1,'Read-only status query');}}
function no_preference_writes($fixture){foreach($fixture->log as $sql){install_check(!preg_match('/\A(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE)\b/',$sql),'No row/migration/destructive statement');}}
$f=new Installation_Fixture();$service=installer($f);
install_check($f->connections===0,'Construction and inert definitions never connect');
$status=$service->status();install_check($status===array('state'=>'absent','errors'=>array(),'outcome'=>'not_attempted'),'Confirmed absence without DDL');readonly_log($f);
install_check($f->creates===0&&!$f->exists,'Status keeps table absent');
$ordinary=new Profile_Store(array($f,'open'));
foreach(array(static function()use($ordinary){$ordinary->read(7);},static function()use($ordinary){$ordinary->compare_and_swap(7,null,Profile_State::defaults());}) as $request){
    try{$request();throw new RuntimeException('Missing storage must remain unavailable');}catch(Storage_Exception $e){install_check($e->getMessage()==='profile_installation_required','Ordinary read/save never installs');}
}
install_check($f->creates===0&&!$f->exists,'Ordinary requests keep storage absent');readonly_log($f);
install_check($service->ensure_installed()===array('state'=>'ready','errors'=>array(),'outcome'=>'confirmed'),'Fresh exact empty installation');
install_check($f->exists&&$f->creates===1&&$f->records===array(),'One CREATE, no preference initialization');
$before=$f->creates;install_check($service->ensure_installed()['state']==='ready'&&$f->creates===$before,'Repeated install performs no CREATE');
$f->records[7]=array('schema_version'=>1,'revision'=>39,'state'=>serialize(array('schema_version'=>1,'revision'=>39,'values'=>array_replace(Profile_State::defaults(),array('theme'=>'builtin:mint','density'=>'large')))));
$saved=serialize($f->records);$f->log=array();
install_check($service->status()['state']==='ready'&&$service->ensure_installed()['state']==='ready','Saved records remain compatible');
install_check(serialize($f->records)===$saved,'Binary bytes and revision preserved');readonly_log($f);no_preference_writes($f);
$before=$f->connections;foreach(array(1,2,99,null,'2') as $target){install_check($service->upgrade($target)===array('state'=>'unsupported_upgrade','errors'=>array('profile_upgrade_not_supported'),'outcome'=>'not_attempted'),'Future upgrade is explicit and inert');}
install_check($f->connections===$before,'Upgrade boundary opens no database');
foreach(array('NO','DISABLED') as $support){$f=new Installation_Fixture();$f->engines=$support;$r=installer($f)->ensure_installed();install_check($r['errors']===array('profile_innodb_unavailable')&&!$f->exists&&$f->creates===0,'InnoDB unavailable cannot substitute engine');}
$f=new Installation_Fixture();$f->engines=null;install_check(installer($f)->ensure_installed()['state']==='unavailable'&&$f->creates===0,'Unknown engines metadata fails closed');
foreach(array('denied'=>'profile_installation_denied','failed'=>'profile_installation_failed','mode'=>'profile_environment_unsupported','mode_reconnect'=>'profile_metadata_unavailable','probe_denied'=>'profile_metadata_unavailable','probe_transport'=>'profile_metadata_unavailable','probe_throw'=>'profile_metadata_unavailable') as $fault=>$code){
    $f=new Installation_Fixture();$f->fault=$fault;$r=installer($f)->ensure_installed();install_check($r['errors']===array($code)&&$r['outcome']==='not_committed','Sanitized definite pre-create/server rejection: '.$fault);install_check(!$f->exists&&($fault==='denied'||$fault==='failed'||$f->creates===0),'Rejected creation remains absent');install_check($f->connections===$f->closed,'Failure closes session');no_preference_writes($f);
}
foreach(array('lost_ack','throw_after','transport_after','false_after','verification','close') as $fault){
    $f=new Installation_Fixture();$f->fault=$fault;$s=installer($f);$r=$s->ensure_installed();install_check($r['state']==='unknown'&&$r['outcome']==='unknown','Uncertain CREATE outcome: '.$fault);install_check($f->exists&&$f->creates===1&&$f->records===array(),'Unknown may have created empty table; no retry/drop');install_check(strpos(json_encode($r),'SECRET')===false,'Fault detail sanitized');$f->fault='';install_check($s->status()['state']==='ready','Explicit read-only reconciliation after unknown');no_preference_writes($f);
}
foreach(array('MyISAM','MEMORY',null) as $engine){$f=new Installation_Fixture();$f->exists=true;$f->engine=$engine;$r=installer($f)->ensure_installed();install_check($r['state']==='incompatible'&&$r['errors']===array('profile_engine_unsupported')&&$f->creates===0&&$f->engine===$engine,'Existing incompatible engine/view untouched');readonly_log($f);}
foreach(array('columns','nullable','extra','type','index','trigger','version') as $fault){
    $f=new Installation_Fixture();$f->exists=true;$f->records[7]=array('schema_version'=>1,'revision'=>42,'state'=>"raw\0binary\xff");
    if($fault==='columns'){array_pop($f->columns);}if($fault==='nullable'){$f->columns[0]['Null']='YES';}if($fault==='extra'){$f->columns[0]['Extra']='auto_increment';}if($fault==='type'){$f->columns[3]['Type']='longtext';}if($fault==='index'){$f->indexes=array();}if($fault==='trigger'){$f->triggers=array(array('Trigger'=>'foreign'));}if($fault==='version'){$f->records[7]['schema_version']=99;}
    $before=serialize(array($f->columns,$f->indexes,$f->triggers,$f->records));$s=installer($f);install_check($s->status()['state']==='incompatible'&&$s->ensure_installed()['state']==='incompatible','Reject unsupported storage: '.$fault);install_check($f->creates===0&&serialize(array($f->columns,$f->indexes,$f->triggers,$f->records))===$before,'Incompatible schema/state unchanged');readonly_log($f);
}
foreach(array(false,true) as $exists){$f=new Installation_Fixture();$f->exists=$exists;$f->metadata=false;$r=installer($f)->ensure_installed();install_check($r['state']==='unavailable'&&$f->creates===0&&$f->exists===$exists,'Metadata failure cannot authorize creation');}
$f=new Installation_Fixture();$f->exists=true;$f->hidden=true;install_check(installer($f)->ensure_installed()['errors']===array('profile_metadata_unavailable')&&$f->creates===0,'Hidden metadata with accessible table is not absence');
$f=new Installation_Fixture();$f->autocommit=0;install_check(installer($f)->ensure_installed()['state']==='unavailable'&&$f->creates===0,'Do not implicitly commit an existing transaction');
foreach(array('compatible','incompatible') as $race){$f=new Installation_Fixture();$f->before_create=static function($f)use($race){$f->exists=true;if($race==='incompatible'){$f->engine='MyISAM';}};$r=installer($f)->ensure_installed();install_check($race==='compatible'?$r['state']==='ready':$r['state']==='unknown','Concurrent creator must pass post-create verification');install_check($f->engine===($race==='compatible'?'InnoDB':'MyISAM')&&$f->records===array(),'Never convert racing table or create preferences');no_preference_writes($f);}
foreach(array(new RuntimeException('SECRET SQL'),new Storage_Exception('SECRET credentials')) as $error){$s=new Profile_Installation(new Profile_Store(static function()use($error){throw $error;}));$r=$s->status();install_check($r['errors']===array('profile_storage_unavailable')&&strpos(json_encode($r),'SECRET')===false,'Unknown exception sanitized');}
echo 'Profile installation contracts: '.$checks." assertions passed; disposable memory/fault fixtures; no CMS/database bootstrap.\n";
