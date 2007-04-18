<?php
$body_insert = '';
require_once('default.inc.php');
if (!authorized_user($member_name,'president')) {
  exit("You are not authorized to use this page.");
}
if (!array_key_exists('election_name',$_REQUEST) || 
    (array_key_exists('modify_election',$_REQUEST) &&
     !array_key_exists('election_name_full',$_REQUEST))) { 
  ?>
<html><head><title><?=array_key_exists('modify_election',$_REQUEST)?'Modify ':'Create '?>
election</title>
<style type="text/css">
   label { 
     vertical-align: top; 
   }
 div.options {
 border: thin black solid; 
 margin-bottom: 10px;
 }
 div.threshold {
 border: thin black solid;
 }
 span.yesno {
   padding-right: 20px;
 }
</style>
<script type='text/javascript' src='create_election.js'></script>
</head><body>
<?=$body_insert?>
<form method='POST' enctype='multipart/form-data'
action="<?=escape_html($_SERVER['REQUEST_URI'])?>">
<?php
    if (array_key_exists('modify_election',$_REQUEST)) {
      $elect_row = $db->GetRow("select `election_name`,`anon_voting`, `end_date` " .
                               "from `elections_record` where `election_name` = ? ",
                               array($_REQUEST['election_name']));
      if (is_empty($elect_row)) {
        exit("The election you're trying to modify, " . escape_html($_REQUEST['election_name']) . 
             " doesn't exist!");
      }
      $name_full = explode('_',$elect_row['election_name'],3);
      print "<input type=hidden name='election_name_full' value='" . 
      escape_html($elect_row['election_name']) . "'>\n";
      $full_date = explode(' ',user_time($elect_row['end_date'],'Y-m-d H:i:s'));
      $elect_row['end_date'] = $full_date[0];
      $full_date = explode(':',$full_date[1]);
      if ($full_date[0] >= 12) {
        if ($full_date[0] > 12) {
          $full_date[0]-=12;
        }
        $elect_row['end_ampm'] = 'pm';
      }
      else {
        if ($full_date[0] == 0) {
          $full_date[0] = '12';
        }
        $elect_row['end_ampm'] = 'am';
      }
      $elect_row['end_hour'] = $full_date[0];
      $elect_row['end_minute'] = $full_date[1];
      $res = $db->Execute("select * from `elections_attribs` where " .
                          "`election_name` = ? order by `autoid`",
                          array($elect_row['election_name']));
      $temp_attribs = array();
      while ($row = $res->FetchRow()) {
        if (!strlen($row['race_name'])) {
          $elect_row[$row['attrib_name']] = $row['attrib_value'];
          continue;
        }
        else {
          if (!isset($temp_attribs[$row['race_name']])) {
            $temp_attribs[$row['race_name']] = array();
          }
          $temp_attribs[$row['race_name']][$row['attrib_name']] = $row['attrib_value'];
        }
      }
      $ii = 1;
      $race_attribs = array();
      foreach ($temp_attribs as $race_name => $attribs) {
        if (!isset($attribs['feedback'])) {
          $attribs['feedback'] = null;
        }
        $race_attribs[$ii] = $attribs;
        $race_attribs[$ii++]['race_name'] = $race_name;
      }
      print "<h4>" . escape_html($elect_row['election_name']) . "</h4>\n";
      print "Voting is " . ($elect_row['anon_voting']?'':'not ') . "anonymous.<br>\n";
      print "<hr>";
    }
    else {
      $elect_row = null;
      $race_attribs = null;
?>
Name of election.  The year and semester will be added automatically.  
You can enter at most 41 characters:
<input type=text name='election_name' maxlength=41><br>
<?php
   $spring = $summer = $fall = ''; 
   $month = date('n');
 if ($month < 6) {
   $spring = 'selected';
 }
 else if ($month < 9) {
   $summer = 'selected';
 }
 else {
   $fall = 'selected';
 }
?>
Semester: <select name='semester'>
<option <?=$spring?>>spring
<option <?=$summer?>>summer
<option <?=$fall?>>fall
</select><br>
Is it anonymous? <input type=checkbox name=anon><br>
<?php
             }
?>
Can people view partial results before the election is over?  Usually, they
shouldn't be able to unless the election is not anonymous.<br>
<?php #stupid insert for emacs because that quote screwed up formatting'
if ($elect_row) {
  $radio_val = $elect_row['interim_results'];
}
else {
  $radio_val = 'sync';
?>
<label for='interim_results_sync'><input type=radio
name='interim_results' id='interim_results_sync' value='sync'
<?=$radio_val == 'sync'?'checked':''?>>
No if the election is anonymous, yes otherwise</label>
<?php
            }
?>
<label for='interim_results_yes'><input type=radio name='interim_results'
id='interim_results_yes' value=1 <?=$radio_val == '1'?'checked':''?>>Yes</label>
<label for='interim_results_no'><input
type=radio name='interim_results' id='interim_results_no' value=0
<?=$radio_val == '0'?'checked':''?>
>No</label><br>
<hr>
Ending date (enter 2005-11-05 for November 5th, 2005): 
<input type=text name=end_date <?=$elect_row?'value="' . 
                                 escape_html($elect_row['end_date']) . '"':''?>><br>
The election will end at midnight at the start of this day unless you enter a
different time here, in which case it will end later, at this time.
<input type=text size=2 name=end_time_hr
<?=$elect_row?'value="' . escape_html($elect_row['end_hour']) .
                                 '"':''?>>&nbsp;:&nbsp;
<input type=text name=end_time_mn size=2
<?=$elect_row?'value="' . escape_html($elect_row['end_minute']) .
                                 '"':''?>>&nbsp;<select name=end_time_ampm>
<option value=0
<?=$elect_row && $elect_row['end_ampm'] == 'am'?' selected':''?>>am
<option value=1 <?=!$elect_row || $elect_row['end_ampm'] == 'pm'?' selected':''?>>pm
</select><br>
The server thinks it is <?=user_time()?> right now.  If this is incorrect,
<a href='../../common/tz_set.php'>set the time zone</a>.
<hr>
You can put some text that will appear at the top of the voter's page here.  Check here to treat it
<?php #';?>
as HTML (otherwise it will be treated as plain text):
<input type=checkbox name='descript_html'
<?=$elect_row && $elect_row['descript_html']?' checked':''?>>
<textarea name='descript' rows=8 cols=60>
<?=$elect_row?escape_html($elect_row['descript']):''?>
</textarea>
<?php
  if ($elect_row && $elect_row['descript_file']) {
    print "<br/>Current file which is linked to from ballot:<br/>\n";
    print "<a href='../descript_file.php?election_name=" . 
    escape_html($elect_row['election_name']) . "' target=descript_file>" .
    escape_html($elect_row['descript_filename']) . "</a>";
?>
<label for='descript_file_remove'>
<input type=checkbox name='descript_file_remove' id='descript_file_remove' value=1>
Remove this file</label>
<?php
  }
?>
<br/>If you want to upload a file
<?php
  if ($elect_row && $elect_row['descript_file']) {
    print " to replace the current one";
  }
  else {
    print "as well that will be linked to at the top of the page";
  }
?>
, you can do that.<br>
<input type="file" name="descript_file" size="40"><br/>

<div id='div_races'>
<input type=hidden name='num_races' id='num_races' value=<?=$race_attribs?count($race_attribs):3?>>
<?php
for ($ii = 1; $ii <= ($race_attribs?count($race_attribs):3); $ii++) {
  if ($race_attribs) {
    $attribs = $race_attribs[$ii];
  }
  else {
    $attribs = null;
  }
?>
<p>
<hr>
<h4>Race <?=$ii?></h4>
<span style="padding-right: 20px;">
<input type=submit value='Make it Yes/No' onclick='event.preventDefault(); init_yesno(<?=$ii?>);'></span>
<input type=submit value='Make it Approved/Not Approved' onclick='event.preventDefault(); init_yesno(<?=$ii?>); init_approved(<?=$ii?>)'><br>
Race name (like President, or VOC Maintenance): <input name='display_name_<?=$ii?>'
<?=$attribs?'value="' . escape_html($attribs['race_name']) . '"':''?>
><br>
<label>Description of race (if necessary):</label>
<textarea name='race_descript_<?=$ii?>' rows=5 cols=60 wrap='off'>
<?=$attribs?escape_html($attribs['race_descript']):''?>
</textarea><br/>
<label>Candidates (one per line):</label><textarea name='candidates_<?=$ii?>'
rows=5 wrap="off" id='candidates_<?=$ii?>'>
<?=$attribs?escape_html($attribs['candidates']):''?>
</textarea><br>
Number of spots (if your house elects two Board Reps, and this is an election
for Board Rep, put a 2 here): <input name='num_<?=$ii?>' id='num_<?=$ii?>'size=3 value=
  <?=$attribs?$attribs['num']:1?>><br>
<div class='options'>
Options:<br> 
  Is this just a feedback race (i.e., a VOC)?
<input type=checkbox name='feedback_<?=$ii?>' id='feedback_<?=$ii?>'
  <?=$attribs['feedback']?'checked':''?> value=1><br/>
  (If so, the other options will be ignored)<br/>
<?php
  if (!$attribs) {
    $radio = 0;
  }
  else {
    $radio = $attribs['runoff'];
  }
?> 
<label for='runoff_<?=$ii?>_none'>Nothing special?<input type=radio name='runoff_<?=$ii?>' id='runoff_<?=$ii?>_none' value=0 
  <?=$radio == 0?'checked':''?>
></label><br>
<label for='runoff_<?=$ii?>_instant'>Instant runoff?<input type=radio name='runoff_<?=$ii?>' id='runoff_<?=$ii?>_instant' value=1 <?=$radio == 1?'checked':''?>>
</label><br>
Unranked preferences allowed?  Enter the maximum number of preferences here: <input name='runoff_<?=$ii?>_num' id='runoff_<?=$ii?>_num' size=3
onchange='change_runoff_num(<?=$ii?>)'
<?=$radio > 1?' value="' . escape_html($radio) . '"':''?>> 
<br>
</div>
<div class='threshold'>
Threshold: <label for='threshold_<?=$ii?>_none'>
<?php
  if (!$attribs) {
    $radio = 0;
  }
  else {
    $radio = $attribs['threshold'];
  }
?>
<input type='radio' name='threshold_<?=$ii?>' id='threshold_<?=$ii?>_none' value=''
  <?=$radio == 0?' checked':''?>>None</label>, 
<input type='radio' name='threshold_<?=$ii?>' id='threshold_<?=$ii?>_percent' value='percent'
  <?=$radio > 0?' checked':''?>>
More than <input name='threshold_<?=$ii?>_num' id='threshold_<?=$ii?>_num_percent' size=2 onclick='document.getElementById("threshold_<?=$ii?>_percent").click()'
  <?=$radio > 0?' value="' . escape_html($radio) . '"':''?>>% of the vote is required,
<input type='radio' name='threshold_<?=$ii?>' id='threshold_<?=$ii?>_number' value='number'
<?=$radio < 0?' checked':''?>>
<input name='threshold_<?=$ii?>_num_absolute' size=2
onclick='document.getElementById("threshold_<?=$ii?>_number").click()'
  <?=$radio < 0?' value="' . escape_html(-$radio) . '"':''?>> votes are required.<br>
Will abstentions count as votes for threshold purposes?
<input type=checkbox name='abstain_count_<?=$ii?>' id='abstain_count_<?=$ii?>'
<?=$attribs && $attribs['abstain_count']?' checked':''?> value=1><br>
Is there a minimum number of votes that must be cast in order for this race to be decided at all
  (perhaps two-thirds of your house must vote on a bylaw change, however they vote, for
   it to have any chance of passing)?
<input name='num_voters_<?=$ii?>' <?=$attribs && $attribs['num_voters']?' value="' . 
  escape_html($attribs['num_voters']) . '"':''?>><br/>
What is the default if one of the above thresholds is not met? <input size=20 name='def_val_<?=$ii?>'
id='def_val_<?=$ii?>' <?=$attribs?' value="' . escape_html($attribs['def_val']) . '"':''?>>
</div>
Can the voters add choices themselves?  (Used for things like 
party themes/naming lizards, etc.)
<input type=checkbox name='member_add_<?=$ii?>' value=1
  <?=$attribs && $attribs['member_add']?' checked':''?>><br>
Can the voters make comments that other voters will see when they are voting?
<input type=checkbox name='member_comments_<?=$ii?>' value='1'
  <?=$attribs && $attribs['member_comments'] !== null?' checked':''?>>
<?php
  if ($attribs && $attribs['member_comments'] !== null) {
    print "<br>Member comments so far:<br>\n";
    print "<textarea name='member_comments_current_<?=$ii?>' cols=70 rows=10 wrap=off>";
    print escape_html($attribs['member_comments']) . "</textarea>";
  }
?>
</p>
<?php } ?>
</div>
<hr>
<input type=button value='Add Race' onclick='add_race();'><p>

<?php
    if (!$race_attribs) {
?>
You can also modify the election after you have added it.<br>
<?php
    }
?>
<input type=submit value=Submit>
</form>
</body>
</html>
   <?php exit; }
