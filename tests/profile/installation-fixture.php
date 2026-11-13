<?php
/** Disposable in-memory wpdb-shaped fault fixture. Never opens a real database. */
final class Installation_Fixture {
    public $exists=false,$engine='InnoDB',$engines='DEFAULT',$metadata=true,$hidden=false,$autocommit=1;
    public $fault='',$log=array(),$connections=0,$closed=0,$creates=0,$records=array(),$before_create;
    public $columns,$indexes,$triggers=array();
    public function __construct(){
        $this->columns=array();foreach(array('user_id'=>'bigint unsigned','schema_version'=>'int unsigned','revision'=>'bigint unsigned','state'=>'longblob') as $name=>$type){$this->columns[]=array('Field'=>$name,'Type'=>$type,'Null'=>'NO','Extra'=>'');}
        $this->indexes=array(array('Key_name'=>'PRIMARY','Column_name'=>'user_id','Non_unique'=>'0'));
    }
    public function open(){return array(new Installation_Connection($this),'course_27_pjh5pwp_profiles','course_27_usermeta');}
}
final class Installation_Connection {
    public $last_error='';private $fixture,$errno=0,$thread;
    public function __construct($fixture){$this->fixture=$fixture;$this->thread=++$fixture->connections;}
    public function prepare($sql,...$values){return vsprintf(str_replace('%d','%s',$sql),array_map(static function($v){return "'".str_replace("'","''",(string)$v)."'";},$values));}
    private function fail($errno){$this->errno=$errno;$this->last_error='SECRET SQL/credentials must never escape';return false;}
    public function get_results($sql,$format){
        $f=$this->fixture;$f->log[]=$sql;$this->last_error='';$this->errno=0;
        if(!$f->metadata||($f->fault==='verification'&&$f->creates)){ $this->fail(1142);return null; }
        if(strpos($sql,'SELECT @@session.autocommit')===0){return array(array('active'=>$f->autocommit));}
        if(strpos($sql,'SHOW TABLE STATUS')===0){return $f->exists&&!$f->hidden?array(array('Engine'=>$f->engine)):array();}
        if($sql==='SHOW ENGINES'){return $f->engines===null?array():array(array('Engine'=>'InnoDB','Support'=>$f->engines));}
        if(strpos($sql,'SHOW FULL COLUMNS')===0){return $f->columns;}
        if(strpos($sql,'SHOW INDEX')===0){return $f->indexes;}
        if(strpos($sql,'SHOW TRIGGERS')===0){return $f->triggers;}
        if(strpos($sql,'SELECT user_id FROM')===0){foreach($f->records as $id=>$row){if($row['schema_version']!==1){return array(array('user_id'=>$id));}}return array();}
        throw new RuntimeException('Unexpected fixture read: '.$sql);
    }
    public function query($sql){
        $f=$this->fixture;$f->log[]=$sql;$this->last_error='';$this->errno=0;
        if(strpos($sql,'SELECT 1 FROM')===0){
            if($f->fault==='probe_denied'){return $this->fail(1142);}
            if($f->fault==='probe_transport'){return $this->fail(2013);}
            if($f->fault==='probe_throw'){throw new RuntimeException('SECRET connection error');}
            return $f->exists?0:$this->fail(1146);
        }
        if(strpos($sql,'SET SESSION sql_mode')===0){
            if($f->fault==='mode'){return $this->fail(1142);}
            if($f->fault==='mode_reconnect'){$this->thread+=100;}
            if(strpos($sql,'NO_ENGINE_SUBSTITUTION')===false){throw new RuntimeException('Engine substitution not prohibited');}return 0;
        }
        if(strpos($sql,'CREATE TABLE IF NOT EXISTS')!==0){throw new RuntimeException('Forbidden fixture write: '.$sql);}
        ++$f->creates;if($f->before_create){$callback=$f->before_create;$f->before_create=null;$callback($f);}
        if($f->fault==='denied'){return $this->fail(1142);}
        if($f->fault==='failed'){return $this->fail(1005);}
        $f->exists=true;
        if($f->fault==='lost_ack'){$this->thread+=100;}
        if($f->fault==='throw_after'){throw new RuntimeException('SECRET lost acknowledgement');}
        if($f->fault==='transport_after'){return $this->fail(2013);}
        if($f->fault==='false_after'){return false;}
        return 0;
    }
    public function query_once($sql){return $this->query($sql);}
    public function thread_id(){return $this->thread;}
    public function error_number(){return $this->errno;}
    public function close(){++$this->fixture->closed;if($this->fixture->fault==='close'){throw new RuntimeException('SECRET close failure');}return true;}
}
