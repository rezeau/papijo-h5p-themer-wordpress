<?php
/** Source-only Profile preview regression/HTML fixtures. No CMS bootstrap or live database. */
if ( 'cli' !== PHP_SAPI ) { exit; }
ob_start(); require __DIR__ . '/run.php'; ob_end_clean();
require dirname( __DIR__, 2 ) . '/includes/administration.php';
use Papijo\H5PThemerWordPress\Profile\Profile_Preview;
use Papijo\H5PThemerWordPress\Profile\Profile_Controller;
use Papijo\H5PThemerWordPress\Profile\Profile_Service;
use Papijo\H5PThemerWordPress\Profile\Profile_State;
use Papijo\H5PThemerWordPress\Admin\Theme_Preview;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Storage\Schema;

function wp_json_encode($value,$flags=0){return json_encode($value,$flags);}
function get_bloginfo($key){return 'en-US';}
function esc_html__($text,$domain){return esc_html(__($text,$domain));}
function plugins_url($path,$file){return '/assets/' . basename($path);}
$previewAssets=array();
function wp_enqueue_style(...$args){$GLOBALS['previewAssets'][]=array('style',$args);}
function wp_enqueue_script(...$args){$GLOBALS['previewAssets'][]=array('script',$args);}
define('PJH5PWP_FILE',dirname(__DIR__,2).'/papijo-h5p-themer-wordpress.php');
define('PJH5PWP_VERSION','0.1.1-beta.1');
function preview_data($html){
    check(1===preg_match('/data-pjh5pwp-profile-preview="([^"]+)"/',$html,$match),'Preview payload exists');
    return json_decode(html_entity_decode($match[1],ENT_QUOTES,'UTF-8'),true);
}
function preview_document($html){
    check(1===preg_match('/srcdoc="([^"]+)"/',$html,$match),'One iframe document exists');
    return html_entity_decode($match[1],ENT_QUOTES,'UTF-8');
}
function preview_render($controller,$id=2){ob_start();$controller->render((object)array('ID'=>$id));return ob_get_clean();}
$start=$checks;
$actor=2;$administrator=false;$edit=true;$multisite=false;set_roles(array('subscriber'));
$previewState=Schema::initial();
$previewOps=new \Papijo\H5PThemerWordPress\Theme\Theme_Operations();
$previewChanges=new \Papijo\H5PThemerWordPress\Theme\Library_Changes();
foreach(array('public_color'=>'Public <script>alert(1)</script>','hidden_global_secret'=>'Private global name','hidden_other_secret'=>'Private unrelated name') as $id=>$label){
    $definition=$previewOps->duplicate(array('kind'=>'builtin','id'=>'mint','version'=>Preset_Catalog::VERSION),$id,$label)['data'];
    $definition['colors']=array('--h5p-theme-background'=>'public_color'===$id?'#123456':('hidden_global_secret'===$id?'#876543':'#98F123'));
    $definition['description']='Private definition description';
    $previewState=$previewChanges->create($previewState,$definition)['data'];
}
$legacy=array('schema_version'=>1,'id'=>'legacy','label'=>'Legacy sparse','density'=>'large','colors'=>array('--h5p-theme-background'=>'#abc'));
$previewState=$previewChanges->create($previewState,$legacy)['data'];
foreach(array('public_color','legacy') as $id){$previewState=$previewChanges->publish($previewState,'custom:'.$id,true)['data'];}
$previewState=$previewChanges->set_default($previewState,array('kind'=>'custom','id'=>'hidden_global_secret'))['data'];
$store=new class {
    public $reads=0;public $record;
    public function read($id){++$this->reads;return $this->record;}
    public function compare_and_swap(){throw new RuntimeException('Preview must not save');}
};
$store->record=array('schema_version'=>1,'revision'=>3,'values'=>Profile_State::defaults());
$libraryReads=0;$coreReads=0;$snordian=false;
$corePath=$argv[2]??null;
$renderer=new Theme_Preview(static function()use(&$coreReads,$corePath){++$coreReads;return $corePath?Theme_Preview::read_css($corePath):':root{--h5p-theme-background:#F9FBFF;--h5p-theme-main-cta-base:#2563eb;}';});
$preview=new Profile_Preview($renderer,static function()use(&$snordian){return $snordian;});
$service=new Profile_Service(static function()use(&$previewState,&$libraryReads){++$libraryReads;return $previewState;},$store,$roles);
$controller=new Profile_Controller($service,$preview);
$beforeWrites=$writes;$beforeMeta=$meta;
$html=preview_render($controller);$data=preview_data($html);$doc=preview_document($html);
same(1,$store->reads,'Reuse existing single profile snapshot');same(1,$libraryReads,'Reuse existing single library read');
same(1,substr_count($html,'<iframe '),'One iframe for the complete catalog');
check(false!==strpos($html,'<details class="pjh5pwp-profile-preview"><summary>Preview selected Theme</summary>'),'Native disclosure collapsed by default');
check(strpos($html,'</select><details')<strpos($html,'id="pjh5pwp-density"'),'Preview is directly beneath Theme, before Density');
check(false===strpos($html,'<details open'),'Disclosure not forced open');
foreach(array('hidden_global_secret','Private global name','hidden_other_secret','Private unrelated name','Private definition description','#98F123','base_preset','schema_version') as $secret){check(false===strpos($html,$secret),'Private data absent everywhere: '.$secret);}
same('#876543',$data['themes']['site_default']['colors']['--h5p-theme-background'],'Unpublished global effective colors permitted');
same('Site default',$data['themes']['site_default']['label'],'Unpublished global generic label');
same(array(),$data['themes']['core']['colors'],'Core has no overrides');
same(33,count($data['themes']['builtin:daylight']['colors']),'Daylight has complete preset overrides, distinct from Core');
foreach(array('daylight','lavender','mint','sunset','dark') as $id){
    $expected=(new Palette_Resolver())->resolve(array('kind'=>'builtin','id'=>$id,'version'=>'moodle-478fed30d2a4'))['data']['colors'];
    $expected=array_intersect_key($expected,array_flip(\Papijo\H5PThemerWordPress\Theme\Color_Properties::supported()));
    $actual=$data['themes']['builtin:'.$id]['colors'];
    same(33,count($actual),'All supported preset properties retained: '.$id);
    ksort($expected);ksort($actual);same($expected,$actual,'Exact pinned preset '.$id);
}
$public=$data['themes']['custom:public_color']['colors'];
same('#123456',$public['--h5p-theme-background'],'Custom override');
same($data['themes']['builtin:mint']['colors']['--h5p-theme-main-cta-base'],$public['--h5p-theme-main-cta-base'],'Custom inheritance');
same(array('--h5p-theme-background'=>'#abc'),$data['themes']['custom:legacy']['colors'],'Legacy remains sparse over Core');
check(false!==strpos($html,'role="status" aria-live="polite" aria-atomic="true"'),'Accessible status');
check(false!==strpos($html,'sandbox="allow-same-origin"')&&false===strpos($html,'allow-scripts'),'Scriptless exact sandbox');
check(false!==strpos(html_entity_decode($doc,ENT_QUOTES,'UTF-8'),"default-src 'none'; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'"),'Original CSP');
foreach(array('<script','<button','<input','<form','tabindex=') as $needle){check(false===strpos($doc,$needle),'Compact preview excludes runtime/interactive element: '.$needle);}
foreach(array('h5p-small','h5p-medium','h5p-large') as $class){check(!preg_match('/class="[^"]*\b'.$class.'\b/',$doc),'No Density class on preview elements: '.$class);}
same('Public <script>alert(1)</script>',$data['themes']['custom:public_color']['label'],'Label survives JSON as text');
check(false===strpos($html,'<script>alert(1)</script>'),'Label escaped in outer HTML');
same(1,substr_count($html,'<input '),'Only original Profile nonce input');same(2,substr_count($html,'<select '),'Only Theme and Density selectors');
same($beforeWrites,$writes,'Render performs no writes');same($beforeMeta,$meta,'Render preserves preferences');
$stateBeforeVisibility=$previewState;
foreach(array('lavender','mint','sunset','dark') as $id){$previewState['publication']['themes']['builtin:'.$id]=false;}
$builtins=preview_data(preview_render($controller));
foreach(array('daylight','lavender','mint','sunset','dark') as $id){check(isset($builtins['themes']['builtin:'.$id]),'Profile always allows builtin '.$id);}
$previewState=$previewChanges->publish($previewState,'custom:public_color',false)['data'];
$unpublishedHtml=preview_render($controller);$unpublishedData=preview_data($unpublishedHtml);
check(!isset($unpublishedData['themes']['custom:public_color']),'Unpublished custom excluded from preview payload');
check(false===strpos($unpublishedHtml,'Public &lt;script&gt;')&&false===strpos($unpublishedHtml,'custom:public_color'),'Unpublished custom identity not emitted');
$previewState=$stateBeforeVisibility;

