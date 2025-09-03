<?php
/**
 * ROE Workshops Admin Dashboard
 */

if (!defined('ABSPATH')) {
    exit;
}

$stats = (new ROE_Workshop_Sync())->get_sync_stats();
?>

<div class="wrap">
    <h1>ROE Workshops Dashboard</h1>
    
    <!-- Sync Statistics Cards -->
    <div class="roe-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
        
        <div class="postbox">
            <div class="postbox-header"><h3>Total Workshops</h3></div>
            <div class="inside" style="text-align: center; padding: 20px;">
                <span style="font-size: 2em; color: #0073aa;"><?php echo esc_html($stats['total_workshops']); ?></span>
            </div>
        </div>
        
        <div class="postbox">
            <div class="postbox-header"><h3>Active Workshops</h3></div>
            <div class="inside" style="text-align: center; padding: 20px;">
                <span style="font-size: 2em; color: #00a32a;"><?php echo esc_html($stats['active_workshops']); ?></span>
            </div>
        </div>
        
        <div class="postbox">
            <div class="postbox-header"><h3>Upcoming Workshops</h3></div>
            <div class="inside" style="text-align: center; padding: 20px;">
                <span style="font-size: 2em; color: #ff8c00;"><?php echo esc_html($stats['upcoming_workshops']); ?></span>
            </div>
        </div>
        
        <div class="postbox">
            <div class="postbox-header"><h3>Recent Errors (24h)</h3></div>
            <div class="inside" style="text-align: center; padding: 20px;">
                <span style="font-size: 2em; color: <?php echo $stats['recent_errors'] > 0 ? '#dc3232' : '#00a32a'; ?>;">
                    <?php echo esc_html($stats['recent_errors']); ?>
                </span>
            </div>
        </div>
        
    </div>
    
    <!-- Last Sync Info -->
    <div class="postbox" style="margin: 20px 0;">
        <div class="postbox-header"><h3>Sync Status</h3></div>
        <div class="inside" style="padding: 20px;">
            <p><strong>Last Sync:</strong> <?php echo esc_html($stats['last_sync']); ?></p>
            <p><strong>Next Scheduled:</strong> 
                <?php 
                $next_sync = wp_next_scheduled('roe_workshop_sync');
                echo $next_sync ? date('Y-m-d H:i:s', $next_sync) : 'Not scheduled';
                ?>
            </p>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div style="margin: 20px 0;">
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('roe_sync_workshops'); ?>
            <input type="submit" name="sync_workshops" class="button button-primary" value="Sync Workshops Now" />
        </form>
        
        <form method="post" style="display: inline-block; margin-right: 10px;">
            <?php wp_nonce_field('roe_test_connection'); ?>
            <input type="submit" name="test_connection" class="button button-secondary" value="Test ODBC Connection" />
        </form>
    </div>
    
    <!-- Recent Workshops Table -->
    <div class="postbox">
        <div class="postbox-header"><h3>Recent Workshops</h3></div>
        <div class="inside">
            <?php if (!empty($workshops)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Workshop #</th>
                        <th>Title</th>
                        <th>Start Date</th>
                        <th>Type</th>
                        <th>Registrations</th>
                        <th>Status</th>
                        <th>Last Synced</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workshops as $workshop): ?>
                    <tr>
                        <td><strong><?php echo esc_html($workshop->workshop_number); ?></strong></td>
                        <td><?php echo esc_html($workshop->title); ?></td>
                        <td><?php echo esc_html($workshop->start_date); ?></td>
                        <td><?php echo esc_html($workshop->workshop_type); ?></td>
                        <td>
                            <?php echo esc_html($workshop->current_registration_count); ?> / 
                            <?php echo esc_html($workshop->max_registration_count); ?>
                            <?php if ($workshop->current_registration_count >= $workshop->max_registration_count): ?>
                                <span style="color: #dc3232;">FULL</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="color: <?php echo $workshop->status === 'Active' ? '#00a32a' : '#dc3232'; ?>;">
                                <?php echo esc_html($workshop->status); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($workshop->last_synced); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No workshops found. Click "Sync Workshops Now" to import from FileMaker.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="postbox">
        <div class="postbox-header"><h3>Quick Actions</h3></div>
        <div class="inside" style="padding: 20px;">
            <h4>Shortcodes for Frontend</h4>
            <p>Use these shortcodes in your WordPress pages:</p>
            <ul>
                <li><code>[roe-workshops]</code> - Display workshop listing</li>
                <li><code>[roe-workshops category="Technology" limit="5"]</code> - Filtered workshop listing</li>
                <li><code>[roe-workshop-detail number="WS123"]</code> - Single workshop detail</li>
            </ul>
            
            <h4>Test Pages</h4>
            <p>Create these pages for testing:</p>
            <ul>
                <li><strong>Workshops</strong> - Add shortcode: <code>[roe-workshops]</code></li>
                <li><strong>Workshop Detail</strong> - Add shortcode: <code>[roe-workshop-detail]</code> (number passed via URL)</li>
            </ul>
        </div>
    </div>
    
</div>

<style>
.roe-stats-grid .postbox {
    margin: 0;
}
.roe-stats-grid .postbox-header h3 {
    margin: 0;
    padding: 10px 15px;
    background: #f1f1f1;
}
</style>