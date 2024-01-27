<?php

require_once( WP_SEARCH_METRICS_DIR_PATH . 'classes/admin/ui/class-admin-header.php' );

class WP_Search_Metrics_Admin_Settings {

    protected static $instance = null;
    
    private $dashboard_page_hook_suffix;
    private $header;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'wp_search_metrics_add_submenus'));
        $this->header = new WP_Search_Metrics_Admin_Header();
    }
    
    public function wp_search_metrics_add_submenus() {
        $this->dashboard_page_hook_suffix = add_submenu_page(
            'wp-search-metrics',
            __('Settings', 'wp-search-metrics'),
            __('Settings', 'wp-search-metrics'),
            'manage_options',
            'wp-search-metrics-settings',
            array($this, 'wp_search_metrics_settings_page')
        );

        add_action('admin_enqueue_scripts', array($this, 'wp_search_metrics_enqueue_admin_scripts_settings'));
    }

    public function wp_search_metrics_settings_page() {
        // Check if the form was submitted
        if ( isset( $_POST['wp_search_metrics_settings_submit'] ) ) {
            // Verify nonce for security
            check_admin_referer( 'wp_search_metrics_settings_action', 'wp_search_metrics_settings_nonce' );
            
            // Set option based on whether the checkbox is checked or not
            $remove_data = isset( $_POST['wp_search_metrics_remove_data'] ) && 'yes' === $_POST['wp_search_metrics_remove_data'] ? 'yes' : 'no';
            update_option( 'wp_search_metrics_remove_data', $remove_data );
        }
        ?>
        <div class="h-full bg-gray-100">
            <div class="min-h-full">
                <?php $this->header->display(); ?>

                <main class="py-8 bg-gray-100 mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="px-4 sm:px-0">
                        <form method="post" action="">
                            <?php wp_nonce_field( 'wp_search_metrics_settings_action', 'wp_search_metrics_settings_nonce' ); ?>

                            <!-- START Delete Data Setting -->
                            <input type="hidden" id="wp_search_metrics_remove_data" name="wp_search_metrics_remove_data" value="<?php echo get_option( 'wp_search_metrics_remove_data', 'no' ); ?>" />
                            <div class="flex items-center justify-between gap-4">
                                <span class="flex flex-grow flex-col">
                                    <span class="text-sm font-medium leading-6 text-gray-900" id="availability-label"><?php esc_html_e( 'Delete Data on Deactivation?', 'wp-search-metrics' ); ?></span>
                                    <span class="text-sm text-gray-500" id="availability-description"><?php esc_html_e( 'WARNING: when you select this option, your metrics data will be deleted upon deactivation of this plugin and cannot be recovered.', 'wp-search-metrics' ); ?></span>
                                </span>
                                <!-- Enabled: "bg-indigo-600", Not Enabled: "bg-gray-200" -->
                                <button data-settings-remove-data type="button" class="bg-gray-200 relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2" role="switch" aria-checked="false">
                                    <span class="sr-only">Use setting</span>
                                    <!-- Enabled: "translate-x-5", Not Enabled: "translate-x-0" -->
                                    <span data-toggle-switch="data-settings-remove-data" class="translate-x-0 pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out">
                                        <!-- Enabled: "opacity-0 duration-100 ease-out", Not Enabled: "opacity-100 duration-200 ease-in" -->
                                        <span data-toggle-switch-icon="no" class="opacity-100 duration-200 ease-in absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true">
                                            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                                <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </span>
                                        <!-- Enabled: "opacity-100 duration-200 ease-in", Not Enabled: "opacity-0 duration-100 ease-out" -->
                                        <span data-toggle-switch-icon="yes" class="opacity-0 duration-100 ease-out absolute inset-0 flex h-full w-full items-center justify-center transition-opacity" aria-hidden="true">
                                            <svg class="h-3 w-3 text-indigo-600" fill="currentColor" viewBox="0 0 12 12">
                                                <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                                            </svg>
                                        </span>
                                    </span>
                                </button>
                            </div>
                            <!-- END Delete Data Setting -->
                            <button type="submit" name="wp_search_metrics_settings_submit" class="mt-12 transition rounded-md bg-indigo-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <?php esc_html_e( 'Save Changes', 'wp-search-metrics' ); ?>
                            </button>
                        </form>
                    </div>
                </main>
            </div>
        </div>
        <?php
    }

    public function wp_search_metrics_enqueue_admin_scripts_settings($hook_suffix) {
        if ($hook_suffix === $this->dashboard_page_hook_suffix) {
            wp_enqueue_script(
                'wp-search-metrics-tailwind-css',
                'https://cdn.tailwindcss.com',
                array(),
                '4.4.1',
                false // in header
            );

			wp_enqueue_style(
				'wp-search-metrics-css',
				plugins_url('/src/css/admin/settings.css', dirname(dirname(__FILE__))),
				array(),
				'1.0',
				'all'
			);
			
            wp_enqueue_script(
                'wp-search-metrics-admin-settings-js',
                plugins_url('/src/js/admin/settings.js', dirname(dirname(__FILE__))),
                array(), // no dependencies
                '1.0',
                true // In footer
            );
        }
    }
}