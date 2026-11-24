<?php
/** Administrator page routing with isolated CMS doubles; no database. */
require dirname(__DIR__,2).'/includes/theme-domain.php';
require dirname(__DIR__,2).'/includes/wordpress-storage.php';
require dirname(__DIR__,2).'/includes/administration.php';
require __DIR__.'/wordpress-doubles.php';
$checks=0;
function verify($condition,$message){global $checks;$checks++;if(!$condition){throw new RuntimeException($message);}}
$controller=new \Papijo\H5PThemerWordPress\Admin\Admin_Controller(static function(){throw new RuntimeException('Must not enter Theme writes');});
$_SERVER['REQUEST_METHOD']='POST';
$_POST=array('pjh5pwp_op'=>'anonymous_permission','allow_anonymous'=>'1','anonymous_complete'=>'1','anonymous_nonce'=>'bad');
$controller->load();verify(true,'New operation handled without Theme service');
$_POST['anonymous_nonce']=wp_create_nonce('pjh5pwp_anonymous_permission');
$controller->load();verify(true,'Option failure contained without Theme writes');
$GLOBALS['pjh5pwp_test_cap']=false;
try{$controller->load();throw new RuntimeException('Missing denial');}catch(RuntimeException $e){verify($e->getMessage()==='wp_die:403','Unauthorized page denied');}
$p=new \Papijo\H5PThemerWordPress\Anonymous\Permission();
verify($p->render('https://example.test/')==='','Unauthorized setting hidden');
$GLOBALS['pjh5pwp_test_cap']=true;
$html=$p->render('https://example.test/?x="');
verify(strpos($html,'Allow anonymous visitors to select their H5P appearance')!==false,'Approved label');
verify(strpos($html,' checked')===false,'Unavailable/absent option defaults off');
verify(strpos($html,'anonymous_nonce')!==false && strpos($html,'anonymous_complete')!==false,'Protected complete form');
verify(strpos($html,'&quot;')!==false,'URL escaped');
echo "Phase 2A administrator routing: $checks assertions passed; no database.\n";
