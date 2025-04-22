<?php
/**
 * Namespace for the CoD Simple RCON plugin.
 */
namespace codsimplercon;

// Prevent direct access to this file.
defined('ABSPATH') || exit;

/**
 * Handles database table creation and population for the CoD Simple RCON plugin.
 */
class Database
{
    /**
     * Creates the servers table to store Call of Duty server configurations.
     */
    public static function create_server_table()
    {
        global $wpdb;

        // Define table name with WordPress prefix and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_servers';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the servers table with fields for server details.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            ip_hostname varchar(255) NOT NULL,
            port int(5) NOT NULL,
            rcon_password varchar(255) NOT NULL,
            server_type varchar(50) NOT NULL,
            server_hostname varchar(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate ENGINE=InnoDB;";

        // Include WordPress upgrade functions and create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates the bans table to store player ban records with a foreign key to the servers table.
     */
    public static function create_bans_table()
    {
        global $wpdb;

        // Define table names and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_bans';
        $servers_table_name = $wpdb->prefix . 'callofdutysimplercon_servers';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the bans table with fields for ban details.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            ban_name varchar(255) NOT NULL,
            ban_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            server_id mediumint(9) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the bans table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Add foreign key to link bans to servers, if the servers table exists.
        if ($wpdb->get_var("SHOW TABLES LIKE '$servers_table_name'") === $servers_table_name) {
            $foreign_key_sql = "ALTER TABLE $table_name
                ADD CONSTRAINT fk_server_id
                FOREIGN KEY (server_id) REFERENCES $servers_table_name (id)
                ON DELETE CASCADE;";

            // Check if the foreign key already exists to avoid duplicate attempts.
            $foreign_key_exists = $wpdb->get_var("
                SELECT COUNT(*)
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = '$table_name'
                AND CONSTRAINT_NAME = 'fk_server_id'
                AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if (!$foreign_key_exists) {
                $result = $wpdb->query($foreign_key_sql);
                if ($result === false) {
                    error_log("Failed to add foreign key to $table_name: " . $wpdb->last_error);
                }
            }
        } else {
            error_log("Cannot add foreign key to $table_name: $servers_table_name does not exist.");
        }
    }

    /**
     * Creates and populates the default maps table with predefined map data for various CoD games.
     */
    public static function create_def_maps_table()
    {
        global $wpdb;

        // Define table name and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_def_maps';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the default maps table with unique map-game combinations.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mp_name varchar(100) NOT NULL,
            mp_alias varchar(255) NOT NULL,
            gamename varchar(100) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY map_game_unique (mp_name, gamename)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Predefined map data for various Call of Duty games.
        $maps = [
            ['mp_bocage', 'Bocage', 'Call of Duty'],
            ['mp_brecourt', 'Brecourt', 'Call of Duty'],
            ['mp_carentan', 'Carentan', 'Call of Duty'],
            ['mp_chateau', 'Chateau', 'Call of Duty'],
            ['mp_dawnville', 'Dawnville', 'Call of Duty'],
            ['mp_depot', 'Depot', 'Call of Duty'],
            ['mp_harbor', 'Harbor', 'Call of Duty'],
            ['mp_hurtgen', 'Hurtgen', 'Call of Duty'],
            ['mp_neuville', 'Neuville', 'Call of Duty'],
            ['mp_pavlov', 'Pavlov', 'Call of Duty'],
            ['mp_powcamp', 'POW Camp', 'Call of Duty'],
            ['mp_railyard', 'Railyard', 'Call of Duty'],
            ['mp_rocket', 'Rocket', 'Call of Duty'],
            ['mp_ship', 'Ship', 'Call of Duty'],
            ['mp_stalingrad', 'Stalingrad', 'Call of Duty'],
            ['mp_tigertown', 'Tigertown', 'Call of Duty'],
            ['mp_arnhem', 'Arnhem', 'CoD:United Offensive'],
            ['mp_berlin', 'Berlin', 'CoD:United Offensive'],
            ['mp_cassino', 'Cassino', 'CoD:United Offensive'],
            ['mp_foy', 'Foy', 'CoD:United Offensive'],
            ['mp_italy', 'Italy', 'CoD:United Offensive'],
            ['mp_kharkov', 'Kharkov', 'CoD:United Offensive'],
            ['mp_kursk', 'Kursk', 'CoD:United Offensive'],
            ['mp_ponyri', 'Ponyri', 'CoD:United Offensive'],
            ['mp_rhinevalley', 'Rhine Valley', 'CoD:United Offensive'],
            ['mp_sicily', 'Sicily', 'CoD:United Offensive'],
            ['mp_uo_stanjel', 'Stanjel', 'CoD:United Offensive'],
            ['mp_farmhouse', 'Beltot, France', 'Call of Duty 2'],
            ['mp_burgundy', 'Burgundy, France', 'Call of Duty 2'],
            ['mp_decoy', 'El Alamein, Egypt', 'Call of Duty 2'],
            ['mp_downtown', 'Moscow, Russia', 'Call of Duty 2'],
            ['mp_leningrad', 'Leningrad, Russia', 'Call of Duty 2'],
            ['mp_matmata', 'Matmata, Tunisia', 'Call of Duty 2'],
            ['mp_breakout', 'Villers-Bocage, France', 'Call of Duty 2'],
            ['mp_toujane', 'Toujane, Tunisia', 'Call of Duty 2'],
            ['mp_trainstation', 'Caen, France', 'Call of Duty 2'],
            ['mp_carentan', 'Carentan, France', 'Call of Duty 2'],
            ['mp_brecourt', 'Brecourt, France', 'Call of Duty 2'],
            ['mp_dawnville', 'St. Mere Eglise, France', 'Call of Duty 2'],
            ['mp_railyard', 'Stalingrad, Russia', 'Call of Duty 2'],
            ['mp_harbor', 'Rostov, Russia', 'Call of Duty 2'],
            ['mp_rhine', 'Wallendar, Germany', 'Call of Duty 2'],
            ['mp_backlot', 'Backlot', 'Call of Duty 4'],
            ['mp_bloc', 'Bloc', 'Call of Duty 4'],
            ['mp_bog', 'Bog', 'Call of Duty 4'],
            ['mp_broadcast', 'Broadcast', 'Call of Duty 4'],
            ['mp_carentan', 'Chinatown', 'Call of Duty 4'],
            ['mp_cargoship', 'Wet Work', 'Call of Duty 4'],
            ['mp_citystreets', 'District', 'Call of Duty 4'],
            ['mp_convoy', 'Ambush', 'Call of Duty 4'],
            ['mp_countdown', 'Countdown', 'Call of Duty 4'],
            ['mp_crash', 'Crash', 'Call of Duty 4'],
            ['mp_crash_snow', 'Winter Crash', 'Call of Duty 4'],
            ['mp_creek', 'Creek', 'Call of Duty 4'],
            ['mp_crossfire', 'Crossfire', 'Call of Duty 4'],
            ['mp_farm', 'Downpour', 'Call of Duty 4'],
            ['mp_killhouse', 'Killhouse', 'Call of Duty 4'],
            ['mp_overgrown', 'Overgrown', 'Call of Duty 4'],
            ['mp_pipeline', 'Pipeline', 'Call of Duty 4'],
            ['mp_shipment', 'Shipment', 'Call of Duty 4'],
            ['mp_showdown', 'Showdown', 'Call of Duty 4'],
            ['mp_strike', 'Strike', 'Call of Duty 4'],
            ['mp_vacant', 'Vacant', 'Call of Duty 4'],
            ['mp_airfield', 'Airfield', 'Call of Duty: World at War'],
            ['mp_asylum', 'Asylum', 'Call of Duty: World at War'],
            ['mp_bgate', 'Breach', 'Call of Duty: World at War'],
            ['mp_castle', 'Castle', 'Call of Duty: World at War'],
            ['mp_courtyard', 'Courtyard', 'Call of Duty: World at War'],
            ['mp_dome', 'Dome', 'Call of Duty: World at War'],
            ['mp_drum', 'Battery', 'Call of Duty: World at War'],
            ['mp_hangar', 'Hangar', 'Call of Duty: World at War'],
            ['mp_kneedeep', 'Knee Deep', 'Call of Duty: World at War'],
            ['mp_kwai', 'Banzai', 'Call of Duty: World at War'],
            ['mp_makin', 'Makin', 'Call of Duty: World at War'],
            ['mp_makin_day', 'Makin Day', 'Call of Duty: World at War'],
            ['mp_nachtfeuer', 'Nightfire', 'Call of Duty: World at War'],
            ['mp_outskirts', 'Outskirts', 'Call of Duty: World at War'],
            ['mp_roundhouse', 'Roundhouse', 'Call of Duty: World at War'],
            ['mp_seelow', 'Seelow', 'Call of Duty: World at War'],
            ['mp_shrine', 'Cliffside', 'Call of Duty: World at War'],
            ['mp_stalingrad', 'Corrosion', 'Call of Duty: World at War'],
            ['mp_suburban', 'Upheaval', 'Call of Duty: World at War'],
            ['mp_subway', 'Station', 'Call of Duty: World at War'],
            ['mp_vodka', 'Revolution', 'Call of Duty: World at War'],
            ['mp_downfall', 'Downfall', 'Call of Duty: World at War'],
            ['mp_docks', 'Sub Pens', 'Call of Duty: World at War'],
        ];

        // Populate the table with default maps if it exists.
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            foreach ($maps as $map) {
                // Check if the map already exists for the game.
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE mp_name = %s AND gamename = %s",
                    $map[0],
                    $map[2]
                ));

                if ($exists == 0) {
                    $result = $wpdb->insert(
                        $table_name,
                        [
                            'mp_name' => $map[0],
                            'mp_alias' => $map[1],
                            'gamename' => $map[2],
                        ],
                        ['%s', '%s', '%s']
                    );

                    if ($result === false) {
                        error_log("Failed to insert map: " . print_r($map, true) . " into $table_name. Error: " . $wpdb->last_error);
                    }
                }
            }
        } else {
            error_log("Table $table_name does not exist after creation.");
        }
    }

