// Prevent multiple initializations of the admin script.
if (!window.codsrconInitialized) {
    jQuery(document).ready(function ($) {
        /**
         * Initializes the admin interface for managing Call of Duty servers.
         * Handles server CRUD operations, map and gametype management, and AJAX requests.
         */

        // Cache AJAX URL and nonces from localized script data.
        const ajaxUrl = codsrconAdminAjax.ajax_url;
        const nonces = codsrconAdminAjax;

        /**
         * Sends an AJAX request to the server with nonce validation.
         *
         * @param {string} action - The AJAX action name.
         * @param {Object} data - Additional data to send.
         * @param {string} [successMsg=''] - Optional success message to display.
         * @param {Function} [onSuccess=() => {}] - Optional callback on successful response.
         * @returns {Promise} The jQuery AJAX promise.
         */
        const sendAjaxRequest = (action, data, successMsg = '', onSuccess = () => {}) => {
            const nonceKey = {
                'codsrcon_process_add_or_update_server': 'nonce_add_server',
                'codsrcon_edit_server': 'nonce_edit_server',
                'codsrcon_delete_server': 'nonce_delete_server',
                'codsrcon_admin_fetch_maps': 'nonce_fetch_maps',
                'codsrcon_admin_fetch_gts': 'nonce_fetch_gts',
                'codsrcon_add_map': 'nonce_add_map',
                'codsrcon_remove_map_from_server': 'nonce_remove_map_from_server',
                'codsrcon_add_gt_to_server': 'nonce_add_gt_to_server',
                'codsrcon_remove_gt_from_server': 'nonce_remove_gt_from_server',
                'codsrcon_add_defaults': 'nonce_add_defaults',
                'codsrcon_add_defaults_gt': 'nonce_add_defaults_gt'
            }[action];
            const nonce = nonces[nonceKey];

            return $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: { action, nonce, ...data },
                success: response => {
                    if (response.success) {
                        if (successMsg) {
                            alert(successMsg);
                        }
                        onSuccess(response);
                    } else {
                        console.error(`Error for ${action}:`, response.data?.message || 'Unknown error');
                        alert(`Error: ${response.data?.message || 'Unknown error'}`);
                    }
                },
                error: (jqXHR, textStatus) => {
                    console.error(`AJAX Failed: ${action} - ${textStatus}`, jqXHR);
                    alert(`Failed: ${textStatus}`);
                }
            });
        };

        /**
         * Validates that all provided fields are non-empty.
         *
         * @param {Object} fields - Object containing field values.
         * @param {string} errorMsg - Error message to display if validation fails.
         * @returns {boolean} True if all fields are non-empty, false otherwise.
         */
        const validateFields = (fields, errorMsg) => {
            const isValid = Object.values(fields).every(v => v);
            if (!isValid) {
                alert(errorMsg);
            }
            return isValid;
        };

        // Handle server form submission for adding or updating a server.
        $('#server-form').on('submit', function (e) {
            e.preventDefault();
            const data = {
                server_id: $('#server_id').val(),
                server_name: $('#server_name').val(),
                ip_hostname: $('#ip_hostname').val(),
                port: $('#port').val(),
                rcon_password: $('#rcon_password').val()
            };

            // Validate hostname format (domain or IPv4).
            if (!data.ip_hostname.match(/^([a-zA-Z0-9.-]+|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/)) {
                alert('Please enter a valid hostname or IP address.');
                return;
            }

            sendAjaxRequest('codsrcon_process_add_or_update_server', data, 'Server saved successfully.', () => {
                location.reload();
            });
        });

        // Track editing state to prevent concurrent edits.
        let isEditing = false;

        // Handle edit server button click.
        $('.codsrcon-edit-button').off('click').on('click', function (e) {
            e.preventDefault();
            if (isEditing) {
                return;
            }
            isEditing = true;
            const serverId = $(this).data('server-id');

            sendAjaxRequest('codsrcon_edit_server', { server_id: serverId }, '', response => {
                const server = response.data;

                // Populate form fields with server data.
                $('#server_name').val(server.name || '');
                $('#ip_hostname').val(server.ip_hostname || '');
                $('#port').val(server.port || '');
                $('#rcon_password').val(server.rcon_password || '');
                $('#server_id, #add-map-server-id').val(server.id || '');
                $('#server-form-title').text('Edit Server');
                $('.codsrcon-submit-button').text('Save Changes');
                window.currentServer = { id: server.id, gameName: server.server_type };

                // Fetch fresh nonces and update maps/gametypes.
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'codsrcon_get_fresh_nonces',
                        nonce: nonces.nonce_edit_server
                    },
                    success: freshResponse => {
                        if (freshResponse.success) {
                            nonces.nonce_fetch_maps = freshResponse.data.nonce_fetch_maps;
                            nonces.nonce_fetch_gts = freshResponse.data.nonce_fetch_gts;

                            // Fetch maps and gametypes concurrently.
                            $.when(
                                sendAjaxRequest('codsrcon_admin_fetch_maps', { server_id: serverId }, '', r => {
                                    $('#server-maps-list').html(r.data.html || '<p>No maps available</p>');
                                }),
                                sendAjaxRequest('codsrcon_admin_fetch_gts', { server_id: serverId }, '', r => {
                                    $('#server-gts-list').html(r.data.html || '<p>No gametypes available</p>');
                                })
                            ).then(() => {
                                $('#server-maps-gts-container, #tables-container').css('display', 'block');
                                isEditing = false;
                            }, error => {
                                console.error('Error fetching maps or gametypes:', error);
                                isEditing = false;
                            });
                        } else {
                            console.error('Failed to fetch fresh nonces:', freshResponse.data?.message || 'Unknown error');
                            isEditing = false;
                        }
                    },
                    error: (jqXHR, textStatus) => {
                        console.error('Fresh nonce request failed:', textStatus);
                        isEditing = false;
                    }
                });
            }).fail(() => {
                isEditing = false;
                console.error('Edit request failed');
            });
        });

        // Handle delete server button click.
        $('.codsrcon-delete-button').on('click', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this server?')) {
                return;
            }
            sendAjaxRequest('codsrcon_delete_server', { server_id: $(this).data('server-id') }, 'Server deleted successfully.', () => {
                location.reload();
            });
        });

        /**
         * Fetches and updates the server maps list.
         *
         * @param {number} serverId - The server ID.
         */
        const fetchServerMaps = serverId => {
            sendAjaxRequest('codsrcon_admin_fetch_maps', { server_id: serverId }, '', response => {
                $('#server-maps-list').html(response.data.html || '<p>No maps available</p>');
            });
        };

        /**
         * Fetches and updates the server gametypes list.
         *
         * @param {number} serverId - The server ID.
         */
        const fetchServerGameTypes = serverId => {
            sendAjaxRequest('codsrcon_admin_fetch_gts', { server_id: serverId }, '', response => {
                $('#server-gts-list').html(response.data.html || '<p>No gametypes available</p>');
            });
        };

        // Handle add map form submission.
        $('#add-map-form').on('submit', function (e) {
            e.preventDefault();
            const serverId = window.currentServer?.id;
            if (!serverId) {
                alert('Please select a server to edit first.');
                return;
            }

            const formData = new FormData(this);
            formData.append('action', 'codsrcon_add_map');
            formData.append('server_id', serverId);
            formData.append('nonce', codsrconAdminAjax.nonce_add_map);
            if ($('#overwrite_confirm').is(':checked')) {
                formData.append('overwrite_confirm', 'yes');
            }

            $.ajax({
                url: codsrconAdminAjax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.success) {
                        alert(response.data.message);
                        fetchServerMaps(serverId);
                    } else if (response.data.confirm_required) {
                        if (confirm(response.data.message)) {
                            $('#overwrite_confirm').prop('checked', true);
                            $(this).submit();
                        }
                    } else {
                        console.error('Add map failed:', response.data?.message || 'Unknown error');
                        alert(`Error: ${response.data?.message || 'Unknown error'}`);
                    }
                },
                error: function (xhr) {
                    console.error('Add map AJAX error:', xhr.status, xhr.responseText);
                    alert('Failed to add map: Server error.');
                }
            });
        });

        // Handle map image file selection and preview.
        $('#map_image').on('change', function (e) {
            const file = e.target.files[0];
            const $preview = $('#preview-image');
            const $fileName = $('#file-name-display');
            if (file?.type === 'image/png') {
                $fileName.text(file.name);
                const reader = new FileReader();
                reader.onload = e => $preview.attr('src', e.target.result).show();
                reader.readAsDataURL(file);
            } else {
                alert('Please select a PNG image.');
                $(this).val('');
                $preview.hide();
                $fileName.text('No file chosen');
            }
        });

        // Handle remove map button click.
        $('#server-maps-list').on('click', '.codsrcon-remove-map', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to remove this map?')) {
                return;
            }
            const serverId = window.currentServer?.id;
            if (!serverId) {
                alert('Please edit a server first.');
                return;
            }
            sendAjaxRequest('codsrcon_remove_map_from_server', { server_id: serverId, map_id: $(this).data('map-id') }, 'Map removed successfully.', () => {
                fetchServerMaps(serverId);
            });
        });

        // Handle add gametype form submission.
        $('#add-gt-form').on('submit', function (e) {
            e.preventDefault();
            const serverId = window.currentServer?.id;
            if (!serverId) {
                alert('Please select a server to edit first.');
                return;
            }
            const fields = {
                gameType: $('#gametype').val(),
                gtAlias: $('#gt_alias').val()
            };
            if (!validateFields(fields, 'Please enter both game type and alias.')) {
                return;
            }

            const data = { server_id: serverId, gametype: fields.gameType, gt_alias: fields.gtAlias };
            sendAjaxRequest('codsrcon_add_gt_to_server', data, 'Game type added successfully.', () => {
                fetchServerGameTypes(serverId);
                $('#gametype, #gt_alias').val('');
            });
        });

        // Handle remove gametype button click.
        $('#server-gts-list').on('click', '.codsrcon-remove-gt', function (e) {
            e.preventDefault();
            if (!confirm('Are you sure you want to remove this game type?')) {
                return;
            }
            const serverId = window.currentServer?.id;
            if (!serverId) {
                alert('Please edit a server first.');
                return;
            }
            sendAjaxRequest('codsrcon_remove_gt_from_server', { server_id: serverId, gt_id: $(this).data('gt-id') }, 'Game type removed successfully.', () => {
                fetchServerGameTypes(serverId);
            });
        });

        // Handle add default maps button click.
        $('#add-defaults-button').on('click', e => {
            e.preventDefault();
            if (!confirm('Add default maps?')) {
                return;
            }
            const { id, gameName } = window.currentServer || {};
            if (!id) {
                alert('Please edit a server first.');
                return;
            }
            sendAjaxRequest('codsrcon_add_defaults', { server_id: id, gamename: gameName }, '', () => {
                $.when(
                    fetchServerMaps(id),
                    fetchServerGameTypes(id)
                ).then(() => {
                    alert('Default maps added successfully.');
                });
            });
        });

        // Handle add default gametypes button click.
        $('#add-defaults-gt-button').on('click', e => {
            e.preventDefault();
            if (!confirm('Add default game types?')) {
                return;
            }
            const { id, gameName } = window.currentServer || {};
            if (!id) {
                alert('Please edit a server first.');
                return;
            }
            sendAjaxRequest('codsrcon_add_defaults_gt', { server_id: id, gamename: gameName }, '', () => {
                fetchServerGameTypes(id).then(() => {
                    alert('Default game types added successfully.');
                });
            });
        });

        // Mark the script as initialized.
        window.codsrconInitialized = true;
    });
}