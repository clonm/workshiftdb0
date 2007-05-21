<html><head><title>Online Signoff</title>
<?php
//it's ok not to be signed in when you go to this page
$require_user = 'ok_nouser';
//the help should be printed into body_insert
$body_insert = '';
require_once('default.inc.php');
if (!get_static('online_signoff',null)) {
  exit("Online signoffs are not active for your house!");
}
$cur_week = get_cur_week();
if ($cur_week == -2) {
  exit("<h3>Your workshift manager has not set up " .
       "when the semester starts yet.</h3>");
}
//make all weeks that don't exist yet
if (!table_exists("week_$cur_week")) {
  $_REQUEST['start_week'] = $cur_week;
  $_REQUEST['end_week'] = $cur_week;
  //flag so make_weeks doesn't print anything
  $suppress_output = true;
  require_once('admin/make_weeks.php');
}
//how many hours after a shift is completed can you sign off for it?
$max_signoff_time = get_static('max_signoff_time',48);
//flag to see if we successfully registered a shift
$redo_status = false;
$workshift_manager_email = get_static('workshift_manager_email',
                                      $house_name . 'wm1@usca.coop');
//is there an online shift to sign off for?
if (isset($_REQUEST['mem_name'])) {
  $redo_status = true;
  //do is here so that we can jump out of it with a "break"
  do {
    if (!$_REQUEST['mem_name']) {
      print "<h3>What's your name?!</h3>";
      break;
    }
    if (!$_REQUEST['verifier_name']) {
      print "<h3>No verifier entered!</h3>";
      break;
    }
    if ($_REQUEST['verifier_name'] == $_REQUEST['mem_name']) {
      print "<h3>You cannot verify your own shift!</h3>";
      break;
    }
    //does the verifier need to enter a password, and does the password match?
    if (get_static('online_signoff_verifier_password',true) &&
        check_passwd($_REQUEST['verifier_name'],
                     $_REQUEST['verifier_password']) <= 0) {
      print "<h3>Verifier username and password do not match.</h3>";
      break;
    }
    //is the member trying to sign off for a shift of their own?
    if (array_key_exists('member_shift_submit',$_REQUEST)) {
      if (!isset($_REQUEST['mem_shift'])) {
        print "<h3>You did not choose a shift of your " .
          "own for which to sign off!</h3>";
        break;
      }
      if (!is_numeric($_REQUEST['mem_shift_hours'])) {
        print "<h3>" . escape_html($_REQUEST['mem_shift_hours']) .
          " is not a valid number of hours for your shift</h3>";
        break;
      }
      //the mem_shift has the week and the autoid of the shift, separated by .
      $signoff_autoid = explode('.',$_REQUEST['mem_shift']);
      $signoff_weeknum = $signoff_autoid[1];
      //check the week number for validity -- it's quoted in the mysql query,
      //but who knows
      if (!ctype_digit($signoff_weeknum )) {
        print "<h3>" . escape_html($signoff_weeknum) . 
          " is not a valid week number!</h3>";
        break;
      }
      $signoff_autoid = $signoff_autoid[0];
      if (!is_numeric($_REQUEST['mem_shift_hours'])) {
        print "<h3>" . escape_html($_REQUEST['mem_shift_hours']) .
          " is not a valid number of hours " .
          "for the shift you are signing off for.</h3>";
        break;
      }
      //get the shift's values for putting in the notes, printing out.
      //Also make sure it's a valid shift to sign off for -- the
      //online_signoff flag is 0, and the time it finishes (or one day
      //after the date it was supposed to be done on, if there is no
      //end time) plus the max_signoff_time is less than the current
      //time
      $row = $db->GetRow("select `hours`,`workshift` from " .
                         bracket("week_$signoff_weeknum") .
                         " where `autoid` = ? and `member_name` = ? and " .
                         "`online_signoff` = 0 and " .
                         "date_add(date_add(`date`," .
                         "interval ifnull(`end_time`,'01:00:00:00') day_second)," .
                         "interval ? hour) >= ?",
                         array($signoff_autoid,
                               $_REQUEST['mem_name'],$max_signoff_time,
                               user_time(null,'Y-m-d H:i:s')));
      if (is_empty($row)) {
        //they can't just reload this page, because that would post their data
        print "<h3>Error!  " . escape_html($_REQUEST['mem_name']) . " cannot " .
          "sign off for the specified shift.  Please <a href='" . 
          this_url() . "'>click here</a>, reload the page, check your entries " .
          "and try again.</h3>";
        break;
      }
      //unfortunately, we need to have multiple updates clauses,
      //depending on whether or not the hours are changed
      else if ($row['hours'] != $_REQUEST['mem_shift_hours']) {
        //the notes are concatenated with any existing notes (space also)
        //the note includes that the hours were changed
        $db->Execute("update " . bracket("week_$signoff_weeknum") .
                     " set `hours` = ?, `verifier` = ?, " .
                     "`notes` = concat(if(`notes`,concat(`notes`,' '),''),?), " .
                     "`online_signoff` = ? where `autoid` = ?",
                     array($_REQUEST['mem_shift_hours'],$_REQUEST['verifier_name'],
                           'ONLINE: (usually ' . $row['hours'] . ' hrs) ',
                           user_time(null,'Y-m-d H:i:s'),$signoff_autoid));
      }
      //no hours change
      else {
        $db->Execute("update `week_$signoff_weeknum` set `verifier` = ?," .
                     "`notes` = concat(if(`notes`,concat(`notes`,' '),''),?), " .
                     "`online_signoff` = ? where `autoid` = ?",
                     array($_REQUEST['verifier_name'],'ONLINE',
                           user_time(null,'Y-m-d H:i:s'),$signoff_autoid));
      }
      $request_workshift = $row['workshift'];
      $request_hours = $_REQUEST['mem_shift_hours'];
    }
    //end of member signing off of own shift, time to do member
    //signing off of someone else's
    else if (array_key_exists('all_shift_submit',$_REQUEST)) {
      $signoff_autoid = explode('.',$_REQUEST['all_which_shift']);
      $signoff_weeknum = $signoff_autoid[1];
      $signoff_autoid = $signoff_autoid[0];
      //check the week number for validity -- it's quoted in the mysql query,
      //but who knows
      if (!ctype_digit($signoff_weeknum )) {
        print "<h3>" . escape_html($signoff_weeknum) . 
          " is not a valid week number!</h3>";
        break;
      }
      if (!is_numeric($_REQUEST['all_hours'])) {
        print "<h3>" . escape_html($_REQUEST['all_hours']) .
          " is not a valid number of hours " .
          "for the shift you are signing off for.</h3>";
        break;
      }
      //get the shift info, and do more checks -- make sure autoid matches,
      //workshift name matches, "day date" matches, and we're within the time
      $row = $db->GetRow("select `hours`,`member_name` from " .
                         bracket("week_$signoff_weeknum") .
                         " where `autoid` = ? and `workshift` = ? and " .
                         "strcmp(concat(`day`,' '," .
                         "date_format(`date`,'%c/%e')), ?) = 0 and " .
                         "`online_signoff` = 0 and " .
                         "date_add(date_add(`date`," .
                         "interval ifnull(`end_time`,'01:00:00:00') day_second)," .
                         "interval ? hour) >= ?",
                         array($signoff_autoid,$_REQUEST['all_shift'],
                               $_REQUEST['day_name'],$max_signoff_time,
                               user_time(null,'Y-m-d H:i:s')));
      if (is_empty($row)) {
        print "<h3>Error!  The shift " . 
          escape_html($_REQUEST['all_shift']) . " on " .
          escape_html($_REQUEST['day_name']) . 
          " does not exist for online signoff.  Please <a href='" . this_url() . 
          "'>click here</a> and reload the page.</h3>";
        break;
      }
      //same as above, two cases.  Actually 4, because member change too
      else if ($row['hours'] != $_REQUEST['all_hours']) {
        //logic in the notes section to put the original workshifter
        //in if needed
        $db->Execute("update " .
                     bracket("week_$signoff_weeknum") .
                     "set `member_name` = ?, `hours` = ?,`verifier` = ?, ".
                     "`notes` = concat(if(`notes`,concat(`notes`,' '),''),?)," .
                     "`online_signoff` = ? where `autoid` = ?",
                     array($_REQUEST['mem_name'],$_REQUEST['all_hours'],
                           $_REQUEST['verifier_name'],'ONLINE: (usually ' . 
                           ($row['member_name'] == $_REQUEST['mem_name']?'':
                            ($row['member_name']?$row['member_name']:
                             'not assigned') . ', ') .
                           $row['hours'] . " hrs) ",
                           user_time(null,'Y-m-d H:i:s'),$signoff_autoid));
      }
      else {
        $db->Execute("update " .
                     bracket("week_$signoff_weeknum") .
                     "set `member_name` = ?," .
                     "`verifier` = ?," .
                     "`notes` = concat(if(`notes`,concat(`notes`,' '),''),?)," .
                     "`online_signoff` = ? where `autoid` = ?",
                     array($_REQUEST['mem_name'],
                           $_REQUEST['verifier_name'],'ONLINE' .
                           ($row['member_name'] == $_REQUEST['mem_name']?'':
                            ': (usually ' . 
                            ($row['member_name']?$row['member_name']:
                             'not_assigned') . ')'),
                           user_time(null,'Y-m-d H:i:s'),$signoff_autoid));
      }
      $request_workshift = $_REQUEST['day_name'] . ": " . $_REQUEST['all_shift'];
      $request_hours = $_REQUEST['all_hours'];
    }
    //done with all shifts, time for a special shift the user created
    else if (array_key_exists('special_shift_submit',$_REQUEST)) {
      if (!$_REQUEST['special_shift']) {
        print "<h3>Please give your special shift a name</h3>";
        break;
      }
      if (!is_numeric($_REQUEST['special_hours'])) {
        print "<h3>" . escape_html($_REQUEST['special_hours']) .
          " is not a valid number of hours for your special shift</h3>";
        break;
      }
      //nothing to check here, since there's no original shift
      //special shifts can't be created for the previous week, but who cares
      $db->Execute("insert into " .
                   bracket("week_$cur_week") .
                   " (`member_name`,`workshift`,`hours`,`notes`," .
                   "`online_signoff`,`verifier`) values (?,?,?,?,?,?)",
                   array($_REQUEST['mem_name'],$_REQUEST['special_shift'],
                         $_REQUEST['special_hours'],
                         'ONLINE (special) ' . $_REQUEST['notes'],
                         user_time(null,'Y-m-d H:i:s'),$_REQUEST['verifier_name']));
      $request_workshift = $_REQUEST['special_shift'];
      $request_hours = $_REQUEST['special_hours'];
    }
    //yay, we succeeded!
    $redo_status = false;
    //should we randomly email the verifier to make sure this shift was legit?
    if (($email_freq = get_static('online_signoff_email',null)) &&
        rand(1,$email_freq) == 1) {
      //does the verifier have an email?
      $email = get_email($_REQUEST['verifier_name']);
      //if they do, was emailing them successful?
      //emailing uses these static texts with substitution, see get_static_text
      //for details
      if (!$email ||
          !mail($email,
                get_static_text('online_email_subject',
                                "Checking shift verification -- " .
                                '%member_name; %workshift; %date; %hours',
                                array('%member_name' =>
                                      array('Workshifter name',
                                            array('_REQUEST','mem_name')),
                                      '%workshift' =>
                                      array('Workshift',
                                            'request_workshift'),
                                      '%date' =>
                                      array('Date/time of signoff',
                                            '*user_time()'),
                                      '%hours' =>
                                      array('Hours signed off for',
                                            'request_hours')),
                                true),
                get_static_text('online_email_body',
                                'This is a confirmation that you verified that ' .
                                '%member_name did their workshift %workshift for ' .
                                '%hours hours on %date.  If this is not correct in ' .
                                'any way, please email the workshift manager.  ' .
                                'Thanks.',
                                array('%member_name' =>
                                      array('Workshifter name',
                                            array('_REQUEST','mem_name')),
                                      '%workshift' =>
                                      array('Workshift',
                                            'request_workshift'),
                                      '%date' =>
                                      array('Date/time of signoff',
                                            '*user_time()'),
                                    '%hours' =>
                                      array('Hours signed off for',
                                            'request_hours')),
                                true),
                "Cc: $workshift_manager_email" . 
                "\r\nFrom: " . 
                $workshift_manager_email)) {
        //emailing was unsuccessful
        print "Couldn't email the verifier.";
        mail($workshift_manager_email,
             'Online shift could not be verified by email',
             "workshifter: " . $_REQUEST['mem_name'] . "\n" .
             "verifier: " . $_REQUEST['verifier_name'] . "\n" .
             "date: " . user_time() . "\n" .
             "workshift: " . $request_workshift . "\n" .
             "hours: " . $request_hours . "\n" .
             "notes: " . $_REQUEST['notes'] . "\n");
      }
    }
    //done with do "loop"!
  } while (false);
  if (!$redo_status) {
    print "<h4>Successfully signed off " . escape_html($_REQUEST['mem_name']) . 
      " with verifier " . escape_html($_REQUEST['verifier_name']) . 
      ".  Check below to make sure all the details are right.</h4>";
  }
  print "<hr>";
}

