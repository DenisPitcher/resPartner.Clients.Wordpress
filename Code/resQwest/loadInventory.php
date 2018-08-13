<?php

register_activation_hook(__FILE__, 'resQwest_loadInventory_activation');

function resQwest_loadInventory_activation() {
    if (! wp_next_scheduled ( 'resQwest_loadInventory_hook' )) {
	wp_schedule_event(time(), 'daily', 'resQwest_loadInventory_hook');
    }
}

///Hook into that action that'll fire every twenty four  hours
add_action( 'resQwest_loadInventory_hook', 'resQwest_loadInventory' );

add_action('cmb2_init', 'resQwest_forceUpdate');

function resQwest_forceUpdate() {
    $forceUpdate = resQwest_get_option('resQwest_forceUpdate');
    if ($forceUpdate === 'on')
    {
        resQwest_loadInventory();
        resQwest_update_option('resQwest_forceUpdate', false);
    }
}


function resQwest_loadInventory() {
     try {
        $enablePageLoading = resQwest_get_option('resQwest_enablePageLoading');
        if ($enablePageLoading === 'on') {
            $accessToken = resQwest_loadSecurityToken();
            $body = "{}";
            $configUri = "https://api-staging.resqwest.com/public/inventory";
            $response = \Httpful\Request::post($configUri)
                ->sendsJson()
                ->body($body)
                ->addHeader('Origin', 'https://' . $_SERVER["HTTP_HOST"])
                ->addHeader('Authorization', 'Bearer ' . $accessToken)
                ->send();
            
            $inventoryResponse = json_decode($response);
            if (isset($inventoryResponse->inventoryDetails))
            {
                $inventoryDetails = $inventoryResponse->inventoryDetails;
                $resQwestRoutes = array(); 
                foreach($inventoryDetails as $key => $inventory)
                {
                    resQwest_loadInventoryPage($inventory);
                }
                return true;
            }
            else {
                return false;
            }

            
        }
     }
     catch(Exception $e)
     {
         // ignore exceptions as it crashed the page load if there is a connectivity issue
         // clear any transient data to force a retry
         error_log('resQwestRoutes loading exception: ' . $e);
     }

}


//add_action('cmb2_init', 'resQwestPageLoader');

function resQwest_loadInventorypage($inventory) {

    
    $slug = str_replace(' ','-',$inventory->name);

    $post = array(
        //'ID' => [ <post id> ] //Are you updating an existing post?
        //'menu_order' => [ <order> ] //If new post is a page, sets the order should it appear in the tabs.
        //'page_template' => [ <template file> ] //Sets the template for the page.
        //'comment_status' => [ 'closed' | 'open' ] // 'closed' means no comments.
        //'ping_status' => [ ? ] //Ping status?
        //'pinged' => [ ? ] //?
        //'post_author' => [ <user ID> ] //The user ID number of the author.
        //'post_category' => [ array(<category id>, <...>) ] //Add some categories.
        //'post_content' => [ <the text of the post> ] //The full text of the post.
        //'post_date' => [ Y-m-d H:i:s ] //The time post was made.
        //'post_date_gmt' => [ Y-m-d H:i:s ] //The time post was made, in GMT.
        //'post_excerpt' => [ <an excerpt> ] //For all your post excerpt needs.
        'post_name' => $slug, // The name (slug) for your post
        //'post_parent' => [ <post ID> ] //Sets the parent of the new post.
        //'post_password' => [ ? ] //password for post?
        //'post_status' => 'publish', //[ 'draft' | 'publish' | 'pending' ] //Set the status of the new post.
        'post_title' => $inventory->name, //The title of your post.
        //'post_type' => 'page', //[ 'post' | 'page' ] //Sometimes you want to post a page.
        //'tags_input' => [ '<tag>, <tag>, <...>' ] //For tags.
        //'to_ping' => [ ? ] //?
        'meta_input' => array(
            '_resQwest_enabled' => "on",
            'resQwest-inventoryId' => $inventory->inventoryId,
            '_resQwest_route' => 'inventory/'.$inventory->inventoryId
        )
    );  

    // check for existing post
    $id = resQwest_get_post_id_by_meta_key_and_value('resQwest-inventoryId',$inventory->inventoryId);

    if ($id) {
        $post['ID'] = $existing->ID;
        wp_update_post( $post );
        
    }
    else
    {
        $post['post_status'] = 'draft';
        $post['post_type'] = 'page';
        $id = wp_insert_post( $post );  
    }
    update_post_meta($id, 'inventory-name', $inventory->name);
    update_post_meta($id, 'inventory-shortDescription', $inventory->shortDescription);
    update_post_meta($id, 'inventory-description', $inventory->description);
    update_post_meta($id, 'inventory-duration', $inventory->duration);
    update_post_meta($id, 'inventory-operates', $inventory->operates);
    update_post_meta($id, 'inventory-checkin', $inventory->checkin);
    update_post_meta($id, 'inventory-cost', $inventory->cost);
    update_post_meta($id, 'inventory-cancelPolicy', $inventory->cancelPolicy);
    update_post_meta($id, 'inventory-restrictions', $inventory->restrictions);
    update_post_meta($id, 'inventory-bookingNotes', $inventory->bookingNotes);
    update_post_meta($id, 'inventory-location', $inventory->location);
}

function resQwest_get_post_id_by_meta_key_and_value( $meta_key, $meta_value ){
	global $wpdb;
 
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) );

    return $ids[0]; // only return first result
    
	// if( count( $ids ) > 1 ) 
    //     return $ids; // return array
	// else
	// 	return $ids[0]; // return int
}

?>