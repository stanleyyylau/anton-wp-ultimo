<?php
/**
 * CPanel API Class
 *
 * Handles the addition of new plans
 * Based on the work of Adnan Hussain Turki <adnan@myphpnotes.tk>, from myPHPnotes
 *
 * @since       1.6.2
 * @author      Arindo Duque
 * @category    Admin
 * @package     WP_Ultimo/Hosting
 * @version     1.0.0
*/

if (!defined('ABSPATH')) {
  exit;
}

class WU_CPanel {

  private $host;
  private $port;
  private $username;
  private $password;
  private $log;
  private $cookie_file;
  private $curlfile;
  private $email_array;
  private $cpsess;
  private $homepage;
  private $ex_page; 

  /**
   * Creates the CPanel Objects
   *
   * @since 1.6.2
   * @param string  $username
   * @param string  $password
   * @param string  $host
   * @param integer $port
   * @param boolean $log
   */
  public function __construct($username, $password, $host, $port = 2083, $log = false) {
    
    // Generates the cookie file
    $this->generate_cookie();
      
    $this->host        = $host;
    $this->port        = $port;
    $this->username    = $username;
    $this->password    = $password;
    $this->log         = $log;
    $this->cookie_file = WU_Logger::get_logs_folder() . 'cpanel-cookie.log';
    
    // Signs up
    $this->sign_in();

  } // end construct;

  /**
   * Generate the Cookie File, that is used to make API requests to CPanel
   *
   * @since 1.6.2
   * @return void
   */
  public function generate_cookie() {

    WU_Logger::add('cpanel-cookie', '');

  } // end generate_cookie;

  /**
   * Logs error or success messages
   *
   * @since 1.6.2
   * @param string $message
   * @return void
   */
  public function log($message) {

    return WU_Logger::add('cpanel', $message);

  } // end log;

  /**
   * Sends the request to the CPanel API
   *
   * @since 1.6.2
   * @param string $url
   * @param array $params
   * @return void
   */
  private function request($url, $params = array()) {
    
    if ($this->log) {

      $curl_log = fopen($this->curlfile, 'a+');

    } // end if;

    if (!file_exists($this->cookie_file)) {

      try {

        fopen($this->cookie_file, "w");

      } catch(Exception $ex) {

        if (!file_exists($this->cookie_file)) {

          $this->log($ex . __('Cookie file missing.', 'wp-ultimo')); exit;

        } // end if;

      } // end catch;

    } else if (!is_writable($this->cookie_file)) {

      $this->log(__('Cookie file not writable', 'wp-ultimo')); exit;

    } // end if;
    
    $ch = curl_init();

    $curl_opts = array(
      CURLOPT_URL             => $url,
      CURLOPT_SSL_VERIFYPEER  => false,
      CURLOPT_SSL_VERIFYHOST  => false,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_FOLLOWLOCATION  => true,
      CURLOPT_COOKIEJAR       => realpath($this->cookie_file),
      CURLOPT_COOKIEFILE      => realpath($this->cookie_file),
      CURLOPT_HTTPHEADER      => array(
      CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:29.0) Gecko/20100101 Firefox/29.0',
        "Host: " . $this->host,
        "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
        "Accept-Language: en-US,en;q=0.5",
        "Accept-Encoding: gzip, deflate",
        "Connection: keep-alive",
        "Content-Type: application/x-www-form-urlencoded"
    ));

    if (!empty($params)) {

      $curl_opts[CURLOPT_POST] = true;
      $curl_opts[CURLOPT_POSTFIELDS] = $params;

    } // end if;

    if ($this->log) {

      $curl_opts[CURLOPT_STDERR] = $curl_log;
      $curl_opts[CURLOPT_FAILONERROR] = false;
      $curl_opts[CURLOPT_VERBOSE] = true;

    } // end if;

    curl_setopt_array($ch, $curl_opts);

    $answer = curl_exec($ch);

    if (curl_error($ch)) {

      echo curl_error($ch); exit;

    } // end if;

    curl_close($ch);

    if ($this->log) {

      fclose($curl_log);

    } // end if;
      
    return (@gzdecode($answer)) ? gzdecode($answer) : $answer;

  } // end request;
  
  /**
   * Signs in on the CPanel
   *
   * @since 1.6.2
   * @return void
   */
  private function sign_in() {

    $url  = 'https://'.$this->host.":".$this->port."/login/?login_only=1";
    $url .= "&user=".$this->username."&pass=".urlencode($this->password);

    $reply = $this->request($url);
    $reply = json_decode($reply, true);
      
    if (isset($reply['status']) && $reply['status'] == 1) {

      $this->cpsess   = $reply['security_token'];
      $this->homepage = 'https://'.$this->host.":".$this->port.$reply['redirect'];
      $this->ex_page  = 'https://'.$this->host.":".$this->port. "/{$this->cpsess}/execute/";

    } else {

      return $this->log(__('Cannot connect to your cPanel server : Invalid Credentials', 'wp-ultimo'));

    } // end if;

  } // end sign_in;

  /**
   * Executes API calls, taking the request to the right API version
   *
   * @since 1.6.2
   * @param string $api
   * @param string $module
   * @param string $function
   * @param array  $parameters
   * @return void
   */
  public function execute($api, $module, $function, array $parameters = array()) {

    switch ($api) {

      case 'api2':
        return $this->api2($module, $function, $parameters);
        break;
      case 'uapi':
        return $this->uapi($module, $function, $parameters);
        break;
      default:
        throw new Exception("Invalid API type : api2 and uapi are accepted", 1);                
        break;

    } // end switch;

  } // end execute;

  /**
   * Send the request if the API being used is the UAPI (newer version)
   *
   * @since 1.6.2
   * @param string $module
   * @param string $function
   * @param array  $parameters
   * @return void
   */
  public function uapi($module, $function, array $parameters = array()) {

    if (count($parameters) < 1) {

      $parameters = "";

    } else {

      $parameters = (http_build_query($parameters));

    } // end if;

    return json_decode($this->request($this->ex_page . $module . "/" . $function . "?" . $parameters));

  } // end uapi;

  /**
   * Send the request if the API being used is the API2 (older version)
   *
   * @since 1.6.2
   * @param string $module
   * @param string $function
   * @param array  $parameters
   * @return void
   */
  public function api2($module, $function, array $parameters = array()) {
      
    if (count($parameters) < 1) {

      $parameters = "";

    } else {

      $parameters = (http_build_query($parameters));

    } // end if;

    $url = "https://".$this->host.":".$this->port.$this->cpsess."/json-api/cpanel" .
      "?cpanel_jsonapi_version=2" .
      "&cpanel_jsonapi_func={$function}" .
      "&cpanel_jsonapi_module={$module}&" . $parameters;

    return json_decode($this->request($url,$parameters));

  } // end api2;

} // end class WU_CPanel;
