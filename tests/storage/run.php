<?php
/** Isolated persistence tests. No WordPress bootstrap or database connection. */
if ( 'cli' !== PHP_SAPI ) { exit; }
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) { throw new ErrorException( $message, 0, $severity, $file, $line ); } );
require dirname( __DIR__, 2 ) . '/includes/theme-domain.php';
require dirname( __DIR__, 2 ) . '/includes/wordpress-storage.php';
require __DIR__ . '/fake-database.php';
require __DIR__ . '/legacy-step.php';

use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Storage\Snapshot_Codec;
use Papijo\H5PThemerWordPress\Storage\WordPress_Snapshot_Store;
use Papijo\H5PThemerWordPress\Storage\Storage_Installation;
use Papijo\H5PThemerWordPress\Storage\Storage_Exception;
use Papijo\H5PThemerWordPress\Storage\Schema_Migrations;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Library_Changes;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Tests\Fake_Database;
use Papijo\H5PThemerWordPress\Tests\Legacy_Step;

$checks = 0;
function check( $condition, $message ) { global $checks; if ( ! $condition ) { throw new RuntimeException( $message ); } ++$checks; }
function same( $a, $b, $message ) { check( $a === $b, $message ); }
function throws_code( callable $action, $code, $outcome = 'not_committed' ) {
	try { $action(); } catch ( Storage_Exception $error ) { same( $code, $error->getMessage(), 'Diagnostic code' ); same( $outcome, $error->outcome(), 'Write outcome' ); return; }
	throw new RuntimeException( 'Expected storage exception: ' . $code );
}
function make_store( Fake_Database $db, $invalidate = null, $migrations = null ) {
	return new WordPress_Snapshot_Store( array( $db, 'open' ), $invalidate ?: static function () {}, $migrations );
}
function plan_data( $plan ) { check( ! $plan['errors'], 'Valid domain plan' ); return $plan['data']; }
function ref( $id ) { return array( 'kind' => 'builtin', 'id' => $id, 'version' => Preset_Catalog::VERSION ); }
$db = new Fake_Database(); $store = make_store( $db ); $installation = new Storage_Installation( $store );
same( 0, $db->connections, 'Constructor is inert' );
same( 'absent', $installation->status()['state'], 'Read-only absence status' ); same( array(), $db->rows, 'Status never initializes' );
same( 'initialized', $installation->initialize()['state'], 'Explicit first initialization' );
$initial_bytes = $db->rows[Schema::OPTION]['option_value'];
same( 'ready', $installation->initialize()['state'], 'Repeated initialization preserves existing state' );
same( $initial_bytes, $db->rows[Schema::OPTION]['option_value'], 'No overwrite on repeated install' );
same( 'off', $db->rows[Schema::OPTION]['autoload'], 'No autoload of theme library' );
same( false, $store->compare_and_swap( null, Schema::initial() ), 'Insert cannot overwrite existing option' );
$db->rows['unrelated_option'] = array( 'option_id' => '900', 'option_value' => 'unrelated', 'autoload' => 'on' );
$db->rows['snordiansh5pthemer_option'] = array( 'option_id' => '901', 'option_value' => 'foreign fixture', 'autoload' => 'on' );
$unrelated = $db->rows;
$changes = new Library_Changes();
$definition = plan_data( ( new Theme_Operations() )->duplicate( ref('mint'), 'custom_example', 'Été 🎨' ) );
$definition['colors']['--h5p-theme-background'] = '#AbC';
$state = plan_data( $changes->create( $store->read(), $definition ) );
$forged=$state;$forged['publication']['themes']['custom:custom_example']=true;
same(false,$store->compare_and_swap(0,$forged),'Production adapter enforces new-custom publication transition');
check( $store->compare_and_swap( 0, $state ), 'Persist full validated change plan' );
same( $state, $store->read(), 'Unicode and sparse overrides round-trip exactly' );
same( false, $store->compare_and_swap( 0, $state ), 'Stale revision rejected' );
same( false, $store->compare_and_swap( '1', $state ), 'Revision type is strict' );
$state = plan_data( $changes->publish( $state, 'custom:custom_example', true ) );
check( $store->compare_and_swap( 1, $state ), 'Publication is atomic with definitions' );
$state = plan_data( $changes->set_default( $state, array('kind'=>'custom','id'=>'custom_example') ) );
check( $store->compare_and_swap( 2, $state ), 'Global default persistence' );
check( ! empty( $changes->delete( $state, array('kind'=>'custom','id'=>'custom_example') )['errors'] ), 'Selected default protected' );
$state = plan_data( $changes->set_default( $state, ref('dark') ) ); check( $store->compare_and_swap( 3, $state ), 'Builtin reference persisted' );
$state = plan_data( $changes->delete( $state, array('kind'=>'custom','id'=>'custom_example') ) ); check( $store->compare_and_swap( 4, $state ), 'Deletion and tombstone committed together' );
same( false, $store->read()['publication']['themes']['custom:custom_example'], 'Deletion tombstone preserved' );
foreach ( array('unrelated_option','snordiansh5pthemer_option') as $key ) { same( $unrelated[$key], $db->rows[$key], 'Unrelated/foreign option preserved' ); }
foreach ( $db->log as $sql ) { check( false === strpos( $sql, 'snordians' ) && false === strpos( $sql, 'unrelated_option' ), 'No foreign option read or write' ); }

