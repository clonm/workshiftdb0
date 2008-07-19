<?php
$php_start_time = array_sum(split(' ',microtime()));
$body_insert = '';
require_once('default.inc.php');
#$db->debug = true;
require_once("$php_includes/weekly_totals.inc.php");
for ($ii = 0; $ii < $week_num; $ii++) {
  $col_styles[5*($ii+1)-1] = 'double';
}

$owed_default = get_static('owed_default');

$javascript_pre = "<script type='text/javascript'>\n";
$javascript_pre .= "var backup_fine_weeks = new Array();\n";
$ii = 1;
foreach ($backup_fine_weeks as $week => $arr) {
  $javascript_pre .= "backup_fine_weeks[" . escape_html($week) . "] = new Array();\n";
  $javascript_pre .= "backup_fine_weeks[" . escape_html($week) . "]['fine_num'] = $ii;\n";
  $ii++;
  foreach ($arr as $key => $val) {
    $javascript_pre .= "backup_fine_weeks[" . escape_html($week) . "][" . 
      dbl_quote($key) . "] = " . escape_html($val,true) . ";\n";
  }
}
$javascript_pre .= "var special_fining = new Array();\n";
if (isset($special_fining)) {
  foreach ($special_fining as $member_name => $arr) {
    $javascript_pre .= "special_fining[" . dbl_quote($member_name) . "] = new Array();\n";
    foreach ($arr as $key => $val) {
      $javascript_pre .= "special_fining[" . dbl_quote($member_name) . "][" . 
        dbl_quote($key) . "] = " . dbl_quote($val) . ";\n";
    }
  }
}
  
if ($cash_hours_auto) {
  $javascript_pre .= "var cash_maxes = new Array();\n";
  foreach ($cash_maxes as $member_name => $val) {
    $javascript_pre .= "cash_maxes[" . dbl_quote($member_name) . "] = " . 
      dbl_quote($val) . ";\n";
  }
}

$table_edit_query = substr($table_edit_query,11);
for ($ii = $week_num; $ii <= $tot_weeks; $ii++) {
  $col_names[] = "owed $ii";
  $col_formats["owed $ii"] = '';
  $col_styles[] = 'input';
  $table_edit_query = "`owed $ii`, " . $table_edit_query;
  $col_sizes[] = 2;
}

$table_edit_query = "select " . $table_edit_query;
//this will be initialized by a col_formats function as it is called -- ooh, fancy
//we can't initialize it here because we haven't done the sql query yet.
$javascript_pre .= "var other_fines = new Array();\n";
$javascript_pre .= 
<<<HEREDOC
var week_num = $week_num;
var tot_weeks = $tot_weeks; 
var zero_partial = $zero_partial;
var fining_percent_fine = $fining_percent_fine;
var fining_rate = $fining_rate;
HEREDOC
;

if ($weekly_fining) {
  $javascript_pre .= "\nvar fining_zero_hours = $fining_zero_hours;\n";
}

$javascript_pre .= "var max_up_hours_fining = " . escape_html($max_up_hours_fining,true) . ";\n";
$javascript_pre .= "var backup_max_up_hours = " . escape_html($backup_max_up_hours,true) . ";\n";
$javascript_pre .= <<<HEREDOC

