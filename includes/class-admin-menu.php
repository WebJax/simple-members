<?php
/**
 * Class AdminMenu
 * Håndterer registrering af admin menuer for Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AdminMenu {
    /**
     * Constructor
     */
    public function __construct() {
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
            array( $this, 'render_page' ),
            'dashicons-groups',
            6
        );

        add_submenu_page(
            'simple_members',
            __( 'Rediger medlemmer', 'simple-members' ),
            __( 'Rediger medlemmer', 'simple-members' ),
            'manage_options',
            'edit_members',
            array( 'MemberTable', 'render_page' )
        );

        add_submenu_page(
            'simple_members',
            __( 'Abonnementer', 'simple-members' ),
            __( 'Abonnementer', 'simple-members' ),
            'manage_options',
            'subscription_manager',
            array( 'SubscriptionManager', 'render_page' )
        );

        add_submenu_page(
            'simple_members',
            __( 'Statistik', 'simple-members' ),
            __( 'Statistik', 'simple-members' ),
            'manage_options',
            'statistics',
            array( 'MemberStatistics', 'render_page' )
        );

        add_submenu_page(
            'simple_members',
            __( 'Hent CSV', 'simple-members' ),
            __( 'Hent CSV', 'simple-members' ),
            'manage_options',
            'get_csv',
            array( 'MemberExport', 'render_page' )
        );

        add_submenu_page(
            'simple_members',
            __( 'Indstillinger', 'simple-members' ),
            __( 'Indstillinger', 'simple-members' ),
            'manage_options',
            'membership_settings',
            array( 'Settings', 'render_page' )
        );
    }
    /**
     * Render the admin page
     */
    public function render_page() {
        // Check if user is logged in and has the required capability
        if ( ! is_user_logged_in() || ! current_user_has_roles( array ('boardmember', 'administrator') ) ) {
            echo '<p>' . __( 'You do not have permission to view this content.', 'simple-members' ) . '</p>';
            return;
        }

        // Render the main admin page
        ?>
        <div class="sm-admin">
            <h1><?php _e( 'Simple Members', 'simple-members' ); ?></h1>
            <p><?php _e( 'Velkommen til Simple Members plugin administration.', 'simple-members' ); ?></p>
        </div>
        <section class="wrap">
            <h2><?php _e( 'Medlemsstatistik', 'simple-members' ); ?></h2>
            <?php
            $stats = new MemberOperations();
            $total = $stats->get_total_products_sold_last_year();
            $indivual[] = $stats->get_products_sold_breakdown_last_year()
            ?>
            <div class="sm-statistics">
                <h3><?php _e( 'Total solgte produkter det sidste år', 'simple-members' ); ?></h3>
                <p><?php echo $total; ?></p>
                <h3><?php _e( 'Solgte produkter opdelt på type', 'simple-members' ); ?></h3>
                <ul>
                    <?php
                    foreach ($indivual as $product) {
                        echo '<li>' . esc_html($product->post_title) . ': ' . esc_html($product->total_sales) . '</li>';
                    }
                    ?>
                </ul>
            </div>
        </section>
        <section class="wrap">
            <h2><?php _e( 'Muligheder', 'simple-members' ); ?></h2>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=edit_members'); ?>" class="button"><?php _e( 'Rediger medlemmer', 'simple-members' ); ?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=subscription_manager'); ?>" class="button"><?php _e( 'Abonnementer', 'simple-members' ); ?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=statistics'); ?>" class="button"><?php _e( 'Statistik', 'simple-members' ); ?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=get_csv'); ?>" class="button"><?php _e( 'Hent CSV', 'simple-members' ); ?></a></li>
                <li><a href="<?php echo admin_url('admin.php?page=membership_settings'); ?>" class="button"><?php _e( 'Indstillinger', 'simple-members' ); ?></a></li>
            </ul>
            <p><?php _e( 'Vælg en mulighed ovenfor for at administrere medlemmer, abonnementer eller indstillinger.', 'simple-members' ); ?></p>
        </section>
        <?php
    }
}