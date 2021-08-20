<?php
/**
 * Get if this is enabled 
 */

$plan          = wu_get_current_site()->get_plan();
$enabled       = $plan->custom_domain;
$custom_domain = wu_get_current_site()->get_meta('custom-domain');

$domain = str_replace('http://', '', network_home_url());
$domain = str_replace('https://', '', $domain);
$domain = trim($domain, '/');

?>
<form id="wu-custom-domain" method="post">

  <ul class="wu_status_list">

    <li class="full">
      <p><?php echo $enabled ? __('You can use a custom domain with your website.', 'wp-ultimo')
      : __('Your plan does not support custom domains. You can upgrade your plan to have access to this feature.', 'wp-ultimo'); ?></p>
    </li> 

    <li class="full">
      <p>
        <input type="text" <?php disabled(!$enabled); ?> value="<?php echo $custom_domain; ?>" class="regular-text" name="custom-domain" placeholder="yourcustomdomain.com">
      </p>
    </li>

    <?php if ($enabled) : ?>
    <li class="full">
      <p><?php 
        printf(__('Point an A Record to the following IP Address <code>%s</code>.', 'wp-ultimo'), WU_Settings::get_setting('network_ip') ? WU_Settings::get_setting('network_ip') : $_SERVER['SERVER_ADDR']);?>
      <br>

      <?php printf(__('You can also create a CNAME record on your domain pointing to our domain <code>%s</code>.', 'wp-ultimo'), $domain); ?>
      
      <?php

      /**
       * Add extra elements
       * @since 1.7.3
       */
      do_action('wu_custom_domain_after', $custom_domain);

      ?>

      </p>
    </li>   
    <?php endif; ?> 

    <li class="full">
      <p class="sub">
        <button data-target="#wu-custom-domain" data-title="<?php _e('Are you sure?', 'wp-ultimo'); ?>" data-text="<?php echo esc_html(WU_Settings::get_setting('domain-mapping-alert-message')); ?>" data-form="true" <?php disabled(!$enabled); ?> name="wu-action-save-custom-domain" class="wu-confirm button <?php echo $enabled ? "button-primary" : ''; ?> button-streched" type="submit">
          <?php _e('Set Custom Domain', 'wp-ultimo'); ?>
        </button>
      </p>
    </li>

  </ul>

  <?php $enabled ? wp_nonce_field('wu-save-custom-domain') : ''; ?>

</form>