<?php
/**
 * WP Ultimo Setup Wizards Class
 *
 * Based on the amazing work of dtbaker
 *
 * @author      Arindo Duque
 * @category    Setup
 * @package     WP_Ultimo/Setup
 * @version     0.0.1
*/

if (!defined( 'ABSPATH')) {
  exit;
}

if (!class_exists('WU_Setup_Wizard')) {
  
  /**
   * WU_Setup_Wizard class
   */
  class WU_Setup_Wizard {

    /**
     * The class version number.
     *
     * @since 1.1.1
     * @access private
     *
     * @var string
     */
    protected $version = '1.1.4';

    /** @var string Current theme name, used as namespace in actions. */
    protected $theme_name = '';

    /** @var string Theme author username, used in check for oauth. */
    protected $envato_username = '';

    /** @var string Full url to server-script.php (available from https://gist.github.com/dtbaker ) */
    protected $oauth_script = '';

    /** @var string Current Step */
    protected $step   = '';

    /** @var array Steps for the setup wizard */
    protected $steps  = array();

    /**
     * Relative plugin path
     *
     * @since 1.1.2
     *
     * @var string
     */
    protected $plugin_path = '';

    /**
     * Relative plugin url for this plugin folder, used when enquing scripts
     *
     * @since 1.1.2
     *
     * @var string
     */
    protected $plugin_url = '';

    /**
     * The slug name to refer to this menu
     *
     * @since 1.1.1
     *
     * @var string
     */
    protected $page_slug;

    /**
     * TGMPA instance storage
     *
     * @var object
     */
    protected $tgmpa_instance;

    /**
     * TGMPA Menu slug
     *
     * @var string
     */
    protected $tgmpa_menu_slug = 'tgmpa-install-plugins';

    /**
     * TGMPA Menu url
     *
     * @var string
     */
    protected $tgmpa_url = 'themes.php?page=tgmpa-install-plugins';

    /**
     * The slug name for the parent menu
     *
     * @since 1.1.2
     *
     * @var string
     */
    protected $page_parent;

    /**
     * Complete URL to Setup Wizard
     *
     * @since 1.1.2
     *
     * @var string
     */
    protected $page_url;


    /**
     * Holds the current instance of the theme manager
     *
     * @since 1.1.3
     * @var WU_Setup_Wizard
     */
    private static $instance = null;

    /**
     * @since 1.1.3
     *
     * @return WU_Setup_Wizard
     */
    public static function get_instance() {
      if ( ! self::$instance ) {
        self::$instance = new self;
      }

      return self::$instance;
    }

    /**
     * A dummy constructor to prevent this class from being loaded more than once.
     *
     * @see WU_Setup_Wizard::instance()
     *
     * @since 1.1.1
     * @access private
     */
    public function __construct() {
      $this->init_globals();
      $this->init_actions();
    }

    /**
     * Setup the class globals.
     *
     * @since 1.1.1
     * @access private
     */
    public function init_globals() {

      $this->page_slug = apply_filters('wu_setup_slug', 'wu-setup');

      $this->parent_slug = apply_filters( $this->theme_name . '_theme_setup_wizard_parent_slug', '' );

      //If we have parent slug - set correct url
      $this->page_url = 'admin.php?page='.$this->page_slug;

      $this->page_url = apply_filters('wu_setup_page_url', $this->page_url);

      //set relative plugin path url
      $this->plugin_path = trailingslashit( $this->cleanFilePath( dirname( __FILE__ ) ) );

      $relative_url = WP_Ultimo()->url('/inc/setup/');
      $this->plugin_url = trailingslashit($relative_url);
    }

    /**
     * Setup the hooks, actions and filters.
     *
     * @uses add_action() To add actions.
     * @uses add_filter() To add filters.
     *
     * @since 1.1.1
     * @access private
     */
    public function init_actions() {


      if (current_user_can('manage_network')) {

        //register_activation_hook(WP_Ultimo()->path("wp-ultimo.php"), array($this, 'activate_plugin'));

        add_action('activate_wp-ultimo/wp-ultimo.php', array($this, 'activate_plugin'));

        // add_action('admin_init', array($this, 'switch_theme'));

        //              if (class_exists( 'TGM_Plugin_Activation' ) && isset($GLOBALS['tgmpa'])) {
        //                add_action( 'init', array( $this, 'get_tgmpa_instanse' ), 30 );
        //                add_action( 'init', array( $this, 'set_tgmpa_url' ), 40 );
        //              }

        add_action( 'network_admin_menu', array( $this, 'admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_init', array( $this, 'admin_redirects' ), 30 );
        add_action( 'admin_init', array( $this, 'init_wizard_steps' ), 30 );
        add_action( 'admin_init', array( $this, 'setup_wizard' ), 30 );
        add_filter( 'tgmpa_load', array( $this, 'tgmpa_load' ), 10, 1 );
        // add_action( 'wp_ajax_wu_setup_plugins', array( $this, 'ajax_plugins' ) );
        add_action( 'wp_ajax_wu_setup_content', array( $this, 'ajax_content' ) );
      }

      //          if (function_exists( 'envato_market' ) ) {
      //            add_action( 'admin_init', array( $this, 'envato_market_admin_init' ), 20 );
      //            add_filter( 'http_request_args', array( $this, 'envato_market_http_request_args' ), 10, 2 );
      //          }
    }

    public function enqueue_scripts() {
    }
    
    public function tgmpa_load( $status ) {
      return is_admin() || current_user_can( 'install_themes' );
    }

    public function activate_plugin() {
      if (get_network_option(null, '_wpultimo_activation_redirect') !== 'done')
        update_network_option(null,  '_wpultimo_activation_redirect', false);
    }

    public function admin_redirects() {
      ob_start();
      if (isset($_GET['page']) && $_GET['page'] == $this->page_slug) return;
      // TODO: Descomment the check below
      if (get_network_option(null, '_wpultimo_activation_redirect') == 'done') return;
      wp_safe_redirect(network_admin_url($this->page_url));
      update_network_option(null, '_wpultimo_activation_redirect', 'done');
      exit;
    }

    /**
     * Get configured TGMPA instance
     *
     * @access public
     * @since 1.1.2
     */
    public function get_tgmpa_instanse(){
      $this->tgmpa_instance = call_user_func( array( get_class( $GLOBALS['tgmpa'] ), 'get_instance' ) );
    }

    /**
     * Update $tgmpa_menu_slug and $tgmpa_parent_slug from TGMPA instance
     *
     * @access public
     * @since 1.1.2
     */
    public function set_tgmpa_url(){

      $this->tgmpa_menu_slug = ( property_exists($this->tgmpa_instance, 'menu') ) ? $this->tgmpa_instance->menu : $this->tgmpa_menu_slug;
      $this->tgmpa_menu_slug = apply_filters($this->theme_name . '_theme_setup_wizard_tgmpa_menu_slug', $this->tgmpa_menu_slug);

      $tgmpa_parent_slug = ( property_exists($this->tgmpa_instance, 'parent_slug') && $this->tgmpa_instance->parent_slug !== 'themes.php' ) ? 'admin.php' : 'themes.php';

      $this->tgmpa_url = apply_filters($this->theme_name . '_theme_setup_wizard_tgmpa_url', $tgmpa_parent_slug.'?page='.$this->tgmpa_menu_slug);

    }

    /**
     * Add admin menus/screens.
     */
    public function admin_menus() {
      // Add submenu
      add_submenu_page('wp-ultimo-setup', __('Setup Wizard', 'wp-ultimo'), __( 'Setup Wizard','wp-ultimo'), 'manage_network', $this->page_slug, array($this, 'setup_wizard'));
    }

    /**
     * Setup steps.
     *
     * @since 1.1.1
     * @access public
     * @return array
     */
    public function init_wizard_steps() {

      $this->steps = array(
        'introduction' => array(
          'name'    => __( 'Introduction', 'wp-ultimo'),
          'view'    => array($this, 'wu_setup_introduction' ),
          'handler' => '',
        ),
      );
    
      $this->steps['settings'] = array(
        'name'    => __('Settings', 'wp-ultimo'),
        'view'    => array($this, 'wu_setup_settings'),
        'handler' => array($this, 'wu_setup_settings_save'),
      );

      $this->steps['checks'] = array(
        'name'    => __('System', 'wp-ultimo'),
        'view'    => array($this, 'wu_setup_checks'),
        'handler' => array($this, 'wu_setup_checks_save'),
      );

      $this->steps['defaults'] = array(
        'name'    => __('Defaults', 'wp-ultimo'),
        'view'    => array($this, 'wu_setup_default_content'),
        'handler' => '',
      );

      $this->steps['design'] = array(
        'name'    => __('Logo', 'wp-ultimo'),
        'view'    => array($this, 'wu_setup_logo_design' ),
        'handler' => array($this, 'wu_setup_logo_design_save'),
      );

      $this->steps['activation-and-support'] = array(
        'name'    => __('Support', 'wp-ultimo'),
        'view'    => array($this, 'wu_setup_help_support'),
        'handler' => '',
      );

      $this->steps['next_steps'] = array(
        'name'    => __( 'Ready!', 'wp-ultimo'),
        'view'    => array($this, 'wu_setup_ready'),
        'handler' => '',
      );

      return apply_filters(  $this->theme_name . '_theme_setup_wizard_steps', $this->steps );

    }

    /**
		 * Show the setup wizard
		 */
    public function setup_wizard() {
      if ( empty( $_GET['page'] ) || $this->page_slug !== $_GET['page'] ) {
        return;
      }
      ob_end_clean();

      set_current_screen('wu-setup');

      $this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

      wp_register_script( 'wu-setup', $this->plugin_url . '/js/wpultimo-setup.js', array( 'jquery', 'jquery-blockui' ), $this->version );
      wp_localize_script( 'wu-setup', 'wu_setup_params', array(
        'tgm_plugin_nonce'            => array(
          'update' => wp_create_nonce( 'tgmpa-update' ),
          'install' => wp_create_nonce( 'tgmpa-install' ),
        ),
        'tgm_bulk_url' => admin_url( $this->tgmpa_url ),
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'wpnonce' => wp_create_nonce( 'wu_setup_nonce' ),
        'verify_text' => __( '...verifying','wp-ultimo'),
      ) );

      //wp_enqueue_style( 'envato_wizard_admin_styles', $this->plugin_url . '/css/admin.css', array(), $this->version );
      wp_enqueue_style( 'wpultimo-setup', $this->plugin_url . '/css/wpultimo-setup.css', array( 'dashicons', 'install' ), $this->version );

      //enqueue style for admin notices
      wp_enqueue_style( 'wp-admin' );

      wp_enqueue_media();
      wp_enqueue_script( 'media' );
      wp_enqueue_script( 'wu-setup' );
      wp_enqueue_script( 'wp-ultimo' );

      ob_start();
      $this->setup_wizard_header();
      $this->setup_wizard_steps();
      $show_content = true;
      echo '<div class="wpultimo-setup-content">';
      if ( ! empty( $_REQUEST['save_step'] ) && isset( $this->steps[ $this->step ]['handler'] ) ) {
        $show_content = call_user_func( $this->steps[ $this->step ]['handler'] );
      }
      if ( $show_content ) {
        $this->setup_wizard_content();
      }
      echo '</div>';
      $this->setup_wizard_footer();
      exit;
    }

    public function get_step_link( $step ) {
      return  add_query_arg( 'step', $step, admin_url( 'admin.php?page=' .$this->page_slug ) );
    }
    public function get_next_step_link() {
      $keys = array_keys( $this->steps );
      return add_query_arg( 'step', $keys[ array_search( $this->step, array_keys( $this->steps ) ) + 1 ], remove_query_arg( 'translation_updated' ) );
    }

    /**
		 * Setup Wizard Header
		 */
    public function setup_wizard_header() {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
  <head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php _e( 'WP Ultimo &rsaquo; Setup Wizard', 'wp-ultimo'); ?></title>
    <script type="text/javascript">
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var pagenow;
    </script>
    <?php do_action( 'admin_enqueue_scripts' ); ?>
    <?php do_action( 'admin_print_styles' ); ?>
    <?php do_action( 'admin_print_scripts' ); ?>
    <?php do_action( 'admin_head' ); ?>
  </head>
  <body class="wpultimo-setup wp-core-ui">
    <h1 id="wc-logo">
      <a href="https://wpultimo.com" target="_blank"><?php
                                           $image_url = WP_Ultimo()->get_asset('logo.png', 'img');
                                           if ( $image_url ) {
                                             $image = '<img class="site-logo" src="%s" alt="%s" style="width:%s; height:auto" />';
                                             printf(
                                               $image,
                                               $image_url,
                                               get_bloginfo( 'name' ),
                                               '200px'
                                             );
                                           } else { ?>
        <img src="<?php echo $this->plugin_url; ?>/images/logo.png" alt="Envato install wizard" /><?php
                                                  } ?></a>
    </h1>
    <?php
                                          }

    /**
		 * Setup Wizard Footer
		 */
    public function setup_wizard_footer() {
    ?>
    <?php if ( 'next_steps' === $this->step ) : ?>
    <a class="wc-return-to-dashboard" href="<?php echo esc_url( admin_url() ); ?>"><?php _e( 'Return to the WordPress Dashboard', 'wp-ultimo'); ?></a>
    <?php endif; ?>
  </body>
  <?php
      @do_action( 'admin_footer' ); // this was spitting out some errors in some admin templates. quick @ fix until I have time to find out what's causing errors.
      do_action( 'admin_print_footer_scripts' );
  ?>
</html>
<?php
    }

    /**
		 * Output the steps
		 */
    public function setup_wizard_steps() {
      $ouput_steps = $this->steps;
      array_shift( $ouput_steps );
?>
<ol class="wpultimo-setup-steps">
  <?php foreach ( $ouput_steps as $step_key => $step ) : ?>
  <li class="<?php
      $show_link = false;
      if ( $step_key === $this->step ) {
        echo 'active';
      } elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
        echo 'done';
        $show_link = true;
      }
             ?>"><?php
      if ( $show_link ) {
    ?>
    <a href="<?php echo esc_url( $this->get_step_link( $step_key ) );?>"><?php echo esc_html( $step['name'] );?></a>
    <?php
      } else {
        echo esc_html( $step['name'] );
      }
    ?></li>
  <?php endforeach; ?>
</ol>
<?php
    }

    /**
		 * Output the content for the current step
		 */
    public function setup_wizard_content() {
      isset( $this->steps[ $this->step ] ) ? call_user_func( $this->steps[ $this->step ]['view'] ) : false;
    }

    /**
		 * Introduction step
		 */
    public function wu_setup_introduction() { ?>
    <h1><?php _e('Welcome to the setup wizard for WP Ultimo!', 'wp-ultimo'); ?></h1>
    
    <p><?php _e('Thank you for choosing the WP Ultimo plugin. This quick setup wizard will help you configure your new network. This wizard will install default content and tell you a little about Help, Support &amp; Activation options. <strong>It should only take 5 minutes.</strong>', 'wp-ultimo'); ?></p>
    
    <p><?php _e('No time right now? If you don\'t want to go through the wizard, you can skip and return to the WordPress dashboard. Come back anytime if you change your mind! This Wizard is accessible via the Help Tabs in any of our plugin pages.', 'wp-ultimo'); ?></p>
    
    <p class="wpultimo-setup-actions step">
      <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>"
         class="button-primary button button-large button-next"><?php _e('Let\'s Go!', 'wp-ultimo'); ?></a>
      <a href="<?php echo esc_url( wp_get_referer() && ! strpos( wp_get_referer(),'update.php' ) ? wp_get_referer() : admin_url( '' ) ); ?>"
         class="button button-large"><?php _e('Not right now', 'wp-ultimo'); ?></a>
    </p>
    <?php
    
    } // end wu_setup_introduction;

    /**
     * Get settings
     * @return array get_settings
     */
    private function _get_settings_sections() {
      // Get all settings
      $allSettings = WU_Settings::get_sections();
      $network = array(
        'network_heading' => $allSettings['network']['fields']['network'],
        'enable_signup'   => $allSettings['network']['fields']['enable_signup'],
        'domain-mapping'  => $allSettings['domain_mapping']['fields']['enable_domain_mapping'],
        'active_gateway'  => $allSettings['gateways']['fields']['active_gateway'],
      );
      $settings = array_merge($network, $allSettings['general']['fields']);
      
      return $settings;

    } // end _get_settings_sections;

    /**
     * Display the settings step of this
     * @return array Array containing the pages
     */
    private function wu_setup_settings() { ?>
    <h1><?php _e('General Settings', 'wp-ultimo'); ?></h1>
    <form method="post">
      <p><?php _e( 'Here we can set the primary settings for of the plugin, mainly related to the currency settings and gateways.', 'wp-ultimo'); ?></p>
      
      <table class="wpultimo-setup-pages" cellspacing="0">
        <tbody>

          <?php foreach ( $this->_get_settings_sections() as $field_slug => $field ) :  ?>
          
          <?php 

          $table_open = false;

          /**
           * Heading fields
           */
          if ($field['type'] == 'heading') : ?>

            <?php if ($table_open) : ?>
            </tbody></table>
            <?php endif; ?>

            <!--<h3><?php echo $field['title']; ?></h3>
            <p><?php echo $field['desc']; ?></p>-->

            <table class="form-table">
              <tbody>

          <?php $table_open = true; ?>

          <?php 
          /**
           * Hidden fields
           */
          elseif ($field['type'] == 'hidden') : ?>
          
          <?php 
          /**
           * Heading fields
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
             * Multi Checkbox
             */
            elseif ($field['type'] == 'multi_checkbox') : ?>
                  
              <tr>
                <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?></th>
                <td>

                  <?php

                  // Check if it was selected
                  $settings = WU_Settings::get_setting($field_slug);

                  /**
                   * Loop the values
                   */
                  foreach ($field['options'] as $field_value => $field_name) : 

                    // Check this setting
                    $this_settings = isset($settings[$field_value]) ? $settings[$field_value] : false;

                    ?>
                  
                    <label for="multiselect-<?php echo $field_value; ?>">
                      <input <?php checked($this_settings); ?> name="<?php echo sprintf('%s[%s]', $field_slug, $field_value); ?>" type="checkbox" id="multiselect-<?php echo $field_value; ?>" value="1">
                      <?php echo $field_name; ?>
                    </label><br>

                  <?php endforeach; ?>

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
              <th scope="row"><label for="<?php echo $field_slug; ?>"><?php echo $field['title']; ?></label> <?php echo WU_Util::tooltip($field['tooltip']); ?></th>
              <td>
                <input name="<?php echo $field_slug; ?>" type="<?php echo $field['type']; ?>" id="<?php echo $field_slug; ?>" class="regular-text" value="<?php echo WU_Settings::get_setting($field_slug); ?>" placeholder="<?php echo isset($field['placeholder']) ? $field['placeholder'] : ''; ?>">

                <?php if (!empty($field['desc'])) : ?>
                <p class="description" id="<?php echo $field_slug; ?>-desc">
                  <?php echo $field['desc']; ?>       
                </p>
                <?php endif; ?>

              </td>
            </tr>
                
          <?php endif; endforeach; ?>

        </tbody>
      </table>

      <br>

      <p class="wpultimo-setup-actions step">
        <?php wp_nonce_field( 'wpultimo-setup' ); ?>
        <button type="submit" class="button-primary button button-large button-next" name="save_step"><?php _e( 'Save and Continue', 'wp-ultimo'); ?></button>
        <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'wp-ultimo'); ?></a>
      </p>
    </form>
    <?php } // end wu_setup_settings;

    /**
     * Display the extra steps for sunrise.php and domains
     */
    public function wu_setup_checks() { ?>

      <h1><?php _e('Custom Domain Support', 'wp-ultimo'); ?></h1>

      <p><?php _e('We use the incredible <strong>Mercator</strong> by humanmade to handle the domain mapping in WP Ultimo. This is all done automatically via a interface with Mercator. Mercator is bundled with WP Ultimo already, so you don\'t need to install it. There are, however, a few extra steps to be taken if you want to enable custom domain support.', 'wp-ultimo'); ?></p>

      <h3>1. <?php _e('Copying sunrise.php', 'wp-ultimo'); ?></h3>

      <p><?php _e('You need to copy the <code>sunrise.php</code> from the <code>wp-ultimo</code> directory to your <code>wp-content</code> directory.', 'wp-ultimo'); ?></p>

      <p><button class="wu-ajax-button button" data-restore="false" data-url="<?php echo admin_url('admin-ajax.php?action=wu_upgrade_sunrise'); ?>"><?php _e('Copy Automatically', 'wp-ultimo'); ?></button>
      </p>

      <h3>2. <?php _e('Setting SUNRISE to true', 'wp-ultimo'); ?></h3>

      <p><?php _e("Now we need to let WordPress know it has to load our new sunrise.php. This should be done by adding <code>define('SUNRISE', true);</code> to your <code>wp-config.php</code> file.", 'wp-ultimo'); ?></p>

      <p style="border-radius: 3px; border: solid 1px #ccc; padding: 10px; display: block;"><?php _e("<strong>IMPORTANT:</strong> Be sure to add the <code>define('SUNRISE', true);</code> code <strong>ABOVE</strong> the <code>/* That's all, stop editing! Happy blogging. */</code> line of your <code>wp-config.php</code> file.", 'wp-ultimo'); ?>

      <form method="post">
      <p class="wpultimo-setup-actions step">
        <?php wp_nonce_field( 'wpultimo-setup' ); ?>
        <button type="submit" class="button-primary button button-large button-next" name="save_step"><?php _e( 'Check Configuration', 'wp-ultimo'); ?></button>
        <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'wp-ultimo'); ?></a>
      </p>
      </form>

    <?php } // end wu_setup_checks;

    /**
     * Display the extra steps for sunrise.php and domains
     */
    public function wu_setup_checks_save() { 

      check_admin_referer('wpultimo-setup');

      $extra_tip_message = '';

      $file_exists  = file_exists(WP_CONTENT_DIR.'/sunrise.php');
      $class_exists = class_exists('\Mercator\Mapping');

      // Check for the sunrise.php file on the wp_content directory
      $file_exists_message = $file_exists ? '<span class="success">OK</span>' : '<span class="error">NOT FOUND</span>';

      /**
       * @since  1.3.0 Checks if Logs Directory Exists
       */
      $logs_exists   = is_dir(WU_Logger::get_logs_folder());
      $logs_writable = is_writable(WU_Logger::get_logs_folder());

      $logs_exists_msg = $logs_exists ? '<span class="success">OK</span>' : '<span class="error">NOT FOUND</span>';
      $logs_writable_msg = $logs_writable ? '<span class="success">OK</span>' : '<span class="error">NOT WRITABLE</span>';

      /**
       * @since  1.2.2 Check for the existence of the Class
       */

      
      // Check sunrise constant
      $constant_set = defined('SUNRISE') ? SUNRISE : false;
      $constant = $constant_set ? '<span class="success">OK</span>' : '<span class="error">NOT FOUND</span>';
      
      if ($file_exists && $constant_set) {

        $file_exists_message = $class_exists ? '<span class="success">OK</span>' : '<span class="error">'. __('The file exists but it seems to be a different version of sunrise.php. Consider replacing the current sunrise.php on wp-content with the file on your wp-ultimo directory. Disregard this message if the SUNRISE constant is not set yet (step below).', 'wp-ultimo') .'</span>';

        $extra_tip_message = $class_exists ? '' : '<p style="border-radius: 3px; border: solid 1px #ccc; padding: 10px; display: block;">' . sprintf(__('<strong>Common Mistake:</strong><br>If you place the <code>%1$s</code> line below the <code>%2$s</code> line on the <code>wp-config.php</code> file, the system wil not be able to load the domain mapping functionality. Please, make sure you place the <code>%1$s</code> line <strong>ABOVE</strong> the <code>%2$s</code> line.', 'wp-ultimo'), 'define(\'SUNRISE\', true);', '/* That\'s all, stop editing! Happy blogging. */') . '</p>';

      } // end if;

      // global check
      $continue = $constant_set && $file_exists && $class_exists && $logs_exists && $logs_writable;

      if ($continue) {

        $extra_tip_message = '<div class="wu-hosting-notice" style="border-radius: 3px; border: solid 1px #ccc; padding: 10px; display: block;"><strong>'. strtoupper(__('Small note on Hosting Support for Domain Mapping:', 'wp-ultimo')) .'</strong><br><br>' . WU_Domain_Mapping::get_hosting_support_text() . '</div>';

      } // end if;

      ?>

      <h1><?php _e('Checking System Setup...', 'wp-ultimo'); ?></h1>

      <p>
        <strong><?php printf(__('Directory %s exists: ', 'wp-ultimo'), "<code>".WU_Logger::get_logs_folder()."</code>"); ?></strong> <?php echo $logs_exists_msg; ?>
        <br>
        <strong><?php printf(__('Directory %s is Writable: ', 'wp-ultimo'), "<code>".WU_Logger::get_logs_folder()."</code>"); ?></strong> <?php echo $logs_writable_msg; ?>
      </p>

      <p>
        <strong><?php _e('Sunrise.php file on the wp-content directory: ', 'wp-ultimo'); ?></strong> <?php echo $file_exists_message; ?>
        <br>
        <strong><?php _e('Sunrise constant set to true: ', 'wp-ultimo'); ?></strong> <?php echo $constant; ?>
      </p>

      <?php echo $extra_tip_message; ?>

      <form method="post">
      <p class="wpultimo-setup-actions step">
        <?php wp_nonce_field( 'wpultimo-setup' ); ?>

        <?php if ($continue) : ?>

          <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-primary button-large button-large button-next"><?php _e( 'Continue', 'wp-ultimo'); ?></a>

        <?php else : ?>

          <button type="submit" class="button-primary button button-large button-next" name="save_step"><?php _e( 'Check Again', 'wp-ultimo'); ?></button>

          <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'wp-ultimo'); ?></a>

        <?php endif; ?>

      </p>
      </form>

    <?php } // end wu_setup_checks_save

    /**
     * Save logo & design options
     */
    public function wu_setup_settings_save() {

      check_admin_referer('wpultimo-setup');

      // First
      $first = true;

      // Get all the settings
      $settings = WU_Settings::get_settings();
      
      // The post passed by the form
      $post = $_POST;
      
      // Saves each setting
      $fields = $this->_get_settings_sections();
        
      // Loop fields
      foreach ($fields as $field_slug => $field) {
        
        // Skip headings
        if ($field['type'] == 'heading') continue;
        
        // Skip check-boxes if this is not the time
        if ($field['type'] == 'checkbox') $post[$field_slug] = isset($post[$field_slug]);
        
        // TODO: Validate
        if (isset($post[$field_slug]))
          $settings[$field_slug] = $post[$field_slug];
        else if ($first)
          $settings[$field_slug] = $field['default'];

        // Do action on field
        do_action('wu_save_setting', $field_slug, $field, $post);
        
      } // end foreach;
      
      // Re-save the settings
      WP_Ultimo()->saveOption('settings', $settings);
      
      /**
       * After the form
       */
      do_action('wu_after_save_settings');

      wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
      exit;

    }

    /**
     * Get the default content to be used, mostly our default Plans
     * @return array Array containing the pages
     */
    private function _content_default_get() {

      $content = array();

      // Template Sites
      $content['template-site'] = array(
        'title'            => __('Template Site', 'wp-ultimo'),
        'description'      => __('Create a simple site on your network, that you can use to create a template for future sign-ups. You can set a different template per plan, but you can also let your users select which template they want to use in the signup. If you already have a subsite that you would like to use as a template, you can uncheck this item.', 'wp-ultimo') .' '. sprintf('<a href="%s" target="_blank">%s</a>', WU_Links()->get_link('site-templates'), __('Read more about Site Templates', 'wp-ultimo')),
        'pending'          => __('Pending.', 'wp-ultimo'),
        'installing'       => __('Creating default template site.', 'wp-ultimo'),
        'success'          => __('Success.', 'wp-ultimo'),
        'install_callback' => array( $this,'_content_install_template_site'),
      );

      // Only display this if there's no plans
      if (count(WU_Plans::get_plans()) == 0) {

        // Pages
        $content['plans'] = array(
          'title'            => __('Plans', 'wp-ultimo'),
          'description'      => __('Import some default plans to have something to work with in the beginning. Basic, Medium and Premium.', 'wp-ultimo'),
          'pending'          => __('Pending.', 'wp-ultimo'),
          'installing'       => __('Creating default Plans.', 'wp-ultimo'),
          'success'          => __('Success.', 'wp-ultimo'),
          'install_callback' => array( $this,'_content_install_plans'),
        );

      } // end if;

      return $content;

    } // end _content_default_get;

    /**
		 * Page setup
		 */
    public function wu_setup_default_content() { ?>
    <h1><?php _e( 'Default Content', 'wp-ultimo'); ?></h1>
    <form method="post">
      <p><?php printf( __( 'It\'s time to insert some default content for your new WordPress Network website. Choose what you would like inserted below and click Continue.', 'wp-ultimo'), '<a href="' . esc_url( admin_url( 'edit.php?post_type=page' ) ) . '" target="_blank">', '</a>' ); ?></p>
      <table class="wpultimo-setup-pages" cellspacing="0">
        <thead>
          <tr>
            <td class="check"> </td>
            <th class="item"><?php _e( 'Item', 'wp-ultimo'); ?></th>
            <th class="description"><?php _e( 'Description', 'wp-ultimo'); ?></th>
            <th class="status"><?php _e( 'Status', 'wp-ultimo'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $this->_content_default_get() as $slug => $default ) {  ?>
          <tr class="envato_default_content" data-content="<?php echo esc_attr( $slug );?>">
            <td>
              <input type="checkbox" name="default_content[pages]" class="envato_default_content" id="default_content_<?php echo esc_attr( $slug );?>" value="1" checked>
            </td>
            <td><label for="default_content_<?php echo esc_attr( $slug );?>"><?php echo $default['title']; ?></label></td>
            <td class="description"><?php echo $default['description']; ?></td>
            <td class="status"> <span><?php echo $default['pending'];?></span> <div class="spinner"></div></td>
          </tr>
          <?php } ?>
        </tbody>
      </table>

      <br>
      <p><?php _e( 'Once inserted, this content can be managed from the WordPress admin dashboard.', 'wp-ultimo'); ?></p>

      <p class="wpultimo-setup-actions step">
        <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button-primary button button-large button-next" data-callback="install_content"><?php _e( 'Continue', 'wp-ultimo'); ?></a>
        <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'wp-ultimo'); ?></a>
        <?php wp_nonce_field( 'wpultimo-setup' ); ?>
      </p>
    </form>
    <?php }


    public function ajax_content() {
      $content = $this->_content_default_get();

      if ( ! check_ajax_referer( 'wu_setup_nonce', 'wpnonce' ) || empty( $_POST['content'] ) && isset( $content[ $_POST['content'] ] ) ) {
        wp_send_json_error( array( 'error' => 1, 'message' => __( 'No content Found','wp-ultimo') ) );
      }

      $json = false;
      $this_content = $content[ $_POST['content'] ];

      if ( isset( $_POST['proceed'] ) ) {
        // install the content!

        if ( ! empty( $this_content['install_callback'] ) ) {
          if ( $result = call_user_func( $this_content['install_callback'] ) ) {
            $json = array(
              'done' => 1,
              'message' => $this_content['success'],
              'debug' => $result,
            );
          }
        }
      } else {

        $json = array(
          'url' => admin_url( 'admin-ajax.php' ),
          'action' => 'wu_setup_content',
          'proceed' => 'true',
          'content' => $_POST['content'],
          '_wpnonce' => wp_create_nonce( 'wu_setup_nonce' ),
          'message' => $this_content['installing'],
        );
      }

      if ( $json ) {
        $json['hash'] = md5( serialize( $json ) ); // used for checking if duplicates happen, move to next plugin
        wp_send_json( $json );
      } else {
        wp_send_json( array( 'error' => 1, 'message' => __( 'Error','wp-ultimo') ) );
      }

      exit;

    }

    private function _import_wordpress_xml_file( $xml_file_path ) {
      global $wpdb;

      if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) { define( 'WP_LOAD_IMPORTERS', true ); }

      // Load Importer API
      require_once ABSPATH . 'wp-admin/includes/import.php';

      if ( ! class_exists( 'WP_Importer' ) ) {
        $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
        if ( file_exists( $class_wp_importer ) ) {
          require $class_wp_importer;
        }
      }

      if ( ! class_exists( 'WP_Import' ) ) {
        $class_wp_importer = __DIR__ .'/importer/wordpress-importer.php';
        if ( file_exists( $class_wp_importer ) ) {
          require $class_wp_importer; }
      }

      if ( class_exists( 'WP_Import' ) ) {
        require_once __DIR__ .'/importer/wpultimo-content-import.php';
        $wp_import = new wu_content_import();
        $wp_import->fetch_attachments = true;
        ob_start();
        $wp_import->import( $xml_file_path );
        $message = ob_get_clean();
        return array( $wp_import->check(),$message );
      }
      return false;
    }

    /**
     * Import the plans, if the user chooses to do so
     * @return boolean If the importing was successful;
     */
    private function _content_install_plans() {
      return $this->_import_wordpress_xml_file( __DIR__ .'/content/plans.xml');
    }

    /**
     * Create the new template blog
     * @return boolean If the creation was successful;
     */
    private function _content_install_template_site() {

      global $current_site;

      // Set the default domain
      $domain     = '';
      $site_slug  = "template";
      $site_title = __("Template Site", 'wp-ultimo');
      $user_id    = get_current_user_id();
    
      if (preg_match('|^([a-zA-Z0-9-])+$|', $site_slug))
        $domain = strtolower($site_slug);

      if (is_subdomain_install()) {
        $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $current_site->domain);
        $path      = $current_site->path;
      } else {
        $newdomain = $current_site->domain;
        $path      = $current_site->path . $domain . '/';
      }

      // Use the default
      $site_id = wpmu_create_blog($newdomain, $path, $site_title, $user_id, get_current_site()->id);

      return true;

    }
    
    private function _get_json( $file ) {
      if ( is_file( __DIR__.'/content/'.basename( $file ) ) ) {
        WP_Filesystem();
        global $wp_filesystem;
        $file_name = __DIR__ . '/content/' . basename( $file );
        if ( file_exists( $file_name ) ) {
          return json_decode( $wp_filesystem->get_contents( $file_name ), true );
        }
      }
      return array();
    }
    /**
		 * Logo & Design
		 */
    public function wu_setup_logo_design() { ?>
    <h1><?php _e('Logo', 'wp-ultimo'); ?></h1>

    <form method="post">
      <p><?php _e('Upload the Logo image we are going to display on the Login and Sign up pages. <br>The optimal size is 320x80px.', 'wp-ultimo'); ?></p>

      
        <div id="current-logo">

          <?php $image_url = WU_Settings::get_logo(); 

            if ( $image_url ) {
              $image = '<img class="site-logo" src="%s" alt="%s" style="width:%s; height:auto">';
              printf(
                $image,
                $image_url,
                get_bloginfo('name'),
                '400px'
              );
              
            } ?>

            <br>

            <a href="#" class="button button-upload">
              <?php _e('Upload New Logo', 'wp-ultimo'); ?>
            </a>

        </div>

      <p><?php // _e( 'Please choose the color scheme for this website. The color scheme (along with font colors &amp; styles) can be changed at any time from the Appearance > Customize area in your dashboard.' ,'wp-ultimo'); ?></p>


      <input type="hidden" name="new_logo_id" id="new_logo_id" value="">
      <input type="hidden" name="new_style" id="new_style" value="">

      <p class="wpultimo-setup-actions step">
        <input type="submit" class="button-primary button button-large button-next" value="<?php esc_attr_e( 'Save and Continue', 'wp-ultimo'); ?>" name="save_step" />
        <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-large button-next"><?php _e( 'Skip this step', 'wp-ultimo'); ?></a>
        <?php wp_nonce_field( 'wpultimo-setup' ); ?>
      </p>
    </form>

    <?php } // end wu_setup_logo_design;

    /**
		 * Save logo & design options
		 */
    public function wu_setup_logo_design_save() {
      check_admin_referer( 'wpultimo-setup' );

      $new_logo_id = (int) $_POST['new_logo_id'];
      // save this new logo url into the database and calculate the desired height based off the logo width.
      // copied from dtbaker.theme_options.php
      if ($new_logo_id) {
        // Save
        $settings = WU_Settings::get_settings();
        $settings['logo'] = $new_logo_id;
        
        // Re-save the settings
        WP_Ultimo()->saveOption('settings', $settings);
      }

      wp_redirect( esc_url_raw( $this->get_next_step_link() ) );
      exit;
    }

    /**
     * This displays the help and activation step
     */
    public function wu_setup_help_support() { ?>
    <h1><?php _e('Activation and Support', 'wp-ultimo'); ?></h1>

    <p><?php _e('This plugin comes with support for issues you may have. Item Support can be requested via email on <a href="mailto:support@wpultimo.com" target="_blank">support (at) wpultimo.com</a> and includes:', 'wp-ultimo'); ?></p>
    
    <ul class="support-available">
      <li><?php _e('Availability of the author to answer questions', 'wp-ultimo'); ?></li>
      <li><?php _e('Answering technical questions about item features', 'wp-ultimo'); ?></li>
      <li><?php _e('Assistance with reported bugs and issues', 'wp-ultimo'); ?></li>
    </ul>

    <br>
    <p><?php _e('Item Support <strong>DOES NOT</strong> Include:', 'wp-ultimo'); ?></p>

    <ul class="support-unavailable">
      <li><?php _e('Customization services', 'wp-ultimo'); ?></li>
      <li><?php _e('Installation services', 'wp-ultimo'); ?></li>
      <li><?php _e('Help and Support for 3rd party plugins (i.e. plugins you install yourself later on)', 'wp-ultimo'); ?></li>
    </ul>

    <br>

    <p class="wpultimo-setup-actions step">
      <a href="<?php echo esc_url( $this->get_next_step_link() ); ?>" class="button button-primary button-large button-next"><?php _e( 'Agree and Continue', 'wp-ultimo'); ?></a>
      <?php wp_nonce_field( 'wpultimo-setup' ); ?>
    </p>
<?php
                                                }

    /**
		 * Final step
		 */
    public function wu_setup_ready() {
?>
<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://wpultimo.com" data-text="<?php echo esc_attr('I just created my own premium WordPress site network with #wpultimo'); ?>" data-via="arindoduque" data-size="large">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

<h1><?php _e( 'Your Network is Ready!', 'wp-ultimo'); ?></h1>

<p><?php _e( 'Congratulations! The plugin has been activated and your network is ready. Go back to your WordPress dashboard to make changes and modify any of the default content to suit your needs.', 'wp-ultimo'); ?></p>

<p>You can see some of our other premium plugins visiting <a href="http://nextpress.co" target="_blank">our portfolio.</a> <br/>Follow <a  href="https://twitter.com/arindoduque" target="_blank">@arindoduque</a> on Twitter to see updates and news. Thanks! </p>

<div class="wpultimo-setup-next-steps">
  <div class="wpultimo-setup-next-steps-first">
    <h2><?php _e( 'Next Steps', 'wp-ultimo'); ?></h2>
    <ul>
      <li class="setup-product"><a class="button button-primary button-large" href="https://twitter.com/arindoduque" target="_blank"><?php _e( 'Follow @arindoduque on Twitter', 'wp-ultimo'); ?></a></li>
      <li class="setup-product"><a class="button button-next button-large" href="<?php echo esc_url( network_admin_url() ); ?>"><?php _e( 'Go to the Dashboard!', 'wp-ultimo'); ?></a></li>
    </ul>
  </div>
  <div class="wpultimo-setup-next-steps-last">
    <h2><?php _e( 'More Resources', 'wp-ultimo'); ?></h2>
    <ul>
      <!--<li class="documentation"><a href="http://dtbaker.net/envato/documentation/" target="_blank"><?php _e( 'Read the Theme Documentation', 'wp-ultimo'); ?></a></li>-->
      <li class="howto"><a href="https://wordpress.org/support/" target="_blank"><?php _e( 'Learn how to use WordPress', 'wp-ultimo'); ?></a></li>

      <li class="support"><a href="mailto:support@wpultimo.com" target="_blank"><?php _e( 'Get Help and Support', 'wp-ultimo'); ?></a></li>
    </ul>
  </div>
</div>
<?php
                                         }

    public function envato_market_admin_init() {

      if(!function_exists('envato_market'))return;

      global $wp_settings_sections;
      if ( ! isset( $wp_settings_sections[ envato_market()->get_slug() ] ) ) {
        // means we're running the admin_init hook before envato market gets to setup settings area.
        // good - this means our oauth prompt will appear first in the list of settings blocks
        register_setting( envato_market()->get_slug(), envato_market()->get_option_name() );
      }

      // pull our custom options across to envato.
      $option = get_option( 'wu_setup_wizard' , array() );
      $envato_options = envato_market()->get_options();
      $envato_options = $this->_array_merge_recursive_distinct($envato_options, $option);
      update_option( envato_market()->get_option_name(), $envato_options );

      //add_thickbox();

      if ( ! empty( $_POST['oauth_session'] ) && ! empty( $_POST['bounce_nonce'] ) && wp_verify_nonce( $_POST['bounce_nonce'], 'envato_oauth_bounce_' . $this->envato_username ) ) {
        // request the token from our bounce url.
        $my_theme = wp_get_theme();
        $oauth_nonce = get_option( 'envato_oauth_'.$this->envato_username );
        if ( ! $oauth_nonce ) {
          // this is our 'private key' that is used to request a token from our api bounce server.
          // only hosts with this key are allowed to request a token and a refresh token
          // the first time this key is used, it is set and locked on the server.
          $oauth_nonce = wp_create_nonce( 'envato_oauth_nonce_' . $this->envato_username );
          update_option( 'envato_oauth_'.$this->envato_username, $oauth_nonce );
        }
        $response = wp_remote_post( $this->oauth_script, array(
          'method' => 'POST',
          'timeout' => 15,
          'redirection' => 1,
          'httpversion' => '1.0',
          'blocking' => true,
          'headers' => array(),
          'body' => array(
            'oauth_session' => $_POST['oauth_session'],
            'oauth_nonce' => $oauth_nonce,
            'get_token' => 'yes',
            'url' => home_url(),
            'theme' => $my_theme->get( 'Name' ),
            'version' => $my_theme->get( 'Version' ),
          ),
          'cookies' => array(),
        )
                                  );
        if ( is_wp_error( $response ) ) {
          $error_message = $response->get_error_message();
          $class = 'error';
          echo "<div class=\"$class\"><p>".sprintf( __( 'Something went wrong while trying to retrieve oauth token: %s','wp-ultimo'), $error_message ).'</p></div>';
        } else {
          $token = @json_decode( wp_remote_retrieve_body( $response ), true );
          $result = false;
          if ( is_array( $token ) && ! empty( $token['access_token'] ) ) {
            $token['oauth_session'] = $_POST['oauth_session'];
            $result = $this->_manage_oauth_token( $token );
          }
          if ( $result !== true ) {
            echo 'Failed to get oAuth token. Please go back and try again';
            exit;
          }
        }
      }

      add_settings_section(
        envato_market()->get_option_name() . '_' . $this->envato_username  . '_oauth_login',
        sprintf( __( 'Login for %s updates', 'wp-ultimo'), $this->envato_username ),
        array( $this, 'render_oauth_login_description_callback' ),
        envato_market()->get_slug()
      );
      // Items setting.
      add_settings_field(
        $this->envato_username  . 'oauth_keys',
        __( 'oAuth Login', 'wp-ultimo'),
        array( $this, 'render_oauth_login_fields_callback' ),
        envato_market()->get_slug(),
        envato_market()->get_option_name() . '_' . $this->envato_username  . '_oauth_login'
      );
    }

    private static $_current_manage_token = false;

    private function _manage_oauth_token( $token ) {
      if ( is_array( $token ) && ! empty( $token['access_token'] ) ) {
        if ( self::$_current_manage_token == $token['access_token'] ) {
          return false; // stop loops when refresh auth fails.
        }
        self::$_current_manage_token = $token['access_token'];
        // yes! we have an access token. store this in our options so we can get a list of items using it.
        $option = get_option( 'wu_setup_wizard', array() );
        if ( ! is_array( $option ) ) {
          $option = array();
        }
        if ( empty( $option['items'] ) ) {
          $option['items'] = array();
        }
        // check if token is expired.
        if ( empty( $token['expires'] ) ) {
          $token['expires'] = time() + 3600;
        }
        if ( $token['expires'] < time() + 120 && ! empty( $token['oauth_session'] ) ) {
          // time to renew this token!
          $my_theme = wp_get_theme();
          $oauth_nonce = get_option( 'envato_oauth_'.$this->envato_username );
          $response = wp_remote_post( $this->oauth_script, array(
            'method' => 'POST',
            'timeout' => 10,
            'redirection' => 1,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => array(
              'oauth_session' => $token['oauth_session'],
              'oauth_nonce' => $oauth_nonce,
              'refresh_token' => 'yes',
              'url' => home_url(),
              'theme' => $my_theme->get( 'Name' ),
              'version' => $my_theme->get( 'Version' ),
            ),
            'cookies' => array(),
          )
                                    );
          if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong while trying to retrieve oauth token: $error_message";
          } else {
            $new_token = @json_decode( wp_remote_retrieve_body( $response ), true );
            $result = false;
            if ( is_array( $new_token ) && ! empty( $new_token['new_token'] ) ) {
              $token['access_token'] = $new_token['new_token'];
              $token['expires'] = time() + 3600;
            }
          }
        }
        // use this token to get a list of purchased items
        // add this to our items array.
        $response = envato_market()->api()->request( 'https://api.envato.com/v3/market/buyer/purchases', array(
          'headers' => array(
            'Authorization' => 'Bearer ' . $token['access_token'],
          ),
        ) );
        self::$_current_manage_token = false;
        if ( is_array( $response ) && is_array( $response['purchases'] ) ) {
          // up to here, add to items array
          foreach ( $response['purchases'] as $purchase ) {
            // check if this item already exists in the items array.
            $exists = false;
            foreach ( $option['items'] as $id => $item ) {
              if ( ! empty( $item['id'] ) && $item['id'] == $purchase['item']['id'] ) {
                $exists = true;
                // update token.
                $option['items'][ $id ]['token'] = $token['access_token'];
                $option['items'][ $id ]['token_data'] = $token;
                $option['items'][ $id ]['oauth'] = $this->envato_username;
                if ( ! empty( $purchase['code'] ) ) {
                  $option['items'][ $id ]['purchase_code'] = $purchase['code'];
                }
              }
            }
            if ( ! $exists ) {
              $option['items'][] = array(
                'id' => '' . $purchase['item']['id'], // item id needs to be a string for market download to work correctly.
                'name' => $purchase['item']['name'],
                'token' => $token['access_token'],
                'token_data' => $token,
                'oauth' => $this->envato_username,
                'type' => ! empty( $purchase['item']['wordpress_theme_metadata'] ) ? 'theme' : 'plugin',
                'purchase_code' => ! empty( $purchase['code'] ) ? $purchase['code'] : '',
              );
            }
          }
        } else {
          return false;
        }
        if ( ! isset( $option['oauth'] ) ) {
          $option['oauth'] = array();
        }
        // store our 1 hour long token here. we can refresh this token when it comes time to use it again (i.e. during an update)
        $option['oauth'][ $this->envato_username ] = $token;
        update_option( 'wu_setup_wizard', $option );

        $envato_options = envato_market()->get_options();
        $envato_options = $this->_array_merge_recursive_distinct($envato_options, $option);
        update_option( envato_market()->get_option_name(), $envato_options );
        envato_market()->items()->set_themes( true );
        envato_market()->items()->set_plugins( true );
        return true;
      } else {
        return false;
      }
    }

    /**
		 * @param $array1
		 * @param $array2
		 *
		 * @return mixed
		 *
		 *
		 * @since    1.1.4
		 */
    private function _array_merge_recursive_distinct( $array1, $array2 ) {
      $merged = $array1;
      foreach ( $array2 as $key => &$value ) {
        if ( is_array( $value ) && isset ( $merged [ $key ] ) && is_array( $merged [ $key ] ) ) {
          $merged [ $key ] = $this->_array_merge_recursive_distinct( $merged [ $key ], $value );
        } else {
          $merged [ $key ] = $value;
        }
      }
      return $merged;
    }

    /**
		 * @param $args
		 * @param $url
		 * @return mixed
		 *
		 * Filter the WordPress HTTP call args.
		 * We do this to find any queries that are using an expired token from an oAuth bounce login.
		 * Since these oAuth tokens only last 1 hour we have to hit up our server again for a refresh of that token before using it on the Envato API.
		 * Hacky, but only way to do it.
		 */
    public function envato_market_http_request_args( $args, $url ) {
      if ( strpos( $url,'api.envato.com' ) && function_exists( 'envato_market' ) ) {
        // we have an API request.
        // check if it's using an expired token.
        if ( ! empty( $args['headers']['Authorization'] ) ) {
          $token = str_replace( 'Bearer ','',$args['headers']['Authorization'] );
          if ( $token ) {
            // check our options for a list of active oauth tokens and see if one matches, for this envato username.
            $option = envato_market()->get_options();
            if ( $option && ! empty( $option['oauth'][ $this->envato_username ] ) && $option['oauth'][ $this->envato_username ]['access_token'] == $token && $option['oauth'][ $this->envato_username ]['expires'] < time() + 120 ) {
              // we've found an expired token for this oauth user!
              // time to hit up our bounce server for a refresh of this token and update associated data.
              $this->_manage_oauth_token( $option['oauth'][ $this->envato_username ] );
              $updated_option = envato_market()->get_options();
              if ( $updated_option && ! empty( $updated_option['oauth'][ $this->envato_username ]['access_token'] ) ) {
                // hopefully this means we have an updated access token to deal with.
                $args['headers']['Authorization'] = 'Bearer '.$updated_option['oauth'][ $this->envato_username ]['access_token'];
              }
            }
          }
        }
      }
      return $args;
    }
    public function render_oauth_login_description_callback() {
      echo 'If you have purchased items from ' . esc_html($this->envato_username).' on ThemeForest or CodeCanyon please login here for quick and easy updates.';

    }

    public function render_oauth_login_fields_callback() {
      $option = envato_market()->get_options();
?>
<div class="oauth-login" data-username="<?php echo esc_attr( $this->envato_username ); ?>">
  <a href="<?php echo esc_url( $this->get_oauth_login_url( admin_url( 'admin.php?page=' . envato_market()->get_slug() . '#settings' ) ) ); ?>"
     class="oauth-login-button button button-primary">Login with Envato to activate updates</a>
</div>
<?php
    }

    /// a better filter would be on the post-option get filter for the items array.
    // we can update the token there.

    public function get_oauth_login_url( $return ) {
      return $this->oauth_script . '?bounce_nonce=' . wp_create_nonce( 'envato_oauth_bounce_' . $this->envato_username ) . '&wp_return=' . urlencode( $return );
    }

    /**
		 * Helper function
		 * Take a path and return it clean
		 *
		 * @param string $path
		 *
		 * @since    1.1.2
		 */
    public static function cleanFilePath( $path ) {
      $path = str_replace( '', '', str_replace( array( "\\", "\\\\" ), '/', $path ) );
      if ( $path[ strlen( $path ) - 1 ] === '/' ) {
        $path = rtrim( $path, '/' );
      }
      return $path;
    }

    public function is_submenu_page(){
      return ( $this->parent_slug == '' ) ? false : true;
    }
  }

} // if !class_exists

/**
 * Loads the main instance of WU_Setup_Wizard to have
 * ability extend class functionality
 *
 * @since 1.1.1
 * @return object WU_Setup_Wizard
 */
add_action('init', 'WU_Setup_Wizard', 10);

if (!function_exists('WU_Setup_Wizard')) :
function WU_Setup_Wizard() {
  WU_Setup_Wizard::get_instance();
}
endif;