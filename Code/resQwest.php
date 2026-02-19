<?php
/*
 * Plugin Name: resQwest
 * Plugin URI: https://github.com/DenisPitcher/resPartner.Clients.Wordpress
 * Description: resQwest
 * Version: 1.6.3
 * Author: resQwest.com
 * Author URI: http://github.com/denispitcher/
 */

// Load CMB2 as a library (included within this plugin)
$cmb2_init_file = dirname( __FILE__ ) . '/cmb2/init.php';
$cmb2_available = false;

if ( file_exists( $cmb2_init_file ) ) {
	try {
		require_once $cmb2_init_file;
		// Check if CMB2 bootstrap class is available (it should be immediately after require)
		$cmb2_available = class_exists( 'CMB2_Bootstrap_2110', false );
	} catch ( Exception $e ) {
		// Silently fail - CMB2 might not be available
		if ( function_exists( 'error_log' ) ) {
			error_log( 'resQwest: Error loading CMB2 library: ' . $e->getMessage() );
		}
	}
}

// Also check if CMB2 was already loaded elsewhere (e.g., as a plugin)
if ( ! $cmb2_available ) {
	$cmb2_available = defined( 'CMB2_LOADED' ) || ( function_exists( 'class_exists' ) && class_exists( 'CMB2', false ) ) || function_exists( 'new_cmb2_box' );
}

// Check if CMB2 is loaded (either as library or as a plugin)
if ( $cmb2_available ) {
	// CMB2 is available, load plugin files
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
	
	function resQwest_loadInventory_deactivation() {
		wp_clear_scheduled_hook( 'resQwest_loadInventory_hook' );
	}

	///Hook into that action that'll fire every twenty four  hours
	add_action( 'resQwest_loadInventory_hook', 'resQwest_loadInventory' );
}
else {
    // CMB2 is not available, add an admin notice
    add_action('admin_notices', 'resQwest_plugin_admin_notice');

    function resQwest_plugin_admin_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('resQwest Plugin requires CMB2 to be available. Please ensure the CMB2 library is included in the plugin directory.', 'resQwest-plugin-textdomain'); ?></p>
        </div>
        <?php
    }
}
?>