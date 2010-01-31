<?php 
//Engine for displaying/modifying tables.  Never viewed directly -- only
//called by other .php files, with variables already initialized 
//Note that in the html for this page, it is crucial that there not be
//excessive spaces, since they are just dud html elements which will cause
//mis-indexing in the javascript.  The javascript gets html elements both by
//id (so don't change the id's without changing the javascript) and by index

//just to see how long this took

if (!isset($php_start_time)) {
  $php_start_time = array_sum(split(' ',microtime()));
}

if (!isset($archive)) {
  $archive = '';
}
//location of css include file
if (!isset($table_edit_css)) {
  $table_edit_css = "$html_includes/table_edit.css";
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN">
<html lang="en">
<HEAD>

<LINK REL=StyleSheet HREF="<?=escape_html($table_edit_css)?>" TYPE="text/css">
<?php
//set all variables that weren't set by calling script 
#$db->debug = true;
//scaling factor for widths -- from characters to ems
  $em_scaling = 2/3;
//location of javascript include file
if (!isset($table_edit_js)) {
  $table_edit_js = "$html_includes/table_edit.js";
}
//utils that are needed when table is editable
if (!isset($table_edit_js_utils)) {
  $table_edit_js_utils = "$html_includes/table_edit.utils.js";
}
//text to be put into page after table_edit.js is included
if (!isset($javascript_post)) {
  $javascript_post = '';
}
//same, for before
if (!isset($javascript_pre)) {
  $javascript_pre = '';
}
//text to insert into header
if (isset($header_insert)) {
  echo $header_insert;
}

//is this table read only?  Various things change
if (!isset($read_only)) {
  $read_only = false;
  if (!isset($delete_flag)) {
    $delete_flag = true;
  }
}
else {
  $read_only = true;
  $delete_flag = false;
  if (isset($col_styles)) {
    unset($col_styles);
  }
}

//maybe the table to update isn't the table that we're selecting from
//(this is the case for views, for example)
if (!isset($table_name_update)) {
  $table_name_update = $table_name;
}
//get all the columns if the caller gave us nothing
if (!isset($col_names) && !isset($col_formats)) {
  $res = $db->Execute("show columns from `$table_name`");
  while ($row = $res->FetchRow()) {
    if ($row['Field'] != 'autoid') {
      $col_names[] = $row['Field'];
    }
  }
}
//calling script can specify various things.  They might only give
//the column names, in which case the formats are trivial
if (!isset($col_formats) && isset($col_names)) {
  $col_formats = array_flip($col_names);
  foreach ($col_formats as $key => $val)
    $col_formats[$key] = '';
}
//or they might only give the formats, because the names are included in those
if (!isset($col_names) && isset($col_formats)) {
  $col_names = array_keys($col_formats);
}
//we may need to know how big columns are -- the sizes can be initialized later
if (!isset($col_sizes)) {
  $col_sizes = array_fill(0,count($col_formats),1);
}
//$col_styles contains classnames for special input cells: used here and by .js
if (!isset($col_styles)) {
  $col_styles = $col_names;
  foreach ($col_styles as $key => $val)
    $col_styles[$key] = '';
}

//col_sortable, if set for a given column, says that that column can be sorted,
//using the specified function.  If null, the page will guess.
if (!isset($col_sortable)) {
  $col_sortable = array_fill(0,count($col_formats),'pre_process_default');
}

//restrict_cols goes to table_edit.js so it knows which columns to restrict on
//these columns are the ones where member's names can appear, and so if we want
//to see only rows where a certain member's name appears, we search through
//only these columns
if (!isset($restrict_cols)) {
  $restrict_cols = array();
}

//what should this page be called?  default is the name of the table
if (!isset($title_page)) {
  $title_page = $table_name;
}
//what is the update_db url?  Probably just update_db.php
if (!isset($update_db_url)) {
  $update_db_url = 'update_db.php';
}

$dummy_string = get_static('dummy_string','XXXXX',$archive);

$empty_function = 'function (dummy) {return true;}';
//different inputs may have different callback handlers.  Append the defaults to
//any that the user may have specified.
if (!isset($class_handlers)) {
  $class_handlers = array();
}
$class_handlers['time'] = array('onchange' => 'time_change_handler');
$class_handlers['delete_check'] = array('onclick' => 'delete_row_handler',
                                        'onchange' => $empty_function,
                                        'onfocus' => $empty_function,
                                        'onblur' => $empty_function);

//for keeping track of rows in javascript
$autoid_row_table = array();

if (!isset($table_edit_query)) {
  //here goes the big query
  $sql = "SELECT ";
  //put together the columns -- bracketvirt makes sure that we don't try to get
  //virtual columns, but that they still occupy space in our result set
  $firstflag = true;
  foreach ($col_formats as $col => $format) {
    if (!$firstflag) {
      $sql .= ', ';
    }
    else {
      $firstflag = false;
    }
    if (!$format || !is_array($format)) {
      $sql .= bracketvirt($col);
    }
    else {
      $sql .= $format[0];
      $col_formats[$col] = $format[1];
    }
  }
  //no autoids necessary if it's readonly -- they're used only for updating
  if (!$read_only) {
    if ($col_names) {
      $sql .= ", ";
    }
    $sql .= bracket('autoid');
  }
  $sql .= " FROM " . bracket($table_name);
  //do we have a restriction?
  if (isset($where_exp)) {
    $sql .= " WHERE $where_exp";
  }
  else {
    $where_exp = '';
  }
  //are we ordering by something?
  if (isset($order_exp)) {
    $sql .= " ORDER BY $order_exp"; 
  }
  else {
    $order_exp = '';
  }
}
else {
  $sql = $table_edit_query;
}
//got the table
$res =& $db->Execute($sql); 
#print_r($res);
if (!$res) {
  exit("</head><body><h1>Error executing " . escape_html($sql) . ": " . 
       escape_html($db->ErrorMsg()) . 
       "</h1></body></html>");
}
//get the houselist for various things.  Not always necessary, but too hard to
//figure out when
$name_array = get_houselist(true,$archive);

//text that appears above restrict select box -- changes when user restricts
$restrict_label_text = "Limit To:";
//we need this
$num_cols = count($col_formats);

//the following formatting functions are used by various tables to format
//the results of the query.  They can be specified by the calling script
//in $col_formats and will then be called here.  They return an array with
//two elements, the text to print and how much space it takes up they take two 
//arguments, the string, and the row number, in case that matters

//format a date by stripping the year, replacing '-' with '/'
function dateformat($str, $num_rows, $escapehtml = true) {
  if ($str) {
    $date = substr($str,5,5);
    if (strlen($date) > 3)
      $date{2} = '/';
  }
  else {
    $date = "";
  }
  return $date;
  //  $len = strlen($date);
  //return array(($escapehtml?escape_html($date):$date),$len);
}

//format a time by putting in the pm or am, possibly stripping the minute
function timeformat($str, $num_rows = null) {
  if ($str) {
    $time = substr($str,0,5);
  }
  else {
    $time = "";
  }
  if ($time) {
    $hour = substr($time,0,2);
    $minute = substr($time,3,2);
    if ($hour >= 12) {
      if ($hour > 12) {
	$hour-=12;
      }
      $suffix = 'pm';
    }
    else {
      if ($hour == 0) {
	$hour = 12;
      }
      $suffix = 'am';
    }
    //get rid of leading 0
    if ($hour < 10) {
      $hour = 0+$hour;
    }
    $time = $hour . (($minute !== '00')?":$minute":'') . " $suffix";
  }
  //Janak changed 5/29/08 to just return time, because length is clear, and
  //non-obvious length is only reason to return array
  return $time;
}

//Janak commented out 5/29/08 because timeinput is equivalent to
//timeformat together with class=time
/* //timeinput is special because it's an input, and also the time's initial */
/* //value is formatted with the above */
/* function timeinput($str, $ii, $jj) { */
/*   global $col_styles, $dummy_string; */
/*   $str = timeformat($str,$ii); */
/*   $str = $str[0]; */
/*   return array("<input class='time tblin'  value='" . escape_html($str) . "' " .  */
/*                //               " onchange='time_change_handler(this);' " . */
/*                "name='cell-{$ii}-{$jj}' id='cell-{$ii}-{$jj}' " .  */
/*                //               " onBlur='blur_handler(this);' onFocus='focus_handler(this);' " . */
/*                "autocomplete=off>",strlen($str)); */
/* } */

//returns a blank cell -- not trivial, because real blank cells take up no space
function blankfield($str,$ii,$jj) {
  if (!isset($blanksize)) {
    $blanksize = 40;
  }
  return array(str_repeat('&nbsp;',$blanksize),$blanksize);
}

function generic_end($ii,$jj) {
  return " name='cell-{$ii}-{$jj}' id='cell-{$ii}-{$jj}' " . 
/*     "onChange='change_handler(this);' " . */
/*     "onBlur='blur_handler(this);' onFocus='focus_handler(this);' " . */
    "autocomplete=off>";
}

?>
<style type="text/css">
INPUT.tblin {
  width: 100%;
}
</style>
<?php
if (isset($head_insert)) {
  echo $head_insert;
}
?>
<title><?=escape_html($title_page)?></title>
</HEAD>


<BODY
<?php 
if (isset($onload_function)) {
  echo "onload=" . dbl_quote($onload_function . "();");
}
?>
>
<?php 
if (!isset($suppress_help)) {
  print_help();
}
//calling script might want to put something in at the top
if (isset($body_insert)) {
  echo $body_insert;
}
//no need to put warnings in if user can't edit page, but too much
//trouble to alter javascript
?>
<p class="status" <?=$read_only?"style='display: none'":''?>
id="statustext">
Ready -- remember to reload page (CTRL-F5 or SHIFT-Refresh) before you start editing</p>
<form 
<?php if (!$read_only) { ?>
method="POST" action="<?=escape_html($update_db_url)?>" 
onSubmit="if (enter_pressed) {return false;} return true;"
<?php } ?>
>
<?php // ids are used by javascript to get the elements ?>
<table id="headtable">
<tr>
<td>Show none</td>
<td>Show all</td>
<?php 
//this table has the checkboxes to view/hide columns, and the select box to 
//restrict rows.  As well, the column names are here in case form is submitted
//without javascript
foreach ($col_names as $title) {
  echo "<td align=center>" . escape_html($title);
  //but we don't need to have column names if it's read-only
  echo '</td>';
} 
if (!$read_only) {
  $ii = 0;
  foreach ($col_formats as $col => $junk) {
    echo "<input type=\"hidden\" name=\"col_names[]\" id='col_name_$ii' value=\"" . escape_html($col) . "\">";
    $ii++;
  }
}
//are we actually restricting on anything?  If so, display the restrict label
//which can be changed later
if (count($restrict_cols)) { 
?><td id="restrict_label" align=center><?=escape_html($restrict_label_text)?></td> <?php } ?>
<td>Color rows?</td>
</tr>
<tr>
<td><input type=checkbox id='checkhidetrue' onClick="hide_allcols(this);"></td>
<td><input type=checkbox id='checkhidefalse' onClick="hide_allcols(this);"></td>
<?php
//actually put out the checkboxes for hiding/showing 
for ($jj = 0; $jj < $num_cols; $jj++) { 
?><td align=center><input type=checkbox id='checkhide<?=$jj?>' checked=checked onClick="hide_col(<?=$jj?>,(this.checked)?'':'none');"></td>
<?php 
} 
//output a house list if there are columns to restrict on
if (count($restrict_cols)) {
?><td><select id="name_limit" onChange="restrict_rows(this);">
<option>
<?php
     foreach ($name_array as $mem) {
       print "<option value='" . escape_html($mem) . "'>" . 
       escape_html($mem) . "\n";
     }
?>
</select></td>
<?php 
     } 
?><td><input type=checkbox id='color_rows_checkbox' checked onclick='color_rows();'></td></tr></table>
<?php 
//should we display submit button?
if (!$read_only) { 
?><input class="button" id="update_button" type=button onClick="submit_data()" value="Update database (CTRL-S)">
<!-- <input class="button" id="update_button_backup" type="submit" value="Update database without Javascript (don't use unless error occurs)" 
onClick="if ('yes' == prompt('Are you sure you want to submit this way?  Type yes into the box below if you do (you do not want to do this unless you have been told to).  Email janak@janak.org before you do this.')) {window.onbeforeunload=''; return true;} return false;"> -->
<?php 
     } 
//from here on out, spaces between html elements become important.
//Don't change the formatting unless you know what you're doing
?><table id="bodytable" cellspacing='0'><?php 
//start outputting the main table
//do we have a caption?
if (isset($table_caption)) { 
?><caption><?=$table_caption?></caption><?php 
} 
?>

<thead>
<tr id="header_row"><?php 
//print out header rows, i.e. column names
$jj = 0;
foreach ($col_names as $title) {
  if ($col_sizes[$jj] != '*') {
    $col_sizes[$jj] = max($col_sizes[$jj],$em_scaling*strlen($title));
  }
?><th><?php
if (isset($col_sortable[$jj])) {
?><a href='#' class='sortheader' onclick='ts_resortTable(this,<?=$jj?>);return false;'><?php
     }
?><?=escape_html($title)?></a><?php
if (isset($col_sortable[$jj])) {
?><span class="sortarrow">&nbsp;&nbsp;&nbsp;</span><?php
}
  $jj++;
?></th><?php 
} 
$jj = 0;
if ($delete_flag) {echo '<td style="width: 3em">Delete?</td>';}
?></tr></thead><tbody id="data_rows"><?php

//here comes the big data loop
//get rows of the resultset, one by one
$num_rows = 0;
$orig_color = $color = 'white';
$orig_other_color = $other_color = '#AFEEEE';
$colorcell = array();
if (!isset($switch_color_frequency)) {
  $switch_color_frequency = 3;
}
$first_cell_index = -1;
while ($row =& $res->FetchRow()) {
  if (isset($mung_whole_row)) {
    $mung_whole_row($row);
    if (!$row) {
      continue;
    }
  }
  //start row
  echo '<tr';
  echo " style='background-color: $color'>";
  if (!(($num_rows+1)%$switch_color_frequency)) {
    $temp = $color;
    $color = $other_color;
    $other_color = $temp;
  }
  $jj = 0;
  //go through columns
  foreach ($col_formats as $col => $format) {
#     echo "<td>";
    //get $col column of this row
    $str = $row[$col];
    $ret = null;
    //is there a format associated to this column?  Probably not
    if (!isset($format) || !is_string($format) || !$format || 
        !function_exists($format) || empty($format)) {
      $format = $col_formats[$col] = '';
    }
    if ($format) {
      $ret = $format($str,$num_rows,$jj);
    }
    else {
      $ret = null;
    }
    echo "<td class='td$jj'";
    if (isset($colorcell[$jj])) {
      echo " style='background-color: " . escape_html($colorcell[$jj]) . "'";
    }
    echo ">";
    if (!$ret || !is_array($ret)) {
      if ($ret) {
        $str = $ret;
      }
      //if we have a style to go with this column, then we get to be an input
      //with the corresponding style
      if ($col_styles[$jj]) {
        //this is a big hack and hasn't been fully tested, but it should never
        //happen in real life -- only in table_edit.wrapper.php
        if ($col_styles[$jj] == 'textarea' || substr_count($str,"\n")) {
          echo "<textarea rows=3 cols=30 class='{$col_styles[$jj]} tblin' " .
            generic_end($num_rows,$jj) . escape_html($str) . 
            "</textarea>";
        }
        else if ($col_styles[$jj] == 'checkbox') {
          echo "<input type=checkbox " . ($str?'checked ':'') . generic_end($num_rows,$jj);
        }
        else {
          echo 
            "<input class='{$col_styles[$jj]} tblin' value='" . escape_html($str) . "'" .
            generic_end($num_rows,$jj);
        }
      }
      else {
	//otherwise we're just text
	echo escape_html($str);
      }
      //keep track of sizes
      //if in textarea, have 30 columns
      if ($col_sizes[$jj] != '*') {
        if (substr_count($str,"\n")) {
          $col_sizes[$jj] = max($col_sizes[$jj],20);
        }
        else {
          $col_sizes[$jj] = max($em_scaling*strlen($str),$col_sizes[$jj]);
        }
      }
    }
    //there was a script-specified format for this column, so use it
    else {
      //print the content -- ret was set above
      echo $ret[0];
      if ($col_sizes[$jj] != '*') {
        //keep track of sizes
        $col_sizes[$jj] = max($em_scaling*$ret[1],$col_sizes[$jj]);
      }
    }
    //done with this cell!
    echo '</td>';
    $jj++;
  }
  //at end of row, tack on autoid if we're modifying
  if (!$read_only) {
    echo "<td class='autoid'><input type=hidden id=\"autoid-{$num_rows}\" " .
      "name=\"autoid-{$num_rows}\" value=\"" . 
      escape_html($row['autoid']) . "\"></td>";
    $autoid_row_table[$row['autoid']] = $num_rows;
  }
  //tack on delete checkbox if we can delete
  if ($delete_flag) {
    echo "<td><input type=checkbox class='delete_check' name='delete_$num_rows' " .
    "tabindex=32767></td>";
  }
  //end of row!
  echo "</tr>\n";
  $num_rows++;
}
//print("<!-- full time generated in " . (array_sum(split(' ',microtime()))-$start_whole_shebang) . "-->\n");
?></tbody></table>
<style type="text/css">
<?php //we know how much space each column takes up now 
$ii = 0;
foreach ($col_formats as $col => $junk) {
  if ($col_sizes[$ii] != '*') {
?>
 td.td<?=$ii?> {
   width: <?=$col_sizes[$ii]?>em;
 }
<?php 
    }
    if (!$col_styles[$ii]) {
      $col_styles[$ii] = "tblin";
    } 
 $ii++; 
} ?>
</style>
<?php 
//we can add rows if we can delete
if ($delete_flag) {
  ?>
<input type=button class="button" onClick="add_row()" value="Add row"><?php 
   }
//now put in the form entries for non-javascript submissions
if (!$read_only) {
  ?><input type="hidden" name="num_rows" id="num_rows" value=<?=$num_rows?> >
<input type="hidden" name="num_cols" value=<?=count($col_names)?>>
<input type="hidden" name="table_name" value="<?=escape_html($table_name_update)?>">
<input type="hidden" name="js_flag" value=0><?php
       }
//following are variables needed by table_edit.js.  They're not all necessary
//in all cases, but it's not worth disentangling them.
?></form>
<script type="text/javascript">
var archive = '<?=$archive?>';
//the main table body
var tbody_elt = document.getElementById("data_rows");
var rows_array = tbody_elt.rows;
var header_row = document.getElementById("header_row");
var statustext = document.getElementById("statustext");
var num_rows = <?=$num_rows?>;
var num_cols = <?=$num_cols?>;
<?php if (!$read_only) { ?>
var col_styles = [<?=js_array($col_styles)?>];
var col_sizes = [<?=js_array($col_sizes)?>];
//lookup table of autoids to row numbers, for use after sorting
<?php js_assoc_array('autoid_row_table',$autoid_row_table); ?>
<?php } ?>
<?php js_assoc_array('col_sortable',$col_sortable); ?>
var name_array = [<?= js_array($name_array)?>];
var restrict_cols = [<?=js_array($restrict_cols)?>];
var restrict_label_old = <?=dbl_quote($restrict_label_text)?>;
var update_db_url = <?=dbl_quote($update_db_url)?>;
//standard string for "shift not assigned"
var dummy_string = <?=dbl_quote($dummy_string)?>;
<?php js_assoc_array('days_arr',array_flip($days))?>
days_arr["Weeklong"] = "7";
var table_name = <?=dbl_quote($table_name_update)?>;
 var row_color = <?=dbl_quote(escape_html($orig_color))?>;
 var other_color = <?=dbl_quote(escape_html($orig_other_color))?>;
 var switch_color_frequency = <?=dbl_quote(escape_html($switch_color_frequency))?>;
<?php
if (isset($change_text_on_update)) {
?>
 var change_text_on_update = <?=dbl_quote($change_text_on_update)?>;
<?php
    }
?>
</script>
<!-- these functions may be called by some pages in their javascript_pre, so we 
need to include this file beforehand -->
<script type='text/javascript' src="<?=escape_html($table_edit_js_utils)?>"></script>
<?=$javascript_pre?>
<script type="text/javascript" src="<?=escape_html($table_edit_js)?>"></script>
<?=$javascript_post?>
<?php
   //needs to go here so that all functions have been defined
   if (!$read_only) {
     print "<script type='text/javascript'>\n";
     print "var class_handlers = {";
     $first_class = true;
     foreach ($class_handlers as $class => $handlers) {
       if ($first_class) {
         $first_class = false;
       }
       else {
         print ",";
       }
       print "\n";
       print dbl_quote($class) . ": {";
       $first_handler = true;
       foreach ($handlers as $event => $handler) {
         if ($first_handler) {
           $first_handler = false;
         }
         else {
           print ",";
         }
         print "\n";
         print dbl_quote($event) . ": " . $handler;
       }
       print "\n}";
     }
     print "};\n</script>";
   }
?>

<p id="phptime">
PHP generated this page in <?=round(array_sum(split(' ',microtime()))-$php_start_time,2)?> seconds.
</p>
</body>
</html>
