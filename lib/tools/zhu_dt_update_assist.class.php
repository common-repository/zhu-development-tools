<?php
defined( 'ABSPATH' ) or header( "Location: /" );

define( 'ZHUDT_UPASSIST_GROUP', 'zhudt_update_assist_group' );
define( 'ZHUDT_UPASSIST_OPTIONS', 'zhudt_update_assist_options' );

define( 'ZHUDT_UPASSIST_OPT_SETMAXTIME', 'zhudt_upassist_opt_setmaxtime' ); // checkbox
define( 'ZHUDT_UPASSIST_OPT_MAXTIME', 'zhudt_upassist_opt_maxtime' );  // value (integer)

define( 'ZHUDT_UPASSIST_ACTION_REMOVE_CORE_UPDATE_LOCK', 'zhu_upssist_coreupdatelock' );



/**
 * Main class for the Update Assist pluggable
 * 
 * This class provides administration options to remove WordPress' Core Updater Lock
 * and issue ini_set('max_execution_time').
 * 
 * @since 1.1.0
 * 
 * @author David Pullin
 */

class zhu_dt_update_assist implements zhu_dt_pluggable_interface {

	private static $maxtimeout_attempted		 = false;
	private static $maxtimeout_attempt_succeeded = false;
	private static $maxtimeout_attempt_message	 = "";

	/**
	 * Implements interface on_plugin_activation() method.  Called when Zhu Dev Tool's plugin is activated
	 * 
	 * No action currently required
	 * 
	 * @since   1.1.0
	 */
	public static function on_plugin_activation() {
		
	}

	/**
	 * Implements interface on_plugin_deactivation() method.  Called when Zhu Dev Tool's plugin is deactivated
	 * 
	 * No action currently required
	 * 
	 * @since   1.1.0
	 */
	public static function on_plugin_deactivation() {
		
	}

	/**
	 * Implements interface on_plugins_loaded() method.  Called when WordPress has loaded all plugins
	 * 
	 * No action currently required
	 * 
	 * @since   1.1.0
	 */
	public static function on_plugins_loaded() {
		
	}

	/**
	 * Implements interface on_init() method.  Called when WordPress invoked Zhu Dev Tools plugin's 'init' action
	 * 
	 * No action currently required
	 * 
	 * @since   1.1.0
	 */
	public static function on_init() {
		
	}

	/**
	 * Implements interface on_admin_init() method to configure admin support
	 * 
	 * Registers ajax support for removing WordPress's core updater lock
	 * 
	 * @since   1.1.0
	 */
	public static function on_admin_init() {
		self::register_settting();

		add_action( 'admin_enqueue_scripts', array( ZHU_DT_UPASSIST_CLASS_NAME, 'add_stylesheet' ) );
		add_action( 'admin_enqueue_scripts', array( ZHU_DT_UPASSIST_CLASS_NAME, 'add_javascript' ) );

		// Setup Ajax action hook
		add_action( 'wp_ajax_zhu_dt_upassist_remove_core_update_lock', array( ZHU_DT_UPASSIST_CLASS_NAME, 'remove_core_updater_lock' ) );

		$currentSettings = self::get_update_assist_options();
		if ( 'on' == $currentSettings[ZHUDT_UPASSIST_OPT_SETMAXTIME] ) {

			self::$maxtimeout_attempted			 = true;
			self::$maxtimeout_attempt_succeeded	 = false;

			$current_value = ini_get( 'max_execution_time' );

			if ( $currentSettings[ZHUDT_UPASSIST_OPT_MAXTIME] == $current_value ) {
				self::$maxtimeout_attempt_succeeded	 = true;
				self::$maxtimeout_attempt_message	 = __( 'No action taken. Already set to', 'zhu_dt_domain' ) . ' ' . $current_value . '.';
			} else {
				if (ini_set( 'max_execution_time', absint( $currentSettings[ZHUDT_UPASSIST_OPT_MAXTIME] ) ) === false ) {
					self::$maxtimeout_attempt_succeeded	 = false;
					self::$maxtimeout_attempt_message	 = 'ini_set() ' . __( 'returned false', 'zhu_dt_domain' ) . '. ' . __('This may be due to a hosting provider restriction,', 'zhu_dt_domain' );
				} else {
					// check it did apply
					$current_value = ini_get( 'max_execution_time' );
					if ( $current_value == $currentSettings[ZHUDT_UPASSIST_OPT_MAXTIME] ) {
						self::$maxtimeout_attempt_succeeded	 = true;
						self::$maxtimeout_attempt_message	 = __( 'Value applied', 'zhu_dt_domain' );
					} else {
						self::$maxtimeout_attempt_succeeded	 = false;
						self::$maxtimeout_attempt_message	 = __( 'Command issued but not applied.','zhu_dt_domain') . ' ' . __('This may be due to a hosting provider restriction,', 'zhu_dt_domain' );
					}
				}
			}
		}
	}

