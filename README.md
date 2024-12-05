# YOURLS-OIDC
Code Repository: https://github.com/gsu-library/YOURLS-OIDC  
Forked From: https://github.com/joshp23/YOURLS-OIDC  
Author: Matt Brooks <mbrooks34@gsu.edu>  
Date Created: 2023-10-10  
License: [GPLv3](LICENSE)  
Version: 1.1.0

## Description
This plugin enables authentication against a generic OpenID Connect server in YOURLS. 

## Requirements
- PHP >= 7.4.0
- [jumbojett/OpenID-Connect-PHP](https://github.com/jumbojett/OpenID-Connect-PHP) library
- Composer
- PHP-cURL extension
- PHP-JSON extension

## Installation
1. Clone this repository into the `YOURLS_BASE_FOLDER/user/plugins/` folder.
1. Run `composer install` in that directory to fetch the OIDC library and its requirements.
1. Configure the plugin (see below).
1. Activate the plugin in the Manage Plugins menu.

## Configuration
Copy and configure the code below into the YOURLS configuration file: `YOURLS_BASE_FOLDER/user/config.php`. The provider URL and client ID are required and must not be empty. All other configuration constants are optional.

```php
// OpenID Connect configuration
const OIDC_PROVIDER_URL = '';
const OIDC_CLIENT_ID = '';
const OIDC_CLIENT_SECRET = '';
const OIDC_AUTH_METHODS = [];
const OIDC_SCOPES = [];
const OIDC_REDIRECT_URL = '';
const OIDC_TOKEN_ENDPOINT = '';
const OIDC_USER_INFO_ENDPOINT = '';
const OIDC_LOGOUT_ENDPOINT = '';
const OIDC_ERROR_MESSAGE = '';
const OIDC_USERNAME_FIELD = '';
```

## Contributors
[halkeye](https://github.com/halkeye)

## Attribution
Forked from [joshp23/YOURLS-OIDC](https://github.com/joshp23/YOURLS-OIDC) by [Joshua Panter](https://github.com/joshp23).
