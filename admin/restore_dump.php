<html><head><title>Restore dumpfile(s)</title></head><body>
<?php
if (!array_key_exists('REQUEST_URI',$_SERVER)) {
  $_FILES['userfile'] = array('tmpname' => $argv[2], 'name' => $argv[2]);
}
if (!array_key_exists('userfile',$_FILES)) {
  ?>
<form enctype="multipart/form-data" action='<?=$_SERVER['REQUEST_URI']?>' 
   method="POST">
    <!-- Name of input element determines name in $_FILES array -->
    Send this file: <input name="userfile" type="file" />
    <input type="submit" value="Send File" />
</form>
<b>ALL</b> databases in the zipfile are restored, so if you do not want to
restore all files, create a new zipfile (the name does not matter) with only 
the ones you want restored.  The names of the files in the zipfile give the 
database, so they are very important -- keep them the same.<p>

What follows has several parts.  First comes the output from unzipping.  Then 
comes the results of backing up each database you are trying to restore.  
Then comes the output, if any, of the msyql command to restore the database 
(there should be no output normally).  Then everything (but the unzip) is 
repeated for each file in the zipfile.  This will take a while, so
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
restore the database (there should be no output normally).  Then everything (but
the unzip) is repeated for each file in the zipfile.  This will take a while, so
be patient.  Any warnings or errors will stop the script, because this is a
very dangerous operation.
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
      trigger_error("Couldn't delete $fname from $restore_dir",E_USER_ERROR);
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
  restore_db($restore_dir,$fname) ||
    janak_error("Couldn't restore $fname");
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
  return 'localhost';
#  return $db . '.test.usca.org';
}

function user_from_db($db) {
  global $url_array;
  return $url_array['user'];
}

function restore_db($restore_dir,$fname) {
  global $db,$url_array, $php_includes, $USE_MYSQL_FEATURES, $archive_pre;
  $server = server_from_db($fname);
  $user = user_from_db($fname);
  $db->Connect($server,$user,$url_array['pwd'],$fname);
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
  $_REQUEST['backup_ext'] = '';
  print("<h3>Backing up old $fname</h3>");
  require("../public_html/admin/backup_database.php");
  $db->debug = true;
  if ($MYSQL_VERSION >= 41000) {
    //this command will screw up the mysql connection, but it's ok because
    //we're about to close and re-open it
    $retval = $db->Execute(file_get_contents("$restore_dir/$fname"));
  }
  else {
    $retval = system("mysql -u" .escapeshellarg($user) . " -p" .
                     escapeshellarg($url_array['pwd']) . " -h" . escapeshellarg($server) .
                     " " . escapeshellarg($fname) . 
                     " < " . escapeshellarg($restore_dir) . "/" . 
                     escapeshellarg($fname) . " 2>&1");
    $retval = !$retval;
  }
  $db->Close();
  if ($retval) {
    print("<h4>Restore succeeded!</h4>");
  }
  return $retval;
}
?>
