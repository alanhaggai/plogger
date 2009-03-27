INSTALLATION
============
To install, upload all of the files in the Plogger distribution to
your Web server.  Then, run the _install.php script from the Web
browser of your choice.  That script should guide you through the
rest of the installation process.

To integrate Plogger into your website, place the following PHP 
statements in the HTML document you wish display the gallery:

First line of HTML file ->  <?php require("gallery.php"); ?>
In HEAD section of HTML -> <?php the_gallery_head(); ?>
Somewhere in BODY section -> <?php the_gallery(); ?>

That's it!

Version: Beta3

UPGRADE FROM BETA1
==================
If you are upgrading from Beta 1 or any checked out test version, Follow these simple instructions:

1.) Upload and Overwrite ALL FILES except for plog-connect.php, and index.php (if you modified it)
2.) Run _upgrade.php

UPGRADE FROM BETA2
==================
If you are upgrading from Beta 2 or any checked out test version, Follow these simple instructions:

1.) Upload and Overwrite ALL FILES except for plog-config.php, and index.php (if you modified it)
2.) Run _upgrade.php
3.) You can delete admin/plog-globals.php if you want, it's not used anymore