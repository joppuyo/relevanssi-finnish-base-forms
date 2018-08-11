(function ($) {
    var update = function () {
        if ($('input[name=api_type]:checked').val() === 'binary' || $('input[name=api_type]:checked').val() === 'command_line') {
            $('.js-finnish-base-forms-api-url').hide();
        } else {
            $('.js-finnish-base-forms-api-url').show();
        }
    };
    $(document).ready(function () {
        update();
        $('input[name=api_type]').change(function () {
            update();
        });
        $('.js-finnish-base-forms-form').submit(function (event) {
            var self = this;
            event.preventDefault();
            $('.js-finnish-base-forms-submit-button').attr('disabled', true);
            var slug = $('.js-finnish-base-forms-form').data('slug');
            var data = {
                action: slug + '_finnish_base_forms_test',
            };
            if ($('input[name=api_type]:checked').val() === 'command_line' || $('input[name=api_type]:checked').val() === 'binary') {
                data.api_type = $('input[name=api_type]:checked').val();
            } else {
                data.api_type = 'web_api';
                data.api_root = $('input[name=api_url]').val();
            }
            $.post(ajaxurl, data)
                .done(function() {
                    $('.js-finnish-base-forms-submit-button').attr('disabled', false);
                    self.submit();
                })
                .fail(function() {
                    window.scrollTo(0, 0);
                    $('.js-finnish-base-forms-submit-button').attr('disabled', false);
                    $('.notice.notice-success').remove();
                    $('.js-finnish-base-forms-admin-notices').html(
                        '<div class="notice notice-error">' +
                        '<p>Failed to connect the Voikko API. Make sure Voikko has been correctly installed.</p>' +
                        '</div>'
                    );
                });
        });
    });
})(jQuery);