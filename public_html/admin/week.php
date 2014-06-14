<?php
$body_insert = '';
require_once('default.inc.php');
$week_num = null;
//There are a few conditions under which we'll redo the page
$redo_flag = true;
//if the week wasn't inputted, show the form
if (array_key_exists('week',$_REQUEST)) {
  $week_num = $_REQUEST['week'];
  //don't redo if the week is numeric, and it's a non-negative
  //integer, or a string of digits (i.e., a non-negative integer
  //string)
  if (is_numeric($week_num) &&
      ((is_integer($week_num) && $week_num >= 0) ||
       ctype_digit($week_num))) {
    $week_num += 0;
    $redo_flag = false;
  }
}
if ($redo_flag) {
?>
  <html><head><title>Get week</title></head><body>
<?=$body_insert?>
<?php
     if (isset($week_num)) {
       print "'" . escape_html($week_num) . "' is not a valid week "
         . "number -- it must be a whole number, like 0 or 17.<br/>\n";
     }
?>
<form method='get' action='<?=this_url()?>'>
     <input type='submit' value='Get week: '><input name='week' size='3'>
</form></body></html> 
<?php
exit;
  }
// Displays a week's input sheets for editing
require_once('default.inc.php');
//order by date, then day, then shift, then name 
//(although the last isn't necessary)
$order_exp = "`date`, `day`, `workshift`,`member_name`";
//this could change depending on convention for naming tables
$table_name = "${archive}week_$week_num";
//does this table even exist?
$make_weeks_form = "<form action='make_weeks.php' method='GET'  onsubmit='" .
    "if (typeof(confirm_create) != \"undefined\") {" .
"return confirm(\"Are you sure?\")}; return true;'>" .
    "<input type=hidden name='start_week' value=$week_num>" .
    "<input type=hidden name='end_week' value=$week_num>" .
    "<input type=hidden name='overwrite' value='1'>" .
    "<input type=hidden name='archive' value='" . escape_html($archive) .
    "'>";
    if (!table_exists("week_$week_num")) {
?>
<html><head><title>Week <?=$week_num?> does not exist yet</title></head>
<body>
<?=$body_insert?>

<?php
   if (!table_exists('house_list')) {
     exit("The house list does not exist yet! Email " . admin_email() . "<p>");
   }
 if (!table_exists('master_shifts')) {
   exit("The list of shifts does not exist yet!  Email " . admin_email() . "<p>");
 }
?>
<?=$make_weeks_form?>
Week <?=$week_num?> does not exist yet.  Create it now?
<input class=button type=submit value='Yes'>
</form>
</body>
</html>
<?php
exit;
 }


//date is modified by calling function dateformat on it, all others are
//displayed in the default way, as modified by $col_styles below
$col_formats = array('date' => 'dateformat', 'day' => '', 'workshift' => '',
		   'member_name' => '',
		   'hours' => '','notes' => '');
//moved out of conditional above
  $col_formats['start_time'] = 'timeformat';
  $col_formats['end_time'] = 'timeformat';
//last three columns are inputs, with the following classes (input is default)
$col_styles = array('','','','member_name','hours','input','time','time');
$col_sortable = array();
$col_sortable[0] = 'pre_process_date';
$col_sortable[1] = 'pre_process_day';
$col_sortable[2] = 'pre_process_default';
$col_sortable[3] = 'pre_process_default';
$col_sortable[4] = 'pre_process_num';
$col_sortable[6] = 'pre_process_time';
$col_sortable[7] = 'pre_process_time';
//the third column (member_name) can be restricted on
$restrict_cols = array(3);

if (get_static('online_signoff',null)) {
  $col_formats['online_signoff'] = array("if(`online_signoff`,date_format(`online_signoff`,'%r %a %c/%e'),if(`online_signoff` = 0,0,`online_signoff`)) as `online_signoff`",'online_signoff_format');
  $col_formats['verifier'] = '';
  $col_styles[] = 'checkbox';
  $col_styles[] = '';
  $col_sortable[8] = 'pre_process_datetime';
  $col_sortable[9] = 'pre_process_default';
  $restrict_cols[] = 9;

  function online_signoff_format($str,$ii,$jj) {
    if ($str) {
      return array($str,strlen($str)*2);
    }
    return array("<input type='checkbox'" . ($str === '0'?' checked':'') . " defaultValue='' value=0 " . generic_end($ii,$jj),1);
  }
}
//minimum size for everything is 2
$col_sizes = array_fill(0,count($col_formats),2);
//notes should expand to fill rest of table
$col_sizes[5] = '*';
//rows can be added/deleted
$delete_flag = true;
//changed by Janak 7 May 2011 because help text not showing on week pages
if (!isset($body_insert)) {
  $body_insert = '';
}
if (!$archive) {
  $create_date = get_mod_date($table_name . '_zz_create',true);
  $mod_date = get_mod_date($table_name,true);
  $master_date = get_mod_date('master_shifts',true);
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
  $body_insert .= "<input class=button type=submit value='Yes'></form>";
}
else if ($mod_date == $create_date) {
  if (!isset($body_insert)) {
    $body_insert = '';
  }
  $body_insert .= <<<BLANK_OUT_NAMES
<div class='update_hide'>
<input id='unmod_blank_week' class=pushbutton type=submit value='Make this week a temp week (blank out all member names)'
onclick='blank_names()'>
<script type='text/javascript'>

    function blank_names(silent_flag) {
      if (!silent_flag && !confirm("Are you sure you want to erase all member names for this week?")) {
        return false;
      }
      for (var ii = rows_array.length-1; ii>=0; ii--) {
        prev_val = get_value(rows_array[ii].cells[3].firstChild);
        if (prev_val && prev_val.length) {
          set_value(rows_array[ii].cells[3].firstChild,'');
          default_change_handler(rows_array[ii].cells[3].firstChild);        
          change_handler(rows_array[ii].cells[3].firstChild);
        }
      }
      document.getElementById('unmod_blank_week').style.display = 'none';
      return false;
    }
</script>
</div>
BLANK_OUT_NAMES
    ;
}
}
$tot_hours = $db->GetRow("select ifnull(sum(`hours`),0) as `sum` from " . 
                         bracket($table_name));
