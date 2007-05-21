<html><head><title>Set password</title></head>
    <body>
<?php 
//change password for user -- must know old password
$body_insert = '';
//don't exit if the user doesn't have a password set.  Unfortunately,
//this flag also means that a non-user is allowed in, but we'll check
//that right away.
if (isset($require_user)) {
  $old_require_user = $require_user;
  if (!is_array($old_require_user)) {
    $old_require_user = array($old_require_user);
  }
}
else {
  $old_require_user = null;
}
$require_user = false;
require_once("$php_includes/janakdb.inc.php");
if (!needs_officer($old_require_user)) {
  $officer_flag = false;
  $person_var = 'member_name';
}
else {
  $officer_flag = true;
  $person_var = 'officer_name';
}

$person = get_session_member($officer_flag);

if (!$person) {
  if (!isset($GLOBALS[$person_var])) {
    if (isset($_REQUEST[$person_var])) {
      $GLOBALS[$person_var] = $_REQUEST[$person_var];
    }
    else {
      require_once("$php_includes/common/member_check.php");
      exit;
    }
  }

  if ($officer_flag) {
    $pass_check = check_officer_passwd($officer_name);
  }
  else {
    $pass_check = check_passwd($member_name);
  }
}
else {
  $GLOBALS[$person_var] = $person;
}

//did we fail to authenticate (but not have no password)?
if (!$person) {
  if ($pass_check <=0 && $pass_check != -1) {
    $require_user = $old_require_user;
    require_once("$php_includes/common/member_check.php");
    exit;
  }
  //set up session
  if ($pass_check > 0) {
    set_session($GLOBALS[$person_var],$officer_flag);
  }
  else {
    $no_passwd_flag = true;
  }
}
if (!isset($_REQUEST['newpasswd'])) {
  if (!isset($person)) {
    $person = $GLOBALS[$person_var];
  }
  ?>
<?=$body_insert?>
    <form action="<?=escape_html($baseurl) . 
($secured?'/admin':'')?>/set_passwd.php" method=POST>
     <input type=hidden name='<?=$person_var?>' value='<?=escape_html($person)?>'>
    Name: <?=escape_html($person)?>&nbsp;&nbsp;
<?php
  if (!isset($no_passwd_flag)) {
?>
  Old password: <input type='text' name='<?=$officer_flag?"officer_":""?>passwd'>
<?php
    }
  else {
?>
<input type='hidden' name='<?=$officer_flag?"officer_":""?>passwd'>
<?php
   }
?>
	New password: <input type=text name='newpasswd'>
	<input type=submit value='Submit'>
          <input type=hidden name='officer_flag' value='<?=$officer_flag?>'>
<?php
          if (!isset($_REQUEST['previous_url']) && 
              isset($_SERVER['HTTP_REFERER'])) {
                $_REQUEST['previous_url'] = $_SERVER['HTTP_REFERER'];
              }
          if (isset($_REQUEST['previous_url'])) {
?>
          <input type=hidden name='previous_url' 
value='<?=escape_html($_REQUEST["previous_url"])?>'>
<?php
          }
?>
	</form>
	</body>
	</html>
<?php 
        exit; 
}
if (!set_passwd($GLOBALS[$person_var],
                $_REQUEST['newpasswd'],
                $_REQUEST[($officer_flag?'officer_':'') . 'passwd'],
                $officer_flag)) {
  exit("<p>Couldn't change password.</p>");
}
$_REQUEST[($officer_flag?'officer_':'') . 'passwd'] = $_REQUEST['newpasswd'];
$require_user = $old_require_user;
print $body_insert;
require_user();
print("Success changing password");
elections_log(null,'member change','password change',null,
              $GLOBALS[$person_var]);
if (isset($_REQUEST['previous_url'])) {
  print ("<p>Go back to <a href='" . escape_html($_REQUEST['previous_url']) . "'>" .
         escape_html($_REQUEST['previous_url']) . "</a></p>");
}
?>