    /**
     * Creates and populates the default game types table with predefined game type data.
     */
    public static function create_def_gt_table()
    {
        global $wpdb;

        // Define table name and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_def_gts';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the default game types table with unique game type-game combinations.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            gametype varchar(50) NOT NULL,
            gt_alias varchar(255) NOT NULL,
            gamename varchar(100) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY gt_game_unique (gametype, gamename)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        // Predefined game type data for various Call of Duty games.
        $gts = [
            ['dm', 'Deathmatch', 'Call of Duty'],
            ['tdm', 'Team Deathmatch', 'Call of Duty'],
            ['hq', 'Headquarters', 'Call of Duty'],
            ['sd', 'Search and Destroy', 'Call of Duty'],
            ['re', 'Retrieval', 'Call of Duty'],
            ['bel', 'Behind Enemy Lines', 'Call of Duty'],
            ['dm', 'Deathmatch', 'CoD:United Offensive'],
            ['tdm', 'Team Deathmatch', 'CoD:United Offensive'],
            ['hq', 'Headquarters', 'CoD:United Offensive'],
            ['sd', 'Search and Destroy', 'CoD:United Offensive'],
            ['re', 'Retrieval', 'CoD:United Offensive'],
            ['bel', 'Behind Enemy Lines', 'CoD:United Offensive'],
            ['bas', 'Base Assault', 'CoD:United Offensive'],
            ['ctf', 'Capture the Flag', 'CoD:United Offensive'],
            ['dm', 'Deathmatch', 'Call of Duty 2'],
            ['tdm', 'Team Deathmatch', 'Call of Duty 2'],
            ['hq', 'Headquarters', 'Call of Duty 2'],
            ['sd', 'Search and Destroy', 'Call of Duty 2'],
            ['ctf', 'Capture the Flag', 'Call of Duty 2'],
            ['dm', 'Free for All', 'Call of Duty 4'],
            ['war', 'Team Deathmatch', 'Call of Duty 4'],
            ['koth', 'Headquarters', 'Call of Duty 4'],
            ['sab', 'Sabotage', 'Call of Duty 4'],
            ['sd', 'Search and Destroy', 'Call of Duty 4'],
            ['dom', 'Domination', 'Call of Duty 4'],
            ['dm', 'Deathmatch', 'Call of Duty: World at War'],
            ['tdm', 'Team Deathmatch', 'Call of Duty: World at War'],
            ['koth', 'Headquarters', 'Call of Duty: World at War'],
            ['sd', 'Search and Destroy', 'Call of Duty: World at War'],
            ['sab', 'Sabotage', 'Call of Duty: World at War'],
            ['ctf', 'Capture the Flag', 'Call of Duty: World at War'],
            ['twar', 'War', 'Call of Duty: World at War'],
        ];

        // Populate the table with default game types if it exists.
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            foreach ($gts as $gt) {
                // Check if the game type already exists for the game.
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE gametype = %s AND gamename = %s",
                    $gt[0],
                    $gt[2]
                ));

