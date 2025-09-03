<?php
/**
 * ROE ODBC Connector
 * Handles connections to FileMaker Pro database via ODBC
 */

class ROE_ODBC_Connector {
    
    private $dsn;
    private $username;
    private $password;
    private $connection;
    private $persistent_connection;
    
    public function __construct() {
        $this->dsn = get_option('roe_odbc_dsn', 'CEDARWOOD');
        $this->username = get_option('roe_odbc_username', 'webuser');
        $this->password = get_option('roe_odbc_password', 'PDAcedar');
        $this->persistent_connection = get_option('roe_use_persistent_connection', true);
    }
    
    /**
     * Establish ODBC connection to FileMaker
     * @return resource|false Connection resource or false on failure
     */
    public function connect() {
        try {
            if ($this->connection) {
                return $this->connection;
            }
            
            if ($this->persistent_connection) {
                $this->connection = odbc_pconnect($this->dsn, $this->username, $this->password);
            } else {
                $this->connection = odbc_connect($this->dsn, $this->username, $this->password);
            }
            
            if (!$this->connection) {
                throw new Exception('ODBC connection failed: ' . odbc_errormsg());
            }
            
            roe_log('INFO', 'ODBC connection established successfully');
            return $this->connection;
            
        } catch (Exception $e) {
            roe_log('ERROR', 'ODBC connection failed', array('error' => $e->getMessage()));
            return false;
        }
    }
    