	/**
	 * Registers and queues required JavaScript file to support update assist
	 * 
	 * @since 1.1.0
	 */
	public static function add_javascript() {
		wp_register_script( 'zhu_dt_upassist_admin_js', plugins_url( 'zhu-dev-tools/js/zhu_dt_upassist_admin.js' ), array( 'jquery' ), '1.1.0' );

		wp_localize_script( 'zhu_dt_upassist_admin_js', 'zhu_dt_upassist_admin_local',
			array( 'ajax_url'	 => admin_url( 'admin-ajax.php' ),
				'removing'	 => __( 'removing lock', 'zhu_dt_domain' ),
				'nonce'		 => wp_create_nonce( ZHUDT_UPASSIST_ACTION_REMOVE_CORE_UPDATE_LOCK )
			)
		);


		wp_enqueue_script( 'zhu_dt_upassist_admin_js' );
	}

	
	/**
	 * Registers and queues required CSS file to support update assist
	 * 
	 * @since 1.1.0
	 */
	public static function add_stylesheet() {
		wp_register_style(
			'zhu_dt_update_assist',
			plugins_url( 'zhu-dev-tools/css/zhu_dt_update_assist.css' )
		);
		wp_enqueue_style( 'zhu_dt_update_assist' );
	}

	/**
	 * Implements interface method add_general_options_meta_boxes().  
	 * 
	 * Adds a meta for update assist settings and the ability to remove core updated lock on demand
	 * 
	 * @since 1.1.0
	 * 
	 * @param string|array|WP_Screen    $screen     The general options screen 
	 */
	public static function add_general_options_meta_boxes( $screen ) {
		add_meta_box( 'zhudt_mb_update_assist', __( 'Update Assist Options', 'zhu_dt_domain' ), array( ZHU_DT_UPASSIST_CLASS_NAME, 'render_update_assist_options_meta_box' ),
			$screen, 'side', 'high' );
	}

	/**
	 * Register this tool's meta box settings with the WordPress framework
	 *
	 * @since 1.1.0
	 */
	private static function register_settting() {

		//Register here as may be required by options.php when form posts to save updated settings
		register_setting( ZHUDT_UPASSIST_GROUP, ZHUDT_UPASSIST_OPTIONS,
			array(
				'type'				 => 'array',
				'description'		 => 'Zhu Dev Tools Update Assist Options',
				'sanitize_callback'	 => array( ZHU_DT_UPASSIST_CLASS_NAME, 'sanitize_update_assist_options' )
			)
		);
	}

	/**
	 * Sanitize settings posted from the update assist meta box as processed via the WordPress framework
	 * 
	 * @since   1.1.0
	 * 
	 * @param array     $input    
	 * 
	 * @return array    Sanitized settings
	 */
	public static function sanitize_update_assist_options( $input ) {

		$new_input = array();

		$new_input[ZHUDT_UPASSIST_OPT_SETMAXTIME]	 = (isset( $input[ZHUDT_UPASSIST_OPT_SETMAXTIME] ) && strcasecmp( 'on', $input[ZHUDT_UPASSIST_OPT_SETMAXTIME] ) == 0) ? 'on' : '';
		$new_input[ZHUDT_UPASSIST_OPT_MAXTIME]		 = isset( $input[ZHUDT_UPASSIST_OPT_MAXTIME] ) ? absint( sanitize_text_field( $input[ZHUDT_UPASSIST_OPT_MAXTIME] ) ) : 300;

		return $new_input;
	}

	/**
	 * Return meta box options
	 * 
	 * @since 1.1.0
	 * 
	 * @param bool $allowDefaults   If set, populate default settings if not already present
	 * @param bool $getDefaultsOnly If set, ignore settings stored in WordPress's options and only return defaults
	 * @return array    Array of settings
	 */
	public static function get_update_assist_options( $allowDefaults = true, $getDefaultsOnly = false ) {
		if ( $getDefaultsOnly ) {
			$opts = array();
		} else {
			$opts = get_option( ZHUDT_UPASSIST_OPTIONS );

			if ( $opts == null ) {
				$opts = array();
			}
		}

		// Add defaults if setting not present 
		if ( $allowDefaults || $getDefaultsOnly ) {

			// ensure we have entries for all expected settings, even if there is no default value
			if ( ! array_key_exists( ZHUDT_UPASSIST_OPT_SETMAXTIME, $opts ) ) {
				$opts[ZHUDT_UPASSIST_OPT_SETMAXTIME] = false;
			}

			if ( ! array_key_exists( ZHUDT_UPASSIST_OPT_MAXTIME, $opts ) ) {
				$opts[ZHUDT_UPASSIST_OPT_MAXTIME] = 30;
			}
		}

		// Validate ranges
		if ( $opts[ZHUDT_UPASSIST_OPT_MAXTIME] < 30 ) {
			$opts[ZHUDT_UPASSIST_OPT_MAXTIME] = 30;
		}

		return $opts;
	}

