<?php
/**
 * Past Questions Template
 * Rebuilt from scratch to fix navigation and display issues
 */

if (!defined('ABSPATH')) exit;

// Ensure exam_types is an array (defensive coding)
if (!isset($exam_types) || !is_array($exam_types)) {
    $exam_types = ZonaTech_Past_Questions::get_exam_types();
}

// Get current user info
$current_user_id = get_current_user_id();
$is_logged_in = is_user_logged_in();

// Get free questions limit from options or constant
$free_questions_limit = get_option('zonatech_free_questions_limit', 
    defined('ZONATECH_FREE_QUESTIONS_LIMIT') ? ZONATECH_FREE_QUESTIONS_LIMIT : 10);

// Get prices from options or constants
$monthly_price = get_option('zonatech_monthly_price', 
    defined('ZONATECH_MONTHLY_PRICE') ? ZONATECH_MONTHLY_PRICE : 5000);
$sixmonth_price = get_option('zonatech_sixmonth_price', 
    defined('ZONATECH_6MONTH_PRICE') ? ZONATECH_6MONTH_PRICE : 25000);
?>

<div class="zonatech-container">
    <div class="zonatech-wrapper">
        <!-- Header -->
        <div class="zonatech-header glass-effect">
            <div class="zonatech-logo">
                <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                <span>ZonaTech NG</span>
            </div>
            <nav class="zonatech-nav">
                <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
                <a href="<?php echo site_url('/zonatech-past-questions/'); ?>" class="active"><i class="fas fa-book-open"></i> Past Questions</a>
                <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
                <a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                    <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </nav>
            
            <!-- Hamburger Menu -->
            <div class="hamburger-menu" id="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        
        <!-- Mobile Navigation Overlay -->
        <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
        
        <!-- Mobile Navigation -->
        <nav class="mobile-nav" id="mobile-nav">
            <div class="mobile-nav-header">
                <div class="zonatech-logo">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="zonatech-logo-img">
                    <span>ZonaTech NG</span>
                </div>
                <button class="mobile-nav-close" id="mobile-nav-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Home</a>
            <a href="<?php echo site_url('/zonatech-past-questions/'); ?>" class="active"><i class="fas fa-book-open"></i> Past Questions</a>
            <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a>
            <a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo site_url('/zonatech-dashboard/'); ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <?php else: ?>
                <a href="<?php echo site_url('/zonatech-login/'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="<?php echo site_url('/zonatech-register/'); ?>"><i class="fas fa-user-plus"></i> Create Account</a>
            <?php endif; ?>
        </nav>
        
        <!-- Exam Type Cards -->
        <div class="section" style="margin-top: 1rem;">
            <div class="cards-grid mb-3">
                <?php if (!empty($exam_types)): ?>
                    <?php foreach ($exam_types as $type => $exam): ?>
                        <div class="service-card animate-card">
                            <div class="service-card-icon" style="background: linear-gradient(135deg, <?php echo esc_attr($exam['color']); ?>20 0%, <?php echo esc_attr($exam['color']); ?>10 100%); color: <?php echo esc_attr($exam['color']); ?>;">
                                <i class="<?php echo esc_attr($exam['icon']); ?>"></i>
                            </div>
                            <h3 class="service-card-title"><?php echo esc_html($exam['name']); ?></h3>
                            <p class="service-card-desc"><?php echo esc_html($exam['full_name']); ?></p>
                            <p class="service-card-price">₦<?php echo number_format($monthly_price); ?>/month</p>
                            <p class="service-card-price-alt" style="font-size: 0.8rem; color: #a78bfa;">or ₦<?php echo number_format($sixmonth_price); ?>/6 months</p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <p class="text-success mt-2" style="font-size: 0.9rem;"><i class="fas fa-gift"></i> <strong>Free Preview:</strong> First <?php echo intval($free_questions_limit); ?> questions in all subjects are FREE!</p>
        </div>
        
        <!-- Category Selection Section -->
        <div class="glass-card mb-3" id="category-section">
            <h3 class="text-white"><i class="fas fa-layer-group"></i> Subject Categories</h3>
            <p class="text-muted mb-2">Subscribe to access unlimited questions and quizzes. Select exam type first, then choose a category.</p>
            <p class="text-warning mb-2" style="font-size: 0.85rem;"><i class="fas fa-info-circle"></i> <strong>Pricing:</strong> ₦<?php echo number_format($monthly_price); ?>/month or ₦<?php echo number_format($sixmonth_price); ?>/6 months</p>
            <p class="text-success mb-2"><i class="fas fa-star"></i> <strong>Mathematics & English are compulsory</strong> - Included in ALL categories!</p>
            
            <div class="form-group mb-2">
                <label for="category-exam-type" class="text-white"><i class="fas fa-graduation-cap"></i> Exam Type</label>
                <div class="input-with-icon">
                    <i class="fas fa-graduation-cap input-icon"></i>
                    <select id="category-exam-type" class="form-control form-control-icon">
                        <option value="">Select Exam Type</option>
                        <?php if (!empty($exam_types)): ?>
                            <?php foreach ($exam_types as $type => $exam): ?>
                                <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($exam['name']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>
            
            <div id="categories-grid" class="cards-grid" style="display: none;">
                <!-- Categories will be loaded here -->
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="glass-card mb-3">
            <h3 class="text-white"><i class="fas fa-filter"></i> Select Questions</h3>
            <div class="row">
                <div class="col col-md-12" style="flex: 1; min-width: 200px;">
                    <div class="form-group">
                        <label for="exam-type-select" class="text-white"><i class="fas fa-graduation-cap"></i> Exam Type</label>
                        <div class="input-with-icon">
                            <i class="fas fa-graduation-cap input-icon"></i>
                            <select id="exam-type-select" class="form-control form-control-icon">
                                <option value="">Select Exam Type</option>
                                <?php foreach ($exam_types as $type => $exam): ?>
                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($exam['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col col-md-12" style="flex: 1; min-width: 200px;">
                    <div class="form-group">
                        <label for="subject-select" class="text-white"><i class="fas fa-book"></i> Subject</label>
                        <div class="input-with-icon">
                            <i class="fas fa-book input-icon"></i>
                            <select id="subject-select" class="form-control form-control-icon">
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col col-md-12" style="flex: 0 0 auto;">
                    <div class="form-group">
                        <label class="text-white">&nbsp;</label>
                        <button id="load-questions-btn" class="btn btn-primary">
                            <i class="fas fa-search"></i> Load Questions
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Questions Container -->
        <div id="questions-container">
            <?php if (!is_user_logged_in()): ?>
                <div class="glass-card text-center" style="padding: 3rem;">
                    <i class="fas fa-user-lock" style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;"></i>
                    <h3 class="text-white">Login Required</h3>
                    <p class="text-muted">Please login or create an account to access past questions.</p>
                    <div class="mt-2">
                        <a href="<?php echo site_url('/zonatech-login/'); ?>" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="<?php echo site_url('/zonatech-register/'); ?>" class="btn btn-secondary">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="glass-card text-center" style="padding: 3rem;">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;"></i>
                    <h3 class="text-white">Select Your Questions</h3>
                    <p class="text-muted">Choose an exam type and subject to view past questions.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Features Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="text-white"><i class="fas fa-star"></i> Why Choose Our Past Questions?</h2>
            </div>
            <div class="cards-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Comprehensive Bank</h4>
                        <p>Access thousands of questions from all years merged together</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Practice Tests</h4>
                        <p>Take timed quizzes and see your score instantly</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Corrections</h4>
                        <p>View detailed explanations for every question</p>
                    </div>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="feature-content">
                        <h4 class="text-white">Mobile Friendly</h4>
                        <p>Study anywhere on any device</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="zonatech-footer">
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="footer-logo-img">
                    <span>ZonaTech NG</span>
                </div>
                <div class="footer-social">
                    <a href="https://wa.me/234<?php echo substr(ZONATECH_WHATSAPP_NUMBER, 1); ?>" target="_blank" title="WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:<?php echo ZONATECH_SUPPORT_EMAIL; ?>" title="Email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
                <p class="footer-copyright">
                    © <?php echo date('Y'); ?> ZonaTech NG. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Mobile Navigation
    var hamburger = $('#hamburger-menu');
    var mobileNav = $('#mobile-nav');
    var mobileNavOverlay = $('#mobile-nav-overlay');
    var mobileNavClose = $('#mobile-nav-close');
    
    function openMobileNav() {
        hamburger.addClass('active');
        mobileNav.addClass('active');
        mobileNavOverlay.addClass('active');
        $('body').css('overflow', 'hidden');
    }
    
    function closeMobileNav() {
        hamburger.removeClass('active');
        mobileNav.removeClass('active');
        mobileNavOverlay.removeClass('active');
        $('body').css('overflow', '');
    }
    
    hamburger.on('click', function() {
        if (mobileNav.hasClass('active')) {
            closeMobileNav();
        } else {
            openMobileNav();
        }
    });
    
    mobileNavClose.on('click', closeMobileNav);
    mobileNavOverlay.on('click', closeMobileNav);
    
    mobileNav.find('a').on('click', function() {
        closeMobileNav();
    });
    
    // =============================================
    // Past Questions - Subject and Year Filtering
    // =============================================
    
    // When exam type changes, load subjects
    $('#exam-type-select').on('change', function() {
        var examType = $(this).val();
        var $subjectSelect = $('#subject-select');
        
        // Reset subject dropdown
        $subjectSelect.html('<option value="">Loading...</option>');
        
        if (!examType) {
            $subjectSelect.html('<option value="">Select Subject</option>');
            return;
        }
        
        // Check if WAEC or NECO - show Coming Soon
        if (examType === 'waec' || examType === 'neco') {
            $subjectSelect.html('<option value="">Coming Soon</option>');
            showNotification(examType.toUpperCase() + ' past questions are coming soon! Currently only JAMB is available.', 'info');
            return;
        }
        
        // Fetch subjects for the selected exam type
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_subjects',
                nonce: zonatech_ajax.nonce,
                exam_type: examType
            },
            success: function(response) {
                if (response.success && response.data.subjects) {
                    var options = '<option value="">Select Subject</option>';
                    $.each(response.data.subjects, function(index, subject) {
                        options += '<option value="' + subject + '">' + subject + '</option>';
                    });
                    $subjectSelect.html(options);
                } else {
                    $subjectSelect.html('<option value="">No subjects available</option>');
                    showNotification('No subjects found for this exam type.', 'warning');
                }
            },
            error: function() {
                $subjectSelect.html('<option value="">Error loading subjects</option>');
                showNotification('Failed to load subjects. Please try again.', 'error');
            }
        });
    });
    
    // Load Questions button click
    $('#load-questions-btn').on('click', function() {
        var examType = $('#exam-type-select').val();
        var subject = $('#subject-select').val();
        
        if (!examType) {
            showNotification('Please select an exam type.', 'warning');
            return;
        }
        
        if (!subject) {
            showNotification('Please select a subject.', 'warning');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_questions',
                nonce: zonatech_ajax.nonce,
                exam_type: examType,
                subject: subject
            },
            success: function(response) {
                if (response.success) {
                    displayQuestions(response.data);
                } else {
                    if (response.data && response.data.require_payment) {
                        showPaymentPrompt(
                            response.data.exam_type, 
                            response.data.subject,
                            response.data.category,
                            response.data.category_name,
                            response.data.price
                        );
                    } else {
                        showNotification(response.data.message || 'Failed to load questions.', 'error');
                    }
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Display questions in the container with pagination
    var currentPage = 1;
    var questionsPerPage = 50;
    var allQuestions = [];
    var allPassages = {};
    var currentExamType = '';
    var currentSubject = '';
    
    function displayQuestions(data) {
        // Store data for pagination
        allQuestions = data.questions || [];
        allPassages = data.passages || {};
        currentExamType = data.exam_type;
        currentSubject = data.subject;
        currentPage = 1;
        
        // Store subscription info
        window.questionData = {
            has_paid_access: data.has_paid_access || false,
            is_limited: data.is_limited || false,
            free_limit: data.free_limit || 10,
            total: data.total || 0,
            shown: data.shown || allQuestions.length,
            category: data.category || '',
            category_name: data.category_name || '',
            monthly_price: data.monthly_price || 5000,
            sixmonth_price: data.sixmonth_price || 25000
        };
        
        renderQuestionsPage();
    }
    
    // Helper function to get passage for a question
    function getPassageForQuestion(question) {
        if (question.passage_id && allPassages[question.passage_id]) {
            return allPassages[question.passage_id];
        }
        return null;
    }
    
    function renderQuestionsPage() {
        var container = $('#questions-container');
        var totalQuestions = allQuestions.length;
        var totalPages = Math.ceil(totalQuestions / questionsPerPage);
        var startIdx = (currentPage - 1) * questionsPerPage;
        var endIdx = Math.min(startIdx + questionsPerPage, totalQuestions);
        var pageQuestions = allQuestions.slice(startIdx, endIdx);
        
        var html = '<div class="glass-card">';
        
        // Check if user is limited (free tier)
        var isLimited = window.questionData && window.questionData.is_limited;
        var totalAvailable = window.questionData ? window.questionData.total : totalQuestions;
        
        // Header with stats
        html += '<div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem;">';
        html += '<div>';
        html += '<h3 class="text-white" style="margin: 0;"><i class="fas fa-book-open"></i> ' + currentExamType + ' ' + currentSubject + '</h3>';
        if (isLimited) {
            html += '<p class="text-muted" style="margin: 0.5rem 0 0;">Showing <strong class="text-white">' + totalQuestions + '</strong> of <strong class="text-warning">' + totalAvailable + '</strong> questions <span class="text-warning" style="font-size: 0.85rem;">(Free Preview)</span></p>';
        } else {
            html += '<p class="text-muted" style="margin: 0.5rem 0 0;">Total Questions: <strong class="text-white">' + totalQuestions + '</strong></p>';
        }
        html += '</div>';
        html += '<div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">';
        if (isLimited) {
            html += '<button class="btn btn-success" onclick="subscribeCategory(\'' + currentExamType.toLowerCase() + '\', \'' + (window.questionData ? window.questionData.category : '') + '\', \'monthly\')">';
            html += '<i class="fas fa-unlock"></i> Subscribe to Unlock All';
            html += '</button>';
        } else {
            html += '<button class="btn btn-primary" onclick="startQuiz(\'' + currentExamType + '\', \'' + currentSubject + '\')">';
            html += '<i class="fas fa-play"></i> Take Quiz';
            html += '</button>';
        }
        html += '</div>';
        html += '</div>';
        
        // Display all passages at the top if there are any (for comprehension sections)
        var passageKeys = Object.keys(allPassages);
        if (passageKeys.length > 0 && currentPage === 1) {
            html += '<div class="passages-section" style="margin-bottom: 2rem;">';
            html += '<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid rgba(139, 92, 246, 0.3);">';
            html += '<i class="fas fa-book" style="color: #8b5cf6; font-size: 1.2rem;"></i>';
            html += '<h3 class="text-white" style="margin: 0; font-size: 1.2rem;">Comprehension Passages</h3>';
            html += '</div>';
            
            // Sort passages by number
            passageKeys.sort(function(a, b) {
                return (allPassages[a].number || 0) - (allPassages[b].number || 0);
            });
            
            $.each(passageKeys, function(idx, key) {
                var passage = allPassages[key];
                html += '<div class="passage-box" style="background: rgba(139, 92, 246, 0.1); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;">';
                html += '<div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">';
                html += '<i class="fas fa-book-reader" style="color: #8b5cf6; font-size: 1.2rem;"></i>';
                html += '<h4 class="text-white" style="margin: 0; font-size: 1.1rem;">Passage ' + (passage.number || (idx + 1)) + (passage.title ? ': ' + passage.title : '') + '</h4>';
                html += '</div>';
                html += '<div class="passage-text" style="color: #e5e5e5; line-height: 1.8; font-size: 0.95rem; white-space: pre-wrap;">' + passage.text + '</div>';
                html += '</div>';
            });
            html += '</div>';
        }
        
        // Page info bar
        if (totalPages > 1) {
            html += '<div style="background: rgba(139, 92, 246, 0.15); border-radius: 12px; padding: 1rem 1.5rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">';
            html += '<div class="text-white">';
            html += '<i class="fas fa-layer-group" style="color: #8b5cf6;"></i> ';
            html += 'Showing questions <strong>' + (startIdx + 1) + ' - ' + endIdx + '</strong> of <strong>' + totalQuestions + '</strong>';
            html += '</div>';
            html += '<div class="text-muted">Page ' + currentPage + ' of ' + totalPages + '</div>';
            html += '</div>';
        }
        
        if (pageQuestions.length > 0) {
            html += '<div class="questions-list">';
            
            $.each(pageQuestions, function(index, question) {
                var questionNumber = startIdx + index + 1;
                var correctAnswer = question.correct_answer ? question.correct_answer.toUpperCase() : '';
                var questionId = 'pq-' + questionNumber;
                
                html += '<div class="question-item glass-effect" id="' + questionId + '" data-correct="' + correctAnswer + '" data-answered="false" style="padding: 1.5rem; margin-bottom: 1rem; border-radius: 12px; border: 1px solid rgba(139, 92, 246, 0.2);">';
                html += '<p class="text-white" style="font-weight: 600; font-size: 1rem; line-height: 1.6;"><span style="display: inline-block; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; padding: 2px 10px; border-radius: 6px; margin-right: 10px; font-size: 0.85rem;">Q' + questionNumber + '</span>' + question.question_text + '</p>';
                html += '<div class="options" style="margin-top: 1rem;">';
                
                // Options - clickable, correct answer hidden until selected
                var options = ['A', 'B', 'C', 'D'];
                var optionValues = {
                    'A': question.option_a,
                    'B': question.option_b,
                    'C': question.option_c,
                    'D': question.option_d
                };
                
                $.each(options, function(i, letter) {
                    // All options start with neutral styling - correct answer not revealed
                    html += '<div class="pq-option" data-question="' + questionId + '" data-letter="' + letter + '">';
                    html += '<span class="option-letter">' + letter + '</span>';
                    html += '<span class="option-text">' + optionValues[letter] + '</span>';
                    html += '<span class="option-icon"></span>';
                    html += '</div>';
                });
                
                html += '</div>';
                
                // Explanation - hidden until an option is selected
                if (question.explanation && question.explanation.trim()) {
                    html += '<div class="pq-explanation-box">';
                    html += '<p class="explanation-title"><i class="fas fa-lightbulb"></i> Explanation</p>';
                    html += '<p class="explanation-content">' + question.explanation + '</p>';
                    html += '</div>';
                }
                
                html += '</div>';
            });
            html += '</div>';
            
            // Pagination controls
            if (totalPages > 1) {
                html += '<div class="pagination-container" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">';
                html += '<div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 0.5rem;">';
                
                // Previous button
                if (currentPage > 1) {
                    html += '<button class="btn btn-secondary pagination-btn" onclick="goToPage(' + (currentPage - 1) + ')" style="padding: 0.75rem 1.25rem;">';
                    html += '<i class="fas fa-chevron-left"></i> Previous';
                    html += '</button>';
                }
                
                // Page numbers
                html += '<div style="display: flex; gap: 0.25rem; align-items: center;">';
                
                // Show first page
                if (currentPage > 3) {
                    html += '<button class="btn btn-secondary pagination-btn" onclick="goToPage(1)" style="min-width: 44px; padding: 0.75rem;">1</button>';
                    if (currentPage > 4) {
                        html += '<span class="text-muted" style="padding: 0 0.5rem;">...</span>';
                    }
                }
                
                // Show pages around current
                for (var i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                    var isActive = i === currentPage;
                    var btnClass = isActive ? 'btn btn-primary' : 'btn btn-secondary';
                    html += '<button class="' + btnClass + ' pagination-btn" onclick="goToPage(' + i + ')" style="min-width: 44px; padding: 0.75rem;">' + i + '</button>';
                }
                
                // Show last page
                if (currentPage < totalPages - 2) {
                    if (currentPage < totalPages - 3) {
                        html += '<span class="text-muted" style="padding: 0 0.5rem;">...</span>';
                    }
                    html += '<button class="btn btn-secondary pagination-btn" onclick="goToPage(' + totalPages + ')" style="min-width: 44px; padding: 0.75rem;">' + totalPages + '</button>';
                }
                
                html += '</div>';
                
                // Next button
                if (currentPage < totalPages) {
                    html += '<button class="btn btn-primary pagination-btn" onclick="goToPage(' + (currentPage + 1) + ')" style="padding: 0.75rem 1.25rem;">';
                    html += 'Next <i class="fas fa-chevron-right"></i>';
                    html += '</button>';
                }
                
                html += '</div>';
                
                // Quick jump
                html += '<div style="text-align: center; margin-top: 1rem;">';
                html += '<span class="text-muted" style="margin-right: 0.5rem;">Jump to page:</span>';
                html += '<select id="page-jump" onchange="goToPage(parseInt(this.value))" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;">';
                for (var p = 1; p <= totalPages; p++) {
                    var selected = p === currentPage ? ' selected' : '';
                    html += '<option value="' + p + '"' + selected + '>Page ' + p + '</option>';
                }
                html += '</select>';
                html += '</div>';
                
                html += '</div>';
            }
            
            // Show subscription prompt if user is limited (free trial ended)
            if (window.questionData && window.questionData.is_limited) {
                html += '<div class="subscription-prompt" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.2) 0%, rgba(124, 58, 237, 0.1) 100%); border: 2px solid rgba(139, 92, 246, 0.5); border-radius: 16px; padding: 2rem; margin-top: 2rem; text-align: center;">';
                html += '<div style="margin-bottom: 1rem;"><i class="fas fa-lock" style="font-size: 3rem; color: #8b5cf6;"></i></div>';
                html += '<h3 class="text-white" style="margin-bottom: 0.5rem;">Unlock All ' + window.questionData.total + ' Questions!</h3>';
                html += '<p class="text-muted" style="margin-bottom: 1.5rem;">You\'ve viewed the first ' + window.questionData.free_limit + ' free questions. Subscribe to access all questions and take unlimited quizzes!</p>';
                
                html += '<div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 1rem; margin-bottom: 1.5rem;">';
                html += '<div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 1.5rem; min-width: 200px;">';
                html += '<p class="text-muted" style="margin: 0 0 0.5rem; font-size: 0.9rem;">Monthly Plan</p>';
                html += '<p class="text-white" style="margin: 0; font-size: 1.5rem; font-weight: 700;">₦' + window.questionData.monthly_price.toLocaleString() + '<span style="font-size: 0.9rem; font-weight: 400; color: #a78bfa;">/month</span></p>';
                html += '<button class="btn btn-primary" onclick="subscribeCategory(\'' + currentExamType.toLowerCase() + '\', \'' + window.questionData.category + '\', \'monthly\')" style="margin-top: 1rem; width: 100%;">Subscribe Monthly</button>';
                html += '</div>';
                
                html += '<div style="background: rgba(139, 92, 246, 0.15); border: 2px solid rgba(139, 92, 246, 0.5); border-radius: 12px; padding: 1.5rem; min-width: 200px; position: relative;">';
                html += '<div style="position: absolute; top: -10px; right: 10px; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">BEST VALUE</div>';
                html += '<p class="text-muted" style="margin: 0 0 0.5rem; font-size: 0.9rem;">6-Month Plan</p>';
                html += '<p class="text-white" style="margin: 0; font-size: 1.5rem; font-weight: 700;">₦' + window.questionData.sixmonth_price.toLocaleString() + '<span style="font-size: 0.9rem; font-weight: 400; color: #a78bfa;">/6 months</span></p>';
                html += '<p class="text-success" style="margin: 0.25rem 0 0; font-size: 0.8rem;">Save ₦' + ((window.questionData.monthly_price * 6) - window.questionData.sixmonth_price).toLocaleString() + '!</p>';
                html += '<button class="btn btn-primary" onclick="subscribeCategory(\'' + currentExamType.toLowerCase() + '\', \'' + window.questionData.category + '\', \'sixmonth\')" style="margin-top: 1rem; width: 100%;">Subscribe 6 Months</button>';
                html += '</div>';
                html += '</div>';
                
                html += '<p class="text-muted" style="font-size: 0.85rem; margin: 0;"><i class="fas fa-check-circle" style="color: #22c55e;"></i> Full access to all questions <i class="fas fa-check-circle" style="color: #22c55e; margin-left: 1rem;"></i> Unlimited quiz attempts</p>';
                html += '</div>';
            } else {
                // Bottom quiz button (only for paid users)
                html += '<div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255, 255, 255, 0.1);">';
                html += '<p class="text-muted" style="margin-bottom: 1rem;">Ready to test your knowledge?</p>';
                html += '<button class="btn btn-primary btn-lg" onclick="startQuiz(\'' + currentExamType + '\', \'' + currentSubject + '\')" style="padding: 1rem 2rem; font-size: 1.1rem;">';
                html += '<i class="fas fa-play"></i> Start Practice Quiz';
                html += '</button>';
                html += '</div>';
            }
            
        } else {
            html += '<p class="text-muted text-center">No questions available for this selection.</p>';
        }
        
        html += '</div>';
        container.html(html);
        
        // Scroll to top of questions container
        $('html, body').animate({
            scrollTop: container.offset().top - 100
        }, 300);
    }
    
    // Handle past question option clicks - using event delegation on container
    $('#questions-container').on('click', '.pq-option', function() {
        var $option = $(this);
        var questionId = $option.data('question');
        var selectedLetter = $option.data('letter');
        var $questionItem = $('#' + questionId);
        var correctAnswer = $questionItem.data('correct');
        var isAnswered = $questionItem.data('answered') === true || $questionItem.data('answered') === 'true';
        
        // If already answered, do nothing
        if (isAnswered) {
            return;
        }
        
        // Mark question as answered
        $questionItem.data('answered', 'true');
        
        var isCorrect = selectedLetter === correctAnswer;
        
        // Update all options in this question
        $questionItem.find('.pq-option').each(function() {
            var $opt = $(this);
            var optLetter = $opt.data('letter');
            var $letterSpan = $opt.find('.option-letter');
            var $textSpan = $opt.find('.option-text');
            var $iconSpan = $opt.find('.option-icon');
            
            // Remove cursor pointer and hover effects
            $opt.css('cursor', 'default');
            
            if (optLetter === correctAnswer) {
                // Highlight correct answer in green
                $opt.css({
                    'background': 'rgba(34, 197, 94, 0.15)',
                    'border': '1px solid rgba(34, 197, 94, 0.3)'
                });
                $letterSpan.css({
                    'background': '#22c55e',
                    'color': 'white'
                });
                $textSpan.css({
                    'color': '#22c55e',
                    'font-weight': '600'
                });
                $iconSpan.html('<i class="fas fa-check-circle" style="color: #22c55e;"></i>');
            } else if (optLetter === selectedLetter && !isCorrect) {
                // Highlight wrong selected answer in red
                $opt.css({
                    'background': 'rgba(239, 68, 68, 0.15)',
                    'border': '1px solid rgba(239, 68, 68, 0.3)'
                });
                $letterSpan.css({
                    'background': '#ef4444',
                    'color': 'white'
                });
                $textSpan.css({
                    'color': '#ef4444',
                    'font-weight': '600'
                });
                $iconSpan.html('<i class="fas fa-times-circle" style="color: #ef4444;"></i>');
            }
        });
        
        // Show notification based on correctness
        if (isCorrect) {
            if (typeof ZonaTechNotify !== 'undefined') {
                ZonaTechNotify.show('Correct! Well done!', 'success', 2000);
            }
        } else {
            if (typeof ZonaTechNotify !== 'undefined') {
                ZonaTechNotify.show('Incorrect. The correct answer is ' + correctAnswer, 'error', 3000);
            }
        }
        
        // Show explanation if available (only when correct answer is displayed)
        $questionItem.find('.pq-explanation-box').fadeIn(300);
    });
    
    // Go to specific page (global function for pagination)
    window.goToPage = function(page) {
        var totalPages = Math.ceil(allQuestions.length / questionsPerPage);
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            renderQuestionsPage();
        }
    };
    
    // Show payment prompt (category-based)
    function showPaymentPrompt(examType, subject, category, categoryName, price) {
        var safeExamType = examType.toLowerCase().replace(/'/g, "\\'");
        var safeCat = category || 'arts';
        var catName = categoryName || 'Arts';
        var catPrice = price || zonatech_ajax.category_price;
        
        var html = '<div class="glass-card text-center" style="padding: 2rem;">';
        html += '<i class="fas fa-lock" style="font-size: 3rem; color: var(--zona-purple); margin-bottom: 1rem;"></i>';
        html += '<h3 class="text-white">Purchase Required</h3>';
        html += '<p class="text-muted">To access this subject, you need to purchase the <strong>' + catName + '</strong> category for <strong>' + examType.toUpperCase() + '</strong>.</p>';
        html += '<p class="text-muted" style="font-size: 0.9rem;">This gives you access to <strong>all subjects</strong> in the ' + catName + ' category!</p>';
        html += '<p class="text-white" style="font-size: 1.5rem; margin: 1rem 0;"><strong>₦' + Number(catPrice).toLocaleString() + '</strong></p>';
        html += '<button type="button" class="btn btn-primary" id="buy-category-btn" data-exam="' + safeExamType + '" data-category="' + safeCat + '" data-name="' + catName + '">';
        html += '<i class="fas fa-credit-card"></i> Buy ' + catName + ' Category';
        html += '</button>';
        html += '</div>';
        
        $('#questions-container').html(html);
        
        // Attach click handler
        $('#buy-category-btn').on('click', function(e) {
            e.preventDefault();
            var exam = $(this).data('exam');
            var cat = $(this).data('category');
            purchaseCategory(exam, cat);
        });
    }
    
    // Helper function to show notifications
    function showNotification(message, type) {
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, type);
        } else {
            alert(message);
        }
    }
});

