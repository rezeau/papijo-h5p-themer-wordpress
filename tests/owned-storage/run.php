<?php
/** F1 isolated adapter contracts. No WordPress bootstrap or database connection. */
if (PHP_SAPI !== 'cli') { exit; }
error_reporting(E_ALL);
set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
require dirname(__DIR__,2).'/foundation/owned-storage/bootstrap.php';
require __DIR__.'/fake-database.php';

use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\State_Schema;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\State_Table;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\Theme_Store;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\Role_Store;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\Disposable_Installation;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Tests\OwnedStorage\Database;

$checks=0;
function verify($v,$message){global $checks;++$checks;if(!$v){throw new RuntimeException($message);}}
function same($a,$b,$message){verify($a===$b,$message);}
function failure(callable $request,$code,$outcome='not_committed'){
	try{$request();}catch(Storage_Exception $e){same($code,$e->getMessage(),'Constant diagnostic');same($outcome,$e->outcome(),'Outcome classification');return;}
	throw new RuntimeException('Expected '.$code);
}
function roles($revision,$grants=array('subscriber'=>true)){ksort($grants,SORT_STRING);return array('schema_version'=>1,'revision'=>$revision,'roles'=>$grants);}
function ref($id){return array('kind'=>'builtin','id'=>$id,'version'=>Preset_Catalog::VERSION);}
function stores(Database $db){$table=new State_Table(array($db,'open'));return array(new Theme_Store($table),new Role_Store($table));}

