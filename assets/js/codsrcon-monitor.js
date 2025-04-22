(function ($) {
    'use strict';

    /**
     * Initializes the server monitor widget for the CoD Simple RCON plugin.
     * Handles loading server data, updating the UI, and styling server names with color codes.
     */

    /**
     * Loads an image into an element's background, using a fallback if it fails.
     *
     * @param {jQuery} $element - The jQuery element to set the background image for.
     * @param {string} src - The URL of the image to load.
     * @param {string} fallbackSrc - The fallback image URL.
     */
    function loadImage($element, src, fallbackSrc) {
        const img = new Image();
        img.onload = () => $element.css('background-image', `url(${src})`);
        img.onerror = () => $element.css('background-image', `url(${fallbackSrc})`);
        img.src = src;
    }

    /**
     * Generates HTML for the player list table.
     *
     * @param {Array} players - Array of player objects with name, score, and ping.
     * @returns {string} HTML string for the player table rows.
     */
    function generatePlayerList(players) {
        if (!players.length) {
            return '<tr><td colspan="3" class="player-row">The server is empty.</td></tr>';
        }
        return players.map(player => `
            <tr class="player-row">
                <td class="player-name">${colorizeServerName(player.name)}</td>
                <td class="player-score">${player.score}</td>
                <td class="player-ping">${player.ping}</td>
            </tr>
        `).join('');
    }

    /**
     * Updates the server monitor widget with server information and player data.
     *
     * @param {string} uniqueId - The unique ID of the monitor container.
     * @param {Object} serverInfo - Server information including hostname, gametype, mapname, etc.
     * @param {Array} players - Array of player objects.
     */
    function updateMonitor(uniqueId, serverInfo, players) {
        const $container = $(`#${uniqueId}`);
        const elements = {
            hostname: $container.find('.monitor-sv-hostname'),
            gametype: $container.find('.gametype'),
            mapname: $container.find('.server-monitor-mapname'),
            image: $container.find('.server-monitor-image'),
            overlay: $container.find('.server-monitor-overlay'),
            playercount: $container.find('.monitor-playercount'),
            playerList: $container.find('.player-list')
        };

        // Update text elements with server information.
        elements.hostname.html(colorizeServerName(serverInfo.sv_hostname || 'N/A'));
        elements.gametype.html(serverInfo.g_gametype || 'N/A');
        elements.mapname.html(`On: ${serverInfo.mapname || 'N/A'}`);

        // Load map and game icon images with fallbacks.
        const mapName = (serverInfo.mp_name || '').replace(/\s+/g, '_').toLowerCase();
        const gameFolder = serverInfo.game_folder || 'unknown';
        const imgPath = `${codsrconMonitorAjax.plugin_url}assets/images/${gameFolder}/${mapName}.png?t=${Date.now()}`;
        const iconPath = `${codsrconMonitorAjax.plugin_url}assets/images/${gameFolder}/icon.png?t=${Date.now()}`;
        loadImage(elements.image, imgPath, codsrconMonitorAjax.fallback_map);
        loadImage(elements.overlay, iconPath, codsrconMonitorAjax.fallback_icon);

        // Update player count and player list.
        const playerCount = players.length;
        const maxClients = parseInt(serverInfo.sv_maxclients || '0', 10);
        elements.playercount.html(`${playerCount}/${maxClients}`);
        elements.playerList.html(generatePlayerList(players));
    }

    /**
     * Loads initial server monitor data and renders the widget.
     *
     * @global
     * @param {number} serverId - The server ID.
     * @param {string} uniqueId - The unique ID of the monitor container.
     */
    window.loadServerMonitor = function (serverId, uniqueId) {
        $.ajax({
            type: 'POST',
            url: codsrconMonitorAjax.ajax_url,
            data: {
                action: 'codsrcon_monitor_server',
                serverId,
                nonce: codsrconMonitorAjax.nonce_monitor_server
            },
            success: response => {
                if (response.success) {
                    const { serverInfo, players } = response.data;
                    const $container = $(`#${uniqueId}`);

                    // Update additional container elements.
                    $container.find('.sv_hostname').html(colorizeServerName(serverInfo.sv_hostname || 'N/A'));
                    $container.find('.gamename').html(serverInfo.gamename || 'N/A');
                    $container.find('.mapname').html(serverInfo.mapname || 'N/A');
                    $container.find('.gametype').html(serverInfo.g_gametype || 'N/A');
                    $container.find('.playercount').html(`${players.length}/${serverInfo.sv_maxclients || 0}`);
                    $container.find('.server-monitor-mapname').html(`On: ${serverInfo.mapname || 'N/A'}`);

                    // Update the monitor with server info and players.
                    updateMonitor(uniqueId, serverInfo, players);
                } else {
                    console.error(`Server monitor ${uniqueId} error:`, response.data?.message || 'Unknown error');
                }
            },
            error: (xhr, status, error) => {
                console.error(`Server monitor ${uniqueId} AJAX error:`, { status, error, responseText: xhr.responseText });
            }
        });
    };

    /**
     * Loads server monitor data for periodic updates.
     *
     * @global
     * @param {number} serverId - The server ID.
     * @param {string} uniqueId - The unique ID of the monitor container.
     */
    window.loadMonitorData = function (serverId, uniqueId) {
        $.ajax({
            type: 'POST',
            url: codsrconMonitorAjax.ajax_url,
            data: {
                action: 'codsrcon_monitor_server',
                serverId,
                nonce: codsrconMonitorAjax.nonce_monitor_server
            },
            success: response => {
                if (response.success) {
                    updateMonitor(uniqueId, response.data.serverInfo, response.data.players);
                } else {
                    console.error(`Monitor ${uniqueId} error:`, response.data?.message || 'Unknown error');
                }
            },
            error: (xhr, status, error) => {
                console.error(`Monitor ${uniqueId} AJAX error:`, { status, error, responseText: xhr.responseText });
            }
        });
    };

    /**
     * Converts color codes in text to HTML spans with corresponding colors.
     *
     * @param {string} text - The input text with color codes (e.g., ^1 for red).
     * @returns {string} HTML string with colored spans.
     */
    function colorizeServerName(text) {
        const colors = {
            '^1': 'red',
            '^2': 'green',
            '^3': 'yellow',
            '^4': 'blue',
            '^5': 'cyan',
            '^6': 'purple',
            '^7': 'white',
            '^8': 'orange',
            '^9': 'grey'
        };
        return text.split(/(\^[0-9])/).reduce((html, part) => {
            if (part.match(/^\^[0-9]$/)) {
                return html + `<span style="color: ${colors[part] || 'black'};">`;
            }
            return html + `${part}</span>`;
        }, '');
    }
})(jQuery);