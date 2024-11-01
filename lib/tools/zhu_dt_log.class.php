<?php
defined( 'ABSPATH' ) or header( "Location: /" );

define( 'ZHUDT_LOG_GROUP', 'zhudt_logging_group' );
define( 'ZHUDT_LOG_OPTIONS', 'zhudt_logging_options' );

//individual settings within ZHUDT_LOG_OPTIONS
define( 'ZHUDT_LOGOPT_ENABLED', 'enabled' );
define( 'ZHUDT_LOGOPT_URL_CONTAINS', 'url_contains' );
define( 'ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION', 'del_table_on_deactivate' );

define( 'ZHUDT_LOG_ACTION_TRUNCATE', 'zhu_log_trucate' );

/**
 * Main class for the Log pluggable
 * 
 * This class provides the main method to add records to the log and truncate the entire log.  
 * It also generates a settings meta box for inclusion in this plugin's general settings page.
 * 
 * @since 1.0.0
 * 
 * @author David Pullin
 */
class zhu_dt_log implements zhu_dt_pluggable_interface {

	/**
	 * Implements interface on_plugin_activation() method.  Called when Zhu Dev Tool's plugin is activated
	 * 
	 * This method creates the database table to store log entries
	 * 
	 * @since   1.0.0
	 */
	public static function on_plugin_activation() {
		self::create_database_table();
	}

	/**
	 * Implements interface on_plugin_deactivation() method.  Called when Zhu Dev Tool's plugin is deactivated
	 * 
	 * This method removes the database (depending on settings)
	 * 
	 * @since   1.0.0
	 */
	public static function on_plugin_deactivation() {
		$opts = self::get_logging_options();

		if ( $opts[ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION] ) {
			self::remove_database_table();
		}
	}

	/**
	 * Implements interface on_plugins_loaded() method.  Called when WordPress has loaded all plugins
	 * 
	 * No action currently required
	 * 
	 * @since   1.0.0
	 */
	public static function on_plugins_loaded() {
		
	}

	/**
	 * Implements interface on_init() method.  Called when WordPress invoked Zhu Dev Tools plugin's 'init' action
	 * 
	 * No action currently required
	 * 
	 * @since   1.0.0
	 */
	public static function on_init() {
//no action required 
	}

	/**
	 * Implements interface on_admin_init() method to configure admin support
	 * 
	 * Queues required CSS and JavaScript files, View Log menu and registers ajax support for log truncation option
	 * 
	 * @since   1.0.0
	 */
	public static function on_admin_init() {
		self::register_settting();

		add_action( 'admin_enqueue_scripts', array( ZHU_DT_LOG_CLASS_NAME, 'add_stylesheet' ) );
		add_action( 'admin_enqueue_scripts', array( ZHU_DT_LOG_CLASS_NAME, 'add_javascript' ) );

		add_action( 'zhu_dt_generate_admin_menu', array( ZHU_DT_LOG_CLASS_NAME, 'on_zhu_dt_generate_admin_menu' ) );

		// Setup Ajax action hook
		add_action( 'wp_ajax_zhu_dt_log_truncate', array( ZHU_DT_LOG_CLASS_NAME, 'zhu_dt_log_truncate' ) );
	}

	/**
	 * Implements interface method add_general_options_meta_boxes().  
	 * 
	 * Adds a meta for log settings and the ability to truncate the log on demand
	 * 
	 * @since 1.0.0
	 * 
	 * @see https://www.sitepoint.com/how-to-use-ajax-in-wordpress-a-real-world-example/
	 * 
	 * @param string|array|WP_Screen    $screen     The general options screen 
	 */
	public static function add_general_options_meta_boxes( $screen ) {
		add_meta_box( 'zhudt_mb_log_opts', __( 'Logging Options', 'zhu_dt_domain' ), array( ZHU_DT_LOG_CLASS_NAME, 'render_logging_options_meta_box' ),
			$screen, 'side', 'high' );
	}

	/**
	 * Registers and queues required CSS file to support the log viewer
	 * 
	 * @since 1.0.0
	 */
	public static function add_stylesheet() {
		wp_register_style(
			'zhu_dt_log_viewer',
			plugins_url( 'zhu-dev-tools/css/zhu_dt_log_viewer.css' )
		);
		wp_enqueue_style( 'zhu_dt_log_viewer' );
	}

