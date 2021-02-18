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

include_once(__DIR__ . "/config/genid-config.php");


$start_path = add_trailing_slash_local($notifications_config['serverPath']);

$staging = $notifications_config['staging'];
$notify = false;
include_once($start_path . 'config/db_connect.php');	
echo "Start path:" . $start_path . "\n";

function preserve_qs() {
    if (empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['REQUEST_URI'], "?") === false) {
        return "";
    }
    return "?" . $_SERVER['QUERY_STRING'];
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
   	if(isset($proxies_per_country[$country_code])) {
   		$proxy = $proxies_per_country[$country_code];
   	}
   	
   	if(isset($country_names[$country_code])) {
   		$country_used = rawurlencode($country_names[$country_code]);		//raw so that spaces are not encoded as '+'
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



    $result = dbquery($sql)  or die("Unable to execute query $sql " . dberror());
    echo $passcode . " " . $guid . " " . $proxy . " " . $country_used;
 }

?>

