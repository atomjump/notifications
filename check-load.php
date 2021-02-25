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
	
	$output = array("atomjumpNotifications" => array("serverPoolLoad" => array()));
	
  	if(isset($notifications_config['atomjumpNotifications']) 
  		&& isset($notifications_config['atomjumpNotifications']['serverPool'])) {
	
		foreach($notifications_config['atomjumpNotifications']['serverPool'] as $country_code => $country_servers) {
			$output['atomjumpNotifications']['serverPoolLoad'][$country_code] = array();
			echo $country_code . "\n";
			for($cnt = 0; $cnt < count($country_servers); $cnt++) {
				$server_url = $country_servers[$cnt];
				
				echo $server_url . "   Load:";
				$load = file_get_contents($server_url . "/load/");
				echo $load;
				$json = "{ \"load\": " . $load . "}";
				echo "  JSON: ". $json;
				$load_array = json_decode($json);
				echo "  15 minute load:" . $load_array->load[2];		//Use 15 minute average
				echo "\n";
				$server_output = array("url" => $server_url, "load" => $load_array->load[2]);
				array_push($output['atomjumpNotifications']['serverPoolLoad'][$country_code],$server_output);
			}
		}
	}
	
	$outfile_str = json_encode($output, JSON_PRETTY_PRINT);
	echo $outfile_str;
	
	
	$parent_folder = __DIR__ . "/outgoing/";
		
	if(!file_exists($parent_folder)) {
		if(!mkdir($parent_folder)) {
			$msg = "Sorry, your notifications send.php script could not create a folder " . $parent_folder . ". You may need to: mkdir outgoing; chmod 777 outgoing";
			error_log($msg);
			echo $msg;
			exit(0);
		}
	}
		
	file_put_contents($parent_folder . "load.json", $outfile_str);
		
?>