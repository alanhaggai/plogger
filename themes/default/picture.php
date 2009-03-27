<?php
plogger_get_header();
if (plogger_has_pictures()) {
	while(plogger_has_pictures()) {
		$row = plogger_load_picture();

		print ' <div id="inner_wrapper"> <div id="big-picture-container">
			<table style="width: 100%;">
				<tr>
					<td>
						<h2 class="date">'.plogger_get_picture_date().'</h2>
					</td>
					<td class="align-right">
						<h2 id="picture_caption">';
		$capt = plogger_get_picture_caption();
		print $capt;

		print '</h2></td></tr></table>';

		// generate XHTML with thumbnail and link to picture view.
		$imgtag = '<img class="photos-large" src="'.plogger_get_picture_thumb(THUMB_LARGE).'" title="'.$capt.'" alt="'.$capt.'" />';

		$detail_link = plogger_get_detail_link();
		$prev_link = plogger_get_prev_picture_link();
		$next_link = plogger_get_next_picture_link();

		print ' <table style="width: 100%;">
				<tr>
					<td id="prev-link-container">
						'.$prev_link.'
					</td>
					<td id="next-link-container">
						'.$next_link.'
					</td>
				</tr>
			</table>';

		print '
		<div id="picture-holder">
			<a accesskey="v" href="'.plogger_get_source_picture_url().'">'.$imgtag.'</a>
		</div>';

		print '<p id="description">' . plogger_get_picture_description() . '</p>';
		
		print '
		<table style="width: 100%;">
			<tr>
				<td id="exif-toggle-container"><div id="exif_toggle">'.$detail_link.'</div></td>
			</tr>
		</table>
		</div>';


		print generate_exif_table($row["id"]);

		// display comments for selected picture
		print plogger_display_comments();
		print '</div>';
	
	} 
} else {
	print "No such image";
};
plogger_get_footer();
?>
