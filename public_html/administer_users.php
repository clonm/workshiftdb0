<?php
//add and remove privileges from users.  There's kind of a bug in that
//if no one has a position, then anyone can sign up anyone else for
//it.  That's probably ok though -- limited damage that can be done,
//usually.  And hopefully there won't be no one in a position too
//often.
$body_insert = '';
$require_user = 'ok_nouser';
$officer_flag = true;
require_once('default.inc.php');

if (!isset($officer_name) && !isset($member_name)) {
  require_once("$php_includes/common/member_check.php");
  exit;
}
if ($member_name) {
  //what privileges does this user has?
  $grants = user_grant_privileges($member_name);
}
else {
  $grants = array();
}
if ($officer_name) {
  $grants[] = substr($officer_name,strlen($house_name));
}
//don't want this anymore, because we've added superusers
#//if no one has this privilege, everyone can grant it
#foreach (array('president','house','workshift') as $managers) {
#  if (!count(users_with_privileges($managers))) {
#    $grants[] = $managers;
#  }
#}
//no privileges to grant?
if (!count($grants)) {
  exit($body_insert . "There are no privileges that you can set");
}
//if there is one privilege, go directly to that page
if (count($grants) == 1) {
  $_REQUEST['privilege_type'] = $grants[0];
}
//did the user (or us right above) choose a privilege?
if (!array_key_exists('privilege_type',$_REQUEST)) {
  ?>
 <html><head><title>Choose Attribute</title></head>
<body>
<?=$body_insert?>
<form action='<?=this_url()?>' method=post>
<select name='privilege_type'>
<?php
    foreach ($grants as $grant) {
      print "<option value='" . escape_html($grant) . 
      "'>" . escape_html($grant) . "\n";
    }
?>
</select>
<input type=submit value='View/Edit Member Attribute'>
</form>
</body></html>
<?php
    exit;
}
//ok, here's our privilege.
$privilege = $_REQUEST['privilege_type'];
//oh, sneaky users
if (!in_array($privilege,$grants)) {
  exit("You cannot change that privilege");
}
//are we adding someone (or removing someone)?
if (array_key_exists('privilege_user',$_REQUEST)) {
  add_authorized_user($_REQUEST['privilege_user'],$privilege);
  unset($_REQUEST['privilege_user']);
}
$_GET = array();
?>
<html><head><title>Choose Members to Add/Remove Attribute
<?=escape_html($privilege)?> From</title></head><body>
<?=$body_insert?>
<p><a href='<?=this_url()?>'>Back to beginning</a></p>
<h4><?=escape_html($privilege)?></h4>
<?php
//print out a form for each user with this privilege, to remove it
  $users = users_with_privileges($privilege);
  foreach ($users as $user) {
?>
<form action='<?=this_url()?>' method=post>
<input type=hidden name='privilege_user' value='<?=escape_html($user)?>'>
<input type=hidden name='privilege_type' value='<?=escape_html($privilege)?>'>
<input type=submit value='Remove <?=escape_html($privilege) . 
" from " . escape_html($user)?>'></form>
<?php
   }
//we can add the privilege to anyone else.
$houselist = array_diff(get_houselist(),$users);
?>
<form action='<?=this_url()?>' method=post>
<input type=hidden name='privilege_type' value='<?=escape_html($privilege)?>'>
<select name='privilege_user'>
<?php
foreach ($houselist as $member) {
  print "<option value='" . escape_html($member) . 
  "'>" . escape_html($member) . "\n";
}
?>
</select>
<input type=submit value='Add attribute'></form>
</form></body></html>

