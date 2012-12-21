<?php
	// TWEET NEST
	// Setup guide
	
	// This file should be deleted once your tweet archive is set up.
	
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set("display_errors", true); // This is NOT a production page; errors can and should be visible to aid the user.
	
	mb_language("uni");
	mb_internal_encoding("UTF-8");
	header("Content-Type: text/html; charset=utf-8");
	
	require "inc/config.php";
	
	$GLOBALS['error'] = false;
	
	function s($str){ return htmlspecialchars($str, ENT_NOQUOTES); } // Shorthand
	
	function displayErrors($e){
		if(count($e) <= 0){ return false; }
		$r = "";
		if(count($e) > 1){
			$r .= "<h2>Errors</h2><ul class=\"error\">";
			foreach($e as $error){
				$r .= "<li>" . $error . "</li>";
				// Not running htmlspecialchars 'cause errors are a set of finite approved messages seen below
			}
			$r .= "</ul>";
		} else {
			$r .= "<h2>Error</h2>\n<p class=\"error\">" . current($e) . "</p>";
		}
		return $r . "\n";
	}
	
	function errorHandler($errno, $message, $filename, $line, $context){
		if(error_reporting() == 0){ return; }
		if($errno & (E_ALL ^ E_NOTICE)){
			$GLOBALS['error'] = true;
			$types = array(
				1 => "error",
				2 => "warning",
				4 => "parse error",
				8 => "notice",
				16 => "core error",
				32 => "core warning",
				64 => "compile error",
				128 => "compile warning",
				256 => "user error",
				512 => "user warning",
				1024 => "user notice",
				2048 => "strict warning",
				4096 => "recoverable fatal error"
			);
			echo "<div class=\"serror\"><strong>PHP " . $types[$errno] . ":</strong> " . s(strip_tags($message)) . " in <code>" . s($filename) . "</code> on line " . s($line) . ".</div>\n";
		}
		return true;
	}
	set_error_handler("errorHandler");
	
	function configSetting($cf, $setting, $value){
		if($value === ""){ return $cf; } // Empty
		$empty = is_bool($value) ? "(true|false)" : "''";
		$val   = is_bool($value) ? ($value ? "true" : "false") : "'" . preg_replace("/([\\'])/", '\\\$1', $value) . "'";
		return preg_replace("/'" . preg_quote($setting, "/") . "'(\s*)=>(\s*)" . $empty . "/", "'" . $setting . "'$1=>$2" . $val, $cf);
	}
	
	$e       = array();
	$log     = array();
	$success = false; // We are doomed :(
	$post    = (strtoupper($_SERVER['REQUEST_METHOD']) == "POST");
	
	// Get the full path
	$fPath = explode(DIRECTORY_SEPARATOR, rtrim(__FILE__, DIRECTORY_SEPARATOR));
	array_pop($fPath); // Remove setup.php
	$fPath = implode($fPath, "/");
	
	// Prerequisites and pre-checks
	if(!empty($config['twitter_screenname'])){ $e[] = "<strong>Your Tweet Nest has already been set up.</strong> If you wish to change settings, open <code>config.php</code> and change values using a text editor. Alternatively, replace it with the default empty <code>config.php</code> and reload this page."; } // Config already defined!
	if(!function_exists("json_decode")){ $e[] = "Your PHP version <strong>doesn&#8217;t seem to support JSON decoding</strong>. This functionality is required to retrieve tweets and is included in the core of PHP 5.2 and above. However, you can also install the <a href=\"http://pecl.php.net/package/json\">JSON PECL module</a> instead, if you&#8217;re using PHP 5.1."; }
	if(version_compare(PHP_VERSION, "5.1.0", "<")){ $e[] = "<strong>A PHP version of 5.1 or above is required.</strong> Your current PHP version is " . PHP_VERSION . ". You need to upgrade" . (version_compare(PHP_VERSION, "5.0.0", "<") ? " or turn PHP 5 on if you have a server that requires you to do that" : " your PHP installation") . "."; }
	if(function_exists("apache_get_modules") && !in_array("mod_rewrite", apache_get_modules())){ $e[] = "Could not detect the <code>mod_rewrite</code> module in your Apache installation. <strong>This module is required.</strong>"; }
	clearstatcache();
	if(!is_writable("inc/config.php")){ $e[] = "Your <code>config.php</code> file is not writable by the server. Please make sure it is before proceeding, then reload this page. Often, this is done through giving every system user the write privileges on that file through FTP."; }
	if(!function_exists("preg_match")){ $e[] = "PHP&#8217;s PCRE support module appears to be missing. Tweet Nest requires Perl-style regular expression support to function."; }
	if(!function_exists("mysql_connect") && !function_exists("mysqli_connect")){ $e[] = "Neither the MySQL nor the MySQLi library for PHP is installed. One of these are required, along with a MySQL server to connect to and store data in."; }
	
	// PREPARE VARIABLES
	$pp   = strpos($_SERVER['REQUEST_URI'], "/setup");
	$path = is_numeric($pp) ? ($pp > 0 ? substr($_SERVER['REQUEST_URI'], 0, $pp) : "/") : "";
	$famousTwitterers = array("alyankovic", "aplusk", "billgates", "cnnbrk", "coldplay", "justinbieber"); // SO IMPORTANT!
	
	// Someone's submitting! Time to set up!
	if($post){
		$log[] = "Settings being submitted!";
		$log[] = "PHP version: " . PHP_VERSION;
		if(!empty($_POST['twitter_screenname']) && !empty($_POST['tz']) && !empty($_POST['path']) && !empty($_POST['db_hostname']) && !empty($_POST['db_username']) && !empty($_POST['db_database'])){ // Required fields
			$log[] = "All required fields filled in.";
			if(preg_match("/^[a-zA-Z0-9_]+$/", $_POST['twitter_screenname']) && strlen($_POST['twitter_screenname']) <= 15){
				$log[] = "Valid Twitter screen name.";
			} else {
				$e[] = "Invalid Twitter screen name.";
			}
			if(date_default_timezone_set($_POST['tz'])){
				$log[] = "Valid time zone.";
			} else {
				$e[] = "Invalid time zone.";
			}
			if(empty($_POST['db_table_prefix']) || preg_match("/^[a-zA-Z0-9_]+$/", $_POST['db_table_prefix'])){
				$log[] = "Valid table name prefix.";
			} else {
				$e[] = "Invalid table name prefix. You can only use letters, numbers and the _ character.";
			}
			if(!empty($_POST['maintenance_http_password']) && $_POST['maintenance_http_password'] != $_POST['maintenance_http_password_2']){
				$e[] = "The two typed admin passwords didn&#8217;t match. Please make sure they&#8217;re the same.";
			}
			$sPath = "/" . trim($_POST['path'], "/");
			$log[] = "Formatted path: " . $sPath;
			if(!$e){
				// Check the database first!
				require "inc/class.db.php";
				try {
					$db = new DB("mysql", array(
						"hostname" => $_POST['db_hostname'],
						"username" => $_POST['db_username'],
						"password" => $_POST['db_password'],
						"database" => $_POST['db_database']
					));
				} catch(Exception $err){
					$e[] = "Got the following database connection error! <code>" . $err->getMessage() . "</code> Please make sure that your database settings are correct and that the database server is running and then try again.";
				}
				if(!$e && !$db){
					$e[] = "Got the following database connection error! <code>" . $db->error() . "</code> Please make sure that your database settings are correct and that the database server is running and then try again.";
				}
				if(!$e && !$GLOBALS['error']){ // If we get a database error, it'll activate $GLOBALS['error'] through PHP error
					$log[] = "Connected to MySQL database!";
					$cv    = $db->clientVersion();
					if(version_compare($cv, "4.1.0", "<")){
						$e[] = "Your MySQL client version is too old. Tweet Nest requires MySQL version 4.1 or higher to function. Your client currently has " . s($cv) . ".";
					} else { $log[] = "MySQL client version: " . $cv; }
					$sv    = $db->serverVersion();
					if(version_compare($sv, "4.1.0", "<")){
						$e[] = "Your MySQL server version is too old. Tweet Nest requires MySQL version 4.1 or higher to function. Your server currently has " . s($sv) . ".";
					} else { $log[] = "MySQL server version: " . $sv; }
					if(!$e){
						// Set up the database!
						$log[] = "Acceptable MySQL version.";
						$DTP = $_POST['db_table_prefix']; // This has been verified earlier on in the code
						
						// Tweets table
						$q = $db->query("CREATE TABLE `".$DTP."tweets` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, `userid` bigint(20) unsigned NOT NULL, `tweetid` varchar(100) NOT NULL, `type` tinyint(4) NOT NULL DEFAULT '0', `time` int(10) unsigned NOT NULL, `text` varchar(255) NOT NULL, `source` varchar(255) NOT NULL, `favorite` tinyint(4) NOT NULL DEFAULT '0', `extra` text NOT NULL, `coordinates` text NOT NULL, `geo` text NOT NULL, `place` text NOT NULL, `contributors` text NOT NULL, PRIMARY KEY (`id`), UNIQUE (`tweetid`), FULLTEXT KEY `text` (`text`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
						if(!$q){
							$e[] = "An error occured while creating table <code>".$DTP."tweets</code>: <code>" . $db->error() . "</code>";
						} else { $log[] = "Successfully created table ".$DTP."tweets"; }
						
						// Tweet users table
						$q = $db->query("CREATE TABLE `".$DTP."tweetusers` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, `userid` bigint(20) unsigned NOT NULL, `screenname` varchar(25) NOT NULL, `realname` varchar(255) NOT NULL, `location` varchar(255) NOT NULL, `description` varchar(255) NOT NULL, `profileimage` varchar(255) NOT NULL, `url` varchar(255) NOT NULL, `extra` text NOT NULL, `enabled` tinyint(4) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
						if(!$q){
							$e[] = "An error occured while creating table <code>".$DTP."tweetusers</code>: <code>" . $db->error() . "</code>";
						} else { $log[] = "Successfully created table ".$DTP."tweetusers"; }
						
						// Tweet words table
						$q = $db->query("CREATE TABLE `".$DTP."tweetwords` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, `tweetid` int(10) unsigned NOT NULL, `wordid` int(10) unsigned NOT NULL, `frequency` float NOT NULL, PRIMARY KEY (`id`), KEY `tweetwords_tweetid` (`tweetid`), KEY `tweetwords_wordid` (`wordid`), KEY `tweetwords_frequency` (`frequency`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
						if(!$q){
							$e[] = "An error occured while creating table <code>".$DTP."tweetwords</code>: <code>" . $db->error() . "</code>";
						} else { $log[] = "Successfully created table ".$DTP."tweetwords"; }
						
						// Words table
						$q = $db->query("CREATE TABLE `".$DTP."words` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, `word` varchar(150) NOT NULL, `tweets` int(11) NOT NULL, PRIMARY KEY (`id`), KEY `words_tweets` (`tweets`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8");
						if(!$q){
							$e[] = "An error occured while creating table <code>".$DTP."words</code>: <code>" . $db->error() . "</code>";
						} else { $log[] = "Successfully created table ".$DTP."words"; }
						
						if(!$e){
							// WRITE THE CONFIG FILE, YAY!
							$cf = file_get_contents("inc/config.php");
							$cf = configSetting($cf, "twitter_screenname", $_POST['twitter_screenname']);
							$cf = configSetting($cf, "timezone", $_POST['tz']);
							$cf = configSetting($cf, "path", $sPath);
							$cf = configSetting($cf, "hostname", $_POST['db_hostname']);
							$cf = configSetting($cf, "username", $_POST['db_username']);
							$cf = configSetting($cf, "password", $_POST['db_password']);
							$cf = configSetting($cf, "database", $_POST['db_database']);
							$cf = configSetting($cf, "table_prefix", $_POST['db_table_prefix']);
							$cf = configSetting($cf, "maintenance_http_password", $_POST['maintenance_http_password']);
							$cf = configSetting($cf, "anywhere_apikey", $_POST['anywhere_apikey']);
							$cf = configSetting($cf, "follow_me_button", !empty($_POST['follow_me_button']));
							$cf = configSetting($cf, "smartypants", !empty($_POST['smartypants']));
							$f  = fopen("inc/config.php", "wt");
							$fe = "Could not write configuration to <code>config.php</code>, please make sure that it is writable! Often, this is done through giving every system user the write privileges on that file through FTP.";
							if($f){
								if(fwrite($f, $cf)){
									fclose($f);
									$success = true;
								} else {
									$e[] = $fe;
								}
							} else {
								$e[] = $fe;
							}
						}
					}
				}
			}
		} else {
			$e[] = "Not all required fields were filled in!";
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Set up Tweet Nest<?php if($e){ ?> &#8212; Error!<?php } ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX,NOFOLLOW" />
	<style type="text/css">
		body {
			background: #eee url(data:image/png;base64,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) no-repeat 0 0;
			margin: 50px 0 100px;
			font-family: "Helvetica Neue", Helvetica, sans-serif;
			color: #999;
			text-align: center;
		}
		
		strong { font-weight: bold;  }
		em     { font-style: italic; }
		
		a {
			color: #29d;
			text-decoration: none;
			font-weight: bold;
		}

		a:hover {
			text-decoration: underline;
		}
		
		h1 {
			color: #666;
			font-size: 269%;
			font-weight: normal;
			text-shadow: 0 2px #fafafa;
		}
		
		h1 strong {
			color: #333;
		}
		
		a#pongsk {
			display: block;
			position: absolute;
			top: 60px;
			left: 0;
			width: 187px;
			height: 36px;
			text-indent: -999em;
		}
		
		#content {
			position: relative;
			width: 670px;
			padding: 35px 40px;
			margin: 0 auto;
			background-color: #fff;
			text-align: left;
		}
		
		#content, .serror {
			font: 63% "Lucida Grande", "Lucida Sans", Verdana, Tahoma, sans-serif;
			line-height: 1.4em;
		}
		
		#content strong {
			color: #666;
		}
		
		#content strong.remember {
			color: #900;
		}
		
		code {
			background-color: #eee;
			color: #666;
			padding: 0 2px;
			white-space: nowrap;
		}
		
		h2 {
			font-size: 160%;
			font-weight: normal;
			margin: 3em 0 .7em;
		}
		
		#excerpt {
			font: 230% "Helvetica Neue", Helvetica, sans-serif;
			line-height: 1.6em;
			margin: 0 0 3em;
			margin: 0;
		}
		
		#excerpt strong {
			color: #666;
		}
		
		#greennotice {
			position: absolute;
			left: -120px;
			width: 100px;
			text-align: right;
			color: #5d6;
			text-shadow: 0 1px #fafafa;
		}
		
		#greennotice strong {
			color: #5d6;
		}
		
		#greennotice span {
			display: block;
			width: 24px;
			height: 12px;
			background-color: #5d6;
			margin: 0 0 3px auto;
			border-radius: 3px;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			-o-border-radius: 3px;
			-khtml-border-radius: 3px;
			box-shadow: 0 1px #fafafa;
			-moz-box-shadow: 0 1px #fafafa;
			-webkit-box-shadow: 0 1px #fafafa;
			-o-box-shadow: 0 1px #fafafa;
			-khtml-box-shadow: 0 1px #fafafa;
		}
		
		.address {
			white-space: nowrap;
		}
		
		.input {
			border-top: 1px solid #f3f3f3;
			padding: 10px 0;
		}
		
		.lastinput {
			border-bottom: 1px solid #f3f3f3;
		}
		
		.noteinput {
			border-width: 0;
		}
		
		.input label {
			float: left;
			width: 130px;
			color: #666;
			font-weight: bold;
			text-transform: uppercase;
			margin: 0;
			padding: 10px 0 0;
		}
		
		.input .field, .input .what {
			margin-left: 150px;
		}
		
		.input .field {
			border-left: 3px solid #fff;
			font-size: 140%;
			margin-bottom: 8px;
		}
		
		.input .field input.text, .input .field select {
			border: 1px solid #ddd;
			background: #fff url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAACCAYAAACHSIaUAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAK8AAACvABQqw0mAAAABx0RVh0U29mdHdhcmUAQWRvYmUgRmlyZXdvcmtzIENTNAay06AAAAAWdEVYdENyZWF0aW9uIFRpbWUAMjIvMDUvMTDlojBHAAAAEklEQVQI12NgYGBgpQAzMJOLAR/xAHni8/cuAAAAAElFTkSuQmCC) repeat-x;
			padding: 7px 9px;
			font: 100% "Lucida Grande", "Lucida Sans", Verdana, Tahoma, sans-serif;
			color: #666;
			margin: 0 0 0 1px;
			width: 496px; /* 670 - 150 - 4 - 2*9 - 2*1 */
		}
		
		.input .field input.text:focus, .input .field select:focus {
			border-color: #999;
			box-shadow: 0 0 10px #999;
			-moz-box-shadow: 0 0 10px #999;
			-webkit-box-shadow: 0 0 10px #999;
			-o-box-shadow: 0 0 10px #999;
			-khtml-box-shadow: 0 0 10px #999;
			/*background-image: none;*/
			-webkit-transition-property: -webkit-box-shadow;
			-webkit-transition-duration: .4s;
		}
		
		@media screen and (-webkit-min-device-pixel-ratio:0){
			.input .field input.text:focus {
				outline-width: 0;
			}
		}
		
		.input .field select {
			width: 100%;
		}
		
		.input .field input.checkbox {
			margin-top: 10px;
		}
		
		.input .required {
			border-left-color: #5d6;
		}
		
		.input .what {
			padding-left: 4px;
		}
		
		code, .input .field input.code {
			font: 95% Menlo, "Menlo Regular", Monaco, monospace;
		}
		
		.note {
			border: 1px solid #ccc;
			border-width: 1px 0;
			background-color: #f3f3f3;
			font-size: 120%;
			padding: 9px 12px;
		}
		
		.note p {
			margin: 0;
			line-height: 1.4em;
		}
		
		.note p.btw {
			margin-top: 5px;
			font-size: 83%;
			line-height: 1.4em;
			color: #aaa;
		}
		
		.note .retweet {
			padding-right: 22px;
			background: transparent url(data:image/gif;base64,R0lGODlhEgAOAJEAAKysrP///////wAAACH5BAEHAAIALAAAAAASAA4AAAIlFI6pYOsPYQhnWrpu1erO9DkhiGUlMnand26W28JhGtU20txMAQA7) no-repeat right center;
		}
		
		input.submit {
			background: #fff url(data:image/gif;base64,R0lGODlhFQAeAMQAAP////7+/v39/fz8/Pv7+/r6+vn5+fj4+Pf39/b29vX19fT09PPz8/Ly8vHx8fDw8O/v7+7u7gAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAACH5BAAHAP8ALAAAAAAVAB4AAAWBICCOZGmeaKqiQeu+cCzPsWDfeK4PfO//wKAQSCgaj8hkYclsOp/Q6NNArVqv2IN2y+16v2AvYkwum8/o9DnBbrvfcIV8Tq/b73j7Ys/v+/8MgYKDhIWGh4UNiouMjY4OkJGSk5SVlpQPmZqbnJ0Qn6ChoqOkpaMRqKmqq6ytrqwhADs=) repeat-x left bottom;
			border: 1px solid #ccc;
			color: #666;
			font: bold 175% "Lucida Grande", "Lucida Sans", Verdana, Tahoma, sans-serif;
			padding: 7px 15px;
			text-shadow: 1px 1px #fff;
			cursor: pointer;
			border-radius: 12px;
			-moz-border-radius: 12px;
			-webkit-border-radius: 12px;
			-o-border-radius: 12px;
			-khtml-border-radius: 12px;
		}
		
		input.submit:hover {
			color: #333;
			-webkit-transition-property: color;
			-webkit-transition-duration: .4s;
		}
		
		input.submit:active {
			padding: 8px 15px 6px;
		}
		
		option.deselected {
			font-style: italic;
			color: #999;
		}
		
		.error, .serror {
			border: 1px solid #000;
			border-width: 1px 0;
			background-color: #333;
			color: #ddd;
			padding: 7px 15px;
			font-size: 125%;
			line-height: 1.4em;
		}
		
		.serror {
			width: 670px;
			padding: 7px 40px;
			margin: 0 auto 20px;
			text-align: left;
			font-size: 78%;
		}
		
		#content .error strong, .serror strong {
			color: #fff;
		}
		
		#content .error code, .serror code {
			background-color: #000;
			color: #fff;
		}
		
		.serror code {
			white-space: normal;
		}
		
		.explanation {
			font-size: 130%;
			line-height: 1.6em;
		}
		
		.explanation li {
			margin: 0 0 .5em;
		}
		
	</style>
