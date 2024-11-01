<?php
defined('ABSPATH') or header("Location: /");

/**
 * Main supporting class for zhu-dev-tools.php
 * 
 * @since   1.0.0
 *
 * @author David Pullin
 */
class zhu_dt {

    /**
     * Invoked when WordPress's activates Zhu Dev Tools plugin.
     * 
     * In turn this method invokes the on_plugin_activation() method of all loaded tools
     * 
     * @since   1.0.0
     * 
     * @global array    $zhu_dt_loaded_tools       Holds class names of loaded tools
     */
    public static function plugin_activation() {
        global $zhu_dt_loaded_tools;

        //ensure that we call on_plugin_activation on all tools
        self::load_all_tools();

        //Call pluggable class's on_plugin_activation method
        if (count($zhu_dt_loaded_tools) > 0) {
            foreach ($zhu_dt_loaded_tools as $class_name) {
                $class_name::on_plugin_activation();
            }
        }
    }

    /**
     * Invoked when WordPress's deactivates Zhu Dev Tools plugin.
     * 
     * In turn this method invokes the on_plugin_deactivation() method of all loaded tools
     * 
     * @since   1.0.0
     * 
     * @global array    $zhu_dt_loaded_tools       Holds class names of loaded tools
     * @global string   $zhu_dt_plugin_tools_dir   Full path to the tools sub-directory
     */
    public static function plugin_deactivation() {
        global $zhu_dt_loaded_tools;

        //ensure that we call on_plugin_deactivation on all tools
        self::load_all_tools();

        //Call tool's on_plugin_activation method
        if (count($zhu_dt_loaded_tools) > 0) {
            foreach ($zhu_dt_loaded_tools as $class_name) {
                $class_name::on_plugin_deactivation();
            }
        }
    }

    /**
     * Loads all tools from the tools sub-directory.  Only loads tools not already loaded
     * 
     * @since 1.0.0
     * 
     * @global array $zhu_dt_loaded_tools           Used to determine currently loaded tools and modified if new news are loaded
     * @global string $zhu_dt_plugin_tools_dir      Used to locate the tools directory
     */
    private static function load_all_tools() {
        global $zhu_dt_loaded_tools, $zhu_dt_plugin_tools_dir;

        // load tools not loaded so their on_plugin_deactivation method is called as well
        // generation options, and instruct to look at directory and then load all 
        // tools found unless that tool is already loaded
        $opts = get_activator_options(true, false, true);
        foreach ($opts['tools'] as $class_name => $ignore_to_load_setting) {
            if (!array_key_exists($class_name, $zhu_dt_loaded_tools)) {
                if (file_exists($zhu_dt_plugin_tools_dir . $class_name . '.class.php')) {
                    include_once $zhu_dt_plugin_tools_dir . $class_name . '.class.php';

                    $zhu_dt_loaded_tools[$class_name] = $class_name;
                }
            }
        }
    }

    /**
     * Invoked when WordPress's invokes the Zhu Dev Tools 'init' action
     * 
     * In turn this method invokes the on_init() method of all loaded tools
     * 
     * @since   1.0.0
     * 
     * @global array    $zhu_dt_loaded_tools       Holds class names of loaded tools
     */
    public static function on_init() {
        global $zhu_dt_loaded_tools;

        //Call pluggable class's on_plugin_activation method
        if (count($zhu_dt_loaded_tools) > 0) {
            foreach ($zhu_dt_loaded_tools as $class_name) {
                $class_name::on_init();
            }
        }
    }

    /**
     * Invoked when WordPress's invokes the Zhu Dev Tools 'plugins_loaded' action
     * 
     * In turn this method invokes the on_plugins_loaded() method of all loaded tools
     * 
     * This method is called from WordPress's wp_body_open action, which was introduced into WordPress 5.2.0 and is also theme dependant so may not work with all themes.
     * 
     * @since   1.0.0
     * 
     * @requires_wordpress  5.2.0                  (WordPress 5.2 required PHP 5.6.20)
     * @requires_php        5.6.20                 (as required by WordPress 5.2)
     * 
     * @global array    $zhu_dt_loaded_tools       Holds class names of loaded tools
     */
    public static function on_plugins_loaded() {
        global $zhu_dt_loaded_tools;

        //Call pluggable class's on_plugin_activation method
        if (count($zhu_dt_loaded_tools) > 0) {
            foreach ($zhu_dt_loaded_tools as $class_name) {
                $class_name::on_plugins_loaded();
            }
        }

        //have any tools registered client facing content
        if (has_filter('zhu_dt_client_toolbar_content')) {

            $enable_toolbar = false;
            $toolbar_opts = get_client_toolbar_options();

            if ($toolbar_opts[ZHUDT_CTB_ENABLED]) {
                $user = wp_get_current_user();
                /** @var WP_User $user */
                if ($user->exists() && $user->ID == $toolbar_opts[ZHUDT_CTB_USER]) {
                    $enable_toolbar = true;
                } elseif ($toolbar_opts[ZHUDT_CTB_ACTIVATION_ID] != '' && array_key_exists('zhu_dt_toolbar_guid', $_COOKIE)) {
                    $enable_toolbar = (strcasecmp($_COOKIE['zhu_dt_toolbar_guid'], $toolbar_opts[ZHUDT_CTB_ACTIVATION_ID]) == 0);
                }

                if ($enable_toolbar) {
                    add_action('wp_body_open', array(ZHU_DT_TOOLS_CLASS_NAME, 'on_body_open_render_client_toolbar'));

                    //common client scripts from
                    add_action('wp_enqueue_scripts', array(ZHU_DT_TOOLS_CLASS_NAME, 'enqueue_client_toolbar_scripts'));

                    //scripts specific to a tool
                    do_action('zhu_dt_enqueue_client_scripts');
                }
            }
        }
    }

    /**
     * Enqueues scripts require to support the client toolbar.  This method is only invoked if the toolbar is going to be displayed
     * 
     * @since 1.0.0
     */
    public static function enqueue_client_toolbar_scripts() {
        wp_register_style(
                'zhu_dt_site_toolbar',
                plugins_url('zhu-dev-tools/css/zhu_dt_site_toolbar.css')
        );
        wp_enqueue_style('zhu_dt_site_toolbar');

        wp_enqueue_script("jquery");
        wp_enqueue_script("jquery-ui-draggable");

        wp_register_script('zhu_dt_site_js', plugins_url('zhu-dev-tools/js/zhu_dt_site.js'));
        wp_enqueue_script('zhu_dt_site_js');

        wp_enqueue_style('dashicons');
    }

    /**
     * Renders the client toolbar.
     * 
     * This method is invoked if the client toolbar is to be displayed.
     * 
     * This method is called from WordPress's wp_body_open action, which was introduced into WordPress 5.2.0 and is also theme dependant so may not work with all themes.
     * 
     * It calls filter zhu_dt_client_toolbar_content, for any registered tools to generate their toolbar icon and other supporting elements
     * 
     * @requires_wordpress  5.2.0
     * 
     * @since 1.0.0
     */
    public static function on_body_open_render_client_toolbar() {
        $content = apply_filters('zhu_dt_client_toolbar_content', null);
        ?>
        <div id="zhu_dt_stb_menu" class='zhu_dt_stb_box'>
            <div class='zhu_dt_stb_drag_handle'>&nbsp;Development Tools</div>
            <span class="dashicons dashicons-lock" id="zhu_dt_toolbar_guid" title="Show Activation ID"></span>
            <?php echo $content; ?>
        </div>
        <?php
    }

}
