<?php
#$db->debug = true;
if (!array_key_exists('submitting_bool',$_REQUEST)) {
?>
<html><head><title>Online Signoff Setup</title>
</head>
<body>
<?php
require_once('default.inc.php');
$online_signoff = get_static('online_signoff',null);
$email_signoff = $online_signoff && get_static('online_signoff_email',null);
?>
<script type='text/javascript'
src='<?=escape_html($html_includes) . '/table_edit.utils.js'?>'></script>
<form action=<?=this_url()?> method=post onsubmit='return validate_form()'>
<input type=hidden name='submitting_bool' value='1'>
Enable online signoffs
<input type=checkbox name='online_signoff_bool' value=1
<?=$online_signoff?'checked ':''?>
onchange='change_online(this.checked,"online")'
><br/>
<div id='props'>
   What is the maximum number of hours after a shift ends that someone is
allowed to sign off for it?  (If a shift has no end time entered, it is
assumed to end at midnight of that day.)
<input class='online' name='max_signoff_time' 
value='<?=get_static('max_signoff_time',48)?>' ><br/>
The server thinks it is <?=user_time()?> right now.  If this is incorrect,
<a href='tz_set.php'>set the time zone.</a>
<br/>
Is the verifier's password required to sign off?  Highly recommended
to prevent fraud.
<!-- emacs ' -->
<input class=online type=checkbox 
<?=$online_signoff?'':'disabled '?> 
 name='online_signoff_verifier_password_bool'
<?=get_static('online_signoff_verifier_password_bool',true)?'checked':''?>
><br/>
Should verifiers be emailed to confirm that they did indeed verify a shift?
If so, put a number in this box.  The chances are 1 in (this number) that
the verifier will be emailed.  If you put 2, around half the verifiers will be
emailed.  If you put 1, all of them will be.  If you put 10, 1/10 will be.
If the verifier doesn't have an email on file, or the email can't be sent
successfully, you (workshift manager) will get an email telling you this.
<input class=online name='online_signoff_email' id='online_signoff_email' 
value='<?=get_static('online_signoff_email',null)?>'
<?=$online_signoff?'':'disabled '?>
 onchange='change_online(this.value,"email")'>
<div id='email_props'>
Workshift manager email: 
<input class="online email" size=25 name='workshift_manager_email'
<?=$email_signoff?'':'disabled '?>
value='<?=get_static('workshift_manager_email',$house_name . '1@usca.coop')?>'>
<br/>
Subject line of email to verifier:
<input class='online email' name='online_email_subject_text'
size=100
<?=$email_signoff?'':'disabled '?> 
 value='<?=escape_html(get_raw_static_text('online_email_subject',
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
                              true))?>'
><br/>
(You can use the following escape codes to insert data into this string:<br/>
<?php
 $esc = get_escapes_text('online_email_subject');
 foreach ($esc as $code => $datum) {
   print escape_html($code) . " will insert the " . escape_html($datum) . "<br/>";
 }
?>
 )<p>
Body of email to verifier:
<textarea class='online email' name='online_email_body_text' cols=80
<?=$email_signoff?'':'disabled '?>>
<?=escape_html(get_raw_static_text('online_email_body',
                             'This is a confirmation that you verified that ' .
                             '%member_name did their workshift %workshift for ' .
                             '%hours hours on %date.  If this is not correct in ' .
                             'any way, please email the workshift manager.  Thanks.',
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
                             true))?>
</textarea><br/>
(You can use the following escape codes to insert data into this text:<br/>
<?php
 $esc = get_escapes_text('online_email_body');
 foreach ($esc as $code => $datum) {
   print escape_html($code) . " will insert the " . escape_html($datum) . "<br/>";
 }
 ?>
 )<p>
 
</div>
</div>
<input type=submit value='Submit!'>
<script type='text/javascript'>
var attr_bools = new Array();
attr_bools['online'] = <?=$online_signoff?1:0?>;
attr_bools['email'] = <?=$email_signoff?1:0?>;
function change_online(flag,which_class) {
  var elt_types = new Array('input','textarea');
  attr_bools[which_class] = !attr_bools[which_class];
  for (var tp in elt_types) {
    elts = document.getElementsByTagName(elt_types[tp]);
    for (var ii in elts) {
      if (elts[ii].className) {
        var classes = elts[ii].className.split(" ");
        var enable_flag = true;
        for (var jj in classes) {
          if (typeof(attr_bools[classes[jj]] != 'undefined')) {
            if (!attr_bools[classes[jj]]) {
              elts[ii].disabled = 'true';
              enable_flag = false;
              break;
            }
          }
        }
        if (enable_flag) {
          elts[ii].disabled = '';
        }
      }
    }
  }
}
function validate_form() {
  var val = document.getElementById('online_signoff_email');
  if (val.value && !is_whole_number(val.value)) {
    alert("Email frequency must be blank or a whole number");
    val.style.borderColor = 'red';
    val.focus();
    return false;
  }
  return true;
}

</script>

<?php
    exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html><head><title>Setting Online Signoffs</title></head><body>
<?php
require_once('default.inc.php');
foreach (array('online_signoff_bool','online_signoff_verifier_password_bool') 
         as $key) {
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
  print("<br>\n");
}
foreach ($_REQUEST as $key => $val) {
  if ($val === '') {
    $_REQUEST[$key] = $val = null;
  }
  if (substr($key,-10) == 'session_id') {
    continue;
  }
  if (substr($key,-5) == '_bool') {
    continue;
  }
  if (substr($key,-5) == '_text') {
    set_static_text($key,$val);
  }
  else {
    set_static($key,$val);
  }
  print("Set " . escape_html($key) . 
        " to <div style='" . white_space_css() . 
        "'>" . escape_html($val,true) . "</div>\n");
}
?>
