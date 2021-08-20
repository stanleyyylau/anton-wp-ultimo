(function($) {
    $(document).ready(function() {

        $.ajaxSetup({
            error: function (x, status, error) {

              console.log(error);
                
                webhook_page.loading = false;
                webhook_page.activeWebhook = false;
                webhook_page.sending = false;
                webhook_page.editing = false;

                wuswal({
                  title: wu_webhook_page_vars.error_title,
                  text: wu_webhook_page_vars.error_message,
                  type: 'error',
                  html: true,
                  showCloseButton: true,
                  showCancelButton: false,
                })
            }
        });

        webhook_page = new Vue({
            el: "#wp-ultimo-wrap",
            data: {
              is_enabled: wu_webhook_page_vars.are_webhooks_enabled,
              selected: [],
              webhooks: wu_webhook_page_vars.webhooks_list,
              editing: false,
              activeWebhook: false,
              loading: false,
              sending: false,
              integrations: wu_webhook_page_vars.integrations,
              filter: '*',
            },
            computed: {
              selectedAll: function() {
                return this.webhooks.length == this.selected.length && this.webhooks.length > 0;
              }
            },
            methods: {
              filterWebhooks: function(filter, $event) {
                this.filter = filter;

                $event.preventDefault();

                var that = this;

                this.loading = true;

                $.post(ajaxurl, {
                  action: 'wu_get_webhooks',
                  filter: that.filter, 
                  _wpnonce: $('#_wpnonce').val(),
                  _wp_http_referer: $('[name="_wp_http_referer"]').val(),
                }, function (results) {

                  that.webhooks = results;
                  that.editing = false;
                  that.activeWebhook = false;
                  that.loading = false;

                });
              },
              selectAll: function() {
                this.selected = !this.selectedAll ? this.webhooks.map(function(webhook) {
                  return webhook.id;
                }) : [];
              },
              reAddTooltip: function() {
                jQuery(".wu-tooltip").tipTip();
              },
              checkEdit: function(id) {
                return this.activeWebhook.id == id && this.activeWebhook.id !== false;
              },
              checkSending: function(id) {
                return this.sending == id && this.sending !== false;
              },
              edit: function($event, webhook) {
                $event.preventDefault();
                this.editing = true;
                this.activeWebhook = webhook;
                this.moveInlineEdit($event.target);
              },
              moveInlineEdit: function(target) {
                var $target = $(target).is('tr') ? $(target) : $(target).parents('tr');
                $('#placeholder, #inline-edit').insertAfter($target);
              },
              stopEdit: function($event) {
                $event.preventDefault();
                this.activeWebhook = false;
                this.editing = false;
              },
              addNew: function($event) {

                $event.preventDefault();

                this.moveInlineEdit( $("#the-list tr:last") );

                var id = false;

                // this.webhooks.push();

                this.activeWebhook = {
                  'id': id,
                  'name': wu_webhook_page_vars.new_message,
                  'url': 'https://url.test',
                  'event': '',
                  'sent_events_count': 0,
                  'active': 1,
                };

                this.editing = true;

                setTimeout(function() {
                  $('#inline-edit').find('input:first').focus();
                }, 100);

              },
              sendTestEvent: function($event, webhook) {
                
                $event.preventDefault();

                var that = this;

                this.sending = webhook.id;

                $.post(ajaxurl, {
                  action: 'wu_send_test_webhook',
                  data: webhook,
                  _wpnonce: $('#_wpnonce').val(),
                  _wp_http_referer: $('[name="_wp_http_referer"]').val(),
                }, function(results) {
                  
                  that.webhooks = results.webhooks;
                  that.sending  = false;

                  wuswal({
                    title: wu_webhook_page_vars.test_title,
                    type: 'success',
                    text: wu_webhook_page_vars.test_message + '<br><pre class="wuswal-pre">' + JSON.stringify(JSON.parse(results.response), null, 2) + '</pre>',
                    html: true,
                    showCloseButton: true,
                    showCancelButton: false,
                  })

                })
              },
              remove: function($event, webhook) {

                $event.preventDefault();

                var that = this;

                this.loading = true;

                $.post(ajaxurl, {
                  action: 'wu_delete_webhook',
                  data: [webhook.id],
                  _wpnonce: $('#_wpnonce').val(),
                  _wp_http_referer: $('[name="_wp_http_referer"]').val(),
                }, function(results) {
                  
                  that.webhooks      = results;
                  that.editing       = false;
                  that.activeWebhook = false;
                  that.loading       = false;

                });
              },
              removeMany: function($event) {

                $event.preventDefault();

                var that = this;

                this.loading = true;

                $.post(ajaxurl, {
                  action: 'wu_delete_webhook',
                  data: that.selected,
                  _wpnonce: $('#_wpnonce').val(),
                  _wp_http_referer: $('[name="_wp_http_referer"]').val(),
                }, function(results) {
                  
                  that.webhooks      = results;
                  that.editing       = false;
                  that.activeWebhook = false;
                  that.loading       = false;

                });

              },
              update: function($event, webhook) {

                $event.preventDefault();

                var that = this;

                this.loading = true;

                $.post(ajaxurl, {
                  action: 'wu_update_webhook',
                  data: webhook,
                  _wpnonce: $('#_wpnonce').val(),
                  _wp_http_referer: $('[name="_wp_http_referer"]').val(),
                }, function(results) {
                  
                  that.webhooks      = results;
                  that.editing       = false;
                  that.activeWebhook = false;
                  that.loading       = false;

                });
              },

            }
        });

    });
})(jQuery);