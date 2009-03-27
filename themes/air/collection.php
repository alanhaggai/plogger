<?php plogger_get_header(); ?>

<div id="thumbnail-container">

<?php if (plogger_has_albums()) : ?>

	<div id="collections">

	<?php while(plogger_has_albums()) : ?>

		<?php plogger_load_album();
		// set variables for the album
		$desc = plogger_get_album_description();
		$name = plogger_get_album_name();
		$num_albums = plogger_album_picture_count();
		?>
		
		<div class="collection"><a href="<?php echo plogger_get_album_url(); ?>">

		<?php // generate XHTML with thumbnail and link to picture view ?>
		<img class="photos" src="<?php echo plogger_get_album_thumb(); ?>" title="<?php echo $name; ?>" alt="<?php echo $name; ?>" /></a>

			<h2><a href="<?php echo plogger_get_album_url(); ?>"><?php echo $name; ?></a></h2>

			<?php echo plogger_download_checkbox(plogger_get_album_id()); ?>

			<span class="meta-header">Contains <?php echo $num_albums . ' '; echo ($num_albums == 1) ? "Picture" : "Pictures"; ?></span>

			<p class="description"><?php echo $desc; ?></p>
		
		</div>

		<?php endwhile; ?>

	</div>

	<?php else : ?>

	<p>No albums yet</p>

	<?php endif; ?>

</div>

<?php plogger_get_footer(); ?>
