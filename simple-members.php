<?php
/**
 * Plugin Name: Simple Members
 * Description: A simple membership plugin for WordPress.
 * Version: 1.0.0
 * Author: Jacob Thygesen
 * Plugin URI: https://github.com/jaxweb/simple-members
 * Author URI: https://jaxweb.dk
 * Text Domain: simple-members
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'SM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SM_PLUGIN_FILE', SM_PLUGIN_DIR . 'simple-members.php' );
define( 'SM_PLUGIN_VERSION', '1.0.0' );

global $wpdb;
define( 'SM_TABLE_NAME', $wpdb->prefix . 'simple_members_orders' );

// Include required files
require_once SM_PLUGIN_DIR . 'includes/activation.php';
require_once SM_PLUGIN_DIR . 'includes/deactivation.php';
require_once SM_PLUGIN_DIR . 'includes/member-functions.php';
require_once SM_PLUGIN_DIR . 'includes/registerstylesscripts.php';
require_once SM_PLUGIN_DIR . 'includes/userordersync.php';
require_once SM_PLUGIN_DIR . 'includes/admin.php';

class SimpleMembers {
    public function __construct() {
        // Initialize the plugin
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        // Load the styles and scripts
        new SimpleMembersRegisterStylesScripts();
        // Load the user order sync class
        new UserOrderSync();
        // Load the admin class
        new SimpleMembersAdmin();
    }
}

// Load the activation and deactivation classes
register_activation_hook( SM_PLUGIN_FILE, array ('SimpleMembersActivation', 'activate' ) );
register_deactivation_hook( SM_PLUGIN_FILE, array ('SimpleMembersDeactivation', 'deactivate' ) );

// Initialize the plugin
$simple_members = new SimpleMembers();