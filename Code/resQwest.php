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

require_once( plugin_dir_path( __FILE__ ) . '/pageTemplate.php' );
add_action( 'plugins_loaded', array( 'pageTemplate', 'get_instance' ) );


add_action('cmb2_init', 'resQwestPageLoader');

function resQwestPageLoader() {
    $enablePageLoading = resQwest_get_option('resQwest_enablePageLoading');
    if ($enablePageLoading === 'on') {


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
            'post_name' => 'the name', // The name (slug) for your post
            //'post_parent' => [ <post ID> ] //Sets the parent of the new post.
            //'post_password' => [ ? ] //password for post?
            //'post_status' => [ 'draft' | 'publish' | 'pending' ] //Set the status of the new post.
            'post_title' => 'the title', //The title of your post.
            'post_type' => 'page', //[ 'post' | 'page' ] //Sometimes you want to post a page.
            //'tags_input' => [ '<tag>, <tag>, <...>' ] //For tags.
            //'to_ping' => [ ? ] //?
            'meta_input' => array(
                'resQwest-inventoryId' => 111
            )
        );  

        // check for existing post
        $args = array(
        'post_type'		=>	'page',
        'meta_query'	=>	array(
            'resQwest-inventoryId' => 111
            )
        );
        $my_query = new WP_Query( $args );
        if ( $my_query->have_posts() ) {
            while ( $my_query->have_posts() ) {
                $my_query->the_post();
                $post['ID'] = get_the_ID();
            }
            /* Restore original Post Data */
            wp_reset_postdata();
        }
          
          // Insert the post into the database
          wp_insert_post( $post );  
    }
}



?>