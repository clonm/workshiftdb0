<?php 
if (!array_key_exists('week',$_REQUEST)) {
?>
  <html><head><title>Get week</title></head><body>
<form method=post action='<?=$_SERVER['REQUEST_URI']?>'>
     <input type=submit value='Get week: '><input name='week' size=3>
</form></body></html> 
     <?php exit;}
// Displays a week's input sheets for editing
require_once('default.inc.php');
//order by date, then day, then shift, then name 
//(although the last isn't necessary)
$order_exp = "`date`, `day`, `workshift`,`member_name`";
//this should be in url as week=x
$week_num = $_REQUEST['week'];
if (!is_numeric($week_num)) {
  janak_error("You didn't enter a numeric week number!  All weeks are numbered.");
}
//this could change depending on convention for naming tables
$table_name = "${archive}week_$week_num";
//does this table even exist?
$make_weeks_form = "<form action='make_weeks.php' method='GET'  onsubmit='" .
    "if (typeof(confirm_create) != \"undefined\") {" .
"return confirm(\"Are you sure?\")}'>" .
    "<input type=hidden name='start_week' value=$week_num>" .
    "<input type=hidden name='end_week' value=$week_num>" .
    "<input type=hidden name='overwrite' value='1'>";
if (!table_exists("week_$week_num")) {
  ?>
<html><head><title>Week <?=$week_num?> does not exist yet</title></head>
<body>
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
/*get_real_table_columns();
if (isset($col_reals['shift_id'])) {
  $table_edit_query = "select `$table_name`.`autoid`,`date`,`day`,`$table_name`.`workshift`," .
    "`$table_name`.`member_name`,`$table_name`.`hours`, `notes`," .
    "`start_time`,`end_time` from `$table_name` order by " . $order_exp;
  if (!isset($col_reals['start_time'])) {
    $db->Execute("alter table `$table_name` add column `start_time` time default null, " .
                 "add column `end_time` time default null");
  }
  if (is_empty($db->GetRow("select * from `$table_name` where `start_time` and " .
                           "`start_time` is not null limit 1"))) {
    $res = $db->Execute("select `shift_id` from `$table_name`");
    $db->SetFetchMode(ADODB_FETCH_NUM);
    while ($row = $res->FetchRow()) {
      if ($row['shift_id']) {
        $times = $db->GetRow("select `start_time` as `0`, `end_time` as `1` from `master_shifts` where `autoid` = ?",
                             array($row['shift_id']));
        if (!is_empty($times)) {
        $db->Execute("update `$table_name` set `start_time` = ?,`end_time` = ? where `shift_id` = ?",
                     array_merge($times,array($row['shift_id'])));
        }
      }
    }
    $db->SetFetchMode(ADODB_FETCH_ASSOC);
  }
}*/
//moved out of conditional above
  $col_formats['start_time'] = 'timeinput';
  $col_formats['end_time'] = 'timeinput';
//last three columns are inputs, with the following classes (input is default)
$col_styles = array('','','','member_name','hours','input','input','input');
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
//rows can be added/deleted
$delete_flag = true;
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
        set_value(rows_array[ii].cells[3].firstChild,'');
        change_handler(rows_array[ii].cells[3].firstChild);
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
$start_date = get_static('semester_start');
if (!strlen($start_date) == 0) {
  $start_date = explode('-',$start_date);
  $start_date[0]+=0;
  $start_date[1]+=0;
  $start_date[2]+=0;
  if (!isset($javascript_pre)) {
    $javascript_pre = '';
  }
  $javascript_pre .= <<<JAVASCRIPT_PRE
<script>    
    function change_cell(elt,new_val) {
      if (get_value(elt) != new_val) {
        set_value(elt,new_val);
        elt.style.color = "red";
        elt.style.borderColor = "black";
      }
    }
  
  var mydate = new Date();
  mydate.setFullYear({$start_date[0]},{$start_date[1]},{$start_date[2]});
  mydate.setDate(mydate.getDate()+7*$week_num);
  function change_handler(elt) {
    default_change_handler(elt);
    if (!elt.id) {
      if (elt.target) elt = elt.target;
      else if (elt.srcElement) elt = elt.srcElement;
    }
    var coords = get_cell(elt);
    if (coords && coords[1] == 1 && !get_value(elt.parentNode.parentNode.cells[0].firstChild)) {
      var add_val = 6;
      if (typeof(days_arr[get_value(elt)]) != 'undefined') {
        add_val = Math.min(6,days_arr[get_value(elt)]);
      }
      var temp_date = new Date();
      temp_date.setDate(mydate.getDate()+add_val);
      change_cell(elt.parentNode.parentNode.cells[0].firstChild,(Number(1)+Number(temp_date.getMonth())) + '/' + temp_date.getDate());
    }
  }
</script>
JAVASCRIPT_PRE
  ;
}      
require_once("$php_includes/table_edit.php");
?>
