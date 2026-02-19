<?php
/**
 * Get the bootstrap! If using the plugin from wordpress.org, REMOVE THIS!
 */

if ( file_exists( dirname( __FILE__ ) . '/cmb2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/cmb2/init.php';
} elseif ( file_exists( dirname( __FILE__ ) . '/CMB2/init.php' ) ) {
	require_once dirname( __FILE__ ) . '/CMB2/init.php';
}


/**
 * Include and setup custom metaboxes and fields. (make sure you copy this file to outside the CMB2 directory)
 *
 * Be sure to replace all instances of 'resQwest_' with your project's prefix.
 * http://nacin.com/2010/05/11/in-wordpress-prefix-everything/
 *
 * @category YourThemeOrPlugin
 * @package  Demo_CMB2
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/WebDevStudios/CMB2
 */


add_action( 'cmb2_admin_init', 'resQwest_register_metabox' );

// Add error logging helper
add_action( 'admin_notices', 'resQwest_error_log_helper' );

function resQwest_error_log_helper() {
	// Only show on post/page edit screens
	$screen = get_current_screen();
	if (!$screen || !in_array($screen->base, array('post', 'page'))) {
		return;
	}
	
	// Check if WP_DEBUG is enabled
	if (defined('WP_DEBUG') && WP_DEBUG) {
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		$log_viewer_url = admin_url('admin.php?page=resqwest-error-log');
		
		if (file_exists($debug_log_path)) {
			$log_size = filesize($debug_log_path);
			$log_size_mb = round($log_size / 1024 / 1024, 2);
			
			// Check for resQwest errors
			$has_resqwest_errors = false;
			$log_content = file_get_contents($debug_log_path);
			if ($log_content && (stripos($log_content, 'resQwest') !== false || stripos($log_content, 'resqwest') !== false)) {
				$has_resqwest_errors = true;
			}
			
			$notice_class = $has_resqwest_errors ? 'notice-warning' : 'notice-info';
			echo '<div class="notice ' . $notice_class . '"><p>';
			echo '<strong>resQwest Debug Info:</strong> ';
			if ($has_resqwest_errors) {
				echo '<span style="color: #dc3232;">⚠ Errors detected!</span> ';
			}
			echo 'Error log: <code>' . esc_html($debug_log_path) . '</code> (' . $log_size_mb . ' MB)';
			echo ' | <a href="' . esc_url($log_viewer_url) . '">View Error Log</a>';
			echo '</p></div>';
		} else {
			echo '<div class="notice notice-info"><p>';
			echo '<strong>resQwest Debug Info:</strong> Debug log not found. ';
			echo '<a href="' . esc_url($log_viewer_url) . '">View Error Log Page</a> for setup instructions.';
			echo '</p></div>';
		}
	}
}

// Enqueue admin scripts for refresh functionality
add_action( 'admin_enqueue_scripts', 'resQwest_enqueue_admin_scripts' );
add_action( 'admin_head', 'resQwest_add_wysiwyg_styles' );

function resQwest_add_wysiwyg_styles() {
    $screen = get_current_screen();
    if ($screen && in_array($screen->base, array('post', 'page'))) {
        echo '<style>
            #inventory-shortDescription_ifr, #inventory-description_ifr {
                opacity: 1 !important;
            }
            #inventory-shortDescription_ifr body, #inventory-description_ifr body {
                color: #000 !important;
                background: #fff !important;
            }
            .mce-content-body {
                color: #000 !important;
                background: #fff !important;
            }
            .mce-edit-area iframe {
                opacity: 1 !important;
            }
        </style>';
    }
}

