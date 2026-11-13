<?php
/** Isolated Profile suspension UX/data-integrity checks; no CMS or database bootstrap. */
if('cli'!==PHP_SAPI){exit;}
ob_start();require __DIR__.'/preview.php';ob_end_clean();

use Papijo\H5PThemerWordPress\Profile\Profile_Controller;
use Papijo\H5PThemerWordPress\Profile\Profile_State;

$start=$checks;
$actor=2;$administrator=false;$edit=true;$multisite=false;set_roles(array('subscriber'));
$store->record=array('schema_version'=>1,'revision'=>9,'values'=>array_replace(Profile_State::defaults(),array('allow_theme'=>'1','allow_density'=>'1','theme'=>'builtin:mint','density'=>'medium')));
$saved=serialize($store->record);$savedWrites=$writes;$savedMeta=$meta;
$status=array('runtime_suspended'=>true,'blockers'=>array('snordian_active'));
$evaluations=0;
$runtime=static function()use(&$status,&$evaluations){++$evaluations;return $status;};
$blocked=new Profile_Controller($service,$preview,$runtime);
$readsBefore=array($store->reads,$libraryReads,$coreReads);
$html=preview_render($blocked);
check(false!==strpos($html,'<h2>Personal H5P preferences</h2>'),'Heading retained');
check(false!==strpos($html,'Personal H5P appearance settings are currently unavailable because another H5P theme plugin is controlling activity appearance. Your previously saved Theme and Density preferences are preserved.'),'Exact explanatory notice');
foreach(array('pjh5pwp-theme','pjh5pwp-density','pjh5pwp_profile_nonce','Preview selected Theme','data-pjh5pwp-profile-preview','<iframe','Private global name','hidden_global_secret') as $absent){check(false===strpos($html,$absent),'Suspended HTML omits: '.$absent);}
same($readsBefore,array($store->reads,$libraryReads,$coreReads),'Suspension notice needs no profile/library/palette read');
same($saved,serialize($store->record),'Rendering preserves complete stored record and revision');

// A normal unrelated WordPress update has no H5P fields/nonce and needs no H5P save.
$_POST=array('first_name'=>'Unrelated Profile change');$errors=new Profile_Test_Errors();
$blocked->validate($errors,true,(object)array('ID'=>2));$blocked->save(2);
check(!$errors->has_errors(),'Unrelated Profile update not blocked');
same($saved,serialize($store->record),'Absent H5P fields never clear/reset preferences');
same($savedWrites,$writes,'No write from unrelated update');

// Old open forms and forged fields cannot rewrite preferences during suspension.
foreach(array(wp_create_nonce($service->nonce_for(2,$store->record)),'invalid') as $nonce){
    $_POST=array('pjh5pwp_profile'=>array('theme'=>'core','density'=>'small'),'pjh5pwp_profile_nonce'=>$nonce);
    $errors=new Profile_Test_Errors();$blocked->validate($errors,true,(object)array('ID'=>2));$blocked->save(2);
    check(!$errors->has_errors(),'Suspended stale H5P payload ignored without blocking unrelated fields');
    same($saved,serialize($store->record),'Suspended submission preserves preferences/revision');
}
same($savedWrites,$writes,'No stale/forged suspension writes');

// Suspension can start after valid validation, before the deferred Profile save.
$status=array('runtime_suspended'=>false,'blockers'=>array());
$_POST=array('pjh5pwp_profile'=>array('theme'=>'core','density'=>'large'),'pjh5pwp_profile_nonce'=>wp_create_nonce($service->nonce_for(2,$store->record)));
$errors=new Profile_Test_Errors();$blocked->validate($errors,true,(object)array('ID'=>2));
check(!$errors->has_errors(),'Unsuspended validation still accepts a permitted request');
$status=array('runtime_suspended'=>true,'blockers'=>array('snordian_active'));
$blocked->save(2);same($saved,serialize($store->record),'Fresh suspension check prevents pending preference write');
$status=array('runtime_suspended'=>false,'blockers'=>array());$blocked->save(2);
same($saved,serialize($store->record),'Skipped pending request is consumed, never replayed later');

