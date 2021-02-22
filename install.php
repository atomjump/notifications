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
	
	$sql = "CREATE TABLE `tbl_notification_pairing` ( `int_pairing_id` int(11) NOT NULL AUTO_INCREMENT,  `var_guid` varchar(255) DEFAULT NULL, `var_passcode` varchar(10) DEFAULT NULL, `dt_set` datetime DEFAULT NULL, `dt_expires` datetime DEFAULT NULL, `var_proxy` varchar(1024) DEFAULT NULL, PRIMARY KEY (`int_pairing_id`), KEY `pass` (`var_passcode`), 	  KEY `expires` (`dt_expires`)) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=latin1;";
	echo "Updating user table. SQL:" . $sql . "\n";
	$result = $api->db_select($sql);
	
	
	
	//Create an 'outgoing' atomjump messages folder which is broadly accessible
	$parent_messages_folder = __DIR__ . "/outgoing/";
	if(!file_exists($parent_messages_folder)) {
		if(!mkdir($parent_messages_folder)) {
			$msg = "Sorry, you could not create the outgoing messages folder " . $parent_messages_folder . ". You may need to: mkdir outgoing; chmod 777 outgoing; chown www-data:www-data outgoing  [or replace 'www-data' with your own Apache user]";
			error_log($msg);
			echo $msg;
			exit(0);
		} else {
			exec("chmod 777 outgoing");
		}
	}
	
		
	echo "Completed.\n";
	

?>