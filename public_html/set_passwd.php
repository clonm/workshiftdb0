<?php 
//change password for user -- must know old password
$require_user = false;
require_once('default.inc.php');
if (($passwdcheck = check_passwd()) <= 0 &&
    ($passwdcheck != -1 || !isset($_REQUEST['newpasswd']))) {
  $houselist = get_houselist();
  if (isset($_REQUEST['member_name'])) {
    $member_name = $_REQUEST['member_name'];
  }
  else {
    $member_name = null;
  }
  ?>
 <html><head><title>Set password</title></head>
    <body>
<?php print_help() ?>
    <form action="<?=this_url()?>" method=POST>
    Name: <select name='member_name'>
    <OPTION>
    <?php
    print "<OPTION>";
  foreach ($houselist as $member) {
    print "<option";
    if ($member === $member_name) {
      print " selected";
    }
    print ">" . stripformslash($member) . "\n";
  }
  print "\n";
   ?>
    </SELECT>
	Old password: <input type=text name='passwd'>
	New password: <input type=text name='newpasswd'>
	<input type=submit value='Submit'>
	</form>
	</body>
	</html>
<?php 
        exit(); 
}
if (!set_passwd($_REQUEST['member_name'],$_REQUEST['newpasswd'],
                $_REQUEST['passwd'])) {
  janak_error("Couldn't change password.");
}
$_REQUEST['passwd'] = $_REQUEST['newpasswd'];
$require_user = true;
require_user();
print("Success changing password");
elections_log(null,'member change','password change',null,$_REQUEST['member_name']);
if (isset($_REQUEST['previous_url'])) {
  print ("<p>Go back to <a href='" . escape_html($_REQUEST['previous_url']) . "'>" .
         escape_html($_REQUEST['previous_url']) . "</a></p>");
}
?>
