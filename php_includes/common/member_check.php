<?php
//generic login page.  Include()d by require_user().  stores all
//values in submitted form in hidden inputs, has option to not display
//password if we are in administrative context, will focus on first
//field that "should" receive input.
require_once('default.inc.php');
if (isset($body_insert)) {
  print $body_insert;
  $body_insert = null;
}
if (!isset($officer_flag)) {
  $officer_flag = false;
}
//get rid of all cookies
// foreach ($_COOKIE as $key => $val) {
//   if ((!$officer_flag && $key == 'session_id') ||
//       ($officer_flag && $key == 'officer_session_id')) {
//     setcookie($key,$val,time()-10800,"/");
//     setcookie($key,"",0,"/");
//     unset($_REQUEST[$key]);
//     break;
//   }
// }
$houselist = get_houselist();
if (array_key_exists('member_name',$_REQUEST)) {
  $member_name = $_REQUEST['member_name'];
}
else {
  $member_name = null;
}
if (!$member_name && 
    array_key_exists('default_member_name',$_REQUEST)) {
  $member_name = $_REQUEST['default_member_name'];
}
?>

<form action='<?=this_url()?>' method=POST>
   Name: <select name='member_name' id='member_name'>
<OPTION>
<?php
foreach ($houselist as $name) {
  if ($name !== $member_name) {
    //remember, need to have value= in option to avoid problems with
    //bad names
    print "<OPTION value='" . escape_html($name) . 
    "'>" . escape_html($name) . "\n";
  }
  else {
    print "<OPTION selected value='" . escape_html($name) . 
    "'>" . escape_html($name) . "\n";
  }
}
?>
</SELECT>
    <?php 
//did page that include()d tell us not to display password?
    if (!isset($skip_password) || !$skip_password) {
  ?>
    Password: <input type=password name='passwd' id='passwd'>
                 <?php 
                 if (needs_officer($require_user)) {
?>
<hr>
<p>
You are trying to enter a restricted area.  If you are already an authorized user,
enter your member name and password above.  Otherwise, enter your managerial
user name and password here to log in.</p>
Officer name: <input name='officer_name'>&nbsp;&nbsp; Officer password:
<input name='officer_passwd' type='password'><hr/>
<?php
     }
    } 
    else { 
      //no passwd id here, because never want to select it
?>
<input type=hidden name='passwd' value=''>
<?php
   }
//put all remaining form variables in
foreach (array_diff($_REQUEST,array_merge($_GET,$_COOKIE)) as $key => $val) {
  //except for the ones displayed above
  switch ($key) {
  case 'member_name': case 'passwd': case 'officer_name': case 'officer_passwd': 
  case 'forget_login':
    continue 2;
  }
  if (!is_array($val)) {
    print "<input type=hidden name='" . escape_html($key) . "' " .
      "value='" . escape_html($val) ."' />\n";
  }
  else {
    foreach ($val as $value) {
      print "<input type=hidden name='" . escape_html($key . '[]') . "' " .
        "value='" . escape_html($value) . "' />\n";
    }
  }
}
?>
<input type=submit value='Submit'>
</form>
(You must have cookies enabled to log in)
<script type='text/javascript'>
//focus first field that should be focused
if (document.getElementById('member_name').value &&
    document.getElementById('passwd')) {
      document.getElementById('passwd').focus();
    }
else {
  document.getElementById('member_name').focus();
}
</script>
