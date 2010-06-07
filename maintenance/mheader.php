<?php
	// TWEET NEST
	// Maintenance area header
	
	if($web){
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title><?php echo htmlspecialchars($pageTitle); ?> &#8212; Tweet Nest</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="ROBOTS" content="NOINDEX,NOFOLLOW" />
	<style type="text/css">
		body { font: small "Helvetica Neue", Helvetica, Arial, sans-serif; margin: 50px; background-color: #eee; color: #666; }
		strong { font-weight: bold; } em { font-style: italic; }
		h1 { color: #000; font-weight: bold; font-size: 250%; } h1 span { color: #444; font-weight: normal; }
		pre, code { font-family: Menlo, "Menlo Regular", Monaco, monospace; }
		pre { border: 1px solid #ccc; background-color: #fff; color: #666; padding: 20px; }
		strong { color: #000; } pre strong { color: #333; } strong.good { color: #3c3; } strong.bad { color: #c00; }
		span.address { color: #999; }
		code { font-size: 95%; background-color: #f7f7f7; color: #666; padding: 0 2px; white-space: nowrap; }
		a img { border-width: 0; }
	</style>
</head>
<body>
	<h1><span>Tweet Nest:</span> <?php echo htmlspecialchars($pageTitle); ?></h1>
	<?php if(!$noPre){ echo "<pre>"; }
	} else {
		header("Content-type: text/plain");
	}