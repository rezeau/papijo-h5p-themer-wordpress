<?php
/** Administration stays usable with every Phase 1A SNORDIAN signal. Fixtures only. */
if('cli'!==PHP_SAPI){exit;}
if(!isset($argv[1]) || '--worker'!==$argv[1]){
	$total=0;
	foreach(array('snordian-active','snordian-constant','snordian-namespace','snordian-priority-zero') as $signal){
		$process=proc_open(array(PHP_BINARY,'-n',__FILE__,'--worker',$signal),array(0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w')),$pipes);
		if(!is_resource($process)){throw new RuntimeException('Cannot start isolated coexistence worker');}
		fclose($pipes[0]);$output=stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($process);
		if($exit || $error || !ctype_digit(trim($output))){throw new RuntimeException($signal . ': ' . $output . $error);}
		$total+=(int)$output;
	}
	echo 'Phase 1C-B coexistence: 4 isolated SNORDIAN signals, ' . $total . " additional assertions passed.\n";exit;
}
// Reuse the independent compatibility fixtures, never the SNORDIAN implementation.
$argv=array(__FILE__,$argv[2]);ob_start();require dirname(__DIR__) . '/worker.php';ob_end_clean();
require dirname(__DIR__) . '/domain/in-memory-store.php';
function wp_create_nonce($action){return hash('sha256','coexistence-session:' . $action);}
function wp_verify_nonce($nonce,$action){return hash_equals(wp_create_nonce($action),$nonce);}
function esc_attr($value){return esc_html($value);}
function esc_url($value){return esc_html($value);}
function add_query_arg($args,$url){return $url . '&' . http_build_query($args);}
function get_current_user_id(){return 1;}
function get_bloginfo($key){return 'en-US';}
function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function get_userdata($id){return (object)array('ID'=>$id,'roles'=>array('administrator'));}
function user_can($user,$cap){return $user->ID===1&&$cap==='manage_options';}
function get_user_meta($id,$key,$single){return in_array($key,array('pjh5pwp_allow_theme','pjh5pwp_allow_density'),true)?'1':'';}
use Papijo\H5PThemerWordPress\Admin\Admin_Service;
use Papijo\H5PThemerWordPress\Admin\Admin_View;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;
$count=0;
function coexist_check($condition){global $count;if(!$condition){throw new RuntimeException('Coexistence assertion failed');}++$count;}
function coexist_post($service,$op,$extra=array()){
	$state=$service->status()['data'];$rev=null===$state?'absent':(string)$state['revision'];$context=Admin_Service::context($state);
	return array_merge(array('pjh5pwp_op'=>$op,'source'=>'','revision'=>$rev,'pjh5pwp_context'=>$context,'pjh5pwp_nonce'=>wp_create_nonce(Admin_Service::nonce_action($op,'',$rev,$context))),$extra);
}
$foreign_options=$GLOBALS['pjh5pwp_options'];$foreign_hooks=$GLOBALS['pjh5pwp_hooks'];
$service=new Admin_Service(new \Papijo\H5PThemerWordPress\Tests\In_Memory_Store());
coexist_check(!empty($policy->evaluate()['snordian']['active']));
coexist_check($policy->evaluate()['runtime_suspended']);
coexist_check(!$service->submit(coexist_post($service,'initialize'))['errors']);
coexist_check(!$service->submit(coexist_post($service,'create',array('id'=>'coexist','label'=>'Coexistence','description'=>'','colors'=>array_fill_keys(Color_Properties::supported(),''))))['errors']);
coexist_check(!$service->submit(coexist_post($service,'default',array('default_theme'=>'custom:coexist')))['errors']);
$html=(new Admin_View('http://isolated.invalid/admin.php?page=pjh5pwp-themes'))->render($service->status());
coexist_check(false!==strpos($html,'Save learner visibility') && false!==strpos($html,'custom:coexist'));
$profile_store=new class { public function read($id){return array('schema_version'=>1,'revision'=>0,'values'=>array('allow_theme'=>'1','allow_density'=>'1','theme'=>'site_default','density'=>'original'));} };
require dirname(__DIR__) . '/owned-storage/fake-database.php';
$state_fixture=new \Papijo\H5PThemerWordPress\Tests\OwnedStorage\Database();
$owned_roles=new \Papijo\H5PThemerWordPress\Storage\Owned\Role_Store(new \Papijo\H5PThemerWordPress\Storage\Owned\State_Table(array($state_fixture,'open')));
$profiles=new \Papijo\H5PThemerWordPress\Profile\Profile_Service(static function()use($service){return $service->status()['data'];},$profile_store,new \Papijo\H5PThemerWordPress\Role\Role_Service($owned_roles));
$plan=$profiles->plan(1,array('theme'=>'core','density'=>'medium'),wp_create_nonce($profiles->nonce_action(1)));
coexist_check(!$plan['errors'] && $plan['patch']===array('theme'=>'core','density'=>'medium'));
// Exercise the actual controller wired by Plugin::boot(), not an independent detector/test controller.
$profile_callback=$GLOBALS['pjh5pwp_hooks']['show_user_profile'][0][0];
ob_start();call_user_func($profile_callback,(object)array('ID'=>1));$profile_html=ob_get_clean();
coexist_check(strpos($profile_html,'Personal H5P preferences')!==false);
coexist_check(strpos($profile_html,'Your previously saved Theme and Density preferences are preserved.')!==false);
coexist_check(strpos($profile_html,'pjh5pwp_profile[theme]')===false && strpos($profile_html,'pjh5pwp_profile[density]')===false);
coexist_check(strpos($profile_html,'Preview selected Theme')===false && strpos($profile_html,'<iframe ')===false);
coexist_check(strpos($profile_html,'pjh5pwp_profile_nonce')===false && strpos($profile_html,'data-pjh5pwp-profile-preview')===false);
coexist_check($foreign_options===$GLOBALS['pjh5pwp_options']);
coexist_check($foreign_hooks===$GLOBALS['pjh5pwp_hooks']);
coexist_check($policy->evaluate()['runtime_suspended'] && $policy->evaluate()['runtime_enabled'] && $policy->evaluate()['runtime_implemented']);
echo $count;
