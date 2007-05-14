<?php 
$body_insert = '';
require_once('default.inc.php');
//don't want weeklong in the days
array_pop($days);
//deal with signoff message and cols first, since it's independent of what's displayed
if (array_key_exists('signoff_message',$_REQUEST)) {
  set_static('signoff_message',$_REQUEST['signoff_message']);
}
$signoff_message = get_static('signoff_message');
if (array_key_exists('cols',$_REQUEST)) {
  set_static('signoff_cols',join("\n",$_REQUEST['cols']));
}
if (array_key_exists('col_wids',$_REQUEST)) {
  set_static('signoff_col_wids',join("\n",$_REQUEST['col_wids']));
}

//nothing entered?  Offer front page
if (count($_GET) == 0) { ?>
<html><head><title>Print Sign-Off Sheets</title></head><body>
<?php
   print $body_insert;
 print_help(); 
?>
<form action='<?=this_url()?>' method=get id='signoff_form'>
   <input size=3 name='week_num' value='<?=($cur_week = get_cur_week())>-1?$cur_week:''?>'>
Enter a week number here to get the signoff sheets from that week (which you
may have changed to add special hours, etc.).<br>
Show names? <input type=checkbox name='Name' checked><br>
Have space for verifier? <input type=checkbox name='Verifier' checked><br>
<hr>
<input type=submit name='gridformat' value="Grid Format"><br>
<hr>
Show days (only for daily signoffs):
<table>
<tr><td><input type=checkbox name='Monday'>Monday</td><td><input type=checkbox name='Tuesday'>Tuesday</td></tr>
<tr><td><input type=checkbox name='Wednesday'>Wednesday</td><td><input type=checkbox name='Thursday'>Thursday</td></tr>
<tr><td><input type=checkbox name='Friday'>Friday</td><td><input type=checkbox name='Saturday'>Saturday</td></tr>
<tr><td><input type=checkbox name='Sunday'>Sunday</td><td><input type=checkbox name='Weeklong'>Weeklong</td></tr>
</table>
<input type=submit value='Submit for daily sheet style (click above for gridformat)'><br><hr>
</form>
    <form action=<?=this_url()?> method=post>
Do you want extra columns displayed (in daily sheets)?  Put them in here and click below.
<table>
<?php
   $cols = split("\n",get_static('signoff_cols',''));
 $col_wids = split("\n",get_static('signoff_col_wids',''));
 while (!strlen(end($cols))) {
   array_pop($cols);
   array_pop($col_wids);
 }
 for ($ii = 0; $ii < max(count($cols)+2,3); $ii++) {
?>
<tr><td>Column name:</td><td><input name='cols[]' value='<?=count($cols) > $ii?$cols[$ii]:''?>'></td></tr>
<tr><td>Column width (optional):</td><td><input name='col_wids[]' value='<?=count($col_wids) > $ii?$col_wids[$ii]:''?>'></td></tr>
<?php
    }
?>
</table>
<hr>
    Do you have a message you want at the top of the sheet?  Put it here,
then click below.<br/>
<textarea name='signoff_message' cols=60 rows=5>
<?php
 //here for legacy -- shouldn't be anyone with this anymore
 if (substr($signoff_message,0,5) == '<pre>' &&
     substr($signoff_message,-6) == '</pre>') {
   $signoff_message = substr($signoff_message,5,-6);
 }
 echo escape_html($signoff_message) . 
   "</textarea><br/><input type=submit value='Update message/columns'></form>";
 exit; 
}
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}
$body_insert = "<div style='" . white_space_css() . "'>" . $signoff_message . "</div>";
   if (isset($_REQUEST['week_num']) && strlen($_REQUEST['week_num'])) {
     $week_num = stripformslash($_REQUEST['week_num']);
     if (!table_exists("week_$week_num")) {
       exit("The sheet for <a href='../admin/week.php?week=" . escape_html($week_num) . "'>Week " .
            escape_html($week_num) . "</a> does not exist yet.  Click on the link to create it.");
     }
     $table_name = "week_$week_num";
     $start_date = get_static('semester_start');
     if (strlen($start_date) == 0) {
       exit("<p>You haven't entered the start of the semester in " .
            "<a href='../admin/basic_consts.php'>basic_consts.php</a>");
     }
     $start_date = explode('-',$start_date);
     $beg_date = date('n/j',mktime(7*24*$week_num,0,0,$start_date[1],
                                   $start_date[2],$start_date[0]));
     $end_date = date('n/j',mktime(24*($week_num*7+6),0,0,$start_date[1],
                                   $start_date[2],$start_date[0]));
     function date_maybe($str) {
       global $start_date,$week_num,$days;
       return $str . " " . 
         date('n/j',mktime(7*24*$week_num+array_search($str,$days),0,0,
                           $start_date[1],$start_date[2],$start_date[0]));
     }
   }
