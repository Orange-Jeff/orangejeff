<?php

namespace FrogstarInvoiceManager;

/**
 * Main plugin class
 */
class Frogstar_Invoice_Manager {

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function instance() {
        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
            self::$instance->init();
        }

        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    private function init() {
        \add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        \add_menu_page(
            \\\__( 'Frogstar Invoices', 'frogstar-invoice-manager' ),
            \\\__( 'Frogstar Invoices', 'frogstar-invoice-manager' ),
            'manage_options',
            'frogstar-invoices',
            array( $this, 'admin_page_content' ),
            'dashicons-money-alt',
            20
        );
    }

    /**
     * Admin page content
     */
    public function admin_page_content() {
        echo '<div class="wrap">';
            echo '<h1>' . \\\esc_html__( 'Frogstar Invoice Manager', 'frogstar-invoice-manager' ) . '</h1>';
            echo '<p>' . \\\esc_html__( 'Welcome to the Frogstar Invoice Manager plugin!', 'frogstar-invoice-manager' ) . '</p>';
        echo '</div>';
    }

    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Do something on activation
    }

    /**
     * Plugin deactivation hook
     */
    public static function deactivate() {
        // Do something on deactivation
    }
}
