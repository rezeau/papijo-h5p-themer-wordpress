<?php
/** Read-only diagnostics and one authorized setup POST; no automatic update repair.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Profile;

use Papijo\H5PThemerWordPress\Admin\Admin_Controller;

final class Profile_Storage_Admin {
	const ACTION = 'pjh5pwp_profile_storage_retry';
	const RESULT = 'pjh5pwp_profile_setup';
	const NONCE = 'pjh5pwp_profile_setup_nonce';
	private $factory;
	public function __construct(callable $factory){$this->factory=$factory;}
	public function register(){
		add_action('admin_notices',array($this,'render'));
		add_action('network_admin_notices',array($this,'render'));
		add_action('admin_post_'.self::ACTION,array($this,'retry'));
	}
	private function state($operation){
		try{
			$result=call_user_func($this->factory)->$operation();
			$state=$result['state']??null;
			return in_array($state,array('ready','absent','incompatible','unavailable','failed','unknown'),true)?$state:'unavailable';
		}catch(\Throwable $error){return 'unavailable';}
	}
	private function attempt(){
		$value=$_GET[self::RESULT]??null;
		return is_string($value)&&in_array($value,array('ready','incompatible','unavailable','failed','unknown'),true)?$value:'';
	}
	public function render(){
		if(!current_user_can('manage_options')){return;}
		$screen=get_current_screen();if(!$screen){return;}
		$suffix='_page_'.Admin_Controller::SLUG;
		$themes=substr($screen->base,-strlen($suffix))===$suffix;
		if(!$themes&&!in_array($screen->base,array('plugins','plugins-network'),true)){return;}
		// Also handles updates which never execute the new activation callback.
		$state=is_multisite()?'multisite':$this->state('status');$attempt=$this->attempt();
		if($state==='ready'&&$attempt===''){return;}
		$messages=array(
			'absent'=>__('Profile storage is not set up. Personal H5P preferences cannot be saved until setup succeeds. Existing preferences are preserved. An administrator can retry setup below.','papijo-h5p-themer-wordpress'),
			'incompatible'=>__('Profile storage uses an unsupported schema, engine, or stored version. Existing data is preserved. No repair or migration was attempted.','papijo-h5p-themer-wordpress'),
			'unavailable'=>__('Profile storage could not be verified. Existing preferences are preserved. Check database access and storage requirements before retrying setup.','papijo-h5p-themer-wordpress'),
			'failed'=>__('Profile storage setup did not complete. Existing data is preserved. Check database access and storage requirements before retrying.','papijo-h5p-themer-wordpress'),
			'unknown'=>__('The Profile storage setup result could not be confirmed. Existing data is preserved. No automatic retry or repair was performed.','papijo-h5p-themer-wordpress'),
			'multisite'=>__('Profile storage setup is not supported on multisite. Existing data is preserved; no Profile tables are created or changed.','papijo-h5p-themer-wordpress'),
			'ready'=>__('Profile storage is ready. Saved preferences are preserved.','papijo-h5p-themer-wordpress'),
		);
		$class=$state==='ready'?'notice-success':'notice-warning';
		echo '<div class="notice '.esc_attr($class).' pjh5pwp-profile-storage"><p><strong>'.esc_html__('Papi Jo Profile storage','papijo-h5p-themer-wordpress').':</strong> '.esc_html($messages[$state]).'</p>';
		// URL results are informational only; the current read-only status is authoritative.
		if($state!=='ready'&&in_array($attempt,array('failed','unknown'),true)){
			echo '<p>'.esc_html($messages[$attempt]).'</p>';
		}
		if($state!=='ready'&&$state!=='multisite'){
			echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
			echo '<input type="hidden" name="action" value="'.esc_attr(self::ACTION).'">';
			echo '<input type="hidden" name="'.esc_attr(self::NONCE).'" value="'.esc_attr(wp_create_nonce(self::ACTION)).'">';
			echo '<input type="hidden" name="pjh5pwp_profile_setup_return" value="'.($themes?'themes':'plugins').'">';
			echo '<p><button type="submit" class="button">'.esc_html__('Retry Profile storage setup','papijo-h5p-themer-wordpress').'</button></p></form>';
		}
		echo '</div>';
	}
	public function retry(){
		if(!current_user_can('manage_options')||is_multisite()){
			wp_die(esc_html__('Profile storage setup is not permitted.','papijo-h5p-themer-wordpress'),'',array('response'=>403));return;
		}
		if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
			wp_die(esc_html__('Profile storage setup requires a POST request.','papijo-h5p-themer-wordpress'),'',array('response'=>405));return;
		}
		$nonce=$_POST[self::NONCE]??null;
		if(!is_string($nonce)||!wp_verify_nonce(wp_unslash($nonce),self::ACTION)){
			wp_die(esc_html__('Profile storage setup could not be authorized. Reload the page and try again.','papijo-h5p-themer-wordpress'),'',array('response'=>403));return;
		}
		$state=$this->state('ensure_installed');
		$themes=($_POST['pjh5pwp_profile_setup_return']??null)==='themes';
		$url=admin_url($themes?'admin.php?page='.Admin_Controller::SLUG:'plugins.php');
		wp_safe_redirect(add_query_arg(array(self::RESULT=>$state),$url),303);
		exit;
	}
}