//Ok, time for the main page
//The first thing we have to do is construct a complicated sql query
//because it may involve two different weeks, but we're not sure
//arr holds the arguments to the mysql query
$arr = array();
//are we past week 0, and so should we select from the previous week as well?
if ($cur_week) {
  //building the query -- select from the previous week.  Week is
  //selected as a constant so that we know which week this row came
  //from later on
  $sql_more = "select ? as `week`, `autoid`, " .
    "concat(`day`,' ',date_format(`date`,'%c/%e')) as `dayte`,`workshift`, " .
    "`member_name`,`hours` from " . bracket("week_" . ($cur_week-1)) . 
    " where `online_signoff` = 0 and " .
    "date_add(date_add(`date`, " .
    "interval ifnull(`end_time`,'01:00:00:00') day_second)," .
    "interval ? hour) >= ? union ";
  //add the appropriate arguments in
  $arr[] = $cur_week-1;
  $arr[] = $max_signoff_time;
  $arr[] = user_time(null,'Y-m-d H:i:s');
}
else {
  $sql_more = '';
}

#$db->debug = true;
//execute query -- see right above for explanation of week select
$res = $db->Execute($sql_more . 
                    "select ? as `week`, `autoid`, " .
                    "concat(`day`,' ',date_format(`date`,'%c/%e')) as `dayte`, " .
                    "`workshift`,`member_name`,`hours` from " .
                    bracket("week_$cur_week") . " where " .
                    "`online_signoff` = 0 and " .
                    "date_add(date_add(`date`, " .
                    "interval ifnull(`end_time`,'01:00:00:00') day_second)," .
                    "interval ? hour) >= ?",
                    array_merge($arr,
                                array($cur_week,$max_signoff_time,
                                      user_time(null,'Y-m-d H:i:s'))));
