<?php
$body_insert = '';
require_once('default.inc.php');
?>
<html><head><title>Vote</title>
<?php
#$db->debug = true;
if (authorized_user($member_name,'president') && isset($_REQUEST['enter-ballot'])) {
  $witnesses = require_witnesses(2);
  $manual_entry = true;
  $member_name = get_static('dummy_string','XXXXX');
}
else {
  $manual_entry = false;
}

if (authorized_user($member_name,'nonvoter')) {
  exit("You are not eligible to vote!");
}

function list_elections($bad_election = null,$manual_entry = false) {
  global $db;
  if ($manual_entry) {
    $res = $db->Execute('select `election_name` from `elections_record` where ' .
                        '`end_date` <= unix_timestamp() and anon_voting < 2');
  }
  else {
    $res = $db->Execute('SELECT ' . bracket('election_name') . ' FROM ' .
                        bracket('elections_record') . 
                        ' WHERE unix_timestamp() < `end_date`');
  }
  if (is_empty($res)) {
    exit("No elections currently open.<p>\n");
  }
  while ($row = $res->FetchRow()) {
    if ($row['election_name'] != $bad_election) {
      $elections[] = $row['election_name'];
    }
  }
  if (!count($elections)) {
    exit("No elections open for you to vote in.");
  }
#}
?>
<form method='GET' action='<?=this_url()?>'>
<?php 
$ii = 0;
foreach ($elections as $election) {
#}
?>
<label for='<?=$ii?>' >
<input type=radio name='election_name' id='<?=$ii++?>' value='<?=escape_html($election)?>'
checked><?=escape_html($election)?></label><br>
<?php 
#{
}
?>
<input type='submit' value='Choose election'>
</form></body></html>
<?php
exit;
}

if (!array_key_exists('election_name',$_REQUEST)) {
list_elections(null,$manual_entry);
}
$election_name = $_REQUEST['election_name'];

$row = $db->GetRow("select unix_timestamp() < `end_date` as `open`, " .
                   "`anon_voting` < 2 as `status` from `elections_record` " .
                   "where `election_name` = ?", array($election_name));
if (is_empty($row)) {
print ("Election does not exist.");
list_elections(null,$manual_entry);
}
if ((!$manual_entry && $row['open']) || 
    ($manual_entry && !$row['open'] && $row['status'])) {
  $votable = true;
}
else {
  $votable = false;
}
$row = $db->GetRow("select `anon_voting` from `elections_record` where `election_name` = ?",
                   array($election_name));
