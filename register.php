<?php
	function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
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
	require($start_path . "classes/cls.pluginapi.php");
	
	$api = new cls_plugin_api();
	
	//Set the notification id for this user/phone
	if($_REQUEST['id'] == "") {
		$notification_id = "NULL";
	} else {
		$notification_id = "'" . $_REQUEST['id'] . "'";
	}
	
	if(isset($_REQUEST['devicetype'])) {
		$device_type = clean_data($_REQUEST['devicetype']);
	} else {
		$device_type = "Android";			//Default to Android if unknown
	}
	
	if(isset($_REQUEST['userid']) && ($_REQUEST['userid'] != "")) {
		$user_id = $_REQUEST['userid'];
	} else {
		//Get from the session variable
		if(isset($_SESSION['logged-user']) && ($_SESSION['logged-user'] != "")) {
			$user_id = $_SESSION['logged-user'];
		} 
	}

	global $msg;
	global $cnf;
	global $lang;
	
	$follow_on_link = "https://atomjump.com";
	if($cnf['serviceHome']) {
		$follow_on_link = $cnf['serviceHome'];
	}
	
	
	
	if($user_id == "") {
		echo "Sorry, please login to your messaging forum and try again.  <a href='" . $follow_on_link . "'>Back to Home</a>";
		exit(0);
	}
	
	$unregister_link = "register.php?userid=" . $user_id . "&id=&devicetype=";

	
	$sql = "var_notification_id = " . $notification_id . ", var_device_type = '" . $device_type . "' WHERE int_user_id = " . $user_id;
	$api->db_update("tbl_user", $sql);


	if($_REQUEST['id'] == "") {
			echo "Your app is now deregistered to this server. You will only receive email notifications.  <a href='" . $follow_on_link . "'>Back to Home</a>"
	} else {
			echo "Your app is now registered to this server. <a href='" . $unregister_link . "'>Deregister (go back to email notifications)</a> <a href='" . $follow_on_link . "'>Back to Home</a>";
	}

?>