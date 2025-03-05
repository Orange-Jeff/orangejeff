<?php
/**
 * Plugin Name: Frogstar Invoice Manager
 * Description: A plugin to manage and automate invoice generation for Frogstar.
 * Version: 1.0.0
 * Author: Cline
 */

// Prevent direct access to the plugin file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'FROGSTAR_INVOICE_MANAGER_VERSION', '1.0.0' );
define( 'FROGSTAR_INVOICE_MANAGER_PLUGIN_DIR', \\\plugin_dir_path( __FILE__ ) );
define( 'FROGSTAR_INVOICE_MANAGER_PLUGIN_URL', \\\plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once FROGSTAR_INVOICE_MANAGER_PLUGIN_DIR . 'includes/class-frogstar-invoice-manager.php';

// Initialize the plugin
function frogstar_invoice_manager_init() {
    \FrogstarInvoiceManager\Frogstar_Invoice_Manager::instance();
}
\\\add_action( 'plugins_loaded', 'frogstar_invoice_manager_init' );

// Activation hook
function frogstar_invoice_manager_activate() {
    // Actions to perform on plugin activation
    \FrogstarInvoiceManager\Frogstar_Invoice_Manager::activate();
}
\\\register_activation_hook( __FILE__, 'frogstar_invoice_manager_activate' );

// Deactivation hook
function frogstar_invoice_manager_deactivate() {
    // Actions to perform on plugin deactivation
    \FrogstarInvoiceManager\Frogstar_Invoice_Manager::deactivate();
}
\\\register_deactivation_hook( __FILE__, 'frogstar_invoice_manager_deactivate' );
