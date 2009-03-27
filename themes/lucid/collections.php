<?php
plogger_get_header();
print '<div id="thumbnail_container">';
if (plogger_has_collections())
{
	print '<div id="collections">';
	while(plogger_has_collections())
	{
		plogger_load_collection();
		$desc = plogger_get_collection_description();

		print '<div class="collection"><a href="' . plogger_get_collection_url() . '">';
			
		// generate XHTML with thumbnail and link to picture view.
		print '<img class="photos" src="'.plogger_get_collection_thumb().'" title="'.$desc.'" alt="'.$desc.'" /></a>';
			
		print '<h2>' . plogger_get_collection_name() . '</h2>';
		print plogger_download_checkbox(plogger_get_collection_id());
		
		print '<span class="meta-header">Contains ';

		$num_albums = plogger_collection_album_count();
		print $num_albums . ' ';
		print ($num_albums == 1) ? "Album" : "Albums";
		
		print '</span>';
		
		print '<p class="description">'.$desc.'</p>';
		print '</div>';
	}
	print '</div>';
} else {
	print "No collections yet";
}
print '</div>';
plogger_get_footer();
?>
