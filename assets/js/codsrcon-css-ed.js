jQuery(document).ready(function ($) {
    /**
     * Initializes the CSS editor interface for the CoD Simple RCON plugin.
     * Manages real-time preview updates, color picker functionality, and form submission.
     */

    /**
     * Updates the preview of the server monitor widget based on form input values.
     * Applies styles to various elements using user-defined or default values.
     * @global
     */
    window.updatePreview = function () {
        const preview = $('#codsrcon-monitor-preview');

        // Style the main container.
        preview.attr('style', `
            width: ${$('#monitor_container_width').val() || '220px'} !important;
            border: ${$('#monitor_container_border_width').val() || '1px'} solid ${$('#monitor_container_border_color').val() || '#ccc'} !important;
            border-radius: ${$('#monitor_container_border_radius').val() || '0px'} !important;
            background-color: ${$('#monitor_container_background_color').val() || '#ffffff'} !important;
            font-family: ${$('#font_family').val() || 'Arial'} !important;
            font-weight: ${$('#font_weight').val() || '400'} !important;
        `);

        // Style the header.
        preview.find('.codsrcon-monitor-header').attr('style', `
            background-color: ${$('#header_background_color').val() || '#a5a5a5'} !important;
            color: ${$('#header_text_color').val() || '#000000'} !important;
            height: ${$('#header_height').val() || '28px'} !important;
            position: relative;
        `);

        // Style the server hostname.
        preview.find('.codsrcon-monitor-sv-hostname').attr('style', `
            line-height: ${$('#header_height').val() || '28px'} !important;
        `);

        // Style the game name.
        preview.find('.codsrcon-monitor-gamename').attr('style', `
            color: ${$('#game_text_color').val() || '#000000'} !important;
            font-size: ${$('#game_font_size').val() || '12px'} !important;
        `);

        // Style the IP address.
        preview.find('.codsrcon-monitor-ip').attr('style', `
            color: ${$('#ip_text_color').val() || '#000000'} !important;
            font-size: ${$('#ip_font_size').val() || '12px'} !important;
        `);

        // Style the gametype.
        preview.find('.codsrcon-monitor-gametype').attr('style', `
            color: ${$('#gametype_text_color').val() || '#000000'} !important;
            font-size: ${$('#gametype_font_size').val() || '12px'} !important;
        `);

        // Style the map image.
        preview.find('.codsrcon-monitor-image').attr('style', `
            border: ${$('#image_border_width').val() || '2px'} solid ${$('#image_border_color').val() || '#000000'} !important;
            border-radius: ${$('#image_border_radius').val() || '0px'} !important;
            background-size: cover !important;
            background-position: center !important;
            background-image: url('${codsrconCssEditorSettings.image_url}');
        `);

        // Style the map name.
        preview.find('.codsrcon-monitor-mapname').attr('style', `
            color: ${$('#mapname_text_color').val() || '#000000'} !important;
            font-size: ${$('#mapname_font_size').val() || '12px'} !important;
        `);

        // Style the player count.
        preview.find('.codsrcon-monitor-players').attr('style', `
            color: ${$('#players_text_color').val() || '#000000'} !important;
            font-size: ${$('#players_font_size').val() || '12px'} !important;
        `);

        // Style the player list table header.
        preview.find('.codsrcon-monitor-playerlist-table thead tr').attr('style', `
            background-color: ${$('#playerlist_header_background_color').val() || '#f2f2f2'} !important;
            color: ${$('#playerlist_header_text_color').val() || '#000000'} !important;
            font-size: ${$('#playerlist_header_font_size').val() || '12px'} !important;
        `);

        // Style odd-numbered player rows.
        preview.find('.codsrcon-monitor-playerlist-table .codsrcon-monitor-player-row:nth-child(odd)').attr('style', `
            background-color: ${$('#playerlist_odd_background_color').val() || '#ffffff'} !important;
            color: ${$('#playerlist_odd_text_color').val() || '#000000'} !important;
            font-size: ${$('#playerlist_odd_font_size').val() || '12px'} !important;
        `);

        // Style even-numbered player rows.
        preview.find('.codsrcon-monitor-playerlist-table .codsrcon-monitor-player-row:nth-child(even)').attr('style', `
            background-color: ${$('#playerlist_even_background_color').val() || '#f9f9f9'} !important;
            color: ${$('#playerlist_even_text_color').val() || '#000000'} !important;
            font-size: ${$('#playerlist_even_font_size').val() || '12px'} !important;
        `);
    };

    /**
     * Initializes WordPress color pickers for color input fields.
     * Enhances with floating picker UI and preview updates.
     */
    $('.wp-color-picker-field').each(function () {
        const $input = $(this);
        $input.wpColorPicker({
            mode: 'hex',
            width: 200,
            change: function (event, ui) {
                $input.val(ui.color.toString());
                window.updatePreview();
            },
            clear: function () {
                $input.val('');
                window.updatePreview();
            }
        });
        $input.addClass('wp-picker-initialized');

        // Customize picker layout for floating behavior.
        const $container = $input.closest('.wp-picker-container');
        const $swatch = $container.find('.wp-color-result');
        const $inputWrap = $container.find('.wp-picker-input-wrap');
        const $holder = $container.find('.wp-picker-holder');
        const $float = $('<div class="picker-float"></div>').append($inputWrap).append($holder);
        $container.append($float);
        $float.hide();
    });

    /**
     * Validates color input to ensure it starts with a hash (#).
     */
    $('.wp-color-picker-field').on('input', function () {
        const $input = $(this);
        let value = $input.val();
        if (value && !value.match(/^#/)) {
            $input.val('#' + value.replace(/^#+/, ''));
        }
    });

    /**
     * Toggles the floating color picker on swatch click.
     * Positions the picker below the swatch.
     */
    $('.wp-color-result').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const $container = $(this).closest('.wp-picker-container');
        const $float = $container.find('.picker-float');
        $('.picker-float').not($float).hide();
        if ($float.is(':visible')) {
            $float.hide();
        } else {
            const swatchOffset = $(this).offset();
            const swatchHeight = $(this).outerHeight();
            const containerOffset = $container.offset();
            $float.css({
                top: `${swatchOffset.top - containerOffset.top + swatchHeight}px`,
                left: `${swatchOffset.left - containerOffset.left}px`,
                display: 'block'
            });
        }
    });

    /**
     * Closes all floating color pickers when clicking outside.
     */
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.wp-picker-container').length) {
            $('.picker-float').hide();
        }
    });

    /**
     * Closes the color picker on Enter key press and updates the preview.
     */
    $('.wp-color-picker-field').on('keypress', function (e) {
        if (e.which === 13) {
            $(this).closest('.wp-picker-container').find('.picker-float').hide();
            window.updatePreview();
        }
    });

    /**
     * Debounces input changes to update the preview efficiently.
     * Applies to font, color, and size inputs.
     */
    let debounceTimeout;
    $('#font_family, #font_weight, .wp-color-picker-field, .pixbox').on('input change', function () {
        clearTimeout(debounceTimeout);
        debounceTimeout = setTimeout(window.updatePreview, 100);
    });

    /**
     * Handles the Save CSS button click to submit the form.
     */
    $('#save-css-btn').on('click', function (e) {
        e.preventDefault();
        $('#reset_defaults_hidden').val('');
        $('#css-editor-form').submit();
        // Note: updatePreview is called by PHP-injected script after save.
    });

    /**
     * Handles the Reset Defaults button click to submit the form with reset flag.
     */
    $('#reset-defaults-btn').on('click', function (e) {
        e.preventDefault();
        $('#reset_defaults_hidden').val('reset');
        $('#css-editor-form').submit();
        // Note: updatePreview is called by PHP-injected script after reset.
    });

    // Initialize the preview and trigger a custom event for integration.
    window.updatePreview();
    $(document).trigger('codsrcon_preview_ready');
});