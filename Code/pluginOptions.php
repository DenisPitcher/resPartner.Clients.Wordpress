<?php
/**
 * resQwest Options
 * @version 0.1.0
 */
class resQwest_Admin {

	/**
 	 * Option key, and option page slug
 	 * @var string
 	 */
	private $key = 'resQwest_options';

	/**
 	 * Options page metabox id
 	 * @var string
 	 */
	private $metabox_id = 'resQwest_option_metabox';

	/**
	 * Options Page title
	 * @var string
	 */
	protected $title = '';

	/**
	 * Options Page hook
	 * @var string
	 */
	protected $options_page = '';

	/**
	 * Holds an instance of the object
	 *
	 * @var resQwest_Admin
	 **/
	private static $instance = null;

	/**
	 * Constructor
	 * @since 0.1.0
	 */
	private function __construct() {
		// Set our title
		$this->title = __( 'resQwest', 'resQwest' );
	}

	/**
	 * Returns the running object
	 *
	 * @return resQwest_Admin
	 **/
	public static function get_instance() {
		if( is_null( self::$instance ) ) {
			self::$instance = new resQwest_Admin();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Initiate our hooks
	 * @since 0.1.0
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_options_page' ) );
		add_action( 'cmb2_admin_init', array( $this, 'add_options_page_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_refresh_scripts' ) );
	}

	/**
	 * Enqueue scripts for refresh functionality
	 * @since 0.1.0
	 */
	public function enqueue_refresh_scripts($hook) {
		// Only load on our options page
		if ($hook !== $this->options_page) {
			return;
		}
		
		wp_enqueue_script('jquery');
		wp_add_inline_script('jquery', '
			jQuery(document).ready(function($) {
				$("#resqwest-refresh-all-btn").on("click", function() {
					var $btn = $(this);
					var $status = $("#resqwest-refresh-all-status");
					var $progress = $("#resqwest-refresh-all-progress");
					var nonce = $btn.data("nonce");
					
					// Disable button and show loading
					$btn.prop("disabled", true);
					$status.html("<span style=\"color: #0073aa;\">Refreshing all inventory...</span>");
					$progress.html("<p>Starting refresh...</p>");
					
					$.ajax({
						url: ajaxurl,
						type: "POST",
						data: {
							action: "resqwest_refresh_all",
							nonce: nonce
						},
						success: function(response) {
							if (response.success) {
								var data = response.data || {};
								var message = data.message || "Refresh completed";
								var pagesProcessed = data.pages_processed || 0;
								var pagesUpdated = data.pages_updated || 0;
								var pagesCreated = data.pages_created || 0;
								var pagesErrored = data.pages_errored || 0;
								var duration = data.duration || 0;
								var responseCode = data.response_code || 'N/A';
								var errors = data.errors || [];
								
								$status.html("<span style=\"color: #46b450;\">✓ " + message + "</span>");
								var progressHtml = "<div style=\"background: #f0f0f0; padding: 10px; border-radius: 4px; margin-top: 10px;\">";
								progressHtml += "<p><strong>Summary:</strong></p>";
								progressHtml += "<ul style=\"margin: 5px 0;\">";
								progressHtml += "<li>Pages Processed: <strong>" + pagesProcessed + "</strong></li>";
								progressHtml += "<li>Pages Updated: <strong>" + pagesUpdated + "</strong></li>";
								progressHtml += "<li>Pages Created: <strong>" + pagesCreated + "</strong></li>";
								if (pagesErrored > 0) {
									progressHtml += "<li style=\"color: #dc3232;\">Pages Errored: <strong>" + pagesErrored + "</strong></li>";
								}
								progressHtml += "<li>Duration: <strong>" + duration + " seconds</strong></li>";
								progressHtml += "<li>API Response Code: <strong>" + responseCode + "</strong></li>";
								progressHtml += "</ul>";
								if (errors.length > 0) {
									progressHtml += "<p style=\"color: #dc3232; margin-top: 10px;\"><strong>Errors:</strong></p><ul style=\"color: #dc3232;\">";
									errors.forEach(function(error) {
										progressHtml += "<li>" + error + "</li>";
									});
									progressHtml += "</ul>";
								}
								progressHtml += "<p style=\"margin-top: 10px; font-size: 11px; color: #666;\">Page will refresh in 3 seconds to show updated debug information...</p>";
								progressHtml += "</div>";
								$progress.html(progressHtml);
								$btn.prop("disabled", false);
								
								// Reload page after 3 seconds to show updated debug fields
								setTimeout(function() {
									location.reload();
								}, 3000);
							} else {
								var data = response.data || {};
								var message = data.message || "Error refreshing";
								var errors = data.errors || [];
								var responseCode = data.response_code || 'N/A';
								var duration = data.duration || 0;
								
								$status.html("<span style=\"color: #dc3232;\">✗ " + message + "</span>");
								var progressHtml = "<div style=\"background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px; border-left: 4px solid #dc3232;\">";
								progressHtml += "<p style=\"color: #dc3232; margin: 0 0 10px 0;\"><strong>" + message + "</strong></p>";
								if (responseCode !== 'N/A') {
									progressHtml += "<p>API Response Code: <strong>" + responseCode + "</strong></p>";
								}
								if (duration > 0) {
									progressHtml += "<p>Duration: <strong>" + duration + " seconds</strong></p>";
								}
								if (errors.length > 0) {
									progressHtml += "<p style=\"margin-top: 10px;\"><strong>Errors:</strong></p><ul>";
									errors.forEach(function(error) {
										progressHtml += "<li>" + error + "</li>";
									});
									progressHtml += "</ul>";
								}
								progressHtml += "</div>";
								$progress.html(progressHtml);
								$btn.prop("disabled", false);
							}
						},
						error: function() {
							$status.html("<span style=\"color: #dc3232;\">✗ Network error occurred</span>");
							$progress.html("<p style=\"color: #dc3232;\">Network error occurred</p>");
							$btn.prop("disabled", false);
						}
					});
				});
			});
		');
	}


	/**
	 * Register our setting to WP
	 * @since  0.1.0
	 */
	public function init() {
		register_setting( $this->key, $this->key );
	}

	/**
	 * Add menu options page
	 * @since 0.1.0
	 */
	public function add_options_page() {
		$this->options_page = add_menu_page( $this->title, $this->title, 'manage_options', $this->key, array( $this, 'admin_page_display' ) );
		
		// Add error log submenu
		add_submenu_page( $this->key, 'Error Log', 'Error Log', 'manage_options', 'resqwest-error-log', array( $this, 'error_log_page_display' ) );

		// Include CMB CSS in the head to avoid FOUC
		add_action( "admin_print_styles-{$this->options_page}", array( 'CMB2_hookup', 'enqueue_cmb_css' ) );
	}
	
	/**
	 * Display error log page
	 * @since 0.1.0
	 */
	public function error_log_page_display() {
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		$log_exists = file_exists($debug_log_path);
		$log_content = '';
		$log_size = 0;
		$max_lines = 500; // Show last 500 lines
		
		if ($log_exists) {
			$log_size = filesize($debug_log_path);
			$log_size_mb = round($log_size / 1024 / 1024, 2);
			
			// Read last N lines of the log file
			if ($log_size > 0) {
				$lines = file($debug_log_path);
				if ($lines !== false) {
					$total_lines = count($lines);
					$start_line = max(0, $total_lines - $max_lines);
					$log_content = implode('', array_slice($lines, $start_line));
					
					if ($total_lines > $max_lines) {
						$log_content = "<!-- Showing last {$max_lines} of {$total_lines} lines -->\n" . $log_content;
					}
				}
			}
		}
		
		// Check for resQwest-specific errors
		$resqwest_errors = array();
		if ($log_content) {
			$all_lines = explode("\n", $log_content);
			foreach ($all_lines as $line) {
				if (stripos($line, 'resQwest') !== false || stripos($line, 'resqwest') !== false) {
					$resqwest_errors[] = $line;
				}
			}
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			
			<div class="notice notice-info">
				<p><strong>Debug Log Location:</strong> <code><?php echo esc_html($debug_log_path); ?></code></p>
				<?php if ($log_exists): ?>
					<p><strong>File Size:</strong> <?php echo esc_html($log_size_mb); ?> MB</p>
				<?php else: ?>
					<p><strong>Status:</strong> Debug log file not found. Enable WP_DEBUG_LOG in wp-config.php to create it.</p>
				<?php endif; ?>
			</div>
			
			<?php if (!empty($resqwest_errors)): ?>
			<div class="notice notice-warning">
				<h2>resQwest-Related Errors (<?php echo count($resqwest_errors); ?>)</h2>
				<div style="background: #fff; border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px;">
					<?php foreach ($resqwest_errors as $error): ?>
						<div style="margin-bottom: 5px; color: #dc3232;"><?php echo esc_html($error); ?></div>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>
			
			<h2>Full Debug Log</h2>
			<?php if ($log_exists && $log_content): ?>
				<div style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 4px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; line-height: 1.5;">
					<pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php echo esc_html($log_content); ?></pre>
				</div>
				<p>
					<button type="button" class="button" onclick="location.reload();">Refresh</button>
					<?php if (current_user_can('manage_options')): ?>
						<button type="button" class="button button-secondary" onclick="if(confirm('Clear the debug log? This cannot be undone.')) { window.location.href='?page=resqwest-error-log&action=clear_log&_wpnonce=<?php echo wp_create_nonce('clear_debug_log'); ?>'; }">Clear Log</button>
					<?php endif; ?>
				</p>
			<?php elseif ($log_exists && $log_size == 0): ?>
				<p>Debug log file exists but is empty.</p>
			<?php else: ?>
				<p>No debug log file found. To enable logging, add these lines to your wp-config.php:</p>
				<pre style="background: #f0f0f0; padding: 10px; border: 1px solid #ccc;">define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);</pre>
			<?php endif; ?>
		</div>
		<?php
		
		// Handle log clearing
		if (isset($_GET['action']) && $_GET['action'] === 'clear_log' && isset($_GET['_wpnonce'])) {
			if (wp_verify_nonce($_GET['_wpnonce'], 'clear_debug_log') && current_user_can('manage_options')) {
				if ($log_exists && is_writable($debug_log_path)) {
					file_put_contents($debug_log_path, '');
					echo '<div class="notice notice-success"><p>Debug log cleared successfully.</p></div>';
					echo '<script>setTimeout(function(){location.href="?page=resqwest-error-log";}, 1000);</script>';
				}
			}
		}
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 * @since  0.1.0
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page <?php echo $this->key; ?>">
			<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
			<?php cmb2_metabox_form( $this->metabox_id, $this->key ); ?>
		</div>
		<?php
	}

	/**
	 * Add the options metabox to the array of metaboxes
	 * @since  0.1.0
	 */
	function add_options_page_metabox() {

		// hook in our save notices
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );

		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'show_on'    => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

        $cmb->add_field( array(
            'name' => 'Enable resQwest Page Loading',
            'desc' => 'enable support for pages to be created matching resQwest config',
            'id'   => 'resQwest_enablePageLoading',
            'type' => 'checkbox'
		) );
		
		$cmb->add_field( array(
            'name' => 'Create categories according to resQwest',
            'desc' => 'create categories and assign them to the associated pages',
            'id'   => 'resQwest_enableCategoryLoading',
            'type' => 'checkbox'
        ) );

		$cmb->add_field( array(
			'name' => __( 'API Id:', 'resQwest' ),
			'desc' => __( 'resQwest API Id', 'resQwest' ),
			'id'   => 'resQwest_apiId',
			'type' => 'text',
		) );
        $cmb->add_field( array(
			'name' => __( 'API Key:', 'resQwest' ),
			'desc' => __( 'resQwest API Key', 'resQwest' ),
			'id'   => 'resQwest_apiKey',
			'type' => 'text',
		) );
        
        // $cmb->add_field( array(
        //     'name' => 'Hide sidebar on reservation pages',
        //     'desc' => 'use a custom page template that ignores the sidebar',
        //     'id'   => 'resQwest_hideSidebar',
        //     'type' => 'checkbox'
		// ) );
		
		// Refresh All Inventory Button
		$cmb->add_field( array(
			'name' => __( 'Refresh All Inventory', 'resQwest' ),
			'desc' => __( 'Refresh all inventory pages from resQwest API', 'resQwest' ),
			'id'   => 'resQwest_refresh_all',
			'type' => 'title',
			'after_field' => function($args, $field) {
				$nonce = wp_create_nonce('resqwest_refresh_all');
				return '<button type="button" id="resqwest-refresh-all-btn" class="button button-primary" data-nonce="' . esc_attr($nonce) . '">Refresh All Inventory</button><span id="resqwest-refresh-all-status" style="margin-left: 10px;"></span><div id="resqwest-refresh-all-progress" style="margin-top: 10px;"></div>';
			},
		) );

		// Debug Information Section for Global Refresh
		$cmb->add_field( array(
			'name' => __( 'Global Refresh Debug Information', 'resQwest' ),
			'desc' => __( 'Information about the last sitewide inventory refresh', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_debug_title',
			'type' => 'title',
		) );

		// Last Update Timestamp
		$cmb->add_field( array(
			'name' => __( 'Last Refresh', 'resQwest' ),
			'desc' => __( 'When the sitewide inventory was last refreshed', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_lastUpdate',
			'type' => 'text',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$value = resQwest_get_option('resQwest_globalRefresh_lastUpdate');
					if ($value && is_string($value)) {
						$timestamp = strtotime($value);
						if ($timestamp !== false && $timestamp > 0) {
							$date_format = get_option('date_format');
							$time_format = get_option('time_format');
							if ($date_format && $time_format) {
								return date_i18n($date_format . ' ' . $time_format, $timestamp);
							} else {
								return date_i18n('Y-m-d H:i:s', $timestamp);
							}
						}
					}
				} catch (Exception $e) {
					error_log('resQwest globalRefresh lastUpdate default_cb error: ' . $e->getMessage());
				}
				return 'Never refreshed';
			},
		) );

		// Update Status
		$cmb->add_field( array(
			'name' => __( 'Refresh Status', 'resQwest' ),
			'desc' => __( 'Status of the last sitewide refresh attempt', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_lastUpdateStatus',
			'type' => 'select',
			'options' => array(
				'success' => 'Success',
				'error' => 'Error',
				'' => 'Never refreshed',
			),
			'attributes' => array(
				'readonly' => 'readonly',
				'disabled' => 'disabled',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$status = resQwest_get_option('resQwest_globalRefresh_lastUpdateStatus');
					return $status ? $status : '';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh lastUpdateStatus default_cb error: ' . $e->getMessage());
				}
				return '';
			},
		) );

		// API Response Code
		$cmb->add_field( array(
			'name' => __( 'API Response Code', 'resQwest' ),
			'desc' => __( 'HTTP response code from the last API call', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_apiResponseCode',
			'type' => 'text_small',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$code = resQwest_get_option('resQwest_globalRefresh_apiResponseCode');
					return $code ? $code : 'N/A';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh apiResponseCode default_cb error: ' . $e->getMessage());
				}
				return 'N/A';
			},
		) );

		// Duration
		$cmb->add_field( array(
			'name' => __( 'Duration', 'resQwest' ),
			'desc' => __( 'Time taken to complete the refresh (seconds)', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_duration',
			'type' => 'text_small',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$duration = resQwest_get_option('resQwest_globalRefresh_duration');
					return $duration ? $duration . ' seconds' : 'N/A';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh duration default_cb error: ' . $e->getMessage());
				}
				return 'N/A';
			},
		) );

