<?php
	// PONGSOCKET TWEET ARCHIVE
	// Image display extension
	
	if(!function_exists("imgid")){
		function imgid($path){
			$m = array();
			preg_match("@/([a-z0-9]+).*@i", $path, $m);
			if(count($m) > 0){
				return $m[1];
			}
			return false;
		}
	}
	
	class Extension_Images {
		public function enhanceTweet($tweet){
			// Finding entities
			$tweetextra = array();
			if(!empty($tweet['extra'])){
				if(is_array($tweet['extra'])){
					$tweetextra = $tweet['extra'];
				} else {
					@$tweetextra = unserialize($tweet['extra']);
				}
			}
			$rt = (array_key_exists("rt", $tweetextra) && !empty($tweetextra['rt']));
			$entities = $rt ? $tweetextra['rt']['extra']['entities'] : $tweetextra['entities'];
			
			// Let's go
			$imgs    = array();
			$text    = $rt ? $tweetextra['rt']['text'] : $tweet['text'];
			$mtext   = TwitterApi::mediaLinkTweetText($text, $entities);
			//$text    = TwitterApi::fullLinkTweetText($text, $entities);
			$links   = findURLs($mtext); // Two link lists because media links might be different from public URLs
			$flinks  = findURLs($text);
			
			if(!empty($links) && !empty($flinks)){ // connection between the two
				$linkmap = array_combine(array_keys($links), array_keys($flinks));
			}
			
			$http    = 'http'; // possible to change to https (if all hosts support it)
			
			foreach($links as $link => $l){
				if(is_array($l) && array_key_exists("host", $l) && array_key_exists("path", $l)){
					$domain = domain($l['host']);
					$imgid  = imgid($l['path']);
					if($imgid){
						if($domain == "twimg.com"){
							$displaylink = $linkmap ? $linkmap[$link] : $link;
							$imgs[$displaylink] = "//pbs.twimg.com" . $l['path'] . ":thumb";
						}
						if($domain == "twitpic.com"){
							$imgs[$link] = "//twitpic.com/show/thumb/" . $imgid;
						}
						if($domain == "imgur.com"){
							$imgs[$link] = "//i.imgur.com/" . $imgid . "s.jpg";
						}
						if($domain == "moby.to"){
							$imgs[$link] = $http . "://moby.to/" . $imgid . ":square";
						}
						if($domain == "instagr.am" || $domain == "instagram.com"){
							$html = (string)getURL($link);
							preg_match('/<meta property="og:image" content="([^"]+)"\s*\/>/i', $html, $matches);
							if(isset($matches[1])){
								$imgs[$link] = $matches[1];
							}			
						}
					}
				}
			}
			if(count($imgs) > 0){
				$tweet['extra']['imgs'] = $imgs;
			}
			return $tweet;
		}
		
		public function displayTweet($d, $tweet){
			global $config;
			$https_strict = $config['https_strict'];
			@$tweetextra = unserialize($tweet['extra']);
			if(is_array($tweetextra) && array_key_exists("imgs", $tweetextra)){
				preg_match("/^([\t]+)</", $d, $m); $x = $m[1];
				$ds    = explode("\n", $d, 2);
				$imgd  = ""; $i = 1; $is = array();
				foreach($tweetextra['imgs'] as $link => $img){
					if(!$https_strict || substr(s($img), 0, 2) == "//" || substr(s($img), 0, 8) == "https://") {
						$imgd .=
							$x . "\t<a class=\"pic pic-" . s($i) . "\" href=\"" . s($link) . "\">" .
							"<img src=\"" . s($img) . "\" alt=\"\" /></a>\n";
					}
					$is[$link] = $i++;
				}
				foreach($is as $link => $i){
					$ds[1] = preg_replace(
						"/class=\"([^\"]*)\" href=\"" . preg_quote(s($link), "/") . "\"/",
						"class=\"$1 picl picl-" . s($i) . "\" href=\"" . s($link) . "\"", 
						$ds[1]
					);
				}
				$d     = implode("\n", array($ds[0], rtrim($imgd, "\n"), $ds[1]));
			}
			return array($d, $tweet);
		}
	}
	
	$o = new Extension_Images();
