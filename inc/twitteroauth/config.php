<?php

/**
 * @file
 * A single location to store configuration for TwitterOAuth.
 */

define('CONSUMER_KEY', $config['consumer_key']);
define('CONSUMER_SECRET', $config['consumer_secret']);
define('OAUTH_CALLBACK', 'http://' . $_SERVER['SERVER_NAME'] . str_replace(basename($_SERVER['PHP_SELF']), "callback.php", $_SERVER['PHP_SELF']));