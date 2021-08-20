<div id="wp-ultimo-wrap" class="wrap">

  <h1 class="wp-heading-inline">

    <?php echo $page->edit ? $page->labels['edit_label'] : $page->labels['add_new_label']; ?>

    <?php 
    /**
     * You can filter the get_title_link using wu_page_get_title_link, see class-wu-page-edit.php
     * 
     * @since 1.8.2
     */
    foreach ($page->get_title_links() as $link => $label) : ?>

      <a href="<?php echo $link; ?>" class="page-title-action"><?php echo $label; ?></a>

    <?php endforeach; ?>

    <?php
    /**
     * Allow plugin developers to add aditional buttons to edit pages
     * 
     * @since 1.8.2
     * @param object  Object holding the information
     * @param WU_Page WP Ultimo Page instance
     */
    do_action('wu_page_edit_after_title', $object, $page);
    ?>

  </h1>

  <?php if (isset($_GET['updated'])) : ?>

    <div id="message" class="updated notice notice-success is-dismissible below-h2">
      <p><?php echo $page->labels['updated_message']; ?></p>
    </div>

  <?php endif; ?>

  <hr class="wp-header-end">

  <form id="form-<?php echo $page->id; ?>" name="post" method="post" autocomplete="off">
    
    <div id="poststuff">

      <div id="post-body" class="metabox-holder columns-2">

        <?php if ($page->has_title) : ?>

          <div id="post-body-content">

            <div id="titlediv">
              
              <div id="titlewrap">
                
                <input placeholder="<?php echo $page->labels['title_placeholder'] ?>" type="text" name="title" size="30" value="<?php echo $object->title; ?>" id="title" spellcheck="true" autocomplete="off">

                <?php if (!empty($page->labels['title_description'])) : ?>

                  <span class="description" style="margin-top: 6px; display: block;"><?php echo $page->labels['title_description']; ?></span>

                <?php endif; ?>

                <?php
                /**
                 * Allow plugin developers to add aditional information below the text input
                 * 
                 * @since 1.8.2
                 * @param object  Object holding the information
                 * @param WU_Page WP Ultimo Page instance
                 */
                do_action('wu_edit_page_after_title_input', $object, $page);
                ?>

              </div>
            
            </div>
            <!-- /titlediv -->

          </div>
          <!-- /post-body-content -->

        <?php endif; ?>

        <div id="postbox-container-1" class="postbox-container">

          <div id="side-sortables" class="meta-box-sortables ui-sortable">

            <?php 
            /**
             * Print Side Metaboxes
             * 
             * Allow plugin developers to add new metaboxes
             * 
             * @since 1.8.2
             * @param object Object being edited right now
             */
            do_meta_boxes($screen->id, 'side', $object); 
            ?>

          </div>
          <!-- /side-sortables -->

        </div>

        <div id="postbox-container-2" class="postbox-container">

          <div id="normal-sortables" class="meta-box-sortables ui-sortable">

            <?php
            /**
             * Print Normal Metaboxes
             * 
             * Allow plugin developers to add new metaboxes
             * 
             * @since 1.8.2
             * @param object Object being edited right now
             */
            do_meta_boxes($screen->id, 'normal', $object); 
            ?>

          </div>
          <!-- /normal-sortables -->

          <div id="advanced-sortables" class="meta-box-sortables ui-sortable">

            <?php 
            /**
             * Print Advanced Metaboxes
             * 
             * Allow plugin developers to add new metaboxes
             * 
             * @since 1.8.2
             * @param object Object being edited right now
             */
            do_meta_boxes($screen->id, 'advanced', $object); 
            ?>

          </div>
          <!-- /advanced-sortables -->

        </div>
        <!-- /normal-sortables -->

      </div>
      <!-- /post-body -->

      <br class="clear">

      <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false); ?>

      <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>

      <?php wp_nonce_field(sprintf('saving_%s', $page->object_id), sprintf('saving_%s', $page->object_id), false) ?>

      <?php wp_nonce_field(sprintf('saving_%s', $page->object_id), '_wpultimo_nonce') ?>

      <?php if ($page->edit) : ?>

        <input type="hidden" name="<?php echo $page->object_id; ?>_id" value="<?php echo $object->id; ?>">

      <?php endif; ?>

    </div>
    <!-- /poststuff -->

  </form>

  <?php
  /**
   * Allow plugin developers to add scripts to the bottom of the page
   * 
   * @since 1.8.2
   * @param object  Object holding the information
   * @param WU_Page WP Ultimo Page instance
   */
  do_action('wu_page_edit_footer', $object, $page);
  ?>
  
</div>