function resQwest_enqueue_admin_scripts($hook) {
    // Only load on post/page edit screens
    if (!in_array($hook, array('post.php', 'post-new.php', 'page.php', 'page-new.php'))) {
        return;
    }
    
    wp_enqueue_script('jquery');
    
    wp_add_inline_script('jquery', '
        jQuery(document).ready(function($) {
            // Disable WYSIWYG editors for readonly inventory fields and ensure content is visible
            function disableWysiwygEditors() {
                $("#inventory-shortDescription_ifr, #inventory-description_ifr").each(function() {
                    var $iframe = $(this);
                    var editorId = $(this).attr("id").replace("_ifr", "");
                    
                    // Ensure iframe is visible
                    $iframe.css({
                        "opacity": "1",
                        "visibility": "visible"
                    });
                    
                    if (typeof tinyMCE !== "undefined" && tinyMCE.get(editorId)) {
                        var editor = tinyMCE.get(editorId);
                        editor.getBody().setAttribute("contenteditable", "false");
                        editor.settings.readonly = true;
                        
                        // Ensure content is visible with proper styling
                        var body = editor.getBody();
                        body.style.color = "#000";
                        body.style.backgroundColor = "#fff";
                        body.style.opacity = "1";
                        
                        // Also set via TinyMCE API
                        editor.getBody().style.setProperty("color", "#000", "important");
                        editor.getBody().style.setProperty("background-color", "#fff", "important");
                    }
                });
                
                // Also disable the textarea directly but keep it visible
                $("#inventory-shortDescription, #inventory-description").prop("readonly", true).css({
                    "color": "#000",
                    "background-color": "#fff",
                    "opacity": "1",
                    "visibility": "visible"
                });
            }
            
            // Run immediately and after TinyMCE loads
            disableWysiwygEditors();
            if (typeof tinyMCE !== "undefined") {
                tinyMCE.on("AddEditor", function(e) {
                    setTimeout(disableWysiwygEditors, 100);
                });
                // Also listen for init
                $(document).on("tinymce-editor-init", function(event, editor) {
                    if (editor.id === "inventory-shortDescription" || editor.id === "inventory-description") {
                        setTimeout(disableWysiwygEditors, 100);
                    }
                });
            }
            
            // Refresh button handler
            $("#resqwest-refresh-btn").on("click", function() {
                var $btn = $(this);
                var $status = $("#resqwest-refresh-status");
                var postId = $btn.data("post-id");
                var nonce = $btn.data("nonce");
                
                // Disable button and show loading
                $btn.prop("disabled", true);
                $status.html("<span style=\"color: #0073aa;\">Refreshing...</span>");
                
                $.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "resqwest_refresh_page",
                        post_id: postId,
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $status.html("<span style=\"color: #46b450;\">✓ " + response.data.message + "</span>");
                            // Reload page after 1 second to show updated data
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            $status.html("<span style=\"color: #dc3232;\">✗ " + (response.data.message || "Error refreshing") + "</span>");
                            $btn.prop("disabled", false);
                        }
                    },
                    error: function() {
                        $status.html("<span style=\"color: #dc3232;\">✗ Network error occurred</span>");
                        $btn.prop("disabled", false);
                    }
                });
            });
        });
    ');
}

// AJAX handler for single page refresh
add_action( 'wp_ajax_resqwest_refresh_page', 'resQwest_ajax_refresh_page' );

function resQwest_ajax_refresh_page() {
    // Check user permissions
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Get post ID from request
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }

    // Verify nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'resqwest_refresh_' . $post_id)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }

    // Refresh the inventory
    $result = resQwest_refreshSingleInventory($post_id);
    
    if ($result['success']) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}
/**
 * Hook in and add a demo metabox. Can only happen on the 'cmb2_admin_init' or 'cmb2_init' hook.
 */
