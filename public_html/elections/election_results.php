<?php 
$body_insert = '';
require_once('default.inc.php');
//member name is used below in loops
$member_name_real = $member_name;
if (!array_key_exists('election_name',$_REQUEST)) {
  //get elections that have ended and been finalized, along with
  //elections which allow the viewing of interim results
  $res = $db->Execute('SELECT ' . bracket('election_name') . ' FROM ' .
                      bracket('elections_record') . 
                      ' WHERE unix_timestamp() >= `end_date` and ' .
                      '`anon_voting` > 1 union ' .
                      'select `election_name` from `elections_attribs` ' .
                      'where `attrib_name` = ? and `attrib_value` != 0 ' .
                      'order by `election_name`',
                      array('interim_results'));
  if (is_empty($res)) {
    exit("No elections currently viewable.<p>\n");
  }
  while ($row = $res->FetchRow()) {
    $elections[] = $row['election_name'];
  }
?>
<html><head><title>Election Results</title></head><body>
<?=$body_insert?>
<form method='GET' action='<?=this_url()?>'>
<?php 
$ii = 0;
foreach ($elections as $election) {
  //trick here -- if everything is checked, last one will be the one.
?>
<label for='<?=$ii?>' ><input type=radio name='election_name'
id='<?=$ii++?>' value='<?=escape_html($election)?>'
checked><?=escape_html($election)?></label><br>
<?php 
}
?>
<input type='submit' value='Choose election'>
</form></body></html>
<?php
exit;
}
$election_name = $_REQUEST['election_name'];
$elect_row = $db->GetRow("select " .
                         "`anon_voting`, `anon_voting` < 2 as `open`, " .
                         "`end_date` > unix_timestamp() as `time_open`, " .
                         "`end_date` " .
                         "from `elections_record` where `election_name` = ? ",
                         array($election_name));
if (is_empty($elect_row)) {
  print escape_html($election_name) . " is not a valid election. ";
  print "<a href='election_results.php'>Click here to choose one</a>.";
  exit;
}
//deal with member comments, member add
foreach ($_REQUEST as $key => $val) {
  if (!$val) {
    continue;
  }
  $race = explode("-",$key,2);
  if (count($race) <= 1) {
    continue;
  }
  if ($race[1] != 'member_comments' && $race[1] != 'member_add') {
    continue;
  }
  $race_name = get_election_attrib('race_name',$race[0]);
  if ($race[1] == 'member_add') {
    if (!get_election_attrib('member_add')) {
      print "<h4>Cannot add candidates in race " . escape_html($race_name) . 
        "!</h4>";
      continue;
    }
    //we can use similar logic for both member_comments and member_add
    $race[1] = 'candidates';
  }
  else {
    if (get_election_attrib('member_comments') === null) {
      print "<h4>Cannot comment on race " . escape_html($race_name) . 
        "!</h4>";
      continue;
    }
  }
  //what's the current state of the text we're updating?
  $cur_text = get_election_attrib($race[1]);
  //member comments have to be pre-pended with member info
  if ($race[1] == 'member_comments') {
    if ($elect_row['anon_voting']) {
      $cur_text .= 'somebody says: ';
    }
    else {
      $cur_text .= $member_name_real . ": ";
    } 
    $cur_text .= $val . "\n";
  }
  else {
    //make sure not a candidate already
    $cands = explode("\n",$cur_text);
    if (array_search($val,$cands) !== false) {
      print "<h4>" . escape_html($val) . " is already a candidate!</h4>\n";
      continue;
    }
    $cur_text .= "\n" . $val;
  }
  //ok, back.  Note that we don't store this anywhere.  It will be
  //retrieved later.
  $db->Execute("update `elections_attribs` set `attrib_value` = ? where " .
               "`election_name` = ? and `race_name` = ? and `attrib_name` = ?",
               array($cur_text,$election_name,$race_name,$race[1]));
}
?>
<html><head>
<title>Election results for <?=escape_html($election_name)?></title></head>
<style>
pre { 
  <?= white_space_css() //white space should wrap and be pre-formatted?>
}
table {
  empty-cells: show;
}
</style>
<body>
<?php
print $body_insert;
#$db->debug = true;
$interim_results = $db->GetRow("select `attrib_value` " .
                               "from `elections_attribs`" .
                               " where `election_name` = ? " .
                               "and `attrib_name` = ?",
                               array($election_name,'interim_results'));
