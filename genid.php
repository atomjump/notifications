<?php

//Generate a 4 digit code and unique uuid for an AtomJump notification server. Time decayed.
//Or get a uuid for a known 4 digit code
	
//$staging = true;		//Use when testing on staging only.

/*
Note: you must have this table in the database:

DROP TABLE IF EXISTS `tbl_notification_pairing`;
CREATE TABLE `tbl_notification_pairing` (
  `int_pairing_id` int(11) NOT NULL AUTO_INCREMENT,
  `var_guid` varchar(255) DEFAULT NULL,
  `var_passcode` varchar(10) DEFAULT NULL,
  `dt_set` datetime DEFAULT NULL,
  `dt_expires` datetime DEFAULT NULL,
  `var_proxy` varchar(1024) DEFAULT NULL,
  PRIMARY KEY (`int_pairing_id`),
  KEY `pass` (`var_passcode`),
  KEY `expires` (`dt_expires`)
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=latin1;
*/

/**
 *  An example CORS-compliant method.  It will allow any GET, POST, or OPTIONS requests from any
 *  origin.
 *
 *  In a production environment, you probably want to be more restrictive, but this gives you
 *  the general idea of what is involved.  For the nitty-gritty low-down, read:
 *
 *  - https://developer.mozilla.org/en/HTTP_access_control
 *  - https://fetch.spec.whatwg.org/#http-cors-protocol
 *
 */
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

function preserve_qs() {
    if (empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['REQUEST_URI'], "?") === false) {
        return "";
    }
    return "?" . $_SERVER['QUERY_STRING'];
}

