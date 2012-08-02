<?php
require_once('default.admin.inc.php');
$temp_houses = getopt('h::');
if (isset($temp_houses['h'])) {
  $houses = $temp_houses['h'];
  if (!is_array($houses)) {
    $houses = array($houses);
  }
}

foreach ($houses as $house_name) {
  //  fwrite(STDERR,"doing $house_name\n");
  //  print "$house_name\n";
  $url_array['db'] = "$db_basename$house_name";
  $sql_user = null;
  $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
               $url_array['db']);
  $include_path = get_include_path();
  $include_path = '../public_html/' . PATH_SEPARATOR . $include_path;
  set_include_path($include_path);
  $can_delete_flag = true;
  $quiet_flag = true;
  require('../public_html/admin/delete_backup_database.php');
  $dbnames = null;
}

?>
