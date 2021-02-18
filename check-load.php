<?php

	/*
	
		TODO: this will be a cron script run once a day or so.
		
		It will check through the pool of MedImage servers in config/config.json atomjumpNotifications.serverPool
		and for each one get the load using the URL   medimageserver.url/load/
		
		This returns a 1 minute, 5 minute, 15 minute load average array as text. e.g [0.60009765625,0.2529296875,0.123046875]
		
		With this load, we will export a current register as .json, so that registrations in genid.php can use the file to choose
		which servers have the least load, and should become that app's stored URL for future checks.
		
		Export format in config/loadEXAMPLE.json
		
	
	*/


?>