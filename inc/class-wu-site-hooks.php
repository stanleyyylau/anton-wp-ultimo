<?php
/**
 * WP Ultimo Site Hooks
 *
 * Handles the site in our network
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Site_Hooks
 * @version     0.0.1
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Site_Hooks {

  public $new_site_hook;

  /**
   * Initiate the class and install hooks
   * @since  1.1.3 Also deletes subscriptions when a user is deleted
   * @private
   */
  public function __construct() {

    // Add the custom Plan column
    add_filter('wpmu_blogs_columns', array($this, 'add_plan_column'));

    add_action('manage_sites_custom_column', array($this, 'display_plan_column'), 10, 2);

    $delete_blog_action = 'delete_blog';
    if (version_compare(get_bloginfo('version'), '5.1.0', '>=')) {
      $delete_blog_action = 'wp_uninitialize_site';
    }

    // On delete site, also deletes entry on wu_sites
    add_action($delete_blog_action, array($this, 'on_delete_blog'));

    add_action('wp_update_site', function($new_site, $old_site) {

      global $wpdb;

      if (!$old_site->deleted && $new_site->deleted) {

        // Get table name
        $table_name = WU_Site_Owner::get_table_name();

        // Remove the entry from our table
        $wpdb->delete($table_name, array(
          'site_id' => $new_site->id,
        ));

      } // end if;

    }, 10, 2);

    // Clears site template caches on delete blog
    add_action($delete_blog_action, array($this, 'clear_available_site_template_cache'));
    add_action('wu_duplicate_site', array($this, 'clear_available_site_template_cache'));

    // Delets subscription on user deletion
    add_action('wpmu_delete_user', array($this, 'on_delete_user'));

    // Add some custom fields to the new Site Creation screen
    add_action('network_site_new_form', array($this, 'new_site_form'));

    // @since 1.1.1 replaces the whole save new site action
    // @since 1.2.0 replaces the save site on the my-site on each individual site as well
    add_action('admin_action_add-site', array($this, 'replace_save_site_action'));

    // Use the information passed on the form to the creation of the new Blog
    add_action('wp_ultimo_after_site_creation', array($this, 'new_site_form_save'));

    // Changing in WordPress settings
    add_action('admin_init', array($this, 'rewrite_ultimo_settings'));

    // Refresh sites upload quotas on change plan
    add_action('wu_subscription_change_plan', array($this, 'refresh_upload_quota_on_change_plan'), 10, 2);
    add_action('wu_after_switch_template', array($this, 'refresh_disk_space'), 10);

    // Add form to edit sites, to add a site to a user and plan
    add_action('wpmueditblogaction', array($this, 'add_owner'), 2000);
    add_action('wpmu_update_blog_options', array($this, 'save_add_owner'));

    // Add plan fields to the user form
    add_action('edit_user_profile', array($this, 'add_plan'));
    add_action('edit_user_profile_update', array($this, 'save_add_plan'));

    // Add custom columns to user
    add_filter('wpmu_users_columns', array($this, 'add_user_plan_column'));
    add_filter('manage_users_custom_column', array($this, 'add_user_plan_column_content'), 10, 3);

    /**
     * @since  1.0.4 serve terms of service
     */
    add_action('wp_ajax_nopriv_wu-terms', array($this, 'serve_terms_of_services'));
    add_action('wp_ajax_wu-terms', array($this, 'serve_terms_of_services'));

    /**
     * @since  1.1.0 select custom template image for site - Deprecated
     */
    // add_action('wp_ultimo_site_settings', array($this, 'select_custom_template_image'));
    // add_action('wpmu_update_blog_options', array($this, 'select_custom_template_image_save'));

    /**
     * @since  1.2.0 Replaces the add site link to our custom add site page, which mimics the network one
     */
    add_action('current_screen', array($this, 'new_site_changes'));

    add_action('admin_menu', array($this, 'new_site_menu'));
    add_action('user_admin_menu', array($this, 'new_site_menu'));

    add_filter('get_blogs_of_user', array($this, 'remove_main_site_from_my_sites'), 10, 2);

    /**
     * Replace WP Die
     * @since 1.2.1
     */
    add_filter('wp_die_handler', array($this, 'replace_wp_die_function'), 10, 3);

    // Adds the template previews
    add_action('init', array($this, 'template_previewer'));

    /**
     * Switching Teamplates
     * @since 1.6.0
     */
    add_action('admin_menu', array($this, 'add_switching_templates_page'));

    add_action('admin_init', array($this, 'handle_template_switching'));

    /**
     * Adds a listing for template sites only
     * @since 1.6.0
     */
    add_action('in_admin_header', array($this, 'add_site_template_separator'));

    add_action('network_admin_menu', array($this, 'add_site_template_menu_item'), 0);

    add_filter('ms_sites_list_table_query_args', array($this, 'filter_site_query'));

    /**
     * Prevents Search Engines from indexing Site Templates
     * @since 1.6.0
     */
    add_action('wp_head', array($this, 'prevent_site_template_indexing'), 20);

    /**
     * Filter AffiliateWP Tables from copying a template site to prevent fatal error on registration
     * @since 1.6.2
     */
    add_filter('wu_filter_tables_to_copy', array($this, 'filter_affiliatewp_tables'));

    /**
     * Adds a basic search and replace functionality
     */
    add_filter('mucd_string_to_replace', array($this, 'search_and_replace_on_duplication'), 10, 3);

    /**
     * Allow users to copy their own sites when creating a new site
     * @since 1.7.4
     */
    add_filter('wu_prepare_site_templates', array($this, 'add_own_sites_to_template_options'), 10, 3);

    /**
     * Clean users tables for sites
     * @since 1.9.0
     */
    add_filter('users_list_table_query_args', array($this, 'clean_user_tables_from_roleless_users'));

    /**
     * Adds extra headers for template previews
     * @since 1.9.1
     */
    add_action('send_headers', array($this, 'add_template_preview_headers'));

    /**
     * Allow super admins to remove transient bloat if they need to
     * @since 1.9.3
     */
    add_action('wp_ajax_wu_remove_transients', array($this, 'remove_signup_transients'));

    /**
     * Notificate if no-index is active for site templates.
     * @since 1.9.9
     */
    add_action('admin_init', array($this, 'add_no_index_warning'));

    /**
     * Makes Elementor re-generate CSS
     */
    add_action('wu_duplicate_site', array($this, 'elementor_regenerate_css'));

    /**
     * Add the user caps need for the site duplication.
     */
    add_action('wu_add_element_permissions', array($this, 'add_element_permissions'));

  } // end construct;

  /**
   * Add the user caps need for the site duplication.
   *
   * @since 1.10
   *
   * @return void
   */
  public function add_element_permissions() {

    add_filter( 'user_has_cap', function($allcaps, $caps, $args) {

      $allcaps['edit_post'] = 1;
      $allcaps['edit_others_posts'] = 1;
      $allcaps['edit_published_posts'] = 1;

      return $allcaps;

    }, 10, 3 );

  } // end add_element_permissions;

  /**
   * Makes sure we force elementor to regenerate the styles when necessary.
   *
   * @param array $site Info about the duplicated site.
   * @return void
   * @since 1.10.10
   */
  public function elementor_regenerate_css($site) {

    if (WU_Settings::get_setting('copy_media', true) || !class_exists('\Elementor\Plugin')) {

      return;

    } // end if;

    if (!isset($site['site_id'])) {

      return;

    } // end if;

    switch_to_blog($site['site_id']);

      Elementor\Plugin::$instance->files_manager->clear_cache();

    restore_current_blog();

  } // end elementor_regenerate_css;

  /**
   * Notificate if the no-index setting is active
   *
   * @since 1.9.8
   * @return void
   */
  public function add_no_index_warning() {

    if (WU_Settings::get_setting('stop_template_indexing')) {

      add_meta_box('wu-warnings', __('WP Ultimo - Notifications', 'wp-ultimo'), function() { // phpcs:disable ?>

        <div class="wu-warning">
          <p><?php _e('Your WP Ultimo settings are configured to <strong>prevent search engines such as Google from indexing your template sites</strong>. This can have inadvertent effects on other sites on the network and should only be used if you are sure about what you are doing.', 'wp-ultimo'); ?></p>

          <p><?php printf(__('If you are experiencing negative SEO impacts on other sites in your network, consider disabling this setting <a href="%s">here</a>.', 'wp-ultimo'), network_admin_url('admin.php?page=wp-ultimo&wu-tab=network#stop_template_indexing')); ?></p>
        </div>

      <?php }, 'dashboard-network', 'normal', 'high'); // phpcs:enable

    } // end if;

  } // end add_no_index_warning;

  /**
   * Allow super admins to remove transient bloat if they need to
   *
   * @since 1.9.3
   * @return void
   */
  public function remove_signup_transients() {

    global $wpdb;

    if (current_user_can('manage_network') && wp_verify_nonce($_REQUEST['_wpnonce'], 'wu_remove_transients')) {

      $table_name = apply_filters('wu_core_get_table_prefix', $wpdb->base_prefix) . 'sitemeta';

      $count  = (int) $wpdb->query("DELETE FROM $table_name WHERE meta_key LIKE ('_site_transient_timeout_wu_signup_%')");
      $count += (int) $wpdb->query("DELETE FROM $table_name WHERE meta_key LIKE ('_site_transient_wu_signup_%')");
      $count += (int) $wpdb->query("DELETE FROM $table_name WHERE meta_key LIKE ('wu_%')");

      wp_send_json(array(
        'message' => sprintf(__('%s row(s) removed successfuly from the transients table.', 'wp-ultimo'), $count),
        'status'  => true,
      ));

      die;

    } // end if;

    wp_send_json(array(
      'message' => __('An error occurred.', 'wp-ultimo'),
      'status'  => false,
    ));

    die;

  } // end remove_signup_transients;

  /**
   * Prevents the CORS problem on template previews
   *
   * @since 1.9.4
   * @return void
   */
  public function set_cors_domain_for_template_preview() {

    global $current_site;

    $domain = str_replace('www.', '', $current_site->domain);

    echo "<script type='text/javascript'>
      if (self !== top) {
        document.domain = '$domain';
      } // end if;
    </script>";

  } // end set_cors_domain_for_template_preview;

  /**
   * Prevents the SAMEORIGIN problem on template previews
   *
   * @since 1.9.1
   * @return void
   */
  public function add_template_preview_headers() {

    if (isset($_REQUEST['is_wu_template_preview'])) {

      /**
       * Allow all domains
       */
      header("X-Frame-Options: ALLOWALL");

      wp_enqueue_script('jquery');

      /**
       * Prevent CORS
       */
      add_action('wp_head', array($this, 'set_cors_domain_for_template_preview'));

      add_action('wp_print_footer_scripts', function() {

        echo "<script type='text/javascript'>

          jQuery(document).ready(function() {
            jQuery('a').each(function(index, element) {

              var url = jQuery(element).prop('href');

              if (url.indexOf('?') == -1) {
                url += '?is_wu_template_preview=1';
              } else {
                url += '&is_wu_template_preview=1';
              }

              jQuery(element).prop('href', url);

            });
          });

        </script>";

      }, 900);

    } // end if;

  } // end add_template_preview_headers;

  /**
   * Clean user tables from users without roles
   *
   * @param array $args
   * @return array
   */
  public function clean_user_tables_from_roleless_users($args) {

    if (!isset($args['include']) && !is_network_admin()) {

      $args['exclude'] = wp_get_users_with_no_role();

    } // end if;

    return $args;

  } // end clean_user_tables_from_roleless_users;

  /**
   * Allow users to picke their own sites as templates on the new site screen.
   * This option can be disable on the WP Ultimo settings page
   *
   * @since  1.7.4
   * @param  array $categories_and_templates
   * @param  array $categories
   * @param  array $templates
   * @return array
   */
  public function add_own_sites_to_template_options($categories_and_templates, $categories, $templates) {

    /**
     * Check if this is allowed and if we are not in the sign-up
     */
    if (!WU_Settings::get_setting('allow_own_site_as_template', true) || WU_Signup()->is_register_page() || !is_admin()) {

      return $categories_and_templates;

    } // end if;

    $subscription = wu_get_current_subscription();

    if (!$subscription) return $categories_and_templates;

    /**
     * Prepare user sites do be added to the list
     * @since 1.7.4
     */
    $user_sites = array_map(function($site_id) {

      /**
      * User should not be able to switch templates to its own site
      * @since 1.9.0
      */
      if ($site_id == get_current_blog_id() && ( isset($_GET['page']) && $_GET['page'] == 'wu-new-template' )) return;

      $template = new WU_Site_Template($site_id);

      if (!isset($template->blog_id)) return;

      return array(
        'id'           => $site_id,
        'screenshot'   => array( $template->get_thumbnail() ), // @todo multiple
        'name'         => $template->blogname,
        'url'          => $template->home,
        'description'  => $template->description,
        'author'       => '',
        'authorAndUri' => '',
        'version'      => '',
        'tags'         => __('Your Sites', 'wp-ultimo'),
        'parent'       => false,
        'active'       => false, // $slug === $current_theme,
        'hasUpdate'    => false,
        'hasPackage'   => false,
        'update'       => false,
        'network'      => false,
        'user_site'    => true,
        'actions'      => array(
          'visit'      => $template->home,
        ),
      );

    }, $subscription->get_sites_ids());

    /**
     * Filter invalid sites
     */
    $user_sites = array_filter($user_sites);

    /**
     * Build new categories list
     */
    $categories = array_merge(array(__('Your Sites', 'wp-ultimo')), $categories);

    /**
     * Add new sites to the template list
     */
    $templates = array_merge($user_sites, $templates);

    /**
     * We're done
     */
    return array($categories, $templates);

  } // end add_own_sites_to_template_options;

  /**
   * Handles search and replace for new blogs from WordPress
   *
   * @since 1.7.0
   * @param integer $to_site_id
   * @return void
   */
  public static function search_and_replace_for_new_site($to_site_id) {

    global $wpdb;

    $to_blog_prefix = $wpdb->get_blog_prefix( $to_site_id );

    $string_to_replace = apply_filters('mucd_string_to_replace', array(), false, $to_site_id);

    $tables = array();

    $to_blog_prefix_like = $wpdb->esc_like($to_blog_prefix);

    $results = MUCD_Data::do_sql_query('SHOW TABLES LIKE \'' . $to_blog_prefix_like . '%\'', 'col', FALSE);

    foreach( $results as $k => $v ) {

      $tables[str_replace($to_blog_prefix, '', $v)] = array();

    } // end foreach;

    foreach( $tables as $table => $col) {

      $results = MUCD_Data::do_sql_query('SHOW COLUMNS FROM `' . $to_blog_prefix . $table . '`', 'col', FALSE);

      $columns = array();

      foreach( $results as $k => $v ) {
        $columns[] = $v;
      }

      $tables[$table] = $columns;

    } // end foreach;

    $default_tables = MUCD_Option::get_fields_to_update();

    foreach( $default_tables as $table => $field) {

      $tables[$table] = $field;

    } // end foreach;

    foreach($tables as $table => $field) {

      foreach($string_to_replace as $from_string => $to_string) {

        MUCD_Data::update($to_blog_prefix . $table, $field, $from_string, $to_string);

      } // end if;

    } // end foreach;

  } // end search_and_replace_for_new_site;

  /**
   * Get search and replace settings
   *
   * @since 1.7.0
   * @return array
   */
  public function get_search_and_replace_settings() {

    $search_and_replace = WU_Settings::get_setting('search_and_replace', array());

    $pairs = array();

    foreach($search_and_replace as $item) {

      if ( ( isset($item['search']) && !empty($item['search']) ) && isset($item['replace'])) {

        $pairs[ $item['search'] ] = $item['replace'];

      } // end if;

    } // end foreach;

    return $pairs;

  } // end get_search_and_replace_settings;

  /**
   * Makes sure the search and replace array have no illegal values, such as null, false, etc
   *
   * @since 1.7.3
   * @param array $search_and_replace
   * @return array
   */
  public function filter_illegal_search_keys($search_and_replace) {

    return array_filter($search_and_replace, function($k) {

      return !is_null($k) && $k !== false && !empty($k);

    }, ARRAY_FILTER_USE_KEY);

  } // end filter_illegal_search_keys;

  /**
   * Add search and replace filter to be used on site duplication
   *
   * @since 1.6.2
   * @param array $search_and_replace
   * @param int   $from_site_id
   * @param int   $to_site_id
   * @return array
   */
  public function search_and_replace_on_duplication($search_and_replace, $from_site_id, $to_site_id) {

    $search_and_replace_settings = $this->get_search_and_replace_settings();

    $additional_duplication = apply_filters('wu_search_and_replace_on_duplication', $search_and_replace_settings, $from_site_id, $to_site_id);

    $final_list = array_merge($search_and_replace, $additional_duplication);

    return $this->filter_illegal_search_keys($final_list);

  } // end search_and_replace_on_duplication;

  /**
   * Prevent the duplicator from copying over AffiliateWP Tables
   *
   * @since 1.6.2
   * @param array $tables
   * @return array
   */
  public function filter_affiliatewp_tables($tables) {

    return array_filter($tables, function($table) {

      return strpos($table, 'affiliate_wp_') === false;

    });

  } // end filter_affiliatewp_tables;

  /**
   * Prevents Search Engines from indexing Site Templates
   *
   * @since 1.6.0
   * @return void
   */
  public function prevent_site_template_indexing() {

    if ( ! WU_Settings::get_setting('stop_template_indexing') ) return;

    $template = new WU_Site_Template( get_current_blog_id() );

    if ( $template->is_template ) {

      // Print no robots
      wp_no_robots();

    } // end if;

  } // end prevent_site_template_indexing;

  /**
   * Add the template site menu item on the menu
   *
   * @since 1.6.0
   * @return void
   */
  public function add_site_template_menu_item() {

    add_submenu_page('sites.php', __('Site Templates', 'wp-ultimo'), __('Site Templates', 'wp-ultimo'), 'create_sites', 'sites.php?type=template');

  } // end add_site_template_menu_item;

  /**
   * Filter the sites for the templates page
   *
   * @since 1.6.0
   * @param array $args
   * @return array
   */
  public function filter_site_query($args) {

    if (isset($_GET['type']) && $_GET['type'] == 'template') {

      $template_ids = array_keys(self::get_available_templates());

      $args['site__in'] = $template_ids;

    } // end if;

    return $args;

  } // end filter_site_query;

  /**
   * Add site template switcher on the site listing table on the network admin
   *
   * @since 1.6.0
   * @return void
   */
  public function add_site_template_separator() {

    global $current_screen;

    if ($current_screen->id !== 'sites-network') return;

    $count_all = get_sites(array(
      'count' => true
    ));

    $count_templates = count( self::get_available_templates() );

    $type = isset($_GET['type']) ? $_GET['type'] : 'current';

    ?>

    <ul class="subsubsub" id="sites-type-list" style="display: none;">
      <li class="all">
        <a href="<?php echo network_admin_url('sites.php'); ?>" class="<?php echo esc_attr(!$type ? 'current' : ''); ?>">
          <?php _e('All Sites', 'wp-ultimo');  ?> <span class="count">(<?php echo esc_html($count_all); ?>)</span>
        </a> |
      </li>
      <li class="super">
        <a href="<?php echo network_admin_url('sites.php?type=template'); ?>" class="<?php echo esc_attr($type == 'template' ? 'current' : ''); ?>">
          <?php _e('Template Sites', 'wp-ultimo');  ?> <span class="count">(<?php echo $count_templates; ?>)</span>
        </a>
      </li>
    </ul>

    <script>
      (function($) {
        $(document).ready(function() {
          $("#sites-type-list").insertAfter($('.wp-header-end')).show();
        });
      })(jQuery);
    </script>

    <?php

  } // end add_site_template_separator;

  /**
   * Adds the switching templates page
   *
   * @since 1.6.0
   * @return void
   */
  public function add_switching_templates_page() {

     if (! WU_Settings::get_setting('allow_template_switching') && !current_user_can('manage_network')) return;

    add_submenu_page('none', __('Switching Templates', 'wp-ultimo'), __('Switching Templates', 'wp-ultimo'), 'read', 'wu-new-template', array($this, 'switching_templates'));

  } // end add_switching_templates_page;

  /**
   * Handles template switching
   *
   * @since 1.6.0
   * @return void
   */
  public function handle_template_switching() {

    if (!isset($_REQUEST['wu_action']) || $_REQUEST['wu_action'] != 'wu_change_template') return;

    check_admin_referer('wu-switch-template');

    $current_blog_id = get_current_blog_id();

    if ($_POST['template'] == $current_blog_id) {

      return WP_Ultimo()->add_message( __('You cannot switch templates using this site as a base template.', 'wp-ultimo'), 'error' );

    } // end if;

    /**
     * We need to keep important info from the base saite before switching.
     */
    $settings_to_keep = apply_filters('wu_switch_template_settings_to_keep', array(
      'blogname'         => get_blog_option($current_blog_id, 'blogname'),
      'blogdescription'  => get_blog_option($current_blog_id, 'blogdescription'),
      'admin_email'      => get_blog_option($current_blog_id, 'admin_email'),
      'wu_custom-domain' => get_blog_option($current_blog_id, 'wu_custom-domain'),
    ));

    $results = self::switch_template( $_POST['template'], $current_blog_id );

    switch_to_blog($current_blog_id);

    if ($results === false || $results['msg'] != WU_NETWORK_PAGE_DUPLICATE_NOTICE_CREATED) {

      WP_Ultimo()->add_message( __('An error occurred when trying to switch your site template.', 'wp-ultimo'), 'error' );

    } else {

      /**
       * Update the template values.
       */
      foreach($settings_to_keep as $key => $value) {

        update_blog_option($current_blog_id, $key, $value);

      } // end foreach;

      WP_Ultimo()->add_message( __('Template successfully switched.', 'wp-ultimo') );

    } // end else;

  } // end handle_template_switching;

  /**
   * Handles the Switching Templates after sign-up
   *
   * @since 1.6.0
   * @return void
   */
  public function switching_templates() {

    global $wpdb;

    $templates = WU_Settings::get_setting('templates');

    // Check if we have templates enabled
    $has_templates = WU_Settings::get_setting('allow_template') && !empty($templates);

    // Check templates
    if (! $has_templates) {

      wp_die(__('No templates available', 'wp-ultimo'), __('No templates available', 'wp-ultimo'));

    } // end if;

    if (isset($_REQUEST['save_step'])) {

      ?>

        <div id="wp-ultimo-wrap" class="wrap">

          <h1><?php echo __('Are you sure about that?', 'wp-ultimo'); ?></h1>

          <p class="description"><?php _e('Are you sure you want to switch to this new template?', 'wp-ultimo'); ?></p>
          <p class="description"><?php _e('If you do decide to switch, all you data and customizations will be replaced with the data from the new template.', 'wp-ultimo'); ?></p>

          <form method="post">

            <input type="hidden" name="wu_action" value="wu_change_template">
            <input type="hidden" name="template" value="<?php echo esc_attr($_POST['template']); ?>">

            <?php wp_nonce_field('wu-switch-template'); ?>

            <br>
            <button class="button button-primary" type="submit"><?php _e('Yes, I\'m sure', 'wp-ultimo'); ?></button>
            <a href="<?php echo admin_url(); ?>" class="button" type="submit"><?php _e('No, bring me back.', 'wp-ultimo'); ?></a>
          </form>

        </div>

      <?php

      return;

    } // end else;

    wp_enqueue_style('wu-shortcodes');

    /**
     * Render the template step
     */
    return WP_Ultimo()->render('signup/steps/step-template', array(
      'signup'              => new WU_Signup,
      'switching_templates' => true,
    ));

  } // end switching_templates;

  /**
   * Template Previewer code
   *
   * @since 1.5.5
   * @return void
   */
  public function template_previewer() {

    $slug = self::get_template_preview_slug();

    if (isset($_GET[$slug]) && $_GET[$slug] !== '') {

      WP_Ultimo()->render('signup/steps/step-template-previewer');

    };

  } // end template_previewer;

  /**
   * Returns the template preview slug to be used
   *
   * @since 1.5.5
   * @return string
   */
  public static function get_template_preview_slug() {

    return apply_filters('wu_get_template_preview_slug', 'template-preview');

  } // end get_template_preview_slug

  /**
   * Returns the preview URL to a given site id

   * @param string $site_id
   * @return void
   */
  public static function get_template_preview_url($site_id = '') {

    $url = network_home_url() . '?' . self::get_template_preview_slug() . '=' . $site_id;

    /**
     * We need to check for transient to apply plan-specific filters
     * @since 1.6.0
     */
    if (isset($_GET['cs'])) {

      $url .= '&cs=' . $_GET['cs'];

    } // end if;

    if (isset($_GET['page']) && $_GET['page'] == 'wu-new-template') {

      $url .= '&switching=1';

    } // end if;

    return apply_filters('wu_get_template_preview_url', $url, $site_id);

  } // end get_template_preview_url;

  /**
   * Replace the function name
   * @param  string $function
   * @return string
   */
  public function replace_wp_die_function($function) {

    return array($this, 'replace_wp_die');

  } // end replace_wp_die_function;

  /**
   * New WP Die
   * @param  string $message
   * @param  string $title
   * @param  array  $args
   * @return html
   */
  public function replace_wp_die($message, $title, $args) {

    $title = $title ?: sprintf('%s - Error', get_network_option(null, 'site_name'));

    $title = apply_filters('wu_wp_die_title', $title);

    WP_Ultimo()->render('meta/error-page', array(
      'message' => apply_filters('wu_wp_die_message', $message),
      'title'   => $title,
      'args'    => $args,
    ));

    die;

  } // end replace_wp_die;

  /**
   * Remove the main site from the my sites list
   * @param  array $blogs
   * @return array
   */
  public function remove_main_site_from_my_sites($blogs, $user_id) {

    return array_filter($blogs, function($blog) use ($user_id) {

      $switched = is_multisite() ? switch_to_blog($blog->userblog_id) : false;

      $keep_blog = user_can($user_id, 'read');

      if ($switched) {

        restore_current_blog();

      } // end if;

      return $keep_blog;

    });

    return $blogs;

  } // end remove_main_site_from_my_sites;

  /**
   * Adds our new add new site link, or remove the My Sites menu if that option is not enabled
   * @since  1.2.0
   */
  public function new_site_menu() {

    $subscription = wu_get_current_subscription();

    /**
     * Checks if the user has a sub but no site
     */
    $needs_site = $subscription && $subscription->get_site_count() == 0;

    /**
     * If the user has that option activated, we display our custom menus, of not, we remove the My Sites menu
     */
    if ((!is_main_site() && $subscription && WU_Settings::get_setting('enable_multiple_sites')) || $needs_site) {

      $this->new_site_hook = add_submenu_page('index.php', __('Add New Site', 'wp-ultimo'), __('Add New Site', 'wp-ultimo'), 'read', 'wu-new-site', array($this, 'new_site_for_user_form'));

      add_action("load-$this->new_site_hook", array($this, 'replace_save_site_action'));

    } else {

      remove_submenu_page('index.php', 'my-sites.php');

    } // end if else;

  } // end new_site_menu;

  /**
   * Renders the new site form page
   *
   * @return void
   */
  public function new_site_for_user_form() {

    wp_enqueue_style('wu-shortcodes');

    WP_Ultimo()->render('forms/new-site-for-user');

  } // end new_site_for_user_form;

  /**
   * Chnages the registration link used on the Add new Link on the dashboard
   * @since  1.2.0
   * @param  WP_Screen $screen
   */
  public function new_site_changes($screen) {

    if ($screen->id == 'my-sites' && WU_Settings::get_setting('enable_multiple_sites')) {

      add_filter('wp_signup_location', function() {
        return admin_url('index.php?page=wu-new-site');
      });

    } // end if;

  } // end new_site_changes;

  /**
   * Replace the save action of saving the form of add_new
   * @return
   */
  public function replace_save_site_action() {

    $site = wu_get_current_site();

    /**
     * Firs of all, let's check the user's permissions
     */
    // if (!current_user_can('manage_network') && !wu_get_subscription( get_current_user_id() )) {

    //   return wp_die(__('You don\'t have permissions to access this page', 'wp-ultimo'));

    // } // end if;

    global $wpdb;

    if ( isset($_REQUEST['action']) && 'add-site' == $_REQUEST['action'] ) {
      check_admin_referer( 'add-blog', '_wpnonce_add-blog' );

      /** Adds the owner email to the payload */
      if (!isset($_POST['blog']['email'])) {
        $_POST['blog']['email'] = $site->site_owner ? $site->site_owner->user_email : wp_get_current_user()->user_email;
      }

      if ( ! is_array( $_POST['blog'] ) )
        wp_die( __( 'Can&#8217;t create an empty site.' ) );

      $blog = $_POST['blog'];
      $domain = '';
      if ( preg_match( '|^([a-zA-Z0-9-])+$|', $blog['domain'] ) )
        $domain = strtolower( $blog['domain'] );

      // If not a subdomain install, make sure the domain isn't a reserved word
      if ( ! is_subdomain_install() ) {
        $subdirectory_reserved_names = get_subdirectory_reserved_names();

        if ( in_array( $domain, $subdirectory_reserved_names ) ) {
          wp_die(
            /* translators: %s: reserved names list */
            sprintf( __( 'The following words are reserved for use by WordPress functions and cannot be used as blog names: %s' ),
              '<code>' . implode( '</code>, <code>', $subdirectory_reserved_names ) . '</code>'
            )
          );
        }
      }

      $title = $blog['title'];

      $meta = array(
        'public' => 1
      );

      // Handle translation install for the new site.
      if ( isset( $_POST['WPLANG']) && function_exists('wp_can_install_language_pack') ) {
        if ( '' === $_POST['WPLANG'] ) {
          $meta['WPLANG'] = ''; // en_US
        } elseif ( wp_can_install_language_pack() ) {
          $language = wp_download_language_pack( wp_unslash( $_POST['WPLANG'] ) );
          if ( $language ) {
            $meta['WPLANG'] = $language;
          }
        }
      }

      if ( empty( $domain ) )
        wp_die( __( 'Missing or invalid site address.' ) );

      if ( isset( $blog['email'] ) && '' === trim( $blog['email'] ) ) {
        wp_die( __( 'Missing email address.' ) );
      }

      $email = sanitize_email( $blog['email'] );
      if ( ! is_email( $email ) ) {
        wp_die( __( 'Invalid email address.' ) );
      }

      if ( is_subdomain_install() ) {
        $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', get_network()->domain );
        $path      = get_network()->path;
      } else {
        $newdomain = get_network()->domain;
        $path      = get_network()->path . $domain . '/';
      }

      $password = 'N/A';
      $user_id = email_exists($email);

      if ( !$user_id ) { // Create a new user with a random password
        /**
         * Fires immediately before a new user is created via the network site-new.php page.
         *
         * @since 4.5.0
         *
         * @param string $email Email of the non-existent user.
         */
        do_action( 'pre_network_site_new_created_user', $email );

        $user_id = username_exists( $domain );
        if ( $user_id ) {
          wp_die( __( 'The domain or path entered conflicts with an existing username.' ) );
        }
        $password = wp_generate_password( 12, false );
        $user_id = wpmu_create_user( $domain, $password, $email );
        if ( false === $user_id ) {
          wp_die( __( 'There was an error creating the user.' ) );
        }

        /**
          * Fires after a new user has been created via the network site-new.php page.
          *
          * @since 4.4.0
          *
          * @param int $user_id ID of the newly created user.
          */
        do_action( 'network_site_new_created_user', $user_id );
      }

      // Our custom action
      do_action('wp_ultimo_after_site_creation', $user_id);

      $wpdb->hide_errors();
      $id = wpmu_create_blog( $newdomain, $path, $title, $user_id, $meta, get_current_network_id() );
      $wpdb->show_errors();
      if ( ! is_wp_error( $id ) ) {
        if ( ! is_super_admin( $user_id ) && !get_user_option( 'primary_blog', $user_id, true ) ) {
          update_user_option( $user_id, 'primary_blog', $id, true );
        }

        wp_mail(
          get_site_option( 'admin_email' ),
          sprintf(
            /* translators: %s: network name */
            __( '[%s] New Site Created' ),
            get_network()->site_name
          ),
          sprintf(
            /* translators: 1: user login, 2: site url, 3: site name/title */
            __( 'New site created by %1$s

    Address: %2$s
    Name: %3$s' ),
            $current_user->user_login,
            get_site_url( $id ),
            wp_unslash( $title )
          ),
          sprintf(
            'From: "%1$s" <%2$s>',
            _x( 'Site Admin', 'email "From" field' ),
            get_site_option( 'admin_email' )
          )
        );

        wpmu_welcome_notification( $id, $user_id, $password, $title, array( 'public' => 1 ) );
        wp_redirect( add_query_arg( array( 'update' => 'added', 'id' => $id ), admin_url('site-new.php' )) );

        exit;
      } else {
        wp_die( $id->get_error_message() );
      }
    }
  }

  /**
   * Saves the new image selected
   * @param  integer $site_id Site we are editing
   */
  public function select_custom_template_image_save($site_id) {

    /**
     * Save the image in the database
     */
    update_blog_option($site_id, 'wu_template_img', $_POST['wu_template_img']);

  }

  /**
   * Add the custom form fields to allow users to select their image
   *
   * @since  1.1.0
   *
   * @param  integer $site_id Site ID to be got
   */
  public function select_custom_template_image($site_id) {

    // We need to get the media scripts
    wp_enqueue_media();
    wp_enqueue_script('media');

    $suffix = WP_Ultimo()->min;

    wp_enqueue_script('wu-field-button-upload', WP_Ultimo()->get_asset("wu-field-image$suffix.js", 'js'));

    $field_slug = "wu_template_img";

    ?>

      <tr class="form-field">

        <th scope="row">
          <label><?php _e('Template Image', 'wp-ultimo'); ?></label>
        </th>

        <td>
          <?php

            $image_id = get_blog_option($site_id, 'template_img');

            $image_obj = wp_get_attachment_image_src($image_id);
            $image_url = $image_obj ? $image_obj['url'] : WU_Screenshot::get_image($site_id);

            $image = '<img id="%s" %s alt="%s" style="width:%s; height:auto">';

            printf(
              $image,
              $field_slug.'-preview',
              "src='". $image_url . "'",
              get_bloginfo('name'),
              '200px'
            );

          ?>
          <br>
          <a href="#" class="button wu-field-button-upload" data-target="<?php echo $field_slug; ?>">
            <?php _e('Upload Template Image', 'wp-ultimo'); ?>
          </a>

          <p class="description" id="<?php echo $field_slug; ?>_desc">
            <?php _e('Upload your custom template image to be displayed in the template select screen.', 'wp-ultimo'); ?>
          </p>

          <input type="hidden" name="<?php echo $field_slug; ?>" id="<?php echo $field_slug; ?>" value="<?php echo $image_id; ?>">

        </td>

      </tr>

    <?php

  }

  /**
   * Serve the terms of service to our frontend
   */
  public function serve_terms_of_services() {

    $title = sprintf('%s - %s', get_network_option(null, 'site_name'), __('Terms of Service', 'wp-ultimo'));

    $text = wpautop(WU_Settings::get_setting('terms_content'));

    $this->replace_wp_die($text, $title, array('response' => 200, 'back_link' => false));

    exit;

  }

  public function add_user_plan_column($columns) {
    $columns['subscription'] = __('Subscription', 'wp-ultimo');
    return $columns;
  }

  public function add_user_plan_column_content($empty, $column_name, $user_id) {

    if ($column_name == 'subscription') {

      $subscription = wu_get_subscription($user_id);

      if (!$subscription || !$subscription->plan_id) return '-';

      else {

        $plan = new WU_Plan($subscription->plan_id);

        $active_title = $subscription->is_active() ? __('Active', 'wp-ultimo') : __('Not Active', 'wp-ultimo');
        $active_class = $subscription->is_active() ? 'wu-sub-active' : 'wu-sub-not-active';

        $html  = sprintf('<strong>%s</strong> - <span class="%s">%s</span>', $plan->title, $active_class, $active_title);
        $html .= '<br>';
        $html .= sprintf('%s, every %s month(s)', wu_format_currency($subscription->price), $subscription->freq);
        $html .= '<br>';
        $html .= sprintf('<a href="%s">%s &rarr;</a>', network_admin_url('admin.php?page=wu-edit-subscription&user_id=' . $user_id), __('Manage User Plan', 'wp-ultimo'));

        return $html;

      }

    } // end if column name;

    return $empty;

  } // end add_user_plan_column_content;

  /**
   * Changes the upload quota after a plan change in the subscription
   * @param  WU_Subscription $subscription Current subscription object
   * @param  WU_Plan $plan                 New Plan
   */
  public static function refresh_upload_quota_on_change_plan($subscription, $plan) {
    $site_list = $subscription->get_sites();
    foreach($site_list as $site) {
      WU_Site_Hooks::refresh_disk_space($site->site_id, $plan);
    }
  } // end refresh_upload_quota_on_change_plan;

  /**
   * Refresh disk space from one of the sites
   * @param  integer $site_id Site to refresh
   */
  public static function refresh_disk_space($site_id, $plan = null) {

    // get plan and site
    $site = wu_get_site($site_id);

    if ($plan === null) {

      $plan = $site->get_plan();

    } // end if;

    update_blog_option($site_id, 'blog_upload_space', (int) $plan->quotas['upload']);

  } // end refresh_disk_space;

  /**
   * Rewrite the ultimo settings on site save
   */
  public function rewrite_ultimo_settings() {

    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'siteoptions')) {

      // Reset registration
      if (isset($_POST['registration']) && $_POST['registration'] != 'all') {
        WU_Settings::save_setting('enable_signup', false);
      } else WU_Settings::save_setting('enable_signup', true);

      // Reset New Users
      $add_new_users = isset($_POST['add_new_users']);
      WU_Settings::save_setting('add_new_users', $add_new_users);

      // Reset Plugins
      $plugins = isset($_POST['menu_items']['plugins']);
      WU_Settings::save_setting('menu_items_plugin', $plugins);

    } // end if;

  } // end rewrite_ultimo_settings;

  /**
   * Add the extra new input
   */
  public function new_site_form() {
    WP_Ultimo()->render('forms/new-site');
  }

  /**
   * Add owner and plan to a site
   */
  public function add_owner($site_id) {
    WP_Ultimo()->render('forms/add-owner', array(
      'site_id' => $site_id,
    ));
  }

  /**
   * Add plan editor to user options
   */
  public function add_plan($user) {

    if (!is_network_admin()) return;

    WP_Ultimo()->render('forms/add-plan', array(
      'user' => $user,
    ));
  }

  /**
   * Edits the plan of a given user
   * @param  user_id $user_id User being edited
   */
  public function save_add_plan($user_id) {

    $subscription = wu_get_subscription($user_id);

    // If the plan is the same, we do nothing
    if (!isset($_POST['subscription_plan']) || !$_POST['subscription_plan'] || $subscription->plan_id == $_POST['subscription_plan']) return;

    $new_subscription = $subscription->plan_id ? false : true;
    if ($new_subscription) $subscription->freq = 1;

    // Check if plan exists
    $plan = new WU_Plan($_POST['subscription_plan']);

    if ($plan->plan_exists()) {

      // Change the plan in the subscription
      $subscription->plan_id = $_POST['subscription_plan'];

      // Hooks, passing new plan
      do_action('wu_subscription_change_plan', $subscription, $plan);

      // Check if we are suposed to change price
      if (isset($_POST['change_price']) || $new_subscription) {

        $subscription->price = $plan->{"price_$subscription->freq"};

        if ($subscription->integration_status && wu_get_gateway($subscription->gateway)) {
          wu_get_gateway($subscription->gateway)->remove_integration(false, $subscription);
        }

      } // end if change price;

      // Save
      $subscription->save();

    } // end if;

  } // end save_add_plan;

  /**
   * Saves a new owner to a given site
   * @param  interger $site_id The site id to be changed
   */
  public function save_add_owner($site_id) {

    // Initialize site
    $site = wu_get_site($site_id);

    /**
     * Update force HTTPs
     */
    $force_https = isset($_POST['force_https']);

    $site->set_meta('force_https', $force_https);

    // Check if owner was passed and it is different from what we have already
    if ($site->site_owner_id == $_POST['site_owner']) return;

    // Set new owner
    $site->set_owner( (int) $_POST['site_owner'], apply_filters('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator'), array(
          'plan_id' => $site->get_plan()->id
    )));

    // Refresh space if user has plan
    if ($site->get_plan()) {
      update_blog_option($site_id, 'blog_upload_space', $site->get_plan()->quotas['upload']);
    }

    // Refreshes cache
    $this->clear_available_site_template_cache();

  } // end save_add_owner;

  /**
   * This function handles the duplication of sites in the form of add-new-site.php
   * @param  user_id $user_id ID of the user criating this site
   */
  public function new_site_form_save($user_id) {

    global $wpdb, $current_site;

    $post = $_POST;

    // Set constant
    if (!defined('MUCD_PRIMARY_SITE_ID')) define('MUCD_PRIMARY_SITE_ID', 1);

   /**
    * Now we move on to creating the site
    */

    // Get the template
    $template_id = apply_filters('wu_site_template_id', $post['site_template'], $user_id);

    // Save the site
    $site_id = WU_Signup()->create_site($user_id, array(
      'blog_title' => $post['blog']['title'],
      'blogname'   => $post['blog']['domain'],
      'role'       => apply_filters('wu_register_default_role', WU_Settings::get_setting('default_role', 'administrator'), array(
          'plan_id' => isset($post['site_plan']) ? $post['site_plan'] : apply_filters('wu_extra_sites_plan_id', false)
      )),
    ), $template_id);

    /**
     * Check for errors in the site creation
     */
    if (is_wp_error($site_id)) {

      wp_die($site_id->get_error_message());

    } // end if;

    /**
     * @since 1.5.0 Allows creation of sites
     */
    $_plan_id = isset($post['site_plan']) ? $post['site_plan'] : apply_filters('wu_extra_sites_plan_id', false);
    $_plan_freq = apply_filters('wu_extra_sites_freq', 1);

    // Check if we set a different plan id
    if ($_plan_id) {

      $plan_id = $_plan_id;

      // Get the plan object
      $plan = new WU_Plan($plan_id);

      /**
       * @since  1.2.0 Create the subscription here and not on the wu_site initialization
       */
      $subscription = new WU_Subscription((object) array(
        'user_id'      => $user_id,
        'created_at'   => date('Y-m-d H:i:s'),
        'active_until' => date('Y-m-d H:i:s'),
        'plan_id'      => (int) $plan_id,
        'freq'         => (int) 1,
        'price'        => $plan->price_1,
      ));

      $subscription = $subscription->save();

      // Set storage space
      update_blog_option($site_id, 'blog_upload_space', $plan->quotas['upload']);

    } // end if;

    $user = get_user_by('id', $user_id);

    // Admin
    WU_Mail()->send_template('account_created', get_network_option(null, 'admin_email'), array(
      'date'               => date(get_option('date_format')),
      'user_name'          => $user->user_name,
      'user_site_id'       => $site_id,
      'user_site_name'     => $post['blog']['title'],
      'user_account_link'  => get_admin_url($site_id, 'admin.php?page=wu-my-account'),
    ));

    // Log Event
    $log = sprintf(__('A new site of title %s and id %s was created, owned by user %s of id %s.', 'wp-ultimo'), $post['blog']['title'], $site_id, $user->user_name, $user_id);

    WU_Logger::add('signup', $log);

    /**
     * For user creating
     * @since  1.2.0
     */
    if (isset($_POST['user_creating'])) {

      /**
       * Check if the user was add to the template site wich he didn't owned;
       */
      $user_owned_sites = WU_Site_Owner::get_user_sites($user_id);

      if (!array_key_exists($template_id, $user_owned_sites)) {

        remove_user_from_blog($user_id, $template_id);

      } // end if;

      wp_redirect(add_query_arg(array('update' => 'added', 'id' => $site_id), get_admin_url($site_id, 'my-sites.php')));

      exit;

    } // end if;

    wp_redirect(add_query_arg(array('update' => 'added', 'id' => $site_id), network_admin_url('site-new.php')));

    exit;

  } // end new_site_form_save;

  /**
   * Clear the caches of available site templates
   * TODO: Move to other class where all the site templates feature should be consolidated
   *
   * @since 1.9.4
   * @return void
   */
  public function clear_available_site_template_cache() {

    /**
     * Clear transients
     */
    delete_site_transient("wu_available_templates_with_wp");
    delete_site_transient("wu_available_templates_without_wp");

    /**
     * Allow devs to hook into this if they want to
     *
     * @since 1.9.4
     * @return void
     */
    do_action('wu_clear_available_site_template_cache');

  } // end clear_available_site_template_cache;

  /**
   * Return all the templates available for use in Blog Creation
   *
   * @since  1.1.3 Templates now use site names instead of path;
   * @since  1.5.4 Optimized version to reduce query count;
   * @return array Array containing all the available templates
   */
  public static function get_available_templates($include_wp = true) {

    global $wpdb;

    $type = $include_wp ? 'with_wp' : 'without_wp';

    $_template_list = get_site_transient("wu_available_templates_$type");

    if ($_template_list && is_array($_template_list)) {

      return $_template_list;

    } // end if:

    // WordPress Template
    $sites_list = $include_wp ? array('0' => __('Use standard WordPress default blog')) : array();

    // Get all the blogs
    $sites = get_sites(array(
      'number'        => 99999,
      'network_id'    => get_current_site()->id,
      'fields'        => 'ids',
      'no_found_rows' => true,
    ));

    // We get the main site ID
    if (($key = array_search(get_current_site()->blog_id, $sites)) !== false) {

      unset($sites[$key]);

    } // end if;

    $sites_with_owners = WU_Site_Owner::get_sites('user_id, site_id');

    // Loop site owner to unset the sites
    foreach($sites_with_owners as $site_with_owner) {

      if ($site_with_owner->user_id && !user_can($site_with_owner->user_id, 'manage_network')) {

        if (($key = array_search($site_with_owner->site_id, $sites)) !== false) {

          unset($sites[$key]);

        } // end if;

      } // end if;

    } // end if;

    foreach($sites as $site) {

      $sites_list[$site] = sprintf('(#%s) %s', $site, get_blog_option($site, 'blogname'));

    } // end foreach;

    // Saves as transient as this is expensive to get
    set_site_transient("wu_available_templates_$type", $sites_list, 20 * MINUTE_IN_SECONDS);

    // Return the list
    return $sites_list;

  } // end get_site_templates;

  /**
   * Duplicates our template site in the creation of the new user site
   * @param  integer [$site_to_duplicate = 2] ID of site template
   * @param  string  $title                   Site Title
   * @param  string  $domain                  Domain of the new site, as selected
   * @param  string  $email                   Admin email of the user
   * @return integer Site ID of the new site
   */
  public static function duplicate_site($site_to_duplicate = 2, $title, $domain, $email, $site_domain = false, $copy_files = '') {

    /**
     * @since  1.3.0 Allow for site duplication without copying files
     */

     if (!is_bool($copy_files)) {

      $copy_files = WU_Settings::get_setting('copy_media', true);

     } // end if;

    $copy_files = $copy_files ? 'yes' : 'no';

    // Base data
    $data = array(
      'source'        => $site_to_duplicate,
      'from_site_id'  => $site_to_duplicate,
      'domain'        => $domain,
      'path'          => $domain,
      'title'         => $title,
      'email'         => $email,
      'copy_files'    => $copy_files,
      'keep_users'    => apply_filters('wu_duplicate_keep_users', 'no'),
      'log'           => WP_DEBUG ? 'yes' : 'no',
      'log-path'      => WU_Logger::get_logs_folder(),
      'advanced'      => 'hide-advanced-options',
    );

    // We need our current site for reference
    global $current_site;

    // Define defaults
    $error = array();
    $domain = '';

    if (preg_match('|^([a-zA-Z0-9-])+$|', $data['domain']))
      $domain = strtolower($data['domain']);

    $site_domain = $site_domain ?: $current_site->domain;

    if (is_subdomain_install()) {
      $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $site_domain);
      $path      = $current_site->path;
    } else {
      $newdomain = $site_domain;
      $path      = $current_site->path . $domain . '/';
    }

    // Set the new values, after treatments
    $data['domain']    = $domain;
    $data['newdomain'] = $newdomain;
    $data['path']      = $path;
    $data['public']    = !isset($data['private']);

    // Network
    $data['network_id'] = $current_site->id;

    // Results
    $duplicated = MUCD_Duplicate::duplicate_site($data);

    /**
     * @since 1.5.0 Removing copy of attachment post type
     */
    if (isset($duplicated['site_id']) && $copy_files === 'no') {

      // Delete media of the newly created site
      global $wpdb;

      switch_to_blog($duplicated['site_id']);

      // delete all posts by post type.
      $sql = 'DELETE `posts`, `pm`
          FROM `' . $wpdb->prefix . 'posts` AS `posts`
          LEFT JOIN `' . $wpdb->prefix . 'postmeta` AS `pm` ON `pm`.`post_id` = `posts`.`ID`
          WHERE `posts`.`post_type` = \'attachment\'';

      $result = $wpdb->query($sql);

      restore_current_blog();

    } // end if;

    /**
     * Allow developers to hook after a site suplication happens
     * TODO: This is not ideal, we should have a interface that allows users to hook both
     * on new "clean" sites as well as duplicated sites
     *
     * @since 1.9.4
     * @return void
     */
    do_action('wu_duplicate_site', $duplicated);

    // Run the duplication system
    return $duplicated;

  } // end duplicate_site;

  /**
   * Switch Template from Site ID to another onw
   *
   * @param integer $from_site_id
   * @param integer $to_site_id
   * @return void
   */
  public static function switch_template($from_site_id, $to_site_id) {

    if (!defined('MUCD_PRIMARY_SITE_ID')) define('MUCD_PRIMARY_SITE_ID', 1);

    // Set the default domain
    $domain = '';

    /**
     * @since  1.3.0 Allow for site duplication without copying files
     */
    $copy_files = WU_Settings::get_setting('copy_media') ? 'yes' : 'no';

    // Base data
    $data = array(
      'source'        => $from_site_id,
      'from_site_id'  => $from_site_id,
      'domain'        => $domain,
      // 'path'          => $domain,
      // 'title'         => $title,
      // 'email'         => $email,
      'copy_files'    => $copy_files,
      'keep_users'    => apply_filters('wu_duplicate_keep_users', 'no'),
      'log'           => WP_DEBUG ? 'yes' : 'no',
      'log-path'      => WU_Logger::get_logs_folder(),
      'advanced'      => 'hide-advanced-options',
    );

    // We need our current site for reference
    global $current_site;

    // Network
    $data['network_id'] = $current_site->id;

    // Results
    $duplicated = MUCD_Duplicate::switch_template($from_site_id, $to_site_id, $data);

    /**
     * @since 1.5.0 Removing copy of attachment post type
     */
    if (isset($duplicated['site_id']) && $copy_files === 'no') {

      // Delete media of the newly created site
      global $wpdb;

      switch_to_blog($duplicated['site_id']);

      // delete all posts by post type.
      $sql = 'DELETE `posts`, `pm`
          FROM `' . $wpdb->prefix . 'posts` AS `posts`
          LEFT JOIN `' . $wpdb->prefix . 'postmeta` AS `pm` ON `pm`.`post_id` = `posts`.`ID`
          WHERE `posts`.`post_type` = \'attachment\'';

      $result = $wpdb->query($sql);

      restore_current_blog();

    } // end if;

    /**
     * Allow plugin developers to hook functions after a user or super admin switches the site template
     *
     * @since 1.9.8
     * @param integer Site ID
     * @return void
     */
    do_action('wu_after_switch_template', $duplicated['site_id']);

    // Run the duplication system
    return $duplicated;

  } // end duplicate_site;

  /**
   * Get our of the WU Sites
   * TODO: Revise this, this is not suposed to return all the subscriptions, but all the sites
   * @param  integer $plan_id The Plan ID to get
   * @return array            All sites with that plan
   */
  public static function get_wu_sites($plan_id) {

    global $wpdb;

    $table_name = WU_Subscription::get_table_name();

    if (!is_numeric($plan_id)) return array();

    $sql = "SELECT * FROM $table_name WHERE plan_id = $plan_id";

    return $wpdb->get_results($sql);

  } // end get_wu_sites;

  /**
   * Adds the custom columns to display the plans of each user
   * @param  array $columns Columns passed by WordPress
   * @return array Columns with our custom column added
   */
  function add_plan_column($columns) {
    $columns['subscription'] = __('Subscription', 'wp-ultimo');
    return $columns;
  }

  /**
   * Display the value for our custom column
   * @param  string  $val         Default value passed by WP
   * @param  string  $column_name Column name for control purposes
   * @param  integer $site_id     User id
   * @return string  New value of the columns
   */
  function display_plan_column($column_name, $site_id) {

    if ($column_name == 'subscription') {

      $site         = wu_get_site($site_id);
      $plan         = $site->get_plan();
      $subscription = $site->get_subscription();

      if ($plan) {

        $active_title = $subscription->is_active() ? __('Active', 'wp-ultimo') : __('Not Active', 'wp-ultimo');
        $active_class = $subscription->is_active() ? 'wu-sub-active' : 'wu-sub-not-active';

        echo sprintf('<strong>%s</strong> - <span class="%s">%s</span>', $plan->title, $active_class, $active_title);

        echo '<br>';
        echo sprintf('<strong>%s</strong>: <a href="%s">%s</a>', __('Site Owner', 'wp-ultimo'), network_admin_url('user-edit.php?user_id='.$subscription->user_id), $site->site_owner->display_name);

        echo '<br>';
        echo sprintf('<a href="%s">%s &rarr;</a>', network_admin_url('admin.php?page=wu-edit-subscription&user_id='.$subscription->user_id), __('Manage this Subscription', 'wp-ultimo'));

      } // end if;

      else echo '--';

    } // end if;

  } // end display_plan_column;

  /**
   * Returns the plan of a specific user
   * @param  integer $site_id Id of the user
   * @return boolean WU_Plan object on success, false on failure
   */
  public static function get_site_plan($site_id = false) {

    // If not one site was passed, we set to the current
    $site_id = $site_id ? $site_id : get_current_blog_id();

    $site = new WU_Site($site_id);
    return $site->get_plan();

  } // end get_site_plan;

  /**
   * Deletes the mapping related to a specific site
   * @param  interget $site_id Site id
   * @return
   */
  public function delete_site_mappings($site_id) {

    if (!class_exists('\Mercator\Mapping')) return false;

    $mappings = Mercator\Mapping::get_by_site($site_id);

    if (is_wp_error($mappings) || !$mappings) {

      return false;

    }

    // Loop all Valid Mappings
    foreach ($mappings as $mapping) {

      $mapping->delete();

    } // end foreach;

  } // end delete_site_mappings;

  /**
   * On Delete Blog
   */
  public function on_delete_blog($site_id) {

    global $wpdb;

    // WP 5.1.0 `wp_uninitialize_site` action passes a WP_Site as argument.
    if (is_a($site_id, 'WP_Site')) {
      $site_id = $site_id->blog_id;
    }

    // Get site name
    $site = wu_get_site($site_id);

    /**
     * @since 1.3.4 Deletes the mappings related to the site
     */
    $this->delete_site_mappings($site_id);

    // Get table name
    $table_name = WU_Site_Owner::get_table_name();

    // Remove the entry from our table
    $wpdb->delete($table_name, array(
      'site_id' => $site_id,
    ));

    /**
     * Sends the account removal email for the user and admin
     * @since 1.7.0
     */
    return WU_Mail()->send_template('site_removed', get_network_option(null, 'admin_email'), array(
      'user_site_id'           => $site_id,
      'user_site_name'         => $site->name,
      'user_name'              => $site->site_owner->data->display_name,
      'user_subscription_link' => network_admin_url('admin.php?page=wu-edit-subscription&user_id=' . $site->site_owner_id),
    ));

  } // end on_delete_blog;

  /**
   * Removes the user subscription when he gets deleted
   * @param  interger $user_id User ID to be deleted
   * @return
   */
  public function on_delete_user($user_id) {

    global $wpdb;

    /**
     * @since  1.3.4 Deletes the mappings related to the site
     */
    $sites = WU_Site_Owner::get_user_sites($user_id);

    if ($sites) {

      foreach (array_keys($sites) as $site_id) {

        $this->delete_site_mappings($site_id);

      } // end foreach;

    } // end if

    $subscription = wu_get_subscription($user_id);

    // Remove subscription
    if ($subscription) {

      $subscription->delete();

    } // end if;

    $table_name = WU_Site_Owner::get_table_name();

    // Remove the entry from our table
    $wpdb->delete($table_name, array(
      'user_id' => $user_id,
    ));

    // Refreshes cache
    $this->clear_available_site_template_cache();

  } // end on_delete_user;

  /**
   * Display the JavaScript needed to put the select on the filter bar
   */
  function footer_js() {
    if (!is_network_admin()) return;
    $js = '
      <script type="text/javascript">
        (function($) {
          if ($(".tablenav > .alignleft").length > 0) {
            $(".bulkactions").after($("#wu-plan-select").show());
          } else {
            $(".tablenav").append($("#wu-plan-select").show());
          }
        })(jQuery);
      </script>
    ';
    echo $js;
  } // end footer_js;

} // end class WU_Site;

new WU_Site_Hooks;
