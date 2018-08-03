<?php
/**
 *  Create a new custom yoast seo sitemap
 */
 
add_filter( 'wpseo_sitemap_index', 'ex_add_sitemap_custom_items' );
add_action( 'init', 'init_wpseo_do_sitemap_actions' );


// Add custom index
function ex_add_sitemap_custom_items(){
	global $wpseo_sitemaps;
	$date = date('Y-m-d');

	$smp ='';

    	$smp .= '<sitemap>' . "\n";
	$smp .= '<loc>' . site_url() .'/resQwest-sitemap.xml</loc>' . "\n";
	$smp .= '<lastmod>' . htmlspecialchars( $date ) . '</lastmod>' . "\n";
	$smp .= '</sitemap>' . "\n";

	
	return $smp;
}


function init_wpseo_do_sitemap_actions(){
	add_action( "wpseo_do_sitemap_resQwest", 'ex_generate_origin_combo_sitemap');
}

function ex_generate_origin_combo_sitemap(){
    global $wpseo_sitemaps;

    $output = '';
    $chf = 'weekly';
    $pri = 1.0;

    $resQwestRoutes = get_transient('resQwestRoutes');
    
    if ( $resQwestRoutes === false )
    {
        loadResQwestRoutes();
    }

    $entries = resQwest_get_option('resQwest_virtualPage');
    foreach ( (array) $entries as $key => $entry ) {
        if ( $resQwestRoutes !== false )
        {
            foreach ( (array) $resQwestRoutes as $key => $value ) {
                $url = array();

                $siteurl = str_ireplace('http://', 'https://', site_url());
                $url['loc'] = $siteurl .'/' . $entry . '/'. $key;
                $url['chf'] = $chf;
                $url['pri'] = $pri;
                $url['mod'] = htmlspecialchars( date('Y-m-d') );

                $output .= $wpseo_sitemaps->sitemap_url( $url );

                //echo $output;
            }
        }

    }
    
    if ( empty( $output ) ) {
        $wpseo_sitemaps->bad_sitemap = true;
        return;
    }

    //Build the full sitemap
    $sitemap = '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
    $sitemap .= 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" ';
    $sitemap .= 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    $sitemap .= $output . '</urlset>';

    //echo $sitemap;
    $wpseo_sitemaps->set_sitemap($sitemap);

}

/*********************************************************
 *  OR we can use $wpseo_sitemaps->register_sitemap( 'resQwest', 'METHOD' );
 ********************************************************/
/**
add_action( 'init', 'ex_register_my_new_sitemap', 99 );

 * On init, run the function that will register our new sitemap as well
 * as the function that will be used to generate the XML. This creates an
 * action that we can hook into built around the new
 * sitemap name - 'wp_seo_do_sitemap_my_new_sitemap'

function ex_register_my_new_sitemap() {
	global $wpseo_sitemaps;
	$wpseo_sitemaps->register_sitemap( 'resQwest', 'ex_generate_origin_combo_sitemap' );
}

add_action( 'init', 'init_do_sitemap_actions' );

function init_do_sitemap_actions(){
	add_action( 'wp_seo_do_sitemap_our-resQwest', 'ex_generate_origin_combo_sitemap' );
}
 */




