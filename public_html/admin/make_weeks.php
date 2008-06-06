<?php 
require_once('default.inc.php');
if (!isset($_REQUEST['start_week'])) { 
?>
<html><head><title>Generate Weeks</title></head><body>
<h2>You should no longer need to use this page.  Weeks will be generated automatically
when you try to view them.  If you have questions, please email janak@berkeley.edu</h2>
<form action="<?=escape_html($_SERVER['REQUEST_URI'])?>" method=post>
<table>
<tr>
<td>Start week</td><td><input name='start_week' type=text value=3></td></tr>
<tr>
<td>End week</td><td><input name='end_week' type=text value=18></td></tr></table>
<input type=submit value=Submit>
</form>
   <?php exit; } 
if (!isset($suppress_output)) {
?>
<html><head><title>Make weeks results</title></head><body>
<p>
<?php 
   }
$start_week = $_REQUEST['start_week'];
$end_week = $_REQUEST['end_week'];
if (!is_numeric($start_week)) {
  exit("Error! the starting week, '" . escape_html($start_week) . "' is not a number");
}
if (!is_numeric($end_week)) {
  exit("Error! the ending week, '" . escape_html($end_week) . "' is not a number");
}
#$db->debug = true;
$db->SetFetchMode(ADODB_FETCH_NUM);
$res = $db->Execute("show tables like ?",array(quote_mysqlreg('week_') . '%'));
$db->SetFetchMode(ADODB_FETCH_ASSOC);
if (!isset($_REQUEST['overwrite'])) {
  $week_exists = array();
  while ($row = $res->FetchRow()) {
    $num = substr($row[0],5);
    if (is_numeric($num) && $start_week <= $num && $num <= $end_week) {
      $week_exists[] = $num;
    }
  }
  if (count($week_exists)) {
    sort($week_exists);
    janak_error("Week(s) " . join(', ',$week_exists) . " already exist(s).  " .
                  "Either <a href='delete_weeks.php'>delete the old sheets</a> " .
                  "before you create the new ones, or press back and change the" .
                  " weeks for which you want to create sheets.");
  }
}
$dummy_string = get_static('dummy_string');


update_master_week();
//start creating the weekly sheets
$start_date = get_static('semester_start');
if (strlen($start_date) == 0) {
  exit("<p>You haven't entered the start of the semester in " .
"<a href='basic_consts.php'>basic_consts.php</a>");
}

if (!isset($start_week) || !isset($end_week)) {
  exit("<p>No start week or end week given!");
}
$start_date = explode('-',$start_date);

function case_days_sql($day,$ind) {
  return "when '$day' then " . ($day == 'Weeklong'?6:$ind);
}