		// Pages Processed
		$cmb->add_field( array(
			'name' => __( 'Pages Processed', 'resQwest' ),
			'desc' => __( 'Total number of inventory items processed', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_pagesProcessed',
			'type' => 'text_small',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$count = resQwest_get_option('resQwest_globalRefresh_pagesProcessed');
					return $count ? $count : '0';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh pagesProcessed default_cb error: ' . $e->getMessage());
				}
				return '0';
			},
		) );

		// Pages Updated
		$cmb->add_field( array(
			'name' => __( 'Pages Updated', 'resQwest' ),
			'desc' => __( 'Number of existing pages that were updated', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_pagesUpdated',
			'type' => 'text_small',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$count = resQwest_get_option('resQwest_globalRefresh_pagesUpdated');
					return $count ? $count : '0';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh pagesUpdated default_cb error: ' . $e->getMessage());
				}
				return '0';
			},
		) );

		// Pages Created
		$cmb->add_field( array(
			'name' => __( 'Pages Created', 'resQwest' ),
			'desc' => __( 'Number of new pages that were created', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_pagesCreated',
			'type' => 'text_small',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$count = resQwest_get_option('resQwest_globalRefresh_pagesCreated');
					return $count ? $count : '0';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh pagesCreated default_cb error: ' . $e->getMessage());
				}
				return '0';
			},
		) );

		// Pages Errored
		$cmb->add_field( array(
			'name' => __( 'Pages Errored', 'resQwest' ),
			'desc' => __( 'Number of pages that failed to process', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_pagesErrored',
			'type' => 'text_small',
			'attributes' => array(
				'readonly' => 'readonly',
			),
			'default_cb' => function($field_args, $field) {
				try {
					$count = resQwest_get_option('resQwest_globalRefresh_pagesErrored');
					return $count ? $count : '0';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh pagesErrored default_cb error: ' . $e->getMessage());
				}
				return '0';
			},
		) );

		// Last Error Message
		$cmb->add_field( array(
			'name' => __( 'Last Error', 'resQwest' ),
			'desc' => __( 'Error message from the last failed refresh attempt', 'resQwest' ),
			'id'   => 'resQwest_globalRefresh_lastError',
			'type' => 'textarea',
			'attributes' => array(
				'readonly' => 'readonly',
				'rows' => 3,
			),
			'default_cb' => function($field_args, $field) {
				try {
					$error = resQwest_get_option('resQwest_globalRefresh_lastError');
					return $error ? $error : 'No errors';
				} catch (Exception $e) {
					error_log('resQwest globalRefresh lastError default_cb error: ' . $e->getMessage());
				}
				return 'No errors';
			},
		) );
        
	}

	/**
	 * Register settings notices for display
	 *
	 * @since  0.1.0
	 * @param  int   $object_id Option key
	 * @param  array $updated   Array of updated fields
	 * @return void
	 */
	public function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		add_settings_error( $this->key . '-notices', '', __( 'Settings updated.', 'resQwest' ), 'updated' );
		settings_errors( $this->key . '-notices' );
	}

	/**
	 * Public getter method for retrieving protected/private variables
	 * @since  0.1.0
	 * @param  string  $field Field to retrieve
	 * @return mixed          Field value or exception is thrown
	 */
	public function __get( $field ) {
		// Allowed fields to retrieve
		if ( in_array( $field, array( 'key', 'metabox_id', 'title', 'options_page' ), true ) ) {
			return $this->{$field};
		}

		throw new Exception( 'Invalid property: ' . $field );
	}

}

