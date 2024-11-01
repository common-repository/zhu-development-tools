<?php
defined('ABSPATH') or header("Location: /");

define('ZHU_DT_COOKIE_VIEWER_CLASS_NAME', 'zhu_dt_cookie_viewer');

define('ZHUDT_COOKIE_VIEWER_GROUP', 'zhudt_cookie_viewer_group');
define('ZHUDT_COOKIE_VIEWER_OPTIONS', 'zhudt_cookie_viewer_options');

//individual settings within ZHUDT_COOKIE_VIEWER_OPTIONS
define('ZHUDT_CVIEW_ENABLED', 'enabled');

/**
 * Provides front end UI support for viewing cookies via local javaScript
 * Support is provided via the floating development toolbar
 * 
 * @since 1.0.0
 *
 * @author David Pullin
 */
class zhu_dt_cookie_viewer implements zhu_dt_pluggable_interface {

    /**
     * Implements interface method add_general_options_meta_boxes().  
     * 
     * Adds a meta box to render admin options for this tool
     * 
     * @since   1.0.0
     * 
     * @param string|array|WP_Screen    $screen     The general options screen 
     */
    public static function add_general_options_meta_boxes($screen) {
        add_meta_box('zhudt_mb_cookie_viewer', __('Cookie Viewer', 'zhu_dt_domain'), array(ZHU_DT_COOKIE_VIEWER_CLASS_NAME, 'render_cookie_viewer_meta_box'),
                $screen, 'side', 'high');
    }

    /**
     * Implements interface on_admin_init() method to configure admin support
     * 
     * @since   1.0.0
     */
    public static function on_admin_init() {
        self::register_settting();
    }

    /**
     * Implements interface on_init() method.  Called when WordPress invoked Zhu Dev Tools plugin's 'init' action
     * 
     * No action currently required
     * 
     * @since   1.0.0
     */
    public static function on_init() {
        
    }

    /**
     * Implements interface on_plugin_activation() method.  Called when Zhu Dev Tool's plugin is activated
     * 
     * No action currently require
     * 
     * @since   1.0.0
     */
    public static function on_plugin_activation() {
        //no action required        
    }

    /**
     * Implements interface on_plugin_deactivation() method.  Called when Zhu Dev Tool's plugin is deactivated
     * 
     * No action currently require
     * 
     * @since   1.0.0
     */
    public static function on_plugin_deactivation() {
        //no action required        
    }

    /**
     * Implements interface on_plugins_loaded() method.  Called when WordPress has loaded all plugins
     * 
     * Register action to be process later for rending content on client side developer toolbar
     * 
     * @since   1.0.0
     */
    public static function on_plugins_loaded() {
        $opts = self::get_cookie_viewer_options();

        if ($opts[ZHUDT_CVIEW_ENABLED]) {
            add_action('zhu_dt_enqueue_client_scripts', array(ZHU_DT_COOKIE_VIEWER_CLASS_NAME, 'on_enqueue_client_toolbar_scripts'));
            add_filter('zhu_dt_client_toolbar_content', array(ZHU_DT_COOKIE_VIEWER_CLASS_NAME, 'filter_cookie_viewer_client_toolbar_content'));
        }
    }

    /**
     * Register this tool's meta box settings with the WordPress framework
     * 
     * @since   1.0.0
     */
    private static function register_settting() {
        //register settings.  Register here as may be required by options.php when form posts to save updated settings
        register_setting(ZHUDT_COOKIE_VIEWER_GROUP, ZHUDT_COOKIE_VIEWER_OPTIONS,
                array(
                    'type' => 'array',
                    'description' => 'Zhu Dev Tools Cookie Viewer Options',
                    'sanitize_callback' => array(ZHU_DT_COOKIE_VIEWER_CLASS_NAME, 'sanitize_cookie_viewer_options')
                )
        );
    }

