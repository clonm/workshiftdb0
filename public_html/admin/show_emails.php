<?php
//print out everyone's emails, and say who doesn't have their emails entered
require_once('default.inc.php');
$res = $db->Execute("SELECT `{$archive}house_list`." . 
                    bracket('member_name') . ', ' . 
		     bracket('email') . " FROM `{$archive}house_info`, " .
                    "`{$archive}house_list` where " .
                    "`{$archive}house_list`.`member_name` = " . 
                    "`{$archive}house_info`.`member_name` " .
                    "order by `{$archive}house_list`.`member_name`");
#$houselist = get_houselist();
?>
<html><head><title>Email addresses</title></head><body>
<pre>
<?php
$no_emails = array(); 
while ($row = $res->FetchRow()) {
  $member_name = $row['member_name'];
  $names = explode(', ',$member_name);
  if (count($names) > 1) {
    $member_name = $names[1] . ' ' . $names[0];
    if (count($names) > 2) {
      $member_name = join(', ',array_slice($names,2));
    }
  }
  if ($row['email']) {
    echo $member_name . ' &lt;' . $row['email'] . "&gt;,\n";
  }
  else {
    $no_emails[] = $member_name;
  }
}
echo "No emails for: \n";
foreach($no_emails as $member) {
  echo $member . "\n";
}
?>