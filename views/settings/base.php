<div id="wp-ultimo-wrap" class="wrap wu-ultimo">
  
  <h1><?php _e('WP Ultimo Settings', 'wp-ultimo'); ?></h1>

  <p class="description"><?php _e('Use the page below to change the main settings of the plugin.', 'wp-ultimo'); ?></p>

  <?php
  /**
   * Check the update notice
   */
  if (isset($_GET['updated'])) : 

    // Set message
    if ($_GET['updated'] == 1) 
      $message = __('Settings updated successfully!', 'wp-ultimo');
    else if ($_GET['updated'] == 2) 
      $message = __('Cleaning processed successfully!', 'wp-ultimo');

    ?>

  <br>
  <div id="message" class="updated notice notice-success is-dismissible below-h2">
    <p><?php echo $message; ?></p>
    <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _e('Dismiss this notice.'); ?></span></button>
  </div>

  <?php endif; ?>
  
  <form method="post" id="mainform" action="" enctype="multipart/form-data">
    
    <h2 class="nav-tab-wrapper woo-nav-tab-wrapper" style="margin: 20px 0 40px 0;">
      
      <?php 
      /**
       * Get all sections to loop them as hooks
       */
      $tabs = WU_Settings::get_sections();
      ?>
      
      <?php foreach($tabs as $tab_slug => $tab) : ?>
      
      <a href="<?php echo network_admin_url('admin.php?page=wp-ultimo&wu-tab=') . $tab_slug; ?>" class="nav-tab <?php echo esc_attr(isset($_GET['wu-tab']) && $_GET['wu-tab'] == $tab_slug ? "nav-tab-active" : ""); ?>"><?php echo $tab['title']; ?></a>
      
      <?php endforeach; ?>
      
    </h2>

    <div class="row">

      <div class="wu-col-sm-9" style="margin-top: -10px;">
    
    <?php
    /**
     * Get the active tab to display its fields
     */
    $active_tab = isset($tabs[$_GET['wu-tab']]) ? $tabs[$_GET['wu-tab']] : false;
    ?>
    
    <?php 
    
    $table_open = false;
    
    /**
     * Loop each field fromt hat tab and section, displaying them accordingly
     */
    if ($active_tab) : 
    
    foreach($active_tab['fields'] as $field_slug => $field) :

      /**
       * Check disabled
       */
      $disabled = isset($field['disabled']) && $field['disabled'];

    ?>
    
    <?php 
    /**
     * Heading fields
     */
    if ($field['type'] == 'heading') : ?>

      <?php if ($table_open) : ?>
      </tbody></table>
      <?php endif; ?>

      <div id="<?php echo $field_slug; ?>" data-type="heading">
        <h3><?php echo $field['title']; ?></h3>
        <p><?php echo $field['desc']; ?></p>
      </div>

      <table class="form-table">
        <tbody>

    <?php $table_open = true; ?>

    <?php 
    /**
     * Heading fields
     */
    elseif ($field['type'] == 'heading_collapsible') : ?>

      <?php if ($table_open) : ?>
      </tbody></table>
      <?php endif; ?>

      <div data-target="<?php echo 'collapsible-'.$field_slug; ?>" class="wu-settings-heading-collapsible wu-col-sm-12 <?php echo isset($field['active']) && !$field['active'] ? 'wu-settings-heading-collapsible-disabled' : ''; ?>">
        <?php echo $field['title']; ?>
      </div>

      <div style="clear: both"></div>
      
      <table style="width: 95%" id="<?php echo 'collapsible-'.$field_slug; ?>" class="form-table wu-collapsible wu-collapsible-hidden">
        <tbody>

    <?php $table_open = true; ?>

    <?php 
    /**
     * Hidden fields
     */
    elseif ($field['type'] == 'hidden') : ?>
    
    <?php 
    /**
     * Select Block
     */
    elseif ($field['type'] == 'select') : ?>
          
      <tr>
        <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?> </th>
        <td>
          
          <select name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>">
            
            <?php foreach($field['options'] as $value => $option) : ?>
            <option <?php selected(WU_Settings::get_setting($field_slug), $value); ?> value="<?php echo $value; ?>"><?php echo $option; ?></option>
            <?php endforeach; ?>
            
          </select>

          <?php if (!empty($field['desc'])) : ?>
          <p class="description" id="<?php echo $field_slug; ?>-desc">
            <?php echo $field['desc']; ?>				
          </p>
          <?php endif; ?>

        </td>
      </tr>

    <?php 
    /**
     * Ajax Button
     * @since  1.1.0
     */
    elseif ($field['type'] == 'ajax_button') : ?>
          
      <tr>
        <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?></th>
        <td>
          
          <label for="<?php echo $field_slug; ?>">
            <button class="button" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" value="<?php echo wp_create_nonce($field['action']); ?>"><?php echo $field['title']; ?></button>
          </label>

          <?php if (!empty($field['desc'])) : ?>
          <p class="description" id="<?php echo $field_slug; ?>-desc">
            <?php echo $field['desc']; ?>       
          </p>
          <?php endif; ?>

          <script type="text/javascript">
            
            (function($) {
              $('#<?php echo $field_slug; ?>').on('click', function(e) {

                e.preventDefault();

                var $this = $(this),
                    default_label = $this.html();

                $this.html('...').attr('disabled', 'disabled');

                $.ajax({
                  url: "<?php echo admin_url('admin-ajax.php?action=').$field['action']; ?>",
                  dataType: "json",
                  success: function(response) {
                    
                    $this.html(response.message);

                    setTimeout(function() {
                      $this.html(default_label).removeAttr('disabled');
                    }, 4000);

                  }

                });

              });
            })(jQuery);

          </script>

        </td>
      </tr>
          
    <?php 
    /**
     * Checkbox
     */
    elseif ($field['type'] == 'checkbox') : ?>
          
      <tr>
        <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?></th>
        <td>
          
          <label for="<?php echo $field_slug; ?>">
            <input <?php checked(WU_Settings::get_setting($field_slug)); ?> name="<?php echo $field_slug; ?>" type="<?php echo $field['type']; ?>" id="<?php echo $field_slug; ?>" value="1">
            <?php echo $field['title']; ?>
          </label>

          <?php if (!empty($field['desc'])) : ?>
          <p class="description" id="<?php echo $field_slug; ?>-desc">
            <?php echo $field['desc']; ?>				
          </p>
          <?php endif; ?>

        </td>
      </tr>

      <?php 
      /**
       * Color Picker
       */
      elseif ($field['type'] == 'color') :  
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker'); ?>

      <tr>
        <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php if(isset($field['tooltip'])) echo WU_Util::tooltip($field['tooltip']); ?></th>
        <td>

          <input class="field-<?php echo $field_slug; ?>" name="<?php echo $field_slug; ?>" type="text" id="<?php echo $field_slug; ?>" class="regular-text" value="<?php echo WU_Settings::get_setting($field_slug); ?>" placeholder="<?php echo isset($field['placeholder']) ? $field['placeholder'] : ''; ?>">

          <?php if (!empty($field['desc'])) : ?>
          <p class="description" id="<?php echo $field_slug; ?>-desc">
            <?php echo $field['desc']; ?>       
          </p>
          <?php endif; ?>

          <script type="text/javascript">
          (function($) {
              $(function() {
                  // Add Color Picker to all inputs that have 'color-field' class
                  $('.field-<?php echo $field_slug; ?>').wpColorPicker();
              });
          })(jQuery);
          </script>

        </td>
      </tr>

      <?php 
      /**
       * Textarea
       */
      elseif ($field['type'] == 'textarea') : ?>
            
        <tr>
          <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php if (isset($field['tooltip'])) { echo WU_Util::tooltip($field['tooltip']); } ?></th>
          <td>
            <textarea cols="60" rows="7" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" class="regular-text" value="" placeholder="<?php echo isset($field['placeholder']) ? $field['placeholder'] : ''; ?>"><?php echo WU_Settings::get_setting($field_slug); ?></textarea>

            <?php if (!empty($field['desc'])) : ?>
            <p class="description" id="<?php echo $field_slug; ?>-desc">
              <?php echo $field['desc']; ?>       
            </p>
            <?php endif; ?>

          </td>
        </tr>

      <?php 
      /**
       * WP Editor
       */
      elseif ($field['type'] == 'wp_editor') : ?>
            
        <tr>
          <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?></th>
          <td>
            <!--<textarea cols="60" rows="7" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" class="regular-text" value="" placeholder="<?php echo isset($field['placeholder']) ? $field['placeholder'] : ''; ?>"><?php echo WU_Settings::get_setting($field_slug); ?></textarea>-->

            <div style="max-width: 800px;">
            <?php wp_editor(WU_Settings::get_setting($field_slug), $field_slug, isset($field['args']) ? $field['args'] : array()); ?>
            </div>

            <?php if (!empty($field['desc'])) : ?>
            <p class="description" id="<?php echo $field_slug; ?>-desc">
              <?php echo $field['desc']; ?>       
            </p>
            <?php endif; ?>

          </td>
        </tr>

      <?php 
      /**
       * Note
       */
      elseif ($field['type'] == 'note') : 

        $colspan = isset($field['title']);

        ?>
            
        <tr>

          <?php if ($colspan) : ?>

            <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label></th>
          
          <?php endif; ?>

          <?php if (!$colspan) { ?>

            <th colspan="2" style="padding-top: 0 !important;">

          <?php } else { ?>

            <td>

          <?php } ?>

            <?php if (!empty($field['desc'])) : ?>
            <p class="description" id="<?php echo $field_slug; ?>-desc">
              <?php echo $field['desc']; ?>       
            </p>
            <?php endif; ?>

          </td>
        </tr>


        <?php 
        /**
         * Image Upload
         */
        elseif ($field['type'] == 'image') : 

          // We need to get the media scripts
          wp_enqueue_media();
          wp_enqueue_script('media');
          
          $suffix = WP_Ultimo()->min;

          wp_enqueue_script('wu-field-button-upload', WP_Ultimo()->get_asset("wu-field-image$suffix.js", 'js')); 

          ?>

        <tr>
          <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label></th>
          <td>

            <?php $image_url = WU_Settings::get_logo('full', false, $field_slug, false);

            if (!$image_url && isset($field['default'])) $image_url = $field['default'];

            $image = '<img id="%s" src="%s" alt="%s" style="width:%s; height:auto; display: %s">';
            printf(
              $image,
              $field_slug.'-preview',
              ($image_url) ? $image_url :  '#',
              get_bloginfo('name'),
              $field['width'].'px',
              ($image_url) ? 'block' :  'none'
            );
            ?>

            <br>

            <a href="#" class="button wu-field-button-upload" data-target="<?php echo $field_slug; ?>">
              <?php echo $field['button'] ?>
            </a>

            <a data-default="<?php echo $field['default']; ?>" href="#" class="button wu-field-button-upload-remove" data-target="<?php echo $field_slug; ?>">
              <?php _e('Remove Image', 'wp-ultimo'); ?>
            </a> 

            <?php
            if ($field['default']) {
              ?>
              <a data-value="<?php echo $field['default']; ?>" href="#" class="button wu-field-button-upload-set-default" data-target="<?php echo $field_slug; ?>">
                <?php _e('Set Default', 'wp-ultimo'); ?>
              </a> 
              <?php
            }
            ?>

            <?php if (!empty($field['desc'])) : ?>
            <p class="description" id="<?php echo $field_slug; ?>-desc">
              <?php echo $field['desc']; ?>       
            </p>

            <input type="hidden" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" value="<?php echo WU_Settings::get_setting($field_slug) ? WU_Settings::get_setting($field_slug) : $field['default']; ?>">

            <?php endif; ?>

          </td>
        </tr>

      <?php 
      /**
       * Multi Checkbox
       */
      elseif ($field['type'] == 'multi_checkbox') : ?>
            
        <tr id="multiselect-<?php echo $field_slug; ?>">
          <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?></th>
          <td>

            <?php

            // Check if it was selected
            $settings = WU_Settings::get_setting($field_slug);

            if ($settings === false) {

              $settings = isset($field['default']) ? $field['default'] : false;

            } 
            
            /**
             * Allow multi-select
             * @since 1.5.0
             */

            $sortable_class = isset($field['sortable']) && $field['sortable'] ? 'wu-sortable' : '';
            
            // If sortable, merge settings and list of items
            if (isset($field['sortable']) && $field['sortable'] && $settings) {

              $_settings = $settings;

              foreach ($_settings as $key => &$value) {
                
                if (!isset($field['options'][$key])) {

                  unset($_settings[$key]);

                  continue;

                } // end if;

                $value = $field['options'][$key];

              } // end foreach;

              $field['options'] = $_settings + $field['options'];

            } // end if;

            ?>

            <div class="row <?php echo $sortable_class; ?>">

            <?php
            /**
             * Loop the values
             */
            foreach ($field['options'] as $field_value => $field_name) : 

              // Check this setting
              $this_settings = isset($settings[$field_value]) ? $settings[$field_value] : false;

              ?>

              <div class="wu-col-sm-4" style="margin-bottom: 2px;">

                <label for="multiselect-<?php echo $field_value; ?>">
                  <input <?php checked($this_settings); ?> name="<?php echo sprintf('%s[%s]', $field_slug, $field_value); ?>" type="checkbox" id="multiselect-<?php echo $field_value; ?>" value="1">
                  <?php echo $field_name; ?>
                </label>
              
              </div>

            <?php endforeach; ?>

            </div>

            <?php if (!empty($field['desc'])) : ?>

            <div style="clear: both"> </div> <br>

            <button type="button" data-select-all="multiselect-<?php echo $field_slug; ?>" class="button wu-select-all"><?php _e('Check / Uncheck All', 'wp-ultimo'); ?></button>

            <br>

            <p class="description" id="<?php echo $field_slug; ?>-desc">
              <?php echo $field['desc']; ?>       
            </p>

            <?php endif; ?>

          </td>
        </tr>

    <?php 
    /**
     * Select2 Block
     */
    elseif ($field['type'] == 'select2') :
    
      $setting = WU_Settings::get_setting($field_slug);

      $setting = is_array($setting) ? $setting : array();

      $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';

      WP_Ultimo()->enqueue_select2();

    ?>
          
      <tr>
        <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?> </th>
        <td>
          
          <select data-width="350px" multiple="multiple" placeholder="<?php echo $placeholder; ?>"  class="wu-select" name="<?php echo $field_slug; ?>[]" id="<?php echo $field_slug; ?>">
            
            <?php foreach($field['options'] as $value => $option) : ?>
            <option <?php selected(in_array($value, $setting)); ?> value="<?php echo $value; ?>"><?php echo $option; ?></option>
            <?php endforeach; ?>
            
          </select>

          <?php if (!empty($field['desc'])) : ?>
          <p class="description" id="<?php echo $field_slug; ?>-desc">
            <?php echo $field['desc']; ?>				
          </p>
          <?php endif; ?>

        </td>
      </tr>

    <?php 
    /**
     * Normal fields
     */
    else : ?>
          
      <tr>
        <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php if (isset($field['tooltip'])) {echo WU_Util::tooltip($field['tooltip']);} ?></th>
        <td>
          <input <?php if (isset($field['html_attr'])) { echo implode(' ', array_map(
    function ($k, $v) { return $k .'="'. htmlspecialchars($v) .'"'; },
    array_keys($field['html_attr']), $field['html_attr']
          )); } ?>  <?php echo $disabled ? 'disabled="disabled"' : ''; ?> name="<?php echo $field_slug; ?>" type="<?php echo $field['type']; ?>" id="<?php echo $field_slug; ?>" class="regular-text" value="<?php echo WU_Settings::get_setting($field_slug); ?>" placeholder="<?php echo isset($field['placeholder']) ? $field['placeholder'] : ''; ?>">

          <?php if (isset($field['append']) && !empty($field['append'])) : ?>
            <?php echo $field['append']; ?>
          <?php endif; ?>

          <?php if (!empty($field['desc'])) : ?>
          <p class="description" id="<?php echo $field_slug; ?>-desc">
            <?php echo $field['desc']; ?>				
          </p>
          <?php endif; ?>

        </td>
      </tr>
          
    <?php endif; 

    // @since 1.0.4
    // Required fields
    if (isset($field['require']) && $field['require']) : ?>

    <script type="text/javascript">
      
    (function($) {

      function check_value($field, value) {
        var result = false;
        result = result || $field.val() == value;

        if ($field.attr('type') == 'checkbox')
          result = $field.attr('checked') == 'checked';

        return result;
      }

      function countProperties(obj) {

          var count = 0;

          for(var prop in obj) {
              if(obj.hasOwnProperty(prop))
                  ++count;
          }

          return count;
      }

      <?php if ($field['type'] == 'heading') : ?>

      var $field = $('#<?php echo $field_slug; ?>');

      <?php else: ?>

      var $field = $('[name^="<?php echo $field_slug; ?>"]').parents('tr');

      <?php endif; ?>

      $field.hide();

      var checkers = <?php echo json_encode($field['require']); ?>;

      $field.data('requirements_met', 0);

      var requirements_met = 1, count = countProperties(checkers);

      $.each(checkers, function(name, value) {

        // Displays initially
        $required_field = $("[name='"+ name +"']");

        if (check_value($required_field, value)) {
          $field.data('requirements_met', parseInt($field.data('requirements_met'), 10) + 1);
        }

        if ($field.data('requirements_met') == count) {
          $field.show();
        } 


        /** On Change */
        $required_field.on('change', function() {

          if (check_value($(this), value)) {
            $field.data('requirements_met', parseInt($field.data('requirements_met'), 10) + 1);
            $field.data(name, 1);
          } else {
            $field.data('requirements_met', parseInt($field.data('requirements_met'), 10) - 1);
          }

          // console.log($field.data('requirements_met'));
          // console.log(count);
          // console.log(checkers);

          if ($field.data('requirements_met') == count) {
            $field.show();
          } else {
            $field.hide();
          }
        });

      });

    })(jQuery);

    </script>


    <?php endif;

    endforeach;
    
    /**
     * After the form
     */
    do_action('wu_after_settings_section_'.$_GET['wu-tab']);
          
    endif; ?>
    
    <?php /** Print end table, if necessary */ ?>
    <?php if ($table_open) : ?>
      </tbody></table>
    <?php endif; ?>
          
    <p class="submit">
      <button type="submit" name="_submit" id="_submit" class="button button-primary"><?php _e('Save Changes', 'wp-ultimo'); ?></button>
      <?php wp_nonce_field('wu_settings'); ?>
      <input type="hidden" name="wu_action" value="save_settings">	
    </p>

    </div>

    <?php
    /**
     * Add widgets to link forum and Docs Page
     */?>
    <div class="wu-col-sm-3 metabox-holder">
      
      <?php 

        /**
         * Renders the metabox for forum and more
         * @since 1.3.0
         */
        do_meta_boxes(get_current_screen()->id, 'normal', false);

      ?>

      <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false); ?>

    </div>
    

  </form>

</div>

<style>

#wu-forum ul li a {
  text-decoration: none;
}

#wu-forum .inside {
  padding-bottom: 0 !important;
}

/**
* Support list
*/
.support-available li::before,
.support-unavailable li::before {
  content: "\f147";
  font-family: dashicons;
  font-weight: bold;
  background-color: green;
  width: 20px;
  height: 20px;
  border-radius: 10px;
  margin-right: 10px;
  color: #fff;
  line-height: 20px;
  display: inline-block;
  text-align: center;
  vertical-align: middle;
}

.support-unavailable li::before {
  background-color: darkred;
  content: "\f460";
}

.support-available li {
  color: green;
}

.support-unavailable li {
  color: darkred;
}
</style>