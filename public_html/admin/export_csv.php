<?php
//lets workshift manager download all tables in .csv format (in case
//they want to import them into excel).
$body_insert = '';
require_once('default.inc.php');
#$db->debug = true;
//we can't create a zipfile in memory, at least not easily.  Potential
//for problems here, if directory doesn't have proper permissions.
$scratch_dir = "$php_includes/scratch";
$csv_dir = "$scratch_dir/csv";
if (!file_exists($csv_dir)) {
  if (!file_exists($scratch_dir)) {
    mkdir($scratch_dir);
  }
  mkdir($csv_dir);
}
//deal with possibility of multiple exports happening (which is likely
//if user reloads before one is finished).  Php documentation makes
//flock seem a little shaky, so I add a more primitive
//open-file-manually test.
$lockfile = "$csv_dir/" . $url_array['db'] . '_lock';
if (file_exists($lockfile)) {
  if (!isset($_REQUEST['delete_lock'])) {
    exit("Restore directory is locked.  Did you just reload this page " .
         "before it was finished loading?" .
         "<form action='" . this_url() . "'><input name='delete_lock' " .
         "value='Try Again'></form>");
  }
  //to avoid erroring if the other script removed the file on its own,
  //&& the unlink with another test for existence.  (Actually, if the
  //unlink fails, the script will probably error out, but that's ok.
  if (!unlink($lockfile) && file_exists($lockfile)) {
    exit("Couldn't start exporting -- another script is trying to " .
         "export at the same time.  Wait a bit and " .
         "<a href='" . this_url() . "'>try again</a>.");
  }
}
//open, demand that file does not exist.
$lock_handle = fopen($lockfile,'x');
if (!$lock_handle) {
  exit("Couldn't start exporting -- another script is trying to " .
       "export at the same time.  Wait a bit and " .
       "<a href='" . this_url() . "'>try again</a>.");
}
//lock
flock($lock_handle,LOCK_EX);
//ok, done with locking shenanigans (until end)

  //make directory with house name
$db_dir = "$csv_dir/" . $url_array['db'];
//empty it out if it's not empty
if (file_exists($db_dir)) {
  $dh = opendir($db_dir);
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') {
        continue;
    }
    //unlink will fail and error out if file exists.
    unlink("$db_dir/$fname");
  }
}
else {
  mkdir($db_dir);
}
$cols = array();
//we're going to give the user a mysql file (same as we would make in
//create_zip.php) in this csv backup.  That way, if the user wants to
//recover from a download they did, we can use the mysql file.
$mysql_file = "$db_dir/" . $url_array['db'];
$sqlhandle = fopen($mysql_file,"w");
flock($sqlhandle,LOCK_EX);
fwrite($sqlhandle,"set autocommit=0;\nbegin;\n");
$db->Execute("set autocommit=0");
$db->Execute("begin");

//need num mode to avoid silly column names in show tables
$db->SetFetchMode(ADODB_FETCH_NUM);
$showres = $db->Execute("show tables");
//need assoc mode for the loop
$db->SetFetchMode(ADODB_FETCH_ASSOC);
while ($tbl = $showres->FetchRow()) {
  $tbl = $tbl[0];
  //skip the archived/backup tables
  if (substr($tbl,0,2) === 'zz') {
    continue;
  }
  //don't export tables with sensitive data
  if ((is_array($table_permissions['table_allow']) && 
       !in_array($tbl,$table_permissions['table_allow'])) ||
      (is_array($table_permissions['table_deny']) &&
       in_array($tbl,$table_permissions['table_deny']))) {
    continue;
  }
  fwrite($sqlhandle,"\nDROP TABLE IF EXISTS " . bracket($tbl) . ";\n");
  $res = $db->Execute("show create table " . bracket($tbl));
  fwrite($sqlhandle,$res->fields['Create Table'] . ";\n");
  $res = $db->Execute("select * from " . bracket($tbl));
  $csvhandle = fopen("$db_dir/" . $tbl . ".csv","w");
  flock($csvhandle,LOCK_EX);
  $first_flag = true;
  //loop through -- don't do all at once, because sometimes lines are
  //too long for mysql dumping
  while ($row = $res->FetchRow()) {
    if ($first_flag) {
      fputcsv($csvhandle,array_keys($row));
      $first_flag = false;
    }
    fputcsv($csvhandle,$row);
    fwrite($sqlhandle,"INSERT INTO " . bracket($tbl) . " VALUES (" .
           join(',',array_map('db_quote',$row)) . ");\n");
  }
}

$db->Execute("commit");
$db->Execute("set autocommit=1");
fwrite($sqlhandle,"commit;\nset autocommit=1;\n");

// We'll be outputting a ZIP file
header('Content-type: application/zip');
header('Content-Disposition: attachment; filename="' . 
       date('Y-m-d-H-i-s') . $url_name . '-csv.zip"');
passthru("zip -j -r - " . escape_html($db_dir));
//don't want anything to fail here
janak_error_reporting(0);
janak_fatal_error_reporting(0);
if ($dh = opendir($db_dir)) {
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$db_dir/$fname");
  }
}
//time to unlock
fclose($lockfile);
unlink($lockfile);

//annoyance that nulls won't be entered properly in db->quote
function db_quote($str) {
  global $db;
  if ($str === null) {
    return 'NULL';
  }
  return $db->quote($str);
}

?>