$member_shifts = array();
$day_shifts = array();

?>
<script type='text/javascript'>
var member_shifts = new Array();
var day_shifts = new Array();
<?php
$days[] = 'Weeklong';

$houselist = get_houselist();
$start_date = get_static('semester_start');
if (strlen($start_date) == 0) {
  exit("<p>The workshift manager hasn't entered the date the semester starts");
}
$start_date = explode('-',$start_date);
while ($row = $res->FetchRow()) {
  $dayte = $row['dayte'];
  if (!isset($day_shifts[$dayte])) {
    $day_shifts[$dayte] = array();
    print "day_shifts[" . dbl_quote($dayte) . "] = new Array();\n";
  }
  $cur =& $day_shifts[$dayte][$row['workshift']];
  if (!isset($day_shifts[$dayte][$row['workshift']])) {
    $day_shifts[$dayte][$row['workshift']] = array();
    //    $cur = array();
    //print_r('here at cur');
    //var_dump($day_shifts[$dayte][$row['workshift']]);
    print "day_shifts[" . dbl_quote($dayte) . "][" . dbl_quote($row['workshift'])
      . "] = new Array();\n";
  }
  print "day_shifts[" . dbl_quote($dayte) . "][" . dbl_quote($row['workshift'])
    . "][" . dbl_quote($row['autoid'] . "." . $row['week']) . 
    "] = new Array(" . dbl_quote($row['member_name'])
    . "," . dbl_quote($row['hours']) . ");\n";
  $cur[$row['autoid'] . "." . $row['week']] = array($row['member_name'],$row['hours']);
  //  var_export($member_shifts);
  if ($row['member_name']) {
    if (!isset($member_shifts[$row['member_name']])) {
      $member_shifts[$row['member_name']] = array();
      print "member_shifts[" . dbl_quote($row['member_name']) . 
        "] = new Array();\n";
    }
    $cur =& $member_shifts[$row['member_name']];
    print "member_shifts[" . dbl_quote($row['member_name']) . "][" . 
      dbl_quote($row['autoid'] . "." . $row['week']) . 
      "]= new Array(" . dbl_quote($dayte) . "," . 
      dbl_quote($row['workshift']) . "," . dbl_quote($row['hours']) . ");\n";
    $cur[$row['autoid'] . "." . $row['week']] = array($dayte,$row['workshift'],$row['hours']);
  }
}
//var_export($day_shifts);
//print_r "member_shifts: ";
//var_dump($member_shifts);
//var_dump($day_shifts);
$cur_day = user_time(time()-3600,'l');
?>
</script></head>
<body>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=post>
Workshifter: <select name='mem_name' id='mem_name' onchange='change_member()'>
<option>
<?php
foreach ($houselist as $mem) {
  print "<option ";
  if ($redo_status && $mem == $_REQUEST['mem_name']) {
    print "selected ";
  }
  print "value='" . escape_html($mem) . "'>" . escape_html($mem) . "\n";
}
?>
</select>
<hr>
Verifier: <select name='verifier_name' id='verifier_name'>
<option>
<?php
foreach ($houselist as $mem) {
  print "<option ";
  if ($redo_status && $mem == $_REQUEST['verifier_name']) {
    print "selected ";
  }
  print "value='" . escape_html($mem) . "'>" . escape_html($mem) . "\n";
}
?>
</select>
<?php
if (get_static('online_signoff_verifier_password',true)) {
?>
Password: <input type='password' name='verifier_password'
id='verifier_password'><br/>
<?php
             }
