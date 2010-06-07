<?php
	// PONGSOCKET TWEET ARCHIVE
	// Switch sort method
	
	setcookie("tweet_sort_order", $_GET['order'] == "time" ? "time" : "", time()+60*60*24*365);
	header("Location: " . 
		((!empty($_GET['from']) && !preg_match("/^[a-z]:\//", $_GET['from']) && substr_count($_GET['from'], "\n") <= 0)
		? $_GET['from']
		: "./")
	);