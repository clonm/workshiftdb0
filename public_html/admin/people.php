<?php
//just display a list of people, for assign_shifts to manipulate
require_once('default.inc.php');
#$db->debug = true;
$houselist = get_houselist();
?>
<HTML><head><title>People</title>
<style>
BODY {
  font-family: Arial, Verdana, Geneva, Helvetica, sans-serif;
  color: Black;
  margin-left: 5px;
  margin-right: 0px;
  background-color: White;
  white-space: nowrap;
}

</style>
<script type="text/javascript">
function show_prefs(e) {
  if (!e) e = window.event;
  if (!e) return true;
  var code;
  var targ;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  var show = window.open('show_prefs.php?person=' + 
			 divhouselist[targ.id.substring(4,targ.id.length)],'show_prefs'); 
  show.focus();
}

var divhouselist = [<?=js_array($houselist)?>];
var houselist = new Array();
<?php
for ($ii = 0; $ii < count($houselist); $ii++) {
?>
 houselist[<?=$ii?>] = new Array();
 houselist[<?=$ii?>][0] = <?=dbl_quote($houselist[$ii])?>;
 houselist[<?=$ii?>][1] = 10000000;
 houselist[<?=$ii?>][2] = -10;
 houselist[<?=$ii?>][3] = null;
 houselist[<?=$ii?>][4] = <?=$ii?>;
 houselist[<?=$ii?>][5] = 0;
<?php
    }

$templist = array_flip($houselist);
$listhouse = array_flip($houselist);
if ($archive) {
  $submit_ok = false;
  $submit_res = $db->Execute("show columns from `{$archive}personal_info`");
  while ($submit_row = $submit_res->FetchRow()) {
    if ($submit_row['Field'] == 'submit_date') {
      $submit_ok = true;
      break;
    }
  }
}

if (!$archive || $submit_ok) {
  $res = $db->Execute("select distinct `{$archive}house_list`.`member_name`," .
                      "`submit_date` from `{$archive}personal_info`" .
                      " right join `{$archive}house_list` on " .
                      "`{$archive}personal_info`.`member_name` = " .
                      "`{$archive}house_list`.`member_name` " .
                      "where `submit_date` order by `submit_date`");
  $ii = 0;
  $submit_date = null;
  $firstflag = true;
  
  while ($row = $res->FetchRow()) {
    if (!$firstflag &&
        $row['submit_date'] == $submit_date) {
      $ii--;
    }
    else {
      $firstflag = false;
    }
    unset($templist[$row['member_name']]);
    $submit_date = $row['submit_date'];
    echo "houselist[" . $listhouse[$row['member_name']] . "][1] = $ii;\n";
    $ii++;
  }
}

foreach ($templist as $mem => $junk) {
  echo "houselist[" . $listhouse[$mem] . "][1] = $ii;\n";
}

$res = $db->Execute("select `member_name`, `category`,`points`,`app_number` " .
                    "from `{$archive}points` order by " .
                    "`category`, `points` desc,`app_number`");
$ii = 0;
$category = $points = $app_number = null;
$firstflag = true;
while ($row = $res->FetchRow()) {
  if (!$firstflag && 
      $row['category'] === $category &&
      $row['points'] === $points &&
      $row['app_number'] === $app_number) {
    $ii--;
  }
  else {
    $firstflag = false;
  }
  $category = $row['category'];
  $points = $row['points'];
  $app_number = $row['app_number'];
  echo "houselist[" . $listhouse[$row['member_name']] . "][2] = $ii;\n";
  $ii++;
}
?>

function maybe_show_prefs(e) {
  if (!e) e = window.event;
  if (!e) return true;
  var code;
  if (e.keyCode) code = e.keyCode;
  else if (e.which) code = e.which;
  //enter
  if (code == 13) {
    show_prefs(e);
  }
  return true;
}

var container;
var sort_select;
var sort_dir;

