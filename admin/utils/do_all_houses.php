<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
#$db->debug = true;
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  $row = $db->GetRow("SELECT COUNT(*) as `ct` FROM `master_shifts` where `floor` IS NOT NULL AND `floor` != ''");
  print $row['ct'] . "<br>";
}

