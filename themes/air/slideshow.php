<?php plogger_get_header(); ?>

<?php if (plogger_has_pictures()) : ?>

	<div id="big-picture-container">
	<script type="text/javascript">
	<!--
	slides = new slideshow("slides");
	slides.prefetch = 2; slides.timeout = 4000;

	<?php while(plogger_has_pictures()) : ?>
	
		<?php $pic = plogger_load_picture(); ?>
		// output a line of javascript for each image
		s = new slide("<?php echo plogger_get_picture_thumb(THUMB_LARGE); ?>",
		"<?php echo plogger_get_source_picture_url(); ?>",
		"<?php echo plogger_get_picture_caption(); ?>",
		"_self","","","<?php echo basename($pic['path']); ?>");
		slides.add_slide(s);
		
	<?php endwhile; ?>
		
	// --> 
	</script>

	<?php echo generate_slideshow_interface(); ?>

<?php else : ?>
	
	<div id="no-pictures-msg">There are no pictures to show.</div>

<?php endif; ?>

</div>

<?php function generate_slideshow_interface() {
	global $config;
	$large_link = '<a accesskey="v" href="javascript:slides.hotlink()" title="View Large Image"><img src="'.$config	["gallery_url"].'graphics/search.gif" width="16" height="16" alt="View Large Image" /></a>';
			
	$prev_url = '<a accesskey="," title="Previous Image" 
	href="javascript: slides.previous();"><img src="'.$config["gallery_url"].'graphics/rewind.gif" 
	width="16" height="16" alt="Previous Image" /></a>';
		
	$next_url = '<a accesskey="." title="Next Image" 
	href="javascript: slides.next();"><img src="'.$config["gallery_url"].'graphics/fforward.gif" 
	width="16" height="16" alt="Next Image" /></a>';
		
	$stop_url = '<a accesskey="x" title="Stop Slideshow" 
	href="javascript: slides.pause();"><img src="'.$config["gallery_url"].'graphics/stop.gif" 
	width="16" height="16" alt="Stop Slideshow" /></a>';
	
	$play_url = '<a accesskey="s" title="Start Slideshow" 
	href="javascript: slides.play();"><img src="'.$config["gallery_url"].'graphics/play.gif" 
	width="16" height="16" alt="Start Slideshow" /></a>';
	
	$output = '<div class="large-thumb-toolbar" style="width:'.$config["max_display_size"].'">
			   '.$large_link.$prev_url.$stop_url.$play_url.$next_url.'</div>';
	$imgtag = '<img id="slideshow_image" class="photos-large" src="about:blank" 
			  title="" alt="" />';
			  
	$output .= '<div id="picture-holder"><a href="javascript:slides.hotlink()">'.$imgtag.'</a></div>';
	
	// activate slideshow object using javascript block
	$output .=	'<script type="text/javascript">
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
				</script>';
	
	return $output;
} ?>

<?php plogger_get_footer(); ?>

