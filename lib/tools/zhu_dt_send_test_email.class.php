<?php
defined('ABSPATH') or header("Location: /");

define('ZHU_DT_SEND_TEST_EMAIL_CLASS_NAME', 'zhu_dt_send_test_email');

define('ZHUDT_EMAIL_TEST_GROUP', 'zhudt_email_test_group');
define('ZHUDT_EMAIL_TEST_OPTIONS', 'zhudt_email_test_options');

//individual settings within ZHUDT_EMAIL_TEST_OPTIONS
define('ZHUDT_EMAIL_TEST_TO', 'to');
define('ZHUDT_EMAIL_TEST_SUBJECT', 'subject');
define('ZHUDT_EMAIL_TEST_BODY', 'body');
define('ZHUDT_EMAIL_TEST_TRIGGER_GUID', 'trigger_guid');

//non-on screen fields, to allow once only triggeting on submit and store the results of
define('ZHUDT_EMAIL_TEST_LAST_GUID', 'last_guid');
define('ZHUDT_EMAIL_TEST_LAST_RESULT', 'last_result');                  // true or false - the return value from wp_mail
define('ZHUDT_EMAIL_TEST_LAST_WHEN', 'last_when');                      // when was the last test
define('ZHUDT_EMAIL_TEST_LAST_ERROR', 'last_error');                    // if last result = false, then the error message, else a blank string

/**
 * Meta Box setting to allow a quick way of sending a test email via WP_mail.
 * 
 * Allows admin user to send a test email.  Details of the last email address, subject and body sent are remembered
 * for next time.  When the test email is sent this class hooks into WordPress's wp_mail_failed action to detect
 * failure.  The details of the failure are stored and also displayed in the settings meta box.
 *  
 * @since 1.0.0
 *
 * @author David Pullin
 */
class zhu_dt_send_test_email implements zhu_dt_pluggable_interface {

    /**
     * @var string  Used to preserve error message if wp_mail() triggers its wp_mail_failed action
     */
    private static $last_error_as_string = null;

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
     * No action currently required
     * 
     * @since   1.0.0
     */
    public static function on_plugins_loaded() {
        
    }

    /**
     * Implements interface on_init() method.  Called when WordPress invoked Zhu Dev Tools plugin's 'init' action
     * 
     * No action currently require
     * 
     * @since   1.0.0
     */
    public static function on_init() {
        //no action required 
    }

    /**
     * Implements interface on_admin_init() method to configure admin support
     * 
     * @since   1.0.0
     */
    public static function on_admin_init() {
        self::register_settting();

        add_action('admin_enqueue_scripts', array(ZHU_DT_SEND_TEST_EMAIL_CLASS_NAME, 'add_stylesheet'));
    }

    /**
     * Implements interface method add_general_options_meta_boxes().  
     * 
     * Adds a meta box to enable sending of a test email
     * 
     * @since   1.0.0
     * 
     * @param string|array|WP_Screen    $screen     The general options screen 
     */
    public static function add_general_options_meta_boxes($screen) {
        add_meta_box('zhudt_mb_email_test', __('Send Test Email', 'zhu_dt_domain'), array(ZHU_DT_SEND_TEST_EMAIL_CLASS_NAME, 'render_email_test_meta_box'),
                $screen, 'side', 'high');
    }

    /**
     * Registers and queues required CSS file to support send email test
     * 
     * @since 1.0.0
     */
    public static function add_stylesheet() {
        wp_register_style(
                'zhu_dt_sent_test_email',
                plugins_url('zhu-dev-tools/css/zhu_dt_send_test_email.css')
        );
        wp_enqueue_style('zhu_dt_sent_test_email');
    }

