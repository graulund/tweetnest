<?php
	// TWEET NEST
	// Twitter API class
	// (a simple one)
	
	class TwitterApi {
		// HTTP grabbin' cURL options, by exsecror
		public $httpOptions = array(
			CURLOPT_FORBID_REUSE   => true,
			CURLOPT_POST           => false,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_USERAGENT      => "Mozilla/5.0 (Compatible; libCURL)",
			CURLOPT_VERBOSE        => false,
			CURLOPT_SSL_VERIFYPEER => false // Insecurity?
		);
		public $dbMap = array(
			"id"           => "tweetid",
			"created_at"   => "time",
			"text"         => "text",
			"source"       => "source",
			"coordinates"  => "coordinates",
			"geo"          => "geo",
			"place"        => "place",
			"contributors" => "contributors",
			"user.id"      => "userid"
		);
		
		public function query($path, $format = "json", $auth = NULL, $ssl = true){
			$format = mb_strtolower(trim($format));
			$path   = ltrim($path, "/");
			if($format != "xml" && $format != "json"){ return false; }
			$url    = "http" . ($ssl ? "s" : "") . "://api.twitter.com/" . $path;
			$file   = "";
			
			do {
				if($file != ""){ sleep(2); } // Wait two secs if we got a failwhale
				$file = getURL($url, $auth);
				if(is_array($file)){ return $file; } // Error
			} while(
				// Protect against failwhale
				(
					($format == "xml" && mb_substr($file, 0, 2) != "<?") || // Invalid XML
					($format == "json" && !in_array(mb_substr($file, 0, 1), array("[", "{"))) // Invalid JSON
				)
				&& mb_substr_count(mb_strtolower($file), "over capacity") > 0
			);
			if($format == "xml"){
				$data = simplexml_load_string($file);
				if(!empty($data->error)){ die($data->error); }
				return $data;
			}
			if($format == "json"){
				// Prevent issues with long ints on 32-bit systems
				$file = preg_replace("/\"([a-z_]+_)?id\":(\d+)(,|\}|\])/", "\"$1id\":\"$2\"$3", $file);
				$data = json_decode($file);
				if(!empty($data->error)){ die($data->error); }
				return $data;
			}
			return false;
		}
		//TODO: BUILD IN SUPPORT FOR "RATE LIMIT EXCEEDED"
		
		public function validateUserParam($p){
			return (preg_match("/^user_id=[0-9]+$/", $p) || preg_match("/^screen_name=[0-9a-zA-Z_]+$/", $p));
		}
		
		public function getUserParam($str){
			list($name, $value) = explode("=", $str, 2);
			return array("name" => $name, "value" => $value);
		}
		
		public function userId($i){
			return "user_id=" . $i;
		}
		
		public function screenName($str){
			return "screen_name=" . $str;
		}
		
		public function getUserId($screenname){
			global $db;
			$q = $db->query("SELECT * FROM `".DTP."tweetusers` WHERE `screenname` = '" . $db->s($screenname) . "' LIMIT 1");
			if($db->numRows($q) > 0){
				$u = $db->fetch($q);
				return $u['userid'];
			}
			return false;
		}
		
		public function getScreenName($uid){
			global $db;
			$q = $db->query("SELECT * FROM `".DTP."tweetusers` WHERE `userid` = '" . $db->s($uid) . "' LIMIT 1");
			if($db->numRows($q) > 0){
				$u = $db->fetch($q);
				return $u['screenname'];
			}
			return false;
		}
		
		public function transformTweet($tweet){ // API tweet object -> DB tweet array
			$t = array(); $e = array(); 
			foreach(get_object_vars($tweet) as $k => $v){
				if(array_key_exists($k, $this->dbMap)){
					$key = $this->dbMap[$k];
					$val = $v;
					if(in_array($key, array("text", "source"))){
						$val = (string) $v;
					} elseif(in_array($key, array("tweetid", "id"))){
						$val = (string) $v; // Yes, I pass tweet id as string. It's a loooong number and we don't need to calc with it.
					} elseif($key == "time"){
						$val = strtotime($v);
					}
					$t[$key] = $val;
				} elseif($k == "user"){
					$t['userid'] = (int) $v->id;
				} elseif($k == "retweeted_status"){
					$rt = array(); $rte = array();
					foreach(get_object_vars($v) as $kk => $vv){
						if(array_key_exists($kk, $this->dbMap)){
							$kkey = $this->dbMap[$kk];
							$vval = $vv;
							if(in_array($kkey, array("text", "source"))){
								$vval = (string) $vv;
							} elseif(in_array($kkey, array("tweetid", "id"))){
								$vval = (string) $vv;
							} elseif($kkey == "time"){
								$vval = strtotime($vv);
							}
							$rt[$kkey] = $vval;
						} elseif($kk == "user"){
							$rt['userid']     = (string) $vv->id;
							$rt['screenname'] = (string) $vv->screen_name;
						} else {
							$rte[$kk] = $vv;
						}
					}
					$rt['extra'] = $rte;
					$e['rt']     = $rt;
				} else {
					$e[$k] = $v;
				}
			}
			$t['extra'] = $e;
			$tt = hook("enhanceTweet", $t, true);
			if(!empty($tt) && is_array($tt) && $tt['text']){
				$t = $tt;
			}
			return $t;
		}
		
		public function entityDecode($str){
			return str_replace("&amp;", "&", str_replace("&lt;", "<", str_replace("&gt;", ">", $str)));
		}
		
		public function insertQuery($t){
			global $db;
			$type = ($t['text'][0] == "@") ? 1 : (preg_match("/RT @\w+/", $t['text']) ? 2 : 0);
			return "INSERT INTO `".DTP."tweets` (`userid`, `tweetid`, `type`, `time`, `text`, `source`, `extra`, `coordinates`, `geo`, `place`, `contributors`) VALUES ('" . $db->s($t['userid']) . "', '" . $db->s($t['tweetid']) . "', '" . $db->s($type) . "', '" . $db->s($t['time']) . "', '" . $db->s($this->entityDecode($t['text'])) . "', '" . $db->s($t['source']) . "', '" . $db->s(serialize($t['extra'])) . "', '" . $db->s(serialize($t['coordinates'])) . "', '" . $db->s(serialize($t['geo'])) . "', '" . $db->s(serialize($t['place'])) . "', '" . $db->s(serialize($t['contributors'])) . "');";
		}
	}