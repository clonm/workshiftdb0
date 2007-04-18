<?php
require_once('default.inc.php');
$oldfetch = $db->fetchMode;
//use numbered columns because columns have database name in them
$db->SetFetchMode(ADODB_FETCH_NUM); 
if (!array_key_exists('backup_name',$_REQUEST)) {
  $delete_dbs = get_dbs_to_delete();
  ?>
<html><head><title>Delete backed-up database</title></head><body>
 If you are running low on space, or if you want to clean up very old backups,
you can delete old backup sets here.  There is no particular reason to do this,
so unless you have some good reason to, why do it?<p>
                                                 
The backed-up databases are backed up by date -- 
year_month_day_hour_minute_second.
You should probably delete the oldest one, if you are just saving space.
                                                    
<form action='<?=this_url()?>' method=POST>
<select id='backup_name' name='backup_name[]' multiple title='double-click to view archive in a new window'>
  <?php
//every backup should have a house list 
//% is a wildcard matching any number of characters, _ matches any one
//unless it's escaped
$res = $db->Execute("show tables like ?",array('%\_house\_list'));
$dbnames = array();
while ($row = $res->FetchRow()) {
  $dbnames[] = substr($row[0],strlen($archive_pre),-1*strlen('_house_list'));
}
 foreach ($dbnames as $backup) { 
   print "<option value='" . escape_html($backup) . "'>" . 
       escape_html($backup) . "\n";
 }
?>
</select><br>
<input type=submit value='Delete backup database'></form>
<input id='button_redund' type=submit value='Select all redundant backups' 
onclick='select_redundant()'><br/>
(Pressing this button will select backups which seem to just be intermediate
backups, made automatically by the system when you changed things, and not
useful since the semester in which they were made is finished.  Please check
and make sure that this is the case.)<br/>
<input id='button_corrupt' type=submit value='Select all corrupt backups'
onclick='select_corrupt()'><br/>
(This will select backups that do not seem to be complete -- they may be from an
earlier version of the program, or something may have gone wrong with them.  They
are unlikely to contain any useful information.)
</body>
<script type='text/javascript'>
var sel_elt = document.getElementById('backup_name');
var button_redund = document.getElementById('button_redund');
var button_corrupt = document.getElementById('button_corrupt');
var redund_flag = false;
var corrupt_flag = false;
 sel_elt.ondblclick = show_backup;
<?php
$redunds = array_flip($delete_dbs[0]);
if (count($redunds)) {
  $redunds[$delete_dbs[0][0]] = 1;
}
$corrupts = array_flip($delete_dbs[1]);
if (count($corrupts)) {
  $corrupts[$delete_dbs[1][0]] = 1;
}
js_assoc_array('redunds',$redunds);
js_assoc_array('corrupts',$corrupts);
?>
function select_redundant() {
  redund_flag = !redund_flag;
  for (var ii=sel_elt.options.length-1;ii>0;ii--) {
    if (redunds[sel_elt.options[ii].value]) {
      sel_elt.options[ii].selected = redund_flag;
    }
  }
  button_redund.value = (redund_flag?'Uns':'S') + 'elect all redundant backups';
}

function select_corrupt() {
  corrupt_flag = !corrupt_flag;
  for (var ii=sel_elt.options.length-1;ii>0;ii--) {
    if (corrupts[sel_elt.options[ii].value]) {
      sel_elt.options[ii].selected = corrupt_flag;
    }
  }
  button_corrupt.value = (corrupt_flag?'Uns':'S') + 'elect all corrupt backups';
}

 function show_backup(e) {
   if (!e) e = window.event;
   if (!e) return true;
  var code;
  var targ;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  var show = window.open('index.php?archive=' + targ.value,'view_backup'); 
  show.focus();
 }
</script>
</html>
<?php 
    exit;
}

$backup_arr = $_REQUEST['backup_name'];
if (!is_array($backup_arr)) {
  $delete_dbs = get_dbs_to_delete();
  $backup_arr = array_merge($delete_dbs[0],$delete_dbs[1]);
}

foreach ($backup_arr as $backup) {
  $ret = true;
  print "<h4>Deleting " . escape_html($backup) . "</h4>\n";
  //quote whatever funky name they gave us to avoid mysql regular expressions
  $res = $db->Execute("show tables like ?",
                      array(quote_mysqlreg("$archive_pre$backup") . '_%'));
  if (is_empty($res)) {
    janak_error("Backup $backup does not exist.");
  }
  //we want to delete as much as possible, not dying ever
  janak_fatal_error_reporting(0);
  //did every single drop table succeed?
  while ($row = $res->FetchRow()) {
    //delete house list last, so it will still show up in list of things to
    //delete if this fails
    if ($row[0] === "$archive_pre{$backup}_house_list") {
      continue;
    }
    $ret &= $db->Execute("drop table " . bracket($row[0]));
  }
  if ($ret) {
    $ret &= $db->Execute("drop table " . 
                         bracket("$archive_pre{$backup}_house_list"));
  }
  if ($ret) {
    print "<h4>Done with " . escape_html($backup) . "</h4>\n";
  }
  else {
    janak_error("<h4>There was an error deleting the backup</h4>\n");
  }
}
echo "<h3>All done!\n</h3>";