foreach(array('wp_','course_27_','bare','') as $prefix){same($prefix.'pjh5pwp_state',State_Schema::table_name($prefix),'Respect WordPress prefix without requiring a trailing underscore');}
foreach(array('bad-prefix_','bad`prefix_',str_repeat('a',64),null) as $prefix){failure(static function()use($prefix){State_Schema::table_name($prefix);},'owned_state_environment_unsupported');}
$db=new Database();list($themes,$grants)=stores($db);
same(0,$db->opened,'Definitions and constructors are inert');same(null,$themes->read(),'Theme absence');same(null,$grants->read(),'Grant absence');same(array(),$db->records,'Reads initialize neither record');
verify($themes->compare_and_swap(null,Schema::initial()),'Explicit unique Theme initialization');verify($grants->compare_and_swap(null,roles(0)),'Separate unique grant initialization');
$identity=$db->records[State_Schema::THEME]['record_id'];$role_bytes=$db->records[State_Schema::ROLES];
verify(!$themes->compare_and_swap(null,Schema::initial()),'Repeated initialization cannot replace Theme');verify(!$grants->compare_and_swap(null,roles(0,array())),'Repeated initialization cannot replace grant');
$changes=new Library_Changes();$custom=(new Theme_Operations())->duplicate(ref('mint'),'unicode','Été 🎨')['data'];$custom['colors']['--h5p-theme-background']='#AbC';
$next=$changes->create($themes->read(),$custom)['data'];verify($themes->compare_and_swap(0,$next),'Persist aggregate');same($next,$themes->read(),'Exact Unicode and hex-case roundtrip');same(serialize($next),$db->records[State_Schema::THEME]['payload'],'Payload is exact existing serialization');same($identity,$db->records[State_Schema::THEME]['record_id'],'Stable identity');same($role_bytes,$db->records[State_Schema::ROLES],'Theme write does not change grants');
$invalid=$next;$invalid['revision']+=2;verify(!$themes->compare_and_swap(1,$invalid),'No revision jumps');verify(!$grants->compare_and_swap(0,roles(2)),'Role revisions monotonic');verify(!$grants->compare_and_swap('0',roles(1)),'Strict revision type');verify(!$grants->compare_and_swap(0,roles(1,array('subscriber'=>false))),'Strict grant schema');
$forged=$next;$forged['revision']=2;$forged['definitions']['copy']=$custom;$forged['definitions']['copy']['id']='copy';$forged['publication']['themes']['custom:copy']=true;verify(!$themes->compare_and_swap(1,$forged),'Production new-custom publication transition remains enforced');
// Race after the internal read: winner selected by actual old-byte predicate.
$first=$changes->set_default($next,ref('mint'))['data'];$second=$changes->set_default($next,ref('dark'))['data'];
$db->before_write=static function()use($themes,$second){verify($themes->compare_and_swap(1,$second),'Concurrent Theme wins');};verify(!$themes->compare_and_swap(1,$first),'Stale Theme SQL loses');same($second,$themes->read(),'Full winner preserved');
$db->before_write=static function()use($grants){verify($grants->compare_and_swap(0,roles(1,array('editor'=>true))),'Concurrent role wins');};verify(!$grants->compare_and_swap(0,roles(1,array())),'Stale role SQL loses');
// Physical deletion/recreation and rogue same-revision changes cannot pass CAS.
$next=$changes->set_default($themes->read(),ref('mint'))['data'];$db->before_write=static function()use($db){$db->records[State_Schema::THEME]['record_id']='999';};verify(!$themes->compare_and_swap(2,$next),'ABA identity mismatch rejected');
$db->before_write=static function()use($db,$themes){$raw=$themes->read();$raw['settings']['default_theme']=ref('sunset');$db->records[State_Schema::THEME]['payload']=serialize($raw);};verify(!$themes->compare_and_swap(2,$next),'Same revision byte change rejected');
foreach(array('theme','roles') as $kind){$race=new Database();list($t,$r)=stores($race);$s=$kind==='theme'?$t:$r;$seed=$kind==='theme'?Schema::initial():roles(0);$race->before_write=static function()use($s,$seed){verify($s->compare_and_swap(null,$seed),'Initializer competitor wins');};verify(!$s->compare_and_swap(null,$seed),'Unique key loser safely rejected');same($seed,$s->read(),'Initialization winner retained');}
foreach(array('write','ack','transport','throw_after') as $fault){$f=new Database();list($t,$r)=stores($f);verify($t->compare_and_swap(null,Schema::initial()),'Fault seed');$next=$changes->set_default($t->read(),ref('mint'))['data'];$f->fault=$fault;failure(static function()use($t,$next){$t->compare_and_swap(0,$next);},$fault==='write'?'storage_write_failed':'storage_write_unknown',$fault==='write'?'not_committed':'unknown');$f->fault='';same($fault==='write'?Schema::initial():$next,$t->read(),'No blind recovery after known/unknown outcome');same($f->opened,$f->closed,'Fault sessions closed');}
// Locks, pre-callback failures and cleanup preserve callback outcomes.
$before=$db->records;same(roles(1,array('editor'=>true)),$grants->with_locked_snapshot(static function($s){return $s;}),'Fresh locked authorization snapshot');same($before,$db->records,'Lock transaction only reads');
foreach(array('lock_start','start_reconnect','lock_read','lock_reconnect') as $fault){$db->fault=$fault;$called=false;try{$grants->with_locked_snapshot(static function()use(&$called){$called=true;});throw new RuntimeException('Expected lock failure');}catch(Storage_Exception $e){same('not_committed',$e->outcome(),'Failed lock cannot commit Profile');}verify(!$called,'No save callback after lock failure');$db->fault='';}
foreach(array('rollback','close') as $fault){$db->fault=$fault;same('acknowledged',$grants->with_locked_snapshot(static function(){return 'acknowledged';}),'Cleanup cannot mask acknowledged callback result');$db->fault='';}
try{$grants->with_locked_snapshot(static function(){throw new RuntimeException('Callback failure');});throw new RuntimeException('Expected callback failure');}catch(RuntimeException $e){same('Callback failure',$e->getMessage(),'Callback exception preserved');}
same($db->opened,$db->closed,'Every owned session closed');
foreach(array('engine','column','index','trigger','autocommit','table') as $fault){$f=new Database();if($fault==='engine'){$f->engine='MyISAM';}if($fault==='column'){$f->columns[4]['Type']='longtext';}if($fault==='index'){$f->indexes[1]['Sub_part']=8;}if($fault==='trigger'){$f->triggers=array(array('Trigger'=>'foreign'));}if($fault==='autocommit'){$f->autocommit=0;}if($fault==='table'){$f->table='wp_options';}list($t,$r)=stores($f);try{$t->read();throw new RuntimeException('Expected incompatible backend');}catch(Storage_Exception $e){same('not_committed',$e->outcome(),'Unsupported storage fails closed');}same(array(),$f->records,'No repair of incompatible storage');same($f->opened,$f->closed,'Rejected session closed');}
foreach(array('metadata','read_reconnect') as $fault){$f=new Database();$f->fault=$fault;list($t)=stores($f);failure(static function()use($t){$t->read();},'storage_read_failed');same($f->opened,$f->closed,'Read failure releases session');}
foreach(array('bytes','envelope_revision','envelope_version','future','identity','grant') as $fault){$f=new Database();list($t,$r)=stores($f);$s=$fault==='grant'?$r:$t;$key=$fault==='grant'?State_Schema::ROLES:State_Schema::THEME;$seed=$fault==='grant'?roles(0):Schema::initial();verify($s->compare_and_swap(null,$seed),'Corruption seed');if($fault==='bytes'){$f->records[$key]['payload']='N;';}if($fault==='envelope_revision'){$f->records[$key]['revision']='9';}if($fault==='envelope_version'){$f->records[$key]['schema_version']='9';}if($fault==='identity'){$f->records[$key]['record_id']='0';}if($fault==='future'){$seed['schema_version']=99;$f->records[$key]['schema_version']='99';$f->records[$key]['payload']=serialize($seed);}if($fault==='grant'){$seed['roles']=array('subscriber'=>false);$f->records[$key]['payload']=serialize($seed);}$saved=$f->records;try{$s->read();throw new RuntimeException('Expected corruption rejection');}catch(Storage_Exception $e){same('not_committed',$e->outcome(),'Malformed/future state blocked');}same($saved,$f->records,'Never repair malformed/future payloads');}
// The creation helper cannot operate against a site, and never seeds either key.
$missing=new Database();$missing->exists=false;list($missing_themes)=stores($missing);
failure(static function()use($missing_themes){$missing_themes->read();},'owned_state_installation_required');failure(static function()use($missing_themes){$missing_themes->compare_and_swap(null,Schema::initial());},'owned_state_installation_required');verify(!$missing->exists&&!array_filter($missing->log,static function($sql){return strpos($sql,'CREATE')===0;}),'Ordinary reads/writes never create storage');
$large=new Database();list($large_themes,$large_roles)=stores($large);$map=array();for($i=0;$i<25000;$i++){$map['role_'.str_pad((string)$i,185,'x')]=true;}$large_state=roles(0,$map);failure(static function()use($large_roles,$large_state){$large_roles->compare_and_swap(null,$large_state);},'storage_too_large');same(0,$large->opened,'Oversized exact payload is rejected before database access');unset($map,$large_state);
$f=new Database();$f->exists=false;$installer=new Disposable_Installation(array($f,'open'));same('absent',$installer->status(),'Read-only confirmed absence');verify(!$f->exists,'Status does not create');same('ready',$installer->ensure_installed(),'Explicit disposable installation');same(array(),$f->records,'Table created without state rows');$creates=count(array_filter($f->log,static function($sql){return strpos($sql,'CREATE TABLE')===0;}));same('ready',$installer->ensure_installed(),'Idempotent verify');same($creates,count(array_filter($f->log,static function($sql){return strpos($sql,'CREATE TABLE')===0;})),'No repeat DDL');
foreach(array('site','hidden','probe_denied','engine_unavailable','mode_denied','create_denied','create_ack','create_throw','racing_engine') as $fault){$f=new Database();$f->exists=false;if($fault==='site'){$f->database='wordpress_live';}elseif($fault==='hidden'){$f->exists=true;$f->fault='hidden';}elseif($fault==='racing_engine'){$f->before_create=static function($f){$f->exists=true;$f->engine='MyISAM';};}else{$f->fault=$fault;}$i=new Disposable_Installation(array($f,'open'));try{$i->ensure_installed();throw new RuntimeException('Expected install refusal');}catch(Storage_Exception $e){same(in_array($fault,array('create_ack','create_throw','racing_engine'),true)?'unknown':'not_committed',$e->outcome(),'Conservative creation outcome: '.$fault);verify(strpos($e->getMessage(),'SECRET')===false,'No DB detail leaked');}if($fault==='site'){verify(!array_filter($f->log,static function($s){return preg_match('/\A(?:CREATE|ALTER|DROP|INSERT|UPDATE|DELETE)/',$s);}), 'Site name guard precedes all writes');}same(array(),$f->records,'No installation seeds');same($f->opened,$f->closed,'Installer session closed');}
foreach($db->log as $sql){verify(!preg_match('/\b(?:wp_options|usermeta|pjh5pwp_profiles|snordian)\b/i',$sql),'New adapter never accesses WordPress/Profile/SNORDIAN tables');verify(!preg_match('/\A(?:ALTER|DROP|DELETE|REPLACE|TRUNCATE)\b/',$sql),'Adapter never repairs or deletes');}
echo 'PASS F1 ISOLATED owned-storage contracts: '.$checks.' assertions; memory/fault fixtures only; shared backend contracts; production integration verified separately.'.PHP_EOL;
