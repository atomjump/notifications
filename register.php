<?php

	function cors() {
	
		// Allow from any origin
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			// Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
			// you want to allow, and if so:
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
			header('Access-Control-Allow-Credentials: true');
			header('Access-Control-Max-Age: 86400');    // cache for 1 day
		}
	
		// Access-Control headers are received during OPTIONS requests
		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
		
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
				// may also be using PUT, PATCH, HEAD etc
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         
		
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
				header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	
			return;
		}
		return;		
	}


	function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
    }
    
    
    //Get status of which notifications are supported
	function check_device_available($device_type, $notifications_config) {
		//Returns a blank string if it is supported, or a string which should be
		//displayed, if not
		global $lang;
		
		
		
		$use_android = false;
		$use_ios = false;
		$use_atomjump = false;
	
		if(isset($notifications_config['androidNotifications']) && 
		   isset($notifications_config['androidNotifications']['use'])) {
	
				if($notifications_config['androidNotifications']['use'] == true) {
					$use_android = true;
				}
		} else {
			//Check legacy option exists
			if(isset($notifications_config['apiKey'])) {
				//Use the legacy option
				$use_android = true;
			}
		}

		if(isset($notifications_config['iosNotifications']) && 
			isset($notifications_config['iosNotifications']['use'])) {
	
			if($notifications_config['iosNotifications']['use'] == true) {
				$use_ios = true;
			}
		} else {
			//Check legacy file exists
			if(file_exists(__DIR__ . "/pushcert.pem")) {
				$use_ios = true;
			}
		}

		if(isset($notifications_config['atomjumpNotifications']) && 
			isset($notifications_config['atomjumpNotifications']['use'])) {
	
			if($notifications_config['atomjumpNotifications']['use'] == true) {
				$use_atomjump = true;
			}
		}
		
		$return_string = null;
		
		
		//Compare device requested, with what ther server supports
		switch($device_type) {
			case "iOS":
				if($use_ios == true) {
					//All good
				} else {
					$return_string = $notifications_config['msgs'][$lang]['wrongApp'];
				}
				
			break;
			
			case "Android":
				if($use_android == true) {
					//All good
				} else {
					$return_string = $notifications_config['msgs'][$lang]['wrongApp'];
				}
			break;
			
			
			case "AtomJump":
				if($use_atomjump == true) {
					//All good
				} else {
					$return_string = $notifications_config['msgs'][$lang]['wrongApp'];
				}
			break;	
			
			case "Unknown":
				$return_string = "";	//Assume we can use it if not specified - it is likely a de-registration where the type hasn't been specified by the app.
			break;
			
			default:
				//Trying to use a different type than we understand. Assume not supported.
				$return_string = $notifications_config['msgs'][$lang]['wrongApp'];			
			break;
		
		}
		
		//Swap in the streaming app link
		$return_string = str_replace("[STREAMINGAPPLINK]", $notifications_config['streamingAppLink'], $return_string);
				
		return $return_string;
	}


	//Called by the AtomJump messaging app to register this particular user's phone
	
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
	
	$define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	
	//For plugins - language change in particular
	require($start_path . "classes/cls.layer.php");
	require($start_path . "classes/cls.ssshout.php");
	
	require($start_path . "classes/cls.pluginapi.php");
	
	$ly = new cls_layer();
	$sh = new cls_ssshout();
	
	$api = new cls_plugin_api();

	
	

	

	if(isset($_REQUEST['action'])) {
		//Vers apps > 1.0.4 and above will have this option set. Can be 'add' or 'remove'
		$multi_device = true;
		if($_REQUEST['action'] == 'add') {
			$action = "add";	
		} else {
			$action = "remove";
		}
	} else {
		//A classic <= 1.0.4 version app and below won't have this option at all.
		//Fully deregistered 
		$multi_device = false;
		$action = "add";
	}
	
	//Set the notification id for this user/phone
	if((isset($_REQUEST['id']))&&($_REQUEST['id'] != "")) {
		$raw_notification_id = $_REQUEST['id'];
		$notification_id = "'" . clean_data(urldecode($raw_notification_id)) . "'";	
		
	} else {
		$raw_notification_id = "";
		$notification_id = "NULL";
		
		//This null case will remove all devices with the old apps
		$action = "remove";
	}
	
	if(isset($_REQUEST['devicetype'])) {
		$device_type = clean_data($_REQUEST['devicetype']);
	} else {
		$device_type = "Unknown";			//Default to Unknown if unknown
	}
	
	
	
	//Get the user id from the session variable, if it exists - this is secure
	if(isset($_SESSION['logged-user']) && ($_SESSION['logged-user'] != "")) {
		$user_id = $_SESSION['logged-user'];
	} 
	
	
	/*
	
	A warning: these types of options are insecure, and would allow someone to
	go through and set all users to your own app:
	
	if(isset($_REQUEST['userid']) && ($_REQUEST['userid'] != "")) {
		$user_id = $_REQUEST['userid'];
	} else {
		
	}
	
	 */
	
	//This passed in email is for visible display purposes, only, and is not used 
	//to select a user, itself, in the backend, or it would be insecure.
	if(isset($_COOKIE['email'])) {
		 $display_email = urldecode($_COOKIE['email']); 
	} else {  
		if(isset($_REQUEST['email'])) { 
			$display_email = urldecode($_REQUEST['email']);
		} else {
			$display_email = '';		//Leave blank for user input
		}
	}
	 
	
	if(isset($_REQUEST['d'])) {
		//This can be passed in - a unique GUID generated when creating a user, which identifies a user id
		$sql = "SELECT * FROM tbl_user WHERE var_confirmcode = '" . clean_data($_REQUEST['d']) . "'";
		
		$result = $api->db_select($sql);
		if($row = $api->db_fetch_array($result))
		{
			//This confirmcode exists - get the user id from it
			$user_id = $row['int_user_id'];
			$user_email = $row['var_email'];		//For usage purposes, not just display
			error_log("Pairing via confirm code. Using user_id:" . $user_id . " Email:" . $user_email);
		} else {
			//Incorrect confirm code. Leave user blank
			error_log("Incorrect error code submitted. This could be a bulk probe.");
		}
	
	} else {
		//User's email for usage purposes, not just display.
		if(isset($_SESSION['logged-email']) && ($_SESSION['logged-email'] != "")) {
			$user_email = $_SESSION['logged-email'];
			
			//This helps distinguish between an auto-account with a user id only
			//vs an account which has been set with a full email address
			
		} else {
			$user_email = "";		
		}
		
		
	
	}
	
	

	global $msg;
	global $cnf;
	global $lang;	
	global $root_server_url;
	
	
	
	
	
	//The $follow_on_link is the default location to go to next. The large messaging icon now always opens a setup messaging window.
	$follow_on_link = "https://atomjump.com";
	if($cnf['serviceHome']) {
		$follow_on_link = add_subdomain_to_path($cnf['serviceHome']);
	}
	
	
	$center = "center";			//Default centering
	$screen_type = "standard";	//Standard message page
	
	
	$subdomain = check_subdomain();
	$webroot = trim_trailing_slash_local($cnf['webRoot']);
	
	if((isset($subdomain))&&($subdomain != "")) {
		$replace_with = $subdomain . ".";
		$webroot = trim_trailing_slash_local(str_replace("[subdomain]", $replace_with,$webroot));
	} else {
		$webroot = str_replace("[subdomain]", "",$webroot);		//Always remove this string if it exists
	}		
	
	if(isset($cnf['chatInnerJSFilename']) &&
	  (file_exists(add_trailing_slash_local($cnf['fileRoot']) . $cnf['chatInnerJSFilename']) )
	  ) {
			$chat_inner_js_filename = $cnf['chatInnerJSFilename'];
			$inner_js = trim_trailing_slash_local($webroot) . $chat_inner_js_filename;
	} else {
		//The default version
		$chat_inner_js_filename = "js/chat-inner-1.3.31.js";			//Use the local version from the plugin
		$inner_js = $chat_inner_js_filename;
	}
	
	
	function atomjump_com_subdomain($user_id) 
	{
		//With this new user ID we don't, by default, know which forum the person wants to actually listen in to (they usually have to tap the 'ear' button to listen)
		//but in the special case of xyz.atomjump.com pages, there is only one forum that they want to listen to, and it is 'ajps_[subdomain]'. We can safely
		//switch on listening to that forum (if it is a public forum)
		//There is one caveat - we don't handle private forums with this. You still need to log in first on those.
		
		//Determine if we have a subdomain
		$subdomain = check_subdomain();		//From db_connect.php
		if($subdomain) {
			$lg = new cls_login();
			$ly = new cls_layer();
			
			$layer_name = "ajps_" . $subdomain;
			$json = $lg->subscribe($user_id, $layer_name, null);		//No password is handled
		}
		
		
		return;
	}
	

	
	$speech_bubble_link = "javascript:";		//Defaults to opening a welcome forum
	
	if(($user_id == "")||($user_email == "")) {			//So there is a case where a user_id is set
														//but the user_email is not.
														//We want to show the signup in this 
														//case still
		//A blank user id - show the sign-up screen only.
		$screen_type = "signup";		//
		$main_message = $notifications_config['msgs'][$lang]['notLoggedIn'];
		$first_button = "#comment-open-Setup";
		$first_button_wording = $notifications_config['msgs'][$lang]['openSetup'];
		$second_button = "";	
		$second_button_wording = ""; 
		$center = "left";   
		
		//But if there is no notification ID, we don't want users to sign up, either.
		//This fairly rare case happens after a 'release' button is pushed, but we
		//have not yet signed up.
		if($raw_notification_id == "") {
				 
				
			 //Show the 'app has been deregistered' screen instead.
			 $screen_type = "standard";
			 $main_message = $notifications_config['msgs'][$lang]['appDeregistered'];
			 $first_button = $follow_on_link;
			 $first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
			 $second_button = "";
			 $second_button_wording = "";
				

		}
		
	} else {
		//We have a user id		
		$has_been_confirmed = false;
		if($user_id) {
			//Check if the user has been confirmed.
			$sql = "SELECT * FROM tbl_user WHERE int_user_id = " . $user_id;
			$result = $api->db_select($sql);
			if($row = $api->db_fetch_array($result))
			{
				if($row['enm_confirmed'] == 'confirmed') {
					//Yes this is a confirmed user.
					$has_been_confirmed = true;
				}
			}
		} else {
			//This should never happen. Only case would be a coding mistake above
			die("Sorry, there was a problem getting the user ID.");	
				
		}
		
		if($has_been_confirmed != true) {
			//User has not been confirmed (or doesn't exist). We will need to send a new confirmation email.
			$follow_on_link = "#comment-open-Setup";
			$main_message = $user_email . ": " . $notifications_config['msgs'][$lang]['mustBeConfirmed'];
			$first_button = "javascript:window.location.reload(true);";
			$first_button_wording = $notifications_config['msgs'][$lang]['completePairing'];
			$second_button = "";
			$second_button_wording = "";	
		
			//Send off another confirmation email
			//This code is pretty similar to that in cls_ssshout.php new_user()
			//We create a new confirmation code.
			$confirm_code = md5(uniqid(rand())); 
			
			$sql = "UPDATE tbl_user SET var_confirmcode = '" . clean_data($confirm_code) . "' WHERE int_user_id = " . $user_id;
			$result = $api->db_select($sql);
			
			$body_message = $msg['msgs'][$lang]['welcomeEmail']['pleaseClick'] . $root_server_url . "/link.php?d=" . $confirm_code . "&id=" . urlencode($raw_notification_id) . "&devicetype=" . urlencode($device_type) . $msg['msgs'][$lang]['welcomeEmail']['confirm'] . str_replace('CUSTOMER_PRICE_PER_SMS_US_DOLLARS', CUSTOMER_PRICE_PER_SMS_US_DOLLARS, $msg['msgs'][$lang]['welcomeEmail']['setupSMS']) . str_replace('ROOT_SERVER_URL',$root_server_url, $msg['msgs'][$lang]['welcomeEmail']['questions']) . $msg['msgs'][$lang]['welcomeEmail']['regards'];
			error_log($body_message);
						
			$notify = true;			//Switch on global notifications			
			cc_mail_direct($user_email, $msg['msgs'][$lang]['welcomeEmail']['title'], $body_message, $cnf['email']['webmasterEmail']);			//Taken away the _direct(
			error_log("Have sent email");
		
		} else {
			//Has been confirmed
			
			
			//With this new user ID we don't, by default, know which forum the person wants to actually listen in to (they usually have to tap the 'ear' button to listen)
			//but in the special case of xyz.atomjump.com pages, there is only one forum that they want to listen to, and it is 'ajps_[subdomain]'. We can safely
			atomjump_com_subdomain($user_id);
			
			
			//Split the ids up and handle each one
			$raw_notification_ids = explode("|", $raw_notification_id);
			$notification_ids = array();
			for($cnt = 0; $cnt < count($raw_notification_ids); $cnt++) {
				$notification_ids[$cnt] = "'" . clean_data(urldecode($raw_notification_ids[$cnt])) . "'";			
			}
				
			$device_types = explode("|", $device_type);
			$one_device_type_available = false;		//Trigger this to display a success message
			
			for($cnt = 0; $cnt < count($notification_ids); $cnt++) {
			
				//Create an unregister link
				$unregister_link = "register.php?userid=" . $user_id . "&id=" . $raw_notification_ids[$cnt] . "&devicetype=" . $device_types[$cnt] . "&action=remove";
			
			
			
				$device_type_not_available = check_device_available($device_types[$cnt], $notifications_config);
			
				if(!$device_type_not_available) {
					//In other words, there is no error, here
					$one_device_type_available = true;
				}
			
			
				//Update the user table with the new entry (for a single device type or a multi device type)
				if((!$device_type_not_available)||($raw_notification_ids[$cnt] == "")) {
					//Update if this device's message type is available on this server, or the app being deregistered
					$sql = "var_notification_id = " . $notification_ids[$cnt] . ", var_device_type = '" . $device_types[$cnt] . "' WHERE int_user_id = " . $user_id;
					$api->db_update("tbl_user", $sql);
				}
			
				//Now handle the multi-device type, but also always add this. The 'action' entry will
				//be already adjusted for the old apps which don't have a specific action specified.
				if(!$device_type_not_available)	{
					//Device type is available on this server
				
					if($action == "add") {
						//Add entry to devices table for this user
					
						//But 1st check if the device already exists for this user, to avoid duplicates
						$sql = "SELECT * FROM tbl_devices WHERE var_notification_id = " . $notification_ids[$cnt] . " AND int_user_id = " . $user_id;
	
						$result = $api->db_select($sql);
						if($row = $api->db_fetch_array($result))
						{
							//There is already an entry - no need to add another
						} else {
							//No entry already exists, add this one
							$api->db_insert("tbl_devices", "(int_devices_id, int_user_id, var_notification_id, var_device_type)", "(NULL, " . $user_id . ", " . $notification_ids[$cnt] . ",'" . $device_types[$cnt] . "')");
						}
					} else {
						//Remove entry from devices table
						if($raw_notification_ids[$cnt] == "") {
							//Remove all multi device entries for this user
							$sql = "DELETE FROM tbl_devices WHERE int_user_id = " . $user_id;
							$api->db_select($sql);
						} else {
							//Remove this one multi-device entry for this user
							$sql = "DELETE FROM tbl_devices WHERE var_notification_id = " . $notification_ids[$cnt] . " AND int_user_id = " . $user_id;
							$api->db_select($sql);
						}
						
						
						//And remove the main entry for this user entry, regardless. 
						//Or it will not clear out and go back to 'email-only' status.
						$sql = "var_notification_id = NULL, var_device_type = NULL WHERE int_user_id = " . $user_id;
						$api->db_update("tbl_user", $sql);
						//Note: this entry will only be used if there are 0 entries in
						//the tbl_devices table for that user. 
					}
				}
			}
			

			if(($raw_notification_id == "")||($action == "remove")) {
				 //App has been deregistered
				 if($action == "remove") {
				 	//Determine if there are any more devices in the list. If so, display the fully deregistered message. Otherwise display the partially deregistered message.
				 	
				 	$full_display = false;
				 	$sql = "SELECT COUNT(*) AS device_count FROM tbl_devices WHERE int_user_id = " . $user_id;
				 	$result = $api->db_select($sql);
				 	if($row = $api->db_fetch_array($result))
					{
				 		if($row['device_count'] <= 0) {
				 			//No more devices for this user - display full message
				 			$full_display = true;
				 		}
				 	}
				 	
				 	if($full_display == true) {
			 			 $main_message = $notifications_config['msgs'][$lang]['appDeregistered'];
						 $first_button = $follow_on_link;
						 $first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
						 $second_button = "";
						 $second_button_wording = "";
				 	} else {
			 			 $main_message = $notifications_config['msgs'][$lang]['appDeregisteredMulti'];
						 $first_button = $follow_on_link;
						 $first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
						 $second_button = "";
						 $second_button_wording = "";				 	
				 	} 
				 	
				 	
				 } else {
				 	//User has a single device, or is using the old app - always show the fully deregistered 'receive emails only' message.
					 $main_message = $notifications_config['msgs'][$lang]['appDeregistered'];
					 $first_button = $follow_on_link;
					 $first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
					 $second_button = "";
					 $second_button_wording = "";
				}	
			} else {
				 //App is registered successfully!
				 if($one_device_type_available == true) {
				 	 //Registered pairing successfully
					 if($user_email == "") {
						$user_email = "[none]";
					 }
					 $speech_bubble_link = 'href="' . $follow_on_link . '"';		//Take you on to the messaging page
					 $main_message = str_replace("[email]", $user_email,  $notifications_config['msgs'][$lang]['appRegistered']);
					 $first_button = $unregister_link;
					 $first_button_wording = $notifications_config['msgs'][$lang]['deregister'];
					 $second_button = $follow_on_link;
					 $second_button_wording = $notifications_config['msgs'][$lang]['backHome'];
				 
				 
				 } else {
					 //All unavailable messaging formats - suggest switch apps to e.g. browser version
					 $screen_type = "standard";
					 $main_message = $device_type_not_available;
					 $first_button = $follow_on_link;
					 $first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
					 $second_button = "";
					 $second_button_wording = "";	
				}
				 
			}
			
		}
	}
	

	
	
	
	cors();

