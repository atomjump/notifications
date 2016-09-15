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

    


     function sendPushNotification($data, $ids, $apiKey)
	 {
		
			// Set POST request body
			$post = array(
							'registration_ids'  => $ids,
							'data'              => $data,
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
			
			return;
	}

    
    
    $data = json_decode(urldecode($argv[1]));
    $ids = json_decode(urldecode($argv[2]));
  	sendPushNotification($data, $ids, $apiKey);
  
  
    
    
?>