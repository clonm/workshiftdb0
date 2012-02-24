<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
$db->debug = true;
#$houses = array('nsc');
foreach ($houses as $house) {
  $db->Connect('localhost',"bsccoo5_wkshift","workshift","bsccoo5_workshift$house");
  print "<h1>$house</h1>";
  $db->Execute("drop table old_wanted_shifts");
  $db->Execute("rename table `wanted_shifts` to `old_wanted_shifts`, `new_wanted_shifts` to `wanted_shifts`");
  /* $res = $db->Execute("select master_shifts.workshift, old_master_shifts.workshift from master_shifts left join old_master_shifts on master_shifts.autoid = old_master_shifts.autoid where master_shifts.workshift != old_master_shifts.workshift"); */
  /* while ($row = $res->FetchRow()) { */
  /*   print_r($row); */
  /* } */
/*   $db->Execute("create table new_wanted_shifts ( */
/*   `autoid` int(11) NOT NULL auto_increment, */
/*   `member_name` varchar(50) NOT NULL default '', */
/*   `shift_id` varchar(50) NOT NULL default '', */
/*   `is_cat` tinyint(1) default '0', */
/*   `rating` int(11) default NULL, */
/*   PRIMARY KEY  (`autoid`) */
/* ) ENGINE=InnoDB DEFAULT CHARSET=latin1"); */
/*   $db->Execute("insert into new_wanted_shifts (member_name,shift_id,rating,is_cat) select member_name, shift as shift_id, rating, true from wanted_shifts where day = 'category'"); */
/*   print $db->Affected_Rows(); */
/*   $db->Execute("insert into new_wanted_shifts (member_name,shift_id,rating) select member_name, master_shifts.autoid as shift_id, rating from wanted_shifts,master_shifts where day = 'shift' and shift = master_shifts.workshift"); */
/*   print $db->Affected_Rows(); */
}

