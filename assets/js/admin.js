jQuery(document).ready(function ($) {
    // Save the active tab in URL when switching tabs
    $('.houzez-api-tabs a').on('click', function (e) {
        var tab = $(this).attr('href').split('tab=')[1];
        localStorage.setItem('houzezApiActiveTab', tab);
    });

    // Set active tab based on URL or localStorage
    var activeTab =
        new URLSearchParams(window.location.search).get('tab') ||
        localStorage.getItem('houzezApiActiveTab') ||
        'api_keys';
    $('.houzez-api-tabs li a[href*="tab=' + activeTab + '"]')
        .parent()
        .addClass('active');

    // Modal functionality
    $('#open-generate-key').on('click', function () {
        $('#houzez-api-modal').addClass('show');
    });

    $('.houzez-api-modal-close, .houzez-api-modal-cancel').on(
        'click',
        function () {
            $('#houzez-api-modal').removeClass('show');
            // Clear form fields
            $('#app_name').val('');
            $('#app_description').val('');
            $('#api_key_expiry').val('0');
        }
    );

    // Close modal when clicking outside
    $(window).on('click', function (e) {
        if ($(e.target).hasClass('houzez-api-modal')) {
            $('.houzez-api-modal').removeClass('show');
            // Clear form fields
            $('#app_name').val('');
            $('#app_description').val('');
            $('#api_key_expiry').val('0');
        }
    });

    // Generate API Key
    $('#generate_api_key').on('click', function () {
        var $button = $(this);
        var appName = $('#app_name').val();
        var description = $('#app_description').val();
        var expiryDays = $('#api_key_expiry').val();

        if (!appName) {
            alert('Please enter an application name');
            return;
        }

        // Disable button and show loading state
        $button.prop('disabled', true).text(houzez_api.generating);

        $.ajax({
            url: houzez_api.ajaxurl,
            type: 'POST',
            data: {
                action: 'houzez_generate_api_key',
                app_name: appName,
                description: description,
                expiry_days: expiryDays,
                nonce: houzez_api.nonce,
            },
            success: function (response) {
                if (response.success) {
                    $('#houzez-api-modal').removeClass('show');
                    location.reload();
                } else {
                    alert(response.data.message || houzez_api.error);
                }
            },
            error: function () {
                alert(houzez_api.error);
            },
            complete: function () {
                $button.prop('disabled', false).text('Generate API Key');
            },
        });
    });

    // Delete API Key
    $('.houzez-api-delete-key').on('click', function () {
        if (
            !confirm(
                'Are you sure you want to delete this API key? This action cannot be undone.'
            )
        ) {
            return;
        }

        var $button = $(this);
        var key = $button.data('key');

        // Disable button
        $button.prop('disabled', true);

        $.ajax({
            url: houzez_api.ajaxurl,
            type: 'POST',
            data: {
                action: 'houzez_delete_api_key',
                api_key: key,
                nonce: houzez_api.nonce,
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || houzez_api.error);
                    $button.prop('disabled', false);
                }
            },
            error: function () {
                alert(houzez_api.error);
                $button.prop('disabled', false);
            },
        });
    });

    // Revoke API Key
    $('.houzez-api-revoke-key').on('click', function () {
        if (
            !confirm(
                'Are you sure you want to revoke this API key? This cannot be undone.'
            )
        ) {
            return;
        }

        var $button = $(this);
        var key = $button.data('key');

        // Disable button
        $button.prop('disabled', true);

        $.ajax({
            url: houzez_api.ajaxurl,
            type: 'POST',
            data: {
                action: 'houzez_revoke_api_key',
                api_key: key,
                nonce: houzez_api.nonce,
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || houzez_api.error);
                    $button.prop('disabled', false);
                }
            },
            error: function () {
                alert(houzez_api.error);
                $button.prop('disabled', false);
            },
        });
    });

    // Activate API Key
    $('.houzez-api-activate-key').on('click', function () {
        if (!confirm('Are you sure you want to activate this API key?')) {
            return;
        }

        var $button = $(this);
        var key = $button.data('key');

        // Disable button
        $button.prop('disabled', true);

        $.ajax({
            url: houzez_api.ajaxurl,
            type: 'POST',
            data: {
                action: 'houzez_activate_api_key',
                api_key: key,
                nonce: houzez_api.nonce,
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || houzez_api.error);
                    $button.prop('disabled', false);
                }
            },
            error: function () {
                alert(houzez_api.error);
                $button.prop('disabled', false);
            },
        });
    });

    // Copy API key
    $('.houzez-api-copy-key').on('click', function () {
        var $button = $(this);
        var $tooltip = $button.find('.tooltip');
        var originalText = $tooltip.text();
        var apiKey = $button.data('key');

        navigator.clipboard.writeText(apiKey).then(function () {
            // Change tooltip text
            $tooltip.text(houzez_api.copied);

            // Revert tooltip text after 3s
            setTimeout(function () {
                $tooltip.text(originalText);
            }, 3000);

            // Remove any existing notifications
            $('.houzez-api-copied').remove();

            // Show copied notification
            var $notification = $(
                '<div class="houzez-api-copied">API key copied to clipboard</div>'
            );
            $('body').append($notification);

            // Trigger reflow to ensure animation works
            $notification[0].offsetHeight;

            // Show notification
            $notification.addClass('show');

            // Remove after delay
            setTimeout(function () {
                $notification.removeClass('show');
                setTimeout(function () {
                    $notification.remove();
                }, 300);
            }, 2000);
        });
    });
});
