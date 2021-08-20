(function($) {
    $(document).ready(function() {

        $('.notice.wu-broadcast-notice').on('click', '.notice-dismiss', function(e) {
            
            e.preventDefault();

            var $this = $(this);

            // Check if field exists
            if ($this.find('[name="message_id"]').length) {
                return;
            }

            // Otherwise, lets make an ajax call  
            $.ajax({
              method: 'post',
              // /wp-admin/admin-ajax.php
              url: ajaxurl,
              // Add action and nonce to our collected data
              data: {
                action: 'wu_dismiss_broadcast',
                nonce: $this.parents('.notice').find('[name="nonce"]').val(),
                message_id: $this.parents('.notice').find('[name="message_id"]').val(),
                user_id: $this.parents('.notice').find('[name="user_id"]').val(),
              },
              // Handle the successful result
              success: function( response ) {}

            });
            
        });

    });
})(jQuery);