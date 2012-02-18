<?php
//Displays the last time each house used the system.  Mostly an ego boost.
//Useful if you majorly screwed something up and need to see which houses' 
//systems need to be fixed.
//Commented by Janak 31 Jan 2010
require_once('default.admin.inc.php');
foreach ($houses as $house) {
  $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
    "$db_basename$house");
  print "<h1>" . escape_html($house) . "</h1>";
  if (table_exists('modified_dates')) {
    $row = $db->GetRow("select `table_name`," .
      "unix_timestamp(`mod_date`) as `mod` " .
      "from `modified_dates` order by mod_date desc limit 1");
    if (is_empty($row)) {
      print "Inactive.";
      continue;
    }
    print escape_html($row['table_name']) . ": ";
    $diff = time()-$row['mod'];
    foreach(array(array(60,'second'),array(60,'minute'),
      array(24,'hour'),array(7,'day'),
      array(30,'week'),array(12,'month'),
      array(10000,'year')) as $time_unit) {
        if ($diff < $time_unit[0]) {
          print "$diff " . $time_unit[1] . ($diff>1?'s':'');
          break;
        }
        $diff = round($diff/$time_unit[0]);
      }
    //'l, F j, g:i:s a' is like 'Sunday, January 24, 11:10:39 pm'
    print " ago, " . escape_html(user_time($row['mod'],'l, F j, g:i:s a'));
  }
  else {
    print "<strong>House's database is not set up properly.</strong>\n";
  }
}
exit;

?>
