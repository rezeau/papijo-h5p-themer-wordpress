<?php
/** Private-session, one-statement InnoDB profile CAS. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Profile;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
final class Profile_Store {
	private $factory;
	public function __construct($factory=null){$this->factory=$factory;}
	private function open(){
		if($this->factory){$parts=call_user_func($this->factory);}else{
			if(is_multisite()||!isset($GLOBALS['wpdb'])||get_class($GLOBALS['wpdb'])!=='wpdb'||strpos(DB_HOST,'p:')===0){throw new Storage_Exception('profile_environment_unsupported');}
			require_once dirname(__DIR__).'/Storage/class-owned-wpdb.php';
			$db=new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);
			$parts=array($db,$GLOBALS['wpdb']->prefix.'pjh5pwp_profiles',$GLOBALS['wpdb']->usermeta);
		}
		foreach(array_slice($parts,1) as $name){if(!is_string($name)||!preg_match('/\A[A-Za-z0-9_]+\z/',$name)){throw new Storage_Exception('profile_environment_unsupported');}}return $parts;
	}
	private function rows($db,$sql){$r=$db->get_results($sql,'ARRAY_A');if(!is_array($r)||$db->last_error){throw new Storage_Exception('profile_database_failed');}return $r;}
	private function verify($db,$table){
		$r=$this->rows($db,$db->prepare('SHOW TABLE STATUS WHERE Name=%s',$table));
		if(count($r)!==1||$r[0]['Engine']!=='InnoDB'){throw new Storage_Exception('profile_installation_required');}
		$c=$this->rows($db,"SHOW FULL COLUMNS FROM `$table`");$expected=array('user_id'=>'bigint unsigned','schema_version'=>'int unsigned','revision'=>'bigint unsigned','state'=>'longblob');
		if(count($c)!==4){throw new Storage_Exception('profile_schema_unsupported');}
		foreach($c as $column){$type=preg_replace('/\([0-9]+\)/','',strtolower($column['Type']));if(!isset($expected[$column['Field']])||$expected[$column['Field']]!==$type||$column['Null']!=='NO'||$column['Extra']!==''){throw new Storage_Exception('profile_schema_unsupported');}}
		$i=$this->rows($db,"SHOW INDEX FROM `$table`");if(count($i)!==1||$i[0]['Key_name']!=='PRIMARY'||$i[0]['Column_name']!=='user_id'||(int)$i[0]['Non_unique']!==0){throw new Storage_Exception('profile_schema_unsupported');}
		if($this->rows($db,$db->prepare('SHOW TRIGGERS WHERE `Table`=%s',$table))){throw new Storage_Exception('profile_schema_unsupported');}
		$a=$this->rows($db,'SELECT @@session.autocommit AS active');if((int)$a[0]['active']!==1){throw new Storage_Exception('profile_environment_unsupported');}
	}
	/** Installation-only inspection. An empty metadata result alone is not absence. */
	private function inspect_installation($db,$table){
		$a=$this->rows($db,'SELECT @@session.autocommit AS active');
		if(count($a)!==1||(int)$a[0]['active']!==1){throw new Storage_Exception('profile_environment_unsupported');}
		$r=$this->rows($db,$db->prepare('SHOW TABLE STATUS WHERE Name=%s',$table));
		if(!$r){
			// Permission/metadata failures must never authorize CREATE. Only 1146 does.
			try{$probe=$db->query("SELECT 1 FROM `$table` LIMIT 0");}catch(\Throwable $e){throw new Storage_Exception('profile_metadata_unavailable');}
			if($probe===false&&$db->error_number()===1146){return 'absent';}
			throw new Storage_Exception('profile_metadata_unavailable');
		}
		if(count($r)!==1||!array_key_exists('Engine',$r[0])){throw new Storage_Exception('profile_metadata_unavailable');}
		if($r[0]['Engine']!=='InnoDB'){throw new Storage_Exception('profile_engine_unsupported');}
		$this->verify($db,$table);
		// A future record format must not be declared ready or silently migrated.
		if($this->rows($db,"SELECT user_id FROM `$table` WHERE schema_version <> 1 LIMIT 1")){throw new Storage_Exception('profile_schema_version_unsupported');}
		return 'ready';
	}
	/** Read-only; never creates storage or preference records. */
	public function installation_status(){
		list($db,$table)=$this->open();try{
			$thread=$db->thread_id();$state=$this->inspect_installation($db,$table);
			if(!$thread||$thread!==$db->thread_id()){throw new Storage_Exception('profile_metadata_unavailable');}
			return $state;
		}finally{$db->close();}
	}
	/** Explicit installation only: never called from activation, GET or saves. */
	public function install(){
		list($db,$table)=$this->open();$attempted=false;try{
			$thread=$db->thread_id();$state=$this->inspect_installation($db,$table);
			if(!$thread||$thread!==$db->thread_id()){throw new Storage_Exception('profile_metadata_unavailable');}
			if($state==='ready'){return 'ready';}
			$engines=$this->rows($db,'SHOW ENGINES');$innodb=null;
			foreach($engines as $engine){if(strcasecmp($engine['Engine']??'','InnoDB')===0){$innodb=$engine['Support']??null;}}
			if($innodb===null){throw new Storage_Exception('profile_metadata_unavailable');}
			if(!in_array(strtoupper($innodb),array('YES','DEFAULT'),true)){throw new Storage_Exception('profile_innodb_unavailable');}
			// Private session only. Prevent fallback to another engine if availability changes.
			if($db->query("SET SESSION sql_mode = CONCAT_WS(',', NULLIF(@@session.sql_mode,''), 'NO_ENGINE_SUBSTITUTION')")===false){throw new Storage_Exception('profile_environment_unsupported');}
			if($thread!==$db->thread_id()){throw new Storage_Exception('profile_metadata_unavailable');}
			$attempted=true;
			try{$r=$db->query_once("CREATE TABLE IF NOT EXISTS `$table` (user_id BIGINT UNSIGNED NOT NULL, schema_version INT UNSIGNED NOT NULL, revision BIGINT UNSIGNED NOT NULL, state LONGBLOB NOT NULL, PRIMARY KEY(user_id)) ENGINE=InnoDB");}
			catch(\Throwable $e){throw new Storage_Exception('profile_installation_unknown','unknown');}
			if($thread!==$db->thread_id()||!$db->thread_id()||($r===false&&in_array($db->error_number(),array(0,2006,2013,2055),true))){throw new Storage_Exception('profile_installation_unknown','unknown');}
			if($r===false){throw new Storage_Exception(in_array($db->error_number(),array(1044,1142,1143,1227),true)?'profile_installation_denied':'profile_installation_failed');}
			try{
				if($this->inspect_installation($db,$table)!=='ready'||$thread!==$db->thread_id()){throw new Storage_Exception('profile_installation_verification_failed');}
			}catch(\Throwable $e){throw new Storage_Exception($e instanceof Storage_Exception?$e->getMessage():'profile_installation_verification_failed','unknown');}
			return 'ready';
		}finally{
			try{$db->close();}catch(\Throwable $e){throw new Storage_Exception($attempted?'profile_installation_unknown':'profile_database_failed',$attempted?'unknown':'not_committed');}
		}
	}
	private function row($db,$table,$legacy,$id){
		// Undeployed four-key format is not silently imported or ignored.
		$r=$this->rows($db,$db->prepare("SELECT umeta_id FROM `$legacy` WHERE user_id=%d AND meta_key IN ('pjh5pwp_allow_theme','pjh5pwp_allow_density','pjh5pwp_theme','pjh5pwp_density') LIMIT 1",$id));
		if($r){throw new Storage_Exception('profile_legacy_review_required');}
		$r=$this->rows($db,$db->prepare("SELECT schema_version,revision,state FROM `$table` WHERE user_id=%d",$id));if(!$r){return null;}
		$bytes=$r[0]['state'];if(strlen($bytes)>4096){throw new Storage_Exception('profile_state_invalid');}
		$v=@unserialize($bytes,array('allowed_classes'=>false));
		if(!Profile_State::valid($v)||serialize($v)!==$bytes||(string)$v['schema_version']!==(string)$r[0]['schema_version']||(string)$v['revision']!==(string)$r[0]['revision']){throw new Storage_Exception('profile_state_invalid');}return $v;
	}
	public function read($id){if(!is_int($id)||$id<=0){throw new Storage_Exception('profile_user_invalid');}list($db,$table,$legacy)=$this->open();try{$this->verify($db,$table);return $this->row($db,$table,$legacy,$id);}finally{$db->close();}}
	public function compare_and_swap($id,$expected,array $values){
		if(!is_int($id)||$id<=0||($expected!==null&&!Profile_State::valid($expected))){return 'validation_failed';}
		$revision=$expected===null?0:$expected['revision']+1;$next=array('schema_version'=>1,'revision'=>$revision,'values'=>$values);
		if(!Profile_State::valid($next)){return 'validation_failed';}
		list($db,$table,$legacy)=$this->open();try{
			$this->verify($db,$table);$current=$this->row($db,$table,$legacy,$id);if($current!==$expected){return 'conflict';}
			if($values===($expected['values']??Profile_State::defaults())){return 'noop';}
			$bytes=bin2hex(serialize($next));
			$sql=$expected===null?$db->prepare("INSERT INTO `$table` (user_id,schema_version,revision,state) VALUES (%d,1,0,UNHEX(%s))",$id,$bytes):$db->prepare("UPDATE `$table` SET revision=%d,state=UNHEX(%s) WHERE user_id=%d AND schema_version=1 AND revision=%d AND BINARY state=UNHEX(%s)",$revision,$bytes,$id,$expected['revision'],bin2hex(serialize($expected)));
			$thread=$db->thread_id();try{$count=$db->query($sql);}catch(\Throwable $e){throw new Storage_Exception('profile_write_unknown','unknown');}
			if(!$thread||$thread!==$db->thread_id()){throw new Storage_Exception('profile_write_unknown','unknown');}
			if($count===1){return 'committed';}if($count===0||($expected===null&&$count===false&&$db->error_number()===1062)){return 'conflict';}
			throw new Storage_Exception('profile_database_failed');
		}finally{$db->close();}
	}
}
