<?php 
//reset password for user so they can set it themselves
$require_user = array('workshift','president');
require_once('default.inc.php');
if (!array_key_exists('member_name_reset',$_REQUEST)) {
  $houselist = get_houselist();
  ?>
 <html><head><title>Reset password</title></head>
    <body>
    If a user has forgotten his or her password, this page can clear it so 
    that the user can go to the personal page and set it themselves.
    <form action="<?=$_SERVER['REQUEST_URI']?>" method=POST>
    Name: <select name='member_name_reset'>
    <OPTION>
    <?php
    foreach ($houselist as $name) {
      print "<OPTION>$name\n";
    }
   ?>
    </SELECT>
	<input type=submit value='Reset Password'>
	</form>
	</body>
	</html>
<?php exit(); }
if (!$db->Execute("UPDATE `password_table` " . 
                  "set `passwd` = null where `member_name` = ?",
                  array($_REQUEST['member_name_reset']))) {
  trigger_error("Error resetting password.",E_USER_ERROR);
}
print "<pre>";
elections_log(null,'workshift change','reset password',null,
              $_REQUEST['member_name_reset']);
exit("Success resetting password");

?>
