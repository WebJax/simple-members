<?php
/**
 * Class Settings
 * Håndterer indstillinger for Simple Members plugin
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {
    /**
     * Constructor
     */
    public function __construct() {
        // Ingen hooks i constructor, da indstillingssiden er statisk
    }

    /**
     * Viser indstillingssiden
     */
    public static function render_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            echo '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
            return;
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
            // Hent alle medlemsordre når der klikkes på en knap
            if (isset($_POST['get_all_orders'])) {
                MemberOperations::update_user_orders();
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