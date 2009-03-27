<?php
// this file handles the generation of the RSS feed.
include_once("plog-functions.php");
include_once("plog-globals.php");
include_once("plog-load_config.php");

function generate_RSS_feed ($level, $id, $search = "") {
	global $config;

	$config["feed_title"] = SmartStripSlashes($config["feed_title"]);

	if (!empty($search)) $level = "search";

	if ($level == "collection") {  // aggregate feed of all albums with collection specified by id
		plogger_init_pictures(array(
			'type' => 'collection',
			'value' => $id,
			'limit' => $config['feed_num_entries'],
			'sortby' => 'id',
		));
		$collection = get_collection_by_id($id);
		$config["feed_title"] .= ": " . $collection['collection_name'] . "Collection";

	} else if ($level == "album") { 
		plogger_init_pictures(array(
			'type' => 'album',
			'value' => $id,
			'limit' => $config['feed_num_entries'],
			'sortby' => 'id',
		));
		$album = get_album_by_id($id);
		$config["feed_title"] .= ": " . $album['album_name'] . " Album";

	} else if ($level == "search") {
		plogger_init_search(array(
			'searchterms' => $search,
			'limit' => $config['feed_num_entries'],
		));

	} else if ($level == "") {
		plogger_init_pictures(array(
			'type' => 'latest',
			'limit' => $config['feed_num_entries'],
			'sortby' => 'id',
		));
		$config["feed_title"] .= ": Entire Gallery";
	}

	$header = 1;
	
	// generate RSS header
	$rssFeed = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<rss version=\"2.0\">";

	$rssFeed.= "<channel>\n";
	$rssFeed.= "<title>".$config['feed_title']."</title>\n";
	$rssFeed.= "<description>Plogger RSS Feed</description>\n";
	$rssFeed.= "<language>".$config['feed_language']."</language>\n";
	$rssFeed.= "<docs>http://blogs.law.harvard.edu/tech/rss</docs>\n";
	$rssFeed.= "<generator>Plogger</generator>\n";
	$rssFeed.= "<link>".$config['gallery_url']."</link>\n";

	while(plogger_has_pictures()) {
		plogger_load_picture();
		$date = plogger_get_picture_date("D, d M Y H:i:s O",1);
			
		if ($header) {
			$rssFeed.= "<pubDate>". $date . "</pubDate>\n";
			$rssFeed.= "<lastBuildDate>". $date . "</lastBuildDate>\n";
			$header = 0;
		}
			
		$rssFeed .= "<item>\n";

		$caption = plogger_get_picture_caption();
		$thumbpath = plogger_get_picture_thumb(THUMB_SMALL);
		$pagelink = plogger_get_picture_url();
  		
  		if ($caption == "" || $caption == "&nbsp;") $caption = "New Photograph (no caption)";
  		
		$discript = '&lt;p&gt;&lt;a href="'.$pagelink.'"  
		title="'.$caption.'"&gt;
		&lt;img src="'.$thumbpath.'" alt="'.$caption.'" style="border: 1px solid #000000;" /&gt;
		&lt;/a&gt;&lt;/p&gt;&lt;p&gt;'.$caption.'&lt;/p&gt;';
		
		$discript .= '&lt;p&gt;'.htmlspecialchars(plogger_get_picture_description()).'&lt;/p&gt;';
			
		$rssFeed .= "<pubDate>" . $date . "</pubDate>\n";
		$rssFeed .= "<title>" .$caption .  "</title>\n";
		$rssFeed .= "<link>" . $pagelink .  "</link>\n";
		$rssFeed .= "<description>" . $discript .  "</description>\n"; 
		$rssFeed .= "<guid isPermaLink=\"false\">".$thumbpath."</guid>";
		$rssFeed .= "</item>\n";
	}
	
	$rssFeed .= "</channel></rss>";
	echo $rssFeed;
}

// send proper header
header("Content-Type: application/xml");

$level = isset($_GET["level"]) ? $_GET["level"] : "";
$id = isset($_GET["id"]) ? intval($_GET["id"]) : "";

// process path here - is set if mod_rewrite is in use

// ja see urli parsimine peaks ka ometi eraldi funktsioonis olema, et ma saaksin seda shareda 
// nii siin kui seal kui ka ilmselt veel mõnes kohas
if (!empty($_REQUEST["path"])) {
	// the followling line calculates the path in the album and excludes any subdirectories if
	// Plogger is installed in one
	$path = join("/",array_diff(explode("/",$_SERVER["REQUEST_URI"]),explode("/",$_SERVER["PHP_SELF"])));
	$resolved_path = resolve_path($path);
	// there is no meaningful RSS feed for images
	if (is_array($resolved_path) && $resolved_path["level"] != "picture") {
		$level = $resolved_path["level"];
		$id = $resolved_path["id"];
	};
};

$parts = parse_url($_SERVER["REQUEST_URI"]);
parse_str($parts["query"],$query_parts);
generate_RSS_feed($level, $id, $query_parts["searchterms"]);

?>
