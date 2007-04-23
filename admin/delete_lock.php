<?php
$delay_include = true;
require_once('default.admin.inc.php');
$restore_dir = "$php_includes/adminrestore";
if ($dh = @opendir($restore_dir)) {
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$restore_dir/$fname") || 
      trigger_error("Couldn't delete $fname from $restore_dir",E_USER_ERROR);
  }
}
print("All done!");
?>
