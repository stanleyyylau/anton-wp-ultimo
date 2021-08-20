<?php

global $wpdb;

// Check if we have templates enabled
$has_templates = WU_Settings::get_setting('allow_template') && !empty(WU_Settings::get_setting('templates'));

if ($has_templates && !isset($_REQUEST['save_step']) && !isset($_REQUEST['create_site'])) {

  /**
   * Render the template step
   */
  WP_Ultimo()->render('signup/steps/step-template', array(
    'signup' => new WU_Signup,
  ));

} else {

  // Reset admin email to the email we know this user has
  // <input name="blog[email]" type="email" class="regular-text wp-suggest-user" id="admin-email" data-autocomplete-type="search" data-autocomplete-field="user_email" />

  if (!empty($_POST) && isset($_REQUEST['create_site'])) {

    $_POST['blog']['email'] = wu_get_current_site()->site_owner->user_email;

  }

  /** WordPress Translation Install API */
  require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

  if ( isset($_GET['update']) ) {
    $messages = array();
    if ( 'added' == $_GET['update'] )
      $messages[] = sprintf(
        /* translators: 1: dashboard url, 2: network admin edit url */
        __( 'Site added. <a href="%1$s">Visit Dashboard</a> or <a href="%2$s">Edit Site</a>' ),
        esc_url( get_admin_url( absint( $_GET['id'] ) ) ),
        network_admin_url( 'site-info.php?id=' . absint( $_GET['id'] ) )
      );
  }

  $title = __('Add New Site');
  $parent_file = 'sites.php';

  $new = isset($_GET['new']) ? $_GET['new'] : '';

  ?>

  <div class="wrap">
  <h1 id="add-new-site"><?php _e( 'Add New Site' ); ?></h1>
  <?php
  if ( ! empty( $messages ) ) {
    foreach ( $messages as $msg )
      echo '<div id="message" class="updated notice is-dismissible"><p>' . $msg . '</p></div>';
  } ?>
  <form method="post" action="<?php echo add_query_arg('action', 'add-site'); ?>" novalidate="novalidate">
  <?php wp_nonce_field( 'add-blog', '_wpnonce_add-blog' ) ?>
    <table class="form-table">
      <tr class="form-field form-required">
        <th scope="row"><label for="site-address"><?php _e( 'Site Address (URL)' ) ?></label></th>
        <td>
        <?php if ( is_subdomain_install() ) { ?>
          <input name="blog[domain]" type="text" value="<?php echo $new; ?>" class="regular-text" id="site-address" aria-describedby="site-address-desc" autocapitalize="none" autocorrect="off"/><span class="no-break">.<?php echo preg_replace( '|^www\.|', '', get_network()->domain ); ?></span>
        <?php } else {
          echo get_network()->domain . get_network()->path ?><input name="blog[domain]" type="text" class="regular-text" id="site-address" aria-describedby="site-address-desc"  value="<?php echo $new; ?>" autocapitalize="none" autocorrect="off" />
        <?php }
        echo '<p class="description" id="site-address-desc">' . __( 'Only lowercase letters (a-z), numbers, and hyphens are allowed.' ) . '</p>';
        ?>
        </td>
      </tr>
      <tr class="form-field form-required">
        <th scope="row"><label for="site-title"><?php _e( 'Site Title' ) ?></label></th>
        <td><input name="blog[title]" type="text" class="regular-text" id="site-title" /></td>
      </tr>
      <?php
      $languages    = get_available_languages();
      $translations = wp_get_available_translations();
      if ( ! empty( $languages ) || ! empty( $translations ) ) :
        ?>
        <tr class="form-field form-required">
          <th scope="row"><label for="site-language"><?php _e( 'Site Language' ); ?></label></th>
          <td>
            <?php
            // Network default.
            $lang = get_site_option( 'WPLANG' );

            // Use English if the default isn't available.
            if ( ! in_array( $lang, $languages ) ) {
              $lang = '';
            }

            wp_dropdown_languages( array(
              'name'                        => 'WPLANG',
              'id'                          => 'site-language',
              'selected'                    => $lang,
              'languages'                   => $languages,
              'translations'                => $translations,
              'show_available_translations' => wp_can_install_language_pack(),
            ) );
            ?>
          </td>
        </tr>
      <?php endif; // Languages. ?>
    </table>

    <input type="hidden" name="site_template" value="<?php echo esc_attr(isset($_POST['template']) ? $_POST['template'] : 0); ?>">
    <input type="hidden" name="user_creating" value="1">
    <input type="hidden" name="create_site" value="1">

    <?php
    /**
     * Fires at the end of the new site form in network admin.
     *
     * @since 4.5.0
     */
    do_action( 'network_site_new_form_for_user' );

    submit_button( __( 'Add Site' ), 'primary', 'add-site' );
    ?>
    </form>
  </div>


<?php } ?>