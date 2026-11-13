<?php
/** One target-aware authorization boundary and explicit administration. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Role;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
final class Role_Service {
	const LABEL = 'Allow personal H5P theme and Density selection';
	private $store;private $production;
	public function __construct($store=null){$this->production=$store===null;$this->store=$store?:new \Papijo\H5PThemerWordPress\Storage\Owned\Role_Store();}
	public static function administer(){return !is_multisite() && current_user_can('manage_options');}
	public function registered(){
		$result=array();
		foreach(wp_roles()->roles as $slug=>$role){
			if(!Role_State::slug($slug)||!is_array($role)||!isset($role['name'],$role['capabilities'])||!is_string($role['name'])||!is_array($role['capabilities'])){continue;}
			$result[$slug]=array('label'=>translate_user_role($role['name']),'automatic'=>!empty($role['capabilities']['manage_options']));
		}
		ksort($result,SORT_STRING);return $result;
	}
	/** Never consult the actor's capabilities when resolving another user. */
	public function allowed($id){
		if(is_multisite()||!is_int($id)||$id<=0){return false;}
		$user=get_userdata($id);if(!$user){return false;}
		if(user_can($user,'manage_options')){try{if($this->production){$this->store->available();}return true;}catch(\Throwable $e){return false;}}
		try{$state=$this->snapshot();$roles=$this->registered();}catch(\Throwable $e){return false;}
		return $this->has_role($user,$state,$roles);
	}
	private function has_role($user,$state,array $roles){
		foreach((array)$user->roles as $slug){if(is_string($slug)&&isset($roles[$slug])&&isset($state['roles'][$slug])&&$state['roles'][$slug]===true){return true;}}
		return false;
	}
	/** Recheck under a shared lock, serializing cooperative role revocation with saves. */
	public function while_allowed($id,callable $save){
		if(is_multisite()||!is_int($id)||$id<=0){return 'forbidden_preference';}
		$user=get_userdata($id);if(!$user){return 'forbidden_preference';}
		if(user_can($user,'manage_options')){if($this->production){$this->store->available();}return $save();}
		return $this->store->with_locked_snapshot(function($state)use($id,$save){
			$user=get_userdata($id);
			if(!$user||!$this->has_role($user,$state,$this->registered())){return 'forbidden_preference';}
			return $save();
		});
	}
	public function snapshot(){
		$state=$this->store->read();if($state!==null&&!Role_State::valid($state)){throw new Storage_Exception('role_state_invalid');}return $state;
	}
	public function registry_context(array $roles){$identity=array();foreach($roles as $slug=>$role){$identity[$slug]=$role['automatic'];}return hash('sha256',serialize($identity));}
	public function nonce_action($state,array $roles){return 'pjh5pwp_roles_'.hash('sha256',serialize($state)).'_'.$this->registry_context($roles);}
	public function submit($post){
		if(!self::administer()){return 'role_forbidden';}
		try{
			$before=$this->snapshot();$roles=$this->registered();
			if(!is_array($post)||($post['pjh5pwp_op']??null)!=='roles'||!isset($post['role_nonce'])||!is_string($post['role_nonce'])||!wp_verify_nonce($post['role_nonce'],$this->nonce_action($before,$roles))){return 'role_conflict_or_invalid_nonce';}
			$expected=array();foreach($roles as $slug=>$role){if(!$role['automatic']){$expected[]=$slug;}}
			if(!$expected&&($post['role_empty']??null)==='1'&&!isset($post['roles'])){$post['roles']=array();}
			if(($post['role_complete']??null)!=='1'||!isset($post['roles'])||!is_array($post['roles'])){return 'role_invalid_submission';}
			$keys=array_keys($post['roles']);sort($keys,SORT_STRING);sort($expected,SORT_STRING);
			if($keys!==$expected){return 'role_invalid_identifiers';}
			$grants=array();foreach($post['roles'] as $slug=>$value){if(!in_array($value,array('0','1'),true)){return 'role_invalid_submission';}if($value==='1'){$grants[$slug]=true;}}
			ksort($grants,SORT_STRING);
			$next=array('schema_version'=>1,'revision'=>$before===null?0:$before['revision']+1,'roles'=>$grants);
			if(!Role_State::valid($next)){return 'role_invalid_submission';}
			// No-op still checks the authoritative revision above; absent denial stays absent.
			if($grants===($before['roles']??array())){return 'noop';}
			return $this->store->compare_and_swap($before===null?null:$before['revision'],$next)?'committed':'role_conflict';
		}catch(Storage_Exception $e){return $e->outcome()==='unknown'?'role_write_unknown':($e->outcome()==='committed'?'role_saved_cache_failed':'role_storage_unavailable');}
		catch(\Throwable $e){return 'role_storage_unavailable';}
	}
}