                if ($exists == 0) {
                    $result = $wpdb->insert(
                        $table_name,
                        [
                            'gametype' => $gt[0],
                            'gt_alias' => $gt[1],
                            'gamename' => $gt[2],
                        ],
                        ['%s', '%s', '%s']
                    );

                    if ($result === false) {
                        error_log("Failed to insert game type: " . print_r($gt, true) . " into $table_name. Error: " . $wpdb->last_error);
                    }
                }
            }
        } else {
            error_log("Table $table_name does not exist after creation.");
        }
    }

    /**
     * Creates the available maps table to store maps available for servers.
     */
    public static function create_available_maps_table()
    {
        global $wpdb;

        // Define table name and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_available_maps';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the available maps table with unique map-game combinations.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            mp_name varchar(100) NOT NULL,
            mp_alias varchar(255) NOT NULL,
            gamename varchar(100) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY map_game_unique (mp_name, gamename)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates the server maps table to link servers with available maps.
     */
    public static function create_server_maps_table()
    {
        global $wpdb;

        // Define table name and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_server_maps';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the server maps table with indexes for performance.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            server_id mediumint(9) NOT NULL,
            map_id mediumint(9) NOT NULL,
            PRIMARY KEY (id),
            KEY server_id (server_id),
            KEY map_id (map_id)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates the available game types table to store game types available for servers.
     */
    public static function create_available_gts_table()
    {
        global $wpdb;

        // Define table name and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_available_gts';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the available game types table with unique game type-game combinations.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            gametype varchar(50) NOT NULL,
            gt_alias varchar(255) NOT NULL,
            gamename varchar(100) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY gt_game_unique (gametype, gamename)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Creates the server game types table to link servers with available game types.
     */
    public static function create_server_gts_table()
    {
        global $wpdb;

        // Define table name and charset.
        $table_name = $wpdb->prefix . 'callofdutysimplercon_server_gts';
        $charset_collate = $wpdb->get_charset_collate();

        // SQL to create the server game types table with indexes for performance.
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            server_id mediumint(9) NOT NULL,
            gt_id mediumint(9) NOT NULL,
            PRIMARY KEY (id),
            KEY server_id (server_id),
            KEY gt_id (gt_id)
        ) $charset_collate ENGINE=InnoDB;";

        // Create/update the table.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Activates all database tables in the correct order to satisfy dependencies.
     */
    public static function activate_tables()
    {
        // Create servers table first due to foreign key dependency in bans table.
        self::create_server_table();

        // Create bans table, which references the servers table.
        self::create_bans_table();

        // Create remaining tables (order is not critical for these).
        self::create_def_maps_table();
        self::create_def_gt_table();
        self::create_available_maps_table();
        self::create_server_maps_table();
        self::create_available_gts_table();
        self::create_server_gts_table();
    }

    /**
     * Populates the available maps table and links maps to a server based on the game name.
     *
     * @param int    $server_id The ID of the server.
     * @param string $gameName  The name of the game (e.g., 'Call of Duty').
     */
    public static function populate_available_maps($server_id, $gameName)
    {
        global $wpdb;

        // Define table names.
        $available_maps_table = $wpdb->prefix . 'callofdutysimplercon_available_maps';
        $def_maps_table = $wpdb->prefix . 'callofdutysimplercon_def_maps';
        $server_maps_table = $wpdb->prefix . 'callofdutysimplercon_server_maps';

        // Fetch default maps for the specified game.
        $default_maps = $wpdb->get_results($wpdb->prepare(
            "SELECT mp_name, mp_alias FROM $def_maps_table WHERE gamename = %s",
            $gameName
        ));

        foreach ($default_maps as $map) {
            // Check if the map exists in the available maps table.
            $existing_map = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $available_maps_table WHERE mp_name = %s AND gamename = %s",
                $map->mp_name,
                $gameName
            ));

            if (!$existing_map) {
                // Insert new map into available maps table.
                $result = $wpdb->insert(
                    $available_maps_table,
                    [
                        'mp_name' => $map->mp_name,
                        'mp_alias' => $map->mp_alias,
                        'gamename' => $gameName,
                    ],
                    ['%s', '%s', '%s']
                );

                if ($result === false) {
                    error_log("Failed to insert map: " . print_r($map, true) . " into $available_maps_table. Error: " . $wpdb->last_error);
                    continue;
                }

                // Link the new map to the server.
                $map_id = $wpdb->insert_id;
                $result = $wpdb->insert(
                    $server_maps_table,
                    [
                        'server_id' => $server_id,
                        'map_id' => $map_id,
                    ],
                    ['%d', '%d']
                );

                if ($result === false) {
                    error_log("Failed to link server_id $server_id to map_id $map_id in $server_maps_table. Error: " . $wpdb->last_error);
                }
            } else {
                // Check if the map is already linked to the server.
                $existing_link = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $server_maps_table WHERE server_id = %d AND map_id = %d",
                    $server_id,
                    $existing_map->id
                ));

                if (!$existing_link) {
                    // Link existing map to the server.
                    $result = $wpdb->insert(
                        $server_maps_table,
                        [
                            'server_id' => $server_id,
                            'map_id' => $existing_map->id,
                        ],
                        ['%d', '%d']
                    );

                    if ($result === false) {
                        error_log("Failed to link server_id $server_id to existing map_id {$existing_map->id} in $server_maps_table. Error: " . $wpdb->last_error);
                    }
                }
            }
        }
    }

    /**
     * Populates the available game types table and links game types to a server based on the game name.
     *
     * @param int    $server_id The ID of the server.
     * @param string $gameName  The name of the game (e.g., 'Call of Duty').
     */
    public static function populate_available_gts($server_id, $gameName)
    {
        global $wpdb;

        // Define table names.
        $available_gts_table = $wpdb->prefix . 'callofdutysimplercon_available_gts';
        $def_gts_table = $wpdb->prefix . 'callofdutysimplercon_def_gts';
        $server_gts_table = $wpdb->prefix . 'callofdutysimplercon_server_gts';

        // Fetch default game types for the specified game.
        $default_gts = $wpdb->get_results($wpdb->prepare(
            "SELECT gametype, gt_alias FROM $def_gts_table WHERE gamename = %s",
            $gameName
        ));

        foreach ($default_gts as $gt) {
            // Check if the game type exists in the available game types table.
            $existing_gt = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $available_gts_table WHERE gametype = %s AND gamename = %s",
                $gt->gametype,
                $gameName
            ));

            if (!$existing_gt) {
                // Insert new game type into available game types table.
                $result = $wpdb->insert(
                    $available_gts_table,
                    [
                        'gametype' => $gt->gametype,
                        'gt_alias' => $gt->gt_alias,
                        'gamename' => $gameName,
                    ],
                    ['%s', '%s', '%s']
                );

                if ($result === false) {
                    error_log("Failed to insert game type: " . print_r($gt, true) . " into $available_gts_table. Error: " . $wpdb->last_error);
                    continue;
                }

                // Link the new game type to the server.
                $gt_id = $wpdb->insert_id;
                $result = $wpdb->insert(
                    $server_gts_table,
                    [
                        'server_id' => $server_id,
                        'gt_id' => $gt_id,
                    ],
                    ['%d', '%d']
                );

                if ($result === false) {
                    error_log("Failed to link server_id $server_id to gt_id $gt_id in $server_gts_table. Error: " . $wpdb->last_error);
                }
            } else {
                // Check if the game type is already linked to the server.
                $existing_link = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $server_gts_table WHERE server_id = %d AND gt_id = %d",
                    $server_id,
                    $existing_gt->id
                ));

                if (!$existing_link) {
                    // Link existing game type to the server.
                    $result = $wpdb->insert(
                        $server_gts_table,
                        [
                            'server_id' => $server_id,
                            'gt_id' => $existing_gt->id,
                        ],
                        ['%d', '%d']
                    );

                    if ($result === false) {
                        error_log("Failed to link server_id $server_id to existing gt_id {$existing_gt->id} in $server_gts_table. Error: " . $wpdb->last_error);
                    }
                }
            }
        }
    }

    /**
     * Populates both available maps and game types for a server.
     *
     * @param int    $server_id The ID of the server.
     * @param string $gameName  The name of the game (e.g., 'Call of Duty').
     */
    public static function populate_available_maps_and_gts($server_id, $gameName)
    {
        // Populate maps and game types for the server.
        self::populate_available_maps($server_id, $gameName);
        self::populate_available_gts($server_id, $gameName);
    }

    /**
     * Deletes map and game type associations for a server.
     *
     * @param int $server_id The ID of the server.
     */
    public static function delete_server_entries($server_id)
    {
        global $wpdb;

        // Delete server map associations.
        $result = $wpdb->delete(
            $wpdb->prefix . 'callofdutysimplercon_server_maps',
            ['server_id' => $server_id],
            ['%d']
        );
        if ($result === false) {
            error_log("Failed to delete server_id $server_id from callofdutysimplercon_server_maps. Error: " . $wpdb->last_error);
        }

        // Delete server game type associations.
        $result = $wpdb->delete(
            $wpdb->prefix . 'callofdutysimplercon_server_gts',
            ['server_id' => $server_id],
            ['%d']
        );
        if ($result === false) {
            error_log("Failed to delete server_id $server_id from callofdutysimplercon_server_gts. Error: " . $wpdb->last_error);
        }
    }
}