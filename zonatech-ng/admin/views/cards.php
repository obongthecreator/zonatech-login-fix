<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table_cards = $wpdb->prefix . 'zonatech_scratch_cards';

// Handle bulk add
if (isset($_POST['zonatech_add_cards']) && wp_verify_nonce($_POST['_wpnonce'], 'zonatech_add_cards')) {
    $card_type = sanitize_text_field($_POST['card_type']);
    $quantity = intval($_POST['quantity']);
    
    for ($i = 0; $i < $quantity; $i++) {
        $wpdb->insert($table_cards, array(
            'card_type' => $card_type,
            'pin' => strtoupper($card_type) . '-' . wp_generate_password(12, false, false),
            'serial_number' => strtoupper($card_type) . '-SN-' . wp_generate_password(8, false, false),
            'status' => 'available'
        ));
    }
    echo '<div class="notice notice-success"><p>' . $quantity . ' cards added successfully!</p></div>';
}

// Get stats
$stats = $wpdb->get_results(
    "SELECT card_type, status, COUNT(*) as count 
     FROM $table_cards 
     GROUP BY card_type, status"
);

$card_stats = array();
foreach ($stats as $stat) {
    if (!isset($card_stats[$stat->card_type])) {
        $card_stats[$stat->card_type] = array('available' => 0, 'sold' => 0);
    }
    $card_stats[$stat->card_type][$stat->status] = $stat->count;
}

// Get recent sold cards
$recent_sold = $wpdb->get_results(
    "SELECT c.*, u.display_name as user_name 
     FROM $table_cards c 
     LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
     WHERE c.status = 'sold' 
     ORDER BY c.sold_at DESC 
     LIMIT 20"
);
?>
<div class="wrap zonatech-admin">
    <h1><span class="dashicons dashicons-tickets-alt"></span> Manage Scratch Cards</h1>
    
    <div class="zonatech-stats-grid">
        <?php foreach ($card_stats as $type => $counts): ?>
            <div class="stat-card">
                <h4><?php echo strtoupper(esc_html($type)); ?> Cards</h4>
                <p><span class="available"><?php echo number_format($counts['available']); ?></span> Available</p>
                <p><span class="sold"><?php echo number_format($counts['sold']); ?></span> Sold</p>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="zonatech-admin-section">
        <h2>Add Cards in Bulk</h2>
        <form method="post" class="zonatech-form inline">
            <?php wp_nonce_field('zonatech_add_cards'); ?>
            
            <div class="form-group inline">
                <label>Card Type</label>
                <select name="card_type" required>
                    <option value="waec">WAEC</option>
                    <option value="neco">NECO</option>
                    <option value="jamb">JAMB</option>
                </select>
            </div>
            
            <div class="form-group inline">
                <label>Quantity</label>
                <input type="number" name="quantity" min="1" max="1000" value="100" required>
            </div>
            
            <button type="submit" name="zonatech_add_cards" class="button button-primary">
                Generate Cards
            </button>
        </form>
    </div>
    
    <div class="zonatech-admin-section">
        <h2>Recently Sold Cards</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>PIN</th>
                    <th>Serial Number</th>
                    <th>Buyer</th>
                    <th>Sold At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recent_sold)): ?>
                    <?php foreach ($recent_sold as $card): ?>
                        <tr>
                            <td><?php echo strtoupper(esc_html($card->card_type)); ?></td>
                            <td><code><?php echo esc_html($card->pin); ?></code></td>
                            <td><?php echo esc_html($card->serial_number); ?></td>
                            <td><?php echo esc_html($card->user_name ?? 'Unknown'); ?></td>
                            <td><?php echo esc_html(date('M j, Y g:i A', strtotime($card->sold_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No sold cards yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>