?>
By verifying this shift, you are affirming that you actually verified that this
shift was done properly.  Falsely verifying a shift has serious consequences.
<hr>
Notes:
<textarea rows=3 cols=40  name='notes'>
<?php
if ($redo_status) {
  print escape_html($_REQUEST['notes']);
}
?>
</textarea>
<hr>
Your shifts:
<select multiple name='mem_shift' id='mem_shift' height=3 
onchange='change_memshift()'>
<?php
$orig_hours = null;
$firstflag = true;
//print_r($_REQUEST);
if ($redo_status && $_REQUEST['mem_name'] && isset($member_shifts[$_REQUEST['mem_name']])) {
  $mem_sh = $member_shifts[$_REQUEST['mem_name']];
  foreach ($mem_sh as $id => $info) {
    //    print $id . "\n";
    print "<option ";
    if ((isset($_REQUEST['mem_shift']) && 
        $id == $_REQUEST['mem_shift']) ||
        (!isset($_REQUEST['mem_shift']) && $firstflag)) {
      print "selected ";
      $firstflag = false;
      $orig_hours = $info[2];
    }
    print " value='" . escape_html($id) . "'>" . 
      escape_html($info[0] . " " . $info[1]) . "\n";
  }
}
?>
</select>
Hours:
<input name='mem_shift_hours' id='mem_shift_hours' size=3
<?php
if ($redo_status && $_REQUEST['mem_name']) {
  print "value='" . escape_html($_REQUEST['mem_shift_hours']) . "'>";
  if (strlen($orig_hours) && $orig_hours != $_REQUEST['mem_shift_hours']) {
    print " <span id='init_mem_hours_span'>(originally " . escape_html($orig_hours) . " hrs)</span>";
  }
}
else {
  print ">";
}
?>
<input type=submit name='member_shift_submit' value='Sign off!'
onclick='return validate_form("mem")'>
<?php
if (count($day_shifts)) {
?>
<hr>
All shifts:
<select name='day_name' id='day_name' multiple height=<?=count($day_shifts)?> onchange='change_shift(0)'>
<?php
if ($redo_status && isset($_REQUEST['day_name'])) {
  $curday = $_REQUEST['day_name'];
}
else {
  $curday = date('l m/d');
}
$success_flag = false;
foreach (array_keys($day_shifts) as $day) {
  if ($curday === $day) {
    $success_flag = true;
    break;
  }
}
if (!$success_flag) {
  unset($_REQUEST['all_shift']);
  unset($_REQUEST['all_which_shift']);
  unset($_REQUEST['all_hours']);
  $curday = array_keys($day_shifts);
  $curday = $curday[0];
}
foreach (array_keys($day_shifts) as $day) {
  print "<option value='" . escape_html($day) . "'";
  if ($curday === $day) {
    print " selected";
  }
  print ">" . escape_html($day) . "\n";
}
?>
</select>
&nbsp;&nbsp;&nbsp;
<?php
$days_arr = array_keys($day_shifts);
?>
<select multiple id='all_shift' name='all_shift' onchange='change_shift(1)'>
<?php
$firstflag = true;
$shifters = array_keys($day_shifts[$curday]);
if ($redo_status && isset($_REQUEST['all_shift'])) {
  $success_flag = false;
  foreach ($shifters as $shift) {
    if ($shift == $_REQUEST['all_shift']) {
      $success_flag = true;
      break;
    }
  }
  if (!$success_flag) {
    unset($_REQUEST['all_which_shift']);
    unset($_REQUEST['all_hours']);
    $sel_shift = $shifters[0];
  }
  else {
    $sel_shift = $_REQUEST['all_shift'];
  }
}
else {
  $sel_shift = $shifters[0];
}   
foreach ($shifters as $shift) {
  print "<option value='" . escape_html($shift) . "'";
  if ($sel_shift == $shift) {
    print " selected";
  }
  print ">" . escape_html($shift) . "\n";
}

?>
</select>
<?php
$shifters = $day_shifts[$curday][$sel_shift];
if ($redo_status && isset($_REQUEST['all_which_shift'])) {
  $success_flag = false;
  foreach ($shifters as $id => $junk) {
    if ($id == $_REQUEST['all_which_shift']) {
      $success_flag = true;
      break;
    }
  }
  if (!$success_flag) {
    unset($_REQUEST['all_hours']);
    $sel_id = array_keys($shifters);
    $sel_id = $sel_id[0];
  }
  else {
    $sel_id = $_REQUEST['all_which_shift'];
  }
}
else {
    $sel_id = array_keys($shifters);
    $sel_id = $sel_id[0];
}   
print "Usual person:";
print "<select multiple id='all_which_shift' name='all_which_shift' onchange='change_shift(2)'>";
foreach ($shifters as $id => $usual) {
  print "<option value='" . escape_html($id) . "'";
  if ($sel_id == $id) {
    print " selected";
    $firstflag = false;
  }
  print ">" . escape_html($usual[0]) . "\n";
}
?>
</select>
&nbsp;&nbsp;&nbsp;
Hours: <input name='all_hours' id='all_hours' size=3 onchange='change_hours(this)'
value='
<?php
if (!$redo_status || !isset($_REQUEST['all_hours']) || !strlen($_REQUEST['all_hours']) ||
    $shifters[$sel_id][1] == $_REQUEST['all_hours']) {
  print escape_html($shifters[$sel_id][1]) . "'>";
#";
}
else {
print escape_html($_REQUEST['all_hours']) . "'>";
print "<span id='init_all_hours_span'> (originally " . escape_html($shifters[$sel_id][1]) .
" hrs) </span>\n";
}
?>
<input type=submit name='all_shift_submit' value='Sign off!'
onclick='return validate_form("all")'>
<?php
}
?>
<hr>
Special shift:
<input size=40 name='special_shift' id='special_shift'
<?php
if ($redo_status) {
print "value='" . escape_html($_REQUEST['special_shift']) . "'";
}
?>
>&nbsp;&nbsp;
Hours: <input size=3 name='special_hours' 
id='special_hours' onchange='change_hours(this)'
<?php
if ($redo_status) {
print "value='" . escape_html($_REQUEST['special_hours']) . "'";
}
?>
>
<input type=submit name='special_shift_submit' value='Sign off!'
onclick='return validate_form("special")'>