function resQwest_register_metabox() {
	try {
		// Start with an underscore to hide fields from custom fields list
		$prefix = '_resQwest_';

		/**
		 * Sample metabox to demonstrate each field type included
		 */
		$cmb_demo = new_cmb2_box( array(
			'id'            => $prefix . 'metabox',
			'title'         => __( 'resQwest', 'cmb2' ),
			'object_types'  => array( 'page', 'post' ), // Post type
			// 'show_on_cb' => 'resQwest_show_if_front_page', // function should return a bool value
			// 'context'    => 'normal',
			// 'priority'   => 'high',
			// 'show_names' => true, // Show field names on the left
			// 'cmb_styles' => false, // false to disable the CMB stylesheet
			// 'closed'     => true, // true to keep the metabox closed by default
		) );


	$cmb_demo->add_field( array(
		'name' => __( 'Enable resQwest', 'cmb2' ),
		//'desc' => __( 'field description (optional)', 'cmb2' ),
		'id'   => $prefix . 'enabled',
		'type' => 'checkbox',
	) );

	// Refresh Button - moved to top for easier access
	$cmb_demo->add_field( array(
		'name' => __( 'Actions', 'cmb2' ),
		'desc' => __( 'Refresh inventory data from API', 'cmb2' ),
		'id'   => $prefix . 'refresh_button',
		'type' => 'title',
		'render_row_cb' => function($field_args, $field) {
			try {
				$post_id = isset($field->object_id) ? $field->object_id : (isset($_GET['post']) ? intval($_GET['post']) : 0);
				if (!$post_id && function_exists('get_the_ID')) {
					$post_id = get_the_ID();
				}
				if (!$post_id) {
					global $post;
					$post_id = isset($post->ID) ? $post->ID : 0;
				}
				if ($post_id) {
					$nonce = wp_create_nonce('resqwest_refresh_' . $post_id);
					?>
					<div class="cmb-row cmb-type-title">
						<div class="cmb-th">
							<label><?php echo esc_html($field_args['name']); ?></label>
						</div>
						<div class="cmb-td">
							<p class="cmb2-metabox-description"><?php echo esc_html($field_args['desc']); ?></p>
							<button type="button" id="resqwest-refresh-btn" class="button button-secondary" data-post-id="<?php echo esc_attr($post_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">Refresh Inventory Data</button>
							<span id="resqwest-refresh-status" style="margin-left: 10px;"></span>
						</div>
					</div>
					<?php
				}
			} catch (Exception $e) {
				error_log('resQwest refresh_button render_row_cb error: ' . $e->getMessage());
				// Output a simple fallback
				echo '<div class="cmb-row cmb-type-title"><div class="cmb-th"><label>' . esc_html($field_args['name']) . '</label></div><div class="cmb-td"><p>Error loading refresh button</p></div></div>';
			}
		},
	) );


	//$cmb_demo->add_field( array(
	//	'name'       => __( 'Test Text', 'cmb2' ),
	//	'desc'       => __( 'field description (optional)', 'cmb2' ),
	//	'id'         => $prefix . 'text',
	//	'type'       => 'text',
	//	'show_on_cb' => 'resQwest_hide_if_no_cats', // function should return a bool value
	//	// 'sanitization_cb' => 'my_custom_sanitization', // custom sanitization callback parameter
	//	// 'escape_cb'       => 'my_custom_escaping',  // custom escaping callback parameter
	//	// 'on_front'        => false, // Optionally designate a field to wp-admin only
	//	// 'repeatable'      => true,
	//) );

	$cmb_demo->add_field( array(
		'name' => __( 'Route', 'cmb2' ),
		'desc' => __( '(optional)', 'cmb2' ),
		'id'   => $prefix . 'route',
		'type' => 'text_medium',
		// 'repeatable' => true,
	) );

	$cmb_demo->add_field( array(
		'name' => __( 'InventoryId', 'cmb2' ),
		'desc' => __( '(optional)', 'cmb2' ),
		'id'   => 'resQwest-inventoryId',
		'type' => 'text_medium',
		// 'repeatable' => true,
	) );


	
	add_small_text_field($cmb_demo, 'name');
	add_wysiwyg_field($cmb_demo, 'shortDescription');
	add_wysiwyg_field($cmb_demo, 'description');
	add_medium_text_field($cmb_demo, 'duration');
	add_medium_text_field($cmb_demo, 'operates');
	add_medium_text_field($cmb_demo, 'checkin');
	add_medium_text_field($cmb_demo, 'cost');
	add_medium_text_field($cmb_demo, 'cancelPolicy');
	add_medium_text_field($cmb_demo, 'restrictions');
	add_medium_text_field($cmb_demo, 'bookingNotes');
	// Location field removed - causing display issues

	// Debug Information Section
	$cmb_demo->add_field( array(
		'name' => __( 'Debug Information', 'cmb2' ),
		'desc' => __( 'Information about the last inventory update', 'cmb2' ),
		'id'   => $prefix . 'debug_title',
		'type' => 'title',
	) );

	// Last Update Timestamp
	$cmb_demo->add_field( array(
		'name' => __( 'Last Update', 'cmb2' ),
		'desc' => __( 'When this inventory data was last successfully updated', 'cmb2' ),
		'id'   => $prefix . 'lastUpdate',
		'type' => 'text',
		'attributes' => array(
			'readonly' => 'readonly',
		),
		'default_cb' => function($field_args, $field) {
			try {
				$post_id = isset($field->object_id) ? $field->object_id : (isset($_GET['post']) ? intval($_GET['post']) : 0);
				if (!$post_id && function_exists('get_the_ID')) {
					$post_id = get_the_ID();
				}
			if ($post_id) {
				$value = get_post_meta($post_id, '_resQwest_lastUpdate', true);
				if ($value && is_string($value)) {
					$timestamp = strtotime($value);
					if ($timestamp !== false && $timestamp > 0) {
						$date_format = get_option('date_format');
						$time_format = get_option('time_format');
						if ($date_format && $time_format) {
							return date_i18n($date_format . ' ' . $time_format, $timestamp);
						} else {
							// Fallback to default format
							return date_i18n('Y-m-d H:i:s', $timestamp);
						}
					}
				}
			}
			} catch (Exception $e) {
				error_log('resQwest lastUpdate default_cb error: ' . $e->getMessage());
			}
			return 'Never updated';
		},
	) );

	// Last Update Status
	$cmb_demo->add_field( array(
		'name' => __( 'Update Status', 'cmb2' ),
		'desc' => __( 'Status of the last update attempt', 'cmb2' ),
		'id'   => $prefix . 'lastUpdateStatus',
		'type' => 'select',
		'options' => array(
			'success' => 'Success',
			'error' => 'Error',
			'' => 'Never updated',
		),
		'attributes' => array(
			'readonly' => 'readonly',
			'disabled' => 'disabled',
		),
		'default_cb' => function($field_args, $field) {
			try {
				$post_id = isset($field->object_id) ? $field->object_id : (isset($_GET['post']) ? intval($_GET['post']) : 0);
				if (!$post_id && function_exists('get_the_ID')) {
					$post_id = get_the_ID();
				}
				if ($post_id) {
					$status = get_post_meta($post_id, '_resQwest_lastUpdateStatus', true);
					return $status ? $status : '';
				}
			} catch (Exception $e) {
				error_log('resQwest lastUpdateStatus default_cb error: ' . $e->getMessage());
			}
			return '';
		},
	) );

	// API Response Code
	$cmb_demo->add_field( array(
		'name' => __( 'API Response Code', 'cmb2' ),
		'desc' => __( 'HTTP response code from the last API call', 'cmb2' ),
		'id'   => $prefix . 'apiResponseCode',
		'type' => 'text_small',
		'attributes' => array(
			'readonly' => 'readonly',
		),
		'default_cb' => function($field_args, $field) {
			try {
				$post_id = isset($field->object_id) ? $field->object_id : (isset($_GET['post']) ? intval($_GET['post']) : 0);
				if (!$post_id && function_exists('get_the_ID')) {
					$post_id = get_the_ID();
				}
				if ($post_id) {
					$code = get_post_meta($post_id, '_resQwest_apiResponseCode', true);
					return $code ? $code : 'N/A';
				}
			} catch (Exception $e) {
				error_log('resQwest apiResponseCode default_cb error: ' . $e->getMessage());
			}
			return 'N/A';
		},
	) );

	// Last Error Message
	$cmb_demo->add_field( array(
		'name' => __( 'Last Error', 'cmb2' ),
		'desc' => __( 'Error message from the last failed update attempt', 'cmb2' ),
		'id'   => $prefix . 'lastError',
		'type' => 'textarea',
		'attributes' => array(
			'readonly' => 'readonly',
			'rows' => 3,
		),
		'default_cb' => function($field_args, $field) {
			try {
				$post_id = isset($field->object_id) ? $field->object_id : (isset($_GET['post']) ? intval($_GET['post']) : 0);
				if (!$post_id && function_exists('get_the_ID')) {
					$post_id = get_the_ID();
				}
				if ($post_id) {
					$error = get_post_meta($post_id, '_resQwest_lastError', true);
					return $error ? $error : 'No errors';
				}
			} catch (Exception $e) {
				error_log('resQwest lastError default_cb error: ' . $e->getMessage());
			}
			return 'No errors';
		},
	) );
	} catch (Exception $e) {
		error_log('resQwest_register_metabox error: ' . $e->getMessage());
		error_log('Stack trace: ' . $e->getTraceAsString());
	}
}

