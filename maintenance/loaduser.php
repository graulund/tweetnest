<?php
	// TWEET NEST
	// Load user
	
	require 'mpreheader.php';
	$pageTitle = 'Loading user info';
	require 'mheader.php';

	// Check for authentication
	if(!isset($config['consumer_key']) || !isset($config['consumer_secret'])){
		die("Consumer key and secret not found. These are required for authentication to Twitter. \n" .
			"Please point your browser to the authorize.php file to configure these.\n");
	}

	// Continue...
	echo l("Connecting & parsing...\n");
	$path = 'account/verify_credentials';
	echo l('Connecting to: <span class="address">' . ls($path) . "</span>\n");
	
	$data = $twitterApi->query($path);

	if($data){
		$extra = array(
			'created_at' => (string) $data->created_at,
			'utc_offset' => (string) $data->utc_offset,
			'time_zone'  => (string) $data->time_zone,
			'lang'       => (string) $data->lang,
			'profile_background_color'     => (string) $data->profile_background_color,
			'profile_text_color'           => (string) $data->profile_text_color,
			'profile_link_color'           => (string) $data->profile_link_color,
			'profile_sidebar_fill_color'   => (string) $data->profile_sidebar_fill_color,
			'profile_sidebar_border_color' => (string) $data->profile_sidebar_border_color,
			'profile_background_image_url' => (string) $data->profile_background_image_url,
			'profile_background_tile'      => (string) $data->profile_background_tile
		);
		echo l("Checking...\n");
		$db->query("DELETE FROM `".DTP."tweetusers` WHERE `userid` = '0'"); // Getting rid of empty users created in error
		$q = $db->query("SELECT * FROM `".DTP."tweetusers` WHERE `userid` = '" . $db->s($data->id_str) . "' LIMIT 1");
		if($db->numRows($q) <= 0){
			$iq = "INSERT INTO `".DTP."tweetusers` (`userid`, `screenname`, `realname`, `location`, `description`, `profileimage`, `url`, `extra`, `enabled`) VALUES ('" . $db->s($data->id_str) . "', '" . $db->s($data->screen_name) . "', '" . $db->s($data->name) . "', '" . $db->s($data->location) . "', '" . $db->s($data->description) . "', '" . $db->s($data->profile_image_url) . "', '" . $db->s($data->url) . "', '" . $db->s(serialize($extra)) . "', '1');";
		} else {
			$iq = "UPDATE `".DTP."tweetusers` SET `screenname` = '" . $db->s($data->screen_name) . "', `realname` = '" . $db->s($data->name) . "', `location` = '" . $db->s($data->location) . "', `description` = '" . $db->s($data->description) . "', `profileimage` = '" . $db->s($data->profile_image_url) . "', `url` = '" . $db->s($data->url) . "', `extra` = '" . $db->s(serialize($extra)) . "' WHERE `userid` = '" . $db->s($data->id_str) . "' LIMIT 1";
		}
		echo l("Updating...\n");
		$q = $db->query($iq);
		echo $q ? l(good('Done!')) : l(bad('DATABASE ERROR: ' . $db->error()));
	} else { echo l(bad('No data! Try again later.')); }
	
	require 'mfooter.php';