<script type="text/javascript" 
src="<?=escape_html("$html_includes/table_edit.utils.js")?>"></script>

<script>
var mem_name = document.getElementById('mem_name');
var mem_shift = document.getElementById('mem_shift');
var mem_shift_hours = document.getElementById('mem_shift_hours');
var all_shift = document.getElementById('all_shift');
var all_hours = document.getElementById('all_hours');
var all_which_shift = document.getElementById('all_which_shift');
var day_name = document.getElementById('day_name');
var init_mem_hours_span = document.getElementById('init_mem_hours_span');
var init_all_hours_span = document.getElementById('init_all_hours_span');
function change_member() {
  if (mem_shift.length) {
    for (var ii=mem_shift.length-1; ii>=0; ii--) {
      mem_shift.remove(ii);
    }
  }
  var mem = member_shifts[mem_name.value];
  if (mem) {
    var first_flag = true;
    for (var ii in mem) {
      if (first_flag) {
        mem_shift_hours.value = mem[ii][2];
        first_flag = false;
      }
      var opt = document.createElement('option');
      opt.value = ii;
      opt.text = mem[ii][0] + " " + mem[ii][1];
      try {
        mem_shift.add(opt,null);
      }
      catch(ex) {
        mem_shift.add(opt);
      }
    }
    mem_shift.selectedIndex = 0;
  }
  if (init_mem_hours_span) {
    init_mem_hours_span.style.display = 'none';
  }
}

