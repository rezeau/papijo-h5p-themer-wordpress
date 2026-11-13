<?php
/** F1 ISOLATED real wpdb test. Only random pjh5pwp_f1_* databases are selected.
 * Usage: php mysql.php <WordPress core source root> <literal credential source>
 */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
require dirname(__DIR__,2).'/foundation/owned-storage/bootstrap.php';
require dirname(__DIR__,2).'/includes/profiles.php';
require dirname(__DIR__).'/storage/wordpress-doubles.php';
require __DIR__.'/native-support.php';

use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\State_Schema;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\State_Table;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\Theme_Store;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\Role_Store;
use Papijo\H5PThemerWordPress\Foundation\OwnedStorage\Disposable_Installation;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Storage\Owned_WPDB;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
use Papijo\H5PThemerWordPress\Profile\Profile_Store;
use Papijo\H5PThemerWordPress\Profile\Profile_Service;
use Papijo\H5PThemerWordPress\Role\Role_Service;

function get_userdata($id){return (object)array('ID'=>$id,'roles'=>array('subscriber'));}
function user_can($user,$cap){return false;}
function current_user_can($cap,$id=0){return $cap==='edit_user'&&$id===123;}
function get_current_user_id(){return 123;}
function wp_roles(){return (object)array('roles'=>array('subscriber'=>array('name'=>'Subscriber','capabilities'=>array()),'teacher'=>array('name'=>'Teacher','capabilities'=>array())));}
function translate_user_role($name){return $name;}
function wp_create_nonce($action){return hash('sha256','f1-disposable-session:'.$action);}
function wp_verify_nonce($nonce,$action){return hash_equals(wp_create_nonce($action),$nonce);}
function f1_roles($revision,$roles=array('subscriber'=>true)){ksort($roles,SORT_STRING);return array('schema_version'=>1,'revision'=>$revision,'roles'=>$roles);}
function f1_ref($id){return array('kind'=>'builtin','id'=>$id,'version'=>Preset_Catalog::VERSION);}
function f1_adapters($mode=''){$table=new State_Table(f1_factory($mode));return array(new Theme_Store($table),new Role_Store($table));}

