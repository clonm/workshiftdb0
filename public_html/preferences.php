<?php
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}
//this file will define $php_includes, the includes directory, and include
//the main includes file, which creates the database object and initializes it.
//No functions to call -- it just does it.
$require_user = 'ok_nouser';
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
$shift_prefs_style = get_static('shift_prefs_style',0);
if (!$shift_prefs_style) {
  $whiches = array('wanted','unwanted');
}
else {
  $max_rating = get_static('wanted_max_rating',5);
  $whiches = array();
  for ($ii = 0; $ii <= $max_rating; $ii++) {
    $whiches[] = $ii;
  }
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
  if (!$shift_prefs_style) {
  //add in the wanted/unwanted preferences
  foreach ($whiches as $which) {
    $wanted_shifts[$which] = array();
#    $db->debug = true;
    $res = $db->Execute("SELECT `shift`,`day`,`floor` FROM `wanted_shifts` " .
                        "WHERE `member_name` = ? and `rating` = ?",
                        array($member_name,
                              $which == 'wanted'?2:
                              ($which == 'unwanted'?0:$which)));
    while ($wanted_shifts[$which][] = $res->FetchRow()) ;
    array_pop($wanted_shifts[$which]);
  }
  }
  else {
    $res = $db->Execute("select * from `wanted_shifts` where `member_name` = ?",
                        array($member_name));
    while ($row = $res->FetchRow()) {
      if ($row['day'] == 'shift') {
        $prefix = 'sft_';
      }
      else {
        $prefix = 'cat_';
      }
      $wanted_shifts[$prefix . $row['shift']] = $row;
    }
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
if (!$shift_prefs_style) {
  $wanted_num = array();
  foreach ($whiches as $which) {
    $wanted_num[$which] = count($wanted_shifts[$which]);
  }
  if (!$no_js) {
    $wanted_attribs = array();
    foreach($whiches as $which) {
      $wanted_attribs[$which] = array();
      for ($ii = 0; $ii < count($wanted_shifts[$which]); $ii++) {
        $wanted_attribs[$which][$ii] = array();
      }
    }
  }
  else {
    $attribs = array();
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
<?php
if (!$shift_prefs_style) { ?>
var wanted_num = <?=$wanted_num['wanted']+$num_blank_shifts?>;
var unwanted_num = <?=$wanted_num['unwanted']+$num_blank_shifts?>;
var num_blank_shifts = <?=$num_blank_shifts?>;
<?php
    }
?>
//set up page, initialize variables, do a ton of stuff
function initialize() {
  //not supposed to be good to test for browsers using explicit codes, but
  //I'm too lazy to fix this
  var ua = window.navigator.userAgent;
  msie = ua.indexOf ( "MSIE " );
<?php
    if (!$shift_prefs_style) {
?>
  wantedButton = document.getElementById("wanted_button");
  unwantedButton = document.getElementById('unwanted_button');
  modifiers[name_index] = new Array();
  //we need to make sure that all select boxes are cleared if they're not
  //selected and if there isn't stored info
  var ii;
  for (ii = wanted_num-num_blank_shifts; ii < wanted_num; ii++) {
    document.getElementById('wanted' + ii).selectedIndex = 0;
  }
  for (ii = unwanted_num-num_blank_shifts; ii < unwanted_num; ii++) {
    document.getElementById('unwanted' + ii).selectedIndex = 0;
  }

  <?php
      //we now have to get all the workshifts, determine what modifiers each
      //of them have (modifiers means floor, day currently, but it might be 
      //anything), and associate them in a big array
      $booldays = '';
  $stringdays = '';
  $dummy_string = get_static('dummy_string'); 
  foreach ($days as $day) {
    $booldays .= "OR not (" . bracket($day) . " <=> ?) ";
    $stringdays .= ", " . bracket($day);
  }
  $result = $db->execute("SELECT `category`," . bracket('workshift') . ", " . 
			 bracket('floor') . $stringdays . 
		      " FROM " . bracket('master_shifts') . 
		      " WHERE ((" . bracket('floor') . " IS NOT NULL) AND " .
		      "(NOT " . bracket('floor') . " = 0)) " . 
		      $booldays . " ORDER BY " . bracket('workshift') .
		      ", " . bracket('floor'), array_fill(0,7,$dummy_string));
  $workshift = 0;
  $indices = array(); $cur_mods = array(); $days_done = array();
  //loop through result set
  while ($modifs = $result->FetchRow()) {
    if ($modifs['category']{0} == '*') {
      continue;
    }
    //have we not figured out the possible modifiers yet?
    //actually this is here because we want to treat $result as an array 
    //of arrays as much as possible, so we have to get the first row with while()
    if (!isset($modif_keys)) {
      //build up regex to match (actually, not match) the days and workshift
      $grepdays = '';
      foreach ($days as $day) {
	$grepdays .= "|($day)";
      }
      $grepdays = '(workshift)' . $grepdays;
      //what keys do we have which aren't workshift or day? (days have to be
      //handled separately because there is a separate column per day, as opposed
      //to the other modifiers, which are assumed to be in one column)
      $modif_keys = array_values(preg_grep("/$grepdays/", 
					   array_keys($modifs),
					   PREG_GREP_INVERT));
    }
    
    //is the current workshift different from the last one we did?
    if ($workshift !== $modifs['workshift']) {
      //output javascript, a new array element for this workshift
      print "  modifiers[" . dbl_quote($modifs['workshift']) . 
	"] = new Array();\r\n";
      //do the attributes setting, assuming we have stored preferences,
      //or do the global attributes setting, assuming we have no js
      if ($no_js) {
        foreach ($cur_mods as $attrib => $arr) {
          if (!isset($attribs[$attrib])) {
            $attribs[$attrib] = array();
          }
          $attribs[$attrib] = array_unique(array_merge($attribs[$attrib],array_keys($arr)));
        }
      }
      else if ($init_flag) {
        foreach ($whiches as $which) {
          for ($jj = 0; $jj < count($wanted_shifts[$which]); $jj++) {
            if ($wanted_shifts[$which][$jj]['shift'] === $workshift) {
              foreach ($cur_mods as $attrib => $arr) {
                $wanted_attribs[$which][$jj][$attrib] = array_keys($arr);
              }
              $wanted_attribs[$which][$jj]['day'] = array_keys($days_done);
            }
          }
        }
      }
      $workshift = $modifs['workshift'];
      $indices = array(); $cur_mods = array(); $days_done = array();
      $ii = 0;
    }
    //does this workshift have specific days?  If so, put in a sub-array
    foreach ($days as $day) {
      if ($modifs[$day] != $dummy_string && 
	  !array_key_exists($day,$days_done)) {
	$days_done[$day] = 1;
	if (!$ii) {
	  print "  modifiers[" . dbl_quote($workshift) . 
            "]['day'] = new Array();\r\n";
	}
	print "  modifiers[" . dbl_quote($workshift) . "]['day'][$ii] = " .
          dbl_quote($day) . ";\r\n";
	$ii++;
      }
    }
    //do the remaining modifiers.  So far it's only floor, but who knows
    foreach ($modif_keys as $key) {
      //make sure this modifier actually exists, and that we haven't set it yet
      if (!array_key_exists($key,$modifs) ||
	  is_null($modifs[$key]) || $modifs[$key] === '' ||
	  (array_key_exists($key,$cur_mods) && 
	   array_key_exists($modifs[$key],$cur_mods[$key]) && 
	   $cur_mods[$key][$modifs[$key]]))
	continue;
      //we won't set this again
      $cur_mods[$key][$modifs[$key]] = 1;
      //which index are we on?
      if (!array_key_exists($key,$indices)) {
	print "  modifiers[" . dbl_quote($workshift) . "][" . dbl_quote($key) . 
          "] = new Array();\r\n";
	$indices[$key] = 0;
      }
      print "  modifiers[" . dbl_quote($workshift) . "][" . dbl_quote($key) . "][" . 
        $indices[$key]++ . "] = " . dbl_quote($modifs[$key]) . ";\r\n";
    }
  }
  if ($no_js) {
    foreach ($cur_mods as $attrib => $arr) {
      $attribs[$attrib] = array_unique(array_merge($attribs[$attrib],array_keys($arr)));
    }
    $attribs['day'] = $days;
  }
  else if ($init_flag) {
    foreach ($whiches as $which) {
      for ($jj = 0; $jj < count($wanted_shifts[$which]); $jj++) {
        if ($wanted_shifts[$which][$jj]['shift'] === $workshift) {
          foreach ($cur_mods as $attrib => $arr) {
            $wanted_attribs[$which][$jj][$attrib] = array_keys($arr);
          }
          $wanted_attribs[$which][$jj]['day'] = array_keys($days_done);
        }
      }
    }
  }
  $ii = 0;
  if (!isset($modif_keys)) {
    $modif_keys = array();
  }
  //from here on out, $modif_keys is just all the possible modifers, day included
  array_push($modif_keys,'day');
  //what are all the possibilities for the modifiers?
  for ($ii = 0; $ii < count($modif_keys); $ii++) {
    print "  modifiers[name_index][$ii] = " . dbl_quote($modif_keys[$ii]) . ";\r\n";
  }
  print "  num_mods = " . count($modif_keys) . ";\r\n";
    }
?>
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
if (!$no_js) { if (!$shift_prefs_style) {?>

<h3>Warning: may not work properly on Macs.  Try using Firefox, then Safari.  
IE may crash on Macs.</h3>
<?php
}
  print("<p><a href='" . $_SERVER['REQUEST_URI'] . "?no_js=true'>Use a version of this page " .
        "that does not require javascript</a></p>");
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
    print "<option selected>" . escape_html($name) . "\n";
  }
  else {
    print "<option>" . escape_html($name) . "\n";
  }
}
?>
</SELECT>
<table>
<tr><td>Room #:</td><td><INPUT type="text" size="3" id="room" name="room" value='<?=escape_html($room)?>'></td><td>
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
<?php if (!$shift_prefs_style) {
print_static_text('preferences_shift_wanted_instructions',
<<<INSTRUCTIONS
List some shifts you want, and be as specific as
possible (i.e. what shift AND what day-this will do more than anything
else to increase your chance of getting the shift you want). Almost
everybody will end up doing a kitchen crew shift (i.e. dishes), so be
sure to include at least one specific kitchen shift.  I encourage you
to apply to work at CO/CK-it tends to be pretty social, and you can
choose your job.  Please let me know if you are applying to be a cook
or to work at CO/CK. See the list of shift descriptions in your
workshift packet for more details.
INSTRUCTIONS
                  ,
                  array(),
                  true,true);
?>
<br>
To select multiple items, or to unselect an item, use the CONTROL key
on your keyboard, or select the blank item.  If you don't specify a
day, it is assumed that you want (or don't want) the shift for any day
of the week.
</p>
<?php
#';
foreach ($whiches as $which) {
  if ($which == 'unwanted') {
    print("List some shifts you would really hate to do.<br>\n");
  }
  for ($ii = 0; $ii < $wanted_num[$which]+$num_blank_shifts; $ii++) {
    if ($ii < $wanted_num[$which]) {
      $row = $wanted_shifts[$which][$ii];
    }
    else {
      $row = null;
    }
  ?>

<p><SELECT id='<?=escape_html($which . $ii)?>' name="<?=
escape_html($which . $ii)?>" <?=
    //we don't want someone whose browser only half-works with javascript
    //to have choices taken away by display_choices
    $no_js?'':'onChange=display_choices(' . array_search($which,$whiches) .",$ii)"
?> >
<option>
<?php
foreach ($workshiftlist as $shift) {
  if ($row && $shift === $row['shift']) {
    print "<option selected>" . escape_html($shift) . "\n";
  }
  else {
    print "<option>" . escape_html($shift) . "\n";
  }
}
?>
</SELECT><?php
    //if we have no javascript, then we display all possible attributes
    //if we have javascript, then we display nothing unless this is a stored
    //preference, in which case we display just the correct cells
if (!$no_js) {
  if (!$row) {
    continue;
  }
  $attribs = $wanted_attribs[$which][$ii];
}
  //go through all possible options
  foreach ($attribs as $attrib => $vals) {
    if ($row && isset($row['attrib'])) {
      $row[$attrib] = explode(';',$row[$attrib]);
    }
    print escape_html($attrib) . ": ";
?><select multiple size=<?=count($vals)?> id="<?=escape_html(
$which . $ii . $attrib)?>" name="<?=escape_html($which . $ii . $attrib)?>[]">
<?php
     //list all the options for this attribute
 foreach ($vals as $val) {
   //select the ones that the user selected last time
   if ($row && isset($row['attrib']) && array_search($val,$row[$attrib]) !== false) {
     print "<option selected>" . escape_html($val) . "\n";
   }
   else {
     print "<option>" . escape_html($val) . "\n";
   }
 }
?>
</select><?php
    }
?></p>
<?php
}
  //it's ok to have the button for people to make new preferences, even in no_js.
  //Maybe they can, so good luck to them.  But those new preferences will have
  //full javascript functionality, and will maybe not work so well if js is bad.
  ?></p><p>
<input type="button" value="add another <?=$which?> shift" 
id="<?=$which?>_button"
onClick="addfields(<?=array_search($which,$whiches)?>)">
</p><?php }
?>
<input type=hidden name='shift_prefs_style' value=0>
<?php
                                  }
    else {
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
<input type=hidden name='shift_prefs_style' value=1>
<?php
$category = null;
$workshifts_done = array();
$no_cat_shifts = array();
$firstflag = 0;
$res = $db->Execute("select * from `master_shifts` where `category` is null or " .
                    "substring(`category`,1,1) != '*'" .
                    "order by `category`, `workshift`");
print "<table>";
while ($row = $res->FetchRow()) {
  if (!$row['category']) {
    $category = null;
    if (!isset($workshifts_done[$row['workshift']])) {
      $no_cat_shifts[] = array($row['autoid'],$row['workshift']);
      $workshifts_done[$row['workshift']] = true;
    }
    continue;
  }
  if ($category != $row['category']) {
    $category = $row['category'];
    if ($firstflag++) {
      print "</div></td></tr>";
    }
    print "<tr><td><input type=hidden name='nm_cat_" . escape_html($row['autoid']) . 
      "' value='" . escape_html($row['category']) . "'>" . 
      "<input size=3 name='cat_" . escape_html($row['autoid']) . "' ";
    if (isset($wanted_shifts['cat_' . $row['category']])) {
      print " value='" . $wanted_shifts['cat_' . $row['category']]['rating'] . "'";
    }
    print ">" . ucfirst(escape_html($row['category'])) . " <input type=submit value='Expand' ";
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
  print "<input type=hidden name='nm_sft_" . escape_html($row['autoid']) . 
      "' value='" . escape_html($row['workshift']) . "'>" . 
    "<input size=3 name='sft_{$row['autoid']}' ";
  if (isset($wanted_shifts['sft_' . $row['workshift']])) {
    print " value='" . $wanted_shifts['sft_' . $row['workshift']]['rating'] . "'";
  }
  print ">" . escape_html($row['workshift']) . "<br>";
}
if ($firstflag) {
  print "</div></td></tr>";
}
print "</table>Uncategorized shifts:<br>";
foreach ($no_cat_shifts as $shift_data) {
  print "<input type=hidden name='nm_sft_" . escape_html($shift_data[0]) . 
    "' value='" . escape_html($shift_data[1]) . "'>" . 
    "<input size=3 name='sft_{$shift_data[0]}' ";
  if (isset($wanted_shifts['sft_' . $shift_data[1]])) {
    print " value='" . $wanted_shifts['sft_' . $shift_data[1]]['rating'] . "'";
  }
  print ">" . escape_html($shift_data[1]) . "<br>";
}
    }
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
