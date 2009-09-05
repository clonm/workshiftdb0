<?php
require_once('default.admin.inc.php');

foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  //"show tables like " gives awful column names, so we go numeric
  $oldfetch = $db->fetchMode;
  $db->SetFetchMode(ADODB_FETCH_NUM);
  //every backup should have a house list
  $res = $db->Execute("show tables like ?",array('%\_house\_list'));
  //get the names without the archive prefix or the house_list
  $archive_pre_length = strlen($archive_pre);
  $house_list_length = -1*strlen('_house_list');
  $dbnames = array();
  while ($row = $res->FetchRow()) { 
    $dbnames[substr($row[0],$archive_pre_length, 
                        $house_list_length)] = 0; 
  }
  $res = $db->Execute("select `archive` from `GLOBAL_archive_data`");
  while ($row = $res->FetchRow()) {
    if (!isset($dbnames[$row[0]])) {
      print "$house: GLOBAL_archive_data lists " . $row[0] .
        " but backup doesn't exist.\n";
    }
    if ($dbnames[$row[0]]) {
      print "$house: multiple listings of " . $row[0] . " in GLOBAL_archive_data\n";
    }
    $dbnames[$row[0]]++;
  }
  foreach ($dbnames as $backup => $num) {
    if (!$num) {
      print "$house: $backup does not appear in GLOBAL_archive_data\n";
    }
  }
}
  
