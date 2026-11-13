<?php
/** Included after the existing administration suite; isolated option SQL double. */
use Papijo\H5PThemerWordPress\Admin\Admin_Controller;
require_once dirname(__DIR__).'/storage/fake-database.php';
function wp_roles(){return (object)array('roles'=>array('administrator'=>array('name'=>'Administrator','capabilities'=>array('manage_options'=>true)),'subscriber'=>array('name'=>'Subscriber <script>bad()</script>','capabilities'=>array())));}
function translate_user_role($name){return $name;}
$roleDb=new \Papijo\H5PThemerWordPress\Tests\Fake_Database();
$roleStore=new \Papijo\H5PThemerWordPress\Role\Role_Store(array($roleDb,'open'),static function(){});
$roleService=new \Papijo\H5PThemerWordPress\Role\Role_Service($roleStore);
$roleController=new Admin_Controller(static function()use($service){return $service;},$roleService);
$_GET=array();$_SERVER['REQUEST_METHOD']='GET';$themeBefore=$store->state;
ob_start();$roleController->render();$roleHtml=ob_get_clean();
check(strpos($roleHtml,'Role permissions')!==false,'Role section integrated into existing overview');
check(strpos($roleHtml,'Subscriber &lt;script&gt;bad()&lt;/script&gt;')!==false&&strpos($roleHtml,'Subscriber <script>bad()')===false,'Dynamic role labels escaped');
same(null,$roleStore->read(),'Role rendering never initializes option');
$rolePost=array('pjh5pwp_op'=>'roles','role_nonce'=>wp_create_nonce($roleService->nonce_action(null,$roleService->registered())),'roles'=>array('subscriber'=>'1'),'role_complete'=>'1');
$_SERVER['REQUEST_METHOD']='POST';$_POST=$rolePost;$roleController->load();
same(array('subscriber'=>true),$roleStore->read()['roles'],'Page POST saves role permissions');same($themeBefore,$store->state,'Role form cannot mutate theme aggregate');
ob_start();$roleController->render();$roleHtml=ob_get_clean();check(strpos($roleHtml,'Role permissions saved.')!==false,'Role-specific success notice');
$roleController->load();ob_start();$roleController->render();$roleHtml=ob_get_clean();check(strpos($roleHtml,'role_conflict_or_invalid_nonce')!==false,'Repeated stale POST is visibly rejected');same(0,$roleStore->read()['revision'],'Repeated form cannot increment revision');
$GLOBALS['pjh5pwp_test_cap']=false;try{$roleController->load();throw new RuntimeException('Expected capability failure');}catch(RuntimeException $e){same('wp_die:403',$e->getMessage(),'Page capability checked before role handler');}$GLOBALS['pjh5pwp_test_cap']=true;
$_GET=array('pjh5pwp_view'=>'create','pjh5pwp_source'=>'');ob_start();$roleController->render();$roleHtml=ob_get_clean();check(strpos($roleHtml,'Save role permissions')===false,'Role form only on overview');
$_GET=array();$_POST=array();$_SERVER['REQUEST_METHOD']='GET';

