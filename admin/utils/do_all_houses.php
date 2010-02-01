<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
#$db->debug = true; $houses = array('caz');
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  $res = $db->Execute('SELECT ' . bracket('election_name') . ' FROM ' .
    bracket('elections_record') . 
    ' WHERE unix_timestamp() < `end_date`');
  if (!is_empty($res)) {
    print "has open elections";
  }
}

