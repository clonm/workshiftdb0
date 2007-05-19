<?php 
//Page to delete weeks.  This page should almost never be called
//directly anymore -- basic_consts.php takes care of it at the start
//of the semester (by calling this script), and each weekly sheet has
//a button at the top to delete (using this script).  The user really
//shouldn't have to access this page.  Weeks are not actually deleted
//-- they go to a backup (not a backup as in a full database backup,
//just a renamed table), and will be overwritten once that week is
//deleted again.
if (!array_key_exists('start_week',$_REQUEST)) { 
?>
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
<?php 
require_once('default.inc.php');
?>
<h2>Do not reload this page!!</h2>
<?php
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
  //put this in an invisible div, so that user doesn't see details
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
