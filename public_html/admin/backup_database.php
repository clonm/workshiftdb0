<?php
require_once('default.inc.php');
?>
<html><head><title>Backup database</title></head><body>
<?php
//this gives a way for managers to backup their databases, both for emergency
//recovery and every semester, so they can refer back to them.
//The tables are backed up in the same database.  Note that this means
//that any other tables which this user might not usually have access
//to will also be backed up.  That should be ok.  Unfortunately, it
//also means that he or she can then restore a backup on top of the
//newer tables.  Too bad.

//are we prompting for a backup string?
if (!array_key_exists('backup_ext',$_REQUEST)) { ?>
 <form action='<?=this_url()?>' method=POST>
Your tables will be backed up with a timestamp (or your string, if you give one) 
prepended.  If you are archiving a finished semester, you might want to put in 
the string 2005_fall or something like that so that you can more easily identify
that this is <b>the</b> archive for that semester.  For normal backups, the 
timestamp is probably what you want.
<br/>
Your backup string (leave blank to use timestamp):
  <input name='backup_ext' value=''><br/>
<input type=submit value=Backup></form>
<?php 
exit; 
} 
$retval = true;
$backup_ext = $_REQUEST['backup_ext'];
//no backup given?  Use the date
if (!$backup_ext) {
  $backup_ext = date('Y_m_d_H_i_s');
}
else if ($backup_ext{0} == '"' && $backup_ext{count($backup_ext)-1} == '"') {
  //stupid managers might put quotes around
  $backup_ext = substr($backup_ext,1,count($backup_ext)-2);
}
//does this backup already exist?  Put a number at the end.
$temp = $backup_ext;
if (!isset($recover) || !$recover) {
  $recover = false;
  echo "Backing up with ";
}
else {
  echo "Restoring from ";
}
$ctr = 1;
if (!$recover) {
  while (table_exists($archive_pre . "{$temp}_house_list")) {
    $temp = $backup_ext . "_$ctr";
    $ctr++;
  }
}

$backup_ext = $temp;
//mysql has a table length limit of 64, so be conservative about the length
//of table names we might have to deal with
if (strlen($backup_ext) > 35) {
  janak_error("Please enter a backup name of 35 characters or fewer.");
}
//I can't handle tables with backticks -- it's just too confusing
if (strpos($backup_ext,'`') !== false) {
  janak_error("You can't use ` in your backup name.  Try a different name.");
}
janak_fatal_error_reporting(E_ALL & ~E_NOTICE);
//check for funky characters
if (preg_match('/\/|\\|:|\*|\?|"|<|>|\|/',$backup_ext)) {
  janak_error("Your backup name has funky characters. " 
    . "Your backup might fail.  Try a different name if it does.",
                E_USER_NOTICE);
}
janak_fatal_error_reporting(E_ALL);
echo "backup name " . escape_html($backup_ext) . "<p>";
//tell user what's going on, in a hidden div, so admin can see if necessary
  echo "<div style='display: none' id=backup_messages>";
  $old_debug = $db->debug;
  $db->debug = true;
  //we're getting the table names, but now there is only one column, so we can
  //use numbers to index into it
  $oldfetch = $db->fetchMode;
  $db->SetFetchMode(ADODB_FETCH_NUM);
  $tables = $db->Execute("show tables like ?",
                         array(($recover?quote_mysqlreg("$archive_pre{$backup_ext}_"):'') . 
                         '%'));
  //return code
  $ret = true;
  //loop through all tables
  while ($tbl_info = $tables->FetchRow()) {
    $tbl = $tbl_info[0];
    //don't backup previously-backed-up tables
    if (!$recover && substr($tbl,0,11) === $archive_pre) {
      continue;
    }
    $btbl = bracket($tbl);
    if (!$recover) {
      $bnewtbl = bracket($archive_pre . $backup_ext . '_' . $tbl);
    }
    else {
      $bnewtbl = substr($tbl,strlen("$archive_pre{$backup_ext}_"));
    }
#    echo "Deleting old table " . escape_html($bnewtbl) . " (if it exists)<br>\n";
    $ret &= $db->Execute("drop table if exists $bnewtbl");
    $res = $db->Execute("show create table $btbl");
    $sqlcode = $res->fields[1];
    $startlen = strlen("CREATE TABLE " . $btbl);
    $sqlcode = "CREATE TABLE " . $bnewtbl . substr($sqlcode,$startlen);
#    echo "Creating table " . $bnewtbl . "<br>\n";
    $ret &= $db->Execute($sqlcode);
    $db->StartTrans();
    // this is not needed since we're dropping above
    //    $db->Execute("delete from $bnewtbl where 1");
#    echo "Copying data from " . escape_html($btbl) . " into " .
      escape_html($bnewtbl) . "<br>\n";
    $ret &= $db->Execute("insert into $bnewtbl select * from $btbl");
    $db->CompleteTrans();
  }
  echo "</div>";
  if ($ret) {
    echo "<h4>" . ($recover?"Restore":"Backup") . " succeeded!</h4>";
  }
  else {
    echo "<h4>" . ($recover?"Restore":"Backup") . " had problems!  Try again.</h4>";
  }
  echo "<input type=submit value='View sql commands issued, if you care' " .
  "onclick=\"document.getElementById('backup_messages').style.display = '';\">";
  $db->debug = $old_debug;
  $db->SetFetchMode($oldfetch);
  //no reload privilege
  //  $db->Execute("flush tables");
?>
