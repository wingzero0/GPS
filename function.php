<?php

require("/var/www/fb_lib/facebook.php");
//include(dirname(__FILE__)."/connection.php");

// Create our Application instance (replace this with your appId and secret).
function fb_init(){
	require(dirname(__FILE__)."/fbmain.php");
	$facebook = new Facebook(array(
		'appId'  => $appId,
		'secret' => $secret,
		'cookie' => true,
	));
	return $facebook;
}

function getLogInOutUrl($facebook){
	// Get User ID
	$user = $facebook->getUser();

	// We may or may not have this data based on whether the user is logged in.
	//
	// If we have a $user id here, it means we know the user is logged into
	// Facebook, but we don't know if the access token is valid. An access
	// token is invalid if the user logged out of Facebook.

	// Login or logout url will be needed depending on current user state.
	if ($user) {
		$url = $facebook->getLogoutUrl();
	} else {
		$url = $facebook->getLoginUrl(
			//array('scope' => 'publish_stream')
			array('scope' => 'publish_checkins')
		);
	}
	return $url;
}

function get_places($facebook, $latitude, $longitude,$max_place = 10,$distance = 1000){
	try{
		echo "/search?type=place&center=$latitude,$longitude&distance=$distance&limit=$max_place\n";
		$place = $facebook->api("/search?type=place&center=$latitude,$longitude&distance=$distance&limit=$max_place");
		return $place;
	} catch (FacebookApiException $e) {
		echo $e->getMessage();
		error_log($e);
		return null;
	}	
}

function get_place_page($facebook, $page_id){
	try{
		$page = $facebook->api("/$page_id");
		return $page;
	} catch (FacebookApiException $e) {
		echo $e->getMessage();
		error_log($e);
		return null;
	}	
}

function get_checkin($facebook, $user){
	try{
		$attachment = array(	
		);
		$ret = $facebook->api("/me/checkins", "GET", $attachment);
		print_r($ret);
	} catch (FacebookApiException $e) {
		echo $e->getMessage();
		error_log($e);
		$user = null;
	}	
}

function post_checkin($facebook, $user){
	try{
		$place = $facebook->api("/search?type=place&center=25.019895,121.541448&distance=1000");
		$lat = 25.019895;
		$long = 121.541448;

		echo "<pre>";
		print_r($place);
		echo "</pre>";

		$attachment = array(
			"message" => "my app checkin test",
			'coordinates' => '{"latitude":"'.$lat.'", "longitude": "'.$long.'"}', 
			"place" => $place['data'][0]['id']
		);
		$ret = $facebook->api("/me/checkins", "post", $attachment);

		//$ret = $facebook->api("/CHECKIN_ID");
		/*$attachment = array(
			"message" => time(),
		);*/
		//$ret = $facebook->api("/me/feed/", "post", $attachment);
		echo "<pre>";
		print_r($ret);
		echo "</pre>";
	} catch (FacebookApiException $e) {
		echo $e->getMessage();
		error_log($e);
		$user = null;
	}	
}

function load_start_position(){
	$fp = fopen("start_position.txt","r");
	if ($fp == NULL){
		echo "error from opening start_position.txt\n";
		return NULL;
	}
	fscanf($fp,"%lf %lf %lf %lf %lf %lf", 
		$pos["latitude"], 
		$pos["longitude"], 
		$pos["lat_max"], 
		$pos["lat_min"], 
		$pos["long_max"], 
		$pos["long_min"]
	);
	fclose($fp);
	return $pos;
}
function save_positions($page_cache, $pos){
	include(dirname(__FILE__)."/connection.php");
	mysql_select_db($database_cnn,$b95119_cnn);
	$fpe = fopen("mysql_error.txt", "a");
	if ($fpe == NULL){
		echo "mysql_error can't be open\n";
	}
	foreach ($page_cache as $id => $v){
		$query = sprintf("insert into `GPS` (`page_id`,`name`, `latitude`, `longitude`, 
			`likes`, `checkins`) values ('%s','%s',%lf,%lf,%d,%d)", 
			$id, preg_replace("/\'/", "\\\'", $v["name"]), $v["latitude"], $v["longitude"], $v["likes"], $v["checkins"]);
		mysql_query($query);
		if (mysql_error()){
			if ($fpe !=NULL){
				fprintf($fpe, "query = %s\nerror msg = %s\n", $query, mysql_error());
			}else{
				printf("query = %s\nerror msg = %s\n", $query, mysql_error());
			}
		}
	}
	fclose($fpe);
	$fp = fopen("start_position.txt","w");
	if ($fp == NULL){
		echo "error from writing start_position.txt\n";
		return false;
	}
	fprintf($fp,"%lf %lf %lf %lf %lf %lf", 
		$pos["latitude"], 
		$pos["longitude"], 
		$pos["lat_max"], 
		$pos["lat_min"], 
		$pos["long_max"], 
		$pos["long_min"]
	);
	fclose($fp);
	return true;
}
function next_position($pos){
	if ($pos["longitude"] + 0.003 > $pos["long_max"]){
		$pos["longitude"] = $pos["long_min"]; // reset to the left most;
		$pos["latitude"] += 0.003;
	}else if ($pos["longitude"] < $pos["long_min"]){
		echo "error in positin GPS\n";
		return false;
	}else{
		$pos["longitude"] += 0.003;
	}
	if ($pos["latitude"] > $pos["lat_max"]){
		return false;
	}else if ($pos["latitude"] < $pos["lat_min"]){
		echo "error in positin GPS\n";
		return false;
	}
	return $pos;
	//return true;
}

?>