/**
 * Helper function to get/return the resQwest_Admin object
 * @since  0.1.0
 * @return resQwest_Admin object
 */
function resQwest_admin() {
	return resQwest_Admin::get_instance();
}

/**
 * Wrapper function around cmb2_get_option
 * @since  0.1.0
 * @param  string  $key Options array key
 * @return mixed        Option value
 */
function resQwest_get_option( $key = '' ) {
	return cmb2_get_option( resQwest_admin()->key, $key );
}

function resQwest_update_option( $key, $value, $single = true ) {
	return cmb2_update_option( resQwest_admin()->key, $key, $value, $single );
}

// AJAX handler for global refresh
add_action( 'wp_ajax_resqwest_refresh_all', 'resQwest_ajax_refresh_all' );

function resQwest_ajax_refresh_all() {
	// Check user permissions
	if (!current_user_can('manage_options')) {
		wp_send_json_error(array('message' => 'Insufficient permissions'));
		return;
	}

	// Verify nonce for security
	if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'resqwest_refresh_all')) {
		wp_send_json_error(array('message' => 'Security check failed'));
		return;
	}

	// Refresh all inventory
	$result = resQwest_loadInventory();
	
	// Get debug information from options
	$lastUpdate = resQwest_get_option('resQwest_globalRefresh_lastUpdate');
	$lastStatus = resQwest_get_option('resQwest_globalRefresh_lastUpdateStatus');
	$lastError = resQwest_get_option('resQwest_globalRefresh_lastError');
	$responseCode = resQwest_get_option('resQwest_globalRefresh_apiResponseCode');
	$duration = resQwest_get_option('resQwest_globalRefresh_duration');
	$pagesProcessed = resQwest_get_option('resQwest_globalRefresh_pagesProcessed');
	$pagesUpdated = resQwest_get_option('resQwest_globalRefresh_pagesUpdated');
	$pagesCreated = resQwest_get_option('resQwest_globalRefresh_pagesCreated');
	$pagesErrored = resQwest_get_option('resQwest_globalRefresh_pagesErrored');
	
	if ($result === true) {
		wp_send_json_success(array(
			'message' => 'All inventory refreshed successfully',
			'last_update' => $lastUpdate,
			'status' => $lastStatus,
			'response_code' => $responseCode,
			'duration' => $duration,
			'pages_processed' => $pagesProcessed,
			'pages_updated' => $pagesUpdated,
			'pages_created' => $pagesCreated,
			'pages_errored' => $pagesErrored,
			'errors' => $lastError ? array($lastError) : array()
		));
	} else {
		wp_send_json_error(array(
			'message' => 'Failed to refresh inventory',
			'last_update' => $lastUpdate,
			'status' => $lastStatus,
			'response_code' => $responseCode,
			'duration' => $duration,
			'pages_processed' => $pagesProcessed,
			'pages_updated' => $pagesUpdated,
			'pages_created' => $pagesCreated,
			'pages_errored' => $pagesErrored,
			'errors' => $lastError ? array($lastError) : array('Unable to load inventory from API')
		));
	}
}

// Get it started
resQwest_admin();

?>