<?php
plogger_get_header();
print '<div id="thumbnail_container">';
if (plogger_has_pictures()) {
	print '<div id="overlay">&nbsp;</div>';
	print '<ul class="slides">';
	
	while(plogger_has_pictures()) {
		
		plogger_load_picture();

		// display thumbnails within album
		// generate XHTML with thumbnail and link to picture view.
		// find thumbnail width
		$thumb_info = plogger_get_thumbnail_info();
		$thumb_width = $thumb_info[0]; // The width of the image. It is integer data type.
		$li_width = $thumb_width + 10; // account for padding/border width

		$capt = plogger_get_picture_caption();
		$img_id = "thumb-".plogger_get_picture_id();
		$imgtag = '<img id="' . $img_id 
		. '" onmouseout="document.getElementById(\'overlay\').style.visibility = \'hidden\';" 
		onmouseover="display_overlay(\''.$img_id.'\', \''.plogger_picture_comment_count().'\')" class="photos" 
		src="'.plogger_get_picture_thumb().'" title="'.$capt.'" alt="'.$capt.'" />';
	
		print '<li style="width: '.$li_width.'px"class="thumbnail"><a href="' . plogger_get_picture_url() . '">' . $imgtag . "</a>";
							
		print plogger_download_checkbox(plogger_get_picture_id());
		
		print '<div class="tag">' . $capt . '</div></li>';
	}
	
	
	print '</ul>';
}
else{
	print '<div id="no-pictures-msg">There are no pictures in this album.</div>';
}

print '</div>';
plogger_get_footer();
?>
