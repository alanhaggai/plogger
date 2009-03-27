<?php

	plogger_init();
	
	// Output highest level container division
	print   '<div id="wrapper">
			<div id="header"><div>'.generate_header().'</div>
			<div id="jump-search-container">'.generate_search_box().'</div></div>';
	
	
	print '<div id="main_container">
			'.plogger_download_selected_form_start().'
			<div id="breadcrumbs">
				<div id="download-selected">'.plogger_print_button().plogger_download_selected_button().'</div>
				<div>'.generate_breadcrumb().'</div>
			</div>';

?>