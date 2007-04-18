<?php
require_once('default.inc.php');

$grants = user_grant_privileges($member_name);
foreach (array('president','house','workshift') as $managers) {
  if (!count(users_with_privileges($managers))) {
    $grants[] = $managers;
  }
}
if (!count($grants)) {
  exit;
}
if (count($grants) == 1) {
  $_REQUEST['privilege_type'] = $grants[0];
}
if (!array_key_exists('privilege_type',$_REQUEST)) {
  ?>
 <html><head><title>Choose Attribute</title></head>
<body>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=post>
<select name='privilege_type'>
<?php
    foreach ($grants as $grant) {
      print "<option>" . escape_html($grant) . "\n";
    }
?>
</select>
<input type=submit value='View/Edit Member Attribute'>
</form>
</body></html>
<?php
    exit;
}
$privilege = $_REQUEST['privilege_type'];
if (!in_array($privilege,$grants)) {
  exit("You cannot change that privilege");
}
if (array_key_exists('privilege_user',$_REQUEST)) {
  add_authorized_user($_REQUEST['privilege_user'],$privilege);
  unset($_REQUEST['privilege_user']);
}
?>
<html><head><title>Choose Members to Add/Remove Attribute
<?=escape_html($privilege)?> From</title></head><body>
<p><a href='<?=escape_html($_SERVER['REQUEST_URI'])?>'>Back to beginning</a></p>
<h4><?=escape_html($privilege)?></h4>
<?php
  $users = users_with_privileges($privilege);
  foreach ($users as $user) {
?>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=post>
<input type=hidden name='privilege_user' value='<?=escape_html($user)?>'>
<input type=hidden name='privilege_type' value='<?=escape_html($privilege)?>'>
<input type=submit value='Remove <?=escape_html($privilege) . " from " . escape_html($user)?>'></form>
<?php
   }
$houselist = array_diff(get_houselist(),$users);
?>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=post>
<input type=hidden name='privilege_type' value='<?=escape_html($privilege)?>'>
<select name='privilege_user'>
<?php
foreach ($houselist as $member) {
  print "<option>" . escape_html($member) . "\n";
}
?>
</select>
<input type=submit value='Add attribute'></form>
</form></body></html>

