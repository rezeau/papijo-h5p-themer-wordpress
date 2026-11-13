<?php
/** Isolated profile lifecycle/API doubles; no CMS/database bootstrap. */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){throw new ErrorException($m,0,$n,$f,$l);});
require dirname(__DIR__,2).'/includes/theme-domain.php';
require dirname(__DIR__,2).'/includes/profiles.php';
require __DIR__.'/memory-store.php';
require dirname(__DIR__,2).'/includes/wordpress-storage.php';
require dirname(__DIR__).'/storage/fake-database.php';
use Papijo\H5PThemerWordPress\Profile\Profile_Service;
use Papijo\H5PThemerWordPress\Profile\Profile_Controller;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Storage\Schema;
$meta=array();$writes=0;$actor=1;$administrator=true;$edit=true;$multisite=false;$hooks=array();$checks=0;$canEditUsers=true;$failWrite=false;
function is_multisite(){global $multisite;return $multisite;}
function get_current_user_id(){global $actor;return $actor;}
function current_user_can($cap,$id=null){global $administrator,$edit,$actor,$canEditUsers;return $cap==='edit_user'?$edit&&($actor===$id||$administrator):($cap==='edit_users'?$administrator&&$canEditUsers:$administrator);}
function get_user_meta($id,$key,$single){global $meta;return $meta[$id][$key]??'';}
function update_user_meta(){throw new RuntimeException('No usermeta writes permitted');}
function wp_create_nonce($action){return hash('sha256',get_current_user_id().':'.$action);}
function wp_verify_nonce($nonce,$action){return hash_equals(wp_create_nonce($action),$nonce);}
function wp_unslash($v){return is_array($v)?array_map('wp_unslash',$v):(is_string($v)?stripslashes($v):$v);}
function __($v,$domain){return $v;}
function esc_html($v){return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function esc_attr($v){return esc_html($v);}
function add_action($hook,$fn,$priority=10,$args=1){global $hooks;$hooks[$hook]=array($fn,$priority,$args);}
function wp_die($m){throw new RuntimeException($m);}
function check($v,$message){global $checks;++$checks;if(!$v){throw new RuntimeException($message);}}
function same($a,$b,$m){check($a===$b,$m);}
class Profile_Test_Errors {public $errors=array();public function add($k,$v){$this->errors[$k]=$v;}public function has_errors(){return !!$this->errors;}}

use Papijo\H5PThemerWordPress\Role\Role_Service;
use Papijo\H5PThemerWordPress\Role\Role_State;
use Papijo\H5PThemerWordPress\Role\Role_Store;
use Papijo\H5PThemerWordPress\Role\Role_View;
$registered=array('administrator'=>array('name'=>'Administrator','capabilities'=>array('manage_options'=>true)),'subscriber'=>array('name'=>'Subscriber','capabilities'=>array()),'editor'=>array('name'=>'Editor','capabilities'=>array()));
$users=array(1=>(object)array('ID'=>1,'roles'=>array('administrator'),'admin'=>true),2=>(object)array('ID'=>2,'roles'=>array('subscriber'),'admin'=>false),3=>(object)array('ID'=>3,'roles'=>array(),'admin'=>false));
function wp_roles(){global $registered;return (object)array('roles'=>$registered);}
function translate_user_role($label){return $label;}
function get_userdata($id){global $users;return $users[$id]??false;}
function user_can($user,$cap){return $cap==='manage_options'&&!empty($user->admin);}
function esc_url($value){return esc_attr($value);}
$roleDB=new \Papijo\H5PThemerWordPress\Tests\Fake_Database();
$roleStore=new Role_Store(array($roleDB,'open'),static function(){});$roles=new Role_Service($roleStore);
$state=Schema::initial();$profileStore=new Profile_Memory_Store();$service=new Profile_Service(static function()use(&$state){return $state;},$profileStore,$roles);$controller=new Profile_Controller($service);
function role_post($grants=array()) {global $roles;$r=$roles->registered();$values=array();foreach($r as $slug=>$entry){if(!$entry['automatic']){$values[$slug]=in_array($slug,$grants,true)?'1':'0';}}return array('pjh5pwp_op'=>'roles','role_nonce'=>wp_create_nonce($roles->nonce_action($roles->snapshot(),$r)),'roles'=>$values,'role_complete'=>'1');}
function set_roles($grants){global $roles,$actor,$administrator;$a=$actor;$wasAdmin=$administrator;$actor=1;$administrator=true;$result=$roles->submit(role_post($grants));check(in_array($result,array('committed','noop'),true),'Role save '.$result);$actor=$a;$administrator=$wasAdmin;}
function plan($payload,$id=2){global $service;return $service->plan($id,$payload,wp_create_nonce($service->nonce_action($id)));}
function apply($payload,$id=2){global $controller,$service;$_POST=array('pjh5pwp_profile'=>$payload,'pjh5pwp_profile_nonce'=>wp_create_nonce($service->nonce_action($id)));$e=new Profile_Test_Errors();$controller->validate($e,true,(object)array('ID'=>$id));check(!$e->has_errors(),'Valid profile submission');$controller->save($id);}
function render_profile($id=2){global $service;$c=new Profile_Controller($service);ob_start();$c->render((object)array('ID'=>$id));return ob_get_clean();}
$controller->register();same(array('show_user_profile','edit_user_profile','user_profile_update_errors','profile_update','admin_enqueue_scripts'),array_keys($hooks),'Profile lifecycle plus scoped preview assets');
check($roles->allowed(1),'Administrator automatically allowed with absent role config');check(!$roles->allowed(2),'Subscriber default denied');check(!$roles->allowed(3),'Roleless denied');check(!$roles->allowed(0),'Anonymous denied');
same('',render_profile(2),'No other-user administrative controls');same(0,$writes,'Views do not create profile records');check(strpos(render_profile(1),'pjh5pwp_profile[theme]')!==false,'Administrator own theme');check(strpos(render_profile(1),'pjh5pwp_profile[density]')!==false,'Administrator own Density');same(array(),$meta,'Admin view creates no record');
$html=(new Role_View())->render($roles,'https://isolated.invalid/themes');check(strpos($html,'Role permissions')!==false,'Role section');check(strpos($html,'Allowed automatically')!==false,'Automatic indication');check(strpos($html,'roles[administrator]')===false,'Administrator cannot be revoked');check(strpos($html,Role_Service::LABEL)!==false,'Unified label');
$actor=2;$administrator=false;check(!!plan(array('theme'=>'core'))['errors'],'Default learner forged save denied');
set_roles(array('subscriber'));check($roles->allowed(2),'Subscriber granted');check(!$roles->allowed(3),'Actor grant does not grant unrelated user');
$html=render_profile();check(strpos($html,'pjh5pwp_profile[theme]')!==false&&strpos($html,'pjh5pwp_profile[density]')!==false,'Unified permission displays both');check(strpos($html,'pjh5pwp_profile[allow_')===false,'No individual permission controls');
check(strpos($html,'supported H5P content embedded in WordPress pages')!==false,'Profile guidance identifies supported page playback');
check(strpos($html,'Styling is not currently applied in the WordPress H5P administration viewer')!==false,'Profile guidance identifies deferred administration viewing');
check(strpos($html,'Subscriber Profile &gt; View H5P content')!==false,'Profile guidance identifies deferred Subscriber viewing and escapes navigation text');
check(strpos($html,'not implemented')===false,'Obsolete playback implementation claim removed');
foreach(array('original','small','medium','large') as $density){apply(array('density'=>$density));same($density,$service->effective(2)['density'],'Native Density token '.$density);}
apply(array('theme'=>'core'));same('personal',$service->effective(2)['theme']['source'],'Explicit Core personal');same('core',$service->values(2)['theme'],'Core stable token');
apply(array('theme'=>'site_default'));same('global',$service->effective(2)['theme']['source'],'Global distinct');same('large',$service->values(2)['density'],'Theme does not modify Density');
foreach(array('daylight','lavender','mint','sunset','dark') as $id){$state['publication']['themes']['builtin:'.$id]=false;if($id==='daylight'){unset($state['publication']['themes']['builtin:daylight']);}apply(array('theme'=>'builtin:'.$id));same('personal',$service->effective(2)['theme']['source'],'Builtin always available');}
$ops=new Theme_Operations();$changes=new Library_Changes();$def=$ops->create_from_default(null,'public_theme','<script>Unsafe label</script>',array())['data'];$state=$changes->create($state,$def)['data'];
check(!!plan(array('theme'=>'custom:public_theme'))['errors'],'Unpublished selection rejected');
$state=$changes->publish($state,'custom:public_theme',true)['data'];apply(array('theme'=>'custom:public_theme'));
same('large',$service->effective(2)['density'],'Legacy theme density cannot override personal Density');
$html=render_profile();check(strpos($html,'&lt;script&gt;Unsafe label&lt;/script&gt;')!==false&&strpos($html,'<script>Unsafe label')===false,'Label escaped');
$state=$changes->publish($state,'custom:public_theme',false)['data'];$before=$meta;$html=render_profile();
check(strpos($html,'value="custom:public_theme"')===false,'Unpublished not a choice');check(strpos($html,'value="keep_saved" selected')!==false,'Unavailable preservation sentinel');same('global',$service->effective(2)['theme']['source'],'Unpublished fallback');
apply(array('theme'=>'keep_saved'));same($before,$meta,'Keep sentinel does not rewrite metadata');
apply(array('density'=>'small'));same('custom:public_theme',$service->values(2)['theme'],'Unrelated Density save preserves hidden choice');
$state=$changes->delete($state,array('kind'=>'custom','id'=>'public_theme'))['data'];same('global',$service->effective(2)['theme']['source'],'Deleted fallback');same('custom:public_theme',$service->values(2)['theme'],'Deleted reference retained');
apply(array('theme'=>'core'));check(!!plan(array('theme'=>'keep_saved'))['errors'],'Cannot use keep sentinel for available preference');
$before=$meta;
foreach(array(array('allow_theme'=>'1'),array('allow_density'=>'1'),array('theme'=>'custom:missing'),array('theme'=>'Dark'),array('theme'=>'../bad'),array('theme'=>array('core')),array('density'=>'huge'),array('density'=>null),array('evil'=>'1'),array('theme'=>'core','allow_theme'=>'1')) as $bad){check(!!plan($bad)['errors'],'Forged input rejected');same($before,$meta,'Invalid plan never writes');}
check(!!$service->plan(2,array('theme'=>'core'),'bad')['errors'],'Nonce failure');check(!!$service->plan(2,'bad','bad')['errors'],'Scalar payload');
check(!!plan(array('theme'=>'core'),1)['errors'],'Other user write rejected');
$edit=false;check(!!plan(array('density'=>'small'))['errors'],'Edit capability required');same('',render_profile(),'Unauthorized render empty');$edit=true;
$multisite=true;check(!!plan(array('density'=>'small'))['errors'],'Multisite fails closed');$multisite=false;
$before=$meta;$_POST=array();$e=new Profile_Test_Errors();$controller->validate($e,true,(object)array('ID'=>2));$controller->save(2);same($before,$meta,'Absent form does not write');
$controller->validate($e,false,(object)array());check(!$e->has_errors(),'Unrelated new-user validation needs no ID');
$_POST=array('pjh5pwp_profile'=>array('density'=>'small'));$e=new Profile_Test_Errors();$controller->validate($e,false,(object)array());check($e->has_errors(),'Forged new-user payload fails without missing-ID warning');$_POST=array();
apply(array());same($before,$meta,'Missing fields do not write');
$_POST=array('pjh5pwp_profile'=>array('density'=>'medium'));$e=new Profile_Test_Errors();$controller->validate($e,true,(object)array('ID'=>2));check($e->has_errors(),'Missing nonce is core validation error');$controller->save(2);same($before,$meta,'Rejected form cannot reach save');
$_POST=array('pjh5pwp_profile'=>array('density'=>'medium'),'pjh5pwp_profile_nonce'=>wp_create_nonce($service->nonce_action(2)));$e=new Profile_Test_Errors();$e->add('email','Core failure');$controller->validate($e,true,(object)array('ID'=>2));$controller->save(2);same($before,$meta,'Existing core errors prevent metadata commit');

$stale=wp_create_nonce($service->nonce_action(2));$saved=$service->values(2);set_roles(array());
same('',render_profile(),'Revocation hides both');check(!!$service->plan(2,array('density'=>'large'),$stale)['errors'],'Old learner form rejected after revocation');same('global',$service->effective(2)['theme']['source'],'Revoked theme uses global');same('original',$service->effective(2)['density'],'Revoked Density uses original');same($saved,$service->values(2),'Revocation preserves all profile bytes');
set_roles(array('subscriber'));same('personal',$service->effective(2)['theme']['source'],'Restored theme');same($saved['density'],$service->effective(2)['density'],'Restored Density');
foreach(array('site_default','core','builtin:daylight','builtin:lavender','builtin:mint','builtin:sunset','builtin:dark') as $theme){foreach(array('original','small','medium','large') as $density){apply(array('theme'=>$theme,'density'=>$density));same($theme,$service->values(2)['theme'],'Independent combination theme');same($density,$service->values(2)['density'],'Independent combination Density');}}
// Stored historical flags never influence role authorization and cannot be submitted.
$record=$profileStore->read(2);$legacy=$record['values'];$legacy['allow_theme']='1';$legacy['allow_density']='1';same('committed',$profileStore->compare_and_swap(2,$record,$legacy),'Seed existing v1 flags without migration');set_roles(array());check(!$service->personalized(2),'Old individual flags grant nothing');set_roles(array('subscriber'));$legacy=$profileStore->read(2);apply(array('theme'=>'builtin:mint','density'=>'large'));same('1',$service->values(2)['allow_theme'],'Preference change preserves inert legacy bytes');
$actor=1;$administrator=true;foreach(array('allow_theme','allow_density') as $flag){check(!!plan(array($flag=>'0'))['errors'],'Administrator cannot edit individual flags');check(!!plan(array($flag=>'1'),1)['errors'],'Administrator cannot edit own flags');}same('',render_profile(2),'Edit User contains no personal administration');apply(array('theme'=>'core','density'=>'small'),1);same('small',$service->effective(1)['density'],'Administrator saves own preferences without role grant');$actor=2;$administrator=false;
$before=$meta;$_POST=array('pjh5pwp_profile'=>array('density'=>'medium'),'pjh5pwp_profile_nonce'=>wp_create_nonce($service->nonce_action(2)));$e=new Profile_Test_Errors();$controller->validate($e,true,(object)array('ID'=>2));same($before,$meta,'Validation does not persist');
$failWrite=true;try{$controller->save(2);throw new RuntimeException('Expected visible storage failure');}catch(RuntimeException $e){check(strpos($e->getMessage(),'H5P state was not confirmed saved')!==false,'Storage failure explicitly reported');}$failWrite=false;same($before,$meta,'Failed write preserves complete state');
$nonce=wp_create_nonce($service->nonce_action(2));$v=$service->values(2);same('noop',$service->save_request(2,array('density'=>$v['density']),$nonce),'Identical submission noop');same($before,$meta,'Noop retains state and revision');apply(array('density'=>'small'));apply(array('density'=>$v['density']));check(!!$service->plan(2,array('theme'=>'core'),$nonce)['errors'],'ABA invalidates profile form');
$nonce=wp_create_nonce($service->nonce_action(2));$profileStore->race=static function()use($profileStore){$r=$profileStore->read(2);$v=$r['values'];$v['theme']='core';same('committed',$profileStore->compare_and_swap(2,$r,$v),'Concurrent profile save');};same('conflict',$service->save_request(2,array('density'=>'medium'),$nonce),'Final profile CAS rejects racing change');same($v['density'],$service->values(2)['density'],'Losing request cannot partly save');
// Permission is rechecked after validation and before deferred profile persistence.
$_POST=array('pjh5pwp_profile'=>array('density'=>'medium'),'pjh5pwp_profile_nonce'=>wp_create_nonce($service->nonce_action(2)));$e=new Profile_Test_Errors();$controller->validate($e,true,(object)array('ID'=>2));$before=$meta;set_roles(array());try{$controller->save(2);throw new RuntimeException('Expected revoked save failure');}catch(RuntimeException $e){check(strpos($e->getMessage(),'H5P state was not confirmed saved')!==false,'Deferred revoked save reported');}same($before,$meta,'Revocation between validation and commit preserves profile');
// Role lifecycle, target-aware capability semantics, and strict admin submissions.
$registered['teacher']=array('name'=>'Administrator','capabilities'=>array());$users[3]->roles=array('teacher');check(!$roles->allowed(3),'New custom role defaults denied despite admin display name');set_roles(array('teacher'));check($roles->allowed(3),'Custom role granted');$registered['teacher']['name']='<script>Renamed</script>';check($roles->allowed(3),'Rename preserves slug grant');$users[2]->roles=array('subscriber','teacher');check($roles->allowed(2),'Any role grants');unset($registered['teacher']);check(!$roles->allowed(3)&&!$roles->allowed(2),'Deleted role grants ignored even on stale user objects');$users[2]->roles=array('subscriber');
$users[3]->admin=true;check($roles->allowed(3),'Target capability grants even without administrator role');$users[3]->admin=false;$actor=1;$administrator=true;check(!$roles->allowed(3),'Actor administrator capability never grants target');check($roles->allowed(1),'Automatic admin independent of role config');
set_roles(array('subscriber'));$post=role_post(array());$winner=role_post(array('editor'));same('committed',$roles->submit($winner),'Administrator winner');same('role_conflict_or_invalid_nonce',$roles->submit($post),'Stale admin rejected');check($roles->allowed(1),'Admin unaffected by config');
foreach(array('missing','administrator','../subscriber') as $slug){$bad=role_post();$bad['roles'][$slug]='1';same('role_invalid_identifiers',$roles->submit($bad),'Invalid/automatic role submission rejected');}
foreach(array(true,1,array('1'),'yes') as $value){$bad=role_post();$bad['roles']['subscriber']=$value;same('role_invalid_submission',$roles->submit($bad),'Noncanonical role flag rejected');}
$bad=role_post();unset($bad['roles']['subscriber']);same('role_invalid_identifiers',$roles->submit($bad),'Missing role cannot partly revoke');$bad=role_post();unset($bad['role_complete']);same('role_invalid_submission',$roles->submit($bad),'Truncated form rejected');$bad=role_post();$bad['role_nonce']='forged';same('role_conflict_or_invalid_nonce',$roles->submit($bad),'Nonce failure');
$bad=role_post();$registered['later']=array('name'=>'Later role','capabilities'=>array());same('role_conflict_or_invalid_nonce',$roles->submit($bad),'Changed registry invalidates administrator form');check(!$roles->allowed(3),'New role still denied');
$actor=2;$administrator=false;same('role_forbidden',$roles->submit($bad),'Learner forged admin request denied');same('',(new Role_View())->render($roles,'https://isolated.invalid'),'Learner cannot render role admin');$actor=1;$administrator=true;$multisite=true;same('role_forbidden',$roles->submit($bad),'Multisite admin denied');check(!$roles->allowed(1),'Multisite resolver denied');$multisite=false;
$actor=1;$administrator=true;$registered['admin_alias']=array('name'=>'Site manager','capabilities'=>array('manage_options'=>true));$html=(new Role_View())->render($roles,'https://isolated.invalid');check(strpos($html,'roles[admin_alias]')===false,'Capability-bearing custom role is read-only');$registered['administrator']['name']='Renamed administrators';check($roles->allowed(1),'Renamed administrator retains automatic capability access');
$good=$roleDB->rows;$roleDB->rows[Role_State::OPTION]['option_value']=serialize(array('schema_version'=>99,'revision'=>1,'roles'=>array('subscriber'=>true)));check(!$roles->allowed(2),'Malformed config fails closed');check($roles->allowed(1),'Malformed config cannot deny admin');check(strpos((new Role_View())->render($roles,'https://isolated.invalid'),'No role permissions can be saved')!==false,'Malformed config visibly blocked');same('role_storage_unavailable',$roles->submit(role_post_placeholder()),'Malformed data cannot be overwritten');$roleDB->rows=$good;
function role_post_placeholder(){return array('pjh5pwp_op'=>'roles');}
$roleDB->fault='read';check(!$roles->allowed(2),'Read failure denies learner');check($roles->allowed(1),'Read failure preserves inherent admin');$roleDB->fault=null;
$html=(new Role_View())->render($roles,'https://isolated.invalid');check(strpos($html,'Save role permissions')!==false,'Explicit save');
echo 'Phase 2A-R roles/profiles: '.$checks." assertions passed; no database or CMS bootstrap.\n";
