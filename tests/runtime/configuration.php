<?php
/** Configuration/admission matrix in fresh processes; no CMS/database/bootstrap writes. */
if ( 'cli' !== PHP_SAPI ) { exit; }
if ( '--worker' !== ( $argv[1] ?? null ) ) {
    $values = array( 'undefined', 'true', 'false', 'one', 'zero', 'string_true', 'string_false', 'empty_string', 'null', 'array', 'float' );
    $signals = array( 'compatible', 'missing', 'wrong-api', 'multisite', 'snordian-active', 'snordian-constant', 'snordian-namespace', 'snordian-priority-zero' );
    $total = 0;
    foreach ( $values as $value ) { foreach ( $signals as $signal ) {
        $p = proc_open( array( PHP_BINARY, '-n', __FILE__, '--worker', $value, $signal ), array( 0=>array('pipe','r'),1=>array('pipe','w'),2=>array('pipe','w') ), $pipes );
        if ( ! is_resource($p) ) { throw new RuntimeException('Cannot start configuration worker'); }
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($p);
        if($exit || $err || !ctype_digit(trim($out))){throw new RuntimeException($value.'/'.$signal.': '.$out.$err);}
        $total+=(int)$out;
    } }
    echo 'Runtime configuration: '.(count($values)*count($signals)).' scenarios; '.$total." assertions passed; isolated processes.\n";exit;
}
$value=$argv[2];$signal=$argv[3];
$constants=array('true'=>true,'false'=>false,'one'=>1,'zero'=>0,'string_true'=>'true','string_false'=>'false','empty_string'=>'','null'=>null,'array'=>array(true),'float'=>1.0);
if('undefined'!==$value){define('PJH5PWP_ENABLE_BROWSER_THEME',$constants[$value]);}
$configured=in_array($value,array('undefined','true'),true);
$argv=array(__FILE__,$signal);ob_start();require dirname(__DIR__).'/worker.php';ob_end_clean();
use Papijo\H5PThemerWordPress\Compatibility\Runtime_Policy;
use Papijo\H5PThemerWordPress\Runtime\Runtime_Admission;
use Papijo\H5PThemerWordPress\Runtime\Playback_Registry;
use Papijo\H5PThemerWordPress\Runtime\Theme_Attachment;
function admin_url($path){return 'https://fixture.test/wp-admin/'.$path;}
function home_url($path){return 'https://fixture.test'.$path;}
function plugins_url($path,$file){return 'https://fixture.test/plugin/'.$path;}
function esc_attr($value){return esc_html($value);}
$count=0;
function config_check($condition,$message){global $count;if(!$condition){throw new RuntimeException($message);}++$count;}
$status=$policy->evaluate();$compatible='compatible'===$signal;
config_check(Runtime_Policy::configured()===$configured,'Exact constant configuration');
config_check(true===$status['runtime_implemented'],'Runtime implemented independently of configuration/admission');
config_check($configured===$status['runtime_enabled'],'Enabled diagnostic represents configuration only');
config_check($compatible===$status['future_runtime_permitted'],'Compatibility diagnostic independent of opt-out');
config_check((!$compatible)===$status['runtime_suspended'],'Compatibility suspension not confused with configuration');
config_check(($compatible?($configured?'available':'disabled'):'suspended')===$status['runtime_state'],'Precise aggregate state');
config_check($configured===isset($GLOBALS['pjh5pwp_hooks']['wp_enqueue_scripts']),'Real Plugin::boot uses default configuration');
$gate=new Runtime_Admission(array($policy,'evaluate'));$context=true;
$registry=new Playback_Registry($gate,static function()use(&$context){return $context;});
$content=array('id'=>10,'embedType'=>'div','library'=>array('id'=>2,'name'=>'H5P.Accordion','majorVersion'=>1,'minorVersion'=>0,'embedTypes'=>'div'));
$html='<div class="h5p-content" data-content-id="10"></div>';
$registry->observe($html,$content);$attachment=new Theme_Attachment($registry);
config_check(($configured&&$compatible)===(null!==$attachment->manifest()),'Default attachment requires configuration AND admission');
$GLOBALS['pjh5pwp_test_assets']=array();$attachment->enqueue();
config_check(($configured&&$compatible?2:0)===count($GLOBALS['pjh5pwp_test_assets']),'CSS/JS only queued for configured eligible pages');
$context=false;$GLOBALS['pjh5pwp_test_assets']=array();$attachment->enqueue();
config_check(null===$attachment->manifest() && !$GLOBALS['pjh5pwp_test_assets'],'Frontend exclusions always enforced');
config_check('suspended'===$gate->evaluate(false)['runtime_state'],'Context rejection reported by context adapter');
if($compatible){
    $context=true;$GLOBALS['pjh5pwp_options']['active_plugins'][]='snordians-h5p-themer/snordians-h5p-themer.php';
    config_check($policy->evaluate()['runtime_suspended'] && null===$attachment->manifest(),'Late SNORDIAN suspends default-on attachment');
    config_check(''===$attachment->tag('<script src="x"></script>',Theme_Attachment::HANDLE),'Late collision suppresses queued bootstrap');
    array_pop($GLOBALS['pjh5pwp_options']['active_plugins']);
    config_check(($configured)===(null!==$attachment->manifest()),'Removing fixture conflict restores configuration-dependent admission');
}
echo $count;
