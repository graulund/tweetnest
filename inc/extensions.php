<?php
	// PONGSOCKET TWEET ARCHIVE
	// Extension and event handling
	
	$ext = array(); // Global extension objects array
	global $ext;
	
	$path   = rtrim($config['path'], "/");
	$extDir = FULL_PATH . "/extensions";
	
	// Loading extensions...
	foreach(scandir($extDir) as $file){
		if(substr($file, -4) == ".php" && filetype($extDir . "/" . $file) == "file"){
			$n = explode(".", $file, 2);
			$name = $n[0]; $o = NULL;
			include $extDir . "/" . $file;
			$ext[$name] = $o; // Giving a value to main object $o is required.
		}
	}
	
	function hook($name, $args, $singleArray = false){
		global $ext;
		$x = $args;
		foreach($ext as $n => $e){
			if(is_object($e) && method_exists($e, $name)){
				if(is_array($x) && !$singleArray){
					$y = call_user_func_array(array($e, $name), $x);
				} else {
					$y = call_user_func(array($e, $name), $x);
				}
				if($y != false){ $x = $y; }
			}
		}
		return $x;
	}
