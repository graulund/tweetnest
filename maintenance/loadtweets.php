<?php
	// TWEET NEST
	// Load tweets
	
	error_reporting(E_ALL ^ E_NOTICE); ini_set("display_errors", true); // For easy debugging, this is not a production page
	@set_time_limit(0);
	
	require_once "mpreheader.php";
	$p = "";
	
	// LOGGING
	// The below is not important, so errors surpressed
	$f = @fopen("loadlog.txt", "a"); @fwrite($f, "Attempted load " . date("r") . "\n"); @fclose($f);
	
	// Header
	$pageTitle = "Loading tweets";
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
	function totalTweets($p){
		global $twitterApi;
		$p = trim($p);
		if(!$twitterApi->validateUserParam($p)){ return false; }
		$data = $twitterApi->query("1/users/show.json?" . $p);
		if(is_array($data) && $data[0] === false){ dieout(l(bad("Error: " . $data[1] . "/" . $data[2]))); }
		return $data->statuses_count;
	}
	
	function importTweets($p){
		global $twitterApi, $db, $config, $access, $search;
		$p = trim($p);
		if(!$twitterApi->validateUserParam($p)){ return false; }
		$maxCount = 200;
		$tweets   = array();
		$sinceID  = 0;
		$maxID    = 0;
		
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
		$tiQ = $db->query("SELECT `tweetid` FROM `".DTP."tweets` WHERE `userid` = '" . $db->s($uid) . "' ORDER BY `time` DESC LIMIT 1");
		if($db->numRows($tiQ) > 0){
			$ti      = $db->fetch($tiQ);
			$sinceID = $ti['tweetid'];
		}
		
		echo l("User ID: " . $uid . "\n");
		
		// Find total number of tweets
		$total = totalTweets($p);
		if($total > 3200){ $total = 3200; } // Due to current Twitter limitation
		$pages = ceil($total / $maxCount);
		
		echo l("Total tweets: <strong>" . $total . "</strong>, Approx. page total: <strong>" . $pages . "</strong>\n");
		if($sinceID){
			echo l("Newest tweet I've got: <strong>" . $sinceID . "</strong>\n");
		}
		
		$page = 1;
		
		// Retrieve tweets
		do {
			// Determine path to Twitter timeline resource
			$path =	"1/statuses/user_timeline.json?" . $p . // <-- user argument
					"&include_rts=true&include_entities=true&count=" . $maxCount .
					($sinceID ? "&since_id=" . $sinceID : "") . ($maxID ? "&max_id=" . $maxID : "");
			// Announce
			echo l("Retrieving page <strong>#" . $page . "</strong>: <span class=\"address\">" . ls($path) . "</span>\n");
			// Get data
			$data = $twitterApi->query($path);
			// Drop out on connection error
			if(is_array($data) && $data[0] === false){ dieout(l(bad("Error: " . $data[1] . "/" . $data[2]))); }
			
			// Start parsing
			echo l("<strong>" . ($data ? count($data) : 0) . "</strong> new tweets on this page\n");
			if(!empty($data)){
				echo l("<ul>");
				foreach($data as $i => $tweet){
					// Shield against duplicate tweet from max_id
					if(!IS64BIT && $i == 0 && $maxID == $tweet->id_str){ unset($data[0]); continue; }
					// List tweet
					echo l("<li>" . $tweet->id_str . " " . $tweet->created_at . "</li>\n");
					// Create tweet element and add to list
					$tweets[] = $twitterApi->transformTweet($tweet);
					// Determine new max_id
					$maxID    = $tweet->id_str;
					// Subtracting 1 from max_id to prevent duplicate, but only if we support 64-bit integer handling
					if(IS64BIT){
						$maxID = (int)$tweet->id - 1;
					}
				}
				echo l("</ul>");
			}
			$page++;
		} while(!empty($data));
		
		if(count($tweets) > 0){
			// Ascending sort, oldest first
			$tweets = array_reverse($tweets);
			echo l("<strong>All tweets collected. Reconnecting to DB...</strong>\n");
			$db->reconnect(); // Sometimes, DB connection times out during tweet loading. This is our counter-action
			echo l("Inserting into DB...\n");
			$error = false;
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
			echo !$error ? l(good("Done!\n")) : "";
		} else {
			echo l(bad("Nothing to insert.\n"));
		}
		
		// Checking personal favorites -- scanning all
		echo l("\n<strong>Syncing favourites...</strong>\n");
		// Resetting these
		$favs  = array(); $maxID = 0; $sinceID = 0; $page = 1;
		do {
			$path = "1/favorites.json?" . $p . "&count=" . $maxCount . ($maxID ? "&max_id=" . $maxID : "");
			echo l("Retrieving page <strong>#" . $page . "</strong>: <span class=\"address\">" . ls($path) . "</span>\n");
			$data = $twitterApi->query($path);
			if(is_array($data) && $data[0] === false){ dieout(l(bad("Error: " . $data[1] . "/" . $data[2]))); }
			echo l("<strong>" . ($data ? count($data) : 0) . "</strong> total favorite tweets on this page\n");
			if(!empty($data)){
				echo l("<ul>");
				foreach($data as $i => $tweet){
					if(!IS64BIT && $i == 0 && $maxID == $tweet->id_str){ unset($data[0]); continue; }
					if($tweet->user->id_str == $uid){
						echo l("<li>" . $tweet->id_str . " " . $tweet->created_at . "</li>\n");
						$favs[] = $tweet->id_str;
					}
					$maxID = $tweet->id_str;
					if(IS64BIT){
						$maxID = (int)$tweet->id - 1;
					}
				}
				echo l("</ul>");
			}
			echo l("<strong>" . count($favs) . "</strong> favorite own tweets so far\n");
			$page++;
		} while(!empty($data));
		
		// Blank all favorites
		$db->query("UPDATE `".DTP."tweets` SET `favorite` = '0'");
		// Insert favorites into DB
		$db->query("UPDATE `".DTP."tweets` SET `favorite` = '1' WHERE `tweetid` IN ('" . implode("', '", $favs) . "')");
		echo l(good("Updated favorites!"));
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
