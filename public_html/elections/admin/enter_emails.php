<?php
$body_insert = '';
require_once('default.inc.php');
?>
<html><head><title>Enter emails</title></head><body>
<?=$body_insert?>
<?php
if (!isset($_REQUEST['emails_status'])) {
?>
If you are the president of a house that doesn't use the workshift system,
you can use this page to update the emails of house members.  You'll need to
have the house list entered already, at
<a href='../../admin/update_house.php'>update_house.php</a>.  You need to
have the workshift manager username and password, which shouldn't be a
problem if there isn't a workshift manager.  Email <?=admin_email()?> to
get it reset if you don't know it.  Then follow the instructions on that
page, and come back here when you're done.

Now, email <a href='mailto:housing@usca.org'>housing@usca.org</a> and ask
them for a list of house emails as a csv file for the workshift system.  Then
come back here, and upload it.
<form action='<?=this_url()?>' method='POST'
   enctype="multipart/form-data">
<input type="file" name="email_csv" size="40"/>
<input type='hidden' name='emails_status' value='file'/>
<input type="submit" value="Upload!"/>
</form>
</body></html>
<?
exit;
}
$res = $db->Execute('select `app_number`, `house_info`.`member_name`,`email` ' .
'from `house_info` inner join `points` using (`member_name`) inner join ' .
'`house_list` using (`member_name`)');
$member_info = array();
while ($row = $res->FetchRow()) {
  $member_info[$row['app_number']] = array($row['member_name'],$row['email']);
}
if ($_REQUEST['emails_status'] == 'file') {
?>
<h4>No changes have been made yet.  To confirm changes, press "submit"
button at bottom of page.</h4>
<?php
$handle = fopen($_FILES['email_csv']['tmp_name'],'r');
if (!$handle) {
   exit("Error opening file.  Please try again.");
}
$header_row = fgetcsv($handle);
$appnum = -1;
$email = -1;
for ($ii = 0; $ii < count($header_row); $ii++) {
  if ($header_row[$ii] == 'app#') {
      $appnum = $ii;
  }
  else if ($header_row[$ii] == 'email') {
    $email = $ii;
  }
  if ($appnum >=0 && $email >= 0) {
    break;
  }
}
if ($appnum < 0 || $email < 0) {
  exit("Your file was not properly formatted.  Could not find an " .
       "application number or email field.");
}
?>
Proposed changes:
<form action='<?=this_url()?>' method=post>
<input type='hidden' name='emails_status' value='submitting'/>
<table>
<tr><th>Name</th><th>App #</th><th>New email</th></tr>
<?php
while ($data_row = fgetcsv($handle)) {
  print "<tr>";
  $app = $data_row[$appnum];
  if (!isset($member_info[$app])) {
    print "<td colspan='3'>No member found with application number " .
          escape_html($app) . " (email entered: " .
          escape_html($data_row[$email]) . ")</td>";
  }
  else if ($member_info[$app][1]) {
    print "<td colspan='3'>Member with application number " .
          escape_html($app) . " already " .
          "has email address of " . escape_html($member_info[$app][1]) .
          ", so it will not be changed to " .
          escape_html($data_row[$email]) . "</td>";
  }
  else {
    $this_mem = $member_info[$app];
//    print "<td>"; var_dump($this_mem); print "</td>";
    print "<td>" . escape_html($this_mem[0]) . "</td><td>" .
          escape_html($app) . "</td><td>" .
          "<input readonly name='newmail_" . escape_html($app) .
          "' value='" . escape_html($data_row[$email]) . "'/></td>";
  }
  print "</tr>\n";
}
print "</table><input type='submit' value='Submit changes'/>" .
"</form></body></html>";
exit;
}
foreach ($member_info as $app => $info) {
  if (isset($_REQUEST["newmail_$app"])) {
    $newemail = $_REQUEST["newmail_$app"];
    $oldemail = $db->GetRow("select `email` from `house_info` where " .
    "`member_name` = ?",array($info[0]));
    $oldemail = $oldemail['email'];
    if ($oldemail === null && $oldemail !== $newemail) {
       print "Setting email for " . escape_html($info[0]) . " to " .
            escape_html($_REQUEST["newmail_$app"]) . "<br/>";
      $db->Execute("update `house_info` set `email` = ? where " .
                   "`member_name` = ?",
                   array($newemail,$info[0]));
      elections_log(null,'president email change',$info[0],
                    $oldemail,$newemail);
    }
    else if ($oldemail !== null) {
      janak_error("Uh-huh.  What were you trying to do?");
    }
    else {
      janak_error("New email is null?");
    }
  }
}