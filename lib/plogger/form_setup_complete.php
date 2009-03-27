<?php
if (!defined('PLOGGER_DIR')) {
	return false;
};
?>
<form method="POST">
<p>Setup is now complete</p>
<p>Your username is <?=$_SESSION['install_values']['admin_username']?> and your password is `<?=$_SESSION['install_values']['admin_password']?>`</p>
<?php if (!empty($_SESSION["plogger_config"])) { ?>
<p>Before you can proceed, please <input type="submit" name="dlconfig" value="click here"/> to download configuration file for your gallery, then upload it to your webhost (into the same directory where you installed Plogger itself).</p>
<?php } ?>
<p>
<input type="submit" name="proceed" value="Proceed"/>
</p>
</form>