//check to see if we can really view this election.
if (!is_empty($interim_results)) {
  $interim_results = $interim_results['attrib_value'];
  //this should never happen -- just for legacy
  if ($interim_results == 'sync') {
    if ($elect_row['anon_voting']) {
      $interim_results = 0;
    }
    else {
      $interim_results = 1;
    }
  }
}
else {
  $interim_results = false;
}
//can't view if election is still open 
if (!$interim_results && $elect_row['open']) {
  exit("<h3>" . escape_html($election_name) . 
       " is not finished and interim results are not viewable.</h3>");
}
$anon_voting = $elect_row['anon_voting']%2;
//don't show full results if anonymous voting unless asked -- no need
//to embarrass people in front of everyone
$full_results = (!$anon_voting && 
                 !array_key_exists('full_results',$_REQUEST)) || 
(array_key_exists('full_results',$_REQUEST) && 
 $_REQUEST['full_results'] !== 0);
?>
Below are the results of the voting.  
<?php if ($elect_row['time_open']) { ?>
 You can still 
<a 
href='voting.php?election_name=<?=escape_html(rawurlencode($election_name))?>'
>vote in the election</a>
<?php if (!$anon_voting) { ?>
or change your vote if you wish
                          <?php } ?>
.
                                                                  <?php } ?>
The method used for instant runoff is the same as for ASUC elections -- 
<a href='http://www.barnsdle.demon.co.uk/vote/fracSTV.html'>Here is a
description of the procedure</a>.  Ties in the runoff (if there are any)
are broken using first the 
<a href='http://condorcet.org/emr/defn.shtml#Condorcet%20winner'>Condorcet
winner</a> (actually loser) if it exists, and then the 
<a href='http://condorcet.org/emr/methods.shtml#Borda'>Borda winner</a> 
(actually loser) if it exists.<p>  
Here is 
<a 
href='voting.php?election_name=<?=escape_html(rawurlencode($election_name))?>'>the
ballot</a> that was used.
  
<h3>Check out the <a href='elections_log.php'>elections log</a> to make
sure no one's trying to cheat.</h3>
  <?php
#';

//get *all* the votes
$res = $db->Execute("select * from `votes` where `election_name` = ?",
                    array($election_name));
$all_votes = array();
$members = array();
//might need totals for threshold purposes
$global_totals = array();
//parse votes and put in array, with races as the keys
while ($row = $res->FetchRow()) {
  $race = $row['option_name'];
  if (!array_key_exists($race,$all_votes)) {
    $all_votes[$race] = array();
    $global_totals[$race] = 0;
  }
  $member_name = $row['member_name'];
  if (!isset($members[$member_name])) {
    $members[$member_name] = array();
  }
  //already voted in this race?  Remember, a race where multiple
  //options can be chosen will just have everything in one field,
  //separated by "\n"
  if (array_key_exists($member_name,$all_votes[$race])) {
    print("<h3>Error!  It looks like " . escape_html($member_name) . 
          " has voted twice!</h3>");
  }
  else {
    //unnecessary options here for some things, but who cares.  Weight
    //is only important for instant runoff.
    $all_votes[$race][$member_name] = array();
    $all_votes[$race][$member_name]['weight'] = 1;
    $global_totals[$race]++;
    if (strlen($row['option_choice'])) {
      $members[$member_name][$race] = 
        $all_votes[$race][$member_name]['ballot'] = 
        explode("\n",$row['option_choice']);
    }
    //abstaining
    else {
      $members[$member_name][$race] = 
        $all_votes[$race][$member_name]['ballot'] = array();
    }

  }
}

//get races in order
$nameres = $db->Execute('select `autoid`,`race_name` ' .
                        'from `elections_attribs` ' .
                        'where `election_name` = ? and `attrib_name` = ? ' .
                        'order by 0+`attrib_value`',
                        array($election_name,'race_name'));

