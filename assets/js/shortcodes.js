/**
 * E-Learning Quiz System - Shortcodes JavaScript
 * Handles loan calculator and other shortcode functionality
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('E-Learning Shortcodes JS loaded');
    
    // Initialize all loan calculators on the page
    $('.loan-calculator-container').each(function() {
        initializeLoanCalculator($(this));
    });
    
    // Initialize lesson/quiz progress tracking
    initializeProgressTracking();
    
    /**
     * Initialize a loan calculator instance
     */
    function initializeLoanCalculator($container) {
        const calculatorId = $container.attr('id');
        
        // Handle Enter key in input fields
        $container.find('.loan-input').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $container.find('.calculate-btn').click();
            }
        });
        
        // Auto-calculate on input change (optional enhancement)
        let calcTimeout;
        $container.find('.loan-input').on('input', function() {
            clearTimeout(calcTimeout);
            // Only auto-calculate if all fields have values
            const hasAllValues = $container.find('.loan-input').filter(function() {
                return $(this).val() === '';
            }).length === 0;
            
            if (hasAllValues) {
                calcTimeout = setTimeout(function() {
                    $container.find('.calculate-btn').click();
                }, 500);
            }
        });
        
        // Format number inputs
        $container.find('.loan-input').on('blur', function() {
            const value = parseFloat($(this).val());
            if (!isNaN(value)) {
                if ($(this).attr('name') === 'interest_rate') {
                    $(this).val(value.toFixed(2));
                } else if ($(this).attr('name') === 'loan_amount') {
                    $(this).val(Math.round(value));
                }
            }
        });
    }
    
    /**
     * Initialize progress tracking for embedded lessons/quizzes
     */
    function initializeProgressTracking() {
        // Track embedded lesson views
        $('.embedded-lesson').each(function() {
            const lessonId = $(this).data('lesson-id');
            if (lessonId) {
                trackLessonView(lessonId);
            }
        });
        
        // Track embedded quiz views
        $('.embedded-quiz').each(function() {
            const quizId = $(this).data('quiz-id');
            if (quizId) {
                trackQuizView(quizId);
            }
        });
    }
    
    /**
     * Track lesson view (for analytics)
     */
    function trackLessonView(lessonId) {
        // This could be expanded to send analytics data
        console.log('Tracking lesson view:', lessonId);
    }
    
    /**
     * Track quiz view (for analytics)
     */
    function trackQuizView(quizId) {
        // This could be expanded to send analytics data
        console.log('Tracking quiz view:', quizId);
    }
    
    /**
     * Global function to calculate loan (called from inline onclick)
     */
    window.calculateLoan = function(calculatorId) {
        const $container = $('#' + calculatorId);
        const $form = $container.find('.loan-form');
        const $resultsDiv = $container.find('.loan-results');
        const $errorDiv = $container.find('.error-message');
        const $button = $container.find('.calculate-btn');
        
        // Clear previous errors
        $errorDiv.hide();
        
        // Validate and calculate
        const loanAmount = parseFloat($form.find('[name="loan_amount"]').val());
        const interestRate = parseFloat($form.find('[name="interest_rate"]').val());
        const loanTerm = parseFloat($form.find('[name="loan_term"]').val());
        
        if (isNaN(loanAmount) || isNaN(interestRate) || isNaN(loanTerm)) {
            showError($errorDiv, elearningShortcodes.strings.error_invalid_input);
            return;
        }
        
        if (loanAmount <= 0 || interestRate < 0 || loanTerm <= 0) {
            showError($errorDiv, elearningShortcodes.strings.error_negative_values);
            return;
        }
        
        // Show loading state
        $button.find('.btn-text').hide();
        $button.find('.btn-loading').show();
        $button.prop('disabled', true);
        
        // Calculate after a brief delay for UX
        setTimeout(function() {
            try {
                // Perform calculation
                const monthlyRate = interestRate / 100 / 12;
                const numPayments = loanTerm * 12;
                
                let monthlyPayment;
                if (monthlyRate === 0) {
                    // Handle 0% interest rate
                    monthlyPayment = loanAmount / numPayments;
                } else {
                    monthlyPayment = (loanAmount * monthlyRate * Math.pow(1 + monthlyRate, numPayments)) / 
                                   (Math.pow(1 + monthlyRate, numPayments) - 1);
                }
                
                const totalPayment = monthlyPayment * numPayments;
                const totalInterest = totalPayment - loanAmount;
                
                // Display results
                displayResults($container, {
                    monthlyPayment: monthlyPayment,
                    totalPayment: totalPayment,
                    totalInterest: totalInterest,
                    interestRate: interestRate,
                    loanAmount: loanAmount,
                    currency: $form.data('currency') || '$'
                });
                
                // Show results with animation
                $resultsDiv.slideDown();
                
                // Scroll to results
                $('html, body').animate({
                    scrollTop: $resultsDiv.offset().top - 100
                }, 500);
                
            } catch (error) {
                console.error('Calculation error:', error);
                showError($errorDiv, 'An error occurred during calculation');
            }
            
            // Reset button state
            $button.find('.btn-text').show();
            $button.find('.btn-loading').hide();
            $button.prop('disabled', false);
            
        }, 300);
    };
    
    /**
     * Display calculation results
     */
    function displayResults($container, results) {
        const formatCurrency = function(amount, currency) {
            return currency + ' ' + new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        };
        
        $container.find('.monthly-payment').text(formatCurrency(results.monthlyPayment, results.currency));
        $container.find('.total-payment').text(formatCurrency(results.totalPayment, results.currency));
        $container.find('.total-interest').text(formatCurrency(results.totalInterest, results.currency));
        $container.find('.interest-display').text(results.interestRate.toFixed(2) + '%');
        
        // Update breakdown chart
        const principalPercentage = (results.loanAmount / results.totalPayment) * 100;
        const interestPercentage = (results.totalInterest / results.totalPayment) * 100;
        
        $container.find('.principal-bar').css('width', principalPercentage + '%');
        $container.find('.interest-bar').css('width', interestPercentage + '%');
        
        // Update chart with animation
        $container.find('.principal-bar, .interest-bar').css('transition', 'width 0.5s ease');
    }
    
    /**
     * Show error message
     */
    function showError($errorDiv, message) {
        $errorDiv.find('.error-text').text(message);
        $errorDiv.slideDown();
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $errorDiv.slideUp();
        }, 5000);
    }
    
    /**
     * Toggle breakdown visibility (global function)
     */
    window.toggleBreakdown = function(calculatorId) {
        const $container = $('#' + calculatorId);
        const $breakdown = $container.find('.payment-breakdown');
        const $button = $container.find('.toggle-breakdown');
        
        if ($breakdown.is(':visible')) {
            $breakdown.slideUp();
            $button.text('Show Payment Breakdown');
        } else {
            $breakdown.slideDown();
            $button.text('Hide Payment Breakdown');
        }
    };
    
    /**
     * Handle AJAX-loaded content
     */
    $(document).on('click', '.lesson-btn, .quiz-btn', function(e) {
        const $button = $(this);
        const href = $button.attr('href');
        
        // Add loading state
        $button.addClass('loading');
        
        // Track click event
        if ($button.hasClass('lesson-btn')) {
            console.log('Lesson button clicked:', href);
        } else if ($button.hasClass('quiz-btn')) {
            console.log('Quiz button clicked:', href);
        }
        
        // Remove loading state when page loads
        setTimeout(function() {
            $button.removeClass('loading');
        }, 1000);
    });
    
    /**
     * Handle user progress widget updates
     */
    if ($('.user-progress-widget').length > 0) {
        // Refresh progress data periodically
        setInterval(function() {
            refreshUserProgress();
        }, 60000); // Every minute
    }
    
    /**
     * Refresh user progress data
     */
    function refreshUserProgress() {
        const $widget = $('.user-progress-widget');
        if ($widget.length === 0) return;
        
        $.ajax({
            url: elearningShortcodes.ajaxUrl,
            type: 'POST',
            data: {
                action: 'elearning_get_user_progress',
                nonce: elearningShortcodes.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateProgressDisplay(response.data);
                }
            }
        });
    }
    
    /**
     * Update progress display
     */
    function updateProgressDisplay(data) {
        // This would update the progress widget with fresh data
        console.log('Progress data received:', data);
    }
    
    /**
     * Print calculator results
     */
    $(document).on('click', '.print-results', function(e) {
        e.preventDefault();
        window.print();
    });
    
    /**
     * Export calculator results
     */
    $(document).on('click', '.export-results', function(e) {
        e.preventDefault();
        const $container = $(this).closest('.loan-calculator-container');
        const results = gatherResultsData($container);
        downloadResults(results);
    });
    
    /**
     * Gather results data for export
     */
    function gatherResultsData($container) {
        return {
            loanAmount: $container.find('[name="loan_amount"]').val(),
            interestRate: $container.find('[name="interest_rate"]').val(),
            loanTerm: $container.find('[name="loan_term"]').val(),
            monthlyPayment: $container.find('.monthly-payment').text(),
            totalPayment: $container.find('.total-payment').text(),
            totalInterest: $container.find('.total-interest').text(),
            calculatedAt: new Date().toLocaleString()
        };
    }
    
    /**
     * Download results as text file
     */
    function downloadResults(results) {
        const content = `Loan Calculator Results
======================
Loan Amount: ${results.loanAmount}
Interest Rate: ${results.interestRate}%
Loan Term: ${results.loanTerm} years
Monthly Payment: ${results.monthlyPayment}
Total Payment: ${results.totalPayment}
Total Interest: ${results.totalInterest}
Calculated at: ${results.calculatedAt}`;
        
        const blob = new Blob([content], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'loan-calculation-' + Date.now() + '.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    console.log('Shortcodes JavaScript initialized');
});