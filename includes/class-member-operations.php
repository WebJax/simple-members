<?php
/**
 * Class MemberOperations
 * Handles all member functions and user order synchronization for the Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MemberOperations {

    /**
     * Constructor
     */
    public function __construct() {
        // Registrer nye ordrer ved checkout
        add_action('woocommerce_thankyou', array($this, 'store_order_data'), 10, 1);
    }

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

    /**
     * Tilføjer en ny ordre til tabellen, når en ordre er gennemført.
     */
    public function store_order_data($order_id) {
        if (!$order_id) {
            return;
        }

        global $wpdb;
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return; // Kun registrerede brugere
        }

        // Tjek om ordren allerede er gemt
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {SM_TABLE_NAME} WHERE order_id = %d",
            $order_id
        ));

        if (!$exists) {
            foreach ($order->get_items() as $item) {
                /** @var WC_Order_Item_Product $item */
                $product = $item->get_product();
                $product_id = $product ? $product->get_id() : 0;
                $quantity = $item->get_quantity();
                $order_created = $order->get_date_created()->date('Y-m-d H:i:s');

                // Indsæt i databasen
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

    /**
     * Finder medlemmer, der har købt et givent produkt inden for det seneste år.
     *
     * @param int $product_id Produkt-ID der skal tjekkes.
     * @return array Liste over bruger-ID'er der har købt dette produkt inden for de sidste 12 måneder.
     */
    public function get_active_members($product_id) {
        global $wpdb;
        $one_year_ago = date('Y-m-d H:i:s', strtotime('-1 year'));

        $query = $wpdb->prepare(
            "SELECT DISTINCT user_id 
            FROM {SM_TABLE_NAME} 
            WHERE product_id = %d 
            AND created_at >= %s",
            $product_id,
            $one_year_ago
        );

        return $wpdb->get_col($query);
    }

    /**
     * Henter det samlede antal købte produkter inden for det seneste år.
     *
     * @return int Samlet antal produkter solgt det seneste år.
     */
    public function get_total_products_sold_last_year() {
        global $wpdb;
        $one_year_ago = date('Y-m-d H:i:s', strtotime('-1 year'));

        $query = $wpdb->prepare(
            "SELECT SUM(quantity) 
            FROM {SM_TABLE_NAME} 
            WHERE created_at >= %s",
            $one_year_ago
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Henter det samlede antal køb per produkt inden for det seneste år.
     *
     * @return array Associeret array med produkt_id som nøgle og solgt antal som værdi.
     */
    public function get_products_sold_breakdown_last_year() {
        global $wpdb;
        $one_year_ago = date('Y-m-d H:i:s', strtotime('-1 year'));

        $query = $wpdb->prepare(
            "SELECT product_id, SUM(quantity) as total_sold 
            FROM {SM_TABLE_NAME} 
            WHERE created_at >= %s
            GROUP BY product_id",
            $one_year_ago
        );

        $results = $wpdb->get_results($query, ARRAY_A);

        $product_breakdown = array();
        foreach ($results as $row) {
            $product_breakdown[$row['product_id']] = $row['total_sold'];
        }

        return $product_breakdown;
    }

    /**
     * Henter salgsstatistik måned for måned for det seneste år.
     *
     * @return array Associeret array med måneder som nøgler og solgte antal som værdier.
     */
    public function get_monthly_sales_last_year() {
        global $wpdb;

        $one_year_ago = date('Y-m-d', strtotime('-1 year')); // Dato for 1 år siden
        $today = date('Y-m-d'); // Dagens dato
        
        // SQL-forespørgsel for at hente månedlige salg
        $query = $wpdb->prepare(
            "SELECT 
                DATE_FORMAT(created_at, '%%Y-%%m') as month, 
                SUM(quantity) as total_sold 
            FROM 
                {$wpdb->prefix}simple_members_orders
            WHERE 
                created_at BETWEEN %s AND %s 
            GROUP BY month 
            ORDER BY month ASC",
            $one_year_ago,
            $today
        );
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Debug output
        error_log('Monthly sales query: ' . $query);
        error_log('Monthly sales results: ' . print_r($results, true));
        
        // Opret array med 0-værdier for alle måneder i perioden
        $months = array();
        $current = new DateTime($one_year_ago);
        $end = new DateTime($today);
        $interval = DateInterval::createFromDateString('1 month');
        $period = new DatePeriod($current, $interval, $end);
        
        foreach ($period as $dt) {
            $month_key = $dt->format('Y-m');
            $months[$month_key] = 0;
        }
        
        // Tilføj den aktuelle måned også
        $current_month = date('Y-m');
        $months[$current_month] = 0;
        
        // Udfyld med faktiske data
        if (!empty($results)) {
            foreach ($results as $row) {
                $months[$row['month']] = (int) $row['total_sold'];
            }
        }
        
        // Returnér dataene sorteret efter måned
        ksort($months);
        
        return $months;
    }

    /**
     * Henter den akkumulerede medlemsvækst måned for måned for det seneste år.
     * Viser fremgang og tilbagegang i det samlede antal af medlemmer over tid.
     *
     * @param int|array $product_ids Produkt-ID eller array af produkt-IDs som definerer medlemskab
     * @return array Associeret array med måneder som nøgler og akkumulerede medlemstal som værdier
     */
    public function get_accumulated_members_growth($product_ids = null) {
        global $wpdb;

        // Konverter enkelt produkt-ID til array hvis nødvendigt
        if (!is_array($product_ids) && !is_null($product_ids)) {
            $product_ids = array($product_ids);
        }
        
        // To år siden (for at sikre, at vi har et komplet billede)
        $two_years_ago = date('Y-m-d', strtotime('-2 years'));
        $today = date('Y-m-d');
        
        // Forbereder WHERE-betingelse for produkter
        $product_where = '';
        if (!is_null($product_ids) && !empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $product_where = $wpdb->prepare("AND product_id IN ($placeholders) ", $product_ids);
        }
        
        // SQL-forespørgsel til at hente hver brugers første køb dato og seneste fornyelse
        // Dette vil give os "tilgang" og "afgang" af medlemmer
        $query = $wpdb->prepare(
            "SELECT 
                user_id, 
                MIN(created_at) as first_purchase,
                MAX(created_at) as last_purchase
            FROM 
                {$wpdb->prefix}simple_members_orders
            WHERE 
                created_at BETWEEN %s AND %s
                $product_where
            GROUP BY user_id",
            $two_years_ago,
            $today
        );
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return array();
        }
        
        // Initialiser arrays for hver måned
        $start_date = new DateTime($two_years_ago);
        $end_date = new DateTime();
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($start_date, $interval, $end_date);
        
        $monthly_new_members = array();
        $monthly_lost_members = array();
        
        // Initialiser alle måneder med 0 værdi
        foreach ($period as $date) {
            $month_key = $date->format('Y-m');
            $monthly_new_members[$month_key] = 0;
            $monthly_lost_members[$month_key] = 0;
        }
        
        // Tilføj nye medlemmer baseret på første køb
        foreach ($results as $member) {
            $first_date = new DateTime($member->first_purchase);
            $month_key = $first_date->format('Y-m');
            
            if (isset($monthly_new_members[$month_key])) {
                $monthly_new_members[$month_key]++;
            }
            
            // Beregn medlemskabets udløbsdato (1 år efter sidste køb)
            $last_purchase = new DateTime($member->last_purchase);
            $expiry_date = clone $last_purchase;
            $expiry_date->add(new DateInterval('P1Y'));
            
            // Hvis udløbsdatoen ligger i fortiden eller inden for perioden, 
            // registrer medlemmet som tabt i udløbsmåneden
            if ($expiry_date <= $end_date) {
                $expiry_month_key = $expiry_date->format('Y-m');
                if (isset($monthly_lost_members[$expiry_month_key])) {
                    $monthly_lost_members[$expiry_month_key]++;
                }
            }
        }
        
        // Beregn den akkumulerede vækst
        $accumulated_growth = array();
        $total_members = 0;
        
        // Vi tager kun data for det seneste år
        $one_year_ago = date('Y-m', strtotime('-1 year'));
        
        foreach ($monthly_new_members as $month => $new_count) {
            // Spring over måneder før det seneste år
            if ($month < $one_year_ago) {
                $total_members += $new_count - $monthly_lost_members[$month];
                continue;
            }
            
            $total_members += $new_count - $monthly_lost_members[$month];
            $accumulated_growth[$month] = $total_members;
        }
        
        // Debug output
        error_log('Accumulated members growth: ' . print_r($accumulated_growth, true));
        
        return $accumulated_growth;
    }

    /**
     * Henter det månedlige antal nye medlemmer og mistede medlemmer for det seneste år.
     * Kan bruges til at visualisere medlemsflow (tilgang vs. afgang).
     *
     * @param int|array $product_ids Produkt-ID eller array af produkt-IDs som definerer medlemskab
     * @return array Associeret array med måneder og arrays af nye og mistede medlemmer
     */
    public function get_monthly_members_flow($product_ids = null) {
        global $wpdb;

        // Konverter enkelt produkt-ID til array hvis nødvendigt
        if (!is_array($product_ids) && !is_null($product_ids)) {
            $product_ids = array($product_ids);
        }
        
        // To år siden (for at kunne beregne tab af medlemmer præcist)
        $two_years_ago = date('Y-m-d', strtotime('-2 years'));
        $today = date('Y-m-d');
        $one_year_ago = date('Y-m', strtotime('-1 year'));
        
        // Forbereder WHERE-betingelse for produkter
        $product_where = '';
        if (!is_null($product_ids) && !empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $product_where = $wpdb->prepare("AND product_id IN ($placeholders) ", $product_ids);
        }
        
        // SQL-forespørgsel som i get_accumulated_members_growth
        $query = $wpdb->prepare(
            "SELECT 
                user_id, 
                MIN(created_at) as first_purchase,
                MAX(created_at) as last_purchase
            FROM 
                {$wpdb->prefix}simple_members_orders
            WHERE 
                created_at BETWEEN %s AND %s
                $product_where
            GROUP BY user_id",
            $two_years_ago,
            $today
        );
        
        $results = $wpdb->get_results($query);
        
        if (empty($results)) {
            return array();
        }
        
        // Initialiser arrays for hver måned
        $start_date = new DateTime($one_year_ago . '-01');
        $end_date = new DateTime();
        $interval = new DateInterval('P1M');
        $period = new DatePeriod($start_date, $interval, $end_date);
        
        $monthly_flow = array();
        
        // Initialiser alle måneder med 0 værdi
        foreach ($period as $date) {
            $month_key = $date->format('Y-m');
            $monthly_flow[$month_key] = array(
                'new' => 0,
                'lost' => 0
            );
        }
        
        // Tilføj medlemsdata
        foreach ($results as $member) {
            $first_date = new DateTime($member->first_purchase);
            $first_month_key = $first_date->format('Y-m');
            
            if (isset($monthly_flow[$first_month_key])) {
                $monthly_flow[$first_month_key]['new']++;
            }
            
            // Beregn medlemskabets udløbsdato (1 år efter sidste køb)
            $last_purchase = new DateTime($member->last_purchase);
            $expiry_date = clone $last_purchase;
            $expiry_date->add(new DateInterval('P1Y'));
            
            // Hvis udløbsdatoen ligger inden for perioden, 
            // registrer medlemmet som tabt i udløbsmåneden
            if ($expiry_date <= $end_date) {
                $expiry_month_key = $expiry_date->format('Y-m');
                if (isset($monthly_flow[$expiry_month_key])) {
                    $monthly_flow[$expiry_month_key]['lost']++;
                }
            }
        }
        
        return $monthly_flow;
    }

    /**
     * Generer og download CSV-fil med brugerdata
     *
     * @param string $start_date Start dato for data
     * @param string $end_date Slut dato for data
     * @return boolean False hvis ingen data findes
     */
    public function generate_csv($start_date = null, $end_date = null) {
        global $wpdb;
    
        // Standard: sidste 12 måneder
        if (!$start_date) $start_date = date('Y-m-d', strtotime('-1 year'));
        if (!$end_date) $end_date = date('Y-m-d');
    
        // Hent data inden for det valgte interval
        $query = $wpdb->prepare("
            SELECT uo.user_id, uo.order_id, uo.product_id, uo.quantity, uo.created_at,
                u.user_email, u.display_name, um1.meta_value as first_name, um2.meta_value as last_name,
                um3.meta_value as billing_address, um4.meta_value as billing_city,
                um5.meta_value as billing_postcode, um6.meta_value as billing_country,
                p.post_title as product_name, o.payment_method_title
            FROM {SM_TABLE_NAME} uo
            JOIN {$wpdb->users} u ON uo.user_id = u.ID
            JOIN {$wpdb->usermeta} um1 ON uo.user_id = um1.user_id AND um1.meta_key = 'billing_first_name'
            JOIN {$wpdb->usermeta} um2 ON uo.user_id = um2.user_id AND um2.meta_key = 'billing_last_name'
            JOIN {$wpdb->usermeta} um3 ON uo.user_id = um3.user_id AND um3.meta_key = 'billing_address_1'
            JOIN {$wpdb->usermeta} um4 ON uo.user_id = um4.user_id AND um4.meta_key = 'billing_city'
            JOIN {$wpdb->usermeta} um5 ON uo.user_id = um5.user_id AND um5.meta_key = 'billing_postcode'
            JOIN {$wpdb->usermeta} um6 ON uo.user_id = um6.user_id AND um6.meta_key = 'billing_country'
            JOIN {$wpdb->posts} p ON uo.product_id = p.ID
            JOIN {$wpdb->prefix}woocommerce_order_items oi ON uo.order_id = oi.order_id
            JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id AND oim.meta_key = '_payment_method_title'
            JOIN {$wpdb->prefix}posts o ON uo.order_id = o.ID
            WHERE uo.created_at BETWEEN %s AND %s
            GROUP BY uo.id
            ORDER BY uo.created_at DESC",
            $start_date, $end_date
        );
    
        $results = $wpdb->get_results($query, ARRAY_A);
    
        if (empty($results)) {
            return false; // Ingen data i intervallet
        }
    
        // Filnavn
        $file_name = "user_orders_{$start_date}_til_{$end_date}.csv";
    
        // Send header for CSV-download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $file_name);
    
        // Åbn output-bufferen
        $output = fopen('php://output', 'w');
    
        // Skriv kolonneoverskrifter
        fputcsv($output, [
            'Bruger ID', 'Navn', 'E-mail', 'Adresse', 'By', 'Postnummer', 'Land',
            'Ordre ID', 'Dato', 'Betalingsmetode', 'Produktnavn', 'Produkt ID', 'Antal'
        ]);
    
        // Skriv rækker
        foreach ($results as $row) {
            fputcsv($output, [
                $row['user_id'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['user_email'],
                $row['billing_address'],
                $row['billing_city'],
                $row['billing_postcode'],
                $row['billing_country'],
                $row['order_id'],
                $row['created_at'],
                $row['payment_method_title'],
                $row['product_name'],
                $row['product_id'],
                $row['quantity']
            ]);
        }
    
        fclose($output);
        exit;
    }
}

/**
 * Hjælpefunktion til at tjekke brugerroller globalt
 * 
 * @param array $roles Array med roller der skal tjekkes
 * @return bool True hvis brugeren har mindst én af de angivne roller, ellers false
 */
function current_user_has_roles($roles) {
    return MemberOperations::current_user_has_roles($roles);
}