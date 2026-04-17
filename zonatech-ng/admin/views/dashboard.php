<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get all statistics
$table_purchases = $wpdb->prefix . 'zonatech_purchases';
$table_questions = $wpdb->prefix . 'zonatech_questions';
$table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
$table_cards = $wpdb->prefix . 'zonatech_scratch_cards';
$table_nin = $wpdb->prefix . 'zonatech_nin_requests';

// Revenue statistics
$today_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed' AND DATE(created_at) = %s",
    date('Y-m-d')
)) ?? 0;

$this_week_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed' AND created_at >= %s",
    date('Y-m-d', strtotime('-7 days'))
)) ?? 0;

$this_month_revenue = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(amount), 0) FROM $table_purchases WHERE status = 'completed' AND MONTH(created_at) = %d AND YEAR(created_at) = %d",
    date('n'), date('Y')
)) ?? 0;

// Count pending items
$pending_purchases = $wpdb->get_var("SELECT COUNT(*) FROM $table_purchases WHERE status = 'pending'") ?? 0;

// Questions by exam type
$question_stats = $wpdb->get_results(
    "SELECT exam_type, COUNT(*) as count FROM $table_questions GROUP BY exam_type"
);

// Recent purchases
$recent_purchases = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, u.display_name, u.user_email 
     FROM $table_purchases p 
     LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
     ORDER BY p.created_at DESC 
     LIMIT %d",
    10
));

// Top subjects by purchase
$top_subjects = $wpdb->get_results(
    "SELECT item_name, COUNT(*) as count, SUM(amount) as revenue 
     FROM $table_purchases 
     WHERE status = 'completed' AND purchase_type = 'subject'
     GROUP BY item_name 
     ORDER BY count DESC 
     LIMIT 5"
);

// User registration trend (last 7 days)
$user_trend = array();
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->users} WHERE DATE(user_registered) = %s",
        $date
    ));
    $user_trend[] = array(
        'date' => date('M j', strtotime($date)),
        'count' => $count
    );
}

// Scratch cards available
$available_cards = $wpdb->get_results(
    "SELECT card_type, COUNT(*) as count FROM $table_cards WHERE status = 'available' GROUP BY card_type"
);
?>
<div class="wrap zonatech-admin zonatech-admin-dashboard">
    <h1><span class="dashicons dashicons-welcome-learn-more"></span> ZonaTech NG Dashboard</h1>
    
    <!-- Revenue Overview -->
    <div class="zonatech-section-title">
        <h2><span class="dashicons dashicons-chart-area"></span> Revenue Overview</h2>
    </div>
    <div class="zonatech-stats-grid zonatech-stats-grid-4">
        <div class="stat-card stat-card-primary">
            <div class="stat-icon"><span class="dashicons dashicons-calendar-alt"></span></div>
            <div class="stat-content">
                <h3>₦<?php echo number_format($today_revenue); ?></h3>
                <p>Today's Revenue</p>
            </div>
        </div>
        
        <div class="stat-card stat-card-info">
            <div class="stat-icon"><span class="dashicons dashicons-calendar"></span></div>
            <div class="stat-content">
                <h3>₦<?php echo number_format($this_week_revenue); ?></h3>
                <p>This Week</p>
            </div>
        </div>
        
        <div class="stat-card stat-card-success">
            <div class="stat-icon"><span class="dashicons dashicons-money-alt"></span></div>
            <div class="stat-content">
                <h3>₦<?php echo number_format($this_month_revenue); ?></h3>
                <p>This Month</p>
            </div>
        </div>
        
        <div class="stat-card stat-card-revenue">
            <div class="stat-icon"><span class="dashicons dashicons-vault"></span></div>
            <div class="stat-content">
                <h3>₦<?php echo number_format($total_revenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="zonatech-section-title">
        <h2><span class="dashicons dashicons-dashboard"></span> Platform Statistics</h2>
    </div>
    <div class="zonatech-stats-grid">
        <div class="stat-card">
            <div class="stat-icon users"><span class="dashicons dashicons-groups"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($users_count); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purchases"><span class="dashicons dashicons-cart"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($purchases_count); ?></h3>
                <p>Completed Purchases</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon quizzes"><span class="dashicons dashicons-clipboard"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($quizzes_count); ?></h3>
                <p>Quizzes Taken</p>
            </div>
        </div>
        
        <div class="stat-card stat-card-warning">
            <div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($pending_purchases); ?></h3>
                <p>Pending Payments</p>
            </div>
        </div>
    </div>
    
    <!-- Questions Overview -->
    <div class="zonatech-section-title">
        <h2><span class="dashicons dashicons-book"></span> Questions by Exam Type</h2>
    </div>
    <div class="zonatech-stats-grid zonatech-stats-grid-3">
        <?php 
        $exam_colors = array('jamb' => '#8b5cf6', 'waec' => '#10b981', 'neco' => '#f59e0b');
        foreach ($question_stats as $stat): 
            $color = $exam_colors[strtolower($stat->exam_type)] ?? '#6b7280';
        ?>
            <div class="stat-card" style="border-left: 4px solid <?php echo $color; ?>;">
                <div class="stat-icon" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;"><span class="dashicons dashicons-book-alt"></span></div>
                <div class="stat-content">
                    <h3><?php echo number_format($stat->count); ?></h3>
                    <p><?php echo strtoupper(esc_html($stat->exam_type)); ?> Questions</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Two Column Layout -->
    <div class="zonatech-dashboard-columns">
        <!-- Recent Purchases -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-cart"></span> Recent Purchases</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Item</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recent_purchases)): ?>
                        <?php foreach ($recent_purchases as $purchase): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($purchase->display_name ?? 'Unknown'); ?></strong>
                                    <br><small><?php echo esc_html($purchase->user_email ?? ''); ?></small>
                                </td>
                                <td><?php echo esc_html($purchase->item_name); ?></td>
                                <td>₦<?php echo number_format($purchase->amount); ?></td>
                                <td>
                                    <?php if ($purchase->status === 'completed'): ?>
                                        <span class="status-badge status-success">Completed</span>
                                    <?php elseif ($purchase->status === 'pending'): ?>
                                        <span class="status-badge status-warning">Pending</span>
                                    <?php else: ?>
                                        <span class="status-badge status-error"><?php echo esc_html($purchase->status); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, g:i A', strtotime($purchase->created_at)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No purchases yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Top Subjects & Scratch Cards -->
        <div class="zonatech-admin-section">
            <h2><span class="dashicons dashicons-star-filled"></span> Top Selling Subjects</h2>
            <?php if (!empty($top_subjects)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Sales</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_subjects as $subject): ?>
                            <tr>
                                <td><?php echo esc_html($subject->item_name); ?></td>
                                <td><?php echo number_format($subject->count); ?></td>
                                <td>₦<?php echo number_format($subject->revenue); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="description">No subject sales yet.</p>
            <?php endif; ?>
            
            <h2 style="margin-top: 30px;"><span class="dashicons dashicons-tickets-alt"></span> Available Scratch Cards</h2>
            <?php if (!empty($available_cards)): ?>
                <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <?php foreach ($available_cards as $card): ?>
                        <div class="stat-card small" style="flex: 1; min-width: 100px;">
                            <h4><?php echo strtoupper(esc_html($card->card_type)); ?></h4>
                            <p style="font-size: 20px; font-weight: bold; color: #10b981;"><?php echo number_format($card->count); ?></p>
                            <p style="font-size: 12px; color: #6b7280;">Available</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="description">No scratch cards available. <a href="<?php echo admin_url('admin.php?page=zonatech-cards'); ?>">Add some</a></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="zonatech-admin-section">
        <h2><span class="dashicons dashicons-clock"></span> Recent Activity</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Activity</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_activities)): ?>
                    <?php foreach ($recent_activities as $activity): ?>
                        <tr>
                            <td><?php echo esc_html($activity->user_name ?? 'Unknown'); ?></td>
                            <td><?php echo esc_html($activity->description); ?></td>
                            <td><?php echo esc_html($activity->time_ago); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No recent activity.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Quick Links -->
    <div class="zonatech-admin-section">
        <h2><span class="dashicons dashicons-admin-links"></span> Quick Links</h2>
        <div class="quick-links">
            <a href="<?php echo admin_url('admin.php?page=zonatech-questions'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-book"></span> Manage Questions
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-cards'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-tickets-alt"></span> Manage Scratch Cards
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-users'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-groups"></span> View Users
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-feedback'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-feedback"></span> View Feedback
            </a>
            <a href="<?php echo admin_url('admin.php?page=zonatech-settings'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-admin-settings"></span> Settings
            </a>
            <a href="<?php echo site_url(); ?>" class="button button-secondary button-hero" target="_blank">
                <span class="dashicons dashicons-external"></span> View Site
            </a>
        </div>
    </div>