//start of huge race loop
while ($namerow = $nameres->FetchRow()) {
  $race = $namerow['autoid'];
  $race_name = $namerow['race_name'];
  $race_names[$race] = $race_name;
  $race_nums[] = $race;
  $res = $db->Execute('select `attrib_name`,`attrib_value` ' .
                      'from `elections_attribs`' .
                      ' where `election_name` = ? and `race_name` = ?',
                      array($election_name,$race_name));
  $row = array();
  while ($temprow = $res->FetchRow()) {
    $row[$temprow['attrib_name']] = $temprow['attrib_value'];
  }
  //normalize data
  if (!isset($global_totals[$race])) {
    $global_totals[$race] = 0;
  }
  if (!isset($row['threshold'])) {
    $row['threshold'] = null;
  }
  if (!isset($row['num_voters'])) {
    $row['num_voters'] = null;
  }
  if (!isset($row['num'])) {
    $row['num'] = null;
  }
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
  $all_races[$race]['display_name'] = $race_name;
  $all_races[$race]['feedback'] = isset($row['feedback']) && $row['feedback'];
  print("<h4>" . escape_html($race_name) . "</h4>");
  //assign by reference -- just a pointer
  $votes =& $all_votes[$race];
  if ($all_races[$race]['feedback']) {
    //maybe nobody's voted yet in this race
    if ($votes) {
      foreach ($votes as $key => $val) {
        $votes[$key] = join(array_map('escape_html',$val['ballot']),"<br/>\n");
      }
      //sort votes as randomly as we can get it, so we don't know who
      //entered ballots first.
      usort($votes,'rand_md5_cmp');
      print "<ul>\n";
      foreach ($votes as $feed) {
        if (strlen($feed)> 0) {
          //already escaped above.
          print "<li>" . $feed . "\n";
        }
      }
      print "</ul>";
    }
    print "<hr>";
    continue;
  }
  $all_races[$race]['candidates'] = explode("\n",$row['candidates']);
  //again, just a pointer
  $candidates =& $all_races[$race]['candidates'];
  if (count($candidates) && !$candidates[0]) {
    array_shift($candidates);
  }
  $count = count($candidates);
  //if there is only one (or no) candidate, then instant runoff and
  //choosing multiple candidates are meaningless.
  if ($count < 2) {
    $row['runoff'] = 0;
  }
  $choices = array();
  foreach ($candidates as $cand) {
    $choices[$cand] = 0;
  }
  //get copy of the votes for archiving -- we're about to change them
  $votes_copy = array();
  $race_votes = 0;
  if (count($votes)) {
    foreach ($votes as $mem => $junk) {
      $mem_ballot =& $votes[$mem];
      //make sure the ballot is ok, no unknown candidates (should
      //never happen), and also that ballot is non-empty.
      if (!normalize_ballot($mem_ballot['ballot'],$mem)) {
        continue;
      }
      $race_votes++;
      //this weight should always be 1.  There's no way it couldn't.
      if ($row['runoff']) {
        $choices[$mem_ballot['ballot'][0]] += $mem_ballot['weight'];
      }
      else {
        foreach ($mem_ballot['ballot'] as $vote) {
          $choices[$vote] += 1;
        }
      }
    }
    foreach ($votes as $mem => $junk) {
      $votes_copy[$mem] = $votes[$mem]['ballot'];
    }
  }
  //figure out the threshold for this race
  if ($row['threshold'] != 0) {
    //absolute number needed
    if ($row['threshold'] < 0) {
      $threshold = -$row['threshold'];
    }
    //percentage needed.  Abstain count tells us whether or not it
    //should come from the global or race (doesn't include abstain)
    //totals
    else {
      $threshold = $row['threshold']*
        ($row['abstain_count']?$global_totals[$race]:$race_votes)/100;
    }
  }
  else {
    $threshold = -1;
  }
  //keep track of winners
  $winners = array();
  //is there a minimum number of voters?
  if (isset($row['num_voters'])) {
    //not enough voters yet?
    if (count($votes) < $row['num_voters']) {
      print "<strong>Not enough people have voted yet (" . 
        escape_html($row['num_voters']) .
        " voters are needed for this to be a valid election, and " . 
        escape_html(count($votes)) .
        " votes have been cast)</strong><br/>\n";
      if (!$elect_row['open']) {
        print "Since the election is over, ";
        if (!$row['def_val']) {
          print "there is no winner.";
        }
        else {
          print "the default result of " .  escape_html($row['def_val']) .
            " has won<br/>\n";
        }
      }
      else {
        //user time puts the time in the user's time zone
        print "If not enough people vote by " . 
          escape_html(user_time($elect_row['end_date']), 'l, F j, Y, g:i a') .
          ", ";
        if (!$row['def_val']) {
          print "there will be no winner.";
        }
        else {
          print "the default result of " . escape_html($row['def_val']) .
          " will win<br/>\n";
        }
      }
      print "The result for the votes cast is:<br/>\n";
    }
  }
  //put condorcet, borda at end.  We do it now for tie-breaking, but
  //suppress the printing until later.
  ob_start();
  //condorcet doesn't regard weights, if those were ever different, so
  //we use votes_copy, which didn't have the weights.
  $other_choice = condorcet_choice(array_keys($choices),$votes_copy,null,true);
  if ($other_choice !== null) {
    print("Condorcet choice");
    //maybe multiple Condorcet choices
    if (is_array($other_choice)) {
      print "s: " . join(', ',array_map('escape_html',$other_choice));
    }
    else {
      print ": " . escape_html($other_choice);
    }
    print "<br>\n";
  }
  if (($other_choice = borda_choice($choices,$votes_copy,null,true)) !== null) {
    print("Borda choice");
    if (is_array($other_choice)) {
      print "s: " . join(', ',array_map('escape_html',$other_choice));
    }
    else {
      print ": " . escape_html($other_choice);
    }
    print "<br>\n";
  }
  //store for later.
  $other_methods = ob_get_clean();
  //while there are still any candidates left and any winners to pick, loop
  while (count($choices) && $row['num']) {
    //little hack so that display of results does not show last
    //candidate standing with all the votes -- just make sure that
    //this candidate will get elected in this iteration.
    if ($row['runoff'] == 1 && count($choices) == 1 && 
        (!isset($threshold) || $threshold == -1)) {
      $one_winner = array_keys($choices);
      $winners[] = $one_winner[0];
      $row['num']--;
      break;
    }
    //sort them in reverse order, so most votes comes first.
    arsort($choices);
    //if we're printing everything out, print out all the votes here.
    if ($full_results && count($votes)) {
      print("<table border=1>");
      //put ballots into bins by current choice, then print out
      $name_choices = array();
      foreach ($votes as $member => $junk) {
        $mem_ballot =& $votes[$member];
        //Abstentions
        if (!is_array($mem_ballot['ballot']) || 
            !count($mem_ballot['ballot'])) {
          continue;
        }
        if (!isset($name_choices[$mem_ballot['ballot'][0]])) {
          $name_choices[$mem_ballot['ballot'][0]] = array();
        }
        //member goes in bin, voting for this candidate
        $name_choices[$mem_ballot['ballot'][0]][] = $member;
      }
      //ok, print out
      print("<tr>");
      foreach ($choices as $option => $count) {
        //nobody voted for this one?
        if (!isset($name_choices[$option])) {
          $name_choices[$option] = array();
        }
        print("<td style='font-weight: bold'>" . escape_html($option) . 
              " (" . count($name_choices[$option]) . ")</td>");
      }
      print("</tr>\n");
      //ok, nasty logic.  We need to print as many rows as the number
      //of votes the most popular candidate got.  Hence the flag -- it
      //tells us to keep on printing rows.
      $flag = true;
      $ii = 0;
      while ($flag) {
        $flag = false;
        print("<tr>");
        foreach ($choices as $option => $count) {
          //done printing this candidate's voters?
          if (!isset($name_choices[$option]) || 
              count($name_choices[$option]) <= $ii) {
            continue;
          }
          //does this print not finish things for this choice?
          if (count($name_choices[$option]) > $ii + 1) {
            $flag = true;
          }
          //print the voter
          print("<td>" . escape_html($name_choices[$option][$ii]));
          //weight might not be 1 -- overflow and all that
          if ($votes[$name_choices[$option][$ii]]['weight'] != 1) {
            print (" (" . 
                   escape_html($votes[$name_choices[$option][$ii]]['weight']) 
                   . ")");
          }
          print("</td>");
        }
        print("</tr>");
        $ii++;
      }
      print("</table>");
    }
    //winners this time around
    $cur_winners = array();
    //people who might be winners, if they're not tied with too many others
    $tie_winners = array();
    //we need a threshold to pick the most popular candidates still
    //remaining if we're doing instant runoff.  They will have at
    //least #votes/#choices, but #votes is #votes *remaining*
    //comment here for emacs
    if ((!isset($threshold) || $threshold == -1) && $row['runoff'] == 1) {
        $soft_threshold = array_sum($choices)/$row['num'];
    }
    else {
      $soft_threshold = $threshold;
    }
    //check choices for winners
    //how many votes did the last "winner" get?
    $cur_votes = -1;
    foreach ($choices as $option => $choice_count) {
      //passed the threshold?
      if ($choice_count >= $soft_threshold) {
        //do we already have enough winners, and the last winner
        //elected had more votes than this potential winner?  If so,
        //don't add this winner.
        if ($row['num']-count($cur_winners)-count($tie_winners) <= 0 &&
            $cur_votes > $choice_count) {
          break;
        }
        //otherwise, add them.
        //are we ok to put these tied winners in with the rest?
        if (!count($tie_winners) || $cur_votes > $choice_count) {
          $cur_winners += $tie_winners;
          $tie_winners = array();
          $cur_votes = $choice_count;
        }
        $tie_winners[$option] = $choice_count;
      }
      //because we ordered by votes, all the rest of the candidates
      //don't have enough.
      else {
        break;
      }
    }
    if (count($cur_winners)+count($tie_winners) <= $row['num']) {
      $cur_winners += $tie_winners;
    }
    //decrease number of winners needed by winners we just got.
    $row['num'] -= count($cur_winners);
    //no runoff?
    if ($row['runoff'] != 1) {
      //these are it -- set and get out of loop forever
      $winners = array_keys($cur_winners);
      break;
    }
    //clean up ballots for next time, since it's instant runoff
    //were there winners whose votes might spill over?
    if (count($cur_winners)) {
      //get the remaining choices
      $choices = array_diff($choices,$cur_winners);
      foreach ($cur_winners as $winner => $count) {
        //maybe there were as many or more winners as candidates
        if (!$count) {
          continue;
        }
        //here's their extra
        $multiplier = 1-$soft_threshold/$count;
        //if there was any extra
        if (!$multiplier) {
          continue;
        }
        //look at every vote to see if they voted for this choice
        foreach ($votes as $mem => $junk) {
          $mem_ballot =& $votes[$mem];
          //did they?
          if (!count($mem_ballot['ballot']) || 
              $mem_ballot['ballot'][0] != $winner) {
            continue;
          }
          //scale the weight of this ballot
          //and if there are any choices remaining after
          //getting rid of the top one, add it in.
          array_shift($mem_ballot['ballot']);
          if (!normalize_ballot($mem_ballot['ballot'])) {
            continue;
          }
          $mem_ballot['weight'] *= $multiplier;
          $choices[$mem_ballot['ballot'][0]] += 
            $mem_ballot['weight'];
        }
      }
      //add these winners into the big list of winners for this race
      $winners = array_merge($winners,array_keys($cur_winners));
    }
    //no winners?  We have to drop somebody
    else {
      //we need to get the lowest candidates.  Lowest will be
      //last, because they're ordered most -> least.  The
      //complication is that there might be multiple candidates who
      //are tied for last.
      $indices = array_keys($choices);
      $drops = array();
      //loop through the choices, getting candidates tied for last
      //start with the last choice and add choices until a candidate
      //doesn't have the same number of votes as the last candidate
      for ($drops[] = array_pop($indices); 
           end($drops) !== null && 
             $choices[end($drops)] == $choices[$drops[0]]; 
           $drops[] = array_pop($indices)) {}
      //we added one too many
      array_pop($drops);
      //did they get any votes at all?  As a short-circuit, all
      //candidates with 0 votes are eliminated immediately, to avoid
      //100-round runoffs.
      if ($choices[$drops[0]]) {
        if (count($drops) > 1) {
          //of these potential drops, who is the least popular?
          $loser = condorcet_choice($drops,$votes_copy,1);
          if ($loser) {
            if (!is_array($loser)) { 
              if ($choices[$loser]) {
                print "Using the Condorcet method to break a tie " .
                  "and eliminate a candidate.<br/>\n";
              }
            }
            else {
              if (count($loser) < count($drops) && 
                  $choices[$loser[0]]) {
                print "Narrowing which candidate to drop " .
                  "using the Condorcet method.<br>\n";
              }
              $drops = $loser;
              $loser = null;
            }
          }
          //might have been reset right above
          if (!$loser) {
            $loser = borda_choice($drops,$votes_copy,1);
            if ($loser) {
              if (!is_array($loser)) { 
                if ($choices[$loser]) {
                  print "Using the Borda method to break a tie " .
                    "and eliminate a candidate.";
                }
              }
              else {
                if (count($loser) < count($drops) && 
                    $choices[$loser[0]]) {
                  print "Narrowing which candidate to drop " .
                    "using the Borda method.<br>\n";
                }
                $drops = $loser;
                $loser = null;
              }
            }
          }
          //oh well, just drop the last one
          if (!$loser) {
            $loser = $drops[0];
            if ($choices[$loser]) {
              print "<h4>Warning!  There is no good way to choose " . 
                "which candidate should be eliminated!</h4>";
            }
          }
        }
        //there was only one loser, so easy to choose
        else {
          $loser = $drops[0];
        }
        //get rid of loser
        unset($choices[$loser]);
      }
      //get rid of all 0-vote choices
      else {
        foreach ($drops as $loser) {
          unset($choices[$loser]);
        }
      }
      if (count($votes)) {
        //go through votes.  Don't normalize all the ballots yet,
        //because maybe there's no need -- just normalize the ones
        //that definitely have to be reassigned.  Note that this is
        //trivial for 0-vote-losers, because they weren't anyone's
        //first choice
        foreach ($votes as $mem => $junk) {
          $mem_ballot =& $votes[$mem];
        //did they chose the loser?
          if (count($mem_ballot['ballot']) && 
              $mem_ballot['ballot'][0] == $loser) {
            if (normalize_ballot($mem_ballot['ballot'])) {
              //reassign
              $choices[$mem_ballot['ballot'][0]] += $mem_ballot['weight'];
            }
          }
        }
      }
    }
  }
  //phew, done with big loop
  //print out winners
  foreach ($winners as $winner) {
    print escape_html($winner);
    //vote totals are inaccurate for runoffs, and don't embarrass
    //people without full results
    if (!$row['runoff'] && $full_results) {
      print " (" . escape_html($choices[$winner]) . " out of " . 
        escape_html($global_totals[$race]) . " votes)";
    }
    print "<br/>\n";
  }
  //were there no winners at all, and a default value?
  if (!count($winners) && strlen($row['def_val'])) {
    print escape_html($row['def_val']);
    //# of votes is meaningless in instant runoff
    //comment for emacs
    if ($row['runoff'] != 1) {
      print " (";
      $firstflag = true;
      foreach ($choices as $choice => $count) {
        if ($choice == $row['def_val']) {
          continue;
        }
        if ($firstflag) {
          $firstflag = false;
        }
        else {
          print "; ";
        }
        //if the threshold is a percent and abstains don't count, then
        //it's the race_votes, otherwise threshold comes from global
        //total.
        print escape_html($choice) . " had "  . 
          escape_html($count) . " out of " . 
          escape_html($row['threshold'] < 0 && !$row['abstain_count']?
                      $race_votes:$global_totals[$race]) . 
          " votes with " .
          escape_html(ceil($threshold)) . " needed";
        //      break;
      }
      print ")";
    }
  }
  //are there still some spots to be filled?
  if ($row['num'] > 0) {
    //might have happened because threshold was too high
    if ($threshold != -1) {
      print("<h4>Warning!  Not enough candidates beat the threshold of " . 
            escape_html($threshold) . "</h4>");
    }
    //or maybe not enough candidates running to start with
    else {
      print "<h4>Warning!  Not enough candidates were elected!</h4>";
    }
    if (count($tie_winners)) {
      print "The last " . escape_html($row['num']) . " winner(s) should be " .
        "chosen from the following:<br/>";
      print join("<br/>",array_map('escape_html',array_keys($tie_winners)));
    }
  }
  //do add comments/candidates
  $str = "<form method=post action='" . escape_html(this_url()) . "'>" .
    "<input type=hidden name='election_name' value='" . 
    escape_html($election_name) . "'>";
  if ($row['member_comments'] !== null) {
    if (strlen($row['member_comments'])) {
      $str .= "People say:\n<pre>";
      $str .= escape_html($row['member_comments']);
      $str .= "</pre>\n";
    }
    $str .= "Add a comment other people will see.<br>" .
      "<textarea rows=5 cols=30 name='" . $race . 
      "-member_comments'></textarea><br>";
  }
  if ($row['member_add']) {
    $str .= "<br>Add a candidate (make sure your candidate does " .
      "not already appear above!)<br>";
    $str .= "<input name='" . $race . "-member_add'>";
  }
  if ($row['member_add'] || isset($row['member_comments'])) {
    print $str;
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
    print "'>";
  }
  //if there were other methods, and we're being asked for everything:
  if ($full_results && $other_methods) {
?>
<h3>What do other methods say?</h3>
<?=$other_methods?>
</p><hr>
<?php
              }
}

  //ok, just need to print out everyone's ballots for verification
