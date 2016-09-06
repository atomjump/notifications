<?php

     function sendPushNotification($data, $ids)
	 {
			// Insert real GCM API key from the Google APIs Console
			// https://code.google.com/apis/console/        
			$apiKey = $this->notifications_config['apiKey'];

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
  	error_log("Data: " . $data . "\n\nIds: " . $ids);
  	sendPushNotification($data, $ids);
  
  
    
    
?>