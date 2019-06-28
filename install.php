<?php
	function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
    }
    
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
	echo "Start path:" . $start_path . "\n";

	
	$define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	
	echo "Classes path:" . $define_classes_path . "\n";
	
	require($start_path . "classes/cls.pluginapi.php");
	
	$api = new cls_plugin_api();
	
	//Insert a column into the user table - one registration id (likely android gcm or iphone)
	$sql = "ALTER TABLE tbl_user ADD COLUMN `var_device_type` varchar(50) DEFAULT NULL";
	echo "Updating user table. SQL:" . $sql . "\n";
	$result = $api->db_select($sql);

	$sql = "ALTER TABLE tbl_user ADD COLUMN `var_notification_id` varchar(255) DEFAULT NULL";
	echo "Updating user table. SQL:" . $sql . "\n";
	$result = $api->db_select($sql);
		
	echo "Completed.\n";
	

?>