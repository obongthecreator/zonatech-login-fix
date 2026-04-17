/**
 * ZonaTech NG - Quiz System JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        ZonaTechQuiz.init();
    });
    
    const ZonaTechQuiz = {
        currentQuiz: null,
        answers: {},
        timer: null,
        timeRemaining: 0,
        
        init: function() {
            this.initQuestionViewer();
            this.initQuizStarter();
        },
        
        // Question Viewer
        initQuestionViewer: function() {
            const self = this;
            
            // Subject filter change
            $(document).on('change', '#exam-type-select', function() {
                self.loadSubjects($(this).val());
            });
            
            $(document).on('change', '#subject-select', function() {
                const examType = $('#exam-type-select').val();
                const subject = $(this).val();
                if (examType && subject) {
                    self.loadYears(examType, subject);
                }
            });
            
            // Load questions button
            $(document).on('click', '#load-questions-btn', function() {
                const examType = $('#exam-type-select').val();
                const subject = $('#subject-select').val();
                const year = $('#year-select').val();
                
                if (!examType || !subject || !year) {
                    ZonaTechNotify.show('Please select exam type, subject and year.', 'warning');
                    return;
                }
                
                self.loadQuestions(examType, subject, year);
            });
        },
        
        // Load subjects for exam type
        loadSubjects: function(examType) {
            if (!examType) {
                $('#subject-select').html('<option value="">Select Subject</option>');
                return;
            }
            
            $('#subject-select').html('<option value="">Loading...</option>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_subjects',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType
                },
                success: function(response) {
                    if (response.success) {
                        let options = '<option value="">Select Subject</option>';
                        response.data.subjects.forEach(function(subject) {
                            options += `<option value="${subject}">${subject}</option>`;
                        });
                        $('#subject-select').html(options);
                    }
                }
            });
        },
        
        // Load years for subject
        loadYears: function(examType, subject) {
            $('#year-select').html('<option value="">Loading...</option>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_years',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType,
                    subject: subject
                },
                success: function(response) {
                    if (response.success) {
                        let options = '<option value="">Select Year</option>';
                        response.data.years.forEach(function(year) {
                            options += `<option value="${year}">${year}</option>`;
                        });
                        $('#year-select').html(options);
                    }
                }
            });
        },
        
        // Load questions
        loadQuestions: function(examType, subject, year) {
            const self = this;
            const container = $('#questions-container');
            
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_questions',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType,
                    subject: subject,
                    year: year
                },
                success: function(response) {
                    if (response.success) {
                        self.renderQuestions(response.data);
                    } else {
                        if (response.data.require_payment) {
                            self.showPaymentPrompt(response.data);
                        } else {
                            container.html(`<div class="alert alert-error">${response.data.message}</div>`);
                        }
                    }
                },
                error: function() {
                    container.html('<div class="alert alert-error">Failed to load questions. Please try again.</div>');
                }
            });
        },
        
        // Render questions
        renderQuestions: function(data) {
            const container = $('#questions-container');
            let html = `
                <div class="questions-header glass-card">
                    <h3>${data.exam_type} ${data.subject} - ${data.year}</h3>
                    <p>Total Questions: ${data.total}</p>
                    <button class="btn btn-primary" id="start-quiz-btn" 
                            data-exam="${data.exam_type.toLowerCase()}" 
                            data-subject="${data.subject}" 
                            data-year="${data.year}">
                        <i class="fas fa-play"></i> Take Quiz
                    </button>
                </div>
                <div class="questions-list">
            `;
            
            data.questions.forEach(function(q, index) {
                const correctAnswer = q.correct_answer ? q.correct_answer.toUpperCase() : '';
                const questionId = 'browse-q-' + index;
                const options = [
                    { letter: 'A', text: q.option_a },
                    { letter: 'B', text: q.option_b },
                    { letter: 'C', text: q.option_c },
                    { letter: 'D', text: q.option_d }
                ];
                
                html += `
                    <div class="question-card animate-card" id="${questionId}" data-correct="${correctAnswer}" data-answered="false">
                        <span class="question-number">${index + 1}</span>
                        <p class="question-text">${q.question_text}</p>
                        <div class="question-options">
                `;
                
                options.forEach(function(opt) {
                    // Options are clickable - correct answer not revealed until selected
                    html += `
                        <div class="option-item browse-option" data-question="${questionId}" data-letter="${opt.letter}" style="cursor: pointer;">
                            <span class="option-letter">${opt.letter}</span>
                            <span class="option-text">${opt.text}</span>
                            <span class="option-icon"></span>
                        </div>
                    `;
                });
                
                html += '</div>';
                
                // Explanation hidden by default - revealed only when an option is selected
                if (q.explanation && q.explanation.trim()) {
                    html += `
                        <div class="explanation" style="display: none; padding: 1rem; background: rgba(139, 92, 246, 0.1); border-left: 3px solid var(--zona-purple); border-radius: 0 0.5rem 0.5rem 0; margin-top: 1rem;">
                            <strong style="color: var(--zona-purple);"><i class="fas fa-lightbulb"></i> Explanation:</strong>
                            <p style="margin: 0.5rem 0 0; color: var(--zona-text-secondary);">${q.explanation}</p>
                        </div>
                    `;
                }
                
                html += '</div>';
            });
            
            html += '</div>';
            container.html(html);
            
            // Attach click handlers for browse options after rendering
            self.attachBrowseOptionHandlers();
        },
        
        // Attach click handlers for browsing questions (reveal answer on click)
        attachBrowseOptionHandlers: function() {
            $(document).off('click', '.browse-option').on('click', '.browse-option', function() {
                var $option = $(this);
                var questionId = $option.data('question');
                var selectedLetter = $option.data('letter');
                var $questionCard = $('#' + questionId);
                var correctAnswer = $questionCard.data('correct');
                var isAnswered = $questionCard.data('answered') === true || $questionCard.data('answered') === 'true';
                
                // If already answered, do nothing
                if (isAnswered) {
                    return;
                }
                
                // Mark question as answered
                $questionCard.data('answered', 'true');
                
                var isCorrect = selectedLetter === correctAnswer;
                
                // Update all options in this question
                $questionCard.find('.browse-option').each(function() {
                    var $opt = $(this);
                    var optLetter = $opt.data('letter');
                    var $letterSpan = $opt.find('.option-letter');
                    var $textSpan = $opt.find('.option-text');
                    var $iconSpan = $opt.find('.option-icon');
                    
                    // Remove cursor pointer
                    $opt.css('cursor', 'default');
                    
                    if (optLetter === correctAnswer) {
                        // Highlight correct answer in green
                        $opt.addClass('correct');
                        $iconSpan.html('<i class="fas fa-check-circle" style="color: var(--zona-success); margin-left: auto;"></i>');
                    } else if (optLetter === selectedLetter && !isCorrect) {
                        // Highlight wrong selected answer in red
                        $opt.addClass('wrong');
                        $iconSpan.html('<i class="fas fa-times-circle" style="color: var(--zona-error); margin-left: auto;"></i>');
                    }
                });
                
                // Show explanation if available
                $questionCard.find('.explanation').fadeIn(300);
                
                // Show notification
                if (isCorrect) {
                    if (typeof ZonaTechNotify !== 'undefined') {
                        ZonaTechNotify.show('Correct! Well done!', 'success', 2000);
                    }
                } else {
                    if (typeof ZonaTechNotify !== 'undefined') {
                        ZonaTechNotify.show('Incorrect. The correct answer is ' + correctAnswer, 'error', 3000);
                    }
                }
            });
        },
        
        // Show payment prompt
        showPaymentPrompt: function(data) {
            const container = $('#questions-container');
            container.html(`
                <div class="glass-card text-center" style="padding: 3rem;">
                    <i class="fas fa-lock" style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;"></i>
                    <h3>Access Required</h3>
                    <p>${data.message}</p>
                    <p class="text-purple" style="font-size: 1.5rem; font-weight: 700; margin: 1rem 0;">
                        ₦${zonatech_ajax.subject_price.toLocaleString()}
                    </p>
                    <button class="btn btn-primary btn-lg" id="buy-subject-btn"
                            data-exam="${data.exam_type}"
                            data-subject="${data.subject}">
                        <i class="fas fa-shopping-cart"></i> Purchase Access
                    </button>
                </div>
            `);
        },
        
        // Initialize quiz starter
        initQuizStarter: function() {
            const self = this;
            
            // Show quiz settings modal when clicking start quiz button
            $(document).on('click', '#start-quiz-btn', function() {
                const examType = $(this).data('exam');
                const subject = $(this).data('subject');
                
                self.showQuizSettingsModal(examType, subject);
            });
            
            // Start quiz with selected settings
            $(document).on('click', '#confirm-start-quiz-btn', function() {
                const examType = $('#quiz-settings-page').data('exam');
                const subject = $('#quiz-settings-page').data('subject');
                const questionCount = parseInt($('#quiz-question-count').val()) || 50;
                const timeMinutes = parseInt($('#quiz-time-minutes').val()) || 0;
                
                self.startQuiz(examType, subject, questionCount, timeMinutes);
            });
            
            // Go back from quiz settings page
            $(document).on('click', '#cancel-quiz-settings-btn', function() {
                self.hideQuizSettingsModal();
            });
            
            // Answer selection
            $(document).on('click', '.quiz-mode .option-item', function() {
                const questionId = $(this).closest('.question-card').data('question-id');
                const answer = $(this).data('answer');
                
                $(this).closest('.question-options').find('.option-item').removeClass('selected');
                $(this).addClass('selected');
                
                self.answers[questionId] = answer;
                self.updateProgress();
            });
            
            // Submit quiz
            $(document).on('click', '#submit-quiz-btn', function() {
                if (Object.keys(self.answers).length === 0) {
                    ZonaTechNotify.show('Please answer at least one question.', 'warning');
                    return;
                }
                
                if (!confirm('Are you sure you want to submit the quiz?')) return;
                
                self.submitQuiz();
            });
            
            // View corrections
            $(document).on('click', '#view-corrections-btn', function() {
                const resultId = $(this).data('result-id');
                self.viewCorrections(resultId);
            });
        },
        
        // Show quiz settings page (full page layout)
        showQuizSettingsModal: function(examType, subject) {
            // Remove existing settings page if any
            $('#quiz-settings-page').remove();
            
            // Store the original page content
            const originalContent = $('#questions-container').html();
            
            const pageHtml = `
                <div id="quiz-settings-page" data-exam="${examType}" data-subject="${subject}" data-original-content="">
                    <div class="glass-card" style="max-width: 100%; margin: 0 auto; padding: 2rem;">
                        <!-- Header -->
                        <div style="text-align: center; margin-bottom: 2.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(139, 92, 246, 0.3);">
                            <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #8b5cf6, #6366f1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; box-shadow: 0 10px 40px rgba(139, 92, 246, 0.4);">
                                <i class="fas fa-cog" style="font-size: 2.5rem; color: #fff;"></i>
                            </div>
                            <h1 style="color: #fff; margin: 0 0 0.75rem; font-size: 2rem; font-weight: 700;">Quiz Settings</h1>
                            <p style="color: #a1a1aa; margin: 0; font-size: 1.2rem;"><i class="fas fa-graduation-cap" style="margin-right: 0.5rem;"></i> ${examType.toUpperCase()} - ${subject}</p>
                        </div>
                        
                        <!-- Settings Form -->
                        <div style="max-width: 600px; margin: 0 auto;">
                            <!-- Number of Questions -->
                            <div style="margin-bottom: 2rem;">
                                <label style="color: #fff; display: block; margin-bottom: 1rem; font-size: 1.2rem; font-weight: 600;">
                                    <i class="fas fa-list-ol" style="margin-right: 0.75rem; color: #8b5cf6;"></i> Number of Questions
                                </label>
                                <select id="quiz-question-count" class="form-control" style="width: 100%; padding: 1.25rem 1.5rem; border-radius: 12px; background: #1a1a2e; border: 2px solid rgba(139, 92, 246, 0.3); color: #fff; font-size: 1.2rem; cursor: pointer;">
                                    <option value="10">10 questions</option>
                                    <option value="20">20 questions</option>
                                    <option value="30">30 questions</option>
                                    <option value="40">40 questions</option>
                                    <option value="50" selected>50 questions</option>
                                    <option value="75">75 questions</option>
                                    <option value="100">100 questions</option>
                                </select>
                            </div>
                            
                            <!-- Time Limit -->
                            <div style="margin-bottom: 2.5rem;">
                                <label style="color: #fff; display: block; margin-bottom: 1rem; font-size: 1.2rem; font-weight: 600;">
                                    <i class="fas fa-clock" style="margin-right: 0.75rem; color: #8b5cf6;"></i> Time Limit
                                </label>
                                <select id="quiz-time-minutes" class="form-control" style="width: 100%; padding: 1.25rem 1.5rem; border-radius: 12px; background: #1a1a2e; border: 2px solid rgba(139, 92, 246, 0.3); color: #fff; font-size: 1.2rem; cursor: pointer;">
                                    <option value="0" selected>Auto (based on questions)</option>
                                    <option value="5">5 minutes</option>
                                    <option value="10">10 minutes</option>
                                    <option value="15">15 minutes</option>
                                    <option value="20">20 minutes</option>
                                    <option value="30">30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes (1 hour)</option>
                                    <option value="90">90 minutes (1.5 hours)</option>
                                    <option value="120">120 minutes (2 hours)</option>
                                </select>
                                <p style="color: #a1a1aa; margin-top: 1rem; font-size: 1rem;">
                                    <i class="fas fa-info-circle" style="margin-right: 0.5rem; color: #8b5cf6;"></i> 
                                    Auto time calculates ~12 seconds per question
                                </p>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap;">
                                <button id="cancel-quiz-settings-btn" class="btn" style="flex: 1; min-width: 150px; padding: 1.25rem 2rem; background: rgba(255,255,255,0.1); border: 2px solid rgba(255,255,255,0.2); border-radius: 12px; color: #fff; font-size: 1.1rem; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i> Go Back
                                </button>
                                <button id="confirm-start-quiz-btn" class="btn btn-primary" style="flex: 2; min-width: 200px; padding: 1.25rem 2rem; background: linear-gradient(135deg, #8b5cf6, #6366f1); border: none; border-radius: 12px; color: #fff; font-size: 1.2rem; font-weight: 700; cursor: pointer; box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);">
                                    <i class="fas fa-play" style="margin-right: 0.5rem;"></i> Start Quiz
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Replace the questions container content with the settings page
            $('#questions-container').html(pageHtml);
            
            // Store original content for restoration
            $('#quiz-settings-page').data('original-content', originalContent);
            
            // Scroll to top so user sees the full page
            window.scrollTo(0, 0);
        },
        
        // Hide quiz settings page and restore original content
        hideQuizSettingsModal: function() {
            const settingsPage = $('#quiz-settings-page');
            if (settingsPage.length) {
                const originalContent = settingsPage.data('original-content');
                if (originalContent) {
                    $('#questions-container').html(originalContent);
                }
                window.scrollTo(0, 0);
            }
        },
        
        // Start quiz with customizable settings
        startQuiz: function(examType, subject, questionCount, timeMinutes) {
            const self = this;
            const container = $('#questions-container');
            
            // Use defaults if not provided
            questionCount = questionCount || 50;
            timeMinutes = timeMinutes || 0; // 0 means auto-calculate
            
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_start_quiz',
                    nonce: zonatech_ajax.nonce,
                    exam_type: examType,
                    subject: subject,
                    question_count: questionCount,
                    time_minutes: timeMinutes
                },
                success: function(response) {
                    if (response.success) {
                        self.currentQuiz = response.data;
                        self.answers = {};
                        self.timeRemaining = response.data.time_limit;
                        self.renderQuiz(response.data);
                        self.startTimer();
                    } else {
                        container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${response.data.message}</div>`);
                        ZonaTechNotify.show(response.data.message, 'error', 5000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Quiz start error:', error);
                    container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to start quiz. Please check your connection and try again.</div>`);
                    ZonaTechNotify.show('Failed to start quiz. Please try again.', 'error', 5000);
                }
            });
        },
        
        // Render quiz - no year display
        renderQuiz: function(data) {
            const container = $('#questions-container');
            let html = `
                <div class="quiz-mode">
                    <div class="quiz-header glass-card">
                        <div class="quiz-info">
                            <h3>${data.exam_type} ${data.subject} Quiz</h3>
                            <p>Questions: ${data.total}</p>
                        </div>
                        <div class="quiz-timer">
                            <i class="fas fa-clock"></i>
                            <span id="quiz-timer">--:--</span>
                        </div>
                    </div>
                    <div class="quiz-progress mb-2">
                        <div class="progress-bar">
                            <div class="progress-fill" id="quiz-progress" style="width: 0%;"></div>
                        </div>
                        <small><span id="answered-count">0</span>/${data.total} answered</small>
                    </div>
                    <div class="questions-list">
            `;
            
            data.questions.forEach(function(q, index) {
                html += `
                    <div class="question-card animate-card" data-question-id="${q.id}">
                        <span class="question-number">${index + 1}</span>
                        <p class="question-text">${q.question_text}</p>
                        <div class="question-options">
                            <div class="option-item" data-answer="A">
                                <span class="option-letter">A</span>
                                <span class="option-text">${q.option_a}</span>
                            </div>
                            <div class="option-item" data-answer="B">
                                <span class="option-letter">B</span>
                                <span class="option-text">${q.option_b}</span>
                            </div>
                            <div class="option-item" data-answer="C">
                                <span class="option-letter">C</span>
                                <span class="option-text">${q.option_c}</span>
                            </div>
                            <div class="option-item" data-answer="D">
                                <span class="option-letter">D</span>
                                <span class="option-text">${q.option_d}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                    <div class="quiz-actions text-center mt-3">
                        <button class="btn btn-primary btn-lg" id="submit-quiz-btn">
                            <i class="fas fa-check"></i> Submit Quiz
                        </button>
                    </div>
                </div>
            `;
            
            container.html(html);
        },
        
        // Start timer
        startTimer: function() {
            const self = this;
            
            this.timer = setInterval(function() {
                self.timeRemaining--;
                
                const minutes = Math.floor(self.timeRemaining / 60);
                const seconds = self.timeRemaining % 60;
                
                $('#quiz-timer').text(
                    String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0')
                );
                
                if (self.timeRemaining <= 60) {
                    $('#quiz-timer').css('color', 'var(--zona-error)');
                }
                
                if (self.timeRemaining <= 0) {
                    clearInterval(self.timer);
                    ZonaTechNotify.show('Time is up! Submitting your quiz...', 'warning');
                    self.submitQuiz();
                }
            }, 1000);
        },
        
        // Update progress
        updateProgress: function() {
            const total = this.currentQuiz.total;
            const answered = Object.keys(this.answers).length;
            const percent = (answered / total) * 100;
            
            $('#quiz-progress').css('width', percent + '%');
            $('#answered-count').text(answered);
        },
        
        // Submit quiz - no year required
        submitQuiz: function() {
            const self = this;
            clearInterval(this.timer);
            
            const container = $('#questions-container');
            container.html('<div class="loading"><div class="spinner"></div><p class="loading-text">Submitting quiz...</p></div>');
            
            const timeTaken = this.currentQuiz.time_limit - this.timeRemaining;
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_submit_quiz',
                    nonce: zonatech_ajax.nonce,
                    exam_type: this.currentQuiz.exam_type.toLowerCase(),
                    subject: this.currentQuiz.subject,
                    answers: JSON.stringify(this.answers),
                    time_taken: timeTaken
                },
                success: function(response) {
                    if (response.success) {
                        self.showResults(response.data);
                        ZonaTechNotify.show('Quiz submitted successfully! Your results are ready.', 'success', 6000);
                    } else {
                        container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${response.data.message}</div>`);
                        ZonaTechNotify.show(response.data.message, 'error', 5000);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Quiz submit error:', error);
                    container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to submit quiz. Please check your connection and try again.</div>`);
                    ZonaTechNotify.show('Failed to submit quiz. Please try again.', 'error', 5000);
                }
            });
        },
        
        // Show results
        showResults: function(data) {
            const container = $('#questions-container');
            const gradeColor = data.score >= 50 ? 'var(--zona-success)' : 'var(--zona-error)';
            
            container.html(`
                <div class="result-card">
                    <div class="result-score" style="background: ${data.score >= 50 ? 'var(--zona-success)' : 'var(--zona-error)'};">
                        <span class="score-value">${data.score}%</span>
                        <span class="score-label">Score</span>
                    </div>
                    <h2 class="result-grade">Grade: ${data.grade}</h2>
                    <p>${data.message}</p>
                    <div class="result-stats">
                        <div class="result-stat correct">
                            <span class="stat-value">${data.correct}</span>
                            <span class="stat-label">Correct</span>
                        </div>
                        <div class="result-stat wrong">
                            <span class="stat-value">${data.wrong}</span>
                            <span class="stat-label">Wrong</span>
                        </div>
                        <div class="result-stat">
                            <span class="stat-value">${data.total}</span>
                            <span class="stat-label">Total</span>
                        </div>
                    </div>
                    <div class="result-actions mt-3">
                        <button class="btn btn-primary" id="view-corrections-btn" data-result-id="${data.result_id}">
                            <i class="fas fa-eye"></i> View Corrections
                        </button>
                        <a href="${window.location.href}" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                    </div>
                </div>
            `);
        },
        
        // View corrections
        viewCorrections: function(resultId) {
            const container = $('#questions-container');
            container.html('<div class="loading"><div class="spinner"></div></div>');
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_get_corrections',
                    nonce: zonatech_ajax.nonce,
                    result_id: resultId
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.corrections.length === 0) {
                            container.html(`
                                <div class="glass-card text-center" style="padding: 3rem;">
                                    <i class="fas fa-trophy" style="font-size: 3rem; color: var(--zona-success); margin-bottom: 1rem;"></i>
                                    <h3>Perfect Score!</h3>
                                    <p>${response.data.message}</p>
                                    <a href="${window.location.href}" class="btn btn-primary mt-2">
                                        <i class="fas fa-redo"></i> Take Another Quiz
                                    </a>
                                </div>
                            `);
                            return;
                        }
                        
                        let html = `
                            <div class="corrections-header glass-card mb-2">
                                <h3><i class="fas fa-check-double"></i> Corrections</h3>
                                <p>Review the questions you got wrong</p>
                            </div>
                            <div class="corrections-list">
                        `;
                        
                        response.data.corrections.forEach(function(c, index) {
                            html += `
                                <div class="question-card">
                                    <span class="question-number">${index + 1}</span>
                                    <p class="question-text">${c.question}</p>
                                    <div class="question-options">
                            `;
                            
                            ['A', 'B', 'C', 'D'].forEach(function(letter) {
                                const isCorrect = letter === c.correct_answer;
                                const isUserAnswer = letter === c.your_answer;
                                let className = '';
                                
                                if (isCorrect) className = 'correct';
                                else if (isUserAnswer) className = 'wrong';
                                
                                html += `
                                    <div class="option-item ${className}">
                                        <span class="option-letter">${letter}</span>
                                        <span class="option-text">${c.options[letter]}</span>
                                        ${isCorrect ? '<i class="fas fa-check" style="color: var(--zona-success); margin-left: auto;"></i>' : ''}
                                        ${isUserAnswer && !isCorrect ? '<i class="fas fa-times" style="color: var(--zona-error); margin-left: auto;"></i>' : ''}
                                    </div>
                                `;
                            });
                            
                            if (c.explanation) {
                                html += `
                                    <div class="explanation mt-2" style="padding: 1rem; background: rgba(139, 92, 246, 0.1); border-radius: 0.5rem;">
                                        <strong><i class="fas fa-lightbulb"></i> Explanation:</strong>
                                        <p style="margin: 0.5rem 0 0;">${c.explanation}</p>
                                    </div>
                                `;
                            }
                            
                            html += `
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += `
                            </div>
                            <div class="text-center mt-3">
                                <a href="${window.location.href}" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Try Again
                                </a>
                            </div>
                        `;
                        
                        container.html(html);
                    } else {
                        container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> ${response.data.message}</div>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Corrections error:', error);
                    container.html(`<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to load corrections. Please try again.</div>`);
                }
            });
        }
    };
    
    window.ZonaTechQuiz = ZonaTechQuiz;
    
})(jQuery);