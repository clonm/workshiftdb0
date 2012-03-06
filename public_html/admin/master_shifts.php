<?php 
//heart of the assigning shifts process.  Usually included as a frame
//by assign_shifts, but can be viewed standalone.  Uses
//table_edit.php, but also does extensive modifications.  Has a
//javascript file, master_shifts.js, where the heavy lifting is done.

//just so we know how long this took
$php_start_time = array_sum(split(' ',microtime()));
require_once('default.inc.php');
//see table_edit for further explanations of these variables
//sort list alphabetically by workshift
$order_exp = 'workshift';
//we're selecting from this table
$table_name = "{$archive}master_shifts";
//the cells have to be somewhat modified, mostly because we want to
//gray out the dummy_string, XXXXX
$namemung_array = array_flip($days);
foreach ($namemung_array as $key => $val) {
  $namemung_array[$key] = 'namemung';
}
//col_formats gives the columns we want, and (if present) a function
//to call on each column, to modify the output
$col_formats = array_merge(array('workshift' => 'shiftlink',  
                                 'hours' => '', 'Weeklong' => 'namemung'),
                           $namemung_array,
                           array('start_time' => 'timeformat', 
                                 'end_time' => 'timeformat','category' => ''));
//columns with names in them, so we can restrict on them
$restrict_cols = array(2,3,4,5,6,7,8,9);

$dummy_string = get_static('dummy_string','XXXXX');
//everything's an input, but some of them are specialized inputs --
//these are defined in table_edit.css
$col_styles = array('input','hours','member_name','member_name','member_name',
		    'member_name','member_name','member_name','member_name',
		    'member_name','time','time','input');
//we can sort on workshift, hours, start time, end time, or category of shift
$col_sortable = array();
$col_sortable[0] = 'pre_process_default';
$col_sortable[1] = 'pre_process_num';
$col_sortable[10] = 'pre_process_time';
$col_sortable[11] = 'pre_process_time';
$col_sortable[12]= 'pre_process_default';

//give a cell which is grey if the dummy string is in there
function namemung($str, $rownum, $colnum) {
  global $col_styles, $dummy_string;
  return array("<input name='cell-{$rownum}-{$colnum}' " .
               "id='cell-{$rownum}-{$colnum}' " . 
               "class='member_name tblin' " .
               " value='" . escape_html($str) . "' " .
               "autocomplete=off" . 
	       (($str === $dummy_string)?' style="background-color: grey;"':'') .
	       '>',strlen($str));
}

//this doesn't actually need to be here.  However, there really should
//be a way to view who wants and doesn't want a shift from this page,
//and shiftlink should allow that, even though it currently doesn't.
function shiftlink($str,$rownum, $colnum) {
  return array("<input name='cell-{$rownum}-{$colnum}' id='cell-{$rownum}-{$colnum}'" . 
               " class='input$colnum'" .
               " value='" . escape_html($str) . "' " .
               "autocomplete=off>",strlen($str));
}

//we want to make the table a bit smaller than usual, because it's so big
$body_insert = 
<<<HEREDOC
<style type="text/css">
TABLE {
  font-family: Arial, Verdana, Geneva, Helvetica, sans-serif;
  font-size: 13px;
  color: Black;
}

</style>

HEREDOC;

//this will be set up to be called by table_edit.php on the load in javascript
$onload_function = 'initialize_master_shifts';

//there's a lot of javascript to put in here, about what shifts people can do,
//etc.  A lot of this is interacting with assign_shifts.php
$javascript_pre = "<script type=\"text/javascript\">var prev_val;\n" . 
"var dummy_string = " . dbl_quote($dummy_string) . ";\nvar hourslist = new Array();\n";
//don't display warnings about shift assignments ever
if (array_key_exists('suppress_all',$_GET)) {
  $javascript_pre .= "var suppress_all = true;\nvar suppress_first = true;\n";
}
//or just the first time
else if (array_key_exists('suppress_first',$_GET)) {
  $javascript_pre .= "var suppress_all = false;\nvar suppress_first = true;\n";
}
else {
  $javascript_pre .= "var suppress_all = false;\nvar suppress_first = false;\n";
}

