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


if(!class_exists('Oidc_Auth')) {
   /**
    * Oidc_Auth class
    */
   class Oidc_Auth {
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
            trigger_error('Autoloader not found for YOURLS-OIDC plugin, run \'composer install\' in ' . __DIR__, E_USER_WARNING);
            return false;
         }

         // Check and load configuration.
         if(!defined('OIDC_PROVIDER_URL') ||
            empty(OIDC_PROVIDER_URL) ||
            !defined('OIDC_CLIENT_ID') ||
            empty(OIDC_CLIENT_ID)
         ) {
            trigger_error('Required configuration not found for YOURLS-OIDC, please see the readme.', E_USER_WARNING);
            return false;
         }

         $clientSecret = '';
         if(defined('OIDC_CLIENT_SECRET')) {
            $clientSecret = OIDC_CLIENT_SECRET;
         }

         require_once(__DIR__ . '/vendor/autoload.php');
         $this->oidc = new Jumbojett\OpenIDConnectClient(OIDC_PROVIDER_URL, OIDC_CLIENT_ID, $clientSecret);

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
      }


      /**
       * Hides the login form and displays error message (only seen if YOURLS account does not exist).
       *
       * @return void
       */
      public function login_form_top() {
         $message = 'User account not found.';

         if(defined('OIDC_ERROR_MESSAGE') && !empty(OIDC_ERROR_MESSAGE)) {
            $message = OIDC_ERROR_MESSAGE;
         }

         echo "<style>.error{display:none;}</style>";
         echo "<p class='error' style='display:inline !important;'>$message</p>";
         echo "\n<!--\n";
      }


      /**
       * Closing tag to hide the login form.
       *
       * @return void
       */
      public function login_form_end() {
         echo "\n-->\n";
      }
   }

   $oidcAuth = new Oidc_Auth();
}
