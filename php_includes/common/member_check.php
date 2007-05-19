<?php
//generic login page.  Include()d by require_user().  stores all
//values in submitted form in hidden inputs, has option to not display
//password if we are in administrative context, will focus on first
//field that "should" receive input.
require_once('default.inc.php');
//get rid of all cookies
foreach ($_COOKIE as $key => $val) {
  setcookie($key,$val,time()-10800,"/");
  setcookie($key,"",0,"/");
  unset($_REQUEST[$key]);
}
$houselist = get_houselist();
if (array_key_exists('member_name',$_REQUEST)) {
  $member_name = $_REQUEST['member_name'];
}
else {
  $member_name = null;
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
                 } 
    else { 
      //no passwd id here, because never want to select it
?>
<input type=hidden name='passwd' value=''>
<?php
   }
//put all remaining form variables in
foreach ($_REQUEST as $key => $val) {
  //except for the ones displayed above
  if ($key == 'member_name' || $key == 'passwd') {
    continue;
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
