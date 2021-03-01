<?php

	//Checks if this server has pull notifications
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
	
	
	if(isset($_REQUEST['all'])) {
		/* Return JSON file e.g. { 
									"supports": 
										{ 
											"atomjump": true,
											"ios": false,
											"android": true
										}
								}
		*/
		
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
		
		
		$arr = array(
			"supports" => array(
				"atomjump" => $use_atomjump,
				"ios" => $use_ios,
				"android" => $use_android		
			)		
		)
		
		
		
	
	} else {
	
		//Basic, almost legacy case: return simple true/false on supporting pull
		$arr = array();
		if(($notifications_config['atomjumpNotifications']) && (isset($notifications_config['atomjumpNotifications']['use']))) {
			$arr['response'] = "true";
		} else {
			$arr['response'] = "false";
		}
	}



	echo $_GET['callback']."(".json_encode($arr).");";

?>