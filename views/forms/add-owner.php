<?php

// Get Select 2
WP_Ultimo()->enqueue_select2();

$args1 = array(
  // 'role'         => 'administrator',
  'blog_id'      => $site_id,
  'orderby'      => 'user_login',
  'order'        => 'ASC',
  'number'       => -1,
  'count_total'  => false,
  'fields'       => array('user_login', 'display_name', 'id'),
); 

$users_on_site     = get_users($args1);
$users_on_site_ids = array();

foreach($users_on_site as $user) {
  $users_on_site_ids[] = $user->id;
}

$args2 = array(
  // 'role'         => 'administrator',
  'exclude'      => $users_on_site_ids,
  'blog_id'      => null,
  'orderby'      => 'user_login',
  'order'        => 'ASC',
  'number'       => -1,
  'count_total'  => false,
  'fields'       => array('user_login', 'display_name', 'id'),
); 

$users = get_users($args2); 

// get this site
$site = wu_get_site($site_id);

?>

<tr id="wp-ultimo-options" class="form-field form-required">
  <th scope="row" colspan="2">
    <h2><?php _e('WP Ultimo Options', 'wp-ultimo'); ?></h2>
  </th>
</tr>

<!-- WP Ultimo: Current Owner -->
<tr class="form-field form-required">

  <th scope="row">
    <label for="site_plan"><?php _e('Current Owner', 'wp-ultimo'); ?></label>
  </th>

  <td>

    <?php if ($site->site_owner) : ?>

      <div style="float: left; margin-right: 12px; margin-bottom: 12px;">
        <?php echo get_avatar($site->site_owner->ID, 40, 'identicon', '', array(
          'force_display' => true,
        )); ?>
      </div>
      <strong><?php echo $site->site_owner->display_name; ?></strong>
      <p class="description">

        <?php printf(__('Current Plan: %s'), $site->get_plan() ? $site->get_plan()->title : __('No plan', 'wp-ultimo')); ?><br>
        <?php printf("<a href='%s'>%s</a>", network_admin_url('admin.php?page=wu-edit-subscription&user_id=' . $site->site_owner->ID), __('Manage this user and plan &rarr;', 'wp-ultimo')); ?>
        
      </p>

    <?php else : ?>

      <?php _e('This site has no owner at the moment, you can add one using the field below.'); ?>

    <?php endif; ?>

  </td>

</tr>

<!-- WP Ultimo: User Owner -->
<tr class="form-field form-required">

  <th scope="row">
    <label for="site_owner"><?php _e('Site Owner', 'wp-ultimo'); ?></label>
  </th>

  <td>

    <select id="site_owner" name="site_owner">
      <option value=""><?php _e('No owner', 'wp-ultimo'); ?></option>

      <optgroup label="<?php _e('Users on Site', 'wp-ultimo'); ?>">
      <?php foreach($users_on_site as $user) : ?>
        <option <?php selected($site->site_owner_id, $user->id); ?> value="<?php echo $user->id; ?>"><?php printf('%s (ID: %s)', $user->display_name, $user->id); ?></option>
      <?php endforeach; ?>
      </optgroup>

      <optgroup label="<?php _e('Other Users', 'wp-ultimo'); ?>">
      <?php foreach($users as $user) : ?>
        <option <?php selected($site->site_owner_id, $user->id); ?> value="<?php echo $user->id; ?>"><?php printf('%s (ID: %s)', $user->display_name, $user->id); ?></option>
      <?php endforeach; ?>
      </optgroup>

    </select>
    <br><br>
    <span class="description"><?php _e('Use this field to add this particular site to a user of your network, or to transfer it to another user.', 'wp-ultimo'); ?></span>

  </td>

</tr>

<!-- WP Ultimo: Force HTTPS -->
<tr class="form-field form-required">

  <th scope="row">
    <label for="force_https"><?php _e('HTTP and SSL Options', 'wp-ultimo'); ?></label>
  </th>

  <td>

  <input name="force_https" id="force_https" type="checkbox" <?php checked($site->get_meta('force_https'), true); ?>><span class="description"><?php _e('Select this option if you want to force access to this particular site to occurr only using HTTPS.', 'wp-ultimo'); ?></span>

  </td>

</tr>


<?php

/**
 * @since  1.1.0 Allow us to add new custom fields on the site settings page
 */

do_action('wp_ultimo_site_settings', $site_id);

?>

<script type="text/javascript">
(function($) {
  $(document).ready(function() {
    $('#site_owner').select2({width: '250px'});
  });
})(jQuery);
</script>
