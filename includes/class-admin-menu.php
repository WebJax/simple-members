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
            array( 'MemberTable', 'render_members_page' ),
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
            array( 'SubscriptionManager', 'render_page' )
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
}