function change_memshift() {
  if (mem_shift.value > -1) {
    mem_shift_hours.value = member_shifts[mem_name.value][mem_shift.value][2];
  }
  if (init_mem_hours_span) {
    init_mem_hours_span.style.display = 'none';
  }
}

function change_shift(flag) {
  var this_day, this_shift;
  switch(flag) {
    case 0:
      if (day_name.value) {
        this_day = day_shifts[day_name.value];
        empty_sel(all_shift);
        for (var ii in this_day) {
          append_option(all_shift,ii,ii);
        }
        all_shift.selectedIndex = 0;
      }
  case 1:
    if (all_shift.value && day_name.value) {
      this_shift = day_shifts[day_name.value][all_shift.value];
      empty_sel(all_which_shift);
      for (var ii in this_shift) {
        append_option(all_which_shift,ii,this_shift[ii][0]);
      }
      all_which_shift.selectedIndex = 0;
    }
  case 2:
    if (all_shift.value && day_name.value && all_which_shift.value) {
      all_hours.value = day_shifts[day_name.value][all_shift.value][all_which_shift.value][1];
    }
  }
if (init_all_hours_span) {
init_all_hours_span.style.display = 'none';
}
}

function empty_sel(elt) {
  if (elt.length) {
    for (var ii=elt.length-1;ii>=0;ii--) {
      elt.remove(ii);
    }
  }
}

