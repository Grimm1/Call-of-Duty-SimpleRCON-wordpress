<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

// Global variables to store server data for query and RCON operations.
global $cod_server_id, $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_isrcon;

/**
 * Resolves a hostname to an IP address, handling localhost and external IP cases.
 *
 * @param string $hostname The hostname or IP to resolve.
 * @return array An array containing success status, resolved IP, and message.
 */
function codsrcon_resolve_hostname(string $hostname): array
{
    error_log("codsrcon_resolve_hostname: Resolving hostname '$hostname'");

    // Initialize result array.
    $result = [
        'success' => false,
        'ip' => null,
        'message' => '',
    ];

    // Check for empty hostname.
    if (empty($hostname)) {
        $result['message'] = __('Hostname is empty', 'codsimplercon');
        error_log("codsrcon_resolve_hostname: Empty hostname provided");
        return $result;
    }

    // If the input is a valid IP, return it directly.
    if (filter_var($hostname, FILTER_VALIDATE_IP)) {
        $result['success'] = true;
        $result['ip'] = $hostname;
        $result['message'] = __('Valid IP address provided', 'codsimplercon');
        error_log("codsrcon_resolve_hostname: $hostname is a valid IP address");
        return $result;
    }

    // Attempt to resolve hostname to IP.
    $resolved_ip = gethostbyname($hostname);

    // Check if resolution failed.
    if ($resolved_ip === $hostname) {
        $result['message'] = __('Unable to resolve hostname', 'codsimplercon');
        error_log("codsrcon_resolve_hostname: Failed to resolve hostname '$hostname'");
        return $result;
    }

    $result['success'] = true;
    $result['ip'] = $resolved_ip;
    $result['message'] = __('Hostname resolved successfully', 'codsimplercon');
    error_log("codsrcon_resolve_hostname: Resolved hostname '$hostname' to IP '$resolved_ip'");

    // Handle localhost resolution to external IP.
    if ($resolved_ip === '127.0.0.1') {
        $response = wp_remote_get('http://ipecho.net/plain');
        if (!is_wp_error($response)) {
            $external_ip = wp_remote_retrieve_body($response);
            if ($external_ip && filter_var($external_ip, FILTER_VALIDATE_IP)) {
                $result['ip'] = $external_ip;
                $result['message'] = __('Resolved localhost to external IP', 'codsimplercon');
                error_log("codsrcon_resolve_hostname: Resolved localhost to external IP '$external_ip'");
            }
        }
    }

    return $result;
}

/**
 * Queries a Call of Duty server for status information using UDP.
 *
 * @return string JSON-encoded server info and player data, or error response.
 */
