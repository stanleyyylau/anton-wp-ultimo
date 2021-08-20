<?php
/**
 * The Template for displaying the signup flow for the end user
 *
 * This template can be overridden by copying it to yourtheme/wp-ultimo/signup/signup-header.php.
 *
 * HOWEVER, on occasion WP Ultimo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author      NextPress
 * @package     WP_Ultimo/Views
 * @version     1.4.0
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

do_action('wu_signup_enqueue_scripts');

/**
 * Enqueue some much needed styles
 */
// add_action('wp_print_scripts', array($signup, 'print_logo'));

$suffix = WP_Ultimo()->min;

/**
 * TODO: Move all of this mess of script declaring to the signup class or the scripts class
 * @since 1.9.0
 */
wp_enqueue_script('jquery-blockui');
wp_enqueue_script('wu-pricing-table');
wp_enqueue_script('wp-ultimo');

wp_enqueue_style('admin-bar');
wp_enqueue_style('wu-signup', WP_Ultimo()->url("assets/css/wu-signup$suffix.css"), array('dashicons', 'install'));

// Do not get the login if the first step
if ($signup->step != 'plan') wp_enqueue_style('login');

// Our custom CSS
if (!WU_UI_Elements()->has_admin_theme_active()) {
  wp_enqueue_style('wu-login');
}

wp_enqueue_style('wu-shortcodes');
wp_enqueue_style('common');

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>

  <head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <title>
      <?php echo apply_filters('wu_signup_page_title', sprintf(__('%s - Signup', 'wp-ultimo'), get_bloginfo('Name'), get_bloginfo('Name'))); ?>
    </title>

    <?php // Signup do action, like the default ?>
    <?php do_action('signup_header'); ?>
    <?php do_action('login_enqueue_scripts'); ?>
    <?php do_action('admin_print_scripts'); ?>
    <?php do_action('admin_print_styles'); ?>
    <?php do_action('wu_signup_enqueue_scripts'); ?>

    <?php do_action('admin_head'); ?>

  </head>

  <body class="login wp-core-ui">

    <div class="wu-setup">

    <?php

    /**
     * Fires right after the start body tag is printed
     * @since 1.6.2
     */
    do_action('wu_signup_header'); ?>

      <div id="login">

        <h1 id="wu-setup-logo">
          <a href="<?php echo get_site_url(get_current_site()->blog_id); ?>">
            <?php printf(__('%s - Signup', 'wp-ultimo'), get_bloginfo('Name')); ?>
          </a>
        </h1>

        <?php
        
        /**
         * Fires before the site sign-up form.
         */
        do_action('wu_before_signup_form');

        /**
         * Get the actual view for that step
         */
        $signup->get_step_view($signup->step);
        
        /**
         * Fires after the sign-up forms, before signup-footer
         */
        do_action('wu_after_signup_form');

        ?>

        <?php
        /**
         * Nav Links
         */
        wu_get_template('signup/signup-nav-links', array('signup' => $signup)); ?>

      </div> <!-- /login -->

      <?php
      /**
       * Navigation Steps
       */
      wu_get_template('signup/signup-steps-navigation', array('signup' => $signup)); ?>

      <?php
      /**
       * Fires right after the start body tag is printed
       * @since 1.6.2
       */
      do_action('wu_signup_footer'); ?>

      <?php 
      /**
       * We also need to print the footer admin scripts, to make sure we are enqueing some of the scripts dependencies
       * our scripts need in order to function properly
       */
      do_action('admin_print_footer_scripts'); ?>

    </div>

  </body>

</html>