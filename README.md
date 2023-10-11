# YOURLS-OIDC
Code Repository: https://github.com/gsu-library/YOURLS-OIDC  
Forked From: https://github.com/joshp23/YOURLS-OIDC  
Author: Matt Brooks <mbrooks34@gsu.edu>  
Date Created: 2023-10-10  
License: [GPLv3](LICENSE)  
Version: 1.0.0

## Description
This plugin enables authentication against a generic OpenID Connect server in YOURLS. 

## Requirements
todo: set requirements in composer?
- YOURLS 7.4.0
- The [jumbojett/OpenID-Connect-PHP](https://github.com/jumbojett/OpenID-Connect-PHP) library
- `composer`, `php-curl`, `php-xml`, and `php-json`

## Installation
1. Download this repo and extract the `oidc` folder into `YOURLS/user/plugins/`
1. `cd` to the directory you just created
1. Run `composer install` in that directory to fetch the OIDC library
1. Define OIDC server parameters (see below)
1. configure OIDC, see below.
1. Enable in Admin

### Configuration
Config: `user/config.php` file.
```
// oidc server
define( 'OIDC_BASE_URL', 'https://keycloak.example.com/auth/realms/master/' );
define( 'OIDC_CLIENT_NAME', 'YOURLS' );
define( 'OIDC_CLIENT_SECRET', 'YOUR-SUPER-SECRET-HASH' );
// Option 1: link OIDC users to local YOURLS users
$oidc_profiles = array( 
	'YOURLS_UNAME' => 'sub attribute from OIDC provider',
);
// Option 2, all users on OIDC platform have YOURLS accounts. uses 'preferred_username' attribute
define( 'OIDC_BYPASS_YOURLS_AUTH', true );
```

## Attribution
Forked from [joshp23/YOURLS-OIDC](https://github.com/joshp23/YOURLS-OIDC) by [Joshua Panter](https://github.com/joshp23).
