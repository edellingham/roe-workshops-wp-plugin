<?php
/**
 * Workshop List Template
 * Displays list of workshops with search and filter options
 */

if (!defined('ABSPATH')) {
    exit;
}

$frontend = new ROE_Frontend_Display();
$categories = $frontend->get_workshop_categories();
?>

<div class="roe-workshops-container">
    
    <?php if ($atts['show_search'] === 'true'): ?>
    <!-- Search and Filter Form -->
    <div class="roe-search-form">
        <form method="GET" class="roe-search-filters">
            <div class="roe-search-row">
                <div class="roe-search-field">
                    <label for="roe-search">Search Workshops:</label>
                    <input type="text" id="roe-search" name="search" 
                           value="<?php echo esc_attr(isset($_GET['search']) ? $_GET['search'] : ''); ?>" 
                           placeholder="Search by title, description, or presenter..." />
                </div>
                
                <div class="roe-filter-field">
                    <label for="roe-category">Category:</label>
                    <select id="roe-category" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category); ?>" 
                                    <?php selected(isset($_GET['category']) ? $_GET['category'] : '', $category); ?>>
                                <?php echo esc_html($category); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="roe-search-submit">
                    <input type="submit" value="Search" class="button button-primary" />
                    <?php if (isset($_GET['search']) || isset($_GET['category'])): ?>
                        <a href="<?php echo remove_query_arg(array('search', 'category')); ?>" class="button">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
    
    <!-- Workshop Results -->
    <div class="roe-workshops-grid">
        <?php if (!empty($workshops)): ?>
            <?php foreach ($workshops as $workshop): ?>
            <div class="roe-workshop-card">
                
                <!-- Workshop Date Badge -->
                <div class="roe-workshop-date">
                    <?php if ($workshop->start_date): ?>
                        <div class="roe-date-month"><?php echo date('M', strtotime($workshop->start_date)); ?></div>
                        <div class="roe-date-day"><?php echo date('j', strtotime($workshop->start_date)); ?></div>
                        <div class="roe-date-year"><?php echo date('Y', strtotime($workshop->start_date)); ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Workshop Content -->
                <div class="roe-workshop-content">
                    
                    <h3 class="roe-workshop-title">
                        <a href="?workshop=<?php echo esc_attr($workshop->workshop_number); ?>">
                            <?php echo esc_html($workshop->title); ?>
                        </a>
                    </h3>
                    
                    <?php if ($workshop->workshop_type): ?>
                    <div class="roe-workshop-category"><?php echo esc_html($workshop->workshop_type); ?></div>
                    <?php endif; ?>
                    
                    <div class="roe-workshop-datetime">
                        <?php echo $frontend->format_workshop_datetime($workshop->start_date, $workshop->start_time); ?>
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
                    
                    <?php if ($workshop->description_full): ?>
                    <div class="roe-workshop-description">
                        <?php 
                        // Show first 150 characters
                        $description = wp_trim_words($workshop->description_full, 25, '...');
                        echo wp_kses_post($description);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Registration Info -->
                    <div class="roe-workshop-registration">
                        <div class="roe-workshop-cost">
                            <?php if ($workshop->web_rate > 0): ?>
                                <strong>Cost:</strong> $<?php echo number_format($workshop->web_rate, 2); ?>
                            <?php elseif ($workshop->cost_student > 0): ?>
                                <strong>Cost:</strong> $<?php echo number_format($workshop->cost_student, 2); ?>
                            <?php else: ?>
                                <strong>FREE</strong>
                            <?php endif; ?>
                        </div>
                        
                        <div class="roe-workshop-availability">
                            <?php echo $frontend->get_registration_spots_info($workshop); ?>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="roe-workshop-actions">
                        <a href="?workshop=<?php echo esc_attr($workshop->workshop_number); ?>" 
                           class="button button-primary">View Details</a>
                        
                        <?php if ($frontend->is_workshop_available($workshop)): ?>
                            <button type="button" class="button button-secondary roe-quick-register" 
                                    data-workshop="<?php echo esc_attr($workshop->workshop_number); ?>"
                                    data-title="<?php echo esc_attr($workshop->title); ?>">
                                Quick Register
                            </button>
                        <?php endif; ?>
                    </div>
                    
                </div>
                
            </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <div class="roe-no-workshops">
                <p>No workshops found matching your criteria.</p>
                <?php if (isset($_GET['search']) || isset($_GET['category'])): ?>
                    <p><a href="<?php echo remove_query_arg(array('search', 'category')); ?>">View all workshops</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination (if needed) -->
    <?php
    $total_workshops = $frontend->get_workshops_count(array(
        'search' => isset($_GET['search']) ? $_GET['search'] : '',
        'category' => isset($_GET['category']) ? $_GET['category'] : '',
        'upcoming_only' => $atts['upcoming'] === 'true'
    ));
    
    if ($total_workshops > intval($atts['limit'])):
    ?>
    <div class="roe-pagination">
        <!-- Pagination links would go here -->
        <p>Showing <?php echo count($workshops); ?> of <?php echo $total_workshops; ?> workshops</p>
    </div>
    <?php endif; ?>
    
</div>

<!-- Quick Registration Modal (will be styled with CSS/JS) -->
<div id="roe-quick-register-modal" style="display: none;">
    <div class="roe-modal-content">
        <span class="roe-modal-close">&times;</span>
        <h3 id="roe-modal-title">Quick Registration</h3>
        <form id="roe-quick-register-form">
            <div class="roe-form-row">
                <label for="first_name">First Name *</label>
                <input type="text" id="first_name" name="first_name" required />
            </div>
            <div class="roe-form-row">
                <label for="last_name">Last Name *</label>
                <input type="text" id="last_name" name="last_name" required />
            </div>
            <div class="roe-form-row">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required />
            </div>
            <div class="roe-form-row">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone" />
            </div>
            <div class="roe-form-row">
                <label for="organization">Organization/School District</label>
                <input type="text" id="organization" name="organization" />
            </div>
            <input type="hidden" id="workshop_number" name="workshop_number" />
            <div class="roe-form-actions">
                <button type="submit" class="button button-primary">Register</button>
                <button type="button" class="button roe-modal-cancel">Cancel</button>
            </div>
        </form>
    </div>
</div>