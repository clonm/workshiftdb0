<?php
$body_insert = '';
require_once('default.inc.php');
$houselist = get_houselist();
if (array_key_exists('member_name_view',$_REQUEST)) {
  $member_name = $_REQUEST['member_name_view'];
  if (strlen($member_name)) {
    if (!in_array($member_name,$houselist)) {
      $body_insert .= "<h4>Member " . escape_html($member_name) .
        " is not a current house member.</h4>\n";
    }
    else {
      require_once("../person.php");
      exit;
    }
  }
}
?>
<html><head><title>View Member Info</title></head><body>
<?=$body_insert?>

<form action='<?=this_url()?>' method=GET>
  <?=print_gets_for_form() ?>
   Name: <select name='member_name_view'>
<?php
foreach ($houselist as $name) {
  print "<OPTION value='" . escape_html($name) . "'>" . 
  escape_html($name) . "\n";
}
?>
</SELECT>
<input type=submit value='Submit'>
</form></body></html>
