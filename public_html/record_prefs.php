<?php
//all the attributes that a shift can have -- not gotten through database
//because that might fail if the database is down and this page has to work
$attribs = array('day');
//this days is a duplicate of janakdb-utils.php, but we can't include it
//because it might possibly fail, and this page HAS to work
$days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
//strips slashes from form input, assuming php is quoting things
if (!function_exists('stripformslash')) {
  if (!ini_get('magic_quotes_sybase')) {
    if (get_magic_quotes_gpc()) {
      function stripformslash($str) {
        global $first_strip;
        if (!$first_strip) {
          return $str;
        }
        if (is_array($str)) {
          return array_map('stripformslash',$str);
        }
        return stripslashes($str);
      }
    }
    else {
      function stripformslash($str) {
        return $str;
      }
    }
  }
  else {
    //quoting for database?  Man, you're stupid -- databases quote things already
    function stripformslash($str) {
      global $first_strip;
      if (!$first_strip) {
        return $str;
      }
      if (is_array($str)) {
        return array_map('stripformslash',$str);
      }
      return str_replace("''","'",$str);
    }
  }
}
$first_strip = true;
$_REQUEST = stripformslash($_REQUEST);
$first_strip = false;
?>
<html><head><title>Preferences Submission</title>
<style>
table {
  empty-cells: show;
}
</style>
</head>
<body>
<h3>Wait for this page to complete loading.  Unless the bottom of the
page says your preferences have been submitted, your preferences have
<strong>not</strong> been submitted properly.  If a problem occurs,
keep this page open and print it if possible.  You can also save it to
your hard drive using your web browser.  Then get the workshift or ethernet manager.</h3>

Your preferences were:<p>
<?php 
//give an absolute url, so that users can save the page, and repost directly
//from their computers
?>
<form method=post action="http<?=array_key_exists('HTTPS',$_SERVER) && $_SERVER['HTTPS'] == 'on'?'s':''?>://<?=$_SERVER['HTTP_HOST'] . ':' . 
                                    $_SERVER['SERVER_PORT'] . 
                                    $_SERVER['REQUEST_URI']?>">

<?php
error_reporting(E_ALL & ~E_NOTICE);
$member_name = $_REQUEST['member_name'];
$room = $_REQUEST['room'];
$email = $_REQUEST['email'];
$phone = $_REQUEST['phone'];
$passwd = $_REQUEST['passwd'];

//availability is stored in bit form -- each bit of an integer gives an hour's
//availability, with 1 meaning busy, and there are 7 integers, one for each day
$flags = array();
for ($ii = 0; $ii<7; $ii++) {
  $tmp = array();
  for ($jj=0; $jj<16; $jj++) {
    $tmp[$jj] = $_REQUEST["av_${ii}_${jj}"];
  }
  $flags[$ii] = join('',$tmp);
}
$notes = $_REQUEST['notes'];
$wanted = array();
$wanted_attribs = array();

//now we output what we got.  This lets the user resubmit if there's an error
//or a bad password, or at least print the page out and save it.
function esc_h($str) {
  return htmlentities($str,ENT_QUOTES);
}
?>

Name: <input type=hidden value='<?=esc_h($member_name)?>' name='member_name'>
<?=esc_h($member_name)?><br>
Room: <input type=text value='<?=esc_h($room)?>' name='room'>
Show room in directory: <input type=checkbox name='privacy_room' 
<?=array_key_exists('privacy_room',$_REQUEST)?' checked':''?>><br>
Phone: <input type=text value='<?=esc_h($phone)?>' name='phone'>
Show phone in directory: <input type=checkbox name='privacy_phone'
<?=array_key_exists('privacy_phone',$_REQUEST)?' checked':''?>><br>
Email: <input type=text value='<?=esc_h($email)?>' name='email'>
Show email in directory: <input type=checkbox name='privacy_email'
<?=array_key_exists('privacy_email',$_REQUEST)?' checked':''?>><br>
Password (hidden): <input type=password value='<?=esc_h($passwd)?>' name='passwd'><br>
<?php 
  $categories = array();
  print "<table>";
  foreach ($_REQUEST as $key => $val) {
    if (!strlen($val)) {
      continue;
    }
    if (substr($key,0,4) == 'cat_' || substr($key,0,4) == 'sft_') {
      print "<tr><td>";
      if (substr($key,0,4) == 'sft_') {
        $shift_name = $_REQUEST['nm_' . $key];
        print ucfirst(esc_h($shift_name)); 
          print "<input type=hidden name='nm_" . esc_h($key) . "'" .
            " value='" . esc_h($shift_name) . 
            "'>";
      }
      else {
        print  ucfirst(esc_h(substr($key,4)));
      }
      print "</td><td><input name='" . esc_h($key) . "' value='" .
        esc_h($val) . "'></td></tr>";
      $categories[$key] = $val;
    }
  }
  print "</table>";
