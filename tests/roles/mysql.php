<?php
/** Real wpdb adapter tests; only disposable pjh5pwp_test_* databases are selected. */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
require dirname(__DIR__,2).'/includes/theme-domain.php';require dirname(__DIR__,2).'/includes/wordpress-storage.php';require dirname(__DIR__,2).'/includes/profiles.php';require dirname(__DIR__).'/storage/wordpress-doubles.php';
use Papijo\H5PThemerWordPress\Role\Role_Store;
use Papijo\H5PThemerWordPress\Role\Role_State;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
use Papijo\H5PThemerWordPress\Storage\Owned_WPDB;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
function credentials($path){
 $tokens=token_get_all(file_get_contents($path));$result=array();
 $decode=static function($s){$v=substr($s,1,-1);return $s[0]==="'"?str_replace(array("\\'","\\\\"),array("'","\\"),$v):stripcslashes($v);};
 for($i=0;$i<count($tokens);$i++){if(is_array($tokens[$i])&&$tokens[$i][0]===T_STRING&&strtolower($tokens[$i][1])==='define'){$strings=array();for($j=$i+1;$j<count($tokens)&&$tokens[$j]!==')';$j++){if(is_array($tokens[$j])&&$tokens[$j][0]===T_CONSTANT_ENCAPSED_STRING){$strings[]=$decode($tokens[$j][1]);}}if(count($strings)===2&&in_array($strings[0],array('DB_HOST','DB_USER','DB_PASSWORD'),true)){$result[$strings[0]]=$strings[1];}}}if(count($result)!==3){throw new RuntimeException('Literal credentials required');}return $result;
}
function boot($c,$root){
 if(!preg_match('/\Apjh5pwp_test_[a-f0-9]{16}\z/',$c['database'])){throw new RuntimeException('Unsafe database');}
 foreach(array_merge($c,array('DB_NAME'=>$c['database'],'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_DEBUG'=>false,'WP_DEBUG_DISPLAY'=>false)) as $k=>$v){if($k!=='database'){define($k,$v);}}
 require $root.'/wp-includes/class-wpdb.php';$GLOBALS['wpdb']=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$GLOBALS['wpdb']->set_prefix('pjh5pwp_');$GLOBALS['wpdb']->suppress_errors(true);
 require dirname(__DIR__,2).'/includes/Storage/class-owned-wpdb.php';
}
final class Profile_Barrier {
 private $db;public function __construct($db){$this->db=$db;}public function __call($n,$a){return call_user_func_array(array($this->db,$n),$a);}public function __get($n){return $this->db->$n;}
 public function query($sql){fwrite(STDOUT,"READY\n");fflush(STDOUT);if(fgets(STDIN)!=="GO\n"){throw new RuntimeException('Barrier interrupted');}return $this->db->query($sql);}
}
final class Profile_Lost_Ack {
 private $db;private $changed=false;public function __construct($db){$this->db=$db;}public function __call($n,$a){return call_user_func_array(array($this->db,$n),$a);}public function __get($n){return $this->db->$n;}
 public function query($sql){$r=$this->db->query($sql);$this->changed=true;return $r;}
 public function thread_id(){return $this->db->thread_id()+($this->changed?1:0);}
}
function factory($barrier=false,$readonly=false){return static function()use($barrier,$readonly){$db=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);if($readonly){$db->query('SET SESSION transaction_read_only=1');}return array($barrier?new Profile_Barrier($db):$db,'pjh5pwp_options');};}
function start_worker($c,$root,$job){$p=proc_open(array(PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli',__FILE__,'--worker'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);fwrite($pipes[0],json_encode(array('config'=>$c,'root'=>$root,'job'=>$job))."\n");fflush($pipes[0]);return array($p,$pipes);}
function finish_worker($w){$out=stream_get_contents($w[1][1]);$err=stream_get_contents($w[1][2]);foreach($w[1] as $pipe){if(is_resource($pipe)){fclose($pipe);}}$code=proc_close($w[0]);if($code||$err!==''){throw new RuntimeException('Worker failure: '.$err.$out);}return json_decode($out,true);}
function get_userdata($id){return (object)array('ID'=>$id,'roles'=>array('subscriber'));}
function user_can($user,$cap){return false;}
function wp_roles(){return (object)array('roles'=>array('subscriber'=>array('name'=>'Subscriber','capabilities'=>array())));}
function translate_user_role($name){return $name;}
function profile_factory($barrier=false){return static function()use($barrier){$db=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);return array($barrier?new Profile_Barrier($db):$db,'pjh5pwp_pjh5pwp_profiles','pjh5pwp_usermeta');};}
if(($argv[1]??'')==='--worker'){
 $input=json_decode(fgets(STDIN),true);boot($input['config'],$input['root']);$job=$input['job'];
 if(!empty($job['preference'])){
  $roles=new \Papijo\H5PThemerWordPress\Role\Role_Service(new Role_Store(factory()));$profiles=new \Papijo\H5PThemerWordPress\Profile\Profile_Store(profile_factory(true));$before=$profiles->read(123);
  $result=$roles->while_allowed(123,static function()use($profiles,$before){$next=$before['values'];$next['theme']='builtin:mint';$next['density']='large';return $profiles->compare_and_swap(123,$before,$next);});echo json_encode(array('result'=>$result));exit;
 }
 $store=new Role_Store(factory(empty($job['reload'])));$before=$store->read();
 if(!empty($job['reload'])){echo json_encode($before);exit;}
 $next=array('schema_version'=>1,'revision'=>$before===null?0:$before['revision']+1,'roles'=>$job['roles']);$result=$store->compare_and_swap($before===null?null:$before['revision'],$next);echo json_encode(array('won'=>$result,'next'=>$next));exit;
}
if(!isset($argv[1],$argv[2])){throw new RuntimeException('Provide WordPress source root and credential-literal path');}
$count=0;function verify($v,$m){global $count;++$count;if(!$v){throw new RuntimeException($m);}}
$c=credentials($argv[2]);$c['database']='pjh5pwp_test_'.bin2hex(random_bytes(8));$root=$argv[1];$admin=new mysqli($c['DB_HOST'],$c['DB_USER'],$c['DB_PASSWORD']);$created=false;
try{
 if(!$admin->query('CREATE DATABASE `'.$c['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')){throw new RuntimeException('Disposable database unavailable');}$created=true;boot($c,$root);$db=$GLOBALS['wpdb'];
 verify($db->query("CREATE TABLE pjh5pwp_options (option_id bigint unsigned NOT NULL AUTO_INCREMENT, option_name varchar(191) NOT NULL, option_value longtext NOT NULL, autoload varchar(20) NOT NULL DEFAULT 'yes', PRIMARY KEY(option_id), UNIQUE KEY option_name(option_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")!==false,'Disposable options table');
 $store=new Role_Store();verify($store->read()===null,'Read keeps option absent');
 // Actual independent processes pause after reads, immediately before INSERT/UPDATE.
 for($round=0;$round<7;$round++){
  $before=$store->read();$workers=array();foreach(array(array('subscriber'=>true),array('teacher'=>true)) as $grants){$workers[]=start_worker($c,$root,array('roles'=>$grants));}
  foreach($workers as $w){$ready=fgets($w[1][1]);if($ready!=="READY\n"){throw new RuntimeException('Barrier failure: '.($ready?:'').stream_get_contents($w[1][1]).stream_get_contents($w[1][2]));}verify(true,'Both reached SQL barrier');}
  foreach($workers as $w){fwrite($w[1][0],"GO\n");fflush($w[1][0]);}
  $results=array_map('finish_worker',$workers);$wins=array_values(array_filter($results,static function($r){return $r['won'];}));verify(count($wins)===1,'One concurrent administrator wins');$after=$store->read();verify($after===$wins[0]['next'],'Entire winner map');verify($after['revision']===($before===null?0:$before['revision']+1),'One monotonic revision');verify((int)$db->get_var("SELECT COUNT(*) FROM pjh5pwp_options WHERE option_name='pjh5pwp_role_permissions'")===1,'One unique aggregate row');
 }
 $before=$store->read();$next=array('schema_version'=>1,'revision'=>$before['revision']+1,'roles'=>array());
 $readonly=new Role_Store(factory(false,true));try{$readonly->compare_and_swap($before['revision'],$next);throw new RuntimeException('Expected failure');}catch(Storage_Exception $e){verify($e->outcome()==='not_committed','Real SQL failure classified');}verify($store->read()===$before,'Real failure preserves map and revision');
 verify(!$store->compare_and_swap($before['revision']-1,$next),'Stale revision rejected');verify($store->compare_and_swap($before['revision'],$next),'Revoke entire map atomically');
 $before=$store->read();$uncertain=new Role_Store(static function(){ $parts=call_user_func(factory());$parts[0]=new Profile_Lost_Ack($parts[0]);return $parts;});$next=array('schema_version'=>1,'revision'=>$before['revision']+1,'roles'=>array('subscriber'=>true));try{$uncertain->compare_and_swap($before['revision'],$next);throw new RuntimeException('Expected unknown');}catch(Storage_Exception $e){verify($e->outcome()==='unknown','Changed session result unknown');}verify($store->read()===$next,'Unknown acknowledgement may commit whole map');
 $w=start_worker($c,$root,array('reload'=>true));fclose($w[1][0]);verify(finish_worker($w)===$next,'Fresh-process exact reload');
 $db->query("INSERT INTO pjh5pwp_options(option_name,option_value) VALUES('foreign_snordian_fixture','preserve')");
 $db->query('START TRANSACTION');$db->query("UPDATE pjh5pwp_options SET option_value='pending' WHERE option_name='foreign_snordian_fixture'");$before=$store->read();$next=array('schema_version'=>1,'revision'=>$before['revision']+1,'roles'=>array('editor'=>true));verify($store->compare_and_swap($before['revision'],$next),'Private session independent of ambient transaction');$db->query('ROLLBACK');verify($store->read()===$next,'Role commit survives unrelated rollback');verify($db->get_var("SELECT option_value FROM pjh5pwp_options WHERE option_name='foreign_snordian_fixture'")==='preserve','Foreign state unchanged');
 // Serialize a complete learner profile CAS against role revocation, using real locks.
 verify($db->query('CREATE TABLE pjh5pwp_usermeta(umeta_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED,meta_key VARCHAR(255),meta_value LONGTEXT) ENGINE=MyISAM')!==false,'Disposable legacy table');
 $profiles=new \Papijo\H5PThemerWordPress\Profile\Profile_Store(profile_factory());verify($profiles->install()==='ready','Disposable profile table');
 $base=\Papijo\H5PThemerWordPress\Profile\Profile_State::defaults();$base['theme']='core';$base['density']='small';verify($profiles->compare_and_swap(123,null,$base)==='committed','Seed profile');
 $before=$store->read();verify($store->compare_and_swap($before['revision'],array('schema_version'=>1,'revision'=>$before['revision']+1,'roles'=>array('subscriber'=>true))),'Grant subscriber for lock race');
 $learner=start_worker($c,$root,array('preference'=>true));verify(fgets($learner[1][1])==="READY\n",'Learner has shared role lock and reaches profile SQL');
 // A second connection's exact role update is blocked while the learner holds its grant.
 $before=$store->read();$candidate=array('schema_version'=>1,'revision'=>$before['revision']+1,'roles'=>array());$lockProbe=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$lockProbe->suppress_errors(true);$lockProbe->query('SET SESSION innodb_lock_wait_timeout=1');
 $sql=$lockProbe->prepare("UPDATE pjh5pwp_options SET option_value=UNHEX(%s) WHERE option_name=%s AND BINARY option_value=UNHEX(%s)",bin2hex(serialize($candidate)),Role_State::OPTION,bin2hex(serialize($before)));
 verify($lockProbe->query($sql)===false&&$lockProbe->error_number()===1205,'Exclusive role revocation blocked by shared authorization lock');$lockProbe->close();verify($store->read()===$before,'Blocked revocation leaves configuration intact');
 $revoke=start_worker($c,$root,array('roles'=>array()));verify(fgets($revoke[1][1])==="READY\n",'Revocation reaches real SQL');fwrite($revoke[1][0],"GO\n");fflush($revoke[1][0]);fwrite($learner[1][0],"GO\n");fflush($learner[1][0]);
 verify(finish_worker($learner)['result']==='committed','Authorized learner aggregate finishes before revocation');verify(finish_worker($revoke)['won']===true,'Revocation commits after shared lock released');verify($store->read()['roles']===array(),'Revocation wins final authorization');$saved=$profiles->read(123);verify($saved['values']['theme']==='builtin:mint'&&$saved['values']['density']==='large','Complete authorized preferences preserved after revocation');
 $learner=start_worker($c,$root,array('preference'=>true));fclose($learner[1][0]);verify(finish_worker($learner)['result']==='forbidden_preference','Completed revocation rejects next learner before any profile SQL');verify($profiles->read(123)===$saved,'Rejected revoked save preserves profile revision and values');
 $db->query("UPDATE pjh5pwp_options SET option_value='malformed' WHERE option_name='pjh5pwp_role_permissions'");try{$store->read();throw new RuntimeException('Expected malformed rejection');}catch(Storage_Exception $e){verify(true,'Malformed state rejected');}
 echo 'PASS role MySQL: '.$count.' assertions; seven administrator races plus serialized learner/revocation race; real wpdb; server '.$admin->server_info.".\n";
}finally{if($created){if(!$admin->query('DROP DATABASE `'.$c['database'].'`')){throw new RuntimeException('Disposable cleanup failed');}$left=$admin->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$admin->real_escape_string($c['database'])."'")->fetch_row()[0];if($left!=='0'){throw new RuntimeException('Disposable database remains');}echo "Disposable database removed and absence verified.\n";}$admin->close();}