	/**
	 * Renders meta box to allow user to edit update_assist
	 * 
	 * @since   1.1.0
	 */
	public static function render_update_assist_options_meta_box() {
		?>
		<form method="post" action="options.php"> 
			<?php
			// render hidden fields and nonce with calculated hash
			settings_fields( ZHUDT_UPASSIST_GROUP );
			$currentSettings = self::get_update_assist_options();

			$set_max_time_enabled = ($currentSettings[ZHUDT_UPASSIST_OPT_SETMAXTIME]) ? 'CHECKED' : null;
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th>
						<?php esc_html_e( "Remove WordPress's Core Updater Lock", 'zhu_dt_domain' ); ?>
					</th>
					<td>
						<button type="button" id="zhu_dt_upassist_remove_core_update_lock_button" class="button button-secondary"><?php esc_html_e( "Remove Core Updater Lock", 'zhu_dt_domain' ); ?></button>
					</td>
				</tr>
				<tr>
					<td colspan='2'>
						<?php
						esc_html_e( 'Using the option blow may assist with timeout issues when running updates.', 'zhu_dt_domain' );
						echo '<br>';
						esc_html_e( 'Please note that some hosting providers prevent this setting from being changed.', 'zhu_dt_domain' );
						?>
					</td>
				</tr>
				<tr>
					<th>
						ini_set('max_execution_time')
					</th>
					<td>
						<input type='checkbox' name='<?php echo ZHUDT_UPASSIST_OPTIONS, '[', ZHUDT_UPASSIST_OPT_SETMAXTIME, ']'; ?>'  <?php echo $set_max_time_enabled; ?> >
						<?php esc_html_e( 'for', 'zhu_dt_domain' ); ?> 
						<input type='number' name='<?php echo ZHUDT_UPASSIST_OPTIONS, '[', ZHUDT_UPASSIST_OPT_MAXTIME, ']'; ?>' 
							   value='<?php esc_attr_e( $currentSettings[ZHUDT_UPASSIST_OPT_MAXTIME] ); ?>' 
							   min='30'
							   max='<?php echo PHP_INT_MAX; ?>'
							   >
							   <?php esc_html_e( 'seconds', 'zhu_dt_domain' ); ?> 
					</td>
				</tr>
				<?php if ( self::$maxtimeout_attempted ) { ?>
					<tr>
						<th><?php esc_html_e( 'Last Setting Attempt', 'zhu_dt_domain' ); ?></th>
						<td <?php echo (( self::$maxtimeout_attempt_succeeded ) ? "class='zhudt_update_assist_success'" : "class='zhudt_update_assist_error'"); ?>>
							<?php echo esc_html( self::$maxtimeout_attempt_message ); ?>
						</td>
					</tr>
				<?php } ?>
			</table>
			<p>
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Save Changes', 'zhu_dt_domain' ); ?>">
			</p>
		</form>
		<?php
	}

	/**
	 * Method invoked via Ajax when user presses the Remove Core Updater Lock button
	 * 
	 * @since 1.1.0
	 * 
	 */
	public static function remove_core_updater_lock() {
		$nonce = (array_key_exists( 'nonce', $_POST )) ? sanitize_text_field( $_POST['nonce'] ) : null;
		if ( $nonce == null || wp_verify_nonce( $nonce, ZHUDT_UPASSIST_ACTION_REMOVE_CORE_UPDATE_LOCK ) == false ) {
			_e( 'The link you followed has expired.', 'zhu_dt_domain' );
		} else {
			if ( delete_option( 'core_updater.lock' ) ) {
				echo( '1|');  // 1 = Success - update button's caption
				_e( 'Lock Removed', 'zhu_dt_domain' );
			} else {
				echo( '0|');  // 0 = display message
				_e( "WordPress's delete_option() returned false.  It is likely that there was no such lock.", 'zhu_dt_domain' );
			}
		}

		//terminate now,  this was an ajax call and we are all done, also we don't want admin-ajax.php to echo anything else.
		exit;
	}

}
