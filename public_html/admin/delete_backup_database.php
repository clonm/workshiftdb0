<?php
$running_shell = !array_key_exists('REQUEST_URI',$_SERVER);
if (!$running_shell) {
?>
<html><head><title>Delete backed-up database</title></head><body>
<?php
}
//interface to delete backup database(s).  Main thing here is that the
//script looks through backups and figures out which ones are
//redundant, and/or corrupt.  Then user can press a button to select
//those databases.  Backups are redundant if there is a later backup
//for the same semester (not the current semester) which has a larger
//week number, as many or more assigned shifts, and a table modified
//later than or at the same time as this backup's last-modified table.
//See the functions below for more details.

//the president and workshift might both need to delete backups.
$require_user = array('workshift','president');
require_once('default.inc.php');
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}
if (!isset($dbnames)) {
  $dbnames = get_backup_dbs();
}
//no longer functional, and shouldn't be necessary, since we now cache
//archive data in a global table, so processing is speedy
//if (!isset($start_db_index) && isset($_REQUEST['start_db_index'])) {
//  $start_db_index = $_REQUEST['start_db_index'];
//}

if (!$running_shell && !array_key_exists('backup_name',$_REQUEST)) {
  ?>
 If you are running low on space, or if you want to clean up very old backups,
you can delete old backup sets here.  It's best just to delete the
redundant/corrupt ones.  This page may take a long time to load.<p>
                                                 
The backed-up databases are backed up by date -- 
year_month_day_hour_minute_second.
You should probably delete the oldest one, if you are just saving space.
<form action='<?=this_url()?>' method=POST>
<select id='backup_name' name='backup_name[]' multiple
title='double-click to view archive in a new window'>
  <?php
 foreach ($dbnames as $backup) { 
   print "<option value='" . escape_html($backup) . "'>" . 
       escape_html($backup) . "\n";
 }
?>
</select><br>
<input type=submit value='Delete backup database(s)'></form>
<hr/>
<?php
 $delete_dbs = get_dbs_to_delete();
 if ($delete_dbs[2] != count($dbnames)) {
?>

Due to the large number of backups, the buttons below will only work on the
first <?=$delete_dbs[2]?> backups in the list above.  Delete some backups and
the selections will extend to later backups.<br/>

<?php
//You can also enter a number here and submit, so that the system will skip
//the first so many backups, and look for redundant ones after that.<br/>
//<form action=<?=this_url()?> method=get>
//<input name='start_db_index' value='<?=escape_html($start_db_index)?>'
//size=3>
//<input type=submit value='Start looking for redundant databases here'/>
//</form>
?>

<?php
}
 if (count($delete_dbs[0])) {
?>
<input id='button_redund' type='button' value='Select all <?=count($delete_dbs[0])?> redundant backup(s)' 
onclick='select_redundant()'><br/>
(Pressing this button will select backups which seem to just be intermediate
backups, made automatically by the system when you changed things, and not
useful since the semester in which they were made is finished.  Please check
and make sure that this is the case.)<br/>
<?php
}
if (count($delete_dbs[1])) {
?>
<input id='button_corrupt' type='button' value='Select all <?=count($delete_dbs[1])?> corrupt backup(s)'
onclick='select_corrupt()'><br/>
(This will select backups that do not seem to be complete -- they may be from an
earlier version of the program, or something may have gone wrong with them.  They
are unlikely to contain any useful information.)
<?php
}
?>
</body>
<script type='text/javascript'>
//javascript so user can press button and select redundant/corrupt backups
var sel_elt = document.getElementById('backup_name');
var button_redund = document.getElementById('button_redund');
var button_corrupt = document.getElementById('button_corrupt');
var redund_flag = false;
var corrupt_flag = false;
sel_elt.ondblclick = show_backup;
<?php
//get redundant backups
$redunds = array_flip($delete_dbs[0]);
//we're about to output a javascript array, and the javascript code
//will test for value's.  Everything will be nonzero except for the
//first one, but why take chances.
foreach ($redunds as $key => $junk) {
$redunds[$key] = 1;
}
$corrupts = array_flip($delete_dbs[1]);
foreach ($corrupts as $key => $junk) {
$corrupts[$key] = 1;
}

js_assoc_array('redunds',$redunds);
js_assoc_array('corrupts',$corrupts);
?>
function select_redundant() {
redund_flag = !redund_flag;
for (var ii=sel_elt.options.length-1;ii>=0;ii--) {
  if (redunds[sel_elt.options[ii].value]) {
    sel_elt.options[ii].selected = redund_flag;
  }
}
button_redund.value = (redund_flag?'Uns':'S') + 'elect all ' +
'<?=count($delete_dbs[0])?> redundant backups';
return true;
}

function select_corrupt() {
corrupt_flag = !corrupt_flag;
for (var ii=sel_elt.options.length-1;ii>=0;ii--) {
  if (corrupts[sel_elt.options[ii].value]) {
    sel_elt.options[ii].selected = corrupt_flag;
  }
}
button_corrupt.value = (corrupt_flag?'Uns':'S') + 'elect all ' +
'<?=count($delete_dbs[1])?> corrupt backups';
return true;
}

//function to display backup (via index.php) when clicked.
function show_backup(e) {
 if (!e) e = window.event;
 if (!e) return true;
var code;
var targ;
if (e.target) targ = e.target;
else if (e.srcElement) targ = e.srcElement;
var show = window.open('index.php?archive=' + targ.value,'view_backup'); 
show.focus();
return true;
}
</script>
</html>
<?php 
  exit;
}

