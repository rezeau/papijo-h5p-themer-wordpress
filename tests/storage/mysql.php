<?php
/**
 * Opt-in disposable MySQL verification. CLI only; no connection selects wp-test.
 * Usage: php tests/storage/mysql.php <WordPress source root> <wp-config path>
 * Only DB_HOST/DB_USER/DB_PASSWORD literals are read, never executed or printed.
 */
if('cli'!==PHP_SAPI){exit;}
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){if(!(error_reporting()&$severity)){return false;}throw new ErrorException($message,0,$severity,$file,$line);});
require dirname(__DIR__,2).'/includes/theme-domain.php';
require dirname(__DIR__,2).'/includes/wordpress-storage.php';
require __DIR__.'/wordpress-doubles.php';

use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Storage_Installation;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;

/** Place both workers inside the adapter, immediately before its actual SQL write. */
final class PJH5PWP_Barrier_Connection {
	private $db;
	public function __construct($db){$this->db=$db;}
	public function __call($name,$args){return call_user_func_array(array($this->db,$name),$args);}
	public function __get($name){return $this->db->$name;}
	public function __isset($name){return isset($this->db->$name);}
	public function query($sql){
		++$GLOBALS['pjh5pwp_write_attempts'];
		fwrite(STDOUT,"READY\n");fflush(STDOUT);
		if("GO\n"!==fgets(STDIN)){throw new RuntimeException('SQL barrier interrupted');}
		return $this->db->query($sql);
	}
}

