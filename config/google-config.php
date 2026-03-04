<?php
/**
 * Google OAuth Configuration
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Google OAuth Credentials
define('GOOGLE_CLIENT_ID', '126175981408-f61peld0bnknh399mug92lol5mrqm33g.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', APP_URL . '/callback.php');

// Google API Scopes
define('GOOGLE_SCOPES', [
    'openid',
    'https://www.googleapis.com/auth/userinfo.email',
    'https://www.googleapis.com/auth/userinfo.profile'
]);

// Google API Endpoints
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USERINFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

// OAuth Settings
define('GOOGLE_ACCESS_TYPE', 'offline');
define('GOOGLE_PROMPT', 'consent');
define('GOOGLE_RESPONSE_TYPE', 'code');

// Email Configuration for App Password
define('GOOGLE_EMAIL', 'exchangebridge.bd@gmail.com');
define('GOOGLE_APP_PASSWORD', 'kjjg feoj ijwv fwwr');

// Session Keys
define('GOOGLE_STATE_KEY', 'google_oauth_state');
define('GOOGLE_TOKEN_KEY', 'google_oauth_token');

// Enable Google OAuth
define('GOOGLE_OAUTH_ENABLED', true);

// Auto-create users from Google OAuth
define('GOOGLE_AUTO_CREATE_USER', true);

// Default role for Google users
define('GOOGLE_DEFAULT_ROLE', 'user');
?>
