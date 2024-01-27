<?php

if ( ! class_exists( 'WP_Search_Metrics_Installer' ) ) {

    class WP_Search_Metrics_Installer {

        const OPTION_NAME = 'wp_search_metrics_remove_data';

        public static function activate() {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            self::create_tables();
            add_option( self::OPTION_NAME, 'no' );
        }

        public static function deactivate() {
            if ( get_option( self::OPTION_NAME ) === 'yes' ) {
                self::remove_tables();
                delete_option( self::OPTION_NAME );
            }
        }

        private static function create_tables() {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();

            $search_queries_table = WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE;
            $search_interactions_table = WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE;
            $post_interactions_table = WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE;

            $search_queries_sql = "CREATE TABLE {$search_queries_table} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                query_text varchar(255) NOT NULL,
                query_count bigint(20) UNSIGNED NOT NULL DEFAULT '0',
                last_searched datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY query_text (query_text)
            ) $charset_collate;";
            dbDelta( $search_queries_sql );

            $post_interactions_sql = "CREATE TABLE {$post_interactions_table} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                click_count bigint(20) UNSIGNED NOT NULL DEFAULT '0',
                last_clicked datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY post_id (post_id)
            ) $charset_collate;";
            dbDelta( $post_interactions_sql );

            $search_interactions_sql = "CREATE TABLE {$search_interactions_table} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                query_id bigint(20) UNSIGNED NOT NULL,
                post_id bigint(20) UNSIGNED,
                interaction_type VARCHAR(20) NOT NULL,
                interaction_time datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            dbDelta( $search_interactions_sql );
        }

        private static function remove_tables() {
            global $wpdb;

            $search_queries_table = WP_SEARCH_METRICS_SEARCH_QUERIES_TABLE;
            $search_interactions_table = WP_SEARCH_METRICS_SEARCH_INTERACTIONS_TABLE;
            $post_interactions_table = WP_SEARCH_METRICS_POST_INTERACTIONS_TABLE;

            $wpdb->query( "DROP TABLE IF EXISTS {$search_interactions_table}" );
            $wpdb->query( "DROP TABLE IF EXISTS {$search_queries_table}" );
            $wpdb->query( "DROP TABLE IF EXISTS {$post_interactions_table}" );
        }
    }
}