<?php

function api_div($content) {

    $original_content = $content ; // preserve the original ...

    if (!is_admin()) {
        
        
        $enabled = get_post_meta( get_the_ID(), '_resQwest_enabled', true );
        if ($enabled == "on") {
            
            $route = get_post_meta( get_the_ID(), '_resQwest_route', true );
            $startroute = '';
            if ($route != "")
            {
                $startroute = 'startRoute="' . $route . '"';
            }

            $original_content = load_tag("[inventory-name]","inventory-name",$original_content);
            $original_content = load_tag("[inventory-shortDescription]","inventory-shortDescription",$original_content);
            $original_content = load_tag("[inventory-description]","inventory-description",$original_content);
            $original_content = load_tag("[inventory-duration]","inventory-duration",$original_content);
            $original_content = load_tag("[inventory-operates]","inventory-operates",$original_content);
            $original_content = load_tag("[inventory-checkin]","inventory-checkin",$original_content);
            $original_content = load_tag("[inventory-cost]","inventory-cost",$original_content);
            $original_content = load_tag("[inventory-cancelPolicy]","inventory-cancelPolicy",$original_content);
            $original_content = load_tag("[inventory-restrictions]","inventory-restrictions",$original_content);
            $original_content = load_tag("[inventory-bookingNotes]","inventory-bookingNotes",$original_content);
            $original_content = load_tag("[inventory-location]","inventory-location",$original_content);

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

function load_tag($tag, $metaId, $original_content) {
    $metaValue = get_post_meta( get_the_ID(), $metaId, true );
    
    if ($metaValue == false || is_object($metaValue) == true)
    {
        return $original_content;
    }

    $pos = stripos($original_content, $tag);
    if ($pos == false) {
        return $original_content;
    } else {
        return str_ireplace($tag, $metaValue, $original_content);
    }
}

function load_head_scripts() {
    if (!is_admin()) {
        $enabled = get_post_meta( get_the_ID(), '_resQwest_enabled', true );
        if ($enabled == "on") {
            wp_register_style( 'resQwestEasyApi-style', 'https://api.resqwest.com/web/app/main.css');
            wp_enqueue_style( 'resQwestEasyApi-style' );
        }
    }
}

function load_footer_scripts() {
    if (!is_admin()) {
        $enabled = get_post_meta( get_the_ID(), '_resQwest_enabled', true );
        if ($enabled == "on") {
            //$domain = get_post_meta( get_the_ID(), '_resQwest_domain', true );
            wp_enqueue_script( 'resQwestEasyApi', 'https://api.resqwest.com/web/app/main-built.js');
        }
    }
}




add_action('wp_enqueue_scripts', 'load_head_scripts');
add_action('wp_footer', 'load_footer_scripts');
add_action('the_content', 'api_div');


?>