// Race two writers after both computed plans from the same revision.
$db = new Fake_Database(); $a = make_store($db); $b = make_store($db); check($a->compare_and_swap(null,Schema::initial()),'Race setup');
$before = $a->read(); $first = plan_data($changes->set_default($before,ref('mint'))); $second = plan_data($changes->set_default($before,ref('dark')));
$db->before_write = static function() use($b,$second){ check($b->compare_and_swap(0,$second),'Competing writer wins'); };
same(false,$a->compare_and_swap(0,$first),'Byte CAS rejects stale writer after fresh read'); same($second,$a->read(),'Winning state preserved');
// Even a rogue same-revision byte change is detected; revision alone is not CAS.
$next = plan_data($changes->set_default($second,ref('mint')));
$db->before_write = static function() use($db,$second){ $altered=$second; $altered['settings']['default_theme']=ref('sunset'); $db->rows[Schema::OPTION]['option_value']=serialize($altered); };
same(false,$a->compare_and_swap(1,$next),'Binary bytes checked, beyond revision');
$current=$a->read(); $next=plan_data($changes->set_default($current,ref('mint')));
$db->before_write=static function() use($db){$db->rows[Schema::OPTION]['option_id']='999';};
same(false,$a->compare_and_swap(1,$next),'Deleted/recreated row identity checked');
$db=new Fake_Database(); $a=make_store($db); $b=make_store($db);
$db->before_write=static function() use($b){check($b->compare_and_swap(null,Schema::initial()),'Competing initializer wins');};
same(false,$a->compare_and_swap(null,Schema::initial()),'Unique insert loses safely without upsert'); same(Schema::initial(),$a->read(),'Initialized state intact');

