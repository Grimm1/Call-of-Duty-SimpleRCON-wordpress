<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Plugin Name: Call of Duty Simple Rcon
 * Description: A simple Rcon plugin for WordPress to manage Call of Duty servers.
 * Version: 1.0.0
 * Author: Grimm, DToX
 * Author URI: https://grimms3dworlds.ddns.net
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: codsimplercon
 * Domain Path: /languages
 */

/**
 * Define plugin constants for version, paths, and file.
 */
define('CODSIMPLERCON_VERSION', '1.0.0');
define('CODSIMPLERCON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CODSIMPLERCON_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CODSIMPLERCON_PLUGIN_FILE', __FILE__);

/**
 * Handles plugin activation, setting up database tables.
 */
function activate()
{
    require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-database.php';
    Database::activate_tables();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\activate');

/**
 * Handles plugin deactivation.
 */
function deactivate()
{
    // Currently no deactivation tasks.
}
register_deactivation_hook(__FILE__, __NAMESPACE__ . '\deactivate');

/**
 * Handles plugin uninstallation, dropping database tables.
 */
function uninstall()
{
    global $wpdb;

    // List of plugin-specific tables to drop.
    $tables = [
        $wpdb->prefix . 'callofdutysimplercon_servers',
        $wpdb->prefix . 'callofdutysimplercon_def_maps',
        $wpdb->prefix . 'callofdutysimplercon_def_gts',
        $wpdb->prefix . 'callofdutysimplercon_available_maps',
        $wpdb->prefix . 'callofdutysimplercon_server_maps',
        $wpdb->prefix . 'callofdutysimplercon_available_gts',
        $wpdb->prefix . 'callofdutysimplercon_server_gts',
    ];

    // Drop each table if it exists.
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS $table");
    }
}
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\uninstall');

/**
 * Loads the plugin's text domain for translations.
 */
