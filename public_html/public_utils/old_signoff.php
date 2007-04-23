<?php 
require_once('default.inc.php');
if (!count($_GET)) { ?>
<html><head><title>Print Sign-Off Sheets</title></head><body>

<form action='<?=$_SERVER['REQUEST_URI']?>' method=POST id='signoff_form'>
   <input size=3 name='week_num'> Enter a week number here to get the signoff sheets from that week (which you
may have changed to add special hours, etc.).<br>
<input type=submit value="Grid Format" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?gridformat'"><br>
<hr>
<table>
<tr><td><input type=submit value="Monday, Tuesday" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Monday&Tuesday'"></td>
<td><input type=submit value="Monday, Tuesday with names" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Monday&Tuesday&Name'"></td></tr>
<tr><td><input type=submit value="Wednesday, Thursday" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Wednesday&Thursday'"></td>
<td><input type=submit value="Wednesday, Thursday with names" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Wednesday&Thursday&Name'"></td></tr>
<tr><td><input type=submit value="Friday, Saturday" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Friday&Saturday'"></td>
<td><input type=submit value="Friday, Saturday with names" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Friday&Saturday&Name'"></td></tr>
<tr><td><input type=submit value="Sunday" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Sunday'"></td>
<td><input type=submit value="Sunday with names" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Sunday&Name'"></td></tr>
<tr><td><input type=submit value="Weeklong" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Weeklong'"></td>
<td><input type=submit value="Weeklong with names" onclick="signoff_form.action='<?=$_SERVER['REQUEST_URI']?>?Weeklong&Name'"></td></tr></table>
   (If you click on the first link, you can easily see how to customize
    the display to what you want by looking at the address bar of your browser.)
<hr>
Or you can customize here (Names and Verifier apply to the gridformat as well):
Show names? <input type=checkbox name='Name' checked><br>
Have space for verifier? <input type=checkbox name='Verifier' checked><br>
                         Show days (only for daily signoffs):
<table>
<tr><td><input type=checkbox name='Monday'>Monday</td><td><input type=checkbox name='Tuesday'>Tuesday</td></tr>
<tr><td><input type=checkbox name='Wednesday'>Wednesday</td><td><input type=checkbox name='Thursday'>Thursday</td></tr>
<tr><td><input type=checkbox name='Friday'>Friday</td><td><input type=checkbox name='Saturday'>Saturday</td></tr>
<tr><td><input type=checkbox name='Sunday'>Sunday</td><td><input type=checkbox name='Weeklong'>Weeklong</td></tr>
</table>
<input type=submit value='Submit for daily sheet style (click above for gridformat)'><br><hr>
<table><tr>
Do you want extra columns displayed?  Put them in here before you click above.
<table>
<?php
   $cols = split("\n",get_static('signoff_cols',''));
 $col_wids = split("\n",get_static('signoff_col_wids',''));
 for ($ii = 0; $ii < max(count($cols)+2,3); $ii++) {
?>
<tr><td>Column name:</td><td><input name='cols[]' value='<?=count($cols) > $ii?$cols[$ii]:''?>'></td></tr>
<tr><td>Column width (optional):</td><td><input name='col_wids[]' value='<?=count($col_wids) > $ii?$col_wids[$ii]:''?>'></td></tr>
<?php
    }
?>
</table>
<hr>
Do you have a message you want at the top of the sheet?  Put it here.
<textarea name='signoff_message'>
<?php
$signoff_message = get_static('signoff_message','');
if (substr($signoff_message,0,5) == '<pre>' &&
    substr($signoff_message,-6) == '</pre>') {
  $signoff_message = substr($signoff_message,5,-6);
}
?>
<?=escape_html($signoff_message)?>
</textarea>
   <?php 
#';
exit; }
if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}
if (isset($_REQUEST['signoff_message'])) {
  $signoff_message = stripformslash($_REQUEST['signoff_message']);
  set_static('signoff_message',$signoff_message);
}
$body_insert = get_static('signoff_message','');
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
   else {
     $start_date = strtotime($start_date);
   }
   $beg_date = date('n/j',$start_date + $week_num*7*24*60*60);
   $end_date = date('n/j',$start_date + ($week_num*7+6)*24*60*60);
   function date_maybe($str) {
     global $start_date,$week_num,$days;
     return $str . " " . date('n/j',$start_date + ($week_num*7+array_search($str,$days))*24*60*60);
   }
 }
 else {
   $week_num = null;
   function date_maybe($str) {
     return $str;
   }
 }