?>
<p>
<p>Notes:</p>
<textarea rows=10 columns=40 name='notes'><?=esc_h($notes)?></textarea><p>
<table border=1>
<tr><td></td>
<?php
//unpack the days' availabilities that we had packed up
$hours = array("8am", "9am", "10am", "11am", "12pm");
foreach ($hours as $hour) {
  print "<td>$hour</td>";
}
for ($ii = 1; $ii<12; $ii++) {
  print "<td>${ii}pm</td>";
}
print "</tr>\n";
$weekday = 0;
$av_options = array('+','&nbsp;','-','x','?');
foreach ($days as $day) {
  print "<tr>";
  print "<td>$day</td>";
  for ($jj=0; $jj<16; $jj++) {
    print "<td><input type=hidden name='av_{$weekday}_{$jj}' " .
      "value='" . substr($flags[$weekday],$jj,1) . "'>" . 
      $av_options[substr($flags[$weekday],$jj,1)];
    print"</td>";
  }
  print "</tr>\n";
  $weekday++;
}
?>
</table>

<p>
<input type="submit" value="Resubmit">
</p>
</form>

<?php
//now for the actual submitting

//utility function -- joins together all the days someone can do a shift,
//or floors, or whatever.  Is abstracted in case we ever do something
//fancy with these joined-up elements, and also so the testing of null
//arrays is hidden here
function mk_array($arr,$ind,$attr) {
  if (!isset($arr[$ind]) || !isset($arr[$ind][$attr]))
    return null;
  return implode(';',$arr[$ind][$attr]);
}

//function called on failure, to output a uniform error message
function fail($string) {
  global $db;
  janak_error("Could not " . esc_h($string) . 
              ".  Save this page, print if possible, and contact " .
              "the workshift or ethernet manager.",$db,E_USER_ERROR);
}
//this file will create the database object and initialize it.  No functions
//to call -- it just does it.  This is probably not the best way to do it.
$require_user = false;
$body_insert = ' ';
require_once('default.inc.php');

//uncomment to view the sql statements being sent back and forth
#$db->debug = true;

function update_member_info($main_arr, $workshift_arr, $member_name,$passwd) {
  global $db, $main_db;
  $shift_arr = array();
  $mn_arr = array();
  //to use the ADODB replace function, we need an associative array
  $mn_arr['member_name'] = $shift_arr['member_name'] = $member_name;
  $shift_arr['notes'] = $workshift_arr[0];
  for ($ii = 0; $ii<7;$ii++) {
    $shift_arr["av_$ii"] = $workshift_arr[1+$ii];
  }
  if ($main_arr[0]) {
    $mn_arr['room'] = $main_arr[0];
  }
  if ($main_arr[1]) {
    $mn_arr['email'] = $main_arr[1];
    $email_row = $db->GetRow("select `email` from `house_info` where " .
                              "`member_name` = ?",
                              array($mn_arr['member_name']));
    if (is_empty($email_row)) {
      $email_row['email'] = null;
    }
    if ($email_row['email'] != $mn_arr['email']) {
      elections_log(null,'member email change',$mn_arr['member_name'],
                    $email_row['email'],
                    $mn_arr['email']);
    }
  }
  if ($main_arr[2]) {
    $mn_arr['phone'] = $main_arr[2];
  }
  if (count($mn_arr) > 1) {
    if (!$db->Replace(bracket('house_info'),
                      $mn_arr,'member_name',true)) {
      return null;
    }
  }
  $db->Execute("update `personal_info` set `submit_date` = now() " .
               "where `member_name` = ?",
               array($member_name));
  $ret = $db->Replace(bracket('personal_info'),$shift_arr,'member_name',true);
  set_mod_date('personal_info');
  return $ret;
}

