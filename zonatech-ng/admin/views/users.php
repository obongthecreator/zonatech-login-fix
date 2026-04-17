<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// Get all WordPress users with ZonaTech activity
$users = get_users(array(
    'orderby' => 'registered',
    'order' => 'DESC',
    'number' => 50
));

$table_purchases = $wpdb->prefix . 'zonatech_purchases';
$table_quiz = $wpdb->prefix . 'zonatech_quiz_results';
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-groups"></span> Manage Users</h1>
    
    <div class="zonatech-stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon users"><span class="dashicons dashicons-groups"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format(count_users()['total_users']); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-clock"></span></div>
            <div class="stat-content">
                <?php 
                $active_users = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = 'zonatech_last_activity' AND meta_value > %d",
                    time() - (24 * 3600) // Active in last 24 hours
                ));
                ?>
                <h3><?php echo number_format($active_users); ?></h3>
                <p>Active Today</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
            <div class="stat-content">
                <?php 
                $verified_users = $wpdb->get_var(
                    "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'zonatech_verified' AND meta_value = '1'"
                );
                ?>
                <h3><?php echo number_format($verified_users ?? 0); ?></h3>
                <p>Verified Users</p>
            </div>
        </div>
    </div>
    
    <div class="zonatech-admin-section">
        <h2>All Users</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Registered</th>
                    <th>Last Activity</th>
                    <th>Purchases</th>
                    <th>Quizzes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php 
                    $last_activity = get_user_meta($user->ID, 'zonatech_last_activity', true);
                    $purchase_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_purchases WHERE user_id = %d AND status = 'completed'",
                        $user->ID
                    ));
                    $quiz_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_quiz WHERE user_id = %d",
                        $user->ID
                    ));
                    ?>
                    <tr>
                        <td><?php echo $user->ID; ?></td>
                        <td>
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <br><small>@<?php echo esc_html($user->user_login); ?></small>
                        </td>
                        <td><a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a></td>
                        <td><?php echo date('M j, Y', strtotime($user->user_registered)); ?></td>
                        <td>
                            <?php if ($last_activity): ?>
                                <?php echo human_time_diff($last_activity, time()); ?> ago
                            <?php else: ?>
                                <span style="color: #999;">Never</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($purchase_count); ?></td>
                        <td><?php echo number_format($quiz_count); ?></td>
                        <td>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button button-small">
                                <span class="dashicons dashicons-edit" style="vertical-align: middle;"></span> Edit
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>