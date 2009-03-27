

<div id="footer">

	<?php if (plogger_pagination_control() != '') { ?>
	<div id="pagination">
		<?php echo plogger_pagination_control(); ?>
	</div>
	<?php } ?>

	<?php if (plogger_download_selected_button() != '') { ?>
	<div id="download-selected">
		<?php echo plogger_download_selected_button(); ?>
	</div>
	<?php } ?>

	<?php if (generate_jump_menu() != '') { ?>
	<div id="navigation-container">
  		<?php echo generate_jump_menu(); ?>
	</div>
	<?php } ?>
  
	<?php if (plogger_sort_control() != '') { ?>
	<div id="sort-control">
		<?php echo plogger_sort_control(); ?>
	</div>
	<?php } ?>

	<?php if (plogger_rss_feed_button() != '') { ?>
	<div id="rss-tag-container">
		<?php echo plogger_rss_feed_button(); ?>
	</div>
	<?php } ?>

	<?php echo plogger_link_back(); ?>
	<div class="credit"><a href="http://www.ardamis.com/">Design by ardamis.com</a></div>
	
</div>
<?php echo plogger_download_selected_form_end(); ?>

</div>