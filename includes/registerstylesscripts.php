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
    }

    /**
     * Register styles and scripts
     */
    public function register_styles_scripts() {
        // Register styles
        wp_enqueue_style( 'simple-members-style', SM_PLUGIN_URL . 'assets/style.css', array(), SM_PLUGIN_VERSION );
        
        // Register scripts
        wp_enqueue_script( 'simple-members-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true );
        wp_enqueue_script( 'simple-members-script', SM_PLUGIN_URL . 'assets/script.js', array(), SM_PLUGIN_VERSION, true );
    }
}