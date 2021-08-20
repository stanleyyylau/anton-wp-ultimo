<table class="form-table">
  <tbody>

    <!-- WP Ultimo: Site Plan -->
    <tr class="form-field form-required wu-site-plan">

      <th scope="row">
        <label for="site_plan"><?php _e('Site Plan', 'wp-ultimo'); ?></label>
      </th>

      <td>

        <select id="site_plan" name="site_plan">
          <option value=''><?php _e('Select Plan', 'wp-ultimo'); ?></option>
          <?php

          // Get plans and make the dropdown
          $plans = WU_Plans::get_plans();
          
          // Loop them
          foreach ($plans as $plan) :

          ?>

          <option value="<?php echo $plan->id; ?>"><?php echo $plan->title; ?></option>

          <?php endforeach; ?>
        </select>

        <span class="description"><?php _e('You can add a new blog directly to one of the plans.', 'wp-ultimo'); ?></span>

      </td>

    </tr>

    <!-- WP Ultimo: Site Template -->
    <tr class="form-field form-required">

      <th scope="row">
        <label for="site_template"><?php _e('Site Template', 'wp-ultimo'); ?></label>
      </th>

      <td>

        <select id="site_template" name="site_template">
          <?php foreach(WU_Site_Hooks::get_available_templates() as $site_id => $site_name) : ?>
            <option value="<?php echo $site_id; ?>"><?php echo $site_name; ?></option>
          <?php endforeach; ?>
        </select>

        <span class="description"><?php _e('This is going to be used in the creation of the new blog.', 'wp-ultimo'); ?></span>

      </td>

    </tr>

  </tbody>
</table>

<?php
/**
 * @since  1.2.0 Pre-fill some fields if an argument is passed
 */
if (isset($_GET['wu_user'])) : $user = get_user_by('id', $_GET['wu_user']); ?>

<script type="text/javascript">
  (function($) {
    $(document).ready(function() {
      
      var wu_user_email = "<?php echo $user->user_email; ?>";
      var text = "<?php printf(__('for User %s', 'wp-ultimo'), $user->display_name); ?>"

      // $('.form-table:last').hide();
      $('[colspan="2"], .wu-site-plan').hide();

      $('#admin-email').val(wu_user_email).parents('tr').hide();

      $('#add-new-site').text(function() {
        return $(this).text() + ' ' + text;
      })

    });
  })(jQuery);
</script>
<?php endif; ?>

<?php
/**
 * Template Duplication
 */

$has_template = false;

// Get the template
if (isset($_GET['site_template'])) {

  // Get the template
  $template = new WU_Site_Template($_GET['site_template']);

  if ($template && $template->is_template) {
    
    $has_template = true;

  } // end if;

} // end if;

if ($has_template) : ?>
<script type="text/javascript">
  (function($) {
    $(document).ready(function() {
      
      var text = "<?php printf(__('Duplicate \'%s\'', 'wp-ultimo'), $template->blogname); ?>"

      $('.wu-site-plan').hide();
      $('#admin-email').val("<?php echo get_network_option(null, 'admin_email'); ?>").parents('tr').hide();
      $('#site-title').val("<?php echo $template->blogname; ?>").parents('tr');
      $('#site_template').val(<?php echo $template->id; ?>).parents('tr').hide();

      $('.form-table').eq(0).find('tr:last').hide();

      $('#add-new-site').text(function() {
        return text;
      })

    });
  })(jQuery);
</script>
<?php endif; ?>
