<?php
//monster page -- the whole elections thing is pretty badly written, and this
//page (along with election_results.php) is just a mess.  Part of it is that
//create_election.php has to "talk" (through the database) with both voting.php
//and election_results.php.
//This page can create or modify an election.  Ok, here goes

//don't print body_insert immediately
$body_insert = '';
require_once('default.inc.php');

//are we submitting this form?
if (!array_key_exists('election_name',$_REQUEST) || 
    (array_key_exists('modify_election',$_REQUEST) &&
     !array_key_exists('election_name_full',$_REQUEST))) { 
  //nope
  ?>
 
 <html><head><title><?=array_key_exists('modify_election',$_REQUEST)?'Modify ':'Create '?>
election</title>
<?php
    ;
 //The label style is so the "Candidates" line is at the top of the textarea,
 //not the bottom.
?>
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
<?php ;
 //one of the few scripts that really needs the utility js functions, and
 //its own auxilary script file (for adding races, validating the form, etc.)
  ?>
<script type='text/javascript' src='<?=$html_includes?>/table_edit.utils.js'></script>
<script type='text/javascript' src='create_election.js'></script>
</head><body>
<?=$body_insert?>
<?php 
   ;
 print_help(); 
 //files can be uploaded through this form, hence the enctype
?>
<form method='POST' enctype='multipart/form-data'
action="<?=this_url()?>" onsubmit='return validate_election_form()'>
<?php
   ;
 //are we modifying, as opposed to creating a new one?
 if (array_key_exists('modify_election',$_REQUEST)) {
   $elect_row = $db->GetRow("select `election_name`,`anon_voting`, `end_date` " .
                            "from `elections_record` where `election_name` = ? ",
                            array($_REQUEST['election_name']));
   if (is_empty($elect_row)) {
     exit("The election you're trying to modify, " . 
          escape_html($_REQUEST['election_name']) . " doesn't exist!");
   }
   $name_full = explode('_',$elect_row['election_name'],3);
   //keep this as a flag so when we submit we know what's going on.
   //It already has the semester, etc., appended.
   print "<input type=hidden name='election_name_full' value='" . 
     escape_html($elect_row['election_name']) . "'>\n";
   //get the date.  It's stored as a timestamp, and has to be
   //translated to the user's time zone, which is what user_time does
   $full_date = explode(' ',user_time($elect_row['end_date'],'Y-m-d H:i:s'));
   $elect_row['end_date'] = $full_date[0];
   //silliness to break up hours and minutes so they can go in form
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
   //get all attributese of election.  Note that races are always
   //ordered by autoid in create_election.php, even though they
   //are ordered by the attrib_value of race_name in other pages.
   $res = $db->Execute("select * from `elections_attribs` where " .
                       "`election_name` = ? order by `autoid`",
                       array($elect_row['election_name']));
   //attribs are retrieved by race name, but we're going to need them
   //in the numeric order of the races, so some data massaging is
   //necessary
   $temp_attribs = array();
   while ($row = $res->FetchRow()) {
     //is this an election-wide (not race-specific) parameter?
     if (!strlen($row['race_name'])) {
       $elect_row[$row['attrib_name']] = $row['attrib_value'];
       continue;
     }
     else {
       //set up the race_name attribs, if first one
       if (!isset($temp_attribs[$row['race_name']])) {
         $temp_attribs[$row['race_name']] = array();
       }
       //add this one on
       $temp_attribs[$row['race_name']][$row['attrib_name']] = $row['attrib_value'];
     }
   }
   //ok, make those race-specific attribs indexed numerically
   $ii = 1;
   $race_attribs = array();
   foreach ($temp_attribs as $race_name => $attribs) {
     //legacy -- feedback should always be written now
     if (!isset($attribs['feedback'])) {
       $attribs['feedback'] = null;
     }
     $race_attribs[$ii] = $attribs;
     $race_attribs[$ii++]['race_name'] = $race_name;
   }
   //user can't modify the election name, or if the election is anonymous
   print "<h4>" . escape_html($elect_row['election_name']) . "</h4>\n";
   print "Voting is " . ($elect_row['anon_voting']?'':'not ') . "anonymous.<br>\n";
   print "<hr>";
 }
 //if it's a new election, start things off (with the parameters that
 //modify can't change
 else {
   $elect_row = null;
   $race_attribs = null;
   //38 is so that we can prepend year_{fall,spring,summer}_
?>
<span id='election_name_span'>Name of election.  The year and semester will be added automatically.  
You can enter at most 38 characters:
  <input type=text id='election_name'
   name='election_name' maxlength=38></span>&nbsp;&nbsp;&nbsp;&nbsp;
<?php
    //logic to figure out the semester, for default choice in the select box
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
Current semester: <select name='semester'>
<option <?=$spring?>>spring
<option <?=$summer?>>summer
<option <?=$fall?>>fall
</select>
<hr>
Is it anonymous? <input type=checkbox name=anon><br>
<?php
  //end of create-specific options.  
             }
 //this is a subtle setting.  election_results.php can show interim
 //results, but often that's a bad idea, especially if the election is
 //anonymous.  Thus, there's a default of "No interim results if
 //anonymous, yes otherwise."  In actual fact, only "interim results
 //on" or "interim results off" is stored in the database.
?>
Can people view partial results before the election is over?  Usually, they
shouldn't be able to unless the election is not anonymous.<br>
<?php #stupid insert for emacs because that quote screwed up formatting'
  //if elect_row is set, we are modifying, so use the pre-set value
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
<span id='end_date_span'>Ending date (enter 2005-11-05 for November 5th, 2005): 
<input type=text id='end_date' name=end_date <?=$elect_row?'value="' . 
                                 escape_html($elect_row['end_date']) . '"':''?>></span><br/>
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
You can put some text that will appear at the top of the voter's page here.
Check here to treat it
<?php #';?>
as HTML (otherwise it will be treated as plain text):
<input type=checkbox name='descript_html'
<?=$elect_row && $elect_row['descript_html']?' checked':''?>>
<textarea name='descript' rows=8 cols=60>
<?=$elect_row?escape_html($elect_row['descript']):''?>
</textarea>
<?php
  //user needs the option to delete an already-uploaded file
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
    print "as well that will be linked to at the top of the voting page";
  }