    /**
     * Execute a safe ODBC query
     * @param string $query SQL query
     * @param array $params Query parameters (for future prepared statement support)
     * @return resource|false Query result or false on failure
     */
    public function execute_query($query, $params = array()) {
        try {
            if (!$this->connect()) {
                throw new Exception('No database connection available');
            }
            
            // Log query for debugging (without sensitive data)
            if (get_option('roe_debug_mode', false)) {
                roe_log('DEBUG', 'Executing query', array('query' => $this->sanitize_query_for_log($query)));
            }
            
            $result = odbc_exec($this->connection, $query);
            
            if (!$result) {
                throw new Exception('Query execution failed: ' . odbc_errormsg($this->connection));
            }
            
            return $result;
            
        } catch (Exception $e) {
            roe_log('ERROR', 'Query execution failed', array(
                'query' => $this->sanitize_query_for_log($query),
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Get all active workshops
     * @param int $limit Number of workshops to retrieve
     * @param int $offset Offset for pagination
     * @return array Workshop data
     */
    public function get_workshops($limit = 50, $offset = 0) {
        $web_include = get_option('roe_web_include', 'Primary Site');
        
        $query = "SELECT TOP $limit 
            WorkshopNumber, Title, DescriptionFull, DateStart, TimeStart, 
            CalculatedFirstSessionTime, CalculatedFirstSessionEndTime,
            WorkshopType, MaximumWebRegistrationCount, CountOfRegistration,
            TotalCostToStudent, TotalCostToStudent2, TotalCostToStudent3, 
            TotalCostToStudent4, TotalCostToStudent5, TotalCostToStudentEmployee,
            WebRate, Presenters, LocationOfFirstMeeting, CountOfSessions,
            StatusActiveCanceled, Approved, RegistrationDueDate
            FROM Workshops 
            WHERE (IncludeWeb LIKE '%$web_include%') 
            AND (Approved = 'Yes') 
            AND (StatusActiveCanceled = 'Active')
            AND (DateStart >= CURDATE())
            ORDER BY DateStart ASC, CalculatedFirstSessionTime ASC";
        
        return $this->fetch_all_results($query);
    }
    
    /**
     * Get workshop by workshop number
     * @param string $workshop_number Workshop identifier
     * @return array|null Workshop data or null if not found
     */
    public function get_workshop_detail($workshop_number) {
        $web_include = get_option('roe_web_include', 'Primary Site');
        $workshop_number = $this->sanitize_workshop_number($workshop_number);
        
        $query = "SELECT WorkshopNumber, Title, DescriptionFull, DateStart, TimeStart,
            CalculatedFirstSessionTime, CalculatedFirstSessionEndTime,
            WorkshopType, MaximumWebRegistrationCount, CountOfRegistration,
            TotalCostToStudent, TotalCostToStudent2, TotalCostToStudent3,
            TotalCostToStudent4, TotalCostToStudent5, TotalCostToStudentEmployee,
            WebRate, Presenters, LocationOfFirstMeeting, CountOfSessions,
            StatusActiveCanceled, Approved, RegistrationDueDate, ConfirmationEmailText,
            CostRate2Description, CostRate3Description, CostRate4Description, CostRate5Description,
            CostRate1Description, AlternateRegistrationProcedure
            FROM Workshops 
            WHERE (WorkshopNumber = '$workshop_number')
            AND (IncludeWeb LIKE '%$web_include%')
            AND (Approved = 'Yes')
            AND (StatusActiveCanceled = 'Active')";
        
        $results = $this->fetch_all_results($query);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Get sessions for a workshop
     * @param string $workshop_number Workshop identifier
     * @return array Session data
     */
    public function get_workshop_sessions($workshop_number) {
        $workshop_number = $this->sanitize_workshop_number($workshop_number);
        
        $query = "SELECT DateStart, BeginTime, EndTime, 
            LocationBuildingAndRoom, LocationOneLineNameCityState
            FROM Sessions 
            WHERE ParentWorkshopNumber = '$workshop_number'
            ORDER BY DateStart ASC, BeginTime ASC";
        
        return $this->fetch_all_results($query);
    }
    
    /**
     * Check workshop availability
     * @param string $workshop_number Workshop identifier
     * @return array Availability information
     */
    public function check_workshop_availability($workshop_number) {
        $workshop_number = $this->sanitize_workshop_number($workshop_number);
        
        $query = "SELECT CountOfRegistration, MaximumWebRegistrationCount, Title
            FROM Workshops 
            WHERE WorkshopNumber = '$workshop_number'";
        
        $results = $this->fetch_all_results($query);
        if (!empty($results)) {
            $result = $results[0];
            return array(
                'title' => $result['Title'],
                'current_count' => (int)$result['CountOfRegistration'],
                'max_count' => (int)$result['MaximumWebRegistrationCount'],
                'available' => ((int)$result['CountOfRegistration'] < (int)$result['MaximumWebRegistrationCount'])
            );
        }
        
        return null;
    }
    
    /**
     * Register user for workshop (simplified - will need full implementation)
     * @param string $workshop_number Workshop identifier
     * @param array $user_data User registration data
     * @return bool Success status
     */
    public function register_user_for_workshop($workshop_number, $user_data) {
        // This will need to be implemented based on the existing registration logic
        // For now, we'll create a placeholder that logs the attempt
        
        roe_log('INFO', 'Registration attempt', array(
            'workshop_number' => $workshop_number,
            'email' => $user_data['email'],
            'name' => $user_data['first_name'] . ' ' . $user_data['last_name']
        ));
        
        // TODO: Implement actual registration insertion logic
        // This requires understanding the Registrations table structure
        
        return true;
    }
    
    /**
     * Fetch all results from a query
     * @param string $query SQL query
     * @return array Results array
     */
    private function fetch_all_results($query) {
        $result = $this->execute_query($query);
        if (!$result) {
            return array();
        }
        
        $data = array();
        while (odbc_fetch_row($result)) {
            $row = array();
            $num_fields = odbc_num_fields($result);
            
            for ($i = 1; $i <= $num_fields; $i++) {
                $field_name = odbc_field_name($result, $i);
                $row[$field_name] = odbc_result($result, $i);
            }
            
            $data[] = $row;
        }
        
        odbc_free_result($result);
        return $data;
    }
    
    /**
     * Sanitize workshop number for queries
     * @param string $workshop_number Raw workshop number
     * @return string Sanitized workshop number
     */
    private function sanitize_workshop_number($workshop_number) {
        // Remove any characters that aren't alphanumeric or hyphens
        return preg_replace('/[^a-zA-Z0-9-]/', '', $workshop_number);
    }
    
    /**
     * Remove sensitive data from query for logging
     * @param string $query SQL query
     * @return string Sanitized query
     */
    private function sanitize_query_for_log($query) {
        // Remove any potential sensitive data from query logs
        $query = preg_replace('/password\s*=\s*[\'"][^\'"]*/i', 'password=***', $query);
        return $query;
    }
    
    /**
     * Test the ODBC connection
     * @return array Test results
     */
    public function test_connection() {
        try {
            if ($this->connect()) {
                // Test with a simple query
                $test_query = "SELECT COUNT(*) as count FROM Workshops";
                $result = $this->execute_query($test_query);
                
                if ($result) {
                    $data = $this->fetch_all_results("SELECT COUNT(*) as count FROM Workshops");
                    return array(
                        'success' => true,
                        'message' => 'Connection successful',
                        'workshop_count' => $data[0]['count']
                    );
                }
            }
            
            return array(
                'success' => false,
                'message' => 'Connection failed - unable to execute test query'
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            );
        }
    }
    
    /**
     * Close the connection
     */
    public function close() {
        if ($this->connection) {
            odbc_close($this->connection);
            $this->connection = null;
        }
    }
    
    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct() {
        $this->close();
    }
}