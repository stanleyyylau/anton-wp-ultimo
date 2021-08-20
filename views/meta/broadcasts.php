<?php
$screen = get_current_screen();
add_thickbox();
WP_Ultimo()->enqueue_select2();
wp_enqueue_script('dashboard');
wp_enqueue_script('jquery-blockui');
?>

<div id="wp-ultimo-wrap" class="wrap">
  
  <h1><?php _e('Broadcast Messages', 'wp-ultimo'); ?></h1>
  <p class="description"><?php _e('Use this page to display global messages on your client\'s admin panel.', 'wp-ultimo'); ?></p>

  <?php if (isset($_GET['deleted'])) : ?>
    <div id="message" class="updated notice notice-success is-dismissible below-h2"><p><?php _e('Message deleted successfully!', 'wp-ultimo'); ?></p>
    </div>
  <?php endif; ?>

  <div id="dashboard-widgets-wrap" class="">

    <div id="dashboard-widgets" class="metabox-holder row row-wp">

      <div id="postbox-container" class="wu-col-xl-4 wu-col-lg-4 wu-col-md-6 wu-col-wp">
        <?php do_meta_boxes($screen->id, 'normal', ''); ?>
      </div>

      <div id="postbox-container" class="wu-col-xl-8 wu-col-lg-8 wu-col-md-6 wu-col-wp">
        <?php do_meta_boxes($screen->id, 'side', ''); ?>
      </div>

    </div>

  </div>

  <div id="preview-id" style="display:none;">
     <div v-html="previewHTML"></div>
  </div>
  
</div>

<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>

<?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>