$anon_voting = $row['anon_voting']%2;
if ($anon_voting && !$manual_entry) {
  $row = $db->GetRow("select count(*) as ct from `voting_record`
where `election_name` = ? and member_name = ?"
                     ,array($election_name,$member_name));
  if ($row['ct'] > 0) {
    $votable = false;
  }
}
if (!array_key_exists('page_type',$_REQUEST) || 
$_REQUEST['page_type'] == 'member_input') {
$descript_file = get_election_attrib('descript_filename',null,false);
  if ($descript_file && !array_key_exists('internal',$_REQUEST)) {
    $internal = false;
    $get_str = '';
    foreach ($_GET as $key => $val) {
      if (strlen($get_str)) {
        $get_str .= '&';
      }
      $get_str.= $key;
      if (strlen($val)) {
        $get_str .= '=' . $val;
      }
    }
    $page_name = $_SERVER['REQUEST_URI'];
    $page_name = substr($page_name,0,strpos($page_name,'?'));
?>
<frameset rows="50%,50%">
<frame src='descript_file.php?election_name=<?=escape_html($election_name)?>'>
<frame src='<?=escape_html($page_name . '?' . $get_str)?>&internal'>
</frameset>
<noframes>
<?php
   }
  else {
    $internal = true;
  }
  if (!function_exists('race_header')) {
    function race_header($race_name) {
      print "<p><h4>" . escape_html($race_name) . "</h4>";
    }
  }
  if (!function_exists('checked_box')) {
    function checked_box($id,$ind,$def_val = null,$cand = null,$show_abstain = null) {
      global $checktype, $house_name;
      if ($checktype == 'checkbox') {
        return array_key_exists($id . "-" . $ind,$_REQUEST)?' checked':'';
      }
      if ($checktype == 'radio') {
        return ((array_key_exists($id,$_REQUEST) && $_REQUEST[$id] == $cand)) ||
        !array_key_exists($id,$_REQUEST) && 
        ($ind == -1 || !$show_abstain && $cand == $def_val)?' checked':'';
      }
      if ($checktype == 'input') {
        return isset($_REQUEST[$id . "-" . $ind])?" value='" . $_REQUEST[$id . "-" . $ind] . "'" : '';
      }
    }
  }
#}
?>
<script type="text/javascript">
function abstain_race(race_num) {
  if_input = (document.getElementById(race_num + '-0').type == 'text');
  for (ii = 0, elt = document.getElementById(race_num + '-' + ii); 
       elt; ++ii, elt = document.getElementById(race_num + '-' + ii)) {
    if (if_input) {
      elt.value = '';
    }
    else {
      elt.checked = false;
    }
  }
  return;
}

function remove_abstain(race_num) {
  document.getElementById(race_num + '--1').checked = false;
}

function validate_checkbox(race_num) {
  ctr = 0;
  for (ii = 0, elt = document.getElementById(race_num + '-' + ii); 
       elt; ++ii, elt = document.getElementById(race_num + '-' + ii)) {
    if (elt.checked) {
      if (++ctr > race_max[race_num]) {
        alert("You have checked too many boxes in " + race_names[race_num] + 
              ".  Please uncheck one.");
        return false;
      }
    }
  }
  return true;
}
function validate_number(num) {
  var numexp = /^[0-9]*$/;
  return (numexp.test(num));
}

function validate_ranking(race_num) {
  var numbers = new Array();
  var maxrank = 0;
  for (var ii = 0, elt = document.getElementById(race_num + '-' + ii);
         elt; ++ii, elt = document.getElementById(race_num + '-' + ii)) {
    var rank = elt.value;
    if (rank != '') {
      if (!validate_number(rank) || rank == 0) {
        alert("Invalid rank: " + rank + " in " + race_names[race_num]);
        elt.focus();
        return false;
      }
      if (numbers[rank]) {
        alert("You gave two choices rank " + rank + " in " + race_names[race_num]);
        elt.focus();
        return false;
      }
      numbers[rank] = 1;
      if (rank > maxrank) {
        maxrank = rank;
      }
    }
  }
  if (maxrank) {
    for (var ii = 1; ii <= maxrank; ii++) {
      if (numbers[ii] && numbers[ii] == 1) {
        continue;
      }
      alert("You didn't rank anything " + ii + " but you have things ranked below that" +
            " in " + race_names[race_num]);
      return false;
    }   
    elt = document.getElementById('race_num' + '--1');
    if (elt) {
      if (elt.checked == true) {
        alert("You abstained and also ranked choices" + " in " + race_names[race_num]);
        return false;
      }
    }
  }
  return true;
}

function validate_ballot() {
  for (var ii in race_nums) {
    ii = race_nums[ii];
    var elt = document.getElementById(ii+'-0');
    if (!elt) {
      continue;
    }
    switch (elt.type) {
    case 'text':
      if (!validate_ranking(ii)) {
        return false;
      }
      break;
    case 'checkbox':
      if (!validate_checkbox(ii)) {
        return false;
      }
    case 'radio':
    case 'default':
    }
  }
  return true;
}
      
</script>
<style>
pre { 
<?php
print white_space_css() . ";\n";
?>
}
</style>
  
<base target='_top'>
</head>
<body>
<?php
if ($internal) {
  print $body_insert;
}
$description = get_election_attrib('descript',null,false);
if (!get_election_attrib('descript_html',null,false)) {
  $description = "<div style='white-space: pre'>" . escape_html($description) . "</div>";
}
if ($descript_file) {
  print "<p><a href='descript_file.php?election_name=" .
    escape_html($election_name) . "'>File with more info on election " .
    "(what's in the frame above)</a></p>";
}
if ($votable) {
?>
<?=escape_html($member_name)?><br>
<?php
/* Browser specific (not valid) styles to make preformatted text wrap */
/* white-space: -moz-pre-wrap;  /* Mozilla, since 1999 
 white-space: -pre-wrap;      /* Opera 4-6 
 white-space: -o-pre-wrap;    /* Opera 7 
 word-wrap: break-word;       /* Internet Explorer 5.5+ */
  if ($anon_voting) {
    print("Voting is anonymous.  You can only vote once.<p>");
  }
  else {
    print("Voting is not anonymous.  You may re-vote as many times as you like.<p>");
  }
 $page_name = $_SERVER['REQUEST_URI'];
 if (array_key_exists('internal',$_REQUEST)) {
   $page_name = substr($page_name,0,-9);
 }
 $uri = escape_html("http" . (array_key_exists('HTTPS',$_SERVER) && 
                              $_SERVER['HTTPS'] == 'on'?'s':'') . "://" .
                    $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT'] . 
                    $page_name);
?>
<form action="<?=$uri?>" 
method='POST'>
<input type='hidden' name='page_type' id='page_type' value='review_vote'>
<input type='hidden' name='election_name' value='<?=escape_html($election_name)?>'>
<?php
}
else {
  print "<p>(You may not vote in this election -- either you have already cast a " .
    "vote, or election is closed.)</p>\n<a href='voting.php'>Choose another election</a> if you wish to vote<p>\n";
}
if ($description) {
  print("<div>$description</div>");
}
$race_nums = array();
$race_names = array();
$race_max = array();
$nameres = $db->Execute('select `autoid`,`race_name` from `elections_attribs` ' .
                        'where `election_name` = ? and `attrib_name` = ? order by 0+`attrib_value`',
                        array($election_name,'race_name'));
while ($namerow = $nameres->FetchRow()) {
  $race_autoid = $namerow['autoid'];
  $race_name = $namerow['race_name'];
  $race_names[$race_autoid] = $race_name;
  $race_nums[] = $race_autoid;
  $res = $db->Execute('select `attrib_name`,`attrib_value` from `elections_attribs`' .
                      ' where `election_name` = ? and `race_name` = ?',
                      array($election_name,$race_name));
  $row = array();
  while ($temprow = $res->FetchRow()) {
    $row[$temprow['attrib_name']] = $temprow['attrib_value'];
  }
  $candidates = explode("\n",$row['candidates']);
  if (!$candidates[0]) {
    array_shift($candidates);
  }
  if ($row['member_add'] && 
      isset($_REQUEST[$race_autoid . '-member_add'])) {
    if ($_REQUEST[$race_autoid . '-member_add']) {
      $cand = $_REQUEST[$race_autoid . '-member_add'];
      if (array_search($cand,$candidates) !== false) {
        print "<h4>Candidate " . escape_html($cand) . " is already on the ballot</h4>";
      }
      else {
        $candidates[] = $cand;
        $db->Execute("update `elections_attribs` " .
                       "set `attrib_value` = ? " .
                       'where `election_name` = ? and `race_name` = ? and `attrib_name` = ?',
                       array(join("\n",$candidates),
                             $election_name,$race_name,'candidates'));
      }
    }
  }
  $count = count($candidates);
  race_header($race_name);
    if (isset($row['race_descript'])) {
      print "<div";
      if (!isset($row['descript_html']) || !$row['descript_html']) {
        print " style='white-space: pre'>" . escape_html($row['race_descript']) . "</div>\n";
      }
      else {
        print ">" . $row['race_descript'] . "</div>\n";
      }
    }
  if (!isset($row['feedback'])) {
      $row['feedback'] = null;
    }
    if ($row['feedback']) {
      print "Leave your comment here.";
      if ($row['feedback'] > 1) {
        print("  It will be visible only to the election administrator, " .
              "who will pass it on to the appropriate person.");
        if ($anon_voting) {
          print("  Your comment will be anonymous.");
        }
      }
      print "<br><textarea rows=5 cols=30 name='" . escape_html($race_autoid) . "'>" .
        (array_key_exists($race_autoid,$_REQUEST)?$_REQUEST[$race_autoid]:'') . 
        "</textarea></p>";
    }
else {
    //    if (isset($row['race_file']) && isset($row['race_filename']) && $row['race_filename']) {
    //  print "<form action='fetch_race_file.php' method=post>"
    //there should be an abstain option if abstentions don't count as votes, or if it won't do any harm.
    //it will do harm only if there is a percentage threshold, and there is a default option that will
    //happen if no one reaches the threshold, because then an abstention is the same as a vote for that
    //option.  (If there is one candidate, the default option is that no one is elected.)
    $show_abstain = !$row['abstain_count'] || ($row['runoff'] == 0 && 
                                               ($count != 1 && !$row['def_val']));
    if ($row['num'] > 1) {
      print("There will be " . escape_html($row['num']) . " winners for this race.<br>");
    }
    if ($row['runoff'] > 1) {
      print("You may vote for up to " . escape_html($row['runoff']) . " choices.<br>");
      $checktype = 'checkbox';
      $race_max[$race_autoid] = $row['runoff'];
    }
    else if ($row['runoff']) {
      print("Rank the choices in order, with 1 being your top choice, 2 your second choice, and so on." .
            "  You do not have to rank all the choices -- if you only have a top choice, you can just " .
            "put a 1 next to it, and leave the rest blank, and so on.<br>");
      $checktype = 'input';
    }
    else if ($count == 1) {
      $checktype = 'checkbox';
    }
    else {
      $checktype = 'radio';
    }
    if ($row['threshold'] > 0) {
      if ($row['def_val']) {
        if ($count == 2) {
          $opt = array_diff($candidates,array($row['def_val']));
          $opt = $opt[0];
        }
        else {
          $opt = "any choice but " . $row['def_val'];
        }
      }
      else {
        $opt = "a choice";
      }
      print("More than " . escape_html($row['threshold']) . "% of the vote is required for " . 
            escape_html($opt) . " to win.<br>");
    }
    else if ($row['threshold'] < 0) {
      print("At least " . escape_html(-$row['threshold']) . " votes are required for a chioce to win.<br>");
    }
    if (!$row['abstain_count']) {
      print("  Abstentions do not count towards the total, so if you wish to abstain, you may, " .
            "by choosing the abstain option.<br>");
    }
    //if we're not doing a revote and we need to choose a radio button
    if (!isset($selected) && $checktype == 'radio') {
      if ($show_abstain) {
        $sel_ind = -1;
      }
      else {
        $sel_ind = 1;
      }
    }
    else {
      $sel_ind = null;
    }
    print("<table><tr>");
    if ($show_abstain) {
      $id = escape_html($race_autoid . "--1");
      print("<td style='padding-right: 40px'><label for='$id'>" . 
            "<input type=" . ($checktype == 'input'?'checkbox':$checktype) . 
            " name='" . (($checktype == 'radio')?$race_autoid . "' value='-1'":$id . "'") . 
            " id='$id' " .
            (($checktype != 'radio')?"onclick='abstain_race(" . 
            escape_html($race_autoid) . ")'":""));
if ($checktype == 'input') {
$old_checktype = 'input';
$checktype = 'checkbox';
}
else {
$old_checktype = null;
}
print(checked_box($race_autoid,-1) . ($sel_ind == -1?' checked':'') .
            ">(Abstain)</label></td>");
if ($old_checktype == 'input') {
$checktype = 'input';
$old_checktype = null;
}
    }
    $ii = 0;
    foreach ($candidates as $cand) {
      $id = escape_html($race_autoid . "-" . $ii);
      print("<td style='padding-right: 40px'><label for='$id'><input type=" . $checktype . " name='" . 
            (($checktype == 'radio')?$race_autoid . "' value='" . escape_html($cand) . "'":$id . "'") . 
            " id='$id' size=2 onchange='" .
            (($checktype == 'checkbox')?"validate_checkbox(" . $race_autoid . ");":"") .
            (($show_abstain && $checktype != 'radio')?"remove_abstain(" . $race_autoid . ");":"") . "'" .
            checked_box($race_autoid,$ii,$row['def_val'],$cand,$sel_ind > 0) . ">" .
            escape_html($cand) . "</label></td>");
      $ii++;
    }
    print "</tr></table>";
}
if (isset($row['member_comments'])) {
if (isset($_REQUEST[$race_autoid . '-member_comments'])) {
if ($_REQUEST[$race_autoid . '-member_comments']) {
if ($anon_voting) {
$row['member_comments'] .= 'somebody says: ';
}
else {
$row['member_comments'] .= "$member_name: ";
}
$row['member_comments'] .=
        $_REQUEST[$race_autoid . '-member_comments'] . "\n";
        $db->Execute("update `elections_attribs` " .
                     "set `attrib_value` = ? " .
                     'where `election_name` = ? and `race_name` = ? and `attrib_name` = ?',
                     array($row['member_comments'],$election_name,$race_name,'member_comments'));
      }
    }
    if (strlen($row['member_comments'])) {
      print("People say:\n<pre>");
      print(escape_html($row['member_comments']));
      print("</pre>\n");
    }
    print("Add a comment other people will see.<br>");
    print("<textarea rows=5 cols=30 name='" . $race_autoid . 
          "-member_comments'></textarea>");
  }
  if ($row['member_add']) {
    print("<br>Add a candidate (make sure your candidate does not already appear above!)<br>");
    print("<input name='" . $race_autoid . "-member_add'>");
  }
if ($row['member_comments'] !== null || $row['member_add']) {
  print "<br><input type=submit value='Add a ";
  if ($row['member_add']) {
    print 'candidate';
    if ($row['member_comments'] !== null) {
      print ' and/or comment';
    }
  }
  else {
    print 'comment';
  }
  print "' onclick='document.getElementById(\"page_type\").value=\"member_input\"'>";
}
print("</p><hr>");
}
?>
<script type='text/javascript'>
var race_nums = [<?=js_array($race_nums)?>];
<?=js_assoc_array('race_names',$race_names)?>
<?=js_assoc_array('race_max',$race_max)?>
</script>

<?php
if ($manual_entry) {
  ?>

<input type=hidden name='enter-ballot' value='true'>

<?php
}
if ($votable) {
?>
<input type=submit value='Review vote' onclick='return validate_ballot();'>
</form>
<?php
   }
?>
</body>
</html>
<?php
exit;
#{
}
?>
</head><body>
<?php
print $body_insert;
function cand_at($ii) {
  global $candidates;
  return $candidates[$ii];
}
if ($_REQUEST['page_type'] == 'review_vote') {
  if (!$votable) {
    exit("You are not eligible to vote!");
  }
#$db->debug = true;
  $dummy_string = get_static('dummy_string','XXXXX');
  if ($anon_voting && !$manual_entry) {
    $row = $db->GetRow(<<<SQLSTMT
select count(*) as ct from `voting_record`
where `election_name` = ? and member_name = ?
SQLSTMT
                       ,array($election_name,$member_name));
    if ($row['ct'] > 0) {
      $db->CompleteTrans(false);
      print("<h3>Election is anonymous and you have already voted</h3>\nChoose another election:<br>\n");
list_elections($election_name);
    }
  }
  $nameres = $db->Execute("select * from `elections_attribs` where " .
                      "`election_name` = ? and `attrib_name` = ? order by 0+`attrib_value`",
                      array($election_name,'race_name'));
  $votes = array();
  function revote($errno,$errstr,$errfile,$errline,$errcontext) {
    global $db,$checktype, $cur_race,$selected;
    ob_end_clean();
    print("<h2>Error ($errline): " . escape_html($errstr) . " in " . escape_html($cur_race) .
          "</h2>\nYour vote was not recorded.  Please try again.<p>");
    unset($_REQUEST['page_type']);
    require(__FILE__);
    exit;
  }
  set_error_handler('revote');
  function race_num($val,$key) {
    global $selected;
    $arr = split('-',$key);
    $race_num = array_shift($arr);
    if (!is_numeric($race_num)) {
      return;
    }
    $cand = join('-',$arr);
    if ($cand == 'member_comments' || $cand == 'member_add') {
      return;
    }
    if (!strlen($val)) {
      return;
    }
    //    print_r("<br>race_num: " . $race_num . "<br>\ncand: " . $cand . "<br>\n");
    if (strspn($cand,'-0123456789') == strlen($cand)) {
      if (!isset($selected[$race_num])) {
        $selected[$race_num] = array();
      }
      $selected[$race_num][$cand] = $val;
    }
  }
  function arr_number($item,$ind) {
    print(escape_html($ind) . ". " . escape_html(cand_at($item)) . "<br>");
  }
  function race_name($race) {
    print("<p><strong>" . escape_html($race) . "</strong>: ");
  }
  //don't tell the voter what they're voting for if they have errors in their voting 
  ob_start();
#}
?>
<h2>Please review your votes and then submit.  If these are not the
correct votes, please press the Back button on your browser and 
check your entries.</h2>
<form method=post action='<?=escape_html($_SERVER['REQUEST_URI'])?>'>
<?php
if ($manual_entry) {
?>

<input type=hidden name='enter-ballot' value='true'>

<?php
}
?>
<input type=hidden name='election_name' value='<?=escape_html($_REQUEST['election_name'])?>' />
<input type=hidden name='page_type' id='page_type' value='register_vote'>
<?php
#$db->debug = true;
$selected = array();
array_walk($_REQUEST,'race_num');
while ($namerow = $nameres->FetchRow()) {
  $race_autoid = $namerow['autoid'];
  $cur_race = $namerow['race_name'];
  $res = $db->Execute('select `attrib_name`,`attrib_value` from `elections_attribs`' .
                      ' where `election_name` = ? and `race_name` = ?',
                      array($election_name,$cur_race));
  $row = array();
  while ($temprow = $res->FetchRow()) {
    $row[$temprow['attrib_name']] = $temprow['attrib_value'];
  }
  $row['display_name'] = $cur_race;
  $row['autoid'] = $race_autoid;
  race_name($row['display_name']);
  $id = $row['autoid'];
  //feedback is easy
  if (isset($row['feedback']) && $row['feedback']) {
    $votes[$id] = stripformslash($_REQUEST[$id]);
    print("<pre>" . escape_html($votes[$id]) . "</pre>");
    print("<input type=hidden name='" . escape_html($id) . "' " .
          "value='" . escape_html($votes[$id]) . "' />\n");
    print("<p>");
    continue;
  }
  if (isset($selected[$id])) {
    $sel = $selected[$id];
  }
  else {
    $sel = array();
  }
  $candidates = explode("\n",$row['candidates']);
  $count = count($candidates);
  if ($count == 1 && !$candidates[0]) {
    $candidates = array();
    $count = 0;
  }
  //did the person abstain?

  if (!count($sel) || ($row['runoff'] == 0 && $count > 1 && 
                       (!isset($sel) || (array_key_exists('',$sel) && $sel[''] == -1)))
      || isset($sel[-1])) {
    if (count($sel) > 1) {
       janak_error("You abstained and chose an option");
    }
    print("(Abstain)<br>");
    print("<input type=hidden name='" . $id . "' value=''>\n");
    print("<p>");
    continue;
  }
  if ($row['runoff'] == 0 && $count > 1) {
    if (array_key_exists($id,$_REQUEST)) {
      print(escape_html($sel['']));
      print("<input type=hidden name='" . escape_html($id) . 
      "' value='" . escape_html($sel['']) . "' />\n");
    }
    print("<p>");
    continue;
  }
  if ($row['runoff'] == 1) {
    $ranks = array_flip($sel);
    ksort($ranks);
    $votes[$row['autoid']] = '';
    print("You ranked the following candidates in order:<br>");
    $lastrank = 0;
    foreach ($ranks as $key => $val) {
      if ($key != ++$lastrank) {
        if ($key == 0) {
          trigger_error("You cannot rank a candidate as 0",E_USER_ERROR);
        }      
        if ($key == $lastrank-1) {
          trigger_error("You ranked two choices the same",E_USER_ERROR);
        }
        trigger_error("You skipped rank " . $lastrank,E_USER_ERROR);
      }
      print("$key. " . escape_html($candidates[$val]) . "<br>\n");
      print("<input type=hidden name='" . escape_html($id) . 
      "[]' value='" . escape_html($val) . "' />\n");
    }
    continue;
  }
  //must have $row['runoff'] > 1
  if ($count > 1) {
    if (count($sel) > $row['num']) {
      trigger_error("You have voted for too many candidates",E_USER_ERROR);
    }
    print("You ranked the following candidates equally:<br>");
  }
  $first_flag = 0;
  foreach ($sel as $key => $val) {
    if ($first_flag++) {
      print(", ");
    }
    print(escape_html($candidates[$key]));
    print("<input type=hidden name='" . escape_html($id) . 
    "[]' value='" . escape_html($key) . 
    "' />\n");
  }
  print("<p>");
}
if (!$manual_entry && get_static('email_voter',true)) {
  if ($anon_voting) {
    $user_email = get_email($member_name);
    if ($user_email) {
?>
<p>The fact that you have voted will be emailed to you.  On the next screen,
your voter id will appear.  When the election results are public, you can use
it to verify that your vote was properly counted.  By default, <strong>your voter id,
as well your actual votes, will be emailed to <?=escape_html($user_email)?></strong>.
  If this email is incorrect, 
<a href='../preferences.php' target='_blank'>correct it here</a> before you cast
your vote.  Just go to the page, enter an email and your password, and submit.
The page will open in a new window, so you won't lose this one.  <strong>If you do not
wish your voter id and votes to be emailed to you, uncheck this box</strong>.
<input name='email_voter_voterid' type=checkbox checked></p>
<?php
    }
    else {
?>
<p>You have no email registered with the system.  It would be nice if you could
<a href='../preferences.php' target='_blank'>enter one</a> before you cast your
vote.  Just go to the page, enter an email and your password, and submit.
The page will open in a new window, so you won't lose this one.</p>
<?php
    }
  }
}

print("<input type=submit value='Submit Vote!'></form>");
exit;
#{
}
if ($_REQUEST['page_type'] == 'register_vote') {
  if (!$votable) {
    exit("You are not eligible to vote!");
  }
#  $db->debug = true;
  $db->Execute("lock tables `current_voting_lock` write");
  $db->StartTrans();
  $election_name = $_REQUEST['election_name'];
  $anon_voting = false;
  $row = $db->GetRow(<<<SQLSTMT
select count(*) as ct from `elections_record`
where `election_name` = ? and (`anon_voting` = 1 or `anon_voting` = 3)
SQLSTMT
                     ,array($election_name));
  if ($row['ct'] > 0) {
    $anon_voting = true;
    if (!$manual_entry) {
      $row = $db->GetRow(<<<SQLSTMT
select count(*) as ct from `voting_record`
where `election_name` = ? and member_name = ?
SQLSTMT
                         ,array($election_name,$member_name));
      if ($row['ct'] > 0) {
        $db->CompleteTrans(false);
        print("<h3>Election is anonymous and you have already voted</h3>\nChoose another election:<br>\n");
list_elections($election_name);
      }
    }
    $db->Execute("delete from `current_voting_lock`");
    $voting_name = substr(md5(time() . rand()),0,16);
    $loop_check = 1;
    $row = array('ct' => 1);
    while ($row['ct'] != 0 && $loop_check++ < 30) {
      $row = $db->GetRow("select count(*) as ct from `votes` where `member_name` = ?",
                         array($voting_name));
      if ($row['ct']) {
        usleep(200000);
      }
      $voting_name = substr(md5(time() . rand()),0,16);
    }
    if ($loop_check >= 30) {
      $db->CompleteTrans(false);
      exit("<h3>Error getting an anonymous voting name</h3>");
    }
    $db->Execute('insert into `current_voting_lock` values(null,?,?)',
                 array($member_name,$voting_name));
  }
  else {
    if (!$manual_entry) {
    $db->Execute(<<<SQLSTMT
delete from `votes` where `member_name` = ? and 
election_name = ?
SQLSTMT
                 ,array($member_name,$election_name));
$db->Execute("delete from `voting_record` where `member_name` = ? and " .
"election_name = ?",array($member_name,$election_name));
    }
    $voting_name = $member_name;
  }
  $db->Execute("insert into `voting_record` values(null,?,?,?)",
               array($member_name,$election_name,$manual_entry));
  
  if ($voting_name !== $member_name) {
    print "Your voter id is $voting_name.  If you save it, you can " .
      "check to make sure your vote was properly counted.<p>";
  }
  $res = $db->Execute("select `autoid`,`race_name` from `elections_attribs` where " .
                      "`election_name` = ? and `attrib_name` = ? order by 0+`attrib_value`",
                      array($election_name,'race_name'));
  $these_votes = array();
  while ($row = $res->FetchRow()) {
    if (!array_key_exists($row['autoid'],$_REQUEST)) {
      continue;
    }
    $race_name = $row['race_name'];
    if ($_REQUEST[$row['autoid']] != -1) {
      $vals = $_REQUEST[$row['autoid']];
      $candidates = explode("\n",get_election_attrib('candidates'));
      if (is_array($vals)) {
        $vals = join("\n",array_map('cand_at',$vals));
      }
    }
    else {
      $vals = null;
    }
    if ($anon_voting) {
      $anon_row = $db->GetRow("select `member_name`, `voting_name` from `current_voting_lock` limit 1");
      if ($anon_row['member_name'] != $member_name || $anon_row['voting_name'] != $voting_name) {
        $db->CompleteTrans(false);
        exit("<h3>Error voting: voting_lock information does not match.  Please contact the administrator.</h3>");
      }
    }
      $these_votes[$race_name] = $vals;
    $db->Execute("insert into `votes` values(null,?,?,?,?)",
                 array($voting_name,$election_name,$row['autoid'],$vals));
  }
  if ($anon_voting) {
    $anon_row = $db->GetRow("select `member_name`, `voting_name` from `current_voting_lock` limit 1");
    if ($anon_row['member_name'] != $member_name || $anon_row['voting_name'] != $voting_name) {
      $db->CompleteTrans(false);
      exit("<h3>Error voting: voting_lock information does not match.  Please contact the administrator.</h3>");
    }
    $db->Execute("delete from `current_voting_lock`");
  }
  $db->CompleteTrans();
  $db->Execute("unlock tables");
  $row = $db->GetRow("select `attrib_value` as `interim_results` from " .
                     "`elections_attribs` where `election_name` = ? and `attrib_name` = ?",
                     array($election_name,'interim_results'));
  $anon_row = $db->GetRow("select `anon_voting` from `elections_record` where " .
                          "`election_name` = ?",
                          array($election_name));
  print "<h3>Your vote was successfully cast!</h3>\n";
  if (!$manual_entry && ((!$anon_row['anon_voting'] && $row['interim_results']) ||
      $row['interim_results'] == 2)) {
?>
<a href='election_results.php?election_name=<?=escape_html($election_name)?>'>
You can view the election results so far</a>.
  <?php 
   } 
  else if ($manual_entry) {
    elections_log($election_name,null,'manual_entry',join("; ",$witnesses),
                  serialize($these_votes));
    print "<h3>DO NOT RELOAD THIS PAGE!</h3>\n";
    print "<a href='admin/administer_election.php?election_name=" .
      escape_html($election_name) . "'>Back to administering election</a>";
  }
if (!$manual_entry && get_static('email_voters',true)) {
  $user_email = get_email($member_name);
  if ($user_email) {
    $email_body = "If you did not vote in this election, please contact your\n" .
      "president/election administrator immediately.";
    if ($anon_voting && isset($_REQUEST['email_voter_voterid'])) {
      $email_body .= "\n\nYour voterid is: $voting_name\n\nYour votes are below:\n\n";
      foreach ($these_votes as $race => $vals) {
        $email_body .= "$race:\n";
        if (is_array($vals)) {
          $email_body .= join("\n",$vals);
        }
        else {
          $email_body .= $vals;
        }
        $email_body .= "\n\n";
      }
    }
    $president_email = $db->GetRow("select `house_info`.`member_name`, `email` from `house_info`,`privilege_table` where " .
                                   "`house_info`.`member_name` = `privilege_table`.`member_name` " .
                                   "and locate('president',`privilege_table`.`privileges`) != 0 and " .
                                   "length(`email`) limit 1");
    if (is_empty($president_email)) {
      $president_email = "Janak (Workshift Site Administrator) <janak@berkeley.edu>";
    }
    else {
      $president_email = dbl_quote($president_email['member_name']) . ' <' . $president_email['email']
        . ">";
    }
    mail($user_email,"You have voted in $election_name",$email_body,
         "From: $president_email");
  }
  else if ($anon_voting) {
    elections_log($election_name,null,'voter_no_email',null,$member_name);
  }
}
exit;
}
exit("I don't know what you're trying to do on this page.  Please email janak@berkeley.edu");
?>