//workhorse -- finds dbs that shouldn't be useful anymore
function get_dbs_to_delete() {
  global $db, $archive,$dummy_string,$days,$archive_pre;
  $corrupt = array();
  //get all archives
  $res = $db->Execute("show tables like ?",array('%' . quote_mysqlreg('_house_list')));
  //go through and get info on each backup
  while ($row = $res->FetchRow()) {
    $matches = array();
    //setting this makes all functions think we're in this archived db
    $archive = substr($row[0],0,-strlen('house_list'));
    //this array has all salient information
    $db_props = array();
    $db_props['archive'] = substr($archive,strlen($archive_pre),-1);
    //did the user name this him/herself?
    if ($db_props['autobackup'] = preg_match('/^zz_archive_([0-9]{4,4})_' .
                                         '([0-9]{2,2})_([0-9]{2,2})_' .
                                         '([0-9]{2,2})_([0-9]{2,2})_' .
                                         '([0-9]{2,2})_house_list$/',
                                             $row[0],$matches)) {
    //here's the timestamp
      $db_props['time'] = mktime($matches[3],$matches[4],$matches[5],$matches[1],
                                 $matches[2],$matches[0]);
    }
    //the archive was set above, so this is the archive's modified date
    if (table_exists('modified_dates')) {
      $mod_row = $db->GetRow("select unix_timestamp(max(`mod_date`)) " .
                             "from " . bracket($archive . 'modified_dates'));
      $db_props['mod_date'] = $mod_row[0];
    }
    else {
      $db_props['mod_date'] = null;
    }
    if (table_exists('wanted_shifts')) {
      $wanted_row = $db->GetRow("select count(*) from " .
                                bracket($archive . 'wanted_shifts'));
      $db_props['wanted'] = $wanted_row[0];
    }
    else {
      $db_props['wanted'] = -1;
    }
    if (table_exists('master_shifts')) {
      $db_props['master'] = 0;
      foreach ($days as $day) {
        $master_row = $db->GetRow("select count(*) from " .
                                  bracket($archive . 'master_shifts') .
                                  " where `$day` is not null and `$day` != ?",
                                  array($dummy_string));
        $db_props['master'] += $master_row[0];
      }
    }
    else {
      $db_props['master'] = null;
    }
    if (table_exists('static_data')) {
      $db_props['semester'] = get_static('semester_start');
    }
    else {
      $db_props['semester'] = null;
    }
    $db_props['week'] = get_cur_week();
    if (!strlen($db_props['semester']) || !strlen($db_props['mod_date']) ||
        !strlen($db_props['master'])) {
      $corrupt[] = $db_props['archive'];
      continue;
    }
    else {
      if (!isset($backups)) {
        $backups = array();
      }
      if (!isset($backups[$db_props['semester']])) {
        $backups[$db_props['semester']] = array();
      }
      $backups[$db_props['semester']][] = $db_props;
    }
  }
  if (!isset($backups)) {
    return array(array(),array());
  }
  $archive = '';
  ksort($backups);
  //don't want to delete things from current semester
  $sem_start = get_static('semester_start');
  if (!strlen($sem_start)) {
      $bkeys = array_keys($backups);
      $sem_start = end($bkeys);
  }
  $to_delete = array();
  //ok, time to find databases we can delete
  foreach ($backups as $sem => $backupsems) {
    //don't delete anything from the current semester
    if ($sem == $sem_start) {
      continue;
    }
    if (!$ct = count($backupsems)) {
      continue;
    }
    //sort list, then go through, eliminating < elements.
    usort($backupsems,'comp_backup_dbs');
    $curct = $ct;
    for ($ii = 0; $ii < $ct; $ii++) {
      if ($curct <= 2) {
        break;
      }
      if ($ii == $ct-1) {
        break;
      }
      if (!$backupsems[$ii]) {
        continue;
      }
      for ($jj = $ct-1; $jj > $ii; $jj--) {
        if (comp_backup_dbs($backupsems[$ii],$backupsems[$jj]) < 0) {
          $to_delete[] = $backupsems[$ii]['archive'];
          $curct--;
          break;
        }
      }
    }
  }
  return array($to_delete,$corrupt);
}

//function which allows us to figure out which dbs are "less" than others,
//so can be deleted
function comp_backup_dbs($arr1,$arr2) {
  //user backups are always on top, and incomparable
  if (!$arr1['autobackup'] && !$arr2['autobackup']) {
    return 0;
  }
  //is first greater than second, not including user fact?
  $raw_comp1 = ((!$arr1['autobackup'] || !$arr2['autobackup'] ||
                 $arr1['time'] >= $arr2['time']) && 
                $arr1['wanted'] >= $arr2['wanted'] &&
                $arr1['week'] >= $arr2['week'] &&
                $arr1['mod_date'] >= $arr2['mod_date'] &&
                $arr1['master'] >= $arr2['master']);
  if (!$arr1['autobackup']) {
    //it wasn't, but since first was user, must be 0
    if (!$raw_comp1) {
      return 0;
    }
    else {
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
?>
</body></html>
