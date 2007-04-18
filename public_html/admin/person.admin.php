<?php
require_once('default.inc.php');
$houselist = get_houselist();
if (array_key_exists('member_name_view',$_REQUEST)) {
  $member_name = $_REQUEST['member_name_view'];
}
else {
?>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=POST>
   Name: <select name='member_name_view'>
<OPTION>
<?php
foreach ($houselist as $name) {
  print "<OPTION>" . escape_html($name) . "\n";
}
?>
</SELECT>
<input type=submit value='Submit'>
</form>
<?php
    exit ;
}
require_once('../person.php');
?>
