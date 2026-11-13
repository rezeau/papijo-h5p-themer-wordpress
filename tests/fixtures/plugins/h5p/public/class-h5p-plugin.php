<?php
/** Test-only capability shapes. Every service call fails to prove non-invocation. */
class PJH5PWP_Test_H5P_Contract {
	public static function get_instance() { throw new RuntimeException( 'H5P must not be initialized' ); }
	public function get_h5p_instance( $type ) { throw new RuntimeException( 'No H5P services' ); }
	public function get_content( $id ) { throw new RuntimeException( 'No content reads' ); }
	public function add_assets( $content, $no_cache = false ) { throw new RuntimeException( 'No assets' ); }
}

if ( 'missing-method' === $GLOBALS['pjh5pwp_scenario'] ) {
	class H5P_Plugin extends PJH5PWP_Test_H5P_Contract {
		public function alter_assets( &$files, &$dependencies, $embed ) {
			do_action_ref_array( 'h5p_alter_library_scripts', array( &$files['scripts'], array(), $embed ) );
			do_action_ref_array( 'h5p_alter_library_styles', array( &$files['styles'], array(), $embed ) );
		}
	}
} elseif ( 'missing-hooks' === $GLOBALS['pjh5pwp_scenario'] ) {
	class H5P_Plugin extends PJH5PWP_Test_H5P_Contract {
		public function get_core_settings() { throw new RuntimeException( 'No settings construction' ); }
		public function alter_assets( &$files, &$dependencies, $embed ) {
			// Decoys must not count: do_action_ref_array('h5p_alter_library_scripts', array());
			$decoy = "do_action_ref_array('h5p_alter_library_styles', array())";
		}
	}
} elseif ( 'wrong-reference' === $GLOBALS['pjh5pwp_scenario'] ) {
	class H5P_Plugin extends PJH5PWP_Test_H5P_Contract {
		public function get_core_settings() { throw new RuntimeException( 'No settings construction' ); }
		public function alter_assets( $files, &$dependencies, $embed ) {
			do_action_ref_array( 'h5p_alter_library_scripts', array( &$files['scripts'], array(), $embed ) );
			do_action_ref_array( 'h5p_alter_library_styles', array( &$files['styles'], array(), $embed ) );
		}
	}
} else {
	class H5P_Plugin extends PJH5PWP_Test_H5P_Contract {
		public function get_core_settings() { throw new RuntimeException( 'No settings construction' ); }
		public function alter_assets( &$files, &$dependencies, $embed ) {
			do_action_ref_array( 'h5p_alter_library_scripts', array( &$files['scripts'], array(), $embed ) );
			do_action_ref_array( 'h5p_alter_library_styles', array( &$files['styles'], array(), $embed ) );
		}
	}
}
