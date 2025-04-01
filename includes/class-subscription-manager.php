<?php
/**
 * Class SubscriptionManager
 * Håndterer abonnementer og deres administration
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SubscriptionManager {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array( $this, 'retry_renew_buttons' ));
        add_action('admin_notices', array( $this, 'show_admin_notices' ));
    }

    /**
     * Viser administrationssiden for abonnementer
     */
    public static function render_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            echo '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
            return;
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
            <?php self::display_subscription_table(); ?>
        </section>
        <?php
    }

    /**
     * Viser tabellen med abonnementer
     */
    public static function display_subscription_table() {
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

    /**
     * Håndterer knapper til at genforsøge betaling og forny abonnementer
     */
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

    /**
     * Viser admin notifikationer efter handlinger
     */
    public function show_admin_notices() {
        if (isset($_GET['message'])) {
            if ($_GET['message'] == 'retry_success') {
                echo '<div class="notice notice-success"><p>Betalingsforsøg er blevet genstartet.</p></div>';
            } elseif ($_GET['message'] == 'renew_success') {
                echo '<div class="notice notice-success"><p>Abonnement er blevet fornyet.</p></div>';
            }
        }
    }
}