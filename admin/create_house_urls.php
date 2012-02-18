<?php
$delay_include = true;
require_once('default.admin.inc.php');
$docroot_dir = substr($php_includes,0,-strlen('php_includes'));

print "Directory links will be created in the directory $docroot_dir<br/>\n";
foreach ($houses as $house) {
  print "$house:<br>\n";
  symlink($docroot_dir . 'public_html/',$docroot_dir . "$house");
}