echo "<h3>List of ballots:</h3>";
echo "<table border>\n";
echo "<tr>\n";
echo "<td>voter id</td>";
//header row 
foreach ($all_races as $race => $info) {
  //feedback isn't shown here -- don't want feedback from different
  //races to be correlated.
  //and sometimes there's a deleted race
  if (!isset($info['feedback']) || 
       !$info['feedback']) {
    echo "<td>" . escape_html($info['display_name']) . "</td>";
  }
}
echo "</tr>\n";
//loop through members 
foreach ($members as $member_name => $votes) {
  echo "<tr>\n";
  echo "<td>" . escape_html($member_name) . "</td>";
  foreach ($all_races as $race => $info) {
    //feedback isn't printed
    if (isset($info['feedback']) && 
        $info['feedback']) {
      continue;
    }
    echo "<td>";
    if (array_key_exists($race,$votes)) {
      if (count($votes[$race])) {
        echo implode("<br/>\n",array_map('escape_html',$votes[$race]));
      }
      else {
        echo "(abstain)";
      }
    }
    else {
      echo "(No vote)";
    }
    echo "</td>";
  }
  echo "</tr>\n";
}
?>
  </table>
<?php
  if (!$full_results) {
    //trickiness with this_url()
    $_GET['full_results'] = null;
    $_GET['election_name'] = $election_name;
?>
 <a href='<?=this_url()?>'>View full results</a>
<?php
    }
