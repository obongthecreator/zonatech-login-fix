<?php
/**
 * Dashboard Template
 */

if (!defined('ABSPATH')) exit;

$user = $user_data['user'];
$stats = $user_data['stats'];
?>

<div class="zonatech-container">
    <!-- Loading Screen -->
    <div id="zonatech-loading-screen" class="loading-screen">
        <div class="loading-content">
            <div class="loading-spinner">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <h2>ZonaTech NG</h2>
            <p>Loading...</p>
        </div>
    </div>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="dashboard-sidebar glass-effect">
            <div class="sidebar-header">
                <a href="<?php echo site_url(); ?>" class="sidebar-logo">
                    <img src="<?php echo ZONATECH_PLUGIN_URL; ?>assets/images/logo.png" alt="ZonaTech NG" class="sidebar-logo-img">
                    <span>ZonaTech NG</span>
                </a>
            </div>
            
            <div class="sidebar-user">
                <img src="<?php echo esc_url($user['avatar']); ?>" alt="Avatar" class="sidebar-user-avatar">
                <div class="sidebar-user-info">
                    <h4><?php echo esc_html($user['display_name']); ?></h4>
                    <p class="text-muted"><?php echo esc_html($user['display_name']); ?></p>
                </div>
            </div>
            
            <nav>
                <ul class="sidebar-nav">
                    <li><a href="<?php echo site_url(); ?>"><i class="fas fa-home"></i> Back to Home</a></li>
                    
                    <?php if (current_user_can('manage_options')): ?>
                    <li><a href="<?php echo site_url('/zonatech-admin/'); ?>" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.3), rgba(167, 139, 250, 0.2)); border-left: 3px solid #8b5cf6;"><i class="fas fa-chart-line"></i> Admin Dashboard</a></li>
                    <?php endif; ?>
                    
                    <li class="nav-divider"></li>
                    
                    <li><a href="#overview" class="active" data-section="overview"><i class="fas fa-tachometer-alt"></i> Overview</a></li>
                    <li><a href="#my-subjects" data-section="my-subjects"><i class="fas fa-book-reader"></i> My Subjects</a></li>
                    <li><a href="<?php echo site_url('/zonatech-past-questions/'); ?>"><i class="fas fa-book"></i> Past Questions</a></li>
                    <li><a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>"><i class="fas fa-credit-card"></i> Scratch Cards</a></li>
                    <li><a href="<?php echo site_url('/zonatech-nin-service/'); ?>"><i class="fas fa-id-card"></i> NIN Service</a></li>
                    
                    <li class="nav-divider"></li>
                    
                    <li><a href="#profile" data-section="profile"><i class="fas fa-user"></i> My Profile</a></li>
                    <li><a href="#payments" data-section="payments"><i class="fas fa-receipt"></i> Payment History</a></li>
                    <li><a href="#activity" data-section="activity"><i class="fas fa-history"></i> Activity</a></li>
                    
                    <li class="nav-divider"></li>
                    
                    <li><a href="#" class="zonatech-logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="dashboard-main">
            <div class="dashboard-header">
                <div class="dashboard-greeting">
                    <h1>Welcome, <?php echo esc_html($user['first_name']); ?>!</h1>
                    <p class="text-muted">Here's an overview of your account</p>
                </div>
                
                <div class="digital-clock glass-effect" id="digital-clock">
                    <div>
                        <span class="clock-time">--:--:--</span>
                        <span class="clock-ampm">--</span>
                    </div>
                    <span class="clock-date">---</span>
                </div>
            </div>
            
            <!-- Overview Section -->
            <div class="dashboard-section-container" id="overview-section">
                <div class="dashboard-stats">
                    <div class="stat-box">
                        <div class="stat-box-icon purple">
                            <i class="fas fa-book-reader"></i>
                        </div>
                        <div class="stat-box-content">
                            <h3><?php echo number_format($stats['subjects']); ?></h3>
                            <p class="text-muted">Subjects Purchased</p>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-box-icon green">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-box-content">
                            <h3><?php echo number_format($stats['quizzes']); ?></h3>
                            <p class="text-muted">Quizzes Taken</p>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-box-icon orange">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-box-content">
                            <h3><?php echo number_format($stats['purchases']); ?></h3>
                            <p class="text-muted">Purchases</p>
                        </div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-box-icon blue">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="stat-box-content">
                            <h3>₦<?php echo number_format($stats['total_spent']); ?></h3>
                            <p class="text-muted">Total Spent</p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="dashboard-section">
                    <div class="section-title">
                        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <a href="<?php echo site_url('/zonatech-past-questions/'); ?>" class="quick-action-btn">
                            <i class="fas fa-book-open"></i>
                            <span>Past Questions</span>
                        </a>
                        <a href="<?php echo site_url('/zonatech-scratch-cards/'); ?>" class="quick-action-btn">
                            <i class="fas fa-credit-card"></i>
                            <span>Buy Scratch Card</span>
                        </a>
                        <a href="<?php echo site_url('/zonatech-nin-service/'); ?>" class="quick-action-btn">
                            <i class="fas fa-id-card"></i>
                            <span>NIN Service</span>
                        </a>
                        <a href="#profile" class="quick-action-btn" data-section="profile">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="dashboard-section">
                    <div class="section-title">
                        <h3><i class="fas fa-history"></i> Recent Activity</h3>
                        <a href="#activity" class="btn btn-ghost btn-sm" data-section="activity">View All</a>
                    </div>
                    <div class="activity-list" id="recent-activity-list">
                        <div class="loading"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
            
            <!-- My Subjects Section -->
            <div class="dashboard-section-container" id="my-subjects-section" style="display: none;">
                <div class="dashboard-section">
                    <div class="section-title">
                        <h3><i class="fas fa-book-reader"></i> My Purchased Categories</h3>
                        <a href="<?php echo site_url('/zonatech-past-questions/'); ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Buy More
                        </a>
                    </div>
                    <div id="my-subjects-list">
                        <div class="loading"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Section -->
            <div class="dashboard-section-container" id="profile-section" style="display: none;">
                <div class="dashboard-section">
                    <div class="section-title">
                        <h3><i class="fas fa-user"></i> Profile Settings</h3>
                    </div>
                    
                    <form id="zonatech-profile-form">
                        <div class="profile-avatar-section">
                            <div class="profile-avatar-wrapper">
                                <img src="<?php echo esc_url($user['avatar']); ?>" alt="Avatar" class="profile-avatar" id="profile-avatar-preview">
                                <label for="profile-avatar-input" class="avatar-upload-btn">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <input type="file" id="profile-avatar-input" name="avatar" accept="image/*" style="display: none;">
                            </div>
                            <div>
                                <h4><?php echo esc_html($user['display_name']); ?></h4>
                                <p class="text-muted">Member since <?php echo date('F Y', strtotime($user['registered'])); ?></p>
                                <p class="text-muted" style="font-size: 0.75rem; margin-top: 0.5rem;"><i class="fas fa-info-circle"></i> Click camera icon to change photo</p>
                            </div>
                        </div>
                        
                        <div class="profile-form">
                            <div class="form-group">
                                <label class="text-white"><i class="fas fa-user"></i> First Name</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" name="first_name" class="form-control form-control-icon" value="<?php echo esc_attr($user['first_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="text-white"><i class="fas fa-user"></i> Last Name</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" name="last_name" class="form-control form-control-icon" value="<?php echo esc_attr($user['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="text-white"><i class="fas fa-envelope"></i> Email Address</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" class="form-control form-control-icon" value="<?php echo esc_attr($user['email']); ?>" disabled>
                                </div>
                                <small class="text-muted"><i class="fas fa-info-circle"></i> Email cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="text-white"><i class="fas fa-phone"></i> Phone Number</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="tel" name="phone" class="form-control form-control-icon" value="<?php echo esc_attr($user['phone']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary mt-2">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
                
                <div class="dashboard-section mt-3">
                    <div class="section-title">
                        <h3><i class="fas fa-lock"></i> Change Password</h3>
                    </div>
                    
                    <form id="zonatech-change-password-form">
                        <div class="profile-form">
                            <div class="form-group">
                                <label class="text-white"><i class="fas fa-lock"></i> Current Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" name="current_password" class="form-control form-control-icon" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="text-white"><i class="fas fa-key"></i> New Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="password" name="new_password" class="form-control form-control-icon" required>
                                </div>
                            </div>
                            
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="text-white"><i class="fas fa-check-circle"></i> Confirm New Password</label>
                                <div class="input-with-icon">
                                    <i class="fas fa-check-circle input-icon"></i>
                                    <input type="password" name="confirm_password" class="form-control form-control-icon" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning mt-2">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Payments Section -->
            <div class="dashboard-section-container" id="payments-section" style="display: none;">
                <div class="dashboard-section">
                    <div class="section-title">
                        <h3><i class="fas fa-receipt"></i> Payment History</h3>
                    </div>
                    <div id="payment-history-list">
                        <div class="loading"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
            
            <!-- Activity Section -->
            <div class="dashboard-section-container" id="activity-section" style="display: none;">
                <div class="dashboard-section">
                    <div class="section-title">
                        <h3><i class="fas fa-history"></i> Activity History</h3>
                    </div>
                    <div id="activity-history-list">
                        <div class="loading"><div class="spinner"></div></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <button class="mobile-menu-toggle">
        <i class="fas fa-bars"></i>
    </button>
    <div class="sidebar-overlay"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Hide loading screen - FAST!
    requestAnimationFrame(function() {
        $('#zonatech-loading-screen').addClass('fade-out');
        setTimeout(function() {
            $('#zonatech-loading-screen').hide();
        }, 100);
    });
    
    // Section navigation
    $('[data-section]').on('click', function(e) {
        e.preventDefault();
        const section = $(this).data('section');
        
        $('.sidebar-nav a').removeClass('active');
        $(this).addClass('active');
        
        $('.dashboard-section-container').hide();
        $('#' + section + '-section').fadeIn();
        
        // Load data for section if needed
        if (section === 'payments') {
            loadPaymentHistory();
        } else if (section === 'activity') {
            loadActivityHistory();
        } else if (section === 'my-subjects') {
            loadMySubjects();
        }
    });
    
    // Check URL hash for direct section navigation
    if (window.location.hash === '#my-subjects') {
        $('[data-section="my-subjects"]').trigger('click');
    }
    
    // Load recent activity on page load
    loadRecentActivity();
    
    function loadMySubjects() {
        const container = $('#my-subjects-list');
        container.html('<div class="loading"><div class="spinner"></div></div>');
        
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_user_subjects',
                nonce: zonatech_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.subjects && response.data.subjects.length > 0) {
                    let html = '<div class="cards-grid">';
                    
                    response.data.subjects.forEach(function(item) {
                        const examColors = {
                            'jamb': '#8b5cf6',
                            'waec': '#22c55e',
                            'neco': '#f59e0b'
                        };
                        const color = examColors[item.exam_type.toLowerCase()] || '#8b5cf6';
                        
                        // Check if this is a category purchase
                        if (item.category) {
                            const categoryIcons = {
                                'science': 'fas fa-flask',
                                'arts': 'fas fa-palette',
                                'business': 'fas fa-briefcase'
                            };
                            const categoryColors = {
                                'science': '#22c55e',
                                'arts': '#8b5cf6',
                                'business': '#f59e0b'
                            };
                            const catIcon = categoryIcons[item.category] || 'fas fa-book';
                            const catColor = categoryColors[item.category] || color;
                            
                            html += `
                                <div class="glass-card subject-card" style="cursor: pointer; border: 2px solid ${catColor}40;" 
                                     onclick="window.location.href='${zonatech_ajax.site_url}/zonatech-past-questions/'">
                                    <div class="d-flex justify-between align-center mb-2">
                                        <span class="badge" style="background: ${color}20; color: ${color};">${item.exam_type.toUpperCase()}</span>
                                        <span class="badge" style="background: ${catColor}; color: white;">${item.category_name}</span>
                                    </div>
                                    <div style="font-size: 2rem; color: ${catColor}; margin: 10px 0;"><i class="${catIcon}"></i></div>
                                    <h4 class="text-white" style="margin: 0.5rem 0;">${item.exam_type.toUpperCase()} ${item.category_name} Subjects</h4>
                                    <p class="text-muted" style="font-size: 0.875rem;">
                                        <i class="fas fa-layer-group"></i> ${item.subjects ? item.subjects.length : 'All'} subjects included
                                    </p>
                                    <p class="text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-calendar-alt"></i> Purchased: ${item.date}
                                    </p>
                                    <p class="text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-clock"></i> Expires: ${item.expires || 'Never'}
                                    </p>
                                    <button class="btn btn-primary btn-sm mt-2" style="width: 100%;">
                                        <i class="fas fa-book-open"></i> View Questions
                                    </button>
                                </div>
                            `;
                        } else {
                            // Legacy individual subject purchase
                            html += `
                                <div class="glass-card subject-card" style="cursor: pointer;" 
                                     onclick="window.location.href='${zonatech_ajax.site_url}/zonatech-past-questions/?exam=${encodeURIComponent(item.exam_type)}&subject=${encodeURIComponent(item.subject)}'">
                                    <div class="d-flex justify-between align-center mb-2">
                                        <span class="badge" style="background: ${color}20; color: ${color};">${item.exam_type.toUpperCase()}</span>
                                        <small class="text-muted">${item.date}</small>
                                    </div>
                                    <h4 class="text-white" style="margin: 0.5rem 0;">${item.subject}</h4>
                                    <p class="text-muted" style="font-size: 0.875rem;">
                                        <i class="fas fa-calendar-alt"></i> Purchased: ${item.date}
                                    </p>
                                    <p class="text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-clock"></i> Expires: ${item.expires || 'Never'}
                                    </p>
                                    <button class="btn btn-primary btn-sm mt-2" style="width: 100%;">
                                        <i class="fas fa-book-open"></i> View Questions
                                    </button>
                                </div>
                            `;
                        }
                    });
                    
                    html += '</div>';
                    container.html(html);
                } else {
                    container.html(`
                        <div class="empty-state">
                            <i class="fas fa-book-reader"></i>
                            <h3 class="text-white">No Subjects Yet</h3>
                            <p>You haven't purchased any subject categories yet. Buy a category to start studying!</p>
                            <a href="${zonatech_ajax.site_url}/zonatech-past-questions/" class="btn btn-primary mt-2">
                                <i class="fas fa-shopping-cart"></i> Browse Past Questions
                            </a>
                        </div>
                    `);
                }
            },
            error: function() {
                container.html('<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Failed to load subjects. Please refresh.</div>');
            }
        });
    }
    
    function loadRecentActivity() {
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_activity_log',
                nonce: zonatech_ajax.nonce,
                page: 1
            },
            success: function(response) {
                if (response.success) {
                    renderActivityList('#recent-activity-list', response.data.activities.slice(0, 5));
                }
            }
        });
    }
    
    function loadActivityHistory() {
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_activity_log',
                nonce: zonatech_ajax.nonce,
                page: 1
            },
            success: function(response) {
                if (response.success) {
                    renderActivityList('#activity-history-list', response.data.activities);
                }
            }
        });
    }
    
    function renderActivityList(container, activities) {
        if (activities.length === 0) {
            $(container).html('<div class="empty-state"><i class="fas fa-history"></i><p>No activity yet.</p></div>');
            return;
        }
        
        let html = '';
        activities.forEach(function(activity) {
            html += `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <p>${activity.description}</p>
                        <span class="activity-time">${activity.time_ago}</span>
                    </div>
                </div>
            `;
        });
        $(container).html(html);
    }
    
    function loadPaymentHistory() {
        $.ajax({
            url: zonatech_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'zonatech_get_payment_history',
                nonce: zonatech_ajax.nonce,
                page: 1
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.payments.length === 0) {
                        $('#payment-history-list').html('<div class="empty-state"><i class="fas fa-receipt"></i><p>No payment history yet.</p></div>');
                        return;
                    }
                    
                    let html = '';
                    response.data.payments.forEach(function(payment) {
                        html += `
                            <div class="payment-item">
                                <div class="payment-item-info">
                                    <div class="payment-item-icon">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                    <div class="payment-item-details">
                                        <h4>${payment.item_name}</h4>
                                        <p>${payment.formatted_date}</p>
                                    </div>
                                </div>
                                <div class="payment-item-amount">
                                    <div class="amount">${payment.formatted_amount}</div>
                                    <span class="status ${payment.status_class}">${payment.status}</span>
                                </div>
                            </div>
                        `;
                    });
                    $('#payment-history-list').html(html);
                }
            }
        });
    }
    
    // Profile avatar preview
    $('#profile-avatar-input').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#profile-avatar-preview').attr('src', e.target.result);
                $('.sidebar-user-avatar').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
            
            // Upload avatar
            var formData = new FormData();
            formData.append('action', 'zonatech_upload_avatar');
            formData.append('nonce', zonatech_ajax.nonce);
            formData.append('avatar', file);
            
            $.ajax({
                url: zonatech_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        if (typeof ZonaTechNotify !== 'undefined') {
                            ZonaTechNotify.success('Profile picture updated!');
                        }
                    } else {
                        if (typeof ZonaTechNotify !== 'undefined') {
                            ZonaTechNotify.error(response.data.message || 'Failed to upload avatar');
                        }
                    }
                },
                error: function() {
                    if (typeof ZonaTechNotify !== 'undefined') {
                        ZonaTechNotify.error('Failed to upload avatar');
                    }
                }
            });
        }
    });
});
</script>