<?php error_reporting(E_ALL);
$body_insert = '';
require_once('default.inc.php');
$time = microtime(1);
$order_exp = bracket("category") . ", " . bracket("points") . " DESC, " . bracket("app_number");
#$week_num = $_GET['week'];
#if (strlen($week_num) == 1) {
#  $week_num = "0$week_num";
#}
$table_name = "points";
$col_formats = array('Rank' => 'number', 'member_name' => '', 'category' => '', 'points' => '',
		     'app_number' => '','squat_room' => '', 'Previous Room' => '', 'Gender' => '');
$col_names = array_keys($col_formats);
$col_sizes = array_flip($col_names);
$jj = 0;
foreach ($col_sizes as $col => $size) {
  $col_sizes[$jj++] = 0;
}
$col_types = $col_sizes;
foreach ($col_types as $col => $type) {
  $col_types[$col] = 'string';
}

$col_virtuals = array_flip($col_names);
foreach ($col_virtuals as $col => $virt) {
  if ($col === 'Rank') {
    $col_virtuals[$col] = 1;
  }
  else {
    $col_virtuals[$col] = 0;
  }
}
$restrict_cols = array(1);

$col_styles = array('','','','','','','','');

function number($str, $rownum, $colnum) {
  return array($rownum+1,2);
}
#$delete_flag = false;
#$update_db_prefix = "../workshift/admin/";
require_once("$php_includes/table_view.php");
?>
