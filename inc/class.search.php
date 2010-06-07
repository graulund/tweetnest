<?php
	// TWEET NEST
	// Search class
	
	// Inspiration from http://www.zez.org/article/articleview/83/
	// Inspiration from PunBB
	
	class TweetNestSearch {
		public $minWordLength = 3;
		
		protected function stripWhitespace($text){
			$itext = str_replace(".", " ", $text);
			$itext = str_replace(",", " ", $itext);
			$itext = str_replace("'", " ", $itext);
			$itext = str_replace("\"", " ", $itext);
			$itext = str_replace("\n", " ", $itext);
			$itext = str_replace("\r", " ", $itext);
			$itext = preg_replace("/\s+/", " ", $itext);
			return $itext;
		}
		
		protected function keywordify($text){
			// No fancy apostrophes and dashes in keywords
			$text = strtolower(stupefyRaw($text, true));
			// Remove any apostrophes which aren't part of words
			$keywords = substr(preg_replace("((?<=\W)'|'(?=\W))", "", " " . $text . " "), 1, -1);
			// Remove symbols and multiple whitespace
			$keywords = preg_replace("/[\^\$&\(\)<>`\"\|,@_\?%~\+\[\]{}:=\/#\\\\;!\.\s]+/", " ", $keywords);
			return $keywords;
		}
		
		public function index($id, $text){
			global $db;
			$itext = $this->keywordify($text);
			
			$words = explode(" ", $itext);
			$wordcount = count($words);
			$uniques = array_count_values($words);
			$words = array_unique($words); // Strip duplicate words
			
			foreach($words as $word){
				if(strlen($word) >= $this->minWordLength){
					// Does word already exist?
					$wQ = $db->query("SELECT * FROM `".DTP."words` WHERE `word` = '" . $db->s($word) . "' LIMIT 1");
					if($db->numRows($wQ) > 0){
						$w = $db->fetch($wQ);
						$wordID = $w['id'];
						$db->query("UPDATE `".DTP."words` SET `tweets` = `tweets`+1 WHERE `id` = '" . $db->s($wordID) . "' LIMIT 1");
					} else {
						$db->query("INSERT INTO `".DTP."words` (`word`, `tweets`) VALUES ('" . $db->s($word) . "', '1')");
						$wordID = $db->insertID();
					}
					// Relevance value
					$frequency = $uniques[$word] / $wordcount;
					$db->query("INSERT INTO `".DTP."tweetwords` (`tweetid`, `wordid`, `frequency`) VALUES ('" . $db->s($id) . "', '" . $db->s($wordID) . "', '" . $db->s($frequency) . "')");
				}
			}
		}
		
		public function query($q, $sort = "relevance", $extraWhere = ""){
			global $db;
			$stf   = 0.7; // Search words can at most be present in 70% of the tweets
			if(strlen($q) < $this->minWordLength){ return false; } // <3 ;)
			
			$qtext = $this->keywordify($q);
			$words = explode(" ", $qtext);
			$words = array_unique($words);
			
			// Get total amount of tweets
			$tQ    = $db->query("SELECT COUNT(*) AS `count` FROM `".DTP."tweets`");
			$t     = $db->fetch($tQ);
			$total = $t['count'];
			if($total < 1){ return false; }
			
			// Build query string
			$sqlA = ""; $sqlO = "";
			foreach($words as $word){
				if($sqlA != ""){ $sqlA .= " AND "; $sqlO .= " OR "; }
				$ws    = "`w`.`word` LIKE '" . $db->s(str_replace("*", "%", $word)) . "'";
				$sqlA .= $ws;
				$sqlO .= $ws;
			}
			
			// Are we just requesting the months?
			if($sort == "months"){
				return $db->query(
					"SELECT MONTH(FROM_UNIXTIME(`t`.`time`)) AS `m`, YEAR(FROM_UNIXTIME(`t`.`time`)) AS `y`, COUNT(DISTINCT `t`.`id`) AS `c` " .
					"FROM `".DTP."tweets` `t` " .
					"INNER JOIN `".DTP."tweetwords` `tw` ON `t`.`id` = `tw`.`tweetid` " .
					"INNER JOIN `".DTP."words` `w` ON `tw`.`wordid` = `w`.`id` " .
					"WHERE (" . $sqlO . ") AND ((`w`.`tweets` / " . $total . ") < " . $stf . ") " .
					"GROUP BY `y`, `m` ORDER BY `y` DESC, `m` DESC"
				);
			}
			
			// Do it!
			$tweets = array();
			$query  = $db->query(
				"SELECT `w`.`word`, `t`.*, `tu`.`screenname`, `tu`.`realname`, `tu`.`profileimage` " .
				"FROM `".DTP."words` `w` " .
				"INNER JOIN `".DTP."tweetwords` `tw` ON `tw`.`wordid` = `w`.`id` " .
				"INNER JOIN `".DTP."tweets` `t` ON `tw`.`tweetid` = `t`.`id` " .
				"LEFT JOIN `".DTP."tweetusers` `tu` ON `t`.`userid` = `tu`.`userid` " .
				"WHERE (" . $sqlO . ") AND ((`w`.`tweets` / " . $total . ") < " . $stf . ")" . $extraWhere . " " .
				"ORDER BY " . ($sort == "time" ? "`t`.`time` DESC" : "`tw`.`frequency` DESC")
			);
			
			if($sort == "time"){
				while($t = $db->fetch($query)){
					// We don't want duplicates when sorting by time
					// .. but we do want accumulated word listing anyway
					$word = $t['word'];
					if(isset($tweets[$t['id']])){
						$t['word'] = $tweets[$t['id']]['word'];
						if(is_array($t['word'])){
							$t['word'][] = $word;
						} else {
							$t['word'] = array($t['word'], $word);
						}
					}
					$tweets[$t['id']] = $t;
				}
				return array_values($tweets);
			} else {
				// Get all instances
				while($t = $db->fetch($query)){
					if(!isset($tweets[$t['id']])){
						$tweets[$t['id']] = array();
					}
					$tweets[$t['id']][] = $t;
				}
				// How many instances of each tweet? The more, the more relevant it is
				$tweetsc = array();
				foreach($tweets as $tid => $ta){
					$c     = count($ta);
					$tweet = $ta[0];
					if(!isset($tweetsc[$c])){
						$tweetsc[$c] = array();
					}
					if($c > 1){
						$words = array();
						foreach($ta as $t){
							$words[] = $t['word'];
						}
						$tweet['word'] = $words;
					}
					$tweetsc[$c][] = $tweet;
				}
				// Final list in correct order
				krsort($tweetsc);
				$tweetlist = array();
				foreach($tweetsc as $c => $tweets){
					foreach($tweets as $tweet){
						$tweetlist[] = $tweet;
					}
				}
				return $tweetlist;
			}
		}
		
		public function monthsQuery($q){
			return $this->query($q, "months");
		}
	}