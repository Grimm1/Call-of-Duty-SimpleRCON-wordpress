<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Class for managing the ban management page and related AJAX handlers in the CoD Simple RCON plugin.
 */
class Cod_Ban_Manager_Page
{
    /**
     * Initializes the ban manager by registering AJAX actions.
     */
    public static function init()
    {
        // Register AJAX handlers for fetching bans and handling unban requests.
        add_action('wp_ajax_codsrcon_fetch_bans', [__CLASS__, 'fetch_bans']);
        add_action('wp_ajax_codsrcon_unban_user', [__CLASS__, 'handle_unban_request']);
    }

    /**
     * Fetches bans for a specific server via AJAX and returns an HTML table.
     */
    public static function fetch_bans()
    {
        global $wpdb;

        // Validate and sanitize server ID from POST data.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        if (!$server_id) {
            error_log("Cod_Ban_Manager_Page: Invalid server ID received in fetch_bans");
            wp_send_json_error(['message' => 'Invalid server ID'], 400);
        }

        // Get the bans table name with WordPress prefix.
        $bans_table_name = $wpdb->prefix . 'callofdutysimplercon_bans';
        error_log("Cod_Ban_Manager_Page: Querying bans for server_id = $server_id in table $bans_table_name");

        // Fetch bans for the specified server.
        $bans = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $bans_table_name WHERE server_id = %d", $server_id),
            ARRAY_A
        );

        // Log any database errors.
        if ($wpdb->last_error) {
            error_log("Cod_Ban_Manager_Page: Database error in fetch_bans: " . $wpdb->last_error);
        }

        // Log the number of bans found.
        if (empty($bans)) {
            error_log("Cod_Ban_Manager_Page: No bans found for server_id = $server_id");
        } else {
            error_log("Cod_Ban_Manager_Page: Found " . count($bans) . " bans for server_id = $server_id");
        }

        // Buffer output for the bans table.
        ob_start();
        ?>
        <table class="codsrcon-server-list codsrcon-table">
            <thead>
                <tr>
                    <th style="width: 200px;"><?php echo esc_html__('Ban Name', 'codsimplercon'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Ban Date', 'codsimplercon'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Actions', 'codsimplercon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bans)): ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html__('No bans found.', 'codsimplercon'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bans as $ban): ?>
                        <tr>
                            <td><?php echo esc_html($ban['ban_name']); ?></td>
                            <td><?php echo esc_html($ban['ban_date']); ?></td>
                            <td>
                                <button class="unban-button"
                                        data-server-id="<?php echo esc_attr($ban['server_id']); ?>"
                                        data-ban-name="<?php echo esc_attr($ban['ban_name']); ?>">
                                    <?php echo esc_html__('Unban', 'codsimplercon'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        $html = ob_get_clean();

        // Send the table HTML as a JSON response.
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Handles an unban request via AJAX by sending an RCON command and removing the ban from the database.
     */
    public static function handle_unban_request()
    {
        global $wpdb;

        // Validate and sanitize input data.
        $server_id = isset($_POST['server_id']) ? intval($_POST['server_id']) : 0;
        $ban_name = isset($_POST['ban_name']) ? sanitize_text_field($_POST['ban_name']) : '';

        // Log the unban request for debugging.
        error_log("Cod_Ban_Manager_Page: Received unban request for server_id = $server_id, ban_name = $ban_name");

        // Check for missing or invalid inputs.
        if (!$server_id || !$ban_name) {
            error_log("Cod_Ban_Manager_Page: Invalid server ID or ban name in handle_unban_request");
            wp_send_json_error(['message' => 'Invalid server ID or ban name'], 400);
        }

        // Include the server query class for RCON commands.
        require_once CODSIMPLERCON_PLUGIN_DIR . 'includes/class-codsrcon-server-query.php';

        // Initialize server query and prepare RCON command.
        $server_query = new Cod_Server_Query();
        $command = 'unbanuser "' . $ban_name . '"'; // Wrap player name in quotes for RCON.
        $isrcon = 1; // Flag indicating an RCON command.

        // Log the RCON command being sent.
        error_log("Cod_Ban_Manager_Page: Sending RCON command '$command' to server ID $server_id with isrcon = $isrcon");

        // Execute the RCON command.
        $result = $server_query->send_command($server_id, $command, $isrcon);

        if ($result) {
            // Remove the ban from the database.
            $bans_table_name = $wpdb->prefix . 'callofdutysimplercon_bans';
            $delete_result = $wpdb->delete(
                $bans_table_name,
                ['server_id' => $server_id, 'ban_name' => $ban_name],
                ['%d', '%s']
            );

            if ($delete_result === false) {
                error_log("Cod_Ban_Manager_Page: Failed to delete ban from database for $ban_name. DB Error: " . $wpdb->last_error);
                wp_send_json_error(['message' => 'Failed to remove ban from database'], 500);
            }

            // Log successful unban.
            error_log("Cod_Ban_Manager_Page: User '$ban_name' unbanned successfully from server ID $server_id");
            wp_send_json_success(['message' => 'User unbanned successfully']);
        } else {
            // Log RCON command failure.
            error_log("Cod_Ban_Manager_Page: Failed to execute RCON command for server ID $server_id");
            wp_send_json_error(['message' => 'Failed to unban user'], 500);
        }
    }

    /**
     * Renders the main ban management admin page with a server selector and bans table.
     */
    public static function render()
    {
        global $wpdb;

        // Get the servers table name with WordPress prefix.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';

        // Fetch all servers for the dropdown.
        $servers = $wpdb->get_results(
            "SELECT id, name, ip_hostname, port, server_type, server_hostname FROM $table_name",
            ARRAY_A
        );

        ?>
        <div class="wrap">
            <div id="wrapper_codsrcon">
                <h1><?php echo esc_html__('Call of Duty Simple Rcon - Ban Manager', 'codsimplercon'); ?></h1>
                <p style="padding-left: 20px; position: relative; top: -10px;">
                    <b><?php echo esc_html__('Manage your Call of Duty servers ban lists.', 'codsimplercon'); ?></b>
                </p>
                <div class="codsrcon-server-list-container">
                    <h4><?php echo esc_html__('Only bans issued by this plugin can be managed here. For other bans, edit the relevant game\'s ban.txt file manually.', 'codsimplercon'); ?></h4>
                    <select id="cod-server-selector">
                        <option value=""><?php echo esc_html__('Select a Call of Duty Server', 'codsimplercon'); ?></option>
                        <?php foreach ($servers as $server): ?>
                            <option value="<?php echo esc_attr($server['id']); ?>">
                                <?php echo esc_html($server['server_hostname']) . ' (' . esc_html($server['server_type']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div style="margin-top: 10px;"></div>
                    <div id="cod-bans-table-container">
                        <?php if (!empty($servers)): ?>
                            <?php self::render_bans_table($servers[0]['id']); ?>
                        <?php else: ?>
                            <p><?php echo esc_html__('No servers available. Please add a server first.', 'codsimplercon'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renders the bans table for a specific server.
     *
     * @param int $server_id The ID of the server to display bans for.
     */
    private static function render_bans_table($server_id)
    {
        global $wpdb;

        // Get the bans table name with WordPress prefix.
        $bans_table_name = $wpdb->prefix . 'callofdutysimplercon_bans';

        // Fetch bans for the specified server.
        $bans = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $bans_table_name WHERE server_id = %d", $server_id),
            ARRAY_A
        );

        ?>
        <table class="codsrcon-server-list codsrcon-table">
            <thead>
                <tr>
                    <th style="width: 200px;"><?php echo esc_html__('Ban Name', 'codsimplercon'); ?></th>
                    <th style="width: 150px;"><?php echo esc_html__('Ban Date', 'codsimplercon'); ?></th>
                    <th style="width: 100px;"><?php echo esc_html__('Actions', 'codsimplercon'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bans)): ?>
                    <tr>
                        <td colspan="3"><?php echo esc_html__('No bans found.', 'codsimplercon'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bans as $ban): ?>
                        <tr>
                            <td><?php echo esc_html($ban['ban_name']); ?></td>
                            <td><?php echo esc_html($ban['ban_date']); ?></td>
                            <td>
                                <button class="unban-button"
                                        data-server-id="<?php echo esc_attr($ban['server_id']); ?>"
                                        data-ban-name="<?php echo esc_attr($ban['ban_name']); ?>">
                                    <?php echo esc_html__('Unban', 'codsimplercon'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renders a placeholder page for dynamic ban manager content loaded via JavaScript.
     */
    public static function render_page()
    {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ban Manager', 'codsimplercon'); ?></h1>
            <div id="ban-manager-container">
                <!-- Ban Manager content will be dynamically loaded by JavaScript -->
            </div>
        </div>
        <?php
    }
}

// Initialize the ban manager.
Cod_Ban_Manager_Page::init();