function delete_old_prefs($member_name, $passwd) {
  global $db;
  //still pretty simple
  $ret = $db->Execute("DELETE FROM " . bracket('wanted_shifts') . 
                      " WHERE " . bracket('member_name') . " = ?",
                      array($member_name));
  set_mod_date('wanted_shifts');
  return $ret;
}

//here's what goes to the main house info
$house_info_array = array($room,$email,$phone);
//and here's workshift-specific
$workshift_info_array = array_merge(array($notes),$flags);

//if we're using mysqlt, this will prevent incomplete submission of preferences
//I don't know if that's really such a bad thing, but whatever
$db->StartTrans();
//how's the password?
$ch = check_passwd();

//check_password returns null if it couldn't get any response at all --
//indicates a problem in the database setup
if (is_null($ch)) {
  fail('check password'); 
}
switch($ch) {
 case -3: case -2:    exit("<h4>Could not verify that this person: " . escape_html($member_name) . 
                           "<br/>is a house member.</h4>");
;
 case 0:  
   exit("<h4>Your password did not match the stored password.  If you " .
        "already have a password set from last semester, please use it -- just " .
        "put it in the password box above and resubmit.</h4>");
   //fail('match the password given with the stored password.  If you ' .
   //	       'mistyped it, you can retype it above and resubmit.  Otherwise:');
 case -1:
 case -4:
   if (!set_passwd($member_name,$passwd,'')) {
     fail('set new password');
   }
   elections_log(null,'member change','password set',null,$member_name);
   break;
 default:
}
if (!update_member_info($house_info_array,$workshift_info_array,
			$member_name,$passwd)) {
  fail('update_member_info');
}
//set privacy
$ii = 1;
$val = 0;
foreach (array('room','phone','email') as $attrib) {
  if (array_key_exists('privacy_' . $attrib,$_REQUEST)) {
    $val |= $ii;
  }
  $ii <<=  1;
}
$db->Execute("update `house_info` set `privacy` = ? where `member_name` = ?",
             array($val,$member_name));
set_mod_date('house_info');

if (!delete_old_prefs($member_name,$passwd)) {
  fail('delete old preferences');
}

foreach ($categories as $cat => $rating) {
  $db->Execute("insert into `wanted_shifts` " .
               "(`member_name`,`shift_id`,`rating`,`is_cat`) " .
               "values (?,?,?,?)",
               array($member_name,substr($cat,4),$rating,$cat{0} == 'c'));
}
if (count($categories)) {
  set_mod_date('wanted_shifts');
}

if ($db->CompleteTrans()) { 
  //let user look at the preferences through a little form hidden here, so the
  //password is sent automatically
?>
 Your preferences have been submitted.
   <form method=post action='preferences.php'>
   <input type=hidden name='member_name' value='<?=esc_h($member_name)?>'>
   <input type=hidden name='passwd' value='<?=esc_h($passwd)?>'>
   <input type=submit value='View or modify your preferences'>
   <?php
   }
else {
  fail('complete updating of preferences');
}

function mymod($num,$base) {
  $loop = 0;
  while ($num > $base) {
    if ($loop++ > 20) {
      exit;
    }
    print "$num $newbase<br>";
    $newbase = $base;
    while ($num > $newbase) {
    if ($loop++ > 20) {
      exit;
    }
    print "$num $newbase<br>";
      $num-=$newbase;
      $newbase *= max(2,$newbase);
    }
  }
  return $num;
}


?>
