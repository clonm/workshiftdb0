<?php
//page included when a login is needed, so require_once not hit that often
require_once('default.inc.php');
$houselist = get_houselist();
if (array_key_exists('member_name',$_REQUEST)) {
  $member_name = $_REQUEST['member_name'];
}
else {
  $member_name = null;
}
?>
<form action='<?=this_url()?>' method=POST>
   Name: <select name='member_name'>
<OPTION>
<?php
foreach ($houselist as $name) {
  if ($name !== $member_name) {
    print "<OPTION value='" . escape_html($name) . "'>" .
    escape_html($name) . "\n";
  }
  else {
    print "<OPTION selected value='" . escape_html($name) . "'>" . 
    escape_html($name) . "\n";
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
?>
<input type=submit value='Submit'>
</form>

