<?php
defined('ABSPATH') or header("Location: /");

/**
 * Interface required by pluggable classes.
 * 
 * @since 1.0.0
 * 
 * This interface is used to ensure that pluggable classes implement expected methods 
 * 
 * @author David Pullin
 */
interface zhu_dt_pluggable_interface {

    /**
     * This method is invoked when this plugin is enabled by WordPress
     * 
     * @since   1.0.0
     */
    public static function on_plugin_activation();

    /**
     * This method is invoked when this plugin is disabled by WordPress
     * 
     * @since   1.0.0
     */
    public static function on_plugin_deactivation();

    /**
     * This method is invoked when all plugins has been loaded by WordPress
     */
    public static function on_plugins_loaded();
    
    /**
     * This method is invoked when the init action is invoked by WordPress
     * 
     * @since 1.0.0
     */
    public static function on_init();

    /**
     * This method is invoked when in is_admin() is set in WordPress for the tool to initialise for admin support
     * 
     * @since   1.0.0
     */
    public static function on_admin_init();

    /**
     * This method is invoked when rending the general options screen, for the plugin (if required)
     * to generate its options meta box for that screen.
     * 
     * @since 1.0.0
     * 
     * @param string|array|WP_Screen    $screen     The general options screen 
     */
    public static function add_general_options_meta_boxes($screen);
}
