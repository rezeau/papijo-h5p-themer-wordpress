<?php
/** F1 memory-only protocol/fault fixture; native tests establish actual locking.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Tests\OwnedStorage;

final class Database {
	public $records = array(), $log = array(), $next_id = 1, $opened = 0, $closed = 0;
	public $exists = true, $engine = 'InnoDB', $autocommit = 1, $triggers = array();
	public $database = 'pjh5pwp_f1_0123456789abcdef', $table = 'course_27_pjh5pwp_state';
	public $columns, $indexes, $fault = '', $before_write, $before_create;
	public $legacy = array();
	public function __construct() {
		$this->columns = array();
		foreach (array('record_id'=>'bigint unsigned','state_key'=>'varbinary(32)','schema_version'=>'int unsigned','revision'=>'bigint unsigned','payload'=>'longblob') as $name=>$type) { $this->columns[]=array('Field'=>$name,'Type'=>$type,'Null'=>'NO','Default'=>null,'Extra'=>$name==='record_id'?'auto_increment':''); }
		$this->indexes=array();
		foreach (array('PRIMARY'=>'record_id','state_key'=>'state_key') as $key=>$column) { $this->indexes[]=array('Key_name'=>$key,'Column_name'=>$column,'Seq_in_index'=>'1','Non_unique'=>'0','Sub_part'=>null,'Index_type'=>'BTREE'); }
	}
	public function open() { return array(new Connection($this),$this->table); }
}
final class Connection {
	public $last_error = ''; private $f, $thread, $errno=0;
	public function __construct(Database $f) { $this->f=$f;$this->thread=++$f->opened; }
	public function prepare($sql,...$args) { return vsprintf(str_replace('%d','%s',$sql),array_map(static function($v){return "'".str_replace("'","''",(string)$v)."'";},$args)); }
	private function fail($number) { $this->errno=$number;$this->last_error='SECRET SQL/server/credentials';return false; }
	public function get_results($sql,$format) {
		$f=$this->f;$f->log[]=$sql;$this->last_error='';$this->errno=0;
		if ($f->fault==='metadata') { $this->fail(1142);return null; }
		if ($sql==='SELECT DATABASE() AS owned_state_database') { return array(array('owned_state_database'=>$f->database)); }
		if (strpos($sql,'SELECT option_name FROM')===0) { return array_map(static function($name){return array('option_name'=>$name);},$f->legacy); }
		if (strpos($sql,'SELECT state_key, record_id, schema_version, revision, payload FROM')===0) { $rows=array();foreach($f->records as $key=>$row){$row['state_key']=$key;$rows[]=$row;}return array_slice($rows,0,3); }
		if (strpos($sql,'SHOW TABLE STATUS')===0) { return $f->exists && $f->fault!=='hidden' ? array(array('Engine'=>$f->engine)) : array(); }
		if (strpos($sql,'SHOW TRIGGERS')===0) { return $f->triggers; }
		if (strpos($sql,'SHOW FULL COLUMNS')===0) { return $f->columns; }
		if (strpos($sql,'SHOW INDEX')===0) { return $f->indexes; }
		if (strpos($sql,'SELECT @@session.autocommit')===0) { return array(array('owned_state_autocommit'=>$f->autocommit)); }
		if ($sql==='SHOW ENGINES') { return array(array('Engine'=>'InnoDB','Support'=>$f->fault==='engine_unavailable'?'DISABLED':'DEFAULT')); }
		if (preg_match("/^SELECT record_id, schema_version, revision, payload .* WHERE state_key = '([^']+)' LIMIT 2( LOCK IN SHARE MODE)?$/",$sql,$m)) {
			if ($f->fault==='read_reconnect' || (isset($m[2]) && $f->fault==='lock_reconnect')) { $this->thread+=100; }
			if (isset($m[2]) && $f->fault==='lock_read') { $this->fail(1205);return null; }
			return isset($f->records[$m[1]])?array($f->records[$m[1]]):array();
		}
		throw new \RuntimeException('Unexpected F1 fixture read: '.$sql);
	}
	public function query_once($sql) {
		$f=$this->f;$f->log[]=$sql;$this->last_error='';$this->errno=0;
		if (strpos($sql,'SELECT 1 FROM')===0) { return $f->exists?0:$this->fail($f->fault==='probe_denied'?1142:1146); }
		if (strpos($sql,'SET SESSION sql_mode')===0) { return $f->fault==='mode_denied'?$this->fail(1142):0; }
		if (strpos($sql,'CREATE TABLE')===0) {
			if ($f->before_create) { $cb=$f->before_create;$f->before_create=null;$cb($f); }
			if ($f->fault==='create_denied') { return $this->fail(1142); }
			$f->exists=true;
			if ($f->fault==='create_ack') { $this->thread+=100; }
			if ($f->fault==='create_throw') { throw new \RuntimeException('SECRET lost DDL response'); }
			return 0;
		}
		if ($sql==='START TRANSACTION') {
			if ($f->fault==='lock_start') { return $this->fail(1205); }
			if ($f->fault==='start_reconnect') { $this->thread+=100; }
			return 0;
		}
		if ($sql==='ROLLBACK') { if ($f->fault==='rollback') { throw new \RuntimeException('Cleanup failure'); }return 0; }
		if ($f->before_write) { $cb=$f->before_write;$f->before_write=null;$cb(); }
		if ($f->fault==='write') { return $this->fail(1142); }
		if (preg_match("/^INSERT .* VALUES \('([^']+)', '([0-9]+)', '([0-9]+)', UNHEX\('([a-f0-9]+)'\)\)$/",$sql,$m)) {
			if (isset($f->records[$m[1]])) { return $this->fail(1062); }
			$f->records[$m[1]]=array('record_id'=>(string)$f->next_id++,'schema_version'=>$m[2],'revision'=>$m[3],'payload'=>hex2bin($m[4]));
		} elseif (preg_match("/^UPDATE .* SET schema_version = '([0-9]+)', revision = '([0-9]+)', payload = UNHEX\('([a-f0-9]+)'\) WHERE state_key = '([^']+)' AND record_id = '([0-9]+)' AND schema_version = '([0-9]+)' AND revision = '([0-9]+)' AND BINARY payload = UNHEX\('([a-f0-9]+)'\)$/",$sql,$m)) {
			$row=$f->records[$m[4]]??null;
			if (!$row || $row['record_id']!==$m[5] || $row['schema_version']!==$m[6] || $row['revision']!==$m[7] || $row['payload']!==hex2bin($m[8])) { return 0; }
			$f->records[$m[4]]=array('record_id'=>$row['record_id'],'schema_version'=>$m[1],'revision'=>$m[2],'payload'=>hex2bin($m[3]));
		} else { throw new \RuntimeException('Forbidden F1 fixture SQL: '.$sql); }
		if ($f->fault==='ack') { $this->thread+=100;return 1; }
		if ($f->fault==='transport') { return $this->fail(2013); }
		if ($f->fault==='throw_after') { throw new \RuntimeException('SECRET lost write response'); }
		return 1;
	}
	public function thread_id() { return $this->thread; }
	public function error_number() { return $this->errno; }
	public function close() { ++$this->f->closed;if ($this->f->fault==='close') { throw new \RuntimeException('Cleanup failure'); } }
}
