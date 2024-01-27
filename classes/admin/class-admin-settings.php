<?php

class WP_Search_Metrics_Admin_Settings {

    // Constructor
    public function __construct() {
        add_action('admin_menu', array($this, 'wp_search_metrics_add_submenus'));
    }
    
    public function wp_search_metrics_add_submenus() {
        add_submenu_page(
            'wp-search-metrics',
            __('WP Search Metrics Settings', 'wp-search-metrics'),
            __('Settings', 'wp-search-metrics'),
            'manage_options',
            'wp-search-metrics-settings',
            array($this, 'wp_search_metrics_settings_page')
        );
    }

    public function wp_search_metrics_settings_page() {
        // Check if the form was submitted
        if ( isset( $_POST['wp_search_metrics_settings_submit'] ) ) {
            // Verify nonce for security
            check_admin_referer( 'wp_search_metrics_settings_action', 'wp_search_metrics_settings_nonce' );
            
            // Set option based on whether the checkbox is checked or not
            $remove_data = isset( $_POST['wp_search_metrics_remove_data'] ) ? 'yes' : 'no';
            update_option( 'wp_search_metrics_remove_data', $remove_data );
            
            // You can add an admin notice here for feedback or simply refresh the page
        }

        // Retrieve the current setting to set the checkbox checked state
        $remove_data_checked = get_option( 'wp_search_metrics_remove_data', 'no' ) === 'yes' ? 'checked' : '';
        ?>
        <div class="wrap">
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'wp_search_metrics_settings_action', 'wp_search_metrics_settings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="wp_search_metrics_remove_data"><?php esc_html_e( 'Delete Data on Deactivation?', 'wp-search-metrics' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="wp_search_metrics_remove_data" name="wp_search_metrics_remove_data" value="yes" <?php echo $remove_data_checked; ?> />
                            <label for="wp_search_metrics_remove_data"><?php esc_html_e( 'WARNING: your metrics data will be deleted and cannot be recovered if you select this option and deactivate the plugin.', 'wp-search-metrics' ); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Changes', 'primary', 'wp_search_metrics_settings_submit' ); ?>
            </form>
        </div>
        <?php
    }
}