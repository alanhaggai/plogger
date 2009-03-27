<?php plogger_init(); ?>

<div id="wrapper">

	<div id="header">
		<?php echo generate_header(); ?>
		
		<div id="search-container">
  			<?php echo generate_search_box(); ?>
		</div>
  
		<div id="breadcrumbs">
  
			<div id="slideshow">
			  	<?php echo plogger_slideshow_link(); ?>
				<?php echo plogger_print_button(); ?>
			</div>

			<?php echo generate_breadcrumb(); ?>
			
		</div>
		
		<!-- <p></p> -->
		
	</div>
	<?php echo plogger_download_selected_form_start(); ?>