<?php
/**
 *  Create a new custom google XML
 */
 
 
add_filter("sm_build_index", "build_resQwest_sitemap_index", 15, 1);
add_filter("sm_build_content", "build_resQwest_sitemap_content", 15, 3);

function build_resQwest_sitemap_index($gsg) {
    $date=date_timestamp_get(date_create());
    $gsg->AddSitemap("resQwest", null, $date);
}

function build_resQwest_sitemap_content($gsg, $type, $params) {
    $resQwestRoutes = get_transient('resQwestRoutes');
        
    if ( $resQwestRoutes === false )
    {
        loadResQwestRoutes();
    }

    $entries = resQwest_get_option('resQwest_virtualPage');
    $siteurl = str_ireplace('http://', 'https://', site_url());
    foreach ( (array) $entries as $key => $entry ) {
        if ( $resQwestRoutes !== false )
        {
            foreach ( (array) $resQwestRoutes as $key => $value ) {
                
                $date=date_timestamp_get(date_create());
                
                $gsg->AddUrl($siteurl.'/' . $entry . '/'. $key, $date, "weekly", 1.0);
                
            }
        }
    }
}

?>