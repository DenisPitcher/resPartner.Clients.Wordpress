<?php
/*
Plugin Name: resQwest
Plugin URI: http://www.resqwest.com
Description: resQwest
Version: 1.1
Author: resQwest.com
Author URI: http://www.resQwest.com
*/

require_once(ABSPATH . 'wp-config.php'); 
require_once(ABSPATH . 'wp-includes/wp-db.php'); 
require_once(ABSPATH . 'wp-admin/includes/taxonomy.php'); 

if ( file_exists( dirname( __FILE__ ) . '/pageConfig.php' ) ) {
	require_once dirname( __FILE__ ) . '/pageConfig.php';
}

if ( file_exists( dirname( __FILE__ ) . '/pluginOptions.php' ) ) {
	require_once dirname( __FILE__ ) . '/pluginOptions.php';
}

if ( file_exists( dirname( __FILE__ ) . '/content.php' ) ) {
	require_once dirname( __FILE__ ) . '/content.php';
}

if ( file_exists( dirname( __FILE__ ) . '/resQwest/securityToken.php' ) ) {
	require_once dirname( __FILE__ ) . '/resQwest/securityToken.php';
}

if ( file_exists( dirname( __FILE__ ) . '/resQwest/loadInventory.php' ) ) {
	require_once dirname( __FILE__ ) . '/resQwest/loadInventory.php';
}

require_once( plugin_dir_path( __FILE__ ) . '/pageTemplate.php' );
add_action( 'plugins_loaded', array( 'pageTemplate', 'get_instance' ) );

register_activation_hook(__FILE__, 'resQwest_loadInventory_activation');

function resQwest_loadInventory_activation() {
    if (! wp_next_scheduled ( 'resQwest_loadInventory_hook' )) {
	wp_schedule_event(time(), 'daily', 'resQwest_loadInventory_hook');
    }
}
register_deactivation_hook( __FILE__, 'resQwest_loadInventory_deactivation' );
  
function my_deactivation() {
    wp_clear_scheduled_hook( 'resQwest_loadInventory_hook' );
}

///Hook into that action that'll fire every twenty four  hours
add_action( 'resQwest_loadInventory_hook', 'resQwest_loadInventory' );

?>