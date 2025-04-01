<?php
/**
 * Class MemberExport
 * Håndterer eksport af medlemsdata
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MemberExport {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_post_download_user_orders_csv', array( $this, 'download_user_orders_csv' ));
    }

    /**
     * Viser eksportformular
     */
    public static function render_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            echo '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
            return;
        }

        ?>
        <div class="sm-admin">
            <h1><?php _e( 'Eksporter brugerdata', 'simple-members' ); ?></h1>
            <form method="get" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="download_user_orders_csv">
                <label for="csv_start_date">Startdato: <input type="date" name="csv_start_date" value="<?php echo date('Y-m-01'); ?>"></label>
                <label for="csv_end_date">Slutdato: <input type="date" name="csv_end_date" value="<?php echo date('Y-m-d'); ?>"></label>
                <input type="submit" class="button button-primary" value="Download CSV">
            </form>
        </div>
        <?php
    }

    /**
     * Håndterer download af CSV med brugerordrer
     */
    public function download_user_orders_csv() {
        $start_date = isset($_GET['csv_start_date']) ? $_GET['csv_start_date'] : null;
        $end_date = isset($_GET['csv_end_date']) ? $_GET['csv_end_date'] : null;
    
        $member_operations = new MemberOperations();
        $member_operations->generate_csv($start_date, $end_date);
    }
}