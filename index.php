<?php

 include_once("classes/cls.pluginapi.php");

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


    class plugin_notifications
    {
        public function on_message($message_forum_name, $message, $message_id, $sender_id, $recipient_id, $sender_name, $sender_email, $sender_phone)
        {
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();



			// Payload data you want to send to Android device(s)
			// (it will be accessible via intent extras)    
			$data = array('message' => $message);
			
			// The recipient registration tokens for this notification
			// https://developer.android.com/google/gcm/    
				//Get the notification id of the logged user
			$sql = "SELECT var_notification_id FROM tbl_user WHERE int_user_id = " . $recipient_id;
			$result = $api->db_select($sql);
			if($row = $api->db_fetch_array($result))
			{
				
				if(isset($row['var_notification_id'])) {
					$ids = array($row['var_notification_id']);
			
					
					// Send push notification via Google Cloud Messaging. TODO: may need to run in a background process
					sendPushNotification($data, $ids);
				}
			}			


            return true;

        }
    }







	function sendPushNotification($data, $ids)
	{
		// Insert real GCM API key from the Google APIs Console
		// https://code.google.com/apis/console/        
		$apiKey = $notifications_config['apiKey'];

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
		curl_setopt($ch, CURLOPT_URL, 'https://gcm-http.googleapis.com/gcm/send');

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
			echo 'GCM error: ' . curl_error($ch);
		}

		// Close curl handle
		curl_close($ch);

		// Debug GCM response       
		echo $result;
	}

/*
https://gcm-http.googleapis.com/gcm/send
Content-Type:application/json
Authorization:key=AIzaSyZ-1u...0GBYzPu7Udno5aA

{ "data": {
    "score": "5x1",
    "time": "15:10"
  },
  "to" : "bk3RNwTe3H0:CI2k_HHwgIpoDKCIZvvDMExUdFQ3P1..."
}
*/



?>