<?php
//there's a target specification below -- it means we have to delay
//ending the <head> section
$body_insert = '';
require_once('default.inc.php');
?>
<html><head><title>Vote</title>
<?php
//only the president can enter manual ballots
if (authorized_user($member_name,'president') && 
    isset($_REQUEST['enter-ballot'])) {
  //it takes 2 witnesses
  $witnesses = require_witnesses(2);
  $manual_entry = true;
}
else {
  $manual_entry = false;
}

//nonvoter is the only negative authorization.  Not the best system
if (authorized_user($member_name,'nonvoter')) {
  exit("</head><body>You are not eligible to vote!</body></html>");
}

if (!function_exists('list_elections')) {
//We need to output the list of elections in more than one place
function list_elections($bad_election = null,$manual_entry = false) {
  global $db;
  //manual entry can only be done for elections that have ended but
  //have not been finalized (hence the <2)
  if ($manual_entry) {
    $res = $db->Execute('select `election_name` ' .
                        "from `elections_record` where " .
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
?>
<form method='GET' action='<?=this_url()?>'>
<?php 
   $ii = 0;
  foreach ($elections as $election) {
    $election = escape_html($election);
?>
<label for='<?=$ii?>' >
<input type=radio name='election_name' id='<?=$ii++?>'
value='<?=$election?>'
checked><?=$election?></label><br>
<?php 
}
?>
<input type='submit' value='Choose election'>
</form></body></html>
<?php
exit;
}
}
//no election name given -- list and exit
if (!array_key_exists('election_name',$_REQUEST)) {
  list_elections(null,$manual_entry);
}
$election_name = $_REQUEST['election_name'];

//get basic data about election
$row = $db->GetRow("select unix_timestamp() < `end_date` as `open`, " .
                   "`anon_voting` < 2 as `status` from `elections_record` " .
                   "where `election_name` = ?", array($election_name));

//wrong election name given?
if (is_empty($row)) {
  print ("Election " . escape_html($election_name) . " does not exist.");
  list_elections(null,$manual_entry);
}

//the ballot can still be viewed even if the user isn't eligible to vote.
if ((!$manual_entry && $row['open']) || 
    ($manual_entry && !$row['open'] && $row['status'])) {
  $votable = true;
}
else {
  $votable = false;
}
//is voting anonymous?
$row = $db->GetRow("select `anon_voting` from `elections_record` " .
                   "where `election_name` = ?",
                   array($election_name));

//need to %2 because election might have ended.  Shouldn't actually
//matter, because if anon_voting > 2, then election has been
//finalized, so this ballot is never votable.
$anon_voting = $row['anon_voting']%2;
//if anonymous and already voted, not votable
if ($anon_voting && !$manual_entry) {
  $row = $db->GetRow("select count(*) as ct from `voting_record`
where `election_name` = ? and member_name = ?"
                     ,array($election_name,$member_name));
  if ($row['ct'] > 0) {
    $votable = false;
  }
}
else if ($manual_entry && !$anon_voting) {
  if (isset($_REQUEST['member_name_vote'])) {
    $member_name = $_REQUEST['member_name_vote'];
  }
  else {
  $houselist = get_houselist();
?>
<form action='<?=this_url()?>' method=POST>
   Voter's name: <select name='member_name_vote'>
<OPTION>
<?php
foreach ($houselist as $name) {
  print "<OPTION value='" . escape_html($name) . 
  "'>" . escape_html($name) . "\n";
}
?>
</SELECT>
<input type=submit value='Submit'>
</form>
<?php
    exit;
  }
}
else if ($manual_entry && $anon_voting) {
  //the member name is basically a flag here
  $member_name = $dummy_string;
}

//page_type stores where in the voting process we are -- front page,
//review vote, or register vote.  Also member input for adding
//candidates/comments.
if (!array_key_exists('page_type',$_REQUEST) || 
    $_REQUEST['page_type'] == 'member_input') {
  //ugliness with descript_file -- we want this page to output a
  //frameset, where the top frame is the file and the bottom frame is
  //the actual ballot.  So really what's happening is voting.php
  //returns a frameset and then gets called again inside that
  //frameset, but this time with the internal flag (to avoid an
  //infinite descending frameset).
  $descript_file = get_election_attrib('descript_filename',null,false);
  if ($descript_file && !array_key_exists('internal',$_REQUEST)) {
    $internal = false;
    //this_url constructs the url using the get array, so this is
    //sneaky but it works
    $_GET['internal'] = null;
    $frame_url = this_url();
    unset($_GET['internal']);
?>
<frameset rows="50%,50%">
<frame src='descript_file.php?election_name=<?=escape_html($election_name)?>'>
<frame src='<?=$frame_url?>'>
</frameset>
<noframes>
<?php
   //note that if there are no frames, then we should do the internal thing
   }
  else {
    $internal = true;
  }
  //ok, from now on, it's always possible that we have crashed when
  //reviewing votes and are therefore back at this page, and have thus
  //already defined all these functions.  Thus the extra checking.
  if (!function_exists('race_header')) {
    //little utility
    function race_header($race_name) {
      print "<p><h4>" . escape_html($race_name) . "</h4>";
    }
  }
  if (!function_exists('checked_box')) {
    //bigger utility to return the appropriate "value" for a choice.
    function checked_box($id,$ind,$cand = null) {
      global $checktype, $row;
      //checkboxes are checked if their names exist in REQUEST
      if ($checktype == 'checkbox') {
        return array_key_exists($id . "-" . $ind,$_REQUEST)?' checked':'';
      }
      //radios are checked if their names are themselves in REQUEST or
      //if nothing is in REQUEST and there is no default value and
      //they are the abstain choice or they are the default value
      if ($checktype == 'radio') {
        return (array_key_exists($id,$_REQUEST) && 
                 $_REQUEST[$id] == $cand) ||
          (!array_key_exists($id,$_REQUEST) && 
           (($ind == -1 && !strlen($row['def_val'])) 
            || $cand == $row['def_val']))?' checked':'';
      }
      //inputs are whatever their names were in REQUEST
      if ($checktype == 'input') {
        return isset($_REQUEST[$id . "-" . $ind])?" value='" . 
          $_REQUEST[$id . "-" . $ind] . "'" : '';
      }
      //should never get here
      janak_error("I don't know what's going on here!  File a bug.");
    }
  }
?>
<script type="text/javascript">
<?php // if you abstain, all your choices are erased ?>
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

<?php //if you choose something else, take away the abstain ?>
function remove_abstain(race_num) {
  document.getElementById(race_num + '--1').checked = false;
}

<?php //if unranked preferences, make sure not too many are chosen ?>
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

<?php //pretty straightforward.  Unfortunately, we don't allow
//equal rankings right now, nor can people skip rankings.  Those are
//legitimate tactics, given the voting system, but since we just store
//the candidates as a "\n"-separated list, we can't accommodate that.
//Should probably be changed at some point. See
//http://sourceforge.net/tracker/index.php?func=detail&aid=1709882&group_id=191164&atid=936272 ?>
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
      alert("You didn't rank anything " + ii + 
            " but you have things ranked below that" +
            " in " + race_names[race_num]);
      return false;
    }   
    elt = document.getElementById('race_num' + '--1');
    if (elt) {
      if (elt.checked == true) {
        alert("You abstained and also ranked choices" + " in " + 
              race_names[race_num]);
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
// we would like pre to print white space properly -- wrap the text,
// but respect white space
    print white_space_css() . ";\n";
//the base target below is because that frame above should be erased
//if we go anywhere -- we want the next page to fill browser window
?>
}
</style>
  
<base target='_top'>
</head>
<body>
<?php
print $body_insert;
$description = get_election_attrib('descript',null,false);
//if not html, escape it and pre-space it
if (!get_election_attrib('descript_html',null,false)) {
  $description = "<div style='" . white_space_css() . "'>" . 
    escape_html($description) . "</div>";
}
if ($descript_file) {
  print "<p><a href='descript_file.php?election_name=" .
    escape_html($election_name) . "'>File with more info on election " .
    "(what's in the frame above)</a></p>";
}
//maybe internal is set, but we don't want it to be here
 unset($_GET['internal']);
 $page_name = this_url();
 //make an absolute url so that users can save locally  and come back
 $uri = escape_html("http" . (array_key_exists('HTTPS',$_SERVER) && 
                              $_SERVER['HTTPS'] == 'on'?'s':'') . "://" .
                    $_SERVER['HTTP_HOST'] . ':' . $_SERVER['SERVER_PORT']) . 
                    $page_name;
?>
<?=escape_html($member_name)?><br>
<form action="<?=$uri?>" method='POST'>
<?php
if ($votable) {
?>
<?php
if ($anon_voting) {
  print("Voting is anonymous.  You can only vote once.<p>");
}
 else {
   print("Voting is not anonymous.  You may re-vote as many times as " .
         "you like.<p>");
 }
?>
<input type='hidden' name='page_type' id='page_type' value='review_vote'>
<input type='hidden' name='election_name'
   value='<?=escape_html($election_name)?>'>
<?php
}
else {
  print "<p>(You may not vote in this election -- either you have " .
    "already cast a vote, or election is closed.)</p>\n<a " .
    "href='voting.php'>Choose another election</a> if you wish to vote<p>\n";
}
if ($description) {
  print("<div>$description</div>");
}
//time to get all the race attributes
$race_nums = array();
$race_names = array();
$race_max = array();
//make sure we're ordering numerically -- attrib_value is an integer.
//Note that race ordering is done by the attrib_value of rows with
//attrib_name=race_name, but the user has no way to change that.
$nameres = $db->Execute('select `autoid`,`race_name` ' .
                        'from `elections_attribs` ' .
                        'where `election_name` = ? and ' .
                        '`attrib_name` = ? order by 0+`attrib_value`',
                        array($election_name,'race_name'));
while ($namerow = $nameres->FetchRow()) {
  $race_autoid = $namerow['autoid'];
  $race_name = $namerow['race_name'];
  $race_names[$race_autoid] = $race_name;
  $race_nums[] = $race_autoid;
  //get all the attributes of this race
  $res = $db->Execute('select `attrib_name`,`attrib_value` from ' .
                      '`elections_attribs`' .
                      ' where `election_name` = ? and `race_name` = ?',
                      array($election_name,$race_name));
  $row = array();
  while ($temprow = $res->FetchRow()) {
    $row[$temprow['attrib_name']] = $temprow['attrib_value'];
  }
  //some things get their own variables
  $candidates = explode("\n",$row['candidates']);
  //this shouldn't be necessary now that we check for empty candidates
  //in create_election.
  if (!strlen($candidates[0])) {
    array_shift($candidates);
  }
  //normalize data
  if (!$row['threshold']) {
    $row['abstain_count'] = null;
    if (!$row['num_voters']) {
      $row['def_val'] = null;
    }
  }
  if (!array_key_exists('feedback',$row)) {
    $row['feedback'] = null;
  }
  if (!$row['feedback'] && !$row['num']) {
    $row['num'] = 1;
  }
  //if there is only one (or no) candidate, then instant runoff and
  //choosing multiple candidates are meaningless.
  if (count($candidates) < 2) {
    $row['runoff'] = 0;
  }
  //is a member adding a candidate?
  if ($row['member_add'] && 
      isset($_REQUEST[$race_autoid . '-member_add'])) {
    //already on ballot?
    if ($_REQUEST[$race_autoid . '-member_add']) {
      $cand = $_REQUEST[$race_autoid . '-member_add'];
      if (array_search($cand,$candidates) !== false) {
        print "<h4>Candidate " . escape_html($cand) . 
          " is already on the ballot</h4>";
      }
      else {
        //add candidate, for this display and forever
        $candidates[] = $cand;
        $db->Execute("update `elections_attribs` " .
                     "set `attrib_value` = ? " .
                     'where `election_name` = ? and ' .
                     '`race_name` = ? and `attrib_name` = ?',
                       array(join("\n",$candidates),
                             $election_name,$race_name,'candidates'));
      }
    }
  }
  $count = count($candidates);
  race_header($race_name);
  if (isset($row['race_descript'])) {
    print "<div";
    //option exists in code for html description, but not in
    //create_election.php.  Wouldn't be much work to put it in.
    if (!isset($row['descript_html']) || !$row['descript_html']) {
      print " style='" . white_space_css() . "'>" . 
        escape_html($row['race_descript']) . "</div>\n";
    }
    else {
      print ">" . $row['race_descript'] . "</div>\n";
    }
  }
  //legacy -- should always be set
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
    if ($row['num'] > 1) {
      print("There will be " . escape_html($row['num']) . 
            " winners for this race.<br>");
    }
    if ($row['runoff'] > 1) {
      print("You may vote for up to " . 
            escape_html($row['runoff']) . " choices.<br>");
      //unranked preferences use checkboxes
      $checktype = 'checkbox';
      $race_max[$race_autoid] = $row['runoff'];
    }
    else if ($row['runoff']) {
      print("Rank the choices in order, with 1 being your top choice, " .
            "2 your second choice, and so on.  You do not have to rank " .
            "all the choices -- if you only have a top choice, you can just " .
            "put a 1 next to it, and leave the rest blank, and so on.<br>");
      //instant runoff uses input (so user can enter numbers)
      $checktype = 'input';
    }
    //one candidate gets a radio, because there's always an abstention
    //option.  Multiple candidates of course get radio.  It would
    //actually make sense for a single runoff candidate to get radio
    //too, but that's too much work for me.
    else {
      $checktype = 'radio';
    }
    //print out messages about the threshold.
    if ($row['threshold']) {
      //little prettiness for printing out which choices need threshold
      if ($row['def_val']) {
        $opt = array_diff($candidates,array($row['def_val']));
        //only one candidate (possibly besides default choice?)
        if (count($opt) == 1) {
          $opt = $opt[0] . " to win.  Otherwise, " .
            escape_html($row['def_val']) . " will win.";
        }
        //more than one other candidate, but def_val isn't a candidate
        else if (count($opt) == count($candidates)) {
          $opt = "a candidate to win.  Otherwise, " . 
            escape_html($row['def_val']) . " will win.";
        }
        //more than one other candidate, and def_val is a candidate
        else {
          $opt = "any choice but " . escape_html($row['def_val']) . " to win.";
        }
      }
      //no def_val
      else {
        $opt = "a choice to win.";
      }
    }
    if ($row['threshold'] > 0) {
      print("More than " . escape_html($row['threshold']) . 
            "% of the vote is required for " . 
            escape_html($opt) . "<br>");
    }
    else if ($row['threshold'] < 0) {
      print("At least " . escape_html(-$row['threshold']) . 
            " votes are required for " . $opt . "<br>");
    }
    if (!$row['abstain_count']) {
      print("  Abstentions do not count towards the total, so if you wish " .
            "to abstain, you may, by choosing the abstain option.<br>");
    }
    else if ($row['threshold'] > 0) {
      print "  <strong>Choosing abstain (or not choosing anything at all) " .
        "makes it harder</strong> for a choice to win.<br/>";
    }
    print("<table><tr>");
    //abstain option
    $id = escape_html($race_autoid . "--1");
    print("<td style='padding-right: 40px'><label for='$id'>" . 
          "<input type=" . 
          //it's a checkbox unless this is a radio election
          ($checktype == 'input'?'checkbox':$checktype) . 
          " name='" . 
          (($checktype == 'radio')?$race_autoid . "' value=''":$id . "'") . 
          " id='$id' " .
          (($checktype != 'radio')?"onclick='abstain_race(" . 
           escape_html($race_autoid) . ")'":""));
    //checktype will give a misleading answer, so change it for now
    if ($checktype == 'input') {
      $old_checktype = 'input';
      $checktype = 'checkbox';
    }
    else {
      $old_checktype = null;
    }
    print(checked_box($race_autoid,-1,'') . ">(Abstain)</label></td>");
    if ($old_checktype == 'input') {
      $checktype = 'input';
      $old_checktype = null;
    }
    $ii = 0;
    foreach ($candidates as $cand) {
      $id = escape_html($race_autoid . "-" . $ii);
      print("<td style='padding-right: 40px'><label for='$id'><input type=" . 
            $checktype . " name='" . 
            (($checktype == 'radio')?$race_autoid . "' value='" . 
             escape_html($cand) . "'":
             $id . "'") . 
            " id='$id' size=2 onchange='" .
            (($checktype == 'checkbox')?"validate_checkbox(" . 
             $race_autoid . ");":"") .
            //radios don't need special code to get rid of abstain
            (($checktype != 'radio')?"remove_abstain(" . 
             $race_autoid . ");":"") . "'" .
            checked_box($race_autoid,$ii,$cand) . ">" .
            escape_html($cand) . "</label></td>");
      $ii++;
    }
    print "</tr></table>";
  }
  //deal with member comments.  Note that the flag for member_comments
  //is a bit different than with most other things.  null is the flag
  //for no comments, empty string is the flag for comments.  isset()
  //returns false for null, which is why it works here
  if (isset($row['member_comments'])) {
    //commented on this race?
    if (isset($_REQUEST[$race_autoid . '-member_comments'])) {
      if ($_REQUEST[$race_autoid . '-member_comments']) {
        //anonymous commenting issue
        if ($anon_voting) {
          $row['member_comments'] .= 'somebody says: ';
        }
        else {
          $row['member_comments'] .= "$member_name: ";
        }
        //append for this time
        $row['member_comments'] .=
          $_REQUEST[$race_autoid . '-member_comments'] . "\n";
        //update for future
        $db->Execute("update `elections_attribs` " .
                     "set `attrib_value` = ? " .
                     'where `election_name` = ? and `race_name` = ? ' .
                     'and `attrib_name` = ?',
                     array($row['member_comments'],$election_name,
                           $race_name,'member_comments'));
      }
    }
    //any comments?
    if (strlen($row['member_comments'])) {
      print("People say:\n<pre>");
      print(escape_html($row['member_comments']));
      print("</pre>\n");
    }
    //same form as the whole thing, but different button (below)
    print("Add a comment other people will see.<br>");
    print("<textarea rows=5 cols=30 name='" . $race_autoid . 
          "-member_comments'></textarea>");
  }
  //member add -- fortunately, nothing but the form
  if ($row['member_add']) {
    print("<br>Add a candidate (make sure your candidate does not " .
          "already appear above!)<br>");
    print("<input name='" . $race_autoid . "-member_add'>");
  }
  //either one of them makes us put a button here
  if (isset($row['member_comments']) || $row['member_add']) {
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
    //we need to alter the page type when this is submitted, so we
    //don't go on to the registering of votes
    print "' onclick='document.getElementById(\"page_type\")" .
      ".value=\"member_input\"'>";
}
print("</p><hr>");
}
//keep track of the race variables for validation
?>
<script type='text/javascript'>
var race_nums = [<?=js_array($race_nums)?>];
<?=js_assoc_array('race_names',$race_names)?>
<?=js_assoc_array('race_max',$race_max)?>
</script>

<?php
//if manual entry, we need to propagate this flag to the next page.
//It will probably be in the url, but just to make sure.  Also the
//member_name_vote is not in the url.
if ($manual_entry) {
  ?>
<input type=hidden name='member_name_vote'
value='<?=escape_html($member_name)?>'>
<input type=hidden name='enter-ballot' value='true'>

<?php
}
//only print a button to go to the next page if the person can vote.
if ($votable) {
?>
<input type=submit value='Review vote' onclick='return validate_ballot();'>
</form>
<?php
   }
?>
</form>
</body>
</html>
<?php
exit;
}
  //now for reviewing the vote.  More complicated than you'd think.
?>
</head><body>
<?php
print $body_insert;
function cand_at($ii) {
  global $candidates;
  return $candidates[$ii];
}
//can't be here if you can't vote.  Note that lots of variables were
//set up above, so we don't have to re-set them here, like votable.
if ($_REQUEST['page_type'] == 'review_vote') {
  if (!$votable) {
    exit("You are not eligible to vote!");
  }
  //has this person voted before?
  if ($anon_voting && !$manual_entry) {
    $row = $db->GetRow(<<<SQLSTMT
select count(*) as ct from `voting_record`
where `election_name` = ? and member_name = ?
SQLSTMT
                       ,array($election_name,$member_name));
    if ($row['ct'] > 0) {
      print("<h3>Election is anonymous " .
            "and you have already voted</h3>\n" .
            "Choose another election:<br>\n");
      list_elections($election_name);
    }
  }
  //this is our error handler.  Probably more trouble than it's worth,
  //but theoretically it should spit back the voting page on errors.
  function revote($errno,$errstr,$errfile,$errline,$errcontext) {
    global $db,$checktype, $cur_race,$selected,$member_name,
      $dummy_string;
    //any output so far is bad.  Get rid of it, since we're going back
    //to the top.
    ob_end_clean();
    print("<h2>Error ($errline): " . 
          escape_html($errstr) . " in " . escape_html($cur_race) .
          "</h2>\nYour vote was not recorded.  Please try again.<p>");
    unset($_REQUEST['page_type']);
    //start all over.  Note that the only globals saved are the ones
    //that were declared global in this function.  Note also that
    //everything will be redefined.
    require(__FILE__);
    exit;
  }
  set_error_handler('revote');
  //this function does way more than the name says.  It parses an
  //element of the REQUEST array to get a race, candidate and ranking
  //and puts it into the $selected array, which is also more than the
  //name sounds like
  function race_num($val,$key) {
    global $selected;
    //figure out what race we have
    $arr = split('-',$key);
    $race_num = array_shift($arr);
    if (!is_numeric($race_num)) {
      return;
    }
    //which candidate do we have?
    $cand = join('-',$arr);
    //note that candidates are indexed, so always numeric
    if ($cand == 'member_comments' || $cand == 'member_add') {
      return;
    }
    if (!strlen($val)) {
      return;
    }
    //is candidate of the proper form?
    if (strspn($cand,'-0123456789') == strlen($cand)) {
      if (!isset($selected[$race_num])) {
        $selected[$race_num] = array();
      }
      //put the candidates rank in
      $selected[$race_num][$cand] = $val;
    }
  }
  //print out ranking of candidates
  function arr_number($item,$ind) {
    print(escape_html($ind) . ". " . escape_html(cand_at($item)) . "<br>");
  }
  function race_name($race) {
    print("<p><strong>" . escape_html($race) . "</strong>: ");
  }
  //don't tell the voter what they're voting for if they have errors
  //in their voting
  ob_start();
#}
?>
<h2>Please review your votes and then submit.  If these are not the
correct votes, please press the Back button on your browser and 
check your entries.</h2>
<form method=post action='<?=this_url()?>'>
<?php
if ($manual_entry) {
?>

<input type=hidden name='member_name_vote' 
value='<?=escape_html($member_name)?>'>
<input type=hidden name='enter-ballot' value='true'>

<?php
}
?>
<input type=hidden name='election_name' 
value='<?=escape_html($_REQUEST['election_name'])?>' />
<input type=hidden name='page_type' id='page_type' value='register_vote'>
<?php
$selected = array();
//this fills the selected array with everything from REQUEST
array_walk($_REQUEST,'race_num');
//get the list of races
 $nameres = $db->Execute("select * from `elections_attribs` where " .
                         "`election_name` = ? and `attrib_name` = ? " .
                         "order by 0+`attrib_value`",
                         array($election_name,'race_name'));
 $votes = array();
 while ($namerow = $nameres->FetchRow()) {
   $race_autoid = $namerow['autoid'];
   $cur_race = $namerow['race_name'];
   $res = $db->Execute('select `attrib_name`,`attrib_value` ' .
                       'from `elections_attribs`' .
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
    $votes[$id] = $_REQUEST[$id];
    print("<pre>" . escape_html($votes[$id]) . "</pre>");
    print("<input type=hidden name='" . escape_html($id) . "' " .
          "value='" . escape_html($votes[$id]) . "' />\n");
    print("<p>");
    continue;
  }
  //was this race filled in ?
  if (isset($selected[$id])) {
    $sel = $selected[$id];
  }
  //otherwise, abstention
  else {
    $sel = array();
  }
  $candidates = explode("\n",$row['candidates']);
  $count = count($candidates);
  //make sure there wasn't just an empty string -- shouldn't happen
  //anymore.
  if ($count == 1 && !$candidates[0]) {
    $candidates = array();
    $count = 0;
  }
  //if there is only one (or no) candidate, then instant runoff and
  //choosing multiple candidates are meaningless.
  if ($count < 2) {
    $row['runoff'] = 0;
  }
  //did the person abstain?  The last clause is because in a radio
  //race, there isn't a separate REQUEST item for each candidate,
  //there's just one per race, with a blank candidate at the end.
  if (!count($sel) || isset($sel[-1]) || 
      ($row['runoff'] == 0 &&  
       (!isset($sel) || (array_key_exists('',$sel) && $sel[''] == '')))) {
    if (count($sel) > 1) {
      janak_error("You abstained and chose an option");
    }
    print("(Abstain)<br>");
    print("<input type=hidden name='" . $id . "' value=''>\n");
    print("<p>");
    continue;
  }
  //do the radio case
  if ($row['runoff'] == 0) {
    if (array_key_exists($id,$_REQUEST)) {
      print(escape_html($sel['']));
      print("<input type=hidden name='" . escape_html($id) . 
      "' value='" . escape_html($sel['']) . "' />\n");
    }
    print("<p>");
    continue;
  }
  //instant runoff case
  if ($row['runoff'] == 1) {
    //want to print based on ranking of candidates
    $ranks = array_flip($sel);
    ksort($ranks);
    $votes[$row['autoid']] = '';
    print("You ranked the following candidates in order:<br>");
    $lastrank = 0;
    foreach ($ranks as $key => $val) {
      if ($key != ++$lastrank) {
        if ($key == 0) {
          janak_error("You cannot rank a candidate as 0");
        }      
        if ($key == $lastrank-1) {
          janak_error("You ranked two choices the same");
        }
        janak_error("You skipped rank " . $lastrank);
      }
      print(escape_html($key) . ". " . 
            escape_html($candidates[$val]) . "<br>\n");
      print("<input type=hidden name='" . escape_html($id) . 
      "[]' value='" . escape_html($val) . "' />\n");
    }
    continue;
  }
  //must have $row['runoff'] > 1
  if ($count > 1) {
    if (count($sel) > $row['num']) {
      janak_error("You have voted for too many candidates");
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
 //voters can be emailed as per the setting of the president.
 if (!$manual_entry && 
     (($anon_voting && get_static('email_voters_anon',true)) ||
     (!$anon_voting && get_static('email_voters_not_anon',true)))) {
   $user_email = get_email($member_name);
   if ($user_email) {
  ?>
<p>The fact that you have voted will be emailed to you.
<?php
   if ($anon_voting) {
?>
On the next screen, your voter id will appear.  When the
election results are public, you can use
it to verify that your vote was properly counted.  By default, an email
with <strong>your voter id as well as 
<?php
  }
   else {
     print "By default, an email with ";
   }
?>
your actual votes will be emailed to
<?=escape_html($user_email)?></strong>.
If this email is incorrect, 
<a href='../preferences.php' target='_blank'>correct it here</a>
before you cast your vote.  Just go to the page, enter an email and
your password, and submit.
The page will open in a new window, so you won't lose this one.
<strong>If you do not wish to receive an email, uncheck this box</strong>.
<input name='email_voter_voterid' type=checkbox checked></p>
<?php
    }
    else {
?>
<p>You have no email registered with the system.  It would be nice if you could
<a href='../preferences.php' target='_blank'>enter one</a> before you cast your
vote, so that you can receive a confirmation email.  Just go to the page,
enter an email and your password, and submit.
The page will open in a new window, so you won't lose this one.</p>
<?php
  }
 }

print("<input type=submit value='Submit Vote!'></form>");
exit;
#{
}
//a lot of the checks don't happen here, because invalid data will
//never help a voter influence the election, and if you got here using
//invalid data, you deserve what's coming to you -- you're probably
//trying to sabotage the races.
if ($_REQUEST['page_type'] == 'register_vote') {
  if (!$votable) {
    exit("You are not eligible to vote!");
  }
  $election_name = $_REQUEST['election_name'];
  $anon_voting = false;
  //we don't want half a vote to be committed.
  $db->StartTrans();
  //nobody else can vote while we are, to avoid any possible problems.
  $db->Execute("lock tables `voting_record` write, `votes` write, " .
               "`elections_attribs` read,`elections_record` read");
  $row = $db->GetRow(<<<SQLSTMT
select `anon_voting` from `elections_record`
where `election_name` = ?
SQLSTMT
                     ,array($election_name));
  if (is_empty($row)) {
    exit("This election does not exist!");
  }
  if ($row['anon_voting']%2) {
    $anon_voting = true;
    if (!$manual_entry) {
      $row = $db->GetRow(<<<SQLSTMT
select count(*) as ct from `voting_record`
where `election_name` = ? and member_name = ?
SQLSTMT
                         ,array($election_name,$member_name));
      if ($row['ct'] > 0) {
        $db->CompleteTrans(false);
        print("<h3>Election is anonymous and you have already voted</h3>\n" .
              "Choose another election:<br>\n");
        list_elections($election_name);
      }
    }
    //the voting name will be unique (hopefully) because of the time
    //and the randomness.  Passing it through md5 is done so that it's
    //not clear on the results page what order votes were cast in.
    //However, in case it isn't unique, we go through an elaborate
    //process to make it unique.  Note that this should never happen
    //because we obtained a lock up there on the votes, so nobody else
    //is voting right now, and so at worst, after 1 second, we'll have
    //a unique id.
    $voting_name = substr(md5(time() . rand()),0,16);
    //how many times will we loop?
    $loop_check = 1;
    //we start assuming not unique
    $row = array('ct' => 1);
    while ($row['ct'] != 0 && $loop_check++ < 30) {
      //is it unique?
      $row = $db->GetRow("select count(*) as ct from `votes` " .
                         "where `member_name` = ?",
                         array($voting_name));
      //nope.  Sleep 200,000 microseconds, or .2 seconds
      if ($row['ct']) {
        usleep(200000);
      }
      //try again
      $voting_name = substr(md5(time() . rand()),0,16);
    }
    //damn it, what happened?
    if ($loop_check >= 30) {
      $db->CompleteTrans(false);
      exit("<h3>Error getting an anonymous voting name</h3>");
    }
  }
  else {
    //non-anonymous is so much easier
    if (!$manual_entry || $member_name != $dummy_string) {
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
  //voting_record gets put in unless this is a manual entry for anon election
  if ($member_name != $dummy_string) {
    $db->Execute("insert into `voting_record` values(null,?,?,?)",
                 array($member_name,$election_name,$manual_entry));
  }
  //user gets this so they can track their vote, make sure it's right
  if ($voting_name !== $member_name) {
    print "Your voter id is $voting_name.  If you save it, you can " .
      "check to make sure your vote was properly counted.<p>";
  }
  //get races
  $res = $db->Execute("select `autoid`,`race_name` " .
                      "from `elections_attribs` where " .
                      "`election_name` = ? and `attrib_name` = ? " .
                      "order by 0+`attrib_value`",
                      array($election_name,'race_name'));
  $these_votes = array();
  while ($row = $res->FetchRow()) {
    //we don't care about races without votes from this user
    if (!array_key_exists($row['autoid'],$_REQUEST)) {
      continue;
    }
    $race_name = $row['race_name'];
    //was it an abstention?
    if ($_REQUEST[$row['autoid']] != -1) {
      //no matter what, candidates voted for are a list
      $vals = $_REQUEST[$row['autoid']];
      $candidates = explode("\n",get_election_attrib('candidates'));
      //get back the candidate names (they were just indices before)
      if (is_array($vals)) {
        $vals = join("\n",array_map('cand_at',$vals));
      }
    }
    else {
      $vals = null;
    }
    //storing the votes for elections_log()ging and emailing
    $these_votes[$race_name] = $vals;
    $db->Execute("insert into `votes` values(null,?,?,?,?)",
                 array($voting_name,$election_name,$row['autoid'],$vals));
  }
  $db->CompleteTrans();
  $db->Execute("unlock tables");
  $row = $db->GetRow("select `attrib_value` as `interim_results` from " .
                     "`elections_attribs` where `election_name` = ? " .
                     "and `attrib_name` = ?",
                     array($election_name,'interim_results'));
  print "<h3>Your vote was successfully cast!</h3>\n";
  if (!$manual_entry && $row['interim_results']) {
  ?>
<a href='election_results.php?election_name=<?=escape_html($election_name)?>'>
You can view the election results so far</a>.
  <?php 
   } 
  //log manually-entered ballots
  else if ($manual_entry) {
    elections_log($election_name,null,'manual_entry',join("; ",$witnesses),
                  serialize($these_votes));
    //big problem -- reloading would enter a copy of the same ballot.
    print "<h3>DO NOT RELOAD THIS PAGE!</h3>\n";
    print "<a href='admin/administer_election.php?election_name=" .
      escape_html($election_name) . "'>Back to administering election</a>";
  }
  //do email
  if (!$manual_entry && 
      ((($anon_voting && get_static('email_voters_anon',true)) ||
      (!$anon_voting && get_static('email_voters_not_anon',true))) &&
       isset($_REQUEST['email_voter_voterid']))) {
    $user_email = get_email($member_name);
    if ($user_email) {
      $email_body = "If you did not vote in this election, " .
        "please contact your\n" .
        "president/election administrator immediately.";
      if ($anon_voting && isset($_REQUEST['email_voter_voterid'])) {
        $email_body .= "\n\nYour voterid is: $voting_name\n\n";
      }
      if (!$anon_voting || isset($_REQUEST['email_voter_voterid'])) {
        $email_body .= "Your votes are below:\n\n";
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
      //get the president's email for the from: line.  email is in
      //house_info.  line in privilege_table might have more than just
      //president, hence the locate function.
      $president_email = 
        $db->GetRow("select `house_info`.`member_name`, " .
                    "`email` from `house_info`," .
                    "`privilege_table` where " .
                    "`house_info`.`member_name` = " .
                    "`privilege_table`.`member_name` " .
                    "and " . 
                    "locate('president',`privilege_table`.`privileges`) != 0" .
                    " and length(`email`) limit 1");
      if (is_empty($president_email)) {
        $president_email = "Janak (Workshift Site Administrator) " .
          "<janak@berkeley.edu>";
      }
      else {
        $president_email = dbl_quote($president_email['member_name']) . ' <' . 
        $president_email['email'] . ">";
      }
      mail($user_email,"You have voted in $election_name",$email_body,
           "From: $president_email");
    }
    //log if we couldn't email someone and it's an anonymous election
    else if ($anon_voting) {
      elections_log($election_name,null,'voter_no_email',null,$member_name);
    }
  }
  else if (!$manual_entry && $anon_voting &&
       	   get_static('email_voters_anon',true)) {
    elections_log($election_name,null,'voter_request_no_email',null,
		  $member_name);
  }
  exit;
}
 exit("I don't know what you're trying to do on this page.  Please email " .
      "janak@berkeley.edu");
?>
