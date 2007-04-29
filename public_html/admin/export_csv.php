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
$mysql_file = "$db_dir/" . $url_array['db'];
$sqlhandle = fopen($mysql_file,"w");
fwrite($sqlhandle,"set autocommit=0;\nbegin;\n");
$db->Execute("set autocommit=0");
$db->Execute("begin");

$showres = $db->Execute("show tables");
$db->SetFetchMode(ADODB_FETCH_ASSOC);
while ($tbl = $showres->FetchRow()) {
  $tbl = $tbl[0];
  if (substr($tbl,0,2) === 'zz') {
    continue;
  }
  fwrite($sqlhandle,"\nDROP TABLE IF EXISTS " . bracket($tbl) . ";\n") ||
    janak_error("Couldn't write to $mysql_file");
  ($res = $db->Execute("show create table " . bracket($tbl))) ||
    janak_error("Couldn't get table definition for $tbl");
  fwrite($sqlhandle,$res->fields['Create Table'] . ";\n") ||
    janak_error("Couldn't write to $mysql_file");
  ($res = $db->Execute("select * from " . bracket($tbl))) ||
    janak_error("Couldn't select from $tbl");
  $tbl_rows = $res->GetRows();
  ($csvhandle = fopen("$db_dir/" . $tbl . ".csv","w")) ||
    janak_error("Couldn't open $db_dir/" . $tbl . ".csv");
  if (!count($tbl_rows)) {
    continue;
  }
  fputcsv($csvhandle,array_keys($tbl_rows[0]));
  foreach ($tbl_rows as $row) {
    fputcsv($csvhandle,$row);
    fwrite($sqlhandle,"INSERT INTO " . bracket($tbl) . " VALUES (" .
           join(',',array_map('db_quote',$row)) . ");\n");
  }
}

$db->Execute("commit");
$db->Execute("set autocommit=1");
fwrite($sqlhandle,"commit;\nset autocommit=1;\n") ||
janak_error("Couldn't write to $mysql_file");

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
  global $db;
  return '(' . join(',',array_map('db_quote',$arr)) . ')';
}

function db_quote($str) {
  global $db;
  if ($str === null) {
    return 'NULL';
  }
  return $db->quote($str);
}

?>
