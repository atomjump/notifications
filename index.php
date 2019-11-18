<?php

 include_once("classes/cls.pluginapi.php");

	


    class plugin_notifications
    {
    	public $notifications_config;
    	
    	
    	public function null_to_blank_string($val)
    	{
    		if(is_null($val)) return "";
    		return $val;
    	
    	}
    	
    	
    
        public function on_more_settings()
        {
            global $msg;
            
            if(isset($_COOKIE['useapp'])) {
                $use_app = $_COOKIE['useapp'];
            } else {
                $use_app = false;
            }
         
         	if($use_app == true) {
         		$app_html = "checked=\"checked\"";
         	} else {
         		$app_html = "";
         	}
         
            //Enter the HTML in here:
            ?>
                <div>
                    </br>
                    <div class="form-group">
						
						Get popup notifications&nbsp;&nbsp;<a target="_blank" href="https://itunes.apple.com/us/app/atomjump-messaging/id1153387200?ls=1&mt=8">iOS</a>&nbsp;&nbsp;<a target="_blank" href="https://play.google.com/store/apps/details?id=com.atomjump.messaging">Android</a>
						
						
						<!--<input type="checkbox" name="useapp" id="useapp" <?php echo $app_html ?>> 
						Get popup notifications (Install / Open App)-->
					</div>
                    
                    <script>
                    	function deepLinkApp() {
                    		var email = $('#email-opt').val();
                    		var forum = $('#passcode-hidden').val();
                    		var password = $('#password-opt').val();
                    		var server = ajFeedback.server;
                    		var forumPass = $('#forumpass').val();
                    		//alert("App opening in here. Email: " + email + "  Forum:" + forum + " Password: " + password + " Server: " + server + " ForumPass:" + forumPass);
                    		//TODO: var url = "https://your_subdomain.page.link/?link=" + email + ":" + forum + ":" + password + ":" + server + ":" + forumPass + "&apn=com.atomjump.messaging";
                    	
                    	}
                    </script>
                </div>
            
            <?php
            
            return true;
            
        }
        
        public function on_save_settings($user_id, $full_request, $type)
        {
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
            
            switch($type) {
                default:
                    if(isset($full_request['useapp'])) {
                        $old_useapp = $_COOKIE['useapp'];
                        
                        $cookie_name = "useapp";
                        $cookie_value = $full_request['useapp'];
                        setcookie($cookie_name, $cookie_value, time() + (365*3*60*60*24*1000), "/"); // 86400 = 1 day
                        
                        /*
                        //Now refresh the current page
                        if($cookie_value != $old_useapp) {
                             return "RELOAD"; //This reloads the entire page
                        }*/
                    }
                break;
            }
            
            return true;        
            
        
        }
	
    	
    	
    	
    	public function on_notify($stage, $message, $message_details, $message_id, $sender_id, $recipient_id, $in_data)
        {
        
        	$message_forum_name = $message_details['forum_name'];
        	
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
					
					
					//Default link
					$out_link = $message_details['observe_url'];
					
					
					//Check this is an atomjump.com message
					if(strpos($message_forum_name, "ajps_") !== false) {
						$aj_forum = str_replace("ajps_", "", $message_forum_name);
						$out_link = "http://" . $aj_forum . ".atomjump.com/?autostart=true";
						$out_forum = $aj_forum . "@";
					
					}
					if($message_forum_name == "test_feedback") {
						//Special case the homepage
						if($this->notifications_config['staging'] == true) {
							
							$out_link = "https://staging.atomjump.com/?autostart=true";
							$out_forum = "AtomJump@";
						
						} else {
							$out_link = "https://atomjump.com/?autostart=true";
							$out_forum = "AtomJump@";
						}
					}
					
					
					
					//Fill in an image in the message if there is an emoticon
					$image = "";
					preg_match('/https?:\/\/[^ ]+?(?:\.jpg|\.png)/', $message, $matches);
					if($matches[0]) {
						$image = $matches[0];
					}
					$out_message = strip_tags($out_message);
					$out_message = trim(preg_replace('/\s\s+/', ' ', $out_message));		//Remove any spaces either side
					
					
					
					//Get a blank ids 
					$ids = array();

					//See https://github.com/phonegap/phonegap-plugin-push/blob/master/docs/PAYLOAD.md#images
					$android_data = array(
								  	"message" => $out_message,
								  	"title" => "AtomJump - " . $out_forum,
									"forumName" => $this->null_to_blank_string($message_forum_name),
									"forumMessage" => $this->null_to_blank_string($message_details['forum_message']),
									"observeUrl" => $this->null_to_blank_string($out_link),
									"observeMessage" => $this->null_to_blank_string($message_details['observe_message']),
									"removeUrl" => $this->null_to_blank_string($message_details['remove_url']),
									"removeMessage" => $this->null_to_blank_string($message_details['remove_message']),
        							"content-available" => "1"
									
								  );
					
					if($image != "") {
						//Optionally append an emoticon or image to that.
						$android_data['image'] = $image;
					
					}
					
					
					$ios_data = array(
									"alert" => $out_message,
									"content-available" => 1,
									"notification" => array(
											"title" => "AtomJump - " . $out_forum
									),
									"data" => array(
										"forumName" => $this->null_to_blank_string($message_forum_name),
										"forumMessage" => $this->null_to_blank_string($message_details['forum_message']),
										"observeUrl" => $this->null_to_blank_string($out_link),
										"observeMessage" => $this->null_to_blank_string($message_details['observe_message']),
										"removeUrl" => $this->null_to_blank_string($message_details['remove_url']),
										"removeMessage" => $this->null_to_blank_string($message_details['remove_message'])										
									
									)
								); 		  
					
					
									//	"title" => "AtomJump - " . $out_forum
					
					if($image != "") {
						//Optionally append an emoticon or image to that.
						$ios_data['data']['image'] = $image;
						
						//Also extend the alert with a note to say there is an image attached - we can't show the
						//actual image in their popup
						$ios_data['alert'] .= " [image]";
					
					}
					
					//A combined version
					$data = array("android" => $android_data,
									"ios" => $ios_data);
								  
					
					
					$in_data["data"] = $data;
					$in_data["ids"] = $ids;
					$ret_data = $in_data;
					$ret = true;
				break;
				
				case "addrecipient":
						//Add a potential recipient - it will check whether that user has a notification id
						// Returns true: the recipient has a phone with a verified app - so no need to email this one.
						//         false: the recipient has no verified app - will need to email.
						if($sender_id != $recipient_id) {
							//Don't send to our own user
							$ret_data = $in_data; 
							$ret = false;
						
							if($recipient_id) {
								$sql = "SELECT var_notification_id, var_device_type FROM tbl_user WHERE int_user_id = " . $recipient_id;
								$result = $api->db_select($sql);
								if($row = $api->db_fetch_array($result))
								{
									if(isset($row['var_notification_id'])) {
										$ret_data['ids'][] = $row['var_notification_id'];
										$ret_data['device'][] = $row['var_device_type'];		//also store which device type to send to
										$ret = true;
									} 
								}
							}
						} else {
							$ret_data = $in_data;
							$ret = false;
						}
						
									
				break;
				
				case "send":
					//If there are some ids to send to
					if(count($in_data['ids']) > 0) {
				
						//Now start a parallel process that posts the msg      
						global $cnf; 
					 
						$command = $cnf['phpPath'] . " " . dirname(__FILE__) . "/send.php " . 
												urlencode(json_encode($in_data['data'])) . " " .
												urlencode(json_encode($in_data['ids'])) . " " .
												urlencode(json_encode($in_data['device']));
												
												
						$api->parallel_system_call($command, "linux");
						
					}
					
					$ret_data = $in_data; 
					$ret = true;
				break;

								  			
			}

    	

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