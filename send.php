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
    
    
    // Insert real GCM API key from the Google APIs Console
	// https://code.google.com/apis/console/        
	$apiKey = $notifications_config['apiKey'];

    


     function sendPushNotification($data, $ids, $apiKey, $devices)
	 {
		
			//First establish which ids are iphone and which are android
			$android_ids = array();
			$ios_ids = array();
			
			for($cnt = 0; $cnt < count($devices); $cnt++) {
				switch($devices[$cnt])
				{
				
					case 'iOS':
						$ios_ids[] = $ids[$cnt];
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
				curl_setopt($ch, CURLOPT_URL, 'https://android.googleapis.com/gcm/send');

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
					error_log('GCM error: ' . curl_error($ch));
				}

				// Close curl handle
				curl_close($ch);

				// Debug GCM response       
				error_log($result);
			}
			
			
			
			//Now process iOS messages, one at a time - not sure if can do a group send 
			//http://stackoverflow.com/questions/21250510/generate-pem-file-used-to-setup-apple-push-notification
			for($cnt = 0; $cnt < count($ios_ids); $cnt++) {
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
			
			
			return;
	}

    
    $data = json_decode(urldecode($argv[1]));
    $ids = json_decode(urldecode($argv[2]));
    $devices = json_decode(urldecode($argv[3]));

  	sendPushNotification($data, $ids, $apiKey, $devices);
  
  
    
    
?>