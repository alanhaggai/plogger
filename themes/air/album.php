<?php plogger_get_header(); ?>

<div id="thumbnail-container" class="clearfix">

<?php if (plogger_has_pictures()) : ?>

	<ul class="slides clearfix">

	<?php while(plogger_has_pictures()) : ?>

		<?php plogger_load_picture();
		// set variables for the album
		$capt = plogger_get_picture_caption();
		// find thumbnail width
		$thumb_info = plogger_get_thumbnail_info();
		$thumb_width = $thumb_info[0]; // The width of the image. It is integer data type.
		$thumb_height = $thumb_info[1];	// The height of the image. It is an integer data type.
		?>

		<li class="thumbnail">

		<a href="<?php echo plogger_get_picture_url(); ?>"><img id="thumb-<?php echo plogger_get_picture_id(); ?>" class="photos" src="<?php echo plogger_get_picture_thumb(); ?>" width="<?php echo $thumb_width; ?>px" height="<?php echo $thumb_height; ?>px" title="<?php echo $capt; ?>" alt="<?php echo $capt; ?>" /></a>
		
		<div class="checkbox"><?php echo plogger_download_checkbox(plogger_get_picture_id()); ?></div>

		<p style="width:<?php echo $thumb_width; ?>px;"><?php echo $capt; ?></p>

		</li>
		
		<?php endwhile; ?>

	</ul>

	<?php else : ?>

	<div id="no-pictures-msg">There are no pictures in this album.</div>

	<?php endif; ?>

</div>


<?php plogger_get_footer(); ?>
