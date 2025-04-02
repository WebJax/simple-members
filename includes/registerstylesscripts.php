<?php
/**
 * Class SimpleMembersRegisterStylesScripts
 * Handles the registration of styles and scripts for the Simple Members plugin.
 * 
 * @package SimpleMembers
 * @since 1.0.0
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SimpleMembersRegisterStylesScripts {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register styles and scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'register_styles_scripts' ) );

        // Register admin styles and scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_styles_scripts' ) );
    }

    /**
     * Register frontend styles and scripts
     */
    public function register_styles_scripts() {
        // Register styles
        wp_enqueue_style( 'simple-members-style', SM_PLUGIN_URL . 'assets/frontend/style.css', array(), time() );
        
        // Register scripts
        wp_enqueue_script( 'simple-members-script', SM_PLUGIN_URL . 'assets/frontend/script.js', array(), SM_PLUGIN_VERSION, true );
    }
    
    /**
     * Register admin styles and scripts
     */
    public function register_admin_styles_scripts() {
        // Register admin styles
        wp_enqueue_style( 'simple-members-admin-style', SM_PLUGIN_URL . 'assets/admin/style.css', array(), time() );

        // Register admin scripts
        wp_enqueue_script( 'simple-members-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
        wp_enqueue_script( 'simple-members-script', SM_PLUGIN_URL . 'assets/admin/script.js', array(), SM_PLUGIN_VERSION, true );
    }
}