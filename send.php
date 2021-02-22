<?php

	
	if(!isset($notifications_config)) {
        //Get global plugin config - but only once
		$config_data = file_get_contents (dirname(__FILE__) . "/config/config.json");
        if($config_data) {
            $notifications_config = json_decode($config_data, true);
            if(!isset($notifications_config)) {
                echo "Error: notifications config/config.json is not valid JSON.";
                error_log("Error: notifications config/config.json is not valid JSON.");
                exit(0);
            }
     
        } else {
            echo "Error: Missing config/config.json in notifications plugin.";
            error_log("Error: Missing config/config.json in notifications plugin.");
            exit(0);
     
        }
  
  
    }
    
    
    
    function post_multipart($url, $data, $headers)
	{
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true, // return the transfer as a string of the return value
			CURLOPT_TIMEOUT => 0,   // The maximum number of seconds to allow cURL functions to execute.
			CURLOPT_POST => true,   // This line must place before CURLOPT_POSTFIELDS
			CURLOPT_POSTFIELDS => $data // The full data to post
		));
		// Set Header
		if (!empty($headers)) {
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		}
		$response = curl_exec($curl);
		$errno = curl_errno($curl);
		if ($errno) {
			return false;
		}
		curl_close($curl);
		return $response;
	}
    
    
    // Insert real GCM API key from the Google APIs Console
	// https://code.google.com/apis/console/        
	$apiKey = $notifications_config['apiKey'];

    


     function sendPushNotification($data, $ids, $apiKey, $devices)
	 {
		
			//First establish which ids are iphone and which are android
			$android_ids = array();
			$ios_ids = array();
			$atomjump_ids = array();
			
			for($cnt = 0; $cnt < count($devices); $cnt++) {
				switch($devices[$cnt])
				{
				
					case 'iOS':
						$ios_ids[] = $ids[$cnt];
					break;
					
					case 'AtomJump':
						$atomjump_ids[] = $ids[$cnt];
					break;
					
					default:
						$android_ids[] = $ids[$cnt];
					
					break;
				}
			
			}
		
			//Now process Android messages
		
			if(count($android_ids) > 0) {
		
				// Set POST request body
				$post = array(
								'registration_ids'  => $android_ids,
								'data'              => $data->android,
							 );
			


				// Set CURL request headers 
				$headers = array( 
									'Authorization: key=' . $apiKey,
									'Content-Type: application/json'
								);

				// Initialize curl handle       
				$ch = curl_init();

				// Set URL to GCM push endpoint     
				curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');

				// Set request method to POST       
				curl_setopt($ch, CURLOPT_POST, true);

				// Set custom request headers       
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

				// Get the response back as string instead of printing it       
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				// Set JSON post data
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));

				// Actually send the request    
				$result = curl_exec($ch);

				// Handle errors
				if (curl_errno($ch))
				{
					error_log('FCM error: ' . curl_error($ch));
				}

				// Close curl handle
				curl_close($ch);
			}
			
			
			
			//Now process iOS messages, one at a time - not sure if can do a group send 
			//http://stackoverflow.com/questions/21250510/generate-pem-file-used-to-setup-apple-push-notification
			for($cnt = 0; $cnt < count($ios_ids); $cnt++) {
				//See this for future ref: http://codular.com/sending-ios-push-notifications-with-php
			
				$deviceToken = $ios_ids[$cnt];	//e.g. '29954cd9ace7a7c29f66918e62e8a18522619c5cabae08972da6cd4273fe874c';
				$passphrase = 'apns';
				//$message = 'test';										
				$ctx = stream_context_create();
				stream_context_set_option($ctx, 'ssl', 'local_cert', dirname(__FILE__) . '/pushcert.pem');		//pushcert.pem
				stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
				$fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
				$body['aps'] = $data->ios;		//Eg. array('alert' => $message,'sound' => 'default');
				$payload = json_encode($body);
				$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
				$result = fwrite($fp, $msg, strlen($msg));
				fclose($fp);
			}
			
			
			if(count($atomjump_ids) > 0) {
				//Post the message as a .json file using a curl POST request multipart/form-data to the ID as the URL
				for($cnt = 0; $cnt < count($atomjump_ids); $cnt++) {
					$post_url = $atomjump_ids[$cnt];		//e.g. https://medimage-nz1.atomjump.com/api/photo/#HMEcfQQCufJmRPMX4C
					$post_url = $post_url . "-message" . rand(1,999999) . ".json";	//So that the URL is called e.g. https://medimage-nz1.atomjump.com/write/HMEcfQQCufJmRPMX4C-message324456.json
	
	
					$post = array(
								'data' => $data->android,
							 );
					$data = json_encode($post);
					
					if (function_exists('curl_file_create')) {
						$data['avatar'] = curl_file_create($file);
					} else {
						$data['avatar'] = '@' . $file;
					}
					
					error_log("Data: " . $data . "  To URL:" . $post_url);
				
					$headers = ["User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.117 Safari/537.36"];
					$resp = post_multipart($post_url, $data, $headers);
					error_log($resp);
				}
			}
			
			return;
	}

    
    $data = json_decode(urldecode($argv[1]));
    $ids = json_decode(urldecode($argv[2]));
    $devices = json_decode(urldecode($argv[3]));

  	sendPushNotification($data, $ids, $apiKey, $devices);
  
  
    
    
?>