$ass_hours = $db->GetRow("select ifnull(sum(`hours`),0) as `sum` from " .
                         bracket($table_name) . " where length(`member_name`)");
$un_hours = $db->GetRow("select ifnull(sum(`hours`),0) as `sum` from " .
                        bracket($table_name) . " where (length(`member_name`) = 0 or " .
                        "`member_name` is null)");
$body_insert .= "<span id='week_assigned_hours'>" . 
escape_html($ass_hours['sum']) . "</span> hours are assigned this week, " .
"<span id='week_unassigned_hours'>" . 
escape_html($un_hours['sum']) . "</span> hours are unassigned, and there are " .
"<span id='week_total_hours'>" .
escape_html($tot_hours['sum']) . "</span> total hours.<br/>";

$start_date = get_static('semester_start');
$start_date = explode('-',$start_date);
$week_dates = array();
if (count($start_date) != 3) {
  $week_dates  = array_fill(0,7,null);
}
else {
  for ($ii = 0; $ii < 7; $ii++) {
    $week_dates[$ii] = date('m/d',
                            mktime(0,0,0,$start_date[1],
                                   $start_date[2]+$week_num*7+$ii,$start_date[0]));
  }
}
if (!isset($javascript_pre)) {
  $javascript_pre = '';
}
$javascript_pre .= "<script>\n";
$javascript_pre .= "var week_dates = new Array(" . js_array($week_dates) . ");\n";
$javascript_pre .= <<<JAVASCRIPT_PRE
  var prev_val;

    function change_cell(elt,new_val) {
      if (get_value(elt) != new_val) {
        set_value(elt,new_val);
        elt.style.color = "red";
        elt.style.borderColor = "black";
      }
    }
  
  //for running hours assigned/unassigned/totals totals
  var total_hours = get_elt_by_id('week_total_hours');
  var assigned_hours = get_elt_by_id('week_assigned_hours');
  var unassigned_hours = get_elt_by_id('week_unassigned_hours');

  function change_handler(elt) {
    var coords = get_cell(elt);
    if (!coords) {
      return elt;
    }
    //change something that affected hours?
    if (coords[1] == 3 || coords[1] == 4) {
      if (coords[1] == 3) {
        var hrs = get_value(get_cell_elt(coords[0],4));
        if (!prev_val || !prev_val.length) {
          set_value(assigned_hours,Number(get_value(assigned_hours))+Number(hrs));
          set_value(unassigned_hours,Number(get_value(unassigned_hours))-Number(hrs));
        }
        else if (!get_value(elt)) {
          set_value(unassigned_hours,Number(get_value(unassigned_hours))+Number(hrs));
          set_value(assigned_hours,Number(get_value(assigned_hours))-Number(hrs));
        }
      }
      else {
        var is_mem = get_value(get_cell_elt(coords[0],3));
        var elt_to_change = unassigned_hours;
        if (is_mem && is_mem.length) {
          elt_to_change = assigned_hours;
        }
        var hrs = get_value(elt);
        set_value(total_hours,Number(get_value(total_hours))-Number(prev_val)+Number(hrs));
        set_value(elt_to_change,Number(get_value(elt_to_change))-Number(prev_val)+Number(hrs));
      }
    }   
    //did user set a day for a row that had no date?  Guess date from day.
    if (coords[1] == 1) {
      var date_cell = get_cell_elt(coords[0],0);
      if (!get_value(date_cell)) {
        var add_val = 6;
        if (typeof(days_arr[get_value(elt)]) != 'undefined') {
          add_val = Math.min(6,days_arr[get_value(elt)]);
        }
        change_cell(date_cell, week_dates[add_val]);
      }
    }
    return elt;
  }
  //for keeping track of hours assigned this week
  function focus_handler(elt) {
    //for the change handler
    prev_val = get_value(elt);
    return elt;
  }

  function delete_row_handler(elt) {
    default_delete_row_handler(elt);
    var rownum = elt.parentNode.parentNode.rowIndex-1;
    var is_mem = get_value(get_cell_elt(rownum,3));
    var elt_to_change = unassigned_hours;
    if (is_mem && is_mem.length) {
      elt_to_change = assigned_hours;
    }
    var hrs_val = get_value(get_cell_elt(rownum,4));
    if (elt.checked) {
      hrs_val *= -1;
    }
    set_value(total_hours,Number(get_value(total_hours))+Number(hrs_val));
    set_value(elt_to_change,Number(get_value(elt_to_change))+Number(hrs_val));
    return elt;
  }
</script>
JAVASCRIPT_PRE
  ;
require_once("$php_includes/table_edit.php");
?>
