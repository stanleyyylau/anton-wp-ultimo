<?php
/**
 * Site Templates
 *
 * Handles site templates and their display functions
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Site_Templates
 * @version     1.2.0
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Site_Templates {

  public function __construct() {

    add_filter('manage_sites_action_links', array($this, 'add_action_link'), 10, 2);

    add_action('in_admin_header', array($this, 'add_inline_form'));
    
    add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

    add_filter('wpmu_blogs_columns', array($this, 'add_categories_column'), 9);

    add_action('manage_sites_custom_column', array($this, 'display_default_info'), 10, 2);

    add_action('wp_ajax_wu_save_site_template', array($this, 'save_site_template'));

  } // end construct;

  /**
   * Save the template model to be used later on.
   * @return
   */
  public function save_site_template() {

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have the necessary permissions to perform this action.', 'wp-ultimo'));

    } // end if;

    check_ajax_referer('wu_site_template_inline_edit');

    // Get the ID
    $id = str_replace('wu_blog_', '', $_POST['extension_id']);

    // Categories
    $cats = isset($_POST['wu_categories']) ? explode(',', trim($_POST['wu_categories'])) : array();
    
    $cats = array_map(function($value) {
      
      return ucfirst(trim($value)); 
      
    }, $cats);

    $cats = implode(', ', array_unique($cats));

    // Adds the elements
    $site_template = new WU_Site_Template($id);

    // Adds the data
    $site_template->set_attributes($_POST);

    $site_template->wu_categories = $cats;

    $site_template->save();

    /**
     * Display the new result
     */
    $wp_list_table = _get_list_table('WP_MS_Sites_List_Table', array('screen' => 'sites-network'));

    $site_template_array = $site_template->to_array();

    if ( ! empty( $_REQUEST['mode'] ) ) {
      $GLOBALS['mode'] = $_REQUEST['mode'] === 'excerpt' ? 'excerpt' : 'list';
      set_user_setting( 'sites_list_mode', $GLOBALS['mode'] );
    } else {
      $GLOBALS['mode'] = get_user_setting( 'sites_list_mode', 'list' );
    }

    // Displays
    ob_start();

    $wp_list_table->single_row( $site_template_array );

    $output = ob_get_clean();

    $output = str_replace('<tr', "<tr data-slug='wu_blog_$id'", $output);

    echo $output;

    // Exits
    die;

  } // end save_site_template;

  /**
   * Adds our custom inline edit link
   * @param [type] $actions [description]
   */
  public function add_action_link($actions, $blog_id) {

    $site = wu_get_site($blog_id);

    if ($site->get_subscription() || $blog_id == 1) return $actions;

    $actions['inline hide-if-no-js wu-template-actions'] = sprintf(
      '<a href="#" class="editinline" aria-label="%s">%s</a>',
      /* translators: %s: post title */
      esc_attr(__('Quick edit inline', 'wp-ultimo')),
      __( 'Edit Site Template Information', 'wp-ultimo')
    );

    /**
     * Duplicate sites
     * @since 1.4.2
     */
    $url = network_admin_url('site-new.php?site_template=' . $blog_id);

    $actions['duplicate hide-if-no-js wu-template-actions'] = sprintf(
      '<a href="%s" class="duplicate" aria-label="%s">%s</a>',
      $url,
      /* translators: %s: post title */
      esc_attr(__('Quick edit inline', 'wp-ultimo')),
      __( 'Duplicate Template', 'wp-ultimo')
    );

    return $actions;

  } // end add_action_link;

  /**
   * Register and enqueue our scripts
   */
  public function enqueue_scripts() {

    $suffix = WP_Ultimo()->min;

    // Register scripts
    wp_register_script('wu-site-template-inline-edit', WP_Ultimo()->get_asset("wu-site-templates-inline-edit$suffix.js", 'js'), array('jquery'), WP_Ultimo()->version);

    wp_localize_script('wu-site-template-inline-edit', 'inlineEditL10n', array(
      'error'      => __( 'Error while saving the changes.' ),
      'ntdeltitle' => __( 'Remove From Bulk Edit' ),
      'notitle'    => __( '(no title)' ),
      'comma'      => trim( _x( ',', 'tag delimiter' ) ),
      'saved'      => __( 'Changes saved.' ),
    )); 

  } // end enqueue_scripts;

  function add_categories_column($columns) {

    $columns['wu_categories'] = __('Templates Categories', 'wp-ultimo');

    return $columns;

  } // end add_categories_column; 

  /**
   * Display the value for our custom column
   * @param  string  $val         Default value passed by WP
   * @param  string  $column_name Column name for control purposes
   * @param  integer $site_id     User id
   * @return string  New value of the columns
   */
  function display_default_info($column_name, $site_id) {

    $site_template = new WU_Site_Template($site_id);

    if ($column_name == 'subscription') {

      /**
       * Display Default info
       */
      foreach(array('blogname', 'blogdescription') as $field) {

        echo "<input type='hidden' name='field_$field' value='". get_blog_option($site_id, $field) ."'>";

      } // end foreach;

      echo "<input type='hidden' name='field_wu_categories' value='". $site_template->get_categories() ."'>";

      echo "<input type='hidden' name='field_template_img-preview' value='". $site_template->get_thumbnail() ."'>";

      echo "<input type='hidden' name='field_template_img' value='$site_template->template_img'>";

    } // end if;

    /**
     * Display Categories
     */
    if ($column_name == 'wu_categories') {

      echo $site_template->is_template ? $site_template->wu_categories : '--' ;

    } // end if;

  } // end display_plan_column;

  /**
   * Prepare the templates array to be used on the template selection screens
   * 
   * @since 1.5.5 Remove the false occurrencies
   * @since 1.1.0
   * @return array
   */
  static function prepare_site_templates() {

    $template_list = WU_Settings::get_setting('templates');
    $templates     = is_array($template_list) && $template_list ? WU_Settings::get_setting('templates') : array();
    $all_templates = apply_filters( 'all_site_templates', array_keys($templates));

    $prepared_templates = array();

    $cats = array();

    foreach($all_templates as $template_id) {

      $template = new WU_Site_Template($template_id);

      if (!$template->is_template) continue; // @since 1.5.5 Remove the false occurrencies

      $cats[] = $template->wu_categories;

      $prepared_templates[] = array(
        'id'           => $template_id,
        'screenshot'   => array( $template->get_thumbnail() ), // @todo multiple
        'name'         => $template->blogname,
        'url'          => $template->home,
        'description'  => $template->description,
        'author'       => '',
        'authorAndUri' => '',
        'version'      => '',
        'tags'         => $template->wu_categories,
        'parent'       => false,
        'active'       => false, // $slug === $current_theme,
        'hasUpdate'    => false,
        'hasPackage'   => false,
        'update'       => false,
        'network'      => false,
        'actions'      => array(
          'visit'      => $template->home,
        ),
      );

    } // end foreach;

    $categories = array_filter(array_unique(explode(', ', implode(', ', $cats))));

    /**
     * Allow to add more categories and templates if they need to. 
     * This also provides a hook for us to add additional options, like letting users choose their own sites as templates, for example.
     * 
     * @since 1.7.4
     * @return array
     */
    return apply_filters('wu_prepare_site_templates', array($categories, $prepared_templates), $categories, $prepared_templates);

  } // end prepare_site_templates;

  /**
   * Render the inline edit form when necessary
   */
  function add_inline_form() {

    // Check for the screen
    $allowed = array('sites-network');

    $screen = get_current_screen();

    if (!in_array($screen->id, $allowed)) return;

    // Enqueue needed Scripts and Styles
    wp_enqueue_script('wu-site-template-inline-edit');

    // Render the template
    WP_Ultimo()->render('site-templates/inline-edit', array(
      'type'                    => __('Site Template', 'wp-ultimo'),
      'type_slug'               => 'site-template',
      'flat_taxonomies'         => array(),
    ));

  } // end add_inline_form;

} // end Class WU_Site_Templates;

// Initialize
new WU_Site_Templates;