?>
, you can do that.<br>
<input type="file" name="descript_file" size="40"><br/>
<?php
               ;
//we've finished the preamble, and now start with the races.  By
//default there are 3.
?>
<div id='div_races'>
<input type=hidden name='num_races' id='num_races'
value=<?=$race_attribs?count($race_attribs):3?>>
<?php
   //loop through the races
for ($ii = 1; $ii <= ($race_attribs?count($race_attribs):3); $ii++) {
  //attribs will hold everything we know about the race
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
<?php
  //we just want to indent the buttons a little for prettiness.  These
  //buttons set up some simple options -- maybe they help users, maybe
  //they don't.
?>
<span class='yesno'>
<input type=button value='Make it Yes/No'
onclick='event.preventDefault(); init_yesno(<?=$ii?>);'></span>
<input type=button value='Make it Approved/Not Approved'
onclick='event.preventDefault(); init_yesno(<?=$ii?>); init_approved(<?=$ii?>)'><br>
<span id='display_name_span_<?=$ii?>'>Race name
(like President, or VOC Maintenance): <input name='display_name_<?=$ii?>'
id='display_name_<?=$ii?>'
<?=$attribs?'value="' . escape_html($attribs['race_name']) . '"':''?>
></span><br/>
<?php
  //this description can't be html -- that might be useful to add
?>
<label>Description of race (if necessary):</label>
<textarea name='race_descript_<?=$ii?>' rows=5 cols=60 wrap='off'>
<?=$attribs?escape_html($attribs['race_descript']):''?>
</textarea>
<hr>
Is this just a feedback race (no candidates, just people leaving
feedback, like perhaps a VOC)?
<input type=checkbox name='feedback_<?=$ii?>' id='feedback_<?=$ii?>'
  <?=$attribs['feedback']?'checked':''?> value=1 onchange='feedback_change(this)'><br/>
  (If so, the other options will be ignored)<hr>
<?php
  //everything in the following div will be hidden if the feedback box
  //is checked
?>
<div id='feedback_disable_<?=$ii?>'
  <?=$attribs['feedback']?'style="display: none"':''?>
>
<span id='candidates_span_<?=$ii?>'><label>Candidates
(one per line):</label><textarea name='candidates_<?=$ii?>'
rows=5 wrap="off" id='candidates_<?=$ii?>'>
<?=$attribs?escape_html($attribs['candidates']):''?>
</textarea></span><br>
<span id='num_span_<?=$ii?>'>Number of spots
(if your house elects two Board Reps, and this is an election
for Board Rep, put a 2 here): <input name='num_<?=$ii?>' id='num_<?=$ii?>'size=3 value=
  <?=$attribs && $attribs['num']?$attribs['num']:1?>></span><br/>
<div class='options' id='options_div_<?=$ii?>'>
Options:<br> 
<?php
  if (!$attribs) {
    $radio = 0;
  }
  else {
    $radio = $attribs['runoff'];
  }
  //the following is a little ugly, but I'm too lazy to change it.
  //There are two real radio buttons, and text box that "acts" like a
  //radio button (when it's changed, the radio buttons stop being
  //selected).  Really, it should be like below, with the threshold,
  //where it has a radio button and the input.  Note that unranked
  //preferences mean that there is no ranking at all -- users just
  //choose the N candidates they like.
?> 
<label for='runoff_<?=$ii?>_none'>Nothing special?<input type=radio name='runoff_<?=$ii?>'
id='runoff_<?=$ii?>_none' value=0 
  <?=$radio == 0?'checked':''?> onchange='change_runoff_radio(<?=$ii?>)'
></label><br>
<label for='runoff_<?=$ii?>_instant'>Instant runoff?<input type=radio
name='runoff_<?=$ii?>' id='runoff_<?=$ii?>_instant'
value=1 onchange='change_runoff_radio(<?=$ii?>)'
<?=$radio == 1?'checked':''?>>
</label><br>
<span id='unranked_preferences_span_<?=$ii?>'>Unranked preferences allowed?
Enter the maximum number of preferences here: <input name='runoff_<?=$ii?>_num' id='runoff_<?=$ii?>_num' size=3
onchange='change_runoff_num(<?=$ii?>)'
<?=$radio > 1?' value="' . escape_html($radio) . '"':''?>></span> 
<br/>
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
  //complicated javascript is called here -- the issue is that
  //abstain_count shouldn't be enabled unless there's a threshold
  //(otherwise it's meaningless), and def_val shouldn't be enabled if
  //there's no threshold here *and* there's no absolute threshold for
  //this race.  So that has to be checked.  Also, changing the radio
  //should erase no-longer-applicable numbers, to make sure the user
  //knows what's going on.
?>
<span id='threshold_span_<?=$ii?>'>
<input type='radio' name='threshold_<?=$ii?>' id='threshold_<?=$ii?>_none' value=''
  <?=$radio == 0?' checked':''?> onchange='threshold_change(<?=$ii?>,0)'>
None</label>, 
<input type='radio' name='threshold_<?=$ii?>' id='threshold_<?=$ii?>_percent'
value='percent'
  <?=$radio > 0?' checked':''?> onchange='threshold_change(<?=$ii?>,1)'>
<span id='threshold_<?=$ii?>_num_percent_span'>
<label for='threshold_<?=$ii?>_percent'>At least </label>
<input name='threshold_<?=$ii?>_num' id='threshold_<?=$ii?>_num_percent'
size=2 onclick='get_elt_by_id("threshold_<?=$ii?>_percent").click()'
onchange='get_elt_by_id("threshold_<?=$ii?>_percent").click()'
  <?=$radio > 0?' value="' . escape_html($radio) . '"':''?>>
<label for='threshold_<?=$ii?>_percent'>% of the vote is required</label></span>,
<input type='radio' name='threshold_<?=$ii?>' id='threshold_<?=$ii?>_number'
value='number'
<?=$radio < 0?' checked':''?> onchange='threshold_change(<?=$ii?>,2)'>
<span id='threshold_<?=$ii?>_num_absolute_span'>
<input name='threshold_<?=$ii?>_num_absolute' id='threshold_<?=$ii?>_num_absolute' size=2
onclick='document.getElementById("threshold_<?=$ii?>_number").click()'
onchange='get_elt_by_id("threshold_<?=$ii?>_number").click()'
  <?=$radio < 0?' value="' . escape_html(-$radio) . '"':''?>><label
for='threshold_<?=$ii?>_number'>votes are required</label></span>.</span>
<br/>
<span id='abstain_count_span_<?=$ii?>'
  <?=$radio?'':'style="color: gray"'?>
> Will abstentions count as votes for threshold purposes?
<input type=checkbox name='abstain_count_<?=$ii?>' id='abstain_count_<?=$ii?>'
<?=$attribs && $attribs['abstain_count']?' checked':''?> value=1
  <?=$radio?'':'disabled=true'?> ></span><br/>
<span id='num_voters_span_<?=$ii?>'
>Is there a minimum number of votes that must be cast in order for this race to be decided at all
  (perhaps two-thirds of your house must vote on a bylaw change, however they vote, for
   it to have any chance of passing)?
<input name='num_voters_<?=$ii?>' id='num_voters_<?=$ii?>' size=3 <?=$attribs && $attribs['num_voters']?' value="' . 
  escape_html($attribs['num_voters']) . '"':''?> onchange='num_voters_change(this)'></span>
<br/>
<?php
  $def_val_disable = (!$radio && (!$attribs || !strlen($attribs['num_voters'])));
?>
<span id='def_val_span_<?=$ii?>'
  <?=$def_val_disable?'style="color: gray"':''?>
>What is the default if one of the above thresholds is not met?</span> <input size=20 name='def_val_<?=$ii?>'
id='def_val_<?=$ii?>' <?=$attribs?' value="' . escape_html($attribs['def_val']) . '"':''?>
<?=$def_val_disable?'disabled=true':''?>
><br/>
  (If you leave this blank, there will be no winner if one of the above thresholds is not met.)
</div>
Can the voters add choices themselves?  (Used for things like 
party themes/naming lizards, etc.)
<input type=checkbox name='member_add_<?=$ii?>' id='member_add_<?=$ii?>' value=1
  <?=$attribs && $attribs['member_add']?' checked':''?>>
  </div> <?php // end of feedback change ?>
Can the voters make comments that other voters will see when they are voting?
<input type=checkbox name='member_comments_<?=$ii?>' value='1'
  <?=$attribs && $attribs['member_comments'] !== null?' checked':''?>><br/>
  (Note: this is different from a feedback race -- a comment someone makes is immediately visible to later voters)
<?php
  //allow the president to edit (i.e., delete inappropriate) member comments.
  if ($attribs && $attribs['member_comments'] !== null) {
    print "<br>Member comments so far:<br>\n";
    print "<textarea name='member_comments_current_$ii' cols=70 rows=10 wrap=off>";
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
   <?php 
   exit; 
}
//it doesn't get any easier when it comes to submitting
print $body_insert;
//new election, whose name we have to mangle?
if (!array_key_exists('election_name_full',$_REQUEST)) {
  //if name is too long, screw user -- they're trying to do it.
  $election_name = substr($_REQUEST['election_name'],0,38);
  $semester = $_REQUEST['semester'];
  //keep things alphabetical with punctuation
  switch ($semester) {
  case 'spring': case 'summer': $semester = '-' . $semester; break;
  case 'fall': $semester = '_' . $semester; break;
  default:
    //who would put a fake semester in??
    janak_error(escape_html($semester) . " is not a valid semester!");
  }
  if (!$election_name) {
    exit("You didn't specify an election name!  Go back and put one in.");
  }
  //it's always the current year :)
  $election_name = date('Y') . $semester . '_' . $election_name;
  $modify = false;
}
else {
  $election_name = $_REQUEST['election_name_full'];
  $modify = true;
}
$end_date = $_REQUEST['end_date'];
if (!$end_date) {
  exit("You didn't enter an end date!  Go back and put one in.");
}
//we don't need to check for more than this, because user_timestamp
//will make valid timestamps from almost any input
else if (!preg_match('/\d\d\d\d-\d\d?-\d\d?/',$end_date)) {
  exit("Please enter the date in year-month-date style, so January 9, 2007 " .
       "would be 2007-01-09");
}
//get real hour and minute from form data
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
//Don't want half an election to be created.
$db->StartTrans();
//the elections_log is not that well-equipped to handle the data we're
//throwing at it.  Everything has to come in order, so we need
//exclusive write access.  Due to MySQL's standards, this involves a
//fudge and a half.  The fudge is just that MySQL demands that we lock
//all tables we might touch, and so we have to list them all.  Note
//that if another table is ever accessed, there will be an error
//because it has not been locked here.  Thus, if you ever modify this
//script, make sure that no other tables are being accessed, or add
//them to the lock list.  get_static, for instance, accesses a table,
//etc.  The half is because, in addition to starttrans() doing 'set
//autocommit=0' (which is what we want, and which is sufficient for
//InnoDB tables to do transactions), it also did a 'begin'.  Locking
//tables isn't transaction-safe, and so that begin is implicitly
//commit-ed when this lock table is executed.  That's ok, though,
//because the autocommit=0 keeps us fully rollback-able.  If there's
//an error, the rollback there will roll back all the commands.  Note
//that I don't think this would work with MyIsam tables, but that
//should never happen -- we really need transactional tables.
$db->Execute("lock tables `elections_record` write,`elections_attribs` write, " .
             "`elections_log` write, `modified_dates` write");
//do we have the election already?  elections_record keeps basic data
//about elections a little more handy than elections_attribs.
$row = $db->GetRow("select count(*) as `ct` from `elections_record` " .
                    "where `election_name` = ? limit 1",
                    array($election_name));
//did we not even get the number 0 back?
if (is_empty($row)) {
  janak_error("Error accessing elections_record");
}
if ($row['ct'] > 0 && !$modify) {
  exit("Election already exists -- choose a different name if you " .
       "are running a different election, or " .
       "<a href='administer_election.php?election_name=" .
       escape_html(urlencode($election_name)) .
       "#delete'>delete it if you're done with it</a>");
}
//The anon_voting field of elections_record is tricky.  It's 0 for
//non-anonymous elections, and 1 for anonymous ones, until the
//election is closed (closed means the results are viewable, paper
//ballots have been manually entered, and everything is done.)  Then
//it becomes 2 for non-anonymous, and 3 for anonymous.  If the
//election is reopened due to a modification, it cannot be closed, so
//the anonymous flags have to go down by 2.
if (!$modify) {
  $db->Execute("insert into `elections_record` " .
               "(`autoid`,`election_name`,`anon_voting`,`end_date`) values(null,?,?,?)",
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

//auxilary function which simplifies setting election attributes.  It
//gets complicated because of the race_name variable.  If race_name is
//set, then it's set in the table.  It's also complicated because it
//logs what it does, so it needs to know if it's changed anything
//(MySQL doesn't tell us reliably, at least as of 4.1.whatever).
function set_election_attrib($attrib,$val) {
  global $db, $election_name,$race_name, $modify;
  if (!isset($race_name)) {
    $race_name = '';
  }
    
  if ($modify) {
    //what did the value used to be?
    $oldval = $db->GetRow("select `attrib_value` from `elections_attribs` " .
                          "where `election_name` = ? and `race_name` = ? " .
                          "and `attrib_name` = ?",
                          array($election_name, $race_name,$attrib));
  }
  if (!$modify || is_empty($oldval)) {
    //only log if we're modifying
    if ($modify && $val !== null) {
      elections_log($election_name,$race_name,$attrib,
                    null,$val);
    }
    return $db->Execute("insert into `elections_attribs` " .
                        "(`election_name`,`race_name`,`attrib_name`,`attrib_value`) " .
                        "values (?,?,?,?)",
                        array($election_name,$race_name,$attrib,$val));
  }
  //uh-oh, we're modifying, maybe
  else if ($oldval['attrib_value'] != $val || 
           ($val === null && $oldval['attrib_value'] !== null) ||
           ($oldval['attrib_value'] === null && $val !== null)) {
    elections_log($election_name,$race_name,$attrib,$oldval['attrib_value'],$val);
    return $db->Execute("update `elections_attribs` set `attrib_value` = ? " .
                        "where `election_name` = ? and `race_name` = ? " .
                        "and `attrib_name` = ?",
                        array($val,$election_name,$race_name,$attrib));
  }
  return null;
}
$race_name = '';
#print("<pre>");
//flag in elections_log to pick out modifications
if ($modify) {
  elections_log($election_name,null,'start_president_modif',null,null);
}
set_election_attrib('descript',$_REQUEST['descript']);
set_election_attrib('descript_html',isset($_REQUEST['descript_html']));
//translate sync into real answer
if ($_REQUEST['interim_results'] == 'sync') {
  $_REQUEST['interim_results'] = isset($_REQUEST['anon'])?0:1;
}
//to guard from attackers
else {
  if ($_REQUEST['interim_results']) {
    $_REQUEST['interim_results'] = 1;
  }
  else {
    $_REQUEST['interim_results'] = 0;
  }
}
print "Interim results will ";
if (!$_REQUEST['interim_results']) {
  print "not ";
}
print "be viewable.<br/>\n";
set_election_attrib('interim_results',$_REQUEST['interim_results']);

//file upload section
if (isset($_FILES['descript_file']) && $_FILES['descript_file']['tmp_name']) {
  print "The file " . escape_html($_FILES['descript_file']['name']) .
    " will be linked to at the top of the ballot.<br/>\n";
  set_election_attrib('descript_filename',$_FILES['descript_file']['name']);
  set_election_attrib('descript_filetype',$_FILES['descript_file']['type']);
  set_election_attrib('descript_file',
                      file_get_contents($_FILES['descript_file']['tmp_name']));
}
else if (!$modify || array_key_exists('descript_file_remove',$_REQUEST)) {
  set_election_attrib('descript_filename',null);
  set_election_attrib('descript_filetype',null);
  set_election_attrib('descript_file',null);
}
  
$num_races = $_REQUEST['num_races'];

//get all the old races, if we're modifying
if ($modify) {
  $ret = $db->Execute("select `race_name` from `elections_attribs` " .
                      "where `election_name` = ? and `attrib_name` = ? order by `autoid`",
                      array($election_name,'race_name'));
  $old_races = array(null);
  while ($row = $ret->FetchRow()) {
    $old_races[] = $row['race_name'];
  }
}


//this is used to make sure we don't have any duplicate race names
$all_race_names = array();
//this is how many races we think we have.  As a matter of fact, there
//may be fewer, because some of them may have been deleted.
print("Number of races input: " . $num_races . "<br/>\n");
//things go in order.  We need 3 indices -- $ii runs through the races
//submitted to us through the form, ii_real gives the actual index
//(not counting deleted ones), and ii_old gives the index of the
//current race in the old list of races, assuming we modified
for ($ii = 1, $ii_real = 0, $ii_old = 0; $ii <= $num_races; $ii++) {
  //no name for this race?
  if (!array_key_exists("display_name_$ii",$_REQUEST) || 
      !strlen($_REQUEST["display_name_$ii"])) {
    //then we're deleting the ith race, if it exists
    if ($modify && count($old_races) > $ii) {
      elections_log($election_name,$old_races[$ii],'delete_race',null,null);
      $db->Execute("delete from `elections_attribs` " .
                   "where `election_name` = ? and `race_name` = ?",
                   array($election_name,$old_races[$ii]));
    }
    continue;
  }
  //ok, we really have a race here
  $ii_real++;
  $race_name = $_REQUEST["display_name_$ii"];
  if (isset($all_race_names[$race_name])) {
    exit("<h3>Error: " . escape_html($race_name) . " cannot be the name of " .
         "two different races.  Please choose another name for one of them.</h3>");
  }
  $all_race_names[$race_name] = true;
  //we must have just renamed this old race
  if ($modify && count($old_races) > $ii && $race_name != $old_races[$ii]) {
    elections_log($election_name,$race_name,'rename_race',$old_races[$ii],$race_name);
    $db->Execute("update `elections_attribs` set `race_name` = ? " .
                 "where `election_name` = ? and `race_name` = ?",
                 array($race_name,$election_name,$old_races[$ii]));
  }
  else {
    set_election_attrib('race_name',$ii_real*10);
  }
  //the following loop takes care of things in a pretty automated way.
  //these are the various race attributes that will be set here
  $race_attribs = array('race_descript','feedback','candidates','num','runoff','threshold',
                        'num_voters','abstain_count','def_val','member_add',
                        'member_comments');

  //We need to remember if we have member comments, because if we do
  //(and we're modifying) then we'll update those at the end as well.
  $have_member_comments = false;
  //we need to remember about threshold and num_voters because whether
  //or not we tell the user about def_val will depend on if one of
  //them has been set.
  $threshold = false;
  $num_voters = false;
  $feedback = false;
  print "<h4>Race $ii: " . escape_html($race_name) . "</h4>\n";
  print "<ul>";
  foreach ($race_attribs as $attrib) {
    //all the checkboxes should be set up correctly to actually give a
    //value here.  The exception is member_comments, because it stores
    //the actual member comments in that field, so any non-empty value
    //is interpreted as an actual member comment.
    $val = isset($_REQUEST[$attrib . '_' . $ii])?$_REQUEST[$attrib . '_' . $ii]:null;
    if ($feedback) {
      set_election_attrib($attrib,null);
      continue;
    }
    switch($attrib) {
    case 'feedback':
      if ($val) {
        print "<li>This is a feedback race, just used for getting members' input.</li>";
        $feedback = true;
        break;
      }
      break;
    case 'candidates':
      //avoid stupid bug
      $val = str_replace("\r\n","\n",$val);
      $val = rtrim($val);
      if (!strlen($val)) {
        exit("<h2>You must have at least one candidate!</h2>");
      }
      $arr_val = explode("\n",$val);
      $new_arr_val = array();
      foreach ($arr_val as $cand) {
        $tcand = ltrim(rtrim($cand));
        if (strlen($tcand) && !in_array($tcand,$new_arr_val)) {
          $new_arr_val[] = escape_html($tcand);
        }
        else if (!strlen($tcand)) {
          print "<h4>Discarding candidate '" . escape_html($cand) .
            "' because it is only whitespace.</h4>";
        }
        else {
          exit("<h2>Candidate '" . escape_html($cand) . 
               "' was included multiple times.</h2>");
        }
      }
      print "<li>Candidates are: '" . 
        join("', '",$new_arr_val) . "'</li>";
      $val = join("\n",$new_arr_val);
      break;
    case 'num': 
      if (!ctype_digit($val) || !$val) {
        exit(escape_html($val) . " is not a valid number of winners.");
      }
      print '<li>There will be ' . escape_html($val) . " winner(s).</li>";
      break;
    case 'runoff': 
      switch ($val) {
        case 0: break 2;
        case 1: print "<li>Instant runoff will be used.</li>"; break 2;
      default: 
        $val = $_REQUEST[$attrib . '_' . $ii . '_num'];
        if (!ctype_digit($val) || !$val) {
          exit("<h2>" . escape_html($val) . 
               " is not a valid number of choices to specify.</h2>");
        }        
        print "<li>Users will specify " . escape_html($val) . 
          " unranked choices.</li>";
        break 2;
      default:
        exit("<h2>There was an error reading your options.  " .
             "Please go back and check them.</h2>");
      }
    case 'threshold':
      switch ($val) {
      case '': break 2;
      case 'percent':
        $val = $_REQUEST[$attrib . '_' . $ii . '_num'];
        $threshold = 1;
        if (!is_numeric($val) || $val <= 0) {
          exit("<h2>" . escape_html($val) . 
               " is not a valid percentage threshold.</h2>");
        }
        print "<li>Candidate(s) must get at least " . escape_html($val) . 
          "% of the vote to win.</li>";
        break 2;
      case 'number':
        $threshold = -1;
        $val = $_REQUEST[$attrib . '_' . $ii . '_num_absolute'];
        if ( !$val || !ctype_digit($val)) {
          exit("<h2>" . escape_html($val) . 
               " is not a valid number threshold.</h2>");
        }
        print "<li>Candidates must get at least " . escape_html($val) .
          " votes to win.</li>";
        $val *= -1;
        break 2;
      default:
        exit("<h2>There was an error reading your threshold preference.  " .
             "Please go back and check it.  <span style='display: none'>" .
             escape_html($val) . "</span></h2>\n");
      }
    case 'abstain_count':
      if ($val) {
        $val = 1;
      }
      if ($threshold > 0) {
        print "<li>Abstaining votes will ";
        if (!$val) {
          print "not ";
        }
        print "count towards the total for threshold purposes.</li>";
      }
      break;
    case 'num_voters':
      if (strlen($val) && (!$val || !ctype_digit($val))) {
        exit("<h2>" . escape_html($val) . 
             " is not a valid minimum number of voters.</h2>");
      }
      if (strlen($val)) {
        $num_voters = true;
        print "<li>At least " . escape_html($val) . " voters must cast " .
          "ballots for this race to have a winner.</li>";
      }
      break;
    case 'def_val':
      if (strlen($val) && ($threshold || $num_voters)) {
        print "<li>If no candidate satisfies the requirements, " . escape_html($val) . 
          " will win.</li>";
      }
      break;
    case 'member_comments':
      //member comments has to be inferred from presence, not from its
      //value, see above
      if (array_key_exists('member_comments_' . $ii,$_REQUEST)) {
        $have_member_comments = true;
        $val = '';
        print "<li>Members may make comments that other members will see when " .
          "voting and on the election results page.</li>";
      }
      break;
    case 'member_add':
      if ($val) {
        print "<li>Members may add candidates to this race when they vote.</li>";
      }
      break;
    default:
    }
    //actually set it
    set_election_attrib($attrib,$val);
  }
  //deal with any modified member comments
  if ($modify && $have_member_comments && 
      array_key_exists("member_comments_current_$ii",$_REQUEST)) {
    set_election_attrib('member_comments',
                        $_REQUEST["member_comments_current_$ii"]);
  }
  print "</ul>";
  //done with race loop!
}

$race_name = '';
set_election_attrib('num_races',$ii_real);
print "Actual number of races: " . escape_html($ii_real) . "<br/>\n";

if ($modify) {
  elections_log($election_name,null,'end_president_modif',null,null);
}
$db->CompleteTrans();
$db->Execute("unlock tables");

?>
<a href='administer_election.php?election_name=<?=escape_html($election_name)?>'>Administer this election</a>