if(($argv[1]??'')==='--worker'){
	$input=json_decode(fgets(STDIN),true);$GLOBALS['f2_production']=!empty($input['production']);f1_boot($input['config'],$input['root']);$job=$input['job'];
	if($job['kind']==='install'){
		$i=new Disposable_Installation(f1_factory('create_barrier',$job['table']));echo json_encode(array('state'=>$i->ensure_installed()));exit;
	}
	if($job['kind']==='preference'){
		list($themes,$roles)=f1_adapters();$profiles=new Profile_Store(f1_profile_factory(true));
		$service=new Profile_Service(array($themes,'read'),$profiles,!empty($GLOBALS['f2_production'])?null:new Role_Service($roles));$nonce=wp_create_nonce($service->nonce_action(123));
		$result=$service->save_request(123,array('theme'=>'builtin:mint','density'=>'large'),$nonce);echo json_encode(array('result'=>$result,'writes'=>$GLOBALS['f1_writes']));exit;
	}
	list($themes,$roles)=f1_adapters(!empty($job['reload'])?'':'barrier');$store=$job['kind']==='theme'?$themes:$roles;$before=$store->read();
	if(!empty($job['reload'])){echo json_encode(array('state'=>$before));exit;}
	$next=$job['kind']==='theme'?($before===null?Schema::initial():(new Library_Changes())->set_default($before,f1_ref($job['value']))['data']):f1_roles($before===null?0:$before['revision']+1,$job['value']);
	$won=$store->compare_and_swap($before===null?null:$before['revision'],$next);echo json_encode(array('won'=>$won,'next'=>$next,'writes'=>$GLOBALS['f1_writes']));exit;
}
if(!isset($argv[1],$argv[2])){throw new RuntimeException('Provide WordPress source root and literal server credential source');}
$checks=0;function verify($v,$message){global $checks;++$checks;if(!$v){throw new RuntimeException($message);}}
function f1_snapshot($db,$table){return serialize(array($db->get_row("SHOW CREATE TABLE `$table`",'ARRAY_N'),$db->get_results("SELECT * FROM `$table` ORDER BY 1",'ARRAY_A')));}
function f1_exception(callable $request,$code,$outcome='not_committed'){
	try{$request();}catch(Storage_Exception $e){verify($e->getMessage()===$code,'Expected diagnostic '.$code);verify($e->outcome()===$outcome,'Expected outcome '.$outcome);return;}
	throw new RuntimeException('Expected storage exception: '.$code);
}
$GLOBALS['f2_production']=($argv[3]??'')==='--production';
$config=f1_credentials($argv[2]);$config['database']='pjh5pwp_f1_'.bin2hex(random_bytes(8));$root=$argv[1];$created=false;$workers=array();$server=new mysqli($config['DB_HOST'],$config['DB_USER'],$config['DB_PASSWORD']);
try{
	if(!$server->query('CREATE DATABASE `'.$config['database'].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')){throw new RuntimeException('Disposable database creation failed');}$created=true;f1_boot($config,$root);$db=$GLOBALS['wpdb'];
	verify($db->query("CREATE TABLE course_27_options (option_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, option_name VARCHAR(191) NOT NULL UNIQUE, option_value LONGTEXT NOT NULL, autoload VARCHAR(20) NOT NULL DEFAULT 'off') ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci")!==false,'Disposable WordPress-shaped MyISAM options');
	$seed=array('pjh5pwp_theme_library'=>serialize(Schema::initial()),'pjh5pwp_role_permissions'=>serialize(f1_roles(14)));if(!empty($GLOBALS['f2_production'])){$seed=array();}
	foreach(array_merge($seed,array('snordiansh5pthemer_option'=>'foreign SNORDIAN bytes','active_plugins'=>'foreign activation bytes','unrelated_option'=>'foreign settings','course_27_user_roles'=>'core role bytes')) as $name=>$value){verify($db->query($db->prepare('INSERT INTO course_27_options (option_name,option_value) VALUES (%s,%s)',$name,$value))===1,'Seed protected disposable option');}
	verify($db->query('CREATE TABLE course_27_usermeta (umeta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, meta_key VARCHAR(255), meta_value LONGTEXT) ENGINE=MyISAM')!==false,'Disposable legacy/core metadata');
	verify($db->query("INSERT INTO course_27_usermeta(user_id,meta_key,meta_value) VALUES(456,'course_27_capabilities','preserve core membership')")===1,'Seed unrelated user metadata');
	$profiles=new Profile_Store();verify($profiles->install()==='ready','Separate existing InnoDB Profile table');
	$profile=array('schema_version'=>1,'revision'=>37,'values'=>array_replace(Profile_State::defaults(),array('theme'=>'core','density'=>'small')));
	foreach(array(123,456) as $id){verify($db->query($db->prepare('INSERT INTO course_27_pjh5pwp_profiles VALUES (%d,1,37,UNHEX(%s))',$id,bin2hex(serialize($profile))))===1,'Existing Profile record');}
	$protected=f1_snapshot($db,'course_27_options');$usermeta=f1_snapshot($db,'course_27_usermeta');$profile_schema=$db->get_row('SHOW CREATE TABLE course_27_pjh5pwp_profiles','ARRAY_N');$other_profile=$profiles->read(456);
	// The retained historical options adapter still fails closed on MyISAM.
	f1_exception(static function(){(new \Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store())->read();},'storage_options_engine_unsupported');
	$install=new Disposable_Installation(f1_factory());verify($install->status()==='absent','New table absence read-only');
	verify($db->get_results("SHOW TABLE STATUS WHERE Name='course_27_pjh5pwp_state'",'ARRAY_A')===array(),'Status never creates');
	list($themes,$roles)=f1_adapters();f1_exception(static function()use($themes){$themes->read();},'owned_state_installation_required');
	verify($install->ensure_installed()==='ready','Explicit disposable state creation');verify($install->ensure_installed()==='ready','Repeat verification only');verify((int)$db->get_var('SELECT COUNT(*) FROM course_27_pjh5pwp_state')===0,'No automatic Theme/grant migration or initialization');
	verify($themes->read()===null&&$roles->read()===null,'Old option contents are not read or migrated');
	$authorization=!empty($GLOBALS['f2_production'])?new Role_Service():new Role_Service($roles);verify(!$authorization->allowed(123),'Missing owned grant defaults denied despite legacy option grant');$called=false;
	verify($authorization->while_allowed(123,static function()use(&$called){$called=true;return 'unexpected';})==='forbidden_preference'&&!$called,'Missing locked permission snapshot never invokes Profile save');
	verify((int)$db->get_var('SELECT COUNT(*) FROM course_27_pjh5pwp_state')===0,'Default denial never initializes either state record');
	$changes=new Library_Changes();
	foreach(array('theme','roles') as $kind){
		$store=$kind==='theme'?$themes:$roles;$identity=null;
		for($round=0;$round<4;$round++){
			$before=$store->read();$pair=array();foreach($kind==='theme'?array('mint','dark'):array(array('subscriber'=>true),array('teacher'=>true)) as $value){$pair[]=f1_worker($config,$root,array('kind'=>$kind,'value'=>$value));}
			$workers=array_merge($workers,$pair);foreach($pair as $w){f1_ready($w);verify(true,'Independent writer reached INSERT/UPDATE after internal read');}foreach($pair as $w){f1_go($w);}
			$results=array_map('f1_finish',$pair);$wins=array_values(array_filter($results,static function($r){return $r['won'];}));verify(count($wins)===1,'Exactly one '.$kind.' initializer/writer wins');verify(array_column($results,'writes')===array(1,1),'No write retries');verify($store->read()===$wins[0]['next'],'Whole winning aggregate retained');verify($store->read()['revision']===($before===null?0:$before['revision']+1),'One monotonic revision');
			$key=$kind==='theme'?State_Schema::THEME:State_Schema::ROLES;$id=$db->get_var($db->prepare('SELECT record_id FROM course_27_pjh5pwp_state WHERE state_key=%s',$key));if($identity===null){$identity=$id;}verify($id===$identity,'Record identity stable through updates');
		}
	}
	$custom=(new Theme_Operations())->duplicate(f1_ref('mint'),'example','Été 🎨')['data'];$custom['colors']['--h5p-theme-background']='#AbC';$custom['density']='small';$before=$themes->read();$next=$changes->create($before,$custom)['data'];verify($themes->compare_and_swap($before['revision'],$next),'Native Unicode/custom aggregate');verify($themes->read()===$next,'Unicode, hex case and legacy metadata exact');verify($db->get_var("SELECT payload FROM course_27_pjh5pwp_state WHERE state_key='theme_library'")===serialize($next),'Native payload exact serialization');
	// Neither state adapter may join or commit an ambient WordPress connection's transaction.
	$ambient_before=$themes->read();$ambient_next=$changes->set_default($ambient_before,f1_ref('sunset'))['data'];
	verify($db->query('START TRANSACTION')!==false,'Start unrelated ambient transaction');
	try{
		verify($db->query('UPDATE course_27_pjh5pwp_profiles SET revision=revision+100 WHERE user_id=456')===1,'Pending unrelated disposable Profile change');
		verify($themes->compare_and_swap($ambient_before['revision'],$ambient_next),'Theme CAS independent of ambient session');
		$role_before=$roles->read();verify($roles->compare_and_swap($role_before['revision'],f1_roles($role_before['revision']+1)),'Role CAS independent of ambient session');
	}finally{$db->query('ROLLBACK');}
	verify($themes->read()===$ambient_next,'Owned Theme commit survives unrelated rollback');verify($profiles->read(456)===$other_profile,'Neither adapter commits unrelated pending data');
	$before=$themes->read();$next=$changes->set_default($before,f1_ref('mint'))['data'];verify(!$themes->compare_and_swap($before['revision']-1,$next),'Stale displayed revision rejected');
	$readonly=new Theme_Store(new State_Table(f1_factory('readonly')));f1_exception(static function()use($readonly,$before,$next){$readonly->compare_and_swap($before['revision'],$next);},'storage_write_failed');verify($themes->read()===$before,'Real SQL rejection leaves revision/payload unchanged');
	$unknown=new Theme_Store(new State_Table(f1_factory('ack')));f1_exception(static function()use($unknown,$before,$next){$unknown->compare_and_swap($before['revision'],$next);},'storage_write_unknown','unknown');verify($themes->read()===$next,'Lost acknowledgement reconciled read-only');
	foreach(array('kill_before','kill_after') as $fault){
		$before=$themes->read();$next=$changes->set_default($before,f1_ref('dark'))['data'];$lost=new Theme_Store(new State_Table(f1_factory($fault)));$attempts=$GLOBALS['f1_writes'];
		f1_exception(static function()use($lost,$before,$next){$lost->compare_and_swap($before['revision'],$next);},'storage_write_unknown','unknown');
		verify($GLOBALS['f1_writes']===$attempts+1,'Disconnected write never replayed');verify($themes->read()===($fault==='kill_before'?$before:$next),'Real disconnected before/after write reconciles without blind rollback');
	}
	$next=$themes->read();
	$reload=f1_worker($config,$root,array('kind'=>'theme','reload'=>true));$workers[]=$reload;verify(f1_finish($reload)['state']===$next,'Fresh-process binary reload');
	// Real row-lock exclusion serializes unchanged Profile_Service against revocation.
	$before=$roles->read();verify($roles->compare_and_swap($before['revision'],f1_roles($before['revision']+1)),'Grant subscriber');
	$learner=f1_worker($config,$root,array('kind'=>'preference'));$workers[]=$learner;f1_ready($learner);verify(true,'Actual Profile_Service reaches Profile SQL while shared role lock held');
	$before=$roles->read();$candidate=f1_roles($before['revision']+1,array());$probe=new Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$probe->suppress_errors(true);$probe->query('SET SESSION innodb_lock_wait_timeout=1');
	$sql=$probe->prepare("UPDATE course_27_pjh5pwp_state SET revision=%d,payload=UNHEX(%s) WHERE state_key=%s AND BINARY payload=UNHEX(%s)",$candidate['revision'],bin2hex(serialize($candidate)),State_Schema::ROLES,bin2hex(serialize($before)));
	verify($probe->query_once($sql)===false&&$probe->error_number()===1205,'Exclusive revocation blocked by shared permission lock');$probe->close();verify($roles->read()===$before,'Failed lock probe changed no grants');
	$theme_before=$themes->read();$theme_next=$changes->set_default($theme_before,f1_ref('dark'))['data'];verify($themes->compare_and_swap($theme_before['revision'],$theme_next),'Separate Theme row remains writable during role lock');
	$revoker=f1_worker($config,$root,array('kind'=>'roles','value'=>array()));$workers[]=$revoker;f1_ready($revoker);f1_go($revoker);f1_go($learner);
	verify(f1_finish($learner)['result']==='committed','Complete authorized Profile save finishes');verify(f1_finish($revoker)['won'],'Revocation commits after lock release');verify($roles->read()['roles']===array(),'Revoked final permission state');
	$saved=$profiles->read(123);verify($saved['revision']===38&&$saved['values']['theme']==='builtin:mint'&&$saved['values']['density']==='large','Whole Profile saved once with independent Theme/Density fields');
	$denied=f1_worker($config,$root,array('kind'=>'preference'));$workers[]=$denied;$result=f1_finish($denied);verify($result['result']==='forbidden_preference'&&$result['writes']===0,'Prior revocation rejects next Profile save before SQL');verify($profiles->read(123)===$saved,'Denied save preserves exact Profile state');
	// Native identity/byte ABA guards: inject an out-of-contract change at write boundary.
	foreach(array('identity','bytes') as $fault){
		$before=$themes->read();$worker=f1_worker($config,$root,array('kind'=>'theme','value'=>'mint'));$workers[]=$worker;f1_ready($worker);
		if($fault==='identity'){
			$row=$db->get_row("SELECT * FROM course_27_pjh5pwp_state WHERE state_key='theme_library'",'ARRAY_A');verify($db->query("DELETE FROM course_27_pjh5pwp_state WHERE state_key='theme_library'")===1,'Disposable rogue deletes row');verify($db->query($db->prepare('INSERT INTO course_27_pjh5pwp_state(state_key,schema_version,revision,payload) VALUES (%s,1,%d,UNHEX(%s))',State_Schema::THEME,$before['revision'],bin2hex($row['payload'])))===1,'Disposable rogue recreates identical bytes/revision');
		}else{$altered=$before;$altered['settings']['default_theme']=f1_ref('sunset');verify($db->query($db->prepare("UPDATE course_27_pjh5pwp_state SET payload=UNHEX(%s) WHERE state_key='theme_library'",bin2hex(serialize($altered))))===1,'Disposable rogue changes bytes without revision');}
		f1_go($worker);verify(!f1_finish($worker)['won'],'Native '.$fault.' mismatch rejects pending CAS');
	}
	// Incompatible owned table is refused without repair; only test code changes it.
	verify($db->query('ALTER TABLE course_27_pjh5pwp_state ENGINE=MyISAM')!==false,'Disposable fault changes owned engine');$wrong=f1_snapshot($db,'course_27_pjh5pwp_state');f1_exception(static function()use($install){$install->ensure_installed();},'owned_state_engine_unsupported');verify(f1_snapshot($db,'course_27_pjh5pwp_state')===$wrong,'No engine conversion by installer');verify($db->query('ALTER TABLE course_27_pjh5pwp_state ENGINE=InnoDB')!==false,'Disposable owned engine restored');
	verify($db->query('ALTER TABLE course_27_pjh5pwp_state ADD unsupported INT')!==false,'Disposable schema fault');$wrong=f1_snapshot($db,'course_27_pjh5pwp_state');f1_exception(static function()use($themes){$themes->read();},'owned_state_schema_unsupported');verify(f1_snapshot($db,'course_27_pjh5pwp_state')===$wrong,'No schema repair');verify($db->query('ALTER TABLE course_27_pjh5pwp_state DROP unsupported')!==false,'Disposable schema restored');
	for($round=0;$round<2;$round++){
		$table='race_'.$round.'_pjh5pwp_state';$pair=array(f1_worker($config,$root,array('kind'=>'install','table'=>$table)),f1_worker($config,$root,array('kind'=>'install','table'=>$table)));$workers=array_merge($workers,$pair);foreach($pair as $w){f1_ready($w);}foreach($pair as $w){f1_go($w);}foreach($pair as $w){verify(f1_finish($w)['state']==='ready','Concurrent disposable creation verified independently');}verify((int)$db->get_var("SELECT COUNT(*) FROM `$table`")===0,'Concurrent creators seed no state');
	}
	$ddl_lost=new Disposable_Installation(f1_factory('create_kill_before','lost_pjh5pwp_state'));
	f1_exception(static function()use($ddl_lost){$ddl_lost->ensure_installed();},'owned_state_installation_unknown','unknown');
	verify((new Disposable_Installation(f1_factory('','lost_pjh5pwp_state')))->status()==='absent','Disconnected CREATE not replayed; absence reconciled read-only');
	verify(f1_snapshot($db,'course_27_options')===$protected,'MyISAM WordPress options schema/engine/all bytes unchanged');verify(f1_snapshot($db,'course_27_usermeta')===$usermeta,'Core/legacy user metadata unchanged');verify($db->get_row('SHOW CREATE TABLE course_27_pjh5pwp_profiles','ARRAY_N')===$profile_schema,'Existing Profile schema unchanged');verify($profiles->read(456)===$other_profile,'Unrelated Profile identity/revision/bytes unchanged');
	foreach($GLOBALS['f1_sql'] as $sql){verify(!preg_match('/\A(?:ALTER|DROP|DELETE|REPLACE|TRUNCATE)\b/',$sql),'Foundation issues no repair/destructive SQL');if(preg_match('/\A(?:INSERT|UPDATE|CREATE)\b/',$sql)){verify(strpos($sql,'pjh5pwp_state')!==false,'Foundation writes only its owned state table');}verify(strpos($sql,'course_27_options')===false&&strpos($sql,'course_27_usermeta')===false,'State algorithm never accesses WordPress tables (production legacy probe is separately read-only)');}
	$filters=0;foreach($GLOBALS['pjh5pwp_test_filters']['query']??array() as $callbacks){foreach($callbacks as $callback){if(is_array($callback)&&$callback[0] instanceof Owned_WPDB){++$filters;}}}verify($filters===0,'Owned-session placeholder filters cleaned up');
	if(!empty($GLOBALS['f2_production'])){
        foreach(array('pjh5pwp_theme_library','pjh5pwp_role_permissions') as $key){
            $db->query($db->prepare('INSERT INTO course_27_options(option_name,option_value) VALUES (%s,%s)',$key,'malformed legacy bytes'));
            $before=f1_snapshot($db,'course_27_options');$destination=f1_snapshot($db,'course_27_pjh5pwp_state');
            f1_exception(static function(){(new \Papijo\H5PThemerWordPress\Storage\Owned\Theme_Store())->read();},'owned_state_legacy_unsupported');
            verify(!(new Role_Service())->allowed(123),'Default authorization blocks reintroduced legacy state');
            verify($before===f1_snapshot($db,'course_27_options')&&$destination===f1_snapshot($db,'course_27_pjh5pwp_state'),'Legacy refusal preserves both backends');
            $db->query($db->prepare('DELETE FROM course_27_options WHERE option_name=%s',$key)); // Fixture only, random disposable DB.
        }
    }
	echo (!empty($GLOBALS['f2_production'])?'PASS F2 PRODUCTION factories native owned storage: ':'PASS F1 ISOLATED native owned storage: ').$checks.' assertions; MyISAM options + InnoDB state/Profile; eight synchronized CAS races, two create races, real role-lock/Profile serialization; MySQL '.$server->server_info.'; PHP '.PHP_VERSION.".\n";
}finally{
	foreach($workers as $w){foreach($w[1] as $pipe){if(is_resource($pipe)){fclose($pipe);}}if(is_resource($w[0])){proc_terminate($w[0]);proc_close($w[0]);}}
	if(isset($GLOBALS['wpdb'])){$GLOBALS['wpdb']->close();}
	if($created){if(!preg_match('/\Apjh5pwp_f1_[a-f0-9]{16}\z/',$config['database'])){throw new RuntimeException('Unsafe cleanup refused');}if(!$server->query('DROP DATABASE `'.$config['database'].'`')){throw new RuntimeException('Disposable cleanup failed');}$count=$server->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$server->real_escape_string($config['database'])."'")->fetch_row()[0];if($count!=='0'){throw new RuntimeException('Disposable database remains');}echo "Disposable F1 database removed and absence verified.\n";}$server->close();
}
