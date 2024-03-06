<?php
/**
 * Get the bootstrap! If using the plugin from wordpress.org, REMOVE THIS!
 */

if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
}


/**
 * Include and setup custom metaboxes and fields. (make sure you copy this file to outside the CMB2 directory)
 *
 * Be sure to replace all instances of 'resQwest_' with your project's prefix.
 * http://nacin.com/2010/05/11/in-wordpress-prefix-everything/
 *
 * @category YourThemeOrPlugin
 * @package  Demo_CMB2
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/WebDevStudios/CMB2
 */


add_action( 'cmb2_admin_init', 'resQwest_register_metabox' );
/**
 * Hook in and add a demo metabox. Can only happen on the 'cmb2_admin_init' or 'cmb2_init' hook.
 */
function resQwest_register_metabox() {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_resQwest_';

	/**
	 * Sample metabox to demonstrate each field type included
	 */
	$cmb_demo = new_cmb2_box( array(
		'id'            => $prefix . 'metabox',
		'title'         => __( 'resQwest', 'cmb2' ),
		'object_types'  => array( 'page', 'post' ), // Post type
		// 'show_on_cb' => 'resQwest_show_if_front_page', // function should return a bool value
		// 'context'    => 'normal',
		// 'priority'   => 'high',
		// 'show_names' => true, // Show field names on the left
		// 'cmb_styles' => false, // false to disable the CMB stylesheet
		// 'closed'     => true, // true to keep the metabox closed by default
	) );


	$cmb_demo->add_field( array(
		'name' => __( 'Enable resQwest', 'cmb2' ),
		//'desc' => __( 'field description (optional)', 'cmb2' ),
		'id'   => $prefix . 'enabled',
		'type' => 'checkbox',
	) );


	//$cmb_demo->add_field( array(
	//	'name'       => __( 'Test Text', 'cmb2' ),
	//	'desc'       => __( 'field description (optional)', 'cmb2' ),
	//	'id'         => $prefix . 'text',
	//	'type'       => 'text',
	//	'show_on_cb' => 'resQwest_hide_if_no_cats', // function should return a bool value
	//	// 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
	//	// 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
	//	// 'on_front'        => false, // Optionally designate a field to wp-admin only
	//	// 'repeatable'      => true,
	//) );

	$cmb_demo->add_field( array(
		'name' => __( 'Route', 'cmb2' ),
		'desc' => __( '(optional)', 'cmb2' ),
		'id'   => $prefix . 'route',
		'type' => 'text_medium',
		// 'repeatable' => true,
	) );

	$cmb_demo->add_field( array(
		'name' => __( 'InventoryId', 'cmb2' ),
		'desc' => __( '(optional)', 'cmb2' ),
		'id'   => 'resQwest-inventoryId',
		'type' => 'text_medium',
		// 'repeatable' => true,
	) );

	$cmb_demo->add_field( array(
		'name' => 'Description',
		'desc' => 'The inventory description loaded from resQwest',
		'id' => 'inventory-description',
		'type' => 'textarea'
	) );

	//$cmb_demo->add_field( array(
	//	'name' => __( 'Your resQwest domain', 'cmb2' ),
	//	'desc' => __( 'eg. dolphins.resqwest.com', 'cmb2' ),
	//	'id'   => $prefix . 'domain',
	//	'type' => 'text_medium',
	//	// 'repeatable' => true,
	//) );



	//$cmb_demo->add_field( array(
	//	'name'             => __( 'Test Select', 'cmb2' ),
	//	'desc'             => __( 'field description (optional)', 'cmb2' ),
	//	'id'               => $prefix . 'select',
	//	'type'             => 'select',
	//	'show_option_none' => true,
	//	'options'          => array(
	//		'standard' => __( 'Option One', 'cmb2' ),
	//		'custom'   => __( 'Option Two', 'cmb2' ),
	//		'none'     => __( 'Option Three', 'cmb2' ),
	//	),
	//) );

}
?>