	/**
	 * Registers and queues required JavaScript file to support the log viewer
	 * 
	 * @since 1.0.0
	 */
	public static function add_javascript() {
		wp_register_script( 'zhu_dt_log_admin_js', plugins_url( 'zhu-dev-tools/js/zhu_dt_log_admin.js' ), array( 'jquery' ), '1.1.0' );

		wp_localize_script( 'zhu_dt_log_admin_js', 'zhu_dt_log_admin_local',
			array( 'ajax_url'		 => admin_url( 'admin-ajax.php' ),
				'are_you_sure'	 => __( 'Are you sure you wish to truncate the log?', 'zhu_dt_domain' ),
				'truncating'	 => __( 'truncating', 'zhu_dt_domain' ),
				'nonce'			 => wp_create_nonce( ZHUDT_LOG_ACTION_TRUNCATE )
			)
		);

		wp_enqueue_script( 'zhu_dt_log_admin_js' );
	}

	/**
	 * Method invoked via Ajax when user presses the Truncate button.
	 * 
	 * @since 1.0.0
	 * 
	 * 2021.01.04   1.0.1   Added nonce for security purposes
	 * 
	 * @global wpdb $wpdb   Used for database access
	 */
	public static function zhu_dt_log_truncate() {
		global $wpdb;
		/** @var wpdb $wpdb */
		$table_name = self::get_name_of_log_table();

		$nonce = (array_key_exists( 'nonce', $_POST )) ? sanitize_text_field( $_POST['nonce'] ) : null;
		if ( $nonce == null || wp_verify_nonce( $nonce, ZHUDT_LOG_ACTION_TRUNCATE ) == false ) {
			_e( 'The link you followed has expired.', 'zhu_dt_domain' );
		} else {
			$res		 = $wpdb->get_results( "SELECT COUNT(*) AS 'county' FROM {$table_name}" );
			$row_count	 = $res[0]->county;

			if ( $row_count ) {
				$wpdb->query( "TRUNCATE TABLE {$table_name}" );
			}

			echo('1|'); //marker to JavaScript to indicate success
			printf(
				_n(
					'%d row deleted',
					'%d rows deleted',
					$row_count,
					'zhu_dt_domain'
				),
				number_format_i18n( $row_count )
			);
		}

		//terminate now,  this was an ajax call and we are all done, also we don't want admin-ajax.php to echo anything else.
		exit;
	}

	/**
	 * Added a 'View Log' sub-menu to the main Zhu Dev Tools menu
	 * 
	 * @param string    $parentScreenID The slug name of the parent menu
	 */
	public static function on_zhu_dt_generate_admin_menu( $parentScreenID ) {
		add_submenu_page( $parentScreenID, 'View Log', 'View Log', 'manage_options', 'zhu_dt_log_view_log', array( ZHU_DT_LOG_CLASS_NAME, 'render_view_log_page' ) );
	}

