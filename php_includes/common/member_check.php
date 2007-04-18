<?php
require_once('default.inc.php');
foreach ($_COOKIE as $key => $val) {
  setcookie($key,stripformslash($val),time()-10800,"/");
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
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=POST>
   Name: <select name='member_name'>
<OPTION>
<?php
foreach ($houselist as $name) {
  if ($name !== $member_name) {
    print "<OPTION>" . escape_html($name) . "\n";
  }
  else {
    print "<OPTION selected>" . escape_html($name) . "\n";
  }
}
?>
</SELECT>
    <?php 
    if (!$secured) {
  ?>
    Password: <input type=password name='passwd'>
                 <?php 
                 } 
    else { 
?>
<input type=hidden name='passwd' value=''>
<?php
   }
foreach ($_REQUEST as $key => $val) {
  if (!is_array($val)) {
    print "<input type=hidden name='" . escape_html($key) . "' " .
      "value='" . escape_html($val) ."' />\n";
  }
  else {
    foreach ($val as $value) {
      print "<input type=hidden name='" . escape_html($key . '[]') . "' " .
        "value='" . escape_html($val) . "' />\n";
    }
  }
}
?>
<input type=submit value='Submit'>
</form>
(You must have cookies enabled to log in)
