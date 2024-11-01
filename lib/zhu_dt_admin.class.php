<?php
defined('ABSPATH') or header("Location: /");

/**
 * Administration support for when WordPress is_admin()
 * 
 * Loaded by the main plugin file when an administration page is requested
 * 
 * @since 1.0.0
 * 
 * @see zhu-dev-tools.php
 * 
 * @author David Pullin
 */
class zhu_dt_admin {

    /**
     * Hook suffix of main menu page as returned by add_menu_page()
     * 
     * @since   1.0.0
     * @var string      
     */
    static $main_options_page = null;

    /**
     * Initialise actions for administration support
     * 
     * Invoked by WordPress's framework's init action. Registered by zhu-dev-tools.php.
     * This method registers WordPress's action admin_menu to call {@see self::on_admin_menu()}
     * and invokes interface method 'on_admin_init' on all loaded Zhu-dev-tools.
     * 
     * @since   1.0.0
     * 
     * @see zhu-dev-tools.php
     * 
     * @global array $zhu_dt_loaded_tools
     */
    public static function on_init() {
        global $zhu_dt_loaded_tools;
        /** @var array $zhu_dt_loaded_tools */
        add_action('admin_menu', array(ZHU_DT_ADMIN_CLASS_NAME, 'on_admin_menu'));

        //Call pluggable class's on_admin_init method
        if (count($zhu_dt_loaded_tools) > 0) {
            foreach ($zhu_dt_loaded_tools as $class_name) {
                $class_name::on_admin_init();
            }
        }
    }

    /**
     * Invoked by WordPress's admin_menu to start building the administration screen menu
     */
    public static function on_admin_menu() {
        self::generate_admin_menu();
    }

    /**
     * Added administration menu to Word Press.  Only applicable when user has manage_options capability.
     * 
     * @global array $submenu   Modifies WordPress's global $submenu to rename the first-sub menu, so it is not the same as the parent
     */
    private static function generate_admin_menu() {

        // Ensure that wp scripts to support meta boxes are loaded 
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');

        $slug = 'zhudt_main_options';
        $page = self::$main_options_page = add_menu_page(
                __('Zhu Dev Tools', 'zhu_dt_domain'),
                __('Zhu Dev Tools', 'zhu_dt_domain'),
                'manage_options',
                $slug,
                array(ZHU_DT_ADMIN_CLASS_NAME, 'render_general_options_page')
        );

        // register script when page loads to register meta boxes
        add_action("load-{$page}", array(ZHU_DT_ADMIN_CLASS_NAME, 'on_load_general_options_page'));

        // register script to run to render supporting Javascript in the header
        add_action("admin_head-{$page}", array(ZHU_DT_ADMIN_CLASS_NAME, 'render_general_options_page_header_script'));

        do_action('zhu_dt_generate_admin_menu', $slug);

        //wordpress places a main menu page also as the first sub-menu with the same display text
        //Change the first-sub menu to 'general'
        global $submenu;
        if (isset($submenu[$slug])) {
            $submenu[$slug][0][0] = __('General', 'zhu_dt_domain');
        }
    }

    /**
     * Create meta boxes for the general options screen
     * 
     * Invoked by WordPress's when the main options page is loaded.  
     * In turn this method invokes the add_general_options_meta_boxes() method of all loaded pluggable tools
     */
    public static function on_load_general_options_page() {
        global $zhu_dt_loaded_tools;

        if (count($zhu_dt_loaded_tools) > 0) {
            foreach ($zhu_dt_loaded_tools as $class_name) {
                $class_name::add_general_options_meta_boxes(self::$main_options_page);
            }
        }
    }

    /**
     * Renders the general options page.
     * 
     * @since   1.0.0
     */
    public static function render_general_options_page() {

        //render support for metaboxes on an admin page
        wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);
        wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);

        $screen = get_current_screen();
        $columns = absint($screen->get_columns());
        $columns_css = '';
        if ($columns) {
            $columns_css = " columns-$columns";
        }
        ?>
        <div class="wrap" style='position:relative'>

            <h2 class='wrap'>Zhu Developer Tools</h2>

            <div id="dashboard-widgets" class="metabox-holder<?php echo $columns_css; ?>">
                <div class="postbox-container column-1 normal" style='min-width:600px'>
                    <?php
                    do_meta_boxes(self::$main_options_page, 'normal', null);
                    ?>
                </div>
                <div class="postbox-container column-2 normal" style='min-width:600px'>
                    <?php
                    do_meta_boxes(self::$main_options_page, 'side', null);
                    ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renders JavaScript required by the general options page.
     * 
     * Invokes WordPress' postboxes support to enable meta box ordering and collapsing of
     * 
     * @since   1.0.0
     */
    public static function render_general_options_page_header_script() {
        ?>
        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                // add event handlers to all postboxes and screen option on the current page.
                postboxes.add_postbox_toggles('<?php echo esc_js(self::$main_options_page); ?>');

            });
            //]]>
        </script> 

        <?php
    }

}
