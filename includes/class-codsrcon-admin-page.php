<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Class for managing the admin page of the CoD Simple RCON plugin in the WordPress dashboard.
 */
class Cod_Rcon_Admin_Page
{
    /**
     * Registers the admin menu page and enqueues necessary scripts and styles.
     */
    public static function register_admin_page()
    {
        // Add the main admin menu page for CoD Simple RCON.
        $hook = add_menu_page(
            __('CoD Simple RCON', 'codsimplercon'), // Page title in the browser.
            __('CoD Simple RCON', 'codsimplercon'), // Menu title in the dashboard.
            'manage_options',                       // Required user capability.
            'cod-rcon',                             // Unique menu slug.
            [__CLASS__, 'render_admin_page'],       // Callback to render the page.
            'dashicons-games',                      // Icon for the menu (WordPress dashicon).
            6                                       // Menu position in the dashboard.
        );

        // Enqueue admin assets only on the plugin's admin page.
        add_action('admin_enqueue_scripts', function ($hook_passed) use ($hook) {
            if ($hook_passed !== $hook) {
                return; // Exit if not on the plugin's admin page.
            }

            // Log asset enqueuing for debugging.
            error_log("Codsrcon: Enqueueing admin assets for hook: $hook_passed");

            // Enqueue the admin stylesheet.
            wp_enqueue_style(
                'codsrcon-admin-style',
                plugins_url('assets/css/codsrcon-admin.css', CODSIMPLERCON_PLUGIN_FILE)
            );

            // Enqueue the admin JavaScript with jQuery dependency.
            wp_enqueue_script(
                'codsrcon-admin-script',
                plugins_url('assets/js/codsrcon-admin.js', CODSIMPLERCON_PLUGIN_FILE),
                ['jquery'],
                null,
                true
            );

            // Localize script with AJAX parameters and nonces for secure requests.
            wp_localize_script('codsrcon-admin-script', 'codsrconAdminAjax', [
                'ajax_url' => admin_url('admin-ajax.php'), // WordPress AJAX endpoint.
                'nonce_add_server' => wp_create_nonce('codsrcon_add_server'), // Nonce for adding servers.
                'nonce_edit_server' => wp_create_nonce('codsrcon_edit_server'), // Nonce for editing servers.
                'nonce_delete_server' => wp_create_nonce('codsrcon_delete_server'), // Nonce for deleting servers.
                'nonce_fetch_maps' => wp_create_nonce('codsrcon_admin_fetch_maps'), // Nonce for fetching maps.
                'nonce_fetch_gts' => wp_create_nonce('codsrcon_admin_fetch_gts'), // Nonce for fetching game types.
                'nonce_add_map' => wp_create_nonce('codsrcon_add_map'), // Nonce for adding maps.
                'nonce_remove_map_from_server' => wp_create_nonce('codsrcon_remove_map_from_server'), // Nonce for removing maps.
                'nonce_add_gt_to_server' => wp_create_nonce('codsrcon_add_gt_to_server'), // Nonce for adding game types.
                'nonce_remove_gt_from_server' => wp_create_nonce('codsrcon_remove_gt_from_server'), // Nonce for removing game types.
                'nonce_add_defaults' => wp_create_nonce('codsrcon_add_defaults'), // Nonce for adding default maps.
                'nonce_add_defaults_gt' => wp_create_nonce('codsrcon_add_defaults_gt') // Nonce for adding default game types.
            ]);
        });
    }