</div>

<style>
.zonatech-admin-dashboard .zonatech-section-title {
    margin-top: 30px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #8b5cf6;
}
.zonatech-admin-dashboard .zonatech-section-title h2 {
    margin: 0;
    font-size: 18px;
    color: #1d2327;
}
.zonatech-admin-dashboard .zonatech-stats-grid-4 {
    grid-template-columns: repeat(4, 1fr);
}
.zonatech-admin-dashboard .zonatech-stats-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}
.zonatech-admin-dashboard .stat-card-primary {
    border-left: 4px solid #8b5cf6;
    background: linear-gradient(135deg, #f5f3ff, #ede9fe);
}
.zonatech-admin-dashboard .stat-card-info {
    border-left: 4px solid #3b82f6;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
}
.zonatech-admin-dashboard .stat-card-success {
    border-left: 4px solid #10b981;
    background: linear-gradient(135deg, #ecfdf5, #d1fae5);
}
.zonatech-admin-dashboard .stat-card-warning {
    border-left: 4px solid #f59e0b;
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
}
.zonatech-admin-dashboard .stat-card-revenue {
    border-left: 4px solid #ec4899;
    background: linear-gradient(135deg, #fdf2f8, #fce7f3);
}
.zonatech-admin-dashboard .zonatech-dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}
.zonatech-admin-dashboard .status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}
.zonatech-admin-dashboard .status-success {
    background: #d1fae5;
    color: #065f46;
}
.zonatech-admin-dashboard .status-warning {
    background: #fef3c7;
    color: #92400e;
}
.zonatech-admin-dashboard .status-error {
    background: #fee2e2;
    color: #991b1b;
}
.zonatech-admin-dashboard .quick-links {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}
.zonatech-admin-dashboard .quick-links .button-hero {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    font-size: 14px;
}
@media (max-width: 1200px) {
    .zonatech-admin-dashboard .zonatech-stats-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    .zonatech-admin-dashboard .zonatech-dashboard-columns {
        grid-template-columns: 1fr;
    }
}
</style>