// Known failure, lost acknowledgement, post-commit cache failure: never rollback blindly.
foreach(array('write','lost_ack','after_commit') as $fault){
	$db=new Fake_Database();$store=make_store($db);check($store->compare_and_swap(null,Schema::initial()),'Fault setup');
	$next=plan_data($changes->set_default($store->read(),ref('mint')));$db->fault=$fault;
	throws_code(static function()use($store,$next){$store->compare_and_swap(0,$next);},'write'===$fault?'storage_write_failed':'storage_write_unknown','write'===$fault?'not_committed':'unknown');
	$db->fault=null;same('write'===$fault?Schema::initial():$next,$store->read(),'Data reflects known/uncertain outcome without rollback');
}
$db=new Fake_Database();$store=make_store($db);$install=new Storage_Installation($store);
$db->rows[Schema::OPTION]=array('option_id'=>'1','option_value'=>'N;','autoload'=>'off');$null_bytes=$db->rows;
same('malformed_storage',$install->initialize()['state'],'Serialized null is not absence');same($null_bytes,$db->rows,'Serialized null never overwritten');
$db=new Fake_Database();$store=make_store($db,static function(){throw new RuntimeException('cache unavailable');});
throws_code(static function()use($store){$store->compare_and_swap(null,Schema::initial());},'storage_cache_failed','committed');
same(Schema::initial(),$store->read(),'Cache error cannot undo committed data');
$db=new Fake_Database();$db->fault='read';throws_code(static function()use($db){make_store($db)->read();},'storage_read_failed');same(array(),$db->rows,'Read failure never repairs data');
// Unsupported engines fail before reading data or attempting any write/repair.
foreach(array('MyISAM','MEMORY','ARCHIVE') as $engine){foreach(array(false,true) as $existing){
	$db=new Fake_Database();$db->engine=$engine;
	$db->rows['snordiansh5pthemer_option']=array('option_id'=>'901','option_value'=>'foreign fixture','autoload'=>'on');
	if($existing){$db->rows[Schema::OPTION]=array('option_id'=>'1','option_value'=>serialize(Schema::initial()),'autoload'=>'off');}
	$before=$db->rows;$invalidations=0;$store=make_store($db,static function()use(&$invalidations){++$invalidations;});$install=new Storage_Installation($store);
	throws_code(static function()use($store){$store->read();},'storage_options_engine_unsupported');
	throws_code(static function()use($store){$store->compare_and_swap(null,Schema::initial());},'storage_options_engine_unsupported');
	$next=plan_data($changes->set_default(Schema::initial(),ref('mint')));
	throws_code(static function()use($store,$next){$store->compare_and_swap(0,$next);},'storage_options_engine_unsupported');
	foreach(array('status','initialize','upgrade') as $operation){$result=$install->$operation();same('storage_options_engine_unsupported',$result['state'],'Specific engine diagnostic');same('not_committed',$result['outcome'],'Unsupported engine never commits');}
	same($before,$db->rows,'Theme and foreign bytes preserved');same($engine,$db->engine,'No automatic conversion');same(0,$invalidations,'No write cache side effects');same($db->connections,$db->closed,'Rejected sessions closed');
	foreach($db->log as $sql){check(0===strpos($sql,'SHOW TABLE STATUS'),'Engine failure performs metadata reads only');}
	$db->engine='InnoDB';same($existing?'ready':'absent',$install->status()['state'],'Read-only recheck after external correction');same($before,$db->rows,'Correction status does not initialize');
	if(!$existing){same('initialized',$install->initialize()['state'],'Corrected environment still requires explicit initialization');}
}}
foreach(array(array(),array(array()),array(array('Engine'=>null)),array(array('Engine'=>'')),array(array('Engine'=>'InnoDB'),array('Engine'=>'MyISAM'))) as $metadata){
	$db=new Fake_Database();$db->status_rows=$metadata;throws_code(static function()use($db){make_store($db)->read();},'storage_environment_unsupported');same(array(),$db->rows,'Unverified metadata never suggests an identified engine failure');same($db->connections,$db->closed,'Metadata rejection closes session');
}
foreach(array('collation'=>'utf8_unicode_ci','unique'=>false,'triggers'=>true,'autoload'=>0) as $property=>$value){
	$db=new Fake_Database();$db->$property=$value;throws_code(static function()use($db){make_store($db)->read();},'storage_environment_unsupported');same(array(),$db->rows,'Unsupported environment untouched');
}
$codec=new Snapshot_Codec();
foreach(array('N;','b:0;','a:broken','a:0:{}junk','O:8:"stdClass":0:{}') as $bytes){throws_code(static function()use($codec,$bytes){$codec->decode($bytes);},'malformed_storage');}
$db=new Fake_Database();$store=make_store($db);$install=new Storage_Installation($store);
foreach(array('malformed'=>array('schema_version'=>1),'newer'=>array('schema_version'=>2,'revision'=>7),'older'=>array('schema_version'=>0,'revision'=>7)) as $name=>$raw){
	$db->rows[Schema::OPTION]=array('option_id'=>'1','option_value'=>serialize($raw),'autoload'=>'off');$before=$db->rows;
	$expected=array('malformed'=>'malformed_storage','newer'=>'newer_storage_schema','older'=>'unsupported_storage_schema');
	same($expected[$name],$install->initialize()['state'],'Unknown/malformed version diagnostic'); $install->upgrade(); same($before,$db->rows,'No destructive automatic recovery');
}

// Explicit synthetic v0->v1 migration tests framework only, not a shipped legacy schema.
$step=new Legacy_Step();$migrations=new Schema_Migrations(array($step));$db=new Fake_Database();$store=make_store($db,null,$migrations);$install=new Storage_Installation($store,$migrations);
$legacy=plan_data($changes->create(Schema::initial(),$definition));$legacy['schema_version']=0;
$db->rows[Schema::OPTION]=array('option_id'=>'1','option_value'=>serialize($legacy),'autoload'=>'off');
same('upgrade_required',$install->status()['state'],'Read-only upgrade diagnostic');same($legacy,$store->read(),'No upgrade on read');
$step->fail=true;same('migration_failed',$install->upgrade()['state'],'Failed migration preserves old bytes');same($legacy,$store->read(),'Migration conversion never writes partially');
$step->fail=false;$db->fault='write';same('storage_write_failed',$install->upgrade()['state'],'Migration write failure is explicit');$db->fault=null;same($legacy,$store->read(),'Migration write failure preserves old snapshot');
$upgraded=$install->upgrade();same('upgraded',$upgraded['state'],'Explicit versioned upgrade');
same($legacy['definitions'],$upgraded['data']['definitions'],'Migration preserves custom definitions');same($legacy['publication'],$upgraded['data']['publication'],'Migration preserves publication');
same($legacy['revision']+1,$upgraded['data']['revision'],'Migration advances revision once');same('ready',$install->upgrade()['state'],'Repeated upgrade idempotent');
$bad=$upgraded['data'];$bad['revision']++;$bad['definitions']['custom_example']['label']='altered';
same(false,$store->compare_and_swap(1,$bad),'Stale migration revision rejected');
same($db->connections,$db->closed,'Owned connections always closed');
echo 'PASS persistence doubles: '.$checks.' assertions; no database connection or site bootstrap.'.PHP_EOL;
