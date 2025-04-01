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

        MemberFunctions::update_user_orders();
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
 }