// Start quiz function (global scope)
function startQuiz(examType, subject) {
    // Use the ZonaTechQuiz system to show quiz settings modal first
    if (typeof ZonaTechQuiz !== 'undefined' && typeof ZonaTechQuiz.showQuizSettingsModal === 'function') {
        ZonaTechQuiz.showQuizSettingsModal(examType.toLowerCase(), subject);
    } else {
        console.error('ZonaTechQuiz not available');
        alert('Quiz system failed to load. Please check your internet connection and reload the page.');
    }
}

// Subscribe to category function (global scope) - supports monthly and 6-month plans
function subscribeCategory(examType, category, plan) {
    // Normalize exam type to lowercase for consistent storage
    examType = examType.toLowerCase();
    console.log('subscribeCategory called:', examType, category, plan);
    
    var price = plan === 'sixmonth' ? 
        (window.questionData ? window.questionData.sixmonth_price : 25000) : 
        (window.questionData ? window.questionData.monthly_price : 5000);
    
    var duration = plan === 'sixmonth' ? '6 months' : '1 month';
    
    if (typeof ZonaTechPayment !== 'undefined' && typeof ZonaTechPayment.initiatePayment === 'function') {
        // Check if Paystack is configured
        if (typeof zonatech_ajax !== 'undefined' && zonatech_ajax.paystack_configured) {
            console.log('Initiating subscription payment for:', examType, category, price, plan);
            
            // Use the ZonaTechPayment system to initiate payment
            ZonaTechPayment.initiatePayment('subscription', price, {
                exam_type: examType,
                category: category,
                plan: plan,
                duration: duration
            });
        } else {
            console.error('Paystack not configured');
            if (typeof ZonaTechNotify !== 'undefined') {
                ZonaTechNotify.show('Payment system is not configured. Please contact support.', 'error', 5000);
            } else {
                alert('Payment system is not configured. Please contact support.');
            }
        }
    } else {
        console.error('ZonaTechPayment not available', typeof ZonaTechPayment);
        alert('Payment system failed to load. Please refresh the page and try again.');
    }
    
    return false;
}

