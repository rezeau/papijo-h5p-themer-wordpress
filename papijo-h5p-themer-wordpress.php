<?php
/**
 * Plugin Name: Papi Jo H5P Themer for WordPress
 * Description: Independent H5P Themer foundation with dependency diagnostics and runtime conflict protection.
 * Version: 0.3.0
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Papi Jo
 * Text Domain: papijo-h5p-themer-wordpress
 * Domain Path: /languages
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Papijo\H5PThemerWordPress
 */

namespace Papijo\H5PThemerWordPress;

defined( 'ABSPATH' ) || exit;

define( 'PJH5PWP_VERSION', '0.3.0' );
define( 'PJH5PWP_FILE', __FILE__ );

require_once __DIR__ . '/includes/Compatibility/class-wordpress-environment.php';
require_once __DIR__ . '/includes/Compatibility/class-hook-inspector.php';
require_once __DIR__ . '/includes/Compatibility/class-h5p-dependency.php';
require_once __DIR__ . '/includes/Compatibility/class-snordian-compatibility.php';
require_once __DIR__ . '/includes/Compatibility/class-runtime-policy.php';
require_once __DIR__ . '/includes/Admin/class-diagnostic-notices.php';
require_once __DIR__ . '/includes/class-plugin.php';
require_once __DIR__ . '/includes/theme-domain.php';
require_once __DIR__ . '/includes/wordpress-storage.php';
require_once __DIR__ . '/includes/administration.php';
require_once __DIR__ . '/includes/profiles.php';
require_once __DIR__ . '/includes/runtime-wordpress.php';

// Register activation at file load, rather than from a later WordPress action.
register_activation_hook( PJH5PWP_FILE, array( Plugin::class, 'activate' ) );
add_action( 'plugins_loaded', array( Plugin::class, 'boot' ), 20 );
