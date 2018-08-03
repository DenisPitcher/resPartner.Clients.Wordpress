<?php
/*
Plugin Name: resQwest EasyApi
Plugin URI: http://www.resqwest.com
Description: resQwest
Version: 0.5
Author: Denis Pitcher [resQwest.com]
Author URI: http://www.denispitcher.com
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

if ( file_exists( dirname( __FILE__ ) . '/virtualPage.php' ) ) {
	require_once dirname( __FILE__ ) . '/virtualPage.php';
}

if ( file_exists( dirname( __FILE__ ) . '/yoast_sitemap_plugin.php' ) ) {
	require_once dirname( __FILE__ ) . '/yoast_sitemap_plugin.php';
}

if ( file_exists( dirname( __FILE__ ) . '/bwp_googleXML_sitemap_plugin.php' ) ) {
	require_once dirname( __FILE__ ) . '/bwp_googleXML_sitemap_plugin.php';
}

if ( file_exists( dirname( __FILE__ ) . '/googleXML_sitemap_plugin.php' ) ) {
	require_once dirname( __FILE__ ) . '/googleXML_sitemap_plugin.php';
}

require_once( plugin_dir_path( __FILE__ ) . '/pageTemplate.php' );
add_action( 'plugins_loaded', array( 'pageTemplate', 'get_instance' ) );


add_action('cmb2_init', 'virtualPageLoader');

function virtualPageLoader() {
    $enableVirtualPages = resQwest_get_option('resQwest_enableVirtualPages');
    if ($enableVirtualPages === 'on') {
        $vp = new resQwest_VirtualPage();
        $entries = resQwest_get_option('resQwest_virtualPage');
        foreach ( (array) $entries as $key => $entry ) {
            $vp->add('#/' . $entry . '$#i', 'loadVirtualPage');
            $vp->add('#/' . $entry . '/#i', 'loadVirtualPage');
        }   
    }
}


function loadVirtualPage($v, $url) {
        $v->template = 'page';
        $v->slug = $url;
}




?>