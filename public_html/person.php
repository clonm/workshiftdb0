<?php 
//include everything
$body_insert = '';
require_once('default.inc.php');
//view a member's personal page, with lots of goodies used by members
//as their primary access point, and by workshift managers to see
//details of members easily (workshift managers access this page
//through inclusion in person.admin.php).  This page does the fullest,
//most detailed accounting of shifts.

//time how long rendering takes
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}
?>
<html><head><title>Information for <?=escape_html($member_name)?></title>
</head><body>
<?php
print $body_insert;
//if the workshift manager is viewing this page, we need to relativize
//links (I'm not sure there are any, but in any case)
if ($secured) {
  $base_uri = split('/',$_SERVER['REQUEST_URI']);
  array_pop($base_uri);
  array_pop($base_uri);
  $base_uri = join('/',$base_uri);
  $base_uri .= "/person.php";
  $base_uri = 'http://' . $_SERVER['HTTP_HOST'] . $base_uri;
  print "<base href='" . escape_html($base_uri) . "'>\n";
}
//don't be ugly -- show empty table cells with a border
?>
<style>
table {
  empty-cells: show;
}
</style>
<h4><?=escape_html($member_name)?></h4>
<?php 
//stuff the member can do (that the workshift manager can't)
if (!$secured) {
?>
<a href='preferences.php'>View or modify your preferences</a><p>
<a href='set_passwd.php'>
Change your password</a><p>
<a href='directory.php'>View the house directory, and modify how public your
information is</a><p>
<?php 
//elections stuff!
?>
<a href='elections/voting.php'>Vote in elections</a><br/>
<a href='elections/election_results.php'>View election results</a><br/>
<?php
}
//end of stuff member can do.  Now for all the information about member
$row = $db->GetRow("SELECT * FROM `{$archive}house_info` where `member_name` = ?",
                   array($member_name));
//put out all house info on one line -- room, phone, email
foreach ($row as $key => $val) {
  if ($key == 'autoid' || $key == 'member_name') {
    continue;
  }
  //don't put out empty fields
  if (!$val) {
    continue;
  }
  //trailing comma.  oh well.
  echo escape_html($key) . ": " . escape_html($val) . ", \n";
}
print "<p>";
print "Currently assigned shifts:\n";
//table has columns for days
print "<table style='text-align: left'>\n<tr>";
//we don't know how many days member works, so no output yet
$headers = '';
$shifts = '';
//days is global from janakdb-utils
foreach($days as $day) {
  $res = $db->Execute("SELECT `workshift`, `floor`, `hours` FROM " . 
                      "`{$archive}master_shifts` WHERE `$day` = ?",
                      array($member_name));
  //are there any assigned shifts?
  if (!is_empty($res)) {
    $headers .= "<th>" . escape_html($day) . "</th>";
    $shifts .= "<td>";
    //shifts should be clickable, going to the description of the shift
    while ($row = $res->FetchRow()) {
      $shifts .= "<a href='shift_descriptions.php#" . 
        escape_html($row['workshift']) .
        "'>" . escape_html($row['workshift']);
      if ($row['floor']) {
        $shifts .= " floor " . escape_html($row['floor']);
      }
      $shifts .= " (" . escape_html($row['hours']) . " hrs)</a><br>\n";
    }
    $shifts .= "</td>";
  }
}
//now we have all the shifts, put them out at once
print $headers . "</tr>\n<tr>" . $shifts . "</tr></table>";
//remember, week_num now has the last *completed* week
$week_num = get_cur_week();
//we're going to get the full totals, fines, etc., but we want
//summaries at the top of the page, so we need to save this output
//until the end
ob_start();
//get the owed hours for each week
$row_owed = $db->GetRow("select * from `{$archive}weekly_totals_data` " .
                        "where `member_name` = ?",
                        array($member_name));
//set up variables for calculations
$fining_rate = get_static('fining_rate',12);
//is it possible to be fined on non-fining period weeks?
$weekly_fining = get_static('weekly_fining',true);
  //if so, get the info about it
