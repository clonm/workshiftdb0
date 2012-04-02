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
$require_user = array('workshift','president');
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
Your backup string (leave blank to use timestamp; limit of 30 characters):
  <input name='backup_ext' size=30 maxlength=30 value=''><br/>
<input type=submit value=Backup></form>
<?php 
exit; 
} 
$backup_ext = $_REQUEST['backup_ext'];
//no backup given?  Use the date
if (!$backup_ext) {
  $backup_ext = user_time(null,'Y_m_d_H_i_s');
}
else if ($backup_ext{0} == '"' && $backup_ext{count($backup_ext)-1} == '"') {
  //stupid managers might put quotes around
  $backup_ext = substr($backup_ext,1,count($backup_ext)-2);
}
//check for funky characters
if (preg_match('/\/|\\|:|\*|\?|"|<|>|`|\|/',$backup_ext)) {
  exit("Your backup name has illegal characters. You can't use any of the " .
       "characters `\\/:*?\"<>|. Please go back and try again!");
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
    $temp = $backup_ext . "-$ctr";
    $ctr++;
  }
}
$backup_ext = $temp;

//mysql has a table length limit of 64, so be conservative about the length
//of table names we might have to deal with
if (strlen($backup_ext) > 30) {
  if ($ctr == 1) {
    janak_error("Please enter a backup name of 30 characters or fewer.");
  }
  else {
    janak_error("You already had a backup with this name.  Please choose a " .
                "new name.");
  }
}
echo "backup name " . escape_html($backup_ext) . "<p>";

  $oldfetch = $db->SetFetchMode(ADODB_FETCH_NUM);

if (!$recover) {
  //put this backup's information into the archive_data table, so it can
  //be seen.
  //this array has all salient information
  $db_props = array();
  $db_props['semester_start'] = get_static('semester_start');
  $mod_row = $db->_Execute("select max(`mod_date`) " .
    "from " . bracket('modified_dates'));
  $db_props['mod_date'] = $mod_row->fields[0];
  $db_props['cur_week'] = get_cur_week();
  $wanted_row = $db->_Execute("select count(*) from " .
    bracket('wanted_shifts'));
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
  $db_props['autobackup'] = !$_REQUEST['backup_ext'];
  $db_props['owed_default'] = get_static('owed_default');
  //store these parameters in archive data
  $db->Execute("insert into `GLOBAL_archive_data` " .
    "(`archive`,`semester_start`,`mod_date`,`cur_week`,`num_wanted`, " .
    "`num_assigned`,`autobackup`,`creation`,`owed_default`) " .
    "VALUES (?,?,?,?,?,?,?,NOW(),?)",
      array($backup_ext,$db_props['semester_start'],
      $db_props['mod_date'],$db_props['cur_week'],$db_props['num_wanted'],
      $db_props['num_assigned'],$db_props['autobackup'],
      $db_props['owed_default']));
}

//tell user what's going on, in a hidden div, so admin can see if necessary
  echo "<div style='display: none' id=" .
  ($recover?'recover':'backup') . "_messages>";
//to see what goes on, turn on debugging
  $old_debug = $db->debug;
  $db->debug = true;
  //we're getting the table names, but now there is only one column, so we can
  //use numbers to index into it
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
    //don't backup GLOBAL tables
    if (substr($tbl,0,strlen("GLOBAL")) === "GLOBAL") {
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
      $bnewtbl = bracket(substr($tbl,strlen("$archive_pre{$backup_ext}_")));
    }
    $db->Execute("drop table if exists $bnewtbl");
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
$db->Execute($sqlcode);
    $db->StartTrans();
$db->Execute("insert into $bnewtbl select * from $btbl");
    $db->CompleteTrans();
  }
  if ($recover) {
    $db->Execute("delete from `GLOBAL_archive_data` where `archive` = ?",
      array($backup_ext));
  }
  echo "</div>";
echo "<h4>" . ($recover?"Restore":"Backup") . " succeeded!</h4>";
  echo "<input type=submit value='View sql commands issued, if you care' " .
  "onclick=\"document.getElementById('" .
  ($recover?'recover':'backup') . "_messages').style.display = '';\"><br/>";
  $db->debug = $old_debug;
  $db->SetFetchMode($oldfetch);
  //no reload privilege, at least with idologic.
  //  $db->Execute("flush tables");
?>
