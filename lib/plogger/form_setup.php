<?php
if (!defined('PLOGGER_DIR')) {
	return false;
};
?>
<h1>Plogger Installation</h1>
<p>To install, simply fill out the following form.  If there are any problems, you will be notified and asked to fix them before the installation will continue.  After the installation has finished, you will be redirected to your Plogger home page.</p>
<form action="_install.php" method="post">
<input type="hidden" name="action" value="install" />
<table>
	<tr>
	<td colspan="2">
		<div id="navcontainer">
			<h1>Database Setup</h1>
		</td>
	</tr>
	<tr>
		<td class="form_label"><label for="db_host">MySQL host:</label></td>
		<td class="form_input"><input type="text" name="db_host" id="db_host" value="<?=$form['db_host']?>" /></td>
	</tr>

	<tr>
		<td class="form_label"><label for="db_user">MySQL Username:</label></td>
		<td class="form_input"><input type="text" name="db_user" id="db_user" value="<?=$form['db_user']?>" /></td>
	</tr>
	<tr>
		<td class="form_label"><label for="db_password">MySQL Password:</label></td>
		<td class="form_input"><input type="password" name="db_pass" id="db_pass" value="<?=$form['db_pass']?>" /></td>
	</tr>

	<tr>
		<td class="form_label"><label for="db_name">MySQL Database:</label></td>
		<td class="form_input"><input type="text" name="db_name" id="db_name" value="<?=$form['db_name']?>" /></td>
	</tr>
	<tr>
		<td colspan="2">
		<div id="navcontainer">
			<h1>Administrative Setup</h1>
		</div>
		</td>
	</tr>
	<tr>
		<td class="form_label"><label for="gallery">Gallery Name:</label></td>
		<td class="form_input"><input type="text" name="gallery_name" id="gallery" value="<?=$form['gallery_name']?>" /></td>
	</tr>
	<tr>
		<td class="form_label"><label for="username">Your e-mail:</label></td>
		<td class="form_input"><input type="text" name="admin_email" id="email" value="<?=$form['admin_email']?>" /></td>
	</tr>
	<tr>
		<td class="submitButtonRow" colspan="2">
			<input type="submit" name="submit" id="submit" value="Install" />
		</td>
	</tr>
</table>
</form>