$days[] = 'Weeklong';
//create every table we can
for ($ii = $start_week; $ii <= $end_week; $ii++) {
  //the day the week started
  $week_date = date('Y-m-d',mktime(0,0,0,$start_date[1],
                                   $start_date[2]+7*$ii,$start_date[0]));
  $tbl = "week_$ii";
  $db->Execute("drop table if exists `$tbl`");
  if (!$db->Execute('CREATE TABLE if not exists ' . bracket($tbl) . ' (' . 
		    bracket('autoid') . ' INT(11) AUTO_INCREMENT PRIMARY KEY, '
		    . bracket('date') . " DATE DEFAULT " . 
		    $db->qstr($week_date) . ', ' . 
		    bracket('day') . ' VARCHAR(20) DEFAULT NULL, ' .
		    bracket('workshift') . ' VARCHAR(50) DEFAULT NULL, ' .
		    bracket('member_name') . ' VARCHAR(50) DEFAULT NULL, ' .
		    bracket('hours') . ' DOUBLE DEFAULT NULL, ' .
                    '`notes` longtext default null, ' .
                    '`shift_id` int(11) default null, ' .
                    '`start_time` time default null, ' .
                    '`end_time` time default null, ' .
                    '`online_signoff` timestamp default 0, ' .
                    '`verifier` varchar(50) default null)')) {
    trigger_error("Error creating $tbl: " . $db->ErrorMsg(),E_USER_ERROR);
  }
  //just paranoia here
  $db->Execute('LOCK TABLES ' . bracket($tbl) . ' WRITE, ' . 
               bracket('master_shifts') . 
               ' READ, ' . bracket('master_week') . " READ");
  $db->StartTrans();
  $db->Execute("delete from `$tbl`");
  $db->Execute('INSERT INTO ' . bracket($tbl) . 
               " SELECT NULL, " . 
               "adddate('$week_date', interval case `day`" .
               join(" ",array_map('case_days_sql',$days,array_keys($days))) .
" end day) AS `date`, `day`, `workshift`, `member_name`, `hours`, null as `notes`,`shift_id`, " .
               "`start_time`, `end_time`, 0 as `online_signoff`, null as `verifier` FROM `master_week`");
  set_mod_date($tbl);
  set_mod_date($tbl . '_zz_create');
  $db->CompleteTrans();
  $db->Execute('UNLOCK TABLES ');
  if (!isset($suppress_output)) {
    echo "<a href='week.php?week=$ii'>Week $ii</a> successfully created!<p>";
  }
  if ($USE_MYSQL_FEATURES) {
    $db->Execute("GRANT SELECT ON " . bracket($tbl) . " TO ?@?",
                 array($house_member,$member_loc));
  }
}
$db->SetFetchMode(ADODB_FETCH_NUM); 
$check_tables = $db->Execute("SHOW TABLES LIKE ?",array("week\_%"));
$db->SetFetchMode(ADODB_FETCH_ASSOC); 
$week_tables = array();
while ($tbl = $check_tables->FetchRow()) {
  $ii = substr($tbl[0],5);
  $week_tables[$ii] = 1;
}
//we need to create weekly_totals_data to be the largest it can be.  It's
//possible that a previous run of this script had not created the largest
//possible weekly_totals_data sheet because there were missing weeks which
//this run has now filled in.
for ($ii = 0; array_key_exists($ii,$week_tables) && $ii <= 100; $ii++) {}
if ($ii === 100) {
  trigger_error("There are way too many weekly tables for any good reason.",
                E_USER_ERROR);
}
$final_week = $ii-1;

//check to see if weekly_totals_data (which has the owed hours for each
//person) exists
if (!$db->Execute('CREATE TABLE if not exists' . bracket('weekly_totals_data') . ' (' .
                  bracket('autoid') . 
                  ' INT(11) AUTO_INCREMENT PRIMARY KEY, ' . 
                  bracket('member_name') . ' VARCHAR(50) UNIQUE KEY, ' .
                  bracket('notes') . ' LONGTEXT)')) {
  trigger_error("Could not create table weekly_totals_data: " . $db->ErrorMsg(),
                E_USER_ERROR);
}
if ($USE_MYSQL_FEATURES) {
  $db->Execute("GRANT SELECT ON " . bracket('weekly_totals_data') . 
               " TO ?@?",array($house_member,$member_loc));
}
//insert columns -- just laziness that they're not inserted above if it's a
//new table, but code is shorter this way, although SQL is probably slower
//how many of the right columns do we have?
$check_data = $db->Execute('SHOW COLUMNS FROM ' . 
                           bracket('weekly_totals_data') . ' LIKE ' . 
                           "'owed %'");
//count how many columns we have
$ii = 0;
while ($row = $check_data->FetchRow()) {
  $ii++;
}
//what should be inserted into each column?
if (!isset($owed_default)) {
  $owed_default = get_static('owed_default',5);
}
//for the remaining columns, insert them
for (; $ii <= max($final_week,get_static('tot_weeks',18)); $ii++) {
  //they get inserted after the previous one
  if ($ii > 0) {
    $db->Execute('ALTER TABLE ' . bracket('weekly_totals_data') . ' ADD ' . 
                 bracket("owed $ii") . ' FLOAT(4) NOT NULL DEFAULT ? AFTER ' .
                 bracket('owed ' . ($ii-1)),array($owed_default));
  }
  //unless it's the first column, in which case they get inserted
  //after the name
  else {
    $db->Execute('ALTER TABLE ' . bracket('weekly_totals_data') . ' ADD ' . 
		 bracket("owed 0") . ' FLOAT(4) NOT NULL DEFAULT ? AFTER ' . 
                 bracket('member_name'),array($owed_default));
  }
  //insert default hours owed
  $db->Execute('UPDATE ' . bracket('weekly_totals_data') . ' SET ' . 
               bracket("owed $ii") . ' = ?',array($owed_default));
}

if (!isset($suppress_output)) {
  if ($start_week == $end_week) {
  ?>
<script type='text/javascript'>
document.location.href='week.php?week=<?=$start_week?>'
</script>
<?php
   }
?>
</body>
</html>
<?php
    }
?>
