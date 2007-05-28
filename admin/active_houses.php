<?php
#print_r($_SERVER);
#exit;
$php_includes = '../php_includes/';
require_once('../php_includes/janakdb.inc.php');
$houses = array('ath','aca','caz','clo','con','dav','euc','hip','hoy',
		'kid','kng','lot','rid','she','stb','wil','wol','nsc');
foreach ($houses as $house) {
  $db->Connect('localhost',"usca_janak$house","workshift","usca_janak$house");
  print "<h1>$house</h1>";
  $db->SetFetchMode(ADODB_FETCH_NUM);
#  $db->debug = true;
  if (table_exists('modified_dates')) {
    $row = $db->GetRow("select * from `modified_dates` order by mod_date desc limit 1");
    if (is_empty($row)) {
      print "No data!";
      continue;
    }
    if ($row['mod_date'] == 'Mon 21, May 2007, 03:58:25') {
      print "Not active";
    }
    else {
      print escape_html($row['table_name']) . ": " . 
        escape_html($row['mod_date']);
    }
  }
}
exit;

#}
#  if (!is_empty($db->Execute("show tables like 'master\_shifts'"))) {
#    $db->Execute("alter table `master_shifts` add column `description` longtext default null");
#  }
#}
  /*  $res = $db->Execute("select " . join(",",$days) . " from `master_shifts`");
  $short_names = array();
  while ($row = $res->FetchRow()) {
    foreach ($row as $mem) {
      if ($mem == 'XXXXX' || !$mem) {
        continue;
      }
      $short_names[] = $mem;
    }
  }
  $short_names = array_unique($short_names);
  foreach ($short_names as $mem) {
    $names = split(' ',$mem);
    $names[] = '';
    $row = $db->GetRow("select `member_name` from `house_list` where `member_name` like '{$names[1]}%, {$names[0]}'");
    if (is_empty($row)) {
      print("No match for $mem<br>\n");
      continue;
    }
    foreach ($days as $day) {
      $db->Execute("update `master_shifts` set `$day` = ? where `$day` = ?",
                   array($row[0],$mem));
    }
  }*/
#  $res = $db->Execute("show tables like 'week\_%\_totals'");
#  while ($row = $res->FetchRow()) {
#    $row2 = $db->GetRow("show full columns from `" . $row[0] . "`");
#    $db->Execute("alter table " . $row[0] . " modify column `autoid` int(11) not null auto_increment first");
#  }
#  system("htpasswd -b -d /home/usca/domains/usca.org/public_html/workshift/php_includes/apache_users {$house}laborczar {$house}laborczar");
#  system("rm ../$house");
#  system("ln -s /home/usca/domains/usca.org/public_html/workshift/public_html ../$house");
#}
?>
