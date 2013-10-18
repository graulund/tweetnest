<?php
	// PONGSOCKET TWEET ARCHIVE
	// HTML output templates
	
	function displayMonths($tabs = 4){
		global $db, $selectedDate, $highlightedMonths, $filterMode, $home, $config;
		$months = array(); $max = 0; $total = 0; $amount = 0;
		$x      = str_repeat("\t", $tabs); $y = str_repeat("\t", $tabs+1);
		$url    = explode("?", $_SERVER['REQUEST_URI'], 2);
		$path   = s(rtrim($config['path'], "/"));
		$q = $db->query("SELECT MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) AS m, YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) AS y, COUNT(*) AS c FROM `".DTP."tweets` GROUP BY y, m ORDER BY y DESC, m DESC");
		while($r = $db->fetch($q)){
			$months[] = $r;
			if($r['c'] > $max){ $max = $r['c']; }
			$total += $r['c'];
			$amount++;
		}
		$searching = $home ? false : (count($highlightedMonths) > 0);
		$s = "<ul id=\"months\">\n";
		if(!$home){
			$s .= $y . "<li class=\"home\"><a href=\"" . $path . "/\"><span class=\"m" . ($searching ? " ms\"><span class=\"a\">" : "\">") . "Recent tweets" . ($searching ? "</span><span class=\"b\"> (exit " . s($filterMode) . ")</span>" : "") . "</span></a></li>\n";
		}
		if(!$searching){
			$s .= $y . "<li class=\"fav\"><a href=\"" . $path . "/favorites\"><span class=\"m\">Favorites</span></a></li>\n";
		}
		if(count($highlightedMonths) > 0 && (!empty($_GET['m']) && !empty($_GET['y']))){
			// Generating URL
			$g    = $_GET;
			unset($g['m']);
			unset($g['y']);
			$qsa  = array();
			foreach($g as $k => $v){
				$qsa[] = $k . "=" . $v;
			}
			$qs   = implode("&", $qsa);
			$cn   = ($filterMode == "favorites") ? "fav" : "search";
			$res  = ($filterMode == "favorites") ? "favorites" : "results";
			$s   .= $y . "<li class=\"" . $cn . "\"><a href=\"" . s($url[0] . ($qs ? "?" . $qs : "")) . "\"><span class=\"m\">All " . $res . "</span></a></li>\n";
		}
		foreach($months as $m){
			$c  = ""; $cc = 0;
			if($selectedDate['y'] == $m['y'] && $selectedDate['m'] == $m['m']){ $c .= " selected"; }
			if(array_key_exists($m['y'] . "-" . $m['m'], $highlightedMonths)){
				$c   .= " highlighted";
				$cc   = $highlightedMonths[$m['y'] . "-" . $m['m']];
				// Generating search URL
				$g    = $_GET; $g['m'] = $m['m']; $g['y'] = $m['y'];
				$qsa  = array();
				foreach($g as $k => $v){
					$qsa[] = $k . "=" . $v;
				}
				$qs   = implode("&", $qsa);
				$pURL = $url[0] . "?" . $qs;
			}
			$c  = trim($c);
			$s .= $y . "<li" . ($c ? " class=\"" . $c . "\"" : "") . ">" .
			"<a href=\"" . ($cc > 0 ? s($pURL) : $path . "/" . s($m['y']) . "/" . s(pad($m['m']))) . "\">" .
			"<span class=\"m\">" . date("F Y", mktime(1,0,0,$m['m'],1,$m['y'])) . "</span>" .
			"<span class=\"n\"> " . number_format($m['c']) . ($cc > 0 ? " <strong>(" . number_format($cc) . ")</strong>" : "") . 
			"</span><span class=\"p\" style=\"width:" . round((($m['c']/$max)*100), 2) . "%\"></span></a></li>\n";
		}
		$s .= $y . "<li class=\"meta\">" . number_format($total) . " total tweets" . ($amount > 0 ? " <!-- approx. " . round(number_format($total / $amount), 2) . " monthly -->" : "") . "</li>\n" . $x . "</ul>\n";
		return $s;
	}
	
	function displayDays($year, $month, $tabs = 3){
		global $db, $selectedDate, $config;
		if(!is_numeric($month) || !is_numeric($year) || (is_numeric($month) && ($month > 12 || $month < 1)) || (is_numeric($year) && $year < 2000)){ return false; }
		$days   = array(); $max = 0; $total = 0;
		$date   = getdate(mktime(1,0,0, $month, 1, $year)); $wd = $date['wday'];
		$x      = str_repeat("\t", $tabs); $y = str_repeat("\t", $tabs+1);
		$_year  = "YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . "))";
		$_month = "MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . "))";
		$path   = s(rtrim($config['path'], "/"));
		$q = $db->query("SELECT DAY(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) as d, " . $_month . " AS m, " . $_year . " AS y, `type`, COUNT(*) AS c FROM `".DTP."tweets` WHERE " . $_year . " = '" . $db->s($year) . "' AND " . $_month . " = '" . $db->s($month) . "' GROUP BY y, m, d, `type` ORDER BY y ASC, m ASC, d ASC, `type` ASC");
		while($r = $db->fetch($q)){
			if(!array_key_exists($r['d'], $days)){
				$days[$r['d']] = array("total" => 0);
			}
			$days[$r['d']]['total'] += $r['c'];
			$days[$r['d']]['c' . $r['type']] = $r['c'];
			if($days[$r['d']]['total'] > $max){ $max = $days[$r['d']]['total']; }
			$total += $r['c'];
		}
		$daysInMonth = getDaysInMonth($month, $year);
		$s = "<div id=\"days\" class=\"days-" . s($daysInMonth) . "\"><div class=\"dr\">\n";
		for($i = 0; $i < $daysInMonth; $i++){
			$today = ($selectedDate['y'] == $year && $selectedDate['m'] == $month && $selectedDate['d'] == ($i+1));
			if(array_key_exists($i+1, $days)){
				$d  = $days[$i+1];
				$s .= $y . "<div class=\"d\"><a title=\"" . s($d['total']) . " tweet" . (($d['total'] == 1) ? "" : "s") .
				(!empty($d['c1']) ? ", " . s($d['c1']) . " repl" . ($d['c1'] == 1 ? "y" : "ies") : "") .
				(!empty($d['c2']) ? ", " . s($d['c2']) . " retweet" . ($d['c2'] == 1 ? "" : "s") : "") .
				"\" href=\"" . $path . "/" . s($year) . "/" . s(pad($month)) . "/" . s(pad($i+1)) . "\">" .
				"<span class=\"p\" style=\"height:" . round((($d['total']/$max)*250), 2) . "px\">" .
				"<span class=\"n\">" . ($d['total'] != 1 ? number_format($d['total']) : "") . "</span>" . 
				(!empty($d['c1']) ? "<span class=\"r\" style=\"height:" . round((($d['c1']/$max)*250), 2) . "px\"></span>" : "") . 
				(!empty($d['c2']) ? "<span class=\"rt\" style=\"height:" . round((($d['c2']/$max)*250), 2) . "px\"></span>" : "") . 
				"</span><span class=\"m" . (($wd == 0 || $wd == 6) ? " mm" : "") . ($today ? " ms" : "") . "\">" . 
				($today ? "<strong>" : "") . s($i+1) . ($today ? "</strong>" : "") . 
				"</span></a></div>\n";
			} else {
				$s .= $y . "<div class=\"d\"><a href=\"" . $path . "/" . s($year) . "/" . s(pad($month)) . "/" . s(pad($i+1)) . "\">" .
				"<span class=\"z\">0</span><span class=\"m" . (($wd == 0 || $wd == 6) ? " mm" : "") . ($today ? " ms" : "") . "\">" .
				($today ? "<strong>" : "") . s($i+1) . ($today ? "</strong>" : "") . 
				"</span></a></div>\n";
			}
			$wd = ($wd == 6) ? 0 : $wd + 1;
		}
		$s .= $x . "</div></div>\n";
		return $s;
	}
	
	function tweetHTML($tweet, $tabs = 4){
		global $twitterApi;
		$tweetextra = array(); $tweetplace = array();
		if(!empty($tweet['extra'])){
			@$tweetextra = unserialize($tweet['extra']);
		}
		if(!empty($tweet['place'])){
			$tweetplace = unserialize(str_replace("O:16:\"SimpleXMLElement\"", "O:8:\"stdClass\"", $tweet['place']));
		}
		$rt = (is_array($tweetextra) && array_key_exists("rt", $tweetextra) && !empty($tweetextra['rt']));
		$t  = str_repeat("\t", $tabs);
		if($rt){ $retweet = $tweetextra['rt']; }
		
		// Entities
		$htmlcontent = s(stupefyRaw($rt ? $twitterApi->entityDecode($retweet['text']) : $tweet['text']), ENT_NOQUOTES);
		$entities    = ($rt ? $tweetextra['rt']['extra']['entities'] : $tweetextra['entities']);
		
		if(areEntitiesEmpty($entities)){
			$htmlcontent = linkifyTweet($htmlcontent);
		} else {
			// Known issue: Entities have faulty indices when parsing a preprocessed $htmlcontent,
			// where the preprocessing creates more characters than were there previously.
			// For example ">" into "&gt;" and an em dash into "---".

			$htmlcontent = linkifyTweet(entitifyTweet($htmlcontent, $entities), true);
		}
		
		$inReplyToTweetId = '';
		
		if($tweetextra && !empty($tweetextra['in_reply_to_status_id'])){
			if(!empty($tweetextra['in_reply_to_status_id_str'])){
				// Always prefer str property if possible
				$inReplyToTweetId = $tweetextra['in_reply_to_status_id_str'];
			} else {
				$inReplyToTweetId = $tweetextra['in_reply_to_status_id'];
			}
		}
		
		$d  =   $t . "<div id=\"tweet-" . s($tweet['tweetid']) . "\" class=\"tweet" . (($tweet['type'] == 1) ? " reply" : "") . (($tweet['type'] == 2) ? " retweet" : "") . "\">\n" . 
				($tweet['favorite'] ? $t . "\t<div class=\"fav\" title=\"A personal favorite\"><span>(A personal favorite)</span></div>\n" : "") .
				$t . "\t<p class=\"text\">" . 
				($rt ? "<a class=\"rt\" href=\"http://twitter.com/" . $retweet['screenname'] . "\"><strong>" . $retweet['screenname'] . "</strong></a> " : "") . 
				
				nl2br(p(highlightQuery($htmlcontent, $tweet), 3)) . "</p>\n" . 
				
				$t . "\t<p class=\"meta\">\n" . $t . "\t\t<a href=\"http://twitter.com/" . s($rt ? $retweet['screenname'] : $tweet['screenname']) . "/statuses/" . s($rt ? $retweet['tweetid'] : $tweet['tweetid']) . "\" class=\"permalink\">" . date("g:i A, M jS, Y", ($rt ? $retweet['time'] : $tweet['time'])) . "</a>\n" . 
				$t . "\t\t<span class=\"via\">via " . ($rt ? $retweet['source'] : $tweet['source']) . "</span>\n" .
				($rt ? $t . "\t\t<span class=\"rted\">(retweeted on " . date("g:i A, M jS, Y", $tweet['time']) . " <span class=\"via\">via " . $tweet['source'] . "</span>)</span>\n" : "") . 
				((!$rt && $inReplyToTweetId) ? $t . "\t\t<a class=\"replyto\" href=\"http://twitter.com/" . s($tweetextra['in_reply_to_screen_name']) . "/statuses/" . s($inReplyToTweetId) . "\">in reply to " . s($tweetextra['in_reply_to_screen_name']) . "</a>\n" : "") . 
				(($tweetplace && @$tweetplace->full_name) ? "\t\t<span class=\"place\">from <a href=\"http://maps.google.com/?q=" . urlencode($tweetplace->full_name) . "\">" . s($tweetplace->full_name) . "</a></span>" : "") .
				$t . "\t</p>\n" . $t . "</div>\n";
		$dd = hook("displayTweet", array($d, $tweet));
		if(!empty($dd)){ $d = $dd[0]; }
		return  $d;
	}
	
	function tweetsHTML($q, $mode = "", $tabs = 4){
		global $db, $home, $config;
		$maxTweets = 200;
		$s         = "";
		$t         = str_repeat("\t", $tabs);
		$path      = s(rtrim($config['path'], "/"));
		$first     = 0; $last = 0; $i = 0; $tweets = array();
		$array     = is_array($q);
		$count     = $array ? count($q) : $db->numRows($q);
		if($count > 0){
			if(!$array){
				while($tweet = $db->fetch($q)){
					$tweets[] = $tweet;
				}
			} else {
				$tweets = $q;
			}
			foreach($tweets as $tweet){
				if($tweet['time'] < $first || $first == 0){ $first = $tweet['time']; }
				if($tweet['time'] > $last){ $last = $tweet['time']; }
				$s .= tweetHTML($tweet, $tabs);
				$i++;
				if($mode == "month" && $i >= $maxTweets){
					$s .= $t . "<div class=\"truncated\"><strong>There&#8217;s more tweets in this month!</strong> <span>Go up and <a href=\"#top\">select a date</a> to see more &uarr;</span></div>\n";
					break;
				}
				if($mode == "search" && $i >= $maxTweets){
					$s .= $t . "<div class=\"truncated\"><strong>There&#8217;s even more search results!</strong> <span>Go up and <a href=\"#top\">add a couple of words</a> to your query to find the specific tweets you want &uarr;</span></div>\n";
					break;
				}
			}
			if($mode == "day"){
				$nextprev = "";
				$half     = "SELECT `tweetid`, `time`, YEAR(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) as `year`, MONTH(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) as `month`, DAY(FROM_UNIXTIME(`time`" . DB_OFFSET . ")) as `day` FROM `".DTP."tweets` WHERE `time`";
				$pTQ      = $db->query($half . " < '" . $db->s($first) . "' ORDER BY `time` DESC LIMIT 1");
				$nTQ      = $db->query($half . " > '" . $db->s($last)  . "' ORDER BY `time` ASC LIMIT 1");
				if($db->numRows($pTQ) > 0){
					$prevTweet = $db->fetch($pTQ);
					$nextprev .= "<a class=\"prev\" href=\"" . $path . "/" . 
						s($prevTweet['year']) . "/" . s(pad($prevTweet['month'])) . "/" . s(pad($prevTweet['day'])) . 
						"\">&larr; <span>" . date("F jS", mktime(4,0,0, $prevTweet['month'], $prevTweet['day'], $prevTweet['year'])) . 
						"</span></a> ";
				}
				if($db->numRows($nTQ) > 0){
					$nextTweet = $db->fetch($nTQ);
					$nextprev .= "<a class=\"next\" href=\"" . $path . "/" . 
						s($nextTweet['year']) . "/" . s(pad($nextTweet['month'])) . "/" . s(pad($nextTweet['day'])) . 
						"\"><span>" . date("F jS", mktime(4,0,0, $nextTweet['month'], $nextTweet['day'], $nextTweet['year'])) . 
						"</span> &rarr;</a>";
				}
				if($nextprev){
					$s .= $t . "<div class=\"nextprev\">" . trim($nextprev) . "</div>\n";
				}
			}
		} else {
			$s = $t . "<div class=\"notweets\">No tweets here!</div>\n";
			if($home){
				$s .= $t . "<p>If you have <strong>just installed</strong> Tweet Nest and this is your archive page, you need to load in your tweets before something will be displayed here. Check out the installation guide on the <a href=\"http://pongsocket.com/tweetnest/\">web site for Tweet Nest</a> for more information.</p>\n";
			}
		}
		return $s;
	}
	
	function errorPage($html, $tabs = 3){
		global $config, $author, $authorextra;
		$t         = str_repeat("\t", $tabs);
		$pageTitle = "Whoops!";
		include "header.php";
		echo $t . "<p class=\"error\"><strong>An error occured:</strong> " . $html . "</p>\n";
		include "footer.php";
		exit;
	}
	
	function highlightQuery($str, $tweet){
		if(!isset($tweet['word']) || (!is_array($tweet['word']) && !is_string($tweet['word']))){ return $str; }
		if(!is_array($tweet['word'])){
			$q = array($tweet['word']);
		} else {
			$q = $tweet['word'];
		}
		foreach($q as $word){
			$s = preg_match("/[A-Za-z0-9_]/", $word[0]) ? "\b" : "\B";
			$e = preg_match("/[A-Za-z0-9_]/", $word[strlen($word)-1]) ? "\b" : "\B";
			$str = preg_replace(
				"/" . $s . "(" . preg_quote(s($word), "/") . ")" . $e . "/i",
				"<strong class=searchword>$1</strong>",
				$str
			);
		}
		// Get 'em outta tha links! We can only do it this way because we have reasonable control over allowed tags, etc.
		$tagsInLinks = "/<a ([^>]*)href=\"([^\"]*)<strong class=searchword>([^\"<]+)<\/strong>([^\"]*)\">/"; $i = 0;
		while(preg_match($tagsInLinks, $str)){
			if($i > 20){ break; } // No infinite loops!
			$str = preg_replace(
				$tagsInLinks,
				"<a $1href=\"$2$3$4\">",
				$str
			);
			$i++;
		}
		// Adding in the quote marks
		$str = str_replace("<strong class=searchword>", "<strong class=\"searchword\">", $str);
		return $str;
	}
	
	// CSS functions ------------------------------------
	
	$css_i = 0;
	global $css_i;
	function css($var, $canBeEmpty = false){ // Display CSS value found in given config variable
		global $config, $authorextra, $css_i;
		if($css_i >= 10){ $css_i = 0; return false; } // Too much recursion
		$var     = trim(strtolower($var));
		$profile = array(
			"profile_background_color", "profile_text_color", "profile_link_color",
			"profile_sidebar_fill_color", "profile_sidebar_border_color"
		);
		if(isset($config['style'][$var])){
			$val = $config['style'][$var];
			if($val == "profile"){
				$css_i = 0;
				$pv = array(
					"text_color" => "profile_text_color",
					"link_color" => "profile_link_color",
					"content_background_color" => "#fff",
					"top_background_color" => "profile_background_color",
					"top_background_image" => "profile_background_image_url",
					"top_background_image_tile" => "profile_background_tile",
					"top_bar_background_color" => "profile_sidebar_fill_color",
					// Tweet
					"tweet_border_color" => "#eee",
					"tweet_meta_text_color" => "#999",
				);
				if(preg_match("/#[0-9a-f]+/", $pv[$var])){
					return cssHex($pv[$var]);
				} else {
					if($authorextra[$pv[$var]]){
						return profileCss($authorextra[$pv[$var]]);
					}
					return $canBeEmpty ? "" : standardCss($var);
				}
			}
			if(in_array($val, $profile)){
				$css_i = 0;
				return cssHex($authorextra[$val]); // They're only ever color vars, no need to run profileCss()
			}
			if(preg_match("/^[a-z_]+$/", $val) && isset($config['style'][$val])){
				$css_i++;
				return css($val, $canBeEmpty); // Recursion
			}
			if(preg_match("/^https?:\/\//", $val) || preg_match("/_image$/", $var)){
				$css_i = 0;
				return "url(" . $val . ")";
			}
			if(is_bool($val)){
				$css_i = 0;
				if(substr_count($var, "tile") > 0){
					return (!sBool($val) ? "no-" : "") . "repeat";
				}
				return $val ? 1 : 0;
			}
			if(preg_match("/^[a-zA-Z0-9-_ #\"'\(\),.]+$/i", $val)){ // Legit
				$css_i = 0;
				return preg_match("/#[0-9a-f]+/", $val) ? cssHex($val) : $val;
			}
			$css_i = 0;
			return $canBeEmpty ? "" : standardCss($var); // Empty or weird
		}
		$css_i = 0;
		return false;
	}
	
	function standardCss($var){
		$var = trim(strtolower($var));
		if(substr_count($var, "color") > 0){
			if(substr_count($var, "text_color") > 0 || substr_count($var, "link_color") > 0){
				return "#ccc";
			}
			return "transparent";
		} elseif(substr_count($var, "tile") > 0){
			return "repeat";
		} elseif(substr_count($var, "position") > 0){
			return "0 0";
		} elseif(substr_count($var, "image") > 0){
			return "none";
		} else {
			return false; // for the WTF case
		}
	}
	
	function profileCss($val){
		if(preg_match("/^https?:\/\//", $val)){
			return "url(" . $val . ")";
		}
		if(is_bool(sBool($val))){
			return (!sBool($val) ? "no-" : "") . "repeat";
		}
		return cssHex($val);
	}
	
	function cssHex($val){
		return "#" . preg_replace('/^([0-9a-f])\1([0-9a-f])\2([0-9a-f])\3$/i', '\1\2\3', ltrim($val, "#"));
	}
	
	function sBool($val){
		if(is_bool($val)){ return $val; }
		if(strtolower(trim($val)) == "false" || $val === 0){ return false; }
		if(strtolower(trim($val)) == "true"  || $val === 1){ return true;  }
		return $val;
	}
	
	// Internal functions -------------------------------
	
	function _linkifyTweet_link($a, $b, $c, $d){
		$url = stripslashes($a);
		$end = stripslashes($d);
		return "<a class=\"link\" href=\"" . ($b[0] == "w" ? "http://" : "") . str_replace("\"", "&quot;", $url) . "\">" . (strlen($url) > 25 ? substr($url, 0, 24) . "..." : $url) . "</a>" . $end;
	}
	function _linkifyTweet_at($a, $b){
		return "<span class=\"at\">@</span><a class=\"user\" href=\"http://twitter.com/" . $a . "\">" . $a . "</a>";
	}
	function _linkifyTweet_hashtag($a, $b){
		return "<a class=\"hashtag\" href=\"http://twitter.com/search?q=%23" . $a . "\">#" . $a . "</a>";
	}
	function linkifyTweet($str, $linksOnly = false){
		// Look behind (it kinda sucks, no | operator)
		$lookbehind = '(?<!href=\")(?<!title=\")' .
				'(?<!href=\"http:\/\/)(?<!href=\"https:\/\/)' .
				'(?<!title=\"http:\/\/)(?<!title=\"https:\/\/)' .
				'(?<!data-image=\")(?<!data-image=\"http:\/\/)(?<!data-image=\"https:\/\/)';
		// Expression
		$html = preg_replace("/$lookbehind\b(((https?:\/\/)|www\.).+?)(([!?,.\"\)]+)?(\s|$))/e", "_linkifyTweet_link('$1', '$2', '$3', '$4')", $str);
		if(!$linksOnly){
			$html = preg_replace("/\B\@([a-zA-Z0-9_]{1,20}(\/\w+)?)/e", "_linkifyTweet_at('$1', '$2')", $html);
			$html = preg_replace("/\B\#([\pL|0-9|_]+)/eu", "_linkifyTweet_hashtag('$1', '$2')", $html);
		}
		return $html;
	}
	
	function entitifyTweet($str, $entities, $newwindow = false){
		if(!$entities){ return $str; }
		$replacements = array();
		$tb = $newwindow ? ' target="_blank"' : '';
		
		// Mentions
		foreach($entities->user_mentions as $entity){
			$replacements[$entity->indices[0]] = array(
				'end'     => $entity->indices[1],
				'content' => '<span class="at">@</span><a class="user"' . $tb . ' href="http://twitter.com/' . s($entity->screen_name) . '">' . s($entity->screen_name) . '</a>'
			);
		}
		
		// Hashtags
		foreach($entities->hashtags as $entity){
			$replacements[$entity->indices[0]] = array(
				'end'     => $entity->indices[1],
				'content' => '<a class="hashtag" rel="search"' . $tb . ' href="http://twitter.com/search?q=%23' . urlencode($entity->text) . '">#' . s($entity->text) . '</a>'
			);
		}
		
		// URLs
		foreach($entities->urls as $entity){
			$truncated = (!empty($entity->display_url) && mb_substr($entity->display_url, -1) == '…');
			$replacements[$entity->indices[0]] = array(
				'end'     => $entity->indices[1], // quittin' rel="nofollow" since this is meant for your own site
				'content' => '<a class="link" href="' . s($entity->url) . '"' . $tb . 
							(!empty($entity->expanded_url) && $truncated ? ' title="' . s($entity->expanded_url) . '"' : '') . '>' . 
							(!empty($entity->display_url) ? $entity->display_url : $entity->url) . '</a>'
			);
		}
		
		// Media
		if(isset($entities->media) && is_array($entities->media)){
			foreach($entities->media as $entity){
				$truncated = (!empty($entity->display_url) && mb_substr($entity->display_url, -1) == '…');
				$replacements[$entity->indices[0]] = array(
					'end'     => $entity->indices[1],
					'content' => '<a class="media" href="' . s($entity->url) . '"' . $tb . ' data-image="' . s($entity->media_url) . '"' . 
								(!empty($entity->expanded_url) && $truncated ? ' title="' . s($entity->expanded_url) . '"' : '') . '>' . 
								(!empty($entity->display_url) ? $entity->display_url : $entity->url) . '</a>'
				);
			}
		}
		
		// Putting it all together
		$out = '';
		$lastEntityEnded = 0;

		// Sort the entity replacements by start index
		ksort($replacements);

		// Loop through all entities
		foreach($replacements as $position => $replacement){

			// Insert the content between this entity and the previous one
			$out .= mb_substr($str, $lastEntityEnded, $position - $lastEntityEnded);

			// Insert the entity content
			$out .= $replacement['content'];

			// Update the position of the last entity end
			$lastEntityEnded = $replacement['end'];
		}

		// Insert the remaining content
		$out .= mb_substr($str, $lastEntityEnded);

		return $out;
	}
	
	function areEntitiesEmpty($entities){
		if(is_object($entities)){
			foreach(get_object_vars($entities) as $property => $value){
				if(!empty($value)){
					return false;
				}
			}
		}
		return true;
	}
	
	// Altered "Days in Month" function taken from:
		// PHP Calendar Class Version 1.4 (5th March 2001)
		//  
		// Copyright David Wilkinson 2000 - 2001. All Rights reserved.
		// 
		// This software may be used, modified and distributed freely
		// providing this copyright notice remains intact at the head 
		// of the file.
		//
		// This software is freeware. The author accepts no liability for
		// any loss or damages whatsoever incurred directly or indirectly 
		// from the use of this script. The author of this software makes 
		// no claims as to its fitness for any purpose whatsoever. If you 
		// wish to use this software you should first satisfy yourself that 
		// it meets your requirements.
		//
		// URL:   http://www.cascade.org.uk/software/php/calendar/
		// Email: davidw@cascade.org.uk
	function getDaysInMonth($month, $year){
		if($month < 1 || $month > 12){ return 0; }
		
		$daysInMonth = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
		$d = $daysInMonth[$month - 1];
		
		if($month == 2){
			// Check for leap year
			// Forget the 4000 rule, I doubt I'll be around then...
			if($year%4 == 0){
				if($year%100 == 0){
					if($year%400 == 0){
						$d = 29;
					}
				} else {
					$d = 29;
				}
			}
		}
		return $d;
    }