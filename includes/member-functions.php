<?php
/**
 * Class MemberFunctions
 * Handles member functions for the Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MemberFunctions {

    /**
     * Opdater medlems ordrer
     */
    public static function update_user_orders() {
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
                        $order_created = $order->get_date_created()->date('Y-m-d H:i:s');

                        // Indsæt data i tabellen
                        $wpdb->insert(
                            SM_TABLE_NAME,
                            array(
                                'user_id' => $user_id,
                                'order_id' => $order_id,
                                'product_id' => $product_id,
                                'quantity' => $quantity,
                                'created_at' => $order_created
                            ),
                            array('%d', '%d', '%d', '%d', '%s')
                        );
                    }
                }
            }
        }
    }

    /**
     * Tjek om den aktuelle bruger har mindst én af de angivne roller
     * 
     * @param array $roles Array med roller der skal tjekkes
     * @return bool True hvis brugeren har mindst én af de angivne roller, ellers false
     */
    public static function current_user_has_roles($roles) {
        // Tjek om brugeren er logget ind
        if (!is_user_logged_in()) {
            return false;
        }

        $user = wp_get_current_user();
        
        // Tjek om brugeren har mindst én af de angivne roller
        foreach ($roles as $role) {
            if (in_array($role, (array) $user->roles)) {
                return true;
            }
        }
        
        return false;
    }

}

/**
 * Hjælpefunktion til at tjekke brugerroller globalt
 * 
 * @param array $roles Array med roller der skal tjekkes
 * @return bool True hvis brugeren har mindst én af de angivne roller, ellers false
 */
function current_user_has_roles($roles) {
    return MemberFunctions::current_user_has_roles($roles);
}