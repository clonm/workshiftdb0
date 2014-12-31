<?php
require_once('default.admin.inc.php');
janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
$db->debug = true;
#$houses = array('nsc');
foreach ($houses as $house) {
  print "<h1>$house</h1>";
  $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
    "$db_basename$house");
/*  $db->Execute("alter table `GLOBAL_archive_data` add column `semester_end` bit(1) default NULL"); */
  $archive = '';
$fining_buffer = get_static('fining_buffer',0,$archive);
$fining_floor = get_static('fining_floor',10,$archive);
$fining_doublefloor = get_static('fining_doublefloor',null,$archive);

$backup_fine_weeks = array();
$res = $db->Execute("select * from `{$archive}fining_periods` order by `week`");
while ($row = $res->FetchRow()) {
  foreach (array('fining_doublefloor','fining_floor','fining_buffer') as $attrib) {
    //if not set for this period, use the global setting
    if (!strlen($row[$attrib])) {
      $row[$attrib] = $GLOBALS[$attrib];
    }
  }
  $backup_fine_weeks[$row['week']] = $row;
}
  foreach ($backup_fine_weeks as $fining_period) {
    if ( $fining_period['zero_hours']) {
      print_r($fining_period);
      break;
    }
  }
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

