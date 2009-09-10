<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
#$db->debug = true; $houses = array('caz');
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  $res = $db->Execute("select `archive` from `GLOBAL_archive_data`");
  while ($row = $res->FetchRow()) {
    $archive = $archive_pre . $row['archive'] . "_";
    $owed = get_static('owed_default');
    if ($owed != 5) {
      print "changing $archive to $owed\n";
      $db->Execute("update `GLOBAL_archive_data` set `owed_default` = ? " .
          "where `archive` = ?",array($owed,$row['archive']));
    }
  }
}

