<?php 
$body_insert = '';
require_once('default.inc.php');
create_and_update_fining_data_totals();
$table_name = 'House Fines';
$restrict_cols = array(0);
$tot_weeks = get_static('tot_weeks',18);
$week_num = get_cur_week();
$fining_rate = get_static('fining_rate',11.7);
if ($fining_rate <= 0) {
  $fining_rate = 12;
  set_static('fining_rate',$fining_rate);
}
$table_edit_query = '';
$sql_begin = "select `{$archive}weekly_totals_data`.`member_name`";
$sql_mid = ", `{$archive}fining_data_totals`.`fines` as `Other Fines` " .
"from `{$archive}weekly_totals_data`, `{$archive}fining_data_totals` ";
$sql_end = " where `{$archive}weekly_totals_data`.`member_name` = " .
"`{$archive}fining_data_totals`.`member_name`";
$col_names = array('member_name');
$col_formats = array();
for ($ii = 0; $ii < $week_num; $ii++) {
  create_and_update_week_totals($ii);
  $sql_begin .= ", `{$archive}week_{$ii}_totals`.`tot`-" .
    "`{$archive}weekly_totals_data`.`owed {$ii}` as `total {$ii}`";
  $sql_mid .= ", `{$archive}week_{$ii}_totals`";
  $sql_end .= " and `{$archive}weekly_totals_data`.`member_name` = " .
    "`{$archive}week_{$ii}_totals`.`member_name`";
  $col_names[] = "total $ii";
  $col_formats["fines $ii"] = 'money_table';
  $col_names[] = "fines $ii";
}
$table_edit_query = $sql_begin . $sql_mid . $sql_end . " order by `member_name`";
$col_names[] = "Total";
if ($nonzeroed_total = get_static('nonzeroed_total_hours',null,$archive)) {
  $col_names[] = 'Nonzeroed Total';
}
if ($cash_hours_auto = get_static('cash_hours_auto',false,$archive)) {
  $cash_res = $db->Execute("select `{$archive}house_list`.`member_name`, sum(`fine`) as `val` " .
                           "from `{$archive}house_list` left join `{$archive}fining_data`" .
                           " on `{$archive}house_list`.`member_name` = `{$archive}fining_data`.`member_name` " .
                           "and `fine` > 0 and `refundable` " .
                           "group by `member_name` order by `member_name`");
  $cash_maxes = array();
  while ($cash_row = $cash_res->FetchRow()) {
    $cash_maxes[$cash_row['member_name']] = $cash_row['val']/$fining_rate;
  }
  $col_names[] = "Total after Cash-in";
  $col_names[] = "Fine Rebate";
  $col_formats['Fine Rebate'] = 'money_table';
}
$col_names[] = "Other Fines";
$col_formats['Other Fines'] = 'money_table';
$col_names[] = "Total Fines";
$col_formats['Total Fines'] = 'money_table';
$col_formats = array_merge(array_flip($col_names),$col_formats);
$weekly_fining = get_static('weekly_fining',false);

if ($weekly_fining) {
  $fining_zero_hours = get_static('fining_zero_hours',null,$archive);
}
$tot_weeks = get_static('tot_weeks',18);

$fining_buffer = get_static('fining_buffer',0,$archive);
$fining_floor = get_static('fining_floor',10,$archive);
$fining_doublefloor = get_static('fining_doublefloor',null,$archive);

$backup_fine_weeks = array();
$res = $db->Execute("select * from `{$archive}fining_periods` order by `week`");
while ($row = $res->FetchRow()) {
  foreach (array('fining_doublefloor','fining_floor','fining_buffer') as $attrib) {
    if (strlen($row[$attrib]) == 0) {
      $row[$attrib] = $GLOBALS[$attrib];
    }
  }
  $backup_fine_weeks[$row['week']] = $row;
}

