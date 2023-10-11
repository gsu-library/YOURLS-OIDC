<?php
/*
 * Plugin Name: OpenID Connect
 * Plugin URI: https://github.com/gsu-library/YOURLS-OIDC/
 * Description: Enables OpenID Connect user authentication.
 * Version: 1.0.0
 * Author: Georgia State University Library
 * Author URI: https://library.gsu.edu/
 */


defined('YOURLS_ABSPATH') || exit;


if(!class_exists('OIDC_Auth')) {
   /**
    * OIDC_Auth class
    */
   class OIDC_Auth {
      private $oidc;


      /**
       * Setup YOURLS hooks.
       */
      function __construct() {
         yourls_add_filter('is_valid_user', [$this, 'is_valid_user']);
         yourls_add_action('logout', [$this, 'logout']);
         yourls_add_action('login_form_top', [$this, 'login_form_top']);
         yourls_add_action('login_form_end', [$this, 'login_form_end']);
      }


      /**
       * Checks and loads OpenID configuration. Returns true if configuration is successful.
       *
       * @return bool
       */
      private function load_configuration() {
         // Autoloader.
         if(!file_exists(__DIR__ . '/vendor/autoload.php')) {
            //todo: throw yourls error
            return false;
         }

         // Check and load configuration.
         if(!defined('OIDC_PROVIDER_URL') ||
            empty(OIDC_PROVIDER_URL) ||
            !defined('OIDC_CLIENT_ID') ||
            empty(OIDC_CLIENT_ID) ||
            !defined('OIDC_CLIENT_SECRET')
         ) {
            //todo: throw yourls error
            return false;
         }

         require_once(__DIR__ . '/vendor/autoload.php');
         $this->oidc = new Jumbojett\OpenIDConnectClient(OIDC_PROVIDER_URL, OIDC_CLIENT_ID, OIDC_CLIENT_SECRET);

         // Set authentication methods.
         if(defined('OIDC_AUTH_METHODS') && !empty(OIDC_AUTH_METHODS)) {
            $this->oidc->setTokenEndpointAuthMethodsSupported(OIDC_AUTH_METHODS);
         }

         // Set redirect URL.
         if(defined('OIDC_REDIRECT_URL') && !empty(OIDC_REDIRECT_URL)) {
            $this->oidc->setRedirectURL(OIDC_REDIRECT_URL);
         }

         // Set scopes.
         if(defined('OIDC_SCOPES') && !empty(OIDC_SCOPES)) {
            $this->oidc->addScope(OIDC_SCOPES);
         }

         // Setup endpoint configuration.
         $configParams = [];

         if(defined('OIDC_TOKEN_ENDPOINT') && !empty(OIDC_TOKEN_ENDPOINT)) {
            $configParams['token_endpoint'] = OIDC_TOKEN_ENDPOINT;
         }
         if(defined('OIDC_USER_INFO_ENDPOINT') && !empty(OIDC_USER_INFO_ENDPOINT)) {
            $configParams['userinfo_endpoint'] = OIDC_USER_INFO_ENDPOINT;
         }
         if(defined('OIDC_LOGOUT_ENDPOINT') && !empty(OIDC_LOGOUT_ENDPOINT)) {
            $configParams['end_session_endpoint'] = OIDC_LOGOUT_ENDPOINT;
         }

         if(!empty($configParams)) {
            $this->oidc->providerConfigParam($configParams);
         }

         return true;
      }


      /**
       * Authorizes user through OIDC Connect client and validates the user. Returns a bool on
       * whether the user was validated.
       *
       * @param bool $is_valid   If the user is valid or not.
       * @return boolean
       */
      public function is_valid_user($is_valid) {
         if(!$this->load_configuration()) {
            return $is_valid;
         }

         // If already valid or is API return.
         if($is_valid || yourls_is_API()) {
            return $is_valid;
         }

         // Has all of the user/password combos at this point.
         global $yourls_user_passwords;

         // Authenticate and request user info
         $this->oidc->authenticate();
         $userInfo = $this->oidc->requestUserInfo();

         $user = $userInfo->user_name;
         if(array_key_exists($user, $yourls_user_passwords)) {
            yourls_set_user($user);
            yourls_redirect('.'); // clears URL parameters
            return true;
         }

         return false;
      }


      /**
       * Logs out the user from YOURLS and OpenID.
       *
       * @return void
       */
      public function logout() {
         if(!$this->load_configuration()) {
            return;
         }

         yourls_store_cookie('');

         // todo: save and pass token id?
         $this->oidc->signOut(null, YOURLS_SITE);
         echo '<pre>'.print_r($this->oidc, true).'</pre>';
      }


      /**
       * Hides the login form and displays error message (only seen if YOURLS account does not exist).
       *
       * @return void
       */
      public function login_form_top() {
         echo "<style>.error{display:none;}</style>";
         echo "<p class='error' style='display:inline !important;'>User account not found.</p>";
         echo "\n<!--\n";
      }


      /**
       * Closing tag to hid the login form.
       *
       * @return void
       */
      public function login_form_end() {
         echo "\n-->\n";
      }
   }

   $oidcAuth = new OIDC_Auth();
}


//todo: check to see if this ip flood check is need, I don't think so

// Largely unchanged: only checking auth against w/ cookies.
// yourls_add_filter( 'shunt_check_IP_flood', 'oidc_check_ip_flood' );
function oidc_check_ip_flood ( $ip ) {
   // don't touch API logic
   if ( yourls_is_API() ) return false;

   yourls_do_action( 'pre_check_ip_flood', $ip ); // at this point $ip can be '', check it if your plugin hooks in here

   // Raise white flag if installing or if no flood delay defined
   if(
      ( defined('YOURLS_FLOOD_DELAY_SECONDS') && YOURLS_FLOOD_DELAY_SECONDS === 0 ) ||
      !defined('YOURLS_FLOOD_DELAY_SECONDS') ||
      yourls_is_installing()
   )
      return true;

   // Don't throttle logged in users XXX and don't trigger OIDC login!
   if( yourls_is_private() && isset( $_COOKIE[ yourls_cookie_name() ] ) && yourls_check_auth_cookie() ) {
      yourls_store_cookie( YOURLS_USER );
      return true;
   }

   // Don't throttle whitelist IPs
   if( defined( 'YOURLS_FLOOD_IP_WHITELIST' ) && YOURLS_FLOOD_IP_WHITELIST ) {
      $whitelist_ips = explode( ',', YOURLS_FLOOD_IP_WHITELIST );
      foreach( (array)$whitelist_ips as $whitelist_ip ) {
         $whitelist_ip = trim( $whitelist_ip );
         if ( $whitelist_ip == $ip )
            return true;
      }
   }

   $ip = ( $ip ? yourls_sanitize_ip( $ip ) : yourls_get_IP() );

   yourls_do_action( 'check_ip_flood', $ip );

   global $ydb;
   $table = YOURLS_DB_TABLE_URL;

   $lasttime = $ydb->fetchValue( "SELECT `timestamp` FROM $table WHERE `ip` = :ip ORDER BY `timestamp` DESC LIMIT 1", array('ip' => $ip) );
   if( $lasttime ) {
      $now = date( 'U' );
      $then = date( 'U', strtotime( $lasttime ) );
      if( ( $now - $then ) <= YOURLS_FLOOD_DELAY_SECONDS ) {
         // Flood!
         yourls_do_action( 'ip_flood', $ip, $now - $then );
         yourls_die( yourls__( 'Too many URLs added too fast. Slow down please.' ), yourls__( 'Too Many Requests' ), 429 );
      }
   }

   return true;
}

?>
