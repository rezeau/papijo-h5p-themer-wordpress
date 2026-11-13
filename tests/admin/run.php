<?php
/** Phase 1C-B: isolated form, authorization, rendering and lifecycle verification. */
if('cli'!==PHP_SAPI){exit;}
error_reporting(E_ALL);
set_error_handler(static function($severity,$message,$file,$line){throw new ErrorException($message,0,$severity,$file,$line);});
require dirname(__DIR__,2) . '/includes/theme-domain.php';
require dirname(__DIR__,2) . '/includes/wordpress-storage.php';
require dirname(__DIR__,2) . '/includes/administration.php';
require __DIR__ . '/wordpress-doubles.php';
require dirname(__DIR__) . '/storage/fake-database.php';
use Papijo\H5PThemerWordPress\Admin\Admin_Service;
use Papijo\H5PThemerWordPress\Admin\Admin_View;
use Papijo\H5PThemerWordPress\Admin\Admin_Controller;
use Papijo\H5PThemerWordPress\Admin\Theme_Preview;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
final class Admin_Test_Store implements Snapshot_Store {
	public $state=null; public $reads=0; public $writes=0; public $race; public $fault;
	public function read() { ++$this->reads; return $this->state; }
	public function compare_and_swap($revision,array $next) {
		++$this->writes;
		if($this->fault){throw new Storage_Exception($this->fault[0],$this->fault[1]);}
		if($this->race){$race=$this->race;$this->race=null;$race($this);}
		$current=null===$this->state?null:$this->state['revision'];
		if($revision!==$current || (new Schema())->transition($this->state,$next)['errors']){return false;}
		$this->state=$next;return true;
	}
}
$checks=0;
function check($condition,$message){global $checks;if(!$condition){throw new RuntimeException($message);}++$checks;}
function same($expected,$actual,$message){check($expected===$actual,$message);}
function fields(){return array_fill_keys(Color_Properties::supported(),'');}
function post_for($store,$op,$source='',array $extra=array()){
	$state=$store->state;$revision=null===$state?'absent':(string)$state['revision'];$context=Admin_Service::context($state);
	return array_merge(array('pjh5pwp_op'=>$op,'source'=>$source,'revision'=>$revision,'pjh5pwp_context'=>$context,'pjh5pwp_nonce'=>wp_create_nonce(Admin_Service::nonce_action($op,$source,$revision,$context))),$extra);
}
function ok($service,$input){$r=$service->submit($input);check(!$r['errors'],'Valid operation: ' . implode(',',$r['errors']));same('committed',$r['outcome'],'Confirmed commit');return $r;}
function rejects($service,$input,$code){$r=$service->submit($input);check(in_array($code,$r['errors'],true),'Expected rejection ' . $code . ', got ' . implode(',',$r['errors']));return $r;}
function extra($id='example',$label='Example'){return array('id'=>$id,'label'=>$label,'description'=>'Description','colors'=>fields());}
$store=new Admin_Test_Store();$service=new Admin_Service($store);
same('absent',$service->status()['state'],'Read-only absent status');same(0,$store->writes,'GET does not initialize');
$input=post_for($store,'initialize');$reads=$store->reads;
$GLOBALS['pjh5pwp_test_cap']=false;rejects($service,$input,'forbidden');same('forbidden',$service->status()['state'],'Unauthorized status');same($reads,$store->reads,'Unauthorized cannot read storage');
$GLOBALS['pjh5pwp_test_cap']=true;$GLOBALS['pjh5pwp_test_multisite']=true;rejects($service,$input,'forbidden');$GLOBALS['pjh5pwp_test_multisite']=false;
$bad=$input;$bad['pjh5pwp_nonce']='forged';rejects($service,$bad,'invalid_nonce');same($reads,$store->reads,'Nonce failure before storage read');
ok($service,$input);same(Schema::initial(),$store->state,'Explicit initial state');rejects($service,$input,'storage_conflict');
$p='--h5p-theme-background';$colors=fields();$colors[$p]='#aBc';$input=post_for($store,'create','',array_merge(extra(),array('colors'=>$colors)));
ok($service,$input);same(array($p=>'#aBc'),$store->state['definitions']['example']['colors'],'Sparse override stored');same('daylight',$store->state['definitions']['example']['base_preset']['id'],'Core Add fallback pinned Daylight');same(false,$store->state['publication']['themes']['custom:example'],'New theme unpublished');
rejects($service,post_for($store,'create','',extra()),'destination_unavailable');
$input=post_for($store,'edit','custom:example',extra('example','Renamed <script>alert(1)</script>'));ok($service,$input);same(array(),$store->state['definitions']['example']['colors'],'Clearing override restores inheritance');same('example',$store->state['definitions']['example']['id'],'Rename preserves identity');
$before=$store->state;
foreach(array('red','#1234',' #abc','#abcd00ff','var(--x)','url(https://invalid)','</style><script>','') as $badColor){
	if(''===$badColor){continue;}$input=post_for($store,'edit','custom:example',extra());$input['colors'][$p]=$badColor;rejects($service,$input,'invalid_hex_color');same($before,$store->state,'Invalid color never persists');
}
foreach(array('Bad','1bad','bad-id',str_repeat('a',65),'') as $id){rejects($service,post_for($store,'create','',extra($id)),'destination_unavailable');}
foreach(array('colors'=>null,'label'=>array('x'),'description'=>array('x'),'id'=>array('x')) as $key=>$value){$input=post_for($store,'edit','custom:example',extra());$input[$key]=$value;check(!empty($service->submit($input)['errors']),'Array/null field fails safely');}
$input=post_for($store,'edit','custom:example',extra());unset($input['colors'][$p]);rejects($service,$input,'invalid_override_input');
$input=post_for($store,'edit','custom:example',extra());$input['colors']['--evil']='#fff';rejects($service,$input,'invalid_override_input');
foreach(array('density'=>'small','base_preset'=>array('id'=>'dark'),'user_id'=>'1','unknown'=>'yes') as $key=>$value){$input=post_for($store,'edit','custom:example',extra());$input[$key]=$value;rejects($service,$input,'malformed_submission');}
foreach(array(null,'x',array(),array('pjh5pwp_op'=>array())) as $input){rejects($service,$input,'malformed_submission');}
rejects($service,post_for($store,'edit','builtin:dark',extra('dark')),'theme_not_mutable');
rejects($service,post_for($store,'delete','builtin:dark',array('confirm'=>'1')),'theme_not_mutable');
rejects($service,post_for($store,'duplicate','core',extra('core_copy')),'core_not_duplicable');
foreach(array('daylight','lavender','mint','sunset','dark') as $id){
	$values=extra('copy_'.$id,ucfirst($id).' copy');unset($values['colors']);ok($service,post_for($store,'duplicate','builtin:'.$id,$values));$d=$store->state['definitions']['copy_'.$id];same(array(),$d['colors'],'Builtin copy zero overrides');same($id,$d['base_preset']['id'],'Exact preset pin');same(false,$store->state['publication']['themes']['custom:copy_'.$id],'Copy starts unpublished');
}
$input=post_for($store,'duplicate','builtin:dark',extra('illegal_copy'));rejects($service,$input,'theme_not_mutable');
ok($service,post_for($store,'default','',array('default_theme'=>'custom:example')));same('example',$store->state['settings']['default_theme']['id'],'Unpublished default allowed');
rejects($service,post_for($store,'delete','custom:example',array('confirm'=>'1')),'theme_is_global_default');
$input=post_for($store,'edit','custom:example',extra());$input['colors'][$p]='#123456';ok($service,$input);
ok($service,post_for($store,'create','',extra('seeded','Seeded')));same(array($p=>'#123456'),$store->state['definitions']['seeded']['colors'],'Blank Add keeps captured global overrides');
ok($service,post_for($store,'duplicate','custom:example',array_merge(extra('custom_copy'),array('colors'=>array_replace(fields(),array($p=>'#123456'))))));same($store->state['definitions']['example']['base_preset'],$store->state['definitions']['custom_copy']['base_preset'],'Custom copy pins preset, not live custom parent');
$revision=$store->state['revision'];ok($service,post_for($store,'visibility','',array('visible'=>array('custom:example'=>'1','builtin:dark'=>'1'))));same($revision+1,$store->state['revision'],'Batch publication is a single revision');same(true,$store->state['publication']['themes']['custom:example'],'Explicit publication');same(false,$store->state['publication']['themes']['builtin:mint'],'Unchecked optional unpublishes');same('example',$store->state['settings']['default_theme']['id'],'Publication does not change default');
foreach(array(array('core'=>'1'),array('builtin:daylight'=>'1'),array('custom:missing'=>'1'),array('builtin:dark'=>'0'),array('builtin:dark'=>array('1')),null) as $visible){check(!empty($service->submit(post_for($store,'visibility','',array('visible'=>$visible)))['errors']),'Malformed/fixed visibility rejected');}
foreach(array('site_default','custom:missing','builtin:missing',array('core')) as $ref){check(!empty($service->submit(post_for($store,'default','',array('default_theme'=>$ref)))['errors']),'Invalid default rejected');}
ok($service,post_for($store,'default','',array('default_theme'=>'builtin:mint')));rejects($service,post_for($store,'delete','custom:example'),'delete_confirmation_required');ok($service,post_for($store,'delete','custom:example',array('confirm'=>'1')));same(false,$store->state['publication']['themes']['custom:example'],'Deleted identity unpublished');check(!isset($store->state['definitions']['example']),'Definition deleted');rejects($service,post_for($store,'edit','custom:example',extra()),'theme_not_mutable');
// Stale forms, changing captured global sources, and CAS races never silently rebase.
$stale=post_for($store,'edit','custom:seeded',extra('seeded','Old edit'));ok($service,post_for($store,'default','',array('default_theme'=>'builtin:dark')));$winner=$store->state;rejects($service,$stale,'storage_conflict');same($winner,$store->state,'Stale data cannot overwrite winner');
$stale['revision']=(string)$store->state['revision'];rejects($service,$stale,'invalid_nonce');
$input=post_for($store,'create','',extra('stale_add'));ok($service,post_for($store,'default','',array('default_theme'=>'core')));rejects($service,$input,'storage_conflict');
$input=post_for($store,'default','',array('default_theme'=>'builtin:mint'));$store->race=static function($s){$s->state=(new Library_Changes())->set_default($s->state,Admin_Service::reference('builtin:lavender'))['data'];};rejects($service,$input,'storage_conflict');same('lavender',$store->state['settings']['default_theme']['id'],'CAS race winner preserved');
foreach(array(array('storage_write_failed','not_committed'),array('storage_write_unknown','unknown'),array('storage_cache_failed','committed')) as $fault){$store->fault=$fault;$r=rejects($service,post_for($store,'default','',array('default_theme'=>'core')),$fault[0]);same($fault[1],$r['outcome'],'Exact storage outcome reported');}$store->fault=null;
$valid=$store->state;$input=post_for($store,'default','',array('default_theme'=>'core'));$store->state=array('schema_version'=>1);check(!empty($service->status()['errors']),'Malformed storage diagnostic');$writes=$store->writes;check(!empty($service->submit($input)['errors']),'Malformed storage not overwritten');same($writes,$store->writes,'Malformed storage never repaired by form');$store->state=$valid;
// Round-trip legacy metadata without exposing/using it for preview.
$store->state['definitions']['seeded']['density']='small';$input=post_for($store,'edit','custom:seeded',extra('seeded'));ok($service,$input);same('small',$store->state['definitions']['seeded']['density'],'Legacy density round-trips independently');
$preview=new Theme_Preview(static function(){return ':root{--h5p-theme-background:#F9FBFF;}';});
$view=new Admin_View('http://isolated.invalid/wp-admin/admin.php?page=pjh5pwp-themes',$preview);
$writes=$store->writes;$html=$view->render($service->status());same($writes,$store->writes,'Rendering catalog is read-only');check(false!==strpos($html,'Save learner visibility'),'Visibility explicit submit');check(false!==strpos($html,'builtin:daylight'),'Stable builtin identifiers');
$html=$view->render($service->status(),'edit','custom:seeded');same(33,substr_count($html,'data-pjh5pwp-color='),'All 33 editable fields');same(33,substr_count($html,'type="color"'),'33 native color companions');same(5,substr_count($html,'class="pjh5pwp-color-group"'),'Five collapsed groups');same(0,substr_count($html,'class="pjh5pwp-color-group" open'),'Default groups collapsed');check(false===strpos($html,'name="density"'),'No legacy or learner Density control');check(false!==strpos($html,'data-pjh5pwp-preview='),'Custom edit live metadata');check(false!==strpos($html,'sandbox="allow-same-origin"'),'Scriptless sandbox');check(false===strpos($html,'allow-scripts'),'No preview script permission');
$source=html_entity_decode(html_entity_decode($html,ENT_QUOTES,'UTF-8'),ENT_QUOTES,'UTF-8');check(false!==strpos($source,"default-src 'none'"),'Preview CSP denies fetching');check(false!==strpos($source,'Selected answer (example)'),'Representative samples');check(false!==strpos($source,'pjh5pwp-preview-progress'),'Progress sample');
foreach(Color_Properties::groups() as $group){check(false!==strpos($html,$group['title']),'Exact group title');}
$bad=post_for($store,'edit','custom:seeded',extra('seeded','<script>bad()</script>'));$bad['colors'][$p]='</style><script>evil()</script>';$html=$view->render($service->status(),'edit','custom:seeded',$bad,array('invalid_hex_color'));
check(false===strpos($html,'<script>bad()'),'Label output escaped');check(false!==strpos($html,'&lt;script&gt;bad()'),'Safe submitted name preserved');check(false!==strpos($html,'&lt;/style&gt;&lt;script&gt;evil()'),'Invalid color text escaped and preserved');same(1,substr_count($html,'class="pjh5pwp-color-group" open'),'Group with invalid color opens');check(false===strpos(html_entity_decode($html,ENT_QUOTES,'UTF-8'),'.pjh5pwp-preview-sample{--h5p-theme-background:</style>'),'Submitted invalid CSS excluded from saved frame');
$html=$view->render($service->status(),'edit','custom:seeded',$stale,array('storage_conflict'));check(false!==strpos($html,' disabled'),'Stale saves disabled');
$before=$store->state;$submitted=post_for($store,'edit','custom:seeded',extra('seeded','Retain deleted edit'));$store->state=(new Library_Changes())->delete($store->state,Admin_Service::reference('custom:seeded'))['data'];$html=$view->render($service->status(),'edit','custom:seeded',$submitted,array('storage_conflict'));check(false!==strpos($html,'pjh5pwp-retained') && false!==strpos($html,'Retain deleted edit'),'Safe edits remain copyable after source deletion');check(false===strpos($html,'<form'),'Deleted source cannot be silently recreated');$store->state=$before;
foreach(array('daylight','lavender','mint','sunset','dark') as $id){$html=$view->render($service->status(),'view','builtin:'.$id);check(false===strpos($html,'<form'),'Builtin view has no edit form');check(false!==strpos($html,'sandbox='),'Builtin saved preview');$html=$view->render($service->status(),'duplicate','builtin:'.$id);check(false===strpos($html,'name="colors['),'Builtin duplication colors read-only');}
$html=$view->render($service->status(),'duplicate','custom:seeded');same(33,substr_count($html,'data-pjh5pwp-color='),'Custom copy editable sparse overrides');check(false===strpos($html,'data-pjh5pwp-preview='),'No live preview on duplicate');
$html=$view->render($service->status(),'edit','builtin:dark');check(false===strpos($html,'<form'),'No builtin edit UI');$html=$view->render($service->status(),'delete','builtin:dark');check(false===strpos($html,'<form'),'No builtin deletion UI');
check(false!==strpos($preview->render(null,array()),'H5P Core defaults'),'Explicit Core preview');
$malicious=new Theme_Preview(static function(){return '</STYLE><script>evil()</script>';});check(false===strpos($malicious->render(null,array()),'<iframe'),'Unsafe local CSS fails closed');
$GLOBALS['pjh5pwp_test_cap']=false;same('',$preview->render(null,array()),'Preview access guarded');$html=$view->render($service->status());check(false===strpos($html,'<form'),'Unauthorized rendering has no forms');$GLOBALS['pjh5pwp_test_cap']=true;
// Menu fallback, precise page-only assets, page-load POST and PRG.
define('PJH5PWP_FILE',dirname(__DIR__,2).'/papijo-h5p-themer-wordpress.php');define('PJH5PWP_VERSION','0.1.1-beta.1');
$controller=new Admin_Controller(static function()use($service){return $service;});$controller->register();same(array('admin_menu','admin_enqueue_scripts'),array_keys($GLOBALS['pjh5pwp_test_hooks']),'No runtime/profile/activity hooks');
$menu=array(array('H5P','view_h5p_contents','h5p'));$controller->menu();same('h5p',$GLOBALS['pjh5pwp_test_menus'][0][0],'Verified H5P menu parent');same('pjh5pwp-themes',$GLOBALS['pjh5pwp_test_menus'][0][4],'Unique page slug');
$controller->assets('foreign-page');same(array(),$GLOBALS['pjh5pwp_test_assets'],'No assets on foreign admin pages');$controller->assets('h5p_page_pjh5pwp-themes');same(2,count($GLOBALS['pjh5pwp_test_assets']),'Only our admin CSS and JS');check(0===strpos($GLOBALS['pjh5pwp_test_inline'][1],"window['pjh5pwp-admin'] = "),'Hyphenated JS identifier uses bracket notation');same('before',$GLOBALS['pjh5pwp_test_inline'][2],'Messages provided before behavior script');
$menu=array();$fallback=new Admin_Controller(static function()use($service){return $service;});$fallback->menu();same('options-general.php',$GLOBALS['pjh5pwp_test_menus'][1][0],'Missing H5P Settings fallback');
$menu=array(array('H5P','view_h5p_contents','h5p'));$GLOBALS['pjh5pwp_test_h5p_cap']=false;$fallback->menu();same('options-general.php',$GLOBALS['pjh5pwp_test_menus'][2][0],'Manage-options administrator without H5P menu capability uses Settings');$GLOBALS['pjh5pwp_test_h5p_cap']=true;
$GLOBALS['pjh5pwp_test_cap']=false;$count=count($GLOBALS['pjh5pwp_test_menus']);$fallback->menu();same($count,count($GLOBALS['pjh5pwp_test_menus']),'Unauthorized sees no menu');try{$fallback->load();throw new RuntimeException('Expected wp_die');}catch(RuntimeException $e){same('wp_die:403',$e->getMessage(),'Direct unauthorized request denied');}$GLOBALS['pjh5pwp_test_cap']=true;
$_SERVER['REQUEST_METHOD']='GET';$writes=$store->writes;$controller->load();same($writes,$store->writes,'Page-load GET never mutates');check($GLOBALS['pjh5pwp_test_nocache'],'Administration responses uncached');
$_SERVER['REQUEST_METHOD']='POST';$_POST=post_for($store,'default','',array('default_theme'=>'builtin:dark'));try{$controller->load();throw new RuntimeException('Expected redirect');}catch(Pjh5pwp_Test_Redirect $e){}same(303,$GLOBALS['pjh5pwp_test_redirect'][1],'Successful POST redirects using 303');same('dark',$store->state['settings']['default_theme']['id'],'POST calls service');
$controller=new Admin_Controller(static function()use($service){return $service;});$_POST=array_map(static function($v){return is_string($v)?addslashes($v):$v;},post_for($store,'create','',extra('quotes',"Jo's theme")));$_POST['pjh5pwp_nonce']='bad';$controller->load();$_GET=array();ob_start();$controller->render();$html=ob_get_clean();check(false!==strpos($html,'Jo&#039;s theme'),'WordPress request unslashed once and safely preserved');
// Batch plan itself remains domain-level and all-or-nothing on invalid tokens.
$before=$store->state;$plan=(new Library_Changes())->publish_many($before,array('builtin:dark'=>false,'custom:missing'=>true));check(!empty($plan['errors']),'Invalid batch plan rejected as a whole');same($before,$store->state,'Batch planner never writes');
require __DIR__ . '/ux-regressions.php';
require dirname(__DIR__) . '/roles/admin-regressions.php';
// Exercise the real storage adapter through the administration boundary.
$engine_db=new \Papijo\H5PThemerWordPress\Tests\Fake_Database();
$engine_store=new \Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store(array($engine_db,'open'),static function(){});
$engine_service=new Admin_Service($engine_store);$engine_view=new Admin_View('http://isolated.invalid/admin.php?page=pjh5pwp-themes');
$engine_input=post_for(new Admin_Test_Store(),'initialize');
foreach(array('MyISAM','MEMORY') as $engine){
	$engine_db->engine=$engine;$engine_status=$engine_service->status();same('storage_options_engine_unsupported',$engine_status['state'],'Specific engine status reaches administration');
	$html=$engine_view->render($engine_status);check(false!==strpos($html,'storage_options_engine_unsupported'),'Engine code rendered');
	foreach(array('historical options-based storage adapter','own InnoDB state table','WordPress options may use MyISAM') as $explanation){check(false!==strpos($html,$explanation),'Actionable engine explanation: '.$explanation);}
	check(false===strpos($html,'<form'),'Unsupported engine offers no initialization or editing forms');
	$r=rejects($engine_service,$engine_input,'storage_options_engine_unsupported');same('not_committed',$r['outcome'],'Blocked administrator POST never commits');same(array(),$engine_db->rows,'Blocked administrator requests never initialize');
}
$GLOBALS['pjh5pwp_test_cap']=false;$reads=$engine_db->connections;$html=$engine_view->render($engine_service->status());check(false===strpos($html,'storage_options_engine_unsupported'),'No engine disclosure to unauthorized users');same($reads,$engine_db->connections,'Unauthorized diagnostics do not open storage');$GLOBALS['pjh5pwp_test_cap']=true;
echo 'Phase 1C-B/1C-D administration: ' . $checks . " assertions passed.\n";