$assetsBeforeReads=array($store->reads,$libraryReads);
$controller->assets('user-edit.php');$controller->assets('plugins.php');same(array(),$previewAssets,'No assets outside own Profile screen');
$controller->assets('profile.php');same(2,count($previewAssets),'Only small Profile CSS and JS');
same(array($store->reads,$libraryReads),$assetsBeforeReads,'Asset enqueue performs no profile/library read');
same(true,$previewAssets[1][1][4],'Script loads in footer');

$deniedCoreReads=$coreReads;$deniedStoreReads=$store->reads;
set_roles(array());same('',preview_render($controller),'Role denial emits no preview or payload');set_roles(array('subscriber'));
$administrator=true;$actor=1;same('',preview_render($controller),'Administrator editing another user gets no preview');
$actor=2;$administrator=false;$edit=false;same('',preview_render($controller),'Missing edit capability denied');$edit=true;
$multisite=true;same('',preview_render($controller),'Multisite denied');$multisite=false;
same($deniedCoreReads,$coreReads,'Denied requests do not read preview assets');same($deniedStoreReads,$store->reads,'Denied requests do not read profile state');

$store->record['values']['theme']='custom:hidden_other_secret';
$keepHtml=preview_render($controller);$keep=preview_data($keepHtml);
check(false!==strpos($keepHtml,'value="keep_saved" selected'),'Unavailable saved selection preserved');
same($keep['themes']['site_default']['colors'],$keep['themes']['keep_saved']['colors'],'keep_saved previews effective default');
check(false===strpos($keepHtml,'hidden_other_secret'),'Saved private identifier never exposed');
$store->record['values']['theme']='site_default';
$_POST=array('pjh5pwp_profile'=>array('theme'=>'custom:public_color','density'=>'small'),'pjh5pwp_profile_nonce'=>'invalid');
$errors=new Profile_Test_Errors();$redisplay=new Profile_Controller($service,$preview);$redisplay->validate($errors,true,(object)array('ID'=>2));
$redisplayed=preview_render($redisplay);check($errors->has_errors(),'Validation still rejects bad nonce');
check(false!==strpos($redisplayed,'value="custom:public_color" selected'),'Validation redisplays unsaved selection');
$redisplayedDoc=preview_document($redisplayed);check(false!==strpos($redisplayedDoc,'--h5p-theme-background:#123456;'),'Initial preview matches redisplayed selection');
check(false!==strpos($redisplayedDoc,'&lt;script&gt;alert(1)&lt;/script&gt;'),'Document label escaped');
$redisplay->save(2);same($beforeWrites,$writes,'Rejected submission cannot save through preview');
$_POST=array();

