<?php

	/*
	
		This is a cron script run once a day or so.
		
		It will check through the pool of MedImage servers in config/config.json atomjumpNotifications.serverPool
		and for each one get the load using the URL   medimageserver.url/load/
		
		This returns a 1 minute, 5 minute, 15 minute load average array as text. e.g [0.60009765625,0.2529296875,0.123046875]
		
		With this load, we will export a current register as .json, so that registrations in genid.php can use the file to choose
		which servers have the least load, and should become that app's stored URL for future checks.
		
		Export format in config/loadEXAMPLE.json
		
	
	*/

	function trim_trailing_slash_local($str) {
		return rtrim($str, "/");
	}

	function add_trailing_slash_local($str) {
		//Remove and then add
		return rtrim($str, "/") . '/';
	}

	if(!isset($notifications_config)) {
		//Get global plugin config - but only once
		$data = file_get_contents (dirname(__FILE__) . "/config/config.json");
		if($data) {
			$notifications_config = json_decode($data, true);
			if(!isset($notifications_config)) {
				echo "Error: notifications config/config.json is not valid JSON.";
				exit(0);
			}
 
		} else {
			echo "Error: Missing config/config.json in notifications plugin.";
			exit(0);
 
		}

	}



	$start_path = add_trailing_slash_local($notifications_config['serverPath']);

	$staging = $notifications_config['staging'];
	$notify = false;
	include_once($start_path . 'config/db_connect.php');
	
	echo "Checking server pool..\n";
  	if(isset($notifications_config['atomjumpNotifications']) 
  		&& isset($notifications_config['atomjumpNotifications']['serverPool'])) {
	
		foreach ($notifications_config['atomjumpNotifications']['serverPool'] as $country) {
				if ($country->_visible == 1) {
					echo $country;
				}
		}
	}
	
		
	
		
?>