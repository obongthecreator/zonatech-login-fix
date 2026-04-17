<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_access = $wpdb->prefix . 'zonatech_user_access';

// Ensure the category column exists (older installs may not have it)
if (class_exists('ZonaTech_Past_Questions')) {
    ZonaTech_Past_Questions::ensure_category_column();
}

// Get all active access records with user info
$active_records = $wpdb->get_results(
    "SELECT a.*, u.display_name, u.user_email 
     FROM $table_access a 
     JOIN {$wpdb->users} u ON a.user_id = u.ID 
     WHERE a.expires_at IS NULL OR a.expires_at > NOW() 
     ORDER BY a.created_at DESC"
);

// Get expired access records
$expired_records = $wpdb->get_results(
    "SELECT a.*, u.display_name, u.user_email 
     FROM $table_access a 
     JOIN {$wpdb->users} u ON a.user_id = u.ID 
     WHERE a.expires_at IS NOT NULL AND a.expires_at <= NOW() 
     ORDER BY a.expires_at DESC 
     LIMIT 50"
);

$active_count = count($active_records);
$expired_count = count($expired_records);
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-admin-users"></span> User Access Management</h1>
    <p>Manually grant or revoke access to exam categories for users. Use this to approve users as paid subscribers without requiring Paystack payment.</p>
    
    <!-- Stats -->
    <div class="zonatech-stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: #22c55e;"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($active_count); ?></h3>
                <p>Active Access Records</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #ef4444;"><span class="dashicons dashicons-dismiss"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($expired_count); ?></h3>
                <p>Expired Records</p>
            </div>
        </div>
    </div>

    <!-- Grant Access Section -->
    <div class="zonatech-admin-section" style="margin-bottom: 30px; background: #fff; padding: 20px; border: 1px solid #c3c4c7; border-radius: 4px;">
        <h2><span class="dashicons dashicons-plus-alt2"></span> Grant Access to User</h2>
        <p class="description">Search for a user and grant them access to an exam category. They will be treated as a paid subscriber.</p>
        
        <div id="grant-access-form">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="user-search">Search User</label></th>
                    <td>
                        <input type="text" id="user-search" class="regular-text" placeholder="Type email or username..." autocomplete="off">
                        <p class="description">Search by email address, username, or display name (minimum 2 characters).</p>
                        <div id="user-search-results" style="display:none; margin-top: 8px; border: 1px solid #ddd; border-radius: 4px; max-height: 200px; overflow-y: auto; background: #fff;"></div>
                        <div id="selected-user" style="display:none; margin-top: 8px; padding: 10px; background: #f0f6fc; border: 1px solid #0073aa; border-radius: 4px;">
                            <strong id="selected-user-name"></strong> (<span id="selected-user-email"></span>)
                            <input type="hidden" id="selected-user-id" value="">
                            <button type="button" class="button button-small" id="clear-user" style="margin-left: 10px;">✕ Clear</button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="grant-exam-type">Exam Type</label></th>
                    <td>
                        <select id="grant-exam-type" class="regular-text">
                            <option value="">Select Exam Type</option>
                            <option value="jamb">JAMB</option>
                            <option value="waec">WAEC</option>
                            <option value="neco">NECO</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="grant-category">Category</label></th>
                    <td>
                        <select id="grant-category" class="regular-text">
                            <option value="">Select Category</option>
                            <option value="all">All Categories (Science + Arts + Business)</option>
                            <option value="science">Science</option>
                            <option value="arts">Arts</option>
                            <option value="business">Business/Commercial</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="grant-duration">Duration</label></th>
                    <td>
                        <select id="grant-duration" class="regular-text">
                            <option value="monthly">1 Month</option>
                            <option value="sixmonth">6 Months</option>
                            <option value="lifetime">Lifetime (Never Expires)</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="button" id="grant-access-btn" class="button button-primary button-large">
                    <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Grant Access
                </button>
            </p>
        </div>
        <div id="grant-access-message" style="display:none;"></div>
    </div>

    <!-- Tabs for Active / Expired -->
    <h2 class="nav-tab-wrapper zonatech-admin-tabs">
        <a href="#" class="nav-tab nav-tab-active" data-tab="tab-active">
            <span class="dashicons dashicons-yes-alt" style="color: #22c55e;"></span> 
            Approved / Active (<?php echo $active_count; ?>)
        </a>
        <a href="#" class="nav-tab" data-tab="tab-expired">
            <span class="dashicons dashicons-clock" style="color: #ef4444;"></span> 
            Expired (<?php echo $expired_count; ?>)
        </a>
    </h2>

    <!-- Active Access Tab -->
    <div id="tab-active" class="zonatech-tab-content" style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; border-top: none;">
        <?php if (empty($active_records)): ?>
            <p style="text-align: center; padding: 30px; color: #666;">
                <span class="dashicons dashicons-info" style="font-size: 40px; width: 40px; height: 40px; color: #ccc;"></span><br>
                No active access records found. Use the form above to grant access.
            </p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Exam Type</th>
                        <th>Category</th>
                        <th>Source</th>
                        <th>Granted On</th>
                        <th>Expires</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($active_records as $record): ?>
                        <tr id="access-row-<?php echo $record->id; ?>">
                            <td><?php echo $record->id; ?></td>
                            <td><strong><?php echo esc_html($record->display_name); ?></strong></td>
                            <td><a href="mailto:<?php echo esc_attr($record->user_email); ?>"><?php echo esc_html($record->user_email); ?></a></td>
                            <td><span class="zonatech-badge" style="background: #8b5cf6; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;"><?php echo esc_html(strtoupper($record->exam_type)); ?></span></td>
                            <td><?php echo esc_html(ucfirst($record->category ?: $record->subject ?: '—')); ?></td>
                            <td><?php echo $record->purchase_id ? '<span style="color: #22c55e;">Payment #' . esc_html(intval($record->purchase_id)) . '</span>' : '<span style="color: #0073aa;">Admin</span>'; ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($record->created_at))); ?></td>
                            <td>
                                <?php if (empty($record->expires_at)): ?>
                                    <span style="color: #22c55e; font-weight: bold;">Lifetime</span>
                                <?php else: ?>
                                    <?php echo date('M j, Y', strtotime($record->expires_at)); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="button button-small revoke-access-btn" data-id="<?php echo $record->id; ?>" data-user="<?php echo esc_attr($record->display_name); ?>" style="color: #d63638;">
                                    <span class="dashicons dashicons-no" style="vertical-align: middle; font-size: 16px;"></span> Revoke
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Expired Access Tab -->
    <div id="tab-expired" class="zonatech-tab-content" style="display:none; background: #fff; padding: 15px; border: 1px solid #c3c4c7; border-top: none;">
        <?php if (empty($expired_records)): ?>
            <p style="text-align: center; padding: 30px; color: #666;">
                <span class="dashicons dashicons-info" style="font-size: 40px; width: 40px; height: 40px; color: #ccc;"></span><br>
                No expired access records found.
            </p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Exam Type</th>
                        <th>Category</th>
                        <th>Source</th>
                        <th>Granted On</th>
                        <th>Expired On</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expired_records as $record): ?>
                        <tr id="access-row-<?php echo $record->id; ?>">
                            <td><?php echo $record->id; ?></td>
                            <td><strong><?php echo esc_html($record->display_name); ?></strong></td>
                            <td><a href="mailto:<?php echo esc_attr($record->user_email); ?>"><?php echo esc_html($record->user_email); ?></a></td>
                            <td><span class="zonatech-badge" style="background: #999; color: #fff; padding: 3px 8px; border-radius: 3px; font-size: 11px;"><?php echo esc_html(strtoupper($record->exam_type)); ?></span></td>
                            <td><?php echo esc_html(ucfirst($record->category ?: $record->subject ?: '—')); ?></td>
                            <td><?php echo $record->purchase_id ? '<span style="color: #999;">Payment #' . esc_html(intval($record->purchase_id)) . '</span>' : '<span style="color: #999;">Admin</span>'; ?></td>
                            <td><?php echo esc_html(date('M j, Y', strtotime($record->created_at))); ?></td>
                            <td><span style="color: #d63638;"><?php echo esc_html(date('M j, Y', strtotime($record->expires_at))); ?></span></td>
                            <td>
                                <button type="button" class="button button-small revoke-access-btn" data-id="<?php echo $record->id; ?>" data-user="<?php echo esc_attr($record->display_name); ?>" style="color: #999;">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle; font-size: 16px;"></span> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var searchTimer = null;
    
    // User search with debounce
    $('#user-search').on('input', function() {
        var query = $(this).val().trim();
        clearTimeout(searchTimer);
        
        if (query.length < 2) {
            $('#user-search-results').hide().html('');
            return;
        }
        
        searchTimer = setTimeout(function() {
            $.ajax({
                url: zonatech_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'zonatech_search_users',
                    nonce: zonatech_admin.nonce,
                    search: query
                },
                success: function(response) {
                    if (response.success && response.data.users.length > 0) {
                        var html = '';
                        response.data.users.forEach(function(user) {
                            html += '<div class="user-search-item" data-id="' + parseInt(user.id) + '" data-name="' + escapeHtml(user.display_name) + '" data-email="' + escapeHtml(user.email) + '" style="padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">';
                            html += '<div><strong>' + escapeHtml(user.display_name) + '</strong> <small style="color: #666;">@' + escapeHtml(user.username) + '</small></div>';
                            html += '<div style="color: #666; font-size: 12px;">' + escapeHtml(user.email) + '</div>';
                            html += '</div>';
                        });
                        $('#user-search-results').html(html).show();
                    } else {
                        $('#user-search-results').html('<div style="padding: 12px; color: #666; text-align: center;">No users found.</div>').show();
                    }
                }
            });
        }, 300);
    });
    
    // Select a user from search results
    $(document).on('click', '.user-search-item', function() {
        var userId = $(this).data('id');
        var name = $(this).data('name');
        var email = $(this).data('email');
        
        $('#selected-user-id').val(userId);
        $('#selected-user-name').text(name);
        $('#selected-user-email').text(email);
        $('#selected-user').show();
        $('#user-search').val('').hide();
        $('#user-search-results').hide().html('');
    });
    
    // Clear selected user
    $('#clear-user').on('click', function() {
        $('#selected-user-id').val('');
        $('#selected-user').hide();
        $('#user-search').val('').show().focus();
    });
    
    // Hover effect on search results
    $(document).on('mouseenter', '.user-search-item', function() {
        $(this).css('background-color', '#f0f6fc');
    }).on('mouseleave', '.user-search-item', function() {
        $(this).css('background-color', '#fff');
    });
    
    // Grant access button
    $('#grant-access-btn').on('click', function() {
        var userId = $('#selected-user-id').val();
        var examType = $('#grant-exam-type').val();
        var category = $('#grant-category').val();
        var duration = $('#grant-duration').val();
        
        if (!userId) {
            showMessage('Please search and select a user first.', 'error');
            return;
        }
        if (!examType) {
            showMessage('Please select an exam type.', 'error');
            return;
        }
        if (!category) {
            showMessage('Please select a category.', 'error');
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).text('Granting...');
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_grant_access',
                nonce: zonatech_admin.nonce,
                user_id: userId,
                exam_type: examType,
                category: category,
                duration: duration
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    // Reset form
                    $('#selected-user-id').val('');
                    $('#selected-user').hide();
                    $('#user-search').val('').show();
                    $('#grant-exam-type').val('');
                    $('#grant-category').val('');
                    $('#grant-duration').val('monthly');
                    // Reload page after short delay to update tables
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    showMessage(response.data.message, 'error');
                }
            },
            error: function() {
                showMessage('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align: middle;"></span> Grant Access');
            }
        });
    });
    
    // Revoke access button
    $(document).on('click', '.revoke-access-btn', function() {
        var accessId = parseInt($(this).data('id'));
        var userName = $(this).data('user');
        
        if (!accessId || isNaN(accessId)) {
            return;
        }
        
        if (!confirm('Are you sure you want to revoke access for "' + userName + '"? This cannot be undone.')) {
            return;
        }
        
        var btn = $(this);
        btn.prop('disabled', true).text('Revoking...');
        
        $.ajax({
            url: zonatech_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_revoke_access',
                nonce: zonatech_admin.nonce,
                access_id: accessId
            },
            success: function(response) {
                if (response.success) {
                    $('#access-row-' + accessId).fadeOut(400, function() { $(this).remove(); });
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false).html('<span class="dashicons dashicons-no" style="vertical-align: middle; font-size: 16px;"></span> Revoke');
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                btn.prop('disabled', false).html('<span class="dashicons dashicons-no" style="vertical-align: middle; font-size: 16px;"></span> Revoke');
            }
        });
    });
    
    function showMessage(message, type) {
        var bgColor = type === 'success' ? '#d4edda' : '#f8d7da';
        var textColor = type === 'success' ? '#155724' : '#721c24';
        var borderColor = type === 'success' ? '#c3e6cb' : '#f5c6cb';
        
        var html = '<div class="notice notice-' + type + ' is-dismissible" style="margin: 10px 0; padding: 10px 15px; background: ' + bgColor + '; color: ' + textColor + '; border-left: 4px solid ' + borderColor + ';">';
        html += '<p>' + message + '</p>';
        html += '</div>';
        
        $('#grant-access-message').html(html).show();
        
        setTimeout(function() {
            $('#grant-access-message').fadeOut();
        }, 5000);
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    }
});
</script>