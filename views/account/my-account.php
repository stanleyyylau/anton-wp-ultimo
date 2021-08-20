<?php do_action('wu-my-account-page'); ?>

<div id="wp-ultimo-wrap" class="wrap">

<?php
/**
 * Display the plan selection Page
 */
if (isset($_GET['action']) && $_GET['action'] == 'change-plan' && empty($_POST)) :

  $suffix = WP_Ultimo()->min;

  wp_enqueue_script('jquery-blockui');

  wp_enqueue_script('wp-ultimo');

  wp_enqueue_style('wu-pricing-table');

  wp_enqueue_script('wu-pricing-table');

  wp_enqueue_style('wu-signup', WP_Ultimo()->url("assets/css/wu-signup$suffix.css"));

  wp_enqueue_style('wu-login');

?>
  
  <h1><?php echo __('Select Plan', 'wp-ultimo'); ?></h1>

  <p class="description"><?php _e('Select which plan you want to change to.', 'wp-ultimo'); ?></p>

  <div class="wu-content-plan">

  <?php
   
    $current_plan = wu_get_current_site()->get_plan();

    // Get all available plans
    $plans = WU_Plans::get_plans(true, $current_plan->id);

    // Render the selector
    WP_Ultimo()->render('signup/pricing-table/pricing-table', array(
      'plans'        => $plans,
      'signup'       => WU_Signup(),
      'current_plan' => $current_plan,
      'is_shortcode' => true,
      'atts'         => array(
        'primary_color'          => WU_Settings::get_setting('primary-color', '#00a1ff'),
        'accent_color'           => WU_Settings::get_setting('accent-color', '#78b336'),
        'default_pricing_option' => WU_Settings::get_setting('default_pricing_option', 1),
        'show_selector'          => true,
      )
    ));

  ?>

  </div>

<?php
/**
 * Display the plan selection confirmation Page
 */
elseif (isset($_GET['action']) && $_GET['action'] == 'change-plan' && isset($_POST['changing-plan'])) : 

  // Get the new plan
  $new_plan = new WU_Plan($_POST['plan_id']);

  ?>

  <h1><?php echo __('Are you sure about that?', 'wp-ultimo'); ?></h1>

  <p class="description"><?php echo sprintf(__('Are you sure you want to change your current plan to <strong>%s</strong>, billed every <strong>%s months</strong>?', 'wp-ultimo'), $new_plan->title, esc_html($_POST['plan_freq'])); ?></p>


  <form method="post">

    <input type="hidden" name="wu_action" value="wu_change_plan">
    <input type="hidden" id="wu_plan_freq" name="plan_freq" value="<?php echo esc_attr($_POST['plan_freq']); ?>">
    <input type="hidden" name="plan_id" value="<?php echo esc_attr($_POST['plan_id']); ?>">
    <?php wp_nonce_field('wu-change-plan'); ?>

    <br>
    <button class="button button-primary" type="submit"><?php _e('Yes, I\'m sure', 'wp-ultimo'); ?></button>
    <a href="<?php echo admin_url('admin.php?page=wu-my-account'); ?>" class="button" type="submit"><?php _e('No, bring me back.', 'wp-ultimo'); ?></a>
  </form>

<?php else : ?>

  <h1><?php echo apply_filters('wu_my_accounts_page_title', __('Account', 'wp-ultimo')); ?></h1>

  <?php // do_action('admin_notices'); ?>
  
  <p class="description"><?php _e('Here is a summary of your Account Status and Plan Settings.', 'wp-ultimo'); ?></p>
  
  <div id="dashboard-widgets-wrap">
    <div id="dashboard-widgets" class="metabox-holder">

      <div id="postbox-container-1" class="postbox-container">
        <?php do_meta_boxes('wu-my-account', 'normal', '' ); ?>
      </div>

      <div id="postbox-container-2" class="postbox-container">
        <?php do_meta_boxes('wu-my-account', 'side', '' ); ?>
      </div>

    </div>
  </div>

  <?php if (isset($_GET['action']) && $_GET['action'] == 'integration-end') : ?>
  
    <?php //echo WU_Util::display_alert(__('Success!', 'wp-ultimo'), __('Your new payment method was added with success! You should get the first receipt for your subscription soon.', 'wp-ultimo')); ?>

  <?php endif; ?>

  <?php if (isset($_GET['action']) && $_GET['action'] == 'integration-removed') : ?>

    <?php echo WU_Util::display_alert(__('Success!', 'wp-ultimo'), __('Your integration was removed successfuly.', 'wp-ultimo')); ?>

  <?php endif; ?>

  <?php if (isset($_GET['action']) && $_GET['action'] == 'plan-changed') : ?>

    <?php echo WU_Util::display_alert(__('Success!', 'wp-ultimo'), __('Your plan was changed successfuly.', 'wp-ultimo')); ?>
  
  <?php endif; ?>

  <?php if (isset($_GET['action']) && $_GET['action'] == 'refund-given') : ?>

    <?php echo WU_Util::display_alert(__('Success!', 'wp-ultimo'), __('Refund given with success.', 'wp-ultimo')); ?>
  
  <?php endif; ?>

<?php endif; ?>

</div>