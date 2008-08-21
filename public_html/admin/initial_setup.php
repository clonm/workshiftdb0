<?php
$body_insert = '';
require_once('default.inc.php');
//getting weird names, easier with numbers anyway
$db->SetFetchMode(ADODB_FETCH_NUM);
$page_status = null;
//post should override get, since get might be from an earlier page
if (array_key_exists('page_status',$_POST)) {
  $page_status = $_POST['page_status'];
}
else if (!array_key_exists('page_status',$_REQUEST)) {
  $page_status = null;
}
else {
  $page_status = $_REQUEST['page_status'];
}
?>
<html><head><title>Initial Setup</title></head><body>
<?=$body_insert;?>
<?php
function monthtosem($month) {
  switch ($month) {
  case 1: return 'spring';
  case 5: case 6: return 'summer';
  case 8: case 9: return 'fall';
  default: return 'unknown';
  }
  }

function order_archives($a,$b) {
  $am = $a['mod_date'];
  $bm = $b['mod_date'];
  if ($am < $bm) {
    return 1;
}
if ($am == $bm) {
  return 0;
}
if ($am > $bm) {
  return -1;
}
}

$current_sem = monthtosem(date('n'));
if ($current_sem == 'unknown') {
?>
Why are you trying to do a beginning-of-semester action now?  You can alter values
over at <a href='basic_consts.php'>basic_consts.php</a> -- this page should only
be accessed at the beginning of a semester.  Please email <?=admin_email()?>
to explain what you're trying to do.
</body>
</html>
<?php
//';
  exit;
} 

