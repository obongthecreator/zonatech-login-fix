<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_activity = $wpdb->prefix . 'zonatech_activity_log';

$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

$total = $wpdb->get_var("SELECT COUNT(*) FROM $table_activity");
$total_pages = ceil($total / $per_page);

$activities = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, u.display_name as user_name 
     FROM $table_activity a 
     LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
     ORDER BY a.created_at DESC 
     LIMIT %d OFFSET %d",
    $per_page,
    $offset
));
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-backup"></span> Activity Log</h1>
    
    <p>Total activities: <?php echo number_format($total); ?></p>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 150px;">User</th>
                <th style="width: 120px;">Type</th>
                <th>Description</th>
                <th style="width: 100px;">IP Address</th>
                <th style="width: 150px;">Time</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($activities)): ?>
                <?php foreach ($activities as $activity): ?>
                    <tr>
                        <td><?php echo esc_html($activity->user_name ?? 'Unknown'); ?></td>
                        <td><span class="activity-type type-<?php echo esc_attr($activity->activity_type); ?>">
                            <?php echo esc_html(ucwords(str_replace('_', ' ', $activity->activity_type))); ?>
                        </span></td>
                        <td><?php echo esc_html($activity->description); ?></td>
                        <td><?php echo esc_html($activity->ip_address); ?></td>
                        <td><?php echo esc_html(date('M j, Y g:i A', strtotime($activity->created_at))); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No activity recorded yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $page
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>