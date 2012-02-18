<html><head><title>Restore dumpfile(s)</title></head><body>
<?php
if (!array_key_exists('REQUEST_URI',$_SERVER)) {
  $_FILES['userfile'] = array('tmp_name' => $argv[2], 'name' => $argv[2]);
}
if (!array_key_exists('userfile',$_FILES)) {
  //we have pretty large backups
  ?>
<form enctype="multipart/form-data" action='<?=$_SERVER['REQUEST_URI']?>' 
   method="POST">
    <!-- Name of input element determines name in $_FILES array -->
    Send this file: <input name="userfile" type="file" />
    <input type="submit" value="Send File" />
</form>
    The name of the file(s) in the zipfile are the house names, so they are very important -- keep them the same. The name of the zipfile gives the backup name, unless it is "janakjanak.zip" in which case it will be ignored.<p>

What follows has several parts.  First comes the output from unzipping.  Then 
comes the results of backing up each database you are trying to restore.  
Then comes the output, if any, of the msyql command to restore the database 
(there should be no output normally). This will take a while, so
be patient.  Any warnings or errors will stop the script, because this is a
very dangerous operation.
</body>
</html>
<?php
   exit; }
require_once('default.admin.inc.php');
print( <<<STROUTPUT
What follows has several parts.  First comes the output from unzipping, if you
uploaded a zipfile.  Then comes the result of backing up each database you are
trying to restore.  Then comes the output, if any, of the msyql command to
restore the database (there should be no output normally). This will take a while, so be patient.  Any warnings or errors will stop the script, because this is a very dangerous operation.
STROUTPUT
      );
$php_start_time = array_sum(split(' ',microtime()));
$filedata = $_FILES['userfile'];
$scratch_dir = "$php_includes/scratch";
$restore_dir = "$scratch_dir/adminrestore";
//for restore backup database -- it thinks it's in admin directory
$include_path = get_include_path();
$include_path = '../public_html/' . PATH_SEPARATOR . $include_path;
set_include_path($include_path);

if (file_exists("$restore_dir/.lock")) {
  trigger_error("Restore directory is locked.  Are you trying to run two copies " .
                "of this page?  If not, " .
                "<a href='delete_lock.php'>delete the lock file</a>",E_USER_ERROR);
}
if (file_exists($restore_dir)) {
  $dh = opendir($restore_dir);
  while ($fname = readdir($dh)) {
    if($fname=='.' || $fname=='..') continue;
    unlink("$restore_dir/$fname") || 
      janak_error("Couldn't delete $fname from $restore_dir");
  }
}
else {
  if (!file_exists($scratch_dir)) {
    mkdir($scratch_dir);
  }
  mkdir($restore_dir);
}

set_error_handler('clean_up');

