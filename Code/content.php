<?php

if ( file_exists( dirname( __FILE__ ) . '/seoRoutes.php' ) ) {
	require_once dirname( __FILE__ ) . '/seoRoutes.php';
}

function api_div($content) {

    $original_content = $content ; // preserve the original ...

    if (!is_admin()) {
        
        
        $enabled = get_post_meta( get_the_ID(), '_resQwest_enabled', true );
        if ($enabled == "on" OR get_the_ID() == -1) {
            
            $route = get_post_meta( get_the_ID(), '_resQwest_route', true );
            $startroute = '';
            if ($route != "")
            {
                $startroute = 'startRoute="' . $route . '"';
            } else {
                $entries = resQwest_get_option('resQwest_virtualPage');
                $url = $_SERVER["REQUEST_URI"];
                foreach ( (array) $entries as $key => $entry ) {
                    $url = str_ireplace('/' . $entry . '/', '', $url);
                }
                $resQwestRoutes = initResQwestRoutes();
        
                if ( $resQwestRoutes !== false )
                {
                    foreach ( (array) $resQwestRoutes as $key => $value ) {
                        if (stripos($url, $key) !== false)
                        {
                            $startroute = 'startRoute="' . $value . '"';
                        }
                    }
                }
            }
            
            $add_pre_content = '<div id="resQwestEasyApi" ' . $startroute . ' "></div>';

            if ($original_content != "") {
                $pos = stripos($original_content, "[resQwestEasyApi]");
                if ($pos === false) {
                    $content = $add_pre_content  . $original_content ;
                } else {
                    $content = str_ireplace("[resQwestEasyApi]", $add_pre_content, $original_content);
                }
            } else {
                $content = $add_pre_content  . $original_content ;
            }            
        }
    }

    return $content;
}

function load_head_scripts() {
    if (!is_admin()) {
        $enabled = get_post_meta( get_the_ID(), '_resQwest_enabled', true );
        if ($enabled == "on" OR get_the_ID() == -1) {
            wp_register_style( 'resQwestEasyApi-style', 'https://api.resqwest.com/web/app/main.css');
            wp_enqueue_style( 'resQwestEasyApi-style' );
        }
    }
}

function load_footer_scripts() {
    if (!is_admin()) {
        $enabled = get_post_meta( get_the_ID(), '_resQwest_enabled', true );
        if ($enabled == "on" OR get_the_ID() == -1) {
            //$domain = get_post_meta( get_the_ID(), '_resQwest_domain', true );
            wp_enqueue_script( 'resQwestEasyApi', 'https://api.resqwest.com/web/app/main-built.js');
        }
    }
}




add_action('wp_enqueue_scripts', 'load_head_scripts');
add_action('wp_footer', 'load_footer_scripts');
add_action('the_content', 'api_div');


?>