<?php
/**
 * Generate the nonce for the deletion
 */
$delete_nonce = wp_create_nonce('wpultimo_delete_coupon');

/**
 * Generate the delete link
 */
$delete_url = network_admin_url(sprintf('admin.php?page=%s&action=%s&coupon=%s&_wpnonce=%s', 'wp-ultimo-coupons', 'delete', absint($coupon->id), $delete_nonce));

?>

<p>
  <?php _e('Be careful, this cannot be undone!', 'wp-ultimo'); ?>
</p>

<a class="button button-large button-delete button-streched" href="<?php echo $delete_url; ?>">
  <?php _e('Delete this Coupon', 'wp-ultimo'); ?>
</a>