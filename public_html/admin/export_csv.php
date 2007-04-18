<?php
require_once('default.inc.php');
#$db->debug = true;
$scratch_dir = "$php_includes/scratch";
$csv_dir = "$scratch_dir/csv";
if (!file_exists($csv_dir)) {
  if (!file_exists($scratch_dir)) {
    mkdir($scratch_dir);
  }
  mkdir($csv_dir);
}
$db_dir = "$csv_dir/" . $url_array['db'];
if (file_exists($db_dir)) {
  $dh = opendir($db_dir);
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$db_dir/$fname") || 
      trigger_error("Couldn't delete $fname from $db_dir",E_USER_ERROR);
  }
}
else {
  mkdir($db_dir);
}
$cols = array();
$db->SetFetchMode(ADODB_FETCH_NUM);
$res = $db->Execute("show tables");
while ($tbl = $res->FetchRow()) {
  ($handle = fopen("$db_dir/" . $tbl[0] . ".csv","w")) ||
    trigger_error("Couldn't open $db_dir/" . $tbl[0] . ".csv",E_USER_ERROR);
  $db->SetFetchMode(ADODB_FETCH_ASSOC);
  $tblres = $db->Execute("select * from " . bracket($tbl[0]));
  if (is_empty($tblres)) {
    continue;
  }
  fputcsv($handle,array_keys($tblres->fields));
  while ($row = $tblres->FetchRow()) {
    fputcsv($handle,$row);
  }
}
// We'll be outputting a ZIP file
header('Content-type: application/zip');
header('Content-Disposition: attachment; filename="' . 
       date('Y-m-d-H-i-s') . $url_name . '-csv.zip"');
passthru("zip -j -r - " . escape_html($db_dir));
#passthru("man zip");
#*/;
janak_error_reporting(0);
janak_fatal_error_reporting(0);
if ($dh = opendir($db_dir)) {
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$db_dir/$fname");
  }
}

function parr($arr) {
  return '(' . join(',',$arr) . ')';
}

?>
