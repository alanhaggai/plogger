<?php
if (plogger_comments_on()) {
	print '<a name="comments"></a><h2 class="comment-heading">Comments:</h2>';
      
	if (plogger_picture_has_comments()) {
		print "<ol class=\"comments\">";
		$counter = 0;
		while(plogger_picture_has_comments()) {
			plogger_load_comment();
			
			$url = plogger_get_comment_url();
			$author = plogger_get_comment_author();
		
			// this code alternates the background color every other comment
			$comment_class = ($counter % 2) ? "comment" : "comment_alt";
					
			print "<li class=\"$comment_class\">";
			print "<p>".plogger_get_comment_text()."</p>";
			print "<cite>Comment by ";
			print (trim($url) != '') ? "<a href=\"$url\">$author</a>" : "$author";
			print "- posted on ".plogger_get_comment_date();
		        		
			print "</cite></li>";
			$counter++;
		}
		print "</ol>";
	} else {
		print "<p>No comments yet.</p>";
	};
      
	if (plogger_picture_allows_comments()) {
		if (plogger_comment_post_error()) {
			print "<p class='errors'>Comment did not post!  Please fill in required fields.</p>";
		};
		
		if (plogger_comment_moderated()) {
			print "<p class='actions'>Your comment was placed in moderation, please wait for approval.  
			Do not submit comment again!</p>";
		};

		global $config;
    	
		print  '<a name="comment-post"></a><h2 class="comment-heading">Post a comment:</h2>
		      <form action="' . $config["gallery_url"] . 'plog-comment.php" method="post" id="commentform">
		      <p>
			<input type="text" name="author" id="author" class="textarea" value="" size="28" tabindex="1" />
			<label for="author">Name</label> (required) <input type="hidden" name="comment_post_ID" value="40" />
			<input type="hidden" name="parent" value="'.plogger_get_picture_id().'" />
		      </p>
		      <p>
			<input type="text" name="email" id="email" value="" size="28" tabindex="2" />
			<label for="email">E-mail</label> (required, but not publicly displayed)
		      </p>
		      <p>
			<input type="text" name="url" id="url" value="" size="28" tabindex="3" />
			<label for="url">Your Website (optional)</label>
		      </p>
		      <p>
			<label for="comment">Your Comment</label>
			<br /><textarea name="comment" id="comment" cols="70" rows="4" tabindex="4"></textarea>
		      </p>
		      <p>
			<input class="submit" name="submit" type="submit" tabindex="5" value="Post Comment!" />
		      </p>
		      </form>';
	
    	} else {
		print '<p class="comments-closed">Comments for this entry are closed</p>';
	}
}; 
?>
