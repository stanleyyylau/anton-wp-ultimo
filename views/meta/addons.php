<?php

wp_enqueue_script('jquery-blockui');

wp_reset_vars( array( 'theme', 'search' ) );

wp_localize_script( 'wu-addons-page', '_wpThemeSettings', array(
    'themes'   => $addons,
    'nonce'    => wp_create_nonce('wu_install_addon'),
    'settings' => array(
        'canInstall'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ),
        'installURI'    => ( ! is_multisite() && current_user_can( 'install_themes' ) ) ? admin_url( 'theme-install.php' ) : null,
        'confirmDelete' => __( "Are you sure you want to delete this theme?\n\nClick 'Cancel' to go back, 'OK' to confirm the delete." ),
        'adminUrl'      => parse_url( admin_url(), PHP_URL_PATH ),
    ),
    'l10n' => array(
        'installing'        => __( 'Installing Add-on', 'wp-ultimo'),
        'error'             => __( 'An error occured', 'wp-ultimo'),
        'addNew'            => __( 'Add New Plugin', 'wp-ultimo'),
        'search'            => __( 'Search available addons', 'wp-ultimo'),
        'searchPlaceholder' => __( 'Search available addons...', 'wp-ultimo'), // placeholder (no ellipsis)
        'themesFound'       => __( 'Number of Plugins found: %d', 'wp-ultimo'),
        'noThemesFound'     => __( 'No addons found. Try a different search.', 'wp-ultimo'),
    ),
) );

// set_current_screen('themes');

add_thickbox();

wp_enqueue_script('wu-addons-page');
wp_enqueue_style('theme');

$current_theme_actions = array();

?>
  <div class="wrap" id="wu-addon">
    
    <h1><?php esc_html_e( 'WP Ultimo Add-ons', 'wp-ultimo' ); ?>
        <span class="title-count theme-count"><?php echo count( $addons ); ?></span>
    </h1>

    <?php if ($golden_ticket_type == 2) : ?>

      <div class="wu-golden-ticket " style="padding: 12px 0">
        <span class="wu-golden-ticket-label"><span class="dashicons dashicons-tickets"></span> <?php _e('Golden Ticket', 'wp-ultimo'); ?></span>
        <?php _e('You were an early supporter of WP Ultimo! You\'ll have unlimited access to all of our add-ons! Just click on their respective "install buttons".', 'wp-ultimo'); ?> <?php echo WU_Util::tooltip(__('You are seeing this because you have purchased WP Ultimo during our prelaunch campaign! Thank you again!', 'wp-ultimo')); ?>
      </div>

    <?php endif; ?>

    <div class="wp-filter">
      <ul class="filter-links">

        <li>
            <a href="#" class="current" data-category=""><?php _e('All Add-ons', 'wp-ultimo'); ?></a>
        </li>

        <li class="selector-inactive">
            <a href="#" class="" data-category="installed"><?php _e('Installed', 'wp-ultimo'); ?></a>
        </li>

        <li class="selector-inactive">
            <a href="#" class='' data-category="Premium"><?php _e('Premium', 'wp-ultimo'); ?></a>
        </li>

        <li class="selector-inactive">
            <a href="#" class="" data-category="Free"><?php _e('Free', 'wp-ultimo'); ?></a>
        </li>

        <?php foreach (array_unique($categories) as $cat) { ?>

          <li>
              <a href="?s=<?php echo $cat; ?>" class="" data-category="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></a>
          </li>

        <?php } ?>

      </ul>
    </div>

  <div class="theme-browser">
    <div class="themes wp-clearfix">

      <?php
        /*
        * This PHP is synchronized with the tmpl-theme template below!
        */

        foreach ( $addons as $addon ) :
        $aria_action = esc_attr( $addon['id'] . '-action' );
        $aria_name   = esc_attr( $addon['id'] . '-name' );
        ?>

        <div class="theme<?php if ( $addon['active'] ) echo ' active'; ?>" tabindex="0" aria-describedby="<?php echo $aria_action . ' ' . $aria_name; ?>">
          <?php if ( ! empty( $addon['screenshot'][0] ) ) { ?>

          <div class="theme-screenshot">
            <img src="<?php echo $addon['screenshot'][0]; ?>" alt="" />
          </div>

          <?php } else { ?>

          <div class="theme-screenshot blank"></div>
          <?php } ?>

          <span class="more-details" id="<?php echo $aria_action; ?>"><?php _e('Add-on Details', 'wp-ultimo'); ?></span>

          <div class="theme-author">
            <?php printf( __( 'By %s' ), $addon['author'] ); ?>
          </div>
          <?php if ( $addon['active'] ) { ?>
          <h2 class="theme-name" id="<?php echo $aria_name; ?>">
              <?php
              /* translators: %s: theme name */
              printf( __( '<span>Installed:</span> %s' ), $addon['name'] );
              ?>
          </h2>

          <?php } else { ?>
          <h2 class="theme-name" id="<?php echo $aria_name; ?>"><?php echo $addon['name']; ?></h2>
          <?php } ?>

          <div class="theme-actions">

            <?php if ( !$addon['installed'] ) { ?>

              <?php if ( $addon['sale'] ) { ?>

                <?php if ( !$addon['golden_ticket'] ) { ?>

                  <a class="button button-primary" href="<?php echo $addon['actions']['buy']; ?>">
                    <?php _e( 'More Info', 'wp-ultimo' ); ?>
                  </a>

                <?php } else { ?>

                  <a data-install-url="<?php echo $addon['download']; ?>" class="button button-primary gt-install" href="<?php echo $addon['actions']['install']; ?>">
                    <?php _e( 'Install it Now!', 'wp-ultimo' ); ?>
                  </a>

                <?php } ?>

              <?php } else { ?>

              <a target="_blank" class="button button-primary" href="<?php echo $addon['actions']['moreInfo']; ?>">
                <?php _e( 'More Info', 'wp-ultimo' ); ?>
              </a>

              <?php } ?>


            <?php } ?>
            
          </div>

        </div>
        <?php endforeach; ?>
    </div>
  </div>

  <div class="theme-overlay"></div>
  
  <p class="no-themes">
    <?php _e( 'No Addons found.', 'wp-ptm' ); ?>
  </p>
         
  </div>
  <!-- .wrap -->
  <?php
