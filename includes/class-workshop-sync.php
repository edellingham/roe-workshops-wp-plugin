<?php
/**
 * ROE Workshop Sync
 * Handles syncing workshop data from FileMaker to WordPress cache
 */

class ROE_Workshop_Sync {
    
    private $connector;
    
    public function __construct() {
        $this->connector = new ROE_ODBC_Connector();
    }
    
    /**
     * Sync all workshops from FileMaker to WordPress cache
     * @return array Sync results
     */
    public function sync_all_workshops() {
        global $wpdb;
        
        try {
            roe_log('INFO', 'Starting workshop sync');
            
            // Get workshops from FileMaker
            $workshops = $this->connector->get_workshops(1000); // Get more for full sync
            
            if (empty($workshops)) {
                throw new Exception('No workshops retrieved from FileMaker');
            }
            
            $workshops_table = $wpdb->prefix . 'roe_workshops';
            $sessions_table = $wpdb->prefix . 'roe_sessions';
            
            $workshops_synced = 0;
            $sessions_synced = 0;
            
            foreach ($workshops as $workshop) {
                // Sync workshop data
                $workshop_data = array(
                    'workshop_number' => $workshop['WorkshopNumber'],
                    'title' => $workshop['Title'],
                    'description_full' => $workshop['DescriptionFull'],
                    'start_date' => $this->convert_filemaker_date($workshop['DateStart']),
                    'start_time' => $this->convert_filemaker_time($workshop['CalculatedFirstSessionTime']),
                    'end_time' => $this->convert_filemaker_time($workshop['CalculatedFirstSessionEndTime']),
                    'workshop_type' => $workshop['WorkshopType'],
                    'max_registration_count' => (int)$workshop['MaximumWebRegistrationCount'],
                    'current_registration_count' => (int)$workshop['CountOfRegistration'],
                    'cost_student' => (float)$workshop['TotalCostToStudent'],
                    'cost_employee' => (float)$workshop['TotalCostToStudentEmployee'],
                    'web_rate' => (float)$workshop['WebRate'],
                    'presenters' => $workshop['Presenters'],
                    'location' => $workshop['LocationOfFirstMeeting'],
                    'status' => $workshop['StatusActiveCanceled'],
                    'approved' => $workshop['Approved'],
                    'include_web' => $workshop['IncludeWeb'],
                    'last_synced' => current_time('mysql')
                );
                
                // Insert or update workshop
                $existing = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id FROM $workshops_table WHERE workshop_number = %s",
                        $workshop['WorkshopNumber']
                    )
                );
                
                if ($existing) {
                    $wpdb->update(
                        $workshops_table,
                        $workshop_data,
                        array('workshop_number' => $workshop['WorkshopNumber'])
                    );
                } else {
                    $wpdb->insert($workshops_table, $workshop_data);
                }
                
                $workshops_synced++;
                
                // Sync sessions for this workshop
                $sessions = $this->connector->get_workshop_sessions($workshop['WorkshopNumber']);
                
                // Clear existing sessions for this workshop
                $wpdb->delete(
                    $sessions_table,
                    array('workshop_number' => $workshop['WorkshopNumber'])
                );
                
                foreach ($sessions as $session) {
                    $session_data = array(
                        'workshop_number' => $workshop['WorkshopNumber'],
                        'session_date' => $this->convert_filemaker_date($session['DateStart']),
                        'begin_time' => $this->convert_filemaker_time($session['BeginTime']),
                        'end_time' => $this->convert_filemaker_time($session['EndTime']),
                        'location_building_room' => $session['LocationBuildingAndRoom'],
                        'location_full' => $session['LocationOneLineNameCityState'],
                        'last_synced' => current_time('mysql')
                    );
                    
                    $wpdb->insert($sessions_table, $session_data);
                    $sessions_synced++;
                }
            }
            
            // Update last sync time
            update_option('roe_last_sync_time', current_time('mysql'));
            
            roe_log('INFO', 'Workshop sync completed successfully', array(
                'workshops_synced' => $workshops_synced,
                'sessions_synced' => $sessions_synced
            ));
            