function get_least_load($server_pool, $country_code) {
	//Input 1: an array of country codes, with an array of servers on each one
	//      2: a country code
	//
	//Check if there is an output file from check-load.php 'outgoings/load.json', and if so
	//    use the least loaded server
	//If not load checks have been done, select from the array list for this country at random
	
	//Returns the preferred URL of the proxy MedImage server
	
	$load_file = __DIR__ . "/outgoings/load.json";
	if(file_exists($load_file)) {
		$load_file_str = file_get_contents($load_file);
		$load = json_decode($load_file_str);
		if($load) {
			if((isset($load['atomjumpNotifications']['serverPool'][$country_code])) &&
				(isset($load['atomjumpNotifications']['serverPool'][$country_code][0]['load'])) ) {
				return $load['atomjumpNotifications']['serverPool'][$country_code][0]['load'];			
			} else {
				error_log("Warning: Sorry, the country code " . $country_code . " file is not correctly in the load file, using a random selection instead.");
			}
		} else {
			error_log("Warning: Sorry, the " . $load_file . " file is not correct JSON, using a random selection instead.");
		}
	} 
	
	//Falling through, use a random selection from the config file
	$random_top = count($server_pool);
	
	$selected = rand(1, $random_top);
	return $server_pool[$selected-1];

}




 $decay = 2; //in hours
 
 if(isset($_GET['proxyServer'])) {
   $proxy = $_GET['proxyServer'];		//the urldecoding potentially differs from the REQUEST version.
   $country_used = rawurlencode("[Unknown - private]");
 
 
 } elseif(isset($_REQUEST['proxyServer'])) {
   $proxy = $_REQUEST['proxyServer'];
   $country_used = rawurlencode("[Unknown - private]");
 } else {
   //Use aj default proxy, based on country
   
   //Defaults
   $proxy = "https://medimage-wrld.atomjump.com";
   $country_used = rawurlencode("New Zealand");
   
   if(isset($_REQUEST['country'])) {
   	$country_code = $_REQUEST['country'];
   	
   	if(isset($notifications_config['atomjumpNotifications']) && isset($notifications_config['atomjumpNotifications']['serverPool'])) {
   		if(isset($notifications_config['atomjumpNotifications']['serverPool'][$country_code])) {
   			//Select the 1st option in the country. TODO - choose the least load option
   			//OLD:$proxy = $notifications_config['atomjumpNotifications']['serverPool'][$country_code][0];
   			$proxy = get_least_load($notifications_config['atomjumpNotifications']['serverPool'][$country_code], $country_code);
   		} else {
   			if(isset($notifications_config['atomjumpNotifications']['serverPool']['Default'])) {
   				//OLD:$proxy = $notifications_config['atomjumpNotifications']['serverPool']['Default'][0];
   				$proxy = get_least_load($notifications_config['atomjumpNotifications']['serverPool']['Default'], 'Default');
   			} else {
   				echo "noproxy";
   			}
   		}
   	}
   	
   	if(isset($notifications_config['atomjumpNotifications']) && isset($notifications_config['atomjumpNotifications']['countryServerResidingIn'])) {
   		
   		if(isset($notifications_config['atomjumpNotifications']['countryServerResidingIn'][$country_code])) {
   			$country_used = rawurlencode($notifications_config['atomjumpNotifications']['countryServerResidingIn'][$country_code]);		//raw so that spaces are not encoded as '+'
   		} else {
   			if(isset($notifications_config['atomjumpNotifications']['countryServerResidingIn']['Default'])) {
   				$country_used = rawurlencode($notifications_config['atomjumpNotifications']['countryServerResidingIn']['Default']);
   			} else {
   				$country_used = rawurlencode("[Unknown - private]");
   			}
   		}
   	}
   		
   }
   
 }

 if(isset($_REQUEST['compare'])) {
 
     $sql = "SELECT * FROM tbl_notification_pairing WHERE var_passcode = '" . clean_data($_REQUEST['compare']) . "'   AND dt_expires > NOW()";
     	$result = dbquery($sql)  or die("Unable to execute query $sql " . dberror());
     if($row = db_fetch_array($result))
	    {
	      	   $guid = $row['var_guid'];
	      	   $proxy = $row['var_proxy'];
	      	   echo $proxy . '/write/' . $guid; 	   
	    } else {
	         echo 'nomatch';
	    }

 
 } else {
    //Add a new code
    
    $letters = "23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ";
    $letters_max = strlen($letters) - 1 ;
    
    
    //Loop until find a random unique 4 digit code
    // still active
    //Check code doesn't already exist
     
    do {  
    
       $passcode = "";
       
       for($cnt = 0; $cnt< 4; $cnt++) {
         $passcode .= $letters[rand(0, $letters_max)];
       }
       //Old way: $passcode = rand(1000, 9999);
       $found = null;
 
       $sql = "SELECT * FROM tbl_notification_pairing WHERE var_passcode = '" . $passcode . "'
                 AND dt_expires > NOW()";
      		$result = dbquery($sql)  or die("Unable to execute query $sql " . dberror());
		      if($row = db_fetch_array($result))
	      	{
	      	   $found = $row['int_pairing_id'];
	      	   
	      	}
    
    } while($found != null); //keep looping until found a unique option
    
    
    //Generate a guid
    if($_REQUEST['guid']) {
    	//If we already have a GUID on the server, just generate a new passcode, and leave the same GUID
    	$guid = $_REQUEST['guid'];
    	
    	//Update the old record
    	$sql = "UPDATE tbl_notification_pairing SET var_passcode = '" . clean_data($passcode) . "',
    						dt_set = NOW(), 
    						dt_expires = DATE_ADD(NOW(), INTERVAL " . $decay . " HOUR),
    						var_proxy = '" . clean_data($proxy) . "' 
    						WHERE var_guid = '" . clean_data($guid) . "'"; 
    	
    } else {
 	//Generate a new GUID
    	$guid = substr(str_shuffle(str_repeat('23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ', mt_rand(1,20))),1,20);	//Note: 20 is slightly longer than 18 letters in MedImage pairing. Therefore will always be differentiated.
    	
    	//Insert the code and guid
    	$sql = "INSERT INTO tbl_notification_pairing (
                                         var_guid,
                                         var_passcode,
                                         dt_set,
                                         dt_expires,
                                         var_proxy) VALUES ( '" . clean_data($guid) . "', 
                                                                '" . clean_data($passcode) . "',
                                                                NOW(),
                                                                DATE_ADD(NOW(), INTERVAL " . $decay . " HOUR),
                                                                '" . clean_data($proxy) . "');";

    }


 	cors();
    $result = dbquery($sql)  or die("Unable to execute query $sql " . dberror());
    echo $passcode . " " . $guid . " " . $proxy . " " . $country_used;
 }

?>

