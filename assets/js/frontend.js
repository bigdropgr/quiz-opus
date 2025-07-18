/* E-Learning Quiz System - Frontend JavaScript - ENHANCED SCORING VERSION */

jQuery(document).ready(function($) {
    'use strict';
    
    // Check if elearningQuiz is defined
    if (typeof elearningQuiz === 'undefined') {
        console.error('E-Learning Quiz: elearningQuiz object not found - Scripts not properly loaded');
        return;
    }
    
    // Detect if device is mobile
    const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
    
    // Quiz state management
    let currentQuiz = {
        id: null,
        attemptId: null,
        currentQuestion: 0,
        totalQuestions: 0,
        answers: {},
        startTime: null,
        questionStartTime: null,
        timeLimit: 0,
        timerInterval: null,
        questionTimings: {},
        selectedWord: null, // For mobile tap interface
        selectedDraggable: null, // For mobile tap interface
        questions: [] // Store questions for results display
    };
    
    // Initialize quiz functionality
    initializeQuiz();
    
    function initializeQuiz() {
        // Start quiz button
        $('.start-quiz-btn').on('click', handleStartQuiz);
        
        // Retake quiz button
        $('.retake-quiz-btn').on('click', handleRetakeQuiz);
        
        // Navigation buttons
        $('.prev-btn').on('click', handlePreviousQuestion);
        $('.next-btn').on('click', handleNextQuestion);
        
        // Submit button - use event delegation for dynamically added buttons
        $(document).on('click', '.quiz-submit-btn', handleSubmitQuiz);
        
        // Answer change handlers
        $(document).on('change', '.quiz-question input[type="radio"], .quiz-question input[type="checkbox"]', handleAnswerChange);
        $(document).on('change', '.match-select', handleMatchingChange);
        
        // Initialize drag and drop OR tap interface based on device
        if (isMobileDevice) {
            initializeMobileTapInterface();
        } else {
            initializeDragAndDrop();
        }
        
        // Keyboard navigation
        $(document).on('keydown', handleKeyboardNavigation);
        
        // Modal handlers
        $(document).on('click', '#confirm-submit', confirmSubmitQuiz);
        $(document).on('click', '#cancel-submit', cancelSubmitQuiz);
        
        // Form submission prevention
        $('.elearning-quiz-form').on('submit', function(e) {
            e.preventDefault();
        });
        
        // Auto-save answers (accessibility feature)
        setInterval(autoSaveProgress, 30000); // Every 30 seconds
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', handleVisibilityChange);
        
        // Handle page unload
        window.addEventListener('beforeunload', handlePageUnload);
        
        // Store quiz questions when they become active
        $(document).on('quiz-started', function() {
            $('.quiz-question').each(function(index) {
                const $question = $(this);
                currentQuiz.questions[index] = {
                    type: $question.data('question-type'),
                    question: $question.find('.question-text').html(),
                    element: $question.clone(),
                    index: index
                };
            });
        });
    }
    
    function handleStartQuiz() {
        const $btn = $(this);
        const quizId = $btn.data('quiz-id');
        
        if (!quizId) {
            console.error('No quiz ID found');
            showError(elearningQuiz.strings.error || 'An error occurred');
            return;
        }
        
        // Store original button text
        if (!$btn.data('original-text')) {
            $btn.data('original-text', $btn.text());
        }
        
        $btn.prop('disabled', true).text(elearningQuiz.strings.loading || 'Loading...');
        
        // Start quiz attempt via AJAX
        $.ajax({
            url: elearningQuiz.ajaxUrl,
            type: 'POST',
            data: {
                action: 'elearning_start_quiz',
                quiz_id: quizId,
                nonce: elearningQuiz.nonce
            },
            success: function(response) {
                if (response.success) {
                    currentQuiz.id = quizId;
                    currentQuiz.attemptId = response.data.attempt_id;
                    currentQuiz.totalQuestions = response.data.total_questions;
                    currentQuiz.timeLimit = response.data.time_limit || 0;
                    currentQuiz.startTime = new Date();
                    
                    // Update form with attempt ID
                    $('input[name="attempt_id"]').val(currentQuiz.attemptId);
                    
                    // Show quiz form, hide intro
                    $('.elearning-quiz-intro').slideUp();
                    $('.elearning-quiz-form').slideDown(function() {
                        // Re-initialize accessibility after form is shown
                        initializeAccessibility();
                        // Trigger event to store questions
                        $(document).trigger('quiz-started');
                    });
                    
                    // Initialize first question
                    showQuestion(0);
                    
                    // Start quiz timer if time limit is set
                    if (currentQuiz.timeLimit > 0) {
                        startQuizTimer();
                    }
                    
                    // Start question timer
                    startQuestionTimer();
                    
                    // Focus first input for accessibility
                    setTimeout(() => {
                        $('.quiz-question.active').find('input, select').first().focus();
                    }, 500);
                    
                } else {
                    showError(response.data || elearningQuiz.strings.error || 'An error occurred');
                    $btn.prop('disabled', false).text($btn.data('original-text') || 'Start Quiz');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showError(elearningQuiz.strings.error || 'An error occurred');
                $btn.prop('disabled', false).text($btn.data('original-text') || 'Start Quiz');
            }
        });
    }
    
    function startQuizTimer() {
        if (currentQuiz.timeLimit <= 0) return;
        
        const endTime = new Date(currentQuiz.startTime.getTime() + currentQuiz.timeLimit * 60000);
        
        // Add timer display
        if (!$('.quiz-timer').length) {
            $('.quiz-progress').after('<div class="quiz-timer"><span class="timer-label">' + 
                (elearningQuiz.strings.time_remaining || 'Time Remaining') + 
                ':</span> <span class="timer-display">--:--</span></div>');
        }
        
        // Update timer every second
        currentQuiz.timerInterval = setInterval(function() {
            const now = new Date();
            const remaining = Math.max(0, endTime - now);
            
            if (remaining <= 0) {
                clearInterval(currentQuiz.timerInterval);
                handleTimeUp();
                return;
            }
            
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            
            $('.timer-display').text(
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0')
            );
            
            // Warning at 1 minute
            if (remaining <= 60000 && !$('.quiz-timer').hasClass('warning')) {
                $('.quiz-timer').addClass('warning');
                announceToScreenReader(elearningQuiz.strings.one_minute_warning || 'One minute remaining');
            }
        }, 1000);
    }
    
    function handleTimeUp() {
        clearInterval(currentQuiz.timerInterval);
        
        // Show time up modal
        const $modal = $('<div class="quiz-modal" id="time-up-modal">' +
            '<div class="modal-content">' +
            '<h3>' + (elearningQuiz.strings.time_up || 'Time\'s Up!') + '</h3>' +
            '<p>' + (elearningQuiz.strings.submitting_quiz || 'Your quiz is being submitted...') + '</p>' +
            '<div class="loading-spinner"></div>' +
            '</div></div>');
        
        $('body').append($modal);
        $modal.fadeIn();
        
        // Auto-submit quiz
        saveCurrentAnswer();
        submitQuizData();
    }
    
    function handleRetakeQuiz() {
        $('.elearning-quiz-passed').slideUp();
        $('.elearning-quiz-intro').slideDown();
        
        // Reset quiz state
        resetQuizState();
        
        // Reset form
        $('.elearning-quiz-form')[0].reset();
        $('.quiz-question').removeClass('active');
        $('.quiz-results').hide();
        
        // Clear any previous answers
        $('.option-label').removeClass('selected');
        $('.blank-space').empty().removeClass('filled');
        $('.word-item').removeClass('used selected');
        $('.match-select').val('');
        $('.drop-zone').each(function() {
            $(this).html('<span class="drop-placeholder">' + 
                (elearningQuiz.strings.drop_here || 'Drop here') + '</span>')
                .removeClass('has-item');
        });
        $('.draggable-item').removeClass('used selected');
    }
    
    function resetQuizState() {
        // Clear timer
        if (currentQuiz.timerInterval) {
            clearInterval(currentQuiz.timerInterval);
        }
        
        currentQuiz = {
            id: null,
            attemptId: null,
            currentQuestion: 0,
            totalQuestions: 0,
            answers: {},
            startTime: null,
            questionStartTime: null,
            timeLimit: 0,
            timerInterval: null,
            questionTimings: {},
            selectedWord: null,
            selectedDraggable: null,
            questions: []
        };
        
        // Remove timer display
        $('.quiz-timer').remove();
    }
    
    function handlePreviousQuestion() {
        if (currentQuiz.currentQuestion > 0) {
            saveCurrentAnswer();
            recordQuestionTime();
            showQuestion(currentQuiz.currentQuestion - 1);
        }
    }
    
    function handleNextQuestion() {
        saveCurrentAnswer();
        recordQuestionTime();
        
        if (currentQuiz.currentQuestion < currentQuiz.totalQuestions - 1) {
            showQuestion(currentQuiz.currentQuestion + 1);
        }
    }
    
    function handleSubmitQuiz() {
        // Save current answer first
        saveCurrentAnswer();
        
        // Check if all questions answered
        const unanswered = [];
        $('.quiz-question').each(function(index) {
            const $question = $(this);
            const questionIndex = parseInt($question.data('question-index'));
            const questionType = $question.data('question-type');
            
            let hasAnswer = false;
            
            switch (questionType) {
                case 'multiple_choice':
                    hasAnswer = $question.find('input[type="radio"]:checked, input[type="checkbox"]:checked').length > 0;
                    break;
                case 'true_false':
                    hasAnswer = $question.find('input[type="radio"]:checked').length > 0;
                    break;
                case 'fill_blanks':
                    hasAnswer = $question.find('.blank-answer').filter(function() {
                        return $(this).val() !== '';
                    }).length > 0;
                    break;
                case 'matching':
                    hasAnswer = $question.find('.match-answer').filter(function() {
                        return $(this).val() !== '';
                    }).length > 0;
                    break;
            }
            
            if (!hasAnswer && !currentQuiz.answers.hasOwnProperty(questionIndex)) {
                unanswered.push(index + 1);
            }
        });
        
        if (unanswered.length > 0) {
            const message = (elearningQuiz.strings.unanswered_questions || 'You have unanswered questions: ') + 
                unanswered.join(', ') + '. ' + 
                (elearningQuiz.strings.submit_anyway || 'Submit anyway?');
            
            if (!confirm(message)) {
                return;
            }
        }
        
        // Show confirmation modal
        const $confirmModal = $('#quiz-confirmation-modal');
        if ($confirmModal.length > 0) {
            $confirmModal.fadeIn();
        } else {
            // If modal doesn't exist, submit directly
            $('#quiz-loading-modal').fadeIn();
            submitQuizData();
        }
    }
    
    function confirmSubmitQuiz() {
        $('#quiz-confirmation-modal').fadeOut();
        $('#quiz-loading-modal').fadeIn();
        
        saveCurrentAnswer();
        recordQuestionTime();
        submitQuizData();
    }
    
    function submitQuizData() {
        // Clear timer
        if (currentQuiz.timerInterval) {
            clearInterval(currentQuiz.timerInterval);
        }
        
        // Submit quiz via AJAX
        $.ajax({
            url: elearningQuiz.ajaxUrl,
            type: 'POST',
            data: {
                action: 'elearning_submit_quiz',
                attempt_id: currentQuiz.attemptId,
                answers: JSON.stringify(currentQuiz.answers),
                question_timings: JSON.stringify(currentQuiz.questionTimings),
                nonce: elearningQuiz.nonce
            },
            success: function(response) {
                $('#quiz-loading-modal').fadeOut();
                $('#time-up-modal').remove();
                
                if (response.success) {
                    displayResults(response.data);
                } else {
                    showError(response.data || elearningQuiz.strings.error || 'An error occurred');
                }
            },
            error: function() {
                $('#quiz-loading-modal').fadeOut();
                $('#time-up-modal').remove();
                showError(elearningQuiz.strings.error || 'An error occurred');
            }
        });
    }
    
    function cancelSubmitQuiz() {
        $('#quiz-confirmation-modal').fadeOut();
    }
    
    function showQuestion(questionIndex) {
        // Update current question
        currentQuiz.currentQuestion = questionIndex;
        
        // Hide all questions
        $('.quiz-question').removeClass('active');
        
        // Show current question
        const $currentQuestion = $('.quiz-question').eq(questionIndex);
        $currentQuestion.addClass('active');
        
        // Store question data if not already stored
        if (!currentQuiz.questions[questionIndex]) {
            currentQuiz.questions[questionIndex] = {
                type: $currentQuestion.data('question-type'),
                question: $currentQuestion.find('.question-text').html(),
                element: $currentQuestion.clone()
            };
        }
        
        // Randomize answer order if enabled
        if ($currentQuestion.data('randomize-answers') === 'yes') {
            randomizeAnswers($currentQuestion);
        }
        
        // Update progress
        updateProgress();
        
        // Update navigation buttons
        updateNavigationButtons();
        
        // Load saved answer if exists
        loadSavedAnswer(questionIndex);
        
        // Start question timer
        startQuestionTimer();
        
        // Scroll to top
        $currentQuestion[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Focus first input for accessibility
        setTimeout(() => {
            $currentQuestion.find('input:not([type="hidden"]), select').first().focus();
        }, 300);
        
        // Announce question to screen reader
        const questionNumber = questionIndex + 1;
        announceToScreenReader(`Question ${questionNumber} of ${currentQuiz.totalQuestions}`);
    }
    
    function randomizeAnswers($question) {
        const questionType = $question.data('question-type');
        
        if (questionType === 'multiple_choice') {
            const $container = $question.find('.multiple-choice-options');
            const $options = $container.find('.option-label');
            
            // Shuffle and re-append
            $options.sort(() => Math.random() - 0.5).appendTo($container);
        }
    }
    
    function updateProgress() {
        const percentage = ((currentQuiz.currentQuestion + 1) / currentQuiz.totalQuestions) * 100;
        $('.progress-fill').css('width', percentage + '%');
        $('.progress-text .current').text(currentQuiz.currentQuestion + 1);
        $('.progress-text .total').text(currentQuiz.totalQuestions);
        
        // Update progress bar accessibility
        $('.quiz-progress').attr('aria-valuenow', currentQuiz.currentQuestion + 1)
                          .attr('aria-valuetext', `Question ${currentQuiz.currentQuestion + 1} of ${currentQuiz.totalQuestions}`);
    }
    
    function updateNavigationButtons() {
        // Previous button
        if (currentQuiz.currentQuestion === 0) {
            $('.prev-btn').prop('disabled', true);
        } else {
            $('.prev-btn').prop('disabled', false);
        }
        
        // Next/Submit button
        if (currentQuiz.currentQuestion === currentQuiz.totalQuestions - 1) {
            $('.next-btn').hide();
            $('.quiz-submit-btn').show();
        } else {
            $('.next-btn').show();
            $('.quiz-submit-btn').hide();
        }
    }
    
    function handleAnswerChange() {
        const $question = $(this).closest('.quiz-question');
        const questionType = $question.data('question-type');
        
        // Visual feedback
        if ($(this).is(':radio')) {
            $question.find('.option-label').removeClass('selected');
        }
        $(this).closest('.option-label').addClass('selected');
        
        // Save answer immediately on change
        saveCurrentAnswer();
        
        // Auto-advance for single-choice questions (disabled for true/false)
        if (questionType === 'multiple_choice' && $question.find('input[type="radio"]').length > 0) {
            setTimeout(() => {
                if (currentQuiz.currentQuestion < currentQuiz.totalQuestions - 1) {
                    $('.next-btn').click();
                }
            }, 800);
        }
    }
    
    function handleMatchingChange() {
        const $select = $(this);
        const $question = $select.closest('.quiz-question');
        
        // Visual feedback
        $select.closest('.match-item').addClass('answered');
    }
    
    function saveCurrentAnswer() {
        const $currentQuestion = $('.quiz-question.active');
        if ($currentQuestion.length === 0) return;
        
        const questionIndex = parseInt($currentQuestion.data('question-index'));
        const questionType = $currentQuestion.data('question-type');
        
        let answer = null;
        
        switch (questionType) {
            case 'multiple_choice':
                const checkboxes = $currentQuestion.find('input[type="checkbox"]:checked');
                const radioButtons = $currentQuestion.find('input[type="radio"]:checked');
                
                if (checkboxes.length > 0) {
                    answer = [];
                    checkboxes.each(function() {
                        answer.push(parseInt($(this).val()));
                    });
                } else if (radioButtons.length > 0) {
                    answer = parseInt(radioButtons.val());
                }
                break;
                
            case 'true_false':
                const tfAnswer = $currentQuestion.find('input[type="radio"]:checked').val();
                if (tfAnswer !== undefined) {
                    answer = tfAnswer;
                }
                break;
                
            case 'fill_blanks':
                answer = [];
                $currentQuestion.find('.blank-answer').each(function() {
                    answer.push($(this).val() || '');
                });
                // Only save if at least one blank is filled
                if (answer.every(a => a === '')) {
                    answer = null;
                }
                break;
                
            case 'matching':
                answer = {};
                let hasMatches = false;
                $currentQuestion.find('.match-answer').each(function() {
                    const $input = $(this);
                    const inputName = $input.attr('name');
                    const value = $input.val();
                    
                    if (value) {
                        hasMatches = true;
                        const leftIndexMatch = inputName.match(/\[(\d+)\]$/);
                        if (leftIndexMatch) {
                            const leftIndex = parseInt(leftIndexMatch[1]);
                            const rightIndex = parseInt(value);
                            answer[leftIndex] = rightIndex;
                        }
                    }
                });
                if (!hasMatches) {
                    answer = null;
                }
                break;
        }
        
        if (answer !== null && answer !== undefined) {
            currentQuiz.answers[questionIndex] = answer;
        }
    }
    
    function loadSavedAnswer(questionIndex) {
        const $question = $('.quiz-question').eq(questionIndex);
        const questionType = $question.data('question-type');
        const savedAnswer = currentQuiz.answers[questionIndex];
        
        if (!savedAnswer) return;
        
        switch (questionType) {
            case 'multiple_choice':
                if (Array.isArray(savedAnswer)) {
                    // Multiple select
                    savedAnswer.forEach(value => {
                        $question.find(`input[type="checkbox"][value="${value}"]`).prop('checked', true)
                            .closest('.option-label').addClass('selected');
                    });
                } else {
                    // Single select
                    $question.find(`input[type="radio"][value="${savedAnswer}"]`).prop('checked', true)
                        .closest('.option-label').addClass('selected');
                }
                break;
                
            case 'true_false':
                $question.find(`input[type="radio"][value="${savedAnswer}"]`).prop('checked', true)
                    .closest('.option-label').addClass('selected');
                break;
                
            case 'fill_blanks':
                if (Array.isArray(savedAnswer)) {
                    savedAnswer.forEach((value, index) => {
                        const $blank = $question.find(`.blank-space[data-blank-index="${index}"]`);
                        const $hiddenInput = $question.find(`.blank-answer[data-blank-index="${index}"]`);
                        
                        if (value) {
                            $blank.text(value).addClass('filled');
                            $hiddenInput.val(value);
                            
                            // Mark word as used
                            $question.find(`.word-item[data-word="${value}"]`).addClass('used');
                        }
                    });
                }
                break;
                
            case 'matching':
                Object.keys(savedAnswer).forEach(leftIndex => {
                    const rightIndex = savedAnswer[leftIndex];
                    const $dropZone = $question.find(`.drop-zone[data-left-index="${leftIndex}"]`);
                    const $draggableItem = $question.find(`.draggable-item[data-right-index="${rightIndex}"]`);
                    const itemText = $draggableItem.data('item-text');
                    
                    if ($dropZone.length && $draggableItem.length) {
                        // Add item to drop zone
                        const droppedItemHtml = `
                            <div class="dropped-item">
                                <span>${itemText}</span>
                                <button type="button" class="remove-match" data-left-index="${leftIndex}" data-right-index="${rightIndex}">×</button>
                            </div>
                        `;
                        $dropZone.html(droppedItemHtml).addClass('has-item');
                        
                        // Update hidden input
                        $question.find(`.match-answer[name*="[${leftIndex}]"]`).val(rightIndex);
                        
                        // Mark draggable item as used
                        $draggableItem.addClass('used');
                    }
                });
                break;
        }
    }
    
    function startQuestionTimer() {
        currentQuiz.questionStartTime = new Date();
    }
    
    function recordQuestionTime() {
        if (!currentQuiz.questionStartTime) return;
        
        const timeSpent = Math.round((new Date() - currentQuiz.questionStartTime) / 1000);
        currentQuiz.questionTimings[currentQuiz.currentQuestion] = timeSpent;
    }
    
    // DESKTOP: Original drag and drop functionality
    function initializeDragAndDrop() {
        // Use jQuery UI for better browser compatibility
        
        // === FILL IN THE BLANKS DRAG & DROP ===
        $('.word-item').draggable({
            revert: 'invalid',
            helper: 'clone',
            cursor: 'move',
            zIndex: 1000,
            start: function(event, ui) {
                if ($(this).hasClass('used')) {
                    return false;
                }
                $(this).addClass('dragging');
            },
            stop: function() {
                $(this).removeClass('dragging');
            }
        });
        
        $('.blank-space').droppable({
            accept: '.word-item',
            hoverClass: 'drop-target',
            drop: function(event, ui) {
                const $blank = $(this);
                const word = ui.draggable.data('word');
                const blankIndex = $blank.data('blank-index');
                const $question = $blank.closest('.quiz-question');
                
                // Clear previous value if any
                const previousWord = $blank.text();
                if (previousWord) {
                    $question.find(`.word-item[data-word="${previousWord}"]`).removeClass('used');
                }
                
                // Set new value
                $blank.text(word).addClass('filled');
                $question.find(`.blank-answer[data-blank-index="${blankIndex}"]`).val(word);
                
                // Mark word as used
                ui.draggable.addClass('used');
                
                // Save answer
                saveCurrentAnswer();
                
                // Provide feedback
                announceToScreenReader(`${word} placed in blank ${blankIndex + 1}`);
            }
        });
        
        // === MATCHING DRAG & DROP ===
        $('.draggable-item').draggable({
            revert: 'invalid',
            helper: 'clone',
            cursor: 'move',
            zIndex: 1000,
            start: function(event, ui) {
                if ($(this).hasClass('used')) {
                    return false;
                }
                $(this).addClass('dragging');
            },
            stop: function() {
                $(this).removeClass('dragging');
            }
        });
        
        $('.drop-zone').droppable({
            accept: '.draggable-item',
            hoverClass: 'drag-over',
            drop: function(event, ui) {
                const $dropZone = $(this);
                const itemText = ui.draggable.data('item-text');
                const rightIndex = ui.draggable.data('right-index');
                const leftIndex = $dropZone.data('left-index');
                const $question = $dropZone.closest('.quiz-question');
                
                // Clear previous match if any
                const $hiddenInput = $question.find(`.match-answer[name*="[${leftIndex}]"]`);
                const previousRightIndex = $hiddenInput.val();
                if (previousRightIndex) {
                    $question.find(`.draggable-item[data-right-index="${previousRightIndex}"]`).removeClass('used');
                }
                
                // Clear this drop zone if it had an item
                $dropZone.find('.dropped-item').remove();
                $dropZone.removeClass('has-item');
                
                // Add new item to drop zone
                const droppedItemHtml = `
                    <div class="dropped-item">
                        <span>${itemText}</span>
                        <button type="button" class="remove-match" data-left-index="${leftIndex}" data-right-index="${rightIndex}">×</button>
                    </div>
                `;
                $dropZone.html(droppedItemHtml).addClass('has-item');
                
                // Update hidden input
                $hiddenInput.val(rightIndex);
                
                // Mark draggable item as used
                ui.draggable.addClass('used');
                
                // Save answer
                saveCurrentAnswer();
                
                // Provide feedback
                announceToScreenReader(`${itemText} matched with item ${parseInt(leftIndex) + 1}`);
            }
        });
        
        // Handle remove match button
        $(document).on('click', '.remove-match', function(e) {
            e.preventDefault();
            
            const leftIndex = $(this).data('left-index');
            const rightIndex = $(this).data('right-index');
            const $question = $(this).closest('.quiz-question');
            
            // Clear the drop zone
            const $dropZone = $(this).closest('.drop-zone');
            $dropZone.html('<span class="drop-placeholder">' + 
                (elearningQuiz.strings.drop_here || 'Drop here') + '</span>').removeClass('has-item');
            
            // Clear hidden input
            $question.find(`.match-answer[name*="[${leftIndex}]"]`).val('');
            
            // Mark draggable item as available again
            $question.find(`.draggable-item[data-right-index="${rightIndex}"]`).removeClass('used');
            
            // Save answer
            saveCurrentAnswer();
            
            // Provide feedback
            announceToScreenReader(`Match removed from item ${parseInt(leftIndex) + 1}`);
        });
        
        // Double-click to remove word from blank
        $(document).on('dblclick', '.blank-space.filled', function() {
            const word = $(this).text();
            const blankIndex = $(this).data('blank-index');
            const $question = $(this).closest('.quiz-question');
            
            $(this).text('').removeClass('filled');
            $question.find(`.blank-answer[data-blank-index="${blankIndex}"]`).val('');
            $question.find(`.word-item[data-word="${word}"]`).removeClass('used');
            
            // Save answer
            saveCurrentAnswer();
            
            announceToScreenReader(`${word} removed from blank ${blankIndex + 1}`);
        });
    }
    
    // MOBILE: Tap-to-select interface
    function initializeMobileTapInterface() {
        // Add mobile class to quiz container
        $('.elearning-quiz-container').addClass('mobile-interface');
        
        // === FILL IN THE BLANKS TAP INTERFACE ===
        
        // Tap on word to select
        $(document).on('click', '.word-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $word = $(this);
            const $question = $word.closest('.quiz-question');
            
            // Don't allow selection of used words
            if ($word.hasClass('used')) {
                return;
            }
            
            // Clear previous selection
            $question.find('.word-item').removeClass('selected');
            $question.find('.blank-space').removeClass('awaiting-selection');
            
            // Select this word
            $word.addClass('selected');
            currentQuiz.selectedWord = $word.data('word');
            
            // Highlight empty blanks
            $question.find('.blank-space:not(.filled)').addClass('awaiting-selection');
            
            // Provide feedback
            announceToScreenReader(`Selected word: ${currentQuiz.selectedWord}. Now tap a blank to place it.`);
        });
        
        // Tap on blank to place selected word
        $(document).on('click', '.blank-space', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $blank = $(this);
            const $question = $blank.closest('.quiz-question');
            const blankIndex = $blank.data('blank-index');
            
            if (currentQuiz.selectedWord) {
                // Clear previous value if any
                const previousWord = $blank.text();
                if (previousWord) {
                    $question.find(`.word-item[data-word="${previousWord}"]`).removeClass('used');
                }
                
                // Place the selected word
                $blank.text(currentQuiz.selectedWord).addClass('filled');
                $question.find(`.blank-answer[data-blank-index="${blankIndex}"]`).val(currentQuiz.selectedWord);
                
                // Mark word as used
                $question.find(`.word-item[data-word="${currentQuiz.selectedWord}"]`)
                    .removeClass('selected')
                    .addClass('used');
                
                // Clear selection state
                $question.find('.blank-space').removeClass('awaiting-selection');
                currentQuiz.selectedWord = null;
                
                // Save answer
                saveCurrentAnswer();
                
                // Provide feedback
                announceToScreenReader(`Word placed in blank ${blankIndex + 1}`);
            } else if ($blank.hasClass('filled')) {
                // Tap on filled blank to remove word
                const word = $blank.text();
                
                $blank.text('').removeClass('filled');
                $question.find(`.blank-answer[data-blank-index="${blankIndex}"]`).val('');
                $question.find(`.word-item[data-word="${word}"]`).removeClass('used');
                
                // Save answer
                saveCurrentAnswer();
                
                announceToScreenReader(`${word} removed from blank ${blankIndex + 1}`);
            }
        });
        
        // === MATCHING TAP INTERFACE ===
        
        // Tap on draggable item to select
        $(document).on('click', '.draggable-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $item = $(this);
            const $question = $item.closest('.quiz-question');
            
            // Don't allow selection of used items
            if ($item.hasClass('used')) {
                return;
            }
            
            // Clear previous selection
            $question.find('.draggable-item').removeClass('selected');
            $question.find('.drop-zone').removeClass('awaiting-selection');
            
            // Select this item
            $item.addClass('selected');
            currentQuiz.selectedDraggable = {
                text: $item.data('item-text'),
                index: $item.data('right-index')
            };
            
            // Highlight empty drop zones
            $question.find('.drop-zone:not(.has-item)').addClass('awaiting-selection');
            
            // Provide feedback
            announceToScreenReader(`Selected: ${currentQuiz.selectedDraggable.text}. Now tap a target to match it.`);
        });
        
        // Tap on drop zone to place selected item
        $(document).on('click', '.drop-zone', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $dropZone = $(this);
            const $question = $dropZone.closest('.quiz-question');
            const leftIndex = $dropZone.data('left-index');
            
            if (currentQuiz.selectedDraggable && !$dropZone.hasClass('has-item')) {
                // Clear previous match if any
                const $hiddenInput = $question.find(`.match-answer[name*="[${leftIndex}]"]`);
                const previousRightIndex = $hiddenInput.val();
                if (previousRightIndex) {
                    $question.find(`.draggable-item[data-right-index="${previousRightIndex}"]`).removeClass('used');
                }
                
                // Place the selected item
                const droppedItemHtml = `
                    <div class="dropped-item">
                        <span>${currentQuiz.selectedDraggable.text}</span>
                        <button type="button" class="remove-match" data-left-index="${leftIndex}" data-right-index="${currentQuiz.selectedDraggable.index}">×</button>
                    </div>
                `;
                $dropZone.html(droppedItemHtml).addClass('has-item');
                
                // Update hidden input
                $hiddenInput.val(currentQuiz.selectedDraggable.index);
                
                // Mark draggable item as used
                $question.find(`.draggable-item[data-right-index="${currentQuiz.selectedDraggable.index}"]`)
                    .removeClass('selected')
                    .addClass('used');
                
                // Clear selection state
                $question.find('.drop-zone').removeClass('awaiting-selection');
                currentQuiz.selectedDraggable = null;
                
                // Save answer
                saveCurrentAnswer();
                
                // Provide feedback
                announceToScreenReader(`Matched with item ${parseInt(leftIndex) + 1}`);
            }
        });
        
        // Handle remove match button (same for mobile)
        $(document).on('click', '.remove-match', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const leftIndex = $(this).data('left-index');
            const rightIndex = $(this).data('right-index');
            const $question = $(this).closest('.quiz-question');
            
            // Clear the drop zone
            const $dropZone = $(this).closest('.drop-zone');
            $dropZone.html('<span class="drop-placeholder">' + 
                (elearningQuiz.strings.drop_here || 'Drop here') + '</span>').removeClass('has-item');
            
            // Clear hidden input
            $question.find(`.match-answer[name*="[${leftIndex}]"]`).val('');
            
            // Mark draggable item as available again
            $question.find(`.draggable-item[data-right-index="${rightIndex}"]`).removeClass('used');
            
            // Save answer
            saveCurrentAnswer();
            
            // Provide feedback
            announceToScreenReader(`Match removed from item ${parseInt(leftIndex) + 1}`);
        });
        
        // Clear selection when tapping elsewhere
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.word-item, .blank-space, .draggable-item, .drop-zone').length) {
                $('.word-item').removeClass('selected');
                $('.draggable-item').removeClass('selected');
                $('.blank-space').removeClass('awaiting-selection');
                $('.drop-zone').removeClass('awaiting-selection');
                currentQuiz.selectedWord = null;
                currentQuiz.selectedDraggable = null;
            }
        });
    }
    
    function handleKeyboardNavigation(e) {
        // Handle keyboard navigation within quiz
        if (!$('.elearning-quiz-form').is(':visible')) return;
        
        switch (e.key) {
            case 'ArrowLeft':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    $('.prev-btn:not(:disabled)').click();
                }
                break;
                
            case 'ArrowRight':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    if ($('.next-btn').is(':visible')) {
                        $('.next-btn').click();
                    } else if ($('.quiz-submit-btn').is(':visible')) {
                        $('.quiz-submit-btn').click();
                    }
                }
                break;
                
            case 'Enter':
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    if ($('.next-btn').is(':visible')) {
                        $('.next-btn').click();
                    } else if ($('.quiz-submit-btn').is(':visible')) {
                        $('.quiz-submit-btn').click();
                    }
                }
                break;
                
            case 'Escape':
                // Close modals
                $('.quiz-modal').fadeOut();
                break;
        }
    }
    
    function displayResults(resultData) {
        $('.elearning-quiz-form').slideUp();
        
        // Remove skip link when showing results
        $('.skip-link').remove();
        
        const passed = resultData.passed;
        const score = parseFloat(resultData.score);
        const correctAnswers = parseInt(resultData.correct_answers);
        const totalQuestions = parseInt(resultData.total_questions);
        const passingScore = parseFloat(resultData.passing_score);
        const totalPoints = parseInt(resultData.total_points || 0);
        const earnedPoints = parseFloat(resultData.earned_points || 0);
        
        let html = '<div class="quiz-results ' + (passed ? 'passed' : 'failed') + '">';
        
        // Result icon and message
        html += '<div class="result-icon">' + (passed ? '🎉' : '😞') + '</div>';
        html += '<div class="result-message">';
        
        if (score === 100) {
            // Perfect score
            html += '<h3>' + (elearningQuiz.strings.perfect_score || 'Congratulations, you had 100% success!') + '</h3>';
        } else if (passed) {
            // Passed but not perfect
            html += '<h3>' + (elearningQuiz.strings.congratulations_score || 'Congratulations, you had %s% success!').replace('%s', score.toFixed(0)) + '</h3>';
        } else {
            // Failed
            html += '<h3>' + (elearningQuiz.strings.sorry_failed || 'Sorry, you had %s% success!').replace('%s', score.toFixed(0)) + '</h3>';
        }
        
        html += '</div>';
        
        // Score display
        html += '<div class="score-display">' + score.toFixed(1) + '%</div>';
        
        // Points breakdown if available
        if (totalPoints > 0) {
            html += '<div class="points-breakdown">';
            html += '<p class="points-info">' + 
                (elearningQuiz.strings.points_earned || 'Points earned: %s out of %s')
                    .replace('%s', earnedPoints.toFixed(1))
                    .replace('%s', totalPoints) + 
                '</p>';
            html += '</div>';
        }
        
        // Show detailed answers only if not perfect score
        if (score < 100 && resultData.show_answers && resultData.detailed_results) {
            html += '<div class="detailed-answers">';
            
            // Your answers section
            html += '<div class="your-answers-section">';
            html += '<h4>' + (elearningQuiz.strings.your_answer_was || 'Your answer was') + '</h4>';
            html += displayUserAnswers(resultData.detailed_results);
            html += '</div>';
            
            // Correct answers section
            html += '<div class="correct-answers-section">';
            html += '<h4>' + (elearningQuiz.strings.correct_answer_is || 'The correct answer is') + '</h4>';
            html += displayCorrectAnswers(resultData.detailed_results);
            html += '</div>';
            
            html += '</div>';
        } else if (score === 100) {
            // For perfect score, just show user answers without corrections
            html += '<div class="detailed-answers perfect-score">';
            html += '<div class="your-answers-section">';
            html += displayUserAnswers(resultData.detailed_results, true);
            html += '</div>';
            html += '</div>';
        }
        
        // Action buttons
        if (!passed) {
            html += '<button type="button" class="retry-btn" onclick="location.reload()">' + 
                (elearningQuiz.strings.retry_quiz || 'Retry Quiz') + '</button>';
        }
        
        html += '</div>';
        
        // Add custom styles for points display
        html += '<style>';
        html += '.points-breakdown { background: rgba(255,255,255,0.7); padding: 15px; border-radius: 8px; margin: 20px 0; }';
        html += '.points-info { font-size: 18px; margin: 0; color: #333; }';
        html += '.question-partial-credit { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }';
        html += '.question-partial-credit .points-display { font-weight: bold; color: #856404; }';
        html += '</style>';
        
        $('.quiz-results').html(html).slideDown();
        
        // Scroll to results
        $('.quiz-results')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        
        // Focus on results for screen readers
        $('.quiz-results').attr('tabindex', '-1').focus();
        
        // Announce results to screen readers
        let announcement;
        if (score === 100) {
            announcement = elearningQuiz.strings.perfect_score || 'Congratulations, you had 100% success!';
        } else if (passed) {
            announcement = (elearningQuiz.strings.congratulations_score || 'Congratulations, you had %s% success!').replace('%s', score.toFixed(0));
        } else {
            announcement = (elearningQuiz.strings.sorry_failed || 'Sorry, you had %s% success!').replace('%s', score.toFixed(0));
        }
        
        announceToScreenReader(announcement);
    }
    
    function displayUserAnswers(detailedResults, isPerfectScore = false) {
        let html = '<div class="user-answers-display">';
        
        detailedResults.forEach((result, index) => {
            const isCorrect = result.correct;
            const hasPartialCredit = result.partial_credit || false;
            const questionType = result.question_type;
            
            let questionClass = 'question-result-display';
            if (isCorrect) {
                questionClass += ' correct';
            } else if (hasPartialCredit) {
                questionClass += ' partial-correct';
            } else {
                questionClass += ' incorrect';
            }
            
            html += '<div class="' + questionClass + '">';
            html += '<div class="question-number">' + (elearningQuiz.strings.question || 'Question') + ' ' + (index + 1) + '</div>';
            html += '<div class="question-text">' + result.question + '</div>';
            
            // Show points earned if available
            if (result.earned_points !== undefined && result.max_points !== undefined && !isPerfectScore) {
                html += '<div class="question-points">';
                html += '<span class="points-display">' + 
                    (elearningQuiz.strings.points || 'Points') + ': ' + 
                    result.earned_points + '/' + result.max_points + 
                    '</span>';
                html += '</div>';
            }
            
            html += '<div class="answer-display">';
            
            if (questionType === 'fill_blanks') {
                // Special handling for fill in the blanks
                html += displayFillBlanksAnswer(result, false, isPerfectScore);
            } else {
                // Other question types
                let answerClass = 'answer-value';
                if (isCorrect) {
                    answerClass += ' correct';
                } else if (hasPartialCredit) {
                    answerClass += ' partial';
                } else {
                    answerClass += ' incorrect';
                }
                
                html += '<div class="' + answerClass + '">';
                html += result.user_answer || (elearningQuiz.strings.no_answer || 'No answer provided');
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        return html;
    }
    
    function displayCorrectAnswers(detailedResults) {
        let html = '<div class="correct-answers-display">';
        
        detailedResults.forEach((result, index) => {
            html += '<div class="question-result-display">';
            html += '<div class="question-number">' + (elearningQuiz.strings.question || 'Question') + ' ' + (index + 1) + '</div>';
            html += '<div class="question-text">' + result.question + '</div>';
            
            html += '<div class="answer-display">';
            
            if (result.question_type === 'fill_blanks') {
                // Special handling for fill in the blanks
                html += displayFillBlanksAnswer(result, true);
            } else {
                // Other question types
                html += '<div class="answer-value correct-answer-highlight">';
                html += result.correct_answer;
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        return html;
    }
    
    function displayFillBlanksAnswer(result, showCorrect, isPerfectScore = false) {
        let html = '';
        
        // Find the matching question from our stored questions
        let $originalQuestion = null;
        for (let i = 0; i < currentQuiz.questions.length; i++) {
            if (currentQuiz.questions[i] && i === result.question_index) {
                $originalQuestion = $(currentQuiz.questions[i].element);
                break;
            }
        }
        
        if ($originalQuestion && $originalQuestion.find('.text-with-blanks').length) {
            // Get the text with blanks structure
            let textContent = $originalQuestion.find('.text-with-blanks').html();
            
            // Replace blank spans with answers
            const userAnswers = result.user_answer_raw || [];
            const correctAnswers = result.correct_answer_raw || [];
            
            let blankIndex = 0;
            textContent = textContent.replace(/<span[^>]*class="blank-space"[^>]*>.*?<\/span>/g, function(match) {
                let replacement;
                
                if (showCorrect) {
                    // Show correct answer with green highlight
                    const correctAnswer = correctAnswers[blankIndex] || '';
                    replacement = '<span class="blank-filled correct-answer-highlight">' + correctAnswer + '</span>';
                } else {
                    // Show user answer
                    const userAnswer = userAnswers[blankIndex] || '';
                    const isBlankCorrect = userAnswer === correctAnswers[blankIndex];
                    
                    if (isPerfectScore || isBlankCorrect) {
                        replacement = '<span class="blank-filled correct">' + userAnswer + '</span>';
                    } else {
                        replacement = '<span class="blank-filled incorrect">' + (userAnswer || '___') + '</span>';
                    }
                }
                
                blankIndex++;
                return replacement;
            });
            
            html = '<div class="fill-blanks-result">' + textContent + '</div>';
        } else {
            // Fallback display
            html = '<div class="answer-value ' + (showCorrect ? 'correct-answer-highlight' : (result.correct ? 'correct' : 'incorrect')) + '">';
            html += showCorrect ? result.correct_answer : result.user_answer;
            html += '</div>';
        }
        
        return html;
    }
    
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        
        if (minutes > 0) {
            return minutes + 'm ' + remainingSeconds + 's';
        }
        return remainingSeconds + 's';
    }
    
    function autoSaveProgress() {
        if (!currentQuiz.attemptId) return;
        
        saveCurrentAnswer();
        
        // Auto-save progress (silent background save)
        $.ajax({
            url: elearningQuiz.ajaxUrl,
            type: 'POST',
            data: {
                action: 'elearning_save_progress',
                attempt_id: currentQuiz.attemptId,
                current_question: currentQuiz.currentQuestion,
                answers: JSON.stringify(currentQuiz.answers),
                nonce: elearningQuiz.nonce
            },
            success: function(response) {
                // Silent save - no user feedback needed
            },
            error: function() {
                // Silent error - don't disrupt user
            }
        });
    }
    
    function showError(message) {
        const $errorDiv = $('<div class="quiz-error" role="alert" style="background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 15px; border-radius: 6px; margin: 20px 0;">' + message + '</div>');
        
        $('.elearning-quiz-container').prepend($errorDiv);
        
        // Auto-remove error after 5 seconds
        setTimeout(() => {
            $errorDiv.fadeOut(() => $errorDiv.remove());
        }, 5000);
        
        // Focus error for screen readers
        $errorDiv.attr('tabindex', '-1').focus();
    }
    
    function announceToScreenReader(message) {
        // Create temporary element for screen reader announcements
        const $announcement = $('<div>', {
            'class': 'sr-only',
            'aria-live': 'polite',
            'aria-atomic': 'true',
            'text': message
        });
        
        $('body').append($announcement);
        
        // Remove after announcement
        setTimeout(() => {
            $announcement.remove();
        }, 1000);
    }
    
    function handleVisibilityChange() {
        if (document.hidden) {
            // Page is hidden - pause timers (if needed)
        } else {
            // Page is visible - resume timers (if needed)
        }
    }
    
    function handlePageUnload(e) {
        if (currentQuiz.attemptId && Object.keys(currentQuiz.answers).length > 0) {
            autoSaveProgress();
            
            // Show warning if quiz is in progress
            const message = elearningQuiz.strings.leave_warning || 'You have unsaved progress. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    }
    
    // Initialize accessibility features
    function initializeAccessibility() {
        // Add skip links only if quiz form exists and is visible
        if ($('.elearning-quiz-form').length && $('.elearning-quiz-form').is(':visible')) {
            const $skipLink = $('<a href="#quiz-content" class="skip-link">' + 
                (elearningQuiz.strings.skip_to_quiz || 'Skip to quiz content') + '</a>');
            $('.elearning-quiz-container').prepend($skipLink);
        }
        
        // Add landmark roles
        $('.elearning-quiz-form').attr('role', 'main').attr('id', 'quiz-content');
        $('.quiz-progress').attr('role', 'progressbar')
                          .attr('aria-label', 'Quiz Progress')
                          .attr('aria-valuemin', 1)
                          .attr('aria-valuemax', currentQuiz.totalQuestions || 1);
        
        // Add live region for announcements
        if (!$('#quiz-announcements').length) {
            $('body').append('<div id="quiz-announcements" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
        }
    }
    
    // Initialize when DOM is ready - but don't add skip link yet
    // Skip link will be added only when quiz form is shown
    
    // Prevent context menu on quiz elements (prevent cheating)
    $('.elearning-quiz-container').on('contextmenu', function(e) {
        if ($(e.target).closest('.quiz-question').length > 0) {
            e.preventDefault();
            return false;
        }
    });
    
    // Prevent text selection on certain elements
    $('.word-item, .blank-space, .draggable-item').css({
        '-webkit-user-select': 'none',
        '-moz-user-select': 'none',
        '-ms-user-select': 'none',
        'user-select': 'none'
    });
});