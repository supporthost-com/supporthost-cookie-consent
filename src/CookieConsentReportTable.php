<?php

namespace SupportHostCookieConsent;

use WP_List_Table;

/**
 * Class CookieConsentReportTable
 *
 * @package SupportHostCookieConsent
 * @author  Ivan.Messina
 * Date: 27. 7. 2022
 */

// Load the parent class if it doesn't exist.
if ( ! class_exists( 'WP_List_Table' ) )
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Create Table
 */
if ( !class_exists( 'CookieConsentReportTable' ) ) {
    class CookieConsentReportTable extends WP_List_Table {

        /**
         * Get a list of columns.
         *
         * @return array
         */

        private $consents;

        // Get table data
        private function get_consents( $search = '' ) {
            global $wpdb;

            $cookieconsent = new CookieConsent;
            $tablename = $cookieconsent->getTableName();

            if (!empty($search)) {
                  return $wpdb->get_results(
                        "SELECT * from {$tablename} WHERE id Like '%{$search}%' OR level Like '%{$search}%' OR data Like '%{$search}%'",
                        ARRAY_A
                  );
            }else{
                  return $wpdb->get_results(
                        "SELECT * from {$tablename}",
                        ARRAY_A
                  );
            }
        }

        // Define table columns
        public function get_columns() {
            return array(
                'cb'        => '<input type="checkbox" />',
                'IP'        => __('IP Address', 'supporthost-cookie-consent'),
                'date'      => __('Visited date', 'supporthost-cookie-consent'),
                'cookies'   => __('Cookie details', 'supporthost-cookie-consent'),
                'user_ID'   => __('User ID', 'supporthost-cookie-consent')
            );
        }

        /**
         * Prepares the list of items for displaying.
         */
        public function prepare_items() {
            if (isset($_POST['page']) && isset($_POST['s'])) {
                $this->consents = $this->get_consents($_POST['s']);
            } else {
                $this->consents = $this->get_consents();
            }

            $columns  = $this->get_columns();
            $hidden   = array('');
            $primary  = 'name';
            $this->_column_headers = array( $columns, $hidden, $primary );

            /* Pagination */
            $per_page = 50;
            $current_page = $this->get_pagenum();
            $total_items = count($this->consents);

            $this->consents = array_slice($this->consents, (($current_page - 1) * $per_page), $per_page);

            $this->set_pagination_args(array(
                  'total_items' => $total_items, // total number of items
                  'per_page'    => $per_page, // items to show on a page
                  'total_pages' => ceil( $total_items / $per_page )
            ));

            $this->items = $this->consents;

        }

        /**
         * Generates content for a single row of the table.
         * 
         * @param object $item The current item.
         * @param string $column_name The current column name.
         */
        protected function column_default( $item, $column_name ) {
            $ipdata = unserialize( $item['data'] );
            $level = array_flip( json_decode( $item['level'] ) );
            $categories = [ 'necessary' => __('Necessary', 'supporthost-cookie-consent'), 'analytics' => __('Analytics', 'supporthost-cookie-consent'), 'marketing' => __('Marketing', 'supporthost-cookie-consent'), 'other' => __('Other', 'supporthost-cookie-consent') ];

            switch ( $column_name ) {
                case 'IP':
                    return $ipdata['ip'];
                case 'date':
                    return esc_html( $item['timestamp'] );
                case 'cookies':
                    $cookies = '';
                    foreach ( $categories as $key => $value ) {
                        $cookie = ( isset($level[$key]) ) ? __('Yes', 'supporthost-cookie-consent') : __('No', 'supporthost-cookie-consent');
                        $cookies .= '<tr><td class="cli-report-td">' . $value . '</td><td class="cli-report-td"> : </td><td class="cli-report-td"><b>' . $cookie . '</b></td></tr>';
                    }
                    return '<table><tbody>' . $cookies . '</tbody></table>';
                case 'user_ID':
                    return $ipdata['cookie_id'];
                default:
                    return;

            }
        }

        // To show checkbox with each row
        function column_cb($item)
        {
              return sprintf(
                    '<input type="checkbox" name="consent[]" value="%s" />',
                    $item['id']
              );
        }

        /**
         * Generates content for a single row of the table.
         *
         * @param object $item The current item.
         */
        public function single_row( $item ) {
            echo '<tr>';
            $this->single_row_columns( $item );
            echo '</tr>';
        }

        // To show bulk action dropdown
        function get_bulk_actions()
        {
                $actions = array(
                    'delete_all'    => 'Delete',
                );
                return $actions;
        }
    }
}