<?php
namespace codsimplercon;

class Shortcodes
{
    public static function init()
    {
        add_shortcode('codsrcon_server_admin_info', [__CLASS__, 'server_admin_info_shortcode']);
        add_shortcode('codsrcon_server_monitor', [__CLASS__, 'server_monitor_shortcode']);
    }

    public static function server_admin_info_shortcode($atts, $content = '', $tag = '')
    {
        // Parse attributes with a default server ID
        $atts = shortcode_atts([
            'server_id' => '1', // Default server ID
        ], $atts, 'codsrcon_server_admin_info');

        $server_id = intval($atts['server_id']);
        if ($server_id <= 0) {
            return '<p>Invalid Server ID</p>';
        }

        // Assuming ServerAdminHandler is already loaded via codsimplercon.php
        return ServerAdminHandler::getServerAdminInfo($server_id); // Pass server_id if the method supports it
    }

    public static function server_monitor_shortcode($atts)
    {
        $atts = shortcode_atts([
            'server_id' => '1', // Default server ID
        ], $atts, 'codsrcon_server_monitor');

        $server_id = intval($atts['server_id']);
        if ($server_id <= 0) {
            return '<p>Invalid Server ID</p>';
        }

        // Use the updated ServerMonitorHandler class method
        return ServerMonitorHandler::get_server_monitor($server_id);
    }
}

// Hook initialization to WordPress 'init' action instead of calling immediately
add_action('init', ['codsimplercon\Shortcodes', 'init']);