function codsrcon_query_server()
{
    global $wpdb, $cod_server_id, $cod_ip_hostname, $cod_port;

    try {
        // Validate IP/hostname.
        if (empty($cod_ip_hostname)) {
            throw new \Exception(__('IP/Hostname not provided', 'codsimplercon'));
        }

        // Resolve hostname to IP.
        $resolve_result = codsrcon_resolve_hostname($cod_ip_hostname);
        if (!$resolve_result['success']) {
            throw new \Exception(__('Failed to resolve hostname: ', 'codsimplercon') . $resolve_result['message']);
        }
        $ip = $resolve_result['ip'];

        $port = (int) $cod_port;
        $query = "\xFF\xFF\xFF\xFFgetstatus";

        // Create UDP socket.
        $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket === false) {
            throw new \Exception(__('Failed to create socket', 'codsimplercon'));
        }

        // Set receive timeout to 2 seconds.
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);

        // Send query to server.
        if (@socket_sendto($socket, $query, strlen($query), 0, $ip, $port) === false) {
            throw new \Exception(__('Failed to send query to server', 'codsimplercon'));
        }

        // Receive response.
        $buffer = '';
        if (@socket_recvfrom($socket, $buffer, 4096, 0, $ip, $port) === false) {
            socket_close($socket);
            // Update server status to offline in database.
            $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';
            $wpdb->update(
                $table_name,
                ['server_hostname' => 'offline'],
                ['id' => $cod_server_id],
                ['%s'],
                ['%d']
            );
            throw new \Exception(__('No response from server', 'codsimplercon'));
        }

        socket_close($socket);

        // Process server response.
        $buffer = substr($buffer, 4);
        $buffer = str_replace('statusResponse', '', $buffer);
        error_log("codsrcon: Raw server response for $ip:$port - " . $buffer);
        $lines = explode("\n", trim($buffer));

        // Initialize server info and players array.
        $server_info = [
            'mapname' => '',
            'g_gametype' => '',
            'sv_maxclients' => 0,
            'player_count' => '0/0',
            'sv_hostname' => '',
            'gamename' => '',
        ];
        $players = [];
        $player_count = 0;
        $sv_hostname = '';
        $gamename = '';
        $player_id = 0;

        // Parse response lines.
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Parse server info (key-value pairs).
            if (strpos($line, "\\") === 0) {
                $parts = explode("\\", trim($line, "\\"));
                for ($i = 0; $i < count($parts) - 1; $i += 2) {
                    $key = $parts[$i];
                    $value = $parts[$i + 1];
                    switch ($key) {
                        case 'mapname':
                        case 'g_gametype':
                            $server_info[$key] = $value;
                            break;
                        case 'sv_maxclients':
                            $server_info[$key] = (int) $value;
                            break;
                        case 'sv_hostname':
                            $sv_hostname = $value;
                            $server_info['sv_hostname'] = $value;
                            break;
                        case 'gamename':
                            $gamename = $value;
                            $server_info['gamename'] = $value;
                            break;
                    }
                }
            } elseif (preg_match('/^\d+\s+\d+\s+/', $line)) {
                // Parse player data.
                $player_parts = preg_split('/\s+/', $line, 3);
                if (count($player_parts) === 3) {
                    $players[] = [
                        'id' => $player_id++,
                        'score' => (int) $player_parts[0],
                        'ping' => (int) $player_parts[1],
                        'name' => trim($player_parts[2], '"'),
                    ];
                    $player_count++;
                }
            }
        }

        // Update player count.
        $server_info['player_count'] = "$player_count/{$server_info['sv_maxclients']}";

        // Update database with server hostname and game type if available.
        if ($sv_hostname || $gamename) {
            $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';
            $update_data = [];
            if ($sv_hostname) {
                $update_data['server_hostname'] = $sv_hostname;
            }
            if ($gamename) {
                $update_data['server_type'] = $gamename;
            }
            $wpdb->update(
                $table_name,
                $update_data,
                ['id' => $cod_server_id],
                array_fill(0, count($update_data), '%s'),
                ['%d']
            );
        }

        error_log("codsrcon: Parsed server_info for $ip:$port - " . json_encode($server_info));
        return json_encode([
            'success' => true,
            'server_info' => $server_info,
            'players' => $players,
        ]);
    } catch (\Exception $e) {
        error_log("codsrcon_query_server: Query error for $ip:$port - " . $e->getMessage());
        return json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * Sends an RCON command to a Call of Duty server using UDP.
 *
 * @param string $command The RCON command to send.
 * @return string JSON-encoded response or error message.
 */
function codsrcon_send_rcon_command($command)
{
    global $cod_ip_hostname, $cod_port, $cod_rcon_password;

    try {
        error_log("codsrcon_send_rcon_command: Preparing to send command '$command' to $cod_ip_hostname:$cod_port");

        // Sanitize command input.
        $command = sanitize_text_field($command);

        // Validate IP/hostname.
        if (empty($cod_ip_hostname)) {
            throw new \Exception(__('IP/Hostname not provided', 'codsimplercon'));
        }

        // Resolve hostname to IP.
        $resolve_result = codsrcon_resolve_hostname($cod_ip_hostname);
        if (!$resolve_result['success']) {
            throw new \Exception(__('Failed to resolve hostname: ', 'codsimplercon') . $resolve_result['message']);
        }
        $ip = $resolve_result['ip'];

        error_log("codsrcon_send_rcon_command: Resolved IP: $ip");

        $port = (int) $cod_port;
        $password = $cod_rcon_password;

        // Create UDP socket client.
        $sock = @stream_socket_client("udp://$ip:$port", $errno, $errstr, 2);
        if ($sock === false) {
            throw new \Exception(sprintf(__('Server offline or not responding: %s (%d)', 'codsimplercon'), $errstr, $errno));
        }

        // Send RCON command.
        $command_packet = "\xff\xff\xff\xffrcon $password $command";
        fwrite($sock, $command_packet);

        // Read response with timeout.
        stream_set_timeout($sock, 2);
        $response = '';
        $start_time = microtime(true);

        while ((microtime(true) - $start_time) < 2) {
            $data = fread($sock, 2048);
            if ($data === false || feof($sock)) {
                break;
            }
            $response .= $data;
        }

        fclose($sock);

        // Clean response by removing prefix.
        $clean_response = str_replace("\xff\xff\xff\xffprint\n", '', $response);

        error_log("codsrcon_send_rcon_command: Received response: $clean_response");

        return json_encode([
            'success' => true,
            'response' => $clean_response,
        ]);
    } catch (\Exception $e) {
        error_log("codsrcon_send_rcon_command: Error - " . $e->getMessage());
        return json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * Processes server data, handling both query and RCON operations.
 *
 * @param int    $server_id     The server ID (0 for new servers using globals).
 * @param int    $isrcon        Flag (0 for query, 1 for RCON).
 * @param string $rcon_command  RCON command (required when isrcon = 1).
 * @return string JSON-encoded result of the operation.
 */
function codsrcon_process_server_data($server_id, $isrcon, $rcon_command = '')
{
    global $wpdb, $cod_server_id, $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_isrcon;

    $cod_server_id = $server_id;
    $cod_isrcon = $isrcon;

    // Handle new server (server_id = 0) using global variables.
    if ($server_id === 0) {
        if (empty($cod_ip_hostname) || empty($cod_port) || ($isrcon && empty($cod_rcon_password))) {
            error_log("codsrcon_process_server_data: Missing required data for new server - ip_hostname=$cod_ip_hostname, port=$cod_port, rcon_password=$cod_rcon_password");
            return json_encode([
                'success' => false,
                'error' => __('Missing required server data', 'codsimplercon'),
            ]);
        }
    } else {
        // Fetch server details from database for existing servers.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';
        $server = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT ip_hostname, port, rcon_password FROM $table_name WHERE id = %d",
                $server_id
            ),
            ARRAY_A
        );

        if (!$server) {
            error_log("codsrcon_process_server_data: Server not found with ID: $server_id");
            return json_encode([
                'success' => false,
                'error' => sprintf(__('Server not found with ID: %d', 'codsimplercon'), $server_id),
            ]);
        }

        // Store server details in globals.
        $cod_ip_hostname = $server['ip_hostname'];
        $cod_port = $server['port'];
        $cod_rcon_password = $server['rcon_password'];
    }

    // Resolve IP.
    $resolve_result = codsrcon_resolve_hostname($cod_ip_hostname);
    if (!$resolve_result['success']) {
        error_log("codsrcon_process_server_data: Failed to resolve hostname $cod_ip_hostname - " . $resolve_result['message']);
        return json_encode([
            'success' => false,
            'error' => $resolve_result['message'],
        ]);
    }
    $cod_ip_hostname = $resolve_result['ip'];

    // Execute query or RCON command based on isrcon flag.
    if ($isrcon === 0) {
        return codsrcon_query_server();
    } else {
        if (empty($rcon_command)) {
            return json_encode([
                'success' => false,
                'error' => __('RCON command required when isrcon = 1', 'codsimplercon'),
            ]);
        }
        return codsrcon_send_rcon_command($rcon_command);
    }
}

/**
 * Handles sending commands to a Call of Duty server.
 */
class Cod_Server_Query
{
    /**
     * Sends a command to the specified server.
     *
     * @param int    $server_id The server ID.
     * @param string $command   The command to send (RCON only).
     * @param int    $isrcon    Flag (0 for query, 1 for RCON).
     * @return bool True on success, false on failure.
     */
    public function send_command($server_id, $command, $isrcon = 0)
    {
        global $wpdb, $cod_ip_hostname, $cod_port, $cod_rcon_password;

        error_log("send_command: Received server_id = $server_id, command = '$command', isrcon = $isrcon");

        // Fetch server details from database.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';
        $server = $wpdb->get_row(
            $wpdb->prepare("SELECT ip_hostname, port, rcon_password FROM $table_name WHERE id = %d", $server_id),
            ARRAY_A
        );

        if (!$server) {
            error_log("send_command: Server not found for ID: $server_id");
            return false;
        }

        $ip_hostname = $server['ip_hostname'];
        $port = $server['port'];
        $rcon_password = $server['rcon_password'];

        error_log("send_command: Fetched server details - IP/Hostname: $ip_hostname, Port: $port");

        // Resolve hostname to IP.
        $resolve_result = codsrcon_resolve_hostname($ip_hostname);
        if (!$resolve_result['success']) {
            error_log("send_command: Failed to resolve hostname for server ID $server_id: " . $resolve_result['message']);
            return false;
        }

        // Assign resolved IP to global variables.
        $cod_ip_hostname = $resolve_result['ip'];
        $cod_port = $port;
        $cod_rcon_password = $rcon_password;

        error_log("send_command: Resolved IP for server ID $server_id: $cod_ip_hostname");

        if ($isrcon === 1) {
            // Send RCON command.
            error_log("send_command: Sending RCON command '$command' to $cod_ip_hostname:$cod_port");
            $response = codsrcon_send_rcon_command($command);
            $response_data = json_decode($response, true);

            if ($response_data['success']) {
                error_log("send_command: RCON command executed successfully for server ID $server_id");
                return true;
            } else {
                error_log("send_command: RCON command failed for server ID $server_id: " . $response_data['error']);
                return false;
            }
        } else {
            error_log("send_command: Non-RCON command received for server ID $server_id");
            return false;
        }
    }
}