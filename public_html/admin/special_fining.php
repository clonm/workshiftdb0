<?php
require_once('default.inc.php');
$col_names = array('member_name');
$col_formats = array('member_name' => null);
$col_styles = array(null);
$res = $db->Execute("select `week` from `{$archive}fining_periods`");
$ii = 1;
while ($row = $res->FetchRow()) {
  $col_names[] = 'Fining period ' . $ii . ' (week ' . $row['week'] . ')';
  $col_formats["fine_week_$ii"] = null;
  $col_styles[] = 'input';
  $ii++;
}
$restrict_cols = array(0);
$table_name = "{$archive}special_fining";
$order_exp = 'member_name';
$delete_flag = false;
$body_insert = "-1 means that this member has the usual fining week" .
" for that fining period.  Leave it blank to excuse the member completely.";
require_once($php_includes . "/table_edit.php");
?>