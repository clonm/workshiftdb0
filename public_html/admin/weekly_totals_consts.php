<?php
require_once('default.inc.php');
$start = get_static('semester_start');
function end_date($week) {
global $start;
  return escape_html(date('M j',strtotime($start)+86400*6+86400*7*$week));
}
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<html><head><title>Update weekly totals numbers</title>
<script type="text/javascript" 
src="<?=escape_html("$html_includes/table_edit.utils.js")?>"></script>

<script type="text/javascript">
var prop_arr = new Array("buffer","floor","doublefloor",
                         "zero_hours","fine");
function make_same(ii) {
  for (var jj = ii-1; jj >= 0; jj--) {
    if (get_value_by_id('fine_' + jj)) {
      for (var prop in prop_arr) {
        set_value_by_id(prop_arr[prop] + '_' + ii,
                        get_value_by_id(prop_arr[prop] + '_' + jj));
        enable_elt_by_id(prop_arr[prop] + '_' + ii,true);
      }
      return true;
    }
  }
  return false;
}

function change_weekly_fining() {
  var val = get_value_by_id('weekly_fining_bool');
  for (var prop in prop_arr) {
    if (prop_arr[prop] == 'fine') {
      continue;
    }
    enable_elt_by_id('fining_' + prop_arr[prop],val);
  }
}

function initialize_form() {
  var elt;
  ii = 0;
  change_weekly_fining();
  while (get_elt_by_id('fine_' + ii)) {
    change_fine(ii);
    ii++;
  }
}

function change_fine(ii) {
  var val = get_value_by_id('fine_' + ii);
  for (var prop in prop_arr) {
    if (prop_arr[prop] == 'fine') {
      continue;
    }
    enable_elt_by_id(prop_arr[prop] + '_' + ii,val);
  }
}

