<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
$db->debug = true; 
foreach ($houses as $house) {
 $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
             $db_basename . $house);
 print "<h1>$house</h1>";
  $db->SetFetchMode(ADODB_FETCH_NUM);
  $res = $db->Execute("show tables like '%master\_shifts'");
  while ($row = $res->FetchRow()) {
    $cols = $db->Execute("describe `" . $row[0] . "`");
    $cols->FetchRow();
    $cols->FetchRow();
    $field = $cols->FetchRow();
    if ($field[0] == 'floor') {
      $db->Execute("alter table `" . $row[0] . "` drop column `floor`");
    }
}
}
