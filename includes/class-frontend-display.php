<?php
/**
 * ROE Frontend Display
 * Handles public-facing workshop display and registration
 */

class ROE_Frontend_Display {
    
    private $connector;
    
    public function __construct() {
        // Choose connection method based on settings
        $connection_method = get_option('roe_connection_method', 'api');
        
        if ($connection_method === 'api') {
            $this->connector = new ROE_API_Connector();
        } else {
            $this->connector = new ROE_ODBC_Connector();
        }
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style(
            'roe-public-style',
            ROE_WORKSHOPS_PLUGIN_URL . 'assets/css/public.css',
            array(),
            ROE_WORKSHOPS_VERSION
        );
        
        wp_enqueue_script(
            'roe-public-script',
            ROE_WORKSHOPS_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            ROE_WORKSHOPS_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('roe-public-script', 'roe_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('roe_public_nonce')
        ));
    }
    
    /**
     * Display workshops shortcode handler
     * Usage: [roe-workshops] or [roe-workshops category="Technology" limit="10"]
     */
    public function display_workshops($atts) {
        $atts = shortcode_atts(array(
            'limit' => 20,
            'category' => '',
            'upcoming' => 'true',
            'show_search' => 'true'
        ), $atts);
        
        global $wpdb;
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        
        // Build query
        $where_conditions = array("status = 'Active'", "approved = 'Yes'");
        
        if ($atts['upcoming'] === 'true') {
            $where_conditions[] = "start_date >= CURDATE()";
        }
        
        if (!empty($atts['category'])) {
            $where_conditions[] = $wpdb->prepare("workshop_type LIKE %s", '%' . $atts['category'] . '%');
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $limit = intval($atts['limit']);
        
        $query = "SELECT * FROM $workshops_table 
                 WHERE $where_clause 
                 ORDER BY start_date ASC, start_time ASC 
                 LIMIT $limit";
        
        $workshops = $wpdb->get_results($query);
        
        // Start output buffering
        ob_start();
        
        // Include template
        include ROE_WORKSHOPS_PLUGIN_DIR . 'templates/workshop-list-template.php';
        
        return ob_get_clean();
    }
    
    /**
     * Display single workshop detail
     * Usage: [roe-workshop-detail number="WS123"] or auto-detect from URL
     */
    public function display_workshop_detail($atts) {
        $atts = shortcode_atts(array(
            'number' => ''
        ), $atts);
        
        // Get workshop number from shortcode or URL parameter
        $workshop_number = $atts['number'];
        if (empty($workshop_number)) {
            $workshop_number = isset($_GET['workshop']) ? sanitize_text_field($_GET['workshop']) : '';
        }
        
        if (empty($workshop_number)) {
            return '<p>No workshop specified.</p>';
        }
        
        global $wpdb;
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        $sessions_table = $wpdb->prefix . 'roe_sessions';
        
        // Get workshop from cache first
        $workshop = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $workshops_table WHERE workshop_number = %s",
                $workshop_number
            )
        );
        
