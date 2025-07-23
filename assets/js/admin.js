/* E-Learning Quiz System - Admin JavaScript - FIXED VERSION 3.0 */
/* Fixed: Visual editor initialization for new sections and matching/fill-in-the-blanks saving issues */

jQuery(document).ready(function($) {
    console.log('E-Learning Quiz System admin loaded - Fixed Version 3.0');
    
    var sectionIndex = $('#lesson-sections-container .lesson-section').length;
    var questionIndex = $('#quiz-questions-container .quiz-question').length;
    
    // Add Section functionality - FIXED VERSION 3.0
    $('#add-section').on('click', function(e) {
        e.preventDefault();
        
        var container = $('#lesson-sections-container');
        var template = $('#section-template').html();
        
        var newSection = template.replace(/\{\{INDEX\}\}/g, sectionIndex);
        container.append(newSection);
        
        // FIXED: Initialize TinyMCE for the new section properly
        var editorId = 'section_content_' + sectionIndex;
        
        // Wait for DOM to be ready
        setTimeout(function() {
            initializeWpEditor(editorId, sectionIndex);
        }, 100);
        
        // Update section numbers
        updateSectionNumbers();
        
        sectionIndex++;
    });
    
    // Remove Section functionality
    $(document).on('click', '.remove-section', function(e) {
        e.preventDefault();
        
        if (confirm(elearningQuizAdmin.strings.confirm_delete)) {
            var sectionDiv = $(this).closest('.lesson-section');
            var editorId = sectionDiv.find('textarea[id^="section_content_"]').attr('id');
            
            // Remove TinyMCE instance if it exists
            if (editorId && typeof tinymce !== 'undefined') {
                var editor = tinymce.get(editorId);
                if (editor) {
                    editor.remove();
                }
            }
            
            sectionDiv.remove();
            updateSectionNumbers();
        }
    });
    
    // Add Question functionality
    $('#add-question').on('click', function(e) {
        console.log('Add question clicked!');
        e.preventDefault();
        
        var container = $('#quiz-questions-container');
        var template = $('#question-template').html();
        
        var newQuestion = template.replace(/\{\{INDEX\}\}/g, questionIndex);
        container.append(newQuestion);
        
        // Update question numbers
        updateQuestionNumbers();
        
        questionIndex++;
    });
    
    // Remove Question functionality
    $(document).on('click', '.remove-question', function(e) {
        e.preventDefault();
        
        if (confirm(elearningQuizAdmin.strings.confirm_delete)) {
            $(this).closest('.quiz-question').remove();
            updateQuestionNumbers();
        }
    });
    
    // === FIXED: Button container selection logic ===
    
    // Add Left Item functionality - FIXED VERSION 2.0
    $(document).on('click', '.add-left-item', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var leftColumn = button.closest('.left-column');
        var container = leftColumn.find('.match-items-container');
        var questionDiv = button.closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        // FIXED: Count existing items to determine next index
        var leftIndex = container.find('.match-item').length;
        
        var newLeftItem = '<div class="match-item">' +
            '<input type="text" name="quiz_questions[' + questionIdx + '][left_column][' + leftIndex + ']" placeholder="Left item" class="regular-text" />' +
            '<button type="button" class="remove-left-item button-link-delete">Remove</button>' +
            '</div>';
        
        container.append(newLeftItem);
        updateMatchingSelects(questionDiv);
    });
    
    // Add Right Item functionality - FIXED VERSION 2.0
    $(document).on('click', '.add-right-item', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var rightColumn = button.closest('.right-column');
        var container = rightColumn.find('.match-items-container');
        var questionDiv = button.closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        // FIXED: Count existing items to determine next index
        var rightIndex = container.find('.match-item').length;
        
        var newRightItem = '<div class="match-item">' +
            '<input type="text" name="quiz_questions[' + questionIdx + '][right_column][' + rightIndex + ']" placeholder="Right item" class="regular-text" />' +
            '<button type="button" class="remove-right-item button-link-delete">Remove</button>' +
            '</div>';
        
        container.append(newRightItem);
        updateMatchingSelects(questionDiv);
    });
    
    // Add Word functionality - FIXED VERSION 2.0
    $(document).on('click', '.add-word', function(e) {
        console.log('Add word clicked!');
        e.preventDefault();
        
        var button = $(this);
        var container = button.prev('.word-bank-container');
        var questionDiv = button.closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        // FIXED: Count existing items to determine next index
        var wordIndex = container.find('.word-row').length;
        
        var newWord = '<div class="word-row">' +
            '<input type="text" name="quiz_questions[' + questionIdx + '][word_bank][' + wordIndex + ']" placeholder="' + (elearningQuizAdmin.strings.word || 'Word') + '" class="regular-text" />' +
            '<button type="button" class="remove-word button-link-delete">' + (elearningQuizAdmin.strings.remove || 'Remove') + '</button>' +
            '</div>';
        
        container.append(newWord);
    });
    
    // Add Match functionality - FIXED VERSION 2.0
    $(document).on('click', '.add-match', function(e) {
        console.log('Add match clicked!');
        e.preventDefault();
        
        var button = $(this);
        var container = button.prev('.matches-container');
        var questionDiv = button.closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        var matchIndex = container.find('.match-row').length;
        
        var leftOptions = '';
        var rightOptions = '';
        
        // FIXED: Get left column items with their actual indices
        questionDiv.find('.left-column .match-item').each(function(index) {
            var input = $(this).find('input[type="text"]');
            var value = input.val() || 'Left Item ' + (index + 1);
            leftOptions += '<option value="' + index + '">' + value + '</option>';
        });
        
        // FIXED: Get right column items with their actual indices
        questionDiv.find('.right-column .match-item').each(function(index) {
            var input = $(this).find('input[type="text"]');
            var value = input.val() || 'Right Item ' + (index + 1);
            rightOptions += '<option value="' + index + '">' + value + '</option>';
        });
        
        var newMatch = '<div class="match-row">' +
            '<select name="quiz_questions[' + questionIdx + '][matches][' + matchIndex + '][left]" class="match-left-select">' +
            '<option value="">' + (elearningQuizAdmin.strings.select_left || 'Select left item') + '</option>' +
            leftOptions +
            '</select>' +
            '<span>' + (elearningQuizAdmin.strings.matches_with || 'matches with') + '</span>' +
            '<select name="quiz_questions[' + questionIdx + '][matches][' + matchIndex + '][right]" class="match-right-select">' +
            '<option value="">' + (elearningQuizAdmin.strings.select_right || 'Select right item') + '</option>' +
            rightOptions +
            '</select>' +
            '<button type="button" class="remove-match button-link-delete">' + (elearningQuizAdmin.strings.remove || 'Remove') + '</button>' +
            '</div>';
        
        container.append(newMatch);
    });
    
    // Add Option functionality - FIXED VERSION 2.0
    $(document).on('click', '.add-option', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var container = button.prev('.options-container');
        var questionDiv = button.closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        // FIXED: Count existing options to determine next index
        var optionIndex = container.find('.option-row').length;
        
        var newOption = '<div class="option-row">' +
            '<input type="text" name="quiz_questions[' + questionIdx + '][options][' + optionIndex + ']" placeholder="' + (elearningQuizAdmin.strings.option_text || 'Option text') + '" class="regular-text" />' +
            '<label><input type="checkbox" name="quiz_questions[' + questionIdx + '][correct_answers][]" value="' + optionIndex + '" /> ' + (elearningQuizAdmin.strings.correct || 'Correct') + '</label>' +
            '<button type="button" class="remove-option button-link-delete">' + (elearningQuizAdmin.strings.remove || 'Remove') + '</button>' +
            '</div>';
        
        container.append(newOption);
    });
    
    // FIXED: Remove handlers with index updates
    $(document).on('click', '.remove-left-item', function(e) {
        e.preventDefault();
        var questionDiv = $(this).closest('.quiz-question');
        $(this).closest('.match-item').remove();
        
        // Re-index remaining items
        reindexMatchingItems(questionDiv, 'left');
        updateMatchingSelects(questionDiv);
    });
    
    $(document).on('click', '.remove-right-item', function(e) {
        e.preventDefault();
        var questionDiv = $(this).closest('.quiz-question');
        $(this).closest('.match-item').remove();
        
        // Re-index remaining items
        reindexMatchingItems(questionDiv, 'right');
        updateMatchingSelects(questionDiv);
    });
    
    $(document).on('click', '.remove-word', function(e) {
        e.preventDefault();
        var container = $(this).closest('.word-bank-container');
        var questionDiv = $(this).closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        $(this).closest('.word-row').remove();
        
        // Re-index remaining words
        container.find('.word-row').each(function(index) {
            $(this).find('input[type="text"]').attr('name', 'quiz_questions[' + questionIdx + '][word_bank][' + index + ']');
        });
    });
    
    $(document).on('click', '.remove-match', function(e) {
        e.preventDefault();
        var container = $(this).closest('.matches-container');
        var questionDiv = $(this).closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        $(this).closest('.match-row').remove();
        
        // Re-index remaining matches
        container.find('.match-row').each(function(index) {
            $(this).find('.match-left-select').attr('name', 'quiz_questions[' + questionIdx + '][matches][' + index + '][left]');
            $(this).find('.match-right-select').attr('name', 'quiz_questions[' + questionIdx + '][matches][' + index + '][right]');
        });
    });
    
    $(document).on('click', '.remove-option', function(e) {
        e.preventDefault();
        var container = $(this).closest('.options-container');
        var questionDiv = $(this).closest('.quiz-question');
        var questionIdx = questionDiv.data('index');
        
        $(this).closest('.option-row').remove();
        
        // Re-index remaining options
        container.find('.option-row').each(function(index) {
            $(this).find('input[type="text"]').attr('name', 'quiz_questions[' + questionIdx + '][options][' + index + ']');
            $(this).find('input[type="checkbox"]').val(index);
        });
    });
    
    // FIXED: Function to re-index matching items
    function reindexMatchingItems(questionDiv, column) {
        var questionIdx = questionDiv.data('index');
        var columnClass = column === 'left' ? '.left-column' : '.right-column';
        
        questionDiv.find(columnClass + ' .match-item').each(function(index) {
            $(this).find('input[type="text"]').attr('name', 'quiz_questions[' + questionIdx + '][' + column + '_column][' + index + ']');
        });
    }
    
    // Update matching selects when items change
    $(document).on('input', '.left-column input[type="text"], .right-column input[type="text"]', function() {
        var questionDiv = $(this).closest('.quiz-question');
        updateMatchingSelects(questionDiv);
    });
    
    // Question type change handler
    $(document).on('change', '.question-type-select', function() {
        var questionContainer = $(this).closest('.quiz-question');
        var optionsContainer = questionContainer.find('.question-options');
        var questionType = $(this).val();
        var questionIdx = questionContainer.data('index');
        
        // Update options container data-type
        optionsContainer.attr('data-type', questionType);
        
        // Load appropriate options template based on question type
        loadQuestionTypeOptions(questionType, questionIdx, optionsContainer);
    });
    
    // FIXED VERSION 3.0: Initialize WordPress editor properly
    function initializeWpEditor(editorId, index) {
        if (typeof wp === 'undefined' || typeof wp.editor === 'undefined') {
            console.log('WordPress editor API not available, using AJAX fallback');
            
            // Fallback to AJAX method
            var textarea = $('#' + editorId);
            var data = {
                action: 'elearning_init_editor',
                editor_id: editorId,
                content: textarea.val(),
                nonce: elearningQuizAdmin.nonce
            };
            
            $.post(elearningQuizAdmin.ajaxUrl, data, function(response) {
                if (response.success) {
                    textarea.closest('td').html(response.data.editor_html);
                }
            });
            return;
        }
        
        // Remove any existing editor
        if (tinymce.get(editorId)) {
            wp.editor.remove(editorId);
        }
        
        // Initialize the editor with proper settings
        var editorSettings = {
            tinymce: {
                wpautop: true,
                plugins: 'lists,paste,wordpress,wplink,wptextpattern',
                toolbar1: 'bold,italic,underline,separator,alignleft,aligncenter,alignright,separator,link,unlink,undo,redo',
                toolbar2: 'formatselect,forecolor,separator,bullist,numlist,separator,outdent,indent,separator,removeformat',
                toolbar3: '',
                toolbar4: '',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            },
            quicktags: {
                buttons: 'strong,em,ul,ol,li,link,close'
            },
            mediaButtons: true
        };
        
        // Initialize the editor
        wp.editor.initialize(editorId, editorSettings);
    }
    
    // Function to update section numbers
    function updateSectionNumbers() {
        $('#lesson-sections-container .lesson-section').each(function(index) {
            $(this).find('.section-header h4').text((elearningQuizAdmin.strings.section || 'Section') + ' ' + (index + 1));
            $(this).attr('data-index', index);
        });
    }
    
    // Function to update question numbers
    function updateQuestionNumbers() {
        $('#quiz-questions-container .quiz-question').each(function(index) {
            $(this).find('.question-header h4').text((elearningQuizAdmin.strings.question || 'Question') + ' ' + (index + 1));
            $(this).attr('data-index', index);
        });
    }
    
    // FIXED: Function to update matching selects
    function updateMatchingSelects(questionDiv) {
        var questionIdx = questionDiv.data('index');
        
        // Update all match selects in this question
        questionDiv.find('.matches-container .match-row').each(function(matchIndex) {
            var leftSelect = $(this).find('.match-left-select');
            var rightSelect = $(this).find('.match-right-select');
            
            var leftCurrentValue = leftSelect.val();
            var rightCurrentValue = rightSelect.val();
            
            // Update left select options
            var leftOptions = '<option value="">' + (elearningQuizAdmin.strings.select_left || 'Select left item') + '</option>';
            questionDiv.find('.left-column .match-item').each(function(index) {
                var input = $(this).find('input[type="text"]');
                var value = input.val() || 'Left Item ' + (index + 1);
                var selected = leftCurrentValue == index ? ' selected' : '';
                leftOptions += '<option value="' + index + '"' + selected + '>' + value + '</option>';
            });
            leftSelect.html(leftOptions);
            
            // Update right select options
            var rightOptions = '<option value="">' + (elearningQuizAdmin.strings.select_right || 'Select right item') + '</option>';
            questionDiv.find('.right-column .match-item').each(function(index) {
                var input = $(this).find('input[type="text"]');
                var value = input.val() || 'Right Item ' + (index + 1);
                var selected = rightCurrentValue == index ? ' selected' : '';
                rightOptions += '<option value="' + index + '"' + selected + '>' + value + '</option>';
            });
            rightSelect.html(rightOptions);
        });
    }
    
    // Function to load question type options
    function loadQuestionTypeOptions(questionType, questionIdx, container) {
        var html = '';
        var strings = elearningQuizAdmin.strings || {};
        
        switch (questionType) {
            case 'multiple_choice':
                html = '<h5>' + (strings.options || 'Options') + '</h5>' +
                    '<div class="options-container">' +
                    '<div class="option-row">' +
                    '<input type="text" name="quiz_questions[' + questionIdx + '][options][0]" placeholder="' + (strings.option_text || 'Option text') + '" class="regular-text" />' +
                    '<label><input type="checkbox" name="quiz_questions[' + questionIdx + '][correct_answers][]" value="0" /> ' + (strings.correct || 'Correct') + '</label>' +
                    '<button type="button" class="remove-option button-link-delete">' + (strings.remove || 'Remove') + '</button>' +
                    '</div>' +
                    '</div>' +
                    '<button type="button" class="add-option button">' + (strings.add_option || 'Add Option') + '</button>';
                break;
                
            case 'fill_blanks':
                html = '<h5>' + (strings.text_with_blanks || 'Text with Blanks') + '</h5>' +
                    '<p class="description">' + (strings.blank_instruction || 'Use {{blank}} to mark where blanks should appear.') + '</p>' +
                    '<textarea name="quiz_questions[' + questionIdx + '][text_with_blanks]" rows="4" class="large-text"></textarea>' +
                    '<h5>' + (strings.word_bank || 'Word Bank') + '</h5>' +
                    '<div class="word-bank-container">' +
                    '<div class="word-row">' +
                    '<input type="text" name="quiz_questions[' + questionIdx + '][word_bank][0]" placeholder="' + (strings.word || 'Word') + '" class="regular-text" />' +
                    '<button type="button" class="remove-word button-link-delete">' + (strings.remove || 'Remove') + '</button>' +
                    '</div>' +
                    '</div>' +
                    '<button type="button" class="add-word button">' + (strings.add_word || 'Add Word') + '</button>';
                break;
                
            case 'true_false':
                html = '<h5>' + (strings.correct_answer || 'Correct Answer') + '</h5>' +
                    '<label><input type="radio" name="quiz_questions[' + questionIdx + '][correct_answer]" value="true" checked /> ' + (strings.true_option || 'True') + '</label><br>' +
                    '<label><input type="radio" name="quiz_questions[' + questionIdx + '][correct_answer]" value="false" /> ' + (strings.false_option || 'False') + '</label>';
                break;
                
            case 'matching':
                html = '<div class="matching-columns">' +
                    '<div class="left-column">' +
                    '<h5>' + (strings.left_column || 'Left Column') + '</h5>' +
                    '<div class="match-items-container">' +
                    '<div class="match-item">' +
                    '<input type="text" name="quiz_questions[' + questionIdx + '][left_column][0]" placeholder="' + (strings.left_item || 'Left item') + '" class="regular-text" />' +
                    '<button type="button" class="remove-left-item button-link-delete">' + (strings.remove || 'Remove') + '</button>' +
                    '</div>' +
                    '</div>' +
                    '<button type="button" class="add-left-item button">' + (strings.add_left_item || 'Add Left Item') + '</button>' +
                    '</div>' +
                    '<div class="right-column">' +
                    '<h5>' + (strings.right_column || 'Right Column') + '</h5>' +
                    '<div class="match-items-container">' +
                    '<div class="match-item">' +
                    '<input type="text" name="quiz_questions[' + questionIdx + '][right_column][0]" placeholder="' + (strings.right_item || 'Right item') + '" class="regular-text" />' +
                    '<button type="button" class="remove-right-item button-link-delete">' + (strings.remove || 'Remove') + '</button>' +
                    '</div>' +
                    '</div>' +
                    '<button type="button" class="add-right-item button">' + (strings.add_right_item || 'Add Right Item') + '</button>' +
                    '</div>' +
                    '</div>' +
                    '<h5>' + (strings.correct_matches || 'Correct Matches') + '</h5>' +
                    '<div class="matches-container"></div>' +
                    '<button type="button" class="add-match button">' + (strings.add_match || 'Add Match') + '</button>';
                break;
        }
        
        container.html(html);
    }
    
    // Form submission handler to sync TinyMCE content
    $('form').on('submit', function() {
        if (typeof tinymce !== 'undefined') {
            tinymce.triggerSave();
        }
    });
    
    // Initialize existing questions on page load
    $('.quiz-question').each(function() {
        var questionDiv = $(this);
        var questionType = questionDiv.find('.question-type-select').val();
        
        if (questionType === 'matching') {
            updateMatchingSelects(questionDiv);
        }
    });
    
    console.log('Admin JavaScript loaded and ready - Version 3.0');
});