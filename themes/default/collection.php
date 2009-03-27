<?php
plogger_get_header();
print '<div id="thumbnail_container">';
if (plogger_has_albums()) {
	print '<ul class="slides">';
	
	while (plogger_has_albums()){
		plogger_load_album();
		$num_pictures = plogger_album_picture_count();

		print '<li class="thumbnail"><div class="tag"><a href="' . plogger_get_album_url() . '">';

		$desc = plogger_get_album_description();
		// generate XHTML with thumbnail and link to picture view.
		print '<img class="photos" src="'.plogger_get_album_thumb().'" title="'.$desc.'" alt="'.$desc.'" />';
		
		print '</a> <br />';
			
		print plogger_download_checkbox(plogger_get_album_id());
			
		print plogger_get_album_name().' <br /><div class="meta-header">(';
		print $num_pictures.' ';
		print ($num_pictures == 1) ? "picture" : "pictures";
		
		print ')</div></div></li>';
				
	}
	print'</ul>';
}
else
{
	print "No pictures in this collection!";
}
print '</form></div>';
plogger_get_footer();
?>

