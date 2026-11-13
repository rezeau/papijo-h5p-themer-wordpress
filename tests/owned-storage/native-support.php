<?php
/** F1 native harness helpers. Never execute wp-config or select its DB_NAME.
 * @license GPL-3.0-or-later
 */
function f1_credentials($path) {
	$tokens=token_get_all(file_get_contents($path));$result=array();
	$decode=static function($s){$v=substr($s,1,-1);return $s[0]==="'"?str_replace(array("\\'","\\\\"),array("'","\\"),$v):stripcslashes($v);};
	for($i=0;$i<count($tokens);$i++){
		if(is_array($tokens[$i])&&$tokens[$i][0]===T_STRING&&strtolower($tokens[$i][1])==='define'){
			$strings=array();for($j=$i+1;$j<count($tokens)&&$tokens[$j]!==')';$j++){if(is_array($tokens[$j])&&$tokens[$j][0]===T_CONSTANT_ENCAPSED_STRING){$strings[]=$decode($tokens[$j][1]);}}
			if(count($strings)===2&&in_array($strings[0],array('DB_HOST','DB_USER','DB_PASSWORD'),true)){$result[$strings[0]]=$strings[1];}
		}
	}
	if(count($result)!==3){throw new RuntimeException('Literal server credentials required');}return $result;
}
function f1_boot(array $config,$root) {
	if(!preg_match('/\Apjh5pwp_f1_[a-f0-9]{16}\z/',$config['database'])){throw new RuntimeException('Non-disposable database refused');}
	foreach(array_merge($config,array('DB_NAME'=>$config['database'],'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_DEBUG'=>false,'WP_DEBUG_DISPLAY'=>false)) as $key=>$value){if($key!=='database'){define($key,$value);}}
	require $root.'/wp-includes/class-wpdb.php';
	$GLOBALS['wpdb']=new wpdb(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$GLOBALS['wpdb']->suppress_errors(true);$GLOBALS['wpdb']->set_prefix('course_27_');
	if(!$GLOBALS['wpdb']->ready){throw new RuntimeException('Disposable connection failed');}
	require dirname(__DIR__,2).'/includes/Storage/class-owned-wpdb.php';
	$GLOBALS['f1_sql']=array();$GLOBALS['f1_writes']=0;
}
final class F1_Connection {
	private $db,$mode,$changed=false;
	public function __construct($db,$mode){$this->db=$db;$this->mode=$mode;}
	public function __get($name){return $this->db->$name;}
	public function __isset($name){return $name==='last_error'||isset($this->db->$name);}
	public function __call($name,$args){return call_user_func_array(array($this->db,$name),$args);}
	public function get_results($sql,$format){$GLOBALS['f1_sql'][]=$sql;return $this->db->get_results($sql,$format);}
	private function execute($sql,$once){
		$GLOBALS['f1_sql'][]=$sql;
		$write=(bool)preg_match('/\A(?:INSERT|UPDATE)\b/',$sql);$create=strpos($sql,'CREATE TABLE')===0;
		if($write){++$GLOBALS['f1_writes'];}
		if(($write&&in_array($this->mode,array('barrier','profile_barrier'),true))||($create&&$this->mode==='create_barrier')){
			fwrite(STDOUT,"READY\n");fflush(STDOUT);if(fgets(STDIN)!=="GO\n"){throw new RuntimeException('Disposable SQL barrier interrupted');}
		}
		if(($write&&$this->mode==='kill_before')||($create&&$this->mode==='create_kill_before')){$this->disconnect();}
		$result=$once?$this->db->query_once($sql):$this->db->query($sql);
		// Model the transport detecting loss before the adapter receives acknowledgement.
		// KILL alone can leave mysqli_thread_id() cached after an already-known result.
		if($write&&$this->mode==='kill_after'){$this->disconnect();$this->db->close();}
		if($write&&$this->mode==='ack'){$this->changed=true;}
		return $result;
	}
	private function disconnect(){
		if(!preg_match('/\Apjh5pwp_f1_[a-f0-9]{16}\z/',DB_NAME)){throw new RuntimeException('Unsafe disconnect fixture');}
		$id=$this->db->thread_id();if(!$id){throw new RuntimeException('Disposable session ID unavailable');}
		$killer=new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$killer->suppress_errors(true);
		try{if($killer->query('KILL CONNECTION '.(int)$id)===false){throw new RuntimeException('Disposable disconnect failed');}}finally{$killer->close();}
	}
	public function query($sql){return $this->execute($sql,false);}
	public function query_once($sql){return $this->execute($sql,true);}
	public function thread_id(){return $this->db->thread_id()+($this->changed?1:0);}
}
function f1_factory($mode='',$table='course_27_pjh5pwp_state') {
	\Papijo\H5PThemerWordPress\Foundation\OwnedStorage\State_Schema::identifier($table);
	return static function()use($mode,$table){
		if(!preg_match('/\Apjh5pwp_f1_[a-f0-9]{16}\z/',DB_NAME)){throw new RuntimeException('Refusing existing site database');}
		if(!empty($GLOBALS['f2_production'])){list($db)=(new \Papijo\H5PThemerWordPress\Storage\Owned\State_Connections())->open();}else{$db=new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);}
		if($mode==='readonly'){$db->query('SET SESSION transaction_read_only=1');}
		return array(new F1_Connection($db,$mode),$table);
	};
}
function f1_profile_factory($barrier=false){return static function()use($barrier){
	if(!preg_match('/\Apjh5pwp_f1_[a-f0-9]{16}\z/',DB_NAME)){throw new RuntimeException('Refusing existing site database');}
	$db=new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$db->suppress_errors(true);
	return array(new F1_Connection($db,$barrier?'profile_barrier':''),'course_27_pjh5pwp_profiles','course_27_usermeta');
};}
function f1_worker($config,$root,array $job){
	$p=proc_open(array(PHP_BINARY,'-n','-d','extension_dir='.ini_get('extension_dir'),'-d','extension=mysqli',__DIR__.'/mysql.php','--worker'),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes,dirname(__DIR__,2));
	if(!is_resource($p)){throw new RuntimeException('Cannot start disposable worker');}
	fwrite($pipes[0],json_encode(array('config'=>$config,'root'=>$root,'job'=>$job,'production'=>!empty($GLOBALS['f2_production'])))."\n");fflush($pipes[0]);return array($p,$pipes);
}
function f1_ready($worker){$ready=fgets($worker[1][1]);if($ready!=="READY\n"){throw new RuntimeException('Worker did not reach SQL barrier: '.($ready?:'').stream_get_contents($worker[1][1]).stream_get_contents($worker[1][2]));}}
function f1_go($worker){fwrite($worker[1][0],"GO\n");fflush($worker[1][0]);}
function f1_finish($worker){
	if(is_resource($worker[1][0])){fclose($worker[1][0]);}
	$out=stream_get_contents($worker[1][1]);$err=stream_get_contents($worker[1][2]);fclose($worker[1][1]);fclose($worker[1][2]);$code=proc_close($worker[0]);
	if($code||$err!==''){throw new RuntimeException('Disposable worker failed: '.$out.$err);}$result=json_decode($out,true);if(!is_array($result)){throw new RuntimeException('Invalid worker output');}return $result;
}
