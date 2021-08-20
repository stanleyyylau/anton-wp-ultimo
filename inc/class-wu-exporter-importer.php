<?php
/**
 * Exporter & Importer
 *
 * Handles exporting and importing of settings and configurations for WP Ultimo
 *
 * @author      WP_Ultimo
 * @category    Admin
 * @package     WP_Ultimo/Exporter
 * @since       1.7.4
 * @version     1.0.0
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_Exporter_Importer {

  /**
   * Holds the errors along the process
   *
   * @since 1.7.4
   * @var array
   */
  protected $errors = array();

  /**
   * Adds the hooks
   */
  public function __construct() {

    add_action('wu_page_wp-ultimo_load', array($this, 'generate_export'), 1);

    add_action('wu_page_wp-ultimo_load', array($this, 'add_notices'));

    add_action('wu_before_save_settings', array($this, 'handle_import'));

    add_action('wu_page_wp-ultimo_before_render', array($this, 'add_alert_message'));

  } // end construct;

  /**
   * Gets the export temp folder, where we'll place our files and the resulting .zip file
   *
   * @since 1.7.4
   * @return void
   */
  public function get_exporter_temp_folder() {

    switch_to_blog(get_current_site()->blog_id);

      $path = wp_upload_dir('wpue', true);

    restore_current_blog();

    return apply_filters('wu_get_exporter_temp_folder', $path['path']);

  } // end get_exporter_temp_folder;

  /**
   * Adds an aleet message when the user tries to import settings
   *
   * @since 1.8.0
   * @return void
   */
  public function add_alert_message() {

    if (isset($_GET['wu-tab']) && $_GET['wu-tab'] == 'export-import') { ?>

      <script type="text/javascript">
        (function($) {
          $(document).ready(function() {

            $('#_submit').on('click', function(e) {

              var $form = $('#mainform');

              e.preventDefault();

              wuswal({
                title: "<?php _e('Are you sure?', 'wp-ultimo'); ?>",
                text: "<?php _e('Importing settings and data sets will erase your current configuration to install the new versions. This action is not reversible!', 'wp-ultimo'); ?>",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "<?php _e('Yes, I\'m sure', 'wp-ultimo'); ?>",
                cancelButtonText: "<?php _e('Cancel', 'wp-ultimo'); ?>",
                closeOnConfirm: false,
                closeOnCancel: true,
                showLoaderOnConfirm: true,
                html: true,
              },
              function(isConfirm) {
                if (isConfirm) {
                  $form.submit();
                }
              });

            });
          });
        })(jQuery)
      </script>

    <?php } // end if;

  } // end add_alert_message;

  /**
   * Returns the strings describing the import process
   *
   * @since 1.8.0
   * @return string
   */
  public static function get_import_description() {

    return __('Select your bundle .zip file below and click "Save Changes" to import.', 'wp-ultimo') . '<br>' .
           __('If you want to select which data sets you would like to import, check the "Partial Import" below to see what options are available', 'wp-ultimo') . '<br><br>' .
           __('Please keep in mind that that if you choose to import plans, coupons and/or broadcasts, all the existing data on those categories will be wiped out from your install. This is IRREVERSIBLE, so be careful', 'wp-ultimo');

  } // end get_import_description:

  /**
   * Returns a list of our custom post types, it is filterable so add-ons can add their data as well
   *
   * @since  1.7.4
   * @return array
   */
  public static function get_data_post_types() {

    /**
     * Allow developers to add post types to the export results
     *
     * @since  1.7.4
     * @return array
     */
    return apply_filters('wu_exporter_importer_get_data_post_types', array(
      'wpultimo_plan'      => __('Plans', 'wp-ultimo'),
      'wpultimo_coupon'    => __('Coupons', 'wp-ultimo'),
      'wpultimo_broadcast' => __('Broadcasts', 'wp-ultimo'),
    ));

  } // end get_data_post_types;

  /**
   * Get all the import options available
   *
   * @since 1.7.4
   * @return array
   */
  public static function get_import_options() {

    $options = array_merge(
      array(
        'settings' => __('Settings', 'wp-ultimo'),
      ), self::get_data_post_types()
    );

    /**
     * Allow developers to add import options
     *
     * @since  1.7.4
     * @return array
     */
    return apply_filters('wu_exporter_importer_get_import_options', $options);

  } // end get_import_options;

  /**
   * Display notices on success and errors
   *
   * @since 1.7.4
   * @return void
   */
  public function add_notices() {

    if (isset($_GET['wu_export']) && $_GET['wu_export'] == 'error') {

      WP_Ultimo()->add_message(__('There was an error during the import process...', 'wp-ultimo'), 'error', true);

    } // end if;

    if (isset($_GET['wu_export']) && $_GET['wu_export'] == 'success') {

      WP_Ultimo()->add_message(__('Your settings were successfully imported!', 'wp-ultimo'), 'success', true);

    } // end if;

    return;

  } // end add_notices;

  /**
   * Get the export action link, this is used on the class-wu-admin-settings.php file
   *
   * @since 1.7.4
   * @return string
   */
  public static function get_export_link() {

    return wp_nonce_url(add_query_arg('wu_action', 'export_settings'), 'wu_exporting_settings');

  } // end get_export_link;

  /**
   * Handles the import process and unziping the file
   *
   * @since 1.7.4
   * @return void
   */
  public function handle_import() {

    if (!current_user_can('manage_network')) {

      return;

    } // end if;

    if (!isset($_FILES['wu-import-file'])) {

      return;

    } // end if;

    if (isset($_FILES['wu-import-file']['name']) && $_FILES['wu-import-file']['name']) {

      $accepted_types = array(
	      'zip'     => 'application/zip',
	      'gz|gzip' => 'application/x-gzip',
      );

      $file_info = wp_check_filetype(basename($_FILES['wu-import-file']['name']), $accepted_types);

      $filename = $_FILES["wu-import-file"]["name"];
      $source   = $_FILES["wu-import-file"]["tmp_name"];

      if (empty($file_info['type'])) {

        $message = "The file you are trying to upload is not a .zip file. Please try again.";

        /**
         * Clean up the temp folder, to prevent security treats
         * @since 1.7.4
         */
        $this->remove_temp_folder_entirely();

        wp_redirect(add_query_arg('wu_export', 'error'));

        exit;

      } // end if;

      $target_path = $this->get_exporter_temp_folder() . $filename . '.zip';

      if (@move_uploaded_file($source, $target_path)) {

        $zip = new ZipArchive();

        $x = $zip->open($target_path);

        if ($x === true) {

          /**
           * Instead of directly unpacking the files, let's search for a '/' folder to avoid log warnings...
           * @since 1.9.0
           */
          for ($i = 0; $i < $zip->numFiles; $i++) {

            if ($zip->getNameIndex($i) != '/') {

              $zip->extractTo($this->get_exporter_temp_folder(), array($zip->getNameIndex($i)));

            } // end if;

          } // end for;

          $zip->close();

          unlink($target_path);

          $this->clean_malicious_files();

          $this->process_imported_files();

        } // end if;

      } // end if;

    } // end if;

    /**
     * Clean up the temp folder, to prevent security treats
     * @since 1.7.4
     */
    $this->remove_temp_folder_entirely();

    wp_redirect(add_query_arg('wu_export', 'error'));

    exit;

  } // end handle_import;

  /**
   * Checks if we should process a given data set or not, this is used for partial imports
   *
   * @since 1.7.4
   * @param string $data_set
   * @return boolean
   */
  public function should_process_import($data_set) {

    if (isset($_REQUEST['import-partial'])) {

      return isset($_REQUEST['import-partial-options'][ $data_set ]);

    } // end if

    return true;

  } // end should_process_import;

  /**
   * Process the imported files, checking for partial imports
   *
   * @since 1.7.4
   * @return void
   */
  public function process_imported_files() {

    /**
     * Process importing the settings, only if that option was selected
     * @since 1.7.4
     */
    if ($this->should_process_import('settings')) {

      $this->process_import_settings();

    } // end if;

    /**
     * Process the import for each of the available post datasets
     * @since 1.7.4
     */
    foreach(self::get_data_post_types() as $post_type => $post_type_label) {

      if ($this->should_process_import($post_type)) {

        $this->process_import_post_type($post_type);

      } // end if;

    } // end foreach;

    /**
     * Clean up the temp folder, to prevent security treats
     * @since 1.7.4
     */
    $this->remove_temp_folder_entirely();

    /**
     * Do final redirect with success message
     * @since 1.7.4
     */
    wp_redirect(add_query_arg('wu_export', 'success'));

    exit;

  } // end process_imported_files;

  /**
   * Process the import of WP Ultimo Settings
   *
   * @since 1.7.4
   * @return boolean
   */
  public function process_import_settings() {

    $file = $this->get_exporter_temp_folder() . 'settings.json';

    if (file_exists($file)) {

      $settings = file_get_contents($file);

      $decoded_settings = json_decode($settings, true);

      if (json_last_error() === JSON_ERROR_NONE) {

        // Re-save the settings
        return WP_Ultimo()->saveOption('settings', $decoded_settings);

      } // end if

      return $this->errors[] = sprintf(__('There was an error importing the settings due to a JSON parser issue: %s', 'wp-ultimo'), json_last_error_msg());

    } // end if;

    return false;

  } // end import_settings;

  /**
   * Handles the import of XML files for post type data sets
   *
   * @since 1.7.4
   * @param string $xml_file_path
   * @return boolean
   */
  public function import_wordpress_xml_file($xml_file_path) {

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
      $class_wp_importer = __DIR__ .'/setup/importer/wordpress-importer.php';
      if ( file_exists( $class_wp_importer ) ) {
        require $class_wp_importer; }
    }

    if ( class_exists( 'WP_Import' ) ) {
      require_once __DIR__ .'/setup/importer/wpultimo-content-import.php';
      $wp_import = new wu_content_import();
      $wp_import->fetch_attachments = false;
      ob_start();
      $wp_import->import( $xml_file_path );
      $message = ob_get_clean();
      return array( $wp_import->check(),$message );
    }

    return false;

  } // end import_wordpress_xml_file;

  /**
   * Checks if we need to import a given post type and process the import
   *
   * @since 1.7.4
   * @param string $post_type
   * @return boolean
   */
  public function process_import_post_type($post_type) {

    global $wpdb;

    $file = $this->get_exporter_temp_folder() . $post_type . '.xml';

    if (file_exists($file)) {

      /**
       * Delete the existing posts on this entwork
       * TODO: maybe let admins decide?
       *
       * @since 1.7.4
       */
      $wpdb->query(
        $wpdb->prepare(
          "DELETE a,b,c
            FROM {$wpdb->prefix}posts a
            LEFT JOIN {$wpdb->prefix}term_relationships b
              ON (a.ID = b.object_id)
            LEFT JOIN {$wpdb->prefix}postmeta c
              ON (a.ID = c.post_id)
            WHERE a.post_type = %s;", $post_type
        )
      );

      return $this->import_wordpress_xml_file($file);

    } // end if;

    return false;

  } // end process_import_port_type;

  /**
   * Completely removes the temp folder recursively
   *
   * We do this to make sure no unallowed file is presetn on the install so we won't have any security breaches.
   *
   * @since 1.7.4
   * @param boolean $dir
   * @return void
   */
  public function remove_temp_folder_entirely($dir = false) {

    $dir = $dir ?: $this->get_exporter_temp_folder();

    if (!$dir) return;

    if (substr($dir, strlen($dir) - 1, 1) != '/') {

      $dir .= '/';

    } // end if;

    $files = glob($dir . '*', GLOB_MARK);

    foreach ($files as $file) {

      if (is_dir($file)) {

        $this->remove_temp_folder_entirely($file);

      } else {

        unlink($file);

      } // end if;

    } // end foreach;

    @rmdir($dir);

  } // end remove_temp_folder_entirely;

  /**
   * Export the plugin settings to a json file
   *
   * @since 1.7.4
   * @return void
   */
  public function export_settings_data() {

    $settings = WU_Settings::get_settings();

    $json = json_encode($settings);

    $handle = fopen($this->get_exporter_temp_folder() . 'settings.json', 'w');

      fwrite($handle, $json);

    fclose($handle);

    return true;

  } // end export_settings;

  /**
   * Access the export URL via CURL and get the results of the export
   *
   * This is not the most efficient way to do that, but it the only one that allow us not to duplicate the export function
   * inside WP Ultimo.
   *
   * @since 1.7.4
   * @param string $post_type_name
   * @return void
   */
  public function download_post_type_xml($post_type_name) {

    $download_url = get_admin_url(1, '/export.php?download=true&content=' . $post_type_name);

    return wp_remote_get($download_url, array(
      'timeout'   => 300,
      'stream'    => true,
      'sslverify' => false,
      'cookies'   => $_COOKIE,
      'filename'  => $this->get_exporter_temp_folder() . $post_type_name . '.xml',
    ));

  } // end download_post_type_xml;

  /**
   * Export the post-like datasets. The process will bounce here if there's an error.
   *
   * @since 1.7.4
   * @return void
   */
  public function export_post_data() {

    foreach (self::get_data_post_types() as $post_type => $post_type_label) {

      $response = $this->download_post_type_xml($post_type);

      if (is_wp_error($response)) {

        if (defined('WP_DEBUG') && WP_DEBUG) {

          $this->errors[] = $response->get_error_message();

        } // end if

        $this->errors[] = __('An error occurred while generating the export.', 'wp-ultimo');

        return;

      } // end if;

    } // end foreach;

  } // end export_post_data;

  /**
   * Generate the export
   *
   * @since 1.7.4
   * @return void
   */
  public function generate_export() {

    if (!isset($_REQUEST['wu_action']) || $_REQUEST['wu_action'] != 'export_settings' || ! wp_verify_nonce($_GET['_wpnonce'], 'wu_exporting_settings')) {

      return;

    } // end if;

    if (!current_user_can('manage_network')) {

      wp_die(__('You do not have permission to upload import files.', 'wp-ultimo'));

    } // end if;

    $this->export_post_data();

    $this->export_settings_data();

    $this->generate_export_zip();

    $this->send_response();

  } // end generate_export;

  /**
   * Decide what response to send, either the .zip file or error messages to display
   *
   * @since 1.7.4
   * @return void
   */
  public function send_response() {

    if (!empty($this->errors)) {

      foreach($this->errors as $error_messages) {

        WP_Ultimo()->add_message($error_messages, 'error', true);

      } // end foreach;

      return;

    } // end if;

    $this->serve_export_zip();

    $this->clean_temp_files();

    die;

  } // end send_response;

  /**
   * Generates the zip file inside the temp folder, copying the xmls and the json file. Bounces on case of errors.
   *
   * @since 1.7.4
   * @return void
   */
  public function generate_export_zip() {

    if (!extension_loaded('zip')) {
      $this->errors[] = __('WP Ultimo was unable to export the settings because the Zip extension isn\'t enabled in your host.', 'wp-ultimo');

      return;
    }

    $zip_file_path = $this->get_exporter_temp_folder() . '/wp-ultimo-export.zip';

    $zip_file = new ZipArchive();

    if (!$zip_file->open($zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {

      $this->errors[] = __('WP Ultimo was unable to create the zip file containing the export data.', 'wp-ultimo');

      return;

    } // end if;

    $options = array(
      'remove_path'        => './',
      'remove_all_path' => TRUE
    );

    $zip_file->addGlob($this->get_exporter_temp_folder() . '*.{xml,json}', GLOB_BRACE, $options);

    if (!$zip_file->status == ZIPARCHIVE::ER_OK) {

      $this->errors[] = __('WP Ultimo was unable to re-write the zip file containing the export data.', 'wp-ultimo');

      return;

    } // end if;

    $zip_file->close();

  } // end generate_export_zip;

  /**
   * Serves the zip file for download
   *
   * @since 1.7.4
   * @return void
   */
  public function serve_export_zip() {

    $zip_file = $this->get_exporter_temp_folder() . '/wp-ultimo-export.zip';

    $file_name = date('Y-m-d-') . basename($zip_file);

    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename=$file_name");
    header("Content-Length: " . filesize($zip_file));

    readfile($zip_file);

  } // end serve_export_zip;

  /**
   * Remove the temp files after the download starts
   *
   * @since 1.7.4
   * @return void
   */
  public function clean_temp_files() {

    foreach(glob($this->get_exporter_temp_folder() . '/*') as $filename) {

      unlink($filename);

    } // end foreach;

  } // end clean_temp_files;

  /**
   * Remove the .php files after the download starts
   *
   * @since 1.7.4
   * @return void
   */
  public function clean_malicious_files() {

    foreach(glob($this->get_exporter_temp_folder() . 'wp-ultimo-import/!*.{xml,json}') as $filename) {

      unlink($filename);

    } // end foreach;

  } // end clean_malicious_files;

} // end class WU_Exporter_Importer;

new WU_Exporter_Importer;