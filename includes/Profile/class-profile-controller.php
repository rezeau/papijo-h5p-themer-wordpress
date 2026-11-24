<?php
/** Standard WordPress profile forms. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Profile;
final class Profile_Controller {
	private $service;private $pending=array();private $attempt=array();private $preview;private $runtime;
	public function __construct(Profile_Service $service, ?Profile_Preview $preview=null, ?callable $runtime=null){$this->service=$service;$this->preview=$preview?:new Profile_Preview();$this->runtime=$runtime?:static function(){return array();};}
	public function register(){
		add_action('show_user_profile',array($this,'render'));
		add_action('edit_user_profile',array($this,'render'));
		add_action('user_profile_update_errors',array($this,'validate'),PHP_INT_MAX,3);
		add_action('profile_update',array($this,'save'),10,1);
		add_action('admin_enqueue_scripts',array($this,'assets'));
	}
	public function assets($hook){
		// Static assets are inert without authorized markup. Do not reread role/profile storage here.
		if('profile.php'!==$hook){return;}
		wp_enqueue_style('pjh5pwp-profile-preview',plugins_url('assets/css/profile-preview.css',PJH5PWP_FILE),array(),PJH5PWP_VERSION);
		wp_enqueue_script('pjh5pwp-profile-preview',plugins_url('assets/js/profile-preview.js',PJH5PWP_FILE),array(),PJH5PWP_VERSION,true);
	}
	private function t($text){return esc_html(__($text,'papijo-h5p-themer-wordpress'));}
	private function suspended(){
		// WordPress composition supplies the same fresh policy used by runtime admission.
		$status=call_user_func($this->runtime);
		return true===($status['runtime_suspended']??false)&&in_array('snordian_active',$status['blockers']??array(),true);
	}
	public function render($user){
		$id=(int)$user->ID;if(get_current_user_id()!==$id||!$this->service->admitted($id)){return;}
		$automatic=$this->service->automatic($id);
		if(!$automatic&&!$this->service->personalized($id)){return;}
		if($this->suspended()){
			echo '<h2>'.$this->t('Personal H5P preferences').'</h2><p>'.$this->t('Personal H5P appearance settings are currently unavailable because another H5P theme plugin is controlling activity appearance. Your previously saved Theme and Density preferences are preserved.').'</p>';
			return;
		}
		if($automatic&&!$this->service->personalized($id)){return;}
		try{$record=$this->service->snapshot($id);$v=$record['values']??Profile_State::defaults();}catch(\Throwable $e){echo '<p role="alert">'.$this->t('H5P profile storage is unavailable or requires installation/review. No H5P fields can be saved.').'</p>';return;}
		echo '<h2>'.$this->t('Personal H5P preferences').'</h2><p>'.$this->t('Your personal Theme and Density preferences apply to supported H5P content embedded in WordPress pages. Styling is not currently applied in the WordPress H5P administration viewer or in Subscriber Profile > View H5P content.').'</p>';
		$nonce=wp_create_nonce($this->service->nonce_for($id,$record));
		if(isset($this->attempt[$id])){$nonce=$this->attempt[$id]['nonce'];$posted=$this->attempt[$id]['payload'];if(is_array($posted)){foreach(array('theme','density') as $field){if(isset($posted[$field])&&is_string($posted[$field])){$v[$field]=$posted[$field];}}}}
		echo '<input type="hidden" name="pjh5pwp_profile_nonce" value="'.esc_attr(is_string($nonce)?$nonce:'').'"><table class="form-table" role="presentation">';
		$state=$this->service->state();$choices=$this->service->catalog($state);$labels=array();foreach($choices as $token=>$entry){$labels[$token]=$entry['label'];}$labels['site_default']='Use site default';unset($labels['core']);
		if ($v['theme']==='core') { $labels=array('core'=>'H5P Core defaults (saved preference)')+$labels; }
		$selected=is_string($v['theme'])&&isset($labels[$v['theme']])?$v['theme']:'site_default';
		if(is_string($v['theme'])&&''!==$v['theme']&&!isset($labels[$v['theme']])){
			echo '<tr><td colspan="2"><p>'.$this->t('The saved theme is unavailable. The global default is used. Keep the saved preference or explicitly choose a replacement.').'</p></td></tr>';
			$labels=array('keep_saved'=>'Keep saved preference (currently uses global default)')+$labels;
			$selected='keep_saved';
		}
		$this->selector('theme',$labels,$selected,false,$this->preview->render($state,$choices,$selected));
		$this->selector('density',array('original'=>'Original','small'=>'Small','medium'=>'Medium','large'=>'Large'),in_array($v['density'],array('original','small','medium','large'),true)?$v['density']:'original');
		echo '</table>';
	}
	private function selector($field,array $choices,$selected,$disabled=false,$after=''){
		echo '<tr><th scope="row"><label for="pjh5pwp-'.$field.'">'.$this->t('theme'===$field?'Personal H5P theme':'Native Density').'</label></th><td><select id="pjh5pwp-'.$field.'"'.($disabled?' disabled':' name="pjh5pwp_profile['.$field.']"').'>';
		foreach($choices as $value=>$label){echo '<option value="'.esc_attr($value).'"'.($value===$selected?' selected':'').($field==='theme'&&$value==='core'?' disabled':'').'>'.$this->t($label).'</option>';}
		echo '</select>'.$after.'</td></tr>';
	}
	public function validate($errors,$update,$user){
		$id=isset($user->ID)?(int)$user->ID:0;unset($this->pending[$id]);
		if(!isset($_POST['pjh5pwp_profile'])){return;}
		if($this->suspended()){return;} // Ignore stale preference fields; unrelated Profile updates remain valid.
		$payload=wp_unslash($_POST['pjh5pwp_profile']);$nonce=isset($_POST['pjh5pwp_profile_nonce'])?wp_unslash($_POST['pjh5pwp_profile_nonce']):null;
		$this->attempt[$id]=array('payload'=>$payload,'nonce'=>$nonce);
		$result=$this->service->plan($id,$payload,$nonce);
		if(!$update||$result['errors']){$errors->add('pjh5pwp_profile',$this->t('H5P preferences were not saved. The profile may be stale, its nonce invalid, storage unavailable, or a choice unauthorized. Reload and review.'));return;}
		if(!$errors->has_errors()){$this->pending[$id]=$this->attempt[$id];}
	}
	public function save($id){
		$id=(int)$id;if(!isset($this->pending[$id])){return;}$request=$this->pending[$id];unset($this->pending[$id]);
		if($this->suspended()){return;} // Suspension may begin after validation but before profile_update.
		$result=$this->service->save_request($id,$request['payload'],$request['nonce']);
		if(!in_array($result,array('committed','noop'),true)){wp_die($this->t('WordPress core profile fields may already have saved. H5P state was not confirmed saved: conflict, invalid request or database failure. Reload and review; an unknown write outcome must not be retried blindly.').' <code>'.esc_html($result).'</code>');}
	}
}
