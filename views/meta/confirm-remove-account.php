<div id="wp-ultimo-wrap" class="wrap">

  <h1 class="wp-heading-inline"><?php echo __('Are you sure about that?', 'wp-ultimo'); ?></h1>

  <hr class="wp-header-end">

  <p class="description"><?php _e('If you choose to proceed, all of your data will be removed, including your sites and your user account. <br>Active payment integrations will be automatically canceled as well.', 'wp-ultimo'); ?></p>

  <?php do_action('wu_remove_account_alerts'); ?>

  <p class="description"><strong><?php _e('This process is irreversible.', 'wp-ultimo'); ?></strong></p>

  <form method="post">
  
    <input type="hidden" name="wu_action" value="wu_remove_account">
    <?php wp_nonce_field('wu-remove-account'); ?>

    <br>
    <button class="button button-primary" type="submit"><?php _e('Yes, I\'m sure', 'wp-ultimo'); ?></button>
    <a href="<?php echo admin_url('admin.php?page=wu-my-account'); ?>" class="button" type="submit"><?php _e('No, bring me back.', 'wp-ultimo'); ?></a>
  </form>

</div>