$snordian=true;$suspended=preview_render($controller);
check(false!==strpos($suspended,'SNORDIAN controls activity appearance while active.'),'SNORDIAN explanatory note');
same(1,substr_count($suspended,'<iframe '),'Isolated sample remains available with SNORDIAN');$snordian=false;
$savedState=$previewState;$previewState['settings']['default_theme']=array('kind'=>'custom','id'=>'gone');
$fallback=preview_data(preview_render($controller));same(array(),$fallback['themes']['site_default']['colors'],'Unavailable library/global safely falls back to Core');$previewState=$savedState;
foreach(array('original','small','medium','large') as $density){
    $store->record['values']['density']=$density;
    same($data,preview_data(preview_render($controller)),'Density does not affect preview payload: '.$density);
}
same($beforeWrites,$writes,'All preview-only operations remain read-only');same($beforeMeta,$meta,'All preview-only operations preserve stored preferences');

$colors=Theme_Preview::colors(array('--h5p-theme-background'=>'#abc','--evil'=>'#fff','--h5p-theme-focus'=>'red;}</style><script>bad()</script>','--h5p-theme-main-cta-base'=>'url(https://invalid)','--h5p-theme-contrast-cta-light'=>'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)'));
same(array('--h5p-theme-background'=>'#abc','--h5p-theme-contrast-cta-light'=>'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)'),$colors,'CSS allowlist accepts only safe values and approved expressions');
same('',$renderer->render(null,array()),'Administration preview remains administrator-only');
$administrator=true;$actor=1;$admin=$renderer->render(null,array());
check(false!==strpos(preview_document($admin),'<button type="button"'),'Existing full administration samples retained');
check(false===strpos(preview_document($admin),'pjh5pwp-preview-document pjh5pwp-preview-compact'),'Administration does not use compact layout');
$actor=2;$administrator=false;
$bad=new Profile_Preview(new Theme_Preview(static function(){return '</STYLE><script>bad()</script>';}));
$unavailable=preview_render(new Profile_Controller($service,$bad));
check(false!==strpos($unavailable,'Theme preview unavailable.')&&false===strpos($unavailable,'<iframe'),'Bad/missing CSS fails gracefully');
check(false!==strpos($unavailable,'id="pjh5pwp-theme"')&&false!==strpos($unavailable,'id="pjh5pwp-density"'),'Asset failure preserves selectors');

if('--render'===($argv[1]??null)){
    $page='<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><style>body{margin:8px}.form-table{width:100%;table-layout:fixed}.form-table th,.form-table td{display:block}select{max-width:100%}</style><link rel="stylesheet" href="/assets/profile-preview.css"><script defer src="/assets/profile-preview.js"></script></head><body><form id="your-profile">'.$html.'<button type="reset">Reset</button></form></body></html>';
    echo json_encode(array('profile'=>$page));
}else{echo 'Profile preview: '.($checks-$start)." assertions passed; source-only fixtures, no CMS/database.\n";}