else {
     $week_num = null;
     function date_maybe($str) {
       return $str;
     }
}
$verifier = isset($_REQUEST['Verifier']);
$use_colors = get_static('colors_signoff',false);
$javascript_pre = '';
  $javascript_pre .= <<<STYLE
<style>

td {
padding: 0px 0px 0px 2px;
}

table {
    empty-cells: show;
}

table.verif {
  table-layout: fixed;
  border-style: none;
  border-width: 0px 0px 0px 0px;
  margin-bottom: -1px;
  margin-top: 1px;
  margin-left: -1px;
  width: auto;
}
td.verif {
  border-style: dotted;
  border-width: 1px 0px 0px 0px; 
  width: 500em;
}

td.signoff, span.signoff {
  border-width: 0px;
  width: 500em;
}
</style>
STYLE
;
if ($use_colors) {
$javascript_pre .= <<<SCRIPT
<script type='text/javascript'>

window.onclick = clickhandler;

var color_cycle = new Array('rgb(255, 70, 70)','rgb(0, 255, 0)','yellow','white','grey');
function clickhandler(elt) {
  if (!elt.style && elt.target) {
    elt = elt.target;
  }
  else if (!elt.style && elt.srcElement) {
    elt = elt.srcElement;
  }
  else if (!elt.style && !this.screen) {
    elt = this;
  }
  if (!is_td(elt)) {
    return true;
  }
  if (!elt.className || 
      ((elt.className.indexOf('signoff') == -1) && !elt.className.match(/^td[1-7]$/))) {
    return true;
  }
  var ind = color_cycle.indexOf(elt.style.backgroundColor)+1;
  if (ind == color_cycle.length) {
    ind = 0;
  }

SCRIPT
;
if (isset($_REQUEST['Verifier'])) {
  $javascript_pre .= "var enable_verif = (elt.style.backgroundColor == 'grey');\n";
}
  $javascript_pre .= "elt.style.backgroundColor = color_cycle[ind];\n";
if (isset($_REQUEST['Verifier'])) {
  $javascript_pre .= <<<SCRIPT
var disable_verif = (elt.style.backgroundColor == 'grey');
  if (enable_verif) {
    elt.parentNode.nextSibling.cells[elt.cellIndex].style.backgroundColor = 'transparent';
}
else if (disable_verif) {
    elt.parentNode.nextSibling.cells[elt.cellIndex].style.backgroundColor = 'grey';
}
SCRIPT
;
}
$javascript_pre .="return true;\n}\n</script>\n";
}

if ($use_colors && isset($table_name)) {
  function calculate_color($person,$defperson,$special) {
    if (!$defperson) {
      if ($special) {
        return "yellow";
      }
      else {
        return 'rgb(0, 255, 0)';
      }
    }
    else if (!$person) {
      return "yellow";
    }
    else if ($person !== $defperson) {
      return 'rgb(255, 70, 70)';
    }
    else {
      return "transparent";
    }
  }
}

if (array_key_exists('gridformat',$_REQUEST)) {
$title_page = 'Sign-Off Sheet';
$header = "<h1>Signoffs for Week";
if (strlen($week_num)) {
$header .= " $week_num ($beg_date-$end_date)";
}
$header .= "</h1>";
$body_insert = $header . $body_insert;
$days_arr = array_flip($days);
foreach ($days_arr as $key => $junk) {
  $days_arr[$key] = 'doday';
}
$col_formats = array_merge(array('workshift' => 'doworkshift'),$days_arr,
                           array('start_time' => 'timeformat','end_time' => 'timeformat'));
$col_names = array_merge(array('workshift'),array_map('date_maybe',$days),
                         array('start_time','end_time'));
 if (!isset($table_name)) {
   $table_edit_query = "select * from `master_shifts` where `Weeklong` = " . 
     $db->qstr($dummy_string) . " order by `workshift`";
 }
 else {
   $table_edit_query = "select * from `$table_name` where " .
     "`day` = '" . join("' or `day` = '",$days) . "' order by `shift_id`,`workshift` ";
 }
$col_sortable = array();
$col_sortable[0] = 'pre_process_default';
$col_sortable[8] = 'pre_process_time';
$col_sortable[9] = 'pre_process_time';

$dummy_string = get_static('dummy_string','XXXXX');

$blank_color = 'rgb(0, 255, 0)';

 if (!isset($table_name)) {
   function mung_whole_row(&$row) {
     global $colorcell,$days,$use_colors;
     if ($row['category'] && substr($row['category'],0,2) == '**') {
       $row = null;
       return;
     }
     $colorcell = array();
     if ($use_colors) {
       if ($row['category'] && $row['category']{0} == '*') {
         $blank_color = 'yellow';
       }
       else {
         $blank_color = 'rgb(0, 255, 0)';
       }
       for ($ii = 0; $ii < count($days); $ii++) {
         if (!$row[$days[$ii]]) {
           $colorcell[$ii+1] = $blank_color;
         }
       }
     }
     $row['workshift'] = format_shift($row['workshift'],$row['hours'],$row['floor']);
   }
 }
 else {
   $cur_shiftid = 'no_shift_id';
   $people = array();
   $shift_name = null;
   $start_time = null;
   $end_time = null;
   $mung_colors = array();
   $skipping_rows = false;
   function mung_whole_row(&$row) {
     global $db,$res,$days,$num_rows,$people,$shift_name,$cur_shiftid,$colorcell, $dummy_string,
       $use_colors,$skipping_rows;
     $colorcell = array();
     if ($res->EOF) {
       if ($cur_shiftid === $row['shift_id']) {
         $people[$row['day']] = $row['member_name'];
         $cur_shiftid .= ' ';
       }
       else {
         $res->Move($num_rows);
       }
     }
     $retflag = false;
     if ($cur_shiftid !== $row['shift_id']) {
       if (count($people)) {
         $colorcell = array();
         for ($ii = 0; $ii < count($days); $ii++) {
           if (isset($people[$days[$ii]])) {
             $row[$days[$ii]] = $people[$days[$ii]];
           }
           else {
             $row[$days[$ii]] = null;
             $colorcell[$ii+1] = 'grey';
           }
         }
         $shiftdbrow = $db->GetRow("select `" . join('`,`',array_keys($people)) .
                                    "`,`start_time`,`end_time`,`category` from `master_shifts` " .
                                    "where `autoid` = ? limit 1",
                                    array($cur_shiftid));
         if (!is_empty($shiftdbrow)) {
           if ($shiftdbrow['category'] && 
               substr($shiftdbrow['category'],0,2) == '**') {
             $skipping_rows = true;
             $row = null;
             return;
           }
           else {
             $skipping_rows = false;
           }
           $row['start_time'] = $shiftdbrow['start_time'];
           $row['end_time'] = $shiftdbrow['end_time'];
           $special = $shiftdbrow['category'] && $shiftdbrow['category']{0} == '*';
           $jj = 1;
           if ($use_colors) {
             foreach ($days as $day) {
               if (array_key_exists($day,$people) && $use_colors) {
                 $colorcell[$jj] = calculate_color($people[$day],$shiftdbrow[$day],$special);
               }
               $jj++;
             }
           }
         }
         else if ($use_colors) {
           for ($ii = 0; $ii < count($days); $ii++) {
             $colorcell[$ii] = 'rgb(255, 70, 70)';
           }
         }
         $retflag = true;
         $people = array();
       }
       if ($skipping_rows) {
         $row = null;
         return;
       }
       $cur_shiftid = $row['shift_id'];
       $temp = $shift_name;
       $shift_name = format_shift($row['workshift'],$row['hours']);
       $row['workshift'] = $temp;
     }
     $people[$row['day']] = $row['member_name'];
     if (!$retflag) {
       $row = null;
     }
   }
 }       

 $mung_whole_row = 'mung_whole_row';
 if (isset($_REQUEST['Verifier'])) {

   function doworkshift($str) {
     return array("<table class='verif' style='font-weight: bold;'>" .
                  "<tr><td class='signoff'>" . escape_html($str) . 
                  "</td></tr><tr><td class='verif'>Verifier</td></tr></table>",
                  max(strlen($str),strlen('Verifier')));
   }
   function doday($str,$ii = null,$jj = null) {
     global $dummy_string, $jj,$colorcell,$use_colors;
     $ret = "<table class='verif'><tr><td class='signoff'";
     if ($str == $dummy_string) { 
       $ret .= " style='background-color: grey'>&nbsp;</td></tr><tr>" .
         "<td style='background-color: grey'";
     }
     else {
       if (!isset($_REQUEST['Name']) || !$str) {
         $output = '&nbsp;';
       }
       else {
         $output = escape_html($str);
       }
       if ($use_colors && isset($colorcell[$jj])) {
         $ret .= " style='background-color: " . escape_html($colorcell[$jj]) . "'>";
         $ret .= $output . "</td></tr><tr><td";
         if ($colorcell[$jj] == 'grey') {
           $ret .= " style='background-color: grey;'";
         }
       }
       else {
         $ret .= ">" . $output . "</td></tr><tr><td";
       }
     }
     $ret .= " class='verif'>&nbsp;</td></tr></table>";
     if ($use_colors) {
       unset($colorcell[$jj]);
     }
     return array($ret,
                  strlen($str));
   }
 }
 else {
   function doday($str,$ii,$jj) {
     global $dummy_string,$colorcell;
     if ($str == $dummy_string) {
       $colorcell[$jj] = 'grey';
       $str = ' ';
     }
     if (isset($_REQUEST['Name'])) {
       return $str;
     }
     else {
       return ' ';
     }
   }
 }
 if (!isset($table_name)) {
    $res = $db->Execute("select `workshift`, `floor`, `hours`, `Weeklong`, `category` " .
                        "from `master_shifts` where `Weeklong` != ? or `Weeklong` is null " .
                        "order by `workshift`",
                        array($dummy_string));
 }
 else {
   $res = $db->Execute("select `workshift`, `hours`, `day`, `member_name`, `shift_id` " .
                       "from `$table_name` where 1 " . str_repeat('and `day` != ? ',7) .
                       " order by `workshift`",
                       $days);
 }
 $wk1_width = 0;
 $wk2_width = 6;
 if (!is_empty($res)) {
   $javascript_pre .= <<<STYLE
<style>
table.wklong {
  border: none;
  display: inline;
}
</style>
<br>
STYLE
;
}
 while ($row = $res->FetchRow()) {
   $colorcell = 'transparent';
   if (!isset($table_name)) {
     if ($row['category'] && substr($row['category'],0,2) == '**') {
       continue;
     }
     $shift = format_shift($row['workshift'],$row['hours'],$row['floor']);
     if ($row['Weeklong'] == $dummy_string) {
       $output = '';
       $colorcell = 'grey';
     }
     else {
       $output = $row['Weeklong'];
       if ($use_colors && !$output) {
         if ($row['category'] && $row['category']{0} == '*') {
           $colorcell = 'yellow';
         }
         else {
           $colorcell = 'rgb(0, 255, 0)';
         }
       }
     }
   }
   else {
     $shift = $row['workshift'];
     $output = $row['member_name'];
     $shiftdbrow = $db->GetRow("select `Weeklong`, `category` from `master_shifts` " .
                                "where `autoid` = ?",
                                array($row['shift_id']));
     if (!is_empty($shiftdbrow) &&
         $shiftdbrow['category'] && substr($shiftdbrow['category'],0,2) == '**') {
       continue;
     }
     if ($use_colors) {
       if (!is_empty($shiftdbrow)) {
         $colorcell = calculate_color($row['member_name'],$shiftdbrow['Weeklong'],
                                      $shiftdbrow['category'] && $shiftdbrow['category']{0} == '*');
       }
       else {
         $colorcell = 'rgb(255, 70, 70)';
       }
     }
   }
   if (!isset($_REQUEST['Name'])) {
     $output = ' ';
   }
   if (!$shift) {
     $shift = '<no shift name>';
   }

$javascript_pre .= "<table class='wklong'><tr><td class='signoff wk1'>" . 
     escape_html($shift) . "</td>";
 $javascript_pre .= "<td class='signoff wk2' style='background-color: " .
   escape_html($colorcell) . "'>" . escape_html($output) . "</td></tr>";
if (isset($_REQUEST['Verifier'])) { 
$javascript_pre .= "<tr><td class='wk1 verif vmozbug'>Verifier</td><td class='wk2 verif vmozbug'" . 
($colorcell == 'grey'?" style='background-color: grey'":"") . "></td></tr>";
} 
$javascript_pre .= "</table>&nbsp;";

      $wk1_width = max(strlen($shift)/2+2,$wk1_width);
 $wk2_width = max(strlen($output)/2,$wk2_width);
   }
$javascript_pre .= <<<SCRIPT
<style>
td.wk1 {
  border-left-style: solid;
  border-left-width: 1px;
  border-top: solid 1px;
  border-right: 1px solid;
  width: {$wk1_width}em;
  padding: 2px;
}
td.wk2 {
  border-right-style: solid;
  border-right-width: 1px;
  border-top: solid 1px;
 width: {$wk2_width}em;
  padding: 2px;
}

td.vmozbug {
border-top-style: dotted;
border-bottom-style: solid;
border-bottom-width: 1px;
}
SCRIPT
;
if (!$verifier) {
$javascript_pre .= <<<SCRIPT
td.wk1 {
  border-bottom: solid 1px;
}
td.wk2 {
  border-bottom: solid 1px;
}
SCRIPT
;
}
$javascript_pre .= "\n</style>\n";
 if (!isset($table_name)) { 
   $table_name = 'master_shifts';
 }
require_once("$php_includes/table_print.php");
exit;
}
if (!isset($table_name)) {
$table_name = 'master_week';
}
//signoff list for the workshift manager to print and post each week
if (isset($_REQUEST['cols'])) {
  $cols = array();
  $col_wids = array();
  for ($ii = 0; $ii < count($_REQUEST['cols']); $ii++) {
    $temp = stripformslash($_REQUEST['cols'][$ii]);
    if (!$temp) {
      break;
    }
    $cols[] = $temp;
    if (isset($_REQUEST['col_wids'][$ii])) {
      $col_wids[] = stripformslash($_REQUEST['col_wids'][$ii]);
    }
  }
  set_static('signoff_cols',join("\n",$cols));
  set_static('signoff_col_wids',join("\n",$col_wids));
}
else {
  $cols = split("\n",get_static('signoff_cols',''));
  $col_wids = split("\n",get_static('signoff_col_wids',''));
}
$time = microtime(1);
function utility_equal ($str) {global $db; return 'day = ' . $db->qstr($str);}
$day_array = array();
$days[] = 'Weeklong';
foreach ($days as $day) {
  if (isset($_REQUEST[$day])) {
    $day_array[] = $day;
  }
}
 if (isset($_REQUEST['days'])) {
   $day_array += $_REQUEST['days'];
 }
 if (!count($day_array)) {
   exit("Please go back and select some days to print the sheets for!");
 }
$where_exp = join(' OR ',array_map('utility_equal',$day_array));
$order_exp = '`janak_sort_column`, `workshift`';
$col_formats = array('date' => 'dateformat','day' => '','workshift' => '',
		     'member_name' => 
		     array_key_exists('Name',$_REQUEST)?'':'blankfield');
if (isset($_REQUEST['Verifier'])) {
$col_formats['Verifier'] = 'blankfield';
}
$col_formats['hours'] = null;
get_real_table_columns();
if (isset($col_reals['shift_id'])) {
$table_edit_query = "select " . ($table_name == 'master_week'?'"" as ':'') . "`date`"  .
",`day`,`$table_name`.`workshift`,`member_name`,0 as `Verifier`," .
"`$table_name`.`hours`,`master_shifts`.`start_time`,`master_shifts`.`end_time`, " .
  "null as `" . join('`, null as `',$cols) . "` " .
  ", find_in_set(`day`,'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday," .
  "Sunday,Weeklong,Special') as `janak_sort_column` from " .
  "`$table_name` left join `master_shifts` on " .
  "`shift_id` = `master_shifts`.`autoid` where " . $where_exp .
  " order by " . $order_exp;
$col_formats['start_time'] = 'timeformat';
$col_formats['end_time'] = 'timeformat';
}
if (count($cols)) {
$col_flips = array_flip($cols);
foreach ($col_flips as $key => $val) {
  if (!$key) {
    unset($col_flips[$key]);
    continue;
  } 
  $col_flips[$key] = '';
}
$col_formats = array_merge($col_formats,$col_flips);
}
 $col_sizes = array_fill(0,count($col_formats),0);
 for ($ii = 0; $ii < count($cols) && $ii < count($col_wids); $ii++) {
   $col_sizes[$ii+count($col_formats)-count($cols)] = $col_wids[$ii];
 }

$col_sortable = array();
$col_sortable[0] = 'pre_process_date';
$col_sortable[1] = 'pre_process_day';
$col_sortable[2] = 'pre_process_default';
$col_sortable[3] = 'pre_process_default';
$ii = 4;
if (isset($col_formats['Verifier'])) {
  $ii++;
}
$col_sortable[$ii++] = 'pre_process_num';
if (isset($col_formats['start_time'])) {
  $col_sortable[$ii++] = 'pre_process_time';
  $col_sortable[$ii++] = 'pre_process_time';
}
$blanksize = 40;

$title_page = 'Signoffs for ' . join('/',$day_array);
$table_caption = '<h1>' . join('/',$day_array) . ' Signoffs for Week ' . 
(strlen($week_num)?"$week_num ($beg_date-$end_date)":'') . "</h1>";

#$db->debug = true;
 if (!isset($body_insert)) {
   $body_insert = '';
 }
 $temp_insert = "<div class='print_hide'><form action='" . escape_html($_SERVER['REQUEST_URI']) .
   "' method=post><input type=hidden name='download'>";
 foreach ($_POST as $key => $val) {
   if (!is_array($val)) {
     $temp_insert .= "<input type=hidden name='" . escape_strip($key) . "' value='" .
       escape_strip($val) . "'>";
   }
   else {
     foreach ($val as $arrval) {
       $temp_insert .= "<input type=hidden name='" . escape_strip($key) . "[]' value='" .
         escape_strip($arrval) . "'>";
     }
   }
 }
 $temp_insert .= "<input type=submit style='background-color: grey' value='Download to Excel'></form></div>\n";
 $body_insert = $temp_insert . $body_insert;
 if (array_key_exists('download',$_REQUEST)) {
   if (!isset($mung_whole_row)) {
     $mung_whole_row = null;
   }
   if (!isset($table_edit_query)) {
     $table_edit_query = null;
   }
   export_csv_file('Signoff Sheet',$table_edit_query,$col_formats,$mung_whole_row);
 }
 else {
   require_once("$php_includes/table_print.php");
 }
?>