function mysql_check($condition,$message){global $mysql_checks;if(!$condition){throw new RuntimeException($message);}++$mysql_checks;}
function test_ref($id){return array('kind'=>'builtin','id'=>$id,'version'=>Preset_Catalog::VERSION);}
function test_bootstrap(array $config,$wp_root){
	if(!preg_match('/\Apjh5pwp_test_[a-f0-9]{16}\z/',$config['database'])){throw new RuntimeException('Disposable database name rejected');}
	foreach(array('DB_HOST'=>$config['DB_HOST'],'DB_USER'=>$config['DB_USER'],'DB_PASSWORD'=>$config['DB_PASSWORD'],'DB_NAME'=>$config['database'],'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_DEBUG'=>false,'WP_DEBUG_DISPLAY'=>false) as $key=>$value){define($key,$value);}
	require $wp_root.'/wp-includes/class-wpdb.php';
	$GLOBALS['wpdb']=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);
	$GLOBALS['wpdb']->suppress_errors(true);$GLOBALS['wpdb']->set_prefix('pjh5pwp_');
	if(!$GLOBALS['wpdb']->ready){throw new RuntimeException('Disposable connection failed');}
}
function read_credentials($path){
	$tokens=token_get_all(file_get_contents($path));$config=array();
	$decode=static function($s){$q=$s[0];$v=substr($s,1,-1);return "'"===$q?str_replace(array("\\'","\\\\"),array("'","\\"),$v):stripcslashes($v);};
	for($i=0;$i<count($tokens);$i++){
		if(is_array($tokens[$i])&&T_STRING===$tokens[$i][0]&&'define'===strtolower($tokens[$i][1])){
			$literals=array();for($j=$i+1;$j<count($tokens)&&')'!==$tokens[$j];$j++){if(is_array($tokens[$j])&&T_CONSTANT_ENCAPSED_STRING===$tokens[$j][0]){$literals[]=$decode($tokens[$j][1]);}}
			if(2===count($literals)&&in_array($literals[0],array('DB_HOST','DB_USER','DB_PASSWORD'),true)){$config[$literals[0]]=$literals[1];}
		}
	}
	if(3!==count($config)){throw new RuntimeException('Literal test credentials unavailable');}return $config;
}
function start_pair(array $config,$wp_root,$mode){
	$processes=array();
	foreach(array('mint','dark') as $theme){
		$p=proc_open(array(PHP_BINARY,__FILE__,'--worker'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,dirname(__DIR__,2));
		if(!is_resource($p)){throw new RuntimeException('Cannot start concurrency worker');}
		fwrite($pipes[0],json_encode(array('config'=>$config,'root'=>$wp_root,'theme'=>$theme,'mode'=>$mode))."\n");fflush($pipes[0]);
		$processes[]=array($p,$pipes);
	}
	foreach($processes as $entry){$ready=fgets($entry[1][1]);if("READY\n"!==$ready){throw new RuntimeException('Concurrency worker failed before barrier');}}
	foreach($processes as $entry){fwrite($entry[1][0],"GO\n");fclose($entry[1][0]);}
	$results=array();foreach($processes as $entry){$out=stream_get_contents($entry[1][1]);$err=stream_get_contents($entry[1][2]);fclose($entry[1][1]);fclose($entry[1][2]);$exit=proc_close($entry[0]);if(0!==$exit||''!==$err){throw new RuntimeException('Concurrency worker failed');}$results[]=json_decode($out,true);}
	return $results;
}
if(isset($argv[1])&&'--worker'===$argv[1]){
	$job=json_decode(fgets(STDIN),true);test_bootstrap($job['config'],$job['root']);$GLOBALS['pjh5pwp_write_attempts']=0;
	$factory=new \Papijo\H5PThemerWordPress\Storage\WordPress_Connections();
	$connections=static function()use($factory){list($db,$table)=$factory->open();return array(new PJH5PWP_Barrier_Connection($db),$table);};
	$store=new WordPress_Snapshot_Store($connections);
	$before=$store->read();$next='initialize'===$job['mode']?Schema::initial():(new Library_Changes())->set_default($before,test_ref($job['theme']))['data'];
	$won=$store->compare_and_swap(null===$before?null:$before['revision'],$next);echo json_encode(array('won'=>$won,'theme'=>$job['theme'],'sql_attempts'=>$GLOBALS['pjh5pwp_write_attempts']));$GLOBALS['wpdb']->close();exit;
}
if(!isset($argv[1],$argv[2])){fwrite(STDERR,"Provide WordPress source root and a literal credential source. This test creates/drops only a random pjh5pwp_test_* database.\n");exit(1);}
$mysql_checks=0;$created=false;$admin=null;$config=read_credentials($argv[2]);$config['database']='pjh5pwp_test_'.bin2hex(random_bytes(8));$database=$config['database'];
try{
	// No database is selected here. In particular DB_NAME from wp-config is ignored.
	mysqli_report(MYSQLI_REPORT_OFF);$admin=@new mysqli($config['DB_HOST'],$config['DB_USER'],$config['DB_PASSWORD']);
	if($admin->connect_errno){throw new RuntimeException('Disposable server connection unavailable');}
	if(!$admin->query("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")){throw new RuntimeException('Disposable database creation unavailable');}$created=true;
	if(!$admin->select_db($database)){throw new RuntimeException('Disposable database selection failed');}
	mysql_check($admin->query("CREATE TABLE pjh5pwp_options (option_id bigint unsigned NOT NULL AUTO_INCREMENT, option_name varchar(191) NOT NULL, option_value longtext NOT NULL, autoload varchar(20) NOT NULL DEFAULT 'yes', PRIMARY KEY(option_id), UNIQUE KEY option_name(option_name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"),'Disposable options table');
	test_bootstrap($config,rtrim($argv[1],'/\\'));$db=$GLOBALS['wpdb'];$store=new WordPress_Snapshot_Store();$install=new Storage_Installation($store);$changes=new Library_Changes();
	mysql_check(null===$store->read(),'Initial absence');
	$pair=start_pair($config,$argv[1],'initialize');mysql_check(1===count(array_filter($pair,static function($r){return $r['won'];})),'One initializer wins');
	mysql_check(array(1,1)===array_column($pair,'sql_attempts'),'Both initializers reached actual INSERT before either proceeded');
	mysql_check('ready'===$install->initialize()['state'],'Idempotent initialization');
	$custom=(new Theme_Operations())->duplicate(test_ref('mint'),'example','Été 🎨')['data'];$custom['colors']['--h5p-theme-background']='#AbC';
	$next=$changes->create($store->read(),$custom)['data'];mysql_check($store->compare_and_swap(0,$next),'Custom write');mysql_check($custom===$store->read()['definitions']['example'],'Exact Unicode/sparse roundtrip');
	for($round=0;$round<6;$round++){
		$before=$store->read();$pair=start_pair($config,$argv[1],'update');$wins=array_values(array_filter($pair,static function($r){return $r['won'];}));
		mysql_check(array(1,1)===array_column($pair,'sql_attempts'),'Both writers reached actual conditional UPDATE before either proceeded');
		mysql_check(1===count($wins),'Exactly one concurrent writer wins');$after=$store->read();mysql_check($after['revision']===$before['revision']+1,'One revision per race');mysql_check($after['settings']['default_theme']['id']===$wins[0]['theme'],'Winner value retained');mysql_check($custom===$after['definitions']['example'],'Concurrent writes preserve custom data');
	}
	$before=$store->read();$next=$changes->publish($before,'custom:example',true)['data'];mysql_check($store->compare_and_swap($before['revision'],$next),'Publish write');
	$before=$store->read();$next=$changes->set_default($before,array('kind'=>'custom','id'=>'example'))['data'];mysql_check($store->compare_and_swap($before['revision'],$next),'Custom global write');mysql_check((bool)$changes->delete($store->read(),array('kind'=>'custom','id'=>'example'))['errors'],'Default deletion blocked');
	$db->query("INSERT INTO pjh5pwp_options (option_name,option_value) VALUES ('unrelated_option','original')");
	$db->query('START TRANSACTION');$db->query("UPDATE pjh5pwp_options SET option_value='pending' WHERE option_name='unrelated_option'");
	$before=$store->read();$next=$changes->set_default($before,test_ref('daylight'))['data'];mysql_check($store->compare_and_swap($before['revision'],$next),'Storage owns an independent session');
	$db->query('ROLLBACK');mysql_check('original'===$db->get_var("SELECT option_value FROM pjh5pwp_options WHERE option_name='unrelated_option'"),'Storage does not commit ambient transaction');mysql_check($next===$store->read(),'Own write committed independently');
	$before=$store->read();$next=$changes->set_default($before,test_ref('mint'))['data'];
	$factory=static function(){ $connection=new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$connection->suppress_errors(true);$connection->query('SET SESSION transaction_read_only = 1');return array($connection,'pjh5pwp_options');};
	$readonly=new WordPress_Snapshot_Store($factory);
	try{$readonly->compare_and_swap($before['revision'],$next);throw new RuntimeException('Expected SQL failure');}catch(Storage_Exception $error){mysql_check('not_committed'===$error->outcome(),'Read-only SQL failure reports no commit');}
	mysql_check($before===$store->read(),'Failed SQL leaves aggregate unchanged');
	mysql_check('original'===$db->get_var("SELECT option_value FROM pjh5pwp_options WHERE option_name='unrelated_option'"),'Unrelated option preserved');
	$raw=$db->get_var($db->prepare('SELECT option_value FROM pjh5pwp_options WHERE option_name=%s',Schema::OPTION));mysql_check(serialize($store->read())===$raw,'Actual SQL stored exact native serialization');
	$owned_filters=0;foreach($GLOBALS['pjh5pwp_test_filters']['query'] as $callbacks){foreach($callbacks as $callback){if(is_array($callback)&&$callback[0] instanceof \Papijo\H5PThemerWordPress\Storage\Owned_WPDB){++$owned_filters;}}}
	mysql_check(0===$owned_filters,'Private-session placeholder filters cleaned up');
	try{new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME.'_missing',DB_HOST);throw new RuntimeException('Expected missing database failure');}
	catch(Storage_Exception $error){mysql_check('storage_read_failed'===$error->getMessage(),'Database selection failure is a constant diagnostic, never a fatal WordPress page');}
	$server=$admin->server_info;$db->close();
	echo 'PASS disposable MySQL '.$server.': '.$mysql_checks.' assertions; six two-process writer races plus initialization race; real WordPress wpdb; no site bootstrap.'.PHP_EOL;
} finally {
	if($created&&$admin instanceof mysqli){
		if(!preg_match('/\Apjh5pwp_test_[a-f0-9]{16}\z/',$database)||$database!==$config['database']){throw new RuntimeException('Refusing unsafe database cleanup');}
		if(!$admin->query("DROP DATABASE `$database`")){throw new RuntimeException('Disposable database cleanup failed');}
		echo "Disposable database removed.\n";
	}
	if($admin instanceof mysqli){$admin->close();}
}
