<?php
/*
 * Created by Parad0x and khai52
 * This program is free software, hence it being under-development still.
 * \\ You can redistribute it and modify it.
*/

// origin point
$start = "http://localhost/seek/test.html";
// our 2 global arrays containing our links to be crawled.
$already_crawled = array();
$crawling = array();

function get_details($url) {
	// the array we pass to stream_context_create() used to modify our User Agent.
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: SEEK/0.1\n"));
	// create stream context.
	$context = stream_context_create($options);
	// create new instance of PHP's DOMDocument class.
	$doc = new DOMDocument();
	// use file file_get_contents() to download page
	@$doc->loadHTML(@file_get_contents($url, false, $context));

	// create an array of all title tags
	$title = $doc->getElementsByTagName("title");
	// there should be only one title on each page, giving our array only one element.
	$title = $title->item(0)->nodeValue;
	// give description and keywords no initial value, to prevent errors.
	$description = "";
	$keywords = "";
	// create an array of all <meta> tags
	$metas = $doc->getElementsByTagName("meta");
	// loop through all meta tags
	for ($i = 0; $i < $metas->length; $i++) {
		$meta = $metas->item($i);
		// get description and keywords of the url
		if (strtolower($meta->getAttribute("name")) == "description")
			$description = $meta->getAttribute("content");
		if (strtolower($meta->getAttribute("name")) == "keywords")
			$keywords = $meta->getAttribute("content");
	}
	// return our json string containg title, description, keywords and url.
	return '{ "Title": "'.str_replace("\n", "", $title).'", "Description": "'.str_replace("\n", "", $description).'", "Keywords": "'.str_replace("\n", "", $keywords).'", "URL": "'.$url.'"},';
}

function follow_links($url) {
	// Give our function access to our crawl arrays.
	global $already_crawled;
	global $crawling;
	// The array that we pass to stream_context_create() to modify our User Agent.
	$options = array('http'=>array('method'=>"GET", 'headers'=>"User-Agent: howBot/0.1\n"));
	// Create the stream context.
	$context = stream_context_create($options);
	// Create a new instance of PHP's DOMDocument class.
	$doc = new DOMDocument();
	// Use file_get_contents() to download the page, pass the output of file_get_contents()
	// to PHP's DOMDocument class.
	@$doc->loadHTML(@file_get_contents($url, false, $context));
	// Create an array of all of the links we find on the page.
	$linklist = $doc->getElementsByTagName("a");
	// Loop through all of the links we find.
	foreach ($linklist as $link) {
		$l =  $link->getAttribute("href");
		// Process all of the links we find. This is covered in part 2 and part 3 of the video series.
		if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
		} else if (substr($l, 0, 2) == "//") {
			$l = parse_url($url)["scheme"].":".$l;
		} else if (substr($l, 0, 2) == "./") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l, 1);
		} else if (substr($l, 0, 1) == "#") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
		} else if (substr($l, 0, 3) == "../") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		} else if (substr($l, 0, 11) == "javascript:") {
			continue;
		} else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		}
		// If the link isn't already in our crawl array add it, otherwise ignore it.
		if (!in_array($l, $already_crawled)) {
				$already_crawled[] = $l;
				$crawling[] = $l;
				// Output the page title, descriptions, keywords and URL. This output is
				// piped off to an external file using the command line.
				echo get_details($l)."\n";
		}

	}
	// Removes an item from the array after we have crawled it.
	// Minimizes the amount of crawling on one page
	array_shift($crawling);
	// Follow each link in the crawling array.
	foreach ($crawling as $site) {
		follow_links($site);
	}

}
// begins crawling process
follow_links($start);