//ok, time to delete

//here's what we're deleting
$backup_arr = null;
if (array_key_exists('backup_name',$_REQUEST)) {
$backup_arr = $_REQUEST['backup_name'];
}
//always array, unless we're being included from another file, in
//which case the other file wants us to delete everything redundant
//that we can.
if (!is_array($backup_arr)) {
$delete_dbs = get_dbs_to_delete();
$backup_arr = array_merge($delete_dbs[0],$delete_dbs[1]);
}

$num_deleted = 0;
//pretty standard deleting.
foreach ($backup_arr as $backup) {
if (!check_php_time()) {
?>
<h2>Ran out of time deleting backups.</h2>
<form action='<?=this_url()?>' method='post'>
<?=print_gets_for_form() ?>
<?php
    for ($ii = $num_deleted; $ii < count($backup_arr); $ii++) {
?>
<input type='hidden' name='backup_name[]'
value='<?=escape_html($backup_arr[$ii])?>'/>

<?php
    }
?>
    <input type='submit' value='Delete some more of the <?=count($backup_arr)-$num_deleted?> backups'>
</form>
<?php
  exit;
}
$num_deleted++;
$ret = true;
if ($running_shell) {
  print $house_name . " " . $backup . "\n";
}
else {
  print "<h4>Deleting " . escape_html($backup) . "</h4>\n";
}

//use numbered columns because columns have database name in them
$oldfetch = $db->fetchMode;
$db->SetFetchMode(ADODB_FETCH_NUM); 

//quote whatever funky name they gave us to avoid mysql regular expressions
$res = $db->Execute("show tables like ?",
                    array(quote_mysqlreg("$archive_pre$backup") . '_%'));
if (is_empty($res)) {
  print("<h4>Backup " . escape_html($backup) . " does not exist.</h4>");
  continue;
}
$db->SetFetchMode($oldfetch);
//we want to delete as much as possible, not dying ever
janak_fatal_error_reporting(0);
//did every single drop table succeed?
while ($row = $res->FetchRow()) {
    //delete house list last, so it will still show up in list of things to
    //delete if this fails
    if ($row[0] === "$archive_pre{$backup}_house_list") {
      continue;
    }
    $rs = $db->Execute("drop table " . bracket($row[0]));
    $ret &= $rs->EOF;
  }
  if ($ret) {
    $rs = $db->Execute("drop table " .  
      bracket("$archive_pre{$backup}_house_list"));
    $db->Execute("delete from `GLOBAL_archive_data` where `archive` = ?",
      array($backup));
    $ret &= $rs->EOF;
  }
  if ($ret) {
    if (!$running_shell) {
      print "<h4>Done with " . escape_html($backup) . "</h4>\n";
    }
  }
  else {
    janak_error("<h4>There was an error deleting the backup</h4>\n");
  }
}
if (!$running_shell) {
  echo "<h3>All done!\n</h3>";
}

