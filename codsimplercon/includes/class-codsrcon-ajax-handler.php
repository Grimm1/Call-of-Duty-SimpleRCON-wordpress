<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Class for handling AJAX requests in the CoD Simple RCON plugin.
 */
class AjaxHandler
{
    /**
     * Global WordPress database object for query execution.
     *
     * @var wpdb
     */
    private static $wpdb;

    /**
     * Array of database table names with plugin-specific prefixes.
     *
     * @var array
     */
    private static $tables = [
        'servers' => 'callofdutysimplercon_servers',
        'available_maps' => 'callofdutysimplercon_available_maps',
        'server_maps' => 'callofdutysimplercon_server_maps',
        'available_gts' => 'callofdutysimplercon_available_gts',
        'server_gts' => 'callofdutysimplercon_server_gts',
        'def_gts' => 'callofdutysimplercon_def_gts',
        'bans' => 'callofdutysimplercon_bans'
    ];

    /**
     * Initializes the AJAX handlers by registering actions for authenticated and unauthenticated requests.
     */
    public static function init()
    {
        // Log initialization for debugging.
        error_log("AjaxHandler: Initializing AJAX handlers");

        // Store the global WordPress database object.
        self::$wpdb = $GLOBALS['wpdb'];

        // Define AJAX actions and their corresponding methods.
        $actions = [
            'codsrcon_get_server_info' => 'codsrcon_get_server_info',
            'codsrcon_get_gametype_list' => 'codsrcon_get_gametype_list',
            'codsrcon_get_map_list' => 'codsrcon_get_map_list',
            'codsrcon_process_add_or_update_server' => 'codsrcon_process_add_or_update_server',
            'codsrcon_edit_server' => 'codsrcon_edit_server',
            'codsrcon_delete_server' => 'codsrcon_delete_server',
            'codsrcon_fetch_maps' => 'codsrcon_fetch_maps',
            'codsrcon_fetch_gts' => 'codsrcon_fetch_gts',
            'codsrcon_add_map' => 'codsrcon_add_map',
            'codsrcon_remove_map_from_server' => 'codsrcon_remove_map_from_server',
            'codsrcon_add_gt_to_server' => 'codsrcon_add_gt_to_server',
            'codsrcon_remove_gt_from_server' => 'codsrcon_remove_gt_from_server',
            'codsrcon_add_defaults' => 'codsrcon_add_defaults',
            'codsrcon_add_defaults_gt' => 'codsrcon_add_defaults_gt',
            'codsrcon_send_rcon_command' => 'codsrcon_send_rcon_command',
            'codsrcon_set_hostname' => 'codsrcon_set_hostname',
            'codsrcon_set_password' => 'codsrcon_set_password',
            'codsrcon_log_ban' => 'codsrcon_log_ban'
        ];

        // Register each action for both authenticated (wp_ajax_) and unauthenticated (wp_ajax_nopriv_) requests.
        foreach ($actions as $action => $method) {
            error_log("AjaxHandler: Registering AJAX action: wp_ajax_$action for method $method");
            add_action("wp_ajax_$action", [__CLASS__, $method]);
            error_log("AjaxHandler: Registering AJAX action: wp_ajax_nopriv_$action for method $method");
            add_action("wp_ajax_nopriv_$action", [__CLASS__, $method]);
        }
    }

    /**
     * Formats a Call of Duty server hostname by converting color codes to HTML spans with corresponding colors.
     *
     * @param string $hostname The raw hostname containing color codes (e.g., ^1Red).
     * @return string The formatted hostname with HTML color styling.
     */
    public static function format_cod_hostname($hostname)
    {
        // Define color codes and their corresponding HTML colors.
        $color_map = [
            '^0' => '#000000', // Black
            '^1' => '#FF0000', // Red
            '^2' => '#00FF00', // Green
            '^3' => '#FFFF00', // Yellow
            '^4' => '#0000FF', // Blue
            '^5' => '#00FFFF', // Cyan
            '^6' => '#FF00FF', // Magenta
            '^7' => '#FFFFFF', // White
            '^8' => '#808080', // Gray
            '^9' => '#FFA500', // Orange
        ];

        $formatted = '';
        $current_pos = 0;

        // Parse the hostname to apply color codes.
        while ($current_pos < strlen($hostname)) {
            $color_code_pos = strpos($hostname, '^', $current_pos);
            if ($color_code_pos === false) {
                // No more color codes; append the remaining text.
                $formatted .= esc_html(substr($hostname, $current_pos));
                break;
            }

            // Append text before the color code.
            if ($color_code_pos > $current_pos) {
                $formatted .= esc_html(substr($hostname, $current_pos, $color_code_pos - $current_pos));
            }

            // Extract the color code (e.g., ^1).
            $code = substr($hostname, $color_code_pos, 2);
            if (isset($color_map[$code]) && $color_code_pos + 2 <= strlen($hostname)) {
                // Valid color code; apply it to the following text chunk.
                $next_text = substr($hostname, $color_code_pos + 2);
                $next_color_pos = strpos($next_text, '^');
                $text_chunk = $next_color_pos === false ? $next_text : substr($next_text, 0, $next_color_pos);
                $formatted .= '<span style="color: ' . $color_map[$code] . ';">' . esc_html($text_chunk) . '</span>';
                $current_pos = $color_code_pos + 2 + ($next_color_pos === false ? strlen($next_text) : $next_color_pos);
            } else {
                // Invalid or incomplete color code; treat as plain text.
                $formatted .= esc_html($code);
                $current_pos = $color_code_pos + 2;
            }
        }

        return $formatted;
    }

