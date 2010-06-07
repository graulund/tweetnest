<?php
	// PONGSOCKET TWEET ARCHIVE
	// Favorites page
	
	require "inc/preheader.php";
	
	$filterMode = "favorites";
	
	$month = false;
	if(!empty($_GET['m']) && !empty($_GET['y'])){
		$m = ltrim($_GET['m'], "0");
		if(is_numeric($m) && $m >= 1 && $m <= 12 && is_numeric($_GET['y']) && $_GET['y'] >= 2000){
			$month = true;
			$selectedDate = array("y" => $_GET['y'], "m" => $m, "d" => 0);
		}
	}
	
	$mq = $db->query("SELECT MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) AS m, YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) AS y, COUNT(*) as c FROM `".DTP."tweets` WHERE `".DTP."tweets`.`favorite` > 0 GROUP BY y, m ORDER BY y DESC, m DESC");
	while($d = $db->fetch($mq)){ $highlightedMonths[$d['y'] . "-" . $d['m']] = $d['c']; }
	
	$q = $db->query("SELECT `".DTP."tweets`.*, `".DTP."tweetusers`.`screenname`, `".DTP."tweetusers`.`realname`, `".DTP."tweetusers`.`profileimage` FROM `".DTP."tweets` LEFT JOIN `".DTP."tweetusers` ON `".DTP."tweets`.`userid` = `".DTP."tweetusers`.`userid` WHERE `".DTP."tweets`.`favorite` > 0" . ($month ? " AND YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) = '" . $db->s($_GET['y']) . "' AND MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) = '" . $db->s($m) . "'" : "") . " ORDER BY `".DTP."tweets`.`time` DESC");
	
	$pageHeader = "Favorite tweets" . ($month ? " from " . date("F Y", mktime(1,0,0,$m,1,$_GET['y'])) : "");
	
	require "inc/header.php";
	echo tweetsHTML($q);
	require "inc/footer.php";