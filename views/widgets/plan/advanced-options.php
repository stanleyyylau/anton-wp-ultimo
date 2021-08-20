<div class="panel-wrap product_data">
  
  <ul class="product_data_tabs wc-tabs" style="">

    <?php 

    /**
     * Tabs
     */
    $advanced_options_tabs = apply_filters('wu_plans_advanced_options_tabs', array(
      'general'         => __('General', 'wp-ultimo'),
      'quotas'          => __('Quotas & Limits', 'wp-ultimo'),
      'pricing_table'   => __('Pricing Table', 'wp-ultimo'),
      'allowed_plugins' => __('Allowed Plugins', 'wp-ultimo'),
      'allowed_themes'  => __('Allowed Themes', 'wp-ultimo'),
    ));

    foreach ($advanced_options_tabs as $tab => $tab_label) : ?>

      <li class="<?php echo $tab; ?>_options <?php echo $tab; ?>_tab">
        <a href="#wu_<?php echo $tab; ?>"><?php echo $tab_label; ?></a>
      </li>

    <?php endforeach; ?>
    
  </ul>

  <div id="wu_general" class="panel wu_options_panel">

    <div class="options_group">
      <p class="form-field _quota_sites_field ">
        <label for="slug">
          <?php _e('Plan Slug', 'wp-ultimo'); ?>
        </label>
        <input <?php disabled(!$edit); ?> type="text" class="short" style="" name="slug" id="slug" value="<?php echo $plan->post ? $plan->post->post_name : ''; ?>" placeholder="">
      </p>
      <!-- <p class="form-field">
        <label class="form-field-full">
          <?php _e('Use this setting to overwrite the global trial period for this plan. Leave blank to use the global setting.', 'wp-ultimo'); ?>
        </label>
      </p> -->
    </div>
    
    <?php if (WU_Settings::get_setting('custom_domains')) : ?>
    
    <div class="options_group">
      <p class="form-field custom_domain_field">
        <label for="custom_domain">
          <?php _e('Custom Domain', 'wp-ultimo'); ?>
        </label>
        <input <?php checked($plan->custom_domain); ?> type="checkbox" class="checkbox" style="" name="custom_domain" id="custom_domain" value="1"> 
        <span class="description"><?php _e('Enable the use of custom domains for this plan.', 'wp-ultimo'); ?></span>
      </p>
    </div>
    
    <?php endif; ?>

    <div class="options_group">
      <p class="form-field _quota_sites_field ">
        <label for="trial">
          <?php _e('Trial Period', 'wp-ultimo'); ?>
        </label>
        <input type="number" class="short" style="" name="trial" id="trial" value="<?php echo $plan->get_trial(); ?>" placeholder="<?php echo WU_Settings::get_setting('trial'); ?>">
      </p>
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('Use this setting to overwrite the global trial period for this plan. Leave blank to use the global setting.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>

    <div class="options_group">
      <p class="form-field">
        <label class="form-field">
          <?php _e('Role', 'wp-ultimo'); ?>
        </label>
        <select class="checkbox" name="role">
          
          <option value=""><?php _e('Use default role selected on WP Ultimo Settings', 'wp-ultimo'); ?></option>

          <?php
          /**
           * Get the roles
           */
          $roles = WU_Settings::get_roles();

          foreach($roles as $value => $label) : 

          ?>

            <option <?php selected($plan->role == $value); ?> value="<?php echo $value; ?>"><?php echo $label ?></option>

          <?php endforeach; ?>

        </select>
      </p>
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('Note: Changing the role plan will change the roles for every user on this plan on their sites. Users using different roles (users you customized) will not be affected.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>

    <?php

    $templates = WU_Site_Hooks::get_available_templates(false);

    /**
     * Allows the admin to select custom site templates for each plan individually
     * @since 1.5.4
     */
    if (WU_Settings::get_setting('allow_template') && $templates) : 
    
    ?>

    <div class="options_group">
      <p class="form-field override_templates_field">
        <label for="override_templates">
          <?php _e('Override Templates', 'wp-ultimo'); ?>
        </label>
        <input <?php checked($plan->override_templates); ?> type="checkbox" class="checkbox" style="" name="override_templates" id="override_templates" value="1" v-model="override_templates"> 
        <span class="description"><?php _e('Check this box if you want to select which templates will be available to this plan.', 'wp-ultimo'); ?></span>
      </p>
    </div>

    <div class="options_group" v-show="override_templates" v-cloak>
      <p class="form-field site_template_field">
        
        <label class="form-field-full">
          <?php _e('Select what templates are available to this particular plan.', 'wp-ultimo'); ?><br>
        </label>
        
        <div style="margin: 0 15px;">
        <div class="row wu-sortable" id="multiselect-template" style="margin-bottom: 15px;">

        <?php

        $_templates = array_filter($plan->templates, function($template) {

          return get_site($template) ? true : false;

        }, ARRAY_FILTER_USE_KEY);

        // If sortable, merge settings and list of items
        foreach ($_templates as $key => &$value) {
          if (isset($templates[$key]))
            $value = $templates[$key];
        }

        $templates = $_templates + $templates;

        /**
         * Loop the values
         */
        foreach ($templates as $template => $field_name) : 

          ?>

          <div class="wu-col-sm-4" style="margin-bottom: 4px;">

            <label for="multiselect-<?php echo $template; ?>" style="margin: 0">
              <input <?php checked($plan->is_template_available($template)); ?> name="<?php echo sprintf('%s[%s]', 'templates', $template); ?>" type="checkbox" id="multiselect-<?php echo $template; ?>" value="1">
              <?php echo $field_name; ?>
            </label>
          
          </div>

        <?php endforeach; ?>

        </div>

        <button data-select-all="multiselect-template" class="button wu-select-all"><?php _e('Check / Uncheck All', 'wp-ultimo'); ?></button>

        <br><br><i><?php _e('Note: Remember, the user will only be limited by the selection below if your template selection step comes after the plan selection step during sign-up, so be careful when re-ordering sign-up steps.', 'wp-ultimo'); ?></i>

        </div>

      </p>
    </div>

    <?php 
    /**
     * If there's no template selection step or no templates to choose from, display the normal template
     */
    else: 
    
    ?>

    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('Use the select box below to set a default site template for this plan. Be aware that if you allow template selection on the sign-up this settings will be overwritten by the user selection.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>

    <div class="options_group">
      <p class="form-field site_template_field">
        <label for="site_template">
          <?php _e('Site Template', 'wp-ultimo'); ?>
        </label>
        <select id="site_template" name="site_template">
          <?php foreach(WU_Site_Hooks::get_available_templates() as $site_id => $site_name) : ?>
            <option <?php selected($plan->site_template, $site_id); ?> value="<?php echo $site_id; ?>"><?php echo $site_name; ?></option>
          <?php endforeach; ?>
        </select>
      </p>
    </div>

    <?php endif; ?>

    <?php
    /**
     * Adding advanced options, to allow overriding of settings only when intentionaly changing them
     * @since 1.7.0
     */
    ?>
    <div id="plan-advanced-options">

      <div class="options_group">
        <p class="form-field advanced_options_field">
          <label for="advanced_options">
            <?php _e('Advanced Options', 'wp-ultimo'); ?>
          </label>
          <input <?php checked($plan->advanced_options); ?> type="checkbox" class="checkbox" style="" name="advanced_options" id="advanced_options" value="1" v-model="advanced_options"> 
          <span class="description"><?php _e('Enable this option to be able to see and set advanced options for this plan.', 'wp-ultimo'); ?></span>
        </p>
      </div>

      <div class="options_group" v-if="advanced_options">
        <p class="form-field copy_media_field">
          <label for="copy_media">
            <?php _e('Copy Media on Template Duplication?', 'wp-ultimo'); ?>
          </label>
          <input <?php checked($plan->should_copy_media()); ?> type="checkbox" class="checkbox" style="" name="copy_media" id="copy_media" value="1"> 
          <span class="description"><?php _e('Checking this option will copy the media uploaded on the template site to the newly created site. This option will override the value of the global setting on WP Ultimo Settings -> Network Settings for this plan.', 'wp-ultimo'); ?></span>
        </p>
      </div>

    </div>

  </div>
  
  <div id="wu_quotas" class="panel wu_options_panel">
    
    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('Using the fields below you can set a post limit for each of the post types activated. Leave 0 for unlimited posts.', 'wp-ultimo'); ?><br>
          <?php _e('Checking the "<strong>Disable for this plan</strong>" box will prevent subscribers of this plan from creating any posts of that type.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>
    
    <div class="options_group">
      
      <?php
      /**
       * Get all post types installed and display them as option fields
       */
      $post_types = get_post_types(array('public' => true), 'objects');
      $post_types = apply_filters('wu_get_post_types', $post_types);
      
      ?>
      
      <?php foreach($post_types as $post_type) : ?>

      <p class="form-field _quota_<?php echo $post_type->name; ?>_field ">

        <label for="quota_<?php echo $post_type->name; ?>">
          <?php printf(__('%s Quota', 'wp-ultimo'), $post_type->label); ?>
        </label>

        <input type="number" class="short" style="" name="quotas[<?php echo $post_type->name; ?>]" id="quota_<?php echo $post_type->name; ?>" value="<?php echo $plan->get_quota($post_type->name) !== false ? $plan->get_quota($post_type->name) : 0 ?>" placeholder="0">

        <span style="margin-left: 10px;">

          <input <?php checked($plan->is_post_type_disabled($post_type->name)); ?> type="checkbox" class="checkbox" style="" name="disabled_post_types[<?php echo $post_type->name; ?>]" id="display_<?php echo $post_type->name; ?>" value="true"> 

          <span class="description"><?php printf(__('Disable for this plan.', 'wp-ultimo'),  $post_type->label); ?></span>

        </span>

      </p>

      <?php endforeach; ?>
      
    </div>
    
    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">

          <?php printf(__('You can change the individual upload quota space for this plan using the setting below. It will only take effect, however, if you turn this limitation on in the <a target="_blank" href="%s">Network Settings</a> page.', 'wp-ultimo'), network_admin_url('settings.php#upload_space_check_disabled')); ?> <?php printf(__('Current State: <strong>%s</strong>', 'wp-ultimo'), ! get_site_option( 'upload_space_check_disabled' ) ? __('Turned On', 'wp-ultimo') : __('Turned Off', 'wp-ultimo')); ?><br>

          <?php printf(__('Leaving 0 will set the limit to the value globally defined on the Network Settings page. Current Network Limit: %s.', 'wp-ultimo'), WU_Util::format_megabytes(get_network_option(null, 'blog_upload_space'))); ?>

        </label>
      </p>
    </div>

    <div class="options_group">
    
      <p class="form-field _quota_upload_field">
        <label for="quota_upload">
          <?php _e('Disk Space', 'wp-ultimo'); ?>
        </label>
        <input type="number" class="short" style="" name="quotas[upload]" id="quota_upload" value="<?php echo $plan->get_quota('upload') !== false ? $plan->get_quota('upload') : 100 ?>" placeholder="100"> <span class="description">MB</span>
      </p>
      
    </div>
    
    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('You can also limit the number of extra users allowed in this plan. Leaving 0 will not allow any extra user to be invited to a site.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>
    
    <div class="options_group">
    
      <p class="form-field _quota_users_field " id="plan-quota-users">
        <label for="quota_users">
          <?php _e('Extra Users', 'wp-ultimo'); ?>
        </label>
        <input :disabled="unlimited_extra_users == true" type="number" class="short" style="" name="quotas[users]" id="quota_users" value="<?php echo $plan->get_quota('users') !== false ? $plan->get_quota('users') : 1 ?>" placeholder="1"> <span class="description"></span>

        <span style="margin-left: 10px;">

          <input v-model="unlimited_extra_users" <?php checked($plan->unlimited_extra_users); ?> type="checkbox" class="checkbox" style="" name="unlimited_extra_users" id="unlimited_extra_users" value="1"> 

          <span class="description"><?php _e('Allow unlimited extra users.', 'wp-ultimo'); ?></span>

        </span>
      </p>
      
    </div>

    <?php if (WU_Settings::get_setting('enable_multiple_sites')) : ?>

      <div class="options_group">
        <p class="form-field">
          <label class="form-field-full">
            <?php printf(__('You can also limit the number of sites your clients on this plan will be able to create. Leave 0 for unlimited sites. You can deactivate this option on the <a href="%s">Network Settings</a> tab, on the WP Ultimo Settings page.', 'wp-ultimo'), network_admin_url('admin.php?page=wp-ultimo&wu-tab=network#enable_multiple_sites')); ?>
          </label>
        </p>
      </div>
      
      <div class="options_group">
      
        <p class="form-field _quota_sites_field ">
          <label for="quota_sites">
            <?php _e('Sites', 'wp-ultimo'); ?>
          </label>
          <input type="number" class="short" style="" name="quotas[sites]" id="quota_sites" value="<?php echo $plan->get_quota('sites') !== false ? $plan->get_quota('sites') : 1 ?>" placeholder="1"> <span class="description"></span>
        </p>
        
      </div>

    <?php endif; ?>

    <?php
    /**
     * Is Visits enabled?
     * @since 1.7.3
     */
    $is_visit_limits_enabled = WU_Settings::get_setting('enable_visits_limiting');
    $visits_tip   = $is_visit_limits_enabled ? '' : __('You need to enable Visits Limitation on WP Ultimo &rarr; Network Settings to set this limit.', 'wp-ultimo');
    ?>

    <!-- @since 1.6.0 Visit Limits -->
    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('Limit the number of visits allowed to each of the sites on this plan. Leave 0 for unlimited visits.', 'wp-ultimo'); ?> <?php echo !$is_visit_limits_enabled ? $visits_tip : ''; ?>
        </label>
      </p>
    </div>
    
    <div class="options_group">
    
      <p class="form-field _quota_sites_field ">
        <label for="quota_sites">
          <?php _e('Visits', 'wp-ultimo'); ?>
        </label>
        <input <?php disabled(!$is_visit_limits_enabled); ?> type="number" class="short" title="<?php echo $visits_tip; ?>" style="" name="quotas[visits]" id="quota_visits" value="<?php echo $plan->get_quota('visits') !== false ? $plan->get_quota('visits') : 0 ?>" placeholder="1"> <span class="description"></span>
      </p>
      
    </div>
    <!-- /Visit Limits -->
    
  </div>

  <?php // Pricing Tables Options ?>
  <div id="wu_pricing_table" class="panel wu_options_panel">

    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('Select which post types you want to display on the pricing tables.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>

    <div class="options_group">

      <input type="hidden" name="display_post_types[setup_fee]" value="0">

      <p class="form-field">
        <label for="display_setup_fee">
          <?php printf(__('Display the Setup Fee', 'wp-ultimo')); ?>
        </label>
        <input <?php checked( $plan->should_display_quota_on_pricing_tables('setup_fee', true) ); ?> id="display_setup_fee" type="checkbox" class="checkbox" style="" name="display_post_types[setup_fee]" value="1"> 
        <span class="description"><?php _e('Display the Setup Fee on the pricing tables.', 'wp-ultimo'); ?></span>
      </p>

      <?php foreach($post_types as $post_type) : ?>
      <p class="form-field">
        <label for="display_<?php echo $post_type->name; ?>">
          <?php printf(__('Display %s', 'wp-ultimo'), $post_type->label); ?>
        </label>
        <input <?php checked( $plan->should_display_quota_on_pricing_tables($post_type->name) ); ?> type="checkbox" class="checkbox" style="" name="display_post_types[<?php echo $post_type->name; ?>]" id="display_<?php echo $post_type->name; ?>" value="true"> 
        <span class="description"><?php printf(__('Display %s on the pricing tables.', 'wp-ultimo'),  $post_type->label); ?></span>
      </p>
      <?php endforeach; ?>

      <p class="form-field">
        <label for="display_upload">
          <?php printf(__('Display the Upload Limit', 'wp-ultimo')); ?>
        </label>
        <input <?php checked( $plan->should_display_quota_on_pricing_tables('upload') ); ?> id="display_upload" type="checkbox" class="checkbox" style="" name="display_post_types[upload]" value="true"> 
        <span class="description"><?php _e('Display the Uploads Limit on the pricing tables.', 'wp-ultimo'); ?></span>
      </p>

      <p class="form-field">
        <label for="display_sites">
          <?php printf(__('Display the Sites Limit', 'wp-ultimo')); ?>
        </label>
        <input <?php checked( $plan->should_display_quota_on_pricing_tables('sites') ); ?> id="display_sites" type="checkbox" class="checkbox" style="" name="display_post_types[sites]" value="true"> 
        <span class="description"><?php _e('Display the Sites Limit on the pricing tables.', 'wp-ultimo'); ?></span>
      </p>

      <p class="form-field">
        <label for="display_visits">
          <?php printf(__('Display the Visit Limit', 'wp-ultimo')); ?>
        </label>
        <input <?php checked( $plan->should_display_quota_on_pricing_tables('visits') ); ?> id="display_visits" type="checkbox" class="checkbox" style="" name="display_post_types[visits]" value="true"> 
        <span class="description"><?php _e('Display the Visits Limit on the pricing tables.', 'wp-ultimo'); ?></span>
      </p>

    </div>

    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full">
          <?php _e('You can add custom features description to the pricing table of this plan. Add one per line. Simple HTML is supported.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>

    <div class="options_group">
    
      <div class="form-field _quota_upload_field" style="padding: 5px 20px 5px 162px!important; margin: 9px 0;">
      
        <label for="feature_list">
          <?php _e('Features List', 'wp-ultimo'); ?><br>
        </label>

        <?php wp_editor( isset($plan->feature_list) ? $plan->feature_list : '', 'feature_list', array(
          'wpautop'       => true,
          'media_buttons' => false,
          'editor_height' => 200,
          'teeny'         => true,
          'tinymce'       => array("toolbar1" => 'bold,italic,strikethrough,link,unlink,undo,redo,pastetext'),
        )); ?>

        <br>

        <span><?php _e('Add one feature per line.', 'wp-ultimo'); ?></span>

      </div>
      
    </div>
    
  </div>
  
  <div id="wu_allowed_plugins" class="panel wu_options_panel" style="display: none;">
    
    <?php
    
    /**
     * Loop each plugin
     * @since  1.1.5 Added the all_plugins filter
     */
    $items = apply_filters('all_plugins', get_plugins());

    $count = 0;

    ?>

    <div class="options_group">
      <p class="form-field">
        <label class="form-field-full" style="width: auto !important;">
          <strong><?php _e('Note:', 'wp-ultimo'); ?></strong> <?php _e('WP Ultimo can only limit plugins that are not network-active.', 'wp-ultimo'); ?>
        </label>
      </p>
    </div>
    
    <?php foreach ($items as $plugin_path => $item) : 

    if ($item['Network'] == true) continue; 
    if (is_plugin_active_for_network($plugin_path)) continue;

    // Up the count so we know that we displayed items
    $count++;

    ?>
    
    <div class="options_group">
      <p class="form-field <?php echo $plugin_path; ?>_field">
        <label for="<?php echo $plugin_path; ?>">
          <?php echo $item['Name'] ?><br>
          <small><?php echo $item['Author'] ?></small>
        </label>
        <input <?php checked(is_array($plan->allowed_plugins) && in_array($plugin_path, $plan->allowed_plugins)); ?> type="checkbox" class="checkbox" style="" name="allowed_plugins[]" id="<?php echo $plugin_path; ?>" value="<?php echo $plugin_path; ?>"> 
        <span class="description"><?php printf(__('Enable %s for this plan.', 'wp-ultimo'), $item['Name']); ?></span>
      </p>

      <?php do_action('wu_allowed_plugins_form', $plugin_path, $item, $plan->id, is_array($plan->allowed_plugins) && in_array($plugin_path, $plan->allowed_plugins)); ?>

    </div>
    
    <?php endforeach; ?>

    <?php if (!$count) : ?>

      <div class="options_group">
        <p class="form-field">
          <label class="form-field-full" style="width: auto !important;">
            <?php _e('There are no plugins that can be limited right now.', 'wp-ultimo'); ?> <a target="_blank" href="<?php echo network_admin_url('plugins.php'); ?>"><?php _e('Manage your Network Plugins', 'wp-ultimo'); ?> &rarr;</a>
          </label>
        </p>
      </div>

    <?php endif; ?>
    
  </div>
  
  <div id="wu_allowed_themes" class="panel wu_options_panel" style="display: none;">
    
    <?php
    /**
     * Loop each theme
     */
    $items = apply_filters('all_themes', wp_get_themes());
    
    ?>
    
    <?php foreach ($items as $theme_slug => $item) : 
    if (!$item->is_allowed()) continue;
    ?>
    
    <div class="options_group">
      <p class="form-field <?php echo $theme_slug; ?>_field show_if_simple show_if_variable">
        <label for="<?php echo $theme_slug; ?>">
          <?php echo $item['Name'] ?><br>
          <small><?php echo $item['Author'] ?></small>
        </label>
        <input <?php checked(is_array($plan->allowed_themes) && in_array($theme_slug, $plan->allowed_themes)); ?> type="checkbox" class="checkbox" style="" name="allowed_themes[]" id="<?php echo $theme_slug; ?>" value="<?php echo $theme_slug; ?>"> 
        <span class="description"><?php printf(__('Enable %s for this plan.', 'wp-ultimo'), $item['Name']); ?></span>
      </p>

      <?php do_action('wu_allowed_themes_form', $theme_slug, $item, $plan->id, is_array($plan->allowed_themes) && in_array($theme_slug, $plan->allowed_themes)); ?>

    </div>
    
    <?php endforeach; ?>
    
  </div>

  <?php 

  /**
   * Displays the extra option panels for added Tabs
   */
  
  do_action('wu_plans_advanced_options_after_panels', $plan);

  ?>
  
  <div class="clear"></div>

</div>

<script type="text/javascript">
  (function($) {
    $(document).ready(function() {
      var wu_plan_advanced_options = new Vue({
        el: "#wu_general",
        data: {
          advanced_options: <?php echo (int) $plan->advanced_options; ?>,
          override_templates: <?php echo (int) $plan->override_templates; ?>,
        },
        mounted: function() {
          wu_checkboxes();
        }
      });
      var wu_plan_unlimited_users = new Vue({
        el: "#plan-quota-users",
        data: {
          unlimited_extra_users: <?php echo (int) $plan->should_allow_unlimited_extra_users(); ?>
        }
      });
    });
  })(jQuery);
</script>