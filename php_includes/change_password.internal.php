<?php 
//change the password of a user automatically, to avoid bugging the admin.
//the user whose password is to be changed is determined by server variables, so
//people can't go changing anybody's password.

$args=file_get_contents("php://stdin");
if ($args) {
  $args = split('&',$args);
  foreach ($args as $arg) {
    $temp = split('=',$arg);
    $_REQUEST[$temp[0]] = $temp[1];
  }
}
$username = get_username();
if (!array_key_exists('newpassword',$_REQUEST)) {
  ?>
 <html><head><title>Change Password</title></head><body>
    
This page will change the password for the user below.  You cannot
create new users in this way, only change the password of existing
users.  Creating users must be done by an administrator.<p>

<form action="<?=$_SERVER['REQUEST_URI']?>" method=POST>
User: <?=$username?><br>
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
/*$passfilename = 'apache_users';
$passfile = file($passfilename);
$newfile = '';
foreach ($passfile as $line) {
  $temp = explode(':',$line);
  chop($temp[2]);
  if ($temp[0] == $username) {
    if ($temp[2] !== md5("$username:" . $temp[1] . ":" . $_REQUEST['oldpassword'])) {
      exit("Error: old password is not correct");
    }
    else {
      $temp[2] = md5("$username:" . $temp[1] . ":" . $newpassword);
    }
  }
  $newfile .= implode(':',$temp) . "\n";
}
if (!file_put_contents($passfilename,$newfile)) {
  trigger_error("Couldn't write to password file",E_USER_ERROR);
}*/
//htpasswd must be specified in your static_data and be fully qualified 
//or be /usr/bin/htpasswd or _:/etcetera
if (!($htpass = get_static('apache_htpasswd_path')) || 
    ($htpass{0} != '/' && $htpass{1} != ':')) {
  $htpass = "/usr/bin/htpasswd";
  set_static('apache_htpasswd_path',$htpass);
}
if (!file_exists('apache_users')) {
  $htpass .= " -c";
}
else {
  $lines = file('apache_users');
  $oldpassword = $_REQUEST['oldpassword'];
  foreach ($lines as $line) {
    $temp = split(':',$line);
    $temp[1] = chop($temp[1]);
    if ($temp[0] === $username) {
      if ((strlen($oldpassword) || 
          strlen($temp[1])) && 
          crypt($oldpassword,substr($temp[1],0,2)) != $temp[1]) {
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
