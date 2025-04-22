<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Class for registering and managing admin menu pages in the WordPress dashboard.
 */
class AdminMenus
{
    /**
     * Initializes the admin menu functionality by hooking into WordPress.
     */
    public static function init()
    {
        // Hook the register_admin_pages method to the admin_menu action.
        add_action('admin_menu', [self::class, 'register_admin_pages']);
    }

    /**
     * Registers the main menu and submenu pages for the CoD Simple RCON plugin.
     */
    public static function register_admin_pages()
    {
        // Register the main menu page (parent) for CoD RCON.
        add_menu_page(
            __('CoD RCON', 'codsimplercon'),        // Page title in the browser.
            __('CoD RCON', 'codsimplercon'),        // Menu title in the dashboard.
            'manage_options',                       // Required user capability.
            'cod-rcon',                             // Unique menu slug.
            null,                                   // No callback for the parent menu.
            'dashicons-games',                      // Icon for the menu (WordPress dashicon).
            80                                      // Menu position in the dashboard.
        );

        // Register the CoD RCON submenu page (same slug as parent to make it default).
        add_submenu_page(
            'cod-rcon',                             // Parent menu slug.
            __('CoD RCON', 'codsimplercon'),        // Page title in the browser.
            __('CoD RCON', 'codsimplercon'),        // Menu title in the dashboard.
            'manage_options',                       // Required user capability.
            'cod-rcon',                             // Menu slug (matches parent for default display).
            [Cod_Rcon_Admin_Page::class, 'render_admin_page'] // Callback to render the page content.
        );

        // Register the Ban Manager submenu page.
        error_log('Registering Ban Manager submenu.'); // Debugging log for submenu registration.
        add_submenu_page(
            'cod-rcon',                             // Parent menu slug.
            __('Ban Manager', 'codsimplercon'),     // Page title in the browser.
            __('Ban Manager', 'codsimplercon'),     // Menu title in the dashboard.
            'manage_options',                       // Required user capability.
            'cod-ban-manager',                      // Unique menu slug.
            [Cod_Ban_Manager_Page::class, 'render'] // Callback to render the page content.
        );

        // Register the CSS Editor submenu page.
        add_submenu_page(
            'cod-rcon',                             // Parent menu slug.
            __('CSS Editor', 'codsimplercon'),      // Page title in the browser.
            __('CSS Editor', 'codsimplercon'),      // Menu title in the dashboard.
            'manage_options',                       // Required user capability.
            'cod-css-editor',                       // Unique menu slug.
            [Cod_Css_Editor_Page::class, 'render']  // Callback to render the page content.
        );
    }
}

// Initialize the admin menu functionality when the plugin is loaded.
AdminMenus::init();