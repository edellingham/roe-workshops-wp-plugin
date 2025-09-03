/**
 * ROE Workshops Public JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Initialize workshop functionality
        initWorkshopList();
        initRegistrationForms();
        initQuickRegistration();
        
    });
    
    /**
     * Initialize workshop list functionality
     */
    function initWorkshopList() {
        // Add loading states to workshop cards
        $('.roe-workshop-card a').on('click', function() {
            $(this).closest('.roe-workshop-card').addClass('loading');
        });
        
        // Smooth scroll to search results after form submission
        if (window.location.search.includes('search=') || window.location.search.includes('category=')) {
            $('html, body').animate({
                scrollTop: $('.roe-workshops-grid').offset().top - 50
            }, 500);
        }
    }
    
    /**
     * Initialize registration form handling
     */
    function initRegistrationForms() {
        // Form validation
        $('.roe-registration-form').on('submit', function(e) {
            var $form = $(this);
            var isValid = validateRegistrationForm($form);
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
        
        // Real-time email validation
        $('input[name="email"]').on('blur', function() {
            var email = $(this).val();
            var $field = $(this).closest('.roe-form-field');
            
            if (email && !isValidEmail(email)) {
                $field.addClass('error');
                if (!$field.find('.error-message').length) {
                    $field.append('<div class="error-message">Please enter a valid email address</div>');
                }
            } else {
                $field.removeClass('error');
                $field.find('.error-message').remove();
            }
        });
        
        // Phone number formatting
        $('input[name="phone"]').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            if (value.length >= 6) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (value.length >= 3) {
                value = value.replace(/(\d{3})(\d{3})/, '($1) $2');
            }
            $(this).val(value);
        });
    }
    
    /**
     * Initialize quick registration modal
     */
    function initQuickRegistration() {
        // Quick register button click
        $(document).on('click', '.roe-quick-register', function(e) {
            e.preventDefault();
            
            var workshopNumber = $(this).data('workshop');
            var workshopTitle = $(this).data('title');
            
            if (!workshopNumber) {
                alert('Workshop information not available. Please try the full registration form.');
                return;
            }
            
            // Pre-fill modal
            $('#workshop_number').val(workshopNumber);
            $('#roe-modal-title').text('Quick Registration - ' + workshopTitle);
            
            // Show modal
            $('#roe-quick-register-modal').fadeIn(300);
            
            // Focus first field
            setTimeout(function() {
                $('#first_name').focus();
            }, 350);
        });
        
        // Close modal
        $(document).on('click', '.roe-modal-close, .roe-modal-cancel', function() {
            $('#roe-quick-register-modal').fadeOut(300);
            // Clear form
            $('#roe-quick-register-form')[0].reset();
            $('.roe-form-messages').empty();
        });
        
        // Close modal on background click
        $(document).on('click', '#roe-quick-register-modal', function(e) {
            if (e.target.id === 'roe-quick-register-modal') {
                $(this).fadeOut(300);
            }
        });
        
        // Escape key to close modal
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC key
                $('#roe-quick-register-modal').fadeOut(300);
            }
        });
    }
    
    /**
     * Validate registration form
     */
    function validateRegistrationForm($form) {
        var isValid = true;
        var $errors = $form.find('.roe-form-messages');
        $errors.empty();
        
        // Clear previous errors
        $form.find('.roe-form-field').removeClass('error');
        $form.find('.error-message').remove();
        
        // Check required fields
        $form.find('[required]').each(function() {
            var $field = $(this);
            var $wrapper = $field.closest('.roe-form-field');
            
            if (!$field.val().trim()) {
                $wrapper.addClass('error');
                $wrapper.append('<div class="error-message">This field is required</div>');
                isValid = false;
            }
        });
        
        // Validate email
        var email = $form.find('[name="email"]').val();
        if (email && !isValidEmail(email)) {
            var $emailField = $form.find('[name="email"]').closest('.roe-form-field');
            $emailField.addClass('error');
            $emailField.append('<div class="error-message">Please enter a valid email address</div>');
            isValid = false;
        }
        
        if (!isValid) {
            $errors.html('<div class="roe-error-message">Please fix the errors below and try again.</div>');
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $form.find('.error').first().offset().top - 50
            }, 300);
        }
        
        return isValid;
    }
    
    /**
     * Validate email format
     */
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Show loading state
     */
    function showLoadingState($button) {
        $button.prop('disabled', true);
        var originalText = $button.text();
        $button.data('original-text', originalText);
        $button.text('Processing...');
    }
    
    /**
     * Hide loading state
     */
    function hideLoadingState($button) {
        $button.prop('disabled', false);
        var originalText = $button.data('original-text');
        if (originalText) {
            $button.text(originalText);
        }
    }
    
})(jQuery);