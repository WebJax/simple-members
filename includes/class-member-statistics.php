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
        ?>
        <div class="sm-admin">
            <h1><?php _e( 'Medlemsstatistik', 'simple-members' ); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="statistics">
                <label for="stats_start_date">Startdato: <input type="date" name="stats_start_date" value="<?php echo isset($_GET['stats_start_date']) ? esc_attr($_GET['stats_start_date']) : date('Y-m-d', strtotime('-1 year')); ?>"></label>
                <label for="stats_end_date">Slutdato: <input type="date" name="stats_end_date" value="<?php echo isset($_GET['stats_end_date']) ? esc_attr($_GET['stats_end_date']) : date('Y-m-d'); ?>"></label>
                <input type="submit" class="button button-primary" value="Opdater">
            </form>
            <canvas id="orderChart"></canvas>
        </div>
        <?php
    }
}