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

		$capt = plogger_get_picture_caption();
		$img_id = "thumb-".plogger_get_picture_id();
		$imgtag = '<img id="' . $img_id 
		. '" onmouseout="document.getElementById(\'overlay\').style.visibility = \'hidden\';" 
		onmouseover="display_overlay(\''.$img_id.'\', \''.plogger_picture_comment_count().'\')" class="photos" 
		src="'.plogger_get_picture_thumb().'" title="'.$capt.'" alt="'.$capt.'" />';
	
		print '<li class="thumbnail"><div class="tag"><a href="' . plogger_get_picture_url() . '">' . $imgtag . "</a><br />";
							
		print plogger_download_checkbox(plogger_get_picture_id());
				
		print '</div></li>';
	}
	
	
	print '</ul></div>';
}
else{
	print '<div id="no-pictures-msg">There are no pictures that matched your search.</div>';
}

plogger_get_footer();
?>
