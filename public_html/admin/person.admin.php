<?php
$body_insert = '';
require_once('default.inc.php');
$houselist = get_houselist();
if (array_key_exists('member_name_view',$_REQUEST)) {
  $member_name = $_REQUEST['member_name_view'];
  if (strlen($member_name)) {
    require_once("../person.php");
    exit;
  }
}
?>
<html><head><title>View Member Info</title></head><body>
<?=$body_insert?>
<form action='<?=this_url()?>' method=GET>
   Name: <select name='member_name_view'>
<?php
foreach ($houselist as $name) {
  print "<OPTION>" . escape_html($name) . "\n";
}
?>
</SELECT>
<input type=submit value='Submit'>
</form></body></html>
