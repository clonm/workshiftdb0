<?php
$body_insert = '';
require_once('default.inc.php');
?>
<html><head><title>Update basics</title></head><body>
<?=$body_insert?>
<?php 
//important page that sets lots of parameters.  Cute stuff with
//automatic setting of parameters (and _bool flag), but otherwise not
//a whole lot going on here in terms of programming.  There's some
//javascript, I guess.
//are we submitting this page's form?
if (!isset($_REQUEST['basic_consts_submitting_bool'])) {
  //we really should have an option to recover system values from
  //previous academic or previous summer semester.
  ?>
This can be a confusing page.  Please 
  <a href='<?=$wiki_url?>Manager#basic_consts' target='help'>look at the help</a>
if you are confused, or email <?=admin_email()?>.
<hr/>
<form action='<?=this_url()?>' method='POST'>
<input type=hidden name='basic_consts_submitting_bool' value=1>
<ul>
<li>Hours of workshift owed per week (you can always change any given person's
<!-- emacs formatting ' -->
obligations for a given week): 
<input name='owed_default' value='<?=get_static('owed_default',5)?>'><br/>
<li>Number of weeks in the semester (usually 18):
  <input name='tot_weeks' value='<?= get_static('tot_weeks',18)?>'><br/>
</ul>
<hr>
<h4>Setup of preference forms</h4>
<ul>
<li>Due date of preference forms (enter like Thursday, September 8): 
<input name='prefs_due_date' value='<?= get_static('prefs_due_date')?>'>
<li>Start of permanent shifts (Usually a Monday -- enter like Monday, September 12): 
<input name='shifts_start_date' value='<?= get_static('shifts_start_date')?>'>
<br/>
<div id='rating_div' style='border: groove;'>
Please fill out the following.
All three values should be whole numbers:<br/>
<input name='wanted_max_rating' value='<?=get_static('wanted_max_rating',5)?>'>
What is the maximum value a member can rank a shift (the minimum is always 0? <br/>
<input name='min_wanted_rating' 
value='<?=get_static('min_wanted_rating',get_static('wanted_max_rating'))?>'>
What is the least value a member can rank a shift which still make you think she
wants it?  (In other words, if the max rating is 10, if a member ranks a shift 9, they
probably still want it.  What's the least number like that?) 
<br/>
<input name='default_rating'
value='<?=get_static('default_rating',max(0,get_static('min_wanted_rating')-1))?>'>
What is the default rating for a shift?  I.e., if a member does not rank a shift,
what is your assumption about how they feel about that shift?
The maximum, the minimum, or somewhere in the middle?
</div>
<li>The following text will appear on the 
<a href='../preferences.php' target='_new'>preferences form</a>.  You can modify
it.<br/>
What appears at the top:<br/>
<textarea cols=80 rows=10 name='preferences_instructions_top_text'>
<?php
print escape_html(get_raw_static_text('preferences_instructions_top',
<<<INSTRUCTIONS
This sheet is your voice in the process of workshift
allotment. <strong>It is due by %prefs_due_date, so that
permanent shifts can begin on %shifts_start_date</strong>. I
start processing these forms as soon as I get them, so the sooner you
turn yours in, the better off you're likely to be.  Not filling this
form out is equivalent to saying, "I refuse to take responsibility for
myself and I submit myself and my time to you, oh Czar of Labor" and
any and all shifts assigned to someone who didn't turn in this sheet
will be their responsibility until they find someone to trade with
them. So fill this out and submit it ASAP.
INSTRUCTIONS
                  ,array('%prefs_due_date' =>
                         array("Preferences due date",'prefs_due_date'),
                         '%shifts_start_date' =>
                         array("Shifts start date",'shifts_start_date')),
                  true));
?>
</textarea><br/>
Do you want this to be html?
<input type=checkbox name='preferences_instructions_top_bool'
<?=get_is_html_text('preferences_instructions_top')?' checked ':''?>
><br/>
(You can use the following escape codes to insert data into this text:<br/>
<?php
 $esc = get_escapes_text('preferences_instructions_top');
 foreach ($esc as $code => $datum) {
   print escape_html($code) . " will insert the " . escape_html($datum) . "<br/>";
 }
 ?>
 )<p>
What appears before the shift preferences:
<div id='rating_div2'>
<textarea cols=80 rows=10 name='preferences_shift_rating_instructions_text'>
<?php
print escape_html(get_raw_static_text('preferences_shift_rating_instructions',
<<<INSTRUCTIONS
Rank each category of shifts from 0 (absolutely hate, hate, hate) to
%max_rating (I love these shifts to pieces).
If you do not rank a category, it will be assumed
that you are giving it the maximum rating.  To rank a shift within a category
differently from the category, click on the "Expand" button next to the category.
Otherwise, all shifts within the category will get the category rating.
INSTRUCTIONS
   ,
   array('%max_rating' => array("Maximum rating",
  "*get_static('wanted_max_rating',5)")),
   true));
?>
</textarea><br/>
Do you want this to be html?
<input type=checkbox name='preferences_shift_rating_instructions_bool'
<?=get_is_html_text('preferences_shift_rating_instructions')?' checked ':''?>
><br/>
(You can use the following escape codes to insert data into this text:<br/>
<?php
 $esc = get_escapes_text('preferences_shift_rating_instructions');
 foreach ($esc as $code => $datum) {
   print escape_html($code) . " will insert the " . escape_html($datum) . "<br/>";
 }
 ?>
 )<p>
</div>
</ul>
<hr>
<h4>Houselists -- only for Sherman and Hoyt right now</h4>
Does your house only get a single houselist from CO, always (i.e., not separated
by gender)?  (Sherman and Hoyt should check this box, other houses
should not.  If you just have a single houselist over summer, don't check
this box.)<br/>
<!-- emacs formatting ' -->
<input type=checkbox 
name='allow_single_houselist_upload_bool'
<?=get_static('allow_single_houselist_upload')?'checked':''?>><p>
<hr>

<input type=submit value=Submit>
</html>
<?php
              exit;
              } 
echo "<input type=submit value='View parameters you set' " .
  "onclick=\"document.getElementById('" .
  "parameter_messages').style.display = '';\"><br/>";
print "<div id='parameter_messages' style='display: none'>";
//It's too hard to manually set each setting.  Just loop through $_REQUEST
//checkboxes have to be dealt with separately, since they aren't present
//in $_REQUEST if they're not checked
$shift_flag = false;
foreach (array('allow_single_houselist_upload_bool') as $key) {
  $real = substr($key,0,-5);
  print("Turned " . escape_html($real) . " o");
  if (isset($_REQUEST[$key])) {
    set_static($real,true);
    print("n");
  }
  else {
    set_static($real,false);
    print("ff");
  }
  print("<br/>\n");
}
foreach ($_POST as $key => $val) {
  //empty strings are null
  if ($val === '') {
    $_REQUEST[$key] = $val = null;
  }
  //skip the checkboxes
  if (substr($key,-5) == '_bool') {
    continue;
  }
  switch ($key) {
  case 'session_id': case 'officer_session_id':
    continue;
  }
  if (substr($key,-5) == '_text') {
    $key = substr($key,0,-5);
    set_static_text($key,$val,array_key_exists($key . '_bool',$_REQUEST));
  }
  else {
    set_static($key,$val);
  }
  print("Set " . escape_html($key) . " to <div style='" . white_space_css() . 
        "'>" . escape_html($val,true) . "</div>\n");
}
//If the total number of weeks is more than the week some fining period ends,
//that fining period will become a "zombie," so we just clear it out.
$db->Execute("delete from `fining_periods` " .
             "where `week` > ?", array(get_static('tot_weeks')));

//not using ratings, but that just means we're rating out of 2, with default 1
print "</div>";

?>
All done!
<p>
You can go and
<ul>
<li><a href="update_house.php">update the house list</a>;
<li><a href='master_shifts.php'>modify the workshifts</a>;
<li><a href="set_shift_descriptions.php">give descriptions of workshifts</a>;
<li><a href="upload_workshift_doc.php">upload a workshift policy document</a>;
<li><a href="weekly_totals_consts.php">set buffer, floor, and fining rate for
weekly totals</a>;
<li><a href="online_signoff_setup.php">set up online signoffs, if you want your
house to use them.</a>
</ul>
  (All these links are available on the <a href='index.php'>front page</a>.)
</body>
</html>
