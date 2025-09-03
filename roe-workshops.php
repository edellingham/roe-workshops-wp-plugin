<?php
/**
 * Plugin Name: ROE Workshops
 * Plugin URI: https://roe24.org
 * Description: WordPress plugin to sync workshop data from FileMaker Pro database and handle registrations for Grundy/Kendall Regional Office of Education.
 * Version: 1.0.0
 * Author: Claude Code
 * License: GPL v2 or later
 * Text Domain: roe-workshops
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ROE_WORKSHOPS_VERSION', '1.0.0');
define('ROE_WORKSHOPS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ROE_WORKSHOPS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Main plugin class
class ROE_Workshops_Plugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load plugin components
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    private function load_dependencies() {
        require_once ROE_WORKSHOPS_PLUGIN_DIR . 'includes/class-odbc-connector.php';
        require_once ROE_WORKSHOPS_PLUGIN_DIR . 'includes/class-workshop-sync.php';
        require_once ROE_WORKSHOPS_PLUGIN_DIR . 'includes/class-admin-interface.php';
        require_once ROE_WORKSHOPS_PLUGIN_DIR . 'includes/class-frontend-display.php';
    }
    
    private function define_admin_hooks() {
        $admin = new ROE_Admin_Interface();
        add_action('admin_menu', array($admin, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($admin, 'enqueue_admin_scripts'));
    }
    
    private function define_public_hooks() {
        $frontend = new ROE_Frontend_Display();
        add_shortcode('roe-workshops', array($frontend, 'display_workshops'));
        add_shortcode('roe-workshop-detail', array($frontend, 'display_workshop_detail'));
        add_action('wp_enqueue_scripts', array($frontend, 'enqueue_public_scripts'));
        
        // AJAX handlers for registration
        add_action('wp_ajax_roe_register_workshop', array($frontend, 'handle_registration'));
        add_action('wp_ajax_nopriv_roe_register_workshop', array($frontend, 'handle_registration'));
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        
        // Schedule sync cron job
        if (!wp_next_scheduled('roe_workshop_sync')) {
            wp_schedule_event(time(), 'hourly', 'roe_workshop_sync');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('roe_workshop_sync');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Workshops cache table
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        $workshops_sql = "CREATE TABLE $workshops_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            workshop_number varchar(50) NOT NULL,
            title varchar(255),
            description_full text,
            start_date date,
            start_time time,
            end_time time,
            workshop_type varchar(100),
            category varchar(100),
            max_registration_count int,
            current_registration_count int,
            cost_student decimal(10,2),
            cost_employee decimal(10,2),
            web_rate decimal(10,2),
            presenters text,
            location varchar(255),
            status varchar(20),
            approved varchar(10),
            include_web varchar(50),
            last_synced datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY workshop_number (workshop_number),
            KEY status (status),
            KEY start_date (start_date)
        ) $charset_collate;";
        
        // Sessions cache table
        $sessions_table = $wpdb->prefix . 'roe_sessions';
        $sessions_sql = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            workshop_number varchar(50),
            session_date date,
            begin_time time,
            end_time time,
            location_building_room varchar(255),
            location_full varchar(500),
            last_synced datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workshop_number (workshop_number),
            KEY session_date (session_date)
        ) $charset_collate;";
        
        // Error log table
        $error_log_table = $wpdb->prefix . 'roe_error_log';
        $error_log_sql = "CREATE TABLE $error_log_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY level (level),
            KEY timestamp (timestamp)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($workshops_sql);
        dbDelta($sessions_sql);
        dbDelta($error_log_sql);
    }
    
    private function set_default_options() {
        // Default plugin settings
        add_option('roe_odbc_dsn', 'CEDARWOOD');
        add_option('roe_odbc_username', 'webuser');
        add_option('roe_odbc_password', 'PDAcedar');
        add_option('roe_sync_frequency', 'hourly');
        add_option('roe_debug_mode', false);
        add_option('roe_company_name', 'Grundy/Kendall Regional Office of Education');
        add_option('roe_company_email', 'info@roe24.org');
        add_option('roe_web_include', 'Primary Site');
    }
}

// Sync cron job handler
add_action('roe_workshop_sync', 'roe_run_workshop_sync');

function roe_run_workshop_sync() {
    if (class_exists('ROE_Workshop_Sync')) {
        $sync = new ROE_Workshop_Sync();
        $sync->sync_all_workshops();
    }
}

// Initialize the plugin
function init_roe_workshops() {
    new ROE_Workshops_Plugin();
}
add_action('plugins_loaded', 'init_roe_workshops');

// Helper function for logging
function roe_log($level, $message, $context = array()) {
    global $wpdb;
    
    // Log to WordPress error log
    error_log("[ROE-$level] $message " . (!empty($context) ? json_encode($context) : ''));
    
    // Also log to custom table
    $table = $wpdb->prefix . 'roe_error_log';
    $wpdb->insert($table, array(
        'level' => $level,
        'message' => $message,
        'context' => json_encode($context),
        'timestamp' => current_time('mysql')
    ));
}