<?php
if (!array_key_exists('table_name',$_REQUEST)) {
  ?>
<html><head><title>View/Edit any table</title></head><body>
You can use this page to view/edit any table.  However, you should only use it 
if something has gone wrong -- there is no reason to use this instead of the 
normal pages.  There is less customization here, so you are taking a risk by
viewing tables in this way.  Please email <?=admin_email()?> if you need to use this.
<form action='<?=$_SERVER['REQUEST_URI']?>' method=POST>
<select name='table_name'>
<?php 
require_once('default.inc.php');
 $db->SetFetchMode(ADODB_FETCH_NUM); 
 $res = $db->Execute("show tables");
 while ($tbl_info = $res->FetchRow()) {
   $tbl = $tbl_info[0];
   if (!access_table($tbl)) {
     continue;
   }
   echo "<option>$tbl\n";
 }
?>
</select><br>
<label for='view_only'><input type=checkbox id='view_only' name='view_only' checked>View only</label><br>
<input type=submit value=View/Edit Table>
</form>
</body>
</html>
<?php
    exit(); }
require_once('default.inc.php');
$table_name = $_REQUEST['table_name'];
if (!access_table($table_name)) {
  exit("You cannot view/edit table $table_name");
}
$res = $db->Execute("show columns from `$table_name`");
if (!$res) {
  exit("Table $table_name doesn't seem to exist");
}
$col_names = array();
$read_only = true;
$delete_flag = false;
while ($row = $res->FetchRow()) {
  $col = $row['Field'];
  if ($col === 'autoid') {
    unset($read_only);
    $delete_flag = true;
    continue;
  }
  $col_names[] = $col;
  if ($col === 'member_name') {
    $col_styles[] = 'member_name';
  }
  else {
    $col_styles[] = 'input';
  }
}

#var_dump($read_only);
if (array_key_exists('view_only',$_REQUEST)) {
  require_once("$php_includes/table_view.php");
}
else {
  require_once("$php_includes/table_edit.php");
}
?>
