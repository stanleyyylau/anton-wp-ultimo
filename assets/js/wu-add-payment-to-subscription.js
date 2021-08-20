(function($) {
    $(document).ready(function() {

        $('body').on('click', '.wu-close-add-new-payment', function(e) {

            e.preventDefault();

            $('#new-transaction-trigger').show(0, function() {
                $('#new-transaction-form').hide();
            });

        });

        $('body').on('click', '.wu-display-new-transaction-trigger', function(e) {

            e.preventDefault();

            $('#new-transaction-form').show(0, function() {
                $('#new-transaction-trigger').hide();
            });

        });

        /**
         * @since  1.4.2, Add new transaction
         */
        $('body').on('click', '.wu-add-new-transaction-trigger', function(e) {

            e.preventDefault();

            var _this = $(this);
            var original_label = _this.html();

            _this.attr('disabled', 'disabled').html('...');

            $.ajax({
                url: ajaxurl,
                data: $('#new-transaction-form :input').serialize() + '&action=wu_add_transaction',
                dataType: 'json',
                success: function(data) {

                    _this.html(data.message);

                    if (data.status) {

                        list.update();

                    }

                    setTimeout(function() {

                        _this.html(original_label).removeAttr('disabled');

                    }, 4000);

                }
            });

        });

    });
})(jQuery);