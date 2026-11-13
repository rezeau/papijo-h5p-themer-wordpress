<?php
/** Native WordPress lifecycle in a private copied core + random disposable DB only.
 * Usage: php activation-wordpress.php <private wordpress-fixture directory> <literal credentials file>
 */
if(PHP_SAPI!=='cli'){exit;}
error_reporting(E_ALL);set_error_handler(static function($n,$m,$f,$l){if(error_reporting()&$n){throw new ErrorException($m,0,$n,$f,$l);}return false;});
function sc_credentials($path){
    $tokens=token_get_all(file_get_contents($path));$out=array();$decode=static function($s){$v=substr($s,1,-1);return $s[0]==="'"?str_replace(array("\\'","\\\\"),array("'","\\"),$v):stripcslashes($v);};
    for($i=0;$i<count($tokens);$i++){if(is_array($tokens[$i])&&$tokens[$i][0]===T_STRING&&strtolower($tokens[$i][1])==='define'){$strings=array();for($j=$i+1;$j<count($tokens)&&$tokens[$j]!==')';$j++){if(is_array($tokens[$j])&&$tokens[$j][0]===T_CONSTANT_ENCAPSED_STRING){$strings[]=$decode($tokens[$j][1]);}}if(count($strings)===2&&in_array($strings[0],array('DB_HOST','DB_USER','DB_PASSWORD'),true)){$out[$strings[0]]=$strings[1];}}}
    if(count($out)!==3){throw new RuntimeException('Literal server credentials required');}return $out;
}
$checks=0;function sc_check($v,$message){global $checks;++$checks;if(!$v){throw new RuntimeException($message);}}
function sc_table($db,$name){return serialize(array($db->get_row('SHOW CREATE TABLE `'.$name.'`','ARRAY_N'),$db->get_results('SELECT user_id,schema_version,revision,HEX(state) AS bytes FROM `'.$name.'` ORDER BY user_id','ARRAY_A')));}
function sc_options($db){return serialize(array($db->get_results("SELECT option_name,option_value,autoload FROM {$db->options} WHERE option_name LIKE 'pjh5pwp\\_%' OR option_name LIKE 'snordiansh5pthemer\\_%' OR option_name IN ('{$db->prefix}user_roles','home','siteurl') ORDER BY option_name",'ARRAY_A'),$db->get_results("SELECT umeta_id,user_id,meta_key,meta_value FROM {$db->usermeta} WHERE meta_key IN ('{$db->prefix}capabilities','{$db->prefix}user_level') ORDER BY umeta_id",'ARRAY_A')));}
function sc_render($admin){ob_start();$admin->render();return ob_get_clean();}
final class SC_Denied extends RuntimeException {public $status;public function __construct($s){$this->status=$s;}}
final class SC_Redirect extends RuntimeException {public $url,$status;public function __construct($u,$s){$this->url=$u;$this->status=$s;}}
final class SC_Fault_Connection {
    private $db,$fault,$stats,$errno=0,$created=false;
    public function __construct($db,$fault,$stats){$this->db=$db;$this->fault=$fault;$this->stats=$stats;}
    public function __call($n,$a){return call_user_func_array(array($this->db,$n),$a);}
    public function __get($n){return $n==='last_error'&&$this->errno?'SECRET server/table/credential detail':$this->db->$n;}
    public function get_results($sql,$format){++$this->stats->reads;$this->errno=0;if($this->fault==='metadata'||($this->fault==='verify_after'&&$this->created)){$this->errno=1142;return null;}return $this->db->get_results($sql,$format);}
    public function query_once($sql){if(strpos($sql,'CREATE TABLE')!==0){return $this->db->query_once($sql);}++$this->stats->creates;if($this->fault==='denied'){$this->errno=1142;return false;}$r=$this->db->query_once($sql);$this->created=true;if($this->fault==='lost_ack'){throw new RuntimeException('SECRET lost CREATE reply');}return $r;}
    public function error_number(){return $this->errno?:$this->db->error_number();}
}
if(!isset($argv[1],$argv[2])){throw new RuntimeException('Provide private copied WordPress fixture and credential-literal path');}
$root=realpath($argv[1]);$workspace=realpath(dirname(__DIR__,2));$private=$workspace.DIRECTORY_SEPARATOR.'.local-acceptance'.DIRECTORY_SEPARATOR;
if(!$root||strpos($root,$private)!==0||basename($root)!=='wordpress-fixture'||!is_file($root.'/wp-load.php')||is_link($root)){throw new RuntimeException('Refusing non-private WordPress root');}
$c=sc_credentials($argv[2]);$database='pjh5pwp_lifecycle_test_'.bin2hex(random_bytes(8));$server=new mysqli($c['DB_HOST'],$c['DB_USER'],$c['DB_PASSWORD']);$created=false;
try{
    if(!$server->query('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')){throw new RuntimeException('Fixture database creation failed');}$created=true;
    $config="<?php\n";foreach(array_merge($c,array('DB_NAME'=>$database,'DB_CHARSET'=>'utf8mb4','DB_COLLATE'=>'','WP_INSTALLING'=>true,'WP_DEBUG'=>false,'WP_DEBUG_DISPLAY'=>false,'WP_DEBUG_LOG'=>false,'WP_HOME'=>'http://fixture.invalid','WP_SITEURL'=>'http://fixture.invalid','WP_DEFAULT_THEME'=>'fixture','DISABLE_WP_CRON'=>true,'AUTOMATIC_UPDATER_DISABLED'=>true,'WP_AUTO_UPDATE_CORE'=>false,'WP_HTTP_BLOCK_EXTERNAL'=>true,'DISALLOW_FILE_MODS'=>true,'WP_ENVIRONMENT_TYPE'=>'local','WP_TEMP_DIR'=>$root.'/tmp','AUTH_KEY'=>str_repeat('fixture-key-',8),'SECURE_AUTH_KEY'=>str_repeat('fixture-secure-',8),'LOGGED_IN_KEY'=>str_repeat('fixture-login-',8),'NONCE_KEY'=>str_repeat('fixture-nonce-',8),'AUTH_SALT'=>'fixture-auth-salt','SECURE_AUTH_SALT'=>'fixture-secure-salt','LOGGED_IN_SALT'=>'fixture-login-salt','NONCE_SALT'=>'fixture-nonce-salt')) as $k=>$v){$config.='define('.var_export($k,true).','.var_export($v,true).");\n";}
    $config.="\$table_prefix='stagec_';\n".'if(!defined(\'ABSPATH\')){define(\'ABSPATH\','.var_export(str_replace('\\','/',$root).'/',true).");}\nrequire_once ABSPATH.'wp-settings.php';\n";
    file_put_contents($root.'/wp-config.php',$config);unset($config,$c);
    foreach(array('/wp-content/themes/fixture','/wp-content/plugins/snordians-h5p-themer','/tmp') as $dir){if(!is_dir($root.$dir)){mkdir($root.$dir,0777,true);}}
    file_put_contents($root.'/wp-content/themes/fixture/style.css',"/* Theme Name: Disposable fixture */\n");file_put_contents($root.'/wp-content/themes/fixture/index.php',"<?php // Disposable fixture only.\n");
    file_put_contents($root.'/wp-content/plugins/snordians-h5p-themer/snordians-h5p-themer.php',"<?php\n/* Plugin Name: Disposable SNORDIAN marker\nVersion: 0.0.12\n*/\nnamespace Snordian\\H5PThemer;\ndefine('SNORDIANSH5PTHEMER_VERSION','0.0.12');\nfunction h5p_themer_alter_scripts(){}\nadd_action('h5p_alter_library_scripts',__NAMESPACE__.'\\\\h5p_themer_alter_scripts',0);\n");
    $_SERVER=array_merge($_SERVER,array('HTTP_HOST'=>'fixture.invalid','SERVER_NAME'=>'fixture.invalid','REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/wp-admin/plugins.php','PHP_SELF'=>'/wp-admin/plugins.php','SCRIPT_FILENAME'=>$root.'/wp-admin/plugins.php','SERVER_PROTOCOL'=>'HTTP/1.1'));$_GET=array();$_POST=array();$_COOKIE=array();
    require $root.'/wp-load.php';
    $options_engine=$argv[3]??'MyISAM';if(!in_array($options_engine,array('MyISAM','InnoDB'),true)){throw new RuntimeException('Unsupported fixture engine');}
    $GLOBALS['wpdb']->query('SET SESSION default_storage_engine='.$options_engine);
    add_filter('pre_wp_mail','__return_true');add_filter('pre_http_request',static function(){return new WP_Error('fixture_external_http_blocked');});
    require_once ABSPATH.'wp-admin/includes/upgrade.php';$installed=wp_install('Disposable Stage C','fixture_admin','admin@fixture.invalid',false,'','Fixture-only-password-934!','en_US');wp_installing(false);
    $adminId=(int)$installed['user_id'];$subscriber=(int)wp_create_user('fixture_subscriber','Fixture-only-password-935!','subscriber@fixture.invalid');wp_set_current_user($adminId);sc_check(current_user_can('manage_options'),'Native administrator capabilities');
    require_once ABSPATH.'wp-admin/includes/plugin.php';require_once ABSPATH.'wp-admin/includes/class-wp-screen.php';require_once ABSPATH.'wp-admin/includes/screen.php';
    $db=$GLOBALS['wpdb'];sc_check(DB_NAME===$database&&preg_match('/\Apjh5pwp_lifecycle_test_[a-f0-9]{16}\z/',DB_NAME),'Only disposable database selected');
    $plugin='papijo-h5p-themer-wordpress/papijo-h5p-themer-wordpress.php';$table=$db->prefix.'pjh5pwp_profiles';$ddl=array();add_filter('query',static function($sql)use(&$ddl){if(preg_match('/\A\s*(CREATE|ALTER|DROP|RENAME|TRUNCATE)\b/i',$sql)){$ddl[]=$sql;}return $sql;},PHP_INT_MAX);
    sc_check(!is_wp_error(activate_plugin('snordians-h5p-themer/snordians-h5p-themer.php')),'Disposable SNORDIAN marker active');$ddl=array();
    $result=activate_plugin($plugin);sc_check(!is_wp_error($result)&&is_plugin_active($plugin),'Native fresh activation succeeds');
    $state_table=$db->prefix.'pjh5pwp_state';sc_check(count($ddl)===2&&strpos($ddl[0],$table)!==false&&strpos($ddl[1],$state_table)!==false,'Exactly Profile and owned-state CREATE');
    sc_check((int)$db->get_var("SELECT COUNT(*) FROM `$table`")===0&&(int)$db->get_var("SELECT COUNT(*) FROM `$state_table`")===0,'Activation creates empty owned tables without preferences or grants');
    $p=\Papijo\H5PThemerWordPress\Plugin::instance();$p::boot();sc_check($p->compatibility()->evaluate()['runtime_suspended'],'SNORDIAN suspends runtime');
    sc_check($db->get_row("SHOW TABLE STATUS WHERE Name='{$db->options}'",'ARRAY_A')['Engine']===$options_engine,'WordPress options engine unchanged');
    sc_check($p->profile_installation()->status()['state']==='ready'&&$p->state_installation()->status()['state']==='ready','Both owned tables ready');
    sc_check($p->storage_installation()->status()['state']==='absent','Explicit Theme initialization still required');
    foreach(array('denied'=>'failed','metadata'=>'unavailable','lost_ack'=>'unknown','verify_after'=>'unknown') as $fault=>$expected){
        $db->query("DROP TABLE `$state_table`");$stats=(object)array('reads'=>0,'creates'=>0);
        $factory=static function()use($fault,$stats){list($owned,$name)=(new \Papijo\H5PThemerWordPress\Storage\Owned\State_Connections())->open();return array(new SC_Fault_Connection($owned,$fault,$stats),$name);};
        $setup=new \Papijo\H5PThemerWordPress\Storage\Owned\State_Installation($factory);$result=$setup->ensure_installed();
        sc_check($result['state']===$expected,'Native state setup failure classified: '.$fault);sc_check($stats->creates===($fault==='metadata'?0:1),'State CREATE attempted at most once');
        sc_check((new \Papijo\H5PThemerWordPress\Storage\Owned\State_Installation())->ensure_installed()['state']==='ready','Fresh verification or explicit setup recovers without replay');
        sc_check((int)$db->get_var("SELECT COUNT(*) FROM `$state_table`")===0,'Recovery never initializes state');
    }
    $state_admin=null;foreach($GLOBALS['wp_filter']['admin_post_'.\Papijo\H5PThemerWordPress\Storage\Owned\State_Storage_Admin::ACTION]->callbacks as $entries){foreach($entries as $entry){if(is_array($entry['function'])&&$entry['function'][0] instanceof \Papijo\H5PThemerWordPress\Storage\Owned\State_Storage_Admin){$state_admin=$entry['function'][0];}}}sc_check($state_admin!==null,'Production State retry hook registered');
    $db->query("DROP TABLE `$state_table`");set_current_screen('plugins');sc_check(strpos(sc_render($state_admin),'Retry State storage setup')!==false,'Missing owned table has actionable read-only notice');
    add_filter('wp_die_handler',static function(){return static function($m,$t,$a){throw new SC_Denied($a['response']);};});add_filter('wp_redirect',static function($url,$status){throw new SC_Redirect($url,$status);},10,2);
    $state_action=\Papijo\H5PThemerWordPress\Storage\Owned\State_Storage_Admin::ACTION;$state_nonce=\Papijo\H5PThemerWordPress\Storage\Owned\State_Storage_Admin::NONCE;
    foreach(array($subscriber,0) as $actor){wp_set_current_user($actor);$_SERVER['REQUEST_METHOD']='POST';$_POST=array($state_nonce=>wp_create_nonce($state_action));try{$state_admin->retry();throw new RuntimeException('Expected state capability denial');}catch(SC_Denied $e){sc_check($e->status===403&&$db->get_results("SHOW TABLE STATUS WHERE Name='$state_table'",'ARRAY_A')===array(),'Unauthorized state setup cannot create');}}
    wp_set_current_user($adminId);$_POST=array($state_nonce=>'bad');try{$state_admin->retry();throw new RuntimeException('Expected state nonce denial');}catch(SC_Denied $e){sc_check($e->status===403,'Native State setup nonce required');}
    $_POST=array($state_nonce=>wp_create_nonce($state_action));try{do_action('admin_post_'.$state_action);}catch(SC_Redirect $e){sc_check($e->status===303,'Authorized State setup redirects safely');}
    sc_check($p->state_installation()->status()['state']==='ready'&&(int)$db->get_var("SELECT COUNT(*) FROM `$state_table`")===0,'Native explicit retry creates empty state');$_POST=array();

    $ddl=array();$options_before=serialize($db->get_results("SELECT * FROM `{$db->options}` ORDER BY option_id",'ARRAY_A'));
    $service=new \Papijo\H5PThemerWordPress\Admin\Admin_Service($p->theme_store());
    $post=static function($op,$extra=array(),$source='')use($service){$state=$service->status()['data'];$rev=$state===null?'absent':(string)$state['revision'];$ctx=\Papijo\H5PThemerWordPress\Admin\Admin_Service::context($state);return array_merge(array('pjh5pwp_op'=>$op,'source'=>$source,'revision'=>$rev,'pjh5pwp_context'=>$ctx,'pjh5pwp_nonce'=>wp_create_nonce(\Papijo\H5PThemerWordPress\Admin\Admin_Service::nonce_action($op,$source,$rev,$ctx))),$extra);};
    $initial=$post('initialize');sc_check($service->submit($initial)['errors']===array(),'Actual production Theme initialization succeeds');sc_check($service->submit($initial)['errors']===array('storage_conflict'),'Repeated stale initialization cannot overwrite');
    $colors=array_fill_keys(\Papijo\H5PThemerWordPress\Theme\Color_Properties::supported(),'');$fields=array('id'=>'f2_example','label'=>'F2 example','description'=>'Native production fixture','colors'=>$colors);
    sc_check($service->submit($post('create',$fields))['errors']===array(),'Production Theme create');
    $stale=$post('default',array('default_theme'=>'builtin:dark'));$fields['label']='F2 edited';sc_check($service->submit($post('edit',$fields,'custom:f2_example'))['errors']===array(),'Production Theme edit');
    sc_check($service->submit($stale)['errors']===array('storage_conflict'),'Stale administrative form rejected');
    sc_check($service->submit($post('visibility',array('visible'=>array('custom:f2_example'=>'1'))))['errors']===array(),'Production custom publication');
    sc_check($service->submit($post('default',array('default_theme'=>'custom:f2_example')))['errors']===array(),'Production global custom default');
    $roles=new \Papijo\H5PThemerWordPress\Role\Role_Service();sc_check(!$roles->allowed($subscriber)&&$roles->allowed($adminId),'Default denied learner, automatic administrator');
    $rolepost=static function($grant)use($roles){$registered=$roles->registered();$values=array();foreach($registered as $slug=>$role){if(!$role['automatic']){$values[$slug]=$slug==='subscriber'&&$grant?'1':'0';}}return array('pjh5pwp_op'=>'roles','role_complete'=>'1','roles'=>$values,'role_nonce'=>wp_create_nonce($roles->nonce_action($roles->snapshot(),$registered)));};
    $grant=$rolepost(true);sc_check($roles->submit($grant)==='committed'&&$roles->allowed($subscriber),'Default production permission store grants subscriber');sc_check($roles->submit($grant)==='role_conflict_or_invalid_nonce','Stale permission form rejected');
    wp_set_current_user($subscriber);$preferences=new \Papijo\H5PThemerWordPress\Profile\Profile_Service(array($p->theme_store(),'read'));
    $nonce=wp_create_nonce($preferences->nonce_action($subscriber));sc_check($preferences->save_request($subscriber,array('theme'=>'custom:f2_example','density'=>'large'),$nonce)==='committed','Native Profile Theme/Density save uses owned permission lock');
    $profile_saved=$preferences->snapshot($subscriber);sc_check($profile_saved['values']['theme']==='custom:f2_example'&&$profile_saved['values']['density']==='large','Whole personal preference retained');
    wp_set_current_user($adminId);sc_check($roles->submit($rolepost(false))==='committed'&&!$roles->allowed($subscriber),'Production role revocation');
    wp_set_current_user($subscriber);sc_check($preferences->save_request($subscriber,array('density'=>'small'),wp_create_nonce($preferences->nonce_action($subscriber)))==='forbidden_preference'&&$preferences->snapshot($subscriber)===$profile_saved,'Revoked Profile save denied and preferences preserved');wp_set_current_user($adminId);
    sc_check($service->submit($post('default',array('default_theme'=>'builtin:mint')))['errors']===array(),'Change global default');sc_check($service->submit($post('delete',array('confirm'=>'1'),'custom:f2_example'))['errors']===array(),'Production Theme deletion');
    sc_check($options_before===serialize($db->get_results("SELECT * FROM `{$db->options}` ORDER BY option_id",'ARRAY_A'))&&$ddl===array(),'Theme/roles/Profile operations never write or alter options');
    $owned_before=serialize($db->get_results("SELECT * FROM `$state_table` ORDER BY record_id",'ARRAY_A'));$p::activate();sc_check($ddl===array()&&$owned_before===serialize($db->get_results("SELECT * FROM `$state_table` ORDER BY record_id",'ARRAY_A')),'Repeat activation verifies exact owned state without initialization');
    foreach(array('engine','schema','version') as $fault){
        if($fault==='engine'){$db->query("ALTER TABLE `$state_table` ENGINE=MyISAM");}if($fault==='schema'){$db->query("ALTER TABLE `$state_table` ADD foreign_column INT");}if($fault==='version'){$db->query("UPDATE `$state_table` SET schema_version=99 WHERE state_key='theme_library'");}
        $before=serialize(array($db->get_row("SHOW CREATE TABLE `$state_table`",'ARRAY_N'),$db->get_results("SELECT * FROM `$state_table` ORDER BY record_id",'ARRAY_A')));$ddl=array();
        sc_check($p->state_installation()->ensure_installed()['state']==='incompatible','Native incompatible owned state refused: '.$fault);
        sc_check($ddl===array()&&$before===serialize(array($db->get_row("SHOW CREATE TABLE `$state_table`",'ARRAY_N'),$db->get_results("SELECT * FROM `$state_table` ORDER BY record_id",'ARRAY_A'))),'Incompatible owned table never repaired');
        if($fault==='engine'){$db->query("ALTER TABLE `$state_table` ENGINE=InnoDB");}if($fault==='schema'){$db->query("ALTER TABLE `$state_table` DROP foreign_column");}if($fault==='version'){$db->query("UPDATE `$state_table` SET schema_version=1 WHERE state_key='theme_library'");}
    }$ddl=array();
    $diagnostics=new \Papijo\H5PThemerWordPress\Admin\Diagnostic_Notices($p->compatibility());set_current_screen('h5p-content_page_pjh5pwp-themes');sc_check(strpos(sc_render($diagnostics),'SNORDIAN')!==false,'Native coexistence notice preserved');
    $state=\Papijo\H5PThemerWordPress\Storage\Schema::initial();$state['revision']=32;$state['settings']['default_theme']=array('kind'=>'builtin','id'=>'mint','version'=>\Papijo\H5PThemerWordPress\Theme\Preset_Catalog::VERSION);
    update_option('pjh5pwp_theme_library',$state,false);update_option('pjh5pwp_role_permissions',array('schema_version'=>1,'revision'=>14,'roles'=>array('subscriber'=>true)),false);update_option('snordiansh5pthemer_option',array('private'=>'preserve fixture settings'),false);
    $legacy_before=sc_options($db);$owned_before=serialize($db->get_results("SELECT * FROM `$state_table` ORDER BY record_id",'ARRAY_A'));
    sc_check($p->state_installation()->status()['state']==='legacy_unsupported'&&$p->state_installation()->ensure_installed()['state']==='legacy_unsupported','Existing legacy options refuse setup despite an existing owned table');
    sc_check($p->storage_installation()->status()['errors']===array('owned_state_legacy_unsupported')&&!$roles->allowed($adminId),'Legacy refuses Theme administration and automatic Profile authorization');
    sc_check($legacy_before===sc_options($db)&&$owned_before===serialize($db->get_results("SELECT * FROM `$state_table` ORDER BY record_id",'ARRAY_A')),'Legacy refusal preserves both source and destination byte-for-byte');
    $record=array('schema_version'=>1,'revision'=>83,'values'=>array_replace(\Papijo\H5PThemerWordPress\Profile\Profile_State::defaults(),array('theme'=>'builtin:mint','density'=>'large')));$bytes=bin2hex(serialize($record));$db->query("INSERT INTO `$table` VALUES($adminId,1,83,UNHEX('$bytes'))");$saved=sc_table($db,$table);$protected=sc_options($db);
    $ddl=array();deactivate_plugins($plugin);sc_check(!is_plugin_active($plugin)&&$saved===sc_table($db,$table)&&$ddl===array(),'Native deactivation preserves table/rows');sc_check(!is_wp_error(activate_plugin($plugin))&&is_plugin_active($plugin),'Native reactivation succeeds');sc_check($saved===sc_table($db,$table)&&$ddl===array(),'Reactivation is compatible validation only');$ddl=array();$p::activate();sc_check($saved===sc_table($db,$table)&&$ddl===array(),'Repeated callback has no DDL or preference writes');
    $property=new ReflectionProperty($p,'profile_installation');$property->setAccessible(true);
    foreach(array('engine','schema','version') as $fault){
        deactivate_plugins($plugin);if($fault==='engine'){$db->query("ALTER TABLE `$table` ENGINE=MyISAM");}if($fault==='schema'){$db->query("ALTER TABLE `$table` ADD foreign_column INT");}if($fault==='version'){$db->query("INSERT INTO `$table` VALUES(999,99,41,UNHEX('667574757265'))");}
        $before=sc_table($db,$table);$ddl=array();$property->setValue($p,null);sc_check(!is_wp_error(activate_plugin($plugin))&&is_plugin_active($plugin),'Incompatible activation stays active: '.$fault);sc_check(sc_table($db,$table)===$before&&$ddl===array(),'Incompatible storage preserved: '.$fault);
        if($fault==='engine'){$db->query("ALTER TABLE `$table` ENGINE=InnoDB");}if($fault==='schema'){$db->query("ALTER TABLE `$table` DROP foreign_column");}if($fault==='version'){$db->query("DELETE FROM `$table` WHERE user_id=999");}
    }
    sc_check($saved===sc_table($db,$table)&&$protected===sc_options($db),'Saved state/default/publication/roles/SNORDIAN preserved');
    foreach(array('denied','metadata','lost_ack','verify_after') as $fault){
        deactivate_plugins($plugin);$db->query("DROP TABLE `$table`");$stats=(object)array('reads'=>0,'creates'=>0);
        $factory=static function()use($table,$fault,$stats,$db){$owned=new \Papijo\H5PThemerWordPress\Storage\Owned_WPDB(DB_USER,DB_PASSWORD,DB_NAME,DB_HOST);$owned->suppress_errors(true);return array(new SC_Fault_Connection($owned,$fault,$stats),$table,$db->usermeta);};
        $service=new \Papijo\H5PThemerWordPress\Profile\Profile_Installation(new \Papijo\H5PThemerWordPress\Profile\Profile_Store($factory));$property->setValue($p,$service);$ddl=array();sc_check(!is_wp_error(activate_plugin($plugin))&&is_plugin_active($plugin),'Native activation remains active after failure: '.$fault);
        sc_check($fault==='metadata'?$stats->reads>0&&$stats->creates===0:$stats->creates===1,'Failure path actually exercised: '.$fault);
        if(in_array($fault,array('denied','metadata'),true)){sc_check($db->get_results("SHOW TABLE STATUS WHERE Name='$table'",'ARRAY_A')===array()&&$ddl===array(),'Denied/metadata unavailable never creates');$db->query("CREATE TABLE `$table` (user_id BIGINT UNSIGNED NOT NULL,schema_version INT UNSIGNED NOT NULL,revision BIGINT UNSIGNED NOT NULL,state LONGBLOB NOT NULL,PRIMARY KEY(user_id)) ENGINE=InnoDB");}else{sc_check((int)$db->get_var("SELECT COUNT(*) FROM `$table`")===0&&count($ddl)===1,'Uncertain outcome creates at most one empty table');}
    }
    $property->setValue($p,null);deactivate_plugins($plugin);$db->query("DROP TABLE `$table`");$ddl=array();sc_check(!is_wp_error(activate_plugin($plugin,'',false,true)),'Silent update activation supported');sc_check($db->get_results("SHOW TABLE STATUS WHERE Name='$table'",'ARRAY_A')===array()&&$ddl===array(),'Update bypassing activation does not install');
    $admin=null;foreach($GLOBALS['wp_filter']['admin_post_'.\Papijo\H5PThemerWordPress\Profile\Profile_Storage_Admin::ACTION]->callbacks as $entries){foreach($entries as $entry){if(is_array($entry['function'])&&$entry['function'][0] instanceof \Papijo\H5PThemerWordPress\Profile\Profile_Storage_Admin){$admin=$entry['function'][0];}}}sc_check($admin!==null,'Actual retry hook registered');
    set_current_screen('plugins');$_GET=array();$ddl=array();$html=sc_render($admin);sc_check(strpos($html,'Retry Profile storage setup')!==false&&$ddl===array(),'Update gets read-only administrator diagnostic');sc_check(strpos($html,'SECRET')===false,'No database/exception detail disclosed');
    wp_set_current_user($subscriber);sc_check(sc_render($admin)===''&&$ddl===array(),'Subscriber cannot see diagnostics');
    add_filter('wp_die_handler',static function(){return static function($m,$t,$a){throw new SC_Denied($a['response']);};});add_filter('wp_redirect',static function($url,$status){throw new SC_Redirect($url,$status);},10,2);
    $nonceKey=\Papijo\H5PThemerWordPress\Profile\Profile_Storage_Admin::NONCE;$action=\Papijo\H5PThemerWordPress\Profile\Profile_Storage_Admin::ACTION;
    foreach(array($subscriber,0) as $actor){wp_set_current_user($actor);$_SERVER['REQUEST_METHOD']='POST';$_POST=array($nonceKey=>wp_create_nonce($action));try{$admin->retry();throw new RuntimeException('Expected unauthorized retry');}catch(SC_Denied $error){sc_check($error->status===403&&$ddl===array(),'Unauthorized retry executes no DDL');}}
    wp_set_current_user($adminId);$_POST=array($nonceKey=>'bad');try{$admin->retry();throw new RuntimeException('Expected nonce rejection');}catch(SC_Denied $error){sc_check($error->status===403&&$ddl===array(),'Native invalid nonce rejected without DDL');}
    $_POST=array($nonceKey=>wp_create_nonce($action),'pjh5pwp_profile_setup_return'=>'themes');try{do_action('admin_post_'.$action);}catch(SC_Redirect $redirect){sc_check($redirect->status===303&&strpos($redirect->url,'page=pjh5pwp-themes')!==false,'Native authorized retry redirects to Themes');}
    sc_check(count($ddl)===1&&(int)$db->get_var("SELECT COUNT(*) FROM `$table`")===0,'Native nonce-protected retry creates exactly one empty table');$_GET=array();$ddl=array();sc_check(sc_render($admin)===''&&$ddl===array(),'Ready ordinary page performs no DDL');
    $db->query("INSERT INTO `$table` VALUES($adminId,1,83,UNHEX('$bytes'))");$store=new \Papijo\H5PThemerWordPress\Profile\Profile_Store();sc_check($store->read($adminId)===$record,'Original binary Profile state still supported');$next=$record['values'];$next['density']='medium';sc_check($store->compare_and_swap($adminId,$record,$next)==='committed'&&$store->compare_and_swap($adminId,$record,$next)==='conflict','Existing revision/CAS semantics preserved');
    sc_check($protected===sc_options($db),'Protected options/roles untouched through activation and retries');sc_check(is_plugin_active('snordians-h5p-themer/snordians-h5p-themer.php')&&$p->compatibility()->evaluate()['runtime_suspended'],'SNORDIAN fixture remains active and suspends runtime');
    echo 'PASS F2 production native WordPress '.$GLOBALS['wp_version'].', MySQL '.$server->server_info.', PHP '.PHP_VERSION.', options '.$options_engine.': '.$checks." assertions; disposable installation/activation/deactivation/silent-update/native-nonce fixtures only.\n";
}finally{
    if(isset($GLOBALS['wpdb'])){$GLOBALS['wpdb']->close();}
    if(is_file($root.'/wp-config.php')){unlink($root.'/wp-config.php');}
    if($created){if(!preg_match('/\Apjh5pwp_lifecycle_test_[a-f0-9]{16}\z/',$database)){throw new RuntimeException('Unsafe fixture cleanup');}if(!$server->query('DROP DATABASE `'.$database.'`')){throw new RuntimeException('Fixture cleanup failed');}$left=$server->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='".$server->real_escape_string($database)."'")->fetch_row()[0];if($left!=='0'){throw new RuntimeException('Fixture database remains');}echo "Disposable WordPress database removed and absence verified; private config removed.\n";}$server->close();
}