$use_colors = get_static('colors_signoff',false);
echo "<html><head>";
if ($use_colors) {
echo <<<SCRIPT

<script type='text/javascript' src='$html_includes/table_edit.utils.js'>
</script>
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
  if (!elt.className || elt.className != 'signoffspace') {
    return true;
  }
  var ind = color_cycle.indexOf(elt.style.backgroundColor)+1;
  if (ind == color_cycle.length) {
    ind = 0;
  }

SCRIPT
;
if (isset($_REQUEST['Verifier'])) {
  echo "var enable_verif = (elt.style.backgroundColor == 'grey');\n";
}
  echo "elt.style.backgroundColor = color_cycle[ind];\n";
if (isset($_REQUEST['Verifier'])) {
  echo <<<SCRIPT
var disable_verif = (elt.style.backgroundColor == 'grey');
  if (enable_verif) {
    elt.parentNode.nextSibling.cells[elt.cellIndex].style.backgroundColor = 'white';
}
else if (disable_verif) {
    elt.parentNode.nextSibling.cells[elt.cellIndex].style.backgroundColor = 'grey';
}
SCRIPT
;
}
echo <<<SCRIPT
return true;
}
</script>
SCRIPT
;
}

if ($use_colors && isset($table_name)) {
function set_color($day,$grey,$cur_shiftid = null) {
global $db;
if ($cur_shiftid) {
  $shiftrow = $db->GetRow("select `$day`,`category` from `master_shifts` " .
                          "where `autoid` = ? limit 1",
                          array($cur_shiftid));
  if (!$shiftrow[$day]) {
    if (strlen($shiftrow['category']) && $shiftrow['category']{0} == '*') {
      return "yellow";
    }
    else {
      return 'rgb(0, 255, 0)';
    }
  }
  else if (!$row['member_name']) {
    return "yellow";
  }
  else if ($row['member_name'] !== $shiftrow[$day]) {
    return 'rgb(255, 70, 70)';
  }
  else {
    return "transparent";
  }
}
else {
  echo "transparent";
}
}
}

