<?php
// TWEET NEST
// Load archive

error_reporting(E_ALL ^ E_NOTICE); ini_set("display_errors", true); // For easy debugging, this is not a production page
@set_time_limit(0);

require_once "mpreheader.php";
$p = "";

// LOGGING
// The below is not important, so errors surpressed
$f = @fopen("loadlog.txt", "a"); @fwrite($f, "Attempted load " . date("r") . "\n"); @fclose($f);

// Header
$pageTitle = "Loading tweets from archive";
require "mheader.php";

// Identifying user
if(!empty($_GET['userid']) && is_numeric($_GET['userid'])){
	$q = $db->query(
		"SELECT * FROM `".DTP."tweetusers` WHERE `userid` = '" . $db->s($_GET['userid']) .
			"' LIMIT 1"
	);
	if($db->numRows($q) > 0){
		$p = "user_id=" . preg_replace("/[^0-9]+/", "", $_GET['userid']);
	} else {
		dieout(l(bad("Please load the user first.")));
	}
} else {
	if(!empty($_GET['screenname'])){
		$q = $db->query(
			"SELECT * FROM `".DTP."tweetusers` WHERE `screenname` = '" . $db->s($_GET['screenname']) .
				"' LIMIT 1"
		);
		if($db->numRows($q) > 0){
			$p = "screen_name=" . preg_replace("/[^0-9a-zA-Z_-]+/", "", $_GET['screenname']);
		} else {
			dieout(l(bad("Please load the user first.")));
		}
	}
}

// Define import routines
function loadArchiveFile( $filepath ) {
	echo l( 'Found archive file ' . basename( $filepath ) . "\n");
	$fileLines = file( $filepath );
	// remove first line
	array_shift( $fileLines );
	return json_decode( implode( '', $fileLines ) );
}

function normalizeTweet( $tweet ) {
	foreach( $tweet as $k => $v ) {
		// replace empty objects with null
		if( is_object( $v ) && count( get_object_vars( $v ) ) === 0 ) {
			$tweet->$k = null;
		}
	}
	foreach( array( 'geo', 'coordinates', 'place', 'contributors' ) as $property ) {
		if( !property_exists( $tweet, $property ) ) {
			$tweet->$property = null;
		}
	}
	return $tweet;
}

function importTweets($p){
	global $twitterApi, $db, $config, $access, $search;
	$p = trim($p);
	if(!$twitterApi->validateUserParam($p)){ return false; }
	$tweets   = array();

	echo l("Importing:\n");

	// Do we already have tweets?
	$pd = $twitterApi->getUserParam($p);
	if($pd['name'] == "screen_name"){
		$uid        = $twitterApi->getUserId($pd['value']);
		$screenname = $pd['value'];
	} else {
		$uid        = $pd['value'];
		$screenname = $twitterApi->getScreenName($pd['value']);
	}
	$tiQ = $db->query("SELECT `tweetid` FROM `".DTP."tweets` WHERE `userid` = '" . $db->s($uid) . "' ORDER BY `id` DESC LIMIT 1");
	if($db->numRows($tiQ) > 0){
		$ti      = $db->fetch($tiQ);
		$sinceID = $ti['tweetid'];
	}

	echo l("User ID: " . $uid . "\n");
	$loadedArchives = is_readable( 'loadarchivelog.txt' ) ? file( 'loadarchivelog.txt' ) : array();

	// go through every file in archive folder
	foreach( glob( dirname( __FILE__ ) . '/../archive/[0-9][0-9][0-9][0-9]_[0-1][0-9].js' ) as $filename ) {
		if( in_array( basename( $filename ) . PHP_EOL, $loadedArchives ) ) {
			echo l("Found in archivelog -> Skipping file\n");
			continue;
		}

		$data = loadArchiveFile( $filename );
		if( !is_array( $data ) ) { dieout( l( bad( "Error: Could not parse JSON " ) ) ); }

		// Start parsing
		echo l("<strong>" . ($data ? count($data) : 0) . "</strong> tweets in this file\n");
		if(!empty($data)){
			echo l("<ul>");
			foreach($data as $i => $tweet){
				// List tweet
				echo l("<li>" . $tweet->id_str . " " . $tweet->created_at . "</li>\n");
				// Create tweet element and add to list
				$tweets[] = $twitterApi->transformTweet( normalizeTweet( $tweet ) );
			}
			echo l("</ul>");
			// Ascending sort, oldest first
			$tweets = array_reverse($tweets);
			$db->reconnect(); // Sometimes, DB connection times out during tweet loading. This is our counter-action
			foreach($tweets as $tweet){
				$q = $db->query($twitterApi->insertQuery($tweet));
				if(!$q){
					dieout(l(bad("DATABASE ERROR: " . $db->error())));
				}
				$text = $tweet['text'];
				$te   = $tweet['extra'];
				if(is_string($te)){ $te = @unserialize($tweet['extra']); }
				if(is_array($te)){
					// Because retweets might get cut off otherwise
					$text = (array_key_exists("rt", $te) && !empty($te['rt']) && !empty($te['rt']['screenname']) && !empty($te['rt']['text']))
						? "RT @" . $te['rt']['screenname'] . ": " . $te['rt']['text']
						: $tweet['text'];
				}
				$search->index($db->insertID(), $text);
			}
		}
		// reset tweets array
		$tweets = array();
		file_put_contents( 'loadarchivelog.txt', basename( $filename ) . PHP_EOL, FILE_APPEND );
	}
}

if($p){
	importTweets($p);
} else {
	$q = $db->query("SELECT * FROM `".DTP."tweetusers` WHERE `enabled` = '1'");
	if($db->numRows($q) > 0){
		while($u = $db->fetch($q)){
			$uid = preg_replace("/[^0-9]+/", "", $u['userid']);
			echo l("<strong>Trying to grab from user_id=" . $uid . "...</strong>\n");
			importTweets("user_id=" . $uid);
		}
	} else {
		echo l(bad("No users to import to!"));
	}
}

require "mfooter.php";