?>
</body></html>
<?php 
//used mainly when candidates have been eliminated, to clean them out.
//Also used as a check at the beginning to make sure all candidates
//are valid, which exposes bugs in the voting process.
function normalize_ballot(&$ballot,$voter=null) {
  //current candidates
  global $choices;
  if (!is_array($ballot) || !count($ballot)) {
    return 0;
  }
  //go through ballot
  for ($ii = 0; $ii < count($ballot); $ii++) {
    //there is the option for "equal preference" with this, although
    //not in the general election options, yet.
    //here, one ranking at this spot?
    if (!is_array($ballot[$ii])) {
      //is this choice a valid one?
      if (!array_key_exists($ballot[$ii],$choices)) {
        //it's not.  Warn people about it, if necessary.
        if ($voter && strlen($ballot[$ii])) {
          print "<h4>Unsetting preference $ii, " . 
            escape_html($ballot[$ii]) . 
            ", for " . escape_html($voter) . 
            ", since it is not a candidate.</h4>";
        }
        array_splice($ballot,$ii,1);
        //repeat this index, since we got rid of this entry
        $ii--;
      }
    }
    //an array of equally-ranked choices!
    else {
      for ($jj = 0; $jj < count($ballot[$ii]); $jj++) {
        if (!array_key_exists($ballot[$ii][$jj],$choices)) {
          if ($voter) {
            print "<h4>Unsetting a preference $ii, " . 
              escape_html($ballot[$ii][$jj]) . 
              ", for " . escape_html($voter) . 
              ", since it is not a candidate.</h4>";
          }
          array_splice($ballot[$ii],$jj,1);
          $jj--;
        }
      }
      //did we unset every preference at this ranking?
      if (!count($ballot[$ii])) {
        array_splice($ballot,$ii,1);
        $ii--;
      }
    }
  }
  return (count($ballot));
}

