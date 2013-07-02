<?php

/**
 * @file
 * A single location to store configuration for TwitterOAuth.
 */

define('CONSUMER_KEY', $config['consumer_key']);
define('CONSUMER_SECRET', $config['consumer_secret']);

$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://';
define('OAUTH_CALLBACK', $protocol . $_SERVER['HTTP_HOST'] . str_replace(basename($_SERVER['PHP_SELF']), 'callback.php', $_SERVER['PHP_SELF']));
