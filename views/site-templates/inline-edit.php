<?php global $mode; ?>
<form method="get">
  <table style="display: none;">
    <tbody id="inlineedit">
      <tr id="inline-edit" class="wu-site-template inline-edit-row inline-edit-row-post inline-edit-post quick-edit-row quick-edit-row-post inline-edit-post" style="display: none">
        <td colspan="7" class="colspanchange">
          <fieldset class="inline-edit-col-left">
            <legend class="inline-edit-legend"><?php printf(__('Edit %s Information', 'wp-ultimo'), $type); ?></legend>
            <div class="inline-edit-col">

              <label>
                <span class="title"><?php _e('Template Name', 'wp-ultimo'); ?></span>
                <span class="input-text-wrap"><input type="text" name="blogname" class="ptitle" value=""></span>
              </label>

              <label>
                <span class="title"><?php _e('Template Description'); ?></span>
                <span class="input-text-wrap"><textarea style="height: 6em;" name="blogdescription"></textarea></span>
              </label>
              
            </div>
          </fieldset>
        
          <fieldset class="inline-edit-col-center inline-edit-categories">
          
            <!-- <legend class="inline-edit-legend"><?php _e('Display Options', 'wp-ultimo'); ?></legend> -->

            <div class="inline-edit-col">

              <label class="inline-edit-tags">
                <span class="title"><?php _e('Template Category', 'wp-ultimo'); ?></span>
                <textarea data-wp-taxonomy="wu_categories" cols="22" style="height: 8.3em;" name="wu_categories" class="tax_input_wu_extension_category_plugin"></textarea>
              </label>

            </div>

          </fieldset>

          <fieldset class="inline-edit-col-right">

            <div class="inline-edit-col">

              <label class="inline-edit-thumbnail">
                <span class="title"><?php _e('Thumbnail', 'wp-ultimo'); ?></span>

                <br>

                <?php 

                $field_slug = 'template_img';
                
                // We need to get the media scripts
                wp_enqueue_media();
                wp_enqueue_script('media');

                ?>


                  <?php $image_url = '';

                    if ( true ) {
                      $image = '<img class="%s" src="%s" alt="%s" style="width:%s; height:auto">';
                      printf(
                        $image,
                        $field_slug.'-preview',
                        $image_url,
                        __('Thumbnail'),
                        '150px'
                      );
                      
                    } ?>

                  <br>

                  <a href="#" class="button wu-field-button-upload" data-target="<?php echo $field_slug; ?>">
                    <?php _e('Upload Image', 'wp-ultimo'); ?>
                  </a>

                  <a href="#" class="button wu-field-button-upload-remove" data-target="<?php echo $field_slug; ?>">
                    <?php _e('Remove Image', 'wp-ultimo'); ?>
                  </a>                  

                  <input type="hidden" name="<?php echo $field_slug; ?>" class="<?php echo $field_slug; ?>" value="">

                </label>
            
            </div>


          </fieldset>

          <p class="submit inline-edit-save">
            <button type="button" class="button cancel alignleft"><?php _e('Cancel', 'wp-ultimo'); ?></button>
            
            <?php wp_nonce_field('wu_site_template_inline_edit'); ?>

            <button type="button" class="button button-primary save alignright"><?php _e('Save Changes', 'wp-ultimo'); ?></button>
            <span class="spinner"></span>
            
            <input type="hidden" name="mode" value="<?php echo $mode; ?>">
            <input type="hidden" name="extension_type" value="<?php echo $type_slug; ?>">

            <span class="error" style="display:none"></span>
            <br class="clear">
          </p>
        </td>
      </tr>
    </tbody>
  </table>
</form>
