<?php
/**
 * ROE Workshops Sync Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>ROE Workshops Sync Settings</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('roe_save_settings'); ?>
        
        <table class="form-table">
            <tbody>
                
                <!-- ODBC Connection Settings -->
                <tr>
                    <th scope="row"><label for="odbc_dsn">ODBC DSN</label></th>
                    <td>
                        <input type="text" id="odbc_dsn" name="odbc_dsn" 
                               value="<?php echo esc_attr($settings['odbc_dsn']); ?>" 
                               class="regular-text" />
                        <p class="description">FileMaker ODBC Data Source Name (usually "CEDARWOOD")</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="odbc_username">ODBC Username</label></th>
                    <td>
                        <input type="text" id="odbc_username" name="odbc_username" 
                               value="<?php echo esc_attr($settings['odbc_username']); ?>" 
                               class="regular-text" />
                        <p class="description">Username for FileMaker database access</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="odbc_password">ODBC Password</label></th>
                    <td>
                        <input type="password" id="odbc_password" name="odbc_password" 
                               placeholder="Enter new password (leave blank to keep current)" 
                               class="regular-text" />
                        <p class="description">Password for FileMaker database access (leave blank to keep current)</p>
                    </td>
                </tr>
                
                <!-- Sync Settings -->
                <tr>
                    <th scope="row"><label for="sync_frequency">Sync Frequency</label></th>
                    <td>
                        <select id="sync_frequency" name="sync_frequency">
                            <option value="hourly" <?php selected($settings['sync_frequency'], 'hourly'); ?>>Every Hour</option>
                            <option value="twicedaily" <?php selected($settings['sync_frequency'], 'twicedaily'); ?>>Twice Daily</option>
                            <option value="daily" <?php selected($settings['sync_frequency'], 'daily'); ?>>Daily</option>
                        </select>
                        <p class="description">How often to automatically sync workshop data</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="web_include">Web Include Filter</label></th>
                    <td>
                        <input type="text" id="web_include" name="web_include" 
                               value="<?php echo esc_attr($settings['web_include']); ?>" 
                               class="regular-text" />
                        <p class="description">FileMaker field value to filter workshops for web inclusion</p>
                    </td>
                </tr>
                
                <!-- Company Information -->
                <tr>
                    <th scope="row"><label for="company_name">Company Name</label></th>
                    <td>
                        <input type="text" id="company_name" name="company_name" 
                               value="<?php echo esc_attr($settings['company_name']); ?>" 
                               class="regular-text" />
                        <p class="description">Organization name for display</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><label for="company_email">Company Email</label></th>
                    <td>
                        <input type="email" id="company_email" name="company_email" 
                               value="<?php echo esc_attr($settings['company_email']); ?>" 
                               class="regular-text" />
                        <p class="description">Email address for registration confirmations</p>
                    </td>
                </tr>
                
                <!-- Debug Settings -->
                <tr>
                    <th scope="row">Debug Mode</th>
                    <td>
                        <label>
                            <input type="checkbox" id="debug_mode" name="debug_mode" 
                                   value="1" <?php checked($settings['debug_mode']); ?> />
                            Enable debug logging
                        </label>
                        <p class="description">Log detailed information for troubleshooting (disable in production)</p>
                    </td>
                </tr>
                
            </tbody>
        </table>
        
        <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
        
    </form>
    
    <!-- Connection Test Section -->
    <div class="postbox" style="margin-top: 30px;">
        <div class="postbox-header"><h3>Connection Test</h3></div>
        <div class="inside" style="padding: 20px;">
            <p>Test your ODBC connection to FileMaker:</p>
            <form method="post" style="display: inline-block;">
                <?php wp_nonce_field('roe_test_connection'); ?>
                <input type="submit" name="test_connection" class="button button-secondary" value="Test Connection" />
            </form>
        </div>
    </div>
    
    <!-- Sync Information -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header"><h3>Sync Information</h3></div>
        <div class="inside" style="padding: 20px;">
            <h4>What Gets Synced</h4>
            <p>The plugin syncs the following data from FileMaker:</p>
            <ul>
                <li><strong>Workshops:</strong> Title, description, dates, pricing, capacity, status</li>
                <li><strong>Sessions:</strong> Individual session dates and times for multi-session workshops</li>
                <li><strong>Registration Counts:</strong> Current vs. maximum capacity</li>
            </ul>
            
            <h4>Sync Process</h4>
            <ol>
                <li>Connect to FileMaker via ODBC using configured DSN</li>
                <li>Query for active, approved workshops where IncludeWeb matches filter</li>
                <li>Cache data in WordPress database for fast frontend display</li>
                <li>Sync individual sessions for each workshop</li>
                <li>Update registration counts and availability status</li>
            </ol>
            
            <p><strong>Note:</strong> Registration submissions go directly to FileMaker in real-time, not through the cache.</p>
        </div>
    </div>
    
</div>