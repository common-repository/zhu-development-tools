<?php
defined('ABSPATH') or header("Location: /");

if (!class_exists('WP_List_Table')) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Class to support viewing of the Zhu Dev Tool's log table
 * 
 * Renders entries from the ???_zhu_log table using WordPress's WP_List_Table UI
 * 
 * @since 1.0.0
 *
 * @author David Pullin
 */
class zhu_dt_log_viewer extends WP_List_Table {

    public function __construct() {
        $args = array(
            'plural' => 'zhu_dt_log_viewer'                  // zhu_log_viewer is the call applied to the html table element
        );
        parent::__construct($args);
    }

    /*
     * Overrides WP_List_Table's get_columns to return the list of columns to display
     * 
     * @since 1.0.0
     * 
     * @return array
     */

    public function get_columns() {
        return array(
            'id' => 'ID',
            'log_date' => 'Logged on',
            'log_content' => 'Content'
        );
    }

    /**
     * Overrides WP_List_Table's get_sortable_columns to return the list of columns that support sorting
     * 
     * @since 1.0.0
     * 
     * @return array
     */
    public function get_sortable_columns() {
        return array(
            'id' => array('id', 'desc'),
            'log_date' => array('log_date', 'asc')          //our default is DESC so set ASC  for default arrow up
        );
    }

    /**
     * Overrides WP_List_Table's get_hidden_columns to return the list of hidden columns 
     * 
     * @since 1.0.0
     * 
     * @param string|WP_Screen $screen The screen you want the hidden columns for
     * 
     * @return array
     */
    public function get_hidden_columns($screen) {
        return array();
    }

    /**
     * Overrides WP_List_Table's prepare_items to configure and populate the list's data
     * 
     * @since 1.0.0
     * 
     * @requires_php 7.1
     * 
     * @global wpdb $wpdb
     */
    public function prepare_items() {
        global $wpdb;
        /** @var wpdb $wpdb */
        //process action: delete
        $action = (array_key_exists('action', $_REQUEST)) ? sanitize_text_field($_REQUEST['action']) : 1;
        $id = (array_key_exists('id', $_REQUEST)) ? (int) $_REQUEST['id'] : 1;
        switch ($action) {
            case 'delete':
                if ($id > 0) {
                    $wpdb->delete("{$wpdb->base_prefix}zhu_log",
                            array('id' => $id),
                            array('%d')
                    );
                }
        }

        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns(null);
        $sortable = $this->get_sortable_columns();
        // list() language construct & its shorthand introduced in v7.1 of PHP          https://www.php.net/manual/en/function.list.php
        ['orderby' => $orderby, 'order' => $order] = $this->get_orderby($columns, 'id', 'desc');
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $items_per_page = 10;
        $num_rows = $this->get_row_count();

        if ($num_rows > 0) {
            $limit = $this->set_paging($num_rows, $items_per_page);

            $this->items = $wpdb->get_results("
                    SELECT id, log_date, log_content
                    FROM {$wpdb->base_prefix}zhu_log
                    ORDER BY {$orderby} {$order}
                    LIMIT {$limit}
                    "
            );
        }
    }

    /**
     * Returns order by details, either from defaults or the the URL
     * 
     * @since 1.0.0
     * 
     * @param array $columns                Columns on display
     * @param string $default_orderby       Name of column to default if not present in the URL
     * @param string $default_order         Default sort order of 'asc' or 'desc' if not present in the URL
     * @return array [
     *      'orderby' => (string) Name of field to order the list.
     *      'order' => (string) Sort order.  Either set to 'asc' or 'desc'.
     * ]
     */
    private function get_orderby(array $columns, string $default_orderby, string $default_order) {


        if (array_key_exists('orderby', $_GET) && array_key_exists($_GET['orderby'], $columns)) {
            $orderby = sanitize_text_field($_GET['orderby']);
        } else {
            $orderby = $default_orderby;
        }

        if (array_key_exists('order', $_GET)) {
            $order = ('asc' === (sanitize_text_field($_GET['order']))) ? 'asc' : 'desc';
        } else {
            $order = $default_order;
        }

        return ['orderby' => $orderby, 'order' => $order];
    }

    /**
     * Overrides WP_List_Table's column_default to display cell content if no cell specific method exists
     * 
     * @since 1.0.0
     * 
     * @param object $item
     * @param string $column_name
     * 
     * @return string   HTML to display
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'log_content':
                return esc_html($item->$column_name);

            case 'log_date':
                return $item->$column_name;
            default:
                return sprintf(__("unexpected column_name of %s in ", 'zhu_dt_domain') . __METHOD__, $column_name);
        }
    }

    /**
     * Called from WP_list_table when rendering a ID cell
     * 
     * @since 1.0.0
     * 
     * @param object    $item
     * 
     * @return string   HTML to display
     */
    public function column_id($item) {

        $delete_word = __('Delete', 'zhu_dt_domain');
        $actions = array(
            'trash' => sprintf("<a href='?page=zhu_dt_log_view_log&action=delete&id=%s'>{$delete_word}</a>", $item->id),
        );

        return sprintf('%1$s %2$s', $item->id, $this->row_actions($actions));
    }

    /**
     * Returns the number of rows in the log
     * 
     * @since 1.0.0
     * 
     * @global wpdb $wpdb
     * 
     * @return int  number of rows
     */
    private function get_row_count(): int {
        global $wpdb;
        /** @var wpdb $wpdb */
        $res = $wpdb->get_results("SELECT COUNT(*) AS 'county' FROM {$wpdb->base_prefix}zhu_log");
        return $res[0]->county;
    }

    /**
     * Sets the paging details and determined the LIMIT parameters to be used in the SQL query
     * 
     * This method calls WP_list_table's set_pagination_args() to configure the number of pages
     * 
     * @since 1.0.0
     * 
     * @param int $num_rows         Number of rows in the table
     * @param int $items_per_page   Number of rows per page
     * 
     * @return string       parameters to use for the SQL Limit statement. E.g. '5' or '5,10' if paging.
     */
    private function set_paging(int $num_rows, int $items_per_page): string {

        $page_no = (array_key_exists('paged', $_REQUEST)) ? (int) $_REQUEST['paged'] : 1;
        $num_pages = $num_rows / $items_per_page;

        if ($num_pages > 1) {

            //round to nearest whole number
            if ((int) ($num_pages) < $num_pages) {
                $num_pages = (int) ($num_pages);
                $num_pages++;
            }

            if ($page_no <= 1) {
                $limit = $items_per_page;
            } else {
                $from_row = $items_per_page * ($page_no - 1);
                if ($from_row > $num_rows) {
                    $limit = (string) $items_per_page;
                } else {
                    $limit = "{$from_row},{$items_per_page}";
                }
            }
        } else {
            $num_pages = 1;
            $limit = (string) $num_rows;
        }

        $this->set_pagination_args(array(
            "total_items" => $num_rows,
            "total_pages" => $num_pages,
            "per_page" => $items_per_page,
        ));

        return $limit;
    }

}
