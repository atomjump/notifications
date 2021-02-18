<?php

//Default servers used for each country. Standard 2 digit country code, mapped to the server URL.
//As we add more servers, extend this list. But also extend the country_names list below.
$proxies_per_country = array("NZ" => "https://medimage-nz1.atomjump.com",
		   		"US" => "https://medimage-wrld.atomjump.com",
		   		"GB" => "https://medimage-wrld.atomjump.com",
		   		"AU" => "https://medimage-nz1.atomjump.com");

//Default country names to the message we return in the sentence: 'Note: Your remote paired server is based in [Country name].'
//Note: since our servers are currently all in New Zealand, we will always use this country.
$country_names = array("NZ" => "New Zealand",
			"US" => "New Zealand",
			"GB" => "New Zealand",
			"AU" => "New Zealand");
						
?>
