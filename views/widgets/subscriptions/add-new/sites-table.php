<?php if (!empty($sites)) : ?>

  <tr class="wu-sites-list-actions">
    <td colspan="3" class="text-center" style="vertical-align: middle;">
      <span class="description"><?php _e('Select all sites below that belong to this user. This user will then be made owner of those sites, which will also be added to the newly created subscription.', 'wp-ultimo'); ?></span>
    </td>
  </tr>

  <?php foreach($sites as $site) : if ($site->userblog_id == 1) continue; ?>
  <tr>
    <th style="vertical-align: middle;">
      <input type="checkbox" name="sites[]" value="<?php echo $site->userblog_id; ?>">
    </th>

    <td>
      <strong><?php echo $site->blogname; ?></strong>
      <br>
      <small><?php printf(__('Site ID: %s', 'wp-ultimo'), $site->userblog_id); ?></small>
    </td>

    <td class="text-right" style="vertical-align: middle;">
      
      <a target="_blank" class="button" href="<?php echo get_admin_url($site->userblog_id); ?>"><?php _e('Visit Dashboard', 'wp-ultimo'); ?></a>
      <a target="_blank" class="button" href="<?php echo get_site_url($site->userblog_id); ?>"><?php _e('Visit Site', 'wp-ultimo'); ?></a>

    </td>
  </tr>
<?php endforeach; else : ?>

  <tr>
    <td colspan="2" class="text-center" style="vertical-align: middle;">
      <span class="description"><?php _e('No sites were found for this user.', 'wp-ultimo'); ?></span>
    </td>
  </tr>

  <?php endif; ?>