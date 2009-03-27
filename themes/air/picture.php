<?php plogger_get_header(); ?>

<div id="big-picture-container">

<?php if (plogger_has_pictures()) : ?>

	<?php while(plogger_has_pictures()) : // equivalent to the Wordpress loop ?>

		<?php plogger_load_picture();
		// set variables for the picture
		$picture_caption = trim(plogger_get_picture_caption());
		$detail_link = plogger_get_detail_link();
		$prev_link = plogger_get_prev_picture_link();
		$next_link = plogger_get_next_picture_link();
		// find thumbnail width
		$thumb_info = plogger_get_thumbnail_info();
		$thumb_width = $thumb_info[0]; // The width of the image. It is integer data type.
		$thumb_height = $thumb_info[1];	// The height of the image. It is an integer data type.
?>

		<div id="nav-link-img-prev"><?php echo $prev_link; ?></div>
		<div id="nav-link-img-next"><?php echo $next_link; ?></div>

		<?php if ($picture_caption != '' || !isset($picture_caption)); ?>
		<h2 id="picture-caption"><?php echo $picture_caption; ?></h2>
		<h2 class="date"><?php echo plogger_get_picture_date(); ?></h2>

		<?php // generate XHTML with picture and link to full raw picture ?>

		<div id="picture-holder"><a accesskey="v" href="<?php echo plogger_get_source_picture_url(); ?>"><img class="photos-large" src="<?php echo plogger_get_picture_thumb(THUMB_LARGE); ?>" title="<?php echo $capt; ?>" alt="<?php echo $capt; ?>" /></a>
			   </div>

		<p id="picture-description"><?php echo plogger_get_picture_description(); ?></p>
		
		<div id="exif_toggle"><?php echo $detail_link; ?></div>

		<div id="exif-toggle-container"><?php echo generate_exif_table(plogger_get_picture_id()); ?></div>

		<div class="clearfix"><?php echo plogger_get_thumbnail_nav(); ?></div>

		<div id="comment-section"><?php echo plogger_display_comments(); ?></div>
		
		</div>
	
		<?php endwhile; ?>
		

	<?php else : ?>

	<p>No such image</p>

	<?php endif; ?>

<?php plogger_get_footer(); ?>
