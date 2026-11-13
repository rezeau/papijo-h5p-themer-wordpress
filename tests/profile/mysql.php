<?php
/** Real wpdb adapter tests; only disposable pjh5pwp_test_* databases are selected. */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
require dirname(__DIR__,2).'/includes/theme-domain.php';require dirname(__DIR__,2).'/includes/wordpress-storage.php';require dirname(__DIR__,2).'/includes/profiles.php';require dirname(__DIR__).'/storage/wordpress-doubles.php';
use Papijo\H5PThemerWordPress\Profile\Profile_Store;
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
function factory($barrier=false,$readonly=false){return static function()use($barrier,$readonly){$db=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);if($readonly){$db->query('SET SESSION transaction_read_only=1');}return array($barrier?new Profile_Barrier($db):$db,'pjh5pwp_pjh5pwp_profiles','pjh5pwp_usermeta');};}
function start_worker($c,$root,$job){$p=proc_open(array(PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli',__FILE__,'--worker'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);fwrite($pipes[0],json_encode(array('config'=>$c,'root'=>$root,'job'=>$job))."\n");fflush($pipes[0]);return array($p,$pipes);}
function finish_worker($w){$out=stream_get_contents($w[1][1]);$err=stream_get_contents($w[1][2]);foreach($w[1] as $pipe){if(is_resource($pipe)){fclose($pipe);}}$code=proc_close($w[0]);if($code||$err!==''){throw new RuntimeException('Worker failure: '.$err.$out);}return json_decode($out,true);}
if(($argv[1]??'')==='--worker'){
 $input=json_decode(fgets(STDIN),true);boot($input['config'],$input['root']);$job=$input['job'];$store=new Profile_Store(factory(empty($job['reload'])));$before=$store->read(123);
 if(!empty($job['reload'])){echo json_encode($before);exit;}
 $values=array_replace($before['values']??Profile_State::defaults(),$job['patch']);$result=$store->compare_and_swap(123,$before,$values);echo json_encode(array('result'=>$result,'values'=>$values));exit;
}
if(!isset($argv[1],$argv[2])){throw new RuntimeException('Provide WordPress source root and credential-literal path');}
$count=0;function verify($v,$m){global $count;++$count;if(!$v){throw new RuntimeException($m);}}
$c=credentials($argv[2]);$c['database']='pjh5pwp_test_'.bin2hex(random_bytes(8));$root=$argv[1];$admin=new mysqli($c['DB_HOST'],$c['DB_USER'],$c['DB_PASSWORD']);$created=false;
try{
 $admin->query('CREATE DATABASE `'.$c['database'].'`');$created=true;boot($c,$root);$db=$GLOBALS['wpdb'];
 $db->query('CREATE TABLE pjh5pwp_usermeta(umeta_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED,meta_key VARCHAR(255),meta_value LONGTEXT) ENGINE=MyISAM');
 $store=new Profile_Store();try{$store->read(123);throw new RuntimeException('Expected missing table');}catch(Storage_Exception $e){verify($e->getMessage()==='profile_installation_required','Missing installation blocks');}
 verify($store->install()==='ready','Explicit install');verify($store->install()==='ready','Repeated install');verify($store->read(123)===null,'GET does not create record');
 verify($store->compare_and_swap(123,null,Profile_State::defaults())==='noop','Absent default no-op');verify($store->read(123)===null,'No-op does not initialize');
 $jobs=array(array(array('allow_theme'=>'1'),array('allow_density'=>'1')),array(array('allow_theme'=>'0'),array('allow_density'=>'0')),array(array('theme'=>'core'),array('theme'=>'builtin:dark')),array(array('density'=>'small'),array('density'=>'large')),array(array('allow_density'=>'0'),array('density'=>'medium')));
 foreach($jobs as $round=>$pair){
  // Ensure both candidates mutate the same starting snapshot.
  if($round>0){$before=$store->read(123);$base=array('allow_theme'=>'1','allow_density'=>'1','theme'=>'site_default','density'=>'original');verify(in_array($store->compare_and_swap(123,$before,$base),array('committed','noop'),true),'Prepare race');}
  $before=$store->read(123);$workers=array();foreach($pair as $patch){$workers[]=start_worker($c,$root,array('patch'=>$patch));}
  foreach($workers as $w){ $ready=fgets($w[1][1]);if($ready!=="READY\n"){throw new RuntimeException('Barrier failure: '.($ready?:'').stream_get_contents($w[1][1]).stream_get_contents($w[1][2]));}verify(true,'Both writers reached SQL barrier');}
  foreach($workers as $w){fwrite($w[1][0],"GO\n");fflush($w[1][0]);}
  $results=array_map('finish_worker',$workers);$outcomes=array_column($results,'result');sort($outcomes);verify($outcomes===array('committed','conflict'),'Exactly one winner, one conflict');
  $after=$store->read(123);verify($after['revision']===($before===null?0:$before['revision']+1),'Single revision');
  $winner=$results[0]['result']==='committed'?$results[0]:$results[1];verify($after['values']===$winner['values'],'Entire winner aggregate retained');
  verify((int)$db->get_var('SELECT COUNT(*) FROM pjh5pwp_pjh5pwp_profiles WHERE user_id=123')===1,'Unique user record');
 }
 $before=$store->read(123);verify($store->compare_and_swap(123,$before,$before['values'])==='noop','Existing no-op');verify($store->read(123)===$before,'No-op retains revision');
 $bad=$before['values'];$bad['density']='evil';verify($store->compare_and_swap(123,$before,$bad)==='validation_failed','Invalid aggregate rejected');verify($store->read(123)===$before,'Invalid write unchanged');
 $readonly=new Profile_Store(factory(false,true));$next=array('allow_theme'=>'0','allow_density'=>'0','theme'=>'builtin:mint','density'=>'large');try{$readonly->compare_and_swap(123,$before,$next);throw new RuntimeException('Expected SQL failure');}catch(Storage_Exception $e){verify($e->getMessage()==='profile_database_failed','Actual failed write classified');}verify($store->read(123)===$before,'Failed multi-field statement unchanged');
 verify($store->compare_and_swap(123,$before,$next)==='committed','Atomic multi-field change');
 verify($store->compare_and_swap(123,$before,$next)==='conflict','Repeated old mutating request conflicts');
 $before=$store->read(123);verify($before['values']===$next,'All four fields changed together');
 $uncertain=new Profile_Store(static function(){ $parts=call_user_func(factory());$parts[0]=new Profile_Lost_Ack($parts[0]);return $parts;});
 $next=$before['values'];$next['theme']='builtin:dark';try{$uncertain->compare_and_swap(123,$before,$next);throw new RuntimeException('Expected unknown acknowledgement');}catch(Storage_Exception $e){verify($e->outcome()==='unknown','Changed session is unknown outcome, not rollback');}
 $before=$store->read(123);verify($before['values']===$next,'Unknown acknowledgement may have committed full aggregate');
 $w=start_worker($c,$root,array('reload'=>true));fclose($w[1][0]);verify(finish_worker($w)===$before,'Fresh-process exact reload');
 $db->query('UPDATE pjh5pwp_pjh5pwp_profiles SET schema_version=99 WHERE user_id=123');try{$store->read(123);throw new RuntimeException('Expected unknown schema');}catch(Storage_Exception $e){verify($e->getMessage()==='profile_state_invalid','Unknown schema fails closed');}
 $db->query('UPDATE pjh5pwp_pjh5pwp_profiles SET schema_version=1,state=0x626164 WHERE user_id=123');try{$store->read(123);throw new RuntimeException('Expected malformed state');}catch(Storage_Exception $e){verify($e->getMessage()==='profile_state_invalid','Malformed state fails closed');}
 $db->query("INSERT INTO pjh5pwp_usermeta(user_id,meta_key,meta_value) VALUES(124,'pjh5pwp_allow_theme','1')");try{$store->read(124);throw new RuntimeException('Expected legacy rejection');}catch(Storage_Exception $e){verify($e->getMessage()==='profile_legacy_review_required','No invented legacy migration');}
 $db->query('ALTER TABLE pjh5pwp_pjh5pwp_profiles ENGINE=MyISAM');try{$store->install();throw new RuntimeException('Expected unsupported engine');}catch(Storage_Exception $e){verify($e->getMessage()==='profile_engine_unsupported','No silent engine conversion');}
 echo 'PASS profile MySQL: '.$count.' assertions; five two-process races including first initialization; real wpdb; fresh-process reload; server '.$admin->server_info.".\n";
}finally{if($created){if(!$admin->query('DROP DATABASE `'.$c['database'].'`')){throw new RuntimeException('Disposable cleanup failed');}$left=$admin->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$admin->real_escape_string($c['database'])."'")->fetch_row()[0];if($left!=='0'){throw new RuntimeException('Disposable database remains');}echo "Disposable database removed and absence verified.\n";}$admin->close();}
