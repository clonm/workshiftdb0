<?php
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}

// include file which regenerates the views and subviews that 
//calculate the totals, if necessary.  Unfortunately, mysql can currently
//not create a view based on subqueries, so we need to create intermediate
//views for each week
//even more unfortunately, if we are in mysql 4, then there are no views,
//so we have to create the query from scratch
#$db->debug = true;

//are we going to be viewing an archived set's weekly totals?
if (array_key_exists('getarchive',$_REQUEST)) {
  ?>
<html><head><title>Archived weekly totals</title></head>
<body>
   Retrieve weekly totals of an archive:
<form action='<?=$_SERVER['REQUEST_URI']?>' method=GET>
<select name='archive'>
<?php
   $dbnames = get_backup_dbs();

print "<option>";
//go in reverse order, so most recently made database appears first
print implode("\n<option>",array_reverse($dbnames));
?>
</select><br>
<input type=submit value='Weekly totals for backup'></form></body></html>
<?php 
exit;
}

create_and_update_fining_data_totals();

$week_num = get_cur_week($archive);
//now we set some variables assuming the stream will eventually go down to
//table_edit.php, even though it might not.  There's also the tricky issue
//of fines to be worked out
//See table_edit.php for explanations of these variables
$order_exp = 'Name';

$table_name = $archive . 'weekly_totals';
//not used unless we're updating, but no harm in having it here
$table_name_update = $archive . 'weekly_totals_data';
$col_formats = array('member_name' => '');
$col_styles = array(0 => '');
$ctr = 1;
for ($ii = 0; $ii < $week_num; $ii++) {
  $col_formats["done $ii"] = '';
  $col_formats["blown $ii"] = '';
  $col_formats["week $ii"] = '';
  $col_formats["owed $ii"] = '';
  $col_styles[$ctr++] = '';
  $col_styles[$ctr++] = '';
  $col_styles[$ctr++] = '';
  $col_styles[$ctr++] = '';
  if ($ii+1 < $week_num) {
    $col_formats["total $ii"] = '';
    $col_styles[$ctr++] = '';
  }
}
$col_formats['Total'] = '';
$col_styles[$ctr++] = '';

$fining_rate = get_static('fining_rate',12);

$fining_buffer = get_static('fining_buffer',0,$archive);
$fining_floor = get_static('fining_floor',10,$archive);
$fining_doublefloor = get_static('fining_doublefloor',null,$archive);

$res = $db->Execute("select * from `{$archive}fining_periods` order by `week`");
$backup_fine_weeks = array();
$max_period = 0;
while ($row = $res->FetchRow()) {
  foreach (array('fining_doublefloor','fining_floor','fining_buffer') as $attrib) {
    if (strlen($row[$attrib]) == 0) {
      $row[$attrib] = $GLOBALS[$attrib];
    }
  }
  $backup_fine_weeks[$row['week']] = $row;
  if ($row['week'] < $week_num) {
    $max_period++;
  }
}

if (!$archive || table_exists('special_fining')) {
$special_res = $db->Execute("select * from `{$archive}special_fining`");
$special_fining = array();
while ($special_row = $special_res->FetchRow()) {
  $special_fining[$special_row['member_name']] = $special_row;
  if ($max_period < 5) {
    for ($ii = $max_period+1; $ii <= 5; $ii++) {
      if ($special_row["fine_week_$ii"] > -1 && 
          $special_row["fine_week_$ii"] < $week_num) {
        $max_period = $ii;
      }
    }
  }
}
}
while ($max_period < count($backup_fine_weeks)) {
  array_pop($backup_fine_weeks);
}

foreach ($backup_fine_weeks as $week => $junk) {
  $col_formats["week $week fines"] = 'money_table';
  $col_styles[$ctr++] = '';
}

if ($nonzeroed_total = get_static('nonzeroed_total_hours',null,$archive)) {
  $col_names[] = 'Nonzeroed Total';
  $col_styles[$ctr++] = '';
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
  $col_formats['Total after Cash-in'] = '';
  $col_names[] = "Total after Cash-in";
  $cashtotal_col = $ctr;
  $col_styles[$ctr++] = '';
  $col_names[] = "Fine Rebate";
  $col_styles[$ctr++] = '';
  $col_formats['Fine Rebate'] = 'money_table';
}
$col_formats["Other Fines"] = 'money_table';
$col_styles[$ctr++] = '';
$col_formats['Total Fines'] = 'money_table';
$total_fine_col = $ctr;
$col_styles[$ctr++] = '';

