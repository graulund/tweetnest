<?php
	// PONGSOCKET TWEET ARCHIVE
	// Month page
	
	require "inc/preheader.php";
	
	$m = ltrim($_GET['m'], "0");
	if(!is_numeric($m) || !is_numeric($_GET['y']) || (is_numeric($m) && ($m > 12 || $m < 1)) || (is_numeric($_GET['y']) && $_GET['y'] < 2000)){ errorPage("Invalid year or month"); }
	
	$q = $db->query("SELECT `".DTP."tweets`.*, `".DTP."tweetusers`.`screenname`, `".DTP."tweetusers`.`realname`, `".DTP."tweetusers`.`profileimage` FROM `".DTP."tweets` LEFT JOIN `".DTP."tweetusers` ON `".DTP."tweets`.`userid` = `".DTP."tweetusers`.`userid` WHERE YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) = '" . $db->s($_GET['y']) . "' AND MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) = '" . $db->s($m) . "' ORDER BY `".DTP."tweets`.`time` DESC");
	
	$selectedDate = array("y" => $_GET['y'], "m" => $m, "d" => 0);
	$pageTitle    = date("F Y", mktime(1,0,0,$m,1,$_GET['y']));
	$preBody      = displayDays($_GET['y'], $m);
	
	require "inc/header.php";
	echo tweetsHTML($q, "month");
	require "inc/footer.php";