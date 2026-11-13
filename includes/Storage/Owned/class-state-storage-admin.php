<?php
/** Read-only diagnostics and one authorized setup POST; no automatic update repair.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Storage\Owned;

use Papijo\H5PThemerWordPress\Admin\Admin_Controller;

final class State_Storage_Admin {
	const ACTION = 'pjh5pwp_state_storage_retry';
	const RESULT = 'pjh5pwp_state_setup';
	const NONCE = 'pjh5pwp_state_setup_nonce';
	private $factory;
	private $diagnostic='';
	public function __construct(callable $factory){$this->factory=$factory;}
	public function register(){
		add_action('admin_notices',array($this,'render'));
		add_action('network_admin_notices',array($this,'render'));
		add_action('admin_post_'.self::ACTION,array($this,'retry'));
	}
	private function state($operation){
		try{
			$result=call_user_func($this->factory)->$operation();
			$code=$result['errors'][0]??'';
			$this->diagnostic=is_string($code)&&strpos($code,'owned_state_')===0?$code:'';
			$state=$result['state']??null;
			return in_array($state,array('ready','absent','incompatible','unavailable','failed','unknown','legacy_unsupported'),true)?$state:'unavailable';
		}catch(\Throwable $error){$this->diagnostic='';return 'unavailable';}
	}
	private function attempt(){
		$value=$_GET[self::RESULT]??null;
		return is_string($value)&&in_array($value,array('ready','incompatible','unavailable','failed','unknown','legacy_unsupported'),true)?$value:'';
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
			'absent'=>__('State storage is not set up. Theme and permission settings cannot be saved until setup succeeds. Existing state is preserved. An administrator can retry setup below.','papijo-h5p-themer-wordpress'),
			'legacy_unsupported'=>\Papijo\H5PThemerWordPress\Admin\Admin_Messages::text('owned_state_legacy_unsupported'),
			'incompatible'=>__('State storage uses an unsupported schema, engine, or stored version. Existing data is preserved. No repair or migration was attempted.','papijo-h5p-themer-wordpress'),
			'unavailable'=>__('State storage could not be verified. Existing state is preserved. Check database access and storage requirements before retrying setup.','papijo-h5p-themer-wordpress'),
			'failed'=>__('State storage setup did not complete. Existing data is preserved. Check database access and storage requirements before retrying.','papijo-h5p-themer-wordpress'),
			'unknown'=>__('The State storage setup result could not be confirmed. Existing data is preserved. No automatic retry or repair was performed.','papijo-h5p-themer-wordpress'),
			'multisite'=>__('State storage setup is not supported on multisite. Existing data is preserved; no state tables are created or changed.','papijo-h5p-themer-wordpress'),
			'ready'=>__('State storage is ready. Saved state is preserved.','papijo-h5p-themer-wordpress'),
		);
		$class=$state==='ready'?'notice-success':'notice-warning';
		echo '<div class="notice '.esc_attr($class).' pjh5pwp-state-storage"><p><strong>'.esc_html__('Papi Jo State storage','papijo-h5p-themer-wordpress').':</strong> '.esc_html($messages[$state]).'</p>';
		if($this->diagnostic!==''&&$state!=='legacy_unsupported'){echo '<p>'.esc_html(\Papijo\H5PThemerWordPress\Admin\Admin_Messages::text($this->diagnostic)).'</p>';}
		// URL results are informational only; the current read-only status is authoritative.
		if($state!=='ready'&&in_array($attempt,array('failed','unknown'),true)){
			echo '<p>'.esc_html($messages[$attempt]).'</p>';
		}
		if(!in_array($state,array('ready','multisite','legacy_unsupported','incompatible'),true)){
			echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
			echo '<input type="hidden" name="action" value="'.esc_attr(self::ACTION).'">';
			echo '<input type="hidden" name="'.esc_attr(self::NONCE).'" value="'.esc_attr(wp_create_nonce(self::ACTION)).'">';
			echo '<input type="hidden" name="pjh5pwp_state_setup_return" value="'.($themes?'themes':'plugins').'">';
			echo '<p><button type="submit" class="button">'.esc_html__('Retry State storage setup','papijo-h5p-themer-wordpress').'</button></p></form>';
		}
		echo '</div>';
	}
	public function retry(){
		if(!current_user_can('manage_options')||is_multisite()){
			wp_die(esc_html__('State storage setup is not permitted.','papijo-h5p-themer-wordpress'),'',array('response'=>403));return;
		}
		if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
			wp_die(esc_html__('State storage setup requires a POST request.','papijo-h5p-themer-wordpress'),'',array('response'=>405));return;
		}
		$nonce=$_POST[self::NONCE]??null;
		if(!is_string($nonce)||!wp_verify_nonce(wp_unslash($nonce),self::ACTION)){
			wp_die(esc_html__('State storage setup could not be authorized. Reload the page and try again.','papijo-h5p-themer-wordpress'),'',array('response'=>403));return;
		}
		$state=$this->state('ensure_installed');
		$themes=($_POST['pjh5pwp_state_setup_return']??null)==='themes';
		$url=admin_url($themes?'admin.php?page='.Admin_Controller::SLUG:'plugins.php');
		wp_safe_redirect(add_query_arg(array(self::RESULT=>$state),$url),303);
		exit;
	}
}
