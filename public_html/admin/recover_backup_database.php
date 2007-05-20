<html><head><title>Recover backed-up database</title></head><body>
<?php
require_once('default.inc.php');
$row = $db->GetRow('SELECT count(*) as `ct` FROM `elections_record` ' .
                   " WHERE unix_timestamp() < `end_date`");
if (!is_empty($row) && $row['ct']) {
  print "<h3>There are open elections!  You will need witnesses to " .
    "recover a database.  To avoid having to use witnesses, please have the " .
    "president modify the elections so that they have ended.</h3>";
  $witnesses = require_witnesses(2);
}

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

<form action='<?this_url()?>' method=POST>
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
<input type=submit value='Recover backup database'>
</form></body></html>
<?php exit; 
}
$backup = $_REQUEST['backup_name'];
$_SERVER['REQUEST_METHOD'] = 'POST';
//back up the current database
require('backup_database.php');

//recover the old database
$recover = true;
$backup_ext_log = $_REQUEST['backup_ext'];
$_REQUEST['backup_ext'] = $backup;
require('backup_database.php');
elections_log(null,'restore_database',$member_name?$member_name:$officer_name,
              array($backup_ext_log,$_REQUEST['backup_name']),
              isset($witnesses)?$witnesses:null);
?>
