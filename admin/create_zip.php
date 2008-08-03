<?php
$php_start_time = array_sum(split(' ',microtime()));
require_once('default.admin.inc.php');
$max_email_size = 9000000;
ini_set('zlib.output_compression',false);
if (!array_key_exists('REQUEST_URI',$_SERVER)) {
  $_REQUEST = array_flip($argv);
  $_REQUEST['houses'] = $houses;
  $command_line = true;
}
else {
  $command_line = false;
}
if (!isset($_REQUEST['houses'])) {
?>
<html><head><title>Create backups</title></head><body>
Create backups for house(s):
<form action='<?=this_url()?>' method='get'>
<select name='houses[]' multiple>
<?php
foreach ($houses as $house) {
   print "<option value='" . escape_html($house) . "'>" . 
   escape_html($house) . "\n";
}
?>
</select><br/>
<input type='checkbox' name='all' id='all'>
<label for='all'>Get backup of entire database, including archives</label><br/>
<input type='checkbox' name='quick' id='quick'>
<label for='quick'>Get backup in a quicker, but less standard way</label><br/>
<input type='checkbox' name='optimize' id='optimize'>
<label for='optimize'>Optimize tables as they are backed up</label><br/>
<input type='submit' value='Create archive'>
</form>
</body>
</html>
<?php
exit;
}
$houses = $_REQUEST['houses'];
$scratch_dir = "$php_includes/scratch";
$backup_dir = "$scratch_dir/backupadmin";
#if (false) {
if (file_exists($backup_dir)) {
  $dh = opendir($backup_dir);
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$backup_dir/$fname") || 
      janak_error("Couldn't delete $fname from $backup_dir");
  }
}
else {
  if (!file_exists($scratch_dir)) {
    mkdir($scratch_dir);
  }
  mkdir($backup_dir);
}
set_error_handler('janak_errhandler');
$done_houses = array();
$max_time_allowed = 10;
foreach ($houses as $house_name) {
  if ($command_line) {
    fwrite(STDERR,"doing $house_name\n");
  }
   if ($command_line || check_php_time()) {
     $done_houses[] = $house_name;
     //     print "doing house $house_name\n";
   }
   else {
     break;
   }
  $url_array['user'] = $url_array['db'] = "usca_janak$house_name";
  $sql_user = null;
  $cols = array();
  if (!array_key_exists('all',$_REQUEST) || 
      array_key_exists('quick',$_REQUEST) || array_key_exists('optimize',$_REQUEST)) {
    $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
                 $url_array['db']);
    $db->SetFetchMode(ADODB_FETCH_NUM);
    $res = $db->Execute("show tables");
    while ($row = $res->FetchRow()) {
      if (substr($row[0],0,2) === 'zz' && !array_key_exists('all',$_REQUEST)) {
        continue;
      }
      if (array_key_exists('optimize',$_REQUEST)) {
        $db->Execute("optimize table " . bracket($row[0]));
      }
      $cols[] = $row[0];
    }
  }
  if (array_key_exists('quick',$_REQUEST)) {
    $db->SetFetchMode(ADODB_FETCH_NUM);
    ($handle = fopen("$backup_dir/" . $url_array['db'],"w")) ||
      trigger_error("Couldn't open $backup_dir/" . $url_array['db'],E_USER_ERROR);
    fwrite($handle,"set autocommit=0;\nbegin;\n") ||
      trigger_error("Couldn't start writing to $backup_dir/" . $url_array['db'],
                    E_USER_ERROR);
    $db->Execute("set autocommit=0");
    $db->Execute("begin");
    foreach ($cols as $tbl) {
      fwrite($handle,"drop table if exists `$tbl`;\n") ||
        trigger_error("Couldn't write to $backup_dir/" . $url_array['db'],
                      E_USER_ERROR);
      ($res = $db->Execute("show create table `$tbl`")) ||
        janak_error("Couldn't get table definition for $tbl");
      fwrite($handle,$res->fields[1] . ";\n") ||
        janak_error("Couldn't write to $backup_dir/" . $url_array['db']);
      ($res = $db->Execute("select * from `$tbl`")) ||
        janak_error("Couldn't select from $tbl");
      //have to do line by line because there are issues with too-long lines
      $tbl_rows = $res->GetRows();
      foreach ($tbl_rows as $data_row) {
        fwrite($handle,
               "INSERT INTO `$tbl` VALUES (" . 
               join(',',array_map('db_quote',$data_row)) . ");\n") ||
          janak_error("Couldn't write to $backup_dir/" . $url_array['db']);
      }
    }
    $db->Execute("commit");
    $db->Execute("set autocommit=1");
    fwrite($handle,"commit;\nset autocommit=1;\n") ||
      janak_error("Couldn't write to $backup_dir/" . $url_array['db']);
  }
  else {
    $exec_string = "mysqldump " .
      "--opt " .
      "--skip-lock-tables " .
      "--single-transaction " .
      "--quote-names " .
    "-u " . escapeshellarg($url_array['user']) . 
      " -p" . escapeshellarg($url_array['pwd']) .  
      " -h" . escapeshellarg($url_array['server']) .
      " " . escapeshellarg($url_array['db']) . " " .
      join(' ',array_map('escapeshellarg',$cols)) . 
      " 2>&1 1> " . escapeshellarg($backup_dir) . "/" . 
           escapeshellarg($url_array['db']);
    //    print $exec_string;
    system($exec_string);
  }
}
#}
// We'll be outputting a ZIP file
if (!$command_line) {
  header('Content-Type: application/zip');
  header('Content-disposition: attachment; filename="' . 
         date('Y-m-d-H-i-s') . '-' . 
         join('-',$done_houses) . '-workshift-backup.zip"');
}
if (!array_key_exists('mail',$_REQUEST)) {
  passthru("zip -j - " . addslashes($backup_dir) . "/*");
}
else {
  $datestring = date('Y-m-d-H-i-s');
  $filename = addslashes($backup_dir) . "/" . $datestring . 
    '-workshift-backup.zipfile';
  system("zip -j $filename " . addslashes($backup_dir) . "/* 2>&1");
  //negative maybe because wraparound of integers
  if (filesize($filename) > $max_email_size || filesize($filename) < 0) {
    system("chdir " . addslashes($backup_dir) . 
           " && split --bytes=$max_email_size $filename $datestring-");
    $ii = 1;
    foreach (glob("$datestring-*") as $filetozip) {
      system("mutt -s 'Backup for " . $datestring . " part $ii' -a " . 
             $filetozip .
             " workshiftadmin@gmail.com < /dev/null 2>&1");
      $ii++;
    }
  }
  else {
    system("mutt -s 'Backup for " . $datestring . "' -a " . 
           $filename .
           " workshiftadmin@gmail.com < /dev/null 2>&1");
  }
}
#*/;
janak_error_reporting(0);
janak_fatal_error_reporting(0);
if ($dh = opendir($backup_dir)) {
  while ($fname = readdir($dh)) {
    if ($fname=='.' || $fname=='..') continue;
    unlink("$backup_dir/$fname");
  }
}

function db_quote($str) {
  global $db;
  if ($str == null) {
    return 'NULL';
  }
  return $db->quote($str);
}

?>
