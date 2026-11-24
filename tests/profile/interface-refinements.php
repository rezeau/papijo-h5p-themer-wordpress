<?php
ob_start();require __DIR__.'/run.php';ob_end_clean();
require dirname(__DIR__,2).'/includes/Admin/class-admin-service.php';
$checks=0;$actor=2;$administrator=false;set_roles(array('subscriber'));
$state=\Papijo\H5PThemerWordPress\Storage\Schema::initial();apply(array('theme'=>'site_default'));
$html=render_profile();check(strpos($html,'value="core"')===false,'Core not offered for ordinary profiles');
apply(array('theme'=>'core'));$before=$service->values(2);$count=$writes;
$html=render_profile();check(strpos($html,'value="core" selected disabled')!==false,'Saved Core displayed as disabled selected option');
same($count,$writes,'Opening Profile does not write');
apply(array('density'=>'large'));same('core',$service->values(2)['theme'],'Density-only save preserves omitted disabled Theme');
apply(array('density'=>'large'));same('core',$service->values(2)['theme'],'Unchanged Profile submission preserves Core');
same('core',$service->effective(2)['theme']['data']['kind'],'Internal resolution remains Core');
apply(array('theme'=>'builtin:mint'));same('builtin:mint',$service->values(2)['theme'],'Explicit replacement accepted');
check(strpos(render_profile(),'value="core"')===false,'Core no longer offered after explicit replacement');
$catalog=\Papijo\H5PThemerWordPress\Admin\Admin_Service::catalog($state);check(isset($catalog['core']),'Administrator Core option retained');
echo "Interface/Profile: $checks focused assertions passed.\n";
