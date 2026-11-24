<?php
ob_start(); require __DIR__.'/anonymous.php'; ob_end_clean();
$state=\Papijo\H5PThemerWordPress\Storage\Schema::initial();
foreach(array('published','hidden') as $id) {
    $d=definition();$d['id']=$id;$d['label']=$id;$state['definitions'][$id]=$d;
    $state['publication']['themes']['custom:'.$id]=$id==='published';
}
$state['settings']['default_theme']=array('kind'=>'custom','id'=>'hidden');
$policy=array('future_runtime_permitted'=>true,'runtime_suspended'=>false,'blockers'=>array());
$enabled=$ready=$runtime=true;$viewer=0;$examples=array();
foreach(array('site_default','core','builtin:lavender','builtin:dark','custom:published','custom:hidden','custom:deleted') as $theme) {
    foreach(array('original','small','medium','large') as $density) {
        $post['theme']=$theme;$post['density']=$density;
        $examples[$theme.'/'.$density]=request_anon()['body'];
    }
}
echo json_encode($examples);
