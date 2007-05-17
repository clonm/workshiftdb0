<html><head><title>Change Password</title></head><body>
<?php
require_once('default.inc.php');
//change the password of a user automatically, to avoid bugging the admin.
//the user whose password is to be changed is determined by server variables, so
//people can't go changing anybody's password.

$username = get_username();
if (!array_key_exists('newpassword',$_REQUEST)) {
  ?>
    
This page will change the password for the user below.  You cannot
create new users in this way, only change the password of existing
users.  Creating users must be done by an administrator.<p>

<form action="<?=this_url()?>" method=POST>
User: <?=escape_html($username)?><br>
Old password: <input type=text value='' name='oldpassword'><br>
New password: <input type=text value='' name='newpassword'><br>
<input type=submit value="Change password">
</form>
</body>
</html>

<?php 
exit;
}

$newpassword = $_REQUEST['newpassword'];

//apache user info is stored in include path -- or it could be stored centrally
//if you have control of your apache
chdir($php_includes);
//start constructing htpasswd command
//htpasswd must be specified in your static_data and be fully qualified 
//or be /usr/bin/htpasswd or _:/etcetera
if (!($htpass = get_static('apache_htpasswd_path')) || 
    ($htpass{0} != '/' && $htpass{1} != ':')) {
  $htpass = "/usr/bin/htpasswd";
  set_static('apache_htpasswd_path',$htpass);
}
//if file doesn't exist, tell htpasswd to create it 
if (!file_exists('apache_users')) {
  $htpass .= " -c";
}
else {
  //if file exists, check to make sure password is right
  $lines = file('apache_users');
  $oldpassword = $_REQUEST['oldpassword'];
  foreach ($lines as $line) {
    $temp = split(':',$line);
    $temp[1] = chop($temp[1]);
    if ($temp[0] === $username) {
      if ((strlen($oldpassword) || 
          strlen($temp[1])) && 
          crypt($oldpassword,$temp[1]) != $temp[1]) {
        if ($real_username == 'workshiftadmin') {
          print "Old password is incorrect<br/>\n";
        }
        else {
          exit("Old password is incorrect");
        }
      }
      break;
    }
  }
}
$htpass .= " -b \"apache_users\" " .  escapeshellarg($username) . " " . 
escapeshellarg($newpassword);
echo "<pre>";
system("$htpass 2>&1");
echo "</pre>";
?>
