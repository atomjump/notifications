<?php

 include_once("classes/cls.pluginapi.php");




    class plugin_notifications
    {
    	public $notifications_config;
    	
    	
    	
    	public function on_notify($stage, $message, $message_details, $message_id, $sender_id, $recipient_id, $in_data)
        {
        
        	$message_forum_name = $message_details->forum_name;
        	
        	/* 				$message_details = array("observe_message" => $observe_message,
										 "observe_url" => $observe_url,
										 "forum_message" => $layer_message,
										 "forum_name" => $layer_name,
										 "remove_message" => $remove_message,
										 "remove_url" => $remove_url);
										 */
        
        
           	if(!isset($this->notifications_config)) {
				//Get global plugin config - but only once
				$data = file_get_contents (dirname(__FILE__) . "/config/config.json");
				if($data) {
					$this->notifications_config = json_decode($data, true);
					if(!isset($this->notifications_config)) {
						error_log("Error: notifications config/config.json is not valid JSON.");
						exit(0);
					}
	 
				} else {
					error_log("Error: Missing config/config.json in notifications plugin.");
					exit(0);
	 
				}
  
  
			}
            

            $api = new cls_plugin_api();


			switch($stage)
			{
			
				case "init":
					// Prep the message
					
					// Payload data you want to send to Android device(s)
					$out_message = str_replace("\\r", "", $message);
					$out_message = str_replace("\\n", "", $out_message);
					
					$out_link = "";
					//Check this is an atomjump.com message
					if(strpos($message_forum_name, "ajps_") !== false) {
						$aj_forum = str_replace("ajps_", "", $message_forum_name);
						$out_link = "window.open(\"http://" . $aj_forum . ".atomjump.com\", \"_system\")";
						$out_forum = $aj_forum . "@";
					
					}
					if($message_forum_name == "test_feedback") {
						//Special case the homepage
						if($this->notifications_config['staging'] == true) {
							
							$out_link = "window.open(\"https://staging.atomjump.com\", \"_system\")";
							$out_forum = "AtomJump@";
						
						} else {
							$out_link = "window.open(\"https://atomjump.com\", \"_system\")";
							$out_forum = "AtomJump@";
						}
					}
					
					$out_message = trim(preg_replace('/\s\s+/', ' ', $out_message));
					
					
					//Get a blank ids 
					$ids = array();

					//See https://github.com/phonegap/phonegap-plugin-push/blob/master/docs/PAYLOAD.md#images
					$data = array(
								  	"message" => $out_message,
								  	"title" => "AtomJump - " . $out_forum,
									"forum" => $message_forum_name,
									"info" => $out_link,
        							"content-available" => "1"
									
								  );
					error_log("Notification prep:" . json_encode($this->data));
					
					$in_data["data"] = $data;
					$in_data["ids"] = $ids;
					$ret_data = $in_data;
					$ret = true;
				break;
				
				case "addrecipient":
						//Add a potential recipient - it will check whether that user has a notification id
						// Returns true: the recipient has a phone with a verified app - so no need to email this one.
						//         false: the recipient has no verified app - will need to email.
						error_log("Notification adding recipient:" . $recipient_id);
						
						$sql = "SELECT var_notification_id FROM tbl_user WHERE int_user_id = " . $recipient_id;
						$result = $api->db_select($sql);
						if($row = $api->db_fetch_array($result))
						{
							if(isset($row['var_notification_id'])) {
								$in_data->ids[] = $row['var_notification_id'];
								error_log("Notification added recipient:" . json_encode($in_data->ids));
								return true;
							}
						}
						
						$ret = false;
						$ret_data = $in_data; 
									
				break;
				
				case "send":
					//If there are some ids to send to
					error_log("Sending notification. Count = " . count($in_data->ids));
					if(count($in_data->ids) > 0) {
				
						//Now start a parallel process that posts the msg      
						global $cnf; 
					 
						$command = $cnf['phpPath'] . " " . dirname(__FILE__) . "/send.php " . urlencode(json_encode($in_data->data)) . " " . urlencode(json_encode($in_data->ids));
					
					
			
						error_log("Command " . $command);
						$api->parallel_system_call($command, "linux");
					}
					
					$ret_data = $in_data; 
					$ret = true;
				break;

								  			
			}

    	
    	
    	/* Old way:
        public function on_message($message_forum_id, $message, $message_id, $sender_id, $recipient_id, $sender_name, $sender_email, $sender_phone, $message_forum_name)
        {
           	if(!isset($this->notifications_config)) {
				//Get global plugin config - but only once
				$data = file_get_contents (dirname(__FILE__) . "/config/config.json");
				if($data) {
					$this->notifications_config = json_decode($data, true);
					if(!isset($this->notifications_config)) {
						error_log("Error: notifications config/config.json is not valid JSON.");
						exit(0);
					}
	 
				} else {
					error_log("Error: Missing config/config.json in notifications plugin.");
					exit(0);
	 
				}
  
  
			}
            
            
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();



			
			
			// The recipient registration tokens for this notification
			// https://developer.android.com/google/gcm/    
				//Get the notification id of the logged user
			$sql = "SELECT var_notification_id FROM tbl_user WHERE int_user_id = " . $recipient_id;
			error_log("SQL:" . $sql);
			$result = $api->db_select($sql);
			if($row = $api->db_fetch_array($result))
			{
				error_log("Notification id:" . $row['var_notification_id']);
				if(isset($row['var_notification_id'])) {
					
					//Confirmed we want to send a message
					
					// Payload data you want to send to Android device(s)
					$out_message = str_replace("\\r", "", $message);
					$out_message = str_replace("\\n", "", $out_message);
					
					error_log("Forum name:" . $message_forum_name);
					$out_link = "";
					//Check this is an atomjump.com message
					if(strpos($message_forum_name, "ajps_") !== false) {
						$aj_forum = str_replace("ajps_", "", $message_forum_name);
						$out_link = "window.open(\"http://" . $aj_forum . ".atomjump.com\", \"_system\")";
						$out_forum = $aj_forum . "@";
					
					}
					if($message_forum_name == "test_feedback") {
						//Special case the homepage
						if($this->notifications_config['staging'] == true) {
							
							$out_link = "window.open(\"https://staging.atomjump.com\", \"_system\")";
							$out_forum = "AtomJump@";
						
						} else {
							$out_link = "window.open(\"https://atomjump.com\", \"_system\")";
							$out_forum = "AtomJump@";
						}
					}
					
					$out_message = trim(preg_replace('/\s\s+/', ' ', $out_message));
					
					
					
					
					// (it will be accessible via intent extras)    
					//$data = array('message' => $out_message);		//remove newlines and double spaces
					$ids = array($row['var_notification_id']);

					//See https://github.com/phonegap/phonegap-plugin-push/blob/master/docs/PAYLOAD.md#images
					$data = array(
								  	"message" => $out_message,
								  	"title" => "AtomJump - " . $out_forum,
									"forum" => $message_forum_name,
									"info" => $out_link,
        							"content-available" => "1"
									
								  );
								 
								  
					
					error_log("Sending message:" . $out_message . "  Outlink:" .  $out_link . "  Forum:" . $message_forum_name);
					
			
					//Now start a parallel process that posts the msg      
					global $cnf; 
					
					error_log(json_encode($cnf));
					
					global $staging;
					 
					$command = $cnf['phpPath'] . " " . dirname(__FILE__) . "/send.php " . urlencode(json_encode($data)) . " " . urlencode(json_encode($ids));
					
					
			
					error_log("Command " . $command);
					$api->parallel_system_call($command, "linux");
			
				}
			}			
			*/

            return array($ret, $ret_data);

        }
        
        

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