function append_option(elt,val,text) {
  var opt = document.createElement('option');
  opt.value = val;
  opt.text = text;
  try {
    elt.add(opt,null);
  }
  catch(ex) {
    elt.add(opt);
  }
}

function validate_form(flag) {
  var alerts = '';
var retflag = true;
var val;
if (get_elt_by_id('mem_name').selectedIndex < 1) {
alerts += "Enter the workshifter's name!\n";
get_elt_by_id('mem_name').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('mem_name').style.borderColor = 'black';
}
if (get_elt_by_id('verifier_name').selectedIndex < 1) {
alerts += "Enter the verifier's name!\n";
get_elt_by_id('verifier_name').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('verifier_name').style.borderColor = 'black';
}
<?php
if (get_static('online_signoff_verifier_password',true)) {
?>
val = get_value_by_id('verifier_password');
if (!val) {
alerts += "Enter the verifier's password!\n";
get_elt_by_id('verifier_password').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('verifier_password').style.borderColor = 'black';
}
<?php
}
?>
if (flag == 'mem') {
if (get_elt_by_id('mem_shift').selectedIndex < 0) {
alerts += "Choose one of your shifts to sign off for!\n";
get_elt_by_id('mem_shift').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('mem_shift').style.borderColor = 'black';
}
val = get_value_by_id('mem_shift_hours');
if (!is_decimal(val)) {
alerts += val + " is not a valid number of hours for your shift!\n";
get_elt_by_id('mem_shift_hours').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('mem_shift_hours').style.borderColor = 'black';
}
}
else if (flag == 'all') {
if (get_elt_by_id('day_name').selectedIndex < 0) {
alerts += "Which day did you sign into a shift?\n";
get_elt_by_id('day_name').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('day_name').style.borderColor = 'black';
}
if (get_elt_by_id('all_shift').selectedIndex < 0) {
alerts += "Which shift are you signing into?\n";
get_elt_by_id('all_shift').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('all_shift').style.borderColor = 'black';
}
if (get_elt_by_id('all_which_shift').selectedIndex < 0) {
alerts += "Select the person whose shift you're taking (or the blank line).\n";
get_elt_by_id('all_which_shift').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('all_which_shift').style.borderColor = 'black';
}
val = get_value_by_id('all_hours');
if (!is_decimal(val)) {
alerts += val + " is not a valid number of hours to sign in for!\n";
get_elt_by_id('all_hours').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('all_hours').style.borderColor = 'black';
}
}
else if (flag = 'special') {
val = get_value_by_id('special_shift');
if (!val) {
alerts += "Please enter a name/description of your special shift!\n";
get_elt_by_id('special_shift').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('special_shift').style.borderColor = 'black';
}
val = get_value_by_id('special_hours');
if (!is_decimal(val)) {
alerts += val + " is not a valid number of hours for your special shift!\n";
get_elt_by_id('special_hours').style.borderColor = 'red';
retflag = false;
}
else {
get_elt_by_id('special_hours').style.borderColor = 'black';
}
}
if (alerts) {
alert(alerts);
}
return retflag;
}
</script>
</form>
<hr>
<?php
?>

