(function($) {
    $(document).ready(function() {

        $('#plan').select2();
        $('#freq').select2();

        // Select: Users
        $('#user').select2({
            minimumInputLength: 3,
            // tags: [],
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                type: "GET",
                quietMillis: 50,
                data: function (term) {
                    return {
                        term: term,
                        action: 'wu_query_user',
                    };
                },
                results: function (data) {
                    return {
                        results: $.map(data, function (item) {
                            return {
                                text: item.data.display_name + ' (' + item.data.user_email + ')',
                                slug: item.data.user_login,
                                id: item.data.ID
                            };
                        })
                    };
                }
            }
        });

        $(document).on('change', '#user', function(e) {

            var _this   = $(this);
            var user_id = _this.val();

            $('#wu-mb-site-selector, #wu-mb-add-new-actions').block({
              message: null,
              overlayCSS: {
                background: '#F1F1F1',
                opacity: 0.4
              }
            });

            var data = 'action=wu_get_sites_user_is_part_of&user_id=' + user_id;

            $.ajax({
                url: ajaxurl,
                method: 'post',
                data: data,
                dataType: 'html',
                success: function(data) {

                    $('#create-subscription').removeAttr('disabled');

                    $('#wu-mb-site-selector table tbody').html(data).unblock();
                    $('#wu-mb-site-selector, #wu-mb-add-new-actions').unblock();

                }
            });

        });

    });
})(jQuery);
