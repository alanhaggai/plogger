<?php
plogger_get_header();
if (plogger_has_pictures()) {
	while(plogger_has_pictures()) {
		
		plogger_load_picture();

		print '<div id="inner_wrapper"> <div id="big-picture-container">
			   <div><h2 class="date">' . plogger_get_picture_date() . '</h2></div>';
			   $picture_caption = trim(plogger_get_picture_caption());
			   if ($picture_caption != '' || !isset($picture_caption))
			   echo '<div><h2 id="picture_caption">' . $picture_caption . '</h2></div>';


		// generate XHTML with thumbnail and link to picture view.
		$imgtag = '<img class="photos-large" src="'.plogger_get_picture_thumb(THUMB_LARGE).'" title="'.$capt.'" alt="'.$capt.'" />';

		$detail_link = plogger_get_detail_link();
		$prev_link = plogger_get_prev_picture_link();
		$next_link = plogger_get_next_picture_link();

		print '<div id="nav-link-img-prev">'.$prev_link.'</div>
			   <div id="nav-link-img-next">'.$next_link.'</div>';

		print '<div id="picture-holder">
					<a accesskey="v" href="'.plogger_get_source_picture_url().'">'.$imgtag.'</a>
			   </div>';

		print '<p id="picture_description">' . plogger_get_picture_description() . '</p>';
		
		print '
		<div id="exif-toggle-container"><div id="exif_toggle">'.$detail_link.'</div></div>';

		print generate_exif_table(plogger_get_picture_id());

		// display comments for selected picture
		print plogger_display_comments();
		print '</div>';
	
	} 
} else {
	print "No such image";
};
plogger_get_footer();
?>