function validate_form() {
  var alerts = '';
    var val = get_value_by_id('cur_week');
  if (val && !is_whole_number(val)) {
     alerts += val + ' is not a valid value for the current week.  It must be a whole ' +
           "number or blank.\n";
  get_elt_by_id('cur_week_text').style.color = 'red';
    retflag = false;
  }
  else {
    get_elt_by_id('cur_week_text').style.color = 'black';
  }
  val = get_value_by_id('fining_rate');
  if (!is_positive_decimal(val)) {
    alerts += val + ' is not a valid value for the fining rate.  ' + 
      "It must be a positive decimal number.\n";
    get_elt_by_id('fining_rate_text').style.color = 'red';
    retflag = false;
  }
  else {
    get_elt_by_id('fining_rate_text').style.color = 'black';
  }
  var nonzeroed_flag = get_value_by_id('nonzeroed_total_hours_bool');
  if (get_value_by_id('weekly_fining_bool')) {
    nonzeroed_flag &= !get_value_by_id('fining_zero_hours');
    val = get_value_by_id('fining_buffer');
    if (val.length && val != 0 && !is_positive_decimal(val)) {
      alerts += val + ' is not a valid value for the fining buffer.  '  +
        "It must be a non-negative decimal number.\n";
      get_elt_by_id('fining_buffer_text').style.color = 'red';
    }
    else {
      get_elt_by_id('fining_buffer_text').style.color = 'black';
    }
    val = get_value_by_id('fining_floor');
    if (val.length && val != 0 && !is_positive_decimal(val)) {
      alerts += val + ' is not a valid value for the fining floor.  ' +
        "It must be a non-negative decimal number.\n";
      get_elt_by_id('fining_floor_text').style.color = 'red';
    }
    else {
      get_elt_by_id('fining_floor_text').style.color = 'black';
    }
    val = get_value_by_id('fining_doublefloor');
    if (val.length && val != 0 && !is_positive_decimal(val)) {
      alerts += val + ' is not a valid value for the double-fining floor.  ' +
        "It must be a non-negative decimal number.\n";
      get_elt_by_id('fining_doublefloor_text').style.color = 'red';
    }
    else {
      get_elt_by_id('fining_doublefloor_text').style.color = 'black';
    }
  }
  val = get_value_by_id('max_up_hours');
  if (val.length && !is_positive_decimal(val)) {
    alerts += val + ' is not a valid value for the maximum up hours.  ' + 
      "It must be a positive decimal number.\n";
    get_elt_by_id('max_up_hours_text').style.color = 'red';
  }
  else {
    get_elt_by_id('max_up_hours_text').style.color = 'black';
  }
  val = get_value_by_id('max_up_hours_fining');
  if (val.length && !is_positive_decimal(val)) {
    alerts += val + ' is not a valid value for the maximum up hours at ' +
      "fining periods.  It must be a positive decimal number.\n";
    get_elt_by_id('max_up_hours_fining_text').style.color = 'red';
  }
  else {
    get_elt_by_id('max_up_hours_fining_text').style.color = 'black';
  }
  val = get_value_by_id('fining_percent_fine');
  if (val.length && !is_whole_number(val)) {
    alerts += val + ' is not a valid value for the fining percent.  ' + 
      "It must be a whole number or blank.\n";
    get_elt_by_id('fining_percent_fine_text').style.color = 'red';
  }
  else {
    get_elt_by_id('fining_percent_fine_text').style.color = 'black';
  }
  val = get_value_by_id('fining_zero_partial');
  if (val.length && !is_whole_number(val)) {
    alerts += val + ' is not a valid value for the percentage of hours ' +
      "remaining after zeroing.  It must be a whole number or blank.\n";
    get_elt_by_id('fining_zero_partial_text').style.color = 'red';
  }
  else {
    get_elt_by_id('fining_zero_partial_text').style.color = 'black';
  }
  ii = 0;
  while (get_elt_by_id("fine_" + ii)) {
    if (!get_value_by_id("fine_" + ii)) {
      ii++;
      continue;
    }
    var this_fine = false;
    val = get_value_by_id('buffer_' + ii);
    if (val.length && val != 0 && !is_positive_decimal(val)) {
      alerts += val + ' is not a valid value for the fining buffer in week ' 
        + ii + ".  It must be a non-negative decimal number.\n";
      this_fine = true;
    }
    val = get_value_by_id('floor_' + ii);
    if (val.length && val != 0 && !is_positive_decimal(val)) {
      alerts += val + ' is not a valid value for the fining floor in week ' 
        + ii + ".  It must be a non-negative decimal number.\n";
      this_fine = true;
    }
    val = get_value_by_id('doublefloor_' + ii);
    if (val.length && val != 0 && !is_positive_decimal(val)) {
      alerts += val + ' is not a valid value for the double-fining floor ' +
        'in week ' + ii + ".  It must be a non-negative decimal number.\n";
      this_fine = true;
    }
    //    val = get_value_by_id('rate_' + ii);
    //    if (!is_positive_decimal(val)) {
    //  alerts += val + ' is not a valid value for the fining rate in week ' + 
    //    ii + ".  It must be a positive decimal number.\n";
    //  this_fine = true;
    //}
    nonzeroed_flag &= !get_value_by_id('zero_hours_' + ii);
    get_elt_by_id('fine_' + ii + '_text').style.color = this_fine?'red':'black';
    ii++;
  }
  if (nonzeroed_flag) {
    alerts += 'There is no point in keeping track of the nonzeroed totals ' + 
      "if you never zero hours at the end of any week or fining period.\n";
  }
  if (alerts) {
    alert(alerts);
    return false;
  }
  return true;
}



</script>
      

