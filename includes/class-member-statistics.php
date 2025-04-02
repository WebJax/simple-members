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
        // Registrer AJAX handler til at hente månedlige salgsdata
        add_action('wp_ajax_get_order_stats', array( $this, 'get_order_stats' ) );

        // Registrer AJAX handler til medlemsvækst
        add_action('wp_ajax_get_members_growth', array($this, 'ajax_get_members_growth'));

        // Registrer AJAX handler til medlemsflow
        add_action('wp_ajax_get_members_flow', array($this, 'ajax_get_members_flow'));
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
            'products_sold' => array_values($monthly_data)
        );
        
        wp_send_json_success($data);
    }

        /**
     * AJAX handler for at hente data til den akkumulerede medlemsvækstgraf
     */
    public function ajax_get_members_growth() {
        // Sikkerhedscheck
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Ikke tilstrækkelige rettigheder');
        }
        
        $members_ops = new MemberOperations();
        
        // Få produkt-IDs fra indstillinger
        $product_ids = get_option('simple_members_product_ids', array());
        
        // Hent den akkumulerede medlemsvækst
        $growth_data = $members_ops->get_accumulated_members_growth($product_ids);
        
        wp_send_json_success($growth_data);
    }
    
    /**
     * AJAX handler for at hente data til medlemsflow-grafen
     */
    public function ajax_get_members_flow() {
        // Sikkerhedscheck
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Ikke tilstrækkelige rettigheder');
        }
        
        $members_ops = new MemberOperations();
        
        // Få produkt-IDs fra indstillinger
        $product_ids = get_option('simple_members_product_ids', array());
        
        // Hent medlemsflow data
        $flow_data = $members_ops->get_monthly_members_flow($product_ids);
        
        wp_send_json_success($flow_data);
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
            <!-- Tilføj en ny sektion til den akkumulerede medlemsvækst -->
            <div class="sm-dashboard-card-full">
                <div class="sm-dashboard-card-header">
                    <h3><?php _e('Akkumuleret medlemsudvikling', 'simple-members'); ?></h3>
                    <p><?php _e('Viser den samlede udvikling i medlemstal henover det seneste år', 'simple-members'); ?></p>
                </div>
                <div class="sm-dashboard-card-content">
                    <canvas id="membersGrowthChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Medlems-flow visning for at vise tilgang vs. afgang -->
            <div class="sm-dashboard-card-full">
                <div class="sm-dashboard-card-header">
                    <h3><?php _e('Månedlig medlemsudvikling', 'simple-members'); ?></h3>
                    <p><?php _e('Viser månedlig tilgang og afgang af medlemmer', 'simple-members'); ?></p>
                </div>
                <div class="sm-dashboard-card-content">
                    <canvas id="membersFlowChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
}