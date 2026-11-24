<?php
/** Menu/page lifecycle; every write occurs only on this page's POST load hook.
 * @license GPL-3.0-or-later
 */
namespace Papijo\H5PThemerWordPress\Admin;
final class Admin_Controller {
	const SLUG = 'pjh5pwp-themes';
	private $factory;
	private $hook = '';
	private $posted = array();
	private $errors = array();
	private $role_result = '';
	private $anonymous_result = '';
	private $roles;
	private $transfers;
	private $download;
	private $transfer_result = array();
	private $transfer_view = '';
	private function roles() { if(!$this->roles){$this->roles=new \Papijo\H5PThemerWordPress\Role\Role_Service();}return $this->roles; }
	public function __construct( callable $factory, $roles = null, ?callable $transfers = null, ?callable $download = null ) {
		$this->factory=$factory; $this->roles=$roles; $this->transfers=$transfers;
		$this->download=$download ?: static function( $contract ) { Transfer_Download::send($contract); exit; };
	}
	public function register() {
		add_action('admin_menu',array($this,'menu'),30);
		add_action('admin_enqueue_scripts',array($this,'assets'));
	}
	public function menu() {
		if(!Admin_Service::allowed()) { return; }
		global $menu;
		$parent=false;
		foreach((array)$menu as $entry) { if(isset($entry[1],$entry[2]) && 'h5p'===$entry[2] && current_user_can($entry[1])) { $parent=true; break; } }
		$title=__('Papi Jo H5P Themes','papijo-h5p-themer-wordpress');
		$this->hook=$parent ? add_submenu_page('h5p',$title,$title,'manage_options',self::SLUG,array($this,'render')) : add_options_page($title,$title,'manage_options',self::SLUG,array($this,'render'));
		if($this->hook) { add_action('load-' . $this->hook,array($this,'load')); }
	}
	private function url() { return menu_page_url(self::SLUG,false); }
	public function load() {
		if(!Admin_Service::allowed()) { wp_die(esc_html(Admin_Messages::text('forbidden')),'',array('response'=>403)); return; }
		nocache_headers();
		if(!isset($_SERVER['REQUEST_METHOD']) || 'POST'!==$_SERVER['REQUEST_METHOD']) { return; }
		$this->posted=is_array($_POST)?wp_unslash($_POST):array();
		// A truncated/oversized PHP POST must not fall through to another operation.
		if(!$this->posted) { $this->errors=array('upload_or_post_limit'); return; }
		$op=$this->posted['pjh5pwp_op']??null;
		if(is_string($op) && in_array($op,array('export','import_preview','import_commit'),true)) {
			$this->transfer_view='export'===$op?'overview':'import';
			$this->transfer_result=$this->transfers ? call_user_func($this->transfers)->submit($this->posted,is_array($_FILES??null)?$_FILES:array()) : array('errors'=>array('review_unavailable'));
			$this->posted=array();
			if(isset($this->transfer_result['download'])) {
				try { call_user_func($this->download,$this->transfer_result['download']); }
				catch(\RuntimeException $error) { $this->errors=array('download_unavailable'); }
				return;
			}
			$this->errors=$this->transfer_result['errors'];
			if(isset($this->transfer_result['summary'])) {
				wp_safe_redirect(add_query_arg(array('pjh5pwp_imported'=>(string)$this->transfer_result['summary']['count']),$this->url()),303); exit;
			}
			return;
		}
		if ($op==='anonymous_permission') {
			$this->anonymous_result=(new \Papijo\H5PThemerWordPress\Anonymous\Permission())->submit($this->posted); return;
		}
		if(($this->posted['pjh5pwp_op']??null)==='roles') {
			$this->role_result=$this->roles()->submit($this->posted);return;
		}
		$result=call_user_func($this->factory)->submit($this->posted);
		$this->errors=$result['errors'];
		if(!$this->errors) {
			$op=isset($this->posted['pjh5pwp_op'])?$this->posted['pjh5pwp_op']:'';
			$args=array('pjh5pwp_saved'=>'1');
			if(in_array($op,array('create','duplicate','edit'),true)) { $args['pjh5pwp_view']='edit'; $args['pjh5pwp_source']='custom:' . $this->posted['id']; }
			wp_safe_redirect(add_query_arg($args,$this->url()),303); exit;
		}
	}
	public function render() {
		if(!Admin_Service::allowed()) { wp_die(esc_html(Admin_Messages::text('forbidden')),'',array('response'=>403)); return; }
		$view=isset($_GET['pjh5pwp_view']) && is_string($_GET['pjh5pwp_view'])?wp_unslash($_GET['pjh5pwp_view']):'overview';
		$source=isset($_GET['pjh5pwp_source']) && is_string($_GET['pjh5pwp_source'])?wp_unslash($_GET['pjh5pwp_source']):'';
		if(!in_array($view,array('overview','view','create','edit','duplicate','delete','import'),true)) { $view='invalid'; }
		if($this->transfer_view) { $view=$this->transfer_view; }
		if(strlen($source)>100) { $source='invalid'; }
		if($this->errors && isset($this->posted['pjh5pwp_op'],$this->posted['source']) && is_string($this->posted['pjh5pwp_op']) && is_string($this->posted['source']) && strlen($this->posted['source'])<=100) {
			$op=$this->posted['pjh5pwp_op'];
			$view=in_array($op,array('create','edit','duplicate','delete'),true)?$op:'overview'; $source=$this->posted['source'];
		}
		$status=call_user_func($this->factory)->status();
		if('import'===$view) { $this->transfer_result['errors']=$this->errors; echo (new Transfer_View($this->url()))->import_page($status,$this->transfer_result); return; }
		if('overview'===$view && isset($_GET['pjh5pwp_imported']) && is_string($_GET['pjh5pwp_imported']) && preg_match('/\A(?:[1-9][0-9]?|100)\z/',$_GET['pjh5pwp_imported'])) { echo (new Transfer_View($this->url()))->imported((int)$_GET['pjh5pwp_imported']); }
		$renderer=new Admin_View($this->url());
		echo $renderer->render($status,$view,$source,$this->posted,$this->errors,isset($_GET['pjh5pwp_saved']) && '1'===$_GET['pjh5pwp_saved']); // All output escaped by the renderer.
		if($view==='overview'){echo (new \Papijo\H5PThemerWordPress\Role\Role_View())->render($this->roles(),$this->url(),$this->role_result);}
		if ($view==='overview') { echo (new \Papijo\H5PThemerWordPress\Anonymous\Permission())->render($this->url(),$this->anonymous_result); }
	}
	public function assets( $hook ) {
		if(!$this->hook || $hook!==$this->hook || !Admin_Service::allowed()) { return; }
		wp_enqueue_style('pjh5pwp-admin',plugins_url('assets/css/admin.css',PJH5PWP_FILE),array(),PJH5PWP_VERSION);
		wp_enqueue_script('pjh5pwp-admin',plugins_url('assets/js/admin.js',PJH5PWP_FILE),array(),PJH5PWP_VERSION,true);
		$messages=array(
			'invalid'=>__('Invalid hexadecimal color. The preview keeps the last valid value.','papijo-h5p-themer-wordpress'),
			'inherited'=>__('Inherited / starting value. Choose a color to create an explicit override.','papijo-h5p-themer-wordpress'),
			'expression'=>__('Inherited CSS expression. Enter a hexadecimal color to enable its color picker.','papijo-h5p-themer-wordpress'),
			'saved'=>__('Saved colors. Changes are not saved until Save is clicked.','papijo-h5p-themer-wordpress'),
			'unsaved'=>__('Unsaved color changes. Save is required to persist them.','papijo-h5p-themer-wordpress'),
			'unavailable'=>__('Live preview unavailable; the saved preview and color fields remain available.','papijo-h5p-themer-wordpress'),
		);
		// wp_localize_script emits a variable declaration; a hyphenated key needs bracket notation.
		wp_add_inline_script('pjh5pwp-admin',"window['pjh5pwp-admin'] = " . wp_json_encode($messages,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) . ';','before');
	}
}
