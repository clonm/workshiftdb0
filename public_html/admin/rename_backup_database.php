<?php
require_once('default.inc.php');
if (!array_key_exists('backup_name',$_REQUEST) ||
    !isset($_REQUEST['new_name'])) {
  $backups = get_backup_dbs();
  print "<form method=get action='" . this_url() . "'>";
  print "Backup: <select name='backup_name'>\n";
  foreach ($backups as $opt) {
    print "<option " . escape_html($opt) . ">" . escape_html($opt) . "\n";
  }
  print "</select>&nbsp;&nbsp;";
  print "<input name='new_name' size=20>\n";
  print "<input type=submit value='Rename'>\n</form>";
  exit;
}

$backup = $_REQUEST['backup_name'];
$new_name = $_REQUEST['new_name'];
$res = $db->Execute("show tables like ?",array(quote_mysqlreg($archive_pre . 
$new_name . '_') . '%'));
if (!is_empty($res)) {
  exit("Backup " . escape_html($new_name) . " already exists.  Try a different one.");
}
if ($new_name{0} == '"' && $new_name{count($new_name)-1} == '"') {
  $new_name = substr($new_name,1,count($new_name)-2);
}
//mysql has a table length limit of 64, so be conservative about the length
//of table names we might have to deal with
if (strlen($new_name) > 35) {
  exit("Please enter a backup name of 35 characters or fewer.</body></html>");
}
//I can't handle tables with backticks -- it's just too confusing
if (strpos($new_name,'`') !== false) {
  exit("You can't use ` in your backup name.  Try a different name.");
}
//check for funky characters
if (preg_match('/\/|\\|:|\*|\?|"|<|>|\|/',$new_name)) {
  exit("Your backup name has funky characters.  Don't use them.");
}

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
  $old_tables[] = array($row[0],
                        $archive_pre . $new_name . substr($row[0],strlen("zz_archive_{$backup}"))
                        );
}
$db->SetFetchMode(ADODB_FETCH_ASSOC); 
$db->debug = true;
//rename all the old tables to the new tables
$exec_str = "rename table ";
foreach ($old_tables as $arr) {
  $exec_str .= bracket($arr[0]) . " to " . bracket($arr[1]) . ", ";
}
$exec_str = substr($exec_str,0,-2);
if (!$db->Execute($exec_str)) {
  janak_error("Couldn't rename tables");
}
?>
