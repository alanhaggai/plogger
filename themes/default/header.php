<?php

	plogger_init();
	print plogger_download_selected_form_start();
	// Output highest level container division
	print   '<div id="wrapper">
			<table id="header-table" width="100%"><tr><td>'.generate_header().
			'</td><td id="jump-search-container">'.generate_jump_menu() . generate_search_box().'</td></tr></table>';
	
	
	print '<div id="main_container">
			<div id="breadcrumbs">
				<table width="100%">
					<tr>
						<td>
							'.generate_breadcrumb().'
						</td>
						<td class="align-right">'.
	
						plogger_download_selected_button().
						plogger_print_button().
	
		'				</td>
					</tr>
				</table>
			</div>';

?>