// Purchase category function (global scope)
function purchaseCategory(examType, category) {
    // Normalize exam type to lowercase for consistent storage
    examType = examType.toLowerCase();
    console.log('purchaseCategory called:', examType, category);
    
    if (typeof ZonaTechPayment !== 'undefined' && typeof ZonaTechPayment.initiatePayment === 'function') {
        // Check if Paystack is configured
        if (typeof zonatech_ajax !== 'undefined' && zonatech_ajax.paystack_configured) {
            var price = zonatech_ajax.monthly_price || 5000;
            console.log('Initiating category payment for:', examType, category, price);
            
            // Use the ZonaTechPayment system to initiate payment
            ZonaTechPayment.initiatePayment('category', price, {
                exam_type: examType,
                category: category
            });
        } else {
            console.error('Paystack not configured');
            if (typeof ZonaTechNotify !== 'undefined') {
                ZonaTechNotify.show('Payment system is not configured. Please contact support.', 'error', 5000);
            } else {
                alert('Payment system is not configured. Please contact support.');
            }
        }
    } else {
        console.error('ZonaTechPayment not available', typeof ZonaTechPayment);
        alert('Payment system failed to load. Please refresh the page and try again.');
    }
    
    return false;
}

// Purchase subject function (legacy - global scope)
function purchaseSubject(examType, subject) {
    console.log('purchaseSubject called:', examType, subject);
    
    if (typeof ZonaTechPayment !== 'undefined' && typeof ZonaTechPayment.initiatePayment === 'function') {
        // Check if Paystack is configured
        if (typeof zonatech_ajax !== 'undefined' && zonatech_ajax.paystack_configured) {
            console.log('Initiating payment for:', examType, subject, zonatech_ajax.subject_price);
            // Use the ZonaTechPayment system to initiate payment
            ZonaTechPayment.initiatePayment('subject', zonatech_ajax.subject_price, {
                exam_type: examType,
                subject: subject
            });
        } else {
            console.error('Paystack not configured');
            if (typeof ZonaTechNotify !== 'undefined') {
                ZonaTechNotify.show('Payment system is not configured. Please contact support.', 'error', 5000);
            } else {
                alert('Payment system is not configured. Please contact support.');
            }
        }
    } else {
        console.error('ZonaTechPayment not available', typeof ZonaTechPayment);
        alert('Payment system failed to load. Please refresh the page and try again.');
    }
    
    return false;
}