/*
 * The tmpl-theme template is synchronized with PHP above!
 */
?>
    <script id="tmpl-theme" type="text/template">
      <# if ( data.screenshot[0] ) { #>
        <div class="theme-screenshot">
          <img src="{{ data.screenshot[0] }}" alt="" />
        </div>
      <# } else { #>
        <div class="theme-screenshot blank"></div>
      <# } #>
            
      <span class="more-details" id="{{ data.id }}-action"><?php _e('Add-on Details', 'wp-ultimo'); ?></span>

      <div class="theme-author">
        <?php
        /* translators: %s: Theme author name */
        printf( __( 'By %s' ), '{{{ data.author }}}' );
        ?>
      </div>

      <# if ( data.active ) { #>
        <h2 class="theme-name" id="{{ data.id }}-name">
          <?php
          /* translators: %s: Theme name */
          printf( __( '<span>Installed:</span> %s' ), '{{{ data.name }}}' );
          ?>
        </h2>
      <# } else { #>
        <h2 class="theme-name" id="{{ data.id }}-name">{{{ data.name }}}</h2>
      <# } #>

      <div class="theme-actions">

        <# if ( !data.installed ) { #>

          <# if ( data.sale ) { #>

            <# if ( !data.golden_ticket ) { #>

              <a class="button button-primary" href="{{{ data.actions.buy }}}">
                <?php _e( 'More Info', 'wp-ultimo' ); ?>
              </a>

            <# } else { #>

              <a data-install-url="{{{ data.download }}}" class="button button-primary gt-install" href="{{{ data.actions.install }}}">
                <?php _e( 'Install it Now!', 'wp-ultimo' ); ?>
              </a>

            <# } #>

          <# } else { #>

          <a target="_blank" class="button button-primary" href="{{{ data.actions.moreInfo }}}">
            <?php _e( 'More Info', 'wp-ultimo' ); ?>
          </a>

          <# } #>

        <# } #>
        
      </div>

    </script>

    <script id="tmpl-theme-single" type="text/template">
      <div class="theme-backdrop"></div>
      <div class="theme-wrap wp-clearfix">
        <div class="theme-header">
          <button class="left dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show previous theme' ); ?></span></button>
          <button class="right dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Show next theme' ); ?></span></button>
          <button class="close dashicons dashicons-no"><span class="screen-reader-text"><?php _e( 'Close details dialog' ); ?></span></button>
        </div>
        <div class="theme-about wp-clearfix">
          <div class="theme-screenshots">
            <# if ( data.screenshot[0] ) { #>
              <div class="screenshot"><img src="{{ data.screenshot[0] }}" alt="" /></div>
              <# } else { #>
                <div class="screenshot blank"></div>
                <# } #>
          </div>
          <div class="theme-info">
            <# if ( data.active ) { #>
              <span class="current-label"><?php _e( 'Active' ); ?></span>
              <# } #>
                <h2 class="theme-name">{{{ data.name }}}<span class="theme-version"><?php printf( __( '%s' ), '{{ data.version }}' ); ?></span></h2>
                <p class="theme-author">
                  <?php printf( __( '%s' ), '{{{ data.authorAndUri }}}' ); ?>
                </p>
                <# if ( data.hasUpdate ) { #>
                  <div class="notice notice-warning notice-alt notice-large">
                    <h3 class="notice-title"><?php _e( 'Update Available' ); ?></h3> {{{ data.update }}}
                  </div>
                  <# } #>
                    <p class="theme-description">{{{ data.description }}}</p>
                    <# if ( data.parent ) { #>
                      <p class="parent-theme">
                        <?php printf( __( 'This is a child theme of %s.' ), '<strong>{{{ data.parent }}}</strong>' ); ?></p>
                      <# } #>
                        <# if ( data.tags ) { #>
                          <p class="theme-tags"><span><?php _e( 'Tags:' ); ?></span> {{{ data.tags }}}</p>
                          <# } #>
          </div>
        </div>
        <div class="theme-actions">
          <div class="active-theme">
            
          </div>
          <div class="inactive-theme">
            <# if ( !data.installed ) { #>

                      <# if ( data.sale ) { #>

                        <# if ( !data.golden_ticket ) { #>

                          <a class="button button-primary" href="{{{ data.actions.buy }}}">
                            <?php _e( 'More Info', 'wp-ultimo' ); ?>
                          </a>

                        <# } else { #>

                          <a data-install-url="{{{ data.download }}}" class="button button-primary gt-install" href="{{{ data.actions.install }}}">
                            <?php _e( 'Install it Now!', 'wp-ultimo' ); ?>
                          </a>

                        <# } #>

                      <# } else { #>

                      <a target="_blank" class="button button-primary" href="{{{ data.actions.moreInfo }}}">
                        <?php _e( 'More Info', 'wp-ultimo' ); ?>
                      </a>

                      <# } #>

                    <# } #>
          </div>
         
        </div>
      </div>
    </script>
    <?php
wp_print_request_filesystem_credentials_modal();
wp_print_admin_notice_templates();
wp_print_update_row_templates();

wp_localize_script( 'updates', '_wpUpdatesItemCounts', array(
    'totals'  => wp_get_update_data(),
) );

