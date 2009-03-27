<?php plogger_get_header(); ?>

<div id="thumbnail-container" class="clearfix">

<?php if (plogger_has_collections()) : ?>

	<div id="collections">

	<?php while(plogger_has_collections()) : ?>

		<?php plogger_load_collection();
		// set variables for the collection
		$desc = plogger_get_collection_description();
		$name = plogger_get_collection_name();
		$num_albums = plogger_collection_album_count();
		?>

		<div class="collection">
		
		<a href="<?php echo plogger_get_collection_url(); ?>">
			
		<?php // generate XHTML with thumbnail and link to album view ?>
		<img class="photos" src="<?php echo plogger_get_collection_thumb(); ?>" title="<?php echo $name; ?>" alt="<?php echo $name; ?>" /></a>

			<h2><a href="<?php echo plogger_get_collection_url(); ?>"><?php echo $name; ?></a></h2>

			<?php echo plogger_download_checkbox(plogger_get_collection_id()); ?>

			<span class="meta-header">Contains <?php echo $num_albums . ' '; echo ($num_albums == 1) ? "Album" : "Albums"; ?></span>

			<p class="description"><?php echo $desc; ?></p>
			
		</div>

		<?php endwhile; ?>

	</div>

	<?php else : ?>

	<p>No collections yet</p>

	<?php endif; ?>

</div>

<?php plogger_get_footer(); ?>
