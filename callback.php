<?php
	/**
	* @file
	* Take the user when they return from Twitter. Get access tokens.
	* Verify credentials and redirect to based on response from Twitter.
	*/

	// Start session
	session_start();

	// If we've defined the consumer key and secret in a session, get 'em:
	if(
		(
			!isset($config) || !is_array($config) ||
			!isset($config['consumer_key']) || !isset($config['consumer_secret'])
		) &&
		isset($_SESSION['entered_consumer_key']) &&
		isset($_SESSION['entered_consumer_secret'])
	){
		if(!isset($config) || !$config){ $config = array(); }
		$config['consumer_key']    = $_SESSION['entered_consumer_key'];
		$config['consumer_secret'] = $_SESSION['entered_consumer_secret'];
	}

	// Load libraries
	require_once 'inc/twitteroauth/twitteroauth.php';
	require_once 'inc/twitteroauth/config.php';

	// Function to go back to the correct place in the user's current workflow
	function goBackToTweetNestSetup(){
		if($_SESSION['redirect_source'] == 'authorize'){
			header('Location: ./authorize.php');
		} else {
			header('Location: ./setup.php');
		}
	}

	// If the oauth_token is old redirect to the connect page
	if(isset($_REQUEST['oauth_token']) && $_SESSION['oauth_token'] !== $_REQUEST['oauth_token']){
		$_SESSION['oauth_status'] = 'oldtoken';
		$_SESSION['status'] = 'try again';
		goBackToTweetNestSetup();
		exit;
	}

	if(!defined('CONSUMER_KEY') || !CONSUMER_KEY || !defined('CONSUMER_SECRET') || !CONSUMER_SECRET){
	    die('<strong>Consumer key and/or secret were not specified.</strong> Please check your configuration file, ' .
	        'or if you were setting up Tweet Nest, please provide these values before authenticating. ' .
	        'You can create them at <a href="//dev.twitter.com/apps">dev.twitter.com</a>.');
	}

	// Create TwitterOAuth object with app key/secret and token key/secret from default phase
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);

	// Request access tokens from Twitter
	$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

	// Save the access tokens
	$_SESSION['access_token'] = $access_token;

	// Remove no longer needed request tokens
	unset($_SESSION['oauth_token']);
	unset($_SESSION['oauth_token_secret']);

	// If HTTP response is 200, the user has been verified
	if($connection->http_code == 200){
		$_SESSION['status'] = 'verified';
	} else {
		$_SESSION['status'] = 'not verified';
	}

	// Send them back to the setup page regardless of what happens.
	goBackToTweetNestSetup();
