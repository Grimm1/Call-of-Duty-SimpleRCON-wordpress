<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Handles server administration tasks, including displaying server info and processing RCON commands.
 */
class ServerAdminHandler
{
    /**
     * Generates HTML for the server admin interface, including a server selector and control panel.
     *
     * @return string The HTML output for the server admin interface.
     */
    public static function getServerAdminInfo()
    {
        global $wpdb;

        // Define the servers table name.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';

        // Fetch server details for the dropdown.
        $servers = $wpdb->get_results("SELECT id, name, ip_hostname, port, server_type, server_hostname FROM $table_name", ARRAY_A);

        // Start building the HTML output.
        $html = '<div class="cod-server-shortcode-container">';

        // Server selector dropdown.
        $html .= '<select id="cod-server-selector">';
        $html .= '<option value="">' . esc_html__('Select a Call of Duty Server', 'codsimplercon') . '</option>';

        foreach ($servers as $index => $server) {
            // Auto-select the first server.
            $selected = ($index === 0) ? ' selected' : '';
            $html .= '<option value="' . esc_attr($server['id']) . '"' . $selected . '>' . esc_html($server['server_hostname']) . ' (' . esc_html($server['server_type']) . ')</option>';
        }
        $html .= '</select>';

        // Server info display section.
        $html .= '<div id="cod-server-info-display">';
        $html .= '<div class="cod-server-info-row">';
        $html .= '<div class="cod-server-info-image" style="background-image: url(\'' . esc_url(plugins_url('assets/images/cod_fallback_map.png', CODSIMPLERCON_PLUGIN_FILE)) . '\');"></div>';
        $html .= '<div class="cod-server-info-details">';
        $html .= '<table class="cod-server-info-table">';
        $html .= '<tr><td class="label">' . esc_html__('Name', 'codsimplercon') . '</td><td class="sv_hostname"></td></tr>';
        $html .= '<tr><td class="label">' . esc_html__('Game', 'codsimplercon') . '</td><td class="gamename"></td></tr>';
        $html .= '<tr><td class="label">' . esc_html__('Map', 'codsimplercon') . '</td><td class="mapname"></td></tr>';
        $html .= '<tr><td class="label">' . esc_html__('Gametype', 'codsimplercon') . '</td><td class="gametype"></td></tr>';
        $html .= '<tr><td class="label">' . esc_html__('Players', 'codsimplercon') . '</td><td class="playercount"></td></tr>';
        $html .= '</table>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        // Player list and control panel section.
        $html .= '<p style="font-size: 14px !important; text-decoration: underline !important;">' . esc_html__('Players', 'codsimplercon') . '</p>';
        $html .= '<div class="cod-server-details-wrapper">';
        $html .= '<div id="cod-server-details">';
        $html .= esc_html__('Select a server to see details.', 'codsimplercon');
        $html .= '</div>';
        $html .= '<div id="cod-server-details-right">';
        $html .= '<div class="cod-rcon-buttons">';
        $html .= '<button id="restart-map" disabled>' . esc_html__('Restart Map', 'codsimplercon') . '</button>';
        $html .= '<button id="fast-restart" disabled style="display: none;">' . esc_html__('Fast Restart', 'codsimplercon') . '</button>';
        $html .= '<button id="map-rotate" disabled>' . esc_html__('Map Rotate', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div class="cod-control-row">';
        $html .= '<select id="cod-player-selector" disabled><option value="">' . esc_html__('No players', 'codsimplercon') . '</option></select>';
        $html .= '<button id="cod-kick-btn" class="cod-rcon-btn" disabled>' . esc_html__('Kick Player', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div class="cod-control-row">';
        $html .= '<select id="cod-ban-selector" disabled><option value="">' . esc_html__('No players', 'codsimplercon') . '</option></select>';
        $html .= '<button id="cod-ban-btn" class="cod-rcon-btn" disabled>' . esc_html__('Ban Player', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div class="cod-control-row">';
        $html .= '<select id="cod-gametype-selector" disabled><option value="">' . esc_html__('No gametypes available', 'codsimplercon') . '</option></select>';
        $html .= '<button id="cod-changegametype-btn" class="cod-rcon-btn" disabled>' . esc_html__('Gametype', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div class="cod-control-row">';
        $html .= '<select id="cod-map-selector" disabled><option value="">' . esc_html__('No maps available', 'codsimplercon') . '</option></select>';
        $html .= '<button id="cod-changemap-btn" class="cod-rcon-btn" disabled>' . esc_html__('Change Map', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div class="cod-control-row">';
        $html .= '<input type="text" id="cod-sv-hostname-input" placeholder="' . esc_attr__('Enter server hostname', 'codsimplercon') . '" disabled>';
        $html .= '<button id="cod-set-hostname-btn" class="cod-rcon-btn" disabled>' . esc_html__('Set Hostname', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div class="cod-control-row">';
        $html .= '<input type="text" id="cod-server-password-input" placeholder="' . esc_attr__('Enter server password', 'codsimplercon') . '" disabled>';
        $html .= '<button id="cod-set-password-btn" class="cod-rcon-btn" disabled>' . esc_html__('Set Password', 'codsimplercon') . '</button>';
        $html .= '</div>';
        $html .= '<div style="font-size: 12px; color: #666; margin-top: 5px;">';
        $html .= esc_html__('Note: Leave the password field blank to remove the server password.', 'codsimplercon');
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Handles AJAX request to fetch server information, including map, gametype, and player data.
     */
    public static function handle_get_server_info()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_get_server_info_nonce', 'nonce');

        // Sanitize and validate server ID.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        if (!$server_id) {
            wp_send_json_error(['message' => __('Invalid server ID', 'codsimplercon')]);
        }

        // Query server data using external function.
        $result = json_decode(codsrcon_process_server_data($server_id, 0), true);
        if (!$result['success']) {
            wp_send_json_error(['message' => __('Server query failed: ', 'codsimplercon') . $result['error']]);
        }

        global $wpdb;
        $server_info = $result['server_info'];
        $players = $result['players'];
        $mapname_raw = $server_info['mapname'] ?? 'unknown';
        $gametype_raw = $server_info['g_gametype'] ?? 'unknown';

        // Fetch map alias for display.
        $map_alias = $wpdb->get_var($wpdb->prepare(
            "SELECT am.mp_alias
             FROM {$wpdb->prefix}callofdutysimplercon_server_maps sm
             JOIN {$wpdb->prefix}callofdutysimplercon_available_maps am ON sm.map_id = am.id
             WHERE sm.server_id = %d AND am.mp_name = %s",
            $server_id,
            $mapname_raw
        ));

        // Fetch server type.
        $server_row = $wpdb->get_row($wpdb->prepare(
            "SELECT server_type FROM {$wpdb->prefix}callofdutysimplercon_servers WHERE id = %d",
            $server_id
        ), ARRAY_A);

        // Fetch gametype alias for display.
        $gametype_alias = $wpdb->get_var($wpdb->prepare(
            "SELECT agt.gt_alias
             FROM {$wpdb->prefix}callofdutysimplercon_server_gts sg
             JOIN {$wpdb->prefix}callofdutysimplercon_available_gts agt ON sg.gt_id = agt.id
             WHERE sg.server_id = %d AND agt.gametype = %s AND agt.gamename = %s",
            $server_id,
            $gametype_raw,
            $server_row['server_type']
        ));

        // Use alias if available, otherwise fall back to raw names.
        $map_display_name = $map_alias ?: $mapname_raw;
        $gametype_display_name = $gametype_alias ?: $gametype_raw;
        $mapname_for_image = strtolower(str_replace(' ', '_', $mapname_raw));
        $server_type = $server_row['server_type'];

        // Determine game folder for map images based on server type.
        $gamefolder = match ($server_type) {
            'Call of Duty' => 'cod',
            'Call of Duty 2' => 'cod2',
            'Call of Duty 4' => 'cod4',
            'CoD:United Offensive' => 'coduo',
            'Call of Duty: World at War' => 'codwaw',
            default => 'cod',
        };

        // Prepare response data.
        $response = [
            'sv_hostname' => AjaxHandler::format_cod_hostname($server_info['sv_hostname'] ?? 'N/A'),
            'gamename' => $server_info['gamename'] ?? 'N/A',
            'mapname' => $map_display_name,
            'gametype' => $gametype_display_name,
            'playercount' => count($players) . '/' . ($server_info['sv_maxclients'] ?? '0'),
            'map_image' => esc_url(plugins_url("assets/images/{$gamefolder}/{$mapname_for_image}.png", CODSIMPLERCON_PLUGIN_FILE)),
            'mapname_raw' => $mapname_raw,
            'g_gametype' => $gametype_raw,
            'server_type' => $server_type ?? $server_info['gamename'],
            'players' => $players ?? [],
        ];

        wp_send_json_success($response);
    }

    /**
     * Handles AJAX request to fetch available maps for a server.
     */
    public static function handle_fetch_maps()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_fetch_maps_nonce', 'nonce');

        // Sanitize and validate server ID.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        if (!$server_id) {
            error_log("handle_fetch_maps: Invalid server ID");
            wp_send_json_error(['message' => __('Invalid server ID', 'codsimplercon')]);
        }

        global $wpdb;

        // Fetch available maps for the server.
        $maps = $wpdb->get_results($wpdb->prepare(
            "SELECT am.mp_name, am.mp_alias
             FROM {$wpdb->prefix}callofdutysimplercon_available_maps am
             JOIN {$wpdb->prefix}callofdutysimplercon_server_maps sm ON am.id = sm.map_id
             WHERE sm.server_id = %d",
            $server_id
        ), ARRAY_A);

        if (empty($maps)) {
            error_log("handle_fetch_maps: No maps found for server_id $server_id");
            wp_send_json_error(['message' => __('No maps available for this server', 'codsimplercon')]);
        }

        // Format map list for response.
        $map_list = array_map(function ($map) {
            return [
                'name' => $map['mp_name'],
                'alias' => $map['mp_alias'] ?: $map['mp_name'],
            ];
        }, $maps);

        wp_send_json_success(['maps' => $map_list]);
    }

    /**
     * Handles AJAX request to fetch available game types for a server.
     */
    public static function handle_fetch_gts()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_fetch_gts_nonce', 'nonce');

        // Sanitize and validate server ID.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        if (!$server_id) {
            error_log("handle_fetch_gts: Invalid server ID");
            wp_send_json_error(['message' => __('Invalid server ID', 'codsimplercon')]);
        }

        global $wpdb;

        // Fetch available game types for the server.
        $gametypes = $wpdb->get_results($wpdb->prepare(
            "SELECT ag.gametype, ag.gt_alias
             FROM {$wpdb->prefix}callofdutysimplercon_available_gts ag
             JOIN {$wpdb->prefix}callofdutysimplercon_server_gts sg ON ag.id = sg.gt_id
             WHERE sg.server_id = %d",
            $server_id
        ), ARRAY_A);

        if (empty($gametypes)) {
            error_log("handle_fetch_gts: No gametypes found for server_id $server_id");
            wp_send_json_error(['message' => __('No gametypes available for this server', 'codsimplercon')]);
        }

        // Format game type list for response.
        $gt_list = array_map(function ($gt) {
            return [
                'name' => $gt['gametype'],
                'alias' => $gt['gt_alias'] ?: $gt['gametype'],
            ];
        }, $gametypes);

        wp_send_json_success(['gametypes' => $gt_list]);
    }

    /**
     * Handles AJAX request to send an RCON command to a server.
     */
    public static function handle_send_rcon_command()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_send_command_nonce', 'nonce');

        // Sanitize and validate inputs.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        $command = isset($_POST['command']) ? sanitize_text_field($_POST['command']) : '';

        if (!$server_id || !$command) {
            wp_send_json_error(['message' => __('Invalid server ID or command', 'codsimplercon')]);
        }

        // Execute RCON command.
        $response = json_decode(codsrcon_process_server_data($server_id, 1, $command), true);
        if ($response['success']) {
            wp_send_json_success(['message' => __('Command executed: ', 'codsimplercon') . $response['response']]);
        } else {
            wp_send_json_error(['message' => $response['error']]);
        }
    }

