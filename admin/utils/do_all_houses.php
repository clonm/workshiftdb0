<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
$db->debug = true;
#$houses = array('nsc');
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  /* $res = $db->Execute("select master_shifts.workshift, old_master_shifts.workshift from master_shifts left join old_master_shifts on master_shifts.autoid = old_master_shifts.autoid where master_shifts.workshift != old_master_shifts.workshift"); */
  /* while ($row = $res->FetchRow()) { */
  /*   print_r($row); */
  /* } */
  /* $db->Execute("create table old_master_shifts like master_shifts"); */
  /* $db->Execute("insert old_master_shifts select * from master_shifts"); */
  var_dump($db->Execute("alter table master_shifts drop column floor"));
}

