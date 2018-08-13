<?php
/*
Plugin Name: resQwest
Plugin URI: http://www.resqwest.com
Description: resQwest
Version: 1.0
Author: resQwest.com
Author URI: http://www.resQwest.com
*/
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



?>