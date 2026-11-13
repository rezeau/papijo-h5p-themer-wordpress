<?php
/** Isolated aggregate double; legacy-named array slots retain original test fixtures. */
final class Profile_Memory_Store {
	const KEYS = array('allow_theme'=>'pjh5pwp_allow_theme','allow_density'=>'pjh5pwp_allow_density','theme'=>'pjh5pwp_theme','density'=>'pjh5pwp_density');
	public $race;
	public function read($id){global $meta;if(empty($meta[$id])){return null;}$v=\Papijo\H5PThemerWordPress\Profile\Profile_State::defaults();foreach(self::KEYS as $field=>$key){if(array_key_exists($key,$meta[$id])){$v[$field]=$meta[$id][$key];}}$r=array('schema_version'=>1,'revision'=>$meta[$id]['_revision']??0,'values'=>$v);if(!\Papijo\H5PThemerWordPress\Profile\Profile_State::valid($r)){throw new RuntimeException('Malformed state');}return $r;}
	public function compare_and_swap($id,$expected,array $v){global $meta,$writes,$failWrite;if($this->race){$fn=$this->race;$this->race=null;$fn();}if($this->read($id)!==$expected){return 'conflict';}if($v===($expected['values']??\Papijo\H5PThemerWordPress\Profile\Profile_State::defaults())){return 'noop';}if($failWrite){throw new RuntimeException('Database write failed');}++$writes;foreach(self::KEYS as $field=>$key){$meta[$id][$key]=$v[$field];}$meta[$id]['_revision']=$expected===null?0:$expected['revision']+1;return 'committed';}
}
