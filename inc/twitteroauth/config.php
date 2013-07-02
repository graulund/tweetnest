<?php

/**
 * @file
 * A single location to store configuration.
 */

define('CONSUMER_KEY', '');
define('CONSUMER_SECRET', '');
define('OAUTH_CALLBACK', 'http://' . $_SERVER['SERVER_NAME'] . str_replace(basename($_SERVER['PHP_SELF']), "callback.php", $_SERVER['PHP_SELF']));