if (isset($_REQUEST['gridformat'])) {
?>
<title>Sign-Off Sheet</title>
<style>
/* as with table_edit.css, change parameters as you wish */
TABLE {
  font-family: Arial, Verdana, Geneva, Helvetica, sans-serif;
  font-size: 12px;
  color: Black;
  border: solid .25px;
  border-spacing: 0;
  empty-cells: show;
}

TD, TH { 
border: solid .25px; 
border-top: none;
padding-left: 1px; 
padding-right: 1px; 
padding-top: 0;
padding-bottom: 0;
}
</style>
</head><body>
<h1>Signoffs for Week <?=strlen($week_num)?"$week_num ($beg_date-$end_date)":''?></h1>
<?=$body_insert?>
<table>
<tr>
   <th>Workshift</th><th><?=join('</th><th>',array_map('date_maybe',$days))?></th>
</tr><?php
 $dummy_string = get_static('dummy_string');
 if (!isset($table_name)) {
   $res = $db->Execute("select * from `master_shifts` where " .
                       " `Weeklong` = ?  order by `workshift`",array($dummy_string));
   $greys = array_flip($days);
   while ($row = $res->FetchRow()) {
    ?><tr>
 <td><?=escape_html($row['workshift'] . ($row['floor']?' ' . $row['floor']:'') .
    ' (' . $row['hours'] . ')')?></td>
<?php
    foreach ($days as $day) {
    if ($row[$day] == $dummy_string) {
      $output = '';
      $greys[$day] = true;
    }
    else {
      if (!isset($_REQUEST['Name'])) {
        $output = '';
      }
      else {
        $output = $row[$day];
      }
      $greys[$day] = false;
    }
    echo "<td class='signoffspace' style='background-color: ";
      if ($greys[$day]) {
        echo "grey";
      }
      else if ($use_colors && !$row[$day]) {
        if (strlen($row['category']) && $row['category']{0} == '*') {
          echo "yellow";
        }
        else {
          echo 'rgb(0, 255, 0)';
        }
      }
      else {
        echo "white";
      }
      echo "'";
    echo ">" . escape_html($output) . "</td>";
    }
?>
</tr><?php if (isset($_REQUEST['Verifier'])) {
?><tr><td>Verifier</td>
<?php
    foreach ($days as $day) {
 echo "<td" . ($greys[$day]?" style='background-color: grey'":"") . "></td>";
    }
?></tr><?php
    }
    foreach ($days as $day) {
      $greys[$day] = false;
}
 }
echo "</table><hr>";
    $res = $db->Execute("select `workshift`, `floor`, `hours`, `Weeklong` " .
                        "from `master_shifts` where `Weeklong` != ? order by `workshift`",
                        array($dummy_string));
 $wk1_width = 6;
 $wk2_width = 0;
 while ($row = $res->FetchRow()) {
 ?>
<table style='display: inline; border: none'><tr><td class='wk1'><?=escape_html($row['workshift'] . ($row['floor']?' ' . $row['floor']:'') .
                              ' (' . $row['hours'] . ')')?></td><?php
    if ($row['Weeklong'] == $dummy_string) {
      $output = '';
      $greys = true;
    }
    else {
      if (!isset($_REQUEST['Name'])) {
        $output = '';
      }
      else {
        $output = $row['Weeklong'];
      }
      $greys = false;
    }
    echo "<td class='signoffspace wk2' style='background-color: ";
      if ($greys) {
        echo "grey";
      }
      else if ($use_colors && !$row['Weeklong']) {
        if (strlen($row['category']) && $row['category']{0} == '*') {
          echo "yellow";
        }
        else {
          echo 'rgb(0, 255, 0)';
        }
      }
      else {
        echo "white";
      }
      echo "'";
    echo ">" . escape_html($output) . "</td></tr>";
if (isset($_REQUEST['Verifier'])) { 
echo "<tr><td class='wk1'>Verifier</td><td class='wk2'" . 
($greys?" style='background-color: grey'":"") . "></td></tr>";
} 
echo "</table>";

      $wk1_width = max(strlen($row['workshift'])/2+strlen($row['floor']) + strlen($row['hours'])+2,$wk1_width);
 $wk2_width = max(strlen($row['Weeklong'])/2,$wk2_width);
   }

 }
//we have a particular week we're getting from
 else {

   $res = $db->Execute("select * from `$table_name` where " .
                       "`day` = '" . join("' or `day` = '",$days) . "' order by `workshift`, `hours`");
 $greys = array_flip($days);
 foreach ($greys as $day => $grey) {
   $greys[$day] = false;
 }
 $first_flag = true;
 $cur_shift = null;
$cur_shiftid = null;
 while (($row = $res->FetchRow()) || $cur_shift) {
   if ($row) {
     $temp = $row['workshift'] . ' (' . $row['hours'] . ')';
   }
   else {
     $temp = null;
   }
   if ($cur_shift != $temp) {
     if (!$first_flag) {
       foreach ($days as $day) {
         echo "<td class='signoffspace' style='background-color: ";
         if ($greys[$day] === false) {
           echo "grey";
         }
         echo "'>" . escape_html($greys[$day]) . "</td>";

       }
       echo "</tr>";
    if (isset($_REQUEST['Verifier'])) {
      echo "<tr><td>Verifier</td>";
    foreach ($days as $day) {
echo "<td style='background-color: ";
         if ($greys[$day] !== false) {
           echo "white";
         }
         else {
           echo "grey";
         }
         echo "'></td>";
    }
    echo "</tr>";
    }
     foreach ($days as $day) {
       $greys[$day] = false;
}
     }
     else {
       $first_flag = false;
     }
     $cur_shift = $temp;
     $cur_shiftid = isset($row['shift_id'])?$row['shift_id']:null;
     if (!$cur_shift) {
       break;
     }
     echo "<tr><td>" . escape_html($cur_shift) . "</td>";
}
   $greys[$row['day']] = isset($_REQUEST['Name'])?$row['member_name']:'';
 }
echo "</table><hr>";
$res = $db->Execute("select `workshift`, `hours`, `member_name` from `$table_name` " .
"where `day` != '" . join("' and `day` != '",$days) . "' order by `workshift`");
 $wk1_width = 6;
 $wk2_width = 0;
 while ($row = $res->FetchRow()) {
 ?>
<table style='display: inline; border: none'><tr><td class='wk1'><?=escape_html($row['workshift'] .
                              ' (' . $row['hours'] . ')')?></td><?php
         echo "<td class='signoffspace wk2' style='background-color: ";
         if ($greys === false) {
           echo "grey";
         }
         else if ($use_colors && $cur_shiftid) {
           $shiftrow = $db->GetRow("select * from `master_shifts` where `autoid` = ? limit 1",
                                    array($cur_shiftid));
           if (!$shiftrow[$day]) {
             if (strlen($shiftrow['category']) && $shiftrow['category']{0} == '*') {
               echo "yellow";
             }
             else {
               echo 'rgb(0, 255, 0)';
             }
           }
           else if (!$row['member_name']) {
             echo "yellow";
           }
           else if ($row['member_name'] !== $shiftrow[$day]) {
             echo 'rgb(255, 70, 70)';
           }
           else {
             echo "black";
           }
         }
         else {
           echo "white";
         }
         echo "'>" . escape_html($greys[$day]) . "</td>";

echo "<td class='wk2'>" . escape_html($row['member_name']) . "</td></tr>";
if (isset($_REQUEST['Verifier'])) { ?>
<tr><td class='wk1'>Verifier</td><td class='wk2'><?=blankfield()?></td></tr>
<?php } ?>
</table>
<?php
      $wk1_width = max(strlen($row['workshift'])/2+strlen($row['floor']) + strlen($row['hours'])+2,$wk1_width);
 $wk2_width = max(strlen($row['member_name'])/2,$wk2_width);
   }
}
?>
<style>
td.wk1 {
  border-top: .25px solid;
  border-right: none;
  width: <?=$wk1_width?>em;
}
td.wk2 {
  border-top: .25px solid;
 width: <?=$wk2_width?>em;
}
</style>
<p id="phptime" style='font-size: 8pt'>
PHP generated this page in <?=round(array_sum(split(' ',microtime()))-$php_start_time,2)?> seconds.
<?php
    exit;
    }
