<?php
/**
 * Class MemberStatistics
 * Håndterer visning af medlemsstatistik
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MemberStatistics {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_get_members_stats', array( $this, 'get_members_stats' ) );
        add_action('wp_ajax_get_order_stats', array( $this, 'get_order_stats' ) );
    }

    /**
     * AJAX handler for order statistics
     */
    public function get_order_stats() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Ikke tilladelse');
            return;
        }

        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-1 year'));
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
        
        $member_operations = new MemberOperations();
        $monthly_data = $member_operations->get_monthly_sales_last_year();
        
        $data = array(
            'labels' => array_keys($monthly_data),
            'orders' => array_values($monthly_data),
            'products' => array_values($monthly_data)
        );
        
        wp_send_json_success($data);
    }

    /**
     * Hovedside for medlemsstatistik
     * 
     * @return void
     */
    public static function render_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            echo '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
            return;
        }

        $start_date = isset($_GET['stats_start_date']) ? esc_attr($_GET['stats_start_date']) : date('Y-m-d', strtotime('-1 year'));
        $end_date = isset($_GET['stats_end_date']) ? esc_attr($_GET['stats_end_date']) : date('Y-m-d');
        
        // Pass variables to JavaScript
        wp_localize_script('simple-members-script', 'smStats', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'startDate' => $start_date,
            'endDate' => $end_date
        ));
        ?>
        <div class="sm-admin">
            <h1><?php _e( 'Medlemsstatistik', 'simple-members' ); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="statistics">
                <label for="stats_start_date">Startdato: <input type="date" name="stats_start_date" value="<?php echo $start_date; ?>"></label>
                <label for="stats_end_date">Slutdato: <input type="date" name="stats_end_date" value="<?php echo $end_date; ?>"></label>
                <input type="submit" class="button button-primary" value="Opdater">
            </form>
            <canvas id="orderChart"></canvas>
        </div>
        <?php
    }
}