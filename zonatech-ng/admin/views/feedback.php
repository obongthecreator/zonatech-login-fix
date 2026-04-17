<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_feedback = $wpdb->prefix . 'zonatech_feedback';

// Handle marking as read
if (isset($_GET['mark_read']) && wp_verify_nonce($_GET['_wpnonce'], 'mark_feedback_read')) {
    $feedback_id = intval($_GET['mark_read']);
    $wpdb->update($table_feedback, array('status' => 'read'), array('id' => $feedback_id));
    echo '<div class="notice notice-success is-dismissible"><p>Feedback marked as read.</p></div>';
}

// Handle deletion
if (isset($_GET['delete']) && wp_verify_nonce($_GET['_wpnonce'], 'delete_feedback')) {
    $feedback_id = intval($_GET['delete']);
    $wpdb->delete($table_feedback, array('id' => $feedback_id));
    echo '<div class="notice notice-success is-dismissible"><p>Feedback deleted.</p></div>';
}

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_feedback'") === $table_feedback;

$feedback_items = array();
$unread_count = 0;
$total_count = 0;

if ($table_exists) {
    $feedback_items = $wpdb->get_results("SELECT * FROM $table_feedback ORDER BY created_at DESC LIMIT 100");
    $unread_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_feedback WHERE status = 'unread'");
    $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_feedback");
}
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-feedback"></span> User Feedback</h1>
    
    <div class="zonatech-stats-grid" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="stat-icon" style="background: #e74c3c;"><span class="dashicons dashicons-email"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($unread_count); ?></h3>
                <p>Unread Feedback</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"><span class="dashicons dashicons-format-chat"></span></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_count); ?></h3>
                <p>Total Feedback</p>
            </div>
        </div>
    </div>
    
    <?php if (!$table_exists): ?>
        <div class="notice notice-info">
            <p>No feedback has been submitted yet. The feedback table will be created when the first feedback is submitted.</p>
        </div>
    <?php else: ?>
    
    <div class="zonatech-admin-section">
        <h2>All Feedback</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40px;">ID</th>
                    <th style="width: 30px;">Status</th>
                    <th style="width: 150px;">From</th>
                    <th style="width: 150px;">Subject</th>
                    <th style="width: 80px;">Rating</th>
                    <th>Message</th>
                    <th style="width: 120px;">Date</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($feedback_items)): ?>
                    <tr>
                        <td colspan="8">No feedback received yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($feedback_items as $item): ?>
                        <tr style="<?php echo $item->status === 'unread' ? 'background: #fff3cd;' : ''; ?>">
                            <td><?php echo $item->id; ?></td>
                            <td>
                                <?php if ($item->status === 'unread'): ?>
                                    <span class="dashicons dashicons-email" style="color: #e74c3c;" title="Unread"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #27ae60;" title="Read"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo esc_html($item->name); ?></strong>
                                <br><small><a href="mailto:<?php echo esc_attr($item->email); ?>"><?php echo esc_html($item->email); ?></a></small>
                            </td>
                            <td><?php echo esc_html($item->subject); ?></td>
                            <td>
                                <?php if ($item->rating > 0): ?>
                                    <span style="color: #fbbf24; font-size: 14px;">
                                        <?php echo str_repeat('★', $item->rating); ?><?php echo str_repeat('☆', 5 - $item->rating); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($item->message); ?>">
                                <?php echo esc_html(substr($item->message, 0, 100)); ?><?php echo strlen($item->message) > 100 ? '...' : ''; ?>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($item->created_at)); ?></td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($item->email); ?>?subject=Re: <?php echo urlencode($item->subject); ?>" class="button button-small button-primary" title="Reply">
                                    <span class="dashicons dashicons-email-alt" style="vertical-align: middle;"></span>
                                </a>
                                <?php if ($item->status === 'unread'): ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=zonatech-feedback&mark_read=' . $item->id), 'mark_feedback_read'); ?>" class="button button-small" title="Mark as Read">
                                        <span class="dashicons dashicons-yes" style="vertical-align: middle;"></span>
                                    </a>
                                <?php endif; ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=zonatech-feedback&delete=' . $item->id), 'delete_feedback'); ?>" class="button button-small" style="color: #e74c3c;" title="Delete" onclick="return confirm('Are you sure you want to delete this feedback?');">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php endif; ?>
</div>