if ($page_status != 'submit') {
  $backupdbs = get_backup_dbs();
  //archives will be organized spring, summer, fall, unknown 
  $options_array = array('spring' => array(),'summer' => array(),
                         'fall' => array(),'unknown' => array());
  //go through and get info on each backup
  foreach ($backupdbs as $backup) {
    //the fact that we're setting the $archive variable means that all
    //$archive-dependent functions will now be accessing the archive,
    //not the current db
    $archive = $archive_pre . $backup . '_';
    if (!table_exists('static_data') || !table_exists('fining_data') ||
        !table_exists('master_shifts') || !table_exists('modified_dates')) {
      continue;
    }
    $mod_row = $db->_Execute("select unix_timestamp(max(`mod_date`)) " .
                           "from " . bracket($archive
                                             . 'modified_dates'));
    $mod_date = $mod_row->fields[0];
    $num_shifts = 0;
    $master_row = $db->_Execute("select count(*) from " .
                                bracket($archive . 'master_shifts'));
    $num_shifts = $master_row->fields[0];
    $owed_default = get_static('owed_default');
    $semester_start = get_static('semester_start');
    $semester_start = explode('-',$semester_start);
    if (count($semester_start) != 3) {
      continue;
    }
    $semester_time = mktime(0,0,0,$semester_start[1],$semester_start[2],
                            $semester_start[0]);
    $archive_sem = monthtosem($semester_start[1]);
    $archive_data = array('name' => $backup, 'owed' => $owed_default,
                          'shifts' => $num_shifts, 'mod_date' => $mod_date);
    //weird archive we don't know the semester of?  Add to end
    if ($archive_sem == 'unknown') {
      $options_array['unknown'][] = $archive_data;
    }
    else {
      //do we not have something for this semester already?
      if (!isset($options_array[$archive_sem][$semester_time])) {
        $options_array[$archive_sem][$semester_time] = array();
      }
      $options_array[$archive_sem][$semester_time][] = $archive_data;
    }
  }
  foreach ($options_array as $key => $junk) {
    krsort($options_array[$key],SORT_NUMERIC);
    foreach ($options_array[$key] as $ind => $junk) {
      usort($options_array[$key][$ind], 'order_archives');
    }
  }
?>
<script type='text/javascript'>
var userset = false;
  var userval = false;
function coupledcheckbox(newval) {
  if (!userset || newval) {
    document.getElementById('delete_assigned_bool').checked = newval;
  }
  else if (!newval && userset) {
    document.getElementById('delete_assigned_bool').checked = userval;
  }
}
function uncouplecheckbox(newval) {
  userset = true;
  userval = newval;
}
</script>
<form action='<?=this_url()?>' method='POST'>
  <input type=hidden name='page_status' value='submit'>
<p>
  This page will automatically make a backup before it makes any changes.  Give a
  name for this backup (leave blank to use the date):<br/>
  <input size=30 maxlength=30 name='backup_ext'><br/>
  If you're starting a new semester and you haven't yet backed up the previous
  semester, you could enter the previous semester, like
<?php
                  $last_time = time()-2*30*24*60*60;
  switch ($current_sem) {
  case 'spring': $last_sem = 'fall'; break;
  case 'summer': $last_sem = 'spring'; break;
  case 'fall': $last_sem = 'summer'; break;
  default: $last_sem = 'spring'; break;
  }
  print '"' . date('Y ',$last_time) . $last_sem . '"';
  ?> (without quotes).</p>
  <hr>
Start of semester (first Monday of week 0 -- enter August 29, 2005 as
2005-08-29): 
<input name='semester_start' value='<?= get_static('semester_start')?>'><br/>
(Even if week 0 started on a Thursday, enter the date of the Monday that the
week started.  So in Fall 2006, the contract starts Thursday 2006-08-24, but
you should enter 2006-08-21, since that is the date of the Monday.)<br/>
<hr/>
<?php
  switch ($current_sem) {
  case 'spring':
?>
Are you in the spring semester?  Then you probably dont need to change the
basic settings, since the fall settings should be fine.  If you do, though,
because your house didn't use the system in the fall, or any other reason,
read on.
<?php
                                                                      break;
  case 'summer':
?>
Are you in the summer semester?  If you haven't already put in last summer's
settings, you should do that now.  If you're not in summer, put in the
appropriate semester's settings.
<?php
                          break;
  case 'fall':
?>
Are you in the fall semester?  If you haven't already put in the settings from
last spring (or fall) to change from summer you should do that now.  If you're
not in fall, put in the appropriate semester's settings.
<?php
//';
}
?>
<br/>
Below, select the archived settings that you want to use for this semester.
This will set the default owed hours, the number of weeks in the semester, the
master list of workshifts, and the fining periods.  You can change all of these
later if you need to (and you will).  <br/>
Archives are grouped by season and semester, and given in order, most recent archive first.
For automatically generated archives, the archive's name is
year_month_day_hour_minute_second, so 2008_08_09_23_56_05 means August
9, 2008, at 11:56 pm.  To help you out, the default owed hours and number
of weeks in the semester is given next to the archive name, as well as the
total number of workshifts.  Choosing an archive from the middle of the
semester you want is a safe bet.<br/>
If you don't want to import archived settings, choose the blank option at the top.
<select name='settings_archive' onchange='coupledcheckbox(this.value)'>
<option>
<?php
//';
switch ($current_sem) {
case 'summer':
  $sem_order = array('summer','fall','spring');
  break;
case 'fall': case 'spring':
  $sem_order = array('spring','fall','summer');
  break;
default:
  $sem_order = array('spring','summer','fall');
}
  if (count($options_array['unknown'])) {
    $sem_order[] = 'unknown';
  }
foreach ($sem_order as $sem) {
  print "<optgroup label='" . ucfirst($sem) . "'>\n";
  foreach ($options_array[$sem] as $start_time => $sem_archives) {
    print "<optgroup label='" . date('F j, Y',$start_time) . "'>\n";
    foreach ($sem_archives as $archive_data) {
      print "<option value='" . escape_html($archive_data['name']) . "'>";
      print escape_html($archive_data['name']) . ", default owed: " .
        $archive_data['owed'] . " hours/week, number of shifts: " .
        $archive_data['shifts'] . ", last modified: " .
        date('F j, Y',$archive_data['mod_date']) . "\n";
    }
    print "</optgroup>";
  }
  print "</optgroup>";
}
?>
</select><br/>
    (If you think there are way too many backups there, feel free to
     <a href='delete_backup_database.php' target='del_backup'>delete some of them</a>.)
<hr/>
<input type=checkbox name='delete_prefs_bool'>
Check this box to delete the preference forms (if you're starting a new
semester, they're from last semester).<br/>
<input id='delete_assigned_bool' type=checkbox name='delete_assigned_bool'
onchange='uncouplecheckbox(this.checked)'>
Check this box to delete the workshift assignments (if you're starting a new
semester, they're from last semester).  If you've selected archived settings
above, this box is checked by default, since it doesn't make sense to import
the shifts with the old workshifters assigned to them -- they'll be blanked out.<br/>
<input type=checkbox name='reset_middle_bool'>
Check this box to delete fines, reset all owed hours to the default,
and delete all the weekly sheets.  You should only do this after the
previous semester has well and truly finished, and any hours/fines
disputes are over, check-out slips have been turned in, etc.  The data
will still be in the backup, but not in the current database.  
<hr/>
<input type=submit value='Start the new semester!'/>
</form>
</body>
</html>
<?
//';
exit;
}

print "<h4>This may take some time -- be patient and wait until the end.</h4>";
require_once('backup_database.php');
print "<hr>";

if (isset($_REQUEST['settings_archive']) && strlen($_REQUEST['settings_archive'])) {
  $db->debug = true;
  $arch = $archive_pre . $_REQUEST['settings_archive'];
  $tmppre = "zztempsettings_";
  $settings_tables = array('static_data','fining_data','master_shifts');
  $db->Execute("drop table if exists `zz" . join('`, `zz',$settings_tables) .
               "`,`$tmppre" . join("`, `$tmppre",$settings_tables));
  function delete_temp_tables_then_die($errno,$errstr,$errfile,$errline,$errcontext) {
    global $db, $tmppre, $settings_tables, $user_errmsg, $admin_email, $house_name,
      $bug_report_url, $old_errhandler;
    $db->Execute("drop table if exists `$tmppre" . join("`, `$tmppre",$settings_tables));
    $old_errhandler($errno,$errstr,$errfile,$errline,$errcontext);
  }
  $old_errhandler = set_error_handler('delete_temp_tables_then_die');
  foreach ($settings_tables as $table) {
    $db->Execute("create table `$tmppre$table` like `$table`");
    $db->Execute("insert into `$tmppre$table` select * from " .
                 bracket($arch . "_$table"));
  }
  function rename_tempmap($arg) {
    global $tmppre;
    return bracket($tmppre . $arg) . " to " . bracket("$arg");
  }
  function rename_origmap($arg) {
    global $tmppre;
    return bracket($arg) . " to " . bracket("zz$arg");
  }
  $db->Execute("rename table " .
               join(",", array_map('rename_origmap',$settings_tables)) . "," .
               join(",",array_map('rename_tempmap',$settings_tables)));
  $db->debug = false;
  set_error_handler($old_errhandler);
}
               

//semester *must* start on a Monday
$sem_start = $_REQUEST['semester_start'];
if (!preg_match('/\d\d\d\d-\d\d?-\d\d?/',$sem_start)) {
  exit("Your semester start date is not in the proper format.  " .
       "Enter the four-digit year, then a dash, then the two-digit " .
       "month, then a dash, then the two-digit date, then a dash, like " .
       "2009-08-26 for August 26, 2009.");
}
if (date('w',strtotime($sem_start))!=1) {
  exit("Your semester start date is not a Monday!  " .
       "Please go back and change it!");
}
set_static('semester_start',$sem_start);

//set up the owed table.  It will be modified as needed if the default number
//of hours changes, the house list changes, etc.
create_and_update_weekly_totals_data();
//we're about to delete all the data accumulated over the semester
//we call delete_weeks.php to do it.
if (array_key_exists('reset_middle_bool',$_REQUEST)) {
  print "<h4>About to reset owed hours . . .</h4>";
  $db->SetFetchMode(ADODB_FETCH_ASSOC);
  $res = $db->Execute("show columns from `weekly_totals_data` like 'owed%'");
  $query = "update `weekly_totals_data` set ";
  $owed_default = get_static('owed_default',5);
  while ($row = $res->FetchRow()) {
    $query .= "`" . $row['Field'] . "` = $owed_default, ";
  }
  $query = substr($query,0,-2);
  $db->Execute($query);
   print ("<h4>About to delete old fines . . .</h4>");
   //we can't have transactions (with rollbacks) when we're deleting
   //tables, but now that that's been done, we can use it.  Probably
   //not useful, but who knows.
   $db->StartTrans();
   //unset any manual current week setting
   set_static('cur_week',null);
   $db->Execute("delete from `fining_data`");
   set_mod_date('fining_data');
   print ("<h4>Delete successful</h4>");
   if ($db->CompleteTrans()) {
     print "<h4>Succeeded!</h4>\n";
   }
   else {
     exit("<h3>Resetting of fines failed!  " .
          "Changes were not made Please email " .
       "the administrator (" . admin_email() . ")</h3>");
   }
   print("<h4>About to delete old weeks . . .</h4>");
   $_REQUEST['start_week'] = 0;
   //just overkill
   $_REQUEST['end_week'] = 100;
   //this page doesn't output that much, but it tells the user what it does
   require_once('delete_weeks.php');
   print("<h4>Delete succeeded!</h4>");
}

//get rid of preference forms and assigned shifts
if (array_key_exists('delete_prefs_bool',$_REQUEST)) {
  print("<h4>About to delete old preferences . . .</h4>");
  //above, we were deleting tables, so we couldn't 
  $db->StartTrans();
  $db->Execute("delete from `wanted_shifts`");
  set_mod_date('wanted_shifts');
  //legacy -- should never exist.
  if (table_exists('unwanted_shifts')) {
    $db->Execute("drop table `unwanted_shifts`");
  }
  $db->Execute("update `personal_info` set `notes` = null, `submit_date` = 0");
  for ($ii = 0; $ii < 7; $ii++) {
    $db->Execute("update `personal_info` set `av_$ii` = 0");
  }
  set_mod_date('personal_info');
  if ($db->CompleteTrans()) {
    print("<h4>Deleting of old preferences succeeded!</h4>");
  }     
  else {
    exit("<h3>Couldn't delete old preferences!</h3>");
  }
}

if (array_key_exists('delete_assigned_bool',$_REQUEST)) {
  print("<h4>About to delete assigned shifts . . .</h4>");
  $db->StartTrans();
  foreach ($days as $day) {
    $db->Execute("update `master_shifts` set `$day` = null where `$day` != ?",
                 array($dummy_string));
  }
  set_mod_date('master_shifts');
  if ($db->CompleteTrans()) {
    print("<h4>Deleting of assigned shifts succeeded!</h4>");
  }     
  else {
    exit("<h3>Couldn't delete assigned shifts!</h3>");
  }
}
?>
All done!  You should go and make sure the <a href='basic_consts.php'>basic settings</a> are right.
</body>
</html>
