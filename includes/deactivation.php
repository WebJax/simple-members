<?php 
/**
 * Class SimpleMembersDeactivation
 * Handles the deactivation of the Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class SimpleMembersDeactivation {
    /**
     * Deactivation callback
     */
    public static function deactivate() {

        // Remove boardmember role
        if ( get_role( 'boardmember' ) ) {
            remove_role( 'boardmember' );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Drop database tables
        self::drop_table();
    }
    /**
     * Drop the database table
     */
    public static function drop_table() {
        global $wpdb;
        $table_name = SM_TABLE_NAME;

        // Drop the table if it exists
        $sql = "DROP TABLE IF EXISTS $table_name;";
        $wpdb->query($sql);
    }
}