$backup_max_up_hours = get_static('max_up_hours',null,$archive);
$max_up_hours_fining = get_static('max_up_hours_fining',null);
if (!strlen($max_up_hours_fining)) {
  $max_up_hours_fining = $backup_max_up_hours;
}
$fining_percent_fine = get_static('fining_percent_fine',null);
if (!strlen($fining_percent_fine)) {
  $fining_percent_fine = 100;
}
$zero_partial = get_static('fining_zero_partial',null);

if (!$archive || table_exists('special_fining')) {
$special_res = $db->Execute("select * from `{$archive}special_fining`");
$special_fining = array();
while ($special_row = $special_res->FetchRow()) {
  $special_fining[$special_row['member_name']] = $special_row;
}
}
function mung_whole_row(&$row) {
  global $week_num, $backup_fine_weeks, $fining_floor, $fining_buffer,
    $fining_rate, $fining_doublefloor, $fining_zero_hours,
    $javascript_pre,$col_styles, $weekly_fining, $nonzeroed_total, 
    $backup_max_up_hours, $cash_hours_auto, $res, $cash_maxes,
    $fining_percent_fine,$zero_partial, $special_fining, $max_up_hours_fining,
    $tot_weeks;
  static $key_weeks = null;
  $cur_fine = 0;
  $cash_hours = 0;
  $row['Total Fines'] = $row['Other Fines'];
  $row['Total'] = 0;
  if ($nonzeroed_total) {
    $row['Nonzeroed Total'] = 0;
  }
  $fine_weeks = $backup_fine_weeks;
  if (isset($special_fining[$row['member_name']])) {
    $new_fine_weeks = array();
    if (!$key_weeks) {
      $key_weeks = array_keys($backup_fine_weeks);
    }
    for ($kk = 1; $kk <= 5; $kk++) {
      $new_week = $special_fining[$row['member_name']]["fine_week_$kk"];
      if ($new_week != -1 && $new_week < $week_num &&
          $new_week != ($old_week = $key_weeks[$kk-1])) {
        if (strlen($new_week)) {
          $new_fine_weeks[$new_week] = 1;
          $fine_weeks[$new_week] = $backup_fine_weeks[$old_week];
        }
        if (!isset($new_fine_weeks[$old_week])) {
          unset($fine_weeks[$old_week]);
        }
      }
    }
  }
  for ($ii = 0; $ii < $week_num; $ii++) {
    $row['Total'] += $row["total $ii"];
    $end_fine = isset($fine_weeks[$ii]);
    if ($end_fine) {
      $max_up_hours = $max_up_hours_fining;
    }
    else {
      $max_up_hours = $backup_max_up_hours;
    }
    if ($ii == $tot_weeks) {
      $fining_percent_fine = 100;
      $zero_partial = 0;
    }
    if ($nonzeroed_total) {
      $row['Nonzeroed Total'] += $row["total $ii"];
      if ($max_up_hours && $row['Nonzeroed Total'] > $max_up_hours) {
        $row['Nonzeroed Total'] = $max_up_hours;
      }
    }
    if ($max_up_hours && $row['Total'] > $max_up_hours) {
      if ($cash_hours_auto) {
        $cash_hours += $row['Total']-$max_up_hours;
      }
      $row['Total'] = $max_up_hours;
    }
    $row["total $ii"] = $row['Total'];
    $row["fines $ii"] = 0;
    if ($end_fine || $weekly_fining) {
      if ($end_fine) {
        $fin_floor = $fine_weeks[$ii]['fining_floor'];
        $fin_rate = $fine_weeks[$ii]['fining_rate'];
        $fin_buffer = $fine_weeks[$ii]['fining_buffer'];
        $fin_doublefloor = $fine_weeks[$ii]['fining_doublefloor'];
      }
      else {
        $fin_floor = $fining_floor;
        $fin_rate = $fining_rate;
        $fin_buffer = $fining_buffer;
        $fin_doublefloor = $fining_doublefloor;
      }
      if (!is_numeric($fin_floor)) {
        $fin_floor = 0;
      }
      if (!is_numeric($fin_buffer)) {
        $fin_buffer = 0;
      }
      if (!is_numeric($fin_rate) || !($fin_rate > 0)) {
        $fin_rate = $fining_rate;
      }
      $temptotal = $row['Total'];
      $temptotal +=$fin_floor;
      $temptotal *= -1;
      if ($temptotal > $fin_buffer) {
        $row["fines $ii"] = $temptotal*$fin_rate*$fining_percent_fine/100;
        if ($fin_doublefloor && $fin_doublefloor >= $fin_floor) {
          $fin_doublefloor -= $fin_floor;
	  if ($temptotal >= $fin_doublefloor) {
            $row["fines $ii"] += ($temptotal-$fin_doublefloor)*$fin_rate;
          }
        }
      }
      else {
        $row["fines $ii"] = 0;
      }
      $row["Total Fines"] += $row["fines $ii"];
      if ($cash_hours_auto) {
        $cash_maxes[$row["member_name"]] += $row["fines $ii"]/$fining_rate;
      }
      if ($ii < $week_num-1) {
        if ($row['Total'] < -$fin_floor) {
          if (($end_fine && $fine_weeks[$ii]['zero_hours']) || 
              ($weekly_fining && $fining_zero_hours)) {
            if (strlen($zero_partial)) {
              $row['Total'] = -$fin_floor + ($row['Total']+$fin_floor)*$zero_partial/100;
            }
            else {
              $row['Total'] = -$fin_floor;
            }
          }
        }
      }
    }
    if ($cash_hours_auto) {
      $cash_max = $cash_maxes[$row['member_name']];
      $row['Total after Cash-in'] = $row['Total'];
      if ($max_up_hours && $cash_hours > 0) {
        $row['Fine Rebate'] = $fining_rate*min($cash_hours,$cash_max);
        $cash_max-=$cash_hours;
      }
      else {
        $row['Fine Rebate'] = 0;
      }
      if ($row['Total after Cash-in'] > 0 && $cash_max > 0) {
        $rebate_hours = min(max($row['Total after Cash-in'],0),$cash_max);
        $row['Fine Rebate'] += $fining_rate*$rebate_hours;
        $row['Total after Cash-in'] -= $rebate_hours;
      }
    }
  }
}