if ($weekly_fining) {
  //are your hours zeroed after a fine?  Of course not.
  $fining_zero_hours = get_static('fining_zero_hours');
  //fines will be stored here
  $weekly_fines = array();
}
//can't be up more than this many hours?
$max_up_hours = get_static('max_up_hours',null);
//some houses have a different value for max_hours at fining periods
$max_up_hours_fining = get_static('max_up_hours_fining',null);
//if not, initialize with the default
if (!strlen($max_up_hours_fining)) {
  $max_up_hours_fining = $max_up_hours;
}
//when you're fined, what percentage of the down hours is fined?
//out of 100.  null means fine 100%
$fining_percent_fine = get_static('fining_percent_fine',null);
//when hours are zeroed, what fraction are zeroed?  out of 100, null
//means fine 100%
$zero_partial = get_static('fining_zero_partial',null);
//total weeks in semester?  Remember, if at end, we're in tot_weeks+1
$tot_weeks = get_static('tot_weeks',18);
//not sure why we need this check
if (!is_numeric($tot_weeks)) {
  $tot_weeks = 18;
}

//can members redeem fines using up hours?
if ($cash_hours_auto = get_static('cash_hours_auto',false)) {
  //what is the most number of hours that can be redeemed?
  //amount of fines:
  $cash_max = $db->GetRow("select sum(`fine`) as `val` from " .
                          bracket("{$archive}fining_data") .
                          " where `member_name` = ? and (`fine` > 0 " .
                          "and `refundable`)",
                          array($member_name));
  //fines scaled by hourly rate
  $cash_max = $cash_max['val']/$fining_rate;
  //might cashed-in hours happen from max_hours overflow?
  if ($max_up_hours || $max_up_hours_fining) {
    $cash_hours = 0;
  }
}
//does workshift manager want nonzeroed totals kept track of?
if ($nonzeroed_total_flag = get_static('nonzeroed_total_hours',null)) {
  $nonzeroed_total = 0;
}

//floor is the "zero" -- below this you can get fined
$fining_floor = get_static('fining_floor',10);
//buffer is the buffer below the floor -- if you're this much
//or less below the floor, you don't get fined
$fining_buffer = get_static('fining_buffer',0);
//below this point, fines count double
$fining_doublefloor = get_static('fining_doublefloor');

//fining period fines will be stored here
$fining_periods = array();
$other_fines = 0;
//maybe this person has different fining periods than everyone else
//-1 indicates the usual week, blank means no fine, another week means
//that week.  Unfortunately, since the weeks are the columns, can only
//manually set first 5 fining periods.  No house has more than 4 now.
$special_row = array();
if (!$archive || table_exists('special_fining')) {
$special_row = $db->GetRow("select * from `{$archive}special_fining` " .
                           "where `member_name` = ?",
                           array($member_name));
//maybe member has nothing special.  
if (is_empty($special_row)) {
  $db->Execute('insert into ' .
               bracket("{$archive}special_fining") . 
               ' (`member_name`) values (?)',
               array($member_name));
  $special_row = null;
}
}
else {
  $special_row = null;
}
$res = $db->Execute("select * from `{$archive}fining_periods` order by `week`");
$real_fining_periods = array();
if ($special_row) {
  foreach ($special_row as $key => $week) {
    if ($key == 'member_name' || $key == 'autoid') {
      continue;
    }
    if ($res->EOF) {
      break;
    }
    $row = $res->FetchRow();
    if (!strlen($week)) {
      continue;
    }
    if ($week == -1) {
      $week = $row['week'];
    }
    foreach (array('fining_doublefloor','fining_floor','fining_buffer') as $attrib) {
      if (strlen($row[$attrib]) == 0) {
        $row[$attrib] = $GLOBALS[$attrib];
      }
    }
    $real_fining_periods[$week] = $row;   
  }
}
else {
  while ($row = $res->FetchRow()) {
    foreach (array('fining_doublefloor','fining_floor','fining_buffer') as $attrib) {
      if (strlen($row[$attrib]) == 0) {
        $row[$attrib] = $GLOBALS[$attrib];
      }
    }
    $real_fining_periods[$row['week']] = $row;
  }
}