window.onkeypress=maybe_show_prefs;
function initialize_people() {
  document.getElementById('list').ondblclick = show_prefs;
  container = document.getElementById('list');
  sort_select = document.getElementById('sort_select');
  sort_dir = document.getElementById('sort_dir');
  for (var ii = 0; ii < houselist.length; ii++) {
    parent.list[ii] = document.getElementById('span'+ii);
  }
}

var temptemptemp = 0;
var temptemp = 0;
var level = 0;
function util_sort(mem1,mem2) {
  var temp_level = level;
  retval = 0;
  while (!retval && temp_level < sort_order.length) {
    var ord = sort_order[temp_level];
    if (ord == 3) {
      used_prio = true;
    }
    if (ord == 5) {
      used_hours = true;
    }
    retval = (mem1[ord]-mem2[ord])*(sort_reverse[temp_level] ^ (ord == 3)?-1:1);
    temp_level++;
  }
  return retval;
}


var sort_order = [<?=js_array(explode(',',get_static('people_sort_order','4,1,2,3,5')))?>];
var sort_reverse = [0,0,0,0,0];

for (var ii = 0; ii < sort_order.length; ii++) {
  if (sort_order[ii].charAt(sort_order.length-1) == 'r') {
    sort_reverse[ii] = 1;
  }
}

var used_prio = false;
var used_hours = false;

function sort_list(shift_change_only) {
  //  alert('sort ' + used_prio);
  if (shift_change_only == 1 && !used_prio) {
    return;
  }
  if (shift_change_only == 2 && !used_hours) {
    return;
  }
  if (!shift_change_only) {
    var newtop = sort_select.value;
    if (sort_order[0] == newtop) {
      sort_reverse[0] = !sort_reverse[0];
    }
    else {
      var temp_sort = newtop;
      var temp_rev = 0;
      for (var ii = 0; ii < sort_order.length; ii++) {
        if (sort_order[ii] == temp_sort) {
          temp_rev = sort_reverse[ii];
          break;
        }
      }
      var temp2, temp3;
      for (var ii = 0; ii < sort_order.length; ii++) {
        temp2 = sort_order[ii];
        sort_order[ii] = temp_sort;
        temp_sort = temp2;
        temp2 = sort_reverse[ii];
        sort_reverse[ii] = temp_rev;
        temp_rev = temp2;
        if (temp_sort == newtop) {
          break;
        }
      }
    }

  }
  if (sort_reverse[0]) {
    sort_dir.innerHTML = '&nbsp;&uarr;&nbsp';
  }
  else {
    sort_dir.innerHTML = '&nbsp;&darr;&nbsp;';
  }
  used_prio = false;
  used_hours = false;
  houselist.sort(util_sort);
  for (ii = 0; ii< houselist.length; ii++) {
    container.appendChild(parent.list[houselist[ii][4]]);
    //    parent.listhouse[houselist[ii][0]] = ii;
  }
  return false;
}

</script>
</head>
<body onload='initialize_people()'>
<?php
if ($url_name == 'co') {
  $houses_array = explode(',',
                          get_static('houses_array',
                                     'aca,ath,caz,clo,con,dav,euc,fen,hip,hoy,kid,kng,lot,nsc,rid,roc,she,stb,wil,wol'));
    ?>
<?php
   foreach ($houses_array as $house) {
     print "<div id='span$house'>$house: </div>\n";
   }  
print "<hr>";
}
?>     
<select id='sort_select' onchange='sort_list(false)' title='Change to re-order the list'>
<option value=1>Date submitted
<option value=2>Points
<option value=3>Wanted
<option selected value=4>Name
<option value=5>Hours
</select><span id='sort_dir' onclick='return sort_list(false)' style='background-color: lightgray;' width=20em title='Click to reverse sort'>&nbsp;&darr;&nbsp;</span>
<div id='list'><?php
$ii = 0;
foreach($houselist as $member) { ?><div id='span<?=$ii?>' title='double-click to view prefs'><?=escape_html($member)?></div><?php 
   $ii++;
} ?>
</div>
</body>
</html>
