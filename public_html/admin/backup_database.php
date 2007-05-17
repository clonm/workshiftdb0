<?php
//this gives a way for managers to backup their databases, both for
//emergency recovery and every semester, so they can refer back to
//them.  The tables are backed up in the same database.  Note that
//this means that any other tables which this user might not usually
//have access to will also be backed up.  That should be ok.
//Unfortunately, it also means that he or she can then restore a
//backup on top of the newer tables.  Too bad.  This script also does
//the restoring (it's included from the restore script).

//printing is suppressed because I don't want to put out html headers
//when this page is included, and it's too much trouble to do the
//cases.
$body_insert = '';
require_once('default.inc.php');

//are we prompting for a backup string?
if (!array_key_exists('backup_ext',$_REQUEST)) { ?>
<html><head><title>Backup database</title></head><body>
<?php
   print $body_insert;
?> 
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
$backup_ext = $_REQUEST['backup_ext'];
//no backup given?  Use the date
if (!$backup_ext) {
  $backup_ext = date('Y_m_d_H_i_s');
}
else if ($backup_ext{0} == '"' && $backup_ext{count($backup_ext)-1} == '"') {
  //stupid managers might put quotes around
  $backup_ext = substr($backup_ext,1,count($backup_ext)-2);
}
$temp = $backup_ext;
//recover flag can only be set when this script is include()d
if (!isset($recover) || !$recover) {
  $recover = false;
  echo "Backing up with ";
}
else {
  echo "Restoring from ";
}
//does this backup already exist?  Put a number at the end.
$ctr = 1;
//note that archive_pre (zz_archive_) starts every backup name
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
//this is silly -- we shouldn't error on this -- informational messages
//should be prettier.  Oh well.
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
//to see what goes on, turn on debugging
  $old_debug = $db->debug;
  $db->debug = true;
  //we're getting the table names, but now there is only one column, so we can
  //use numbers to index into it
  $oldfetch = $db->fetchMode;
  $db->SetFetchMode(ADODB_FETCH_NUM);
//show all tables (or, if recovering, show all tables in backup)
  $tables = $db->Execute("show tables like ?",
                         array(($recover?
                                quote_mysqlreg("$archive_pre{$backup_ext}_"):
                                '') .'%'));
  //return code
  $ret = true;
  //loop through all tables
  while ($tbl_info = $tables->FetchRow()) {
    $tbl = $tbl_info[0];
    //don't backup previously-backed-up tables
    if (!$recover && substr($tbl,0,strlen($archive_pre)) === $archive_pre) {
      continue;
    }
    //we're going to need the bracketed table over and over
    $btbl = bracket($tbl);
    if (!$recover) {
      //and the new table name too
      $bnewtbl = bracket($archive_pre . $backup_ext . '_' . $tbl);
    }
    else {
      //the new table name if we're recovering
      $bnewtbl = substr($tbl,strlen("$archive_pre{$backup_ext}_"));
    }
    $ret &= $db->Execute("drop table if exists $bnewtbl");
    //this code is now no longer strictly necessary -- our version of
    //mysql has "create table like" command.  But who knows what the
    //next service we go to will have.  Until everyone's upgraded,
    //better to play it safe.  What we're doing here is creating the
    //new table just like the old table.  So we find out the command
    //to create the old table, then alter it.
    $res = $db->Execute("show create table $btbl");
    $sqlcode = $res->fields[1];
    $startlen = strlen("CREATE TABLE " . $btbl);
    $sqlcode = "CREATE TABLE " . $bnewtbl . substr($sqlcode,$startlen);
    $ret &= $db->Execute($sqlcode);
    $db->StartTrans();
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
  //no reload privilege, at least with idologic.
  //  $db->Execute("flush tables");
?>