print $body_insert;
#print_r($_REQUEST);
if (!array_key_exists('election_name_full',$_REQUEST)) {
  $election_name = substr($_REQUEST['election_name'],0,41);
  $semester = $_REQUEST['semester'];
  switch ($semester) {
  case 'spring': case 'summer': $semester = '-' . $semester; break;
  case 'fall': $semester = '_' . $semester; break;
  default:
    janak_error(escape_html($semester) . " is not a valid semester!");
  }
  if (!$election_name) {
    exit("You didn't specify an election name!  Go back and put one in.");
  }
  $election_name = date('Y') . $semester . '_' . $election_name;
  $modify = false;
}
else {
  $election_name = stripformslash($_REQUEST['election_name_full']);
  $modify = true;
}
$end_date = stripformslash($_REQUEST['end_date']);
if (!$end_date) {
  exit("You didn't enter an end date!  Go back and put one in.");
}
else if (!preg_match('/\d\d\d\d-\d\d?-\d\d?/',$end_date)) {
  exit("Please enter the date in year-month-date style, so January 9, 2007 " .
       "would be 2007-01-09");
}
if (strlen($_REQUEST['end_time_hr'])) {
  $end_hour = $_REQUEST['end_time_hr'];
  if ($_REQUEST['end_time_ampm'] == 'pm') {
    if ($end_hour != 12) {
      $end_hour += 12;
    }
  }
  else {
    if ($end_hour == 12) {
      $end_hour = 0;
    }
  }
  $end_minute = $_REQUEST['end_time_mn'];
  if (!strlen($end_minute)) {
    $end_minute = 0;
  }
}
else {
  $end_hour = 0;
  $end_minute = 0;
}
$end_date = explode('-',$end_date);
$end_date = user_timestamp(0,$end_minute,$end_hour,
                           $end_date[2],$end_date[1],$end_date[0]);
