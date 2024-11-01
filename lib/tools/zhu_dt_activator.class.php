<?php
defined('ABSPATH') or header("Location: /");

/**
 * This tool allows the user to maintain which other tools are activated (i.e. loaded) and
 * also allows the user to control settings relating to the client side toolbar
 * 
 * @since 1.0.0
 * 
 * @author David Pullin
 */
class zhu_dt_activator implements zhu_dt_pluggable_interface {

    /**
     * Implements interface method add_general_options_meta_boxes().  
     * 
     * Registers meta option boxes to allow user to set which tools are to be activated and options relating to the client toolbar
     * 
     * @since 1.0.0
     * 
     * @param string|array|WP_Screen    $screen     The general options screen 
     */
    public static function add_general_options_meta_boxes($screen) {
        add_meta_box('zhudt_mb_activator', __('Configure Which Tools to Load', 'zhu_dt_domain'), array(ZHU_DT_ACTIVATOR_CLASS_NAME, 'render_activator_meta_box'),
                $screen, 'normal', 'high');

        add_meta_box('zhudt_mb_client_toolbar', __('Client Development Toolbar', 'zhu_dt_domain'), array(ZHU_DT_ACTIVATOR_CLASS_NAME, 'render_client_toolbar_meta_box'),
                $screen, 'normal', 'high');
    }

    /**
     * Implements interface on_admin_init() method to configure admin support
     * 
     * Allows user to configure which tools this plugin loads
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
        //no action required
    }

    /**
     * Implements interface on_plugin_activation() method.  Called when Zhu Dev Tool's plugin is activated
     * 
     * No action currently required
     * 
     * @since   1.0.0
     */
    public static function on_plugin_activation() {
        //no action required
    }