//when the owed hours are changed, the totals have to be updated
function change_handler(elt) {
  default_change_handler(elt);
  if (!is_input(elt)) {
    return;
  }
  var coords = get_cell(elt);
  if (!coords) {
    return;
  }
  //is this an owed field, i.e. before the Totals field
  if (coords[1] >= 5*week_num+Number(1)) {
    return;
  }
  var row = rows_array[coords[0]].cells;
  var key_weeks = 0;
HEREDOC
  ; 
  if ($cash_hours_auto) {
    $javascript_pre .= <<<HEREDOC
    cash_hours = 0;
HEREDOC
  ;
  }
  if ($nonzeroed_total) {
    $javascript_pre .= <<<HEREDOC
      nonzeroed_total = 0;
HEREDOC
  ;
  }
  $javascript_pre .= <<<HEREDOC
  var runtot = 0;
  var weekly_fining_text = '';
  var fine_weeks = backup_fine_weeks;
  var member_name = get_value(row[0]);
  var oth_fine = other_fines[member_name];
  var total_fine = oth_fine;
  if (special_fining[member_name]) {
    var new_fine_weeks = new Array();
    if (!key_weeks) {
      for (var kk in backup_fine_weeks) {
        key_weeks[key_weeks.length] = kk;
      }
    }
    for (kk = 1; kk <= 5; kk++) {
      var new_week = special_fining[member_name]["fine_week_" + kk];
      if (new_week != -1 && new_week < week_num &&
          new_week != (old_week = key_weeks[kk-1])) {
        if (new_week.length) {
          new_fine_weeks[new_week] = old_week;
          fine_weeks[new_week] = backup_fine_weeks[old_week];
        }
        if (!new_fine_weeks[old_week]) {
          delete fine_weeks[old_week];
        }
      }
    }
  }
HEREDOC
  ;
 if ($weekly_fining) {
   $javascript_pre .= <<<HEREDOC
     var weekly_fining_text = '';
HEREDOC
  ;
 }
 $javascript_pre .= <<<HEREDOC

  for (var ii = 0; ii < week_num; ii++) {
    var this_week = 0;
    var end_fine = false;
    var max_up_hours = backup_max_up_hours;
    if (row[5*ii+3].firstChild) {
      this_week = Number(get_value(row[5*ii+Number(3)]));
    }
    this_week -= Number(get_value(row[5*ii+Number(4)]));
    runtot += this_week;
    if (ii < week_num -1) {
      change_cell(row[5*ii+Number(5)],this_week);
    }
    if (fine_weeks[ii]) {
      end_fine = true;
      max_up_hours = max_up_hours_fining;
    }
    if (ii == tot_weeks) {
      fining_percent_fine = 100;
      zero_partial = 100;
    }

HEREDOC
  ;
 if ($nonzeroed_total) {
   $javascript_pre .= <<<HEREDOC
     nonzeroed_total += this_week;
     if (max_up_hours && nonzeroed_total > max_up_hours) {
        nonzeroed_total = max_up_hours;
      }
HEREDOC
  ;
 }
 $javascript_pre .= <<<HEREDOC
   if (max_up_hours && runtot > max_up_hours) {
HEREDOC
;
if ($cash_hours_auto) {
  $javascript_pre .= <<<HEREDOC
  cash_hours += runtot-max_up_hours;
HEREDOC
  ;
}
$javascript_pre .= "\n     runtot = max_up_hours;\n   }\n";
if (!$weekly_fining) {
  $javascript_pre .= "if (fine_weeks[ii]) {\n";
}
$javascript_pre .= <<<HEREDOC
var fin_floor; var fin_rate; var fin_buffer; var fin_doublefloor;
if (end_fine) {
  fin_floor = fine_weeks[ii]['fining_floor'];
  fin_rate = fine_weeks[ii]['fining_rate'];
  fin_buffer = fine_weeks[ii]['fining_buffer'];
  fin_doublefloor = fine_weeks[ii]['fining_doublefloor'];
}
HEREDOC
;
 if ($weekly_fining) {
   $javascript_pre .= " else {\n";
   $javascript_pre .= "fin_floor = " . escape_html($fining_floor,true) . ";\n";
   $javascript_pre .= "fin_rate = " . escape_html($fining_rate,true) . ";\n";
   $javascript_pre .= "fin_buffer = " . escape_html($fining_buffer,true) . ";\n";
   $javascript_pre .= "fin_doublefloor = " . escape_html($fining_doublefloor,true) . ";\n";
   $javascript_pre .= "}\n";
 }
$javascript_pre .= <<<HEREDOC
if (!fin_floor) {
  fin_floor = 0;
}
if (!fin_buffer) {
  fin_buffer = 0;
}
if (!fin_rate) {
  fin_rate = fining_rate;
}
var temptotal = runtot;
temptotal = Number(temptotal) + Number(fin_floor);
temptotal *= -1;
if (temptotal <= fin_buffer) {
  if (end_fine) {
    change_cell(row[5*week_num+Number(fine_weeks[ii]['fine_num'])],'$0');
  }
}
else {
  temptotal *= fining_percent_fine/100;
  var fine = temptotal*fin_rate;
  if (fin_doublefloor && fin_doublefloor >= fin_floor) {
    fin_doublefloor -= fin_floor;
    fine = Number(fine) + Number((temptotal-fin_doublefloor)*fin_rate);
  }
  if (fine) {
    total_fine = Number(total_fine)+fine;
  if (end_fine) {
    change_cell(row[5*week_num+Number(fine_weeks[ii]['fine_num'])],'$' + 
                Math.round(fine*100)/100);
  }
HEREDOC
  ;
  if ($weekly_fining) {
    $javascript_pre .= <<<HEREDOC
      else {
        if (!weekly_fining_text.length) {
          weekly_fining_text += '(also fined for week ';
        }
        else {
          weekly_fining_text += ', ';
        }
        weekly_fining_text += ii;
        oth_fine = Number(oth_fine)+Number(fine);
      }
HEREDOC
  ;
  }
  if ($cash_hours_auto) {
    $javascript_pre .= <<<HEREDOC
    cash_maxes[member_name] = Number(cash_maxes) + Number(fine/fining_rate);
HEREDOC
    ;
  }
  $javascript_pre .= <<<HEREDOC
  if (ii < week_num-1) {
    if (runtot < -fin_floor) {
      if ((end_fine && fine_weeks[ii]['zero_hours'])
HEREDOC
          ;
          if ($weekly_fining) {
            $javascript_pre .= "|| (fining_zero_hours)";
          }
          $javascript_pre .= <<<HEREDOC
) {
            runtot = Number(-fin_floor) + 
                     Number((Number(runtot)+Number(fin_floor))*zero_partial/100);
          }
        }
      }
    }
}
   }
HEREDOC
    ;
    if (!$weekly_fining) {
      $javascript_pre .=  "\n}\n";
    }
    $javascript_pre .= <<<HEREDOC

    change_cell(row[$total_fine_col-1],'$' + Math.round(oth_fine*100)/100);
    change_cell(row[$total_fine_col],'$' + Math.round(total_fine*100)/100);
    change_cell(row[5*(week_num-1)+Number(5)],runtot);
HEREDOC
    ;
    if ($weekly_fining) {
      $javascript_pre .= <<<HEREDOC
        if (weekly_fining_text.length) {
          weekly_fining_text += ")";
          if (row[$notes_col].childNodes.length < 2) {
            row[$notes_col].appendChild(document.createTextNode(weekly_fining_text));
          }
          else {
            row[$notes_col].childNodes[1] = document.createTextNode(weekly_fining_text);
          }
        }
        else {
          if (row[$notes_col].childNodes.length >= 2) {
            row[$notes_col].removeChild(row[$notes_col].childNodes[1]);
          }
        }
HEREDOC
  ;
    }      
 if ($cash_hours_auto) {
   $javascript_pre .= <<<HEREDOC
     cash_max = cash_maxes[member_name];
   var cashtotal = runtot;
   var fine_rebate = 0;
   if (max_up_hours && cash_hours > 0) {
     fine_rebate = fining_rate*Math.min(cash_hours,cash_max);
     cash_max-=cash_hours;
   }
   if (cashtotal > 0 && cash_max > 0) {
     rebate_hours = Math.min(Math.max(cashtotal,0),cash_max);
     fine_rebate = Number(fine_rebate)+Number(fining_rate*rebate_hours);
     cashtotal -= rebate_hours;
     change_cell(row[$cashtotal_col],cashtotal);
     change_cell(row[$cashtotal_col-1],fine_rebate);
   }
HEREDOC
  ;
 }
$javascript_pre .= <<<HEREDOC
}

    function change_cell(elt,new_val) {
   if (get_value(elt) != new_val) {
     set_value(elt,new_val);
     elt.style.color = "red";
     elt.style.borderColor = "black";
   }
 }

    var last_offset = Number($notes_col)+Number(1);

function initialize_weekly_totals_update() {
  var ii;
  for (ii = 1; ii < 5*week_num; ii++) {
    if ((ii % 5) == 4) {
      continue;
    }
    document.getElementById('checkhide'+ii).checked = false;
    hide_col(ii,'none');
  }
  var startval;
  for (ii = last_offset; ii <= last_offset+Number(tot_weeks)-week_num; ii++) {
    document.getElementById('checkhide'+ii).checked = false;
    hide_col(ii,'none');
  }    
}

 function alter_cell(elt,val) {
   var old_val = get_value(elt);
   if (old_val == val) {
     return 1;
   }
   if (elt.focus) {
     elt.focus();
     set_value(elt,val);
     elt.blur();
     pass_on_change({'originalTarget' : elt});
     return 0;
   }
   return 2;
 }
 
 function change_week_handler() {
   var newval = get_value_by_id('change_hours_week');
   var elt = get_elt_by_id('change_hours_week_end');
   if (get_value(elt) < newval) {
     set_value(elt,newval);
   }
   return true;
 }

  function change_hours() {
    var mem = get_value_by_id('change_hours_member');
    if (!mem.length) {
      alert("You didn't select anyone.");
      return false;
    }
    var hrs = get_value_by_id('change_hours_value');
    var overwrite = get_value_by_id('change_hours_overwrite');
    var week = parseInt(get_value_by_id('change_hours_week'));
    var week_end = parseInt(get_value_by_id('change_hours_week_end'));
    if (week < 0) {
      alert(week + " is less than 0 -- not allowed.  Starting at week 0.");
      week = 0;
    }
    if (week_end < 0) {
      alert("Ending week, " + week_end + " cannot be less than 0.");
      return false;
    }
    if (week > tot_weeks) {
      alert("Can't alter owed for week " + week + 
            " -- the semester isn't that long");
      return false;
    }
    if (week_end > tot_weeks) {
      alert("Can't alter owed for week " + week_end + 
            " -- the semester isn't that long.  Just doing " + 
            "up to " + tot_weeks);
    week_end = tot_weeks;
    }
    var cols = new Array();
    for (; week <= week_end; week++) {
      if (week < week_num) {
        cols[cols.length] = 5*(Number(week)+1)-1;
      }
      else {
        cols[cols.length] = last_offset+Number(week)-week_num;
      }
    }
    if (mem == '(Whole house)') {
      return change_week_hours(cols, hrs, overwrite, rows_array.length);
    }
    else {
      return change_person_hours(mem, cols, hrs, overwrite, rows_array.length);
    }
  }

  function change_week_hours(cols, hrs, overwrite, rowlen) {
    //go through rows, changing hours
    for (var ii = 0; ii < rowlen; ii++) {
      change_row_hours(ii,cols,hrs,overwrite);
    }
    return true;
}

  function change_person_hours(mem, cols, hrs, overwrite, rowlen) {
    //go through rows, looking for matches
    for (var ii = 0; ii < rowlen; ii++) {
      var cur_mem = get_value(rows_array[ii].firstChild);
      if (cur_mem == mem) {
        break;
      }
    }
    if (ii == rowlen) {
      alert("Error!  Couldn't find " + mem + " in list!");
      return false;
    }
    change_row_hours(ii,cols,hrs,overwrite);
    return true;
  }
 
  function change_row_hours(row_num,cols,hrs,overwrite) {
    var arr = rows_array[row_num].childNodes;
    for (var jj in cols) {
      var elt = arr[cols[jj]].firstChild;
      if (!overwrite) {
        if (get_value(elt) != $owed_default) {
          continue;
        }
      }
      if (alter_cell(elt,hrs) > 1) {
        alert("Couldn't alter element in row " + row_num
              + " with value " + get_value(elt));
      }
    }
    return true;
  }
</script>
HEREDOC;
	     
$onload_function = 'initialize_weekly_totals_update';
if (!isset($body_insert)) {
  $body_insert = '';
}
$houselist = get_houselist();
$body_insert .= <<<EOS
<div style='border: blue 1px solid; width: 90%'>
Change hours for <select id='change_hours_member'>
<option selected>
<option>(Whole house)
EOS
  ;
foreach ($houselist as $mem) {
$body_insert .= "<option>" . escape_html($mem) . "\n";
}
$body_insert .= <<<EOS
</select>
to
<input id='change_hours_value' style='border-width: 1px; border-style: solid;' value=3 size=1> starting at week
<input id='change_hours_week' style='border-width: 1px; border-style: solid;'
onchange='change_week_handler()' value=0 size=1> and going through week
<input id='change_hours_week_end' style='border-width: 1px; border-style: solid;' value=0 size=1>.<br/>(<input type=checkbox id='change_hours_overwrite'>
<label for='change_hours_overwrite'>check here to change hours owed 
even if a person currently doesn't owe $owed_default hours for that week)</label><br/>
<input class='button' type=submit onclick='change_hours()' value='Change!'/>
</div>
EOS;
require_once("$php_includes/table_edit.php");
?>
