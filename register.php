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
	$notification_id = $_REQUEST['id'];
	$api->db_update("tbl_user", "var_notification_id = '" . $notification_id . "'  WHERE int_user_id = " . $_SESSION['logged-user']);

?>