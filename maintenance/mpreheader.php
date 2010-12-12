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
	
	// Maintenance HTTP password
	if($web && !empty($config['maintenance_http_password'])){
		if(!empty($_SERVER['HTTP_AUTHORIZATION'])){
			list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(":", base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
		}
		if(empty($_SERVER['PHP_AUTH_USER'])){
			header((substr(php_sapi_name(), 0, 6) == "apache" ? "HTTP/1.0 " : "Status: ") . "401 Unauthorized");
			header('WWW-Authenticate: Basic realm="Tweet Archive Maintenance"');
			die("Unauthorized. Log in with your maintenance HTTP password found in your config.");
		} else {
			if(!(strtolower($_SERVER['PHP_AUTH_USER']) == strtolower($config['twitter_screenname']) && $_SERVER['PHP_AUTH_PW'] == $config['maintenance_http_password'])){
				header((substr(php_sapi_name(), 0, 6) == "apache" ? "HTTP/1.0 " : "Status: ") . "401 Unauthorized");
				header('WWW-Authenticate: Basic realm="Tweet Archive Maintenance"');
				die("Unauthorized. Wrong username/password.");
			}
		}
	}
	if($web && empty($config['maintenance_http_password'])){ die("No maintenance HTTP password. Please use the command line to run these scripts, or add a password in the <code>maintenance_http_password</code> section in <code>inc/config.php</code>."); } // Comment out this line to enable password-less HTTP maintenance access
	
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