            return array(
                'success' => true,
                'workshops_synced' => $workshops_synced,
                'sessions_synced' => $sessions_synced
            );
            
        } catch (Exception $e) {
            roe_log('ERROR', 'Workshop sync failed', array('error' => $e->getMessage()));
            
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Sync single workshop
     * @param string $workshop_number Workshop to sync
     * @return bool Success status
     */
    public function sync_single_workshop($workshop_number) {
        global $wpdb;
        
        try {
            $workshop = $this->connector->get_workshop_detail($workshop_number);
            
            if (!$workshop) {
                throw new Exception("Workshop $workshop_number not found in FileMaker");
            }
            
            $workshops_table = $wpdb->prefix . 'roe_workshops';
            
            $workshop_data = array(
                'workshop_number' => $workshop['WorkshopNumber'],
                'title' => $workshop['Title'],
                'description_full' => $workshop['DescriptionFull'],
                'start_date' => $this->convert_filemaker_date($workshop['DateStart']),
                'start_time' => $this->convert_filemaker_time($workshop['CalculatedFirstSessionTime']),
                'end_time' => $this->convert_filemaker_time($workshop['CalculatedFirstSessionEndTime']),
                'workshop_type' => $workshop['WorkshopType'],
                'max_registration_count' => (int)$workshop['MaximumWebRegistrationCount'],
                'current_registration_count' => (int)$workshop['CountOfRegistration'],
                'cost_student' => (float)$workshop['TotalCostToStudent'],
                'cost_employee' => (float)$workshop['TotalCostToStudentEmployee'],
                'web_rate' => (float)$workshop['WebRate'],
                'presenters' => $workshop['Presenters'],
                'location' => $workshop['LocationOfFirstMeeting'],
                'status' => $workshop['StatusActiveCanceled'],
                'approved' => $workshop['Approved'],
                'include_web' => $workshop['IncludeWeb'],
                'last_synced' => current_time('mysql')
            );
            
            // Insert or update
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id FROM $workshops_table WHERE workshop_number = %s",
                    $workshop_number
                )
            );
            
            if ($existing) {
                $wpdb->update(
                    $workshops_table,
                    $workshop_data,
                    array('workshop_number' => $workshop_number)
                );
            } else {
                $wpdb->insert($workshops_table, $workshop_data);
            }
            
            return true;
            
        } catch (Exception $e) {
            roe_log('ERROR', 'Single workshop sync failed', array(
                'workshop_number' => $workshop_number,
                'error' => $e->getMessage()
            ));
            return false;
        }
    }
    
    /**
     * Convert FileMaker date to MySQL format
     * @param string $fm_date FileMaker date
     * @return string MySQL date format
     */
    private function convert_filemaker_date($fm_date) {
        if (empty($fm_date) || $fm_date === '0000-00-00') {
            return null;
        }
        
        // Handle various FileMaker date formats
        $date = DateTime::createFromFormat('m/d/Y', $fm_date);
        if (!$date) {
            $date = DateTime::createFromFormat('Y-m-d', $fm_date);
        }
        if (!$date) {
            $date = DateTime::createFromFormat('m-d-Y', $fm_date);
        }
        
        return $date ? $date->format('Y-m-d') : null;
    }
    
    /**
     * Convert FileMaker time to MySQL format
     * @param string $fm_time FileMaker time
     * @return string MySQL time format
     */
    private function convert_filemaker_time($fm_time) {
        if (empty($fm_time)) {
            return null;
        }
        
        // Handle various time formats
        $time = DateTime::createFromFormat('H:i:s', $fm_time);
        if (!$time) {
            $time = DateTime::createFromFormat('H:i', $fm_time);
        }
        if (!$time) {
            $time = DateTime::createFromFormat('g:i A', $fm_time);
        }
        if (!$time) {
            $time = DateTime::createFromFormat('g:i a', $fm_time);
        }
        
        return $time ? $time->format('H:i:s') : null;
    }
    
    /**
     * Get sync statistics
     * @return array Sync statistics
     */
    public function get_sync_stats() {
        global $wpdb;
        
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        $sessions_table = $wpdb->prefix . 'roe_sessions';
        $error_log_table = $wpdb->prefix . 'roe_error_log';
        
        return array(
            'total_workshops' => $wpdb->get_var("SELECT COUNT(*) FROM $workshops_table"),
            'active_workshops' => $wpdb->get_var("SELECT COUNT(*) FROM $workshops_table WHERE status = 'Active'"),
            'upcoming_workshops' => $wpdb->get_var("SELECT COUNT(*) FROM $workshops_table WHERE start_date >= CURDATE()"),
            'total_sessions' => $wpdb->get_var("SELECT COUNT(*) FROM $sessions_table"),
            'recent_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $error_log_table WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"),
            'last_sync' => get_option('roe_last_sync_time', 'Never')
        );
    }
    
    /**
     * Clean old workshop data
     */
    public function cleanup_old_data() {
        global $wpdb;
        
        $workshops_table = $wpdb->prefix . 'roe_workshops';
        $sessions_table = $wpdb->prefix . 'roe_sessions';
        $error_log_table = $wpdb->prefix . 'roe_error_log';
        
        // Remove workshops older than 1 year
        $wpdb->query(
            "DELETE FROM $workshops_table 
             WHERE start_date < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)"
        );
        
        // Remove orphaned sessions
        $wpdb->query(
            "DELETE s FROM $sessions_table s 
             LEFT JOIN $workshops_table w ON s.workshop_number = w.workshop_number 
             WHERE w.workshop_number IS NULL"
        );
        
        // Remove error logs older than 30 days
        $wpdb->query(
            "DELETE FROM $error_log_table 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        
        roe_log('INFO', 'Old data cleanup completed');
    }
}