// Deactivation followed by a Profile reload is a new WordPress request/controller.
$blocked=new Profile_Controller($service,$preview,$runtime);
$restored=preview_render($blocked);
check(false!==strpos($restored,'value="builtin:mint" selected'),'Original saved Theme reappears unchanged');
check(false!==strpos($restored,'value="medium" selected'),'Original saved Density reappears unchanged');
check(false!==strpos($restored,'Preview selected Theme')&&substr_count($restored,'<iframe ')===1,'Accepted Profile preview returns');
check(false===strpos($restored,'Personal H5P appearance settings are currently unavailable'),'Suspension notice disappears');
same($saved,serialize($store->record),'Reappearance does not rewrite flags/preferences/revision');

// Only a SNORDIAN-caused runtime suspension hides controls, not unrelated blockers.
foreach(array(
    array('runtime_suspended'=>true,'blockers'=>array('h5p_missing')),
    array('runtime_suspended'=>false,'blockers'=>array('snordian_active')),
    array('runtime_suspended'=>false,'blockers'=>array(),'snordian'=>array('active'=>true))
) as $other){
    $status=$other;$normal=preview_render($blocked);
    check(false!==strpos($normal,'id="pjh5pwp-theme"')&&false!==strpos($normal,'id="pjh5pwp-density"'),'Uses shared suspension plus blocker, not an independent active flag');
}
$status=array('runtime_suspended'=>true,'blockers'=>array('h5p_missing','snordian_active'));
check(false===strpos(preview_render($blocked),'id="pjh5pwp-theme"'),'SNORDIAN also hides controls when other blockers coexist');

// Existing role, own-user, capability and multisite rendering guards still run first.
set_roles(array());$policyCalls=$evaluations;
same('',preview_render($blocked),'Denied learner sees no section');same($policyCalls,$evaluations,'Role denial precedes suspension evaluation');
$actor=1;$administrator=true;
$adminNotice=preview_render($blocked,1);check(false!==strpos($adminNotice,'Personal H5P preferences'),'Automatic administrator access sees explanatory section');
check(false===strpos($adminNotice,'id="pjh5pwp-theme"'),'Own administrator controls also suspended');
same('',preview_render($blocked,2),'Administrator editing another user still sees no personal controls');
$actor=2;$administrator=false;set_roles(array('subscriber'));
$edit=false;same('',preview_render($blocked),'Edit capability still required');$edit=true;
$multisite=true;same('',preview_render($blocked),'Multisite still excluded');$multisite=false;
same($savedWrites,$writes,'All suspension transitions are read-only');same($savedMeta,$meta,'No shared Profile/role fixture preference mutation');

// Once available, the unchanged nonce/role/CAS path persists an authorized choice.
$status=array('runtime_suspended'=>false,'blockers'=>array());
$saveStore=new class {
    public $record;public $commits=0;
    public function read($id){return $this->record;}
    public function compare_and_swap($id,$expected,array $values){
        if($expected!==$this->record){return 'conflict';}
        if($values===$this->record['values']){return 'noop';}
        ++$this->commits;$this->record=array('schema_version'=>1,'revision'=>$expected['revision']+1,'values'=>$values);return 'committed';
    }
};
$saveStore->record=unserialize($saved,array('allowed_classes'=>false));
$normalService=new \Papijo\H5PThemerWordPress\Profile\Profile_Service(static function()use(&$previewState){return $previewState;},$saveStore,$roles);
$normalController=new Profile_Controller($normalService,$preview,$runtime);
$_POST=array('pjh5pwp_profile'=>array('theme'=>'core','density'=>'small'),'pjh5pwp_profile_nonce'=>wp_create_nonce($normalService->nonce_for(2,$saveStore->record)));
$errors=new Profile_Test_Errors();$normalController->validate($errors,true,(object)array('ID'=>2));$normalController->save(2);
check(!$errors->has_errors(),'Normal available save remains valid');same(1,$saveStore->commits,'Normal save uses existing CAS');
same('core',$saveStore->record['values']['theme'],'Normal Theme selection persists');same('small',$saveStore->record['values']['density'],'Independent Density selection persists');
same(10,$saveStore->record['revision'],'Existing revision increment preserved');
$_POST['pjh5pwp_profile_nonce']='invalid';$errors=new Profile_Test_Errors();$normalController->validate($errors,true,(object)array('ID'=>2));$normalController->save(2);
check($errors->has_errors(),'Invalid nonce still rejected when controls available');same(1,$saveStore->commits,'Rejected request cannot reach persistence');
$_POST=array();
echo 'Profile suspension: '.($checks-$start)." assertions passed; isolated policy/role/Profile fixtures, no live database.\n";
