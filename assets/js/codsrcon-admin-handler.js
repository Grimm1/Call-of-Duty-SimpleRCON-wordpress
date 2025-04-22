jQuery(document).ready(function ($) {
    /**
     * Initializes the server admin interface when the DOM is ready.
     * Manages server selection, displays server information, and handles RCON commands.
     */

    // Cache jQuery selectors for DOM elements.
    const $selector = $('#cod-server-selector'); // Server dropdown.
    const $display = $('#cod-server-info-display'); // Server info display area.
    const $details = $('#cod-server-details'); // Player list and details table.
    const $restartBtn = $('#restart-map'); // Map restart button.
    const $fastRestartBtn = $('#fast-restart'); // Fast restart button.
    const $mapRotateBtn = $('#map-rotate'); // Map rotate button.
    const $kickBtn = $('#cod-kick-btn'); // Kick player button.
    const $banBtn = $('#cod-ban-btn'); // Ban player button.
    const $changeMapBtn = $('#cod-changemap-btn'); // Change map button.
    const $changeGametypeBtn = $('#cod-changegametype-btn'); // Change gametype button.
    const $setHostnameBtn = $('#cod-set-hostname-btn'); // Set hostname button.
    const $setPasswordBtn = $('#cod-set-password-btn'); // Set password button.
    const $playerSelector = $('#cod-player-selector'); // Player dropdown for kicking.
    const $banSelector = $('#cod-ban-selector'); // Player dropdown for banning.
    const $mapSelector = $('#cod-map-selector'); // Map dropdown.
    const $gametypeSelector = $('#cod-gametype-selector'); // Gametype dropdown.
    const $svHostnameInput = $('#cod-sv-hostname-input'); // Hostname input field.
    const $serverPasswordInput = $('#cod-server-password-input'); // Password input field.

    // Store current server state.
    let currentServerId = null;
    let currentMapRaw = null;
    let currentGamename = null;
    let currentServerType = null;

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
     * Removes color codes (e.g., ^0, ^1) from a string.
     *
     * @param {string} str - The input string.
     * @returns {string} The string with color codes removed.
     */
    function stripColorCodes(str) {
        return str.replace(/\^[0-9]/g, '');
    }

    /**
     * Generates HTML for the player list table.
     *
     * @param {Array} players - Array of player objects with id, name, score, and ping.
     * @returns {string} HTML string for the player table rows.
     */
    function generatePlayerList(players) {
        if (!players.length) {
            return '<tr><td colspan="4" class="empty-server">No players currently connected.</td></tr>';
        }

        return players.map(player => `
            <tr>
                <td>${player.id}</td>
                <td>${escapeHtml(stripColorCodes(player.name))}</td>
                <td>${player.score}</td>
                <td>${player.ping}</td>
            </tr>
        `).join('');
    }

    /**
     * Escapes HTML characters in a string to prevent XSS.
     *
     * @param {string} str - The input string.
     * @returns {string} The escaped string.
     */
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    /**
     * Strips HTML tags from a string, returning plain text.
     *
     * @param {string} html - The HTML string.
     * @returns {string} The plain text.
     */
    function stripHtml(html) {
        const temp = document.createElement('div');
        temp.innerHTML = html;
        return temp.textContent || temp.innerText || '';
    }

    /**
     * Populates a player dropdown with player data.
     *
     * @param {jQuery} $selectorElement - The jQuery dropdown element.
     * @param {Array} players - Array of player objects.
     * @param {jQuery} $buttonElement - The associated button to enable/disable.
     */
    function populatePlayerSelector($selectorElement, players, $buttonElement) {
        $selectorElement.empty();
        if (!players || !players.length) {
            $selectorElement.append('<option value="">No players</option>').prop('disabled', true);
            $buttonElement.prop('disabled', true);
        } else {
            $selectorElement.append('<option value="">Select a player</option>');
            players.forEach(player => {
                const cleanName = stripColorCodes(player.name);
                $selectorElement.append(`<option value="${player.id}">${player.id} - ${escapeHtml(cleanName)}</option>`);
            });
            $selectorElement.prop('disabled', false);
            $buttonElement.prop('disabled', false);
        }
    }

    /**
     * Fetches and populates the map dropdown for a server via AJAX.
     *
     * @param {number} serverId - The server ID.
     */
    function populateMapSelector(serverId) {
        $.ajax({
            url: codsrconAdminAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'codsrcon_fetch_maps',
                nonce: codsrconAdminAjax.nonce_fetch_maps,
                server_id: serverId
            },
            success: function (response) {
                if (response.success && response.data.maps) {
                    $mapSelector.empty().append('<option value="">Select a map</option>');
                    response.data.maps.forEach(map => {
                        $mapSelector.append(
                            $('<option>', {
                                value: map.name,
                                text: map.alias
                            })
                        );
                    });
                    $mapSelector.prop('disabled', false);
                    $changeMapBtn.prop('disabled', false);
                } else {
                    console.warn('Map list error:', response.data?.message || 'Unknown error');
                    $mapSelector.empty().append('<option value="">No maps available</option>').prop('disabled', true);
                    $changeMapBtn.prop('disabled', true);
                }
            },
            error: function (xhr, status, error) {
                console.error('Map list AJAX error:', { status, error, responseText: xhr.responseText });
                $mapSelector.empty().append('<option value="">No maps available</option>').prop('disabled', true);
                $changeMapBtn.prop('disabled', true);
            }
        });
    }

    /**
     * Fetches and populates the gametype dropdown for a server via AJAX.
     *
     * @param {number} serverId - The server ID.
     */
    function populateGametypeSelector(serverId) {
        $.ajax({
            url: codsrconAdminAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'codsrcon_fetch_gts',
                nonce: codsrconAdminAjax.nonce_fetch_gts,
                server_id: serverId
            },
            success: function (response) {
                if (response.success && response.data.gametypes) {
                    $gametypeSelector.empty().append('<option value="">Select a gametype</option>');
                    response.data.gametypes.forEach(gt => {
                        $gametypeSelector.append(
                            $('<option>', {
                                value: gt.name,
                                text: gt.alias
                            })
                        );
                    });
                    $gametypeSelector.prop('disabled', false);
                    $changeGametypeBtn.prop('disabled', false);
                } else {
                    console.warn('Gametype list error:', response.data?.message || 'Unknown error');
                    $gametypeSelector.empty().append('<option value="">No gametypes available</option>').prop('disabled', true);
                    $changeGametypeBtn.prop('disabled', true);
                }
            },
            error: function (xhr, status, error) {
                console.error('Gametype list AJAX error:', { status, error, responseText: xhr.responseText });
                $gametypeSelector.empty().append('<option value="">No gametypes available</option>').prop('disabled', true);
                $changeGametypeBtn.prop('disabled', true);
            }
        });
    }

    /**
     * Fetches and updates server information via AJAX.
     *
     * @param {number} serverId - The server ID.
     */
    function updateServerInfo(serverId) {
        if (!serverId || isNaN(serverId) || serverId === '') {
            console.error('Invalid serverId:', serverId);
            $details.text('Error: Invalid server ID provided.');
            resetControls();
            loadImage($display.find('.cod-server-info-image'), codsrconAdminAjax.fallback_image, codsrconAdminAjax.fallback_image);
            return;
        }

        $.ajax({
            url: codsrconAdminAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'codsrcon_get_server_info',
                nonce: codsrconAdminAjax.nonce_get_info,
                server_id: serverId
            },
            success: function (response) {
                if (response.success) {
                    renderServerInfo(response.data, serverId);
                } else {
                    console.warn('Server info failed:', response.data?.message);
                    $details.text(`Error: ${response.data?.message || 'Unknown error'}`);
                    resetControls();
                    loadImage($display.find('.cod-server-info-image'), codsrconAdminAjax.fallback_image, codsrconAdminAjax.fallback_image);
                }
            },
            error: function (xhr, status, error) {
                console.error('Server info AJAX error:', { status, error, responseText: xhr.responseText, statusCode: xhr.status });
                $details.text(`AJAX error occurred: ${xhr.responseText || error}`);
                resetControls();
                loadImage($display.find('.cod-server-info-image'), codsrconAdminAjax.fallback_image, codsrconAdminAjax.fallback_image);
            }
        });
    }

    /**
     * Renders server information and updates UI elements.
     *
     * @param {Object} data - Server data including hostname, map, gametype, players, etc.
     * @param {number} serverId - The server ID.
     */
    function renderServerInfo(data, serverId) {
        $display.find('.sv_hostname').html(data.sv_hostname || 'N/A');
        $display.find('.gamename').text(data.gamename || 'N/A');
        $display.find('.mapname').text(data.mapname || 'N/A');
        $display.find('.gametype').text(data.gametype || 'N/A');
        $display.find('.playercount').text(data.playercount || '0/0');

        const mapImage = data.map_image || codsrconAdminAjax.fallback_image;
        loadImage($display.find('.cod-server-info-image'), mapImage, codsrconAdminAjax.fallback_image);

        $details.html(`
            <table class="cod-player-list-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Score</th>
                        <th>Ping</th>
                    </tr>
                </thead>
                <tbody id="cod-player-list">
                    ${generatePlayerList(data.players || [])}
                </tbody>
            </table>
        `);

        populatePlayerSelector($playerSelector, data.players || [], $kickBtn);
        populatePlayerSelector($banSelector, data.players || [], $banBtn);

        populateMapSelector(serverId);
        populateGametypeSelector(serverId);

        currentServerId = serverId;
        currentMapRaw = data.mapname_raw;
        currentGamename = data.gamename;
        currentServerType = data.server_type;

        // Show/hide fast restart button based on game type.
        const gamename = data.gamename || '';
        if (gamename === 'Call of Duty' || gamename === 'CoD:United Offensive') {
            $fastRestartBtn.hide();
        } else {
            $fastRestartBtn.show();
        }

        // Enable control buttons and inputs.
        $restartBtn.prop('disabled', false);
        $fastRestartBtn.prop('disabled', false);
        $mapRotateBtn.prop('disabled', false);
        $svHostnameInput.prop('disabled', false).val(stripHtml(data.sv_hostname) || '');
        $setHostnameBtn.prop('disabled', false);
        $serverPasswordInput.prop('disabled', false);
        $setPasswordBtn.prop('disabled', false);
    }

    /**
     * Resets UI controls to their disabled state.
     */
    function resetControls() {
        $restartBtn.prop('disabled', true);
        $fastRestartBtn.prop('disabled', true).hide();
        $mapRotateBtn.prop('disabled', true);
        $playerSelector.empty().append('<option value="">No players</option>').prop('disabled', true);
        $kickBtn.prop('disabled', true);
        $banSelector.empty().append('<option value="">No players</option>').prop('disabled', true);
        $banBtn.prop('disabled', true);
        $mapSelector.empty().append('<option value="">No maps available</option>').prop('disabled', true);
        $changeMapBtn.prop('disabled', true);
        $gametypeSelector.empty().append('<option value="">No gametypes available</option>').prop('disabled', true);
        $changeGametypeBtn.prop('disabled', true);
        $svHostnameInput.prop('disabled', true).val('');
        $setHostnameBtn.prop('disabled', true);
        $serverPasswordInput.prop('disabled', true).val('');
        $setPasswordBtn.prop('disabled', true);
    }

    /**
     * Sends an RCON command to the server via AJAX.
     *
     * @param {number} serverId - The server ID.
     * @param {string} command - The RCON command to send.
     * @param {Function} [callback] - Optional callback function to execute on success.
     */
    function sendRconCommand(serverId, command, callback) {
        $.ajax({
            url: codsrconAdminAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'codsrcon_send_rcon_command',
                nonce: codsrconAdminAjax.nonce_send_command,
                server_id: serverId,
                command: command
            },
            success: function (response) {
                if (response.success) {
                    if (callback) callback(response.data);
                    else alert(response.data.message || 'Command executed successfully.');
                } else {
                    alert(`Error: ${response.data?.message || 'Unknown error'}`);
                }
            },
            error: function (xhr, status, error) {
                console.error('Rcon command error:', { status, error, responseText: xhr.responseText });
                alert(`Rcon AJAX error: ${error}`);
            }
        });
    }

    // Event handler for map restart button.
    $restartBtn.on('click', function () {
        if (currentServerId && confirm('Are you sure you want to restart the map?')) {
            sendRconCommand(currentServerId, 'map_restart', () => {
                setTimeout(() => updateServerInfo(currentServerId), 2000);
            });
        }
    });

    // Event handler for fast restart button.
    $fastRestartBtn.on('click', function () {
        if (currentServerId && confirm('Are you sure you want to fast restart the map?')) {
            sendRconCommand(currentServerId, 'fast_restart', () => {
                setTimeout(() => updateServerInfo(currentServerId), 2000);
            });
        }
    });

    // Event handler for map rotate button.
    $mapRotateBtn.on('click', function () {
        if (currentServerId && confirm('Are you sure you want to rotate the map?')) {
            sendRconCommand(currentServerId, 'map_rotate', () => {
                setTimeout(() => updateServerInfo(currentServerId), 2000);
            });
        }
    });

    // Event handler for kick player button.
    $kickBtn.on('click', function () {
        if (currentServerId) {
            const playerId = $playerSelector.val();
            const playerName = $playerSelector.find('option:selected').text().split(' - ')[1] || 'Unknown';
            if (playerId && confirm(`Are you sure you want to kick player ${playerId} - ${playerName}?`)) {
                sendRconCommand(currentServerId, `clientkick ${playerId}`, () => {
                    setTimeout(() => updateServerInfo(currentServerId), 1000);
                });
            }
        }
    });

    // Event handler for ban player button.
    $banBtn.on('click', function () {
        if (currentServerId) {
            const playerId = $banSelector.val();
            const playerName = $banSelector.find('option:selected').text().split(' - ')[1] || 'Unknown';
            if (playerId && confirm(`Are you sure you want to ban player ${playerId} - ${playerName}?`)) {
                sendRconCommand(currentServerId, `banclient ${playerId}`, () => {
                    $.ajax({
                        url: codsrconAdminAjax.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'codsrcon_log_ban',
                            nonce: codsrconAdminAjax.nonce_send_command,
                            server_id: currentServerId,
                            ban_name: playerName
                        },
                        success: function (response) {
                            if (response.success) {
                                alert('Player banned and logged successfully.');
                            } else {
                                alert(`Player banned, but failed to log: ${response.data?.message || 'Unknown error'}`);
                            }
                            setTimeout(() => updateServerInfo(currentServerId), 1000);
                        },
                        error: function (xhr, status, error) {
                            console.error('Ban log error:', { status, error, responseText: xhr.responseText });
                            alert(`Player banned, but AJAX error logging ban: ${error}`);
                            setTimeout(() => updateServerInfo(currentServerId), 1000);
                        }
                    });
                });
            }
        }
    });

    // Event handler for change map button.
    $changeMapBtn.on('click', function () {
        if (currentServerId) {
            const mapName = $mapSelector.val();
            const mapAlias = $mapSelector.find('option:selected').text() || mapName;
            if (mapName && confirm(`Are you sure you want to change the map to ${mapAlias} (${mapName})?`)) {
                sendRconCommand(currentServerId, `map ${mapName}`, () => {
                    setTimeout(() => updateServerInfo(currentServerId), 2000);
                });
            }
        }
    });

    // Event handler for change gametype button.
    $changeGametypeBtn.on('click', function () {
        if (currentServerId) {
            const newGametype = $gametypeSelector.val();
            const gametypeText = $gametypeSelector.find('option:selected').text() || newGametype;
            if (newGametype && confirm(`Are you sure you want to change the gametype to ${gametypeText}?`)) {
                sendRconCommand(currentServerId, `g_gametype ${newGametype}`, () => {
                    setTimeout(() => sendRconCommand(currentServerId, `map ${currentMapRaw}`, () => updateServerInfo(currentServerId)), 1000);
                });
            }
        }
    });

    // Event handler for set hostname button.
    $setHostnameBtn.on('click', function () {
        if (currentServerId) {
            const newHostname = $svHostnameInput.val().trim();
            if (!newHostname) {
                alert('Please enter a hostname.');
                return;
            }
            if (confirm(`Are you sure you want to set the server hostname to "${newHostname}"?`)) {
                $.ajax({
                    url: codsrconAdminAjax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'codsrcon_set_hostname',
                        nonce: codsrconAdminAjax.nonce_set_hostname,
                        server_id: currentServerId,
                        sv_hostname: newHostname
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.data.message);
                            $display.find('.sv_hostname').html(response.data.sv_hostname);
                            $selector.find(`option[value="${currentServerId}"]`).text(`${stripHtml(response.data.sv_hostname)} (${currentServerType})`);
                            $svHostnameInput.val(stripHtml(response.data.sv_hostname));
                        } else {
                            alert(`Error: ${response.data?.message || 'Unknown error'}`);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Set hostname error:', { status, error, responseText: xhr.responseText });
                        alert(`AJAX error: ${error}`);
                    }
                });
            }
        }
    });

    // Event handler for set password button.
    $setPasswordBtn.on('click', function () {
        if (currentServerId) {
            const newPassword = $serverPasswordInput.val().trim();
            const actionText = newPassword ? `set the server password to "${newPassword}"` : 'remove the server password';
            if (confirm(`Are you sure you want to ${actionText}?`)) {
                $.ajax({
                    url: codsrconAdminAjax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'codsrcon_set_password',
                        nonce: codsrconAdminAjax.nonce_set_password,
                        server_id: currentServerId,
                        password: newPassword
                    },
                    success: function (response) {
                        if (response.success) {
                            alert(response.data.message);
                            $serverPasswordInput.val('');
                        } else {
                            alert(`Error: ${response.data?.message || 'Unknown error'}`);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Set password error:', { status, error, responseText: xhr.responseText });
                        alert(`AJAX error: ${error}`);
                    }
                });
            }
        }
    });

    // Event handler for server selector change.
    $selector.on('change', function () {
        const serverId = $(this).val();
        if (serverId) {
            updateServerInfo(serverId);
        } else {
            $display.find('td:not(.label)').text('');
            loadImage($display.find('.cod-server-info-image'), codsrconAdminAjax.fallback_image, codsrconAdminAjax.fallback_image);
            $details.text('Select a server to see details.');
            resetControls();
        }
    });

    // Disable the empty server option.
    $selector.find('option[value=""]').prop('disabled', true);

    // Initialize with provided initial data if available.
    if (typeof codInitialData !== 'undefined' && codInitialData.serverId && codInitialData.data) {
        currentServerId = codInitialData.serverId;
        renderServerInfo(codInitialData.data, codInitialData.serverId);
        $selector.val(codInitialData.serverId);
    }

    // Initialize with selected server ID or reset UI.
    const initialServerId = $selector.val();
    if (initialServerId) {
        updateServerInfo(initialServerId);
    } else {
        $display.find('td:not(.label)').text('');
        loadImage($display.find('.cod-server-info-image'), codsrconAdminAjax.fallback_image, codsrconAdminAjax.fallback_image);
        $details.text('Select a server to see details.');
        resetControls();
    }
});