?><!DOCTYPE html>
<html lang="en" id="fullscreen">
  <head>
  	    <meta charset="utf-8">
		 <meta name="viewport" content="width=device-width, user-scalable=no">
		 <title>AtomJump Messaging Server - provided by AtomJump</title>

		 <meta name="description" content="<?php echo $msg['msgs'][$lang]['description'] ?>">

		 <meta name="keywords" content="<?php echo $msg['msgs'][$lang]['keywords'] ?>">

			  <!-- Bootstrap core CSS -->
			<link rel="StyleSheet" href="css/bootstrap.min.css" rel="stylesheet">

			<!-- AtomJump Feedback CSS -->
			<link rel="StyleSheet" href="css/comments-1.0.4.css?ver=1">

			<!-- Bootstrap HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
			<!--[if lt IE 9]>
			  <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
			  <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
			  <style>
			  

				.looplogo {
					background: url(images/logo640.png)  no-repeat;
					position: relative;
					width: 640px;
					height:640px;
					margin-left: auto;
					margin-right: auto;
				}
			  </style>
			<![endif]-->

			<!-- Include your version of jQuery here.  This is version 1.9.1 which is tested with AtomJump Feedback. -->
			<script type="text/javascript" src="<?php echo $webroot ?>/js/jquery-1.11.0.min.js"></script>
			<!-- Took from here 15 May 2014: http://ajax.googleapis.com/ajax/libs/jquery/1.9.1 -->

			<!-- For the dropdown autocomplete -->
			<link rel="stylesheet" href="css/jquery-ui.css">
			<script src="js/jquery-ui.js"></script>


			<script>
					//Add your configuration here for AtomJump Feedback
					var ajFeedback = {
						"uniqueFeedbackId" : "Setup",	//Anything globally unique to your company/page, starting with 'apix-'	
						"myMachineUser" : "<?php echo $cnf['adminMachineUser']; ?>",			
						"server":  "<?php echo $webroot; ?>",
						"cssFeedback": "css/comments-1.0.4.css?ver=1",
						"cssBootstrap": "css/bootstrap.min.css"
					}
			</script>
			
			<?php if($screen_type == "signup") { ?>
				<script type="text/javascript" src="<?php echo $inner_js ?>"></script>
			<?php } else { //Warning - these will conflict if on the same screen ?>
				<script type="text/javascript" src="js/chat-1.0.9.js"></script>
				<!--No svg support -->
				<!--[if lt IE 9]>
				  <script src="https://frontcdn.atomjump.com/atomjump-frontend/chat-1.0.7.js"></script>
				<![endif]-->
			<?php } ?>
			 


			<style>
			
				
			
				h2 {
					text-align: center;
				}
				
				.signuptitle {
					font-size: 24px;
					font-family: inherit;
					font-weight: 500;
					line-height: 1.1;
					color: inherit;
				} 
				
				.signuptitle-section {
					margin-top: 20px;
					margin-bottom: 30px;
				}

				textarea:focus, input:focus, img:focus {
					outline: 0;
				}
		
				.looplogo {
					position: relative;
					width: 600px;
					height:600px;
					margin-left: auto;
					margin-right: auto;
					z-index: 10;
				}


				.overimage {
					position: relative;
					top: 0px;
					z-index: 1;
				}


        		.darkoverlay {
        			position: relative;
        			top: 100px;
        			width: 100%;
        			background-color: black;
        			opacity: 0.5;
    				filter: alpha(opacity=50); /* For IE8 and earlier */
    				z-index: 1;
        		}
        		
        		.infront {
        			z-index: 100;
        			position: relative;	/* Trying this */
        		}


			   .share {
					position: fixed;
					top: 10px;
					float: left;
					margin-left: 20px;
					z-index: 20;
				}

				.cpy {
						position: relative;
						right: 10px;
						bottom: 10px;
						float: right;
						margin-right: 20px;
				}

			
				/*.cpy a:link, a:visited {
					color: #888;
				}*/



				/* iphone and other phones */
				@media screen and (max-width: 480px) {

					.looplogo {
						width: 320px;
						height:320px;


					}

					.looplogo:hover {
						height: 320px;
						width: 320px;
					}

					.subs {

						position: relative;
						margin-top: 10px;
						float: left;
						margin-left: 20px;
						z-index: 0;
					}

					.cpy {
						position: relative;
						margin-top: 10px;
						margin-right: 20px;
					}

				
				}


				/* ipad */
				@media screen and (max-device-width: 1024px) and (min-device-width: 768px) {

					.cpy {
						position: relative;
						right: 10px;
						bottom: 10px;
						float: right;
						margin-right: 20px;
						z-index: 20;

					}

	
				}

				/* Samsung S4 */
				@media screen and (-webkit-min-device-pixel-ratio: 3.0) and (max-width: 1080px) {
					.looplogo {
						width: 320px;
						height:320px;


					}

					.looplogo:hover {
						height: 320px;
						width: 320px;
					}

					.subs {

						position: fixed;
						bottom: 10px;
						float: left;
						margin-left: 20px;
						z-index: 0;
					}

					.cpy {
						position: relative;
						right: 10px;
						bottom: 10px;
						float: right;
						margin-right: 20px;
					}


					
				}

				#bg {
					position:relative;
					top:0;
					left:0;
					width:100%;
					height:100%;
					z-index: -1;
				}
				
				
				





			</style>


			<script>
    		var ie8 = false;
			</script>



			<!--[if IE 8]>
				<script>
					ie8 = true;
					document.getElementById('sumo').src = "";	//blank out this on IE8
				</script>
			<![endif]-->

	</head>

	<body>


		<script>
				var granted = false;
				
				var sendPublic = true;
				var sendPrivatelyMsg = '<?php echo $msg['msgs'][$lang]['sendPrivatelyButton'] ?>';
				var sendPubliclyMsg = '<?php echo $msg['msgs'][$lang]['sendButton'] ?>';
				var goPrivateMsg = '<?php echo $msg['msgs'][$lang]['sendSwitchToPrivate'] ?>';
				var goPublicMsg = '<?php echo $msg['msgs'][$lang]['sendSwitchToPublic'] ?>';

				//Overwrite the default message slightly
				lsmsg.msgs.en.loggedIn = "Logged in.";		//original is 'Logged in. Please wait..'
				lsmsg.msgs.es.loggedIn = "Conectado.";
				lsmsg.msgs.pt.loggedIn = "Iniciado.";
				lsmsg.msgs.ch.loggedIn = "已登录。";
				lsmsg.msgs.de.loggedIn = "Eingeloggt.";
				lsmsg.msgs.fr.loggedIn = "Connecté.";
				lsmsg.msgs.hi.loggedIn = "में लॉग इन";
				lsmsg.msgs.ru.loggedIn = "Выполнен вход.";
				lsmsg.msgs.jp.loggedIn = "ログインしました。";
				lsmsg.msgs.bg.loggedIn = "লগ ইন";
				lsmsg.msgs.ko.loggedIn = "로그인되었습니다.";
				lsmsg.msgs.pu.loggedIn = "ਲੌਗ ਇਨ ਹੋਇਆ.";
				lsmsg.msgs.it.loggedIn = "Accesso effettuato.";
				lsmsg.msgs.in.loggedIn = "Sudah masuk.";
				lsmsg.msgs.cht.loggedIn = "已登錄。";

				function isChromeDesktop()
				{
					var ua = navigator.userAgent;
					if ((/Chrome/i.test(ua))||(/Safari/i.test(ua))) {
						//Is Chrome, now return false if mobile version - actually Android we still want this option on
						if (/webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile|mobile/i.test(ua)) {
							return false;
						}
						return true;

					} else {
						return false;
					}
				}
					
					
				function getParentUrl() {
					var isInIframe = (parent !== window),
						parentUrl = null;

					if (isInIframe) {
						parentUrl = document.referrer;
					}

					return parentUrl;
				}
				
				
				function clearPass()
				{
					var ur = "<?php echo $webroot; ?>/clear-pass.php";
				
					var email = $('#email-opt').val();
					if(email != '') {
						ur = ur + '?email=' + email;
					
						//Also save this cookie
						document.cookie = 'email=' + encodeURIComponent(email) + '; path=/; expires=' + cookieOffset() + ';';
					}
				
			
					$('#clear-password').html("<img src=\"img/ajax-loader.gif\" width=\"16\" height=\"16\">");
					 $.get(ur, function(response) { 
						 
						   $('#clear-password').html(response);
					   
					 });
				  
					 return false;
				 }
				 
				 function refresh() {
				  var hash = null;
				  var url = window.location.origin;
				  var pathname = window.location.pathname;
				  if(window.location.search) {
				  		hash = window.location.search;
				  } else {
				  		if(window.location.hash) {
				  	 		hash = window.location.hash;
				  	 	}
				  }
					
				  if(hash) {
				  	 if(hash[0] == '?') hash = '&' + hash.substring(1);
				 	 window.location = url + pathname + '?application_refresh=' + (Math.random() * 100000) + hash;
				  } else {
				  	window.location.reload(true);
				  }
				}


				$(document).ready(function(){
					$("#change-lang-button").click(function() {
						var newLang = $("[name='lang']").val();
						document.cookie = 'lang=' + newLang  + '; path=/; expires=' + cookieOffset() + ';'; 
						//Works on all platforms except iphones: window.location.reload(true);
						refresh();
					});
					
					
					
					$("#pair-again-button").click(function() {
						//Works on all platforms except iphones: window.location.reload(true);
						refresh();
					});
					
					
					$("#sign-and-pair-button").click(function() {
						
						var allGood = true;
						if($("#email-opt").val() == '') {
							$("#comment-messages").html("<?php echo $msg['msgs'][$lang]['enterEmail'] ?>");		//"Enter your email". Better would be: Please enter an email address.
							allGood = false;
						}
						
						if($("#password-opt").val() == '') {
							$("#comment-messages").html("<?php echo $msg['msgs'][$lang]['enterPassword'] ?>");		//"Enter your password" Please enter a password.
							
							allGood = false;
						}
						
						if(($("#email-opt").val() == '') && ($("#password-opt").val() == '')) {
							$("#comment-messages").html("<?php echo $msg['msgs'][$lang]['enterEmail'] ?>"); //"Enter your email". Better would be: Please enter an email and password.
							allGood = false;
						}
						
						if(allGood == true) {
							$("#comment-messages").html("<img src=\"img/ajax-loader.gif\" width=\"16\" height=\"16\">");
							
						}
						$("#comment-messages").show();
						
						if(allGood == true) {
							var returned = set_options_cookie();
							
							//$("#sign-and-pair-button").hide();
							$("#pair-again-button").fadeIn();
							
							return returned;
						} else {
							return false;
						}
						
					});
				});
		</script>

		
		<?php if($screen_type == "signup") { ?>
     	<div class="container-fluid infront">
			<div class="row justify-content-center">
				<div class="col-md-12">
				
				<div class="">
					<span class="signuptitle-section" style="text-align:left; float: left; width: 50%;">
						<span class="signuptitle"><?php echo $notifications_config['msgs'][$lang]['signUp']; ?></span></br>
						<span><?php echo $notifications_config['msgs'][$lang]['orSignIn']; ?></span>
					</span>
					<span class="signuptitle-section" style="text-align:right; float: right; width: 50%;">
						
							<a href="<?php echo $follow_on_link; ?>"><img src="img/logo80.png" width="70" height="70"></a>

					</span>
				</div>
				<div style="clear: both;"></div>
				
				
				<!-- Signup Form -->
				<form id="options-frm" class="form" role="form" action="" onsubmit=""  method="POST">
				 				 <input type="hidden" name="passcode" id="passcode-options-hidden" value="<?php echo $_REQUEST['uniqueFeedbackId'] ?>">
				 				 <input type="hidden" name="general" id="general-options-hidden" value="<?php echo $_REQUEST['general'] ?>">
				 				 <input type="hidden" name="id" id="pair-id" value="<?php echo $_REQUEST['id'] ?>">
				 				 <input type="hidden" name="devicetype" id="device-type" value="<?php echo $_REQUEST['devicetype'] ?>">
				 				 <input type="hidden" name="date-owner-start" value="<?php echo $date_start ?>">
				 				 <input type="hidden" id="email-modified" name="email_modified" value="false">
				 				 <?php $sh->call_plugins_settings(null); //User added plugins here ?>								
				 				
				 				 <a id="change-lang-button"><img style="margin-top: 10px; margin-bottom: 14px;" src='img/refresh.png' width='60' height='60'></a> <img src="img/flags.png" width="48" height="14"><br/>
								 <div class="form-group">
		 									<div><?php echo $msg['msgs'][$lang]['yourEmail'] ?></div>
						  					<input oninput="if(this.value.length > 0) { $('#email-modified').val('true'); $('#save-button').html('<?php if($msg['msgs'][$lang]['subscribeSettingsButton']) {
		 echo $msg['msgs'][$lang]['subscribeSettingsButton']; 
		} else { 
			echo $msg['msgs'][$lang]['saveSettingsButton'];
		} ?>'); } else { $('#email-modified').val('false'); $('#save-button').html('<?php echo $msg['msgs'][$lang]['saveSettingsButton'] ?>'); }" id="email-opt" name="email-opt" type="email" class="form-control" placeholder="<?php echo $msg['msgs'][$lang]['enterEmail'] ?>" autocomplete="false" value="<?php echo $display_email; ?>">
								</div>
								<!--<div><a id="comment-show-password" href="javascript:"><?php echo $msg['msgs'][$lang]['more'] ?></a></div>-->
								<div id="comment-password-vis" style="">
									<div  class="form-group">
										<div><?php echo $msg['msgs'][$lang]['yourPassword'] ?> <a id='clear-password' href="javascript:" onclick="return clearPass();"><?php echo $msg['msgs'][$lang]['resetPasswordLink'] ?></a> <span id="password-explain" style="display: none; color: #f88374;"><?php echo $msg['msgs'][$lang]['yourPasswordReason'] ?> </span></div>
						  				<input oninput="if(this.value.length > 0) { $('#save-button').html('<?php if($msg['msgs'][$lang]['loginSettingsButton']) {
		 echo $msg['msgs'][$lang]['loginSettingsButton']; 
		} else { 
			echo $msg['msgs'][$lang]['saveSettingsButton'];
		} ?>'); } else { $('#save-button').html('<?php echo $msg['msgs'][$lang]['saveSettingsButton'] ?>'); }" id="password-opt" name="pd" type="password" class="form-control" autocomplete="false" placeholder="<?php echo $msg['msgs'][$lang]['enterPassword'] ?>" value="<?php if(isset($_REQUEST['pd'])) { echo $_REQUEST['pd']; } ?>">
									</div>
									<div  class="form-group">
										 <input  id="phone-opt" name="ph" type="hidden" placeholder="<?php echo $msg['msgs'][$lang]['enterMobile'] ?>" value="<?php if(isset($_COOKIE['phone'])) { echo urldecode($_COOKIE['phone']); } else { echo ''; } ?>">
									</div>
									<div id="user-id-show" class="form-group" style="display:none;">
										<div style="color: red;" id="user-id-show-set"></div>
						  			</div>
									
								</div>
								<div class="form-group">
				 						<div><?php echo $msg['msgs'][$lang]['yourName'] ?> (<?php echo $msg['msgs'][$lang]['optional'] ?>)</div>
							 			<input id="your-name-opt" name="your-name-opt" type="text" class="form-control" placeholder="<?php echo $msg['msgs'][$lang]['enterYourName'] ?>" autocomplete="false" value="<?php if(isset($_COOKIE['your_name'])) { echo urldecode($_COOKIE['your_name']); } else { echo ''; } ?>" >
								</div>
								<div class="form-group">
		 									<div style="display: none; color: red;" id="comment-messages"></div>
								</div>
								<br/>
							 <button id="sign-and-pair-button" type="submit" class="btn btn-primary" style="margin-bottom:3px;"><?php echo $notifications_config['msgs'][$lang]['signUp']; ?></button><br/><small><a href="<?php echo $notifications_config['privacyPolicyLink']; ?>" target="_blank"><?php echo $notifications_config['msgs'][$lang]['privacyPolicy']; ?></a></small><br/><br/>
							 <button style="display: none;" id="pair-again-button"  class="btn btn-primary btn-lg" style="margin-bottom:3px;"><?php echo $notifications_config['msgs'][$lang]['completePairing']; ?></button>
							<br/>
							<br/>
							
							 
							 
				 </form>
				
				
				</div> 		
			</div>
		</div>
   		
   		
   		<?php } else { 	
   			//logo-wrapper
   	    ?>
		<div>			
		    <div id="logo-wrapper" class="looplogo comment-open" <?php echo $speech_bubble_link ?>>
					<img class="saver-hideable" src="img/speech-bubble-start-1.png" id="bg" alt="" border="0">
			</div>
		</div>
   		

   		
   		<div class="container-fluid infront">
			<div class="row justify-content-center">
				<div class="col-md-2">
				</div>
				 <div class="col-md-8">
						<h3 align="<?php echo $center ?>" style="color: #aaa;"><?php echo $main_message ?></h3>
				
						<div class="form-row text-center">
    						<div class="col-12">
				
								<a class="btn btn-primary" style="margin: 6px;" href='<?php echo $first_button ?>'><?php echo $first_button_wording ?></a>

								<?php if($second_button) { ?>
									<a class="btn btn-primary" style="margin: 6px;" href='<?php echo $second_button ?>'><?php echo $second_button_wording ?></a>
								<?php } ?>
							</div>
						</div>

			 
				 </div>
				<div class="col-md-2">
				</div>
			</div>
		</div>
		
    	<div class="container-fluid darkoverlay" id="mydarkoverlay">
            <div class="row">
                <div class="col-md-2">
                </div>
                 <div class="col-md-8">
                 </div>
                <div class="col-md-2">
                	<p align="right"><a href="https://atomjump.com/smart.php">Learn More</a></p>
					<p align="right" style="color: #aaa;"><small>&copy; <?php echo date('Y'); ?> <?php echo $msg['msgs'][$lang]['copyright'] ?></small></p>
                </div>
            </div>
        </div>
		<br/><br/><br/><br/>

		<!-- Needed? Seems to be straggling: </div>-->
		<?php } ?>


		<div id="comment-holder"></div><!-- holds the popup comments. Can be anywhere between the <body> tags -->


	</body>

</html>
