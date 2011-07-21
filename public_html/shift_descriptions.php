<?php
$body_insert = '';
require_once('default.inc.php');
?>
<html><head><title>Shift Descriptions</title></head>
<style>
/* Browser specific (not valid) styles to make preformatted text wrap */		

pre {
 white-space: pre-wrap;       /* css-3 */
 white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
 white-space: -pre-wrap;      /* Opera 4-6 */
 white-space: -o-pre-wrap;    /* Opera 7 */
 word-wrap: break-word;       /* Internet Explorer 5.5+ */
}

table {
  empty-cells: show;
}
</style>
<body>
<?=$body_insert ?>
<table border>
<tr><th>Workshift</th><th>Earliest starting time</th><th>Latest ending time</th><th>Description</th></tr>
<?php
$res = $db->Execute("select `workshift`, `hours`, `floor`, `start_time`, `end_time`, `description` from `master_shifts`" .
                    " order by `workshift`, `floor`, `hours`");
$workshift = null;
$hours = null;
$floor = null;

$new_flag = false;
while ($row = $res->FetchRow()) {
  if ($row['workshift'] === $workshift &&
      $row['floor'] === $floor &&
      $row['hours'] === $hours) {
    continue;   
  }
  if ($workshift !== $row['workshift']) {
    $new_flag = true;
  }
  $workshift = $row['workshift'];
  $floor = $row['floor'];
  $hours = $row['hours'];
  $shift = escape_html(format_shift($row['workshift'],$row['hours'],$row['floor']));
  print "<tr";
  if ($new_flag) {
    print " id='" . escape_html($row['workshift']) . "'";
    $new_flag = false;
  }
  print "><td style='width: 20em; vertical-align: top'>" . $shift . "</td><td style='vertical-align: top'>" . 
    escape_html(timeformat($row['start_time'])) . 
    "</td><td style='vertical-align: top'>" . escape_html(timeformat($row['end_time'])) . 
    "<td style='width: 30em'><pre>" . escape_html($row['description']) . "</pre></td></tr></a>\n";
}

//format a time by putting in the pm or am, possibly stripping the minute
function timeformat($str) {
  if ($str) {
    $time = substr($str,0,5);
  }
  else {
    $time = "";
  }
  if ($time) {
    $hour = substr($time,0,2);
    $minute = substr($time,3,2);
    if ($hour >= 12) {
      if ($hour > 12) {
	$hour-=12;
      }
      $suffix = 'pm';
    }
    else {
      if ($hour == 0) {
	$hour = 12;
      }
      $suffix = 'am';
    }
    //get rid of leading 0
    if ($hour < 10) {
      $hour = 0+$hour;
    }
    $time = $hour . (($minute !== '00')?":$minute":'') . " $suffix";
  }
  return $time;
}

?>
</table></body></html>
