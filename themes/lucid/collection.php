<?php
plogger_get_header();
print '<div id="thumbnail_container">';
if (plogger_has_albums())
{
	print '<div id="collections">';
	while(plogger_has_albums())
	{
		plogger_load_album();
		$desc = plogger_get_album_description();

		print '<div class="collection"><a href="' . plogger_get_album_url() . '">';
			
		// generate XHTML with thumbnail and link to picture view.
		print '<img class="photos" src="'.plogger_get_album_thumb().'" title="'.$desc.'" alt="'.$desc.'" /></a>';
			
		print '<h2>' . plogger_get_album_name() . '</h2>';
		print plogger_download_checkbox(plogger_get_album_id());
		
		print '<span class="meta-header">Contains ';

		$num_albums = plogger_album_picture_count();
		print $num_albums . ' ';
		print ($num_albums == 1) ? "Pictures" : "Pictures";
		
		print '</span>';
		
		print '<p class="description">'.$desc.'</p>';
		print '</div>';
	}
	print '</div>';
} else {
	print "No albums yet";
}
print '</div>';
plogger_get_footer();
?>
