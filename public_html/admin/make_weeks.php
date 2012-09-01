<?php 
require_once('default.inc.php');
if (!isset($_REQUEST['start_week'])) { 
?>
<html><head><title>Generate Weeks</title></head><body>
<h2>You should no longer need to use this page.  Weeks will be generated automatically
when you try to view them.  If you have questions, please email workshiftadmin@gmail.com</h2>
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
$res = $db->Execute("show tables like ?",array(quote_mysqlreg($archive . 'week_') . '%'));
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
  $tbl = $archive . "week_$ii";
  $db->Execute("drop table if exists `$tbl`");
  if (!$db->Execute('CREATE TABLE if not exists ' . bracket($tbl) . ' (' . 
		    bracket('autoid') . ' INT(11) AUTO_INCREMENT PRIMARY KEY, '
		    . bracket('date') . " DATE DEFAULT " . 
		    $db->qstr($week_date) . ', ' . 
		    bracket('day') . ' VARCHAR(20) DEFAULT NULL, ' .
		    bracket('workshift') . ' VARCHAR(50) DEFAULT NULL, ' .
		    bracket('member_name') . ' int(11) DEFAULT NULL, ' .
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
               bracket('master_week') . " READ");
  $db->StartTrans();
  $db->Execute("delete from `$tbl`");
  $db->Execute('INSERT INTO ' . bracket($tbl) . 
               " SELECT NULL, " . 
               "adddate('$week_date', interval case `day`" .
               join(" ",array_map('case_days_sql',$days,array_keys($days))) .
" end day) AS `date`, `day`, `workshift`, `member_name`, `hours`, null as `notes`,`shift_id`, " .
               "`start_time`, `end_time`, 0 as `online_signoff`, null as `verifier` FROM `{$archive}master_week`");
  set_mod_date($tbl);
  set_mod_date($tbl . '_zz_create');
  $db->CompleteTrans();
  $db->Execute('UNLOCK TABLES ');
  if (!isset($suppress_output)) {
    echo "<a href='week.php?week=$ii'>Week $ii</a> successfully created!<p>";
  }
}

create_and_update_weekly_totals_data();

if (!isset($suppress_output)) {
  if ($start_week == $end_week) {
  ?>
<script type='text/javascript'>
    document.location.href='week.php?week=<?=$start_week?><?=$archive?'&archive=' . escape_html($archive):''?>'
</script>
<?php
   }
?>
</body>
</html>
<?php
    }
?>
