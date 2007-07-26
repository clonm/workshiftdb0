<?php 
//displays the weekly_totals in a more fine-oriented way -- each week
//is broken down separately.  Can also be downloaded to a .csv file
//and given to the house manager (who can also view this directly).
//For legacy reasons, and because this is a likely page to view the
//archive of, you can view an archive's house_fines directly from this
//page.  Note that this page is really in the weekly_totals family.
//It differs enough from them, though, that it gets all its own code,
//which sucks.
$body_insert = '';
$require_user = array('workshift','house');
require_once('default.inc.php');
create_and_update_fining_data_totals();
//not a real table
$table_name = 'House Fines';
//member_name is 0th column
$restrict_cols = array(0);
$tot_weeks = get_static('tot_weeks',18);
$week_num = get_cur_week();
$fining_rate = get_static('fining_rate',11.7);
//legacy -- shouldn't be here anymore
if (!strlen($fining_rate) || $fining_rate <= 0) {
  $fining_rate = 12;
  set_static('fining_rate',$fining_rate);
}
//as always, monster query
$table_edit_query = '';
$sql_begin = "select `{$archive}weekly_totals_data`.`member_name`";
$sql_mid = ", `{$archive}fining_data_totals`.`fines` as `Other Fines` " .
"from `{$archive}weekly_totals_data`, `{$archive}fining_data_totals` ";
$sql_end = " where `{$archive}weekly_totals_data`.`member_name` = " .
"`{$archive}fining_data_totals`.`member_name`";
$col_names = array('member_name');
$col_formats = array();
//for 
for ($ii = 0; $ii < $week_num; $ii++) {
  //for each week, we might have to redo the totals
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
//does the house use nonzeroed totals as well?
if ($nonzeroed_total = get_static('nonzeroed_total_hours',null,$archive)) {
  $col_names[] = 'Nonzeroed Total';
}
//do they cash hours in?
if ($cash_hours_auto = get_static('cash_hours_auto',false,$archive)) {
  //how much of the other fines is refundable?
  $cash_res = $db->Execute("select `{$archive}house_list`.`member_name`, sum(`fine`) as `val` " .
                           "from `{$archive}house_list` left join `{$archive}fining_data`" .
                           " on `{$archive}house_list`.`member_name` = `{$archive}fining_data`.`member_name` " .
                           "and `fine` > 0 and `refundable` " .
                           "group by `member_name` order by `member_name`");
  //you can only cash in as many hours as you've been refundably fined
  //(workshift hours are presumed always refundable)
  $cash_maxes = array();
  while ($cash_row = $cash_res->FetchRow()) {
    $cash_maxes[$cash_row['member_name']] = $cash_row['val']/$fining_rate;
  }
  $col_names[] = "Total after Cash-in";
  $col_names[] = "Fine Rebate";
  //money_table is defined in table_edit.  It formats dollar amounts.
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

$fining_buffer = get_static('fining_buffer',0,$archive);
$fining_floor = get_static('fining_floor',10,$archive);
$fining_doublefloor = get_static('fining_doublefloor',null,$archive);

//when are the fining periods?  Call them backup because individuals'
//may be shifted
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

//how many hours up can you be in a normal week?
$backup_max_up_hours = get_static('max_up_hours',null,$archive);
//in a fining week?
$max_up_hours_fining = get_static('max_up_hours_fining',null);
//if not set for fining, it's normal
if (!strlen($max_up_hours_fining)) {
  $max_up_hours_fining = $backup_max_up_hours;
}
//how much of your fines do you actually owe?
$fining_percent_fine = get_static('fining_percent_fine',null);
if (!strlen($fining_percent_fine)) {
  $fining_percent_fine = 100;
}
//how much of your hours are zeroed after you're fined?
$zero_partial = get_static('fining_zero_partial',null);

//legacy if, just in case archive we're looking at doesn't have
//special_fining
if (!$archive || table_exists('special_fining')) {
  $special_res = $db->Execute("select * from `{$archive}special_fining`");
  $special_fining = array();
  //get all the special fines
  while ($special_row = $special_res->FetchRow()) {
    $special_fining[$special_row['member_name']] = $special_row;
  }
}

//as with all the weekly_totals
function mung_whole_row(&$row) {
  global $week_num, $backup_fine_weeks, $fining_floor, $fining_buffer,
    $fining_rate, $fining_doublefloor, $fining_zero_hours,
    $javascript_pre,$col_styles, $weekly_fining, $nonzeroed_total, 
    $backup_max_up_hours, $cash_hours_auto, $res, $cash_maxes,
    $fining_percent_fine,$zero_partial, $special_fining, $max_up_hours_fining,
    $tot_weeks;
  //will contain the actual weeks that fining periods normally take place in
  static $key_weeks = null;
  $cur_fine = 0;
  $cash_hours = 0;
  //zero everything out
 $row['Total Fines'] = $row['Other Fines'];
  $row['Total'] = 0;
  if ($nonzeroed_total) {
    $row['Nonzeroed Total'] = 0;
  }
  //we're going to work with fine_weeks, but we might need to alter
  //it, if this person's fining dates were altered
  $fine_weeks = $backup_fine_weeks;
  //oh, maybe they were (actually, now everyone should have a
  //special_fining row)
  if (isset($special_fining[$row['member_name']])) {
    $new_fine_weeks = array();
    //set the fining weeks
    if (!$key_weeks) {
      $key_weeks = array_keys($backup_fine_weeks);
    }
    //assume that never more than 5 fining periods
    for ($kk = 1; $kk <= 5; $kk++) {
      //when is this fining period
      $new_week = $special_fining[$row['member_name']]["fine_week_$kk"];
      //-1 means unchanged.  Did it move, and is it not actually just the
      //usual week?
      if ($new_week != -1 && 
          $new_week != ($old_week = $key_weeks[$kk-1])) {
        //was this fine not canceled altogether, and not moved to the future?
        if (strlen($new_week) && $new_week < $week_num) {
          //no, it wasn't.  Set a flag so we know this is a fine week,
          //and don't later erase it (say if the manager moves 3 to 5,
          //5 to 7, we don't want to delete the fining period at week
          //5 that came from week 3 when we get to week 5).
          $new_fine_weeks[$new_week] = 1;
          //the policy for this fining period is the same, only the
          //date has changed.
          $fine_weeks[$new_week] = $backup_fine_weeks[$old_week];
        }
        //if the old week isn't a fining week anymore, unset it.
        if (!isset($new_fine_weeks[$old_week])) {
          unset($fine_weeks[$old_week]);
        }
      }
    }
  }
  //ok, we're all set.  Time to do the calculations!
  for ($ii = 0; $ii < $week_num; $ii++) {
    //add on the hours done this week.
    $row['Total'] += $row["total $ii"];
    //is this a fining period?
    $end_fine = isset($fine_weeks[$ii]);
    //if so, prepare
    if ($end_fine) {
      $max_up_hours = $max_up_hours_fining;
    }
    else {
      $max_up_hours = $backup_max_up_hours;
    }
    //are we at the end of the semester?  CO-mandated rules.
    if ($ii == $tot_weeks) {
      $fining_percent_fine = 100;
      $zero_partial = 0;
    }
    if ($nonzeroed_total) {
      $row['Nonzeroed Total'] += $row["total $ii"];
      //nonzeroed total is subject to max_up_hours, like normal total.
      if ($max_up_hours && $row['Nonzeroed Total'] > $max_up_hours) {
        $row['Nonzeroed Total'] = $max_up_hours;
      }
    }
    //too many hours up?
    if ($max_up_hours && $row['Total'] > $max_up_hours) {
      //cash them in if we can.
      if ($cash_hours_auto) {
        $cash_hours += $row['Total']-$max_up_hours;
      }
      $row['Total'] = $max_up_hours;
    }
    $row["total $ii"] = $row['Total'];
    $row["fines $ii"] = 0;
    //is it possible to be fined this week?
    if ($end_fine || $weekly_fining) {
      //end of fining period?
      if ($end_fine) {
        //set up variables
        $fin_floor = $fine_weeks[$ii]['fining_floor'];
        $fin_rate = $fine_weeks[$ii]['fining_rate'];
        $fin_buffer = $fine_weeks[$ii]['fining_buffer'];
        $fin_doublefloor = $fine_weeks[$ii]['fining_doublefloor'];
      }
      else {
        //and likewise for weekly fining
        $fin_floor = $fining_floor;
        $fin_rate = $fining_rate;
        $fin_buffer = $fining_buffer;
        $fin_doublefloor = $fining_doublefloor;
      }
      //normalize parameters
      if (!is_numeric($fin_floor)) {
        $fin_floor = 0;
      }
      if (!is_numeric($fin_buffer)) {
        $fin_buffer = 0;
      }
      if (!is_numeric($fin_rate) || !($fin_rate > 0)) {
        $fin_rate = $fining_rate;
      }
      //we'll be altering the total, so get our variable
      $temptotal = $row['Total'];
      //only get fined for difference from floor
      $temptotal +=$fin_floor;
      //this is a negative number, if we're getting fined, make it
      //positive, so we'll be fined a positive amount.
      $temptotal *= -1;
      //did we exceed the buffer (so we'll be fined)?
      if ($temptotal > $fin_buffer) {
        //fine for the total number of down hours at the fining rate
        //times whatever percentage is being fined.
        $row["fines $ii"] = $temptotal*$fin_rate*$fining_percent_fine/100;
        //maybe there's also a double-fine.  Make sure it makes sense
        if ($fin_doublefloor && $fin_doublefloor >= $fin_floor) {
          //only double-fining for the difference
          $fin_doublefloor -= $fin_floor;
          //we subtracted the fining-floor from temptotal before, so
          //this comparison makes sense
	  if ($temptotal >= $fin_doublefloor) {
            //add in another fining-rate*difference
            $row["fines $ii"] += ($temptotal-$fin_doublefloor)*$fin_rate;
          }
        }
      }
      else {
        //temptotal didn't exceed fining buffer
        $row["fines $ii"] = 0;
      }
      $row["Total Fines"] += $row["fines $ii"];
      //if we're cashing in hours automatically, our max just went up
      //by whatever this fine was.
      if ($cash_hours_auto) {
        $cash_maxes[$row["member_name"]] += $row["fines $ii"]/$fining_rate;
      }
      //zeroing hours now, but never zero in the current week --
      //people would get confused, because they would be fined, but be
      //at 0 hours.
      if ($ii < $week_num-1) {
        //would we benefit from being zeroed?  zero is actually a
        //misnomer, because we're just going to go up to the fining
        //floor
        if ($row['Total'] < -$fin_floor) {
          //are we zeroing hours?
          if (($end_fine && $fine_weeks[$ii]['zero_hours']) || 
              ($weekly_fining && $fining_zero_hours)) {
            //are we zeroing only partially?
            if (strlen($zero_partial)) {
              $row['Total'] = -$fin_floor + 
                ($row['Total']+$fin_floor)*$zero_partial/100;
            }
            else {
              $row['Total'] = -$fin_floor;
            }
          }
        }
      }
    }
    //done with possibility of fining!
  }
  //done with week loop
  if ($cash_hours_auto) {
    //how much could we have cashed in before?
    $cash_max = $cash_maxes[$row['member_name']];
    $row['Total after Cash-in'] = $row['Total'];
    //if max_up_hours was there, it's possible that we've already
    //cashed in some hours
    if ($max_up_hours && $cash_hours > 0) {
      $row['Fine Rebate'] = $fining_rate*min($cash_hours,$cash_max);
      $cash_max-=$cash_hours;
    }
    else {
      $row['Fine Rebate'] = 0;
    }
    //can we still cash in more?
    if ($row['Total after Cash-in'] > 0 && $cash_max > 0) {
      //we can cash in between 0 and total hours, up to cash_max
      $rebate_hours = min(max($row['Total after Cash-in'],0),$cash_max);
      $row['Fine Rebate'] += $fining_rate*$rebate_hours;
      $row['Total after Cash-in'] -= $rebate_hours;
    }
  }
}

//tell table_edit to call this function on each row
$mung_whole_row = 'mung_whole_row';

if (isset($_REQUEST['download'])) {
  //nice function I wrote.  Should really be used more.
  export_csv_file('House Fines',$table_edit_query,$col_formats,$mung_whole_row);
  exit;
}

if (!isset($body_insert)) {
  $body_insert = '';
}
//this_url trickery
$_GET['download'] = '';
$body_insert .= "<div class='print_hide'><a href='" . this_url() .
"'>Download to Excel</a>\n";
//offer dbs to look at -- latest first
$backup_dbs = array_reverse(get_backup_dbs());
if (count($backup_dbs)) {
  unset($_GET['download']);
  unset($_GET['archive']);
  $body_insert .= "<form method=get action='" .
    this_url() . "'>" .
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
?>
