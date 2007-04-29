<?php
/*
if (array_key_exists('logout',$_REQUEST)) {
  setcookie('member_name',null,time()-3600);
}
else {
  if (!array_key_exists('member_name',$_REQUEST)) {
    setcookie('member_name','Janak',time()+3600);
  }
  print_r($_REQUEST);
}
<pre>
<form method=post action='utility_script.php'>
<input name="testing'%20one" value=4>
<a href='utility_script.php?logout'>Log out</a>
</form>
#?php
#print_r($_SERVER);
#exit;
#print_r($_REQUEST);
#print_r($_POST);
#print_r($GLOBALS);
#exit;
*/
$php_includes = '../php_includes/';
require_once('../php_includes/janakdb.inc.php');
$houses = array('ath','aca','caz','clo','con','dav','euc','hip','hoy',
		'kid','kng','lot','rid','she','stb','wil','wol','co');
#$houses = array('rid','she','stb','wil','wol','co');
#$houses = array('hoy');
#janak_fatal_error_reporting(0);
#$db->SetFetchMode(ADODB_FETCH_NUM);
#$houses = array('kng');
#require_once('../public_html/admin/create_all_tables.php');
#$creates = array('master_shifts','wanted_shifts','house_list','password_table','personal_info',
#                 'house_info','workshift_description','static_data','fining_periods',
#                 'fining_data','fining_data_totals','master_week','modified_dates',
#                 'elections_record','current_voting_lock','voting_record','votes','points',
#                 'elections_attribs','elections_text','userconf_data','elections_log',
#                 'privilege_table','session_data','special_fining','static_text');
foreach ($houses as $house) {
  $db->debug = true;
  $db->Connect('localhost',"usca_janak$house","workshift","usca_janak$house");
  print "<h1>$house</h1>";
#  $db->Execute("alter table `votes` modify `option_choice` longtext default null");
#  $res = $db->Execute("select `member_name`,`race_name`,`option_choice` from `elections_attribs`, `votes` where length(`option_choice`) > 98 and `elections_attribs`.`autoid`
#= `votes`.`option_name`");
#  $cur_member = null;
#  while ($row = $res->FetchRow()) {
#    if  ($row['member_name'] != $cur_member) {
#      $cur_member = $row['member_name'];
#      print "<br>";
#      print "Voter id " . escape_html($cur_member) . ": <br>";
#    }
#    print substr($row['race_name'],4) . ": " . $row['option_choice'] . "<br>";
#  }
#  exit;
#  $row = $db->GetRow("select substr(`attrib_value`,-667,1) as `txt` from `elections_attribs` where `attrib_name` = 'member_comments' and `election_name` = '2006_fall_lizard_name'");
#  $html_ent_tables["\x92"] = 'hi there';
#  print strtr($row['txt'],$html_ent_tables);
# $row = $db->GetRow("select `attrib_value` as `txt` from `elections_attribs` where `attrib_name` = 'member_comments' and `election_name` = '2006_fall_lizard_name'");
 
#  print escape_html($row['txt']);
#  exit;
#  print ord($row['txt']) . "<br>";
#  exit;
#  $row = $db->GetRow("select `attrib_value` as `txt` from `elections_attribs` where `attrib_name` = 'member_comments' and `election_name` = '2006_fall_lizard_name'");
#  print escape_html($row['txt']);
  
#  $res = $db->Execute("select `race_name`,`attrib_value` from `elections_attribs` where length(`attrib_value`) and `attrib_name` = 'member_comments' and `election_name` = '2007-spring_Manager Evaluations: 1st VOCs'");
#  print "<pre>";
#  while ($row = $res->FetchRow()) {
#    print $row['race_name'] . "\n***\n" . $row['attrib_value'] . "\n---\n";
#  }
#  $db->Execute("update `elections_attribs` set `attrib_value` = `autoid` where `attrib_name` = 'race_name'");
#  $db->Execute("insert into `fining_data_totals` (`member_name`) select `member_name` from `house_list`");
#  $res = $db->Execute("show table status");
#  $sz = 0;
#  while ($row = $res->FetchRow()) {
#    $sz += $row['Data_length'];
#  }
#  print "<h4>Total size: $sz</h4>\n";
#  $db->Execute("alter table `house_info` add column `privacy` int default null");
#  $db->Execute("alter table `master_week` add column `start_time` time default null, add column `end_time` time default null");
#foreach ($creates as $fn) {
#  $temp = 'create_' . $fn;
#  print "<h4>$fn</h4>";
#  $temp();
#}
#  $db->Execute("alter table `house_info` drop column `submit_date`");
#  $db->Execute("alter table `fining_data` add column `refundable` int default 1");
#  $db->Execute("delete from `static_text`");
#  $res = $db->Execute("show tables like 'week\_%'");
#  while ($row = $res->FetchRow()) {
#    if ($row[0] == 'week_1') {
#      continue;
#    }
#    if (substr($row[0],-7) == '_totals') {
#      continue;
#    }
#    $db->Execute("alter table `" . $row[0] . "` add column `online_signoff` timestamp default 0");
#    $db->Execute("alter table `" . $row[0] . "` add column `verifier` varchar(50) default null");
#  }
}
exit;
#  $res1 = $db->Execute("select `member_name` from `voting_record` where `election_name` = ? order by `autoid`",
#                       array("2006_fall_Pioneer_Award_Nominee"));
#  $res2 = $db->Execute("select `option_choice` from `votes` where `election_name` = ? order by `autoid`",
#                       array("2006_fall_Pioneer_Award_Nominee"));
#  while (($row1 = $res1->FetchRow()) && ($row2 = $res2->FetchRow())) {
#    print escape_html($row1[0]) . ": " . escape_html($row2[0]) . "<br>";
#  }
#  $db->Execute("drop table `privilege_table`");
#  $db->Execute("drop table `session_data`");
#  $db->Execute("alter table `elections_record` add column `end_date2` int(10) unsigned default null");
#  $db->Execute("update `elections_record` set `end_date2` = unix_timestamp(`end_date`)");
#  if ($house != 'stb') {
#    $db->Execute("drop table `elections_record`");
#  }
  if (table_exists("points")) {
    rs2html($db->Execute("select * from points"));
  }