</head><body onload='initialize_form()'>
<?php 
print_help();
#}
?>
<form onsubmit='return validate_form()'
action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
<span id='cur_week_text'>Current week:
<input id='cur_week' name='cur_week' value='<?=get_static("cur_week")?>'></span>
(This is determined automatically, but if you are behind by a week, you can
 use this so that the week you're up to will be displayed.)
<b>REMEMBER TO UNSET THIS WHEN YOU'VE CAUGHT UP!!</b><p>
The following sets up your house's fining system.  You will have one policy for
normal weeks, and then a list of fining weeks, and a fining policy for each of
them.  Remember that CO policy is that end-of-semester fines are done with no
fining buffer and a fining floor of 0 -- members are fined for every negative
  hour they are at.<p>
<span id='fining_rate_text'>Fining rate: 
<input id='fining_rate' name='fining_rate'
value='<?= get_static("fining_rate",12)?>'></span>
This is the rate at which down hours are fined, whenever fines are
calculated.  If some fining period has a special fining rate, you can
enter it below.<br>
<h4>Week-to-week values</h4>
Is there weekly fining?  If members cannot be fined except at fining periods,
uncheck this box. 
<input type='checkbox' id='weekly_fining_bool' 
name='weekly_fining_bool' <?=get_static("weekly_fining",false)?'checked':''?>'
onchange='change_weekly_fining()'><br>
<span id='fining_buffer_text'>Fining buffer: 
<input id='fining_buffer' name='fining_buffer' value='<?= get_static("fining_buffer",0)?>'></span>
Members are not fined until their down hours exceed this buffer.  Most houses
have no buffer on ordinary weeks<br>
<span id='fining_floor_text'>Fining floor: 
<input id='fining_floor' name='fining_floor' value='<?= get_static("fining_floor",10)?>'></span>
All hours above this amount are fined at the workshift rate, set below.  Stebbins
has a 10 hour fining floor on ordinary weeks -- you are not fined unless you are
down more than 10 hours.<p>
<span id='fining_doublefloor_text'>Double-fining floor: 
<input name='fining_doublefloor' id='fining_doublefloor' 
value='<?=get_static("fining_doublefloor",null)?>'></span>
All hours above this amount are additionally fined at the workshift rate, set below, so these
hours will be double-fined, since they'll be fined for being below the floor and the double-fining
floor too.<br>
Are down hours zeroed each week?  As in, if a member is 3 hours down at the end
of one week, are they still three hours down at the start of the next?  Probably
they are still down.  If down hours reset each week, check this box.
<input type=checkbox name='fining_zero_hours_bool' id='fining_zero_hours'
<?=get_static("fining_zero_hours",false)?'checked':''?>'><br>
<h4>Miscellaneous features</h4>
<span id='max_up_hours_text'>Is there a maximum number of hours a member can be up?  If so, enter it here
<input id='max_up_hours' name='max_up_hours' value='<?=get_static('max_up_hours',null)?>'></span><br>
Should up hours at the end of the semester automatically be applied to repay workshift fines?
Stebbins has this system.  Read the <a href='help.html#cash_hours_auto'>help file</a>
for more information.<input type=checkbox name='cash_hours_auto_bool'
<?=get_static('cash_hours_auto',null)?'checked':''?>><br>
<span id='max_up_hours_fining_text'>
Do you have a different maximum number of up hours that a member can be at the
 end of a fining period?  (Are you Hoyt or CZ?)  Enter it here:
<input name='max_up_hours_fining' id='max_up_hours_fining' size=3
value='<?=get_static("max_up_hours_fining",null)?>'></span><br>
Are you zeroing hours at fining periods (or week-by-week), and yet you still want a tally kept
of the hours without zeroing?  (Hoyt is the only house I know of that needs this)
<input type=checkbox name='nonzeroed_total_hours_bool' id='nonzeroed_total_hours_bool'
<?=get_static('nonzeroed_total_hours',null)?'checked':''?>><br>
<span id='fining_percent_fine_text'>
 Do you not fine for 100% of the hours down at fining periods, but rather some
 percentage?  (Are you Wilde?)  If so, enter that percentage (75 for Wilde) here:
<input name='fining_percent_fine' id='fining_percent_fine' size=3 
value='<?=get_static("fining_percent_fine",null)?>'>%<br></span>
<span id='fining_zero_partial_text'>
Do you zero hours only partially after fines, so some percentage is left of the
negative hours, they are not totally reset?  (Are you Wilde?)  If so, enter
the percentage <strong>that is left</strong>:
<input name='fining_zero_partial' id='fining_zero_partial' size=3
value='<?=get_static("fining_zero_partial",null)?>'>%</span><br>

<h4>Fining periods</h4>
For each week which ends a fining period, please check the box, and make sure that the
numbers for fining floor and buffer are accurate.  The fining rate is the same as the general
fining rate unless you fill it in specially.
<table border=3><tr>
<td>Week and end date</td><td>Fining?</td><td>Fining buffer</td><td>Fining floor</td>
<td>Double-fining floor</td><td>Zero hours?</td>
<!-- <td>Fining rate</td> -->
</tr>
<?php
#}
#';
$res = $db->Execute("select * from `fining_periods` order by `week`");
$row = $res->FetchRow();
$rate = $row['fining_rate'];
$buffer = $row['fining_buffer'];
$floor = $row['fining_floor'];
$doublefloor = $row['fining_doublefloor'];
$zero_hours = $row['zero_hours'];
$tot_weeks = get_static('tot_weeks',18);
if (!is_numeric($tot_weeks)) {
set_static('tot_weeks',18);
$tot_weeks = get_static('tot_weeks');
}
for ($ii = 0; $ii <= $tot_weeks; $ii++) {
  if ($row && $row['week'] == $ii) {
    $rate = $row['fining_rate'];
    $buffer = $row['fining_buffer'];
    $floor = $row['fining_floor'];
    $doublefloor = $row['fining_doublefloor'];
    $zero_hours = $row['zero_hours'];
    $fining = ' checked';
    $row = $res->FetchRow();
  }
  else {
    if ($ii == $tot_weeks) {
      $fining = ' checked';
      $buffer = 0;
      $floor = 0;
      $zero_hours = false;
    }
    else {
      $fining = '';
    }
  }
#}
?>
<tr id='fine_<?=$ii?>_text'><td><?=$ii?>, <?=end_date($ii)?></td>
<td><input type=checkbox name='fine_<?=$ii?>' id='fine_<?=$ii?>' 
onchange='change_fine(<?=$ii?>)' <?=$fining?>></td>
<td><input size=5 name='buffer_<?=$ii?>' id='buffer_<?=$ii?>' 
value='<?=escape_html($buffer)?>'></td>
<td><input size=5 name='floor_<?=$ii?>' id='floor_<?=$ii?>' 
value='<?=escape_html($floor)?>'></td>
<td><input size=5 name='doublefloor_<?=$ii?>' id='doublefloor_<?=$ii?>'
value='<?=escape_html($doublefloor)?>'></td>
<td><input type=checkbox name='zero_hours_<?=$ii?>' id='zero_hours_<?=$ii?>' 
<?=$zero_hours?'checked':''?> ></td>
<!-- <td><input size=5 name='rate_<?=$ii?>' id='rate_<?=$ii?>' 
value='<?=escape_html($rate)?>'></td> -->
<td>
<?php
if ($ii != 0) {
?>
<input type=button value='Make this a fining period like the last one'
onclick='make_same(<?=$ii?>)'>
<?php
}
?>
</td>
</tr>
<?php
#{
}
?>
</table>
<input type=submit value=Submit>
</html>
<?php
#{
  exit;
}
?>
<html><head><title>Update Weekly Totals Numbers</title></head><body>
<?php
print_help();
foreach (array('weekly_fining_bool','fining_zero_hours_bool',
               'cash_hours_auto_bool', 'nonzeroed_total_hours_bool') as $key) {
  $real = substr($key,0,-5);
  print("Turned " . escape_html($real) . " o");
  if (isset($_REQUEST[$key])) {
    set_static($real,true);
    print("n");
  }
  else {
    set_static($real,false);
    print("ff");
  }
  print("<br>\n");
}
foreach ($_REQUEST as $key => $val) {
  if ($val === '') {
    $_REQUEST[$key] = $val = null;
  }
  if (substr($key,-5) == '_bool') {
    continue;
  }
  if (!preg_match('/[0-9]$/',$key)) {
    set_static($key,$val);
    print("Set " . escape_html($key) . " to " . escape_html($val,true) . "<br>\n");
  }
}
$tot_weeks = get_static('tot_weeks');
for ($ii = 0; $ii <= $tot_weeks; $ii++) {
  $db->Execute("delete from `fining_periods` where `week` = ?",
               array($ii));
  if (!array_key_exists("fine_$ii",$_REQUEST)) {
    print("Week $ii does not end a fining period<br>");
    continue;
  }
  $db->Execute("insert into `fining_periods` values (null,?,?,?,?,?,?)",
               array($ii, $_REQUEST['fining_rate'], $_REQUEST["buffer_$ii"],
                     $_REQUEST["floor_$ii"], $_REQUEST["doublefloor_$ii"],
                     isset($_REQUEST["zero_hours_$ii"])?1:null));
  print("Week $ii, ending " . end_date($ii) . ", ends a fining period with fining buffer " . 
        escape_html($_REQUEST["buffer_$ii"],true) .
        ", fining floor " . escape_html($_REQUEST["floor_$ii"],true) . ", double-fining floor " .
        escape_html($_REQUEST["doublefloor_$ii"],true) . ", hours are <b>" . 
        (isset($_REQUEST["zero_hours_$ii"])?'':'not ') . "zeroed</b> after the period ends, and fining rate " . 
        escape_html($_REQUEST["fining_rate"]) . "<br>\n");
}
?>
</body>
</html>
