jQuery(document).ready(function ($) {
    function buildMessage(msg, type) {
        var $p = $('<div />').addClass(type);
        $p.append($('<p>').html(msg));

        return $p;
    }

    $('.ml-wpsearch-import-form').on('click', '#ml-wpsearch-import-submit', function (e) {
        e.preventDefault();

        var $form = $(this).parent('.ml-wpsearch-import-form');
        var $field = $('[name="ml_wpsearch_import_type"]');
        var type;

        if (!$field.filter(':checked').length) {
            $field.first().focus();
            return false;
        }

        type = $field.filter(':checked').val();

        $.post(ajaxurl, {
            post_type: type,
            nonce: $('[name="ml_wpsearch_import_nonce"]').val(),
            action: $('[name="ml_wpsearch_import_action"]').val()
        }).success(function (resp) {
            $('.ml-wpsearch-import-messages').append(buildMessage(resp.message, 'updated'));
        }).fail(function (resp) {
            $('.ml-wpsearch-import-messages').prepend(buildMessage(resp.responseJSON.error, 'error'));
        });

        return false;
    });
});
