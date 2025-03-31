<?php
/**
 * Class SimpleMembersActivation
 * Handles the activation of the Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class SimpleMembersActivation {
    /**
     * Activation callback
     */
    static function activate() {
        // add boardmember role
        self::add_boardmember_role();

        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Create database tables
        self::create_table();

        self::update_user_orders();
    }

    /**
     * Opretter bestyrelsesmedlem rolle
     */
    static function add_boardmember_role() {
        add_role('boardmember', 'Bestyrelsesmedlem', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => false,
        ));
    }

    /**
     * Opretter databasen til at gemme ordrer
     */
    static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS " . SM_TABLE_NAME . " (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            order_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            quantity INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Finder og gemmer seneste ordre for hver bruger.
     */
    static function update_user_orders() {
        if (!class_exists('WooCommerce')) {
            return; // Sikrer, at WooCommerce er aktivt
        }

        global $wpdb;

        $users = get_users(array('role__in' => array('customer', 'subscriber')));

        foreach ($users as $user) {
            $user_id = $user->ID;

            // Hent seneste ordre
            $orders = wc_get_orders(array(
                'customer_id' => $user_id,
                'limit' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            if (!empty($orders)) {
                $order = $orders[0];
                $order_id = $order->get_id();

                // Tjek om ordren allerede er i tabellen
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM " . SM_TABLE_NAME . " WHERE order_id = %d",
                    $order_id
                ));

                if (!$exists) {
                    foreach ($order->get_items() as $item) {
                        /** @var WC_Order_Item_Product $item */
                        $product_id = $item->get_product_id();
                        $quantity = $item->get_quantity();

                        // Indsæt data i tabellen
                        $wpdb->insert(
                            SM_TABLE_NAME,
                            array(
                                'user_id' => $user_id,
                                'order_id' => $order_id,
                                'product_id' => $product_id,
                                'quantity' => $quantity
                            ),
                            array('%d', '%d', '%d', '%d')
                        );
                    }
                }
            }
        }
    }
}