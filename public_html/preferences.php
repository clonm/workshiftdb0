<?php
//page gets user preferences.  One of the first pages I wrote.  It's
//been redone a number of times, but it still probably has legacy
//issues.
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}
//this file will define $php_includes, the includes directory, and include
//the main includes file, which creates the database object and initializes it.
//No functions to call -- it just does it.
$require_user = 'ok_nouser';
$body_insert = '';
require_once('default.inc.php');
array_pop($days);
//uncomment to view the sql statements being sent back and forth
//note that this will break the javascript
//if the require_user function has the member name set, we're viewing saved preferences
if ($member_name) {
  $init_flag = true;
}
else {
  $init_flag = false;
}
$no_js = array_key_exists('no_js',$_REQUEST);
//this is how many blank spots are given for people to enter their preferences
//before they have to press the button to add a new one.  Change it if you want.
$num_blank_shifts = 3;
//this page lets users submit preferences.  It's also included by
//view_prefs.php for users to see what their preferences were, and
//modify them.  It has to work with all browsers, since users may not
//all be using Firefox.
//let user know about any problems at all
//get_houselist does what you think
$houselist = get_houselist();
//insert this text later on
$prefs_due_date = get_static('prefs_due_date');
if (!isset($prefs_due_date)) {
  $prefs_due_date = "Thursday, September 8";
}
$shifts_start_date = get_static('shifts_start_date');
if (!isset($shifts_start_date)) {
//shifts normally start the Monday after preference sheets are due
  $shifts_start_date = date('l, F j',strtotime('next Monday',
					       strtotime($prefs_due_date)));
}
$max_rating = get_static('wanted_max_rating',5);
$whiches = array();
for ($ii = 0; $ii <= $max_rating; $ii++) {
  $whiches[] = $ii;
}
//are we going from previously stored preferences that are being given to us?
if ($init_flag) {
  $mem_info = $db->GetRow("SELECT * FROM `house_info`,`personal_info` " .
                          "WHERE `house_info`.`member_name` = ? AND " .
                          "`personal_info`.`member_name` = ?",
                          array($member_name,$member_name));
  if (!count($mem_info)) {
    $mem_info['email'] = '';
    $mem_info['phone'] = '';
    $mem_info['room'] = '';
    $mem_info['notes'] = '';
    $mem_info['privacy'] = 0;
    for ($ii = 0; $ii < 7; $ii++) {
      $mem_info["av_$ii"] = 0;
    }
  }
  $email = $mem_info['email'];
  $phone = $mem_info['phone'];
  $room = $mem_info['room'];
  $privacy_room = $mem_info['privacy'] & 1;
  $privacy_phone = $mem_info['privacy'] & 2;
  $privacy_email = $mem_info['privacy'] & 4;
  $notes = $mem_info['notes'];
  for ($ii = 0; $ii < 7; $ii++) {
    $flags[$ii] = $mem_info["av_$ii"];
  }
  $wanted_shifts = array();
  $res = $db->Execute("select * from `wanted_shifts` where `member_name` = ?",
                      array($member_name));
  while ($row = $res->FetchRow()) {
    if (!$row['is_cat']) {
      $prefix = 'sft_';
    }
    else {
      $prefix = 'cat_';
    }
    $wanted_shifts[$prefix . $row['shift_id']] = $row['rating'];
  }
} //init_flag
else {
  $member_name = $email = $phone = $room = $notes = '';
  $privacy_room = $privacy_email = $privacy_phone = null;
  $wanted_shifts = array_flip($whiches);
  foreach ($wanted_shifts as $key => $val) {
    $wanted_shifts[$key] = array();
  }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0//EN" --"http://www.w3.org/TR/REC-html40/strict.dtd"-->
<html><head><title>PERMANENT WORKSHIFT PREFERENCE SHEET</title>
<script type="text/javascript">
var theForm;
var wantedButton;
var unwantedButton;
var name_index = "__name";
var num_mods;
var modifiers = new Array();
var msie;
//set up page, initialize variables, do a ton of stuff
function initialize() {
  //not supposed to be good to test for browsers using explicit codes, but
  //I'm too lazy to fix this
  var ua = window.navigator.userAgent;
  msie = ua.indexOf ( "MSIE " );

}

//before submitting, there must be a name and password entered
function check_form()
{
var member_name = document.getElementById("member_name");
if (member_name.selectedIndex < 1) {
alert("Please enter your name.");
member_name.focus();
return false;
}
var passwd = document.getElementById("passwd");
if (!passwd.value) {
alert("Please enter a password.");
passwd.focus();
return false;
}
return true;
}

//add either a wanted or unwanted preference
function addfields(arg1)
{
  var prefix;
  var button;
  var counter;
  if (arg1 == 0) {
    prefix = "wanted";
    button = wantedButton;
    counter = wanted_num++;
  }
  else {
    prefix = "unwanted";
    button = unwantedButton;
    counter = unwanted_num++;
  }
  var p = document.createElement("p");
  var input;
  //ah, browser incompatibility
  if (msie>=0) {
    input = document.createElement("<SELECT onchange=display_choices(" + 
				   arg1 + "," + counter + ")>");
  }
  else {
    input = document.createElement("SELECT");
    input.setAttribute("onchange","display_choices(" + arg1 + "," + counter + ")");
  }  
  p.appendChild(input);
  button.parentNode.insertBefore(p,button);
  input.setAttribute("id",prefix + counter);
  input.setAttribute("name",input.id);
  var oOption;
<?php
    //the full list of workshifts gets put in here, as it does later on.
$res = $db->Execute("SELECT DISTINCT " . bracket('workshift') .
		    " FROM " . bracket('master_shifts') . 
		    " ORDER BY " . bracket('workshift'));
  $workshiftlist = array();
  while ($row = $res->FetchRow()) {
    $workshiftlist[] = $row['workshift'];
  }
?>
//make the blank option
  oOption = document.createElement('option');
  oOption.innerText = '';
  input.appendChild(oOption);
  oOption.text = '';
  input.appendChild(oOption);
<?php
    //option for every workshift
    foreach ($workshiftlist as $shift) :
?>
  oOption = document.createElement('option');
  oOption.text = <?=dbl_quote($shift)?>;
  input.appendChild(oOption);
  oOption.innerText = <?=dbl_quote($shift)?>;
  input.appendChild(oOption);
<?php endforeach; ?>
}

//once the user selects a workshift, new fields have to spring into
//action and tell the user what the sub-choices are
function display_choices(arg1, counter) {
  var prefix = (arg1? "unwanted" : "wanted");
  var curelt = document.getElementById(prefix + counter);
  //get rid of any old choices there might have been
  for (var modifs = curelt.nextSibling; modifs != null;
       modifs = curelt.nextSibling) {
    curelt.parentNode.removeChild(modifs);
  }
  //what was selected?
  var selection = curelt.options[curelt.selectedIndex].text;
  //maybe we're in IE
  if (!selection)
    selection = curelt.options[curelt.selectedIndex].innerText;
  //does this workshift have any modifiers?
  if (modifiers[selection]) {
    //loop through all the possible modifiers
    for (var outloop = 0; outloop<num_mods; outloop++) {
      var fieldname = modifiers[name_index][outloop];
      //aha!  here's one that applies
      if (modifiers[selection][fieldname]) {
        var nameelt = document.createTextNode(fieldname + ": ");
        curelt.parentNode.appendChild(nameelt);
	var input = document.createElement("SELECT");
	curelt.parentNode.appendChild(input);
	//not sure why this is here
	input.focus();
	input.setAttribute("id",prefix + counter + fieldname + "[]");
	input.setAttribute("name",input.id);
	input.setAttribute("multiple","true");
	var oOption;
	//add in all the possible options for this modifier
	for (var loop = 0; loop<modifiers[selection][fieldname].length; loop++) {
	  oOption = document.createElement('option');
	  oOption.text = modifiers[selection][fieldname][loop];
	  oOption.innerText = modifiers[selection][fieldname][loop];
          oOption.value = modifiers[selection][fieldname][loop];
	  input.appendChild(oOption);
	}
	//make sure there are no unsightly scrollbars
	input.size = input.length;
	input.selectedIndex = -1;
      }
    }
  }
}

//programmatically select one or more options from a select element
function multiple_select(elt, option) {
  elt.focus();
  for (ii = 0; ii < elt.options.length; ii++) {
    if (elt.options[ii].value == option || elt.options[ii].innerText == option) {
      elt.options[ii].selected = true;
      break;
    }
  }
  elt.blur();
}

</script>

<style type='text/css'>
div.hidearrow {
  float: right; 
  width: 25px; 
  overflow: hidden;
}
</style>

</head>
<!-- initialize the document -->
<body onLoad="initialize()">
<?php
print $body_insert;
if (!$no_js) {
 $_GET['no_js'] = true; 
  print("<p><a href='" . this_url() . "'>Use a version of this page " .
        "that does not require javascript</a></p>");
  unset($_GET['no_js']);
}
else { 
?>
 <h3>Note that you may not be able to add
more wanted or unwanted shifts on this page.  Press back on your browser or   
<a href='preferences.php'>Click here to view this page with full javascript functionality</a>
</h3>
<?php 
    }
if (!$init_flag) {?>
<h4>If you have already filled out this sheet and want to modify your preferences,
   <a href='person.php'>do it here</a></h4>
<?php } 
print_static_text('preferences_instructions_top',
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
                  true,true);
?>
<p>
<p>

<form name="workshift_form" id="workshift_form" method="post" 
onSubmit="return check_form()" action="record_prefs.php">
<p>
Name:<SELECT name="member_name" id="member_name">
<OPTION>
<?php
foreach ($houselist as $name) {
  if ($name === $member_name) {
    print "<option value='" . escape_html($name) . 
    "' selected>" . escape_html($name) . "\n";
  }
  else {
    print "<option value='" . escape_html($name) . 
    "'>" . escape_html($name) . "\n";
  }
}
?>
</SELECT>
<table>
<tr><td>Room #:</td>
<td><INPUT type="text" size="3" id="room" name="room" value='<?=escape_html($room)?>'></td><td>
Show room in directory: <input type=checkbox name='privacy_room' 
<?=$privacy_room?' checked':''?>></td></tr><tr>
<td>Phone Number:</td><td><INPUT type="text" size="20" id="phone" name="phone" value='<?=escape_html($phone)?>'></td><td>Show phone in directory: <input type=checkbox name='privacy_phone' 
<?=$privacy_phone?' checked':''?>></td></tr><tr>
<!-- some question in my mind of whether email might become a reserved
word in something -->
<td>Email Address:</td><td><INPUT type="text" size="30" id="email" name="email" value='<?=escape_html($email)?>'></td><td>Show email in directory: <input type=checkbox name='privacy_email' 
<?=$privacy_email?' checked':''?>></td></tr></table>
<p>
The email list is one of the primary ways that you will receive
important information on house and workshift policy. You are
responsible for reading the emails and letting me know if you haven't
been getting them. The full list is not available to anyone but the elected
managers.  
</p>
<p>
Password: <INPUT type="password" size="30" id="passwd" name="passwd" value=''>
</p>
<p>
The very first time you fill out this form, set a password you wish to use.  
Using this password, you can view and re-enter your preferences if needed, 
and also view other workshift data during the semester.  If you filled this form
out last semester, use the <strong>same password</strong>!
It's saved from semester to semester.
</p>
<p>
<a href='shift_descriptions.php' target='shift_descriptions'>More info about each workshift</a><br>
<a href='workshift_doc.php' target='workshift_doc'>Workshift document</a></p>
<p>
<?php
print "<p>";
print_static_text('preferences_shift_rating_instructions',
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
   true,true);
?>
<p>
<?php
$category = null;
$workshifts_done = array();
$firstflag = 0;
$res = $db->Execute("select *, " .
                    "if(isnull(category) or length(trim(category))=0,null,category) " .
                    "as `catcleaned` from `master_shifts` " .
                    "where `category` is null or " .
                    "substring(`category`,1,1) != '*'" .
                    "order by ISNULL(`catcleaned`), `catcleaned`, `workshift` ASC");
print "<table>";
$category = 'janakjanak';
while ($row = $res->FetchRow()) {
  if ($category !== $row['catcleaned']) {
    $category = $row['catcleaned'];
    if ($firstflag++) {
      print "</div></td></tr>";
    }      
    print "<tr><td>";
    if ($category) {
      print "<input size=3 name='cat_" . 
        escape_html($row['category']) . "' ";
      if (isset($wanted_shifts['cat_' . $row['category']])) {
        print " value='" . $wanted_shifts['cat_' . $row['category']] . "'";
      }
      print ">" . ucfirst(escape_html($row['category'])) . " <input type=button value='Expand' ";
      print <<<ONCLICK
onclick='if (this.value == "Expand") {
document.getElementById("div_{$row['autoid']}").style.display = "";
this.value = "Hide";
}
else {
document.getElementById("div_{$row['autoid']}").style.display = "none";
this.value = "Expand";
}
return false;' ><div id='div_{$row['autoid']}' style='display: none; margin-left: 10px'>
ONCLICK
;
    }
    else {
      print "Uncategorized shifts:<br/>\n";
    }
  }
  print "<input type=hidden name='nm_sft_" . escape_html($row['autoid']) . 
      "' value='" . escape_html($row['workshift']) . "'>" . 
    "<input size=3 name='sft_" . escape_html($row['autoid']) . "' ";
  if (isset($wanted_shifts['sft_' . $row['autoid']])) {
    print " value='" . $wanted_shifts['sft_' . $row['autoid']] . "'";
  }
  print ">" . escape_html($row['workshift'])  . "<br/>";
}
if ($firstflag) {
  print "</div></td></tr>";
}
print "</table>";    
?>