<?php
if ($cur_week) {
  $sql_more = "select concat(`day`,' ',date_format(`date`,'%c/%e')) as `Date of Workshift`,`workshift`,`member_name` as `Member`,`workshift`,`hours`,`verifier`,date_format(`online_signoff`,'%r %a %b %D') as `Time Signed Off`,`online_signoff` from `week_" . ($cur_week-1) . "` where `online_signoff` union ";
}
else {
  $sql_more = '';
}

$res = $db->Execute($sql_more . 
                    "select  concat(`day`,' ',date_format(`date`,'%c/%e')) as `Date of Workshift`,`workshift`,`member_name` as `Member`,`workshift`,`hours`,`verifier`,date_format(`online_signoff`,'%r %a %b %D') as `Time Signed Off`,`online_signoff` from `week_$cur_week` where `online_signoff` order by `online_signoff` desc");

if (!is_empty($res)) {
print <<<PRINTONLINE
<hr>
<h3>Recent online signoffs</h3>
If you think one of these signoffs is inaccurate, click on the member name to
send an email to the workshift manager.
<table border><tr>
PRINTONLINE
;
foreach ($res->fields as $col => $junk) {
if ($col == 'online_signoff') {
continue;
  }
  print "<th>" . escape_html($col) . "</th>";
}
print "</tr>";
while ($row = $res->FetchRow()) {
$subj = urlencode('Online: ' . $row['Member'] . ' signed off for ' . $row['workshift'] .
' done ' . $row['Date of Workshift'] . ' for ' . $row['hours'] . ' hours, signed off ' .
$row['Time Signed Off'] . ' by ' . $row['verifier']);
$subj = 'testing';
  print "<tr>";
  foreach ($row as $key => $col) {
    if ($key == 'online_signoff') {
      continue;
    }
    print "<td>";
if ($key == 'Member') {
print "<a href='mailto:" . escape_html($workshift_manager_email . 
'?subject=' . $subj) . "'>";
}
else if ($key == 'workshift') {
print "<a href='shift_descriptions.php#" . escape_html($row['workshift']) . 
"'>";
}
    print escape_html($col);
if ($key == 'workshift' || $key == 'Member') {
  print "</a>";
}
print "</td>";
  }
  print "</tr>";
}
print "</table>";
}
?>
</body></html>
