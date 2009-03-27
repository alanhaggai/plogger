<?php
	

	print '</div>';

	print ' <div id="pagination">
				<table style="width: 100%;">
					<tr><td>'.plogger_slideshow_link().'</td>
						<td>'.plogger_pagination_control().'</td>
						<td id="sortby-container">'.plogger_sort_control().'</td> 
						<td id="rss-tag-container">'.plogger_rss_feed_button().'</td>
					</tr>
				</table>
			</div>';

	print plogger_link_back();
	print '</div>';
	print plogger_download_selected_form_end();
?>