    /**
     * Sanitize settings posted from the cookie viewer meta box as processed via the WordPress framework
     * 
     * @since   1.0.0
     * 
     * @param array     $input    
     * 
     * @return array    Sanitized settings
     */
    public static function sanitize_cookie_viewer_options($input) {
        $new_input = array();

        $new_input[ZHUDT_CVIEW_ENABLED] = (isset($input[ZHUDT_CVIEW_ENABLED]) && strcasecmp('on', $input[ZHUDT_CVIEW_ENABLED]) == 0) ? 'on' : '';

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
    public static function get_cookie_viewer_options($allowDefaults = true, $getDefaultsOnly = false) {
        if ($getDefaultsOnly) {
            $opts = array();
        } else {
            $opts = get_option(ZHUDT_COOKIE_VIEWER_OPTIONS);

            if ($opts == null) {
                $opts = array();
            }
        }

        // Add defaults if setting not present 
        if ($allowDefaults || $getDefaultsOnly) {

            // ensure we have entries for all expected settings, even if there is no default value
            if (!array_key_exists(ZHUDT_CVIEW_ENABLED, $opts)) {
                $opts[ZHUDT_CVIEW_ENABLED] = false;
            }
        }

        return $opts;
    }

    /**
     * Renders meta box to allow user to edit cookie viewer options
     * 
     * @since   1.0.0
     */
    public static function render_cookie_viewer_meta_box() {
        ?>
        <form method="post" action="options.php"> 
            <?php
            // render hidden fields and nonce with calculated hash
            settings_fields(ZHUDT_COOKIE_VIEWER_GROUP);
            $currentSettings = self::get_cookie_viewer_options();

            $enabled = ($currentSettings[ZHUDT_CVIEW_ENABLED]) ? 'CHECKED' : null;
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th>
                        <?php esc_html_e('Enable in Client Toolbar', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='checkbox' name='<?php echo ZHUDT_COOKIE_VIEWER_OPTIONS, '[', ZHUDT_CVIEW_ENABLED, ']'; ?>'  <?php echo $enabled; ?> >
                        <p>
                            <?php
                            /* translators: placeholders here are embedded icons and html markup */
                            printf(
                                    esc_html__('When enabled an eye icon %1$s will appear on the Client Development Toolbar that will allow you to view your website cookies within a floating %2$s.',
                                            'zhu_dt_domain'
                                    ),
                                    '<span class="dashicons dashicons-visibility"></span>',
                                    '<i>div</i>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Changes', 'zhu_dt_domain'); ?>">
            </p>

        </form>
        <?php
    }

    /**
     * Enqueues JavaScript support required for the client side cookie viewer
     * 
     * This method is enqueued after the plugin is loaded and the option to enable the cookie viewer is set
     * 
     * Called when the Cookie Viewer is enabled
     * 
     * @since 1.0.0
     */
    public static function on_enqueue_client_toolbar_scripts() {
        add_action('wp_enqueue_scripts', array(ZHU_DT_COOKIE_VIEWER_CLASS_NAME, 'register_cookie_viewier_client_scripts'));
    }

    /**
     * Register required JavaScript to support to client side cookie viewer
     * 
     * @see on_enqueue_client_toolbar_scripts
     * 
     * @since 1.0.0
     */
    public static function register_cookie_viewier_client_scripts() {
        global $zhu_dt_plugin_tools_url;

        wp_register_script('zhu_dt_cookie_viewer_client_js', $zhu_dt_plugin_tools_url . 'cookie_viewer/js/cookie_viewer_client.js');
        wp_enqueue_script('zhu_dt_cookie_viewer_client_js');
    }

    /**
     * Generates HTML to be added to the client side toolbar.  Generates image for the user to click to open the cookie viewer
     * 
     * @return string   generated HTML
     */
    public static function filter_cookie_viewer_client_toolbar_content() {
        return <<<html
            <span class="dashicons dashicons-visibility" title='View Cookies' onclick='zhu_dt_toggle_cookie_viewer(event)'></span>
html;
    }

}
