<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Handles server monitoring, including displaying server details and updating via AJAX.
 */
class ServerMonitorHandler
{
    /**
     * Initializes the server monitor by registering AJAX actions and footer script.
     */
    public static function init()
    {
        // Register AJAX handlers for authenticated and unauthenticated users.
        add_action('wp_ajax_codsrcon_monitor_server', [__CLASS__, 'handle_monitor_server']);
        add_action('wp_ajax_nopriv_codsrcon_monitor_server', [__CLASS__, 'handle_monitor_server']);

        // Add monitor script to the footer with high priority.
        add_action('wp_footer', [__CLASS__, 'add_monitor_script'], 20);
    }

    /**
     * Generates HTML for the server monitor interface.
     *
     * @param int $server_id The ID of the server to monitor.
     * @return string The HTML output for the server monitor.
     */
    public static function get_server_monitor($server_id)
    {
        global $wpdb;

        // Define the servers table name.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';

        // Fetch server details.
        $server = $wpdb->get_row(
            $wpdb->prepare("SELECT id, server_hostname, server_type, ip_hostname, port FROM $table_name WHERE id = %d", $server_id),
            ARRAY_A
        );

        // Return error message if server not found.
        if (!$server) {
            return esc_html__('Server information not found.', 'codsimplercon');
        }

        // Generate unique ID for the monitor instance.
        $unique_id = 'server_monitor_' . esc_attr($server_id);

        // Store instance data for use in footer script.
        self::$monitor_instances[] = ['server_id' => $server_id, 'unique_id' => $unique_id];

        // Load custom CSS settings or use defaults.
        $css_settings_file = CODSIMPLERCON_PLUGIN_DIR . 'assets/css/codsrcon-css-settings.php';
        if (file_exists($css_settings_file)) {
            $custom_css = include $css_settings_file;
        } else {
            $custom_css = [
                'font_family' => 'Arial',
                'font_weight' => '400',
                'header_background_color' => '#a5a5a5',
                'header_text_color' => '#000000',
                'header_height' => '28px',
                'game_text_color' => '#000000',
                'game_font_size' => '12px',
                'ip_text_color' => '#000000',
                'ip_font_size' => '12px',
                'gametype_text_color' => '#000000',
                'gametype_font_size' => '12px',
                'image_border_color' => '#000000',
                'image_border_width' => '2px',
                'image_border_radius' => '0px',
                'mapname_text_color' => '#000000',
                'mapname_font_size' => '12px',
                'players_text_color' => '#000000',
                'players_font_size' => '12px',
                'monitor_container_width' => '220px',
                'monitor_container_background_color' => '#ffffff',
                'monitor_container_border_color' => '#ccc',
                'monitor_container_border_width' => '1px',
                'monitor_container_border_radius' => '0px',
                'playerlist_odd_background_color' => '#ffffff',
                'playerlist_even_background_color' => '#f9f9f9',
                'playerlist_odd_text_color' => '#000000',
                'playerlist_odd_font_size' => '12px',
                'playerlist_even_text_color' => '#000000',
                'playerlist_even_font_size' => '12px',
                'playerlist_header_background_color' => '#f2f2f2',
                'playerlist_header_text_color' => '#000000',
                'playerlist_header_font_size' => '12px',
                'overlay_image_url' => plugins_url('/assets/images/cod/icon.png', CODSIMPLERCON_PLUGIN_FILE),
            ];
        }

        // Ensure overlay image URL is set.
        if (!isset($custom_css['overlay_image_url'])) {
            $custom_css['overlay_image_url'] = plugins_url('/assets/images/cod/icon.png', CODSIMPLERCON_PLUGIN_FILE);
        }

        // Build HTML using echo statements to reduce line length.
        ob_start();
        echo '<div id="' . esc_attr($unique_id) . '" class="server-monitor-container" style="';
        echo 'width: ' . esc_attr($custom_css['monitor_container_width']) . ' !important; ';
        echo 'background-color: ' . esc_attr($custom_css['monitor_container_background_color']) . ' !important; ';
        echo 'border: ' . esc_attr($custom_css['monitor_container_border_width']) . ' solid ' . esc_attr($custom_css['monitor_container_border_color']) . ' !important; ';
        echo 'border-radius: ' . esc_attr($custom_css['monitor_container_border_radius']) . ' !important; ';
        echo 'padding: 0px !important; margin-bottom: 0px !important; position: relative !important; ';
        echo 'font-family: ' . esc_attr($custom_css['font_family']) . ' !important; ';
        echo 'font-weight: ' . esc_attr($custom_css['font_weight']) . ' !important;">';

        echo '<div class="server-monitor-header" style="';
        echo 'background-color: ' . esc_attr($custom_css['header_background_color']) . ' !important; ';
        echo 'color: ' . esc_attr($custom_css['header_text_color']) . ' !important; ';
        echo 'height: ' . esc_attr($custom_css['header_height']) . ' !important; ';
        echo 'width: 100% !important; text-align: left !important; margin: 0px !important;">';
        echo '<div class="server-monitor-overlay" style="background-image: url(' . esc_url($custom_css['overlay_image_url']) . '); ';
        echo 'position: absolute !important; top: 3px !important; right: 3px !important; ';
        echo 'width: 48px !important; height: 48px !important; z-index: 10 !important;"></div>';
        echo '<div class="monitor-sv-hostname" style="overflow: hidden !important; ';
        echo 'line-height: ' . esc_attr($custom_css['header_height']) . ' !important; ';
        echo 'width: 100% !important; text-align: left !important; margin: 0px !important; ';
        echo 'padding-left: 5px !important; height: 50px !important;">' . esc_html($server['server_hostname']) . '</div>';
        echo '</div><br>';

        echo '<div class="monitor-gamename" style="color: ' . esc_attr($custom_css['game_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['game_font_size']) . ' !important; ';
        echo 'width: 100% !important; height: 30px !important; text-align: center !important; margin-bottom: 0px !important;">';
        echo esc_html($server['server_type']) . '</div>';
        echo '<div class="server-monitor-ip" style="color: ' . esc_attr($custom_css['ip_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['ip_font_size']) . ' !important; width: 100% !important; text-align: center !important;">';
        echo esc_html($server['ip_hostname'] . ':' . $server['port']) . '</div><br>';
        echo '<div class="server-monitor-gametype" style="color: ' . esc_attr($custom_css['gametype_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['gametype_font_size']) . ' !important; ';
        echo 'width: 100% !important; height: 20px !important; text-align: center !important; margin-bottom: 5px !important;">';
        echo esc_html__('Playing: ', 'codsimplercon') . '<span class="gametype"></span></div>';
        echo '<div class="server-monitor-image-container" style="width: 192px !important; height: 128px !important; ';
        echo 'margin: 5px auto 0px !important; margin-bottom: 10px !important;">';
        echo '<div class="server-monitor-image" style="border: ' . esc_attr($custom_css['image_border_width']) . ' solid ';
        echo esc_attr($custom_css['image_border_color']) . ' !important; border-radius: ';
        echo esc_attr($custom_css['image_border_radius']) . ' !important; width: 100% !important; height: 100% !important; ';
        echo 'background-size: cover !important; background-position: center !important;"></div>';
        echo '</div>';
        echo '<div class="server-monitor-mapname" style="color: ' . esc_attr($custom_css['mapname_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['mapname_font_size']) . ' !important; width: 100% !important; ';
        echo 'text-align: center !important; margin-bottom: 16px !important;"></div>';
        echo '<div class="server-monitor-players" style="color: ' . esc_attr($custom_css['players_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['players_font_size']) . ' !important; width: 100% !important; ';
        echo 'text-align: center !important; margin-bottom: 10px !important;">';
        echo esc_html__('Players: ', 'codsimplercon') . '<span class="monitor-playercount">0/0</span></div>';
        echo '<div class="server-monitor-playerlist" style="width: 100% !important;">';
        echo '<table class="server-monitor-playerlist-table" style="width: 94% !important; border-collapse: collapse !important; ';
        echo 'margin: 2px 0px 4px 6px !important;">';
        echo '<thead><tr style="background-color: ' . esc_attr($custom_css['playerlist_header_background_color']) . ' !important; ';
        echo 'color: ' . esc_attr($custom_css['playerlist_header_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['playerlist_header_font_size']) . ' !important;">';
        echo '<th style="border: 1px solid #ddd !important; padding: 2px !important; text-align: left !important;">';
        echo esc_html__('Name', 'codsimplercon') . '</th>';
        echo '<th style="border: 1px solid #ddd !important; padding: 2px !important; text-align: left !important;">';
        echo esc_html__('Score', 'codsimplercon') . '</th>';
        echo '<th style="border: 1px solid #ddd !important; padding: 2px !important; text-align: left !important;">';
        echo esc_html__('Ping', 'codsimplercon') . '</th></tr></thead>';
        echo '<tbody class="player-list"></tbody>';
        echo '</table>';
        echo '</div>';
        echo '</div>';

        echo '<style type="text/css">';
        echo '#' . esc_attr($unique_id) . ' .server-monitor-playerlist-table .player-row:nth-child(odd) { ';
        echo 'background-color: ' . esc_attr($custom_css['playerlist_odd_background_color']) . ' !important; ';
        echo 'color: ' . esc_attr($custom_css['playerlist_odd_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['playerlist_odd_font_size']) . ' !important; }';
        echo '#' . esc_attr($unique_id) . ' .server-monitor-playerlist-table .player-row:nth-child(even) { ';
        echo 'background-color: ' . esc_attr($custom_css['playerlist_even_background_color']) . ' !important; ';
        echo 'color: ' . esc_attr($custom_css['playerlist_even_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['playerlist_even_font_size']) . ' !important; }';
        echo '#' . esc_attr($unique_id) . ' .server-monitor-playerlist-table thead tr { ';
        echo 'background-color: ' . esc_attr($custom_css['playerlist_header_background_color']) . ' !important; ';
        echo 'color: ' . esc_attr($custom_css['playerlist_header_text_color']) . ' !important; ';
        echo 'font-size: ' . esc_attr($custom_css['playerlist_header_font_size']) . ' !important; }';
        echo '</style>';

        return ob_get_clean();
    }

    /**
     * Stores monitor instances for script generation.
     *
     * @var array
     */
    private static $monitor_instances = [];

    /**
     * Adds JavaScript to the footer to refresh monitor data periodically.
     */
    public static function add_monitor_script()
    {
        // Skip if no monitor instances exist.
        if (empty(self::$monitor_instances)) {
            return;
        }

        // Generate JavaScript for refreshing monitor data.
        echo '<script type="text/javascript">';
        echo 'jQuery(document).ready(function($) {';
        foreach (self::$monitor_instances as $instance) {
            $server_id = esc_attr($instance['server_id']);
            $unique_id = esc_attr($instance['unique_id']);
            echo 'function refreshServerMonitor_' . $unique_id . '() {';
            echo '    loadMonitorData(' . $server_id . ', "' . $unique_id . '");';
            echo '}';
            echo 'refreshServerMonitor_' . $unique_id . '();';
            echo 'setInterval(refreshServerMonitor_' . $unique_id . ', 60000);';
        }
        echo '});';
        echo '</script>';
    }

    /**
     * Handles AJAX request to fetch server monitor data.
     */
    public static function handle_monitor_server()
    {
        global $wpdb;

        // Define the servers table name.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';

        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_monitor_server_nonce', 'nonce');

        // Validate server ID.
        if (!isset($_POST['serverId'])) {
            wp_send_json_error(['data' => __('Server ID not provided', 'codsimplercon')]);
            wp_die();
        }

        $serverId = intval($_POST['serverId']);

        // Fetch server details.
        $server = $wpdb->get_row(
            $wpdb->prepare("SELECT server_hostname, server_type, ip_hostname, port FROM $table_name WHERE id = %d", $serverId),
            ARRAY_A
        );

        if (!$server) {
            wp_send_json_error(['data' => __('Server not found in database', 'codsimplercon')]);
            wp_die();
        }

        try {
            // Query server data.
            $response = codsrcon_process_server_data($serverId, 0);
            $data = json_decode($response, true);

            if (!$data['success']) {
                throw new \Exception($data['error']);
            }

            $server_info = $data['server_info'];
            $players = $data['players'];

            // Resolve game folder from server type.
            $server_type = $server_info['gamename'] ?? $server['server_type'];
            $gamefolder = match ($server_type) {
                'Call of Duty' => 'cod',
                'Call of Duty 2' => 'cod2',
                'Call of Duty 4' => 'cod4',
                'CoD:United Offensive' => 'coduo',
                'Call of Duty: World at War' => 'codwaw',
                default => 'cod',
            };

            // Resolve map alias.
            $mapname_raw = $server_info['mapname'] ?? 'N/A';
            $map_alias = $wpdb->get_var($wpdb->prepare(
                "SELECT am.mp_alias
                 FROM {$wpdb->prefix}callofdutysimplercon_server_maps sm
                 JOIN {$wpdb->prefix}callofdutysimplercon_available_maps am ON sm.map_id = am.id
                 WHERE sm.server_id = %d AND am.mp_name = %s",
                $serverId,
                $mapname_raw
            ));
            $map_display_name = $map_alias ?: $mapname_raw;

            // Resolve gametype alias.
            $gametype_raw = $server_info['g_gametype'] ?? 'N/A';
            $gametype_alias = $wpdb->get_var($wpdb->prepare(
                "SELECT agt.gt_alias
                 FROM {$wpdb->prefix}callofdutysimplercon_server_gts sg
                 JOIN {$wpdb->prefix}callofdutysimplercon_available_gts agt ON sg.gt_id = agt.id
                 WHERE sg.server_id = %d AND agt.gametype = %s AND agt.gamename = %s",
                $serverId,
                $gametype_raw,
                $server_type
            ));
            $gametype_display_name = $gametype_alias ?: $gametype_raw;

            // Prepare response data.
            $response_data = [
                'serverInfo' => [
                    'sv_hostname' => $server_info['sv_hostname'] ?? $server['server_hostname'],
                    'gamename' => $server_info['gamename'] ?? $server['server_type'],
                    'g_gametype' => $gametype_display_name,
                    'mapname' => $map_display_name,
                    'sv_maxclients' => $server_info['sv_maxclients'] ?? '0',
                    'game_folder' => $gamefolder,
                    'mp_name' => $mapname_raw,
                    'ip_hostname' => $server['ip_hostname'],
                    'port' => $server['port'],
                ],
                'players' => $players,
            ];

            wp_send_json_success($response_data);
        } catch (\Exception $e) {
            // Log error and return fallback data.
            error_log('Query Error for Monitor Server ' . $serverId . ': ' . $e->getMessage());

            // Resolve game folder for fallback data.
            $server_type = $server['server_type'];
            $gamefolder = match ($server_type) {
                'Call of Duty' => 'cod',
                'Call of Duty 2' => 'cod2',
                'Call of Duty 4' => 'cod4',
                'CoD:United Offensive' => 'coduo',
                'Call of Duty: World at War' => 'codwaw',
                default => 'cod',
            };

            // Prepare fallback response data.
            $fallback_data = [
                'serverInfo' => [
                    'sv_hostname' => $server['server_hostname'],
                    'gamename' => $server['server_type'],
                    'g_gametype' => 'N/A',
                    'mapname' => 'N/A',
                    'sv_maxclients' => '0',
                    'game_folder' => $gamefolder,
                    'mp_name' => 'N/A',
                    'ip_hostname' => $server['ip_hostname'],
                    'port' => $server['port'],
                ],
                'players' => [],
            ];
            wp_send_json_success($fallback_data);
        }

        wp_die();
    }
}