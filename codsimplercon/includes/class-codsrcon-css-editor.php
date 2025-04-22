<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Manages the CSS editor page for customizing the appearance of Call of Duty server monitors.
 */
class Cod_CSS_Editor_Page
{
    /**
     * Initializes the CSS editor by registering necessary hooks.
     */
    public static function init()
    {
        // Register settings on admin initialization.
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Placeholder for registering settings. Currently empty as settings are saved to a file.
     */
    public static function register_settings()
    {
        // No settings registered with WordPress; CSS is saved directly to a file.
    }

    /**
     * Renders the CSS editor admin page with a form and live preview.
     */
    public static function render()
    {
        // Restrict access to users with 'manage_options' capability.
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'codsimplercon'), 403);
        }

        // Define default CSS settings for the server monitor.
        $default_css = [
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
        ];

        // Define the file path for storing CSS settings.
        $css_settings_file = CODSIMPLERCON_PLUGIN_DIR . 'assets/css/codsrcon-css-settings.php';

        // Handle form submission for saving or resetting CSS settings.
        if (isset($_POST['codsrcon_css_editor_nonce']) && wp_verify_nonce($_POST['codsrcon_css_editor_nonce'], 'codsrcon_css_editor')) {
            if (isset($_POST['reset_defaults']) && $_POST['reset_defaults'] === 'reset') {
                // Reset to default CSS settings.
                $custom_css = $default_css;
            } else {
                // Sanitize and validate submitted CSS values, falling back to defaults for invalid inputs.
                $custom_css = [
                    'font_family' => sanitize_text_field($_POST['font_family'] ?? $default_css['font_family']),
                    'font_weight' => sanitize_text_field($_POST['font_weight'] ?? $default_css['font_weight']),
                    'header_background_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['header_background_color'] ?? '') ? $_POST['header_background_color'] : $default_css['header_background_color'],
                    'header_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['header_text_color'] ?? '') ? $_POST['header_text_color'] : $default_css['header_text_color'],
                    'header_height' => sanitize_text_field($_POST['header_height'] ?? $default_css['header_height']),
                    'game_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['game_text_color'] ?? '') ? $_POST['game_text_color'] : $default_css['game_text_color'],
                    'game_font_size' => sanitize_text_field($_POST['game_font_size'] ?? $default_css['game_font_size']),
                    'ip_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['ip_text_color'] ?? '') ? $_POST['ip_text_color'] : $default_css['ip_text_color'],
                    'ip_font_size' => sanitize_text_field($_POST['ip_font_size'] ?? $default_css['ip_font_size']),
                    'gametype_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['gametype_text_color'] ?? '') ? $_POST['gametype_text_color'] : $default_css['gametype_text_color'],
                    'gametype_font_size' => sanitize_text_field($_POST['gametype_font_size'] ?? $default_css['gametype_font_size']),
                    'image_border_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['image_border_color'] ?? '') ? $_POST['image_border_color'] : $default_css['image_border_color'],
                    'image_border_width' => sanitize_text_field($_POST['image_border_width'] ?? $default_css['image_border_width']),
                    'image_border_radius' => sanitize_text_field($_POST['image_border_radius'] ?? $default_css['image_border_radius']),
                    'mapname_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['mapname_text_color'] ?? '') ? $_POST['mapname_text_color'] : $default_css['mapname_text_color'],
                    'mapname_font_size' => sanitize_text_field($_POST['mapname_font_size'] ?? $default_css['mapname_font_size']),
                    'players_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['players_text_color'] ?? '') ? $_POST['players_text_color'] : $default_css['players_text_color'],
                    'players_font_size' => sanitize_text_field($_POST['players_font_size'] ?? $default_css['players_font_size']),
                    'monitor_container_width' => sanitize_text_field($_POST['monitor_container_width'] ?? $default_css['monitor_container_width']),
                    'monitor_container_background_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['monitor_container_background_color'] ?? '') ? $_POST['monitor_container_background_color'] : $default_css['monitor_container_background_color'],
                    'monitor_container_border_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['monitor_container_border_color'] ?? '') ? $_POST['monitor_container_border_color'] : $default_css['monitor_container_border_color'],
                    'monitor_container_border_width' => sanitize_text_field($_POST['monitor_container_border_width'] ?? $default_css['monitor_container_border_width']),
                    'monitor_container_border_radius' => sanitize_text_field($_POST['monitor_container_border_radius'] ?? $default_css['monitor_container_border_radius']),
                    'playerlist_odd_background_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['playerlist_odd_background_color'] ?? '') ? $_POST['playerlist_odd_background_color'] : $default_css['playerlist_odd_background_color'],
                    'playerlist_even_background_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['playerlist_even_background_color'] ?? '') ? $_POST['playerlist_even_background_color'] : $default_css['playerlist_even_background_color'],
                    'playerlist_odd_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['playerlist_odd_text_color'] ?? '') ? $_POST['playerlist_odd_text_color'] : $default_css['playerlist_odd_text_color'],
                    'playerlist_odd_font_size' => sanitize_text_field($_POST['playerlist_odd_font_size'] ?? $default_css['playerlist_odd_font_size']),
                    'playerlist_even_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['playerlist_even_text_color'] ?? '') ? $_POST['playerlist_even_text_color'] : $default_css['playerlist_even_text_color'],
                    'playerlist_even_font_size' => sanitize_text_field($_POST['playerlist_even_font_size'] ?? $default_css['playerlist_even_font_size']),
                    'playerlist_header_background_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['playerlist_header_background_color'] ?? '') ? $_POST['playerlist_header_background_color'] : $default_css['playerlist_header_background_color'],
                    'playerlist_header_text_color' => preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $_POST['playerlist_header_text_color'] ?? '') ? $_POST['playerlist_header_text_color'] : $default_css['playerlist_header_text_color'],
                    'playerlist_header_font_size' => sanitize_text_field($_POST['playerlist_header_font_size'] ?? $default_css['playerlist_header_font_size']),
                ];
            }

            // Save CSS settings to the settings file.
            $file_content = '<?php return ' . var_export($custom_css, true) . ';';
            if (file_put_contents($css_settings_file, $file_content) !== false) {
                // Invalidate OPcache to ensure the new settings are loaded.
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($css_settings_file, true);
                }
                // Display success message.
                echo '<div class="updated"><p>' . esc_html__('CSS updated successfully.', 'codsimplercon') . '</p></div>';
                // Reload settings to reflect saved values.
                $custom_css = include $css_settings_file;
            } else {
                // Display error message and log failure.
                echo '<div class="error"><p>' . esc_html__('Failed to save CSS settings. Check file permissions.', 'codsimplercon') . '</p></div>';
                error_log("Cod_CSS_Editor_Page: Failed to save CSS settings to $css_settings_file");
            }
        }

        // Load existing CSS settings or fall back to defaults.
        $custom_css = file_exists($css_settings_file) && is_readable($css_settings_file) ? include $css_settings_file : $default_css;

        ?>
        <div class="wrap">
            <div class="wrapper_codsrcon">
                <div class="csspicker" style="display: flex; flex-direction: column;">
                    <h1><?php esc_html_e('CoD Simple RCON CSS Editor', 'codsimplercon'); ?></h1>
                    <p style="padding-left: 20px; position: relative; top: -20px;">
                        <b><?php esc_html_e('Customize the appearance of the Call of Duty server monitors.', 'codsimplercon'); ?></b>
                    </p>
                    <form id="css-editor-form" method="post" action="" style="display: flex; flex-direction: row; width: 100%;">
                        <?php wp_nonce_field('codsrcon_css_editor', 'codsrcon_css_editor_nonce'); ?>

                        <!-- Main Body Settings -->
                        <div class="flex-common table-1">
                            <h2 style="color: blue;"><?php esc_html_e('Main Body', 'codsimplercon'); ?></h2>
                            <table class="form-table-ccsed">
                                <tr class="flex-row">
                                    <th scope="row"><label for="font_family"><?php esc_html_e('Font Family', 'codsimplercon'); ?></label></th>
                                    <td>
                                        <select name="font_family" id="font_family" class="regular-text">
                                            <option value="Arial" <?php selected($custom_css['font_family'], 'Arial'); ?>>Arial</option>
                                            <option value="Verdana" <?php selected($custom_css['font_family'], 'Verdana'); ?>>Verdana</option>
                                            <option value="Helvetica" <?php selected($custom_css['font_family'], 'Helvetica'); ?>>Helvetica</option>
                                            <option value="Times New Roman" <?php selected($custom_css['font_family'], 'Times New Roman'); ?>>Times New Roman</option>
                                            <option value="Georgia" <?php selected($custom_css['font_family'], 'Georgia'); ?>>Georgia</option>
                                            <option value="Courier New" <?php selected($custom_css['font_family'], 'Courier New'); ?>>Courier New</option>
                                            <option value="Tahoma" <?php selected($custom_css['font_family'], 'Tahoma'); ?>>Tahoma</option>
                                            <option value="Trebuchet MS" <?php selected($custom_css['font_family'], 'Trebuchet MS'); ?>>Trebuchet MS</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="font_weight"><?php esc_html_e('Font Weight', 'codsimplercon'); ?></label></th>
                                    <td>
                                        <select name="font_weight" id="font_weight" class="regular-text">
                                            <option value="400" <?php selected($custom_css['font_weight'], '400'); ?>>Normal (400)</option>
                                            <option value="700" <?php selected($custom_css['font_weight'], '700'); ?>>Bold (700)</option>
                                            <option value="300" <?php selected($custom_css['font_weight'], '300'); ?>>Light (300)</option>
                                            <option value="600" <?php selected($custom_css['font_weight'], '600'); ?>>Semi-Bold (600)</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="monitor_container_width"><?php esc_html_e('Overall Width', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="monitor_container_width" id="monitor_container_width" value="<?php echo esc_attr($custom_css['monitor_container_width']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="monitor_container_background_color"><?php esc_html_e('Background Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="monitor_container_background_color" id="monitor_container_background_color" value="<?php echo esc_attr($custom_css['monitor_container_background_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="monitor_container_border_color"><?php esc_html_e('Border Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="monitor_container_border_color" id="monitor_container_border_color" value="<?php echo esc_attr($custom_css['monitor_container_border_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="monitor_container_border_width"><?php esc_html_e('Border Width', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="monitor_container_border_width" id="monitor_container_border_width" value="<?php echo esc_attr($custom_css['monitor_container_border_width']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="monitor_container_border_radius"><?php esc_html_e('Border Radius', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="monitor_container_border_radius" id="monitor_container_border_radius" value="<?php echo esc_attr($custom_css['monitor_container_border_radius']); ?>" class="regular-text"></td>
                                </tr>
                            </table>

                            <!-- Text Settings -->
                            <h2 style="color: blue;"><?php esc_html_e('Text', 'codsimplercon'); ?></h2>
                            <table class="form-table-ccsed">
                                <tr class="flex-row">
                                    <th scope="row"><label for="game_text_color"><?php esc_html_e('Game Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="game_text_color" id="game_text_color" value="<?php echo esc_attr($custom_css['game_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="game_font_size"><?php esc_html_e('Game Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="game_font_size" id="game_font_size" value="<?php echo esc_attr($custom_css['game_font_size']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="ip_text_color"><?php esc_html_e('IP Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="ip_text_color" id="ip_text_color" value="<?php echo esc_attr($custom_css['ip_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="ip_font_size"><?php esc_html_e('IP Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="ip_font_size" id="ip_font_size" value="<?php echo esc_attr($custom_css['ip_font_size']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="gametype_text_color"><?php esc_html_e('Gametype Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="gametype_text_color" id="gametype_text_color" value="<?php echo esc_attr($custom_css['gametype_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="gametype_font_size"><?php esc_html_e('Gametype Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="gametype_font_size" id="gametype_font_size" value="<?php echo esc_attr($custom_css['gametype_font_size']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="mapname_text_color"><?php esc_html_e('Map Name Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="mapname_text_color" id="mapname_text_color" value="<?php echo esc_attr($custom_css['mapname_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="mapname_font_size"><?php esc_html_e('Map Name Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="mapname_font_size" id="mapname_font_size" value="<?php echo esc_attr($custom_css['mapname_font_size']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="players_text_color"><?php esc_html_e('Players Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="players_text_color" id="players_text_color" value="<?php echo esc_attr($custom_css['players_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="players_font_size"><?php esc_html_e('Players Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="players_font_size" id="players_font_size" value="<?php echo esc_attr($custom_css['players_font_size']); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Header and Image Box Settings -->
                        <div class="flex-common table-2">
                            <h2 style="color: blue;"><?php esc_html_e('Header', 'codsimplercon'); ?></h2>
                            <table class="form-table-ccsed">
                                <tr class="flex-row">
                                    <th scope="row"><label for="header_background_color"><?php esc_html_e('Background Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="header_background_color" id="header_background_color" value="<?php echo esc_attr($custom_css['header_background_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="header_text_color"><?php esc_html_e('Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="header_text_color" id="header_text_color" value="<?php echo esc_attr($custom_css['header_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="header_height"><?php esc_html_e('Height', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="header_height" id="header_height" value="<?php echo esc_attr($custom_css['header_height']); ?>" class="regular-text"></td>
                                </tr>
                            </table>

                            <h2 style="color: blue;"><?php esc_html_e('Image Box', 'codsimplercon'); ?></h2>
                            <table class="form-table-ccsed">
                                <tr class="flex-row">
                                    <th scope="row"><label for="image_border_color"><?php esc_html_e('Border Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="image_border_color" id="image_border_color" value="<?php echo esc_attr($custom_css['image_border_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="image_border_width"><?php esc_html_e('Border Width', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="image_border_width" id="image_border_width" value="<?php echo esc_attr($custom_css['image_border_width']); ?>" class="regular-text"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="image_border_radius"><?php esc_html_e('Border Radius', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="image_border_radius" id="image_border_radius" value="<?php echo esc_attr($custom_css['image_border_radius']); ?>" class="regular-text"></td>
                                </tr>
                            </table>

                            <!-- Player List Settings -->
                            <h2 style="color: blue;"><?php esc_html_e('Player List', 'codsimplercon'); ?></h2>
                            <table class="form-table-ccsed">
                                <tr>
                                    <th colspan="2" style="color: blue; text-align: left; padding-left: 25px;"><?php esc_html_e('Table Header', 'codsimplercon'); ?></th>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_header_background_color"><?php esc_html_e('Background Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="playerlist_header_background_color" id="playerlist_header_background_color" value="<?php echo esc_attr($custom_css['playerlist_header_background_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_header_text_color"><?php esc_html_e('Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="playerlist_header_text_color" id="playerlist_header_text_color" value="<?php echo esc_attr($custom_css['playerlist_header_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_header_font_size"><?php esc_html_e('Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="playerlist_header_font_size" id="playerlist_header_font_size" value="<?php echo esc_attr($custom_css['playerlist_header_font_size']); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th colspan="2" style="color: blue; text-align: left; padding-left: 25px;"><?php esc_html_e('Odd Rows', 'codsimplercon'); ?></th>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_odd_background_color"><?php esc_html_e('Background Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="playerlist_odd_background_color" id="playerlist_odd_background_color" value="<?php echo esc_attr($custom_css['playerlist_odd_background_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_odd_text_color"><?php esc_html_e('Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="playerlist_odd_text_color" id="playerlist_odd_text_color" value="<?php echo esc_attr($custom_css['playerlist_odd_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_odd_font_size"><?php esc_html_e('Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="playerlist_odd_font_size" id="playerlist_odd_font_size" value="<?php echo esc_attr($custom_css['playerlist_odd_font_size']); ?>" class="regular-text"></td>
                                </tr>
                                <tr>
                                    <th colspan="2" style="color: blue; text-align: left; padding-left: 25px;"><?php esc_html_e('Even Rows', 'codsimplercon'); ?></th>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_even_background_color"><?php esc_html_e('Background Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="playerlist_even_background_color" id="playerlist_even_background_color" value="<?php echo esc_attr($custom_css['playerlist_even_background_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_even_text_color"><?php esc_html_e('Text Color', 'codsimplercon'); ?></label></th>
                                    <td><input type="text" name="playerlist_even_text_color" id="playerlist_even_text_color" value="<?php echo esc_attr($custom_css['playerlist_even_text_color']); ?>" class="regular-text wp-color-picker-field"></td>
                                </tr>
                                <tr class="flex-row">
                                    <th scope="row"><label for="playerlist_even_font_size"><?php esc_html_e('Font Size', 'codsimplercon'); ?></label></th>
                                    <td><input class="pixbox" type="text" name="playerlist_even_font_size" id="playerlist_even_font_size" value="<?php echo esc_attr($custom_css['playerlist_even_font_size']); ?>" class="regular-text"></td>
                                </tr>
                            </table>
                        </div>

                        <!-- Preview Section -->
                        <div class="flex-common table-3">
                            <h2 style="color: blue;"><?php esc_html_e('Preview', 'codsimplercon'); ?></h2>
                            <div id="codsrcon-monitor-preview" class="codsrcon-monitor-container">
                                <div class="codsrcon-monitor-header">
                                    <div class="codsrcon-monitor-overlay" style="background-image: url('<?php echo esc_url(plugins_url('assets/images/cod/icon.png', dirname(__FILE__))); ?>'); position: absolute !important; top: 3px !important; right: 3px !important; width: 48px !important; height: 48px !important; z-index: 10 !important; background-size: 48px 48px !important; background-repeat: no-repeat !important;"></div>
                                    <div class="codsrcon-monitor-sv-hostname"><?php esc_html_e('CoD Server', 'codsimplercon'); ?></div>
                                </div>
                                <div class="codsrcon-monitor-gamename"><?php esc_html_e('Call of Duty', 'codsimplercon'); ?></div>
                                <div class="codsrcon-monitor-ip"><?php esc_html_e('example.com:28960', 'codsimplercon'); ?></div>
                                <br>
                                <div class="codsrcon-monitor-gametype">
                                    <?php esc_html_e('Playing:', 'codsimplercon'); ?>
                                    <span class="codsrcon-monitor-gametype-value"><?php esc_html_e('Team Deathmatch', 'codsimplercon'); ?></span>
                                </div>
                                <div class="codsrcon-monitor-image-container">
                                    <div class="codsrcon-monitor-image" style="background-image: url('<?php echo esc_url(plugins_url('assets/images/cod/mp_carentan.png', dirname(__FILE__))); ?>');"></div>
                                </div>
                                <div class="codsrcon-monitor-mapname"><?php esc_html_e('On: Carentan', 'codsimplercon'); ?></div>
                                <div class="codsrcon-monitor-players">
                                    <?php esc_html_e('Players:', 'codsimplercon'); ?>
                                    <span class="codsrcon-monitor-playercount">4/20</span>
                                </div>
                                <div class="codsrcon-monitor-playerlist">
                                    <table class="codsrcon-monitor-playerlist-table">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Name', 'codsimplercon'); ?></th>
                                                <th><?php esc_html_e('Score', 'codsimplercon'); ?></th>
                                                <th><?php esc_html_e('Ping', 'codsimplercon'); ?></th>
                                            </tr>
                                        </thead>
                                        <tbody class="codsrcon-monitor-player-list">
                                            <tr class="codsrcon-monitor-player-row">
                                                <td class="codsrcon-monitor-player-name"><?php esc_html_e('Soldier1', 'codsimplercon'); ?></td>
                                                <td class="codsrcon-monitor-player-score">15</td>
                                                <td class="codsrcon-monitor-player-ping">30</td>
                                            </tr>
                                            <tr class="codsrcon-monitor-player-row">
                                                <td class="codsrcon-monitor-player-name"><?php esc_html_e('Soldier2', 'codsimplercon'); ?></td>
                                                <td class="codsrcon-monitor-player-score">25</td>
                                                <td class="codsrcon-monitor-player-ping">45</td>
                                            </tr>
                                            <tr class="codsrcon-monitor-player-row">
                                                <td class="codsrcon-monitor-player-name"><?php esc_html_e('Soldier3', 'codsimplercon'); ?></td>
                                                <td class="codsrcon-monitor-player-score">10</td>
                                                <td class="codsrcon-monitor-player-ping">60</td>
                                            </tr>
                                            <tr class="codsrcon-monitor-player-row">
                                                <td class="codsrcon-monitor-player-name"><?php esc_html_e('Soldier4', 'codsimplercon'); ?></td>
                                                <td class="codsrcon-monitor-player-score">30</td>
                                                <td class="codsrcon-monitor-player-ping">20</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <input type="hidden" name="reset_defaults" id="reset_defaults_hidden" value="">
                        </div>
                    </form>

                    <!-- Save and Reset Buttons -->
                    <div style="padding: 20px 0 0 20px;">
                        <button id="save-css-btn" class="button-primary"><?php esc_html_e('Save CSS', 'codsimplercon'); ?></button>
                        <button id="reset-defaults-btn" class="button-secondary"><?php esc_html_e('Reset to Defaults', 'codsimplercon'); ?></button>
                    </div>
                </div>

                <!-- Inline CSS for Preview -->
                <style>
                    .wrap h1 {
                        font-size: 28px !important;
                        font-weight: 600 !important;
                        font-style: italic !important;
                        width: 100% !important;
                        color: #1d2327 !important;
                        border-left: 4px solid rgb(130, 130, 243) !important;
                        padding: 10px 15px !important;
                        margin: 20px 0 !important;
                        border-radius: 5px !important;
                        position: relative;
                    }

                    .wrap h1::after {
                        content: "";
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        width: 100%;
                        height: 4px;
                        background: linear-gradient(to right, rgb(130, 130, 243), transparent);
                        border-radius: 0 0 5px 5px;
                    }

                    #codsrcon-monitor-preview {
                        width: <?php echo esc_attr($custom_css['monitor_container_width']); ?> !important;
                        border: <?php echo esc_attr($custom_css['monitor_container_border_width']); ?> solid <?php echo esc_attr($custom_css['monitor_container_border_color']); ?> !important;
                        border-radius: <?php echo esc_attr($custom_css['monitor_container_border_radius']); ?> !important;
                        background-color: <?php echo esc_attr($custom_css['monitor_container_background_color']); ?> !important;
                        font-family: <?php echo esc_attr($custom_css['font_family']); ?> !important;
                        font-weight: <?php echo esc_attr($custom_css['font_weight']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-header {
                        background-color: <?php echo esc_attr($custom_css['header_background_color']); ?> !important;
                        color: <?php echo esc_attr($custom_css['header_text_color']); ?> !important;
                        height: <?php echo esc_attr($custom_css['header_height']); ?> !important;
                        position: relative !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-sv-hostname {
                        line-height: <?php echo esc_attr($custom_css['header_height']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-gamename {
                        color: <?php echo esc_attr($custom_css['game_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['game_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-ip {
                        color: <?php echo esc_attr($custom_css['ip_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['ip_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-gametype {
                        color: <?php echo esc_attr($custom_css['gametype_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['gametype_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-image {
                        border: <?php echo esc_attr($custom_css['image_border_width']); ?> solid <?php echo esc_attr($custom_css['image_border_color']); ?> !important;
                        border-radius: <?php echo esc_attr($custom_css['image_border_radius']); ?> !important;
                        background-size: cover !important;
                        background-position: center !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-mapname {
                        color: <?php echo esc_attr($custom_css['mapname_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['mapname_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-players {
                        color: <?php echo esc_attr($custom_css['players_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['players_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-playerlist-table thead tr {
                        background-color: <?php echo esc_attr($custom_css['playerlist_header_background_color']); ?> !important;
                        color: <?php echo esc_attr($custom_css['playerlist_header_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['playerlist_header_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-playerlist-table .codsrcon-monitor-player-row:nth-child(odd) {
                        background-color: <?php echo esc_attr($custom_css['playerlist_odd_background_color']); ?> !important;
                        color: <?php echo esc_attr($custom_css['playerlist_odd_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['playerlist_odd_font_size']); ?> !important;
                    }

                    #codsrcon-monitor-preview .codsrcon-monitor-playerlist-table .codsrcon-monitor-player-row:nth-child(even) {
                        background-color: <?php echo esc_attr($custom_css['playerlist_even_background_color']); ?> !important;
                        color: <?php echo esc_attr($custom_css['playerlist_even_text_color']); ?> !important;
                        font-size: <?php echo esc_attr($custom_css['playerlist_even_font_size']); ?> !important;
                    }

                    .flex-common {
                        flex: 1;
                        border: 1px solid #000;
                        border-radius: 10px;
                        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.8);
                    }

                    .table-1 {
                        width: 300px;
                        padding-left: 20px;
                        padding-right: 20px;
                        margin-right: 20px;
                    }

                    .table-2 {
                        width: 300px;
                        padding-left: 20px;
                        padding-right: 20px;
                        padding-bottom: 20px;
                        margin-right: 20px;
                    }

                    .table-3 {
                        width: 300px;
                        padding-left: 20px;
                        padding-right: 20px;
                    }

                    .codsrcon-monitor-header {
                        width: 100% !important;
                        text-align: left !important;
                        margin-bottom: 10px !important;
                    }

                    .codsrcon-monitor-sv-hostname {
                        width: 100% !important;
                        text-align: left !important;
                        margin-bottom: 0px !important;
                        padding-left: 5px !important;
                        height: 50px !important;
                    }

                    .codsrcon-monitor-gamename {
                        width: 100% !important;
                        height: 30px !important;
                        text-align: center !important;
                        margin-bottom: 0px !important;
                    }

                    .codsrcon-monitor-ip {
                        width: 100% !important;
                        text-align: center !important;
                        margin-bottom: 0px !important;
                    }

                    .codsrcon-monitor-gametype {
                        width: 100% !important;
                        height: 20px !important;
                        text-align: center !important;
                        margin-bottom: 5px !important;
                    }

                    .codsrcon-monitor-image-container {
                        width: 192px !important;
                        height: 128px !important;
                        margin: 5px auto 0px !important;
                        margin-bottom: 10px !important;
                    }

                    .codsrcon-monitor-image {
                        width: 100% !important;
                        height: 100% !important;
                    }

                    .codsrcon-monitor-mapname {
                        width: 100% !important;
                        text-align: center !important;
                        margin-bottom: 16px !important;
                    }

                    .codsrcon-monitor-players {
                        width: 100% !important;
                        text-align: center !important;
                        margin-bottom: 10px !important;
                    }

                    .codsrcon-monitor-playerlist {
                        width: 100% !important;
                    }

                    .codsrcon-monitor-playerlist-table {
                        width: 94% !important;
                        border-collapse: collapse !important;
                        margin: 2px 0px 4px 6px !important;
                    }

                    .codsrcon-monitor-playerlist-table th,
                    .codsrcon-monitor-playerlist-table td {
                        border: 1px solid #ddd !important;
                        padding: 2px !important;
                        text-align: left !important;
                    }

                    .csspicker {
                        width: 1000px;
                        margin: auto;
                    }

                    .pixbox {
                        width: 60px;
                    }

                    h2 {
                        width: 100%;
                        text-align: left;
                    }

                    .form-table-ccsed {
                        width: 350px;
                        border-collapse: collapse;
                    }

                    .flex-row {
                        display: flex;
                        align-items: center;
                    }

                    .form-table-ccsed .flex-row th {
                        text-align: center;
                        flex: 0 0 180px;
                    }

                    .form-table-ccsed .flex-row td {
                        text-align: right;
                        flex: 0 0 104px;
                        position: relative;
                    }

                    .wp-core-ui select {
                        width: 100%;
                    }

                    .wp-picker-container {
                        display: inline-block;
                    }

                    .wp-color-result {
                        display: inline-block;
                    }

                    .picker-float {
                        display: none;
                        position: absolute;
                        z-index: 1000;
                        background: #fff;
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
                        padding: 5px;
                        border: 1px solid #ccc;
                    }

                    .wp-picker-input-wrap {
                        display: inline-block;
                    }

                    .wp-picker-clear {
                        margin-left: 5px;
                    }

                    input.wp-color-picker-field:not(.wp-picker-initialized) {
                        display: none !important;
                    }

                    .wp-picker-container .wp-picker-input-wrap input[type="text"].wp-color-picker-field {
                        width: 80px !important;
                    }

                    #wrapper_codsrcon {
                        width: 1000px;
                        margin: auto;
                        padding: 0;
                    }
                </style>
            </div>
            <script type="text/javascript">
                // Initialize global settings object and populate with current CSS values.
                window.codsrconCssEditorSettings = window.codsrconCssEditorSettings || {};
                window.codsrconCssEditorSettings.custom_css = <?php echo wp_json_encode($custom_css); ?>;

                // Initialize jQuery and prepare for preview updates.
                jQuery(document).ready(function($) {
                    // Note: updatePreview() is commented out in the original code, indicating it may be defined elsewhere.
                    // If needed, ensure updatePreview() is implemented in an external script to refresh the preview dynamically.
                    // updatePreview();
                });
            </script>
        </div>
        <?php
    }
}