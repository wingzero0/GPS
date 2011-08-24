<?php
/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require(dirname(__FILE__)."/function.php");

$facebook = fb_init();

// Get User ID
$user = $facebook->getUser();

$url = getLogInOutUrl($facebook);

?>
<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
	<head>
		<title>find GPS</title>
		<style>
			body {
				font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
			}
			h1 a {
				text-decoration: none;
				color: #3b5998;
			}
			h1 a:hover {
				text-decoration: underline;
			}
		</style>
	</head>
	<body>
		<h1>php-sdk</h1>


		<?php if ($user): ?>
			<a href="<?php echo $url; ?>">Logout</a>
			<h3>You</h3>
			<img src="https://graph.facebook.com/<?php echo $user; ?>/picture">
		<?php else: ?>
			<a href="<?php echo $url; ?>">Login</a>
			<strong><em>You are not Connected.</em></strong>
		<?php endif ?>
<pre>
<?php
echo date("H:i:s\n");
$pos = load_start_position();
$counter = 0;
do{
	$ret = get_places($facebook, $pos["latitude"],$pos["longitude"], 100, 500);
	//print_r($ret);
	echo "i = ".$pos["latitude"]." j = ".$pos["longitude"]." count=".count($ret["data"])."\n";
	if ($ret != NULL) {
		foreach($ret["data"] as $index => $v){
			if ( !isset($page_cache[$v["id"]]) ){
				$page = get_place_page($facebook, $v["id"]);
				//print_r($page);
				//echo $page["name"]."\tlikes=".$page["likes"]."\tcheckins".$page["checkins"]."\n";
				$page_cache[$v["id"]]["name"]= $page["name"];
				$page_cache[$v["id"]]["likes"]= $page["name"];
				$page_cache[$v["id"]]["checkins"]= $page["checkins"];
				$page_cache[$v["id"]]["latitude"]= $page["location"]["latitude"];
				$page_cache[$v["id"]]["longitude"]= $page["location"]["longitude"];
			}
		}
	}
	$counter++;
	$pos = next_position($pos);
}while ($pos && $counter < 10);
echo date("H:i:s\n");
//print_r($page_cache);
save_positions($page_cache, $pos);
?>
</pre>
	</body>
</html>