#  $res = $db->Execute("show tables like 'week\_1%'");
#  while ($row = $res->FetchRow()) {
#    if ($row[0] == 'week_1') {
#      continue;
#    }
#    if (substr($row[0],-7) == '_totals') {
#      continue;
#    }
#    $db->Execute("update `" . $row[0] . "` set `date` = adddate(`date`,interval 1 day)");
#  }
#  $db->Execute("delete from `static_data` where `var_name` = 'zero_hours_silly'");
  /*  create_special_fining();
  $res = $db->Execute("select `member_name` from `special_fining`");
  $cur = array();
  while ($row = $res->FetchRow()) {
    $cur[$row['member_name']] = true;
  }
  $temp = get_houselist();
  $houselist = array();
  foreach ($temp as $person) {
    if (!isset($cur[$person])) {
      $houselist[] = array($person);
    }
  }
  if (count($houselist)) {
    $db->Execute("insert into `special_fining` (`member_name`) values (?)",
                 $houselist);
  }*/
#  $row = $db->GetRow("select `attrib_value` from `elections_attribs` where `attrib_name` " .
#                     " = 'candidates' and `election_name` = '2006_fall_lizard_name'");
#  $row['attrib_value'] = join("\n",explode("\0",$row['attrib_value']));
#  $db->Execute("update `elections_attribs` set `attrib_value` = ? where `attrib_name` = 'candidates' " .
#               " and `election_name` = '2006_fall_lizard_name'",$row['attrib_value']);
#  exit;
#  if (table_exists('modified_dates')) {
#    rs2html($db->Execute("select * from `modified_dates` order by mod_date desc limit 1"));
#  }
#  $db->SetFetchMode(ADODB_FETCH_NUM);
#  $db->debug = true;
#  if ($house == 'lot') {
#    create_master_shifts();
#  };
#  create_master_week();
#  janak_fatal_error_reporting(0);
#  if (table_exists('wanted_shifts')) {
#    rs2html($db->Execute("select `member_name`, count(*) from `wanted_shifts` group by `member_name`"));
#  }
#  if (table_exists('week_0')) {
#$db->Execute("alter table `week_0` change column `shift_id` `shift_id` int(11) default null");
#  }
#  rs2html($db->Execute("select * from `modified_dates` where `table_name` = 'week_0'"));
#  rs2html($tblres = $db->Execute("show tables like 'week\_%'"));
#  while ($tblrow = $tblres->FetchRow()) {
#    print $tb
#  }
#}

