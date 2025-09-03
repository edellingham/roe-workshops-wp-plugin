<?php
/**
 * Registration Form Template
 * Handles workshop registration form
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form id="roe-registration-form" class="roe-registration-form">
    <?php wp_nonce_field('roe_public_nonce', 'nonce'); ?>
    <input type="hidden" name="workshop_number" value="<?php echo esc_attr($workshop_number); ?>" />
    
    <div class="roe-form-section">
        <h4>Personal Information</h4>
        
        <div class="roe-form-row">
            <div class="roe-form-field">
                <label for="reg_first_name">First Name *</label>
                <input type="text" id="reg_first_name" name="first_name" required />
            </div>
            
            <div class="roe-form-field">
                <label for="reg_last_name">Last Name *</label>
                <input type="text" id="reg_last_name" name="last_name" required />
            </div>
        </div>
        
        <div class="roe-form-row">
            <div class="roe-form-field">
                <label for="reg_email">Email Address *</label>
                <input type="email" id="reg_email" name="email" required />
                <small>Confirmation will be sent to this email</small>
            </div>
            
            <div class="roe-form-field">
                <label for="reg_phone">Phone Number</label>
                <input type="tel" id="reg_phone" name="phone" />
            </div>
        </div>
    </div>
    
    <div class="roe-form-section">
        <h4>Organization Information</h4>
        
        <div class="roe-form-row">
            <div class="roe-form-field roe-form-field-full">
                <label for="reg_organization">Organization/School District</label>
                <input type="text" id="reg_organization" name="organization" />
            </div>
        </div>
    </div>
    
    <div class="roe-form-section">
        <h4>Mailing Address</h4>
        
        <div class="roe-form-row">
            <div class="roe-form-field roe-form-field-full">
                <label for="reg_address">Street Address</label>
                <input type="text" id="reg_address" name="address" />
            </div>
        </div>
        
        <div class="roe-form-row">
            <div class="roe-form-field">
                <label for="reg_city">City</label>
                <input type="text" id="reg_city" name="city" />
            </div>
            
            <div class="roe-form-field">
                <label for="reg_state">State</label>
                <input type="text" id="reg_state" name="state" value="IL" />
            </div>
            
            <div class="roe-form-field">
                <label for="reg_zip">ZIP Code</label>
                <input type="text" id="reg_zip" name="zip" pattern="[0-9]{5}(-[0-9]{4})?" />
            </div>
        </div>
    </div>
    
    <div class="roe-form-section">
        <h4>Registration Options</h4>
        
        <div class="roe-form-row">
            <div class="roe-form-field roe-form-field-full">
                <label>
                    <input type="checkbox" name="email_updates" value="1" />
                    Send me updates about future workshops and professional development opportunities
                </label>
            </div>
        </div>
        
        <div class="roe-form-row">
            <div class="roe-form-field roe-form-field-full">
                <label for="reg_special_needs">Special Accommodations Needed</label>
                <textarea id="reg_special_needs" name="special_needs" rows="3" 
                          placeholder="Please describe any special accommodations you need..."></textarea>
            </div>
        </div>
    </div>
    
    <div class="roe-form-actions">
        <button type="submit" class="button button-primary button-large">
            Register for Workshop
        </button>
        <div class="roe-form-loading" style="display: none;">
            Processing registration...
        </div>
    </div>
    
    <div class="roe-form-messages"></div>
    
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
    
    // Quick registration modal
    $('.roe-quick-register').on('click', function(e) {
        e.preventDefault();
        var workshopNumber = $(this).data('workshop');
        var workshopTitle = $(this).data('title');
        
        $('#workshop_number').val(workshopNumber);
        $('#roe-modal-title').text('Quick Registration - ' + workshopTitle);
        $('#roe-quick-register-modal').show();
    });
    
    // Close modal
    $('.roe-modal-close, .roe-modal-cancel').on('click', function() {
        $('#roe-quick-register-modal').hide();
    });
    
    // Handle registration form submission
    $('#roe-registration-form, #roe-quick-register-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $loading = $form.find('.roe-form-loading');
        var $messages = $('.roe-form-messages');
        
        // Show loading state
        $submitBtn.prop('disabled', true);
        $loading.show();
        $messages.empty();
        
        // Collect form data
        var formData = {
            action: 'roe_register_workshop',
            nonce: $form.find('[name="nonce"]').val(),
            workshop_number: $form.find('[name="workshop_number"]').val(),
            first_name: $form.find('[name="first_name"]').val(),
            last_name: $form.find('[name="last_name"]').val(),
            email: $form.find('[name="email"]').val(),
            phone: $form.find('[name="phone"]').val(),
            organization: $form.find('[name="organization"]').val(),
            address: $form.find('[name="address"]').val(),
            city: $form.find('[name="city"]').val(),
            state: $form.find('[name="state"]').val(),
            zip: $form.find('[name="zip"]').val(),
            special_needs: $form.find('[name="special_needs"]').val(),
            email_updates: $form.find('[name="email_updates"]:checked').val() || '0'
        };
        
        // Submit via AJAX
        $.post(roe_ajax.ajax_url, formData)
            .done(function(response) {
                if (response.success) {
                    $messages.html('<div class="roe-success-message">' + response.data.message + '</div>');
                    $form[0].reset();
                    $('#roe-quick-register-modal').hide();
                } else {
                    var errorHtml = '<div class="roe-error-message">Registration failed: ' + response.data.message;
                    if (response.data.errors) {
                        errorHtml += '<ul>';
                        response.data.errors.forEach(function(error) {
                            errorHtml += '<li>' + error + '</li>';
                        });
                        errorHtml += '</ul>';
                    }
                    errorHtml += '</div>';
                    $messages.html(errorHtml);
                }
            })
            .fail(function() {
                $messages.html('<div class="roe-error-message">Registration failed due to a network error. Please try again.</div>');
            })
            .always(function() {
                $submitBtn.prop('disabled', false);
                $loading.hide();
            });
    });
    
});
</script>