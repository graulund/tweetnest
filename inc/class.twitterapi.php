<?php
	// TWEET NEST
	// Twitter API class
	// (a simple one)

require_once "twitteroauth/twitteroauth.php";
require_once "twitteroauth/config.php";

	class TwitterApi {

        private $connection;

		public $dbMap = array(
			"id_str"       => "tweetid",
			"created_at"   => "time",
			"text"         => "text",
			"source"       => "source",
			"coordinates"  => "coordinates",
			"geo"          => "geo",
			"place"        => "place",
			"contributors" => "contributors",
			"user.id"      => "userid"
		);

        public function __construct() {
            global $config;
            $this->connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $config['twitter_token'], $config['twitter_token_secr']);
        }
		
		public function query($path){
			return $this->connection->get($path);
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
					if(in_array($key, array("text", "source", "tweetid", "id", "id_str"))){
						$val = (string)$v;
						// Yes, I pass tweet id as string. It's a loooong number and we don't need to calc with it.
					} elseif($key == "time"){
						$val = strtotime($v);
					}
					$t[$key] = $val;
				} elseif($k == "user"){
					$t['userid'] = (string)$v->id_str;
				} elseif($k == "retweeted_status"){
					$rt = array(); $rte = array();
					foreach(get_object_vars($v) as $kk => $vv){
						if(array_key_exists($kk, $this->dbMap)){
							$kkey = $this->dbMap[$kk];
							$vval = $vv;
							if(in_array($kkey, array("text", "source", "tweetid", "id", "id_str"))){
								$vval = (string)$vv;
							} elseif($kkey == "time"){
								$vval = strtotime($vv);
							}
							$rt[$kkey] = $vval;
						} elseif($kk == "user"){
							$rt['userid']     = (string)$vv->id_str;
							$rt['screenname'] = (string)$vv->screen_name;
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
		
		// Replace t.co links with full links, for internal use
		public static function fullLinkTweetText($text, $entities, $mediaUrl = false){
			if(!$entities){ return $text; }
			$sources      = property_exists($entities, 'media') ? array_merge($entities->urls, $entities->media) : $entities->urls;
			$replacements = array();
			foreach($sources as $entity){
				if(property_exists($entity, 'expanded_url')){
					$replacements[$entity->indices[0]] = array(
						'end'     => $entity->indices[1],
						'content' => $mediaUrl && $entity->media_url ? $entity->media_url : $entity->expanded_url
					);
				}
			}
			$out = '';
			$lastEntityEnded = 0;
			ksort($replacements);
			foreach($replacements as $position => $replacement){
				$out .= mb_substr($text, $lastEntityEnded, $position - $lastEntityEnded);
				$out .= $replacement['content'];
				$lastEntityEnded = $replacement['end'];
			}
			$out .= mb_substr($text, $lastEntityEnded);
			return $out;
		}
		
		// Same as above, but prefer media urls
		public static function mediaLinkTweetText($text, $entities){
			return self::fullLinkTweetText($text, $entities, true);
		}
		
		public function insertQuery($t){
			global $db;
			$type = ($t['text'][0] == "@") ? 1 : (preg_match("/RT @\w+/", $t['text']) ? 2 : 0);
			return "INSERT INTO `".DTP."tweets` (`userid`, `tweetid`, `type`, `time`, `text`, `source`, `extra`, `coordinates`, `geo`, `place`, `contributors`) VALUES ('" . $db->s($t['userid']) . "', '" . $db->s($t['tweetid']) . "', '" . $db->s($type) . "', '" . $db->s($t['time']) . "', '" . $db->s($this->entityDecode($t['text'])) . "', '" . $db->s($t['source']) . "', '" . $db->s(serialize($t['extra'])) . "', '" . $db->s(serialize($t['coordinates'])) . "', '" . $db->s(serialize($t['geo'])) . "', '" . $db->s(serialize($t['place'])) . "', '" . $db->s(serialize($t['contributors'])) . "');";
		}
	}