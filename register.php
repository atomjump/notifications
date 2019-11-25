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
	
	//User's email for display purposes only.
	if(isset($_SESSION['logged-email']) && ($_SESSION['logged-email'] != "")) {
			$user_email = $_SESSION['logged-email'];
	} 
	
	$follow_on_link = "https://atomjump.com";
	if($cnf['serviceHome']) {
		$follow_on_link = $cnf['serviceHome'];
	}
	
	
	
	if($user_id == "") {
		//A blank user id
		//echo $notifications_config['msgs'][$lang]['notLoggedIn'] . " <a href='" . $follow_on_link . "'>" . $notifications_config['msgs'][$lang]['backHome'] ."</a>";
		$main_message = $notifications_config['msgs'][$lang]['notLoggedIn'];
		$first_button = $follow_on_link;
		$first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
		$second_button = "";
		$second_button_wording = "";
	} else {
	
		$unregister_link = "register.php?userid=" . $user_id . "&id=&devicetype=";

	
		$sql = "var_notification_id = " . $notification_id . ", var_device_type = '" . $device_type . "' WHERE int_user_id = " . $user_id;
		$api->db_update("tbl_user", $sql);


		if($_REQUEST['id'] == "") {
			 //App has been deregistered
			 //echo $notifications_config['msgs'][$lang]['appDeregistered'] . " <a href='" . $follow_on_link . "'>" . $notifications_config['msgs'][$lang]['backHome'] ."</a>";
			 $main_message = $notifications_config['msgs'][$lang]['appDeregistered'];
			 $first_button = $follow_on_link;
			 $first_button_wording = $notifications_config['msgs'][$lang]['backHome'];
			 $second_button = "";
			 $second_button_wording = "";	
		} else {
			 //App is registered
			 //echo $notifications_config['msgs'][$lang]['appRegistered'] . $user_email .". <a href='" . $unregister_link . "'>" . $notifications_config['msgs'][$lang]['deregister'] ."</a> <a href='" . $follow_on_link . "'>" . $notifications_config['msgs'][$lang]['backHome'] ."</a>";
			 $main_message = $notifications_config['msgs'][$lang]['appRegistered'] . $user_email;
			 $first_button = $unregister_link;
			 $first_button_wording = $notifications_config['msgs'][$lang]['deregister'];
			 $second_button = $follow_on_link;
			 $second_button_wording = $notifications_config['msgs'][$lang]['backHome'];
		}
	}

?>
<!DOCTYPE html>
<html lang="en" id="fullscreen">
  <head>
  	    <meta charset="utf-8">
		 <meta name="viewport" content="width=device-width, user-scalable=no">
		 <title>AtomJump Messaging Server - provided by AtomJump</title>

		 <meta name="description" content="<?php echo $msg['msgs'][$lang]['description'] ?>">

		 <meta name="keywords" content="<?php echo $msg['msgs'][$lang]['keywords'] ?>">

			  <!-- Bootstrap core CSS -->
			<link rel="StyleSheet" href="https://atomjump.com/css/bootstrap.min.css" rel="stylesheet">

			<!-- AtomJump Feedback CSS -->
			<link rel="StyleSheet" href="https://atomjump.com/css/comments-0.1.css">

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
			<script type="text/javascript" src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
			<!-- Took from here 15 May 2014: http://ajax.googleapis.com/ajax/libs/jquery/1.9.1 -->

			<!-- For the dropdown autocomplete -->
			<link rel="stylesheet" href="//code.jquery.com/ui/1.11.2/themes/smoothness/jquery-ui.css">
			<script src="//code.jquery.com/ui/1.11.2/jquery-ui.js"></script>

			<style>
				h2 {
					text-align: center;
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
        			position: absolute;
        			top: 800px;
        			width: 100%;
        			background-color: black;
        			opacity: 0.9;
    				filter: alpha(opacity=90); /* For IE8 and earlier */
        		}


			   .share {
					position: fixed;
					top: 10px;
					float: left;
					margin-left: 20px;
					z-index: 20;
				}

				.cpy {
						position: fixed;
						right: 10px;
						bottom: 10px;
						float: right;
						margin-right: 20px;
				}

			
				.cpy a:link, a:visited {
					color: #888;
				}



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

				 	<?php if($include_image) { ?>

						.wrapper{
							background: transparent !important;

						}


						html {
							background-color: transparent !important;
							background-image: url('<?php echo $image ?>');

							background-position: center center !important;
							background-repeat: no-repeat;
							 background-attachment: scroll; /* Don't have a fixe background image */
							-webkit-background-size: cover;
							-moz-background-size: cover;
							-o-background-size: cover;
							background-size: cover !important;
							height: 100%;
							min-height:100%;

						}
					<?php } ?>
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
						position: fixed;
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


				$(document).ready(function(){

				});
		</script>

		

		

		<div>
		    <div id="logo-wrapper" class="looplogo">
				<img class="saver-hideable" src="https://atomjump.com/wp/wp-content/uploads/2018/12/speech-bubble-start-1.png" id="bg" alt="">
				<br/>
				<h3 align="center" style="color: #aaa;"><?php echo $main_message ?></h3>
                     	
				<a class="button" href='<?php echo $first_button ?>'><?php echo $first_button_wording ?></a>

				<?php if($second_button) { ?>
					<a class="button" href='<?php echo $second_button ?>'><?php echo $second_button_wording ?></a>
				<?php } ?>
                    		

			</div>
		</div>

		
    	<div class="container-fluid darkoverlay" id="mydarkoverlay">
            <div class="row">
                <div class="col-md-2">
                </div>
                 <div class="col-md-8">
                    

                    
                 </div>
                <div class="col-md-2">
                </div>
            </div>
		<br/><br/><br/><br/>

		</div>


			<div class="cpy">
				<p align="right"><a href="https://atomjump.com/smart.php">Learn More</a></p>
				<p align="right" style="color: #aaa;"><b>Local Server Install</b></p>
				<p align="right" style="color: #aaa;"><small>&copy; <?php echo date('Y'); ?> <?php echo $msg['msgs'][$lang]['copyright'] ?></small></p>
			</div>

		<div id="comment-holder"></div><!-- holds the popup comments. Can be anywhere between the <body> tags -->


	</body>

</html>