<p>

YOUR SCHEDULE
<br>
Leave blank times you are neutral towards, put a + in times you would like to
  work, a - in times you would prefer not to work, an x in times you absolutely
  <strong>cannot</strong> work (try to give a reason in the notes -- job, classes, etc.),
and a ? if you are still unsure of your availability at this time (you can come back and change it
when you know.)  If your schedule still isn't completely settled by
<?=escape_html($prefs_due_date)?>, make your best guess-your permanent shifts for the
semester will be determined by what you mark.<br>
(You can use the keyboard to type the symbol and the &lt;tab&gt; key to move between cells to avoid
using the mouse)<br>
</p>
<table border=1>
<tr><td></td>
<?php
#'"stupid emacs formatting";
//you can change the intervals the day is subdivided into -- change the 16
//below into the number you want, change how the table headers are printed
//out, change show_prefs.php similarly, and finally change the can_do
//javascript routine in master_shifts.php, which will be a headache --
//don't bother, actually.
$hours = array("8am", "9am", "10am", "11am", "12pm");
foreach ($hours as $hour) {
  print "<td>$hour</td>";
}
for ($ii = 1; $ii<12; $ii++) {
  print "<td>${ii}pm</td>";
}
print "</tr>\n";
$weekday = 0;
$av_options = array('+','','-','x','?');
foreach($days as $day) {
  print "<tr>";
  print "<td>$day</td>";
  for ($ii=0; $ii<16; $ii++) {
    if (isset($flags)) {
      $temp = decode_avail($flags[$weekday],$ii);
    }
    else {
      $temp = 1;
    }
    print "<td><div class='hidearrow'><select name='av_{$weekday}_{$ii}'>\n";
    foreach ($av_options as $key => $val) {
      print "<option value='$key'";
      if ($key == $temp) {
        print " selected";
      }
      print ">" . escape_html($val) . "\n";
    }
    print "</div></select></td>";
  }
  print "</tr>\n";
  $weekday++;
}
?>
</table></p><p>
You can write anything else you want me to know here.  Let me know if you
have any condition that would impair you from being able to complete a
given shift (i.e. disabilities, allergies, pregnancy, etc). Anything
you tell me is completely confidential, but if you don't let me know,
I can't help you.<br>
Notes:</p><p> <textarea rows=10 cols=40 id="notes" name="notes">
<?=escape_html($notes)?></textarea>
</p><p>
<p>
<input type="submit" value="Submit">
</p>
</form>
<p id="phptime" style="font-size: 10px">
PHP generated this page in <?=round(array_sum(split(' ',microtime()))-$php_start_time,2)?> seconds.
</p>
</body>
</html>
