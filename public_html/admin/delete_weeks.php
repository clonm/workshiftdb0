<?php if (!array_key_exists('start_week',$_REQUEST)) { ?>
<html><head><title>DELETE weeks</title></head><body>
<h3>Deleting weeks is not recommended unless it's the beginning of the
semester, or permanent shifts have changed and need to be redone.
Backups of the latest deletion will still be in the database, so don't
panic if you screw up -- <a href='recover_backup_week'>recover the backed-up week here</a>.</h3>
<form action='delete_weeks.php' method=post>
Starting week to delete: <input type=text name='start_week' size=2><br>
Ending week to delete: <input type=text name='end_week' size=2<br>
<input type=submit value=Delete></form></body></html>
	       <?php exit; } ?>
<html><head><title>DELETE weeks result</title></head><body>
<h2>Do not reload this page!!</h2>
<?php 
require_once('default.inc.php');
$ctr = $_REQUEST['start_week'];
$end_week = $_REQUEST['end_week'];
if (!isset($ctr) || !isset($end_week)) {
  exit("Starting or ending week wasn't specified.");
}
////delete everything we can get our hands on
//janak_fatal_error_reporting(0);
$olddebug = $db->debug;
$db->debug = true;
for (;$ctr <= $end_week; $ctr++) {
  echo "<div style='display: none'>\n";
  if (table_exists("week_$ctr")) {
    $db->Execute('DROP TABLE IF EXISTS ' . bracket("zzBackupweek_$ctr"));
    $db->Execute('RENAME TABLE ' . bracket("week_$ctr") . ' TO ' . 
                 bracket("zzBackupweek_$ctr"));
    echo "</div>\nDeleted week $ctr";
  }
  else {
    echo "</div>";
  }
}
$db->debug = $olddebug;
?>