$dummy_string = get_static('dummy_string');
if (!isset($table_name)) {
create_master_week();
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
$where_exp = join(' OR ',array_map('utility_equal',$day_array));
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
"`$table_name`.`hours`,`master_shifts`.`start_time`,`master_shifts`.`end_time` from " .
"`$table_name` left join `master_shifts` on `shift_id` = `master_shifts`.`autoid` where " . $where_exp;
$col_formats['start_time'] = 'timeformat';
$col_formats['end_time'] = 'timeformat';
}
if (count($cols)) {
$cols = array_flip($cols);
foreach ($cols as $key => $val) {
  if (!$key) {
    unset($cols[$key]);
continue;
} 
  $cols[$key] = '';
}
$col_formats = array_merge($col_formats,$cols);
}
$col_sizes = $col_formats;
$ii = 0;
foreach ($col_sizes as $key=>$junk) {
  if (count($col_wids) > $ii && isset($cols[$key])) {
    $col_sizes[$key] = $col_wids[$ii++];
  }
  else {
    $col_sizes[$key] = 0;
  }
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
$order_exp = 'day';
if (count($day_array) > 1) {
  if (strcmp($day_array[0],$day_array[1]) > 0)
    $order_exp .= ' DESC';
}
$order_exp .= ', workshift';
$blanksize = 40;

$title_page = 'Signoffs for ' . join('/',$day_array);
$table_caption = '<h1>' . join('/',$day_array) . ' Signoffs for Week ' . 
(strlen($week_num)?"$week_num ($beg_date-$end_date)":'') . "</h1>";

#$db->debug = true;
require_once("$php_includes/table_print.php");
?>
