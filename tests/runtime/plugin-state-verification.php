<?php
/** Operator-helper membership comparison; never loaded by the production plugin. */
function pjh5pwp_same_plugin_members( $actual, $expected ) {
 if ( ! is_array( $actual ) || ! is_array( $expected ) || count( $actual ) !== count( $expected ) ) { return false; }
 foreach ( array( $actual, $expected ) as $list ) {
  foreach ( $list as $plugin ) { if ( ! is_string( $plugin ) || $plugin === '' ) { return false; } }
  if ( count( array_unique( $list ) ) !== count( $list ) ) { return false; }
 }
 foreach ( $expected as $plugin ) { if ( ! in_array( $plugin, $actual, true ) ) { return false; } }
 return true;
}
if ( PHP_SAPI === 'cli' && realpath( $_SERVER['SCRIPT_FILENAME'] ) === __FILE__ ) {
 $cases = array(
  array( array(0=>'h5p/h5p.php',2=>'papi/main.php'), array('h5p/h5p.php','papi/main.php'), true ),
  array( array(7=>'papi/main.php',3=>'h5p/h5p.php'), array('h5p/h5p.php','papi/main.php'), true ),
  array( array('h5p/h5p.php','snordian/main.php'), array('h5p/h5p.php','papi/main.php'), false ),
  array( array('h5p/h5p.php'), array('h5p/h5p.php','papi/main.php'), false ),
  array( array('h5p/h5p.php','h5p/h5p.php'), array('h5p/h5p.php','papi/main.php'), false ),
  array( array(0), array('0'), false ), array( null, array(), false ), array(array(),array(),true),
  array( array(''), array(''), false )
 );
 foreach($cases as $case){if(pjh5pwp_same_plugin_members($case[0],$case[1])!==$case[2]){throw new RuntimeException('Membership regression');}}
 $before=array(0=>'a',1=>'snordian',2=>'b');$actual=$before;unset($actual[1]);$oldExpected=array_values(array_diff($before,array('snordian')));
 if($actual===$oldExpected||!pjh5pwp_same_plugin_members($actual,$oldExpected)||in_array('snordian',$actual,true)){throw new RuntimeException('Exact prior failure reproduction');}
 echo "Plugin-state verification: 10 regression cases PASS; sparse keys accepted, membership changes rejected.\n";
}
