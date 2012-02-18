<?php
error_reporting((E_ALL | E_RECOVERABLE_ERROR) & ~E_STRICT);
$delay_include = true;
require_once('default.admin.inc.php');
$docroot_dir = substr($php_includes,0,-strlen('php_includes'));

print "Directory links will be created in the directory $docroot_dir<br/>\n";
print "If creating the links fails, try running this from the command line, as " .
"php create_house_urls.php<br/>\n";
foreach ($houses as $house) {
  print "$house: ";
  if (!symlink($docroot_dir . 'public_html/',$docroot_dir . "$house")) {
    print "failed!";
  }
  print "<br/>\n";
}