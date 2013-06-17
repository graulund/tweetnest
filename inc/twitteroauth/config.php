<?php

/**
 * @file
 * A single location to store configuration.
 */

define('CONSUMER_KEY', 'roWBfb6P8ylWYEB0gkMNQ');
define('CONSUMER_SECRET', 'wnuS7qdCqAV0q2OE7n0hAuP8TkEPwCiCbW4r554');
define('OAUTH_CALLBACK', 'http://' . $_SERVER['SERVER_NAME'] . str_replace(basename($_SERVER['PHP_SELF']), "callback.php", $_SERVER['PHP_SELF']));