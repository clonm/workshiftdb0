<?php
$backup_email = 'workshiftbackups1@gmail.com';
$max_email_size = 3000000;

$php_start_time = array_sum(split(' ',microtime()));
require_once('default.admin.inc.php');
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
     if (defined('STDERR')) {
       fwrite(STDERR,"ran out of time");
     }
     break;
   }
  $url_array['db'] = $db_basename . $house_name;
  $cols = array();
  if (!array_key_exists('all',$_REQUEST) || 
      array_key_exists('quick',$_REQUEST) || array_key_exists('optimize',$_REQUEST)) {
    $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
                 $url_array['db']);
    $db->SetFetchMode(ADODB_FETCH_NUM);
    if (array_key_exists('all',$_REQUEST)) {
      $res = $db->Execute("show tables");
    }
    else {
      $res = $db->Execute("show tables where " . 
                          bracket('Tables_in_' . $url_array['db']) . " REGEXP '^[^z]'");
    }
    while ($row = $res->FetchRow()) {
      if (array_key_exists('optimize',$_REQUEST)) {
        $db->Execute("optimize table " . bracket($row[0]));
      }
      $cols[] = $row[0];
    }
  }
  if (array_key_exists('quick',$_REQUEST)) {
    $db->SetFetchMode(ADODB_FETCH_NUM);
    $db_file = "$backup_dir/" . $house_name;
    ($handle = fopen($db_file,"w")) ||
      janak_error("Couldn't open $db_file");
    fwrite($handle,"set autocommit=0;\nbegin;\n") ||
      janak_error("Couldn't start writing to $db_file");
    $db->Execute("set autocommit=0");
    $db->Execute("begin");
    foreach ($cols as $tbl) {
      fwrite($handle,"drop table if exists `$tbl`;\n") ||
        janak_error("Couldn't write to $db_file");
      ($res = $db->Execute("show create table `$tbl`")) ||
        janak_error("Couldn't get table definition for $tbl");
      fwrite($handle,$res->fields[1] . ";\n") ||
        janak_error("Couldn't write to $db_file");
      ($res = $db->Execute("select * from `$tbl`")) ||
        janak_error("Couldn't select from $tbl");
    while ($data_row = $res->FetchRow()) {
      fwrite($handle,
               "INSERT INTO `$tbl` VALUES (" . 
               join(',',array_map('db_quote',$data_row)) . ");\n") ||
          janak_error("Couldn't write to $db_file");
      }
    }
    $db->Execute("commit");
    $db->Execute("set autocommit=1");
    fwrite($handle,"commit;\nset autocommit=1;\n") ||
      janak_error("Couldn't write to $db_file");

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
           escapeshellarg($house_name);
    //    print $exec_string;
    system($exec_string);
  }
}
#}
// We'll be outputting a ZIP file
if (!$command_line && !array_key_exists('mail',$_REQUEST)) {
  header('Content-Type: application/zip');
  header('Content-disposition: attachment; filename="' . 
         date('Y-m-d-H-i-s') . '-' . 
         join('-',$done_houses) . '-weekly-backup.zip"');
  passthru("zip -j - " . addslashes($backup_dir) . "/*");
}
else {
  $datestring = date('Y-m-d-H-i-s');
  $zip_houses = array();
  $this_size = 0;
  foreach ($houses as $house_name) {
    $this_size += filesize(addslashes($backup_dir) . "/" . $house_name);
    $zip_houses[] = $house_name;
    //zip compression is around 1/6
    if ($this_size > 6*$max_email_size) {
      process_houses($zip_houses,$datestring,$backup_dir);
      $this_size = 0;
      $zip_houses = array();
    }
  }
  if (count($zip_houses)) {
    process_houses($zip_houses,$datestring,$backup_dir);
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

function process_houses($zip_houses,$datestring,$backup_dir) {
  global $php_includes,$php_utils,$backup_email;
  $backup_sdir = addslashes($backup_dir . "/");
  $filename = $backup_sdir . $datestring . 
    '-weekly-backup.zip';
  system("$zipexec -j $filename " . $backup_sdir . 
         implode(' ' . $backup_sdir . "/",$zip_houses) .
         " 2>&1");
  mail_backup($filename,' ' . implode(' ',$zip_houses) . ' ' . $datestring);
  unlink($filename);
  foreach ($zip_houses as $delete_file) {
    unlink($backup_dir . '/' . $delete_file);
  }
}

function db_quote($str) {
  global $db;
  return $db->quote($str);
}

function mail_backup($filename, $subjline) {
  global $backup_email,$php_includes,$php_utils;
  static $init = false;
  static $crlf = "\n";
  if (!$init) {
    set_include_path(get_include_path() . PATH_SEPARATOR . $php_includes . '/' . $php_utils);
    require_once('Mail.php');
    require_once('Mail/mime.php');
    $init = true;
  }
  $hdrs = array(
              'From'    => 'Workshift backup system <webmaster@bsc.coop>',
              'Subject' => "Backup for " . $subjline);
$mime = new Mail_mime($crlf);
$mime->addAttachment($filename, 'application/octet-stream');

//do not ever try to call these lines in reverse order
$body = $mime->get();
$hdrs = $mime->headers($hdrs);

$mail =& Mail::factory('mail');
$mail->send($backup_email, $hdrs, $body);
}
?>