    /**
     * Renders the admin page for managing Call of Duty servers, maps, and game types.
     */
    public static function render_admin_page()
    {
        global $wpdb;
        // Retrieve the servers table name.
        $table_name = self::get_table('servers');
        // Fetch all servers from the database.
        $servers = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        ?>
        <div class="wrap">
            <div id="wrapper_codsrcon">
                <h1><?php echo esc_html__('Call of Duty Simple RCON - Server Management', 'codsimplercon'); ?></h1>
                <p style="padding-left: 20px; position: relative; top: -10px;">
                    <b><?php echo esc_html__('Manage your Call of Duty servers.', 'codsimplercon'); ?></b>
                </p>
                <p><?php echo esc_html__('Use the shortcode [codsrcon_server_admin_info] to add the server administration block on a page or post.', 'codsimplercon'); ?></p>
                <p style="color: red; font-weight: bold;"><?php echo esc_html__('Warning: Ensure the server administration block page is secured with a password or appropriate user permissions to prevent unauthorized access.', 'codsimplercon'); ?></p>
                <p><?php echo esc_html__('To add monitors, use the shortcode [codsrcon_server_monitor server_id="your server id"], replacing "your server id" with the desired server ID.', 'codsimplercon'); ?></p>

                <!-- Server List Section -->
                <div class="codsrcon-server-list-container">
                    <h2><?php echo esc_html__('Current Servers', 'codsimplercon'); ?></h2>
                    <table class="codsrcon-server-list codsrcon-table">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('ID', 'codsimplercon'); ?></th>
                                <th><?php echo esc_html__('Name', 'codsimplercon'); ?></th>
                                <th><?php echo esc_html__('Game Name', 'codsimplercon'); ?></th>
                                <th><?php echo esc_html__('Hostname', 'codsimplercon'); ?></th>
                                <th><?php echo esc_html__('Port', 'codsimplercon'); ?></th>
                                <th><?php echo esc_html__('Action', 'codsimplercon'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($servers)): ?>
                                <tr>
                                    <td colspan="6"><?php echo esc_html__('No servers found.', 'codsimplercon'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($servers as $server): ?>
                                    <tr>
                                        <td><?php echo esc_html($server['id']); ?></td>
                                        <td><?php echo esc_html($server['name']); ?></td>
                                        <td><?php echo esc_html($server['server_type']); ?></td>
                                        <td><?php echo esc_html($server['server_hostname']); ?></td>
                                        <td><?php echo esc_html($server['port']); ?></td>
                                        <td>
                                            <div class="button-container">
                                                <button class="codsrcon-button codsrcon-edit-button"
                                                        data-server-id="<?php echo esc_attr($server['id']); ?>">
                                                    <?php echo esc_html__('Edit', 'codsimplercon'); ?>
                                                </button>
                                                <button class="codsrcon-button codsrcon-delete-button"
                                                        data-server-id="<?php echo esc_attr($server['id']); ?>">
                                                    <?php echo esc_html__('Delete', 'codsimplercon'); ?>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Server Management Section -->
                <div class="server-management-container">
                    <div class="server-edit-container">
                        <div id="add-map-form-container2">
                            <h2 id="server-form-title"><?php echo esc_html__('Add New Server', 'codsimplercon'); ?></h2>
                            <form id="server-form" method="post">
                                <input type="hidden" name="server_id" id="server_id" value="">
                                <div class="form-group">
                                    <label for="server_name"><?php echo esc_html__('Server Name', 'codsimplercon'); ?></label>
                                    <input type="text" name="server_name" id="server_name" class="regular-text" required>
                                </div>
                                <div class="form-group">
                                    <label for="ip_hostname"><?php echo esc_html__('IP/Hostname', 'codsimplercon'); ?></label>
                                    <input type="text" name="ip_hostname" id="ip_hostname" class="regular-text" required>
                                </div>
                                <div class="form-group">
                                    <label for="port"><?php echo esc_html__('Port', 'codsimplercon'); ?></label>
                                    <input type="number" name="port" id="port" min="1" max="65535" class="regular-text" required>
                                </div>
                                <div class="form-group">
                                    <label for="rcon_password"><?php echo esc_html__('RCON Password', 'codsimplercon'); ?></label>
                                    <input type="password" name="rcon_password" id="rcon_password" class="regular-text" required>
                                </div>
                                <button type="submit" class="codsrcon-button codsrcon-submit-button">
                                    <?php echo esc_html__('Add Server', 'codsimplercon'); ?>
                                </button>
                            </form>
                        </div>

                        <!-- Maps and Game Types Management Section -->
                        <div id="server-maps-gts-container" style="display: none;">
                            <div id="add-map-form-container">
                                <h2>
                                    <?php echo esc_html__('Maps for Server', 'codsimplercon'); ?>
                                    <button id="add-defaults-button" class="codsrcon-button">
                                        <?php echo esc_html__('Add Defaults', 'codsimplercon'); ?>
                                    </button>
                                </h2>
                                <form id="add-map-form" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="server_id" id="add-map-server-id" value="" />
                                    <label for="mp_name"><?php echo esc_html__('Map Name:', 'codsimplercon'); ?></label>
                                    <input type="text" name="mp_name" id="mp_name" placeholder="Enter map name" required>
                                    <label for="map_alias"><?php echo esc_html__('Alias:', 'codsimplercon'); ?></label>
                                    <input type="text" name="map_alias" id="map_alias" placeholder="Enter map alias" required>
                                    <div class="file-upload-wrapper">
                                        <?php echo esc_html__('Map image (PNG only, optional):', 'codsimplercon'); ?>
                                        <label for="map_image" class="codsrcon-button codsrcon-add-button">
                                            <?php echo esc_html__('Choose File', 'codsimplercon'); ?>
                                        </label>
                                        <input type="file" name="map_image" id="map_image" accept="image/png" class="hidden-file-input">
                                        <span id="file-name-display"><?php echo esc_html__('No file chosen', 'codsimplercon'); ?></span>
                                    </div>
                                    <div id="map-image-preview" style="margin-top: 10px;">
                                        <img id="preview-image" src="" alt="Image Preview" style="max-width: 192px; display: none;">
                                    </div>
                                    <button type="submit" class="codsrcon-button codsrcon-add-button">
                                        <?php echo esc_html__('Add Map', 'codsimplercon'); ?>
                                    </button>
                                </form>

                                <h2>
                                    <?php echo esc_html__('Game Types for Server', 'codsimplercon'); ?>
                                    <button id="add-defaults-gt-button" class="codsrcon-button">
                                        <?php echo esc_html__('Add Defaults', 'codsimplercon'); ?>
                                    </button>
                                </h2>
                                <form id="add-gt-form" method="post">
                                    <label for="gametype"><?php echo esc_html__('Gametype:', 'codsimplercon'); ?></label>
                                    <input type="text" name="gametype" id="gametype" placeholder="type" required style="width: 50px;">
                                    <label for="gt_alias"><?php echo esc_html__('Alias:', 'codsimplercon'); ?></label>
                                    <input type="text" name="gt_alias" id="gt_alias" placeholder="alias" required style="width: 100px;">
                                    <button type="submit" class="codsrcon-button codsrcon-add-button">
                                        <?php echo esc_html__('Add Gametype', 'codsimplercon'); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maps and Game Types Tables Section -->
                <div id="tables-container" class="codsrcon-server-list-container" style="display: none;">
                    <div class="tables-side-by-side">
                        <div class="table-wrapper">
                            <h2><?php echo esc_html__('Maps', 'codsimplercon'); ?></h2>
                            <div id="server-maps-list"></div>
                        </div>
                        <div class="table-wrapper" id="server-gts-table">
                            <h2><?php echo esc_html__('Game Types', 'codsimplercon'); ?></h2>
                            <div id="server-gts-list"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Verifies the nonce and user permissions for AJAX requests.
     *
     * @param string $nonce_action The nonce action to verify.
     */
    private static function verify_nonce_and_permissions($nonce_action)
    {
        // Log nonce verification attempt for debugging.
        error_log("Verifying nonce for action: $nonce_action | Received nonce: " . ($_POST['nonce'] ?? 'none'));

        // Verify the nonce to prevent unauthorized requests.
        check_ajax_referer($nonce_action, 'nonce');

        // Ensure the user has the required capability.
        if (!current_user_can('manage_options')) {
            self::send_json_response(false, ['message' => 'Permission denied']);
        }

        // Log successful verification.
        error_log("Nonce and permissions verified for action: $nonce_action");
    }

    /**
     * Retrieves the prefixed table name for a given key.
     *
     * @param string $key The table key (e.g., 'servers', 'available_maps').
     * @return string The fully qualified table name with WordPress prefix.
     */
    private static function get_table($key)
    {
        $tables = [
            'servers' => 'callofdutysimplercon_servers',
            'available_maps' => 'callofdutysimplercon_available_maps',
            'server_maps' => 'callofdutysimplercon_server_maps',
            'available_gts' => 'callofdutysimplercon_available_gts',
            'server_gts' => 'callofdutysimplercon_server_gts',
            'def_gts' => 'callofdutysimplercon_def_gts'
        ];
        return $GLOBALS['wpdb']->prefix . $tables[$key];
    }

    /**
     * Validates a server ID and returns it if valid.
     *
     * @param int $server_id The server ID to validate.
     * @return int The validated server ID.
     */
    private static function validate_server_id($server_id)
    {
        $server_id = intval($server_id);
        if (!$server_id) {
            error_log("Invalid server ID: $server_id");
            self::send_json_response(false, ['message' => 'Invalid server ID']);
        }
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
        $success ? wp_send_json_success($data) : wp_send_json_error($data);
    }

    /**
     * Handles adding or updating a server via AJAX.
     */
    public static function handle_add_or_update_server()
    {
        // Log the AJAX request for debugging.
        error_log('handle_add_or_update_server called with POST: ' . print_r($_POST, true));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_server');

        global $wpdb;
        $server_table = self::get_table('servers');

        // Sanitize and validate input data.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        $name = isset($_POST['server_name']) ? sanitize_text_field($_POST['server_name']) : '';
        $ip_hostname = isset($_POST['ip_hostname']) ? sanitize_text_field($_POST['ip_hostname']) : '';
        $port = isset($_POST['port']) ? intval($_POST['port']) : 0;
        $rcon_password = isset($_POST['rcon_password']) ? sanitize_text_field($_POST['rcon_password']) : '';

        // Check for missing required fields.
        if (empty($name) || empty($ip_hostname) || empty($port) || empty($rcon_password)) {
            error_log('Missing fields: name=' . $name . ', ip_hostname=' . $ip_hostname . ', port=' . $port . ', rcon_password=' . $rcon_password);
            self::send_json_response(false, ['message' => 'All fields are required']);
        }

        // Validate port number.
        if ($port < 1 || $port > 65535) {
            self::send_json_response(false, ['message' => 'Invalid port number']);
        }

        // Test RCON connection.
        global $cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id;
        [$cod_ip_hostname, $cod_port, $cod_rcon_password, $cod_server_id] = [$ip_hostname, $port, $rcon_password, $server_id];
        $rcon_result = json_decode(\codsimplercon\codsrcon_send_rcon_command("status"), true);
        if (!$rcon_result || !isset($rcon_result['success']) || !$rcon_result['success']) {
            $error = $rcon_result['error'] ?? 'Unknown RCON error';
            error_log("RCON test failed for $ip_hostname:$port - " . $error);
            self::send_json_response(false, ['message' => 'Failed to send RCON command: ' . $error]);
        }

        // Check for invalid RCON password responses.
        $invalidPasswordResponses = ["Invalid password.", "Bad rcon", "Bad rconpassword."];
        foreach ($invalidPasswordResponses as $invalidResponse) {
            if (isset($rcon_result['response']) && stripos($rcon_result['response'], $invalidResponse) !== false) {
                error_log("Invalid RCON password for $ip_hostname:$port - Response: " . $rcon_result['response']);
                self::send_json_response(false, ['message' => 'Invalid RCON password']);
            }
        }

        // Query server information.
        $query_result = json_decode(\codsimplercon\codsrcon_query_server(), true);
        if (!$query_result || !isset($query_result['success']) || !$query_result['success']) {
            $error = $query_result['error'] ?? 'Unknown query error';
            error_log("Query failed for $ip_hostname:$port - " . $error);
            self::send_json_response(false, ['message' => 'Failed to query server: ' . $error]);
        }

        // Prepare server data for database.
        $data = [
            'name' => $name,
            'ip_hostname' => $ip_hostname,
            'port' => $port,
            'rcon_password' => $rcon_password,
            'server_type' => $query_result['server_info']['gamename'] ?? 'Unknown',
            'server_hostname' => preg_replace('/\^[0-9]/', '', $query_result['server_info']['sv_hostname'] ?? 'Unknown')
        ];

        if ($server_id) {
            // Update existing server.
            $result = $wpdb->update($server_table, $data, ['id' => $server_id]);
            if ($result === false) {
                error_log("Update failed for server_id $server_id: " . $wpdb->last_error);
                self::send_json_response(false, ['message' => 'Failed to update server: ' . $wpdb->last_error]);
            }
            self::send_json_response(true, ['message' => 'Server updated successfully', 'server_id' => $server_id]);
        } else {
            // Add new server.
            error_log("Attempting to insert server: " . print_r($data, true));
            $result = $wpdb->insert($server_table, $data);
            if ($result === false) {
                error_log("Insert failed: " . $wpdb->last_error);
                self::send_json_response(false, ['message' => 'Failed to add server: ' . $wpdb->last_error]);
            }
            $server_id = $wpdb->insert_id;
            error_log("Server added with ID: $server_id");
            \codsimplercon\Database::populate_available_maps_and_gts($server_id, $data['server_type']);
            self::send_json_response(true, ['message' => 'Server added successfully', 'server_id' => $server_id]);
        }
    }

    /**
     * Fetches server details for editing via AJAX.
     */
    public static function codsrcon_edit_server()
    {
        // Log the AJAX request for debugging.
        error_log('codsrcon_edit_server handler called');

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_edit_server');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        global $wpdb;
        // Fetch server details from the database.
        $server = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ), ARRAY_A);

        // Log query result for debugging.
        error_log('Server query result: ' . print_r($server, true));

        // Send response with server data or error.
        self::send_json_response($server !== null, $server ?: ['message' => 'Server not found']);
    }