$mung_whole_row = 'mung_whole_row';

if (!isset($_REQUEST['download'])) {
    if (!isset($body_insert)) {
    $body_insert = '';
  }
  $body_insert .= "<div class='print_hide'><a href='" . escape_html($_SERVER['REQUEST_URI']);
  if ($archive) {
    $body_insert .= escape_html('&');
  }
  else {
    $body_insert .= escape_html('?');
  }
  $body_insert .= "download'>Download to Excel</a>\n";
  $backup_dbs = array_reverse(get_backup_dbs());
  if (count($backup_dbs)) {
    $body_insert .= "<form method=get action='" .
      escape_html($_SERVER['REQUEST_URI']) . "'>" .
      "<select name='archive'>\n<option>\n";
    foreach ($backup_dbs as $backup) {
      $body_insert .= "<option>" . escape_html($backup) . "\n";
    }
    $body_insert .= "<input type=submit class=button value='View Archive'>" .
      "</form>\n";
  }
  else {
    $body_insert .= "<br>\n";
  }
  $body_insert .= "</div>";
  if (count($backup_fine_weeks) > 0) {
    $body_insert .= "Fining period(s) are at week ";
    $first_flag = true;
    foreach ($backup_fine_weeks as $fine_week) {
      if ($first_flag) {
        $first_flag = false;
      }
      else {
        $body_insert .= ", ";
      }
      $body_insert .= escape_html($fine_week['week']);
    }
    $body_insert .= "<p>";
  }
  require_once("$php_includes/table_print.php");
  exit;
}
export_csv_file('House Fines',$table_edit_query,$col_formats,$mung_whole_row);
?>