</head>
<body>
	<div id="container">
		<a id="pongsk" href="http://pongsocket.com/" target="_blank" title="Open pongsocket.com in a new window">pongsocket</a>
		<h1>Set up <strong>Tweet Nest</strong></h1>
<?php if($post && $success && !$e){ 
	$dPath = s(rtrim($sPath, "/"));
?>
		<div id="content">
			<h2 id="excerpt"><strong>Yay!</strong> Tweet Nest has now been set up on your server. There&#8217;s a couple things left you still need to do:</h2>
			<ol class="explanation">
				<li>Remove this <code>setup.php</code> file from your server; it&#8217;s not relevant any longer.</li>
				<li>Visit the <a href="<?php echo $dPath; ?>/maintenance/loaduser.php" target="_blank">load user</a> and <a href="<?php echo $dPath; ?>/maintenance/loadtweets.php" target="_blank">load tweets</a> pages to load your tweets into the system (log in username is your Twitter screen name). If you didn&#8217;t provide an admin password, you&#8217;ll have to do this through the command lines by executing the following commands:
					<ul>
						<li><code>php <?php echo s($fPath); ?>/maintenance/loaduser.php</code></li>
						<li><code>php <?php echo s($fPath); ?>/maintenance/loadtweets.php</code></li>
					</ul>
				The <em>load tweets</em> command will need to be run regularly; the <em>load user</em> command will only need to be run when you change user information like icon, full name or location. <a href="http://pongsocket.com/tweetnest/#installation" target="_blank">More information in the installation guide &rarr;</a>
				</li>
				<li>If you changed the write privileges on <code>config.php</code> prior to running the setup guide, you should now change them back to the normal values to prevent unexpected changes to your configuration.</li>
				<li>Customization! <a href="http://pongsocket.com/tweetnest/#customization" target="_blank">More information in the customization guide &rarr;</a></li>
			</ol>
<!--
INSTALL LOG: <?php var_dump($log); ?>
-->
		</div>
<?php } elseif($e && !$post){ ?>
		<div id="content">
			<h2 id="excerpt"><strong>Whoops!</strong> An error occured that prevented you from being able to install Tweet Nest until it is fixed.</h2>
			<?php echo displayErrors($e); ?>
		</div>
<?php } else { ?>
		<form id="content" action="" method="post">
<?php if($e && $post){ ?>
			<h2 id="excerpt"><strong>Whoops!</strong> An error occured that prevented you from being able to install Tweet Nest until it is fixed.</h2>
			<?php echo displayErrors($e); ?>
<!--
INSTALL LOG: <?php var_dump($log); ?>
-->
<?php } else { ?>
			<h2 id="excerpt">To <strong>install</strong> Tweet Nest on this server and <strong>customize</strong> it to your likings, please fill in the below <strong>one-page</strong> setup configuration. If you want to change any of these values, you can edit the file <code>config.php</code> at any time to do so.</h2>
<?php } ?>
			<h2>Basic settings</h2>
			<div id="greennotice"><span></span>Green color means the value is <strong>required</strong></div>
			<div class="input">
				<label for="twitter_screenname">Twitter screen name</label>
				<div class="field required"><input type="text" class="text" name="twitter_screenname" id="twitter_screenname" maxlength="15" value="<?php echo s($_POST['twitter_screenname']); ?>" /></div>
				<div class="what">Your Twitter screen name is the name that&#8217;s in the URL of your profile page. <span class="address">Example: http://twitter.com/<strong><?php echo $famousTwitterers[array_rand($famousTwitterers)]; ?></strong></span></div>
			</div>
			<div class="input">
				<label for="tz">Your time zone</label>
				<div class="field required">
				<select name="tz" id="tz"><option class="deselected" value=""<?php if(!$_POST['tz']){ ?> selected="selected"<?php } ?>>Choose...</option>
				<option value="Africa/Abidjan"<?php if($_POST['tz'] == "Africa/Abidjan"){ ?> selected="selected"<?php } ?>>Africa: Abidjan</option><option value="Africa/Accra"<?php if($_POST['tz'] == "Africa/Accra"){ ?> selected="selected"<?php } ?>>Africa: Accra</option><option value="Africa/Addis_Ababa"<?php if($_POST['tz'] == "Africa/Addis_Ababa"){ ?> selected="selected"<?php } ?>>Africa: Addis Ababa</option><option value="Africa/Algiers"<?php if($_POST['tz'] == "Africa/Algiers"){ ?> selected="selected"<?php } ?>>Africa: Algiers</option><option value="Africa/Asmera"<?php if($_POST['tz'] == "Africa/Asmera"){ ?> selected="selected"<?php } ?>>Africa: Asmera</option><option value="Africa/Bamako"<?php if($_POST['tz'] == "Africa/Bamako"){ ?> selected="selected"<?php } ?>>Africa: Bamako</option><option value="Africa/Bangui"<?php if($_POST['tz'] == "Africa/Bangui"){ ?> selected="selected"<?php } ?>>Africa: Bangui</option><option value="Africa/Banjul"<?php if($_POST['tz'] == "Africa/Banjul"){ ?> selected="selected"<?php } ?>>Africa: Banjul</option><option value="Africa/Bissau"<?php if($_POST['tz'] == "Africa/Bissau"){ ?> selected="selected"<?php } ?>>Africa: Bissau</option><option value="Africa/Blantyre"<?php if($_POST['tz'] == "Africa/Blantyre"){ ?> selected="selected"<?php } ?>>Africa: Blantyre</option><option value="Africa/Brazzaville"<?php if($_POST['tz'] == "Africa/Brazzaville"){ ?> selected="selected"<?php } ?>>Africa: Brazzaville</option><option value="Africa/Bujumbura"<?php if($_POST['tz'] == "Africa/Bujumbura"){ ?> selected="selected"<?php } ?>>Africa: Bujumbura</option><option value="Africa/Cairo"<?php if($_POST['tz'] == "Africa/Cairo"){ ?> selected="selected"<?php } ?>>Africa: Cairo</option><option value="Africa/Casablanca"<?php if($_POST['tz'] == "Africa/Casablanca"){ ?> selected="selected"<?php } ?>>Africa: Casablanca</option><option value="Africa/Ceuta"<?php if($_POST['tz'] == "Africa/Ceuta"){ ?> selected="selected"<?php } ?>>Africa: Ceuta</option><option value="Africa/Conakry"<?php if($_POST['tz'] == "Africa/Conakry"){ ?> selected="selected"<?php } ?>>Africa: Conakry</option><option value="Africa/Dakar"<?php if($_POST['tz'] == "Africa/Dakar"){ ?> selected="selected"<?php } ?>>Africa: Dakar</option><option value="Africa/Dar_es_Salaam"<?php if($_POST['tz'] == "Africa/Dar_es_Salaam"){ ?> selected="selected"<?php } ?>>Africa: Dar es Salaam</option><option value="Africa/Djibouti"<?php if($_POST['tz'] == "Africa/Djibouti"){ ?> selected="selected"<?php } ?>>Africa: Djibouti</option><option value="Africa/Douala"<?php if($_POST['tz'] == "Africa/Douala"){ ?> selected="selected"<?php } ?>>Africa: Douala</option><option value="Africa/El_Aaiun"<?php if($_POST['tz'] == "Africa/El_Aaiun"){ ?> selected="selected"<?php } ?>>Africa: El Aaiun</option><option value="Africa/Freetown"<?php if($_POST['tz'] == "Africa/Freetown"){ ?> selected="selected"<?php } ?>>Africa: Freetown</option><option value="Africa/Gaborone"<?php if($_POST['tz'] == "Africa/Gaborone"){ ?> selected="selected"<?php } ?>>Africa: Gaborone</option><option value="Africa/Harare"<?php if($_POST['tz'] == "Africa/Harare"){ ?> selected="selected"<?php } ?>>Africa: Harare</option><option value="Africa/Johannesburg"<?php if($_POST['tz'] == "Africa/Johannesburg"){ ?> selected="selected"<?php } ?>>Africa: Johannesburg</option><option value="Africa/Kampala"<?php if($_POST['tz'] == "Africa/Kampala"){ ?> selected="selected"<?php } ?>>Africa: Kampala</option><option value="Africa/Khartoum"<?php if($_POST['tz'] == "Africa/Khartoum"){ ?> selected="selected"<?php } ?>>Africa: Khartoum</option><option value="Africa/Kigali"<?php if($_POST['tz'] == "Africa/Kigali"){ ?> selected="selected"<?php } ?>>Africa: Kigali</option><option value="Africa/Kinshasa"<?php if($_POST['tz'] == "Africa/Kinshasa"){ ?> selected="selected"<?php } ?>>Africa: Kinshasa</option><option value="Africa/Lagos"<?php if($_POST['tz'] == "Africa/Lagos"){ ?> selected="selected"<?php } ?>>Africa: Lagos</option><option value="Africa/Libreville"<?php if($_POST['tz'] == "Africa/Libreville"){ ?> selected="selected"<?php } ?>>Africa: Libreville</option><option value="Africa/Lome"<?php if($_POST['tz'] == "Africa/Lome"){ ?> selected="selected"<?php } ?>>Africa: Lome</option><option value="Africa/Luanda"<?php if($_POST['tz'] == "Africa/Luanda"){ ?> selected="selected"<?php } ?>>Africa: Luanda</option><option value="Africa/Lubumbashi"<?php if($_POST['tz'] == "Africa/Lubumbashi"){ ?> selected="selected"<?php } ?>>Africa: Lubumbashi</option><option value="Africa/Lusaka"<?php if($_POST['tz'] == "Africa/Lusaka"){ ?> selected="selected"<?php } ?>>Africa: Lusaka</option><option value="Africa/Malabo"<?php if($_POST['tz'] == "Africa/Malabo"){ ?> selected="selected"<?php } ?>>Africa: Malabo</option><option value="Africa/Maputo"<?php if($_POST['tz'] == "Africa/Maputo"){ ?> selected="selected"<?php } ?>>Africa: Maputo</option><option value="Africa/Maseru"<?php if($_POST['tz'] == "Africa/Maseru"){ ?> selected="selected"<?php } ?>>Africa: Maseru</option><option value="Africa/Mbabane"<?php if($_POST['tz'] == "Africa/Mbabane"){ ?> selected="selected"<?php } ?>>Africa: Mbabane</option><option value="Africa/Mogadishu"<?php if($_POST['tz'] == "Africa/Mogadishu"){ ?> selected="selected"<?php } ?>>Africa: Mogadishu</option><option value="Africa/Monrovia"<?php if($_POST['tz'] == "Africa/Monrovia"){ ?> selected="selected"<?php } ?>>Africa: Monrovia</option><option value="Africa/Nairobi"<?php if($_POST['tz'] == "Africa/Nairobi"){ ?> selected="selected"<?php } ?>>Africa: Nairobi</option><option value="Africa/Ndjamena"<?php if($_POST['tz'] == "Africa/Ndjamena"){ ?> selected="selected"<?php } ?>>Africa: Ndjamena</option><option value="Africa/Niamey"<?php if($_POST['tz'] == "Africa/Niamey"){ ?> selected="selected"<?php } ?>>Africa: Niamey</option><option value="Africa/Nouakchott"<?php if($_POST['tz'] == "Africa/Nouakchott"){ ?> selected="selected"<?php } ?>>Africa: Nouakchott</option><option value="Africa/Ouagadougou"<?php if($_POST['tz'] == "Africa/Ouagadougou"){ ?> selected="selected"<?php } ?>>Africa: Ouagadougou</option><option value="Africa/Porto-Novo"<?php if($_POST['tz'] == "Africa/Porto-Novo"){ ?> selected="selected"<?php } ?>>Africa: Porto-Novo</option><option value="Africa/Sao_Tome"<?php if($_POST['tz'] == "Africa/Sao_Tome"){ ?> selected="selected"<?php } ?>>Africa: Sao Tome</option><option value="Africa/Timbuktu"<?php if($_POST['tz'] == "Africa/Timbuktu"){ ?> selected="selected"<?php } ?>>Africa: Timbuktu</option><option value="Africa/Tripoli"<?php if($_POST['tz'] == "Africa/Tripoli"){ ?> selected="selected"<?php } ?>>Africa: Tripoli</option><option value="Africa/Tunis"<?php if($_POST['tz'] == "Africa/Tunis"){ ?> selected="selected"<?php } ?>>Africa: Tunis</option><option value="Africa/Windhoek"<?php if($_POST['tz'] == "Africa/Windhoek"){ ?> selected="selected"<?php } ?>>Africa: Windhoek</option>
				<option value="America/Adak"<?php if($_POST['tz'] == "America/Adak"){ ?> selected="selected"<?php } ?>>America: Adak</option><option value="America/Anchorage"<?php if($_POST['tz'] == "America/Anchorage"){ ?> selected="selected"<?php } ?>>America: Anchorage</option><option value="America/Anguilla"<?php if($_POST['tz'] == "America/Anguilla"){ ?> selected="selected"<?php } ?>>America: Anguilla</option><option value="America/Antigua"<?php if($_POST['tz'] == "America/Antigua"){ ?> selected="selected"<?php } ?>>America: Antigua</option><option value="America/Araguaina"<?php if($_POST['tz'] == "America/Araguaina"){ ?> selected="selected"<?php } ?>>America: Araguaina</option><option value="America/Aruba"<?php if($_POST['tz'] == "America/Aruba"){ ?> selected="selected"<?php } ?>>America: Aruba</option><option value="America/Asuncion"<?php if($_POST['tz'] == "America/Asuncion"){ ?> selected="selected"<?php } ?>>America: Asuncion</option><option value="America/Atka"<?php if($_POST['tz'] == "America/Atka"){ ?> selected="selected"<?php } ?>>America: Atka</option><option value="America/Barbados"<?php if($_POST['tz'] == "America/Barbados"){ ?> selected="selected"<?php } ?>>America: Barbados</option><option value="America/Belem"<?php if($_POST['tz'] == "America/Belem"){ ?> selected="selected"<?php } ?>>America: Belem</option><option value="America/Belize"<?php if($_POST['tz'] == "America/Belize"){ ?> selected="selected"<?php } ?>>America: Belize</option><option value="America/Boa_Vista"<?php if($_POST['tz'] == "America/Boa_Vista"){ ?> selected="selected"<?php } ?>>America: Boa Vista</option><option value="America/Bogota"<?php if($_POST['tz'] == "America/Bogota"){ ?> selected="selected"<?php } ?>>America: Bogota</option><option value="America/Boise"<?php if($_POST['tz'] == "America/Boise"){ ?> selected="selected"<?php } ?>>America: Boise</option><option value="America/Buenos_Aires"<?php if($_POST['tz'] == "America/Buenos_Aires"){ ?> selected="selected"<?php } ?>>America: Buenos Aires</option><option value="America/Cambridge_Bay"<?php if($_POST['tz'] == "America/Cambridge_Bay"){ ?> selected="selected"<?php } ?>>America: Cambridge Bay</option><option value="America/Cancun"<?php if($_POST['tz'] == "America/Cancun"){ ?> selected="selected"<?php } ?>>America: Cancun</option><option value="America/Caracas"<?php if($_POST['tz'] == "America/Caracas"){ ?> selected="selected"<?php } ?>>America: Caracas</option><option value="America/Catamarca"<?php if($_POST['tz'] == "America/Catamarca"){ ?> selected="selected"<?php } ?>>America: Catamarca</option><option value="America/Cayenne"<?php if($_POST['tz'] == "America/Cayenne"){ ?> selected="selected"<?php } ?>>America: Cayenne</option><option value="America/Cayman"<?php if($_POST['tz'] == "America/Cayman"){ ?> selected="selected"<?php } ?>>America: Cayman</option><option value="America/Chicago"<?php if($_POST['tz'] == "America/Chicago"){ ?> selected="selected"<?php } ?>>America: Chicago</option><option value="America/Chihuahua"<?php if($_POST['tz'] == "America/Chihuahua"){ ?> selected="selected"<?php } ?>>America: Chihuahua</option><option value="America/Cordoba"<?php if($_POST['tz'] == "America/Cordoba"){ ?> selected="selected"<?php } ?>>America: Cordoba</option><option value="America/Costa_Rica"<?php if($_POST['tz'] == "America/Costa_Rica"){ ?> selected="selected"<?php } ?>>America: Costa Rica</option><option value="America/Cuiaba"<?php if($_POST['tz'] == "America/Cuiaba"){ ?> selected="selected"<?php } ?>>America: Cuiaba</option><option value="America/Curacao"<?php if($_POST['tz'] == "America/Curacao"){ ?> selected="selected"<?php } ?>>America: Curacao</option><option value="America/Danmarkshavn"<?php if($_POST['tz'] == "America/Danmarkshavn"){ ?> selected="selected"<?php } ?>>America: Danmarkshavn</option><option value="America/Dawson"<?php if($_POST['tz'] == "America/Dawson"){ ?> selected="selected"<?php } ?>>America: Dawson</option><option value="America/Dawson_Creek"<?php if($_POST['tz'] == "America/Dawson_Creek"){ ?> selected="selected"<?php } ?>>America: Dawson Creek</option><option value="America/Denver"<?php if($_POST['tz'] == "America/Denver"){ ?> selected="selected"<?php } ?>>America: Denver</option><option value="America/Detroit"<?php if($_POST['tz'] == "America/Detroit"){ ?> selected="selected"<?php } ?>>America: Detroit</option><option value="America/Dominica"<?php if($_POST['tz'] == "America/Dominica"){ ?> selected="selected"<?php } ?>>America: Dominica</option><option value="America/Edmonton"<?php if($_POST['tz'] == "America/Edmonton"){ ?> selected="selected"<?php } ?>>America: Edmonton</option><option value="America/Eirunepe"<?php if($_POST['tz'] == "America/Eirunepe"){ ?> selected="selected"<?php } ?>>America: Eirunepe</option><option value="America/El_Salvador"<?php if($_POST['tz'] == "America/El_Salvador"){ ?> selected="selected"<?php } ?>>America: El Salvador</option><option value="America/Ensenada"<?php if($_POST['tz'] == "America/Ensenada"){ ?> selected="selected"<?php } ?>>America: Ensenada</option><option value="America/Fort_Wayne"<?php if($_POST['tz'] == "America/Fort_Wayne"){ ?> selected="selected"<?php } ?>>America: Fort Wayne</option><option value="America/Fortaleza"<?php if($_POST['tz'] == "America/Fortaleza"){ ?> selected="selected"<?php } ?>>America: Fortaleza</option><option value="America/Glace_Bay"<?php if($_POST['tz'] == "America/Glace_Bay"){ ?> selected="selected"<?php } ?>>America: Glace Bay</option><option value="America/Godthab"<?php if($_POST['tz'] == "America/Godthab"){ ?> selected="selected"<?php } ?>>America: Godthab</option><option value="America/Goose_Bay"<?php if($_POST['tz'] == "America/Goose_Bay"){ ?> selected="selected"<?php } ?>>America: Goose Bay</option><option value="America/Grand_Turk"<?php if($_POST['tz'] == "America/Grand_Turk"){ ?> selected="selected"<?php } ?>>America: Grand Turk</option><option value="America/Grenada"<?php if($_POST['tz'] == "America/Grenada"){ ?> selected="selected"<?php } ?>>America: Grenada</option><option value="America/Guadeloupe"<?php if($_POST['tz'] == "America/Guadeloupe"){ ?> selected="selected"<?php } ?>>America: Guadeloupe</option><option value="America/Guatemala"<?php if($_POST['tz'] == "America/Guatemala"){ ?> selected="selected"<?php } ?>>America: Guatemala</option><option value="America/Guayaquil"<?php if($_POST['tz'] == "America/Guayaquil"){ ?> selected="selected"<?php } ?>>America: Guayaquil</option><option value="America/Guyana"<?php if($_POST['tz'] == "America/Guyana"){ ?> selected="selected"<?php } ?>>America: Guyana</option><option value="America/Halifax"<?php if($_POST['tz'] == "America/Halifax"){ ?> selected="selected"<?php } ?>>America: Halifax</option><option value="America/Havana"<?php if($_POST['tz'] == "America/Havana"){ ?> selected="selected"<?php } ?>>America: Havana</option><option value="America/Hermosillo"<?php if($_POST['tz'] == "America/Hermosillo"){ ?> selected="selected"<?php } ?>>America: Hermosillo</option><option value="America/Indianapolis"<?php if($_POST['tz'] == "America/Indianapolis"){ ?> selected="selected"<?php } ?>>America: Indianapolis</option><option value="America/Inuvik"<?php if($_POST['tz'] == "America/Inuvik"){ ?> selected="selected"<?php } ?>>America: Inuvik</option><option value="America/Iqaluit"<?php if($_POST['tz'] == "America/Iqaluit"){ ?> selected="selected"<?php } ?>>America: Iqaluit</option><option value="America/Jamaica"<?php if($_POST['tz'] == "America/Jamaica"){ ?> selected="selected"<?php } ?>>America: Jamaica</option><option value="America/Jujuy"<?php if($_POST['tz'] == "America/Jujuy"){ ?> selected="selected"<?php } ?>>America: Jujuy</option><option value="America/Juneau"<?php if($_POST['tz'] == "America/Juneau"){ ?> selected="selected"<?php } ?>>America: Juneau</option><option value="America/Knox_IN"<?php if($_POST['tz'] == "America/Knox_IN"){ ?> selected="selected"<?php } ?>>America: Knox IN</option><option value="America/La_Paz"<?php if($_POST['tz'] == "America/La_Paz"){ ?> selected="selected"<?php } ?>>America: La Paz</option><option value="America/Lima"<?php if($_POST['tz'] == "America/Lima"){ ?> selected="selected"<?php } ?>>America: Lima</option><option value="America/Los_Angeles"<?php if($_POST['tz'] == "America/Los_Angeles"){ ?> selected="selected"<?php } ?>>America: Los Angeles</option><option value="America/Louisville"<?php if($_POST['tz'] == "America/Louisville"){ ?> selected="selected"<?php } ?>>America: Louisville</option><option value="America/Maceio"<?php if($_POST['tz'] == "America/Maceio"){ ?> selected="selected"<?php } ?>>America: Maceio</option><option value="America/Managua"<?php if($_POST['tz'] == "America/Managua"){ ?> selected="selected"<?php } ?>>America: Managua</option><option value="America/Manaus"<?php if($_POST['tz'] == "America/Manaus"){ ?> selected="selected"<?php } ?>>America: Manaus</option><option value="America/Martinique"<?php if($_POST['tz'] == "America/Martinique"){ ?> selected="selected"<?php } ?>>America: Martinique</option><option value="America/Mazatlan"<?php if($_POST['tz'] == "America/Mazatlan"){ ?> selected="selected"<?php } ?>>America: Mazatlan</option><option value="America/Mendoza"<?php if($_POST['tz'] == "America/Mendoza"){ ?> selected="selected"<?php } ?>>America: Mendoza</option><option value="America/Menominee"<?php if($_POST['tz'] == "America/Menominee"){ ?> selected="selected"<?php } ?>>America: Menominee</option><option value="America/Merida"<?php if($_POST['tz'] == "America/Merida"){ ?> selected="selected"<?php } ?>>America: Merida</option><option value="America/Mexico_City"<?php if($_POST['tz'] == "America/Mexico_City"){ ?> selected="selected"<?php } ?>>America: Mexico City</option><option value="America/Miquelon"<?php if($_POST['tz'] == "America/Miquelon"){ ?> selected="selected"<?php } ?>>America: Miquelon</option><option value="America/Monterrey"<?php if($_POST['tz'] == "America/Monterrey"){ ?> selected="selected"<?php } ?>>America: Monterrey</option><option value="America/Montevideo"<?php if($_POST['tz'] == "America/Montevideo"){ ?> selected="selected"<?php } ?>>America: Montevideo</option><option value="America/Montreal"<?php if($_POST['tz'] == "America/Montreal"){ ?> selected="selected"<?php } ?>>America: Montreal</option><option value="America/Montserrat"<?php if($_POST['tz'] == "America/Montserrat"){ ?> selected="selected"<?php } ?>>America: Montserrat</option><option value="America/Nassau"<?php if($_POST['tz'] == "America/Nassau"){ ?> selected="selected"<?php } ?>>America: Nassau</option><option value="America/New_York"<?php if($_POST['tz'] == "America/New_York"){ ?> selected="selected"<?php } ?>>America: New York</option><option value="America/Nipigon"<?php if($_POST['tz'] == "America/Nipigon"){ ?> selected="selected"<?php } ?>>America: Nipigon</option><option value="America/Nome"<?php if($_POST['tz'] == "America/Nome"){ ?> selected="selected"<?php } ?>>America: Nome</option><option value="America/Noronha"<?php if($_POST['tz'] == "America/Noronha"){ ?> selected="selected"<?php } ?>>America: Noronha</option><option value="America/Panama"<?php if($_POST['tz'] == "America/Panama"){ ?> selected="selected"<?php } ?>>America: Panama</option><option value="America/Pangnirtung"<?php if($_POST['tz'] == "America/Pangnirtung"){ ?> selected="selected"<?php } ?>>America: Pangnirtung</option><option value="America/Paramaribo"<?php if($_POST['tz'] == "America/Paramaribo"){ ?> selected="selected"<?php } ?>>America: Paramaribo</option><option value="America/Phoenix"<?php if($_POST['tz'] == "America/Phoenix"){ ?> selected="selected"<?php } ?>>America: Phoenix</option><option value="America/Port-au-Prince"<?php if($_POST['tz'] == "America/Port-au-Prince"){ ?> selected="selected"<?php } ?>>America: Port-au-Prince</option><option value="America/Port_of_Spain"<?php if($_POST['tz'] == "America/Port_of_Spain"){ ?> selected="selected"<?php } ?>>America: Port of Spain</option><option value="America/Porto_Acre"<?php if($_POST['tz'] == "America/Porto_Acre"){ ?> selected="selected"<?php } ?>>America: Porto Acre</option><option value="America/Porto_Velho"<?php if($_POST['tz'] == "America/Porto_Velho"){ ?> selected="selected"<?php } ?>>America: Porto Velho</option><option value="America/Puerto_Rico"<?php if($_POST['tz'] == "America/Puerto_Rico"){ ?> selected="selected"<?php } ?>>America: Puerto Rico</option><option value="America/Rainy_River"<?php if($_POST['tz'] == "America/Rainy_River"){ ?> selected="selected"<?php } ?>>America: Rainy River</option><option value="America/Rankin_Inlet"<?php if($_POST['tz'] == "America/Rankin_Inlet"){ ?> selected="selected"<?php } ?>>America: Rankin Inlet</option><option value="America/Recife"<?php if($_POST['tz'] == "America/Recife"){ ?> selected="selected"<?php } ?>>America: Recife</option><option value="America/Regina"<?php if($_POST['tz'] == "America/Regina"){ ?> selected="selected"<?php } ?>>America: Regina</option><option value="America/Rio_Branco"<?php if($_POST['tz'] == "America/Rio_Branco"){ ?> selected="selected"<?php } ?>>America: Rio Branco</option><option value="America/Rosario"<?php if($_POST['tz'] == "America/Rosario"){ ?> selected="selected"<?php } ?>>America: Rosario</option><option value="America/Santiago"<?php if($_POST['tz'] == "America/Santiago"){ ?> selected="selected"<?php } ?>>America: Santiago</option><option value="America/Santo_Domingo"<?php if($_POST['tz'] == "America/Santo_Domingo"){ ?> selected="selected"<?php } ?>>America: Santo Domingo</option><option value="America/Sao_Paulo"<?php if($_POST['tz'] == "America/Sao_Paulo"){ ?> selected="selected"<?php } ?>>America: Sao Paulo</option><option value="America/Scoresbysund"<?php if($_POST['tz'] == "America/Scoresbysund"){ ?> selected="selected"<?php } ?>>America: Scoresbysund</option><option value="America/Shiprock"<?php if($_POST['tz'] == "America/Shiprock"){ ?> selected="selected"<?php } ?>>America: Shiprock</option><option value="America/St_Johns"<?php if($_POST['tz'] == "America/St_Johns"){ ?> selected="selected"<?php } ?>>America: St Johns</option><option value="America/St_Kitts"<?php if($_POST['tz'] == "America/St_Kitts"){ ?> selected="selected"<?php } ?>>America: St Kitts</option><option value="America/St_Lucia"<?php if($_POST['tz'] == "America/St_Lucia"){ ?> selected="selected"<?php } ?>>America: St Lucia</option><option value="America/St_Thomas"<?php if($_POST['tz'] == "America/St_Thomas"){ ?> selected="selected"<?php } ?>>America: St Thomas</option><option value="America/St_Vincent"<?php if($_POST['tz'] == "America/St_Vincent"){ ?> selected="selected"<?php } ?>>America: St Vincent</option><option value="America/Swift_Current"<?php if($_POST['tz'] == "America/Swift_Current"){ ?> selected="selected"<?php } ?>>America: Swift Current</option><option value="America/Tegucigalpa"<?php if($_POST['tz'] == "America/Tegucigalpa"){ ?> selected="selected"<?php } ?>>America: Tegucigalpa</option><option value="America/Thule"<?php if($_POST['tz'] == "America/Thule"){ ?> selected="selected"<?php } ?>>America: Thule</option><option value="America/Thunder_Bay"<?php if($_POST['tz'] == "America/Thunder_Bay"){ ?> selected="selected"<?php } ?>>America: Thunder Bay</option><option value="America/Tijuana"<?php if($_POST['tz'] == "America/Tijuana"){ ?> selected="selected"<?php } ?>>America: Tijuana</option><option value="America/Tortola"<?php if($_POST['tz'] == "America/Tortola"){ ?> selected="selected"<?php } ?>>America: Tortola</option><option value="America/Vancouver"<?php if($_POST['tz'] == "America/Vancouver"){ ?> selected="selected"<?php } ?>>America: Vancouver</option><option value="America/Virgin"<?php if($_POST['tz'] == "America/Virgin"){ ?> selected="selected"<?php } ?>>America: Virgin</option><option value="America/Whitehorse"<?php if($_POST['tz'] == "America/Whitehorse"){ ?> selected="selected"<?php } ?>>America: Whitehorse</option><option value="America/Winnipeg"<?php if($_POST['tz'] == "America/Winnipeg"){ ?> selected="selected"<?php } ?>>America: Winnipeg</option><option value="America/Yakutat"<?php if($_POST['tz'] == "America/Yakutat"){ ?> selected="selected"<?php } ?>>America: Yakutat</option><option value="America/Yellowknife"<?php if($_POST['tz'] == "America/Yellowknife"){ ?> selected="selected"<?php } ?>>America: Yellowknife</option>
				<option value="Antarctica/Casey"<?php if($_POST['tz'] == "Antarctica/Casey"){ ?> selected="selected"<?php } ?>>Antarctica: Casey</option><option value="Antarctica/Davis"<?php if($_POST['tz'] == "Antarctica/Davis"){ ?> selected="selected"<?php } ?>>Antarctica: Davis</option><option value="Antarctica/DumontDUrville"<?php if($_POST['tz'] == "Antarctica/DumontDUrville"){ ?> selected="selected"<?php } ?>>Antarctica: DumontDUrville</option><option value="Antarctica/Mawson"<?php if($_POST['tz'] == "Antarctica/Mawson"){ ?> selected="selected"<?php } ?>>Antarctica: Mawson</option><option value="Antarctica/McMurdo"<?php if($_POST['tz'] == "Antarctica/McMurdo"){ ?> selected="selected"<?php } ?>>Antarctica: McMurdo</option><option value="Antarctica/Palmer"<?php if($_POST['tz'] == "Antarctica/Palmer"){ ?> selected="selected"<?php } ?>>Antarctica: Palmer</option><option value="Antarctica/South_Pole"<?php if($_POST['tz'] == "Antarctica/South_Pole"){ ?> selected="selected"<?php } ?>>Antarctica: South Pole</option><option value="Antarctica/Syowa"<?php if($_POST['tz'] == "Antarctica/Syowa"){ ?> selected="selected"<?php } ?>>Antarctica: Syowa</option><option value="Antarctica/Vostok"<?php if($_POST['tz'] == "Antarctica/Vostok"){ ?> selected="selected"<?php } ?>>Antarctica: Vostok</option>
				<option value="Arctic/Longyearbyen"<?php if($_POST['tz'] == "Arctic/Longyearbyen"){ ?> selected="selected"<?php } ?>>Arctic: Longyearbyen</option>
				<option value="Asia/Aden"<?php if($_POST['tz'] == "Asia/Aden"){ ?> selected="selected"<?php } ?>>Asia: Aden</option><option value="Asia/Almaty"<?php if($_POST['tz'] == "Asia/Almaty"){ ?> selected="selected"<?php } ?>>Asia: Almaty</option><option value="Asia/Amman"<?php if($_POST['tz'] == "Asia/Amman"){ ?> selected="selected"<?php } ?>>Asia: Amman</option><option value="Asia/Anadyr"<?php if($_POST['tz'] == "Asia/Anadyr"){ ?> selected="selected"<?php } ?>>Asia: Anadyr</option><option value="Asia/Aqtau"<?php if($_POST['tz'] == "Asia/Aqtau"){ ?> selected="selected"<?php } ?>>Asia: Aqtau</option><option value="Asia/Aqtobe"<?php if($_POST['tz'] == "Asia/Aqtobe"){ ?> selected="selected"<?php } ?>>Asia: Aqtobe</option><option value="Asia/Ashgabat"<?php if($_POST['tz'] == "Asia/Ashgabat"){ ?> selected="selected"<?php } ?>>Asia: Ashgabat</option><option value="Asia/Ashkhabad"<?php if($_POST['tz'] == "Asia/Ashkhabad"){ ?> selected="selected"<?php } ?>>Asia: Ashkhabad</option><option value="Asia/Baghdad"<?php if($_POST['tz'] == "Asia/Baghdad"){ ?> selected="selected"<?php } ?>>Asia: Baghdad</option><option value="Asia/Bahrain"<?php if($_POST['tz'] == "Asia/Bahrain"){ ?> selected="selected"<?php } ?>>Asia: Bahrain</option><option value="Asia/Baku"<?php if($_POST['tz'] == "Asia/Baku"){ ?> selected="selected"<?php } ?>>Asia: Baku</option><option value="Asia/Bangkok"<?php if($_POST['tz'] == "Asia/Bangkok"){ ?> selected="selected"<?php } ?>>Asia: Bangkok</option><option value="Asia/Beirut"<?php if($_POST['tz'] == "Asia/Beirut"){ ?> selected="selected"<?php } ?>>Asia: Beirut</option><option value="Asia/Bishkek"<?php if($_POST['tz'] == "Asia/Bishkek"){ ?> selected="selected"<?php } ?>>Asia: Bishkek</option><option value="Asia/Brunei"<?php if($_POST['tz'] == "Asia/Brunei"){ ?> selected="selected"<?php } ?>>Asia: Brunei</option><option value="Asia/Calcutta"<?php if($_POST['tz'] == "Asia/Calcutta"){ ?> selected="selected"<?php } ?>>Asia: Calcutta</option><option value="Asia/Choibalsan"<?php if($_POST['tz'] == "Asia/Choibalsan"){ ?> selected="selected"<?php } ?>>Asia: Choibalsan</option><option value="Asia/Chongqing"<?php if($_POST['tz'] == "Asia/Chongqing"){ ?> selected="selected"<?php } ?>>Asia: Chongqing</option><option value="Asia/Chungking"<?php if($_POST['tz'] == "Asia/Chungking"){ ?> selected="selected"<?php } ?>>Asia: Chungking</option><option value="Asia/Colombo"<?php if($_POST['tz'] == "Asia/Colombo"){ ?> selected="selected"<?php } ?>>Asia: Colombo</option><option value="Asia/Dacca"<?php if($_POST['tz'] == "Asia/Dacca"){ ?> selected="selected"<?php } ?>>Asia: Dacca</option><option value="Asia/Damascus"<?php if($_POST['tz'] == "Asia/Damascus"){ ?> selected="selected"<?php } ?>>Asia: Damascus</option><option value="Asia/Dhaka"<?php if($_POST['tz'] == "Asia/Dhaka"){ ?> selected="selected"<?php } ?>>Asia: Dhaka</option><option value="Asia/Dili"<?php if($_POST['tz'] == "Asia/Dili"){ ?> selected="selected"<?php } ?>>Asia: Dili</option><option value="Asia/Dubai"<?php if($_POST['tz'] == "Asia/Dubai"){ ?> selected="selected"<?php } ?>>Asia: Dubai</option><option value="Asia/Dushanbe"<?php if($_POST['tz'] == "Asia/Dushanbe"){ ?> selected="selected"<?php } ?>>Asia: Dushanbe</option><option value="Asia/Gaza"<?php if($_POST['tz'] == "Asia/Gaza"){ ?> selected="selected"<?php } ?>>Asia: Gaza</option><option value="Asia/Harbin"<?php if($_POST['tz'] == "Asia/Harbin"){ ?> selected="selected"<?php } ?>>Asia: Harbin</option><option value="Asia/Hong_Kong"<?php if($_POST['tz'] == "Asia/Hong_Kong"){ ?> selected="selected"<?php } ?>>Asia: Hong Kong</option><option value="Asia/Hovd"<?php if($_POST['tz'] == "Asia/Hovd"){ ?> selected="selected"<?php } ?>>Asia: Hovd</option><option value="Asia/Irkutsk"<?php if($_POST['tz'] == "Asia/Irkutsk"){ ?> selected="selected"<?php } ?>>Asia: Irkutsk</option><option value="Asia/Istanbul"<?php if($_POST['tz'] == "Asia/Istanbul"){ ?> selected="selected"<?php } ?>>Asia: Istanbul</option><option value="Asia/Jakarta"<?php if($_POST['tz'] == "Asia/Jakarta"){ ?> selected="selected"<?php } ?>>Asia: Jakarta</option><option value="Asia/Jayapura"<?php if($_POST['tz'] == "Asia/Jayapura"){ ?> selected="selected"<?php } ?>>Asia: Jayapura</option><option value="Asia/Jerusalem"<?php if($_POST['tz'] == "Asia/Jerusalem"){ ?> selected="selected"<?php } ?>>Asia: Jerusalem</option><option value="Asia/Kabul"<?php if($_POST['tz'] == "Asia/Kabul"){ ?> selected="selected"<?php } ?>>Asia: Kabul</option><option value="Asia/Kamchatka"<?php if($_POST['tz'] == "Asia/Kamchatka"){ ?> selected="selected"<?php } ?>>Asia: Kamchatka</option><option value="Asia/Karachi"<?php if($_POST['tz'] == "Asia/Karachi"){ ?> selected="selected"<?php } ?>>Asia: Karachi</option><option value="Asia/Kashgar"<?php if($_POST['tz'] == "Asia/Kashgar"){ ?> selected="selected"<?php } ?>>Asia: Kashgar</option><option value="Asia/Katmandu"<?php if($_POST['tz'] == "Asia/Katmandu"){ ?> selected="selected"<?php } ?>>Asia: Katmandu</option><option value="Asia/Kolkata"<?php if($_POST['tz'] == "Asia/Kolkata"){ ?> selected="selected"<?php } ?>>Asia: Kolkata</option><option value="Asia/Krasnoyarsk"<?php if($_POST['tz'] == "Asia/Krasnoyarsk"){ ?> selected="selected"<?php } ?>>Asia: Krasnoyarsk</option><option value="Asia/Kuala_Lumpur"<?php if($_POST['tz'] == "Asia/Kuala_Lumpur"){ ?> selected="selected"<?php } ?>>Asia: Kuala Lumpur</option><option value="Asia/Kuching"<?php if($_POST['tz'] == "Asia/Kuching"){ ?> selected="selected"<?php } ?>>Asia: Kuching</option><option value="Asia/Kuwait"<?php if($_POST['tz'] == "Asia/Kuwait"){ ?> selected="selected"<?php } ?>>Asia: Kuwait</option><option value="Asia/Macao"<?php if($_POST['tz'] == "Asia/Macao"){ ?> selected="selected"<?php } ?>>Asia: Macao</option><option value="Asia/Magadan"<?php if($_POST['tz'] == "Asia/Magadan"){ ?> selected="selected"<?php } ?>>Asia: Magadan</option><option value="Asia/Manila"<?php if($_POST['tz'] == "Asia/Manila"){ ?> selected="selected"<?php } ?>>Asia: Manila</option><option value="Asia/Muscat"<?php if($_POST['tz'] == "Asia/Muscat"){ ?> selected="selected"<?php } ?>>Asia: Muscat</option><option value="Asia/Nicosia"<?php if($_POST['tz'] == "Asia/Nicosia"){ ?> selected="selected"<?php } ?>>Asia: Nicosia</option><option value="Asia/Novosibirsk"<?php if($_POST['tz'] == "Asia/Novosibirsk"){ ?> selected="selected"<?php } ?>>Asia: Novosibirsk</option><option value="Asia/Omsk"<?php if($_POST['tz'] == "Asia/Omsk"){ ?> selected="selected"<?php } ?>>Asia: Omsk</option><option value="Asia/Phnom_Penh"<?php if($_POST['tz'] == "Asia/Phnom_Penh"){ ?> selected="selected"<?php } ?>>Asia: Phnom Penh</option><option value="Asia/Pontianak"<?php if($_POST['tz'] == "Asia/Pontianak"){ ?> selected="selected"<?php } ?>>Asia: Pontianak</option><option value="Asia/Pyongyang"<?php if($_POST['tz'] == "Asia/Pyongyang"){ ?> selected="selected"<?php } ?>>Asia: Pyongyang</option><option value="Asia/Qatar"<?php if($_POST['tz'] == "Asia/Qatar"){ ?> selected="selected"<?php } ?>>Asia: Qatar</option><option value="Asia/Rangoon"<?php if($_POST['tz'] == "Asia/Rangoon"){ ?> selected="selected"<?php } ?>>Asia: Rangoon</option><option value="Asia/Riyadh"<?php if($_POST['tz'] == "Asia/Riyadh"){ ?> selected="selected"<?php } ?>>Asia: Riyadh</option><option value="Asia/Saigon"<?php if($_POST['tz'] == "Asia/Saigon"){ ?> selected="selected"<?php } ?>>Asia: Saigon</option><option value="Asia/Sakhalin"<?php if($_POST['tz'] == "Asia/Sakhalin"){ ?> selected="selected"<?php } ?>>Asia: Sakhalin</option><option value="Asia/Samarkand"<?php if($_POST['tz'] == "Asia/Samarkand"){ ?> selected="selected"<?php } ?>>Asia: Samarkand</option><option value="Asia/Seoul"<?php if($_POST['tz'] == "Asia/Seoul"){ ?> selected="selected"<?php } ?>>Asia: Seoul</option><option value="Asia/Shanghai"<?php if($_POST['tz'] == "Asia/Shanghai"){ ?> selected="selected"<?php } ?>>Asia: Shanghai</option><option value="Asia/Singapore"<?php if($_POST['tz'] == "Asia/Singapore"){ ?> selected="selected"<?php } ?>>Asia: Singapore</option><option value="Asia/Taipei"<?php if($_POST['tz'] == "Asia/Taipei"){ ?> selected="selected"<?php } ?>>Asia: Taipei</option><option value="Asia/Tashkent"<?php if($_POST['tz'] == "Asia/Tashkent"){ ?> selected="selected"<?php } ?>>Asia: Tashkent</option><option value="Asia/Tbilisi"<?php if($_POST['tz'] == "Asia/Tbilisi"){ ?> selected="selected"<?php } ?>>Asia: Tbilisi</option><option value="Asia/Tehran"<?php if($_POST['tz'] == "Asia/Tehran"){ ?> selected="selected"<?php } ?>>Asia: Tehran</option><option value="Asia/Tel_Aviv"<?php if($_POST['tz'] == "Asia/Tel_Aviv"){ ?> selected="selected"<?php } ?>>Asia: Tel Aviv</option><option value="Asia/Thimbu"<?php if($_POST['tz'] == "Asia/Thimbu"){ ?> selected="selected"<?php } ?>>Asia: Thimbu</option><option value="Asia/Thimphu"<?php if($_POST['tz'] == "Asia/Thimphu"){ ?> selected="selected"<?php } ?>>Asia: Thimphu</option><option value="Asia/Tokyo"<?php if($_POST['tz'] == "Asia/Tokyo"){ ?> selected="selected"<?php } ?>>Asia: Tokyo</option><option value="Asia/Ujung_Pandang"<?php if($_POST['tz'] == "Asia/Ujung_Pandang"){ ?> selected="selected"<?php } ?>>Asia: Ujung Pandang</option><option value="Asia/Ulaanbaatar"<?php if($_POST['tz'] == "Asia/Ulaanbaatar"){ ?> selected="selected"<?php } ?>>Asia: Ulaanbaatar</option><option value="Asia/Ulan_Bator"<?php if($_POST['tz'] == "Asia/Ulan_Bator"){ ?> selected="selected"<?php } ?>>Asia: Ulan Bator</option><option value="Asia/Urumqi"<?php if($_POST['tz'] == "Asia/Urumqi"){ ?> selected="selected"<?php } ?>>Asia: Urumqi</option><option value="Asia/Vientiane"<?php if($_POST['tz'] == "Asia/Vientiane"){ ?> selected="selected"<?php } ?>>Asia: Vientiane</option><option value="Asia/Vladivostok"<?php if($_POST['tz'] == "Asia/Vladivostok"){ ?> selected="selected"<?php } ?>>Asia: Vladivostok</option><option value="Asia/Yakutsk"<?php if($_POST['tz'] == "Asia/Yakutsk"){ ?> selected="selected"<?php } ?>>Asia: Yakutsk</option><option value="Asia/Yekaterinburg"<?php if($_POST['tz'] == "Asia/Yekaterinburg"){ ?> selected="selected"<?php } ?>>Asia: Yekaterinburg</option><option value="Asia/Yerevan"<?php if($_POST['tz'] == "Asia/Yerevan"){ ?> selected="selected"<?php } ?>>Asia: Yerevan</option>
				<option value="Atlantic/Azores"<?php if($_POST['tz'] == "Atlantic/Azores"){ ?> selected="selected"<?php } ?>>Atlantic: Azores</option><option value="Atlantic/Bermuda"<?php if($_POST['tz'] == "Atlantic/Bermuda"){ ?> selected="selected"<?php } ?>>Atlantic: Bermuda</option><option value="Atlantic/Canary"<?php if($_POST['tz'] == "Atlantic/Canary"){ ?> selected="selected"<?php } ?>>Atlantic: Canary</option><option value="Atlantic/Cape_Verde"<?php if($_POST['tz'] == "Atlantic/Cape_Verde"){ ?> selected="selected"<?php } ?>>Atlantic: Cape Verde</option><option value="Atlantic/Faeroe"<?php if($_POST['tz'] == "Atlantic/Faeroe"){ ?> selected="selected"<?php } ?>>Atlantic: Faeroe</option><option value="Atlantic/Jan_Mayen"<?php if($_POST['tz'] == "Atlantic/Jan_Mayen"){ ?> selected="selected"<?php } ?>>Atlantic: Jan Mayen</option><option value="Atlantic/Madeira"<?php if($_POST['tz'] == "Atlantic/Madeira"){ ?> selected="selected"<?php } ?>>Atlantic: Madeira</option><option value="Atlantic/Reykjavik"<?php if($_POST['tz'] == "Atlantic/Reykjavik"){ ?> selected="selected"<?php } ?>>Atlantic: Reykjavik</option><option value="Atlantic/South_Georgia"<?php if($_POST['tz'] == "Atlantic/South_Georgia"){ ?> selected="selected"<?php } ?>>Atlantic: South Georgia</option><option value="Atlantic/St_Helena"<?php if($_POST['tz'] == "Atlantic/St_Helena"){ ?> selected="selected"<?php } ?>>Atlantic: St Helena</option><option value="Atlantic/Stanley"<?php if($_POST['tz'] == "Atlantic/Stanley"){ ?> selected="selected"<?php } ?>>Atlantic: Stanley</option>
				<option value="Australia/ACT"<?php if($_POST['tz'] == "Australia/ACT"){ ?> selected="selected"<?php } ?>>Australia: ACT</option><option value="Australia/Adelaide"<?php if($_POST['tz'] == "Australia/Adelaide"){ ?> selected="selected"<?php } ?>>Australia: Adelaide</option><option value="Australia/Brisbane"<?php if($_POST['tz'] == "Australia/Brisbane"){ ?> selected="selected"<?php } ?>>Australia: Brisbane</option><option value="Australia/Broken_Hill"<?php if($_POST['tz'] == "Australia/Broken_Hill"){ ?> selected="selected"<?php } ?>>Australia: Broken Hill</option><option value="Australia/Canberra"<?php if($_POST['tz'] == "Australia/Canberra"){ ?> selected="selected"<?php } ?>>Australia: Canberra</option><option value="Australia/Darwin"<?php if($_POST['tz'] == "Australia/Darwin"){ ?> selected="selected"<?php } ?>>Australia: Darwin</option><option value="Australia/Hobart"<?php if($_POST['tz'] == "Australia/Hobart"){ ?> selected="selected"<?php } ?>>Australia: Hobart</option><option value="Australia/LHI"<?php if($_POST['tz'] == "Australia/LHI"){ ?> selected="selected"<?php } ?>>Australia: LHI</option><option value="Australia/Lindeman"<?php if($_POST['tz'] == "Australia/Lindeman"){ ?> selected="selected"<?php } ?>>Australia: Lindeman</option><option value="Australia/Lord_Howe"<?php if($_POST['tz'] == "Australia/Lord_Howe"){ ?> selected="selected"<?php } ?>>Australia: Lord Howe</option><option value="Australia/Melbourne"<?php if($_POST['tz'] == "Australia/Melbourne"){ ?> selected="selected"<?php } ?>>Australia: Melbourne</option><option value="Australia/NSW"<?php if($_POST['tz'] == "Australia/NSW"){ ?> selected="selected"<?php } ?>>Australia: NSW</option><option value="Australia/North"<?php if($_POST['tz'] == "Australia/North"){ ?> selected="selected"<?php } ?>>Australia: North</option><option value="Australia/Perth"<?php if($_POST['tz'] == "Australia/Perth"){ ?> selected="selected"<?php } ?>>Australia: Perth</option><option value="Australia/Queensland"<?php if($_POST['tz'] == "Australia/Queensland"){ ?> selected="selected"<?php } ?>>Australia: Queensland</option><option value="Australia/South"<?php if($_POST['tz'] == "Australia/South"){ ?> selected="selected"<?php } ?>>Australia: South</option><option value="Australia/Sydney"<?php if($_POST['tz'] == "Australia/Sydney"){ ?> selected="selected"<?php } ?>>Australia: Sydney</option><option value="Australia/Tasmania"<?php if($_POST['tz'] == "Australia/Tasmania"){ ?> selected="selected"<?php } ?>>Australia: Tasmania</option><option value="Australia/Victoria"<?php if($_POST['tz'] == "Australia/Victoria"){ ?> selected="selected"<?php } ?>>Australia: Victoria</option><option value="Australia/West"<?php if($_POST['tz'] == "Australia/West"){ ?> selected="selected"<?php } ?>>Australia: West</option><option value="Australia/Yancowinna"<?php if($_POST['tz'] == "Australia/Yancowinna"){ ?> selected="selected"<?php } ?>>Australia: Yancowinna</option>
				<option value="Europe/Amsterdam"<?php if($_POST['tz'] == "Europe/Amsterdam"){ ?> selected="selected"<?php } ?>>Europe: Amsterdam</option><option value="Europe/Andorra"<?php if($_POST['tz'] == "Europe/Andorra"){ ?> selected="selected"<?php } ?>>Europe: Andorra</option><option value="Europe/Athens"<?php if($_POST['tz'] == "Europe/Athens"){ ?> selected="selected"<?php } ?>>Europe: Athens</option><option value="Europe/Belfast"<?php if($_POST['tz'] == "Europe/Belfast"){ ?> selected="selected"<?php } ?>>Europe: Belfast</option><option value="Europe/Belgrade"<?php if($_POST['tz'] == "Europe/Belgrade"){ ?> selected="selected"<?php } ?>>Europe: Belgrade</option><option value="Europe/Berlin"<?php if($_POST['tz'] == "Europe/Berlin"){ ?> selected="selected"<?php } ?>>Europe: Berlin</option><option value="Europe/Bratislava"<?php if($_POST['tz'] == "Europe/Bratislava"){ ?> selected="selected"<?php } ?>>Europe: Bratislava</option><option value="Europe/Brussels"<?php if($_POST['tz'] == "Europe/Brussels"){ ?> selected="selected"<?php } ?>>Europe: Brussels</option><option value="Europe/Bucharest"<?php if($_POST['tz'] == "Europe/Bucharest"){ ?> selected="selected"<?php } ?>>Europe: Bucharest</option><option value="Europe/Budapest"<?php if($_POST['tz'] == "Europe/Budapest"){ ?> selected="selected"<?php } ?>>Europe: Budapest</option><option value="Europe/Chisinau"<?php if($_POST['tz'] == "Europe/Chisinau"){ ?> selected="selected"<?php } ?>>Europe: Chisinau</option><option value="Europe/Copenhagen"<?php if($_POST['tz'] == "Europe/Copenhagen"){ ?> selected="selected"<?php } ?>>Europe: Copenhagen</option><option value="Europe/Dublin"<?php if($_POST['tz'] == "Europe/Dublin"){ ?> selected="selected"<?php } ?>>Europe: Dublin</option><option value="Europe/Gibraltar"<?php if($_POST['tz'] == "Europe/Gibraltar"){ ?> selected="selected"<?php } ?>>Europe: Gibraltar</option><option value="Europe/Helsinki"<?php if($_POST['tz'] == "Europe/Helsinki"){ ?> selected="selected"<?php } ?>>Europe: Helsinki</option><option value="Europe/Istanbul"<?php if($_POST['tz'] == "Europe/Istanbul"){ ?> selected="selected"<?php } ?>>Europe: Istanbul</option><option value="Europe/Kaliningrad"<?php if($_POST['tz'] == "Europe/Kaliningrad"){ ?> selected="selected"<?php } ?>>Europe: Kaliningrad</option><option value="Europe/Kiev"<?php if($_POST['tz'] == "Europe/Kiev"){ ?> selected="selected"<?php } ?>>Europe: Kiev</option><option value="Europe/Lisbon"<?php if($_POST['tz'] == "Europe/Lisbon"){ ?> selected="selected"<?php } ?>>Europe: Lisbon</option><option value="Europe/Ljubljana"<?php if($_POST['tz'] == "Europe/Ljubljana"){ ?> selected="selected"<?php } ?>>Europe: Ljubljana</option><option value="Europe/London"<?php if($_POST['tz'] == "Europe/London"){ ?> selected="selected"<?php } ?>>Europe: London</option><option value="Europe/Luxembourg"<?php if($_POST['tz'] == "Europe/Luxembourg"){ ?> selected="selected"<?php } ?>>Europe: Luxembourg</option><option value="Europe/Madrid"<?php if($_POST['tz'] == "Europe/Madrid"){ ?> selected="selected"<?php } ?>>Europe: Madrid</option><option value="Europe/Malta"<?php if($_POST['tz'] == "Europe/Malta"){ ?> selected="selected"<?php } ?>>Europe: Malta</option><option value="Europe/Minsk"<?php if($_POST['tz'] == "Europe/Minsk"){ ?> selected="selected"<?php } ?>>Europe: Minsk</option><option value="Europe/Monaco"<?php if($_POST['tz'] == "Europe/Monaco"){ ?> selected="selected"<?php } ?>>Europe: Monaco</option><option value="Europe/Moscow"<?php if($_POST['tz'] == "Europe/Moscow"){ ?> selected="selected"<?php } ?>>Europe: Moscow</option><option value="Europe/Nicosia"<?php if($_POST['tz'] == "Europe/Nicosia"){ ?> selected="selected"<?php } ?>>Europe: Nicosia</option><option value="Europe/Oslo"<?php if($_POST['tz'] == "Europe/Oslo"){ ?> selected="selected"<?php } ?>>Europe: Oslo</option><option value="Europe/Paris"<?php if($_POST['tz'] == "Europe/Paris"){ ?> selected="selected"<?php } ?>>Europe: Paris</option><option value="Europe/Prague"<?php if($_POST['tz'] == "Europe/Prague"){ ?> selected="selected"<?php } ?>>Europe: Prague</option><option value="Europe/Riga"<?php if($_POST['tz'] == "Europe/Riga"){ ?> selected="selected"<?php } ?>>Europe: Riga</option><option value="Europe/Rome"<?php if($_POST['tz'] == "Europe/Rome"){ ?> selected="selected"<?php } ?>>Europe: Rome</option><option value="Europe/Samara"<?php if($_POST['tz'] == "Europe/Samara"){ ?> selected="selected"<?php } ?>>Europe: Samara</option><option value="Europe/San_Marino"<?php if($_POST['tz'] == "Europe/San_Marino"){ ?> selected="selected"<?php } ?>>Europe: San Marino</option><option value="Europe/Sarajevo"<?php if($_POST['tz'] == "Europe/Sarajevo"){ ?> selected="selected"<?php } ?>>Europe: Sarajevo</option><option value="Europe/Simferopol"<?php if($_POST['tz'] == "Europe/Simferopol"){ ?> selected="selected"<?php } ?>>Europe: Simferopol</option><option value="Europe/Skopje"<?php if($_POST['tz'] == "Europe/Skopje"){ ?> selected="selected"<?php } ?>>Europe: Skopje</option><option value="Europe/Sofia"<?php if($_POST['tz'] == "Europe/Sofia"){ ?> selected="selected"<?php } ?>>Europe: Sofia</option><option value="Europe/Stockholm"<?php if($_POST['tz'] == "Europe/Stockholm"){ ?> selected="selected"<?php } ?>>Europe: Stockholm</option><option value="Europe/Tallinn"<?php if($_POST['tz'] == "Europe/Tallinn"){ ?> selected="selected"<?php } ?>>Europe: Tallinn</option><option value="Europe/Tirane"<?php if($_POST['tz'] == "Europe/Tirane"){ ?> selected="selected"<?php } ?>>Europe: Tirane</option><option value="Europe/Tiraspol"<?php if($_POST['tz'] == "Europe/Tiraspol"){ ?> selected="selected"<?php } ?>>Europe: Tiraspol</option><option value="Europe/Uzhgorod"<?php if($_POST['tz'] == "Europe/Uzhgorod"){ ?> selected="selected"<?php } ?>>Europe: Uzhgorod</option><option value="Europe/Vaduz"<?php if($_POST['tz'] == "Europe/Vaduz"){ ?> selected="selected"<?php } ?>>Europe: Vaduz</option><option value="Europe/Vatican"<?php if($_POST['tz'] == "Europe/Vatican"){ ?> selected="selected"<?php } ?>>Europe: Vatican</option><option value="Europe/Vienna"<?php if($_POST['tz'] == "Europe/Vienna"){ ?> selected="selected"<?php } ?>>Europe: Vienna</option><option value="Europe/Vilnius"<?php if($_POST['tz'] == "Europe/Vilnius"){ ?> selected="selected"<?php } ?>>Europe: Vilnius</option><option value="Europe/Warsaw"<?php if($_POST['tz'] == "Europe/Warsaw"){ ?> selected="selected"<?php } ?>>Europe: Warsaw</option><option value="Europe/Zagreb"<?php if($_POST['tz'] == "Europe/Zagreb"){ ?> selected="selected"<?php } ?>>Europe: Zagreb</option><option value="Europe/Zaporozhye"<?php if($_POST['tz'] == "Europe/Zaporozhye"){ ?> selected="selected"<?php } ?>>Europe: Zaporozhye</option><option value="Europe/Zurich"<?php if($_POST['tz'] == "Europe/Zurich"){ ?> selected="selected"<?php } ?>>Europe: Zurich</option>
				<option value="Indian/Antananarivo"<?php if($_POST['tz'] == "Indian/Antananarivo"){ ?> selected="selected"<?php } ?>>Indian: Antananarivo</option><option value="Indian/Chagos"<?php if($_POST['tz'] == "Indian/Chagos"){ ?> selected="selected"<?php } ?>>Indian: Chagos</option><option value="Indian/Christmas"<?php if($_POST['tz'] == "Indian/Christmas"){ ?> selected="selected"<?php } ?>>Indian: Christmas</option><option value="Indian/Cocos"<?php if($_POST['tz'] == "Indian/Cocos"){ ?> selected="selected"<?php } ?>>Indian: Cocos</option><option value="Indian/Comoro"<?php if($_POST['tz'] == "Indian/Comoro"){ ?> selected="selected"<?php } ?>>Indian: Comoro</option><option value="Indian/Kerguelen"<?php if($_POST['tz'] == "Indian/Kerguelen"){ ?> selected="selected"<?php } ?>>Indian: Kerguelen</option><option value="Indian/Mahe"<?php if($_POST['tz'] == "Indian/Mahe"){ ?> selected="selected"<?php } ?>>Indian: Mahe</option><option value="Indian/Maldives"<?php if($_POST['tz'] == "Indian/Maldives"){ ?> selected="selected"<?php } ?>>Indian: Maldives</option><option value="Indian/Mauritius"<?php if($_POST['tz'] == "Indian/Mauritius"){ ?> selected="selected"<?php } ?>>Indian: Mauritius</option><option value="Indian/Mayotte"<?php if($_POST['tz'] == "Indian/Mayotte"){ ?> selected="selected"<?php } ?>>Indian: Mayotte</option><option value="Indian/Reunion"<?php if($_POST['tz'] == "Indian/Reunion"){ ?> selected="selected"<?php } ?>>Indian: Reunion</option>
				<option value="Pacific/Apia"<?php if($_POST['tz'] == "Pacific/Apia"){ ?> selected="selected"<?php } ?>>Pacific: Apia</option><option value="Pacific/Auckland"<?php if($_POST['tz'] == "Pacific/Auckland"){ ?> selected="selected"<?php } ?>>Pacific: Auckland</option><option value="Pacific/Chatham"<?php if($_POST['tz'] == "Pacific/Chatham"){ ?> selected="selected"<?php } ?>>Pacific: Chatham</option><option value="Pacific/Easter"<?php if($_POST['tz'] == "Pacific/Easter"){ ?> selected="selected"<?php } ?>>Pacific: Easter</option><option value="Pacific/Efate"<?php if($_POST['tz'] == "Pacific/Efate"){ ?> selected="selected"<?php } ?>>Pacific: Efate</option><option value="Pacific/Enderbury"<?php if($_POST['tz'] == "Pacific/Enderbury"){ ?> selected="selected"<?php } ?>>Pacific: Enderbury</option><option value="Pacific/Fakaofo"<?php if($_POST['tz'] == "Pacific/Fakaofo"){ ?> selected="selected"<?php } ?>>Pacific: Fakaofo</option><option value="Pacific/Fiji"<?php if($_POST['tz'] == "Pacific/Fiji"){ ?> selected="selected"<?php } ?>>Pacific: Fiji</option><option value="Pacific/Funafuti"<?php if($_POST['tz'] == "Pacific/Funafuti"){ ?> selected="selected"<?php } ?>>Pacific: Funafuti</option><option value="Pacific/Galapagos"<?php if($_POST['tz'] == "Pacific/Galapagos"){ ?> selected="selected"<?php } ?>>Pacific: Galapagos</option><option value="Pacific/Gambier"<?php if($_POST['tz'] == "Pacific/Gambier"){ ?> selected="selected"<?php } ?>>Pacific: Gambier</option><option value="Pacific/Guadalcanal"<?php if($_POST['tz'] == "Pacific/Guadalcanal"){ ?> selected="selected"<?php } ?>>Pacific: Guadalcanal</option><option value="Pacific/Guam"<?php if($_POST['tz'] == "Pacific/Guam"){ ?> selected="selected"<?php } ?>>Pacific: Guam</option><option value="Pacific/Honolulu"<?php if($_POST['tz'] == "Pacific/Honolulu"){ ?> selected="selected"<?php } ?>>Pacific: Honolulu</option><option value="Pacific/Johnston"<?php if($_POST['tz'] == "Pacific/Johnston"){ ?> selected="selected"<?php } ?>>Pacific: Johnston</option><option value="Pacific/Kiritimati"<?php if($_POST['tz'] == "Pacific/Kiritimati"){ ?> selected="selected"<?php } ?>>Pacific: Kiritimati</option><option value="Pacific/Kosrae"<?php if($_POST['tz'] == "Pacific/Kosrae"){ ?> selected="selected"<?php } ?>>Pacific: Kosrae</option><option value="Pacific/Kwajalein"<?php if($_POST['tz'] == "Pacific/Kwajalein"){ ?> selected="selected"<?php } ?>>Pacific: Kwajalein</option><option value="Pacific/Majuro"<?php if($_POST['tz'] == "Pacific/Majuro"){ ?> selected="selected"<?php } ?>>Pacific: Majuro</option><option value="Pacific/Marquesas"<?php if($_POST['tz'] == "Pacific/Marquesas"){ ?> selected="selected"<?php } ?>>Pacific: Marquesas</option><option value="Pacific/Midway"<?php if($_POST['tz'] == "Pacific/Midway"){ ?> selected="selected"<?php } ?>>Pacific: Midway</option><option value="Pacific/Nauru"<?php if($_POST['tz'] == "Pacific/Nauru"){ ?> selected="selected"<?php } ?>>Pacific: Nauru</option><option value="Pacific/Niue"<?php if($_POST['tz'] == "Pacific/Niue"){ ?> selected="selected"<?php } ?>>Pacific: Niue</option><option value="Pacific/Norfolk"<?php if($_POST['tz'] == "Pacific/Norfolk"){ ?> selected="selected"<?php } ?>>Pacific: Norfolk</option><option value="Pacific/Noumea"<?php if($_POST['tz'] == "Pacific/Noumea"){ ?> selected="selected"<?php } ?>>Pacific: Noumea</option><option value="Pacific/Pago_Pago"<?php if($_POST['tz'] == "Pacific/Pago_Pago"){ ?> selected="selected"<?php } ?>>Pacific: Pago Pago</option><option value="Pacific/Palau"<?php if($_POST['tz'] == "Pacific/Palau"){ ?> selected="selected"<?php } ?>>Pacific: Palau</option><option value="Pacific/Pitcairn"<?php if($_POST['tz'] == "Pacific/Pitcairn"){ ?> selected="selected"<?php } ?>>Pacific: Pitcairn</option><option value="Pacific/Ponape"<?php if($_POST['tz'] == "Pacific/Ponape"){ ?> selected="selected"<?php } ?>>Pacific: Ponape</option><option value="Pacific/Port_Moresby"<?php if($_POST['tz'] == "Pacific/Port_Moresby"){ ?> selected="selected"<?php } ?>>Pacific: Port Moresby</option><option value="Pacific/Rarotonga"<?php if($_POST['tz'] == "Pacific/Rarotonga"){ ?> selected="selected"<?php } ?>>Pacific: Rarotonga</option><option value="Pacific/Saipan"<?php if($_POST['tz'] == "Pacific/Saipan"){ ?> selected="selected"<?php } ?>>Pacific: Saipan</option><option value="Pacific/Samoa"<?php if($_POST['tz'] == "Pacific/Samoa"){ ?> selected="selected"<?php } ?>>Pacific: Samoa</option><option value="Pacific/Tahiti"<?php if($_POST['tz'] == "Pacific/Tahiti"){ ?> selected="selected"<?php } ?>>Pacific: Tahiti</option><option value="Pacific/Tarawa"<?php if($_POST['tz'] == "Pacific/Tarawa"){ ?> selected="selected"<?php } ?>>Pacific: Tarawa</option><option value="Pacific/Tongatapu"<?php if($_POST['tz'] == "Pacific/Tongatapu"){ ?> selected="selected"<?php } ?>>Pacific: Tongatapu</option><option value="Pacific/Truk"<?php if($_POST['tz'] == "Pacific/Truk"){ ?> selected="selected"<?php } ?>>Pacific: Truk</option><option value="Pacific/Wake"<?php if($_POST['tz'] == "Pacific/Wake"){ ?> selected="selected"<?php } ?>>Pacific: Wake</option><option value="Pacific/Wallis"<?php if($_POST['tz'] == "Pacific/Wallis"){ ?> selected="selected"<?php } ?>>Pacific: Wallis</option><option value="Pacific/Yap"<?php if($_POST['tz'] == "Pacific/Yap"){ ?> selected="selected"<?php } ?>>Pacific: Yap</option>
				<option value="UTC"<?php if($_POST['tz'] == "UTC"){ ?> selected="selected"<?php } ?>>UTC</option></select></div>
				<div class="what">The time zone (closest major city) that you live in. Used to make sure your tweets have the correct timestamp so it doesn&#8217;t look like you tweeted at 4 AM. (Unless, you know, you actually did.)</div>
			</div>
			<div class="input lastinput">
				<label for="path">Tweet Nest path</label>
				<div class="field required"><input type="text" class="text" name="path" id="path" value="<?php echo $_POST['path'] ? s($_POST['path']) : s($path); ?>" /></div>
				<div class="what">The folder in which you have installed Tweet Nest, i.e. the part after your domain name. If on the root of the domain, simply type <strong>/</strong>. <span class="address">Example: <strong>/tweets</strong></span> for <span class="address">http://pongsocket.com<strong>/tweets</strong></span> (Note: No end slash, please!)</div>
			</div>
			
			<h2>Database authentication</h2>
			<div class="input">
				<label for="db_hostname">Database host name</label>
				<div class="field required"><input type="text" class="text" name="db_hostname" id="db_hostname" value="<?php echo $_POST['db_hostname'] ? s($_POST['db_hostname']) : "localhost"; ?>" /></div>
				<div class="what">The host name of your database server. Usually this is the same as the web server and you can thus type <strong>&#8220;localhost&#8221;</strong>. But this is not always the case, so change it if you must!</div>
			</div>
			<div class="input">
				<label for="db_username">Database username</label>
				<div class="field required"><input type="text" class="text" name="db_username" id="db_username" value="<?php echo s($_POST['db_username']); ?>" /></div>
				<div class="what">The username part of your database login.</div>
			</div>
			<div class="input">
				<label for="db_password">Database password</label>
				<div class="field required"><input type="password" class="text" name="db_password" id="db_password" value="" /></div>
				<div class="what">The password part of your database login.<?php if($_POST['db_password']){ ?> <strong class="remember">REMEMBER TO TYPE THIS IN AGAIN!</strong><?php } ?></div>
			</div>
			<div class="input">
				<label for="db_database">Database name</label>
				<div class="field required"><input type="text" class="text" name="db_database" id="db_database" value="<?php echo s($_POST['db_database']); ?>" /></div>
				<div class="what">The name of the actual database where you want Tweet Nest to store its data once logged in to the database server.</div>
			</div>
			<div class="input lastinput">
				<label for="db_table_prefix">Table name prefix</label>
				<div class="field required"><input type="text" class="text" name="db_table_prefix" id="db_table_prefix" maxlength="10" value="<?php echo !empty($_POST) ? s($_POST['db_table_prefix']) : s("tn_"); ?>" /></div>
				<div class="what">The Tweet Archive set up page (that&#8217;s this one!) generates three different tables, and to prevent the names clashing with some already there, here you can type a character sequence prefixed to the name of both tables. Something like <strong>&#8220;ta_&#8221;</strong> or <strong>&#8220;tn_&#8221;</strong> is good.</div>
			</div>
			
			<h2>Miscellaneous settings</h2>
			<div class="input">
				<label for="maintenance_http_password">Admin password</label>
				<div class="field"><input type="password" class="text" name="maintenance_http_password" id="maintenance_http_password" value="" /></div>
				<div class="what">If you want to <strong>load your tweets</strong> into Tweet Nest through your browser, specify an admin password. If you don&#8217;t specify this, you&#8217;ll only be able to load tweets through your server&#8217;s command line, so this is <strong>highly encouraged</strong>. Note: Unless you have SSL, this will be sent in clear text, so probably <strong>not</strong> make it the same as your Twitter password!<?php if($_POST['maintenance_http_password']){ ?> <strong class="remember">REMEMBER TO TYPE THIS IN AGAIN!</strong><?php } ?></div>
			</div>
			<div class="input">
				<label for="maintenance_http_password_2">(Repeat it)</label>
				<div class="field"><input type="password" class="text" name="maintenance_http_password_2" id="maintenance_http_password_2" value="" /></div>
				<div class="what">If you typed an admin password above, type it here again.<?php if($_POST['maintenance_http_password']){ ?> <strong class="remember">REMEMBER TO TYPE THIS IN AGAIN!</strong><?php } ?></div>
			</div>
			<div class="input">
				<label for="follow_me_button">&#8220;Follow me&#8221; button</label>
				<div class="field"><input type="checkbox" class="checkbox" name="follow_me_button" id="follow_me_button" checked="checked" /></div>
				<div class="what">Display a &#8220;Follow me on Twitter&#8221; button on your Tweet Nest page?</div>
			</div>
			<div class="input lastinput">
				<label for="smartypants">SmartyPants</label>
				<div class="field"><input type="checkbox" class="checkbox" name="smartypants" id="smartypants" checked="checked" /></div>
				<div class="what">Use <a href="http://daringfireball.net/projects/smartypants/" target="_blank">SmartyPants</a> to perfect punctuation inside tweets? Changes all "straight quotes" to &#8220;curly quotes&#8221; and more.</div>
			</div>
			
			<h2>@Anywhere integration</h2>
			<div class="input lastinput">
				<label for="anywhere_apikey">@Anywhere API key</label>
				<div class="field"><input type="text" class="text code" name="anywhere_apikey" id="anywhere_apikey" maxlength="30" value="<?php echo s($_POST['anywhere_apikey']); ?>" /></div>
				<div class="what">If you want hovercard-style information displayed when you mouseover a Twitter username on your archive, insert your @Anywhere API key here. <a href="http://dev.twitter.com/anywhere" target="_blank">Here&#8217;s where to get one &rarr;</a></div>
			</div>
			
			<h2>Style settings</h2>
			<div class="note">
				<p>If you open up <code>config.php</code> after setting up your Tweet Nest, you will see a lot of style settings that you can play around with. Read the guide on the Tweet Nest website for more information on how to <a href="http://pongsocket.com/tweetnest/#customization" target="_blank">customize your Tweet Nest&#8217;s look &rarr;</a></p>
			</div>
			
			<h2>That&#8217;s it!</h2>
			<div><input type="submit" class="submit" value="Submit and set up" /></div>
		</form>
<?php } ?>
	</div>
</body>
</html>