function load_textdomain()
{
    load_plugin_textdomain(
        'codsimplercon',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
}
add_action('plugins_loaded', __NAMESPACE__ . '\load_textdomain');

/**
 * Main plugin class implementing the Singleton pattern.
 */
class CodSimpleRcon
{
    /**
     * Singleton instance of the class.
     *
     * @var CodSimpleRcon|null
     */
    private static $instance = null;

    /**
     * Returns the singleton instance of the class.
     *
     * @return CodSimpleRcon The singleton instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        $this->load_includes();
        $this->init();
    }

    /**
     * Loads required plugin include files.
     */
    private function load_includes()
    {
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-database.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-server-query.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-admin-page.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-admin-menu.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-shortcodes.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-ajax-handler.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-server-admin-handler.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-server-monitor-handler.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-css-editor.php';
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-ban-manager-page.php';
    }

    /**
     * Initializes plugin functionality, including asset enqueuing and handlers.
     */
    private function init()
    {
        // Register hooks for asset enqueuing and AJAX handlers.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('init', [$this, 'register_ajax_handlers']);

        // Initialize admin menus and server monitor.
        AdminMenus::init();
        ServerMonitorHandler::init();
    }

    /**
     * Registers AJAX handlers for admin and frontend actions.
     */
    public function register_ajax_handlers()
    {
        // Admin AJAX handlers.
        $admin_handlers = [
            'codsrcon_process_add_or_update_server' => 'handle_add_or_update_server',
            'codsrcon_edit_server' => 'codsrcon_edit_server',
            'codsrcon_delete_server' => 'codsrcon_delete_server',
            'codsrcon_add_map' => 'codsrcon_add_map',
            'codsrcon_remove_map_from_server' => 'codsrcon_remove_map_from_server',
            'codsrcon_add_gt_to_server' => 'codsrcon_add_gt_to_server',
            'codsrcon_remove_gt_from_server' => 'codsrcon_remove_gt_from_server',
            'codsrcon_add_defaults' => 'codsrcon_add_defaults',
            'codsrcon_add_defaults_gt' => 'codsrcon_add_defaults_gt',
            'codsrcon_admin_fetch_maps' => 'codsrcon_admin_fetch_maps',
            'codsrcon_admin_fetch_gts' => 'codsrcon_admin_fetch_gts',
            'codsrcon_get_fresh_nonces' => 'codsrcon_get_fresh_nonces',
        ];
        foreach ($admin_handlers as $action => $method) {
            add_action("wp_ajax_$action", ["codsimplercon\\Cod_Rcon_Admin_Page", $method]);
        }

        // Frontend AJAX handlers.
        $frontend_handlers = [
            'codsrcon_get_server_info' => 'handle_get_server_info',
            'codsrcon_send_rcon_command' => 'handle_send_rcon_command',
            'codsrcon_set_hostname' => 'handle_set_hostname',
            'codsrcon_set_password' => 'handle_set_password',
            'codsrcon_log_ban' => 'handle_log_ban',
            'codsrcon_fetch_maps' => 'handle_fetch_maps',
            'codsrcon_fetch_gts' => 'handle_fetch_gts',
        ];
        foreach ($frontend_handlers as $action => $method) {
            add_action("wp_ajax_$action", ["codsimplercon\\ServerAdminHandler", $method]);
            // Note: Public-facing handlers are commented out by default.
            // add_action("wp_ajax_nopriv_$action", ["codsimplercon\\ServerAdminHandler", $method]);
        }

        // Debug AJAX handler.
        add_action('wp_ajax_codsrcon_debug', [__CLASS__, 'debug_ajax']);
    }

    /**
     * Handles debug AJAX requests for logging POST data.
     */
    public static function debug_ajax()
    {
        error_log('Debug AJAX called with POST: ' . print_r($_POST, true));
        wp_send_json_success(['message' => __('Debug received', 'codsimplercon'), 'post' => $_POST]);
    }

    /**
     * Enqueues frontend assets when shortcodes are present.
     */
    public function enqueue_frontend_assets()
    {
        global $wpdb;

        error_log("Codsrcon: Checking frontend assets enqueue");
        $enqueue = false;

        // Check for shortcodes in post content.
        if (is_singular() || is_front_page()) {
            $post = get_post();
            if (is_a($post, 'WP_Post') && !empty($post->post_content)) {
                error_log("Codsrcon: Checking post content for shortcodes (Post ID: " . ($post->ID ?? 'none') . ")");
                if (
                    has_shortcode($post->post_content, 'codsrcon_server_admin_info') ||
                    has_shortcode($post->post_content, 'codsrcon_server_monitor') ||
                    strpos($post->post_content, '[codsrcon_server_monitor') !== false
                ) {
                    $enqueue = true;
                    error_log("Codsrcon: Found shortcode in post content");
                }
            } else {
                error_log("Codsrcon: No post content available");
            }
        }

        // Check widgets for shortcodes.
        $widget_types = ['widget_text', 'widget_custom_html', 'widget_block'];
        foreach ($widget_types as $widget_type) {
            $widgets = get_option($widget_type, []);
            error_log("Codsrcon: Checking widget type: $widget_type, widgets: " . print_r($widgets, true));
            if (!empty($widgets)) {
                foreach ($widgets as $widget_id => $widget) {
                    $widget_content = '';
                    if ($widget_type === 'widget_block' && !empty($widget['content'])) {
                        $widget_content = $widget['content'];
                    } elseif (is_array($widget) && !empty($widget['text'])) {
                        $widget_content = $widget['text'];
                    }
                    if ($widget_content && (
                        strpos($widget_content, '[codsrcon_server_monitor') !== false ||
                        strpos($widget_content, 'wp:shortcode') !== false
                    )) {
                        $enqueue = true;
                        error_log("Codsrcon: Found codsrcon_server_monitor or shortcode block in $widget_type, widget ID: $widget_id, content: " . substr($widget_content, 0, 500));
                        break 2;
                    }
                }
            }
        }

        // Fallback: Check rendered content.
        ob_start();
        the_content();
        $rendered_content = ob_get_clean();
        if (
            strpos($rendered_content, '[codsrcon_server_monitor') !== false ||
            strpos($rendered_content, 'codsrcon-monitor-container') !== false
        ) {
            $enqueue = true;
            error_log("Codsrcon: Found codsrcon_server_monitor or codsrcon-monitor-container in rendered content");
        }

        // Additional fallback: Check active widgets for Shortcode Block.
        $sidebars = get_option('sidebars_widgets', []);
        error_log("Codsrcon: Checking sidebars_widgets: " . print_r($sidebars, true));
        foreach ($sidebars as $sidebar => $widgets) {
            if (is_array($widgets)) {
                foreach ($widgets as $widget) {
                    if (strpos($widget, 'block-') !== false) {
                        $block_widgets = get_option('widget_block', []);
                        $widget_id = str_replace('block-', '', $widget);
                        if (
                            isset($block_widgets[$widget_id]['content']) &&
                            strpos($block_widgets[$widget_id]['content'], '[codsrcon_server_monitor') !== false
                        ) {
                            $enqueue = true;
                            error_log("Codsrcon: Found codsrcon_server_monitor in block widget, sidebar: $sidebar, widget: $widget");
                            break 2;
                        }
                    }
                }
            }
        }

        // Enqueue assets if shortcodes are found.
        if ($enqueue) {
            error_log("Codsrcon: Enqueuing frontend assets for shortcode");

            // Define fallback image URLs.
            $fallback_map_url = plugins_url('assets/images/cod_fallback_map.png', __FILE__);
            $fallback_icon_url = plugins_url('assets/images/cod_fallback_icon.png', __FILE__);

            // Enqueue styles and scripts.
            wp_enqueue_style(
                'codsrcon-frontend',
                CODSIMPLERCON_PLUGIN_URL . 'assets/css/codsrcon-frontend.css',
                [],
                CODSIMPLERCON_VERSION
            );
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'codsrcon-monitor-js',
                CODSIMPLERCON_PLUGIN_URL . 'assets/js/codsrcon-monitor.js',
                ['jquery'],
                CODSIMPLERCON_VERSION . '.1',
                true
            );
            wp_enqueue_script(
                'codsrcon-admin-handler',
                CODSIMPLERCON_PLUGIN_URL . 'assets/js/codsrcon-admin-handler.js',
                ['jquery'],
                CODSIMPLERCON_VERSION . '.1',
                true
            );

            // Fetch initial server data for the first server.
            $firstServerId = null;
            $initialData = [];
            $servers_table = $wpdb->prefix . 'callofdutysimplercon_servers';
            $servers = $wpdb->get_results("SELECT id FROM $servers_table LIMIT 1", ARRAY_A);
            error_log("Codsrcon: Queried servers table, result: " . print_r($servers, true));

            if ($servers) {
                $firstServerId = $servers[0]['id'];
                error_log("Codsrcon: Processing server data for server ID: $firstServerId");
                $result = json_decode(codsrcon_process_server_data($firstServerId, 0), true);
                error_log("Codsrcon: Server data result: " . print_r($result, true));
                if ($result['success']) {
                    $server_info = $result['server_info'];
                    $players = $result['players'];
                    $mapname_raw = $server_info['mapname'] ?? 'unknown';
                    $gametype_raw = $server_info['g_gametype'] ?? 'unknown';

                    // Fetch map alias.
                    $map_alias = $wpdb->get_var($wpdb->prepare(
                        "SELECT am.mp_alias
                         FROM {$wpdb->prefix}callofdutysimplercon_server_maps sm
                         JOIN {$wpdb->prefix}callofdutysimplercon_available_maps am ON sm.map_id = am.id
                         WHERE sm.server_id = %d AND am.mp_name = %s",
                        $firstServerId,
                        $mapname_raw
                    ));

                    // Fetch gametype alias.
                    $gametype_alias = $wpdb->get_var($wpdb->prepare(
                        "SELECT agt.gt_alias
                         FROM {$wpdb->prefix}callofdutysimplercon_server_gts sg
                         JOIN {$wpdb->prefix}callofdutysimplercon_available_gts agt ON sg.gt_id = agt.id
                         WHERE sg.server_id = %d AND agt.gametype = %s",
                        $firstServerId,
                        $gametype_raw
                    ));

                    $map_display_name = $map_alias ?: $mapname_raw;
                    $gametype_display_name = $gametype_alias ?: $gametype_raw;
                    $mapname_for_image = strtolower(str_replace(' ', '_', $mapname_raw));

                    // Prepare initial data for JavaScript.
                    $initialData = [
                        'sv_hostname' => $server_info['sv_hostname'] ?? 'N/A',
                        'gamename' => $server_info['gamename'] ?? 'N/A',
                        'mapname' => $map_display_name,
                        'gametype' => $gametype_display_name,
                        'playercount' => count($players) . '/' . ($server_info['sv_maxclients'] ?? '0'),
                        'map_image' => plugins_url("assets/images/{$mapname_for_image}.png", CODSIMPLERCON_PLUGIN_FILE),
                        'mapname_raw' => $mapname_raw,
                        'g_gametype' => $gametype_raw,
                        'server_type' => $server_info['gamename'] ?? 'N/A',
                        'players' => $players ?? [],
                    ];
                } else {
                    error_log("Codsrcon: Failed to process server data for server ID: $firstServerId");
                }
            } else {
                error_log("Codsrcon: No servers found in database");
            }

            // Localize script data for monitor.
            wp_localize_script('codsrcon-monitor-js', 'codsrconMonitorAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce_monitor_server' => wp_create_nonce('codsrcon_monitor_server_nonce'),
                'plugin_url' => CODSIMPLERCON_PLUGIN_URL,
                'fallback_map' => $fallback_map_url,
                'fallback_icon' => $fallback_icon_url,
            ]);

            // Localize script data for admin handler.
            wp_localize_script('codsrcon-admin-handler', 'codsrconAdminAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce_get_info' => wp_create_nonce('codsrcon_get_server_info_nonce'),
                'nonce_fetch_maps' => wp_create_nonce('codsrcon_fetch_maps_nonce'),
                'nonce_fetch_gts' => wp_create_nonce('codsrcon_fetch_gts_nonce'),
                'nonce_send_command' => wp_create_nonce('codsrcon_send_command_nonce'),
                'nonce_set_hostname' => wp_create_nonce('codsrcon_set_hostname_nonce'),
                'nonce_set_password' => wp_create_nonce('codsrcon_set_password_nonce'),
                'fallback_image' => $fallback_map_url,
                'serverId' => $firstServerId,
                'initialData' => $initialData,
            ]);
        } else {
            error_log("Codsrcon: No relevant shortcodes found, skipping frontend assets");
        }
    }

    /**
     * Enqueues admin assets for specific plugin pages.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_admin_assets($hook)
    {
        // Define relevant admin hooks.
        $relevant_hooks = [
            'toplevel_page_cod-rcon',
            'cod-rcon_page_cod-ban-manager',
            'cod-rcon_page_cod-css-editor',
        ];

        if (!in_array($hook, $relevant_hooks)) {
            error_log("Codsrcon: Skipping admin assets for irrelevant hook: $hook");
            return;
        }

        error_log("Codsrcon: Enqueuing admin assets for hook: $hook");

        if ($hook === 'toplevel_page_cod-rcon') {
            // Enqueue styles and scripts for the main admin page.
            wp_enqueue_style(
                'codsrcon-admin-style',
                CODSIMPLERCON_PLUGIN_URL . 'assets/css/codsrcon-admin-style.css',
                [],
                CODSIMPLERCON_VERSION
            );
            wp_enqueue_script(
                'codsrcon-admin',
                CODSIMPLERCON_PLUGIN_URL . 'assets/js/codsrcon-admin.js',
                ['jquery'],
                CODSIMPLERCON_VERSION,
                true
            );
            wp_localize_script(
                'codsrcon-admin',
                'codsrconAdminAjax',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce_add_server' => wp_create_nonce('codsrcon_add_server'),
                    'nonce_edit_server' => wp_create_nonce('codsrcon_edit_server'),
                    'nonce_delete_server' => wp_create_nonce('codsrcon_delete_server'),
                    'nonce_fetch_maps' => wp_create_nonce('codsrcon_fetch_maps'),
                    'nonce_fetch_gts' => wp_create_nonce('codsrcon_fetch_gts'),
                    'nonce_add_map' => wp_create_nonce('codsrcon_add_map'),
                    'nonce_remove_map_from_server' => wp_create_nonce('codsrcon_remove_map_from_server'),
                    'nonce_add_gt_to_server' => wp_create_nonce('codsrcon_add_gt_to_server'),
                    'nonce_remove_gt_from_server' => wp_create_nonce('codsrcon_remove_gt_from_server'),
                    'nonce_add_defaults' => wp_create_nonce('codsrcon_add_defaults'),
                    'nonce_add_defaults_gt' => wp_create_nonce('codsrcon_add_defaults_gt'),
                    'nonce_save_server' => wp_create_nonce('codsrcon_save_server'),
                    'nonce_get_info' => wp_create_nonce('codsrcon_get_server_info'),
                    'nonce_send_command' => wp_create_nonce('codsrcon_send_rcon_command'),
                    'nonce_set_hostname' => wp_create_nonce('codsrcon_set_hostname'),
                    'nonce_set_password' => wp_create_nonce('codsrcon_set_password'),
                ]
            );
        } elseif ($hook === 'cod-rcon_page_cod-ban-manager') {
            // Enqueue styles and scripts for the ban manager page.
            wp_enqueue_style(
                'codsrcon-admin-style',
                CODSIMPLERCON_PLUGIN_URL . 'assets/css/codsrcon-admin-style.css',
                [],
                CODSIMPLERCON_VERSION
            );
            wp_enqueue_script(
                'codsrcon-ban-manager',
                CODSIMPLERCON_PLUGIN_URL . 'assets/js/codsrcon-ban-manager.js',
                ['jquery'],
                CODSIMPLERCON_VERSION,
                true
            );
            wp_localize_script(
                'codsrcon-ban-manager',
                'codsrconAdminAjax',
                [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce_delete_server' => wp_create_nonce('codsrcon_delete_server'),
                    'nonce_save_server' => wp_create_nonce('codsrcon_save_server'),
                    'nonce_fetch_maps' => wp_create_nonce('codsrcon_fetch_maps'),
                    'nonce_fetch_gts' => wp_create_nonce('codsrcon_fetch_gts'),
                    'nonce_get_info' => wp_create_nonce('codsrcon_get_server_info'),
                    'nonce_send_command' => wp_create_nonce('codsrcon_send_rcon_command'),
                    'nonce_set_hostname' => wp_create_nonce('codsrcon_set_hostname'),
                    'nonce_set_password' => wp_create_nonce('codsrcon_set_password'),
                ]
            );
        } elseif ($hook === 'cod-rcon_page_cod-css-editor') {
            // Enqueue styles and scripts for the CSS editor page.
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_script(
                'codsrcon-css-editor',
                CODSIMPLERCON_PLUGIN_URL . 'assets/js/codsrcon-css-ed.js',
                ['jquery', 'wp-color-picker'],
                CODSIMPLERCON_VERSION,
                true
            );

            // Load CSS settings and localize script.
            $css_settings_file = CODSIMPLERCON_PLUGIN_DIR . 'assets/css/codsrcon-css-settings.php';
            $custom_css = file_exists($css_settings_file) ? include $css_settings_file : [];
            wp_localize_script(
                'codsrcon-css-editor',
                'codsrconCssEditorSettings',
                [
                    'custom_css' => $custom_css,
                    'image_url' => CODSIMPLERCON_PLUGIN_URL . 'assets/images/cod/mp_carentan.png',
                ]
            );
        }
    }
}

// Instantiate the plugin.
CodSimpleRcon::get_instance();