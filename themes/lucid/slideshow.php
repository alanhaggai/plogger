<?php
plogger_get_header();
print '<div id="thumbnail_container">';
if (plogger_has_pictures()) {
	print '<div id="inner_wrapper"><div id="big-picture-container">';
	print '<script type="text/javascript">';
	
	print 'slides = new slideshow("slides");';
	print 'slides.prefetch = 2; slides.timeout = 4000; ';

	while(plogger_has_pictures()) {
		$pic = plogger_load_picture();
		// output a line of javascript for each image
		echo 's = new slide("'.plogger_get_picture_thumb(THUMB_LARGE).'",
		"'.plogger_get_source_picture_url().'",
		"'.plogger_get_picture_caption().'",
		"_self","","","'.basename($pic['path']).'");
		slides.add_slide(s);';
	};
	print '// --> </script>';
	print '</div></div>';
	print generate_slideshow_interface();

} else {
	print '<div id="no-pictures-msg">No pictures to show.</div>';
}
print '</div>';

function generate_slideshow_interface() {
	global $config;
	$large_link = '<a accesskey="v" href="javascript:slides.hotlink()" title="View Large Image"><img hspace="1" src="'.$config	["gallery_url"].'graphics/search.gif" width="16" height="16"></a>';
			
	$prev_url = '<a accesskey="," title="Previous Image" 
	href="javascript: slides.previous();"><img hspace="1" src="'.$config["gallery_url"].'graphics/rewind.gif" 
	width="16" height="16"></a>';
		
	$next_url = '<a accesskey="." title="Next Image" 
	href="javascript: slides.next();"><img hspace="1" src="'.$config["gallery_url"].'graphics/fforward.gif" 
	width="16" height="16"></a>';
		
	$stop_url = '<a accesskey="x" title="Stop Slideshow" 
	href="javascript: slides.pause();"><img hspace="1" src="'.$config["gallery_url"].'graphics/stop.gif" 
	width="16" height="16"></a>';
	
	$play_url = '<a accesskey="s" title="Start Slideshow" 
	href="javascript: slides.play();"><img hspace="1" src="'.$config["gallery_url"].'graphics/play.gif" 
	width="16" height="16"></a>';
	
	$output = '<div class="large-thumb-toolbar" style="width:'.$config["max_display_size"].'">
			   '.$large_link.$prev_url.$stop_url.$play_url.$next_url.'</div>';
	$imgtag = '<img name="slideshow_image" class="photos-large" src="about:blank" 
			  title="" alt="" />';
			  
	$output .= '<div id="picture-holder"><a href="javascript:slides.hotlink()">'.$imgtag.'</a></div><br>';
	
	// activate slideshow object using javascript block
	$output .=	'<SCRIPT TYPE="text/javascript">
				<!--
				if (document.images)
				{
				  slides.set_image(document.images.slideshow_image);
				  slides.textid = "picture_caption"; // optional
				  slides.imagenameid = "image_name"; // optional
				  slides.update();
				  slides.play();
				}
				//-->
				</SCRIPT>';
	
	return $output;
}
plogger_get_footer();
?>