    /**
     * Verifies the nonce and optional user permissions for AJAX requests.
     *
     * @param string      $nonce_action The nonce action to verify.
     * @param string|null $capability   The user capability to check (optional).
     */
    private static function verify_nonce_and_permissions($nonce_action, $capability = null)
    {
        // Log nonce verification attempt for debugging.
        error_log("AjaxHandler: Verifying nonce for action: $nonce_action, nonce value: " . ($_POST['nonce'] ?? 'not provided'));

        // Verify the nonce to prevent unauthorized requests.
        if (!check_ajax_referer($nonce_action, 'nonce', false)) {
            error_log("AjaxHandler: Nonce verification failed for action: $nonce_action");
            wp_send_json_error(['message' => 'Nonce verification failed'], 400);
        }

        // Check user capability if specified.
        if ($capability && is_user_logged_in() && !current_user_can($capability)) {
            error_log("AjaxHandler: Permission denied for user: " . (get_current_user_id() ?: 'not logged in'));
            wp_send_json_error(['message' => 'You do not have permission to perform this action'], 403);
        }

        // Log successful verification.
        error_log("AjaxHandler: Nonce and permissions verified successfully for action: $nonce_action");
    }

    /**
     * Retrieves the prefixed table name for a given key.
     *
     * @param string $key The table key (e.g., 'servers', 'available_maps').
     * @return string The fully qualified table name with WordPress prefix.
     */
    private static function get_table($key)
    {
        return self::$wpdb->prefix . self::$tables[$key];
    }

    /**
     * Validates a server ID and returns it if valid.
     *
     * @param mixed $server_id The server ID to validate.
     * @return int The validated server ID.
     */
    private static function validate_server_id($server_id)
    {
        // Log validation attempt for debugging.
        error_log("AjaxHandler: Validating server_id: " . ($server_id ?? 'not provided'));

        $server_id = intval($server_id);
        if (!$server_id) {
            error_log("AjaxHandler: Invalid server_id: $server_id");
            wp_send_json_error(['message' => 'Invalid server ID'], 400);
        }

        // Log successful validation.
        error_log("AjaxHandler: Server_id validated: $server_id");
        return $server_id;
    }

    /**
     * Sends a JSON response for AJAX requests.
     *
     * @param bool  $success Whether the request was successful.
     * @param array $data    Additional data to include in the response.
     */
    private static function send_json_response($success, $data = [])
    {
        $response = $success ? ['success' => true, 'data' => $data] : ['success' => false, 'data' => $data];
        error_log("AjaxHandler: Sending JSON response: " . json_encode($response));
        $success ? wp_send_json_success($data) : wp_send_json_error($data);
    }

