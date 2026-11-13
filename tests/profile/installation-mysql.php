<?php
/** Opt-in real wpdb tests. Only random disposable databases are ever selected. */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
require dirname(__DIR__,2).'/includes/theme-domain.php';require dirname(__DIR__,2).'/includes/wordpress-storage.php';require dirname(__DIR__,2).'/includes/profiles.php';require dirname(__DIR__).'/storage/wordpress-doubles.php';
use Papijo\H5PThemerWordPress\Profile\Profile_Installation;
use Papijo\H5PThemerWordPress\Profile\Profile_Store;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
use Papijo\H5PThemerWordPress\Storage\Owned_WPDB;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
function install_credentials($path){
    $tokens=token_get_all(file_get_contents($path));$result=array();
    $decode=static function($s){$v=substr($s,1,-1);return $s[0]==="'"?str_replace(array("\\'","\\\\"),array("'","\\"),$v):stripcslashes($v);};
    for($i=0;$i<count($tokens);$i++){if(is_array($tokens[$i])&&$tokens[$i][0]===T_STRING&&strtolower($tokens[$i][1])==='define'){$strings=array();for($j=$i+1;$j<count($tokens)&&$tokens[$j]!==')';$j++){if(is_array($tokens[$j])&&$tokens[$j][0]===T_CONSTANT_ENCAPSED_STRING){$strings[]=$decode($tokens[$j][1]);}}if(count($strings)===2&&in_array($strings[0],array('DB_HOST','DB_USER','DB_PASSWORD'),true)){$result[$strings[0]]=$strings[1];}}}
    if(count($result)!==3){throw new RuntimeException('Literal server credentials unavailable');}return $result;
}
function install_boot($c,$root){
    if(!preg_match('/\Apjh5pwp_install_test_[a-f0-9]{16}\z/',$c['database'])){throw new RuntimeException('Unsafe disposable database');}
    foreach(array_merge($c,array('DB_NAME'=>$c['database'],'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_DEBUG'=>false,'WP_DEBUG_DISPLAY'=>false)) as $key=>$value){if($key!=='database'){define($key,$value);}}
    require $root.'/wp-includes/class-wpdb.php';$GLOBALS['wpdb']=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$GLOBALS['wpdb']->set_prefix('course_27_');$GLOBALS['wpdb']->suppress_errors(true);
    require dirname(__DIR__,2).'/includes/Storage/class-owned-wpdb.php';
}
final class Installation_Mysql_Connection {
    private $db,$fault,$table,$log,$delta=0,$injected=0,$created=false;
    public function __construct($db,$fault,$table,&$log){$this->db=$db;$this->fault=$fault;$this->table=$table;$this->log=&$log;}
    public function __call($name,$args){return call_user_func_array(array($this->db,$name),$args);}
    public function __get($name){return $name==='last_error'&&$this->injected?'PRIVATE injected server failure':$this->db->$name;}
    public function get_results($sql,$format){
        $this->log[]=$sql;$this->injected=0;
        if($this->fault==='metadata'||($this->fault==='verification'&&$this->created)){$this->injected=1142;return null;}
        if($this->fault==='engine'&&$sql==='SHOW ENGINES'){return array(array('Engine'=>'InnoDB','Support'=>'DISABLED'));}
        return $this->db->get_results($sql,$format);
    }
    public function query_once($sql){return $this->query($sql,true);}
    public function query($sql,$once=false){
        $this->log[]=$sql;$this->injected=0;
        if(strpos($sql,'CREATE TABLE')===0){
            if($this->fault==='denied'){$this->injected=1142;return false;}
            if($this->fault==='barrier'){fwrite(STDOUT,"READY\n");fflush(STDOUT);if(fgets(STDIN)!=="GO\n"){throw new RuntimeException('Installation barrier interrupted');}}
            if(!$once){throw new RuntimeException('Installer must disable wpdb CREATE replay');}
            if($this->fault==='disconnect_before'){
                if(!preg_match('/\Apjh5pwp_install_test_[a-f0-9]{16}\z/',DB_NAME)){throw new RuntimeException('Unsafe fixture disconnect');}
                $id=$this->db->thread_id();if(!$id){throw new RuntimeException('Missing disposable connection');}
                $killer=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$killer->suppress_errors(true);
                try{if($killer->query('KILL CONNECTION '.(int)$id)===false){throw new RuntimeException('Fixture disconnect failed');}}finally{$killer->close();}
            }
            $result=$this->db->query_once($sql);$this->created=true;
            if($this->fault==='lost_ack'){$this->delta=1;}
            if($this->fault==='throw_after'){throw new RuntimeException('PRIVATE lost CREATE reply');}
            return $result;
        }
        return $this->db->query($sql);
    }
    public function thread_id(){return $this->db->thread_id()+$this->delta;}
    public function error_number(){return $this->injected?:$this->db->error_number();}
}
function install_factory($table,$fault,&$log){
    if(!preg_match('/\A(?:course_27|race_[0-9]+)_pjh5pwp_profiles\z/',$table)){throw new RuntimeException('Unsafe disposable table');}
    return static function()use($table,$fault,&$log){$db=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);if($fault==='readonly'){$db->query('SET SESSION transaction_read_only=1');}return array(new Installation_Mysql_Connection($db,$fault,$table,$log),$table,'course_27_usermeta');};
}
function install_service($table,$fault,&$log){return new Profile_Installation(new Profile_Store(install_factory($table,$fault,$log)));}
function install_worker($c,$root,$table){
    $p=proc_open(array(PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli',__FILE__,'--worker'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
    if(!is_resource($p)){throw new RuntimeException('Cannot start installation worker');}fwrite($pipes[0],json_encode(array('config'=>$c,'root'=>$root,'table'=>$table))."\n");fflush($pipes[0]);return array($p,$pipes);
}
function install_finish($worker){$out=stream_get_contents($worker[1][1]);$err=stream_get_contents($worker[1][2]);foreach($worker[1] as $pipe){if(is_resource($pipe)){fclose($pipe);}}$exit=proc_close($worker[0]);if($exit||$err){throw new RuntimeException('Installation worker failed: '.$err.$out);}return json_decode($out,true);}
if(($argv[1]??'')==='--worker'){$input=json_decode(fgets(STDIN),true);install_boot($input['config'],$input['root']);$log=array();echo json_encode(install_service($input['table'],'barrier',$log)->ensure_installed());exit;}
if(!isset($argv[1],$argv[2])){throw new RuntimeException('Provide WordPress source root and credential-literal path');}
$checks=0;function install_check($v,$message){global $checks;++$checks;if(!$v){throw new RuntimeException($message);}}
function install_rows($db,$table){return $db->get_results('SELECT * FROM `'.$table.'` ORDER BY 1','ARRAY_A');}
function install_snapshot($db,$table){return serialize(array($db->get_row('SHOW CREATE TABLE `'.$table.'`','ARRAY_N'),install_rows($db,$table)));}
function install_ddl_count($log){return count(array_filter($log,static function($sql){return strpos($sql,'CREATE TABLE')===0;}));}
function install_no_dml($log){foreach($log as $sql){install_check(!preg_match('/\A(?:INSERT|UPDATE|DELETE|REPLACE|ALTER|DROP|TRUNCATE)\b/',$sql),'Installer must never write user/other data');}}
$c=install_credentials($argv[2]);$c['database']='pjh5pwp_install_test_'.bin2hex(random_bytes(8));$root=$argv[1];$admin=new mysqli($c['DB_HOST'],$c['DB_USER'],$c['DB_PASSWORD']);$created=false;$workers=array();
try{
    if(!$admin->query('CREATE DATABASE `'.$c['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')){throw new RuntimeException('Disposable database creation failed');}$created=true;install_boot($c,$root);$db=$GLOBALS['wpdb'];
    $db->query('CREATE TABLE course_27_usermeta(umeta_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED,meta_key VARCHAR(255),meta_value LONGTEXT) ENGINE=InnoDB');
    $db->query("INSERT INTO course_27_usermeta(user_id,meta_key,meta_value) VALUES(999,'pjh5pwp_theme','legacy must remain untouched'),(7,'course_27_capabilities','role bytes')");
    $db->query('CREATE TABLE course_27_options(option_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,option_name VARCHAR(191) NOT NULL UNIQUE,option_value LONGTEXT NOT NULL,autoload VARCHAR(20) NOT NULL DEFAULT \'off\') ENGINE=MyISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $theme_hex=bin2hex(serialize(\Papijo\H5PThemerWordPress\Storage\Schema::initial()));
    $db->query("INSERT INTO course_27_options VALUES(1,'pjh5pwp_theme_library',UNHEX('$theme_hex'),'off'),(2,'pjh5pwp_role_permissions','roles','off'),(3,'snordiansh5pthemer_option','SNORDIAN','off'),(4,'active_plugins','activation','off')");
    $untouched=serialize(array(install_rows($db,'course_27_options'),install_rows($db,'course_27_usermeta')));$table='course_27_pjh5pwp_profiles';$log=array();$s=install_service($table,'',$log);
    install_check($s->status()['state']==='absent','Fresh read-only absence');install_check(install_ddl_count($log)===0,'Status never creates');
    install_check($s->ensure_installed()['state']==='ready','Fresh installation succeeds');install_check(install_ddl_count($log)===1,'Exactly one CREATE');install_check(install_rows($db,$table)===array(),'New table is empty');
    install_check($db->get_row("SHOW TABLE STATUS WHERE Name='$table'",'ARRAY_A')['Engine']==='InnoDB','Exact InnoDB engine');
    // A fresh Profile table and retry cannot repair a WordPress-owned MyISAM table.
    $options_snapshot=install_snapshot($db,'course_27_options');$options_log=array();
    $theme_store=new \Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store(static function()use(&$options_log){$owned=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$owned->suppress_errors(true);return array(new Installation_Mysql_Connection($owned,'','course_27_options',$options_log),'course_27_options');},static function(){});
    $themes=new \Papijo\H5PThemerWordPress\Storage\Storage_Installation($theme_store);
    foreach(array('status','initialize') as $operation){$result=$themes->$operation();install_check($result['state']==='storage_options_engine_unsupported'&&$result['outcome']==='not_committed','Native MyISAM options fail closed: '.$operation);}
    install_check($s->ensure_installed()['state']==='ready','Profile retry succeeds independently');
    install_check($themes->status()['state']==='storage_options_engine_unsupported','Profile retry cannot fix options engine');
    install_check($options_snapshot===install_snapshot($db,'course_27_options'),'Options schema, engine and all foreign bytes untouched');
    install_no_dml($options_log);install_check(install_ddl_count($options_log)===0,'Theme diagnostics never create tables');
    // Only the disposable test administrator performs this conversion.
    install_check($db->query('ALTER TABLE course_27_options ENGINE=InnoDB')!==false,'Fixture-controlled options correction');
    install_check($themes->status()['state']==='ready'&&$theme_store->read()===\Papijo\H5PThemerWordPress\Storage\Schema::initial(),'Native recheck reads original Theme bytes after correction');
    $log=array();install_check($s->ensure_installed()['state']==='ready'&&install_ddl_count($log)===0,'Repeat uses no DDL');
    install_check((new Profile_Installation(new Profile_Store()))->status()['state']==='ready','Default store uses nonstandard WordPress prefix');
    $store=new Profile_Store(install_factory($table,'',$log));$record=array('schema_version'=>1,'revision'=>37,'values'=>array_replace(Profile_State::defaults(),array('theme'=>'builtin:mint','density'=>'large')));$hex=bin2hex(serialize($record));
    $db->query("INSERT INTO `$table` VALUES(7,1,37,UNHEX('$hex'))");$snapshot=install_snapshot($db,$table);$log=array();
    install_check($s->status()['state']==='ready'&&$s->ensure_installed()['state']==='ready','Saved table validates');install_check($snapshot===install_snapshot($db,$table),'Existing schema, revisions, binary bytes preserved');install_check($store->read(7)===$record,'Existing canonical Profile record reloads exactly');
    $next=$record['values'];$next['density']='medium';install_check($store->compare_and_swap(7,$record,$next)==='committed','Existing CAS still commits');install_check($store->compare_and_swap(7,$record,$next)==='conflict','Stale revision still rejected');install_check($store->read(7)['revision']===38,'CAS increments exactly once');
    $db->query("INSERT INTO `$table` VALUES(8,99,81,UNHEX('00ff62696e617279'))");$snapshot=install_snapshot($db,$table);$log=array();
    install_check($s->status()['errors']===array('profile_schema_version_unsupported')&&$s->ensure_installed()['state']==='incompatible','Unknown stored format rejected');install_check($snapshot===install_snapshot($db,$table)&&install_ddl_count($log)===0,'Unknown version retained without DDL');$db->query("DELETE FROM `$table` WHERE user_id=8");
    $db->query("ALTER TABLE `$table` ENGINE=MyISAM");$snapshot=install_snapshot($db,$table);$log=array();install_check($s->ensure_installed()['errors']===array('profile_engine_unsupported'),'Wrong engine rejected');install_check($snapshot===install_snapshot($db,$table)&&install_ddl_count($log)===0,'No engine conversion');$db->query("ALTER TABLE `$table` ENGINE=InnoDB");
    $db->query("ALTER TABLE `$table` ADD unsupported INT");$snapshot=install_snapshot($db,$table);$log=array();install_check($s->ensure_installed()['state']==='incompatible','Wrong schema rejected');install_check($snapshot===install_snapshot($db,$table)&&install_ddl_count($log)===0,'No schema repair');$db->query("ALTER TABLE `$table` DROP unsupported");
    $log=array();$before=install_snapshot($db,$table);foreach(array(1,2,99) as $target){install_check($s->upgrade($target)['state']==='unsupported_upgrade','No automatic future migration');}install_check($before===install_snapshot($db,$table)&&$log===array(),'Upgrade boundary does not access database');
    foreach(array('denied'=>'failed','engine'=>'unavailable','metadata'=>'unavailable','readonly'=>'failed') as $fault=>$expected){
        $name='race_'.(100+strlen($fault)).'_pjh5pwp_profiles';$log=array();$service=install_service($name,$fault,$log);$result=$service->ensure_installed();install_check($result['state']===$expected,'Definite failure: '.$fault);install_check($db->get_results("SHOW TABLE STATUS WHERE Name='$name'",'ARRAY_A')===array(),'No table created on failure: '.$fault);install_no_dml($log);
    }
    foreach(array('lost_ack','throw_after','verification') as $offset=>$fault){
        $name='race_'.(200+$offset).'_pjh5pwp_profiles';$log=array();$result=install_service($name,$fault,$log)->ensure_installed();install_check($result['state']==='unknown'&&$result['outcome']==='unknown','Lost/uncertain DDL acknowledged conservatively: '.$fault);install_check(install_ddl_count($log)===1&&install_rows($db,$name)===array(),'No automatic retry/drop/user initialization after unknown');install_check(install_service($name,'',$log)->status()['state']==='ready','Read-only reconciliation after uncertain creation');install_no_dml($log);
    }
    $log=array();$name='race_300_pjh5pwp_profiles';$result=install_service($name,'disconnect_before',$log)->ensure_installed();
    install_check($result['state']==='unknown'&&$result['outcome']==='unknown','Real disconnected CREATE is uncertain');
    install_check($db->get_results("SHOW TABLE STATUS WHERE Name='$name'",'ARRAY_A')===array(),'wpdb never reconnects and replays CREATE');
    install_check(install_service($name,'',$log)->status()['state']==='absent','Read-only reconciliation confirms no table after lost connection');install_no_dml($log);
    // The new opt-in DDL guard must not change ordinary wpdb reconnection behavior.
    $normal=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$normal->suppress_errors(true);$oldThread=$normal->thread_id();
    $killer=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$killer->suppress_errors(true);
    try{$killer->query('KILL CONNECTION '.(int)$oldThread);}finally{$killer->close();}
    try{install_check((string)$normal->get_var('SELECT 42')==='42','Normal query still reconnects');install_check($normal->thread_id()!==$oldThread,'Only installer query_once disables reconnection');}finally{$normal->close();}
    for($round=0;$round<3;$round++){
        $name='race_'.$round.'_pjh5pwp_profiles';$pair=array(install_worker($c,$root,$name),install_worker($c,$root,$name));$workers=array_merge($workers,$pair);
        foreach($pair as $w){$ready=fgets($w[1][1]);if($ready!=="READY\n"){throw new RuntimeException('Barrier failed: '.($ready?:'').stream_get_contents($w[1][1]).stream_get_contents($w[1][2]));}install_check(true,'Concurrent caller reached CREATE after confirmed absence');}
        foreach($pair as $w){fwrite($w[1][0],"GO\n");fflush($w[1][0]);}
        foreach($pair as $w){install_check(install_finish($w)['state']==='ready','Concurrent ensure succeeds after independent verification');}
        install_check(install_rows($db,$name)===array(),'Concurrent installations leave one empty table');
    }
    install_check(serialize(array(install_rows($db,'course_27_options'),install_rows($db,'course_27_usermeta')))===$untouched,'Theme/default/publication/role/SNORDIAN/activation/legacy fixtures untouched');
    install_check($store->read(7)['revision']===38,'Installation tests never alter saved revision');
    try{$store->read(999);throw new RuntimeException('Expected legacy review');}catch(Storage_Exception $e){install_check($e->getMessage()==='profile_legacy_review_required','Legacy values not silently migrated');}
    echo 'PASS Profile installation MySQL '.$admin->server_info.': '.$checks.' assertions; three synchronized two-process installation races; real wpdb; fault injection only for unavailable engine/metadata/CREATE grants/acknowledgement; PHP '.PHP_VERSION.".\n";
}finally{
    foreach($workers as $w){foreach($w[1] as $pipe){if(is_resource($pipe)){fclose($pipe);}}if(is_resource($w[0])){proc_terminate($w[0]);proc_close($w[0]);}}
    if($created){if(!preg_match('/\Apjh5pwp_install_test_[a-f0-9]{16}\z/',$c['database'])){throw new RuntimeException('Unsafe cleanup refused');}if(!$admin->query('DROP DATABASE `'.$c['database'].'`')){throw new RuntimeException('Disposable cleanup failed');}$left=$admin->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$admin->real_escape_string($c['database'])."'")->fetch_row()[0];if($left!=='0'){throw new RuntimeException('Disposable database remains');}echo "Disposable installation database removed and absence verified.\n";}$admin->close();
}
