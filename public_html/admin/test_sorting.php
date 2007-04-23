<?php
require_once('default.inc.php');
$table_name = 'week_0';
$col_sortable = array();
$col_sortable[0] = 'pre_process_date';
$col_sortable[1] = 'pre_process_day';
$col_sortable[2] = 'pre_process_default';
$col_sortable[3] = 'pre_process_default';
$col_sortable[4] = 'pre_process_num';


//date is modified by calling function dateformat on it, all others are
//displayed in the default way, as modified by $col_styles below
$col_formats = array('date' => 'dateformat', 'day' => '', 'workshift' => '',
		   'member_name' => '',
		   'hours' => '','notes' => '');
$col_sizes = $col_formats;
//minimum size for everything is 2
foreach($col_sizes as $col => $size) {
  $col_sizes[$col] = 2;
}
//last three columns are inputs, with the following classes (input is default)
$col_styles = array('','','','member_name','hours','input');
//the third column (member_name) can be restricted on
$restrict_cols = array(3);

//rows can be added/deleted
$delete_flag = true;
$create_date = get_mod_date($table_name . '_zz_create',true,$archive);
$mod_date = get_mod_date($table_name,true,$archive);
$master_date = get_mod_date('master_shifts',true,$archive);
if ($master_date >= $create_date) {
  $body_insert = $make_weeks_form . 
    "<script type='text/javascript'>var confirm_create = true;</script>";  
  $ch_text = "<b>Abandon changes</b> and re-create table from current shift assignments?";
  if ($mod_date > $create_date) {
    $body_insert .= $ch_text;
  }
  else {
    $change_text_on_update = $ch_text;
    $body_insert .=
<<<BODYINSERT
<span id='change_text_on_update'>
<b>Re-create table</b> from current shift assignments? (No changes have been made to this table yet.)
</span>
BODYINSERT
      ;
  }
  $body_insert .= "<input type=submit value='Yes'></form>";
}
require_once("$php_includes/table_edit2.php");
?>
