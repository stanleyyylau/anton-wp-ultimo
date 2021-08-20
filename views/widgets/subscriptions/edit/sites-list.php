<?php
/**
 * This widget varies if the user is super admin or not
 */

$display_advanced_options = is_network_admin() && current_user_can('manage_network');

?>
<table id="wu-sites-list" width="100%" class="widefat fixed striped wu-sites-list">
  
  <?php foreach($sites as $site) : ?>

    <?php
    /**
     * Calculate the site dates
     * @since 1.7.0
     */
    switch_to_blog($site->ID);

      $space_used        = get_space_used();
      $space_allowed     = get_space_allowed() ?: 1;
      $percentage        = ceil($space_used / $space_allowed * 100);
      $unlimited_space   = get_site_option( 'upload_space_check_disabled' ); 
      $message           = $unlimited_space ? '%s' : '%s / %s (%s%s)';

      $site              = wu_get_current_site();
      $next_reset        = $site->get_visit_count_reset_date();
      $plan              = $site->get_plan();
      $subscription      = $site->get_subscription();

      $visits_count      = (int) $site->get_meta('visits_count');
      $visits_limit      = $plan ? 
        ($plan->get_quota('visits') == 0 ? ' / ' . __('Unlimited', 'wp-ultimo') : ' / ' . number_format($plan->get_quota('visits')))
        : '';
      $reset_visits_link = current_user_can('manage_network') && $subscription ? sprintf(' - <a href="%s">%s</a>', admin_url('?action=wu_reset_visit_counter&redirect=') . urlencode($subscription->get_manage_url()), __('Reset Visit Counter', 'wp-ultimo')) : '';

    restore_current_blog();
    
    ?>

    <tr>
      <td>
        <strong><?php echo $site->name; ?></strong>
        <br>
        <small>
          <?php if (current_user_can('manage_network')) : ?>
            <?php printf(__('Site ID: %s', 'wp-ultimo'), $site->ID); ?> 
            - 
            <a v-on:click="toggle(<?php echo $site->ID; ?>, $event)" class="hide-if-no-js" href="#">
              <?php _e('See more Information', 'wp-ultimo'); ?> 
              <span v-html="!is_open(<?php echo $site->ID; ?>) ? '&darr;' : '&uarr;'"></span>
            </a>
          <?php else: ?>

            <?php printf(__('Created on %s', 'wp-ultimo'), date_i18n(get_option('date_format'), strtotime($site->site->registered))); ?> 

          <?php endif; ?>

        </small>

        <?php if (current_user_can('manage_network')) : ?>
          <div class="wu-site-list-stats" v-cloak v-show="is_open(<?php echo $site->ID; ?>)">
            <ul>
              <li class="list-stats-item">
                <strong><?php _e('Space Used', 'wp-ultimo'); ?></strong>: <?php printf($message, WU_Util::format_megabytes($space_used), WU_Util::format_megabytes($space_allowed), $percentage, '%'); ?>
              </li>
              <li class="list-stats-item">
                <strong><?php _e('Visits Count', 'wp-ultimo'); ?></strong>: 
                <?php echo number_format($visits_count); ?> <?php echo $visits_limit; ?> - <?php printf(__('Next Reset: %s', 'wp-ultimo'), $next_reset); ?><?php echo $reset_visits_link; ?>
              </li>
            </ul>
          </div>
        <?php endif; ?>
      </td>

      <td class="text-right" style="padding-top: 14px;">
        
        <a target="_blank" class="button" href="<?php echo get_admin_url($site->ID); ?>"><?php _e('Visit Dashboard', 'wp-ultimo'); ?></a>
        <a target="_blank" class="button" href="<?php echo get_site_url($site->ID); ?>"><?php _e('Visit Site', 'wp-ultimo'); ?></a>
        
        <?php if ($display_advanced_options) : ?>
        
          <a target="_blank" class="button" href="<?php echo network_admin_url('site-settings.php?id=').$site->ID; ?>"><?php _e('Edit Settings', 'wp-ultimo'); ?></a>

          <?php 
          /**
           * Allow quick access to site mappings
           * @since 1.7.0
           */
          if (WU_Settings::get_setting('enable_domain_mapping') && class_exists('\Mercator\Mapping')) : ?>
          
            <a target="_blank" class="button" href="<?php echo network_admin_url('admin.php?action=mercator-aliases&id=').$site->ID; ?>"><?php _e('Edit Mappings', 'wp-ultimo'); ?></a>

          <?php endif; ?>

        <?php endif; ?>

      </td>
    </tr>
  <?php endforeach; ?>

  <?php if (WU_Settings::get_setting('enable_multiple_sites') || current_user_can('manage_network')) : ?>
  <tr class="wu-sites-list-actions">
    <td colspan="2" class="text-right" style="vertical-align: middle;">
      
      <a class="button button-primary" href="<?php echo $display_advanced_options ? network_admin_url('site-new.php?wu_user=').$user_id : admin_url('index.php?page=wu-new-site'); ?>"><?php _e('Add new Site', 'wp-ultimo'); ?></a>

    </td>
  </tr>
  <?php endif; ?>
</table>

<script>
  (function($) {
    $(document).ready(function() {

      var wu_site_lists = new Vue({
        el: '#wu-sites-list',
        data: {
          opened_sites: []
        },
        methods: {
          is_open: function(id) {
            return this.opened_sites.indexOf(id) >= 0;
          },
          toggle: function(id, e) {
            e.preventDefault();
            this.is_open(id) ? this.close(id) : this.open(id);
          },
          open: function(id) {
            this.opened_sites.push(id);
          },
          close: function(id) {
            index = this.opened_sites.indexOf(id);
            this.opened_sites.splice(index, 1);
          }
        }
      });

    });
  })(jQuery);
</script>