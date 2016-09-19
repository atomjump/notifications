<?php

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
 
	$start_path = $notifications_config['serverPath'];
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
	
	$sql = "var_notification_id = " . $notification_id . ", var_device_type = '" . $device_type . "' WHERE int_user_id = " . $_REQUEST['userid'];
	$api->db_update("tbl_user", $sql);

	echo "Updated";
?>