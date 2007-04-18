<?php
require_once('default.inc.php');

if (array_key_exists('modify_privacy',$_REQUEST)) {
  $ii = 1;
  $val = 0;
  foreach (array('room','phone','email') as $attrib) {
    if (array_key_exists($attrib,$_REQUEST)) {
      $val |= $ii;
    }
    $ii <<=  1;
  }
  $db->Execute("update `house_info` set `privacy` = ? where `member_name` = ?",
               array($val,$member_name));
}
$privacy = $db->GetRow("select `privacy` from `house_info` where `member_name` = ?",
                   array($member_name));
$privacy = $privacy['privacy'];
?>
To modify what info of yours appears in the directory, check/uncheck the buttons and submit.
<form action='<?=this_url()?>' method=post><input type=hidden name='modify_privacy'>
Room: <input type=checkbox name='room' <?=$privacy & 1?'checked':''?>>, 
Email: <input type=checkbox name='email' <?=$privacy & 2?'checked':''?>>, 
Phone: <input type=checkbox name='phone' <?=$privacy & 4?'checked':''?>>
<input class=button type=submit value='Submit changes'></form>
<?php
$res = $db->Execute("select `house_list`.`member_name`, if(`privacy` & 1,`room`,null) as `room`, " .
                    "if (`privacy` & 2,`phone`,null) as `phone`, " .
                    "if (`privacy` & 4,`email`,null) as `email` " .
                    " from `house_list`,`house_info` " .
                    "where `house_list`.`member_name` = `house_info`.`member_name` " .
                    "order by `member_name`");
$other_td = '';
print "<table border><tr><td>";
$email_flag = false;
while ($row = $res->FetchRow()) {
  print escape_html($row['member_name'] . 
                    (strlen($row['room'])?', room ' . $row['room']:'') .
                    (strlen($row['phone'])?', ' . $row['phone']:'')) .
    "<br/>\n";
  if ($row['email']) {
    $other_td .= escape_html($row['email']) . ",";
    $email_flag = true;
  }
  $other_td .= "<br/>\n";
}
print "</td>";
if ($email_flag) {
  print "<td>$other_td</td></tr></table>";
}
?>