echo "<h4>List of all Shifts Done</h4>";
//what you think
$tot_hours = 0;
//ditto
$total_fine = 0;
//hours for the week we're doing
$week_hours = 0;
//<= is because we'll give provisional totals for the current week
for ($ii = 0; $ii <= $week_num; $ii++) {
  //we need to do some setup for cleanup if it's the current week
  if ($ii == $week_num) {
    //if the semester has ended, get out.
    if ($ii > $tot_weeks) {
      break;
    }
    //does the current week have a weekly page?
    if (!table_exists("week_$ii") || !isset($row_owed["owed $ii"])) {
      break;
    }
    //let workshift manager customize this text
    print_static_text('shifts_this_week',
                      "Current week (<b>may be inaccurate if workshift manager " .
                      "has not finished input or you still have more shifts to " .
                      "do</b>): <br/>",array(),true);
    //variables that might be altered here but need to be reverted
    $old_tot_hours = $tot_hours;
    $old_total_fine = $total_fine;
    if ($cash_hours_auto && 
        ($max_up_hours || $max_up_hours_fining)) {
      if (isset($cashed_in)) {
        $old_cashed_in = $cashed_in;
      }
      else {
        $old_cashed_in = 0;
      }
      $old_cash_hours = $cash_hours;
    }
  }
  echo "Week $ii: <p>";
  //put out table with workshifts done this week
  echo "<table border>\n";
  echo "<tr><td>Date</td><td>Day</td><td>Workshift</td><td>Hours</td>" .
    "<td>Notes</td></tr>\n";
  $res = $db->Execute("SELECT `date`, `day`, `workshift`, `hours`, `notes` " .
                      " FROM `{$archive}week_$ii` WHERE `member_name` = ?",
                      array($member_name));
  while ($row = $res->FetchRow()) {
    echo "<tr>\n";
    foreach ($row as $key => $val) {
      if ($key == 'hours') {
        $tot_hours += $val;
        $week_hours += $val;
      }
      echo "<td>";
      //workshift should be clickable, going to shift descriptions
      if ($key == 'workshift') {
        print "<a href='shift_descriptions.php#" . 
          escape_html($row['workshift']) . "'>";
      }
      echo escape_html($val);
      if ($key == 'workshift') {
        print "</a>";
      }
      echo "</a>";
    }
    echo "</tr>";
  }
  echo "</table>\n";
  $owed = $row_owed["owed $ii"];;
  $tot_hours -= $owed;
  //does this week end a fining period?
  if (isset($real_fining_periods[$ii])) {
    $row = $real_fining_periods[$ii];
    $flag_fining = true;
    //switch out the max_up_hours
    $old_max_up_hours = $max_up_hours;
    $max_up_hours = $max_up_hours_fining;
  }
  else {
    $flag_fining = false;
  }
  //by CO policy, fines are 100% the last week, zero is meaningless.
  if ($ii == $tot_weeks) {
    $fining_percent_fine = null;
    $zero_partial = null;
  }
  //do we need to keep track of nonzeroed totals?
  if ($nonzeroed_total_flag) {
    //keep track
    $nonzeroed_total += $week_hours-$owed;
    //are we over the max_up_hours?  go down to it
    if ($max_up_hours && $nonzeroed_total > $max_up_hours) {
      $nonzeroed_total = $max_up_hours;
    }
  }
  //over max_up_hours?
  if ($max_up_hours && $tot_hours > $max_up_hours) {
    //tell member about it
    echo "(Couldn't go more than " . escape_html($max_up_hours) . " hours up, so " .
      ($tot_hours-$max_up_hours) . " were discarded";
    //are extra hours applied to fines?
    if ($cash_hours_auto) {
      //we can cash in at most $cash_max (what we had to
      //start)-$cash_hours, which is how much we've cashed so far, and
      //of course, we cash non-negatively-many hours.
      $cashed_in =min($tot_hours-$max_up_hours,max(0,$cash_max-$cash_hours));
      $cash_hours += $cashed_in;
      if ($cashed_in) {
        echo ", with " . escape_html($cashed_in) . 
          " of those hours applied towards fines";
      }
    }
    echo ")<br>";
    $week_hours -= ($tot_hours-$max_up_hours);
    $tot_hours = $max_up_hours;
  }
  //summary line for this week
  echo "(Net hours done $week_hours, Owed $owed, Week's change " . 
    ($week_hours-$owed) . ", Running total " . escape_html($tot_hours);
  //do fines
  //flag_fining was set above, if we were in a fining period
  if ($flag_fining || $weekly_fining) {
    //fining period?
    if ($flag_fining) {
      //this will hold the fine for this period
      $fining_periods[$ii] = 0;
      //set variables with fining-period-specific values
      $fin_floor = $row['fining_floor'];
      $fin_rate = $row['fining_rate'];
      $fin_buffer = $row['fining_buffer'];
      $fin_doublefloor = $row['fining_doublefloor'];
      //restore usual max_up_hours -- we're done with it
      if (strlen($max_up_hours_fining)) {
        $max_up_hours = $old_max_up_hours;
      }
    }
    else {
      //set values from weekly_fining vars
      $fin_floor = $fining_floor;
      $fin_rate = $fining_rate;
      $fin_buffer = $fining_buffer;
      $fin_doublefloor = $fining_doublefloor;
    }
    //clean up variables
    if (!is_numeric($fin_floor)) {
      $fin_floor = 0;
    }
    if (!is_numeric($fin_buffer)) {
      $fin_buffer = 0;
    }
    if (!is_numeric($fin_rate) || !($fin_rate > 0)) {
      $fin_rate = $fining_rate;
    }
    $temptotal = $tot_hours;
    //adjust total upwards (it's negative, presumably) by floor
    $temptotal +=$fin_floor;
    //make it positive (presumably)
    $temptotal *= -1;
    //uh-oh, we're about to get fined
    if ($temptotal > $fin_buffer) {
      //scale by fining_percent_fine
      if (strlen($fining_percent_fine)) {
        $temptotal *= $fining_percent_fine/100;
      }
      //here's the fine
      $fine = $temptotal*$fin_rate;
      //do we have a meaningful doublefining policy?
      if ($fin_doublefloor && $fin_doublefloor >= $fin_floor) {
        //take out the part we already fined for
        $fin_doublefloor -= $fin_floor;
        //add in another fine of the remainder
        $fine += ($temptotal-$fin_doublefloor)*$fin_rate;
      }
    }
    else {
      $fine = 0;
    }
    $total_fine += $fine;
    //if we were fined, stuff to take care of
    if ($fine) {
      //if fines can be worked off, add these fines to the potential work
      if ($cash_hours_auto) {
        $cash_max += $fine/$fining_rate;
      }
      //tell member it hurts
      echo ", Fined " . money($fine);
      //if we're in a fining period, add this in
      if ($flag_fining) {
        $fining_periods[$ii] =  $fine;
        print(" at the end of the fining period");
      }
      else {
        $weekly_fines[$ii] = $fine;
      }
      //if we're at the last displayed "real" week, we don't want to
      //zero hours because that will make someone down 10 hours think
      //they're down 0 hours, because the hours are zeroed.  This
      //makes the "current week" numbers inaccurate, but who really
      //cares.
      if ($ii < $week_num-1) {
        //were we eligible for fining?
        if ($tot_hours < -$fin_floor) {
          //does this house zero hours?
          if (($flag_fining && $row['zero_hours']) || 
              ($weekly_fining && $fining_zero_hours)) {
            //we can go back up to the fining floor.  Potentially scaled.
            if (strlen($zero_partial)) {
              $tot_hours = -$fin_floor + ($tot_hours+$fin_floor)*$zero_partial/100;
            }
            else {
              $tot_hours = -$fin_floor;
            }
            echo ", hours reset to $tot_hours";            
          }
        }
      }
    }
  } 
  echo ")<p>\n";
  //done with the week!
  $week_hours = 0;
  //unset changed variables if we're in the current week, since it's not right
  if ($ii == $week_num) {
    $total_fine = $old_total_fine;
    $tot_hours = $old_tot_hours;
    if ($cash_hours_auto && 
        ($max_up_hours || $max_up_hours_fining)) {
      $cashed_in = $old_cashed_in;
      $cash_hours = $old_cash_hours;
    }
    if (isset($fining_periods)) {
      unset($fining_periods[$ii]);
    }
    if (isset($weekly_fines)) {
      unset($weekly_fines[$ii]);
    }
  }
}