$res = $db->Execute("SELECT `{$archive}house_list`.`member_name`, `shift_id`, " .
                    "`is_cat`, `rating` " .
                    "FROM `{$archive}house_list` LEFT JOIN " .
                    "`{$archive}wanted_shifts` ON " .
                    "`{$archive}house_list`.`member_name` = " .
                    "`{$archive}wanted_shifts`.`member_name`");
if (!$res) {
  exit("<h1>Error!  Couldn't get list of workshift preferences!</h1>");
}
//tell javascript about all the wanted shifts people have
 $initedlist = array();
$javascript_pre .="var wantedlist = new Array();\n";
while ($row = $res->FetchRow()) {
  $member_name = $row['member_name'];
  //new member?  Make an array for them
  if (!array_key_exists($member_name,$initedlist)) {
    $javascript_pre .="wantedlist[" . dbl_quote($member_name) . "] = new Array();\n";
    $initedlist[$member_name] = true;
  }
  $rating = $row['rating'];
  //no rating given?  Don't put it in
  if (!strlen($rating)) {
    continue;
  }
  $javascript_pre .="wantedlist[" . 
    dbl_quote($member_name) . "][" . dbl_quote($row['shift_id']) . "] = [" .
    $rating . ', ' . ($row['is_cat']?0:1) . "];\n";
}

//hourslist will be set later on after the shifts are actually read in
$houselist = get_houselist();
foreach ($houselist as $member) {
  $javascript_pre .= "hourslist[" . dbl_quote($member) . "] = 0;\n";
}

//get the availability so we can tell if times conflict
//utility function
function av_map($num) {
  global $row;
  $ret = $row["av_$num"];
  return '[' . js_array(str_split(str_pad((is_null($ret)?0:$ret),16,'0',STR_PAD_LEFT))) . ']';
}
$res = $db->Execute("SELECT `member_name`, " . 
		    bracket('av_0') . ", `av_1`, " .
		    bracket('av_2') . ", `av_3`, " .
		    bracket('av_4') . ", `av_5`, " .
		    bracket('av_6') . " FROM " . 
                    bracket($archive . 'personal_info'));
$initedlist = array();
$javascript_pre .= "var busylist = new Array();\n";
$javascript_pre .= "var origbusylist = new Array();\n";
while ($row = $res->FetchRow()) {
  $member = $row['member_name'];
  if (!array_key_exists($member,$initedlist)) {
    $javascript_pre .= "busylist[" . dbl_quote($member) . "] = new Array();\n";
    $javascript_pre .= "origbusylist[" . dbl_quote($member) . "] = new Array();\n";
    $initedlist[$member] = 1;
  }
  $javascript_pre .= "busylist[" . dbl_quote($member) . "] = [" .
    join(', ',array_map('av_map',array(0,1,2,3,4,5,6))) . "];\n";
  $javascript_pre .= "origbusylist[" . dbl_quote($member) . "] = [" .
    join(', ',array_map('av_map',array(0,1,2,3,4,5,6))) . "];\n";
}

$javascript_pre .= "var shiftlist = new Array();\n";
foreach ($houselist as $member) {
  $javascript_pre .= 'shiftlist[' . dbl_quote($member) . "] = new Array();\n";
}

$javascript_pre .= "var weekly_hours_quota = " . get_static('owed_default',5) .
<<<HEREDOC
;
</script>
<script type='text/javascript' src='master_shifts.js'></script>
HEREDOC;

$body_insert .= <<<HOURSCOUNT
<table style='border-style: none'><tr style='border-style: none'><td style='border-style: none; width: 1%; white-space: nowrap'>
<span id='assigned_hours'>0</span> hours have been assigned so far.<br/>
<span id='unassigned_hours'>0</span> hours are still unassigned.<br/>
<span id='total_hours'>0</span> is the total number of hours of shifts.<br/>
  </td><td id='click_reset_people' onclick="parent.reset_list()" style='border-style: solid; display: none; text-align: center'>(Click here to reset sidebar)</td>
</tr></table>
<script>
  if (parent != self) {
    document.getElementById('click_reset_people').style.display = '';
  }
</script>
HOURSCOUNT
;
#$body_insert = "<input type=submit value='Assign Shifts Automatically' onclick='autoassign()'><br>";
$delete_flag = true;
$post_table_text = '<input type=button class="button" onClick="copy_last_row()" value="Copy last row">';

require_once("$php_includes/table_edit.php");
?>