        // If not in cache, try to sync from FileMaker
        if (!$workshop) {
            $sync = new ROE_Workshop_Sync();
            if ($sync->sync_single_workshop($workshop_number)) {
                $workshop = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM $workshops_table WHERE workshop_number = %s",
                        $workshop_number
                    )
                );
            }
        }
        
        if (!$workshop) {
            return '<p>Workshop not found.</p>';
        }
        
        // Get sessions
        $sessions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $sessions_table WHERE workshop_number = %s ORDER BY session_date ASC, begin_time ASC",
                $workshop_number
            )
        );
        
        // Start output buffering
        ob_start();
        
        // Include template
        include ROE_WORKSHOPS_PLUGIN_DIR . 'templates/workshop-detail-template.php';
        
        return ob_get_clean();
    }
    
    /**
     * Handle workshop registration AJAX
     */
    public function handle_registration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'roe_public_nonce')) {
            wp_die('Security check failed');
        }
        
        // Sanitize input data
        $workshop_number = sanitize_text_field($_POST['workshop_number']);
        $user_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'organization' => sanitize_text_field($_POST['organization']),
            'address' => sanitize_textarea_field($_POST['address']),
            'city' => sanitize_text_field($_POST['city']),
            'state' => sanitize_text_field($_POST['state']),
            'zip' => sanitize_text_field($_POST['zip'])
        );
        
        // Validate required fields
        $validation_errors = array();
        
        if (empty($user_data['first_name'])) {
            $validation_errors[] = 'First name is required';
        }
        if (empty($user_data['last_name'])) {
            $validation_errors[] = 'Last name is required';
        }
        if (empty($user_data['email']) || !is_email($user_data['email'])) {
            $validation_errors[] = 'Valid email address is required';
        }
        if (empty($workshop_number)) {
            $validation_errors[] = 'Workshop number is required';
        }
        
        if (!empty($validation_errors)) {
            wp_send_json_error(array(
                'message' => 'Validation failed',
                'errors' => $validation_errors
            ));
        }
        
        // Check workshop availability
        $availability = $this->connector->check_workshop_availability($workshop_number);
        
        if (!$availability) {
            wp_send_json_error(array(
                'message' => 'Workshop not found or not available for registration'
            ));
        }
        
        if (!$availability['available']) {
            wp_send_json_error(array(
                'message' => 'Workshop is full. Registration capacity: ' . $availability['max_count']
            ));
        }
        
        // Process registration
        try {
            $registration_success = $this->connector->register_user_for_workshop($workshop_number, $user_data);
            
            if ($registration_success) {
                // Send confirmation email
                $this->send_confirmation_email($workshop_number, $user_data);
                
                wp_send_json_success(array(
                    'message' => 'Registration successful! You will receive a confirmation email shortly.',
                    'workshop_title' => $availability['title']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Registration failed. Please try again or contact us for assistance.'
                ));
            }
            
        } catch (Exception $e) {
            roe_log('ERROR', 'Registration processing failed', array(
                'workshop_number' => $workshop_number,
                'email' => $user_data['email'],
                'error' => $e->getMessage()
            ));
            
            wp_send_json_error(array(
                'message' => 'Registration failed due to a system error. Please try again later.'
            ));
        }
    }
    
    /**
     * Send registration confirmation email
     * @param string $workshop_number Workshop identifier
     * @param array $user_data User registration data
     */
    private function send_confirmation_email($workshop_number, $user_data) {
        try {
            global $wpdb;
            $workshops_table = $wpdb->prefix . 'roe_workshops';
            
            // Get workshop details
            $workshop = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM $workshops_table WHERE workshop_number = %s",
                    $workshop_number
                )
            );
            
            if (!$workshop) {
                return false;
            }
            
            $company_name = get_option('roe_company_name');
            $company_email = get_option('roe_company_email');
            
            $to = $user_data['email'];
            $subject = 'Registration Confirmation - ' . $workshop->title;
            
            $message = "Dear " . $user_data['first_name'] . " " . $user_data['last_name'] . ",\n\n";
            $message .= "Thank you for registering for: " . $workshop->title . "\n\n";
            $message .= "Workshop Details:\n";
            $message .= "Date: " . date('F j, Y', strtotime($workshop->start_date)) . "\n";
            if ($workshop->start_time) {
                $message .= "Time: " . date('g:i A', strtotime($workshop->start_time)) . "\n";
            }
            if ($workshop->location) {
                $message .= "Location: " . $workshop->location . "\n";
            }
            $message .= "\nYou will receive additional details and directions approximately two weeks before the workshop.\n\n";
            $message .= "If you have any questions, please contact us at " . $company_email . "\n\n";
            $message .= "Sincerely,\n" . $company_name;
            
            $headers = array(
                'From: ' . $company_name . ' <' . $company_email . '>',
                'Content-Type: text/plain; charset=UTF-8'
            );
            
            wp_mail($to, $subject, $message, $headers);
            
            roe_log('INFO', 'Confirmation email sent', array(
                'workshop_number' => $workshop_number,
                'email' => $user_data['email'],
                'workshop_title' => $workshop->title
            ));
            
            return true;
            
        } catch (Exception $e) {
            roe_log('ERROR', 'Failed to send confirmation email', array(
                'workshop_number' => $workshop_number,
                'email' => $user_data['email'],
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Get workshops from cache with search/filter support
     * @param array $args Query arguments
     * @return array Workshop results
     */
    public function get_workshops_for_display($args = array()) {
        global $wpdb;
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'search' => '',
            'category' => '',
            'upcoming_only' => true,
            'order_by' => 'start_date',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where_conditions = array("status = 'Active'", "approved = 'Yes'");
        
        if ($args['upcoming_only']) {
            $where_conditions[] = "start_date >= CURDATE()";
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_conditions[] = $wpdb->prepare(
                "(title LIKE %s OR description_full LIKE %s OR presenters LIKE %s)",
                $search_term, $search_term, $search_term
            );
        }
        
        if (!empty($args['category'])) {
            $where_conditions[] = $wpdb->prepare(
                "workshop_type LIKE %s",
                '%' . $args['category'] . '%'
            );
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        $limit = intval($args['limit']);
        $offset = intval($args['offset']);
        
        $query = "SELECT * FROM $workshops_table 
                 WHERE $where_clause 
                 ORDER BY $order_by 
                 LIMIT $limit OFFSET $offset";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get total workshop count for pagination
     * @param array $args Query arguments (same as get_workshops_for_display)
     * @return int Total count
     */
    public function get_workshops_count($args = array()) {
        global $wpdb;
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        
        $defaults = array(
            'search' => '',
            'category' => '',
            'upcoming_only' => true
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause (same as display function)
        $where_conditions = array("status = 'Active'", "approved = 'Yes'");
        
        if ($args['upcoming_only']) {
            $where_conditions[] = "start_date >= CURDATE()";
        }
        
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_conditions[] = $wpdb->prepare(
                "(title LIKE %s OR description_full LIKE %s OR presenters LIKE %s)",
                $search_term, $search_term, $search_term
            );
        }
        
        if (!empty($args['category'])) {
            $where_conditions[] = $wpdb->prepare(
                "workshop_type LIKE %s",
                '%' . $args['category'] . '%'
            );
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        return $wpdb->get_var("SELECT COUNT(*) FROM $workshops_table WHERE $where_clause");
    }
    
    /**
     * Get available workshop categories for filter dropdown
     * @return array Unique categories
     */
    public function get_workshop_categories() {
        global $wpdb;
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        
        $categories = $wpdb->get_col(
            "SELECT DISTINCT workshop_type FROM $workshops_table 
             WHERE status = 'Active' AND approved = 'Yes' 
             AND workshop_type IS NOT NULL AND workshop_type != '' 
             ORDER BY workshop_type ASC"
        );
        
        return array_filter($categories);
    }
    
    /**
     * Format workshop date for display
     * @param string $date Workshop date
     * @param string $time Workshop time
     * @return string Formatted date/time
     */
    public function format_workshop_datetime($date, $time = '') {
        if (empty($date)) {
            return 'Date TBD';
        }
        
        $formatted = date('F j, Y', strtotime($date));
        
        if (!empty($time) && $time !== '00:00:00') {
            $formatted .= ' at ' . date('g:i A', strtotime($time));
        }
        
        return $formatted;
    }
    
    /**
     * Check if workshop has available spots
     * @param object $workshop Workshop object
     * @return bool True if available
     */
    public function is_workshop_available($workshop) {
        return (
            $workshop->status === 'Active' &&
            $workshop->approved === 'Yes' &&
            $workshop->current_registration_count < $workshop->max_registration_count &&
            strtotime($workshop->start_date) >= strtotime(date('Y-m-d'))
        );
    }
    
    /**
     * Get registration spots info
     * @param object $workshop Workshop object
     * @return string Formatted spots info
     */
    public function get_registration_spots_info($workshop) {
        $current = $workshop->current_registration_count;
        $max = $workshop->max_registration_count;
        $available = $max - $current;
        
        if ($available <= 0) {
            return '<span style="color: #dc3232; font-weight: bold;">FULL</span>';
        } elseif ($available <= 3) {
            return '<span style="color: #ff8c00; font-weight: bold;">' . $available . ' spots left</span>';
        } else {
            return '<span style="color: #00a32a;">' . $available . ' spots available</span>';
        }
    }
    
    /**
     * Generate registration form HTML
     * @param string $workshop_number Workshop identifier
     * @return string Registration form HTML
     */
    public function get_registration_form($workshop_number) {
        ob_start();
        include ROE_WORKSHOPS_PLUGIN_DIR . 'templates/registration-form-template.php';
        return ob_get_clean();
    }
}