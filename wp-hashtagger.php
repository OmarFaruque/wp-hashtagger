<?php
/**
 * The hashtagger Plugin
 *
 * hashtagger allows usage of #hashtags, @usernames and $cashtags in posts
 *
 * @wordpress-plugin
 * Plugin Name: wp-hashtagger
 * Description: Use #hashtags, @usernames and $cashtags in your posts, #tags links
 * Version: 1.0.1
 * Author: larasoft
 * Text Domain: wp-hashtagger
 */

// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}
define('WHDIR', plugin_dir_path( __FILE__ ));
define('WHURL', plugin_dir_url( __FILE__ ));


/**
 * Load core plugin class and run the plugin
 */
require_once( WHDIR . '/inc/class-wp-hashtagger.php' );
require_once( WHDIR . '/inc/hash-wedget.php' );
require_once( WHDIR . '/inc/front/ajax.php' );
$wp_hashtagger = new WP_Hashtagger( __FILE__ );


function do_hashtagger( $content ) {
  global $wp_hashtagger;
  return $wp_hashtagger->work( $content );
}


?>