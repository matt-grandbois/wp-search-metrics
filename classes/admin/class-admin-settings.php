<?php

class BBM_Search_Metrics_Admin_Settings {

    // Constructor
    public function __construct() {
        add_action('admin_menu', array($this, 'bbm_search_metrics_add_submenus'));
    }
    
    public function bbm_search_metrics_add_submenus() {
        add_submenu_page(
            'bbm-search-metrics',
            __('BBM Search Metrics Settings', 'bbm-search-metrics'),
            __('Settings', 'bbm-search-metrics'),
            'manage_options',
            'bbm-search-metrics-settings',
            array($this, 'bbm_search_metrics_settings_page')
        );
    }

    public function bbm_search_metrics_settings_page() {
        // Check if the form was submitted
        if ( isset( $_POST['bbm_search_metrics_settings_submit'] ) ) {
            // Verify nonce for security
            check_admin_referer( 'bbm_search_metrics_settings_action', 'bbm_search_metrics_settings_nonce' );
            
            // Set option based on whether the checkbox is checked or not
            $remove_data = isset( $_POST['bbm_search_metrics_remove_data'] ) ? 'yes' : 'no';
            update_option( 'bbm_search_metrics_remove_data', $remove_data );
            
            // You can add an admin notice here for feedback or simply refresh the page
        }

        // Retrieve the current setting to set the checkbox checked state
        $remove_data_checked = get_option( 'bbm_search_metrics_remove_data', 'no' ) === 'yes' ? 'checked' : '';
        ?>
        <div class="wrap">
            <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'bbm_search_metrics_settings_action', 'bbm_search_metrics_settings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="bbm_search_metrics_remove_data"><?php esc_html_e( 'Delete Data on Deactivation?', 'bbm-search-metrics' ); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="bbm_search_metrics_remove_data" name="bbm_search_metrics_remove_data" value="yes" <?php echo $remove_data_checked; ?> />
                            <label for="bbm_search_metrics_remove_data"><?php esc_html_e( 'WARNING: your metrics data will be deleted and cannot be recovered if you select this option and deactivate the plugin.', 'bbm-search-metrics' ); ?></label>
                        </td>
                    </tr>
                </table>
                <?php submit_button( 'Save Changes', 'primary', 'bbm_search_metrics_settings_submit' ); ?>
            </form>
        </div>
        <?php
    }
}