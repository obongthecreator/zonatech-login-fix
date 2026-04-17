<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_questions = $wpdb->prefix . 'zonatech_questions';

// Handle form submission
if (isset($_POST['zonatech_add_question']) && wp_verify_nonce($_POST['_wpnonce'], 'zonatech_add_question')) {
    $wpdb->insert($table_questions, array(
        'exam_type' => sanitize_text_field($_POST['exam_type']),
        'subject' => sanitize_text_field($_POST['subject']),
        'year' => intval($_POST['year']),
        'question_text' => sanitize_textarea_field($_POST['question_text']),
        'option_a' => sanitize_text_field($_POST['option_a']),
        'option_b' => sanitize_text_field($_POST['option_b']),
        'option_c' => sanitize_text_field($_POST['option_c']),
        'option_d' => sanitize_text_field($_POST['option_d']),
        'correct_answer' => sanitize_text_field($_POST['correct_answer']),
        'explanation' => sanitize_textarea_field($_POST['explanation'])
    ));
    echo '<div class="notice notice-success"><p>Question added successfully!</p></div>';
}

// Get questions count by exam type
$stats = $wpdb->get_results(
    "SELECT exam_type, COUNT(*) as count FROM $table_questions GROUP BY exam_type"
);

// Subject options for both forms
$subjects = array(
    'Use of English',
    'English Language',
    'Mathematics',
    'Further Mathematics',
    'Physics',
    'Chemistry',
    'Biology',
    'Economics',
    'Government',
    'Literature in English',
    'Commerce',
    'Accounting',
    'Financial Accounting',
    'Geography',
    'Agricultural Science',
    'Computer Science',
    'Computer Studies',
    'Data Processing',
    'Civic Education',
    'History',
    'Christian Religious Studies',
    'Islamic Religious Studies',
    'Home Economics',
    'Food & Nutrition',
    'Fine Arts',
    'Music',
    'French',
    'Arabic',
    'Hausa',
    'Igbo',
    'Yoruba',
    'Physical Education',
    'Health Education',
    'Health Science',
    'Technical Drawing',
    'Animal Husbandry',
    'Marketing',
    'Insurance',
    'Office Practice'
);
sort($subjects);
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-book"></span> Manage Questions</h1>
    
    <div class="zonatech-stats-grid" style="margin-bottom: 20px;">
        <?php foreach ($stats as $stat): ?>
            <div class="stat-card small">
                <h4><?php echo strtoupper(esc_html($stat->exam_type)); ?></h4>
                <p><?php echo number_format($stat->count); ?> questions</p>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Delete All Questions Section -->
    <div class="delete-all-section" style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 15px; margin-bottom: 20px;">
        <h3 style="margin-top: 0; color: #856404;"><span class="dashicons dashicons-warning"></span> Danger Zone</h3>
        <p style="color: #856404;">Delete all questions across all exam types and subjects. This action cannot be undone!</p>
        <button type="button" id="delete-all-questions" class="button button-link-delete" style="background: #dc3545; color: #fff; border-color: #dc3545;">
            <span class="dashicons dashicons-trash" style="margin-top: 3px;"></span> Delete All Questions
        </button>
    </div>
    
    <!-- Tabs Navigation -->
    <h2 class="nav-tab-wrapper zonatech-admin-tabs">
        <a href="#" class="nav-tab nav-tab-active" data-tab="tab-single">Add Single Question</a>
        <a href="#" class="nav-tab" data-tab="tab-import">Bulk Import Questions</a>
    </h2>
    
    <!-- Single Question Tab -->
    <div id="tab-single" class="zonatech-tab-content zonatech-admin-section" style="margin-top: 0; border-top: none;">
        <form method="post" class="zonatech-form">
            <?php wp_nonce_field('zonatech_add_question'); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Exam Type</label>
                    <select name="exam_type" required>
                        <option value="jamb">JAMB</option>
                        <option value="waec">WAEC</option>
                        <option value="neco">NECO</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject" required>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Year</label>
                    <select name="year" required>
                        <?php for ($y = intval(date('Y')); $y >= 1990; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Question</label>
                <textarea name="question_text" rows="3" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Option A</label>
                    <input type="text" name="option_a" required>
                </div>
                <div class="form-group">
                    <label>Option B</label>
                    <input type="text" name="option_b" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Option C</label>
                    <input type="text" name="option_c" required>
                </div>
                <div class="form-group">
                    <label>Option D</label>
                    <input type="text" name="option_d" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Correct Answer</label>
                    <select name="correct_answer" required>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Explanation (Optional)</label>
                <textarea name="explanation" rows="2"></textarea>
            </div>
            
            <button type="submit" name="zonatech_add_question" class="button button-primary">
                Add Question
            </button>
        </form>
    </div>
    
    <!-- Bulk Import Tab -->
    <div id="tab-import" class="zonatech-tab-content zonatech-admin-section" style="display: none; margin-top: 0; border-top: none;">
        <div class="import-instructions" style="background: #f0f6ff; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px;">
            <h3 style="margin-top: 0;"><span class="dashicons dashicons-info"></span> Import Instructions</h3>
            <p><strong>Option 1: Upload Files</strong> - Upload PDF, DOCX, or TXT files containing questions and/or answer keys. The system will attempt to automatically detect exam type, subject, and year from the content.</p>
            <p><strong>Option 2: Paste Text</strong> - Paste questions with numbered format like:</p>
            <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">
81. He was determined to ....... all position into submission?
A. push
B. cow
C. box
D. pound

82. I tried to discourage him, but he persisted ........ revealing the secret.
A. for
B. in
C. on
D. to</pre>
            
            <p><strong>Answers Format:</strong> Paste answer key with format like:</p>
            <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; margin: 10px 0;">
1. D    2. A    3. C    4. D    5. B    6. D
7. B    8. A    9. B    10. C   11. B   12. D
...</pre>
            <p style="margin-bottom: 0;"><em>The system will automatically match question numbers with their answers.</em></p>
        </div>
        
        <!-- File Upload Section -->
        <div class="file-upload-section" style="background: #f9f9f9; border: 2px dashed #ccc; border-radius: 8px; padding: 20px; margin-bottom: 20px; text-align: center;">
            <h3 style="margin-top: 0;"><span class="dashicons dashicons-upload"></span> Upload Files (PDF, DOCX, TXT)</h3>
            <p>Upload files and the system will extract text and try to detect exam type, subject, and year automatically.</p>
            
            <div class="form-row" style="justify-content: center; gap: 20px; margin-top: 15px;">
                <div class="upload-box" style="flex: 1; max-width: 300px;">
                    <label for="questions_file" style="display: block; margin-bottom: 5px;"><strong>Questions File</strong></label>
                    <input type="file" id="questions_file" accept=".pdf,.docx,.doc,.txt" style="width: 100%;">
                    <button type="button" id="upload_questions_btn" class="button button-secondary" style="margin-top: 10px;">
                        <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> Extract Questions
                    </button>
                </div>
                <div class="upload-box" style="flex: 1; max-width: 300px;">
                    <label for="answers_file" style="display: block; margin-bottom: 5px;"><strong>Answer Key File</strong></label>
                    <input type="file" id="answers_file" accept=".pdf,.docx,.doc,.txt" style="width: 100%;">
                    <button type="button" id="upload_answers_btn" class="button button-secondary" style="margin-top: 10px;">
                        <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> Extract Answers
                    </button>
                </div>
            </div>
            
            <!-- Detection Results -->
            <div id="detection-results" style="display: none; margin-top: 15px; padding: 10px; background: #e7f5e7; border: 1px solid #28a745; border-radius: 4px; text-align: left;">
                <strong><span class="dashicons dashicons-yes"></span> Detected from file:</strong>
                <span id="detected-info"></span>
                <button type="button" id="apply-detected" class="button button-small" style="margin-left: 10px;">Apply to form</button>
            </div>
        </div>
        
        <form id="zonatech-import-form" class="zonatech-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Exam Type <span style="color: red;">*</span></label>
                    <select name="import_exam_type" id="import_exam_type" required>
                        <option value="">Select Exam Type</option>
                        <option value="jamb">JAMB</option>
                        <option value="waec">WAEC</option>
                        <option value="neco">NECO</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Subject <span style="color: red;">*</span></label>
                    <select name="import_subject" id="import_subject" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo esc_attr($subject); ?>"><?php echo esc_html($subject); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Year <span style="color: red;">*</span></label>
                    <select name="import_year" id="import_year" required>
                        <option value="">Select Year</option>
                        <?php for ($y = intval(date('Y')); $y >= 1990; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Questions Text <span style="color: red;">*</span></label>
                <textarea name="questions_text" id="questions_text" rows="12" required 
                    placeholder="Paste your questions here or upload a file above...
                    
Example:
81. He was determined to ....... all position into submission?
A. push
B. cow
C. box
D. pound

82. I tried to discourage him, but he persisted ........ revealing the secret.
A. for
B. in
C. on
D. to"></textarea>
            </div>
            
            <div class="form-group">
                <label>Answer Key (Optional but recommended)</label>
                <textarea name="answers_text" id="answers_text" rows="6" 
                    placeholder="Paste answer key here or upload a file above...
                    
Example:
1. D    2. A    3. C    4. D    5. B    6. D
81. B   82. B   83. C   84. B   85. A"></textarea>
                <p class="description">If no answers are provided, you'll need to manually add them after import.</p>
            </div>
            
            <div class="form-actions" style="display: flex; gap: 10px; margin-top: 20px;">
                <button type="button" id="preview-import" class="button button-secondary">
                    <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> Preview Import
                </button>
                <button type="button" id="submit-import" class="button button-primary" disabled>
                    <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> Import Questions
                </button>
            </div>
        </form>
        
        <!-- Preview Section -->
        <div id="import-preview" style="display: none; margin-top: 20px;">
            <h3><span class="dashicons dashicons-visibility"></span> Preview (<span id="preview-count">0</span> questions)</h3>
            <div id="preview-content" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; background: #fff;"></div>
        </div>
        
        <!-- Import Results -->
        <div id="import-results" style="display: none; margin-top: 20px;"></div>
    </div>
</div>

<style>
.zonatech-admin-tabs {
    margin-bottom: 0;
}

.zonatech-tab-content {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-top: none;
    padding: 20px;
}

#preview-content .preview-question {
    background: #f9f9f9;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

#preview-content .preview-question h4 {
    margin: 0 0 10px;
    color: #1e3a5f;
}

#preview-content .preview-question .options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    margin-top: 10px;
}

