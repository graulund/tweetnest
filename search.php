<?php
	// PONGSOCKET TWEET ARCHIVE
	// Search page
	
	require "inc/preheader.php";
	
	$path = rtrim($config['path'], "/");
	if(empty($_GET['q'])){ header("Location: " . $path . "/"); exit; }
	
	$month = false;
	if(!empty($_GET['m']) && !empty($_GET['y'])){
		$m = ltrim($_GET['m'], "0");
		if(is_numeric($m) && $m >= 1 && $m <= 12 && is_numeric($_GET['y']) && $_GET['y'] >= 2000){
			$month = true;
			$selectedDate = array("y" => $_GET['y'], "m" => $m, "d" => 0);
		}
	}
	
	$sort = $_COOKIE['tweet_sort_order'] == "time" ? "time" : "relevance"; // Sorting by time or default order (relevance)
	
	$tooShort = (strlen($_GET['q']) < $search->minWordLength || $search->minWordLength > 1 && strlen(trim($_GET['q'], "*")) <= 1);

	if(!$tooShort){
		$mq = $search->monthsQuery($_GET['q']);
		while($d = $db->fetch($mq)){ $highlightedMonths[$d['y'] . "-" . $d['m']] = $d['c']; }
		
		$results = $search->query(
			$_GET['q'],
			$sort,
			($month 
				? " AND YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) = '" . s($_GET['y']) . "' AND MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) = '" . s($m) . "'"
				: ""
			)
		);
	}
	
	$pageTitle   = "Searching for \"" . $_GET['q'] . "\"" . ($month ? " in " . date("F Y", mktime(1,0,0,$m,1,$_GET['y'])) : "");
	// Don't worry; above string is being sanitized later in the files
	$searchQuery = $_GET['q'];
	
	require "inc/header.php";
	$isRelv = $sort == "relevance";
	$isTime = $sort == "time";
?>
				<div id="sorter">Sort by <a href="<?php echo $path; ?>/sort?order=relevance&amp;from=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="first<?php if($isRelv){ ?> selected<?php } ?>"><?php if($isRelv){ ?><strong><?php } ?>Relevance<?php if($isRelv){ ?></strong><?php } ?></a><span> </span><a href="<?php echo $path; ?>/sort?order=time&amp;from=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="last<?php if($isTime){ ?> selected<?php } ?>"><?php if($isTime){ ?><strong><?php } ?>Time<?php if($isTime){ ?></strong><?php } ?></a></div>
<?php
	echo $tooShort ? "<div class=\"notweets\">Search query too short. Please type at least " . number_format($search->minWordLength) . " characters.</div>" : tweetsHTML($results, "search");
	require "inc/footer.php";