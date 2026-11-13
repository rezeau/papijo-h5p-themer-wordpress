<?php
/** Escaped standalone form on the existing themes administration page. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Role;
final class Role_View {
	private function t($text){return esc_html(__($text,'papijo-h5p-themer-wordpress'));}
	public function render(Role_Service $service,$url,$result=''){
		if(!Role_Service::administer()){return '';}
		$html='<div class="wrap pjh5pwp-admin"><section class="pjh5pwp-role-permissions"><h2>'.$this->t('Role permissions').'</h2>';
		if($result!==''){$ok=in_array($result,array('committed','noop'),true);$html.='<div class="notice '.($ok?'notice-success':'notice-error').'"><p>'.$this->t($ok?'Role permissions saved.':'Role permissions were not confirmed saved. Reload and review before trying again.').' <code>'.esc_html($result).'</code></p></div>';}
		try{$state=$service->snapshot();$roles=$service->registered();}catch(\Throwable $e){return $html.'<p role="alert">'.$this->t('Role configuration is unavailable or malformed. No role permissions can be saved.').'</p></section></div>';}
		$html.='<p>'.$this->t('Users with manage_options capability are allowed automatically. Other users are allowed when any registered role is enabled. New roles default to disabled.').'</p><form method="post" action="'.esc_url($url).'">';
		$html.='<input type="hidden" name="pjh5pwp_op" value="roles"><input type="hidden" name="role_nonce" value="'.esc_attr(wp_create_nonce($service->nonce_action($state,$roles))).'">';
		$html.='<table class="widefat striped"><thead><tr><th>'.$this->t('Role').'</th><th>'.$this->t(Role_Service::LABEL).'</th></tr></thead><tbody>';
		$configurable=0;
		foreach($roles as $slug=>$role){
			$html.='<tr><td>'.esc_html($role['label']).'<br><code>'.esc_html($slug).'</code></td><td>';
			if($role['automatic']){$html.=$this->t('Allowed automatically');}else{
				++$configurable;$name='roles['.$slug.']';
				$html.='<input type="hidden" name="'.esc_attr($name).'" value="0"><label><input type="checkbox" name="'.esc_attr($name).'" value="1"'.(!empty($state['roles'][$slug])?' checked':'').'> '.$this->t(Role_Service::LABEL).' <span class="screen-reader-text">'.esc_html($role['label']).'</span></label>';
			}
			$html.='</td></tr>';
		}
		// PHP cannot submit an empty array; normalize this sentinel in submit().
		if(!$configurable){$html.='<input type="hidden" name="role_empty" value="1">';}
		return $html.'</tbody></table><input type="hidden" name="role_complete" value="1"><p><button class="button button-primary" type="submit">'.$this->t('Save role permissions').'</button></p></form></section></div>';
	}
}
