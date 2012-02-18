<?php
$delay_include = true;
require_once('default.admin.inc.php');
$restore_dir = "$php_includes/scratch/adminrestore";
if ($dh = @opendir($restore_dir)) {
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$restore_dir/$fname") || 
      janak_error("Couldn't delete $fname from $restore_dir");
  }
}
else {
  exit("Couldn't open directory");
}
print("All done!");
?>
