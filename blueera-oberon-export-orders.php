<?php
/**
 * Plugin Name:       blueera - OBERON Export orders
 * Plugin URI:        https://oberonconnector.sk/
 * Description:       Export orders from Woocommerce to XML file format for OBERON
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            BLUEERA
 * Author URI:        https://blueera.sk/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://oberonconnector.sk/
 * Text Domain:       blueeraoberonexportorders
 * Domain Path:       /languages
 */

if (! defined( 'ABSPATH' ) ){ die('Plugin blueera - OBERON Export orders can not run !!!'); }

define('BE_OEO_FILE', __FILE__);
define('BE_OEO_PATH', plugin_dir_path( __FILE__ ));
define('BE_OEO_URL', plugin_dir_url( __FILE__ ));

include_once(BE_OEO_PATH . 'app/config.php');
include_once(BE_OEO_PATH . 'app/bulk-actions.php');
include_once(BE_OEO_PATH . 'app/xml.php');
if(file_exists(BE_OEO_PATH . 'init.php')) { include_once(BE_OEO_PATH . 'init.php'); }