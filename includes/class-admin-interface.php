<?php
/**
 * ROE Admin Interface
 * WordPress admin pages for workshop management
 */

class ROE_Admin_Interface {
    
    public function __construct() {
        // Constructor
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            'ROE Workshops',
            'ROE Workshops',
            'manage_options',
            'roe-workshops',
            array($this, 'admin_dashboard_page'),
            'dashicons-calendar-alt',
            25
        );
        
        // Submenu pages
        add_submenu_page(
            'roe-workshops',
            'Workshop List',
            'Workshops',
            'manage_options',
            'roe-workshops',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'roe-workshops',
            'Sync Settings',
            'Sync Settings',
            'manage_options',
            'roe-sync-settings',
            array($this, 'sync_settings_page')
        );
        
        add_submenu_page(
            'roe-workshops',
            'Error Logs',
            'Error Logs',
            'manage_options',
            'roe-error-logs',
            array($this, 'error_logs_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'roe-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'roe-admin-style',
            ROE_WORKSHOPS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ROE_WORKSHOPS_VERSION
        );
        
        wp_enqueue_script(
            'roe-admin-script',
            ROE_WORKSHOPS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ROE_WORKSHOPS_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('roe-admin-script', 'roe_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('roe_admin_nonce')
        ));
    }
    
    /**
     * Main dashboard page
     */
    public function admin_dashboard_page() {
        // Handle actions
        if (isset($_POST['sync_workshops'])) {
            check_admin_referer('roe_sync_workshops');
            $this->trigger_manual_sync();
        }
        
        if (isset($_POST['test_connection'])) {
            check_admin_referer('roe_test_connection');
            $this->test_odbc_connection();
        }
        
        // Get workshop data for display
        global $wpdb;
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        
        $workshops = $wpdb->get_results(
            "SELECT * FROM $workshops_table 
             ORDER BY start_date ASC, start_time ASC 
             LIMIT 20"
        );
        
        $total_workshops = $wpdb->get_var("SELECT COUNT(*) FROM $workshops_table");
        $last_sync = get_option('roe_last_sync_time', 'Never');
        
        include ROE_WORKSHOPS_PLUGIN_DIR . 'admin/admin-dashboard.php';
    }
    
    /**
     * Sync settings page
     */
    public function sync_settings_page() {
        // Save settings if form submitted
        if (isset($_POST['save_settings'])) {
            check_admin_referer('roe_save_settings');
            $this->save_sync_settings();
        }
        
        // Get current settings
        $settings = array(
            'odbc_dsn' => get_option('roe_odbc_dsn', 'CEDARWOOD'),
            'odbc_username' => get_option('roe_odbc_username', 'webuser'),
            'sync_frequency' => get_option('roe_sync_frequency', 'hourly'),
            'debug_mode' => get_option('roe_debug_mode', false),
            'company_name' => get_option('roe_company_name', 'Grundy/Kendall Regional Office of Education'),
            'company_email' => get_option('roe_company_email', 'info@roe24.org'),
            'web_include' => get_option('roe_web_include', 'Primary Site')
        );
        
        include ROE_WORKSHOPS_PLUGIN_DIR . 'admin/sync-settings.php';
    }
    
    /**
     * Error logs page
     */
    public function error_logs_page() {
        global $wpdb;
        $error_log_table = $wpdb->prefix . 'roe_error_log';
        
        // Handle log clearing
        if (isset($_POST['clear_logs'])) {
            check_admin_referer('roe_clear_logs');
            $wpdb->query("DELETE FROM $error_log_table");
            echo '<div class="notice notice-success"><p>Error logs cleared.</p></div>';
        }
        
        // Get recent error logs
        $logs = $wpdb->get_results(
            "SELECT * FROM $error_log_table 
             ORDER BY timestamp DESC 
             LIMIT 100"
        );
        
        include ROE_WORKSHOPS_PLUGIN_DIR . 'admin/error-logs.php';
    }
    
    /**
     * Trigger manual workshop sync
     */
    private function trigger_manual_sync() {
        try {
            $sync = new ROE_Workshop_Sync();
            $result = $sync->sync_all_workshops();
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>Sync completed successfully. ' . 
                     $result['workshops_synced'] . ' workshops synced.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Sync failed: ' . 
                     esc_html($result['error']) . '</p></div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>Sync failed: ' . 
                 esc_html($e->getMessage()) . '</p></div>';
        }
    }
    
    /**
     * Test ODBC connection
     */
    private function test_odbc_connection() {
        $connector = new ROE_ODBC_Connector();
        $test_result = $connector->test_connection();
        
        if ($test_result['success']) {
            echo '<div class="notice notice-success"><p>Connection test successful! Found ' . 
                 esc_html($test_result['workshop_count']) . ' workshops in database.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Connection test failed: ' . 
                 esc_html($test_result['message']) . '</p></div>';
        }
    }
    
    /**
     * Save sync settings
     */
    private function save_sync_settings() {
        // Sanitize and save settings
        update_option('roe_odbc_dsn', sanitize_text_field($_POST['odbc_dsn']));
        update_option('roe_odbc_username', sanitize_text_field($_POST['odbc_username']));
        update_option('roe_sync_frequency', sanitize_text_field($_POST['sync_frequency']));
        update_option('roe_debug_mode', isset($_POST['debug_mode']));
        update_option('roe_company_name', sanitize_text_field($_POST['company_name']));
        update_option('roe_company_email', sanitize_email($_POST['company_email']));
        update_option('roe_web_include', sanitize_text_field($_POST['web_include']));
        
        // Handle password separately (only update if provided)
        if (!empty($_POST['odbc_password'])) {
            update_option('roe_odbc_password', sanitize_text_field($_POST['odbc_password']));
        }
        
        // Reschedule cron job if frequency changed
        wp_clear_scheduled_hook('roe_workshop_sync');
        if (!wp_next_scheduled('roe_workshop_sync')) {
            wp_schedule_event(time(), sanitize_text_field($_POST['sync_frequency']), 'roe_workshop_sync');
        }
        
        echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
    }
}