<?php
    // TWEET NEST
    // File to help you authorize when you've just upgraded to OAuth

	// ...a lot of this is shameless copy-paste from setup.php. Could be more DRY...

	if(php_sapi_name() == 'cli'){
		die("Please run this in a web browser.\n");
	}

	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', true); // This is NOT a production page; errors can and should be visible to aid the user.

	mb_language('uni');
	mb_internal_encoding('UTF-8');
	session_start();

	header('Content-Type: text/html; charset=utf-8');

	// Considerations regarding the configuration we already have:
	require 'inc/config.php';

	// No config at all? You're not supposed to be here.
	if(!isset($config) || !is_array($config) || empty($config['twitter_screenname'])){
		header('Location: ./setup.php');
		exit;
	}

	// These values are already in the config? Nothing to do here!
	if(isset($config['consumer_key']) && !empty($config['consumer_key'])){
		die('Consumer key and secret are already set. Nothing to do here! You may delete this file.');
	}

	// ...Okay, good to continue!

	$GLOBALS['error'] = false;

	$_SESSION['redirect_source'] = 'authorize';

	function s($str){ return htmlspecialchars($str, ENT_NOQUOTES); } // Shorthand

	function displayErrors($e){
		if(count($e) <= 0){ return false; }
		$r = '';
		if(count($e) > 1){
			$r .= '<h2>Errors</h2><ul class="error">';
			foreach($e as $error){
				$r .= '<li>' . $error . '</li>';
				// Not running htmlspecialchars 'cause errors are a set of finite approved messages
			}
			$r .= '</ul>';
		} else {
			$r .= "<h2>Error</h2>\n<p class=\"error\">" . current($e) . '</p>';
		}
		return $r . "\n";
	}

	function errorHandler($errno, $message, $filename, $line, $context){
		if(error_reporting() == 0){ return false; }
		if($errno & (E_ALL ^ E_NOTICE)){
			$GLOBALS['error'] = true;
			$types = array(
				1 => 'error',
				2 => 'warning',
				4 => 'parse error',
				8 => 'notice',
				16 => 'core error',
				32 => 'core warning',
				64 => 'compile error',
				128 => 'compile warning',
				256 => 'user error',
				512 => 'user warning',
				1024 => 'user notice',
				2048 => 'strict warning',
				4096 => 'recoverable fatal error'
			);
			echo '<div class="serror"><strong>PHP ' . $types[$errno] . ':</strong> ' . s(strip_tags($message)) . ' in <code>' . s($filename) . '</code> on line ' . s($line) . ".</div>\n";
		}
		return true;
	}
	set_error_handler('errorHandler');

	// Utility function, thanks to stackoverflow.com/questions/3835636
	function str_lreplace($search, $replace, $subject){ $pos = strrpos($subject, $search); if($pos !== false){ $subject = substr_replace($subject, $replace, $pos, strlen($search)); } return $subject; }

	// Function to insert configuration value into the array literal in the configuration file
	function configSetting($cf, $setting, $value){
		if($value === ''){ return $cf; } // Empty
		$empty = is_bool($value) ? '(true|false)' : "''";
		$val   = is_bool($value) ? ($value ? 'true' : 'false') : "'" . preg_replace("/([\\'])/", '\\\$1', $value) . "'";

		// First check if the directive exists in the config file.
		$directiveHead = "'" . preg_quote($setting, '/') . "'(\s*)=>";
		$exists = preg_match('/' . $directiveHead . '/', $cf);

		if($exists){
			// If it exists, simply add the value instead of an empty one
			return preg_replace('/' . $directiveHead . '(\s*)' . $empty . '/', "'" . $setting . "'$1=>$2" . $val, $cf);
		} else {
			// If it does not exist, let's add it to the end of the literal array in the file.
			return str_lreplace(');', ",'" . $setting . "' => " . $val . "\n);", $cf);
		}
	}

	$e       = array();
	$log     = array();
	$success = false; // We are doomed :(
	$post    = (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST');

	// Message shown when people have actively tried to go through OAuth but it failed verification
	if(isset($_SESSION['status']) && $_SESSION['status'] == 'not verified'){
		$e[] = '<strong>We could not verify you through Twitter.</strong> Please make sure you&#8217;ve entered the correct credentials on the Twitter authentication page.';
	}
	// Message shown when people have actively tried to go through OAuth but there was an old key or other mechanical mishap
	if(isset($_SESSION['status']) && $_SESSION['status'] == 'try again'){
		$e[] = '<strong>Something broke during the verification through Twitter.</strong> Please try again.';
	}

	// We have the access token! Everything should be good!

	if(isset($_SESSION['status']) && $_SESSION['status'] == 'verified' && isset($_SESSION['access_token'])){
		// Time to write the config file with the information we now have.

		$cf = file_get_contents('inc/config.php');
		$cf = configSetting($cf, 'consumer_key', $_POST['consumer_key']);
		$cf = configSetting($cf, 'consumer_secret', $_POST['consumer_secret']);
		$cf = configSetting($cf, 'your_tw_screenname', $_SESSION['access_token']['screen_name']);
		$cf = configSetting($cf, 'twitter_token', $_SESSION['access_token']['oauth_token']);
		$cf = configSetting($cf, 'twitter_token_secr', $_SESSION['access_token']['oauth_token_secret']);
		$f  = fopen('inc/config.php', 'wt');
		$fe = 'Could not write configuration to <code>config.php</code>, please make sure that it is writable! Often, ' .
			'this is done through giving every system user the write privileges on that file through FTP.';
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

	// Someone's submitting!
	if($post && !$success){
		if(
			isset($_POST['consumer_key']) && !empty($_POST['consumer_key']) &&
			isset($_POST['consumer_secret']) && !empty($_POST['consumer_secret'])
		){
			// Redirect them to Twitter to get the access token.

			$config['consumer_key']    = $_SESSION['entered_consumer_key']    = $_POST['consumer_key'];
			$config['consumer_secret'] = $_SESSION['entered_consumer_secret'] = $_POST['consumer_secret'];

			require 'redirect.php';
			exit;

		} else {
			$e[] = 'Please fill in your <strong>Twitter app consumer key and secret</strong>.';
		}
	}


	// Form preparation
	$enteredConsumerKey = '';
	$enteredConsumerSecret = '';

	if(isset($_SESSION['entered_consumer_key']) && !empty($_SESSION['entered_consumer_key'])){
		$enteredConsumerKey = $_SESSION['entered_consumer_key'];
	}
	if(isset($_SESSION['entered_consumer_secret']) && !empty($_SESSION['entered_consumer_secret'])){
		$enteredConsumerSecret = $_SESSION['entered_consumer_secret'];
	}
	if($post && isset($_POST['consumer_key']) && !empty($_POST['consumer_key'])){
		$enteredConsumerKey = $_POST['consumer_key'];
	}
	if($post && isset($_POST['consumer_secret']) && !empty($_POST['consumer_secret'])){
		$enteredConsumerSecret = $_POST['consumer_secret'];
	}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title>Authorize Tweet Nest<?php if($e){ ?> &#8212; Error!<?php } ?></title>
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

input.submit, a.proceed {
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

input[type=image] {
	cursor: pointer;
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

#content strong.authorized {
	color: #fff;
	background-color: #0c0;
	padding: 2px 8px;
	border-radius: 3px;
	margin-left: 1px;
}

</style>
</head>
<body>
<div id="container">
	<a id="pongsk" href="http://pongsocket.com/" target="_blank" title="Open pongsocket.com in a new window">pongsocket</a>
	<h1>Authorize <strong>Tweet Nest</strong></h1>
	<div id="content">
<?php
	if($success){
?>

		<h2 id="excerpt"><strong>Success!</strong> You have now authorized Tweet Nest.</h2>

		<p>You can now start <a href="./maintenance/loadtweets.php">loading your newest tweets</a>.</p>
		<p>You may also remove this file.</p>

<?php
	} else {
		if($e && $post){
?>
		<h2 id="excerpt"><strong>Whoops!</strong> An error occured that prevented you from being able to install Tweet Nest until it is fixed.</h2>
		<?php echo displayErrors($e); ?>
<?php } ?>

		<p><strong>Due to Twitter&#8217;s newest policies,</strong> every connection made to Twitter now has to be
			authenticated as coming from a source which is registered with Twitter. Because of this, to continue
			to use Tweet Nest, you have to <strong>register your Tweet Nest installation as an app with Twitter</strong>.</p>
		<p>If you haven&#8217;t already done so, here&#8217;s a link to Twitter&#8217;s Developer Site where you can
			click the &#8220;Create new application&#8221; button to register (link opens in a new window):</p>
		<p><a href="http://dev.twitter.com/apps" target="_blank">Go to Twitter&#8217;s Developer Site</a></p>
		<p>When you do that, you get two strings in return, labeled the <em>consumer key</em> and the <em>consumer secret</em>.
			Paste them below:</p>


		<div class="input">
			<label for="consumer_key">Twitter consumer key</label>
			<div class="field required"><input type="text" class="text" name="consumer_key" id="consumer_key" value="<?php echo s($enteredConsumerKey); ?>" /></div>
			<div class="what">The consumer key of an app created and registered on <a href="http://dev.twitter.com/apps">dev.twitter.com</a>.</div>
		</div>
		<div class="input">
			<label for="consumer_secret">Twitter consumer secret</label>
			<div class="field required"><input type="text" class="text" name="consumer_secret" id="consumer_secret" value="<?php echo s($enteredConsumerSecret); ?>" /></div>
			<div class="what">The consumer secret of the above.</div>
		</div>

		<h2>That&#8217;s it!</h2>

		<p>With the provided strings above, clicking the button below will now redirect your browser to Twitter to
			ask for your personal Twitter credentials, which will be used to let Tweet Nest access your tweets.</p>

		<div><input type="submit" class="submit" value="Authorize" /></div>
<?php } ?>
	</div>
</div>
</body>
</html>