//utility function to find something in array, wherever it may be.
//Returns key of element in top array that will lead eventually to
//needle.  Limit key allows you to stop search when you hit something.
function array_search_recursive($needle,$haystack,$limit_key = false) {
  $key = array_search($needle,$haystack);
  if ($key !== false) {
    return $key;
  }
  foreach ($haystack as $key => $bale) {
    if (is_array($bale) &&
        array_search_recursive($needle,$bale) !== false) {
      return $key;
    }
    if ($key === $limit_key) {
      return false;
    }
  }
  return false;
}
 
//figure out which of two things comes first in more ballots -- used
//for condorcet
 function compare_two($choice1,$choice2,&$votes) {
  $ctr = 0;
  foreach ($votes as $mem => $ballot) {
    $key1 = array_search_recursive($choice1,$ballot);
    $key2 = array_search_recursive($choice2,$ballot,$key1);
    if ($key1 !== false && ($key2 === false || $key1 < $key2)) {
      $ctr++;
    }
    else if ($key2 !== false && ($key1 == false || $key1 > $key2)) {
      $ctr--;
    }
  }
  return $ctr;
}

//find the condorcet winner (loser if loser not null).  New forces a
//recalculation of the whole grid
function condorcet_choice($opts,&$votes,$loser = null,$new = null) {
  //choices are the candidates currently existing
  global $choices;
  $choice_keys = array_keys($choices);
  $count = count($choices);
  //populate this once, then we're done.  It's a 2-d array of
  //comparisons.
  static $data = array();
  if ($new) {
    $data = array();
  }
  if ($data == array()) {
    $first = true;
  }
  else {
    $first = false;
  }
  $rets = array();
  foreach ($choice_keys as $ii) {
    //skip candidates not in the full list
    if (!in_array($ii,$opts)) {
      continue;
    }
    //2-d array
    if (!isset($data[$ii])) {
      $data[$ii] = array();
    }
    //is this choice a winner/loser?  Assume yes and "and" each time
    $return = true;
   //comparisons reversed for finding losers
    if ($loser) {
      $scalar = -1;
    }
    else {
      $scalar = 1;
    }
    //compare with all other possible choices
    foreach ($choice_keys as $jj) {
      //not a valid choice?
      if (!in_array($jj,$opts)) {
        continue;
      }
      //don't compare with self
      if ($ii == $jj) {
        continue;
      }
      //have we not calculated this yet?
      if (!isset($data[$ii][$jj])) {
        //well, if we've calculated the transpose element, that's easy.
        if (isset($data[$jj][$ii])) {
          $data[$ii][$jj] = -$data[$jj][$ii];
        }
        //do some work -- compare these options
        else {
          $data[$ii][$jj] = compare_two($ii,$jj,$votes);
        }
      }
      //and return so far with what we got
      $return &= ($scalar*$data[$ii][$jj] >= 0);
    }
    //it compared > (or <) everything!
    if ($return) {
      $rets[] = $ii;
    }
  }
  //print the whole grid.
  if ($first) {
    print("<h4>Condorcet grid</h4><table border=1>");
    print("<tr><td></td>");
    foreach ($choice_keys as $ii) {
      print("<td>" . escape_html($ii) . "</td>");
    }
    print("</tr>");
    foreach ($choice_keys as $ii) {
      print("<tr><td>" . escape_html($ii) . "</td>");
      foreach ($choice_keys as $jj) {
        print("<td");
        if ($ii == $jj || !$data[$ii][$jj]) {
          print("></td>");
          continue;
        }
        if ($data[$ii][$jj] < 0) {
          print(" style='color: red'");
        }
        print(">" . $data[$ii][$jj] . "</td>");
      }
      print("</tr>\n");
    }
    print("</table>");
  }
  //don't return an array with one element
  if (count($rets)) {
    if (count($rets) == 1) {
      return $rets[0];
    }
    return $rets;
  }
  return null;
}

