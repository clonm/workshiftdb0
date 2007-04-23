<?php
//print out a list of members, with each member's shifts listed by their name
$require_user = false;
require_once('default.inc.php');
$houselist = array();
foreach(array("Monday","Tuesday","Wednesday","Thursday","Friday",
	      "Saturday","Sunday","Weeklong") as $day) {
  $res = $db->Execute('SELECT ' . bracket('workshift') . ', ' . 
		      bracket('floor') . ', ' . bracket($day) . 
		       ' FROM ' . bracket($archive . 'master_shifts') .
		      " WHERE " . bracket($day) . " <> ?",
		      array($dummy_string));
  while ($row = $res->FetchRow()) {
    if (!$row[$day])
      continue;
    if (!array_key_exists($row[$day],$houselist)) {
      $houselist[$row[$day] ] = '';
    }
    else
      $houselist[$row[$day] ] .= ', ';
    $houselist[$row[$day] ] .= ($day === 'Weeklong'?'':"$day ") . 
      $row['workshift'] . ($row['floor']?' ' . $row['floor']:'');
  }
}
//sort by keys, which are the names
ksort($houselist);
?>
<html><head>
<LINK REL=StyleSheet HREF="<?=$html_includes?>/table_print.css" TYPE="text/css">
<title>Shifts By Name</title></head><body>
<table>
<?php foreach ($houselist as $member_name => $shifts) { ?>
  <tr><td><?=$member_name?></td><td><?=$shifts?></td></tr>
     <?php } ?>
</table>
</body>
</html>
