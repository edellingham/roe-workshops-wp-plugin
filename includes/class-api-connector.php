<?php
/**
 * ROE API Connector
 * Connects to FileMaker via HTTP API bridge instead of direct ODBC
 * This allows the WordPress site to be on a different server
 */

class ROE_API_Connector {
    
    private $api_url;
    private $api_key;
    private $admin_key;
    private $timeout;
    
    public function __construct() {
        $this->api_url = get_option('roe_api_url', 'https://roe24.org/Registration/api-bridge/bridge.php');
        $this->api_key = get_option('roe_api_key', '');
        $this->admin_key = get_option('roe_api_admin_key', '');
        $this->timeout = 30;
    }
    
    /**
     * Make API request to the bridge
     * @param string $action API action to perform
     * @param array $params Parameters for the request
     * @param string $method HTTP method (GET or POST)
     * @return array|false Response data or false on failure
     */
    private function request($action, $params = array(), $method = 'GET') {
        try {
            $url = $this->api_url . '?action=' . $action;
            
            $args = array(
                'method' => $method,
                'headers' => array(
                    'X-API-Key' => $this->api_key,
                    'Content-Type' => 'application/json'
                ),
                'timeout' => $this->timeout,
                'sslverify' => true
            );
            
            if ($method === 'GET' && !empty($params)) {
                $url .= '&' . http_build_query($params);
            } elseif ($method === 'POST' && !empty($params)) {
                $args['body'] = json_encode($params);
            }
            
            // Log API request if debug mode enabled
            if (get_option('roe_debug_mode', false)) {
                roe_log('DEBUG', 'API request', array(
                    'action' => $action,
                    'method' => $method,
                    'params' => $params
                ));
            }
            
            // Make the request using WordPress HTTP API
            $response = wp_remote_request($url, $args);
            
            if (is_wp_error($response)) {
                throw new Exception('API request failed: ' . $response->get_error_message());
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if ($status_code !== 200 || !$data) {
                throw new Exception('API request failed with status ' . $status_code);
            }
            
            if (!isset($data['success']) || !$data['success']) {
                $error_message = isset($data['data']['error']) ? $data['data']['error'] : 'Unknown API error';
                throw new Exception($error_message);
            }
            
            return $data['data'];
            
        } catch (Exception $e) {
            roe_log('ERROR', 'API request failed', array(
                'action' => $action,
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Test API connection
     * @return array Test results
     */
    public function test_connection() {
        try {
            $result = $this->request('health_check');
            
            if ($result && isset($result['status']) && $result['status'] === 'healthy') {
                return array(
                    'success' => true,
                    'message' => 'API connection successful',
                    'api_version' => isset($result['version']) ? $result['version'] : 'Unknown',
                    'database_connected' => isset($result['database']) && $result['database'] === 'connected'
                );
            }
            
            return array(
                'success' => false,
                'message' => 'API health check failed'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Get all active workshops
     * @param int $limit Number of workshops to retrieve
     * @param int $offset Offset for pagination
     * @return array Workshop data
     */
    public function get_workshops($limit = 50, $offset = 0) {
        $params = array(
            'limit' => $limit,
            'offset' => $offset
        );
        
        $result = $this->request('get_workshops', $params);
        
        if ($result && isset($result['workshops'])) {
            return $result['workshops'];
        }
        
        return array();
    }
    
    /**
     * Get workshop by workshop number
     * @param string $workshop_number Workshop identifier
     * @return array|null Workshop data or null if not found
     */
    public function get_workshop_detail($workshop_number) {
        $params = array(
            'workshop_id' => $workshop_number
        );
        
        $result = $this->request('get_workshop_detail', $params);
        
        if ($result && isset($result['workshop'])) {
            return $result['workshop'];
        }
        
        return null;
    }
    
    /**
     * Get sessions for a workshop
     * @param string $workshop_number Workshop identifier
     * @return array Session data
     */
    public function get_workshop_sessions($workshop_number) {
        $params = array(
            'workshop_id' => $workshop_number
        );
        
        $result = $this->request('get_workshop_sessions', $params);
        
        if ($result && isset($result['sessions'])) {
            return $result['sessions'];
        }
        
        return array();
    }
    
    /**
     * Search workshops
     * @param array $filters Search filters
     * @return array Workshop results
     */
    public function search_workshops($filters = array()) {
        $params = array(
            'search' => isset($filters['search']) ? $filters['search'] : '',
            'category' => isset($filters['category']) ? $filters['category'] : '',
            'date_from' => isset($filters['date_from']) ? $filters['date_from'] : '',
            'date_to' => isset($filters['date_to']) ? $filters['date_to'] : '',
            'limit' => isset($filters['limit']) ? $filters['limit'] : 50
        );
        
        // Remove empty parameters
        $params = array_filter($params);
        
        $result = $this->request('search_workshops', $params);
        
        if ($result && isset($result['workshops'])) {
            return $result['workshops'];
        }
        
        return array();
    }
    
    /**
     * Check workshop availability
     * @param string $workshop_number Workshop identifier
     * @return array Availability information
     */
    public function check_workshop_availability($workshop_number) {
        $params = array(
            'workshop_id' => $workshop_number
        );
        
        $result = $this->request('check_availability', $params);
        
        if ($result) {
            return array(
                'title' => isset($result['title']) ? $result['title'] : '',
                'current_count' => isset($result['current_registrations']) ? (int)$result['current_registrations'] : 0,
                'max_count' => isset($result['max_registrations']) ? (int)$result['max_registrations'] : 0,
                'available' => isset($result['available']) ? $result['available'] : false
            );
        }
        
        return null;
    }
    
    /**
     * Register user for workshop
     * @param string $workshop_number Workshop identifier
     * @param array $user_data User registration data
     * @return bool Success status
     */
    public function register_user_for_workshop($workshop_number, $user_data) {
        $params = array(
            'workshop_id' => $workshop_number,
            'first_name' => $user_data['first_name'],
            'last_name' => $user_data['last_name'],
            'email' => $user_data['email'],
            'phone' => isset($user_data['phone']) ? $user_data['phone'] : '',
            'organization' => isset($user_data['organization']) ? $user_data['organization'] : '',
            'address' => isset($user_data['address']) ? $user_data['address'] : '',
            'city' => isset($user_data['city']) ? $user_data['city'] : '',
            'state' => isset($user_data['state']) ? $user_data['state'] : '',
            'zip' => isset($user_data['zip']) ? $user_data['zip'] : '',
            'special_needs' => isset($user_data['special_needs']) ? $user_data['special_needs'] : ''
        );
        
        $result = $this->request('register_participant', $params, 'POST');
        
        if ($result && isset($result['success'])) {
            roe_log('INFO', 'Registration submitted via API', array(
                'workshop_number' => $workshop_number,
                'email' => $user_data['email']
            ));
            return true;
        }
        
        return false;
    }
    
    /**
     * Check registration status
     * @param string $email User email
     * @param string $workshop_number Workshop identifier
     * @return array Registration status
     */
    public function check_registration($email, $workshop_number) {
        $params = array(
            'email' => $email,
            'workshop_id' => $workshop_number
        );
        
        $result = $this->request('check_registration', $params);
        
        if ($result) {
            return array(
                'registered' => isset($result['registered']) ? $result['registered'] : false,
                'status' => isset($result['status']) ? $result['status'] : 'not_registered',
                'registration_date' => isset($result['registration_date']) ? $result['registration_date'] : null
            );
        }
        
        return array('registered' => false, 'status' => 'error');
    }
    
    /**
     * Get user's registrations
     * @param string $email User email
     * @return array Registration history
     */
    public function get_user_registrations($email) {
        $params = array(
            'email' => $email
        );
        
        $result = $this->request('get_user_registrations', $params);
        
        if ($result && isset($result['registrations'])) {
            return $result['registrations'];
        }
        
        return array();
    }
    
    /**
     * Admin: Manage IP whitelist
     * @param string $operation Operation (add, remove, list, clear)
     * @param string $ip IP address for add/remove operations
     * @return array Operation result
     */
    public function manage_whitelist($operation, $ip = '') {
        if (empty($this->admin_key)) {
            return array('success' => false, 'message' => 'Admin key not configured');
        }
        
        // Use admin key for this request
        $saved_key = $this->api_key;
        $this->api_key = $this->admin_key;
        
        $params = array(
            'operation' => $operation
        );
        
        if (!empty($ip)) {
            $params['ip'] = $ip;
        }
        
        $result = $this->request('manage_whitelist', $params, ($operation !== 'list' ? 'POST' : 'GET'));
        
        // Restore regular API key
        $this->api_key = $saved_key;
        
        return $result;
    }
    
    /**
     * Admin: Get API logs
     * @param int $lines Number of log lines to retrieve
     * @return array Log entries
     */
    public function get_api_logs($lines = 100) {
        if (empty($this->admin_key)) {
            return array();
        }
        
        // Use admin key for this request
        $saved_key = $this->api_key;
        $this->api_key = $this->admin_key;
        
        $params = array(
            'lines' => $lines
        );
        
        $result = $this->request('get_logs', $params);
        
        // Restore regular API key
        $this->api_key = $saved_key;
        
        if ($result && isset($result['logs'])) {
            return $result['logs'];
        }
        
        return array();
    }
    
    /**
     * Get sync statistics from API
     * @return array Statistics
     */
    public function get_api_stats() {
        $result = $this->request('get_stats');
        
        if ($result) {
            return array(
                'total_workshops' => isset($result['total_workshops']) ? $result['total_workshops'] : 0,
                'active_workshops' => isset($result['active_workshops']) ? $result['active_workshops'] : 0,
                'upcoming_workshops' => isset($result['upcoming_workshops']) ? $result['upcoming_workshops'] : 0,
                'total_registrations' => isset($result['total_registrations']) ? $result['total_registrations'] : 0
            );
        }
        
        return array(
            'total_workshops' => 0,
            'active_workshops' => 0,
            'upcoming_workshops' => 0,
            'total_registrations' => 0
        );
    }
    
    /**
     * Verify API configuration
     * @return bool True if API is properly configured
     */
    public function is_configured() {
        return !empty($this->api_url) && !empty($this->api_key);
    }
    
    /**
     * Get configuration status
     * @return array Configuration details
     */
    public function get_configuration_status() {
        return array(
            'configured' => $this->is_configured(),
            'api_url' => $this->api_url,
            'has_api_key' => !empty($this->api_key),
            'has_admin_key' => !empty($this->admin_key)
        );
    }
}