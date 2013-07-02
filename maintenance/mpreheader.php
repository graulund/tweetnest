<?php
	// TWEET NEST
	// Maintenance area preheader
	
	$web  = !empty($_SERVER['HTTP_HOST']);
	global $web;
	$ds   = preg_quote(DIRECTORY_SEPARATOR, "/");
	$dir  = str_replace(DIRECTORY_SEPARATOR, "/", preg_replace("/" . $ds . "[^" . $ds . "]*$/", "", __FILE__));
	require $dir . "/../inc/preheader.php";
	date_default_timezone_set($config['timezone']);
	$path = rtrim($config['path'], "/");
	
	// Are we running on 64-bit?
	if(!defined('IS64BIT')){
		define('IS64BIT', ((int)"9223372036854775807" > 2147483647));
	}

	// Valid screen names to log in with
	$screenNames = array(
		strtolower($config['twitter_screenname'])
	);
	if(array_key_exists('your_tw_screenname', $config)){
		$screenNames[] = strtolower($config['your_tw_screenname']);
	}

	$unauthorized = (substr(php_sapi_name(), 0, 6) == 'apache' ? 'HTTP/1.0 ' : 'Status: ') . '401 Unauthorized';
	
	// Maintenance HTTP password
	if($web && !empty($config['maintenance_http_password'])){
		if(!empty($_SERVER['HTTP_AUTHORIZATION'])){
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		}
		if(empty($_SERVER['PHP_AUTH_USER'])){
			header($unauthorized);
			header('WWW-Authenticate: Basic realm="Tweet Archive Maintenance"');
			die('Unauthorized. Log in with your Twitter screen name and the maintenance HTTP password found in your config.');
		} else {
			if(!(in_array(strtolower($_SERVER['PHP_AUTH_USER']), $screenNames) && $_SERVER['PHP_AUTH_PW'] == $config['maintenance_http_password'])){
				header($unauthorized);
				header('WWW-Authenticate: Basic realm="Tweet Archive Maintenance"');
				die('Unauthorized. Wrong username/password. Log in with your Twitter screen name and the maintenance HTTP password found in your config.');
			}
		}
	}

	// Comment out the below to enable password-less HTTP maintenance access
	if($web && empty($config['maintenance_http_password'])){
		die('No maintenance HTTP password set in the Tweet Nest configuration. Please use the command line to run these scripts, ' .
			'or add a password in the <code>maintenance_http_password</code> section in <code>inc/config.php</code>.');
	}
	
	function l($html){ // Display log line in correct way, depending on HTTP or not
		global $web;
		return $web ? str_replace("</li>\n", "</li>", $html) : strip_tags(str_replace("<li>", "<li> - ", $html));
	}
	
	function ls($html){
		global $web;
		return $web ? s($html) : $html; // Only encode HTML special chars if we're actually in a HTML doc
	}
	
	function good($html){
		return "<strong class=\"good\">" . $html . "</strong>";
	}
	
	function bad($html){
		return "<strong class=\"bad\">" . $html . "</strong>";
	}
	
	function dieout($html){
		echo $html;
		require "mfooter.php";
		die();
	}
	