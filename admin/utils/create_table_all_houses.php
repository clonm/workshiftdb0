<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
$db->debug = true; 
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  $db->Execute("CREATE TABLE IF NOT EXISTS `GLOBAL_archive_data` (" .
  "`autoid` int(11) NOT NULL auto_increment, " .
  "`archive` varchar(50) NOT NULL, " .
  "`semester_start` date NOT NULL, " .
  "`mod_date` datetime NOT NULL, " .
  "`cur_week` int(11) default NULL, " .
  "`num_wanted` int(11) default NULL, " .
  "`num_assigned` int(11) default NULL, " .
  "PRIMARY KEY (`autoid`), UNIQUE KEY `archive` (`archive`), " .
  "INDEX (`semester_start`), INDEX (`mod_date`)" .
  ")ENGINE=InnoDB");
}