// Load categories when exam type is selected
jQuery(document).ready(function($) {
    $('#category-exam-type').on('change', function() {
        var examType = $(this).val();
        var $grid = $('#categories-grid');
        
        if (!examType) {
            $grid.hide().html('');
            return;
        }
        
        // Check if WAEC or NECO - show Coming Soon
        if (examType === 'waec' || examType === 'neco') {
            $grid.html('<div class="glass-effect text-center" style="padding: 3rem; border-radius: 12px;"><i class="fas fa-clock" style="font-size: 3rem; color: #f59e0b; margin-bottom: 1rem;"></i><h3 class="text-white">Coming Soon!</h3><p class="text-muted">' + examType.toUpperCase() + ' past questions are coming soon. Currently only JAMB questions are available.</p></div>').show();
            return;
        }
        
        $grid.html('<div class="text-center text-muted" style="padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading categories...</div>').show();
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_categories',
                nonce: zonatech_ajax.nonce,
                exam_type: examType
            },
            success: function(response) {
                if (response.success && response.data.categories) {
                    var html = '';
                    $.each(response.data.categories, function(catKey, cat) {
                        var accessBadge = cat.has_access ? 
                            '<span class="badge" style="background: #22c55e; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><i class="fas fa-check"></i> Purchased</span>' : 
                            '<span class="badge" style="background: #8b5cf6; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">₦' + Number(cat.price).toLocaleString() + '</span>';
                        
                        var actionBtn = cat.has_access ?
                            '<button class="btn btn-success btn-sm view-category-btn" data-category="' + catKey + '" style="margin-top: 10px; width: 100%;"><i class="fas fa-eye"></i> View Subjects</button>' :
                            '<button class="btn btn-primary btn-sm buy-category-btn" data-exam="' + examType + '" data-category="' + catKey + '" data-name="' + cat.name + '" style="margin-top: 10px; width: 100%;"><i class="fas fa-shopping-cart"></i> Buy Now</button>';
                        
                        html += '<div class="category-card glass-effect" style="padding: 1.5rem; border-radius: 12px; text-align: center; border: 2px solid ' + cat.color + '40;">';
                        html += '<div style="font-size: 2.5rem; color: ' + cat.color + '; margin-bottom: 10px;"><i class="' + cat.icon + '"></i></div>';
                        html += '<h4 class="text-white" style="margin: 0 0 5px;">' + cat.name + '</h4>';
                        html += '<p class="text-muted" style="font-size: 12px; margin: 0 0 10px;">' + cat.description + '</p>';
                        html += '<p style="color: ' + cat.color + '; font-weight: 600;">' + cat.subject_count + ' Subjects</p>';
                        html += accessBadge;
                        html += actionBtn;
                        
                        // Show subjects list
                        html += '<div class="subjects-list" style="margin-top: 15px; text-align: left; max-height: 150px; overflow-y: auto; font-size: 12px;">';
                        $.each(cat.subjects.slice(0, 8), function(i, subj) {
                            // Escape HTML to prevent XSS
                            var safeSubj = $('<div>').text(subj).html();
                            html += '<span class="text-muted" style="display: inline-block; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; margin: 2px;">' + safeSubj + '</span>';
                        });
                        if (cat.subjects.length > 8) {
                            html += '<span class="text-muted" style="display: inline-block; padding: 2px 6px;">+' + (cat.subjects.length - 8) + ' more</span>';
                        }
                        html += '</div>';
                        
                        html += '</div>';
                    });
                    
                    $grid.html(html);
                    
                    // Handle buy button clicks
                    $grid.find('.buy-category-btn').on('click', function(e) {
                        e.preventDefault();
                        var exam = $(this).data('exam');
                        var cat = $(this).data('category');
                        purchaseCategory(exam, cat);
                    });
                    
                    // Handle view subjects clicks
                    $grid.find('.view-category-btn').on('click', function(e) {
                        e.preventDefault();
                        var cat = $(this).data('category');
                        // Scroll to the filter section
                        $('html, body').animate({
                            scrollTop: $('#exam-type-select').offset().top - 100
                        }, 500);
                    });
                } else {
                    $grid.html('<div class="text-center text-muted" style="padding: 2rem;">No categories available.</div>');
                }
            },
            error: function() {
                $grid.html('<div class="text-center text-muted" style="padding: 2rem;">Error loading categories. Please try again.</div>');
            }
        });
    });
});
</script>