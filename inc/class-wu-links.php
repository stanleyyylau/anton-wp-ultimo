<?php
/**
 * Links Class
 *
 * This helper class allow us to keep our external link references in one place for better control;
 * Links are also filterable;
 * 
 * @since       1.7.0
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Links
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Links {

  /**
   * Makes sure we are only using one instance of the class
   * 
   * @var object WU_Links
   */
  public static $instance;

  /**
   * Holds the links so we can retrieve them later
   * 
   * @var array
   */
  public $links;

  /**
   * Holds the default link
   * 
   * @var string
   */
  public $default_link = 'https://docs.wpultimo.com/knowledge-base/';

  /**
   * Returns the instance of WP_Ultimo
   * 
   * @return object A WU_Links instance
   */
  public static function get_instance() {

    if (null === self::$instance) {

      self::$instance = new self();

    } // end if;

    return self::$instance;

  } // end get_instance;

  /**
   * Set the links
   */
  public function __construct() {

    $links = array(

      /**
       * Main Links
       * @since 1.7.0
       */
      'main-site'            => 'https://wpultimo.com/?utm_source=plugin&utm_medium=network-admin',
      'blog'                 => 'https://wpultimo.com/blog?utm_source=plugin&utm_medium=network-admin',
      'documentation'        => 'https://help.wpultimo.com/',
      'addons'               => 'https://wpultimo.com/addons/',
      'roadmap'              => 'https://trello.com/b/jHh9E92P/wp-ultimo-roadmap',
      'review'               => 'https://goo.gl/forms/S8fC49oOgRHIjxpn2',
      'translate'            => 'https://translate.nextpress.co',
      'forums'               => 'https://community.wpultimo.com/',
      'facebook-group'       => 'https://www.facebook.com/groups/wpultimo/',
      'rss-feed'             => 'https://community.wpultimo.com/topics/feed',
      'blog-rss-feed'        => 'https://wpultimo.com/feed',
      'changelog'            => 'https://deploy.nextpress.co/?get-changelog=wp-ultimo',
      'webinar'              => 'https://wpultimo.com/how-it-works',

      /**
       * Tutorials
       * @since 1.9.9 Replaced docs.wpultimo.com links with help.wpultimo.com links.
       * @since 1.7.0
       */
      'cloudways-tutorial'   => 'https://help.wpultimo.com/tutorials/configuring-automatic-domain-syncing-with-cloudways',
      'cpanel-tutorial'      => 'https://help.wpultimo.com/tutorials/configuring-automatic-domain-syncing-with-cpanel',
      'runcloud-tutorial'    => 'https://help.wpultimo.com/tutorials/configuring-automatic-domain-syncing-with-runcloudio',
      'serverpilot-tutorial' => 'https://help.wpultimo.com/tutorials/configuring-automatic-domain-syncing-with-serverpilotio-with-autossl-support',
      'webhooks-tutorial'    => 'https://help.wpultimo.com/tutorials/integrating-wp-ultimo-with-zapier-using-webhooks',
      'search-and-replace'   => 'https://help.wpultimo.com/tutorials/using-the-search-and-replace-api-on-site-duplication',
      'site-templates'       => 'https://help.wpultimo.com/getting-started/getting-started-with-site-templates',
      'beta-program'         => 'https://help.wpultimo.com/meta/get-access-to-beta-releases-by-joining-the-beta-program',
      
    ); 

    $this->links = apply_filters('wu_links_list', $links);

  } // end construct;

  /**
   * Retrieves a link registered
   *
   * @since 1.7.0
   * @param  string $slug
   * @return string
   */
  public function get_link($slug) {

    $link = isset($this->links[$slug]) ? $this->links[$slug] : $this->default_link;

    /**
     * Allow plugin developers to filter the links.
     * Not sure how that could be useful, but it doesn't hurt to have it
     * 
     * @since 1.7.0
     * @param string $link         The link registered
     * @param string $slug         The slug used to retirve the link
     * @param string $default_link The default link registered
     */
    return apply_filters('wu_get_link', $link, $slug, $this->default_link);

  } // end get_link;

  /**
   * Add a new link to the list of links available for reference
   *
   * @since 1.7.0
   * @param string $slug
   * @param string $link
   * @return void
   */
  public function register_link($slug, $link) {

    $this->links[$slug] = $link;

  } // end add_link;

} // end class WU_Links;

/**
 * Returns the singleton
 */
function WU_Links() {

  return WU_Links::get_instance();

} // end WU_Links;

// Initialize
WU_Links();