$db->Execute("lock tables `elections_log` write");
$db->StartTrans();
#$db->debug = true;
$res = $db->GetRow("select count(*) as `ct` from `elections_record` " .
                    "where `election_name` = ? limit 1",
                    array($election_name));
if (!count($res)) {
  trigger_error("Error accessing elections_record",E_USER_ERROR);
}
if ($res['ct'] > 0 && !$modify) {
  trigger_error("Election already exists -- choose a different name if you " .
                "are running a different election, or " .
                "<a href='delete_election.php'>delete it if you're done with it</a>",
                E_USER_ERROR);
}
if (!$modify) {
  $db->Execute("insert into `elections_record` (`autoid`,`election_name`,`anon_voting`,`end_date`) values(null,?,?,?)",
               array($election_name,isset($_REQUEST['anon']),$end_date));
}
else {
  $db->Execute("update `elections_record` set `end_date` = ? " .
               "where `election_name` = ?",
               array($end_date,$election_name));
  if ($end_date > time()) {
    $db->Execute("update `elections_record` set `anon_voting` = `anon_voting`-2 " .
                 "where `election_name` = ? and `anon_voting` > 1",
                 array($election_name));
  }
}

function set_election_attrib($attrib,$val) {
  global $db, $election_name,$race_name, $modify;
  if (!isset($race_name)) {
    $race_name = '';
  }
  $oldval = $db->GetRow("select `attrib_value` from `elections_attribs` " .
                        "where `election_name` = ? and `race_name` = ? " .
                        "and `attrib_name` = ?",
                        array($election_name, $race_name,$attrib));
  if (is_empty($oldval)) {
    if ($modify) {
      elections_log($election_name,$race_name,$attrib,
                    null,$val);
    }
    return $db->Execute("insert into `elections_attribs` " .
                        "(`election_name`,`race_name`,`attrib_name`,`attrib_value`) " .
                        "values (?,?,?,?)",
                        array($election_name,$race_name,$attrib,$val));
  }
  else {
    if ($oldval['attrib_value'] != $val) {
      elections_log($election_name,$race_name,$attrib,$oldval['attrib_value'],$val);
      return $db->Execute("update `elections_attribs` set `attrib_value` = ? " .
                          "where `election_name` = ? and `race_name` = ? " .
                          "and `attrib_name` = ?",
                          array($val,$election_name,$race_name,$attrib));
    }
    return null;
  }
}
$race_name = '';
print("<pre>");
if ($modify) {
  elections_log($election_name,null,'start_president_modif',null,null);
}
set_election_attrib('descript',$_REQUEST['descript']);
set_election_attrib('descript_html',isset($_REQUEST['descript_html']));
if ($_REQUEST['interim_results'] == 'sync') {
  $_REQUEST['interim_results'] = isset($_REQUEST['anon'])?0:1;
}
set_election_attrib('interim_results',$_REQUEST['interim_results']);

