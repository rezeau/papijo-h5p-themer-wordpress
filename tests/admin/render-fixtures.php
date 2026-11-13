<?php
/** CLI-only static pages for optional disposable-browser tests. Never bootstraps a CMS. */
if('cli'!==PHP_SAPI){exit;}
require dirname(__DIR__,2) . '/includes/theme-domain.php';
require dirname(__DIR__,2) . '/includes/wordpress-storage.php';
require dirname(__DIR__,2) . '/includes/administration.php';
require __DIR__ . '/wordpress-doubles.php';
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Admin\Theme_Preview;
use Papijo\H5PThemerWordPress\Admin\Admin_View;
use Papijo\H5PThemerWordPress\Admin\Admin_Controller;
$core=isset($argv[1])?Theme_Preview::read_css($argv[1]):null;
if(null===$core){fwrite(STDERR,"Supply the fixed official H5P variables CSS path.\n");exit(1);}
define('PJH5PWP_FILE',dirname(__DIR__,2).'/papijo-h5p-themer-wordpress.php');define('PJH5PWP_VERSION','0.1.1-beta.1');
$state=Schema::initial();
$ref=array('kind'=>'builtin','id'=>'mint','version'=>Preset_Catalog::VERSION);
$def=(new Theme_Operations())->duplicate($ref,'example','Mint example')['data'];
$state=(new Library_Changes())->create($state,$def)['data'];
$state=(new Library_Changes())->set_default($state,array('kind'=>'custom','id'=>'example'))['data'];
$controller=new Admin_Controller(static function(){throw new RuntimeException('Fixture never calls storage');});
$GLOBALS['menu']=array(array('H5P','view_h5p_contents','h5p'));$controller->menu();$controller->assets('h5p_page_pjh5pwp-themes');
$view=new Admin_View('/fixture/overview',new Theme_Preview(static function()use($core){return $core;}));
$status=array('state'=>'ready','data'=>$state,'errors'=>array());$pages=array();
foreach(array('overview'=>array('overview',''),'edit'=>array('edit','custom:example'),'builtin'=>array('view','builtin:dark'),'add'=>array('create',''),'duplicate'=>array('duplicate','builtin:mint'),'delete'=>array('delete','custom:example')) as $name=>$route){
	$pages[$name]='<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Isolated administration fixture</title><link rel="stylesheet" href="/assets/admin.css"><style>body{font-family:system-ui,sans-serif;background:#f0f0f1;padding:24px}button,input,select,textarea{font:inherit}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:.5rem}summary{margin-bottom:.5rem}.h5p-content{--h5p-theme-background:#ff00ff !important}</style></head><body>' . $view->render($status,$route[0],$route[1]) . '<script>' . $GLOBALS['pjh5pwp_test_inline'][1] . '</script><script src="/assets/admin.js"></script></body></html>';
}
echo json_encode($pages);