$handle = fopen("$restore_dir/.lock","w");
fwrite($handle,time() . "\n");
print_r($filedata);
if (!strcasecmp(substr($filedata['name'],-4),'.zip')) {
  print("<pre>");
  system("unzip {$filedata['tmp_name']} -d $restore_dir 2>&1",$retval);
  print("</pre>");
}
else {
  $retval = rename($filedata['tmp_name'],"$restore_dir/" . basename($filedata['name']));
}
($retval === 0 || $retval === TRUE) ||
janak_error("Couldn't rename/extract files");
//this flag allows multiple-statement queries, which come below
$db->clientFlags += 65536;
//close the connection since we'll need to open it with the proper properties in
//a little while
if ($db->_connectionID) {
  $db->Close();
}
$dh = opendir($restore_dir);
while ($fname = readdir($dh)) {
  if ($fname=='.' || $fname=='..' || $fname=='.lock') {
    continue;
  }
  if (substr($fname,-4) == '.csv') {
    continue;
  }
  print("<h1>Restoring $fname</h1>");
  $server = server_from_db($fname);
  $user = user_from_db($fname);
  $db->Connect($server,$user,$url_array['pwd'],"$db_basename$fname");
  $row = $db->GetRow("select version() as vs");
  $temp = $row['vs'];
  $temp = explode('.',$temp);
  $MYSQL_VERSION = 10000*$temp[0];
  $MYSQL_VERSION += 1000*$temp[1];
  $temp = explode('-',$temp[2]);
  $MYSQL_VERSION += $temp[0];
  $MYSQL_VERSION = 0;
  $db->debug = true;
#  $db->clientFlags += 65536;
#  $db->debug = true;
  // Return associative arrays
  $db->SetFetchMode(ADODB_FETCH_ASSOC); 
  //enable transactions -- MyISAM doesn't have transactions
  $db->Execute("set table_type = 'InnoDB'");
  //are there any tables anyway?
  if (!is_empty($db->GetRow("show tables"))) {
    $_REQUEST['backup_ext'] = '';
    print("<h3>Backing up old $fname</h3>");
    require("../public_html/admin/backup_database.php");
  }
  $db->debug = false;
  $retval = system("mysql -u" .escapeshellarg($user) . " -p" .
                   escapeshellarg($url_array['pwd']) . " -h" . escapeshellarg($server) .
                   " " . escapeshellarg("$db_basename$fname") .
                   " < " . escapeshellarg($restore_dir) . "/" .
                   escapeshellarg($fname) . " 2>&1");
  if ($filedata['name'] !== "janakjanak.zip") {
    $archive = $archive_pre . substr($filedata['name'],0,-4) . "_";
    $db_props = array();
    $db_props['semester_start'] = get_static('semester_start');
    $restore_fetch = $db->SetFetchMode(ADODB_FETCH_NUM);
    $mod_row = $db->_Execute("select max(`mod_date`) " .
                             "from " . bracket($archive . 'modified_dates'));
    $db_props['mod_date'] = $mod_row->fields[0];
    $db_props['cur_week'] = get_cur_week();
    $wanted_row = $db->_Execute("select count(*) from " .
                                bracket($archive . 'wanted_shifts'));
    $db_props['num_wanted'] = $wanted_row->fields[0];
    $db_props['num_assigned'] = 0;
    foreach ($days as $day) {
      $master_row = $db->GetRow("select count(*) from " .
                                bracket($archive . 'master_shifts') .
                                " where `$day` is not null and `$day` != ?",
                                array($dummy_string));
      $db_props['num_assigned'] += $master_row[0];
    }
    //did the user not name this him/herself
    $db_props['autobackup'] = false;
    $db_props['owed_default'] = get_static('owed_default');
    //store these parameters in archive data
    $db->Execute("insert into `GLOBAL_archive_data` (`archive`," .
                 "`semester_start`,`mod_date`,`cur_week`,`num_wanted`, " .
                 "`num_assigned`,`autobackup`,`creation`,`owed_default`) " .
                 "VALUES (?,?,?,?,?,?,?,NOW(),?)",
                 array(substr($filedata['name'],0,-4),
                       $db_props['semester_start'],
                       $db_props['mod_date'],$db_props['cur_week'],$db_props['num_wanted'],
                       $db_props['num_assigned'],$db_props['autobackup'],
                       $db_props['owed_default']));
    $db->SetFetchMode($restore_fetch);
  }
  $retval = !$retval;
  $db->Close();
  if ($retval) {
    print("<h4>Restore succeeded!</h4>");
  }
  else {
    janak_error("Couldn't restore $fname");
  }
}
clean_up(null,null,null,null,null);

function clean_up($errno,$errstr,$errfile,$errline,$errcontext) {
  global $handle, $restore_dir,$php_start_time;
  if ($errfile) {
    print("<b>Error:</b>" . $errstr . " in $errfile on line " . __LINE__ . 
          "<p>\n");
  }
  if ($handle) {
    fclose($handle) ||
      print("<b>Warning</b>: Couldn't close filehandle for lockfile in " .
            __FILE__ . " on line " . __LINE__ . "<br>\n");
  }
  if ($dh = opendir($restore_dir)) {
    while ($fname = readdir($dh)) {
      if($fname=='.' || $fname=='..') continue;
      unlink("$restore_dir/$fname") || 
        print("<b>Warning</b>: Couldn't delete $fname from $restore_dir in " .
            __FILE__ . " on line " . __LINE__ . "<br>\n");
    }
  }
  else {
    print("<b>Warning</b>: Couldn't open $restore_dir in " .
            __FILE__ . " on line " . __LINE__ . "<br>\n");
  }
  rmdir($restore_dir) ||
    print("<b>Warning</b>: Couldn't delete $restore_dir in " .
            __FILE__ . " on line " . __LINE__ . "<br>\n");
  $arr = split(' ',microtime());
  print("Page took " . round(array_sum(split(' ',microtime()))-$php_start_time,2) . 
        " seconds to generate<br>\n");
janak_errhandler($errno,$errstr,$errfile,
                 $errline,$errcontext);
  exit;
} 

function server_from_db($db) {
  global $url_array;
  return $url_array['server'];
#  return $db . '.test.usca.org';
}

function user_from_db($db) {
  global $url_array;
  return $url_array['user'];
}
?>