	/**
	 * Creates the log table and supporting indexes if they currently do not exist.
	 * 
	 * @since 1.0.0
	 * 
	 * @global wpdb $wpdb
	 */
	private static function create_database_table() {
		global $wpdb;
		/** @var wpdb $wpdb */
		$charset_collate = $wpdb->get_charset_collate();
		$table_name		 = self::get_name_of_log_table();

		$query1 = <<<sql
            CREATE TABLE IF NOT EXISTS {$table_name} (
                ID bigint unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                log_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                log_date_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                log_content longtext NOT NULL
            ) ENGINE=InnoDB {$charset_collate}
sql;

		$wpdb->query( $query1 );

		//Create index
		$res		 = $wpdb->get_results( "SELECT COUNT(1) as 'county' FROM INFORMATION_SCHEMA.STATISTICS
                WHERE table_schema=DATABASE() AND table_name='{$table_name}' AND index_name='AKZhu_log_log_date_IDX'" );
		$idx_count	 = $res[0]->county;

		if ( $idx_count == 0 ) {
			$wpdb->query( "CREATE INDEX AKZhu_log_log_date_IDX USING BTREE ON {$table_name} (log_date)" );
		}
	}

	/**
	 * Returns the name of the database table use for the log
	 * 
	 * Note: the database table is prefixed by the WordPress database table's base_preix
	 * 
	 * @global wpdb $wpdb   Used to access WordPress database details
	 * 
	 * @return string
	 */
	private static function get_name_of_log_table() {
		global $wpdb;
		/** @var wpdb $wpdb */
		return $wpdb->base_prefix . 'zhu_log';
	}

	/**
	 * Drops the log table from the database
	 * 
	 * @global wpdb $wpdb   Used to access WordPress database operations
	 */
	private static function remove_database_table() {
		global $wpdb;
		/** @var wpdb $wpdb */
		$table_name = self::get_name_of_log_table();

		$wpdb->query( "DROP TABLE {$table_name}" );
	}

	/**
	 * Adds an entry to the log table.  This method should not be called directly.
	 * 
	 * DO NOT CALL THIS METHOD DIRECTLY.  Use the global function zhu_log().  
	 * This is to ensure that existing code calling zhu_log() does not error if this tool is disabled.
	 * 
	 * @global wpdb $wpdb
	 * @staticvar array $opts       Cache holding result of self::get_logging_options() to avoid unnecessary multiple processing/database access
	 * 
	 * @param string    $content    Text to write to log
	 */
	public static function add( string $content ) {
		global $wpdb;
		/** @var wpdb $wpdb */
		static $opts = null;

		if ( $opts == null ) {
			$opts = self::get_logging_options();
		}

		if ( $opts[ZHUDT_LOGOPT_ENABLED] ) {
			if ( $opts[ZHUDT_LOGOPT_URL_CONTAINS] == '' || ( array_key_exists( 'HTTP_REFERER', $_SERVER ) && stripos( $_SERVER['HTTP_REFERER'], $opts[ZHUDT_LOGOPT_URL_CONTAINS] ) !== false) ) {
				$now = current_time( 'mysql' );

				$table_name = self::get_name_of_log_table();

				$wpdb->insert( $table_name,
					array(
						'log_date'		 => $now,
						'log_date_gmt'	 => get_gmt_from_date( $now ),
						'log_content'	 => $content
					),
					array(
						'%s',
						'%s',
						'%s' )
				);
			}
		}
	}

	/**
	 * Register this tool's meta box settings with the WordPress framework
	 *
	 * @since 1.0.0
	 */
	private static function register_settting() {

		//Register here as may be required by options.php when form posts to save updated settings
		register_setting( ZHUDT_LOG_GROUP, ZHUDT_LOG_OPTIONS,
			array(
				'type'				 => 'array',
				'description'		 => 'Zhu Dev Tools Log Options',
				'sanitize_callback'	 => array( ZHU_DT_LOG_CLASS_NAME, 'sanitize_log_options' )
			)
		);
	}

	/**
	 * Sanitize settings posted from the log options meta box as processed via the WordPress framework
	 * 
	 * @since   1.0.0
	 * 
	 * @param array     $input    
	 * 
	 * @return array    Sanitized settings
	 */
	public static function sanitize_log_options( $input ) {

		$new_input = array();

		$new_input[ZHUDT_LOGOPT_ENABLED]					 = (isset( $input[ZHUDT_LOGOPT_ENABLED] ) && strcasecmp( 'on', $input[ZHUDT_LOGOPT_ENABLED] ) == 0) ? 'on' : '';
		$new_input[ZHUDT_LOGOPT_URL_CONTAINS]				 = isset( $input[ZHUDT_LOGOPT_URL_CONTAINS] ) ? sanitize_text_field( $input[ZHUDT_LOGOPT_URL_CONTAINS] ) : null;
		$new_input[ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION]	 = (isset( $input[ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION] ) && strcasecmp( 'on', $input[ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION] ) == 0) ? 'on' : '';


		return $new_input;
	}

	/**
	 * Return meta box options
	 * 
	 * @since 1.0.0
	 * 
	 * @param bool $allowDefaults   If set, populate default settings if not already present
	 * @param bool $getDefaultsOnly If set, ignore settings stored in WordPress's options and only return defaults
	 * @return array    Array of settings
	 */
	public static function get_logging_options( $allowDefaults = true, $getDefaultsOnly = false ) {
		if ( $getDefaultsOnly ) {
			$opts = array();
		} else {
			$opts = get_option( ZHUDT_LOG_OPTIONS );

			if ( $opts == null ) {
				$opts = array();
			}
		}

		// Add defaults if setting not present 
		if ( $allowDefaults || $getDefaultsOnly ) {

			// ensure we have entries for all expected settings, even if there is no default value
			if ( ! array_key_exists( ZHUDT_LOGOPT_ENABLED, $opts ) ) {
				$opts[ZHUDT_LOGOPT_ENABLED] = false;
			}

			if ( ! array_key_exists( ZHUDT_LOGOPT_URL_CONTAINS, $opts ) ) {
				$opts[ZHUDT_LOGOPT_URL_CONTAINS] = '';
			}

			if ( ! array_key_exists( ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION, $opts ) ) {
				$opts[ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION] = false;
			}
		}

		return $opts;
	}

	/**
	 * Renders meta box to allow user to edit logging options
	 * 
	 * @since   1.0.0
	 */
	public static function render_logging_options_meta_box() {
		?>
		<form method="post" action="options.php"> 
			<?php
			// render hidden fields and nonce with calculated hash
			settings_fields( ZHUDT_LOG_GROUP );
			$currentSettings = self::get_logging_options();

			$enabled			 = ($currentSettings[ZHUDT_LOGOPT_ENABLED]) ? 'CHECKED' : null;
			$drop_table_enabled	 = ($currentSettings[ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION]) ? 'CHECKED' : null;

			$table_name = self::get_name_of_log_table();
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th>
						<?php esc_html_e( 'Enabled', 'zhu_dt_domain' ); ?>
					</th>
					<td>
						<input type='checkbox' name='<?php echo ZHUDT_LOG_OPTIONS, '[', ZHUDT_LOGOPT_ENABLED, ']'; ?>'  <?php echo $enabled; ?> >
						<p>
							<?php
							printf(
								esc_html__( 'Calls to %s will store records into database table', 'zhu_dt_domain' ),
								"<code>function zhu_log(\$string)</code>"
							);
							?>
							<code><?php echo $table_name; ?></code>
						</p>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Only if referer contains', 'zhu_dt_domain' ); ?>
					</th>
					<td>
						<input type='text' name='<?php echo ZHUDT_LOG_OPTIONS, '[', ZHUDT_LOGOPT_URL_CONTAINS, ']' ?>' 
							   value='<?php echo esc_attr( $currentSettings[ZHUDT_LOGOPT_URL_CONTAINS] ); ?>'
							   placeholder="e.g. localhost">
						<p><?php esc_html_e( 'Leave blank to always log (if enabled).', 'zhu_dt_domain' ); ?></p>
						<p><?php esc_html_e( 'Performs a case insensitive match against', 'zhu_dt_domain' ); ?> <code>$_SERVER['HTTP_REFERER']</code></p>
					</td>
				</tr>
				<tr class="zhu_dt_table_sep">
					<th>
						<hr>
					</th>
					<td>
						<hr>
					</td>
				</tr>
				<tr>
					<th>
						<?php esc_html_e( 'Drop Log Table on Deactivation', 'zhu_dt_domain' ); ?>
					</th>
					<td>
						<input type='checkbox' name='<?php echo ZHUDT_LOG_OPTIONS, '[', ZHUDT_LOGOPT_DEL_TABLE_ON_DEACTIVATION, ']'; ?>'  <?php echo $drop_table_enabled; ?> >
						<p>
							<?php printf( esc_html__( 'If checked, table %s will be deleted when this plugin is deactivated within WordPress', 'zhu_dt_domain' ), "<code>$table_name</code>" ); ?>
						<p>
					</td>
				</tr>

			</table>
			<p>
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'zhu_dt_domain' ); ?>">
				<button type="button" id="zhu_dt_log_truncate_button" class="button button-secondary"><?php _e( 'Truncate Log', 'zhu_dt_domain' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Renders page containing log entries
	 * 
	 * Dynamically includes support class zhu_dt_log_viewer.class.php
	 * 
	 * @since 1.0.0
	 * 
	 * @global wpdb $wpdb
	 */
	public static function render_view_log_page() {
		global $wpdb;
		/** @var wpdb $wpdb */
		if ( ! class_exists( 'zhu_dt_log_viewer' ) ) {
			require_once( __DIR__ . '/log/zhu_dt_log_viewer.class.php' );
		}

		$viewer = new zhu_dt_log_viewer();

		echo '<div class="wrap"><h2>', __( 'Log Table', 'zhu_dt_domain' ),
		' <code>(', $wpdb->base_prefix, 'zhu_log)</code></h2>';
		$viewer->prepare_items();
		$viewer->display();
		echo '</div>';
	}

}
