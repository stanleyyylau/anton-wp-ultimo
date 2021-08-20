<div id="wp-ultimo-wrap" class="wrap">

  <h1 class="wp-heading-inline">

    <?php echo $page->title; ?>

    <?php 
    /**
     * You can filter the get_title_link using wu_page_list_get_title_link, see class-wu-page-list.php
     * 
     * @since 1.8.2
     */
    foreach ($page->get_title_links() as $label => $link) : ?>

      <a href="<?php echo $link; ?>" class="page-title-action"><?php echo $label; ?></a>

    <?php endforeach; ?>

    <?php
    /**
     * Allow plugin developers to add aditional buttons to list pages
     * 
     * @since 1.8.2
     * @param WU_Page WP Ultimo Page instance
     */
    do_action('wu_page_list_after_title', $page);
    ?>

  </h1>

  <?php if (isset($_GET['deleted'])) : ?>
    <div id="message" class="updated notice notice-success is-dismissible below-h2">
      <p><?php echo $page->labels['deleted_message']; ?></p>
    </div>
  <?php endif; ?>

  <hr class="wp-header-end">

  <div id="poststuff">

    <div id="post-body" class="">

      <div id="post-body-content">

        <div class="meta-box-sortables ui-sortable">

          <?php $table->prepare_items(); ?>

          <?php $table->views(); ?>

          <form id="posts-filter" method="post">

            <input type="hidden" name="page" value="<?php echo $page->id; ?>">

            <?php $page->has_search && $table->search_box($page->labels['search_label'], $page->object_id); ?>

            <?php $table->display(); ?>

          </form>

        </div>
        <!-- /ui-sortable -->

      </div>
      <!-- /post-body-content -->

    </div>
    <!-- /post-body -->

    <br class="clear">

  </div>
  <!-- /poststuff -->

  <?php
  /**
   * Allow plugin developers to add scripts to the bottom of the page
   * 
   * @since 1.8.2
   * @param WU_Page WP Ultimo Page instance
   */
  do_action('wu_page_list_footer', $page);
  ?>

</div>