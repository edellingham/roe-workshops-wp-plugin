/**
 * ROE Workshops Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Auto-refresh sync status
        if ($('#roe-workshops-dashboard').length) {
            setInterval(updateSyncStatus, 60000); // Check every minute
        }
        
        // Toggle connection settings based on method
        $('#connection_method').on('change', function() {
            var method = $(this).val();
            if (method === 'api') {
                $('.api-settings').show();
                $('.odbc-settings').hide();
            } else {
                $('.api-settings').hide();
                $('.odbc-settings').show();
            }
        }).trigger('change');
        
        // Confirm destructive actions
        $('input[name="clear_logs"]').on('click', function(e) {
            if (!confirm('Are you sure you want to clear all error logs? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
        
        // Add loading states to sync buttons
        $('input[name="sync_workshops"]').on('click', function() {
            var $btn = $(this);
            $btn.val('Syncing...');
            $btn.prop('disabled', true);
            
            // Re-enable after 30 seconds (in case of timeout)
            setTimeout(function() {
                $btn.val('Sync Workshops Now');
                $btn.prop('disabled', false);
            }, 30000);
        });
        
        $('input[name="test_connection"]').on('click', function() {
            var $btn = $(this);
            $btn.val('Testing...');
            $btn.prop('disabled', true);
            
            // Re-enable after 10 seconds
            setTimeout(function() {
                $btn.val('Test ODBC Connection');
                $btn.prop('disabled', false);
            }, 10000);
        });
        
        // Settings form validation
        $('#roe-sync-settings-form').on('submit', function(e) {
            var dsnValue = $('input[name="odbc_dsn"]').val().trim();
            var usernameValue = $('input[name="odbc_username"]').val().trim();
            
            if (!dsnValue || !usernameValue) {
                alert('ODBC DSN and Username are required fields.');
                e.preventDefault();
                return false;
            }
        });
        
    });
    
    /**
     * Update sync status (for real-time dashboard updates)
     */
    function updateSyncStatus() {
        // This could make an AJAX call to get current sync status
        // For now, just update the timestamp display
        var now = new Date();
        $('.sync-status-updated').text('Last checked: ' + now.toLocaleTimeString());
    }
    
})(jQuery);