//ok, now we have all that output, and we can start outputting in realtime
$weekly_shift_printout = ob_get_contents();
ob_end_clean();
//no, wait, we're back to storage.  Curses!
ob_start();
//is there a potential for cashing in fines?
$cash_flag = $cash_hours_auto && ($cash_max > 0);
if ($cash_flag) {
  //would any up hours cashed in be real, or just potential, at this point?
  //we assume that up hours will be cashed in after the end of the last
  //fining period
  $max_week = max(array_keys($real_fining_periods));
  if ($max_week) {
    $temp = min($tot_weeks,$max_week+1);
  }
  //are we there yet?  Probably not.
  $cash_flag_potential = ($temp >= $week_num);
}
ob_start();
//we have to put out a fairly complicated series of tables which give
//the fines.  It's complicated partly because we want a total fines
//with subtotals.
?>
<style>
table.fines th, td {
 padding: 0px 20px 0px 20px;
 text-align: center;
 border-style: solid solid solid solid;
 border-collapse: collapse;
 border-width: 1px;
}

table.internalfines {
  empty-cells: show;
}

table.internalfines th {
 padding: 0px 0px 0px 0px;
 text-align: right;
}
table.internalfines td {
 padding: 0px 0px 0px 0px;
 border-style: inset;
 border-width: thin;
 text-align: right;
}
</style>
<h4>Fines</h4>
<table class='fines'>
<tr>
<?php
//first off, can we cash in any hours?
if ($cash_flag) {
  //we'll want to tell member what we started with
  $old_total = $tot_hours;
  //did we have some hours accrued along the way?
  if ($max_up_hours && $cash_hours > 0) {
    $rebate = $fining_rate*min($cash_hours,$cash_max);
    $cash_max-=$cash_hours;
  }
  else {
    $rebate = 0;
  }
  //anything left to cash in?
  if ($tot_hours > 0 && $cash_max > 0) {
    $rebate_hours = min($tot_hours,$cash_max);
    $rebate += $fining_rate*$rebate_hours;
    //here's what we have left of member's hours
    $cash_total = $tot_hours-$rebate_hours;
  }
  else {
    $cash_total = $tot_hours;
  }
  //if no rebate, doesn't matter if we *could* have cashed in
  if (!$rebate) {
    $cash_flag = false;
  }
}
//keep track of fines for three categories -- periods, weekly, other
$fine_exists = array();
if (!is_empty($fining_periods)) {
  echo "<th><b>Fining Periods</b></th>";
  $fine_exists[0] = true;
}
if ($weekly_fining && !is_empty($weekly_fines)) {
  echo "<th><b>Weekly</b></th>";  
  $fine_exists[1] = 'true';
}
$res = $db->Execute("select * from `{$archive}fining_data` where `member_name` = ?",
                    array($member_name));
