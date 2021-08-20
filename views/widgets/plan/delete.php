<?php
/**
 * Generate the nonce for the deletion
 */
$delete_nonce = wp_create_nonce('wpultimo_delete_plan');

/**
 * Generate the delete link
 */
$delete_url = network_admin_url(sprintf('admin.php?page=%s&action=%s&plan=%s&_wpnonce=%s', 'wp-ultimo-plans', 'delete', absint($plan->id), $delete_nonce));

?>

<p>
  <?php _e('Be careful, this cannot be undone!', 'wp-ultimo'); ?>
</p>

<a class="button button-large button-delete button-streched" href="<?php echo $delete_url; ?>">
  <?php _e('Delete this Plan', 'wp-ultimo'); ?>
</a>