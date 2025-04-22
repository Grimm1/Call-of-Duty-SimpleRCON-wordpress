jQuery(document).ready(function ($) {
    /**
     * Initializes the ban manager interface for the CoD Simple RCON plugin.
     * Handles server selection, fetching bans, and unbanning users via AJAX.
     */

    // Cache the server selector element.
    const $serverSelector = $('#cod-server-selector');

    // Verify the selector exists before proceeding.
    if (!$serverSelector.length) {
        console.error('Server selector element (#cod-server-selector) not found on the page.');
        return;
    }

    /**
     * Handles server selection change to fetch and display bans.
     * Uses a namespaced event to prevent duplicate bindings.
     */
    $serverSelector.off('change.codsrcon').on('change.codsrcon', function () {
        const serverId = $(this).val();

        // Handle empty or invalid server selection.
        if (!serverId) {
            $('#cod-bans-table-container').html('<p>Please select a valid server.</p>');
            return;
        }

        // Fetch bans for the selected server via AJAX.
        $.ajax({
            url: codsrconAdminAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'codsrcon_fetch_bans',
                server_id: serverId,
                nonce: codsrconAdminAjax.nonce_fetch_bans
            },
            success: function (response) {
                if (response.success) {
                    // Update the bans table container with the returned HTML.
                    $('#cod-bans-table-container').html(response.data.html || '<p>No bans found.</p>');
                } else {
                    // Display error message if the request fails.
                    console.error('Error fetching bans:', response.data?.message || 'Unknown error');
                    $('#cod-bans-table-container').html(`<p>Error: ${response.data?.message || 'Unknown error'}</p>`);
                }
            },
            error: function (xhr, status, error) {
                // Handle AJAX errors and display a fallback message.
                console.error('AJAX error fetching bans:', { status, error, responseText: xhr.responseText });
                $('#cod-bans-table-container').html('<p>Failed to fetch bans. Please try again.</p>');
            }
        });
    });

    /**
     * Handles unban button clicks to remove a ban for a user.
     * Uses event delegation for dynamically added buttons.
     */
    $(document).on('click', '.unban-button', function () {
        const serverId = $(this).data('server-id');
        const banName = $(this).data('ban-name');

        // Confirm the unban action with the user.
        if (!confirm(`Are you sure you want to unban ${banName}?`)) {
            return;
        }

        // Send AJAX request to unban the user.
        $.ajax({
            url: codsrconAdminAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'codsrcon_unban_user',
                server_id: serverId,
                ban_name: banName,
                nonce: codsrconAdminAjax.nonce_fetch_bans
            },
            success: function (response) {
                if (response.success) {
                    // Display success message and refresh the bans table.
                    alert(response.data.message);
                    $serverSelector.trigger('change');
                } else {
                    // Display error message if the request fails.
                    alert(`Error: ${response.data?.message || 'Unknown error'}`);
                }
            },
            error: function (xhr, status, error) {
                // Handle AJAX errors and display a fallback message.
                console.error('AJAX error unbanning user:', { status, error, responseText: xhr.responseText });
                alert('Failed to unban user. Please try again.');
            }
        });
    });
});