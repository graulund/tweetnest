<?php
	// PONGSOCKET TWEET ARCHIVE
	// Cumulative SQL upgrades across versions. Run this when you upgrade Tweet Nest!
	
	require "inc/preheader.php";
	$db->query("ALTER TABLE `".DTP."tweets` CHANGE `tweetid` `tweetid` VARCHAR(100) NOT NULL") or die($db->error());
	$db->query("ALTER TABLE `".DTP."tweets` ADD UNIQUE ( `tweetid` )") or die($db->error());
    $db->query('ALTER TABLE `'.DTP.'tweets` CHANGE `text` `text` VARCHAR(510) NOT NULL') or die($db->error());
    $db->query('ALTER TABLE `'.DTP.'tweetusers` ADD UNIQUE ( `userid` )') or die($db->error());
	echo "Done! You can delete upgrade.php now.\n";