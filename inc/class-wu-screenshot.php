<?php

/**
 * Screenshot Class
 *
 * Handles the automation of the screenshot getter
 *
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Screenshot
 * @version     0.0.1
 */

if (!defined('ABSPATH')) {
  exit;
}

class WU_Screenshot {

  /**
   * Number of URLS to process on each call
   *
   * @var integer
   */
  public $chunk_size = 20;

  /**
   * Holds the Google Insights API key
   *
   * @var string
   */
  public $api_key = 'AIzaSyCDw0QunzPAXHJpa6Dk_o0d7gxUUc5jylg';

  /**
   * Constructor of our screenshot module
   * Sets up our cron, to automate the screenshot
   */
  public function __construct() {

    // Add our cron tab
    if (!wp_next_scheduled('wu_cron_tab')) {
      
      wp_schedule_event(time(), 'hourly', 'wu_cron_tab');
      
    } // end if;

    /**
     * Check the option to see if we need to add the cron job
     */
    if (WU_Settings::get_setting('enable_screenshot_scraper', false)) {

      // Run cron to get images
      add_action('wu_cron_tab', function() {

        $sites = $this->get_sites_list();

        $this->process_queue($sites);

      });

    } // end if;

    // Add the ajax handler
    add_action('wp_ajax_wu_screenshot_scraper', array($this, 'ajax_get_thumbnails'));

  } // end __construct;

  /**
   * Allows us to scrap images using a ajax button
   * @since  1.1.0
   */
  public function ajax_get_thumbnails() {

   if (!current_user_can('manage_network')) {

      wp_send_json(array(
        'message' => __('You don\'t have permissions to perform that action.', 'wp-ultimo'),
        'status'  => false,
      ));

    } // end if;

    $sites = $this->get_sites_list();
    
    /**
     * Run our new version, asyncronically 
     */
    $this->process_queue($sites, true);

    wp_send_json(array(
      'message' => __('Screenshots being retrieved in the background. It might take a few minutes.', 'wp-ultimo'),
      'status'  => true,
    ));

  } // end ajax_get_thumbnails;

  /**
   * Retrieve the list of template sites
   * @since  1.1.0
   * @return array
   */
  public function get_sites_list() {

    // Set the sites
    $sites = array();

    /**
     * Get all the templates and run them on loop
     */
    foreach ((array) WU_Settings::get_setting('templates') as $template_id => $template_name) {

      $template = get_blog_details($template_id);
      if ($template === false) continue;
      $sites[] = $template->siteurl;

    } // end foreach;

    return $sites;

  } // end get_sites_list;

  /**
   * Get the image for a particular site ID
   * @param  integer $site_id The site ID to get the screenshot
   * @return string           The URL of the image
   */
  static function get_image($site_id) {

    $template = get_blog_details($site_id);

    if ($template === false) return;

    /**
     * Get the custom image if we have one in the database
     * @since  1.1.0
     */
    if ($image = wp_get_attachment_image_url(get_blog_option($site_id, 'template_img'), 'full')) {

      return $image;
    
    } else {

      // get filename
      $filename = sanitize_title($template->siteurl);
      $dir      = wp_upload_dir();

      // Check if exists
      $filepath = $dir['basedir']."/".$filename.'.jpg';
      
      // Return URL
      return file_exists($filepath) && 0 !== filesize($filepath)
        ? $dir['baseurl']."/".$filename.'.jpg' 
        : WP_Ultimo()->get_asset('no-preview.png');

    }

  } // end get_image;

  /**
   * DEPRECATED: This function takes the screenshots of the sites from time to time
   * 
   * @param  array $urls Array containing the URLs of sites
   */
  function get_thumbnails($urls, $overwrite = false) {

    _deprecated_function(__FUNCTION__, '1.9.4');

  } // end get_thumbnails

  /**
   * New async version of the screenshot crawler (works well with lists of templates with a count < 50)
   *
   * @param array $sites
   * @param boolean $overwrite
   * @return void
   */
  public function process_queue($sites = array(), $overwrite = false) {

    // Sets the Execution time to 10 minutes
    ini_set('max_execution_time', 5000);

    // Time it so we can keep an eye on performance
    $start = microtime(true);

    // Start ch
    $ch = array();

    // Let's chunk it
    $chunks = array_chunk($sites, $this->chunk_size);

    foreach($chunks as $chunk_id => $sites) {

      // Setting the queue and the options
      $mh = curl_multi_init();

      foreach($sites as $key => $url) {

        // get filename
        $filename    = sanitize_title($url);
        $dir         = wp_upload_dir();
        $filepath    = $dir['basedir']."/".$filename.'.jpg';
        $file_exists = file_exists($filepath);

        /**
         * Should we overwrite the existing files?
         */
        $overwrite = $overwrite ?: $file_exists && time() - filemtime($filepath) > (5 * DAY_IN_SECONDS);

        // Check if file exists, or if it is old
        if (!$file_exists || $overwrite) {

          $value = add_query_arg(array(
						'action' => 'capture',
						'url'    => $url,
						'key'    => $this->api_key,
					), 'https://services.wpultimo.com/');

          $ch[$key] = curl_init($value);

          curl_setopt($ch[$key], CURLOPT_NOBODY, false);
          curl_setopt($ch[$key], CURLOPT_HEADER, false);
          curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch[$key], CURLOPT_SSL_VERIFYPEER, true);
          
          curl_multi_add_handle($mh, $ch[$key]);

        } else {

          WU_Logger::add('screenshot-scraper', sprintf(__('Screenshot for site %s already existed. Skipping...', 'wp-ultimo'), $url));

        } // end if;

      } // end foreach;

      // Execute the call
      do {

        curl_multi_exec($mh, $running);
        curl_multi_select($mh);

      } while ($running > 0);

      // Get the responses
      foreach(array_keys($ch) as $key) {

        if (!isset($chunks[$chunk_id][$key])) {

          continue;

        } // end if;

        // get filename
        $url = $chunks[$chunk_id][$key];
        $filename = sanitize_title($url);
        $dir      = wp_upload_dir();
        $filepath = $dir['basedir']."/".$filename.'.jpg';

        $result = json_decode(curl_multi_getcontent($ch[$key]), true);

        /**
         * If image exists
         */
        if (isset($result['screenshot']['data'])) {

          $image = $result['screenshot']['data'];

          // Replace Things
          $image = str_replace(array('_','-'), array('/','+'), $image);

          $image = base64_decode($image);

          WU_Logger::add('screenshot-scraper', sprintf(__('Screenshot for site %s retrieved successfully.', 'wp-ultimo'), $url));

          // Save image
          file_put_contents($filepath, $image);

        } else {

          WU_Logger::add('screenshot-scraper', sprintf(__('Site %s was unaccessible. The scraper does not work on local environments.', 'wp-ultimo'), $url));

        } // end if;
        
        curl_multi_remove_handle($mh, $ch[$key]);

      } // end foreach;

      // Close all of this
      curl_multi_close($mh);

    } // end foreach;

    $end = microtime(true) - $start;

    WU_Logger::add('screenshot-scraper', sprintf(__('Scraping took %s seconds.', 'wp-ultimo'), $end));

  } // end process_queue;

} // end WU_Screenshot;

// Run the class
new WU_Screenshot;
