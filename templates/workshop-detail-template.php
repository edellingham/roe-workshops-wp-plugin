<?php
/**
 * Workshop Detail Template
 * Displays detailed view of a single workshop
 */

if (!defined('ABSPATH')) {
    exit;
}

$frontend = new ROE_Frontend_Display();
?>

<div class="roe-workshop-detail">
    
    <!-- Workshop Header -->
    <div class="roe-workshop-header">
        <div class="roe-workshop-date-large">
            <?php if ($workshop->start_date): ?>
                <div class="roe-date-month"><?php echo date('M', strtotime($workshop->start_date)); ?></div>
                <div class="roe-date-day"><?php echo date('j', strtotime($workshop->start_date)); ?></div>
                <div class="roe-date-year"><?php echo date('Y', strtotime($workshop->start_date)); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="roe-workshop-info">
            <h1><?php echo esc_html($workshop->title); ?></h1>
            
            <?php if ($workshop->workshop_type): ?>
                <div class="roe-workshop-category">
                    <strong>Category:</strong> <?php echo esc_html($workshop->workshop_type); ?>
                </div>
            <?php endif; ?>
            
            <div class="roe-workshop-datetime">
                <strong>Date & Time:</strong> 
                <?php echo $frontend->format_workshop_datetime($workshop->start_date, $workshop->start_time); ?>
                <?php if ($workshop->end_time && $workshop->end_time !== $workshop->start_time): ?>
                    - <?php echo date('g:i A', strtotime($workshop->end_time)); ?>
                <?php endif; ?>
            </div>
            
            <?php if ($workshop->location): ?>
            <div class="roe-workshop-location">
                <strong>Location:</strong> <?php echo esc_html($workshop->location); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($workshop->presenters): ?>
            <div class="roe-workshop-presenters">
                <strong>Presenter(s):</strong> <?php echo esc_html($workshop->presenters); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Workshop Description -->
    <?php if ($workshop->description_full): ?>
    <div class="roe-workshop-description">
        <h3>Workshop Description</h3>
        <div class="roe-description-content">
            <?php echo wp_kses_post(nl2br($workshop->description_full)); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sessions Information -->
    <?php if (!empty($sessions)): ?>
    <div class="roe-workshop-sessions">
        <h3>Session Schedule</h3>
        <div class="roe-sessions-table">
            <table class="roe-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sessions as $session): ?>
                    <tr>
                        <td><?php echo date('F j, Y', strtotime($session->session_date)); ?></td>
                        <td>
                            <?php if ($session->begin_time): ?>
                                <?php echo date('g:i A', strtotime($session->begin_time)); ?>
                                <?php if ($session->end_time): ?>
                                    - <?php echo date('g:i A', strtotime($session->end_time)); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                Time TBD
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($session->location_building_room): ?>
                                <?php echo esc_html($session->location_building_room); ?>
                            <?php elseif ($session->location_full): ?>
                                <?php echo esc_html($session->location_full); ?>
                            <?php else: ?>
                                Location TBD
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Registration Section -->
    <div class="roe-registration-section">
        <h3>Registration Information</h3>
        
        <div class="roe-registration-details">
            <!-- Cost Information -->
            <div class="roe-cost-info">
                <h4>Cost</h4>
                <?php if ($workshop->web_rate > 0): ?>
                    <div class="roe-cost-primary">$<?php echo number_format($workshop->web_rate, 2); ?></div>
                <?php elseif ($workshop->cost_student > 0): ?>
                    <div class="roe-cost-primary">$<?php echo number_format($workshop->cost_student, 2); ?></div>
                <?php else: ?>
                    <div class="roe-cost-primary">FREE</div>
                <?php endif; ?>
                
                <?php if ($workshop->cost_employee > 0 && $workshop->cost_employee !== $workshop->cost_student): ?>
                    <div class="roe-cost-secondary">
                        Employee Rate: $<?php echo number_format($workshop->cost_employee, 2); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Availability Information -->
            <div class="roe-availability-info">
                <h4>Availability</h4>
                <div class="roe-availability-status">
                    <?php echo $frontend->get_registration_spots_info($workshop); ?>
                </div>
                <div class="roe-capacity-details">
                    <?php echo esc_html($workshop->current_registration_count); ?> registered / 
                    <?php echo esc_html($workshop->max_registration_count); ?> maximum
                </div>
            </div>
        </div>
        
        <!-- Registration Form -->
        <?php if ($frontend->is_workshop_available($workshop)): ?>
        <div class="roe-registration-form">
            <h4>Register for This Workshop</h4>
            <?php echo $frontend->get_registration_form($workshop->workshop_number); ?>
        </div>
        <?php else: ?>
        <div class="roe-registration-closed">
            <h4>Registration Status</h4>
            <?php if ($workshop->current_registration_count >= $workshop->max_registration_count): ?>
                <p><strong>This workshop is currently full.</strong></p>
                <p>Please contact us at <?php echo get_option('roe_company_email', 'info@roe24.org'); ?> 
                   to be added to the waiting list.</p>
            <?php elseif (strtotime($workshop->start_date) < time()): ?>
                <p><strong>Registration for this workshop has ended.</strong></p>
            <?php else: ?>
                <p><strong>Registration is not currently available for this workshop.</strong></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Navigation -->
    <div class="roe-workshop-navigation">
        <a href="javascript:history.back()" class="button button-secondary">← Back to Workshop List</a>
    </div>
    
</div>