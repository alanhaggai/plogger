<?php
plogger_get_header();
print '<div id="thumbnail_container">';
if (plogger_has_collections())
{
	print '<ul class="slides">';
	while(plogger_has_collections())
	{
		$row = plogger_load_collection();
		$desc = plogger_get_collection_description();

		print '<li class="thumbnail"><div class="tag"><a href="' . plogger_get_collection_url() . '">';
			
		// generate XHTML with thumbnail and link to picture view.
		print '<img class="photos" src="'.plogger_get_collection_thumb().'" title="'.$desc.'" alt="'.$desc.'" />';
		
		print '</a><br/>';
			
		print plogger_download_checkbox(plogger_get_collection_id());
			
		print plogger_get_collection_name().' <br />';
		print '<div class="meta-header">(';

		$num_albums = plogger_collection_album_count();
		print $num_albums . ' ';
		print ($num_albums == 1) ? "album" : "albums";
		
		print ')</div></div></li>';
	}
	print '</ul>';
} else {
	print "No collections yet";
}
print '</div>';
plogger_get_footer();
?>