//workhorse -- finds dbs that shouldn't be useful anymore
function get_dbs_to_delete() {
  global $db,$sem_start;
  $corrupt = array();
  $num_done = 0;
  $archive_res = $db->Execute("select `archive`, " .
    "`semester_start` as `semester`, " .
    "unix_timestamp(`mod_date`) as `mod_date`,`cur_week` as `week`, " .
    "`num_wanted` as `wanted`, " .
    "`num_assigned` as `master`, unix_timestamp(`creation`) as `time`, " .
    "`autobackup`+0 as `autobackup` from `GLOBAL_archive_data`");
  while ($row = $archive_res->FetchRow()) {
    $num_done++;
    if (!isset($backups)) {
      $backups = array();
    }
    if (!isset($backups[$row['semester']])) {
      $backups[$row['semester']] = array();
    }
    $backups[$row['semester']][] = $row;
  }
  if (!isset($backups)) {
    return array(array(),$corrupt, $num_done);
  }
  //back to normal
  $archive = '';
  //sort by semester (the key)
  ksort($backups);
  if (!strlen($sem_start)) {
    $bkeys = array_keys($backups);
    $sem_start = end($bkeys);
  }
  $to_delete = array();
  //ok, time to find databases we can delete
/*   print "<pre>"; */
  foreach ($backups as $sem => $backupsems) {
    //don't delete anything from the current semester
    if ($sem == $sem_start) {
      continue;
    }
    //are there no backups here?  I don't know how that happens
    if (!$ct = count($backupsems)) {
      continue;
    }
    //sort list, then go through, eliminating < elements.  See below
    //for comp_backup_dbs
    usort($backupsems,'comp_backup_dbs');
    $curct = $ct;
    for ($ii = 0; $ii < $ct; $ii++) {
/*       print "doing \n"; */
/*       print_r($backupsems[$ii]); */
      //always leave at least 2 backups per semester
      if ($curct <= 2) {
        break;
      }
      //last backup of semester is always non-redundant
      if ($ii == $ct-1) {
        break;
      }
      //nothing here?  Don't know how that could happen
      if (!$backupsems[$ii]) {
        continue;
      }
      //check for redundancies.  Go backwards, because it's likely
      //that later archive will make redundant.
      for ($jj = $ct-1; $jj > $ii; $jj--) {
/*         print "comparing \n"; */
/*         print_r($backupsems[$jj]); */
/*         var_dump(comp_backup_dbs($backupsems[$ii],$backupsems[$jj])); */
        //is this backup less than later one?
        if (comp_backup_dbs($backupsems[$ii],$backupsems[$jj]) < 0) {
          $to_delete[] = $backupsems[$ii]['archive'];
          $curct--;
          break;
        }
      }
    }
  }
  return array($to_delete,$corrupt,$num_done);
}

//function which allows us to figure out which dbs are "less" than others,
//so can be deleted
function comp_backup_dbs($arr1,$arr2) {
  //user backups are always on top, and incomparable
  if (!$arr1['autobackup'] && !$arr2['autobackup']) {
    return 0;
  }
  //is first greater than second?  Compare each attribute.  Time is a
  //bit tricky -- if one of them is user-named, we can't look at the
  //times, so we need to skip that attribute.
  $raw_comp1 = ((!$arr1['autobackup'] || !$arr2['autobackup'] || 
                 $arr1['time'] >= $arr2['time']) && 
                $arr1['wanted'] >= $arr2['wanted'] &&
                $arr1['week'] >= $arr2['week'] &&
                $arr1['mod_date'] >= $arr2['mod_date'] &&
                $arr1['master'] >= $arr2['master']);
  //if first was a user backup, it can't possibly be less.
  if (!$arr1['autobackup']) {
    //it wasn't more, so must be 0
    if (!$raw_comp1) {
      return 0;
    }
    else {
      //first was more
      return 1;
    }
  }
  $raw_comp2 = ((!$arr1['autobackup'] || !$arr2['autobackup'] ||
                 $arr2['time'] >= $arr1['time']) && 
                $arr2['wanted'] >= $arr1['wanted'] &&
                $arr2['week'] >= $arr1['week'] &&
                $arr2['mod_date'] >= $arr1['mod_date'] &&
                $arr2['master'] >= $arr1['master']);
  if (!$arr2['autobackup']) {
    if (!$raw_comp2) {
      return 0;
    }
    else {
      return -1;
    }
  }
  return $raw_comp1-$raw_comp2;
}
if (!$running_shell) {
?>
</body></html>
<?php
}
?>