    /**
     * Handles AJAX request to set the server hostname.
     */
    public static function handle_set_hostname()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_set_hostname_nonce', 'nonce');

        // Sanitize and validate inputs.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        $sv_hostname = isset($_POST['sv_hostname']) ? sanitize_text_field($_POST['sv_hostname']) : '';

        if (!$server_id || !$sv_hostname) {
            wp_send_json_error(['message' => __('Invalid server ID or hostname', 'codsimplercon')]);
        }

        // Set server hostname via RCON.
        $response = json_decode(codsrcon_process_server_data($server_id, 1, "sv_hostname \"$sv_hostname\""), true);
        if ($response['success']) {
            global $wpdb;

            // Update hostname in the database.
            $wpdb->update(
                $wpdb->prefix . 'callofdutysimplercon_servers',
                ['server_hostname' => $sv_hostname],
                ['id' => $server_id],
                ['%s'],
                ['%d']
            );

            wp_send_json_success([
                'message' => __('Hostname updated successfully', 'codsimplercon'),
                'sv_hostname' => AjaxHandler::format_cod_hostname($sv_hostname),
            ]);
        } else {
            wp_send_json_error(['message' => $response['error']]);
        }
    }

    /**
     * Handles AJAX request to set or remove the server password.
     */
    public static function handle_set_password()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_set_password_nonce', 'nonce');

        // Sanitize and validate inputs.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';

        if (!$server_id) {
            wp_send_json_error(['message' => __('Invalid server ID', 'codsimplercon')]);
        }

        // Set or remove password via RCON.
        $command = $password ? "g_password \"$password\"" : "g_password \"\"";
        $response = json_decode(codsrcon_process_server_data($server_id, 1, $command), true);
        if ($response['success']) {
            $message = $password ? sprintf(__('Password set to \'%s\'', 'codsimplercon'), $password) : __('Password removed', 'codsimplercon');
            wp_send_json_success(['message' => $message]);
        } else {
            wp_send_json_error(['message' => $response['error']]);
        }
    }

    /**
     * Handles AJAX request to log a player ban in the database.
     */
    public static function handle_log_ban()
    {
        // Verify the AJAX nonce for security.
        check_ajax_referer('codsrcon_send_command_nonce', 'nonce');

        // Sanitize and validate inputs.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        $ban_name = isset($_POST['ban_name']) ? sanitize_text_field($_POST['ban_name']) : '';

        if (!$server_id || !$ban_name) {
            error_log("handle_log_ban: Invalid server ID ($server_id) or ban name ($ban_name)");
            wp_send_json_error(['message' => __('Invalid server ID or ban name', 'codsimplercon')]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'callofdutysimplercon_bans';

        // Insert ban record into the database.
        $result = $wpdb->insert(
            $table_name,
            [
                'server_id' => $server_id,
                'ban_name' => $ban_name,
                // ban_date defaults to CURRENT_TIMESTAMP
            ],
            ['%d', '%s']
        );

        if ($result !== false && $wpdb->insert_id) {
            wp_send_json_success(['message' => sprintf(__('Ban logged for %s on server %d', 'codsimplercon'), $ban_name, $server_id)]);
        } else {
            error_log("handle_log_ban: Failed to insert ban - Server ID: $server_id, Ban Name: $ban_name, Error: " . $wpdb->last_error);
            wp_send_json_error(['message' => __('Failed to log ban', 'codsimplercon')]);
        }
    }
}