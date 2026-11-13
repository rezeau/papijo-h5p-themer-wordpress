<?php
/** Permission and preference boundary; no runtime application. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Profile;
use Papijo\H5PThemerWordPress\Publication\Publication_Policy;
use Papijo\H5PThemerWordPress\Storage\Schema;

final class Profile_Service {
	private $library;private $store;private $roles;
	public function __construct(callable $library,$store=null,$roles=null) { $this->library=$library;$this->store=$store?:new Profile_Store();$this->roles=$roles?:new \Papijo\H5PThemerWordPress\Role\Role_Service(); }
	public function admitted($id) { return !is_multisite() && $id>0 && current_user_can('edit_user',$id); }
	public function personalized($id) { return $this->roles->allowed($id); }
	public function automatic($id) { $user=get_userdata($id);return $user&&user_can($user,'manage_options'); }
	public function snapshot($id){return $this->store->read($id);}
	public function values($id) { return $this->snapshot($id)['values']??Profile_State::defaults(); }
	public function nonce_for($id,$record){return 'pjh5pwp_profile_'.$id.'_'.hash('sha256',serialize($record));}
	public function nonce_action($id) { return $this->nonce_for($id,$this->snapshot($id)); }
	public function state() {
		try { $state=call_user_func($this->library);return !(new Schema())->validate($state)['errors']?$state:null; }
		catch(\Throwable $e){return null;}
	}
	public function catalog($state) {
		// Phase 2A: built-ins are always available; custom publication remains explicit.
		$policy=is_array($state)?$state['publication']:array('schema_version'=>1,'themes'=>array());
		foreach(array('lavender','mint','sunset','dark') as $id){$policy['themes']['builtin:'.$id]=true;}
		return (new Publication_Policy())->catalog(is_array($state)?$state['definitions']:array(),$policy)['data'];
	}
	public function effective($id) {
		try{$v=$this->values($id);}catch(\Throwable $e){$v=Profile_State::defaults();}$state=$this->state();$allowed=$this->personalized($id);$token=$allowed?$v['theme']:'site_default';
		$catalog=$this->catalog($state);if(!is_string($token)||!isset($catalog[$token])){$token='site_default';}
		$policy=is_array($state)?$state['publication']:array('schema_version'=>1,'themes'=>array());
		foreach(array('lavender','mint','sunset','dark') as $preset){$policy['themes']['builtin:'.$preset]=true;}
		return array('theme'=>(new Publication_Policy())->resolve_learner($token,$state['settings']['default_theme']??null,$state['definitions']??array(),$policy),'density'=>$allowed&&in_array($v['density'],array('original','small','medium','large'),true)?$v['density']:'original');
	}
	public function plan($id,$payload,$nonce) {
		$fail=static function($code){return array('patch'=>array(),'errors'=>array($code));};
		if(!$this->admitted($id)){return $fail('forbidden');}
		try{$record=$this->snapshot($id);}catch(\Throwable $e){return $fail('profile_storage_unavailable');}
		if(!is_array($payload)||!is_string($nonce)||!wp_verify_nonce($nonce,$this->nonce_for($id,$record))){return $fail('profile_conflict_or_invalid_nonce');}
		if(get_current_user_id()!==$id||!$this->personalized($id)){return $fail('forbidden_preference');}
		$v=$record['values']??Profile_State::defaults();$patch=array();
		foreach($payload as $field=>$value){
			if(!in_array($field,array('theme','density'),true)||!is_string($value)){return $fail('invalid_profile_field');}
			if('theme'===$field){
				$catalog=$this->catalog($this->state());
				if('keep_saved'===$value&&Publication_Policy::valid_token($v['theme'])&&!isset($catalog[$v['theme']])){continue;}
				if(!isset($catalog[$value])){return $fail('unavailable_theme');}
			}elseif(!in_array($value,array('original','small','medium','large'),true)){return $fail('invalid_density');}
			if($v[$field]!==$value){$patch[$field]=$value;}
		}
		return array('patch'=>$patch,'expected'=>$record,'values'=>array_replace($v,$patch),'errors'=>array());
	}
	public function save_request($id,$payload,$nonce) {
		$plan=$this->plan($id,$payload,$nonce);if($plan['errors']){return $plan['errors'][0];}
		try{return $this->roles->while_allowed($id,function()use($id,$plan){return $this->store->compare_and_swap($id,$plan['expected'],$plan['values']);});}
		catch(\Papijo\H5PThemerWordPress\Storage\Storage_Exception $e){return $e->outcome()==='unknown'?'profile_write_unknown':'profile_database_failed';}
		catch(\Throwable $e){return 'profile_database_failed';}
	}
}