    /**
     * Returns a GUIDv4 string
     *
     * Uses the best cryptographically secure method
     * for all supported platforms with fallback to an older,
     * less secure version.
     *
     * @link https://www.php.net/manual/en/function.com-create-guid.php taken from
     * 
     * @since   1.0.0
     * 
     * @param bool $trim   
     * @return string
     */
    private static function create_GUID(bool $trim = true): string {

        // Windows
        if (function_exists('com_create_guid') === true) {
            if ($trim === true) {
                return trim(com_create_guid(), '{}');
            } else {
                return com_create_guid();
            }
        }

        // OSX/Linux
        if (function_exists('openssl_random_pseudo_bytes') === true) {
            $data = openssl_random_pseudo_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Fallback (PHP 4.2+)
        mt_srand((double) microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace .
                substr($charid, 0, 8) . $hyphen .
                substr($charid, 8, 4) . $hyphen .
                substr($charid, 12, 4) . $hyphen .
                substr($charid, 16, 4) . $hyphen .
                substr($charid, 20, 12) .
                $rbrace;
        return $guidv4;
    }

    /**
     * Register this tool's meta box settings with the WordPress framework
     * 
     * @since   1.0.0
     */
    private static function register_settting() {

        //register settings.  Register here as may be required by options.php when form posts to save updated settings
        register_setting(ZHUDT_EMAIL_TEST_GROUP, ZHUDT_EMAIL_TEST_OPTIONS,
                array(
                    'type' => 'array',
                    'description' => 'Zhu Dev Tools Send Test Email Options',
                    'sanitize_callback' => array(ZHU_DT_SEND_TEST_EMAIL_CLASS_NAME, 'sanitize_email_test_options')
                )
        );
    }

    /**
     * Sanitize settings posted from the test email meta box as processed via the WordPress framework
     * 
     * @since   1.0.0
     * 
     * @param array $input    
     * 
     * @return array    Sanitized settings
     */
    public static function sanitize_email_test_options($input) {

        //get current options, so we can obtain the last guid used
        $oldOptions = self::get_mail_test_options();

        //sanitize incoming settings
        $to_was = $input[ZHUDT_EMAIL_TEST_TO];
        $to = (!isset($input[ZHUDT_EMAIL_TEST_TO])) ? null : sanitize_email($input[ZHUDT_EMAIL_TEST_TO]);
        $subject = (!isset($input[ZHUDT_EMAIL_TEST_SUBJECT])) ? null : sanitize_text_field($input[ZHUDT_EMAIL_TEST_SUBJECT]);
        $body = (!isset($input[ZHUDT_EMAIL_TEST_BODY])) ? null : sanitize_text_field($input[ZHUDT_EMAIL_TEST_BODY]);
        $trigger_guid = (!isset($input[ZHUDT_EMAIL_TEST_TRIGGER_GUID])) ? null : sanitize_text_field($input[ZHUDT_EMAIL_TEST_TRIGGER_GUID]);

        $new_input = array();

        // if next_guid does not match the previous setting's last guid then send the test email
        // then update the last guid to be the same as the next_guid.  We are doing this incase 
        // this sanitize method is called more than one
        if ($oldOptions[ZHUDT_EMAIL_TEST_LAST_GUID] != $trigger_guid) {


            $new_input[ZHUDT_EMAIL_TEST_LAST_WHEN] = date_create()->format('Y/m/d H:i:s');

            if ($to == '') {
                $new_input[ZHUDT_EMAIL_TEST_LAST_RESULT] = '0';
                if ($to_was != '') {
                    $new_input[ZHUDT_EMAIL_TEST_LAST_ERROR] = sprintf(__('%s is an invalid email address', 'zhu_dt_domain'), esc_html($to_was));
                } else {
                    $new_input[ZHUDT_EMAIL_TEST_LAST_ERROR] = __('Missing To Email Address', 'zhu_dt_domain');
                }
            } else {
                self::$last_error_as_string = null;

                add_action('wp_mail_failed', array(ZHU_DT_SEND_TEST_EMAIL_CLASS_NAME, 'on_wp_mail_failed'));
                $test_email = wp_mail($to, $subject, $body);
                remove_action('wp_mail_failed', array(ZHU_DT_SEND_TEST_EMAIL_CLASS_NAME, 'on_wp_mail_failed'));

                $new_input[ZHUDT_EMAIL_TEST_LAST_RESULT] = ($test_email) ? '1' : '0';
                $new_input[ZHUDT_EMAIL_TEST_LAST_ERROR] = self::$last_error_as_string;
            }

            // Prevent further triggering until form is posted again
            $new_input[ZHUDT_EMAIL_TEST_LAST_GUID] = $trigger_guid;
        } else {
            // no re-submit by user, this method called again by framework
            // preserve last results
            $new_input[ZHUDT_EMAIL_TEST_LAST_RESULT] = (isset($input[ZHUDT_EMAIL_TEST_LAST_RESULT])) ? $input[ZHUDT_EMAIL_TEST_LAST_RESULT] : null;
            $new_input[ZHUDT_EMAIL_TEST_LAST_ERROR] = (isset($input[ZHUDT_EMAIL_TEST_LAST_ERROR])) ? $input[ZHUDT_EMAIL_TEST_LAST_ERROR] : null;
        }

        $new_input[ZHUDT_EMAIL_TEST_TO] = $to;
        $new_input[ZHUDT_EMAIL_TEST_SUBJECT] = $subject;
        $new_input[ZHUDT_EMAIL_TEST_BODY] = $body;

        return $new_input;
    }

    /**
     * Hook function called from WordPress's wp_mail() if sending of the mail fails
     * 
     * Populates internal string with error messages
     * 
     * @since   1.0.0
     * 
     * @param WP_Error $wpe
     */
    public static function on_wp_mail_failed(WP_Error $wpe) {
        self::$last_error_as_string = implode('|', $wpe->get_error_messages());
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
    private static function get_mail_test_options(bool $allowDefaults = true, bool $getDefaultsOnly = false) {
        if ($getDefaultsOnly) {
            $opts = array();
        } else {
            $opts = get_option(ZHUDT_EMAIL_TEST_OPTIONS);

            if ($opts == null) {
                $opts = array();
            }
        }

        // Add defaults if setting not present 
        if ($allowDefaults || $getDefaultsOnly) {

            // ensure we have entries for all expected settings, even if there is no default value
            if (!array_key_exists(ZHUDT_EMAIL_TEST_TO, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_TO] = '';
            }

            if (!array_key_exists(ZHUDT_EMAIL_TEST_TRIGGER_GUID, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_TRIGGER_GUID] = '';
            }

            if (!array_key_exists(ZHUDT_EMAIL_TEST_LAST_GUID, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_LAST_GUID] = '';
            }

            if (!array_key_exists(ZHUDT_EMAIL_TEST_LAST_RESULT, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_LAST_RESULT] = '';
            }

            if (!array_key_exists(ZHUDT_EMAIL_TEST_LAST_WHEN, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_LAST_WHEN] = '';
            }

            if (!array_key_exists(ZHUDT_EMAIL_TEST_LAST_ERROR, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_LAST_ERROR] = '';
            }

            //defaults with values
            if (!array_key_exists(ZHUDT_EMAIL_TEST_SUBJECT, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_SUBJECT] = 'This is a test subject';
            }

            if (!array_key_exists(ZHUDT_EMAIL_TEST_BODY, $opts)) {
                $opts[ZHUDT_EMAIL_TEST_BODY] = 'This is a test email';
            }
        }

        return $opts;
    }

    /**
     * Renders meta box to allow user to send a test email.
     * 
     * @since   1.0.0
     */
    public static function render_email_test_meta_box() {
        ?>
        <form method="post" action="options.php"> 
            <?php
            // render hidden fields and nonce with calculated hash
            settings_fields(ZHUDT_EMAIL_TEST_GROUP);

            $currentSettings = self::get_mail_test_options();

            $newGUIDValue = self::create_GUID();
            ?>
            <input type='hidden' name='<?php echo ZHUDT_EMAIL_TEST_OPTIONS, '[', ZHUDT_EMAIL_TEST_TRIGGER_GUID, ']' ?>' value='<?php echo esc_attr($newGUIDValue); ?>'>
            <table class="form-table" role="presentation">
                <tr>
                    <th>
                        <?php esc_html_e('To', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='text' name='<?php echo ZHUDT_EMAIL_TEST_OPTIONS, '[', ZHUDT_EMAIL_TEST_TO, ']' ?>' 
                               value='<?php echo esc_attr($currentSettings[ZHUDT_EMAIL_TEST_TO]); ?>' >
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Subject', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='text' name='<?php echo ZHUDT_EMAIL_TEST_OPTIONS, '[', ZHUDT_EMAIL_TEST_SUBJECT, ']' ?>' 
                               value='<?php echo esc_attr($currentSettings[ZHUDT_EMAIL_TEST_SUBJECT]); ?>' >
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Body', 'zhu_dt_domain'); ?>
                    </th>
                    <td>
                        <input type='text' name='<?php echo ZHUDT_EMAIL_TEST_OPTIONS, '[', ZHUDT_EMAIL_TEST_BODY, ']' ?>' 
                               value='<?php echo esc_attr($currentSettings[ZHUDT_EMAIL_TEST_BODY]); ?>' >
                    </td>
                </tr>

                <?php ?>

                <tr>
                    <th>
                        <?php esc_html_e('Last Test Result', 'zhu_dt_domain'); ?>
                    </th>
                    <td <?php echo ((0 == $currentSettings[ZHUDT_EMAIL_TEST_LAST_RESULT]) ? "class='zhudt_test_email_error'" : "class='zhudt_test_email_success'"); ?>>
                        <?php
                        //empty string is the default, if not test has been performed before
                        if ('' !== $currentSettings[ZHUDT_EMAIL_TEST_LAST_RESULT]) {

                            if (1 == $currentSettings[ZHUDT_EMAIL_TEST_LAST_RESULT]) {
                                esc_html_e('Success', 'zhu_dt_domain');
                            } else {
                                esc_html_e('Failed', 'zhu_dt_domain');
                            }

                            esc_html_e(' on ', 'zhu_dt_domain');
                            $when = date_create($currentSettings[ZHUDT_EMAIL_TEST_LAST_WHEN]);
                            echo $when->format(get_option('date_format') . ' ' . get_option('time_format'));
                            echo ' (', get_option('timezone_string'), ')';

                            if (0 == $currentSettings[ZHUDT_EMAIL_TEST_LAST_RESULT]) {
                                echo '<p>', esc_html__($currentSettings[ZHUDT_EMAIL_TEST_LAST_ERROR]), '</p>';
                            }
                        }
                        ?>
                    </td>
                </tr>

            </table>
            <p>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e('Send Test Email', 'zhu_dt_domain'); ?>">
            </p>
        </form>

        <?php
    }

}
