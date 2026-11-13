<?php
/** Option CAS fault/race tests; the separate mysql suite proves actual SQL locking. */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){throw new ErrorException($m,0,$n,$f,$l);});
require dirname(__DIR__,2).'/includes/theme-domain.php';
require dirname(__DIR__,2).'/includes/wordpress-storage.php';
require dirname(__DIR__,2).'/includes/roles.php';
require dirname(__DIR__).'/storage/fake-database.php';
use Papijo\H5PThemerWordPress\Role\Role_Store;
use Papijo\H5PThemerWordPress\Role\Role_State;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Tests\Fake_Database;
$count=0;
function verify($v,$m){global $count;++$count;if(!$v){throw new RuntimeException($m);}}
function state($revision,$roles=array('subscriber'=>true)){ksort($roles,SORT_STRING);return array('schema_version'=>1,'revision'=>$revision,'roles'=>$roles);}
function expect_failure($fn,$outcome='not_committed'){try{$fn();throw new RuntimeException('Expected storage exception');}catch(Storage_Exception $e){verify($e->outcome()===$outcome,'Correct failure outcome');}}
$db=new Fake_Database();$invalidations=0;$store=new Role_Store(array($db,'open'),static function()use(&$invalidations){++$invalidations;});
verify($store->read()===null,'Missing state stays absent');verify(!$db->rows,'Read never initializes');
verify($store->compare_and_swap(null,state(0)),'Explicit initialize');verify($store->read()===state(0),'Exact read');verify($invalidations===1,'Evicted after commit');verify($db->rows[Role_State::OPTION]['autoload']==='off','No autoload');
verify(!$store->compare_and_swap(null,state(0,array('editor'=>true))),'Repeated initialization rejected');
verify(!$store->compare_and_swap(0,state(3)),'Revision jump rejected');verify(!$store->compare_and_swap('0',state(1)),'String revision rejected');verify(!$store->compare_and_swap(0,state(1,array('subscriber'=>'1'))),'Malformed grant rejected');
verify($store->compare_and_swap(0,state(1,array('editor'=>true))),'Replace entire map');verify(!$store->compare_and_swap(0,state(1)),'Stale update rejected');verify($store->read()===state(1,array('editor'=>true)),'Stale update preserves winner');
$before=$db->rows;verify($store->with_locked_snapshot(static function($snapshot){return $snapshot;})===state(1,array('editor'=>true)),'Locked current snapshot supplied');verify($db->rows===$before,'Shared-lock callback never writes roles');verify((bool)array_filter($db->log,static function($sql){return strpos($sql,'LOCK IN SHARE MODE')!==false;}),'Shared-lock SQL requested');
try{$store->with_locked_snapshot(static function(){throw new RuntimeException('Callback failed');});throw new RuntimeException('Expected callback exception');}catch(RuntimeException $e){verify($e->getMessage()==='Callback failed','Callback error preserved');}verify($db->connections===$db->closed,'Lock connection released after callback exception');
// Both callers pass pre-read checks; the SQL old-byte predicate selects the winner.
$db->before_write=static function()use($store){verify($store->compare_and_swap(1,state(2,array('teacher'=>true))),'Racing winner commits');};
verify(!$store->compare_and_swap(1,state(2,array('subscriber'=>true))),'Racing loser rejected by SQL');verify($store->read()===state(2,array('teacher'=>true)),'Entire racing winner retained');
$saved=$db->rows;$db->fault='write';expect_failure(static function()use($store){$store->compare_and_swap(2,state(3));});verify($db->rows===$saved,'Failed write changes nothing');$db->fault=null;
$db->fault='lost_ack';expect_failure(static function()use($store){$store->compare_and_swap(2,state(3));},'unknown');$db->fault=null;verify($store->read()===state(3),'Unknown result may have committed whole state');
$badCache=new Role_Store(array($db,'open'),static function(){throw new RuntimeException('Cache unavailable');});expect_failure(static function()use($badCache){$badCache->compare_and_swap(3,state(4,array()));},'committed');verify($store->read()===state(4,array()),'Cache failure does not imply rollback');
$saved=$db->rows;
foreach(array('N;','not serialized',serialize(array('schema_version'=>99,'revision'=>5,'roles'=>array())),serialize(state(5,array('subscriber'=>false))),serialize(state(5)).'trailing') as $bytes){$db->rows[Role_State::OPTION]['option_value']=$bytes;expect_failure(static function()use($store){$store->read();});verify($db->rows[Role_State::OPTION]['option_value']===$bytes,'Malformed state never repaired');}
$db->rows=$saved;
foreach(array('engine'=>'MyISAM','unique'=>false,'triggers'=>true,'autoload'=>0,'collation'=>'latin1_swedish_ci') as $property=>$bad){$previous=$db->$property;$db->$property=$bad;expect_failure(static function()use($store){$store->read();});$db->$property=$previous;}
$db->rows=array();$db->before_write=static function()use($store){verify($store->compare_and_swap(null,state(0,array('editor'=>true))),'Concurrent initializer winner');};verify(!$store->compare_and_swap(null,state(0)),'Concurrent initialization loser');verify($store->read()===state(0,array('editor'=>true)),'Initializer winner retained');
verify($db->connections===$db->closed,'Every private session closed');
echo 'Phase 2A-R role storage: '.$count." assertions passed; no database or CMS bootstrap.\n";
