<div id="wu-activate-for-plan" class="alignleft actions" style="display: none;">
  
  <label for="wu-select-plan" class="screen-reader-text"><?php _e('Select Plan', 'wp-ultimo'); ?></label>
  
  <select name="activate-for-plan" id="wu-select-plan">

    <option value=""><?php _e('Select a Plan', 'wp-ultimo'); ?></option>

    <?php 

    /**
     * Get plans
     * @since 1.5.5
     */
    $plans = WU_Plans::get_plans();

    ?>

    <?php foreach($plans as $plan) : ?>

      <option value="<?php echo $plan->get_id(); ?>">
        <?php 
        $site_count = $plan->get_site_count();
        echo $plan->title . ' - ' . sprintf(_n('%s site', '%s sites', $site_count, 'wp-ultimo'), $site_count); 
        ?>
      </option>

    <?php endforeach; ?>

  </select>

  <button v-on:click="sendActivateAction('activate', $event)" v-bind:disabled="disabled && data.action == 'activate'" name="wu-select-plan-action" type="submit" id="wu-activate" class="button action" value="activate">
    <?php _e('Activate for Plan', 'wp-ultimo'); ?>
  </button>

  <button v-on:click="sendActivateAction('deactivate', $event)" v-bind:disabled="disabled && data.action == 'deactivate'" name="wu-select-plan-action" type="submit" id="wu-deactivate" class="button action" value="deactivate">
    <?php _e('Deactivate for Plan', 'wp-ultimo'); ?>
  </button>

  <div v-show="data.running" v-bind:class="{'notice notice-warning': data.running, 'notice-force-display': true}"> 
    <p v-html="data.message"></p>
  </div>

  <div v-show="ended" v-bind:class="{'notice notice-success': ended, 'notice-force-display': true}">
    <p v-html="data.message"></p>
  </div>

</div>

<script>
  (function($) {
    $(document).ready(function() {

      /**
       * Adds the form to the right place
       * @since 1.5.5
       */
      $('.tablenav.top .clear').before($('#wu-activate-for-plan').show()).show();

      /**
       * Handles the ajax action
       * @since 1.5.5
       */
      wu_activation_checker = new Vue({
        el: "#wu-activate-for-plan",
        data: {
          disabled: false,
          ended: false,
          data: {
            running: false,
            percent: 0,
            count: 0,
            total: 0,   
          }
        },
        mounted: function() {
        },
        methods: {
          sendActivateAction: function(action, event) {
            
            event.preventDefault();

            this.disabled = true;
            this.ended = false;
            this.data.action = action;
            this.data.message = '<?php _e('Starting process...', 'wp-ultimo'); ?>'; 
            this.data.running = true;
            
            var data = $('#bulk-action-form').serialize() + '&action=wu-handle-activate-plugins-for-plan';
            var that = this;

            jQuery.ajax({
              type: "POST",
              url: ajaxurl + "?action=wu-handle-activate-plugins-for-plan&wu-select-plan-action=" + action,
              data: data,
              success: function(response) {

              },
              dataType: 'json',
            });

            // setTimeout(function() {
            //   that.checkProgress()
            // }, 1000);
            
            wu_status_checker = setInterval(that.checkProgress, 3000);

          },
          checkProgress: function() {

            var that = this;

            jQuery
              .getJSON(ajaxurl + "?action=wu-get-activation-status")
              .done(function(response) {
                
                that.data = response;
                
                /* later */
                if (response.running == false) {

                  clearInterval(wu_status_checker);

                  that.ended = true;
                  that.disabled = false;

                } // end checker

              })
              .fail(function(error) {
              });

          }
        }
      });

    });
  })(jQuery);
</script>