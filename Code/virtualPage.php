<?php
/*
 * Virtual Themed Page class
 *
 * This class implements virtual pages for a plugin.
 *
 * It is designed to be included then called for each part of the plugin
 * that wants virtual pages.
 *
 * It supports multiple virtual pages and content generation functions.
 * The content functions are only called if a page matches.
 *
 * The class uses the theme templates and as far as I know is unique in that.
 * It also uses child theme templates ahead of main theme templates.
 *
 * Example code follows class.
 *
 * August 2013 Brian Coogan
 *
 * source: https://gist.github.com/brianoz/9105004
 * 
 */
// There are several virtual page classes, we want to avoid a clash!
//
//
class resQwest_VirtualPage
{
    public $title = '';
    public $body = '';
    private $vpages = array();  // the main array of virtual pages
    private $mypath = '';
    function __construct($plugin_path = null)
    {
        if (empty($plugin_path))
            $plugin_path = dirname(__FILE__);
        $this->mypath = $plugin_path;
        // Virtual pages are checked in the 'parse_request' filter.
        // This action starts everything off if we are a virtual page
        add_action('parse_request', array(&$this, 'vtp_parse_request'));
    }
    
    function add($virtual_regexp, $contentfunction)
    {
	   $this->vpages[$virtual_regexp] = $contentfunction;
    }
    // Check page requests for Virtual pages
    // If we have one, call the appropriate content generation function
    //
    function vtp_parse_request(&$wp)
    {
        //global $wp;
        //if (empty($wp->query_vars['pagename']))
        //    return; // page isn't permalink

        $p = $_SERVER['REQUEST_URI'];
        $matched = 0;
        foreach ($this->vpages as $regexp => $func)
        {
            if (preg_match($regexp, $p))
            {
            $matched = 1;
            break;
            }
        }
        // Do nothing if not matched
        if (! $matched)
            return;
            
        // setup hooks and filters to generate virtual movie page
        add_filter('the_posts', array(&$this, 'vtp_createdummypost'));
        
        // Call user content generation function
        // Called last so it can remove any filters it doesn't like
        // It should set:
        //    $this->body   -- body of the virtual page
        //    $this->title  -- title of the virtual page
        //    $this->template  -- optional theme-provided template
        //          eg: page
        //    $this->subtemplate -- optional subtemplate (eg movie)
        // Doco is unclear whether call by reference works for call_user_func()
        // so using call_user_func_array() instead, where it's mentioned.
        // See end of file for example code.
        $this->template = $this->subtemplate = null;
        $this->title = null;
        unset($this->body);
        call_user_func_array($func, array(&$this, $p));
        if (! isset($this->body)) //assert 
        {
            $this->body = '';
            //wp_die("Virtual Themed Pages: must save ->body [VTP07]");
        }
        return($wp);
    }
    
    // Setup a dummy post/page 
    // From the WP view, a post == a page
    //
    function vtp_createdummypost($posts)
    {
        // have to create a dummy post as otherwise many templates
        // don't call the_content filter
        global $wp, $wp_query;
        //create a fake post intance
        $p = new stdClass;
        // fill $p with everything a page in the database would have
        $p->ID = -1;
        $p->virtualPage = 1;
        $p->post_author = 1;
        $p->post_date = current_time('mysql');
        $p->post_date_gmt =  current_time('mysql', $gmt = 1);
        $p->post_content = $this->body;
        $p->post_title = $this->title;
        $p->post_excerpt = '';
        $p->post_status = 'publish';
        $p->ping_status = 'closed';
        $p->post_password = '';
        $p->post_name = 'resQwest'; // slug
        $p->to_ping = '';
        $p->pinged = '';
        $p->modified = $p->post_date;
        $p->modified_gmt = $p->post_date_gmt;
        $p->post_content_filtered = '';
        $p->post_parent = 0;
        $p->guid = get_home_url('/' . $p->post_name); // use url instead?
        $p->menu_order = 0;
        $p->post_type = 'page';
        $p->post_mime_type = '';
        $p->comment_status = 'closed';
        $p->comment_count = 0;
        $p->filter = 'raw';
        $p->ancestors = array(); // 3.6
        // reset wp_query properties to simulate a found page
        $wp_query->is_page = TRUE;
        $wp_query->is_singular = TRUE;
        $wp_query->is_home = FALSE;
        $wp_query->is_archive = FALSE;
        $wp_query->is_category = FALSE;
        unset($wp_query->query['error']);
        $wp->query = array();
        $wp_query->query_vars['error'] = '';
        $wp_query->is_404 = FALSE;
        $wp_query->current_post = $p->ID;
        $wp_query->found_posts = 1;
        $wp_query->post_count = 1;
        $wp_query->comment_count = 0;
        // -1 for current_comment displays comment if not logged in!
        $wp_query->current_comment = null;
        $wp_query->is_singular = 1;
        $wp_query->is_attachment = false;
        $wp_query->post = $p;
        $wp_query->posts = array($p);
        $wp_query->queried_object = $p;
        $wp_query->queried_object_id = $p->ID;
        $wp_query->current_post = $p->ID;
        $wp_query->post_count = 1;
        return array($p);
    }
    
   
} // class
