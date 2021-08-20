(function($) {
    $(document).ready(function() {

        var broadcastVuew = new Vue({
          el: "#wp-ultimo-wrap",
          delimiters: ["<%", "%>"],
          data: {
            type: "message",
            style: "success",
            message: "",
            subject: "",
            previewHTML: ""
          },
          mounted: function() {

            // Register ajax widgets as postbox toggles. 
            postboxes.add_postbox_toggles(pagenow, { });

            list.init();
            
            var that = this;

            window.tinyMCE.onAddEditor.add(function(mgr, ed) {
              ed.onKeyUp.add(function(ed, l) {
                that.message = ed.getContent();
              });
            });

            $('.wp-editor-area').on('keyup', function() {
              that.message = $(this).val();
            });
          },
          methods: {
            setType: function(e, type) {
                e.preventDefault();
                this.type = type;
            },
            showModal: function(e, id) {
                e.preventDefault();
                
            }
          }
        });

        // Select Plans
        $('#wu-broadcast-plans').select2({
          placeholder: wu_broadcast_sender.placeholder_plans
        });

        // Select: Users
        $('#wu-broadcast-users').select2({
            minimumInputLength: 3,
            tags: [],
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

        // $(document).on('change', '[name="type"]', function(e) {

        //     var _this = $(this);

        //     if (_this.val() === 'email') {
        //         $('#preview-message').show();
        //     } else {
        //         $('#preview-message').hide();
        //     }

        // });

        /**
         * @since  1.1.5 
         */
        $(document).on('click', '#save-message, #preview-message', function(e) {

          e.preventDefault();

          var _this          = $(this);
          var action         = _this.attr('name');
          var original_label = _this.html();
          var content        = tinyMCE.get('post_content').getContent();

          _this.attr('disabled', 'disabled').html('. . .');

          $('#wp-ultimo-send-broadcast').block({
            message: null,
            overlayCSS: {
              background: '#F1F1F1',
              opacity: 0.4
            }
          });

          var data = _this.parents('form').serialize() + '&message_action=' + action + '&post_content=' + content;

          $.ajax({
            url: ajaxurl,
            method: 'post',
            data: data,
            dataType: 'json',
            success: function(data) {

              _this.html(data.message);

              $('#wp-ultimo-send-broadcast').unblock();

              setTimeout(function() {

                _this.html(original_label).removeAttr('disabled');

                if (data.status && action === 'submit') {

                    // _this.parents('form').find('input:not(), select, textarea').val('');
                    
                    list.update();
                
                }

              }, 3000);

            }
          });

        });

    });
})(jQuery);