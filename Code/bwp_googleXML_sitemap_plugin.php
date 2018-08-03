<?php
/**
 *  Create a new custom google XML
 */
 
add_filter('bwp_gxs_external_pages', 'bwp_gxs_external_pages');
function bwp_gxs_external_pages($pages)
{
    $virtualpages = array();
    
    
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
                $siteurl = str_ireplace('http://', 'https://', site_url());
                $page = array('location' => $siteurl .'/' . $entry . '/'. $key, 'lastmod' => date('Y-m-d'), 'frequency' => 'auto', 'priority' => '1.0');
                array_push($virtualpages, $page);
            }
        }
    }
    return $virtualpages;


}
