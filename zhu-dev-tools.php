<?php

/*
  Contributors:         davidpullin
  Plugin Name:          Zhu Development Tools
  Plugin URI:           https://wordpress.org/plugins/zhu-development-tools/
  Description:          Zhu Development Tools
  Tags:                 testing, development, logging, tools
  Version:              1.1.0
  Stable Tag:           1.1.0
  Requires at least:    5.2.0
  Tested up to:         5.6
  Requires PHP:         7.3.0
  Author:               David Pullin
  Author URI:           https://ict-man.me
  License:              GPL v2 or later
  License URI:          https://www.gnu.org/licenses/gpl-2.0.en.html
 */

/*
  Copyright (C) 2021  David Pullin

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License v2 or later 
  as published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <https://www.gnu.org/licenses/gpl-2.0.en.html>.
 */

if (!defined('ABSPATH')) {
    header("Location: /");
    exit;
}


define('ZHU_DT_TOOLS_CLASS_NAME', 'zhu_dt');
define('ZHU_DT_LOG_CLASS_NAME', 'zhu_dt_log');
define('ZHU_DT_ADMIN_CLASS_NAME', 'zhu_dt_admin');
define('ZHU_DT_UPASSIST_CLASS_NAME','zhu_dt_update_assist');			// Added v1.1.0 Feb 2021

// Activator settings - these are globally available settings - @see global function get_activator_options()
define('ZHU_DT_ACTIVATOR_CLASS_NAME', 'zhu_dt_activator');
define('ZHUDT_ACTIVATOR_GROUP', 'zhudt_activator_group');
define('ZHUDT_ACTIVATOR_OPTIONS', 'zhudt_activator_options');

// The default value is true, when set, any new tools found within the tools directory 
// (for example, newly introdcued on a plugin update) will be loaded.
define('ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT', 'zhudt_activator_load_by_default');

// Client Toolbar settings - these are globally available settings = @see global function get_client_toolbar_options()
define('ZHUDT_CLIENT_TOOLBAR_GROUP', 'zhudt_client_toolbar_group');
define('ZHUDT_CLIENT_TOOLBAR_OPTIONS', 'zhudt_client_toolbar_options');
define('ZHUDT_CTB_ENABLED', 'enabled');
define('ZHUDT_CTB_USER', 'user');
define('ZHUDT_CTB_ACTIVATION_ID', 'activation_id');


// minimum required classes 
require_once __DIR__ . '/lib/zhu_dt_interface.php';
require_once __DIR__ . '/lib/zhu_dt.class.php';

global $zhu_dt_loaded_tools;
global $zhu_dt_plugin_tools_dir;
global $zhu_dt_tools_dir_spec;

$zhu_dt_plugin_tools_dir = __DIR__ . '/lib/tools/';
$zhu_dt_plugin_tools_url = plugins_url('/zhu-dev-tools/lib/tools/');

$zhu_dt_tools_dir_spec = $zhu_dt_plugin_tools_dir . 'zhu_dt_*.class.php';
$zhu_dt_loaded_tools = array();

// 3rd param = $scan_tools_directory.  Don't scan when not admin to assist with site loading times
$activator_settings = get_activator_options(true, false, is_admin());

// load dt_log pluggable if the file is present
// done early so other tools can records to the log if required

$load_early = array('zhu_dt_log');
foreach ($load_early as $class_name) {

    if (array_key_exists($class_name, $activator_settings['tools'])) {
        $to_load = $activator_settings['tools'][$class_name]['to_load'];
    } else {
        $to_load = $activator_settings[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT];
    }

    if ($to_load) {
        // internall pluggable components - comment out line to remove functionaility
        if (file_exists(__DIR__ . '/lib/tools/' . $class_name . '.class.php')) {
            include_once __DIR__ . '/lib/tools/' . $class_name . '.class.php';
            $zhu_dt_loaded_tools[$class_name] = $class_name;
        }
    }
}

//Create global function for any code to call to write to Zhu Dev Tool's log
//Function exists even if the dt_log pluggable is not loaded, which then becomes a do-nothing function so code
//which still makes calles to zhu_log() does not error.
if (!function_exists('zhu_log')) {

    /**
     * Add string to Zhu Dev Tools log if the plugin class has been loaded.
     * 
     * @since 1.0.0
     * 
     * @param string $content   string to add to database
     */
    function zhu_log(string $content) {
        if (class_exists(ZHU_DT_LOG_CLASS_NAME)) {
            zhu_dt_log::add($content);
        }
    }

}