//"other" fines? fining_data has them, and cashing in also goes here
if (!is_empty($res) || $cash_flag) {
  echo "<th><b>Other</b></th>";
  $fine_exists[2] = 'true';
}
print "</tr>\n<tr>";

$subtot = array();
if (isset($fine_exists[0])) {
  print "<td>";
  //each class of fines gets its own table
  print "<table class='internalfines'>\n";
  $subtot[0] = 0;
  print "<tr><td>Week</td><td>Fine amount</td></tr>";
  foreach ($fining_periods as $week => $amount) {
    print "<tr><td>" . escape_html($week) . "</td><td>" .
      money($amount) . "</td></tr>";
    $subtot[0] += $amount;
  }
  print "</table></td>";
}
if (isset($fine_exists[1])) {
  print "<td><table class='internalfines'>\n";
  $subtot[1] = 0;
  print "<tr><td>Week</td><td>Fine amount</td></tr>";
  foreach ($weekly_fines as $week => $amount) {
    print "<tr><td>" . escape_html($week) . "</td><td>" .
      money($amount) . "</td></tr>";
    $subtot[1] += $amount;
  }
  print "</table></td>";
}
  
if (isset($fine_exists[2])) {
  print("<td><table class='internalfines'>\n");
  $subtot[2] = 0;
  print("<tr><td>Date</td><td>Fine amount</td><td>Description</td></tr>");
  while ($row = $res->FetchRow()) {
    print "<td>" . escape_html($row['date']) . "</td>";
    print "<td>" . money($row['fine']) . "</td>";
    $total_fine += $row['fine'];
    $subtot[2] += $row['fine'];
    //this doesn't happen yet, but maybe one day it will --
    //the workshift manager can manually cash-in positive hours
    if (!$row['description'] && $row['week_cashed']) {
      $row['description'] = 'cash-in of positive hours in week ' . 
        $row['week_cashed'];
    }
    print "<td>" . escape_html($row['description']) . "</td>";
    print "</tr>";
  }
  if ($cash_flag) {
    //if it's not the end of the semester yet, we talk about this
    //cash-in as a potential one, not actual, even though any hours
    //coming from max_up_hours overflow are pretty much definite
    print "<td></td><td>" . money(-1*$rebate) . "</td>" .
      "<td><a href='help.html#cash_hours'>" .
      ($cash_flag_potential?'<b>Potential</b> c':'C') . 
      "ash-in of up hours</a></td></tr>";
    $subtot[2] += -$rebate;
    if ($cash_flag_potential) {
      $cash_fines = $total_fine-$rebate;
    }
    else {
      $temp = $tot_hours;
      $tot_hours = $cash_total;
      $cash_total = $temp;
      $cash_fines = $total_fine;
      $total_fine -= $rebate;
    }
  }
  else {
    $cash_flag = false;
  }
  print "</table></td>";
}
print "</tr>\n<tr>";
//put out the subtotals for each category
foreach ($fine_exists as $key => $val) {
  print "<td>Subtotal: " . money($subtot[$key]) . "</td>";
}
print "</tr></table>";
print "(Negative numbers are credits)<br><br>\n";
//were there any fines total?
if (array_sum($fine_exists)) {
  ob_end_flush();
}
else {
  //if no, discard output
  ob_end_clean();
}
print "</p><b>Total Fines: " . money($total_fine) . "</b>";
if ($cash_flag && $total_fine != $cash_fines) {
  echo " (" . money($cash_fines) . " " .
    ($cash_flag_potential?"after":"before") .
    " <a href='help.html#cash_hours'>cash-in of up hours</a>)";
}   
echo "<p>\n";
$fines_table = ob_get_contents();
//Finally, we've gotten all the fining info out of the way, so we can
//go back to realtime output
ob_end_clean();
if ($tot_hours >= 0) {
  print "<h4 style='color:green'>Up +";
}
else {
  print "<h4 style='color:red'>Down ";
}
print $tot_hours . " hours";
//were there cashed-in hours?
if ($cash_flag && $tot_hours != $cash_total) {
  print " (" . escape_html($cash_total) . " " . 
    ($cash_flag_potential?"after":"before") .
    " <a href='help.html#cash_hours'>cash-in of up hours</a>)";
}
print "</h4>";
//we'll output a nonzeroed total if there is one, and our nonzeroed
//total is different from total hours (if we output total hours above)
//or different from cash_hours (if we output cash_hours above)
if ($nonzeroed_total_flag &&
    (((!$cash_flag || $cash_flag_potential) && 
      $nonzeroed_total != $tot_hours) ||
     ($cash_flag && !$cash_flag_potential && 
      $nonzeroed_total != $cash_hours))) {
  print "(Your <a href='help.html#nonzeroed_total'>nonzeroed " .
    "total</a> is " . escape_html($nonzeroed_total) . " hours)<p>";
}
//print out fines table, now that summary info has been printed
print $fines_table;
//this logic is unnecessarily annoying because of English plurals, and
//still isn't perfect.
//are there fining periods to print out?
if (count($real_fining_periods)) {
  //any that have happened so far?
  $printfinebuf = "Fining periods so far have been at week";
  $newbuf = '';
  $first_flag = true;
  $counting = 0;
  foreach ($real_fining_periods as $week => $flag) {
    //this loop is only for past fining periods
    if ($week >= $week_num) {
      break;
    }
    $counting++;
    if ($first_flag) {
      $first_flag = false;
    }
    else {
      //painfully put together string of numbers
      $newbuf .= ', ';
    }
    $newbuf .= $week;
    //if this period was adjusted, say so
    if ($week != $flag['week']) {
      $newbuf .= " (week " . $flag['week'] . " for everyone else)";
    }
  }
  //was there more than one fining period?
  if ($counting > 1) {
    $printfinebuf .= "s";
  }
  //were there any?
  if ($counting) {
    print $printfinebuf;
  }
  print " $newbuf";
  $first_flag = true;
  //any more fining periods?
  if ($counting < count($real_fining_periods)) {
    //continuing a sentence, or starting a new one?
    if ($counting) {
      print " and future f";
    }
    else {
      print "F";
    }
    print "ining periods will be at the end";
    $ofweek = " of week";
    $newbuf = '';
    $first_flag = true;
    $counting = 0;
    foreach ($real_fining_periods as $week =>$flag) {
      if ($week < $week_num) {
        continue;
      }
      $counting++;
      if ($first_flag) {
        $first_flag = false;
      }
      else {
        $newbuf .= ', ';
      }
      $newbuf .= $week;
      if ($week != $flag['week']) {
        $newbuf .= " (week " . $flag['week'] . " for everyone else)";
      }
    }
    if ($counting > 1) {
      print "s";
      $ofweek .= 's';
    }
    print $ofweek . " $newbuf";
  }
  print "<p>";
}
if ($row_owed['notes']) {
  print "Notes: " . escape_html($row_owed['notes']) . "<p>\n";
}
//finally, print out all the weekly stuff
print $weekly_shift_printout;
?>
<p id="phptime" style='font-size: 10px'>
PHP generated this page in <?=round(array_sum(split(' ',microtime()))-$php_start_time,2)?> seconds.
</p>
