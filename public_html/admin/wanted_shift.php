<?php 
require_once('default.inc.php');
if (!array_key_exists('workshift',$_REQUEST)) { ?>
 <html><head><title>View information</title></head>
    <body>
    <form action="<?=$_SERVER['REQUEST_URI']?>" method=GET>
   Workshift: <select name='workshift'>
    <?php $res = $db->Execute("select `workshift` from `{$archive}master_shifts` order by `workshift`");
 while ($row = $res->FetchRow()) {
   print "<option>" . $row['workshift'] . "\n";
 }
 ?>
</select>
<input type=submit value='Submit'>
</form>
</body>
</html>
<?php exit(); }
$workshift = $_REQUEST['workshift'];
$time = microtime(1);
#$db->debug = true;
?>
<html><head><title>People who want or don't want <?=$workshift?></title></head><body>
<?php
#$db->debug = true;
$cols = array('member_name','day','floor');
$list = $db->Execute('SELECT ' . implode(', ',$cols) . ' FROM ' . 
		     bracket($archive . 'wanted_shifts') .
		     ' WHERE ' . bracket('shift') . 
		     ' = ?',
		     array($workshift));
print "People who want $workshift<p>\n";
print "<table border=1>\n";
print "<tr><td>";
print implode('</td><td>',$cols);
print "</td></tr>\n";
while ($row = $list->FetchRow()) {
  print "<tr><td>";
  print implode('</td><td>',$row);
  print "</td></tr>\n";
}
print "</table><p>";
$cols = array('member_name','day','floor');
$list = $db->Execute('SELECT ' . implode(', ',$cols) . ' FROM ' . 
		     bracket('unwanted_shifts') .
		     ' WHERE ' . bracket('shift') . 
		     ' = ?',
		     array($workshift));
print "People who don't want $workshift<p>\n";
print "<table border=1>\n";
print "<tr><td>";
print implode('</td><td>',$cols);
print "</td></tr>\n";
while ($row = $list->FetchRow()) {
  print "<tr><td>";
  print implode('</td><td>',$row);
  print "</td></tr>\n";
}
print "</table>";
?>
</body></html>
