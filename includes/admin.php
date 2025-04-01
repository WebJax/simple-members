<?php
/**
 * Class SimpleMembersAdmin
 * Handles the shortcodes for the Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimpleMembersAdmin {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_post_download_user_orders_csv', array( $this, 'download_user_orders_csv' ));
        add_action('wp_ajax_get_members_stats', array( $this, 'get_members_stats' ) );
        add_action('admin_init', array( $this, 'retry_renew_buttons' ));
        add_action('admin_notices', array( $this, 'show_admin_notices' ));
        add_action('admin_menu', array( $this, 'register_admin_menu' ) );
    }

    /**
     * Register the admin menu
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'Medlemmer', 'simple-members' ),
            __( 'Medlemmer', 'simple-members' ),
            'manage_options',
            'simple_members',
            array( $this, 'simple_members_admin' ),
            'dashicons-groups',
            6
        );

        add_submenu_page(
            'simple_members',
            __( 'Abonnementer', 'simple-members' ),
            __( 'Håndter abonnementer', 'simple-members' ),
            'manage_options',
            'subscription_manager',
            array( $this, 'render_subscription_manager_page' )
        );

        add_submenu_page(
            'simple_members',
            __( 'Indstillinger', 'simple-members' ),
            __( 'Vælg indstillinger', 'simple-members' ),
            'manage_options',
            'membership_settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Simple Members Admin Page
     *
     * @return string
     */

     public function simple_members_admin() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            return '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
        }

        // Get the current user
        $current_user = wp_get_current_user();
        $username = $current_user->user_firstname . ' ' . $current_user->user_lastname;
        if ( empty( $username ) ) {
            $username = $current_user->user_login;
        }

        ?>
        <div class="sm-admin">
            <h1><?php _e( 'Medlemsadministration', 'simple-members' ); ?></h1>
            <p><?php _e( 'Velkommen '. $username .' til medlemsadministrationen.', 'simple-members' ); ?></p>
            <?php $this->show_csv_form(); ?>
            <?php $this->members_table(); ?>
        </div>
        <?php
    }

    public function show_csv_form() {
        ?>
        <section class="wrap">
            <h2>Eksportér brugerdata</h2>
            <form method="get" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="download_user_orders_csv">
                <label for="csv_start_date">Startdato: <input type="date" name="csv_start_date" value="<?php echo date('Y-m-01'); ?>"></label>
                <label for="csv_end_date">Slutdato: <input type="date" name="csv_end_date" value="<?php echo date('Y-m-d'); ?>"></label>
                <input type="submit" class="button button-primary" value="Download CSV">
            </form>
        </section>
        <?php
    }

    public function download_user_orders_csv () {
        $start_date = isset($_GET['csv_start_date']) ? $_GET['csv_start_date'] : null;
        $end_date = isset($_GET['csv_end_date']) ? $_GET['csv_end_date'] : null;
    
        $export = new UserOrderSync();
        $export->generate_csv($start_date, $end_date);
    }

    public function members_table() {
        ?>
        <section class="wrap">
            <h2>Medlemmer</h2>
            <form method="get">
                <input type="hidden" name="page" value="simple_members">
                <label for="members_start_date">Startdato: <input type="date" name="members_start_date" value="<?php echo isset($_GET['members_start_date']) ? esc_attr($_GET['members_start_date']) : date('Y-m-01'); ?>"></label>
                <label for="members_end_date">Slutdato: <input type="date" name="members_end_date" value="<?php echo isset($_GET['members_end_date']) ? esc_attr($_GET['members_end_date']) : date('Y-m-d'); ?>"></label>
                <input type="submit" class="button button-primary" value="Opdater">
            </form>
            <canvas id="orderChart"></canvas>
            <br>
            <?php $this->display_members_table(); ?>
        </section>
        <?php
    }

    public function display_members_table() {
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
               p.post_title as product_name
            FROM " . SM_TABLE_NAME . " o
            LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
            LEFT JOIN {$wpdb->usermeta} um1 ON o.user_id = um1.user_id AND um1.meta_key = 'billing_first_name'
            LEFT JOIN {$wpdb->usermeta} um2 ON o.user_id = um2.user_id AND um2.meta_key = 'billing_last_name'
            LEFT JOIN {$wpdb->posts} p ON o.product_id = p.ID
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
        echo '<thead><tr><th>Bruger</th><th>Email</th><th>Ordre ID</th><th>Produkt</th><th>Antal</th><th>Dato</th></tr></thead><tbody>';
    
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row['first_name'] . ' ' . $row['last_name']) . '</td>';
            echo '<td>' . esc_html($row['user_email']) . '</td>';
            echo '<td>' . esc_html($row['order_id']) . '</td>';
            echo '<td>' . esc_html($row['product_name']) . '</td>';
            echo '<td>' . esc_html($row['quantity']) . '</td>';
            echo '<td>' . esc_html($row['created_at']) . '</td>';
            echo '</tr>';
        }
    
        echo '</tbody></table>';
    }

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

    public function render_subscription_manager_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            return '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
        }
        // Check if WooCommerce Subscriptions is active
        if ( ! class_exists( 'WC_Subscriptions' ) ) {
            echo "<p>WooCommerce Subscriptions er ikke aktivt.</p>";
            return;
        }
        ?>
        <section class="wrap">
            <h2>Håndtering af abonnementer</h2>
            <form method="get">
                <input type="hidden" name="page" value="subscription_manager">
                <select name="subscription_status">
                    <option value="any" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : '', 'any'); ?>>Alle statuser</option>
                    <option value="active" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : '', 'active'); ?>>Aktiv</option>
                    <option value="on-hold" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : 'on-hold', 'on-hold'); ?>>På hold</option>
                    <option value="cancelled" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : '', 'cancelled'); ?>>Annulleret</option>
                    <option value="pending" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : '', 'pending'); ?>>Afventende</option>
                    <option value="expired" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : '', 'expired'); ?>>Udløbet</option>
                    <option value="pending-cancel" <?php selected(isset($_GET['subscription_status']) ? $_GET['subscription_status'] : '', 'pending-cancel'); ?>>Afventer annullering</option>
                </select>
                <input type="text" name="search" placeholder="Søg på bruger eller abonnement ID" value="<?php echo isset($_GET['search']) ? esc_attr($_GET['search']) : ''; ?>">
                <input type="submit" class="button button-primary" value="Søg">
            </form>
            <br>
            <?php $this->display_subscription_table(); ?>
        </section>
        <?php
    }

    public function display_subscription_table() {
        if (!class_exists('WC_Subscriptions')) {
            echo "<p>WooCommerce Subscriptions er ikke aktivt.</p>";
            return;
        }
    
        $args = ['posts_per_page' => -1];
        
        // Set status filter
        if (isset($_GET['subscription_status']) && $_GET['subscription_status'] !== 'any') {
            $args['subscription_status'] = sanitize_text_field($_GET['subscription_status']);
        } else if (!isset($_GET['subscription_status'])) {
            $args['subscription_status'] = 'on-hold'; // Default to on-hold if not specified
        }
    
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $args['s'] = sanitize_text_field($_GET['search']);
        }
    
        $subscriptions = wcs_get_subscriptions($args);
    
        if (empty($subscriptions)) {
            echo "<p>Ingen abonnementer fundet.</p>";
            return;
        }
    
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>Abonnement ID</th><th>Bruger</th><th>Status</th><th>Næste betaling</th><th>Handlinger</th></tr></thead><tbody>';
    
        foreach ($subscriptions as $subscription) {
            $id = $subscription->get_id();
            $user = get_userdata($subscription->get_user_id());
            $status = wcs_get_subscription_status_name($subscription->get_status());
            $next_payment = $subscription->get_date('next_payment') ?: 'Ingen fornyelse';
    
            echo "<tr>
                <td>#{$id}</td>
                <td>{$user->display_name} ({$user->user_email})</td>
                <td>{$status}</td>
                <td>{$next_payment}</td>
                <td>
                    <form method='post'>
                        <input type='hidden' name='subscription_id' value='{$id}'>
                        <button type='submit' name='retry_payment' class='button'>Genforsøg betaling</button>
                        <button type='submit' name='renew_now' class='button'>Forny nu</button>
                    </form>
                </td>
            </tr>";
        }
    
        echo '</tbody></table>';
    }

    public function retry_renew_buttons() { 
        if (!isset($_POST['subscription_id'])) return;

        $subscription_id = absint($_POST['subscription_id']);
        $subscription = wcs_get_subscription($subscription_id);
    
        if (!$subscription) {
            wp_die('Abonnement ikke fundet.');
        }
    
        if (isset($_POST['retry_payment'])) {
            // Trigger the renewal payment hook
            do_action('woocommerce_scheduled_subscription_payment', $subscription->get_id());
            wp_redirect(admin_url('admin.php?page=subscription_manager&message=retry_success'));
            exit;
        }
    
        if (isset($_POST['renew_now'])) {
            wcs_create_renewal_order($subscription);
            wp_redirect(admin_url('admin.php?page=subscription_manager&message=renew_success'));
            exit;
        }
    }

    public function show_admin_notices() {
        if (isset($_GET['message'])) {
            if ($_GET['message'] == 'retry_success') {
                echo '<div class="notice notice-success"><p>Betalingsforsøg er blevet genstartet.</p></div>';
            } elseif ($_GET['message'] == 'renew_success') {
                echo '<div class="notice notice-success"><p>Abonnement er blevet fornyet.</p></div>';
            }
        }
    }

    public function render_settings_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            return '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
        }
        // Save settings if form is submitted
        if (isset($_POST['action']) && $_POST['action'] == 'save_membership_settings') {
            check_admin_referer('simple_members_nonce');
            $membership_products = isset($_POST['membership_products']) ? $_POST['membership_products'] : [];
            update_option('membership_products', $membership_products);
            echo '<div class="notice notice-success"><p>Indstillinger gemt.</p></div>';
        }
        // Get saved membership products
        $membership_products = get_option('membership_products', []);        
        ?>
        <section class="wrap">
            <h2>Indstillinger</h2>
            <form method="post" action="">
                <!-- Vælg hvilke produkter der skal anvendes som medlemskaber -->
                <label for="membership_products">Vælg medlemskaber:</label>
                <select name="membership_products[]" id="membership_products" multiple>
                    <?php
                    // Hent alle produkter
                    $products = get_posts(array(
                        'post_type' => 'product',
                        'posts_per_page' => -1,
                    ));
                    foreach ($products as $product) {
                        $selected = in_array($product->ID, $membership_products) ? 'selected' : '';
                        echo '<option value="' . esc_attr($product->ID) . '" ' . $selected . '>' . esc_html($product->post_title) . '</option>';
                    }
                    ?>
                </select>
                <p>Vælg de produkter, der skal anvendes som medlemskaber. Hold Ctrl-tasten nede for at vælge flere.</p>
                <input type="hidden" name="action" value="save_membership_settings">
                <input type="hidden" name="page" value="simple_members">
                <input type="hidden" name="simple_members_nonce" value="<?php echo wp_create_nonce('simple_members_nonce'); ?>">
                <input type="submit" class="button button-primary" value="Gem indstillinger">
            </form>
        </section>
        <section class="wrap">
            <h2>Hent alle medlemsordre</h2>
            <?php 
            // Hent alle medlemsordre når der klikke på en knap
            if (isset($_POST['get_all_orders'])) {
                MemberFunctions::update_user_orders();
                echo '<div class="notice notice-success"><p>Alle medlemsordrer er blevet opdateret.</p></div>';
            }
            ?>
            <form method="post">
                <input type="hidden" name="get_all_orders" value="1">
                <input type="submit" class="button button-primary" value="Hent alle medlemsordre">
            </form>
        </section>
        <?php
    }
}