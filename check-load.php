<?php


	//This is a cron script run once a day or so. E.g.
	//sudo crontab -e  
	//	0 0 * * *	/usr/bin/php /yourserverpath/plugins/notifications/check-load.php
	/*
	
		It will check through the pool of MedImage servers in config/config.json atomjumpNotifications.serverPool
		and for each one get the load using the URL   medimageserver.url/load/
		
		This returns a 1 minute, 5 minute, 15 minute load average array as text. e.g [0.60009765625,0.2529296875,0.123046875]
		
		With this load, we will export a current register as .json, so that registrations in genid.php can use the file to choose
		which servers have the least load, and should become that app's stored URL for future checks.
		
		Export format in config/loadEXAMPLE.json
		Export file in outgoing/load.json
	
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
	$notify = true;
	include_once($start_path . 'config/db_connect.php');
	
	echo "Checking server pool..\n";
	
	$output = array("atomjumpNotifications" => array("serverPoolLoad" => array()));
	
  	if(isset($notifications_config['atomjumpNotifications']) 
  		&& isset($notifications_config['atomjumpNotifications']['serverPool'])) {
	
		$warning_messages = array();
	
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
			
			//Now sort from least load to highest within this country
			// Obtain a list of columns. TODO: debug this.
			
			$server_loads_arr = $output['atomjumpNotifications']['serverPoolLoad'][$country_code];
			$url = array();
			$load = array();
			foreach ($server_loads_arr as $key => $row) {
				$url[$key]  = $row['url'];
				$load[$key] = $row['load'];
			}

			// Sort the data with volume descending, edition ascending
			// Add $data as the last parameter, to sort by the common key
			array_multisort($load, SORT_ASC, $url, SORT_ASC, $server_loads_arr);
			
			$output['atomjumpNotifications']['serverPoolLoad'][$country_code] = $server_loads_arr;
			
			
			if(isset($notifications_config['atomjumpNotifications']['notifyAdminWhenLoadAbove'])) {
				$threshold = $notifications_config['atomjumpNotifications']['notifyAdminWhenLoadAbove'];
				$least_server_load = $server_loads_arr[0]['load'] * 100.0;		//Turn into a percentage
				if($least_server_load > $threshold) {
					$msg = "* " . $country_code . ": the server with the least load in the country, " . $server_loads_arr[0]['url'] . ", has a load above the threshold " . $threshold . "% with a 15 minute average load of " . $least_server_load . "%.";
					array_push($warning_messages,  $msg);
				}
			}
		}
	}
	
	
	

	
	

	
	
	$outfile_str = json_encode($output, JSON_PRETTY_PRINT);
	echo $outfile_str;
	
	
	if(count($warning_messages) > 0) {
		//Send off an email to the system admin
		$subject = "Warning: New AtomJump Messaging notifications hardware needed";
		$warnings = "";
		for($cnt = 0; $cnt < count($warning_messages); $cnt++) {
			$warnings .= $warning_messages[$cnt] . "\n";
		
		}
		
		$msg = "You have server loads above the threshold for the AtomJump Messaging notification system. We suggest that you buy new hardware and add it to your list of notification servers (see plugins/notifications/config/config.json).\n\nIndividual country warnings are below:\n\n" . $warnings . "\n\nA full load breakdown, in JSON format, is below:\n\n" . $outfile_str;
		//Send off email to AtomJump Messaging config sys admin
		
		echo "\n" . $msg;
		
		global $cnf;
		if(isset($cnf['email']) && isset($cnf['email']['adminEmail'])) {
			$to_email = $cnf['email']['adminEmail'];
			if(isset($cnf['email']['noReplyEmail'])) {
				$sender_email = $cnf['email']['noReplyEmail'];
			} else {
				$sender_email = $cnf['email']['adminEmail'];
			}
			
			
			cc_mail_direct($to_email, $subject, $msg, $sender_email);
		}
		
	}
	
	
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