<?php



add_action('cmb2_init', 'resQwest_forceUpdate');


function resQwest_forceUpdate() {
    $forceUpdate = resQwest_get_option('resQwest_forceUpdate');
    if ($forceUpdate === 'on')
    {
        resQwest_update_option('resQwest_forceUpdate', false);
        resQwest_loadInventory();
    }
}


function resQwest_loadInventory() {
     try {
        $enablePageLoading = resQwest_get_option('resQwest_enablePageLoading');
        if ($enablePageLoading === 'on') {
            $accessToken = resQwest_loadSecurityToken();
            $body = "{}";
            $configUri = "https://api.resqwest.com/public/inventory";
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

                resQwest_markRemovedPagesAsDraft($inventoryDetails);

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


function resQwest_markRemovedPagesAsDraft($inventoryDetails)
{
    $inventoryPages = resQwest_get_post_ids_by_meta_key('resQwest-inventoryId');
    foreach($inventoryPages as $key => $pageMeta)
    {
        $invId = $pageMeta->meta_value;
        $exists = false;
        foreach($inventoryDetails as $key => $inventory)
        {
            if ($inventory->inventoryId == $invId)
            {
                $exists = true;
            }
        }
        if ($exists == false)
        {
            $post = array(
                'ID' => $pageMeta->post_id,
                'post_status' => 'draft'
            );
            wp_update_post( $post );
        }
    }
}


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
            '_resQwest_route' => 'SelectTimes/'.$inventory->inventoryId
        )
    );

    $enableCategoryLoading = resQwest_get_option('resQwest_enableCategoryLoading');
    if ($enableCategoryLoading === 'on') {
        if (sizeof($inventory->categories) > 0)
        {
            $firstCategory = array_pop(array_reverse($inventory->categories));
            $categoryPageId = resQwest_loadCategoryPage($firstCategory);
            $post['post_parent'] = $categoryPageId;
        }
    }

    // check for existing post
    $id = resQwest_get_post_id_by_meta_key_and_value('resQwest-inventoryId',$inventory->inventoryId);

    if ($id) {
        $post['ID'] = $id;
        wp_update_post( $post );
        
    }
    else
    {
        $post['post_content'] = '[inventory-shortDescription]';
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

function resQwest_loadCategoryPage($category) {
    $slug = str_replace(' ','-',$category->name);

    $post = array(
        'post_name' => $slug, // The name (slug) for your post
        'post_title' => $category->name, //The title of your post.
        'meta_input' => array(
            '_resQwest_enabled' => "on",
            'resQwest-categoryId' => $category->id,
            '_resQwest_route' => 'Category/'.$category->id
        )
    );

    // check for existing post
    $id = resQwest_get_post_id_by_meta_key_and_value('resQwest-categoryId',$category->id);

    if ($id) {
        $post['ID'] = $id;
        wp_update_post( $post );
    }
    else
    {
        $post['post_status'] = 'draft';
        $post['post_type'] = 'page';
        $id = wp_insert_post( $post );  
    }
    return $id;
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

function resQwest_get_post_ids_by_meta_key( $meta_key ){
	global $wpdb;
 
	$ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key ) );

    return $ids; 
}

?>