function borda_choice($choices,&$votes,$loser = null,$new = null) {
  static $done = false;
  static $borda = null;
  //same principle -- cache data
  if ($new) {
    $done = $borda = false;
  }
  if (!$done) {
    $borda = $choices;
    //zero out votes
    foreach ($borda as $key => $junk) {
      $borda[$key] = 0;
    }
    $choice_keys = array_keys($borda);
    $count_orig = count($choices);
    foreach ($votes as $voter => $ballot) {
      $count = $count_orig;
      //choice gets points for being ahead of everything remaining on
      //this voter's ballot
      foreach ($ballot as $pref) {
        if (!is_array($pref)) {
          $borda[$pref] += --$count;
          continue;
        }
        $tie_count = count($pref);
        $count -= $tie_count;
        foreach ($pref as $tie) {
          //only get half point for being tied with something
          $borda[$tie] += $count + ($tie_count/2);
        }
      }
    }
    $done = true;
  }
  //to find the borda winner/loser of a particular set, look for the
  //max (or min) num among the possible options
  $max_num = null;
  $return_choice = null;
  if ($loser) {
    $scalar = -1;
  }
  else {
    $scalar = 1;
  }
  foreach ($borda as $choice => $num) {
    //not in the set?  skip
    if (!in_array($choice,$choices)) {
      continue;
    }
    //haven't found one yet, or this one is better?
    if ($max_num === null ||
        $scalar*$num > $scalar*$max_num) {
      $max_num = $num;
      $return_choice = array($choice);
      continue;
    }
    //tie?
    if ($num == $max_num) {
      $return_choice[] = $choice;
    }
  }
  if (count($return_choice) < 2) {
    $return_choice = $return_choice[0];
  }
  return $return_choice;
}

//for as random a sort as we can get so that it stays the same each time.
function rand_md5_cmp($a,$b) {
  global $race;
  if ($a === $b) {
    return 0;
  }
  return strcmp(md5($a . $race), md5($b . $race));
}
?>
