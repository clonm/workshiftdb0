<?php
$body_insert = '';
require_once('default.inc.php');
if (!isset($_REQUEST['members'])) {
?>

<html><head><title>Initialize and Mail Passwords for Members</title></head><body>
<?=$body_insert?>
If you have a large number of users who have not set their passwords, you can
assign them random passwords, which will be emailed to their email addresses on
file.  Note that you cannot set a user's password here unless they have an
email address on file and they have not set their password.  You can set
email addresses at <a href='enter_emails.php'>enter_emails.php</a>.

<?php
$res = $db->Execute(
"select `house_list`.`member_name` from `house_list` inner join `password_table` " .
"using (`member_name`) inner join `house_info` using (`member_name`) where " .
"`password_table`.`passwd` is null and `house_info`.`email` is not null");
  $no_passwords = array();
  while ($row = $res->FetchRow()) {
    $no_passwords[] = $row['member_name'];
  }
?>

<form action='<?=this_url()?>' method=POST>
<select id='members' name='members[]' multiple>
  <?php
    foreach ($no_passwords as $member) { 
  print "<option value='" . escape_html($member) . "'";
  print " selected>" .  escape_html($member) . "\n";
 }
?>
</select><br>
<input type=submit value='Set password randomly and email to member(s)'></form>
</form>

<script type='text/javascript'>
var sel_elt = document.getElementById('members');

</script>
<?php
    exit;
    }
?>
<html><head><title>Resetting passwords</title></head><body>
Please wait for the page to say "All done!" at the bottom.<br/>

<?php
$members = $_REQUEST['members'];
$res = $db->Execute(
"select `house_list`.`member_name` from `house_list` left join `password_table` " .
"using (`member_name`) left join `house_info` using (`member_name`) where " .
"`password_table`.`passwd` is null and `house_info`.`email` is not null");
  $no_password = array();
  while ($row = $res->FetchRow()) {
    $no_password[$row['member_name']] = 1;
  }
foreach ($members as $member) {
  if (!isset($no_password[$member])) {
    print "Did not reset password for " . escape_html($member) .
    ", since either member already has password set, does not have email " .
    "set, or does not exist in the system.<br/>\n";
    continue;
  }
  $passwd = substr(md5(rand() . $member),0,6);
  set_passwd($member,$passwd,null,false,true);
  mail(get_email($member), "Your password has been automatically set",
       "The " . $house_name . " president has set your password.  " .
       "Your new password is: " . $passwd . "\n" .
       "Your president does not know this password -- only you do.  You can " .
       "change your password at http://workshift.bsc.coop/" . $house_name . 
       "/person.php and also vote there.",
       "From: ${house_name}hp1@usca.coop");
}
?>
All done!