    /**
     * Deletes a server via AJAX.
     */
    public static function codsrcon_delete_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_delete_server');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        global $wpdb;
        // Delete server from the database.
        $result = $wpdb->delete(self::get_table('servers'), ['id' => $server_id], ['%d']);
        if ($result === false) {
            self::send_json_response(false, ['message' => 'Failed to delete server']);
        }

        // Clean up related database entries.
        \codsimplercon\Database::delete_server_entries($server_id);

        self::send_json_response(true, ['message' => 'Server deleted successfully']);
    }

    /**
     * Fetches maps associated with a server via AJAX.
     */
    public static function codsrcon_admin_fetch_maps()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_admin_fetch_maps');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        global $wpdb;
        // Fetch maps linked to the server.
        $maps = $wpdb->get_results($wpdb->prepare(
            "SELECT am.id, am.mp_name, am.mp_alias
             FROM " . self::get_table('available_maps') . " am
             JOIN " . self::get_table('server_maps') . " sm ON am.id = sm.map_id
             WHERE sm.server_id = %d",
            $server_id
        ), ARRAY_A);

        // Buffer output for the maps table.
        ob_start();
        ?>
        <table id="server-maps-table" class="codsr deque
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
                                <button type="button" class="codsrcon-button codsrcon-delete-button codsrcon-remove-map"
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
     * Fetches game types associated with a server via AJAX.
     */
    public static function codsrcon_admin_fetch_gts()
    {
        // Log the AJAX request for debugging.
        error_log('codsrcon_admin_fetch_gts called with server_id: ' . ($_POST['server_id'] ?? 'none'));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_admin_fetch_gts');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        global $wpdb;
        // Fetch game types linked to the server.
        $gts = $wpdb->get_results($wpdb->prepare(
            "SELECT ag.id, ag.gametype, ag.gt_alias
             FROM " . self::get_table('available_gts') . " ag
             JOIN " . self::get_table('server_gts') . " sg ON ag.id = sg.gt_id
             WHERE sg.server_id = %d",
            $server_id
        ), ARRAY_A);

        // Log query result for debugging.
        error_log('Gametypes query result: ' . print_r($gts, true));

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
                                <button type="button" class="codsrcon-button codsrcon-delete-button codsrcon-remove-gt"
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
     * Generates and returns fresh nonces for map and game type fetching via AJAX.
     */
    public static function codsrcon_get_fresh_nonces()
    {
        // Log the AJAX request for debugging.
        error_log("codsrcon_get_fresh_nonces called with POST: " . print_r($_POST, true));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_edit_server');

        // Send fresh nonces for map and game type fetching.
        self::send_json_response(true, [
            'nonce_fetch_maps' => wp_create_nonce('codsrcon_admin_fetch_maps'),
            'nonce_fetch_gts' => wp_create_nonce('codsrcon_admin_fetch_gts')
        ]);
    }

    /**
     * Adds a map to a server via AJAX, including optional image upload.
     */
    public static function codsrcon_add_map()
    {
        // Log the AJAX request and file upload for debugging.
        error_log('codsrcon_add_map called with POST: ' . print_r($_POST, true) . ' | FILES: ' . print_r($_FILES, true));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_map');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);
        error_log("Validated server_id: $server_id");

        global $wpdb;
        // Sanitize input data.
        $mp_name = isset($_POST['mp_name']) ? sanitize_text_field($_POST['mp_name']) : '';
        $map_alias = isset($_POST['map_alias']) ? sanitize_text_field($_POST['map_alias']) : '';
        $overwrite_confirmed = isset($_POST['overwrite_confirm']) && $_POST['overwrite_confirm'] === 'yes';

        // Check for missing required fields.
        if (empty($mp_name) || empty($map_alias)) {
            error_log("Missing required fields: mp_name='$mp_name', map_alias='$map_alias'");
            self::send_json_response(false, ['message' => 'Please enter both map name and alias']);
        }

        // Validate map name format.
        if (strpos($mp_name, 'mp_') !== 0) {
            error_log("Invalid map name: $mp_name (must start with 'mp_')");
            self::send_json_response(false, ['message' => "Map name must start with 'mp_' (e.g., mp_naout)"]);
        }

        // Fetch server game type.
        $server = $wpdb->get_row($wpdb->prepare(
            "SELECT server_type FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ));
        if (!$server) {
            error_log("Server not found: server_id=$server_id");
            self::send_json_response(false, ['message' => 'Server not found']);
        }
        $gameName = $server->server_type;

        $available_maps_table = self::get_table('available_maps');

        // Check for existing map with the same name but different alias.
        $existing_map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $available_maps_table WHERE mp_name = %s AND gamename = %s",
            $mp_name,
            $gameName
        ));

        if ($existing_map && $existing_map->mp_alias !== $map_alias) {
            if (!$overwrite_confirmed) {
                error_log("Map exists with different alias: $mp_name (current: {$existing_map->mp_alias}, new: $map_alias)");
                self::send_json_response(false, [
                    'message' => sprintf(
                        'A map with name "%s" already exists with alias "%s". Do you want to overwrite it with alias "%s"?',
                        $mp_name,
                        $existing_map->mp_alias,
                        $map_alias
                    ),
                    'confirm_required' => true
                ]);
            }
            // Overwrite existing map alias.
            $wpdb->update(
                $available_maps_table,
                ['mp_alias' => $map_alias],
                ['id' => $existing_map->id],
                ['%s'],
                ['%d']
            );
            $map_id = $existing_map->id;
            error_log("Updated existing map: id=$map_id, new alias=$map_alias");
        } else {
            // Check for exact match or add new map.
            $map = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $available_maps_table WHERE mp_name = %s AND mp_alias = %s AND gamename = %s",
                $mp_name,
                $map_alias,
                $gameName
            ));

            if (!$map) {
                $result = $wpdb->insert(
                    $available_maps_table,
                    ['mp_name' => $mp_name, 'mp_alias' => $map_alias, 'gamename' => $gameName],
                    ['%s', '%s', '%s']
                );
                if ($result === false) {
                    error_log("Failed to insert map: " . $wpdb->last_error);
                    self::send_json_response(false, ['message' => 'Error adding map: ' . $wpdb->last_error]);
                }
                $map_id = $wpdb->insert_id;
                error_log("Inserted new map: id=$map_id");
            } else {
                $map_id = $map->id;
                error_log("Map already exists: id=$map_id");
            }
        }

        // Link map to server.
        $server_maps_table = self::get_table('server_maps');
        $existing_server_map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $server_maps_table WHERE server_id = %d AND map_id = %d",
            $server_id,
            $map_id
        ));

        if ($existing_server_map) {
            error_log("Map already linked to server: server_id=$server_id, map_id=$map_id");
            self::send_json_response(false, ['message' => 'Map is already associated with this server']);
        }

        $result = $wpdb->insert(
            $server_maps_table,
            ['server_id' => $server_id, 'map_id' => $map_id],
            ['%d', '%d']
        );

        if ($result === false) {
            error_log("Failed to link map to server: " . $wpdb->last_error);
            self::send_json_response(false, ['message' => 'Error adding map to server: ' . $wpdb->last_error]);
        }

        // Handle image upload if provided.
        if (!empty($_FILES['map_image']['name'])) {
            $file = $_FILES['map_image'];
            $file_type = wp_check_filetype($file['name']);

            // Validate file type.
            if ($file_type['ext'] !== 'png' || $file_type['type'] !== 'image/png') {
                error_log("Invalid file type: " . $file_type['type']);
                self::send_json_response(false, ['message' => 'Invalid image format. Only PNG is allowed']);
            }

            // Map game names to folder names.
            $game_mapping = [
                'Call of Duty' => 'cod',
                'Call of Duty 2' => 'cod2',
                'Call of Duty 4' => 'cod4',
                'Call of Duty: World at War' => 'codwaw',
                'CoD:United Offensive' => 'coduo'
            ];
            $folder_name = isset($game_mapping[$gameName]) ? $game_mapping[$gameName] : 'default';
            $upload_dir = CODSIMPLERCON_PLUGIN_DIR . "assets/images/$folder_name/";

            // Create upload directory if it doesn't exist.
            if (!file_exists($upload_dir)) {
                wp_mkdir_p($upload_dir);
            }

            // Save and resize image.
            $new_filename = sanitize_file_name($mp_name) . '.png';
            $destination = $upload_dir . $new_filename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                error_log("Failed to move uploaded file to: $destination");
                self::send_json_response(false, ['message' => 'Error moving uploaded file']);
            }

            $image_editor = wp_get_image_editor($destination);
            if (is_wp_error($image_editor)) {
                error_log("Image editor error: " . $image_editor->get_error_message());
                self::send_json_response(false, ['message' => 'Error resizing image: ' . $image_editor->get_error_message()]);
            }

            $image_editor->resize(192, 192, true);
            $saved = $image_editor->save($destination);
            if (is_wp_error($saved)) {
                error_log("Failed to save resized image: " . $saved->get_error_message());
                self::send_json_response(false, ['message' => 'Error saving resized image']);
            }

            error_log("Image resized and saved to: $destination");
            self::send_json_response(true, ['message' => 'Map and image added successfully']);
        }

        // Success response if no image was uploaded.
        error_log("Map added successfully: server_id=$server_id, map_id=$map_id");
        self::send_json_response(true, ['message' => 'Map added successfully (no image provided)']);
    }

    /**
     * Removes a map from a server via AJAX.
     */
    public static function codsrcon_remove_map_from_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_remove_map_from_server');

        // Validate server and map IDs.
        $server_id = self::validate_server_id($_POST['server_id']);
        $map_id = intval($_POST['map_id']) ?: self::send_json_response(false, ['message' => 'Invalid map ID']);

        global $wpdb;
        // Delete the map-server association.
        $wpdb->delete(self::get_table('server_maps'), ['server_id' => $server_id, 'map_id' => $map_id], ['%d', '%d']);
        self::send_json_response(true, ['message' => 'Map removed successfully']);
    }

    /**
     * Adds a game type to a server via AJAX.
     */
    public static function codsrcon_add_gt_to_server()
    {
        // Log the AJAX request for debugging.
        error_log('codsrcon_add_gt_to_server called with POST: ' . print_r($_POST, true));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_gt_to_server');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize input data.
        $gametype = isset($_POST['gametype']) ? sanitize_text_field($_POST['gametype']) : '';
        $gt_alias = isset($_POST['gt_alias']) ? sanitize_text_field($_POST['gt_alias']) : '';

        // Check for missing required fields.
        if (empty($gametype) || empty($gt_alias)) {
            self::send_json_response(false, ['message' => 'Game type and alias are required']);
        }

        global $wpdb;
        // Fetch server game type.
        $server = $wpdb->get_row($wpdb->prepare(
            "SELECT server_type FROM " . self::get_table('servers') . " WHERE id = %d",
            $server_id
        ));
        if (!$server) {
            self::send_json_response(false, ['message' => 'Server not found']);
        }

        // Check if game type exists.
        $gt_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . self::get_table('available_gts') . " WHERE gametype = %s AND gamename = %s",
            $gametype,
            $server->server_type
        ));

        if (!$gt_id) {
            // Add new game type.
            $wpdb->insert(
                self::get_table('available_gts'),
                ['gametype' => $gametype, 'gt_alias' => $gt_alias, 'gamename' => $server->server_type],
                ['%s', '%s', '%s']
            );
            $gt_id = $wpdb->insert_id;
            if (!$gt_id) {
                self::send_json_response(false, ['message' => 'Failed to add game type: ' . $wpdb->last_error]);
            }
        }

        // Check if game type is already linked to the server.
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM " . self::get_table('server_gts') . " WHERE server_id = %d AND gt_id = %d",
            $server_id,
            $gt_id
        ));
        if ($exists) {
            self::send_json_response(false, ['message' => 'Game type already linked to this server']);
        }

        // Link game type to server.
        $result = $wpdb->insert(
            self::get_table('server_gts'),
            ['server_id' => $server_id, 'gt_id' => $gt_id],
            ['%d', '%d']
        );
        if ($result === false) {
            self::send_json_response(false, ['message' => 'Failed to link game type: ' . $wpdb->last_error]);
        }

        self::send_json_response(true, ['message' => 'Game type added successfully']);
    }

    /**
     * Removes a game type from a server via AJAX.
     */
    public static function codsrcon_remove_gt_from_server()
    {
        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_remove_gt_from_server');

        // Validate server and game type IDs.
        $server_id = self::validate_server_id($_POST['server_id']);
        $gt_id = intval($_POST['gt_id']) ?: self::send_json_response(false, ['message' => 'Invalid game type ID']);

        global $wpdb;
        // Delete the game type-server association.
        $wpdb->delete(self::get_table('server_gts'), ['server_id' => $server_id, 'gt_id' => $gt_id], ['%d', '%d']);
        self::send_json_response(true, ['message' => 'Game type removed successfully']);
    }

    /**
     * Adds default maps to a server via AJAX.
     */
    public static function codsrcon_add_defaults()
    {
        // Log the AJAX request for debugging.
        error_log('codsrcon_add_defaults called with POST: ' . print_r($_POST, true));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_defaults');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize game name.
        $gamename = sanitize_text_field($_POST['gamename']);

        // Check for missing game name.
        if (empty($gamename)) {
            error_log("No gamename provided for server_id: $server_id");
            self::send_json_response(false, ['message' => 'Game name is required']);
        }

        global $wpdb;
        try {
            // Populate default maps for the server.
            \codsimplercon\Database::populate_available_maps($server_id, $gamename);
            error_log("Default maps populated for server $server_id, gamename: $gamename");

            // Fetch existing maps to avoid duplicates.
            $existing_maps = $wpdb->get_col($wpdb->prepare(
                "SELECT map_id FROM " . self::get_table('server_maps') . " WHERE server_id = %d",
                $server_id
            ));

            // Link new default maps to the server.
            $new_maps = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM " . self::get_table('available_maps') . " WHERE gamename = %s AND id NOT IN (" . implode(',', array_fill(0, count($existing_maps), '%d')) . ")",
                array_merge([$gamename], $existing_maps)
            ), ARRAY_A);

            foreach ($new_maps as $map) {
                $result = $wpdb->insert(
                    self::get_table('server_maps'),
                    ['server_id' => $server_id, 'map_id' => $map['id']],
                    ['%d', '%d']
                );
                if ($result === false) {
                    error_log("Failed to link map {$map['id']} to server $server_id: " . $wpdb->last_error);
                    self::send_json_response(false, ['message' => 'Failed to link map: ' . $wpdb->last_error]);
                }
            }

            error_log("Default maps added successfully for server $server_id");
            self::send_json_response(true, ['message' => 'Default maps added']);
        } catch (Exception $e) {
            error_log("Exception in codsrcon_add_defaults: " . $e->getMessage());
            self::send_json_response(false, ['message' => 'Error adding defaults: ' . $e->getMessage()]);
        }
    }

    /**
     * Adds default game types to a server via AJAX.
     */
    public static function codsrcon_add_defaults_gt()
    {
        // Log the AJAX request for debugging.
        error_log('codsrcon_add_defaults_gt called with POST: ' . print_r($_POST, true));

        // Verify nonce and permissions.
        self::verify_nonce_and_permissions('codsrcon_add_defaults_gt');

        // Validate server ID.
        $server_id = self::validate_server_id($_POST['server_id']);

        // Sanitize game name.
        $gamename = sanitize_text_field($_POST['gamename']);

        // Check for missing game name.
        if (empty($gamename)) {
            error_log("No gamename provided for server_id: $server_id");
            self::send_json_response(false, ['message' => 'Game name is required']);
        }

        global $wpdb;
        try {
            // Populate default game types for the server.
            \codsimplercon\Database::populate_available_gts($server_id, $gamename);
            error_log("Default gametypes populated for server $server_id, gamename: $gamename");

            // Fetch existing game types to avoid duplicates.
            $existing_gts = $wpdb->get_col($wpdb->prepare(
                "SELECT gt_id FROM " . self::get_table('server_gts') . " WHERE server_id = %d",
                $server_id
            ));

            // Link new default game types to the server.
            $new_gts = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM " . self::get_table('available_gts') . " WHERE gamename = %s AND id NOT IN (" . implode(',', array_fill(0, count($existing_gts), '%d')) . ")",
                array_merge([$gamename], $existing_gts)
            ), ARRAY_A);

            foreach ($new_gts as $gt) {
                $result = $wpdb->insert(
                    self::get_table('server_gts'),
                    ['server_id' => $server_id, 'gt_id' => $gt['id']],
                    ['%d', '%d']
                );
                if ($result === false) {
                    error_log("Failed to link gametype {$gt['id']} to server $server_id: " . $wpdb->last_error);
                    self::send_json_response(false, ['message' => 'Failed to link gametype: ' . $wpdb->last_error]);
                }
            }

            error_log("Default gametypes added successfully for server $server_id");
            self::send_json_response(true, ['message' => 'Default game types added']);
        } catch (Exception $e) {
            error_log("Exception in codsrcon_add_defaults_gt: " . $e->getMessage());
            self::send_json_response(false, ['message' => 'Error adding default gametypes: ' . $e->getMessage()]);
        }
    }
}