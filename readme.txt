# Zhu Development Tools for WordPress

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

== Description ==

A WordPress plugin to provide general tools to assist with WordPress development and maintenance. Each tool within this collection is pluggable, allowing you full control, over which ones are loaded.

Main features include:-

 > Internal Developer Log (database table with viewer) that can be called from code to log message, debug details etc.
 > Send A Test Email
 > List Cookies (client side)
 > Clear WordPress' Core Updater Lock & Increase PHP execution time to assist with timeout issues


== Screenshots ==

1. Client Development Toolbar Options
2. Development Database Log
3. Send Test Email
4. Cookie Viewer Options
5. Cookie Viewer on Client Development Toolbar
6. Update Assist


== Installation ==

1. Upload this plugin to your /wp-content/plugins/ directory.

== Deactivation ==

When deactivating this plugin from WordPress' Plugins maintenance any option relating to deactivating will be adhered to even if that tool is disabled (as 
long as the tool's physical file is still present). 

For example, even if you disable the loading of the Development Logging Support tool, when this plugin is deactivated, if the Logging Support's tool option to drop the log table on deactivation is set, the database table will still be deleted.

== Pluggable ==

Each tool is in itself pluggable and can be removed or disabled from your WordPress installation.

You may wish to do this to limit which tools are available on your live site.

There are two different methods of disabling a tool.

= Removing via Administration Options =

On the main general settings screen you are able to choose which tools are active.  By default tools are active and by default any new tools in 
future updates of this plugin will default to active. You can also change this behaviour by turning off the option to activate new tools by default.

= Removing via Physical File Deletion =

Each tool exists within the tools\ sub-directory.  You can disable a tool simply by deleting the file or rename it so it is no longer prefixed with zhu_dt_.

Please note, if you apply an update to this plugin the removed file will be placed back as part of the update process.  Upon restoring the file it will be treated as a newly detected tool which may be enabled depending on the option to activate new tools by default.  You can find this option on the general settings screen.



==== The Tools ====

1. Tool Activator & Client Development Toolbar
2. Generic development log database table
3. Quick email test
4. Cookie viewer
5. Update Assist

=== 1. Tool Activator & Client Development Toolbar ===

This tool provides UI support on the general settings screen to allow you to enable and disable other tools.

You cannot disable this tool via the UI but like the other files in the tool directory you can remove the file zhu_dt_activator.class.php to disable UI support.


== Client Development Toolbar ==

Some tools provide features to be available on the client side (i.e. your website not the admin area of WordPress). If there are no tools enabled that provide client side features the toolbar will not be displayed.

To prevent the toolbar being displayed for everyone, i.e. public, it is also restricted to the specified user as per its settings.  This however, will only work when that user is logged into WordPress and viewing their site.

However, there may be times, when you wish to use these tools when visiting your site as a member of the public and therefore you will not be logged into WordPress.  To achieve this, the toolbar has an option to create a browser cookie containing an Activation ID.  This ID is then matched with the ID that you will need to enter into the tool's settings.  If matched then the toolbar is rendered on the system with the matching cookie value.

The toolbar hooks into WordPress wp_body_open action to inject required HTML. wp_body_open was introduced into WordPress 5.2.0 which should be triggered via your theme after the open body html tag.  As such, if your theme does not support this then the client toolbar will not be displayed.


=== 2. Generic development log database table ===

Tool filename: zhu_dt_log.class.php

Provides a public function to call via your code to record a log into the database

    public function zhu_log(	string $content) {}
    E.g. zhu_log(‘testing my feature ‘ . $mode);

This function records, with a date a time stamp, into the database table [wp]_zhu_log.  Where [wp] is your site’s database table prefix.  Entries in this table can be viewed via this plugin’s log viewer which is accessible from the Administration Menu.  

The log can be truncated at anytime via the truncate button found on this plugin’s options page.

In the event that this tool has been disabled or zhu_dt_log.class.php has been removed any calls to zhu_log() within your own code will *not* fail. However no logging will take place as zhu_log() simply becomes a do nothing function.  This allows you to keep your logging code in place, if required, and enable/disable the actual recording to the database by enabling and disabling this tool as required.


=== 3. Email Test ===

Tool filename: zhu_dt_send_test_email.class.php

Provides, within this plugin's options screen, a quick test to send an email using WordPress' wp_mail() function.

The email address, subject and body of the email are remembered for next time. If an error is detected its details are displayed on screen.


=== 4. Cookie Viewer ===

Tool filename: zhu_dt_cookie_viewer.class.php

When enabled, this tool adds an icon to the main site's floating toolbar. The feature allows you to view cookies used by the site.  Detection is performed bia locally running JavaScript.

This pop-up viewer also provides an easy method of deleting individual cookies.


=== 5. Update Assist ===

Tool filename: zhu_dt_cookie_viewer.class.php

This tool provides a simple button to remove WordPress's Core Updater Lock. This will remove WordPress's internal "lock" that is used by WordPress to indicate that a core update is taking place.  Whan a core update fails, for example, due to a timeout issue when downloading, the lock may still be present.  As such, when you try again WordPess displays the message "Another Update in Progress".

This tool also allows you to set the value of PHP's max_execution_time.  This may assist with timeout issues when downloading updates.  After enabling this option and pressing save the tool will inform you as best it can with the results of applying the change.  Please note, that some hosting providers configure their systems so changing this value has no effect even even if PHP reports the change was successful.



== Changelog ==

1.0.1   2021.01.04      Security enhancements:
                        Additional sanitization added for processing log 
                        viewers $_REQUEST for sorting params and added nonce 
                        to ajax/log truncation.

1.1.0	2021.02.12		New: Update Assist 