function add_wysiwyg_field($cmb_demo, $fieldname) {
    // Use WYSIWYG for formatted display, but disable editing
    $cmb_demo->add_field( array(
		'name' => $fieldname,
		'desc' => 'The inventory ' . $fieldname . ' field loaded from resQwest (read-only)',
		'id' => 'inventory-' . $fieldname,
		'type' => 'wysiwyg',
		'options' => array(
			'media_buttons' => false,
			'textarea_rows' => 8,
			'teeny' => false,
			'quicktags' => false,
			'tinymce' => array(
				'toolbar1' => '',
				'toolbar2' => '',
				'toolbar3' => '',
				'toolbar4' => '',
				'readonly' => 1,
				'content_style' => 'body { color: #000 !important; background: #fff !important; }',
			),
		),
		'attributes' => array(
			'readonly' => 'readonly',
		),
	) );
}

function add_small_text_field($cmb_demo, $fieldname) {
    $cmb_demo->add_field( array(
		'name' => $fieldname,
		'desc' => 'The inventory ' . $fieldname . ' field loaded from resQwest',
		'id' => 'inventory-' . $fieldname,
		'type' => 'text_small',
		'attributes' => array(
			'readonly' => 'readonly',
		),
	) );
}
function add_medium_text_field($cmb_demo, $fieldname) {
    $cmb_demo->add_field( array(
		'name' => $fieldname,
		'desc' => 'The inventory ' . $fieldname . ' field loaded from resQwest',
		'id' => 'inventory-' . $fieldname,
		'type' => 'text_medium',
		'attributes' => array(
			'readonly' => 'readonly',
		),
		'sanitization_cb' => function($value, $field_args, $field) {
			// Ensure value is always a string
			if (is_object($value) || is_array($value)) {
				return json_encode($value);
			}
			return is_string($value) ? $value : (string)$value;
		},
		'default_cb' => function($field_args, $field) {
			try {
				$post_id = isset($field->object_id) ? $field->object_id : (isset($_GET['post']) ? intval($_GET['post']) : 0);
				if (!$post_id && function_exists('get_the_ID')) {
					$post_id = get_the_ID();
				}
				if ($post_id) {
					$field_id = isset($field_args['id']) ? $field_args['id'] : 'inventory-' . (isset($field_args['name']) ? $field_args['name'] : '');
					$value = get_post_meta($post_id, $field_id, true);
					// Ensure value is always a string
					if (is_object($value) || is_array($value)) {
						return json_encode($value);
					}
					return is_string($value) ? $value : (string)$value;
				}
			} catch (Exception $e) {
				error_log('resQwest ' . (isset($field_args['name']) ? $field_args['name'] : 'unknown') . ' default_cb error: ' . $e->getMessage());
			}
			return '';
		},
	) );
}
?>