exit;
#$houses = array('rid');
foreach ($houses as $house) {
  $db->Connect('localhost',"usca_janak$house","workshift","usca_janak$house");
  print "<h1>$house</h1>";
#  rs2html($db->Execute("select * from master_shifts"));
  $db->SetFetchMode(ADODB_FETCH_NUM);
#  $db->debug = true;
  if (table_exists('house_info')) {
    $tblres = $db->Execute("show tables like ?",array('zz\_archive\_%' . quote_mysqlreg('house_info')));
    $posses = array();
    while ($row = $tblres->FetchRow()) {
      $posses[] = $row[0];
    }
    $db->SetFetchMode(ADODB_FETCH_ASSOC);
    if (count($posses)) {
      $res = $db->Execute("select * from `house_info` where (`email` is null or `email` = '') and (`room` is null or `room` = '') and (`phone` is null or `phone` = '')");
      while ($row = $res->FetchRow()) {
        foreach ($posses as $tbl) {
          $possrow = $db->GetRow("select * from `$tbl` where `member_name` = ? " .
                                 "and ((`room` != '' and `room` is not null) or "
                                 . "(`email` != '' and `email` is not null) or " .
                                 "(`phone` != '' and `phone` is not null))",
                                 array($row['member_name']));
          if (!is_empty($possrow)) {
            $db->debug = true;
            $db->Execute("update `house_info` set `room` = ?, `email` = ?, " .
                         "`phone` = ? where `member_name` = ?",
                         array($possrow['room'],$possrow['email'],$possrow['phone'],$possrow['member_name']));
            $db->debug = false;
            break;
          }
        }
      }
    }
  }
}          
exit;
#  while ($row = $tblres->FetchRow()) {
#    $db->Execute("alter table `{$row[0]}` add column `description` varchar(50) default null after `end_time`");   
#    $db->Execute("alter table `{$row[0]}` add column `category` varchar(50) default null after `description`");
#  }
#    $subres = $db->Execute("select `av_0`,av_1,av_2,av_3,av_4,av_5,av_6 from `{$row[0]}`")
#    for ($ii = 0; $ii < 7; $ii++) {
#      $db->Execute("alter table `{$row[0]}` change column `av_$ii` `av_$ii` bigint default 0");
#      $db-
#    }
#  }
#    $db->Execute("insert into `wanted_shifts` (`member_name`,`shift`,`day`,`floor`,`rating`) " .
#                 "select `member_name`, `shift`, `day`, `floor`, 0 as `rating` from `unwanted_shifts`");
#    if ($house != 'ath') {
#      $db->Execute("alter table `wanted_shifts` add column `rating` int(11) default null after `floor`");
#    }
#    $db->Execute("update `wanted_shifts` set `rating` = 2");
#  }
#  while ($tblrow = $tblres->FetchRow()) {
#    $colres = $db->Execute('show columns from ' . bracket($tblrow[0]));
#    while ($colrow = $colres->FetchRow()) {
#      if ($colrow[0] == 'member_name') {
#         $res = $db->Execute("select * from " . bracket($tblrow[0]) . " where `member_name` = ?",
#                             array("Hart, Hannah"));
#         if (!is_empty($res)) {
#           print $tblrow[0] . "<br>";
#           rs2html($res);
#         }
#        continue;
#      }
#    }
#  }
#}
#  if (!is_empty($db->Execute("show tables like 'fining\_periods'"))) {
#    $db->Execute("alter table `fining_periods` modify column `fining_rate` double default null");
#  }
# rs2html($db->Execute("select * from `fining_data_totals`"));
# exit;
#  rs2html($db->Execute("select * from `house_list` where `member_name` = ?",
#               array("' union all select * from `password_table` where ''='")));
#  exit;
  
#  $res = $db->Execute("show tables like '%week\_s0'");
#  while ($row = $res->FetchRow()) {
#    print($row[0] . "<br>\n");
#  }
#  exit;
#    if (strlen($row['0']) < 8) {
#      $db->Execute("drop table $row[0]");
#    }
#  }
#  $res = $db->Execute("select `member_name` from `password_table` where `passwd` is null");
#  while ($row = $res->FetchRow()) {
#    $oldrow = $db->GetRow("select `passwd` from `zz_archive_2006_spring_no_shifts_password_table` where " .
#                          "`member_name` = ?",array($row['member_name']));
#    if (!is_empty($oldrow)) {
#      $db->Execute("update `password_table` set `passwd` = ? where `member_name` = ?",
#                   array($oldrow['passwd'],$row['member_name']));
#    }
#  }
#    
#}
#exit;
#  if (table_exists('modified_dates')) {
#    rs2html($db->Execute("select * from `modified_dates` order by mod_date desc limit 1"));
#  }
#}
exit;

#}
#  if (!is_empty($db->Execute("show tables like 'master\_shifts'"))) {
#    $db->Execute("alter table `master_shifts` add column `description` longtext default null");
#  }
#}
  /*  $res = $db->Execute("select " . join(",",$days) . " from `master_shifts`");
  $short_names = array();
  while ($row = $res->FetchRow()) {
    foreach ($row as $mem) {
      if ($mem == 'XXXXX' || !$mem) {
        continue;
      }
      $short_names[] = $mem;
    }
  }
  $short_names = array_unique($short_names);
  foreach ($short_names as $mem) {
    $names = split(' ',$mem);
    $names[] = '';
    $row = $db->GetRow("select `member_name` from `house_list` where `member_name` like '{$names[1]}%, {$names[0]}'");
    if (is_empty($row)) {
      print("No match for $mem<br>\n");
      continue;
    }
    foreach ($days as $day) {
      $db->Execute("update `master_shifts` set `$day` = ? where `$day` = ?",
                   array($row[0],$mem));
    }
  }*/
#  $res = $db->Execute("show tables like 'week\_%\_totals'");
#  while ($row = $res->FetchRow()) {
#    $row2 = $db->GetRow("show full columns from `" . $row[0] . "`");
#    $db->Execute("alter table " . $row[0] . " modify column `autoid` int(11) not null auto_increment first");
#  }
#  system("htpasswd -b -d /home/usca/domains/usca.org/public_html/workshift/php_includes/apache_users {$house}laborczar {$house}laborczar");
#  system("rm ../$house");
#  system("ln -s /home/usca/domains/usca.org/public_html/workshift/public_html ../$house");
#}
?>
