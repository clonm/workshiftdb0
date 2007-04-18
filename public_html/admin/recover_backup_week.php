<html><head><title>Recover backed-up week</title></head><body>
<?php
require_once('default.inc.php');
if (!array_key_exists('week_num',$_REQUEST)) {
?>
Only use this if you deleted a week by accident.  If you just want to look
at a deleted week's data, you should just <a href='table_edit.wrapper.php'>view
the table</a>.<p>

<form action='<?=$_SERVER['REQUEST_URI']?>' method=POST>
Week to recover: <select name='week_num'>
<option><?php
#';
                       ;
 $db->SetFetchMode(ADODB_FETCH_NUM); 
 $res = $db->Execute("show tables like ?",
                     array(quote_mysqlreg('zzBackupweek_') . "%"));
 $backups = array();
 while ($row = $res->FetchRow()) {
   $backups[] = substr($row[0],strlen('zzBackupweek_'));
 }
 sort($backups);
 print(join("\n<option>",$backups));
?>
</select>
<input type=submit value='Recover week'>
</form>
</body>
</html>
<?php
 exit(); }
$db->debug = true;
$week_num = $_REQUEST['week_num'];
if (is_empty($db->Execute('show tables like ?',
                          array('zzBackupweek\_' . $week_num)))) {
  trigger_error("There is no backup table to recover</body></html>",
                E_USER_ERROR);
}
$timestamp = time();
if (!$db->Execute("rename table `week_$week_num` to `{$timestamp}temp`, " .
                  "`zzBackupweek_$week_num` to `week_$week_num`, " .
                  "`{$timestamp}temp` to `zzBackupweek_$week_num`")) {
  trigger_error("Error renaming table: " . $db->ErrorMsg(),E_USER_ERROR);
}
exit("Success!  Your backed up table has been moved to the " .
     "<a href='week.php?week=0'>week table</a> and week $week_num " .
     "is now backed up in the " .
     "<a href='table_edit.wrapper.php?table_name=zzBackupweek_$week_num'>" .
     "backup table</a>.</body></html>");
?>