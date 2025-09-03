<?php
/**
 * ROE Workshops Error Logs Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>ROE Workshops Error Logs</h1>
    
    <!-- Clear Logs Button -->
    <div style="margin: 20px 0;">
        <form method="post" onsubmit="return confirm('Are you sure you want to clear all error logs?');">
            <?php wp_nonce_field('roe_clear_logs'); ?>
            <input type="submit" name="clear_logs" class="button button-secondary" value="Clear All Logs" />
        </form>
    </div>
    
    <!-- Error Logs Table -->
    <div class="postbox">
        <div class="postbox-header"><h3>Recent Error Logs (Last 100)</h3></div>
        <div class="inside">
            <?php if (!empty($logs)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="15%">Timestamp</th>
                        <th width="10%">Level</th>
                        <th width="50%">Message</th>
                        <th width="25%">Context</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log->timestamp); ?></td>
                        <td>
                            <span class="roe-log-level roe-log-<?php echo strtolower($log->level); ?>">
                                <?php echo esc_html($log->level); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td>
                            <?php if ($log->context && $log->context !== 'null'): ?>
                                <details>
                                    <summary>View Context</summary>
                                    <pre><?php echo esc_html($log->context); ?></pre>
                                </details>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No error logs found. This is good news!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Log Level Information -->
    <div class="postbox" style="margin-top: 20px;">
        <div class="postbox-header"><h3>Log Level Guide</h3></div>
        <div class="inside" style="padding: 20px;">
            <ul>
                <li><strong>ERROR:</strong> Critical issues that prevent normal operation</li>
                <li><strong>WARNING:</strong> Issues that should be addressed but don't prevent operation</li>
                <li><strong>INFO:</strong> General information about plugin operations</li>
                <li><strong>DEBUG:</strong> Detailed information for troubleshooting (only when debug mode enabled)</li>
            </ul>
        </div>
    </div>
    
</div>

<style>
.roe-log-level {
    padding: 3px 8px;
    border-radius: 3px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 0.8em;
}
.roe-log-error {
    background: #dc3232;
    color: white;
}
.roe-log-warning {
    background: #ff8c00;
    color: white;
}
.roe-log-info {
    background: #0073aa;
    color: white;
}
.roe-log-debug {
    background: #666;
    color: white;
}
</style>