if (isset($_FILES['descript_file']) && $_FILES['descript_file']['tmp_name']) {
  set_election_attrib('descript_filename',$_FILES['descript_file']['name']);
  set_election_attrib('descript_filetype',$_FILES['descript_file']['type']);
  set_election_attrib('descript_file',
                      file_get_contents($_FILES['descript_file']['tmp_name']));
}
else if (!$modify || array_key_exists('descript_file_remove',$_REQUEST)) {
  set_election_attrib('descript_filename',null);
  set_election_attrib('descript_file',null);
}
  
$num_races = $_REQUEST['num_races'];
$races = array();

$race_attribs = array('race_descript','candidates','num','runoff','threshold',
                      'num_voters','abstain_count','def_val','candidates','member_add',
                      'member_comments','feedback');

if ($modify) {
  $ret = $db->Execute("select `attrib_value` from `elections_attribs` " .
                      "where `election_name` = ? and `attrib_name` = ? order by `autoid`",
                      array($election_name,'race_name'));
  $old_races = array(null);
  while ($row = $ret->FetchRow()) {
    $old_races[] = $row['attrib_value'];
  }
}

print("Number of races: " . $num_races . "\n");
for ($ii = 1, $ii_real = 0, $ii_old = 0; $ii <= $num_races; $ii++) {
  if (!array_key_exists("display_name_$ii",$_REQUEST) || !$_REQUEST["display_name_$ii"]) {
    if ($modify && count($old_races) > $ii) {
      elections_log($election_name,$old_races[$ii],'delete_race',null,null);
      $db->Execute("delete from `elections_attribs` " .
                   "where `election_name` = ? and `race_name` = ?",
                   array($election_name,$old_races[$ii]));
    }
    continue;
  }
  $ii_real++;
  $race_name = $_REQUEST["display_name_$ii"];
  if ($modify && count($old_races) > $ii && $race_name != $old_races[$ii]) {
    elections_log($election_name,null,'rename_race',$old_races[$ii],$race_name);
    $db->Execute("update `elections_attribs` set `race_name` = ? " .
                 "where `election_name` = ? and `race_name` = ?",
                 array($race_name,$election_name,$old_races[$ii]));
    //commented out because attrib_value now stores order of race
#    $db->Execute("update `elections_attribs` set `attrib_value` = ? " .
#                 "where `election_name` = ? and `attrib_name` = ? and " .
#                 "`attrib_value` = ?",
#                 array($race_name,$election_name,'race_name',$old_races[$ii]));
  }
  else {
    set_election_attrib('race_name',$race_name);
  }
  $have_member_comments = false;
  foreach ($race_attribs as $attrib) {
    $val = isset($_REQUEST[$attrib . '_' . $ii])?$_REQUEST[$attrib . '_' . $ii]:null;
    if (($attrib != 'threshold' || !$val) && ($attrib != 'runoff' || strlen($val))) {
      if ($attrib == 'candidates') {
        $val = str_replace("\r\n","\n",$val);
      }
      if ($attrib == 'member_comments' && array_key_exists($attrib . '_' . $ii,$_REQUEST)) {
        $val = '';
        $have_member_comments = true;
      }
      print($attrib . ": " . $val . "<br>");
      set_election_attrib($attrib,$val);
    }
    else {
      if ($val != 2) {
        $numval = $_REQUEST[$attrib . '_' . $ii . '_num'];
      }
      else {
        $numval = -1*$_REQUEST[$attrib . '_' . $ii . '_num_absolute'];
      }
      set_election_attrib($attrib,$numval,true);
    }
  }
  if ($modify && $have_member_comments && 
      array_key_exists("member_comments_current_$ii",$_REQUEST)) {
    set_election_attrib('member_comments',
                        $_REQUEST['member_comments_current']);
  }
}

$race_name = '';
set_election_attrib('num_races',$ii_real);

if ($modify) {
  elections_log($election_name,null,'end_president_modif',null,null);
}
$db->CompleteTrans();
$db->Execute("unlock tables");

?>
<a href='administer_election.php?election_name=<?=escape_html($election_name)?>'>Administer this election</a>