$weekly_fining = get_static('weekly_fining',false);
if ($weekly_fining) {
  $col_formats['notes'] = 'notes_fines';
}
else {
  $col_formats['notes'] = '';
}
$notes_col = $ctr;
$col_styles[$ctr++] = 'input';

$col_names = array_keys($col_formats);
$col_sizes = array_fill(0,count($col_formats)-1,2);
$col_sizes[] = 10;
$col_types = array_flip($col_names);

$restrict_cols = array(0);

$delete_flag = false;

if ($weekly_fining) {
function notes_fines($str,$num_rows,$jj) {
  global $col_styles, $notes_fines, $col_formats, $notes_col, $weekly_fining;
  if ($col_styles[$notes_col] != 'input') {
    $col_formats['notes'] = '';
    return $str;
  }
  if (substr_count($str,"\n") || $col_styles[$jj] === 'textarea') {
    return array("<textarea rows=3 cols=30 name='cell-{$num_rows}-{$jj}' id='cell-{$num_rows}-{$jj}'" . 
                 " class='{$col_styles[$jj]}'" .
                 " onChange='change_handler(this);' " .
                 "onBlur='blur_handler(this);' onFocus='focus_handler(this);' " .
                 "autocomplete=off>" . escape_html($str) . "</textarea>" . 
                 escape_html($notes_fines),
                 20+strlen($notes_fines)/2);
  }
  else {
    return array("<input name='cell-{$num_rows}-{$jj}' id='cell-{$num_rows}-{$jj}'" . 
                 " class='{$col_styles[$jj]}' " . 
                 "size='" . max(2,1+strlen($str)/2) . "' " .
                 " value='" . escape_html($str) . "' onChange='change_handler(this);' " .
                 "onBlur='blur_handler(this);' onFocus='focus_handler(this);' " .
                 "autocomplete=off>" . escape_html($notes_fines),
                 (max(4,2+strlen($str)) + strlen($notes_fines))/2);
  }
                 
}
}

if ($weekly_fining) {
  $fining_zero_hours = get_static('fining_zero_hours',null,$archive);
  if (!strlen($fining_zero_hours)) {
    $fining_zero_hours = 0;
  }
}
$tot_weeks = get_static('tot_weeks',18);

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
if (!strlen($zero_partial)) {
  $zero_partial = 0;
}


