<?php
require_once('default.inc.php');
if (!array_key_exists('backup_1',$_REQUEST) ||
    !array_key_exists('backup_2',$_REQUEST)) {
  $backups = get_backup_dbs();
  print "<form method=get action='" . this_url() . "'>";
  for ($ii = 1; $ii < 3; $ii++) {
    print "Backup $ii: <select name='backup_$ii'>\n";
    foreach ($backups as $opt) {
      print "<option " . escape_html($opt) . ">" . escape_html($opt) . "\n";
    }
    print "</select>&nbsp;&nbsp;";
  }
  print "<input type=submit value='Compare'>\n</form>";
  exit;
}
$backup_1 = $_REQUEST['backup_1'] . '_';
$backup_2 = $_REQUEST['backup_2'] . '_';
for ($ii = 1; $ii < 3; $ii++) {
  $archive = $archive_pre . $GLOBALS["backup_$ii"];
  if (table_exists('wanted_shifts')) {
    $wanted_row = $db->GetRow("select count(*) as `ct` from " .
                              bracket($archive . 'wanted_shifts'));
    $db_props['number of shift preferences submitted'] = $wanted_row['ct'];
  }
  else {
    $db_props['number of shift preferences submitted '] = -1;
  }
  if (table_exists('master_shifts')) {
    $db_props['assigned shifts'] = 0;
    foreach ($days as $day) {
      $master_row = $db->GetRow("select count(*) as `ct` from " .
                                bracket($archive . 'master_shifts') .
                                " where `$day` is not null and `$day` != ?",
                                array($dummy_string));
      $db_props['assigned shifts'] += $master_row['ct'];
    }
  }
  else {
    $db_props['assigned shifts'] = null;
  }
  if (table_exists('static_data')) {
    $db_props['semester start'] = get_static('semester_start');
  }
  else {
    $db_props['semester start'] = null;
  }
  $db_props['current week'] = get_cur_week();
  print "Backup $ii: ";
  print_r($db_props);
  print "<br/>\n";
}

rs2html($db->Execute("select 'Backup 1' as `which`, `$archive_pre{$backup_1}house_list`.`member_name` from " .
                     "`$archive_pre{$backup_1}house_list` left join `$archive_pre{$backup_2}house_list` " .
                     "on `$archive_pre{$backup_1}house_list`.`member_name` = `$archive_pre{$backup_2}house_list`.`member_name` where `$archive_pre{$backup_2}house_list`.`member_name` is null union " .
                     "select 'Backup 2' as `which`, `$archive_pre{$backup_2}house_list`.`member_name` from " .
                     "`$archive_pre{$backup_2}house_list` left join `$archive_pre{$backup_1}house_list` " .
                     "on `$archive_pre{$backup_2}house_list`.`member_name` = `$archive_pre{$backup_1}house_list`.`member_name` where `$archive_pre{$backup_1}house_list`.`member_name` is null order by `which`, `member_name`"));
$mod_1 = "`$archive_pre{$backup_1}modified_dates`";
$mod_2 = "`$archive_pre{$backup_2}modified_dates`";
rs2html($db->Execute("select 'Backup 1' as `which`, $mod_1.`table_name`, $mod_1.`mod_date`, " .
                     "$mod_2.`mod_date` as `mod_date2`, $mod_1.`mod_date` < $mod_2.`mod_date` as `older` from $mod_1 left join $mod_2 on " .
                     "$mod_1.`table_name` = $mod_2.`table_name` where " .
                     "$mod_1.`table_name` is null or $mod_1.`mod_date` != $mod_2.`mod_date` union " .
                     "select 'Backup 2' as `which`, $mod_2.`table_name`, $mod_1.`mod_date`, " .
                     "$mod_2.`mod_date` as `mod_date2`, $mod_1.`mod_date` < $mod_2.`mod_date` as `older` from $mod_2 left join $mod_1 on " .
                     "$mod_1.`table_name` = $mod_2.`table_name` where " .
                     "$mod_1.`table_name` is null order by `table_name`"));
?>