#preview-content .preview-question .option {
    padding: 5px 10px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 3px;
}

#preview-content .preview-question .option.correct {
    background: #d4edda;
    border-color: #28a745;
}

#preview-content .preview-question .answer-badge {
    display: inline-block;
    background: #28a745;
    color: #fff;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 10px;
}

#preview-content .preview-question .no-answer {
    color: #dc3545;
    font-style: italic;
}

.import-loading {
    text-align: center;
    padding: 20px;
}

.import-loading .spinner {
    float: none;
    margin: 0 auto;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Store detected metadata
    var detectedMetadata = {};
    
    // File Upload Handler
    function handleFileUpload(fileInputId, targetTextareaId, fileType) {
        var fileInput = document.getElementById(fileInputId);
        if (!fileInput.files || !fileInput.files[0]) {
            alert('Please select a file first.');
            return;
        }
        
        var file = fileInput.files[0];
        var formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'zonatech_upload_file');
        formData.append('nonce', zonatech_admin.nonce);
        formData.append('file_type', fileType);
        
        var $btn = $('#upload_' + fileType + '_btn');
        var originalText = $btn.html();
        $btn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $btn.prop('disabled', false).html(originalText);
                
                if (response.success) {
                    // Put extracted text into textarea
                    $('#' + targetTextareaId).val(response.data.content);
                    
                    // Show detected metadata if available
                    if (response.data.detected) {
                        detectedMetadata = response.data.detected;
                        var infoHtml = '';
                        if (detectedMetadata.exam_type) {
                            infoHtml += '<strong>Exam:</strong> ' + detectedMetadata.exam_type.toUpperCase() + ' ';
                        }
                        if (detectedMetadata.subject) {
                            infoHtml += '<strong>Subject:</strong> ' + detectedMetadata.subject + ' ';
                        }
                        if (detectedMetadata.year) {
                            infoHtml += '<strong>Year:</strong> ' + detectedMetadata.year + ' ';
                        }
                        if (detectedMetadata.confidence) {
                            infoHtml += '(<em>' + detectedMetadata.confidence + ' confidence</em>)';
                        }
                        
                        if (infoHtml) {
                            $('#detected-info').html(infoHtml);
                            $('#detection-results').show();
                        }
                    }
                    
                    alert('File processed successfully! Text has been extracted and placed in the form.');
                } else {
                    alert(response.data.message || 'Error processing file.');
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html(originalText);
                alert('Error uploading file: ' + error);
            }
        });
    }
    
    // Upload Questions Button
    $('#upload_questions_btn').on('click', function() {
        handleFileUpload('questions_file', 'questions_text', 'questions');
    });
    
    // Upload Answers Button
    $('#upload_answers_btn').on('click', function() {
        handleFileUpload('answers_file', 'answers_text', 'answers');
    });
    
    // Apply Detected Metadata
    $('#apply-detected').on('click', function() {
        if (detectedMetadata.exam_type) {
            $('#import_exam_type').val(detectedMetadata.exam_type);
        }
        if (detectedMetadata.subject) {
            $('#import_subject').val(detectedMetadata.subject);
        }
        if (detectedMetadata.year) {
            $('#import_year').val(detectedMetadata.year);
        }
        alert('Detected values have been applied to the form. Please verify they are correct.');
    });
    
    // Preview Import
    $('#preview-import').on('click', function() {
        var questionsText = $('#questions_text').val().trim();
        var answersText = $('#answers_text').val().trim();
        
        if (!questionsText) {
            alert('Please paste some questions first or upload a file.');
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Parsing...');
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_preview_import',
                nonce: zonatech_admin.nonce,
                questions_text: questionsText,
                answers_text: answersText
            },
            success: function(response) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> Preview Import');
                
                if (response.success) {
                    var questions = response.data.questions;
                    var html = '';
                    
                    questions.forEach(function(q, index) {
                        html += '<div class="preview-question">';
                        html += '<h4>Q' + q.number + ': ' + escapeHtml(q.question_text) + '</h4>';
                        html += '<div class="options">';
                        html += '<div class="option' + (q.correct_answer === 'A' ? ' correct' : '') + '"><strong>A.</strong> ' + escapeHtml(q.option_a || '(empty)') + '</div>';
                        html += '<div class="option' + (q.correct_answer === 'B' ? ' correct' : '') + '"><strong>B.</strong> ' + escapeHtml(q.option_b || '(empty)') + '</div>';
                        html += '<div class="option' + (q.correct_answer === 'C' ? ' correct' : '') + '"><strong>C.</strong> ' + escapeHtml(q.option_c || '(empty)') + '</div>';
                        html += '<div class="option' + (q.correct_answer === 'D' ? ' correct' : '') + '"><strong>D.</strong> ' + escapeHtml(q.option_d || '(empty)') + '</div>';
                        // Show option E if present (for older JAMB format)
                        if (q.option_e) {
                            html += '<div class="option' + (q.correct_answer === 'E' ? ' correct' : '') + '"><strong>E.</strong> ' + escapeHtml(q.option_e) + '</div>';
                        }
                        html += '</div>';
                        if (q.correct_answer) {
                            html += '<p style="margin: 10px 0 0;"><strong>Answer:</strong> <span class="answer-badge">' + q.correct_answer + '</span></p>';
                        } else {
                            html += '<p class="no-answer" style="margin: 10px 0 0;">⚠️ No answer provided</p>';
                        }
                        html += '</div>';
                    });
                    
                    $('#preview-content').html(html);
                    $('#preview-count').text(questions.length);
                    $('#import-preview').show();
                    $('#submit-import').prop('disabled', false);
                } else {
                    alert(response.data.message || 'Error parsing questions.');
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span> Preview Import');
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Submit Import
    $('#submit-import').on('click', function() {
        var examType = $('#import_exam_type').val();
        var subject = $('#import_subject').val();
        var year = $('#import_year').val();
        var questionsText = $('#questions_text').val().trim();
        var answersText = $('#answers_text').val().trim();
        
        if (!examType || !subject || !year) {
            alert('Please select Exam Type, Subject, and Year.');
            return;
        }
        
        if (!questionsText) {
            alert('Please paste some questions first or upload a file.');
            return;
        }
        
        if (!confirm('Are you sure you want to import these questions?\n\nExam: ' + examType.toUpperCase() + '\nSubject: ' + subject + '\nYear: ' + year)) {
            return;
        }
        
        var $btn = $(this);
        $btn.prop('disabled', true).text('Importing...');
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_import_questions',
                nonce: zonatech_admin.nonce,
                exam_type: examType,
                subject: subject,
                year: year,
                questions_text: questionsText,
                answers_text: answersText
            },
            success: function(response) {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> Import Questions');
                
                var resultsHtml = '';
                if (response.success) {
                    resultsHtml = '<div class="notice notice-success" style="padding: 15px;">';
                    resultsHtml += '<h3 style="margin-top: 0;"><span class="dashicons dashicons-yes-alt"></span> Import Successful!</h3>';
                    resultsHtml += '<p>' + response.data.message + '</p>';
                    if (response.data.details) {
                        resultsHtml += '<ul>';
                        resultsHtml += '<li><strong>Total Parsed:</strong> ' + response.data.details.total_parsed + '</li>';
                        resultsHtml += '<li><strong>Imported:</strong> ' + response.data.details.success_count + '</li>';
                        resultsHtml += '<li><strong>Skipped (duplicates):</strong> ' + response.data.details.skipped + '</li>';
                        resultsHtml += '<li><strong>Errors:</strong> ' + response.data.details.errors.length + '</li>';
                        resultsHtml += '</ul>';
                    }
                    resultsHtml += '</div>';
                    
                    // Clear form after successful import
                    $('#questions_text').val('');
                    $('#answers_text').val('');
                    $('#import-preview').hide();
                    $('#submit-import').prop('disabled', true);
                } else {
                    resultsHtml = '<div class="notice notice-error" style="padding: 15px;">';
                    resultsHtml += '<h3 style="margin-top: 0;"><span class="dashicons dashicons-warning"></span> Import Failed</h3>';
                    resultsHtml += '<p>' + (response.data.message || 'An error occurred during import.') + '</p>';
                    if (response.data.details && response.data.details.errors && response.data.details.errors.length > 0) {
                        resultsHtml += '<ul>';
                        response.data.details.errors.slice(0, 10).forEach(function(err) {
                            resultsHtml += '<li>' + escapeHtml(err) + '</li>';
                        });
                        if (response.data.details.errors.length > 10) {
                            resultsHtml += '<li>... and ' + (response.data.details.errors.length - 10) + ' more errors</li>';
                        }
                        resultsHtml += '</ul>';
                    }
                    resultsHtml += '</div>';
                }
                
                $('#import-results').html(resultsHtml).show();
            },
            error: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-upload" style="margin-top: 3px;"></span> Import Questions');
                alert('An error occurred. Please try again.');
            }
        });
    });
    
    // Delete All Questions Handler
    $('#delete-all-questions').on('click', function() {
        var confirmMsg = 'WARNING: This will permanently delete ALL questions from ALL exam types and subjects.\n\n';
        confirmMsg += 'This action CANNOT be undone!\n\n';
        confirmMsg += 'Are you absolutely sure you want to proceed?';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Double confirmation
        var doubleConfirm = prompt('To confirm deletion, type "DELETE ALL" (case-sensitive):');
        if (doubleConfirm !== 'DELETE ALL') {
            alert('Deletion cancelled. You must type "DELETE ALL" exactly to confirm.');
            return;
        }
        
        var $btn = $(this);
        var originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="margin-top: 3px;"></span> Deleting...');
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_delete_all_questions',
                nonce: zonatech_admin.nonce
            },
            success: function(response) {
                $btn.prop('disabled', false).html(originalText);
                
                if (response.success) {
                    alert('SUCCESS: ' + response.data.message);
                    // Refresh the page to update stats
                    location.reload();
                } else {
                    alert('ERROR: ' + (response.data.message || 'Failed to delete questions.'));
                }
            },
            error: function(xhr, status, error) {
                $btn.prop('disabled', false).html(originalText);
                alert('Error: ' + error);
            }
        });
    });
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
</script>