    /**
     * Implements interface on_plugin_deactivation() method.  Called when Zhu Dev Tool's plugin is deactivated
     * 
     * No action currently required
     * 
     * @since   1.0.0
     */
    public static function on_plugin_deactivation() {
        
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
     * Register this tool's meta box settings with the WordPress framework
     * 
     * @since   1.0.0
     */
    private static function register_settting() {

        //register settings.  Register here as may be required by options.php when form posts to save updated settings
        register_setting(ZHUDT_ACTIVATOR_GROUP, ZHUDT_ACTIVATOR_OPTIONS,
                array(
                    'type' => 'array',
                    'description' => 'Zhu Dev Tools Activator Options',
                    'sanitize_callback' => array(ZHU_DT_ACTIVATOR_CLASS_NAME, 'sanitize_activator_options')
                )
        );

        register_setting(ZHUDT_CLIENT_TOOLBAR_GROUP, ZHUDT_CLIENT_TOOLBAR_OPTIONS,
                array(
                    'type' => 'array',
                    'description' => 'Zhu Dev Tools Toolbar Options',
                    'sanitize_callback' => array(ZHU_DT_ACTIVATOR_CLASS_NAME, 'sanitize_client_toolbar_options')
                )
        );
    }

    /**
     * Sanitize settings posted from the activator meta box as processed via the WordPress framework
     * 
     * @since   1.0.0
     * 
     * @param array $input    
     * 
     * @return array    Sanitized settings
     */
    public static function sanitize_activator_options($input) {

        $new_input = array();

        $new_input[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT] = (isset($input[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT]) && strcasecmp('on', $input[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT]) == 0) ? 'on' : '';

        //process any tool_ prefixed entries into sub-array to determine if tool is to be loaded or not
        //we are looking at the hidden input for each tool as the key as this will be present regardless if the checkbox is unchecked
        //i.e. avoid issues with knowing the different between not-present and not-set
        $new_input['tools'] = array();
        foreach ($input as $input_key => $ignore_value) {
            if ('tool_' == substr($input_key, 0, 5)) {
                $class = substr($input_key, 5);
                $new_input['tools'][$class]['to_load'] = array_key_exists($class, $input) ? 'on' : '';
            }
        }

        return $new_input;
    }

    /**
     * Sanitize settings posted from the client toolbar options meta box as processed via the WordPress framework
     * 
     * @since   1.0.0
     * 
     * @param array     $input    
     * 
     * @return array    Sanitized settings
     */
    public static function sanitize_client_toolbar_options($input) {

        $new_input = array();

        $new_input[ZHUDT_CTB_ENABLED] = (isset($input[ZHUDT_CTB_ENABLED]) && strcasecmp('on', $input[ZHUDT_CTB_ENABLED]) == 0) ? 'on' : '';
        $new_input[ZHUDT_CTB_USER] = isset($input[ZHUDT_CTB_USER]) ? (int) sanitize_text_field($input[ZHUDT_CTB_USER]) : 0;
        $new_input[ZHUDT_CTB_ACTIVATION_ID] = isset($input[ZHUDT_CTB_ACTIVATION_ID]) ? sanitize_text_field($input[ZHUDT_CTB_ACTIVATION_ID]) : 0;

        return $new_input;
    }

    /**
     * Renders meta box to allow user to configure settings to enable
     * disable the loading of tools
     * 
     * @since   1.0.0
     */
    public static function render_activator_meta_box() {
        ?>
        <form method="post" action="options.php"> 
            <?php
            // render hidden fields and nonce with calculated hash
            settings_fields(ZHUDT_ACTIVATOR_GROUP);

            $currentSettings = get_activator_options(true, false, true);

            $load_new_checked = ($currentSettings[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT]) ? 'CHECKED' : null;
            ?>
            <table class="form-table" role="presentation">
                <?php
                $zhu_dt_nice_names = array(
                    'zhu_dt_log' => array(
                        'name' => __('Logging Support', 'zhu_dt_domain')
                    ),
                    'zhu_dt_send_test_email' => array(
                        'name' => __('Send Test Emails', 'zhu_dt_domain')
                    ),
                    'zhu_dt_cookie_viewer' => array(
                        'name' => __('Cookie Viewer', 'zhu_dt_domain')
                    ),
                    'zhu_dt_update_assist' => array(
                        'name' => __('Update Assist', 'zhu_dt_domain')
                    )
                );


                foreach ($currentSettings['tools'] as $tool_class_name => $tool) {


                    //omit the activator itself.  i.e. don't allow user to not load the activator
                    //if the user does not require the activator not to be loaded then the file
                    //can be deleted from the tools directory
                    if (strcasecmp($tool_class_name, ZHU_DT_ACTIVATOR_CLASS_NAME) !== 0) {

                        if (array_key_exists($tool_class_name, $zhu_dt_nice_names)) {
                            $tool_name = esc_html($zhu_dt_nice_names[$tool_class_name]['name']);
                        } else {
                            $tool_name = esc_html($tool_class_name);
                        }

                        $tool_checked = (array_key_exists('to_load', $tool) && $tool['to_load']) ? 'checked' : null;
                        $option_name = ZHUDT_ACTIVATOR_OPTIONS . '[' . $tool_class_name . ']';
                        $hidden_name = ZHUDT_ACTIVATOR_OPTIONS . '[tool_' . $tool_class_name . ']';
                        
                        $load_word = esc_html_x('Load', 'verb', 'zhu_dt_domain');
                        
                        echo <<<html
                            <tr>
                                <th>{$load_word} {$tool_name}</th>
                                <td>
                                    <input type='checkbox' name='{$option_name}' {$tool_checked} ?> 
                                    <input type='hidden' name='{$hidden_name}' value='' ?>
                                </td>
                            </tr>
html;
                    }
                }
                ?>
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
                        <?php esc_html_e('Load new tools by default', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='checkbox' name='<?php echo ZHUDT_ACTIVATOR_OPTIONS, '[', ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT, ']' ?>' 
                               <?php echo $load_new_checked; ?> >
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Save Changes', 'zhu_dt_domain'); ?>">
            </p>

        </form>
        <?php
    }

    /**
     * Renders meta box to allow user to edit settings relating to the client toolbar
     * 
     * @since   1.0.0
     */
    public static function render_client_toolbar_meta_box() {
        ?>
        <form method = "post" action = "options.php">
            <?php
            // render hidden fields and nonce with calculated hash
            settings_fields(ZHUDT_CLIENT_TOOLBAR_GROUP);
            $currentSettings = get_client_toolbar_options();

            $enabled = ($currentSettings[ZHUDT_CTB_ENABLED]) ? 'CHECKED' : null;
            $activation_id = esc_attr($currentSettings[ZHUDT_CTB_ACTIVATION_ID]);
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th>
                        <?php esc_html_e('Enabled', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='checkbox' name='<?php echo ZHUDT_CLIENT_TOOLBAR_OPTIONS, '[', ZHUDT_CTB_ENABLED, ']'; ?>'  <?php echo $enabled; ?> >
                        <p>
                            <?php
                            esc_html_e('When enabled a floating toolbar will appear on your site.  It will not be displayed if there are no relevant enabled tools.', 'zhu_dt_domain');
                            ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Only display for WordPress user', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <?php
                        wp_dropdown_users(array(
                            'show' => 'user_login',
                            'show_option_none' => ' ',
                            'option_none_value' => 0,
                            'selected' => $currentSettings[ZHUDT_CTB_USER],
                            'name' => ZHUDT_CLIENT_TOOLBAR_OPTIONS . '[' . ZHUDT_CTB_USER . ']',
                            'id' => ZHUDT_CLIENT_TOOLBAR_OPTIONS . '_' . ZHUDT_CTB_USER
                        ));
                        ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('or for Activation ID', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='text' maxlength='8' name='<?php echo ZHUDT_CLIENT_TOOLBAR_OPTIONS, '[', ZHUDT_CTB_ACTIVATION_ID, ']'; ?>' value='<?php echo $activation_id; ?>' >
                        <p>
                            <?php
                            printf(
                                    esc_html__('This option allows you to display the toolbar when not logged into WordPress. You will first need to use the toolbar when logged into WordPress. Select the lock icon %s to display the activation ID.  This ID is stored as a cookie in your browser which, when it matches this value, will ensure that the toolbar is rendered.',
                                            'zhu_dt_domain'
                                    ),
                                    '<span class="dashicons dashicons-lock"></span>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Save Changes', 'zhu_dt_domain'); ?>">
            </p>

        </form>
        <?php
    }

}
