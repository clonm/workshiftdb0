<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
$db->debug = true; 
#$houses = array('nsc');
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  $res = $db->Execute("select `archive` from `GLOBAL_archive_data` where not `creation`");
  while ($row = $res->FetchRow()) {
    $matches = array();
    //setting this makes all functions think we're in this archived db
    //this array has all salient information
    //did the user not name this him/herself (conforms to autoname scheme)?
    if (preg_match('/^' . '([0-9]{4,4})_' .
      '([0-9]{2,2})_([0-9]{2,2})_' .
      '([0-9]{2,2})_([0-9]{2,2})_' .
      '([0-9]{2,2})$/',
      $row['archive'])) {
        print "doing " . $row['archive'];
        $time = str_replace('_','-',$row['archive']);
        $db->Execute("update `GLOBAL_archive_data` set `creation` = ? " .
          "where `archive` = ?",array($time,$row['archive']));
      }
  }
}

