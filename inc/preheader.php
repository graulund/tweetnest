<?php
	// TWEET NEST
	// Preheader
	
	$startTime = microtime(true);
	
	error_reporting(E_ALL ^ E_NOTICE);
	mb_language("uni");
	mb_internal_encoding("UTF-8");
	
	define("TWEET_NEST", "0.8"); // Version number
	
	require "config.php";
	if(empty($config['twitter_screenname'])){ header("Location: ./setup.php"); exit; }
	date_default_timezone_set($config['timezone']);
	define("DTP", $config['db']['table_prefix']);
	
	// Get the full path
	$fPath = explode(DIRECTORY_SEPARATOR, rtrim(__FILE__, DIRECTORY_SEPARATOR));
	array_pop($fPath); array_pop($fPath); // Remove inc/preheader.php
	$fPath = implode($fPath, "/");
	define("FULL_PATH", $fPath);
	
	// SmartyPants
	include "smartypants.php";
	
	// DB
	require "class.db.php";
	$db = new DB("mysql", $config['db']);
	if(!$delayedDB){ unset($config['db']['password']); }
	
	// Twitter API class
	require "class.twitterapi.php";
	$twitterApi = new TwitterApi();
	
	// Search
	require "class.search.php";
	$search = new TweetNestSearch();
	
	// Outputting various generic parts
	require "html.php";
	
	// Extensions
	require "extensions.php";
	
	$selectedDate      = array("y" => 0, "m" => 0, "d" => 0);
	$highlightedMonths = array();
	$filterMode        = "search";
	$home              = false;
	$jQueryVersion     = "1.4.2";
	
	// Getting database time offset
	$dbtQ = $db->query("SELECT TIME_FORMAT(NOW() - UTC_TIMESTAMP(), '%H%i') AS `diff`");
	$dbtR = $db->fetch($dbtQ);
	
	$dbOffset          = date("Z") - ($dbtR['diff'] * 36); if(!is_numeric($dbOffset)){ $dbOffset = 0; }
	$dbOffset          = $dbOffset >= 0 ? "+" . $dbOffset : $dbOffset; // Explicit positivity/negativity
	
	global $db, $twitterApi, $search, $selectedDate, $highlightedMonths, $filterMode, $home, $dbOffset;
	define("DB_OFFSET", $dbOffset);
	
	// String manipulation functions
	function s($str){ return htmlspecialchars($str, ENT_NOQUOTES); } // Shorthand
	function x($str, $attr = NULL){ return p(s($str), $attr); } // Shorthand
	function p($str, $attr = NULL, $force = false){ global $config; return ($config['smartypants'] || $force) ? SmartyPants($str, $attr) : $str; }
	
	// Numeric manipulation functions
	function pad($int){ if($int < 10){ return "0" . $int; } return $int; }
	
	// Consts
	define("PST_GZIP", (substr_count($_SERVER['HTTP_ACCEPT_ENCODING'], "gzip") > 0));
	define("PST_GZIP_S", (PST_GZIP ? ".gz" : ""));
	
	// Check for cURL
	if(!extension_loaded("curl")){
	    $prefix = (PHP_SHLIB_SUFFIX === "dll") ? "php_" : "";
	    if(!function_exists("dl") || !@dl($prefix . "curl." . PHP_SHLIB_SUFFIX)){
	        trigger_error("Unable to load the PHP cURL extension.", E_USER_ERROR);
	        exit;
	    }
	}
	
	// Author info
	$authorQ     = $db->query("SELECT * FROM `".DTP."tweetusers` WHERE `screenname` = '" . $db->s($config['twitter_screenname']) . "' LIMIT 1");
	$author      = $db->fetch($authorQ);
	$authorextra = unserialize($author['extra']);
	global $author, $authorextra;
	
	function getURL($url, $auth = NULL){
		// HTTP grabbin' cURL options, also exsecror
		$httpOptions = array(
			CURLOPT_FORBID_REUSE   => true,
			CURLOPT_POST           => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERAGENT      => "Mozilla/5.0 (Compatible; libCURL)",
			CURLOPT_VERBOSE        => false,
			CURLOPT_SSL_VERIFYPEER => false // Insecurity?
		);
		$conn = curl_init($url);
		$o    = $httpOptions;
		if(is_array($auth) && count($auth) == 2){
			$o[CURLOPT_USERPWD] = $auth[0] . ":" . $auth[1];
		}
		curl_setopt_array($conn, $o);
		$file = curl_exec($conn);
		if(!curl_errno($conn)){
			curl_close($conn);
			return $file;
		} else {
			$a = array(false, curl_errno($conn), curl_error($conn));
			curl_close($conn);
			return $a;
		}
	}
	
	function findURLs($str){
		$urls = array();
		preg_match_all("/\b(((https*:\/\/)|www\.).+?)(([!?,.\"\)]+)?(\s|$))/", $str, $m);
		foreach($m[1] as $url){
			$u = ($url[0] == "w") ? "http://" . $url : $url;
			$urls[$u] = parse_url($u);
		}
		return $urls;
	}
	
	function domain($host){
		if(empty($host) || !is_string($host)){ return false; }
		if(preg_match("/^[0-9\.]+$/", $host)){ return $host; } // IP
		if(substr_count($host, ".") <= 1){
			return $host;
		} else {
			$h = explode(".", $host, 2);
			return $h[1];
		}
	}
	
	// STUPE STUPE STUPEFY
	function stupefyRaw($str, $force = false){
		global $config;
		return ($config['smartypants'] || $force) ? str_replace(
			array("–", "—", "‘", "’", "“", "”", "…"),
			array("---", "--", "'", "'", "\"", "\"", "..."),
			$str) : $str;
	}