if (count($activator_settings['tools'])) {
    foreach ($activator_settings['tools'] as $class_name => $tool_activation_settings) {

        if (!array_key_exists($class_name, $zhu_dt_loaded_tools)) {

            if (array_key_exists($class_name, $activator_settings['tools'])) {
                $to_load = $tool_activation_settings['to_load'];
            } else {
                $to_load = $activator_settings[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT];
            }

            if ($to_load) {
                if (file_exists($zhu_dt_plugin_tools_dir . $class_name . '.class.php')) {
                    include_once $zhu_dt_plugin_tools_dir . $class_name . '.class.php';

                    $zhu_dt_loaded_tools[$class_name] = $class_name;
                }
            }
        }
    }
}

//register WordPress Plugin hooks
register_activation_hook(__FILE__, array(ZHU_DT_TOOLS_CLASS_NAME, 'plugin_activation'));
register_deactivation_hook(__FILE__, array(ZHU_DT_TOOLS_CLASS_NAME, 'plugin_deactivation'));

add_action('plugins_loaded', array(ZHU_DT_TOOLS_CLASS_NAME, 'on_plugins_loaded'));
add_action('init', array(ZHU_DT_TOOLS_CLASS_NAME, 'on_init'));

//If request is for a WordPress administration screen then load this Plug's admin support class and set the init hook
if (is_admin()) {

    if (!class_exists('zhu_dt_admin')) {
        require_once __DIR__ . '/lib/zhu_dt_admin.class.php';
    }
    add_action('init', array(ZHU_DT_ADMIN_CLASS_NAME, 'on_init'));
}

function get_activator_options(bool $allowDefaults = true, bool $getDefaultsOnly = false, $scan_tools_directory = false): array {
    if ($getDefaultsOnly) {
        $opts = array();
    } else {
        $opts = get_option(ZHUDT_ACTIVATOR_OPTIONS);

        if ($opts == null) {
            $opts = array();
        }
    }

    //ensure array element exists
    if (!array_key_exists('tools', $opts)) {
        $opts['tools'] = array();
    }

    // Add defaults if setting not present 
    if ($allowDefaults || $getDefaultsOnly) {
        if (!array_key_exists(ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT, $opts)) {
            $opts[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT] = true;
        }

        // Are we to look at physical files present
        // This will detect files where not already present in the ['tools'] array and set the default loading
        // It will also remove from ['tools'] any entries where no corresponding file was found
        if ($scan_tools_directory) {
            global $zhu_dt_tools_dir_spec;
            $tools = glob($zhu_dt_tools_dir_spec);

            if (count($tools)) {

                $new_tool_detected = false;
                $scanned_tools = array();
                foreach ($tools as $file_and_class_name) {
                    $class_name = str_replace('.class.php', '', basename($file_and_class_name));


                    //dose the existing options know about this tool
                    //if not, then add and set default load
                    if (!array_key_exists($class_name, $opts['tools'])) {
                        $new_tool_detected = true;

                        $scanned_tools[$class_name] = array('to_load' => $opts[ZHUDT_ACTIVATOR_LOAD_NEW_BY_DEFAULT]);
                    } else {
                        $scanned_tools[$class_name] = $opts['tools'][$class_name];
                    }
                }

                $opts['tools'] = $scanned_tools;

                if ($new_tool_detected) {
                    //update ['tools'] with only with the ones we found.  
                    //This will remove any existing entries for where the file not longer exists
                    update_option(ZHUDT_ACTIVATOR_OPTIONS, $opts);
                }
            }

            //even if the activator tool file is not present, or default says otherwise,
            //always ensure that we have an entry for the activitor as it is to be loaded
            if (!array_key_exists(ZHU_DT_ACTIVATOR_CLASS_NAME, $opts['tools'])) {
                $opts['tools'][ZHU_DT_ACTIVATOR_CLASS_NAME] = array();
            }
            $opts['tools'][ZHU_DT_ACTIVATOR_CLASS_NAME]['to_load'] = true;
        }
    }

    return $opts;
}

function get_client_toolbar_options(bool $allowDefaults = true, bool $getDefaultsOnly = false): array {
    if ($getDefaultsOnly) {
        $opts = array();
    } else {
        $opts = get_option(ZHUDT_CLIENT_TOOLBAR_OPTIONS);

        if ($opts == null) {
            $opts = array();
        }
    }

    // Add defaults if setting not present 
    if ($allowDefaults || $getDefaultsOnly) {
        if (!array_key_exists(ZHUDT_CTB_ENABLED, $opts)) {
            //The toolbar is disabled by default, to prevent display before the settings have been check by the user (admin)
            $opts[ZHUDT_CTB_ENABLED] = false;
        }

        if (!array_key_exists(ZHUDT_CTB_ACTIVATION_ID, $opts)) {
            $opts[ZHUDT_CTB_ACTIVATION_ID] = '';
        }

        if (!array_key_exists(ZHUDT_CTB_USER, $opts)) {
            $opts[ZHUDT_CTB_USER] = 0;
        }
    }

    return $opts;
}
