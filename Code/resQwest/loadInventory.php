<?php

// Note: resQwest_forceUpdate() hook removed - now using AJAX refresh button in pluginOptions.php

function resQwest_loadInventory() {
     try {
        //$enablePageLoading = resQwest_get_option('resQwest_enablePageLoading');
        //if ($enablePageLoading === 'on') {
            $accessToken = resQwest_loadSecurityToken();
            if (!$accessToken) {
                error_log('resQwest_loadInventory: Failed to load security token');
                return false;
            }

            $body = "{}";
            $configUri = "https://public.resqwest.com/inventory";
            // Suppress deprecation warnings from Httpful library (PHP 8.2+ compatibility)
            $old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
            $response = \Httpful\Request::post($configUri)
                ->sendsJson()
                ->body($body)
                ->addHeader('Origin', 'https://' . $_SERVER["HTTP_HOST"])
                ->addHeader('Authorization', 'Bearer ' . $accessToken)
                ->send();
            error_reporting($old_error_reporting);
            
            $responseCode = $response->code;
            $inventoryResponse = json_decode($response);
            
            if ($responseCode >= 200 && $responseCode < 300) {
                if (isset($inventoryResponse->inventoryDetails))
                {
                    $inventoryDetails = $inventoryResponse->inventoryDetails;
                    $resQwestRoutes = array(); 
                    foreach($inventoryDetails as $key => $inventory)
                    {
                        resQwest_loadInventoryPage($inventory, $responseCode);
                    }

                    resQwest_markRemovedPagesAsDraft($inventoryDetails);

                    return true;
                }
                else {
                    error_log('resQwest_loadInventory: API response missing inventoryDetails. Response code: ' . $responseCode);
                    return false;
                }
            } else {
                error_log('resQwest_loadInventory: API request failed with response code: ' . $responseCode);
                return false;
            }
        //}
     }
     catch(Exception $e)
     {
         // ignore exceptions as it crashed the page load if there is a connectivity issue
         // clear any transient data to force a retry
         error_log('resQwestRoutes loading exception: ' . $e->getMessage());
         return false;
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


function resQwest_loadInventorypage($inventory, $responseCode = '') {
    // Store timestamp before processing
    $apiResponseTime = current_time('mysql');
    $updateStatus = 'error';
    $errorMessage = '';

    try {
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
            'post_name' => isset($inventory->name) ? $slug : '', // The name (slug) for your post
            //'post_parent' => [ <post ID> ] //Sets the parent of the new post.
            //'post_password' => [ ? ] //password for post?
            //'post_status' => 'publish', //[ 'draft' | 'publish' | 'pending' ] //Set the status of the new post.
            'post_title' => isset($inventory->name) ? $inventory->name : '', //The title of your post.
            //'post_type' => 'page', //[ 'post' | 'page' ] //Sometimes you want to post a page.
            'tags_input' => isset($inventory->categories) ? $inventory->categories : array(),
            //'to_ping' => [ ? ] //?
            'meta_input' => array(
                '_resQwest_enabled' => "on",
                'resQwest-inventoryId' => $inventory->inventoryId,
                '_resQwest_route' => 'SelectTimes/'.$inventory->inventoryId
            )
        );

        $enableCategoryLoading = resQwest_get_option('resQwest_enableCategoryLoading');
        if ($enableCategoryLoading === 'on') {
            if (isset($inventory->categories) && is_array($inventory->categories) && count($inventory->categories) > 0)
            {
                $categoriesReversed = array_reverse($inventory->categories);
                if (!empty($categoriesReversed)) {
                    $firstCategory = array_pop($categoriesReversed);
                    if ($firstCategory) {
                        $categoryPageId = resQwest_loadCategoryPage($firstCategory);
                        $post['post_parent'] = $categoryPageId;
                    }
                }
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
            $enablePageLoading = resQwest_get_option('resQwest_enablePageLoading');
            if ($enablePageLoading === 'on') {
                $post['post_content'] = '[inventory-shortDescription]';
                $post['post_status'] = 'draft';
                $post['post_type'] = 'page';
                $id = wp_insert_post( $post );  
            }
        }

        if ($id) {
            // Store inventory field data - check if properties exist before accessing
            if (isset($inventory->name)) update_post_meta($id, 'inventory-name', $inventory->name);
            if (isset($inventory->shortDescription)) update_post_meta($id, 'inventory-shortDescription', $inventory->shortDescription);
            if (isset($inventory->description)) update_post_meta($id, 'inventory-description', $inventory->description);
            if (isset($inventory->duration)) update_post_meta($id, 'inventory-duration', $inventory->duration);
            if (isset($inventory->operates)) update_post_meta($id, 'inventory-operates', $inventory->operates);
            if (isset($inventory->checkin)) update_post_meta($id, 'inventory-checkin', $inventory->checkin);
            if (isset($inventory->cost)) update_post_meta($id, 'inventory-cost', $inventory->cost);
            if (isset($inventory->cancelPolicy)) update_post_meta($id, 'inventory-cancelPolicy', $inventory->cancelPolicy);
            if (isset($inventory->restrictions)) update_post_meta($id, 'inventory-restrictions', $inventory->restrictions);
            if (isset($inventory->bookingNotes)) update_post_meta($id, 'inventory-bookingNotes', $inventory->bookingNotes);
            if (isset($inventory->location)) {
                // Convert location to string if it's an object
                $location_value = $inventory->location;
                if (is_object($location_value) || is_array($location_value)) {
                    $location_value = json_encode($location_value);
                }
                update_post_meta($id, 'inventory-location', $location_value);
            }

            // Mark as successful
            $updateStatus = 'success';
            $lastUpdate = current_time('mysql');
            update_post_meta($id, '_resQwest_lastUpdate', $lastUpdate);
        } else {
            $errorMessage = 'Failed to create or update post';
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        error_log('resQwest_loadInventoryPage exception: ' . $errorMessage);
    }

    // Store debug metadata if we have a post ID
    if (isset($id) && $id) {
        update_post_meta($id, '_resQwest_apiResponseTime', $apiResponseTime);
        update_post_meta($id, '_resQwest_lastUpdateStatus', $updateStatus);
        if ($responseCode) {
            update_post_meta($id, '_resQwest_apiResponseCode', $responseCode);
        }
        if ($errorMessage) {
            update_post_meta($id, '_resQwest_lastError', $errorMessage);
        } else {
            // Clear error on success
            delete_post_meta($id, '_resQwest_lastError');
        }
    }
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

    // Return first result if exists, otherwise return false
    if (isset($ids[0])) {
        return $ids[0];
    }
    return false;
}

function resQwest_get_post_ids_by_meta_key( $meta_key ){
	global $wpdb;
 
	$ids = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key ) );

    return $ids; 
}

/**
 * Refresh inventory data for a single page by post_id
 * 
 * @param int $post_id The WordPress post ID to refresh
 * @return array Result array with 'success' (bool) and 'message' (string)
 */
function resQwest_refreshSingleInventory($post_id) {
    try {
        // Get the inventoryId for this page
        $inventoryId = get_post_meta($post_id, 'resQwest-inventoryId', true);
        
        if (!$inventoryId) {
            return array(
                'success' => false,
                'message' => 'No inventoryId found for this page'
            );
        }

        // Load security token
        $accessToken = resQwest_loadSecurityToken();
        if (!$accessToken) {
            return array(
                'success' => false,
                'message' => 'Failed to load security token'
            );
        }

        // Fetch inventory data from API
        $body = "{}";
        $configUri = "https://public.resqwest.com/inventory";
        // Suppress deprecation warnings from Httpful library (PHP 8.2+ compatibility)
        $old_error_reporting = error_reporting(E_ALL & ~E_DEPRECATED);
        $response = \Httpful\Request::post($configUri)
            ->sendsJson()
            ->body($body)
            ->addHeader('Origin', 'https://' . $_SERVER["HTTP_HOST"])
            ->addHeader('Authorization', 'Bearer ' . $accessToken)
            ->send();
        error_reporting($old_error_reporting);
        
        $responseCode = $response->code;
        $inventoryResponse = json_decode($response);
        
        if ($responseCode >= 200 && $responseCode < 300) {
            if (isset($inventoryResponse->inventoryDetails)) {
                $inventoryDetails = $inventoryResponse->inventoryDetails;
                
                // Find the matching inventory item
                $foundInventory = null;
                foreach($inventoryDetails as $inventory) {
                    if ($inventory->inventoryId == $inventoryId) {
                        $foundInventory = $inventory;
                        break;
                    }
                }
                
                if ($foundInventory) {
                    // Update the page with the found inventory data
                    resQwest_loadInventoryPage($foundInventory, $responseCode);
                    
                    return array(
                        'success' => true,
                        'message' => 'Inventory data refreshed successfully',
                        'timestamp' => current_time('mysql')
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => 'Inventory item not found in API response'
                    );
                }
            } else {
                return array(
                    'success' => false,
                    'message' => 'API response missing inventoryDetails. Response code: ' . $responseCode
                );
            }
        } else {
            return array(
                'success' => false,
                'message' => 'API request failed with response code: ' . $responseCode
            );
        }
    } catch (Exception $e) {
        error_log('resQwest_refreshSingleInventory exception: ' . $e->getMessage());
        return array(
            'success' => false,
            'message' => 'Error refreshing inventory: ' . $e->getMessage()
        );
    }
}

?>