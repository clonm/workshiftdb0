<html><head><title>Recover backed-up database</title></head><body>
<?php
if (!array_key_exists('backup_name',$_REQUEST)) {
  ?>
If you're restoring the spring semester for the fall semester, or 
if you've made a really horrible error, and need to get back a
previously backed-up database, you can do so here.  Your current database will be backed
up with the current date and time, so that you can recover it if you choose.
If you just need to look at an old table, which is probably what you want if neither
of the above two is your situation, you
can just go to <a href='table_edit.wrapper.php'>this link</a>.  This script
will back up your current database before it restores the old one.<p>

The backed-up databases are backed up by date -- 
year_month_day_hour_minute_second.
You should probably pick the most recent one that has what you want.

<form action='<?$_SERVER['REQUEST_URI']?>' method=POST>
<select name='backup_name'>
  <?php
$dbnames = get_backup_dbs();
print("<option>");
//go in reverse order, so most recently made database appears first
print(implode("\n<option>",array_reverse($dbnames)));

?>
</select><br>
What name should the backup of your current database be called?  (Leave it
blank to use the date):<input name='backup_ext'>
<!-- { -->
<input type=submit value='Recover backup database'></form></body></html>
<?php exit; 
}
$backup = $_REQUEST['backup_name'];
//the return value from the query has a strangely named column, so use numbers
$db->SetFetchMode(ADODB_FETCH_NUM); 
//show all tables with this starting string
$res = $db->Execute("show tables like ?",array(quote_mysqlreg('zz_archive_' . 
$backup . '_') . '%'));
//list of old tables
$old_tables = array();
//list of what tables will be named
$new_tables = array();
while ($row = $res->FetchRow()) {
  $old_tables[] = $row[0];
  $new_tables[] = substr($row[0],strlen("zz_archive_{$backup}_"));
}
//get the current tables
$res = $db->Execute("show tables");
$cur_tables = array();
while ($row = $res->FetchRow()) {
  //skip the archived tables
  if (substr($row[0],0,11) === 'zz_archive_') {
    continue;
  }
  $cur_tables[] = $row[0];
}
$db->SetFetchMode(ADODB_FETCH_ASSOC); 
$_SERVER['REQUEST_METHOD'] = 'POST';
# $db->debug = true;
//back up the current database
require('backup_database.php');

//delete all the current tables, since they've been backed up
#foreach ($new_tables as $tbl) {
#  if (!$db->Execute("drop table if exists " . bracket($tbl))) {
#    trigger_error("Error: " . $db->ErrorMsg(), E_USER_ERROR);
#  }
#}
$recover = true;
$_REQUEST['backup_ext'] = $backup;
require('backup_database.php');
//rename all the old tables to the new tables
#if (!$db->Execute("rename table " . 
#                  implode(', ',array_map('arr_rename',$old_tables)))) {
#  trigger_error("Error: " . $db->ErrorMsg(),E_USER_ERROR);
#}

//utility for the array_map
function arr_rename($tbl) {
  global $backup;
  return bracket($tbl) . " to " . 
    bracket(substr($tbl,strlen("zz_archive_{$backup}_")));
}
?>