    /**
     * Fetches detailed server information via AJAX, including hostname, map, game type, and players.
     */
    public static function codsrcon_get_server_info()
    {
        // Log the AJAX request for debugging.
        error_log("AjaxHandler: codsrcon_get_server_info called with POST data: " . json_encode($_POST));

        // Verify nonce (no capability check, allowing unauthenticated access if permitted).
        self::verify_nonce_and_permissions('codsrcon_get_server_info_nonce', null);

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id'] ?? '');

        // Fetch server details from the database.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT ip_hostname, port, rcon_password, server_type FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ), ARRAY_A);
        if (!$server) {
            error_log("AjaxHandler: Server not found for ID: $server_id");
            self::send_json_response(false, ['message' => 'Server not found']);
        }

        // Log retrieved server data for debugging.
        error_log("AjaxHandler: Server data retrieved: " . json_encode($server));

        try {
            // Process server data using the external method.
            $result = \codsimplercon\codsrcon_process_server_data($server_id, 0);
            error_log("AjaxHandler: Server data processing result: $result");
            $status = json_decode($result, true);
            if (!$status['success']) {
                error_log("AjaxHandler: Server data processing failed: " . $status['error']);
                self::send_json_response(false, ['message' => $status['error']]);
            }

            $server_info = $status['server_info'];
            $players = $status['players'];

            // Fetch map alias for display.
            $mapname_raw = $server_info['mapname'] ?? 'unknown';
            $map_alias = self::$wpdb->get_var(self::$wpdb->prepare(
                "SELECT mp_alias FROM " . self::get_table('server_maps') . " sm
                 JOIN " . self::get_table('available_maps') . " am ON sm.map_id = am.id
                 WHERE sm.server_id = %d AND am.mp_name = %s",
                $server_id,
                $mapname_raw
            ));
            $map_display_name = $map_alias ?: $mapname_raw;
            $mapname_for_image = strtolower(str_replace(' ', '_', $mapname_raw));

            // Map server type to game folder for image paths.
            $server_type = $server['server_type'];
            $gamefolder = match ($server_type) {
                'Call of Duty' => 'cod',
                'Call of Duty 2' => 'cod2',
                'Call of Duty 4' => 'cod4',
                'CoD:United Offensive' => 'coduo',
                'Call of Duty: World at War' => 'codwaw',
                default => 'cod'
            };

            // Generate map image URL.
            $map_image = plugins_url("assets/images/{$gamefolder}/{$mapname_for_image}.png", CODSIMPLERCON_PLUGIN_FILE);
            error_log("AjaxHandler: Resolved map image URL: $map_image");

            // Fetch game type alias for display.
            $gametype_raw = $server_info['g_gametype'] ?? 'unknown';
            $gametype_alias = self::$wpdb->get_var(self::$wpdb->prepare(
                "SELECT agt.gt_alias
                 FROM " . self::get_table('server_gts') . " sg
                 JOIN " . self::get_table('available_gts') . " agt ON sg.gt_id = agt.id
                 WHERE sg.server_id = %d AND agt.gametype = %s AND agt.gamename = %s",
                $server_id,
                $gametype_raw,
                $server_type
            ));
            $gametype_display_name = $gametype_alias ?: $gametype_raw;

            // Prepare response data.
            $response_data = [
                'sv_hostname' => self::format_cod_hostname($server_info['sv_hostname'] ?? 'N/A'),
                'gamename' => $server_info['gamename'] ?? 'N/A',
                'mapname' => $map_display_name,
                'gametype' => $gametype_display_name,
                'playercount' => $server_info['player_count'] ?? '0/0',
                'map_image' => $map_image,
                'mapname_raw' => $mapname_raw,
                'g_gametype' => $gametype_raw,
                'server_type' => $server_type ?? $server_info['gamename'] ?? 'N/A',
                'players' => $players ?? []
            ];

            // Log response data for debugging.
            error_log("AjaxHandler: Server info prepared: " . json_encode($response_data));
            self::send_json_response(true, $response_data);
        } catch (Exception $e) {
            error_log("AjaxHandler: Server query error: " . $e->getMessage());
            self::send_json_response(false, ['message' => 'Server query error: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetches the list of default game types for a given game via AJAX.
     */
    public static function codsrcon_get_gametype_list()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_get_gametype_list_nonce', 'manage_options');

        // Sanitize game name.
        $gamename = sanitize_text_field($_POST['gamename']) ?: self::send_json_response(false, ['message' => 'Invalid gamename']);

        // Fetch game types from the default game types table.
        $gametypes = self::$wpdb->get_results(self::$wpdb->prepare(
            "SELECT gametype, gt_alias FROM " . self::get_table('def_gts') . " WHERE gamename = %s",
            $gamename
        ), ARRAY_A);

        // Send response with game types or error.
        self::send_json_response(!empty($gametypes), !empty($gametypes) ? ['gametypes' => $gametypes] : ['message' => "No gametypes found for this gamename: $gamename"]);
    }

    /**
     * Fetches the list of maps associated with a server via AJAX.
     */
    public static function codsrcon_get_map_list()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_get_map_list_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Fetch maps linked to the server.
        $maps = self::$wpdb->get_results(self::$wpdb->prepare(
            "SELECT am.mp_name, am.mp_alias
             FROM " . self::get_table('available_maps') . " am
             JOIN " . self::get_table('server_maps') . " sm ON am.id = sm.map_id
             WHERE sm.server_id = %d",
            $server_id
        ), ARRAY_A);

        // Send response with maps or error.
        self::send_json_response(!empty($maps), !empty($maps) ? ['maps' => $maps] : ['message' => 'No maps found for this server']);
    }

    /**
     * Processes adding or updating a server via AJAX.
     */
    public static function codsrcon_process_add_or_update_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_server_nonce', 'manage_options');

        // Parse form data from AJAX request.
        parse_str($_POST['form_data'], $form_data);
        $server_id = intval($form_data['server_id'] ?? 0);

        // Sanitize and validate input data.
        $data = [
            'name' => sanitize_text_field($form_data['server_name'] ?? ''),
            'ip_hostname' => sanitize_text_field($form_data['ip_hostname'] ?? ''),
            'port' => intval($form_data['port'] ?? 0),
            'rcon_password' => sanitize_text_field($form_data['rcon_password'] ?? '')
        ];

        // Check for missing or invalid fields.
        if (in_array('', $data, true) || $data['port'] < 1 || $data['port'] > 65535) {
            self::send_json_response(false, ['message' => in_array('', $data, true) ? 'All fields are required.' : 'Invalid port number. Must be between 1 and 65535.']);
        }

        // Set global variables for RCON and query functions.
        global $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id;
        [$cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id] = [$data['ip_hostname'], $data['port'], $data['rcon_password'], $server_id];

        // Validate hostname resolution.
        $resolve_result = \codsimplercon\codsrcon_resolve_hostname($cod_ip_hostname);
        if (!$resolve_result['success']) {
            error_log("AjaxHandler: Failed to resolve hostname {$data['ip_hostname']} - " . $resolve_result['message']);
            self::send_json_response(false, ['message' => 'Failed to resolve hostname: ' . $resolve_result['message']]);
        }
        $cod_ip_hostname = $resolve_result['ip'];

        // Test RCON connection.
        $rcon_result = json_decode(\codsimplercon\codsrcon_send_rcon_command("status"), true);
        if (!$rcon_result['success']) {
            error_log("AjaxHandler: RCON test failed for {$data['ip_hostname']}:{$data['port']} - " . $rcon_result['error']);
            self::send_json_response(false, ['message' => 'Failed to send RCON command: ' . $rcon_result['error']]);
        }

        // Check for invalid RCON password responses.
        $invalidPasswordResponses = ["Invalid password.", "Bad rcon", "Bad rconpassword."];
        foreach ($invalidPasswordResponses as $invalidResponse) {
            if (stripos($rcon_result['response'], $invalidResponse) !== false) {
                error_log("AjaxHandler: Invalid RCON password detected for {$data['ip_hostname']}:{$data['port']} - Response: {$rcon_result['response']}");
                self::send_json_response(false, ['message' => 'Invalid RCON password.', 'rcon_response' => $rcon_result['response']]);
            }
        }

        // Query server information.
        $query_result = json_decode(\codsimplercon\codsrcon_query_server(), true);
        if (!$query_result['success']) {
            error_log("AjaxHandler: Query failed for {$data['ip_hostname']}:{$data['port']} - " . $query_result['error']);
            self::send_json_response(false, ['message' => 'Failed to query server: ' . $query_result['error']]);
        }

        // Add server type and hostname to data.
        $data['server_type'] = $query_result['server_info']['gamename'] ?? 'Unknown';
        $data['server_hostname'] = preg_replace('/\^[0-9]/', '', $query_result['server_info']['sv_hostname'] ?? 'Unknown');

        // Update or insert server into the database.
        $table = self::get_table('servers');
        if ($server_id) {
            $result = self::$wpdb->update($table, $data, ['id' => $server_id], ['%s', '%s', '%d', '%s', '%s', '%s'], ['%d']);
        } else {
            $result = self::$wpdb->insert($table, $data, ['%s', '%s', '%d', '%s', '%s', '%s']);
            $server_id = self::$wpdb->insert_id;
            \codsimplercon\Database::populate_available_maps_and_gts($server_id, $data['server_type']);
        }

        if ($result === false) {
            error_log("AjaxHandler: " . ($server_id ? 'Update' : 'Insert') . " failed - " . self::$wpdb->last_error);
            self::send_json_response(false, ['message' => "Failed to " . ($server_id ? 'update' : 'add') . " server: " . self::$wpdb->last_error]);
        }

        self::send_json_response(true, ['message' => 'Server saved successfully']);
    }

    /**
     * Fetches server details for editing via AJAX.
     */
    public static function codsrcon_edit_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_edit_server_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Fetch server details from the database.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT * FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ), ARRAY_A);

        // Send response with server data or error.
        self::send_json_response($server !== null, $server ?: ['message' => 'Server not found']);
    }

    /**
     * Deletes a server via AJAX.
     */
    public static function codsrcon_delete_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_delete_server_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Delete server from the database.
        $result = self::$wpdb->delete(self::get_table('servers'), ['id' => $server_id], ['%d']);
        if ($result === false) {
            self::send_json_response(false, ['message' => 'Failed to delete server: ' . self::$wpdb->last_error]);
        }

        // Clean up related database entries.
        \codsimplercon\Database::delete_server_entries($server_id);

        self::send_json_response(true, ['message' => 'Server deleted successfully']);
    }

    /**
     * Fetches maps associated with a server via AJAX and returns an HTML table.
     */
    public static function codsrcon_fetch_maps()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_fetch_maps_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Fetch maps linked to the server.
        $maps = self::$wpdb->get_results(self::$wpdb->prepare(
            "SELECT am.id, am.mp_name, am.mp_alias
             FROM " . self::get_table('available_maps') . " am
             JOIN " . self::get_table('server_maps') . " sm ON am.id = sm.map_id
             WHERE sm.server_id = %d",
            $server_id
        ), ARRAY_A);

        // Buffer output for the maps table.
        ob_start();
        ?>
        <table id="server-maps-table" class="codsrcon-maps-table codsrcon-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Map Name', 'codsimplercon'); ?></th>
                    <th><?php echo esc_html__('Alias', 'codsimplercon'); ?></th>
                    <th><?php echo esc_html__('Action', 'codsimplercon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($maps)): ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html__('No maps found.', 'codsimplercon'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($maps as $map): ?>
                        <tr>
                            <td><?php echo esc_html($map['mp_name']); ?></td>
                            <td><?php echo esc_html($map['mp_alias']); ?></td>
                            <td>
                                <button type="button" class="codsrcon-button codsrcon-delete-button remove-map"
                                        data-map-id="<?php echo esc_attr($map['id']); ?>">
                                    <?php echo esc_html__('Remove', 'codsimplercon'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        // Send the table HTML as a JSON response.
        self::send_json_response(true, ['html' => ob_get_clean()]);
    }

    /**
     * Fetches game types associated with a server via AJAX and returns an HTML table.
     */
    public static function codsrcon_fetch_gts()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_fetch_gts_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Fetch game types linked to the server.
        $gts = self::$wpdb->get_results(self::$wpdb->prepare(
            "SELECT ag.id, ag.gametype, ag.gt_alias
             FROM " . self::get_table('available_gts') . " ag
             JOIN " . self::get_table('server_gts') . " sg ON ag.id = sg.gt_id
             WHERE sg.server_id = %d",
            $server_id
        ), ARRAY_A);

        // Buffer output for the game types table.
        ob_start();
        ?>
        <table id="server-gts-table" class="codsrcon-gts-table codsrcon-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Game Type', 'codsimplercon'); ?></th>
                    <th><?php echo esc_html__('Alias', 'codsimplercon'); ?></th>
                    <th><?php echo esc_html__('Action', 'codsimplercon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($gts)): ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html__('No game types found.', 'codsimplercon'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($gts as $gt): ?>
                        <tr>
                            <td><?php echo esc_html($gt['gametype']); ?></td>
                            <td><?php echo esc_html($gt['gt_alias']); ?></td>
                            <td>
                                <button type="button" class="codsrcon-button codsrcon-delete-button remove-gt"
                                        data-gt-id="<?php echo esc_attr($gt['id']); ?>">
                                    <?php echo esc_html__('Remove', 'codsimplercon'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        // Send the table HTML as a JSON response.
        self::send_json_response(true, ['html' => ob_get_clean()]);
    }

    /**
     * Adds a map to a server via AJAX, including optional image upload.
     */
    public static function codsrcon_add_map()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_map_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize input data.
        $mp_name = sanitize_text_field($_POST['mp_name'] ?? '');
        $mp_alias = sanitize_text_field($_POST['map_alias'] ?? '');
        $overwrite_confirm = isset($_POST['overwrite_confirm']) && $_POST['overwrite_confirm'] === 'yes';

        // Check for missing required fields.
        if (!$mp_name || !$mp_alias) {
            self::send_json_response(false, ['message' => 'Missing required fields']);
        }

        // Fetch server game type.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT server_type FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ));
        if (!$server) {
            self::send_json_response(false, ['message' => 'Server not found']);
        }
        $game_name = $server->server_type;

        // Check for existing map.
        $existing_map = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT id FROM " . self::get_table('available_maps') . " WHERE mp_name = %s AND gamename = %s",
            $mp_name,
            $game_name
        ));

        // Prompt for overwrite confirmation if map exists and overwrite not confirmed.
        if ($existing_map && !$overwrite_confirm) {
            self::send_json_response(false, ['message' => 'Map already exists. Do you want to overwrite it?', 'confirm_required' => true]);
        }

        // Handle image upload if provided.
        if (!empty($_FILES['map_image']['name'])) {
            self::handle_map_image_upload($mp_name, $game_name);
        }

        // Update or insert map and link to server.
        $map_id = $existing_map ? self::update_map($existing_map->id, $mp_alias) : self::insert_map($mp_name, $mp_alias, $game_name);
        self::link_map_to_server($server_id, $map_id);

        self::send_json_response(true, ['message' => 'Map added successfully']);
    }

    /**
     * Handles the upload and processing of a map image.
     *
     * @param string $mp_name   The map name for the image filename.
     * @param string $game_name The game name for determining the storage folder.
     */
    private static function handle_map_image_upload($mp_name, $game_name)
    {
        // Include WordPress image and file handling dependencies.
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $file = $_FILES['map_image'];
        $file_type = wp_check_filetype($file['name']);

        // Validate file type.
        if ($file_type['ext'] !== 'png' || $file_type['type'] !== 'image/png') {
            error_log("AjaxHandler: Invalid image format for $mp_name: {$file_type['type']}");
            self::send_json_response(false, ['message' => 'Invalid image format. Only PNG is allowed.']);
        }

        // Map game names to folder names.
        $game_mapping = [
            'Call of Duty' => 'cod',
            'Call of Duty 2' => 'cod2',
            'Call of Duty 4' => 'cod4',
            'Call of Duty: World at War' => 'codwaw',
            'CoD:United Offensive' => 'coduo'
        ];
        $folder_name = $game_mapping[$game_name] ?? 'default';
        $upload_dir = plugin_dir_path(CODSIMPLERCON_PLUGIN_FILE) . "assets/images/$folder_name/";

        // Create upload directory if it doesn't exist.
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        // Save and resize image.
        $new_filename = sanitize_file_name($mp_name) . '.png';
        $destination = $upload_dir . $new_filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            error_log("AjaxHandler: Failed to move uploaded file for $mp_name to $destination");
            self::send_json_response(false, ['message' => 'Error moving uploaded file']);
        }

        $image_editor = wp_get_image_editor($destination);
        if (is_wp_error($image_editor)) {
            error_log("AjaxHandler: Image editor failed for $mp_name: " . $image_editor->get_error_message());
            self::send_json_response(false, ['message' => 'Failed to process image: ' . $image_editor->get_error_message()]);
        }

        $image_editor->resize(192, 192, true);
        $saved = $image_editor->save($destination);
        if (is_wp_error($saved)) {
            error_log("AjaxHandler: Image save failed for $mp_name: " . $saved->get_error_message());
            self::send_json_response(false, ['message' => 'Failed to resize image: ' . $saved->get_error_message()]);
        }

        error_log("AjaxHandler: Image saved to $destination");
    }

    /**
     * Updates an existing map's alias in the database.
     *
     * @param int    $map_id   The ID of the map to update.
     * @param string $mp_alias The new alias for the map.
     * @return int The map ID.
     */
    private static function update_map($map_id, $mp_alias)
    {
        self::$wpdb->update(
            self::get_table('available_maps'),
            ['mp_alias' => $mp_alias],
            ['id' => $map_id],
            ['%s'],
            ['%d']
        );
        return $map_id;
    }

    /**
     * Inserts a new map into the database.
     *
     * @param string $mp_name   The map name.
     * @param string $mp_alias  The map alias.
     * @param string $game_name The game name associated with the map.
     * @return int The new map ID.
     */
    private static function insert_map($mp_name, $mp_alias, $game_name)
    {
        self::$wpdb->insert(
            self::get_table('available_maps'),
            ['mp_name' => $mp_name, 'mp_alias' => $mp_alias, 'gamename' => $game_name],
            ['%s', '%s', '%s']
        );
        return self::$wpdb->insert_id;
    }

    /**
     * Links a map to a server in the database.
     *
     * @param int $server_id The server ID.
     * @param int $map_id    The map ID.
     */
    private static function link_map_to_server($server_id, $map_id)
    {
        // Check if the map is already linked to the server.
        if (!self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT * FROM " . self::get_table('server_maps') . " WHERE server_id = %d AND map_id = %d",
            $server_id,
            $map_id
        ))) {
            // Insert the map-server association.
            self::$wpdb->insert(
                self::get_table('server_maps'),
                ['server_id' => $server_id, 'map_id' => $map_id],
                ['%d', '%d']
            );
        }
    }

    /**
     * Removes a map from a server via AJAX.
     */
    public static function codsrcon_remove_map_from_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_remove_map_nonce', 'manage_options');

        // Validate server and map IDs.
        $server_id = self::validate_server_id($_POST['server_id']);
        $map_id = intval($_POST['map_id']) ?: self::send_json_response(false, ['message' => 'Invalid map ID']);

        // Delete the map-server association.
        self::$wpdb->delete(self::get_table('server_maps'), ['server_id' => $server_id, 'map_id' => $map_id], ['%d', '%d']);
        self::send_json_response(true, ['message' => 'Map removed successfully']);
    }

    /**
     * Adds a game type to a server via AJAX.
     */
    public static function codsrcon_add_gt_to_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_gt_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Parse form data from AJAX request.
        parse_str($_POST['form_data'], $form_data);
        $gametype = sanitize_text_field($form_data['gametype'] ?? '');
        $gt_alias = sanitize_text_field($form_data['gt_alias'] ?? '');

        // Check for missing required fields.
        if (!$gametype || !$gt_alias) {
            self::send_json_response(false, ['message' => 'Missing required fields']);
        }

        // Fetch server game type.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT server_type FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ));
        if (!$server) {
            self::send_json_response(false, ['message' => 'Server not found']);
        }
        $game_name = $server->server_type;

        // Check for existing game type.
        $existing_gt = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT id FROM " . self::get_table('available_gts') . " WHERE gametype = %s AND gamename = %s",
            $gametype,
            $game_name
        ));

        // Update or insert game type and link to server.
        $gt_id = $existing_gt ? self::update_gt($existing_gt->id, $gt_alias) : self::insert_gt($gametype, $gt_alias, $game_name);
        self::link_gt_to_server($server_id, $gt_id);

        self::send_json_response(true, ['message' => 'Game type added successfully']);
    }

    /**
     * Logs a player ban in the database via AJAX.
     */
    public static function codsrcon_log_ban()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_send_command_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize ban name.
        $ban_name = sanitize_text_field($_POST['ban_name']);

        // Check for missing ban name.
        if (empty($ban_name)) {
            self::send_json_response(false, ['message' => 'Player name cannot be empty']);
        }

        // Insert ban record into the database.
        $result = self::$wpdb->insert(
            self::get_table('bans'),
            [
                'server_id' => $server_id,
                'ban_name' => $ban_name,
                'ban_date' => current_time('mysql')
            ],
            ['%d', '%s', '%s']
        );

        if ($result === false) {
            self::send_json_response(false, ['message' => 'Failed to log ban: ' . self::$wpdb->last_error]);
        }

        self::send_json_response(true, ['message' => 'Ban logged successfully']);
    }

    /**
     * Updates an existing game type's alias in the database.
     *
     * @param int    $gt_id    The ID of the game type to update.
     * @param string $gt_alias The new alias for the game type.
     * @return int The game type ID.
     */
    private static function update_gt($gt_id, $gt_alias)
    {
        self::$wpdb->update(
            self::get_table('available_gts'),
            ['gt_alias' => $gt_alias],
            ['id' => $gt_id],
            ['%s'],
            ['%d']
        );
        return $gt_id;
    }

    /**
     * Inserts a new game type into the database.
     *
     * @param string $gametype  The game type identifier.
     * @param string $gt_alias  The game type alias.
     * @param string $game_name The game name associated with the game type.
     * @return int The new game type ID.
     */
    private static function insert_gt($gametype, $gt_alias, $game_name)
    {
        self::$wpdb->insert(
            self::get_table('available_gts'),
            ['gametype' => $gametype, 'gt_alias' => $gt_alias, 'gamename' => $game_name],
            ['%s', '%s', '%s']
        );
        return self::$wpdb->insert_id;
    }

    /**
     * Links a game type to a server in the database.
     *
     * @param int $server_id The server ID.
     * @param int $gt_id     The game type ID.
     */
    private static function link_gt_to_server($server_id, $gt_id)
    {
        // Check if the game type is already linked to the server.
        if (!self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT * FROM " . self::get_table('server_gts') . " WHERE server_id = %d AND gt_id = %d",
            $server_id,
            $gt_id
        ))) {
            // Insert the game type-server association.
            self::$wpdb->insert(
                self::get_table('server_gts'),
                ['server_id' => $server_id, 'gt_id' => $gt_id],
                ['%d', '%d']
            );
        }
    }

    /**
     * Removes a game type from a server via AJAX.
     */
    public static function codsrcon_remove_gt_from_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_remove_gt_nonce', 'manage_options');

        // Validate server and game type IDs.
        $server_id = self::validate_server_id($_POST['server_id']);
        $gt_id = intval($_POST['gt_id']) ?: self::send_json_response(false, ['message' => 'Invalid game type ID']);

        // Delete the game type-server association.
        self::$wpdb->delete(self::get_table('server_gts'), ['server_id' => $server_id, 'gt_id' => $gt_id], ['%d', '%d']);
        self::send_json_response(true, ['message' => 'Game type removed successfully']);
    }

    /**
     * Adds default maps to a server via AJAX.
     */
    public static function codsrcon_add_defaults()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_defaults_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize game name.
        $game_name = sanitize_text_field($_POST['gamename'] ?? '') ?: self::send_json_response(false, ['message' => 'Invalid game name']);

        // Populate default maps for the server.
        \codsimplercon\Database::populate_available_maps($server_id, $game_name);
        self::send_json_response(true, ['message' => 'Default maps added successfully']);
    }

    /**
     * Adds default game types to a server via AJAX.
     */
    public static function codsrcon_add_defaults_gt()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_defaults_gt_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize game name.
        $game_name = sanitize_text_field($_POST['gamename'] ?? '') ?: self::send_json_response(false, ['message' => 'Invalid game name']);

        // Populate default game types for the server.
        \codsimplercon\Database::populate_available_gts($server_id, $game_name);
        self::send_json_response(true, ['message' => 'Default game types added successfully']);
    }

    /**
     * Sends an RCON command to a server via AJAX.
     */
    public static function codsrcon_send_rcon_command()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_send_command_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize command.
        $command = sanitize_text_field($_POST['command']);

        // Fetch server details from the database.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT ip_hostname, port, rcon_password FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ), ARRAY_A);
        if (!$server) {
            self::send_json_response(false, ['message' => 'Server not found']);
        }

        // Set global variables for RCON command execution.
        global $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id;
        [$cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id] = [$server['ip_hostname'], $server['port'], $server['rcon_password'], $server_id];

        // Execute RCON command.
        $result = json_decode(\codsimplercon\codsrcon_send_rcon_command($command), true);
        if (!$result['success']) {
            self::send_json_response(false, ['message' => 'RCON command failed: ' . $result['error']]);
        }

        self::send_json_response(true, ['message' => 'Command executed successfully', 'response' => $result['response']]);
    }

    /**
     * Sets the server hostname via AJAX using an RCON command.
     */
    public static function codsrcon_set_hostname()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_set_hostname_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize hostname.
        $sv_hostname = sanitize_text_field($_POST['sv_hostname']);

        // Check for empty hostname.
        if (empty($sv_hostname)) {
            self::send_json_response(false, ['message' => 'Hostname cannot be empty']);
        }

        // Fetch server details from the database.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT ip_hostname, port, rcon_password FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ), ARRAY_A);
        if (!$server) {
            self::send_json_response(false, ['message' => 'Server not found']);
        }

        // Set global variables for RCON command execution.
        global $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id;
        [$cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id] = [$server['ip_hostname'], $server['port'], $server['rcon_password'], $server_id];

        // Execute RCON command to set hostname.
        $result = json_decode(\codsimplercon\codsrcon_send_rcon_command("sv_hostname \"$sv_hostname\""), true);
        if (!$result['success']) {
            self::send_json_response(false, ['message' => 'Failed to set hostname: ' . $result['error']]);
        }

        // Update hostname in the database.
        self::$wpdb->update(
            self::get_table('servers'),
            ['server_hostname' => $sv_hostname],
            ['id' => $server_id],
            ['%s'],
            ['%d']
        );

        self::send_json_response(true, ['message' => 'Hostname updated successfully', 'sv_hostname' => $sv_hostname]);
    }

    /**
     * Sets or removes the server password via AJAX using an RCON command.
     */
    public static function codsrcon_set_password()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_set_password_nonce', 'manage_options');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize password.
        $password = sanitize_text_field($_POST['password']);

        // Fetch server details from the database.
        $server = self::$wpdb->get_row(self::$wpdb->prepare(
            "SELECT ip_hostname, port, rcon_password FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ), ARRAY_A);
        if (!$server) {
            self::send_json_response(false, ['message' => 'Server not found']);
        }

        // Set global variables for RCON command execution.
        global $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id;
        [$cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id] = [$server['ip_hostname'], $server['port'], $server['rcon_password'], $server_id];

        // Execute RCON command to set or remove password.
        $result = json_decode(\codsimplercon\codsrcon_send_rcon_command("g_password \"$password\""), true);
        if (!$result['success']) {
            self::send_json_response(false, ['message' => 'Failed to set password: ' . $result['error']]);
        }

        self::send_json_response(true, ['message' => $password ? 'Password set successfully' : 'Password removed successfully']);
    }
}