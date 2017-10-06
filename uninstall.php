<?php 

/**
 * The hashtagger Plugin Uninstall
 */
  
  
// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
  die;
}


// If this is somehow accessed withou plugin uninstall is requested, abort
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! WP_UNINSTALL_PLUGIN ||	dirname( WP_UNINSTALL_PLUGIN ) != dirname( plugin_basename( __FILE__ ) ) ) {
  die;
}


// Load plugin file and start uninstall
include_once plugin_dir_path( __FILE__ ) . 'hashtagger.php';
$pp_hashtagger->uninstall();

?>