<?php
	// PONGSOCKET TWEET ARCHIVE
	// Upgrade tables to 0.8.1
	
	require "inc/preheader.php";
	$db->query("ALTER TABLE `".DTP."tweets` CHANGE `tweetid` `tweetid` VARCHAR(100) NOT NULL") or die($db->error());
	echo "Done! You can delete me now.\n";