<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_Search_Metrics_Admin_Header {

    public function display() { 
        
        // Determine the current page
        $current_page = isset($_GET['page']) ? $_GET['page'] : '';

        // Define base classes for the menu items
        $base_classes = "transition rounded-md px-3 py-2 text-sm font-medium";
        $inactive_classes = "text-gray-300 hover:bg-gray-700 hover:text-white focus:bg-gray-700 focus:text-white";
        $active_classes = "bg-gray-700 text-white hover:text-white hover:bg-gray-600 focus:text-white focus:bg-gray-600";

        // WP Search Metrics Dashboard Page Link
        $dashboard_classes = $base_classes . ($current_page == 'wp-search-metrics' ? " " . $active_classes : " " . $inactive_classes);

        // WP Search Metrics Settings Page Link
        $settings_classes = $base_classes . ($current_page == 'wp-search-metrics-settings' ? " flex flex-row items-center justify-center gap-1 " . $active_classes : " flex flex-row items-center justify-center gap-1 " . $inactive_classes);
        
        ?>
        <!-- START Navigation -->
        <div class="bg-gray-900">
            <nav class="bg-gray-900">
                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div class="border-b border-gray-700">
                        <div class="flex h-16 items-center justify-between px-4 sm:px-0">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                            <img class="h-8 w-8" src="https://tailwindui.com/img/logos/mark.svg?color=indigo&shade=500" alt="Your Company">
                            </div>
                            <div class="block">
                            <div class="ml-10 flex items-center space-x-4">
                                <!-- Current: "bg-gray-900 text-white", Default: "text-gray-300 hover:bg-gray-700 hover:text-white" -->
                                <a href="<?php echo admin_url('admin.php?page=wp-search-metrics'); ?>" class="<?php echo esc_attr($dashboard_classes); ?>" aria-current="page">Dashboard</a>
                                <a href="<?php echo admin_url('admin.php?page=wp-search-metrics-settings'); ?>" class="<?php echo esc_attr($settings_classes); ?>">
                                    <svg class="h-5 w-5 shrink-0 text-gray-300 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <span>Settings</span>
                                </a>
                            </div>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </nav>
            <header class="py-10">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-bold tracking-tight text-white"><?php echo esc_html( get_admin_page_title() ); ?></h1>
                    <nav class="flex mt-2" aria-label="Breadcrumb">
                        <ol role="list" class="flex items-center space-x-2">
                            <li>
                            <div class="flex">
                                <a href="<?php echo admin_url('admin.php?page=wp-search-metrics'); ?>" class="text-sm transition font-medium text-gray-500 hover:text-gray-400">WP Search Metrics</a>
                            </div>
                            </li>
                            <li>
                            <div class="flex items-center">
                                <svg class="h-5 w-5 flex-shrink-0 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                </svg>
                                <span class="ml-4 text-sm font-medium text-gray-500"><?php echo esc_html( get_admin_page_title() ); ?></span>
                            </div>
                            </li>
                        </ol>
                        </nav>
                </div>
            </header>
        </div>
        <!-- END Navigation -->
    <?php }
    
}