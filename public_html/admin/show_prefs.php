<?php
require_once('default.inc.php');
ob_start();
#$db->debug = true;
if (!isset($_REQUEST['person'])) {
  $houselist = get_houselist();
  $this_url = explode('?',$_SERVER['REQUEST_URI']);
  $this_url = $this_url[0];
?>
<html><head><title>Preference Form View</title></head>
<body>
<form action='<?=escape_html($this_url)?>' method=GET>
   Name: <select name='person'>
<?php
foreach ($houselist as $name) {
  print "<OPTION>" . escape_html($name) . "\n";
}
?>
</SELECT>
    <?php 
    if (!$secured) {
  ?>
    Password: <input type=password name='passwd'>
                 <?php 
                 } 
    else { 
?>
<input type=hidden name='passwd' value=''>
<?php
   }
  foreach ($_GET as $key => $val) {
    print "<input type=hidden name='" . escape_html($key) .
      "' value='" . escape_html($val) . "'>";
  }
?>
<input type=submit value='Submit'>
</form>
</body>
</html>
<?php 
   exit(); 
}
$person = $_REQUEST['person'];
?>
<html><head><title>Info for <?=$person?></title></head>
<style>
table {
  empty-cells: show;
}
</style>

<body>
<?php
print "$person: <p>\n";
$firstflag = true;
$days = array('Monday', 'Tuesday', 'Wednesday', 
	      'Thursday', 'Friday', 'Saturday','Sunday');
foreach(array_pad($days,8,'Weeklong') as $day) {
  $res = $db->Execute("SELECT " . bracket('workshift') . ", " . 
		     bracket('floor') . ", " . bracket($day) .
		     " AS " . bracket('day') . " FROM " . 
		     bracket($archive . 'master_shifts') . 
                      " WHERE " . bracket($day) .
		     " = ?",array($person));
  if (is_empty($res)) {
    continue;
  }
  if ($firstflag) {
    $firstflag = false;
    print "Assigned: <p>\n";
  }
  print "$day: <br>";
  while ($row = $res->FetchRow()) {
    print $row['workshift'];
    if ($row['floor']) {
      print " floor " . $row['floor'];
    }
    print "<br>\n";
  }
  print "<br>";
}
$shift_prefs_style = get_static('shift_prefs_style',0);
$max_rating = get_static('wanted_max_rating',2);

if (!$shift_prefs_style) {
  for ($ii = 0; $ii <= 2; $ii++) {        
    if ($ii == 1) {
      continue;
    }
    $res = $db->Execute("select `shift`,`floor`,`day` from `{$archive}wanted_shifts` where " .
                        '`member_name` = ? and `rating` = ?',
                        array($person,$ii));
    switch($ii) {
    case '2': print 'W'; break;
    case '0': print 'Unw'; break;
    }
    print "anted: <br>\n";
    rs2html($res);
  }
}
else {
  print "<h4>Shift Preferences:</h4>";
  $table_name = "{$archive}wanted_shifts";
  $col_names = array('shift','rating','type','floor');
  $col_sizes = array(100,0,0,0);
  $col_sortable = array('pre_process_default','pre_process_num','pre_process_default');
  $table_edit_query = 'select `shift`,`rating`,`day` as `type`,`floor` from `wanted_shifts` where ' .
    '(`day` = ' . $db->qstr('category') . ' or `day` = ' .
    $db->qstr('shift') . ') and `member_name` = ' . $db->qstr($person) . ' order by `type`,`shift`';
  $body_insert = ob_get_contents();
  ob_end_clean();
  ob_start();
}

$res = $db->Execute("SELECT " . bracket('notes') . ", " . bracket('av_0') .
		    ", " . bracket('av_1') . ", " . bracket('av_2') . 
		    ", " . bracket('av_3') . ", " . bracket('av_4') .
		    ", " . bracket('av_5') . ", " . bracket('av_6') .
		    " FROM " . bracket($archive . 'personal_info') . 
                    " WHERE " . 
		    bracket('member_name') . " = ?", 
		    array($person));

$row = null;
while ($rw = $res->FetchRow()) {
  $row = $rw;
  break;
}
if (count($row)) {
  print "<p>" . escape_html($row['notes']) . "<p>";
?>
<table border=1>
<tr><td></td>
<?php
$hours = array("8am", "9am", "10am", "11am", "12pm");
foreach ($hours as $hour) {
  print "<td>$hour</td>";
}
for ($ii = 1; $ii<12; $ii++) {
  print "<td>${ii}pm</td>";
}
print "</tr>\n";
$av_options = array('+','&nbsp;','-','x','?');
$weekday = 0;
foreach ($days as $day) {
  print "<tr>";
  print "<td>$day</td>";
  for ($ii=0; $ii<16; $ii++) {
    print "<td>" . $av_options[decode_avail($row["av_$weekday"],$ii)] . "</td>";
  }
  print "</tr>\n";
  $weekday++;
}
?>
</table>
<?php
    }
if (!$shift_prefs_style) {
  ob_end_flush();
?>
</body>
</html>
<?php
    }
    else {
      $javascript_pre = ob_get_contents();
      ob_end_clean();
      require_once("$php_includes/table_view.php");
    }
?>