//it's too slow when we call a function for each column, so this
//does the whole row at once
function mung_whole_row(&$row) {
  global $week_num, $backup_fine_weeks, $fining_floor, $fining_buffer,
    $fining_rate, $fining_doublefloor, $fining_zero_hours,
    $javascript_pre,$col_styles, $weekly_fining, $nonzeroed_total, 
    $backup_max_up_hours, $cash_hours_auto, $res, $cash_maxes,
    $fining_percent_fine,$zero_partial, $special_fining, $max_up_hours_fining,
    $tot_weeks,$notes_col;
  static $key_weeks = null;
  $cash_hours = 0;
  $row['Total Fines'] = $row['Other Fines'];
  $javascript_pre = substr($javascript_pre,0,-9) . "other_fines[" . dbl_quote($row['member_name']) . "] = " . escape_html($row['Other Fines']) .
    ";\n</script>";
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
    for ($kk = 1; $kk <= count($key_weeks); $kk++) {
      $new_week = $special_fining[$row['member_name']]["fine_week_$kk"];
      if ($new_week != -1 && $new_week < $week_num &&
          $new_week != ($old_week = $key_weeks[$kk-1])) {
        if (strlen($new_week)) {
          $new_fine_weeks[$new_week] = $old_week;
          $fine_weeks[$new_week] = $backup_fine_weeks[$old_week];
        }
        if (!isset($new_fine_weeks[$old_week])) {
          unset($fine_weeks[$old_week]);
        }
      }
    }
  }
  $flag_notes = false;
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
      $row['Nonzeroed Total'] += $row["week $ii"];
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
        if (strlen($fining_percent_fine)) {
          $temptotal *= $fining_percent_fine/100;
        }
        $fine = $temptotal*$fin_rate;
        if ($fin_doublefloor && $fin_doublefloor >= $fin_floor) {
          $fin_doublefloor -= $fin_floor;
          $fine += ($temptotal-$fin_doublefloor)*$fin_rate;
        }
      }
      else {
        $fine = 0;
      }
      if ($fine) {
        $row['Total Fines'] += $fine;
        if ($end_fine) {
          if (isset($new_fine_weeks[$ii])) {
            $this_week = $new_fine_weeks[$ii];
          }
          else {
            $this_week = $ii;
          }
          $row["week $this_week fines"] =  $fine;
        }
        else {
          $flag_notes = true;
          $row["Other Fines"] += $fine;
          $weekly_fines[] = $ii;
        }
      }
      if ($cash_hours_auto) {
        $cash_maxes[$row["member_name"]] += $fine/$fining_rate;
      }
      if ($ii < $week_num-1) {
        if ($row['Total'] < -$fin_floor) {
          if (($end_fine && $fine_weeks[$ii]['zero_hours']) || 
              ($weekly_fining && $fining_zero_hours)) {
            $row['Total'] = -$fin_floor + ($row['Total']+$fin_floor)*$zero_partial/100;
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
  if ($flag_notes) {
    $str = "(also fined for week " . join(', ',$weekly_fines) . ")";
    if ($col_styles[$notes_col] == 'input') {
      $notes_fines =  $str;
    }
    else {
      $row["notes"] .= $str;
    }
  }
}

$mung_whole_row = 'mung_whole_row';

$onload_function = 'initialize_weekly_totals';

$javascript_pre = <<<JAVASCRIPT
<script type='text/javascript'>\nvar week_num = $week_num; 
function initialize_weekly_totals() {
  for (var ii = 1; ii < 5*week_num-2; ii++) {
    if ((ii == 5*week_num-6) || (ii == 5*week_num-7)) {
      continue;
    }
    document.getElementById('checkhide'+ii).checked = false;
    hide_col(ii,'none');
  }
}
var other_fines = new Array();
</script>
JAVASCRIPT;

if ($MYSQL_VERSION >= 41000 && false) {
  //make a gigantic query with 3 subqueries for every week
  $table_edit_query = 
<<<STARTQUERY
    select `{$archive}weekly_totals_data`.`autoid` as `autoid`,
    `{$archive}weekly_totals_data`.`member_name` AS `member_name`,
STARTQUERY
    ;
  for ($ii = 0; $ii < $week_num; $ii++) {
    $table_edit_query .= 
<<<CONTQUERY
ifnull((select sum(greatest(0,hours)) from `{$archive}week_$ii`
        where `{$archive}week_$ii`.member_name = `{$archive}weekly_totals_data`.member_name),
             0) as `done $ii`,
      ifnull((select sum(least(0,hours)) from `{$archive}week_$ii`
              where `{$archive}week_$ii`.member_name = `{$archive}weekly_totals_data`.member_name),
             0) as `blown $ii`,
      ifnull((select sum(hours) from `{$archive}week_$ii` 
              where `{$archive}week_$ii`.member_name = `{$archive}weekly_totals_data`.member_name),
             0) as `week $ii`,
      ifnull(`{$archive}weekly_totals_data`.`owed $ii`,0) as `owed $ii`, `{$archive}week_{$ii}_totals`.`tot`-`{$archive}weekly_totals_data`.`owed $ii` as `total $ii`,
CONTQUERY
      ;
  }
  $table_edit_query .= "0 as `Total`, ";
  $table_edit_query .= join('',array_map('table_period_name',$backup_fine_weeks)) .
<<<ENDQUERY
    0 as `Other Fines`, 0 as `Total Fines`, `{$archive}weekly_totals_data`.`notes`
from `{$archive}weekly_totals_data` order by `{$archive}weekly_totals_data`.`member_name`
ENDQUERY
    ;
}
else {
#  $db->debug = true;
  $table_edit_query = 
<<<STARTQUERY
    select `{$archive}weekly_totals_data`.`autoid` as `autoid`,
    `{$archive}weekly_totals_data`.`member_name` AS `member_name`,
STARTQUERY
    ;
  $mid_query = "0 as `Total`, ";
  $mid_query .= join('',array_map('table_period_name',$backup_fine_weeks)) .
<<<ENDQUERY
`{$archive}fining_data_totals`.`fines` as `Other Fines`, 0 as `Total Fines`, 
    `{$archive}weekly_totals_data`.`notes` from `{$archive}weekly_totals_data`, `{$archive}fining_data_totals` 
ENDQUERY
;
  $end_query = " where ";
  for ($ii = 0; $ii < $week_num; $ii++) {
    $table_edit_query .= <<<FIELDS
`{$archive}week_{$ii}_totals`.`done` as `done $ii`,`{$archive}week_{$ii}_totals`.`blown` as `blown $ii`,
`{$archive}weekly_totals_data`.`owed $ii` as `owed $ii`,`{$archive}week_{$ii}_totals`.`tot` as `week $ii`,
                                                                               `{$archive}week_{$ii}_totals`.`tot`-`{$archive}weekly_totals_data`.`owed $ii` as `total $ii`,
FIELDS
;
    $mid_query .= ", `{$archive}week_{$ii}_totals`";
    $end_query .= "`{$archive}weekly_totals_data`.`member_name` = `{$archive}week_{$ii}_totals`.`member_name` ";
    $end_query .= ' and ';
    create_and_update_week_totals($ii);
  }
  $table_edit_query .= $mid_query . $end_query . 
  " `{$archive}weekly_totals_data`.`member_name` = `{$archive}fining_data_totals`.`member_name` " .
    " order by `{$archive}weekly_totals_data`.`member_name`";
  //  print_r($table_edit_query);
  //exit;
}

function table_period_name($arr) {
  return "0 as " . bracket("week " . $arr['week'] . " fines") . ", ";
}

?>

<?php
/*
  //oh god, this sucks
#  $db->debug = true;
  $table_edit_query = 
<<<STARTQUERY
    select `{$archive}weekly_totals_data`.`autoid` as `autoid`,
    `{$archive}weekly_totals_data`.`member_name` AS `member_name`,
STARTQUERY
    ;
  $mid_query = "0 as `Total`, ";
  $mid_query .= join('',array_map('table_period_name',$backup_fine_weeks)) .
<<<ENDQUERY
`{$archive}fining_data_totals`.`fines` as `Other Fines`, 0 as `Total Fines`, 
    `{$archive}weekly_totals_data`.`notes` from `{$archive}weekly_totals_data`, `{$archive}fining_data_totals` 
ENDQUERY
;
  $end_query = " where ";
  for ($ii = 0; $ii < $week_num; $ii++) {
    $table_edit_query .= <<<FIELDS
`{$archive}week_{$ii}_totals`.`done` as `done $ii`,`{$archive}week_{$ii}_totals`.`blown` as `blown $ii`,
`{$archive}weekly_totals_data`.`owed $ii` as `owed $ii`,`{$archive}week_{$ii}_totals`.`tot` as `week $ii`,
`{$archive}week_{$ii}_totals`.`tot`-`{$archive}weekly_totals_data`.`owed $ii` as `total $ii`,
FIELDS
;
    $mid_query .= ", `{$archive}week_{$ii}_totals`";
    $end_query .= "`{$archive}weekly_totals_data`.`member_name` = `{$archive}week_{$ii}_totals`.`member_name` ";
    $end_query .= ' and ';
    create_and_update_week_totals($ii);
  }
  $table_edit_query .= $mid_query . $end_query . 
  " `{$archive}weekly_totals_data`.`member_name` = `{$archive}fining_data_totals`.`member_name` " .
    " order by `{$archive}weekly_totals_data`.`member_name`";
  //  print_r($table_edit_query);
  //exit;



  //oh god, this sucks
#  $db->debug = true;
  $table_edit_query = 
<<<STARTQUERY
    select `{$archive}weekly_totals_data`.`autoid` as `autoid`,
    `{$archive}weekly_totals_data`.`member_name` AS `member_name`,
STARTQUERY
    ;
  $mid_query = "0 as `Total`, ";
  $mid_query .= join('',array_map('table_period_name',$backup_fine_weeks)) .
<<<ENDQUERY
`{$archive}fining_data_totals`.`fines` as `Other Fines`, 0 as `Total Fines`, 
    `{$archive}weekly_totals_data`.`notes` from 
ENDQUERY
;
  $join_query = "`{$archive}weekly_totals_data` left join `{$archive}fining_data_totals` using (`member_name`)";
  
  $end_query = " where ";
  for ($ii = 0; $ii < $week_num; $ii++) {
    $table_edit_query .= <<<FIELDS
`{$archive}week_{$ii}_totals`.`done` as `done $ii`,`{$archive}week_{$ii}_totals`.`blown` as `blown $ii`,
`{$archive}weekly_totals_data`.`owed $ii` as `owed $ii`,`{$archive}week_{$ii}_totals`.`tot` as `week $ii`,
`{$archive}week_{$ii}_totals`.`tot`-`{$archive}weekly_totals_data`.`owed $ii` as `total $ii`,
FIELDS
;
# $mid_query .= ", `{$archive}week_{$ii}_totals`";
 $join_query = "($join_query) left join `{$archive}week_{$ii}_totals` using (`member_name`)";
 $end_query .= "`{$archive}weekly_totals_data`.`member_name` = `{$archive}week_{$ii}_totals`.`member_name` ";
    $end_query .= ' and ';
    create_and_update_week_totals($ii);
  }
  $table_edit_query .= $mid_query . $join_query . 
    " order by `{$archive}weekly_totals_data`.`member_name`";
#    print_r($table_edit_query);
#    print "<h2>Being worked on -- will be online in a few minutes.</h2>";
#  exit;
  */
?>
