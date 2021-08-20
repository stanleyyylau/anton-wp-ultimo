<ul class="wu_status_list">
  <li class="quotas">
    
    <?php
    /**
     * Loop the quotas 
     */
    $plan       = wu_get_current_site()->get_plan();
    $post_types = get_post_types(array('public' => true), 'objects');
    $post_types = apply_filters('wu_get_post_types', $post_types);
    
    foreach($post_types as $post_type_slug => $post_type) :

      /**
       * @since 1.5.4 Check the post type
       */
      if ($plan->is_post_type_disabled($post_type_slug)) continue;

      if ($plan->get_quota($post_type_slug) !== false) :

        $post_count = wp_count_posts($post_type_slug);

        if (!empty( (array) $post_count) ) {

          $post_count_status = apply_filters('wu_post_count_status', array('publish'), $post_type);

          $post_count = $post_type_slug === 'attachment' ? $post_count->inherit : WU_Plans_Limits::get_post_count($post_count, $post_type_slug);

        } else {

          $post_count = 0;

        }

        // Filter Post Count for custom added things
        $post_count = apply_filters('wu_post_count', $post_count, $post_type_slug);

        // Calculate width
        if ($plan->get_quota($post_type_slug) == 0) {
          $width = 1;
        } else {
          $width = ($post_count / $plan->get_quota($post_type_slug) * 100);
        }

    ?>

    <?php if ($plan->should_display_quota($post_type_slug)) : ?>

    <p class="quota">
      <?php echo $post_type->label; ?>
      <span class="bar-trail">
        <span class="bar-line" style="width: <?php echo $width; ?>%;"></span>
      </span>
      <small><?php echo $post_count; ?> / <?php echo $plan->get_quota($post_type_slug) == 0 ? __('Unlimited', 'wp-ultimo') : $plan->get_quota($post_type_slug) ; ?></small>
    </p>

    <?php endif; endif; endforeach; ?>

    <?php

    if (WU_Settings::get_setting('enable_multiple_sites')) :

      /**
       * Get things necessary to display the Sites Limit
       */
      $subscription = wu_get_current_site()->get_subscription();
      
      $sites_count = $subscription ? $subscription->get_site_count() : 1;

      $width = (!$plan->get_quota('sites')) ? 1 : $sites_count / $plan->get_quota('sites') * 100;

    ?>

    <?php if ($plan->should_display_quota('sites')) : ?>

    <p class="quota">
      <?php _e('Sites', 'wp-ultimo') ?>
      <span class="bar-trail">
        <span class="bar-line" style="width: <?php echo $width; ?>%;"></span>
      </span>
      <small><?php echo $sites_count; ?> / <?php echo $plan->get_quota('sites') == 0 ? __('Unlimited', 'wp-ultimo') : $plan->get_quota('sites') ; ?></small>
    </p>

    <?php  endif; endif; ?>

    <?php if ($plan->should_display_quota('visits')) :

      $next_reset = wu_get_current_site()->get_visit_count_reset_date();
    
      $visits_count = (int) wu_get_current_site()->get_meta('visits_count');
      $visits_width = (!$plan->get_quota('visits')) ? 1 : $visits_count / $plan->get_quota('visits') * 100;

      $reset_visits_link = current_user_can('manage_network') ? sprintf(' - <a href="%s">%s</a>', admin_url('?action=wu_reset_visit_counter'), __('Reset Visit Counter (only you see this link)', 'wp-ultimo')) : '';

    ?>

    <p class="quota">
      <?php _e('Visits (this month)', 'wp-ultimo') ?>
      <span class="bar-trail">
        <span class="bar-line" style="width: <?php echo $visits_width; ?>%;"></span>
      </span>
      <small><?php echo number_format($visits_count); ?> / <?php echo $plan->get_quota('visits') == 0 ? __('Unlimited', 'wp-ultimo') : number_format($plan->get_quota('visits')) ; ?> - <?php printf(__('Next Reset: %s', 'wp-ultimo'), $next_reset); ?><?php echo $reset_visits_link; ?></small>
    </p>

    <?php  endif; ?>

    </li>

    <?php if (
              ( WU_Settings::get_setting('allow_template_switching') || current_user_can('manage_network') ) && 
              WU_Settings::get_setting('allow_template') && 
              is_array(WU_Settings::get_setting('templates')) && 
              !empty(WU_Settings::get_setting('templates')) &&
              !empty(WU_Site_Hooks::get_available_templates(false))
    ) : ?>
      <li class="quotas">
        <p style="overflow: hidden;">
          <a href="<?php echo admin_url('admin.php?page=wu-new-template'); ?>" class="button button-primary pull-right"><?php _e('Switch Template', 'wp-ultimo'); ?> <?php echo WU_Util::tooltip( __('Use this action to select a new starter template from the catalog. If you do decide to switch, all you data and customizations will be replaced with the data from the new template.', 'wp-ultimo') ); ?></a> 
        </p>
      </li>
    <?php endif; ?>

  </ul>
  
</ul>