<?php
	

	print plogger_download_selected_form_end();
	print '</div>';
	print '<div id="pagination">'.plogger_pagination_control().'</div>';
	
	print '<div id="footer">
				<div id="slideshow_link">'.plogger_slideshow_link().'</div>
				<div id="sortby-container">'.generate_jump_menu().'</div>'; 
			print '</div>';

	print plogger_link_back();
	
	print '</div>';
	

?>
