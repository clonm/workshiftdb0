<html><head><title>Insert shifts into master_shifts</title></head><body>
<?php 
require_once('default.inc.php');
create_master_shifts();

$db->SetFetchMode(ADODB_FETCH_NUM); 
$res = $db->Execute('show columns from `master_shifts`');
$db->SetFetchMode(ADODB_FETCH_ASSOC);
$cols = array();
while ($row = $res->FetchRow()) {
  if ($row[0] === 'autoid') {
    continue;
  }
  $cols[] = $row[0];
}
  $dummy_string = get_static('dummy_string');
  if (!$dummy_string) {
    $dummy_string = 'XXXXX';
    set_static('dummy_string',$dummy_string);
  }
if (!array_key_exists('insert_rows',$_REQUEST)) { 
?>
If you would like to import master_shifts information from another data format,
you can do it here.<br>
Enter rows, one per line, with entries delimited by tabs (don't put in extra 
spaces).  You can leave entries blank, except for the shift name, but remember to 
put the appropriate number of tabs in.  To import from Excel, save your 
spreadsheet as Text (tab delimited), then open the file in a text editor (Notepad)
and copy the text into the box below (note that you can't enter tabs in the text box).
If you want to say that a shift is not done on a particular day (for instance, 
bathroom clean is not done on Saturdays), put <?=$dummy_string?> in for it on that day.<br>
Enter time in 24-hour time if you can, so 6 pm is 18:00.<br>
After you click submit, you'll be taken to another page where you'll have to
hit submit again.<br>
The first row should have the column names.  Any column names that you give which do not
appear here will be ignored, so make sure you have the correct names:<br>
<?=implode("; ",$cols)?><br>
<form action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
<textarea cols=80 rows=40 name='insert_rows'></textarea>
<input type=submit value=Submit></form></body></html>
                                   <?php exit; }
#';
$temp_rows = explode("\n",$_REQUEST['insert_rows']);
$row_count = 0;
$missing_cols = array();
foreach ($temp_rows as $row) {
  if (!strlen($row)) {
    continue;
  }
  $row = explode("\t",$row);
  //getting rid of quotes
  foreach ($row as $key => $cell) {
    //start and end whitespace is always accidental, I hope
    if (strlen($cell) && $cell{0} == '\\') {
      $cell = stripslashes($cell);
      eval("\$row[\$key] = $cell;");
    }
    $row[$key] = ltrim(rtrim($row[$key]));
  }
  if (!$row_count++) {
    $col_count = count($row);
    $missing_cols = array_diff(array_merge($days,array('Weeklong')),
                               $row);
  }
  elseif (count($row) !== $col_count) {
    exit("Error: Row $row_count does not have $col_count columns");
  }
  $cells[] = $row;
}
?>
<form action='update_db.php' method='POST'>
<input type=hidden name='js_flag' value=0>
<input type=hidden name='table_name' value='master_shifts'>
<input type=hidden name='num_rows' value='<?=count($cells)-1?>'>
<input type=hidden name='num_cols' value='<?=count($cells[0])?>'>
<?php
foreach ($cells[0] as $col) {
  echo "<input type=hidden name='col_names[]' value='" . 
  htmlspecialchars($col,ENT_QUOTES) . "'>\n";
}
foreach ($missing_cols as $col) {
  echo "<input type=hidden name='col_names[]' value='" . 
  htmlspecialchars($col,ENT_QUOTES) . "'>\n";
}  
for ($ii = 1; $ii < count($cells); $ii++) {
  echo "<input type=hidden name='autoid-" . ($ii-1) . "' value=''>\n";
  for ($jj = 0; $jj < count($cells[$ii]); $jj++) {
    echo ("<input type=hidden name='cell-" . ($ii-1) . "-{$jj}' value='" . 
      htmlspecialchars($cells[$ii][$jj],ENT_QUOTES) . "'>");
  }
  foreach ($missing_cols as $col) {
    echo ("<input type=hidden name='cell-" . ($ii-1) . '-' . ++$jj . 
          "' value='" . escape_html($dummy_string) . "'>");
  }
  echo "\n";
}
?>
<input type=submit value=Submit data>
</form></body></html>

