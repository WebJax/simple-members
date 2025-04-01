<?php
/**
 * Class MemberTable
 * Håndterer visning af medlemstabeller og data
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MemberTable {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_get_members_stats', array( $this, 'get_members_stats' ) );
    }

    /**
     * Hovedside for medlemmer
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
            <h1><?php _e( 'Rediger medlemmer', 'simple-members' ); ?></h1>
            <?php 
            // Show members table
            self::show_members_table(); 
            ?>
        </div>
        <?php
    }

    /**
     * Viser medlemstabellen
     */
    public static function show_members_table() {
        ?>
        <section class="wrap">
            <h2>Medlemmer</h2>
            <form method="get">
                <input type="hidden" name="page" value="simple_members">
                <label for="members_start_date">Startdato: <input type="date" name="members_start_date" value="<?php echo isset($_GET['members_start_date']) ? esc_attr($_GET['members_start_date']) : date('Y-m-01'); ?>"></label>
                <label for="members_end_date">Slutdato: <input type="date" name="members_end_date" value="<?php echo isset($_GET['members_end_date']) ? esc_attr($_GET['members_end_date']) : date('Y-m-d'); ?>"></label>
                <input type="submit" class="button button-primary" value="Opdater">
            </form>
            <?php self::display_members_table(); ?>
        </section>
        <?php
    }

    /**
     * Viser tabellen med medlemsdata
     */
    public static function display_members_table() {
        global $wpdb;

        // Hent datoer fra URL
        $start_date = isset($_GET['members_start_date']) ? $_GET['members_start_date'] : date('Y-m-01');
        $end_date = isset($_GET['members_end_date']) ? $_GET['members_end_date'] : date('Y-m-d');
    
        // Query data from SM_TABLE_NAME for the date range
        $query = $wpdb->prepare("
            SELECT o.user_id, o.order_id, o.product_id, o.quantity, o.created_at, 
               u.user_email, u.display_name, 
               um1.meta_value as first_name, 
               um2.meta_value as last_name,
               p.post_title as product_name,
               op.post_status as order_status
            FROM " . SM_TABLE_NAME . " o
            LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON o.user_id = um1.user_id AND um1.meta_key = 'billing_first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON o.user_id = um2.user_id AND um2.meta_key = 'billing_last_name'
            LEFT JOIN {$wpdb->posts} p ON o.product_id = p.ID
            LEFT JOIN {$wpdb->posts} op ON o.order_id = op.ID
            WHERE o.created_at BETWEEN %s AND %s
            ORDER BY o.created_at DESC",
            $start_date, $end_date
        );
    
        $results = $wpdb->get_results($query, ARRAY_A);
    
        if (empty($results)) {
            echo "<p>Ingen data i dette interval.</p>";
            return;
        }
    
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Medlem</th><th>Email</th><th>Ordre ID</th><th>Produkt</th><th>Antal</th><th>Købsdato</th><th>Status</th></tr></thead><tbody>';
    
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row['first_name'] . ' ' . $row['last_name']) . '</td>';
            echo '<td>' . esc_html($row['user_email']) . '</td>';
            echo '<td>' . esc_html($row['order_id']) . '</td>';
            echo '<td>' . esc_html($row['product_name']) . '</td>';
            echo '<td>' . esc_html($row['quantity']) . '</td>';
            echo '<td>' . esc_html($row['created_at']) . '</td>';
            
            // Format order status for display - remove 'wc-' prefix and capitalize
            $status = $row['order_status'];
            if (strpos($status, 'wc-') === 0) {
                $status = substr($status, 3);
            }
            $status_label = wc_get_order_status_name($status);
            
            echo '<td>' . esc_html($status_label) . '</td>';
            echo '</tr>';
        }
    
        echo '</tbody></table>';
    }

    /**
     * AJAX handler til at hente medlemsstatistikker
     */
    public function get_members_stats() {
        global $wpdb;

        $start_date = isset($_GET['members_start_date']) ? $_GET['members_start_date'] : date('Y-m-01');
        $end_date = isset($_GET['members_end_date']) ? $_GET['members_end_date'] : date('Y-m-d');
    
        // Hent data opdelt per dag
        $query = $wpdb->prepare("
            SELECT DATE(created_at) as order_date, COUNT(DISTINCT order_id) as total_orders, SUM(quantity) as total_products
            FROM {SM_TABLE_NAME}
            WHERE created_at BETWEEN %s AND %s
            GROUP BY order_date
            ORDER BY order_date ASC",
            $start_date, $end_date
        );
    
        $results = $wpdb->get_results($query, ARRAY_A);
    
        $labels = [];
        $orders = [];
        $products = [];
    
        foreach ($results as $row) {
            $labels[] = $row['order_date'];
            $orders[] = (int) $row['total_orders'];
            $products[] = (int) $row['total_products'];
        }
    
        wp_send_json([
            'labels' => $labels,
            'orders' => $orders,
            'products' => $products
        ]);
    }
}