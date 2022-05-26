<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-F3w7mX95PdgyTmZZMECAngseQB83DfGTowi0iMjiWaeVhAn4FJkqJByhZMI3AhiU" crossorigin="anonymous">
<title>Crawler</title>
<div style='padding: 20px;'>
	<form  action='index.php' spellcheck="false">
		<p><b><a href='<?php echo $_SERVER['PHP_SELF']; ?>' style=' text-decoration: none; color: black;'>Enter urls:</a></b></p>
		<p><textarea rows="5" cols="45" name="urls"><?php echo @$_GET['urls']; ?></textarea></p>
		<p>
			<label for="links_limit">Maximum links to parse:</label>
			<select id="links_limit" name="links_limit">
				<option value="10">10</option>
				<option value="100">100</option>
				<option value="1000">1000</option>
				<option value="100000">100000</option>
			</select>
		</p>
		<p><input type="submit" value="Start"></p>
	</form>
 <?php
 
if ( !isset($_GET['urls']) ) die;

$links_limit = $_GET['links_limit'];

set_time_limit(0);
include_once('simple_html_dom.php');
ini_set("memory_limit","1024M");

$base_url = '';

$urls_file_path = 'urls_list.txt';
$errors_file_path = 'errors_list.txt';


if ( ! is_writable(dirname($urls_file_path))) {
	echo '<h3 style="color: red">Directory '.realpath( dirname($urls_file_path) ). ' must be writable!<br>
	"chmod o+w '.realpath( dirname($urls_file_path) ). '" in cmd should help!
	<h3>';
}

$recursive_current = 0;

$urls_global_errors = array();

$urls = trim($_GET['urls']);
$urls = preg_split("/\r\n|\n|\r/", $urls); //split by new line

$urls_global = array();
//$urls_global = file_get_contents($urls_file_path);
//$urls_global = preg_split("/\r\n|\n|\r/", $urls_global);

$i = 0;

foreach( $urls as $url ) {
	$i = $i +1;
	echo	'<h1>'.$i.'. '.$url.'</h1><br>';

	$base_url = $url;
	check_link($url);
}

$urls_to_store = implode("\n", $urls_global);
file_put_contents($urls_file_path, $urls_to_store);

echo '<br><br>Data stored in <a href="'.$urls_file_path.'">'.$urls_file_path.'</a>';

if ( count($urls_global_errors) > 0 ) {
	$errors_to_store = implode("\n", $urls_global_errors);
	file_put_contents($errors_file_path, $errors_to_store);
	echo '<br><br>Errors stored in <a href="'.$errors_file_path.'">'.$errors_file_path.'</a>';
}
else echo '<br><br>Congrats! No errors.';

function check_link($url) {

	global $urls_global, $recursive_current, $base_url, $urls_global_errors, $links_limit;
	$links_processed = array();
	
	$recursive_current = $recursive_current + 1;

	if ( $recursive_current > $links_limit ) {
		return;
	}

//	$html = file_get_html($url);

	$html = str_get_html( curl($url) );
//	sleep(1);

	if ( $html != null && $html->find('a', 0) != null ) {

		echo '<br><h6 style="color: green;">'.$recursive_current.'. '.$url.'</h6>';

		$links = $html->find('a');
		foreach($links as $link) {
			$link = $link->href;
//			echo $link.'<br>';
			if ( !preg_match( '|\/\/|', $link) ) {
				if ( @$link[0] == '/' ) $link = substr($link, 1);
				$link = $base_url.$link;
			}
//			echo strpos($link, '//').'<br>';
			if ( !in_array($link, $urls_global) && strpos($link, $base_url) === 0 ) {
				echo $link.'<br>';
				
				$links_processed[] = $link;
				$urls_global[] = $link;
			}
		}

		if ( count($links_processed) > 0 ) {
			foreach($links_processed as $link) check_link($link) ;
		}
	}		
	else {
		echo '<br><h6 style="color: red;">'.$recursive_current.'. '.$url.'</h6><br>';
		$urls_global_errors[] = $url;
	}
	
}

function curl( $url, $retry = 3 ){
	ob_implicit_flush(true);
	@ob_end_flush();
	
	$user_agent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/94.0.4606.54 Safari/537.36';

	if( $retry > 5 ) {
		print "Maximum 5 retries are done, skipping!\n";
		return "in loop!";
	}
	
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url);
	curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
	curl_setopt ($ch, CURLOPT_HEADER, TRUE);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
//	curl_setopt ($ch, CURLOPT_REFERER, 'http://www.google.com.ua/');
//curl_setopt($ch, CURLOPT_PROXY, 'socks5://144.76.64.245:9100');

//	curl_setopt($ch,CURLOPT_ENCODING , "");
//	curl_setopt ($ch, CURLOPT_COOKIEFILE,"./cookie.txt");
//	curl_setopt ($ch, CURLOPT_COOKIEJAR,"./cookie.txt");
//	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);

	// handling the follow redirect
	if(preg_match("|Location: (https?://\S+)|", $result, $m)){
		echo "Manually doing follow redirect! -> $m[1] <br>";
		return curl($m[1], $user_agent, $retry + 1);
	}

	// add another condition here if the location is like Location: /home/products/index.php

	return $result;
}

?>