<?php 
$body_insert = '';
require_once('default.inc.php');
$member_name_real = $member_name;
#$db->debug = true;
$dummy_string = get_static('dummy_string');
if (!array_key_exists('election_name',$_REQUEST)) {
  $res = $db->Execute('SELECT ' . bracket('election_name') . ' FROM ' .
                      bracket('elections_record') . 
                      ' WHERE unix_timestamp() >= `end_date` and `anon_voting` > 1 union ' .
                      'select `election_name` from `elections_attribs` where ' .
                      '`attrib_name` = ? and `attrib_value` != 0 order by `election_name`',
                      array('interim_results'));
  if (is_empty($res)) {
    exit("No elections currently viewable.<p>\n");
  }
  while ($row = $res->FetchRow()) {
    $elections[] = $row['election_name'];
  }
#}
?>
<html><head><title>Election Results</title></head><body>
<?=$body_insert?>
<form method='GET' action='<?=escape_html($_SERVER['REQUEST_URI'])?>'>
<?php 
$ii = 0;
foreach ($elections as $election) {
#}
?>
<label for='<?=$ii?>' ><input type=radio name='election_name' id='<?=$ii++?>' value='<?=escape_html($election)?>' checked><?=escape_html($election)?></label><br>
<?php 
#{
}
?>
<input type='submit' value='Choose election'>
</form></body></html>
<?php
exit;
#{
}
$election_name = $_REQUEST['election_name'];
$elect_row = $db->GetRow("select " .
                         "`anon_voting`, `anon_voting` < 2 as `open`, " .
                         "`end_date` > unix_timestamp() as `time_open`, `end_date` " .
                         "from `elections_record` where `election_name` = ? ",
                         array($election_name));
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
    $race[1] = 'candidates';
  }
  else {
    if (get_election_attrib('member_comments') === null) {
      print "<h4>Cannot comment on race " . escape_html($race_name) . 
        "!</h4>";
      continue;
    }
  }
  $cur_text = get_election_attrib($race[1]);
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
    $cands = explode("\n",$cur_text);
    if (array_search($val,$cands) !== false) {
      print "<h4>" . escape_html($val) . " is already a candidate!</h4>\n";
      continue;
    }
    $cur_text .= "\n" . $val;
  }
  $db->Execute("update `elections_attribs` set `attrib_value` = ? where " .
               "`election_name` = ? and `race_name` = ? and `attrib_name` = ?",
               array($cur_text,$election_name,$race_name,$race[1]));
}
?>
<html><head>
<title>Election results for <?=escape_html($election_name)?></title></head>
<style>
/* Browser specific (not valid) styles to make preformatted text wrap */
pre { 
 white-space: pre-wrap;       /* css-3 */
 white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
 white-space: -pre-wrap;      /* Opera 4-6 */
 white-space: -o-pre-wrap;    /* Opera 7 */
 word-wrap: break-word;       /* Internet Explorer 5.5+ */
}
table {
  empty-cells: show;
}
</style>
<body>
<?php
print $body_insert;
#$db->debug = true;
$interim_results = $db->GetRow("select `attrib_value` from `elections_attribs`" .
                               " where `election_name` = ? and `attrib_name` = ?",
                               array($election_name,'interim_results'));
if (!is_empty($interim_results)) {
  $interim_results = $interim_results['attrib_value'];
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
if ($elect_row['open'] && 
    (!$interim_results || ($elect_row['anon_voting'] && $interim_results < 2))) {
  exit("<h3>$election_name is not finished and interim results are not viewable.</h3>");
}
$anon_voting = $elect_row['anon_voting']%2;
$full_results = (!$anon_voting && !array_key_exists('full_results',$_REQUEST)) || 
(array_key_exists('full_results',$_REQUEST) && $_REQUEST['full_results'] !== 0);
?>
Below are the results of the voting.  
<?php if ($elect_row['time_open']) { ?>
 You can still <a href='voting.php?election_name=<?=escape_html($election_name)?>'>vote in the election</a>
<?php if (!$anon_voting) { ?>
or change your vote if you wish
                          <?php } ?>
.
                                                                  <?php } ?>
The method used for instant runoff is the same as for ASUC elections -- <a href='http://www.barnsdle.demon.co.uk/vote/fracSTV.html'>Here is a description of the procedure</a>.  Ties in the runoff are broken using first the <a href='http://condorcet.org/emr/defn.shtml#Condorcet%20winner'>Condorcet winner</a> (actually loser) if it exists, and then the <a href='http://condorcet.org/emr/methods.shtml#Borda'>Borda winner</a> (actually loser) if it exists.<p>  
Here is <a href='voting.php?election_name=<?=escape_html(urlencode($election_name))?>'>the ballot</a> that was used.
  
<h3>Check out the <a href='elections_log.php'>elections log</a> to make sure no one's trying to cheat</h3>
  <?php
#';
#$db->debug = true;
$res = $db->Execute("select * from `votes` where `election_name` = ?",array($election_name));
$all_votes = array();
$members = array();
while ($row = $res->FetchRow()) {
  $race = $row['option_name'];
  if (!array_key_exists($race,$all_votes)) {
    $all_votes[$race] = array();
  }
  $member_name = $row['member_name'];
  if (array_key_exists($member_name,$all_votes[$race])) {
    print("<h3>Error!  It looks like " . escape_html($member_name) . " has voted twice!</h3>");
  }
  else {
    $all_votes[$race][$member_name] = array();
    $all_votes[$race][$member_name]['weight'] = 1;
    if (strlen($row['option_choice'])) {
      $members[$member_name][$race] = 
        $all_votes[$race][$member_name]['ballot'] = explode("\n",$row['option_choice']);
    }
    else {
            $members[$member_name][$race] = 
              $all_votes[$race][$member_name]['ballot'] = array();
    }

  }
}
$res = $db->Execute("select distinct `member_name` from `votes` " .
                    "where `election_name` = ?",array($election_name));
$global_total = 0;
while ($res->FetchRow()) {
  $global_total++;
}

$nameres = $db->Execute('select `autoid`,`race_name` from `elections_attribs` ' .
                        'where `election_name` = ? and `attrib_name` = ? order by 0+`attrib_value`',
                        array($election_name,'race_name'));

while ($namerow = $nameres->FetchRow()) {
  $race = $namerow['autoid'];
  $race_name = $namerow['race_name'];
  $race_names[$race] = $race_name;
  $race_nums[] = $race;
  $res = $db->Execute('select `attrib_name`,`attrib_value` from `elections_attribs`' .
                      ' where `election_name` = ? and `race_name` = ?',
                      array($election_name,$race_name));
  $row = array();
  while ($temprow = $res->FetchRow()) {
    $row[$temprow['attrib_name']] = $temprow['attrib_value'];
  }
  if (!isset($row['num'])) {
    $row['num'] = 1;
  }

  $all_races[$race]['display_name'] = $race_name;
  $all_races[$race]['feedback'] = isset($row['feedback']) && $row['feedback'];
  print("<h4>" . escape_html($race_name) . "</h4>");
  $votes =& $all_votes[$race];
  if ($all_races[$race]['feedback']) {
    foreach ($votes as $key => $val) {
      $votes[$key] = join($val['ballot'],"\n");
    }
    usort($votes,'rand_md5_cmp');
    print "<ul>\n";
    foreach ($votes as $feed) {
      if (strlen($feed)> 0) {
        print "<li>" . $feed . "\n";
      }
    }
    if ($house_name == 'hoy') {
      switch ($all_races[$race]['display_name']) {
        case 'VOC President':
          print ("<li> Sometimes there are a bit too many emails, could be
more succint. House meetings are very scattered-the second meeting was
short notice.  Perhaps making a poster shown in the stairwell would
help as well to do a reminder other than email.
<li> yeah, please less emails.  i need weekend meetings, i
can't meet on monday through thursday.
<li> Overall, good job. She takes her job seriously and does
the best she can. I think the emails could be more to-the-point, as a
previous member has written, but I don't mind them as I think they are
important notices and save on paper, which has been a complaint in the
past. I think the house president's job is to say very little during a
meeting, but rather to simply direct \"traffic\" so-to-speak. Perhaps
she could work on this as sometimes I feel that she reitterates things
that do not need reitteration. Otherwise-great job.
<li> Maria conducts meetings efficiently.  However, it would
be nice to have a little time at the end of the meetings for member
announcements and last minute member concerns.
<li> Have the by-laws been updated yet?  Individuals keep
saying, \"We're working on it,\" but no word has been said on the
progress made since summer.  Now that we have an online voting system,
more free time should be available, right?");
          break;
      case 'VOC House Manager':
        print ("<li> I haven't had a fire safety training yet and it is
already well into the semester.
<li> fire safety isn't mandatory, it's a precaution that
Hoyt takes that is above and beyond, and it's really just walking down
the fire escape.
<li> You could always go up to her individually and schedule one.
<li> Quiet hours are never inforced until two hours after
they start.  I can hear her coming out of the television room after
ten being loud herself.
<li> I think quiet hours are enforced just fine. The few
seconds after one comes out of the television room seems hardly a
point of contention, in my opinion. Perhaps if someone is that light
of a sleeper, they should wear earplugs. I haven't heard any after
hours loud noises/conversations for an extended period of time this
semester. I think it's important to remember that managers cannot be
everywhere at once. And if any individual in the house has a problem,
it is important to speak up directly and not just bring it up in VOCs.
A manager is not omnipotent, so please be fair.
<li> Controlling by fine threats is not good for the house moral.
<li> She is excellent at taking comments and putting them
into action or at least directing questions/comments to the right
individuals. In response to the \"fine threats\" comment--when all else
has been tried first, this is the most efficient way to get things
done after a Hoytian does not abide by house rules. If you feel fine
threats are not good, perhaps you should try to talk to the managers
about a new system. The fine system is tried and true, and in the end,
it's good for the house and yields great returns (ie, the surplus
which has paid for dues in the past).
<li> Iisha rocks!  I think she's doing a great job, the
house is running great this semester. :)
<li> Keep it up! She's doing an awesome job.
<li> Iisha is not as approachable as she could be and
doesn't follow up on some matters she is responsible for.");
        break;
      case 'VOC Workshift Manager':
        print("<li> She really tries hard to accommodate workshifters- she
does not want them to get fined. I have never seen a workshift manager
work so hard for the members.
<li> I think she does an awesome job of both being an
advocate for the members while at the same time enforcing the laws of
the land which MUST be done so we don't live in a dump. Great job!<li> You go girl!");
        break;
      case 'VOC Maintenance Manager':
        print("<li> I don't think this question has to do with the job that
the Maintenance Manager is doing. Our house is old and there's always
something that's going to go wrong, but Joan is doing an amazing job
to battle it, so I'm abstaining.
<li> Needs to be more on top of central maintenance when
house level tasks are not completed.
<li> There is no being more on top of CM than Joan is. They
are simply VERY difficult to deal with at times and I think she is
doing an outstanding job being on top of things. Many times they won't
return calls, or they'll leave something half finished, but she calls
and leaves multiple messenges. Why not talk to her if you think that
something is not being done? I bet you 10 times out of 10, she's
already talked to them.
<li> When you enter a room leave it the way it was- lock the
deadbolt.
<li> I did not receive a note that she would be entering my
room for the second round of room inspections.
<li> you need to let people know 48 hours in advance if
you're going to be entering their room.  i believe that's in the
bylaws.
<li> one word- piano :(
<li> Very open to suggestions and comments! Thank you for
listening to us!
<li> I suggest an orientation on how to plunge a toilet be
made mandatory. Individuals should know how to declog their own
toilets.  This would not only free up time for the maintenance crew to
do more important tasks, but it would also be more pleasant for the
next visitor.
<li> My guess would be that people know how they just don't
want to because either their embarrassed or lazy. Either way, I know
for a fact that the maintenance crew members do not spend all their
time unclogging toilets.
<li> Joan is thorough in her work, very approachable to all
members, and has gone beyond what is required of her as maintenance
manager.  Keep it up, Joan!");
        break;
      case 'VOC Finance Manager':
        print("<li> we should have gotten a house bill for fall already,
it's the 5th week.
<li> Yes! When I let her know about our faulty laundry
machines she got right on it that very moment! (I feel that not a lot
of people know this, but Finance Managers are in charge of the
maintenance of the washers and driers, so let her know if anything
happens).
<li> Tracy is an excellenct finance manager in all regards.");
        break;
      case 'VOC Kitchen Manager':
        print("<li> She is the best kitchen manager I have seen to this
date! It is very tough to be a kitchen manager and a lot of her job is
behind the scenes. I think she goes above and beyond the call of duty.
<li> Definitely. IKC is but one example--not to mention the
alphabetizing of the spices! The list goes on and on...
<li> If the co-ops offer the tea and it is not expensive,
then it should not be a problem to cater to individuals.
<li> What tea? We have tea, and it's yummy!
<li> Very unapproachable.
<li> I think sometimes she can take an unconstructive
complaint too personally. Granted, it's tough not to. Managers have it
rough--no question--people can get really nasty sometimes, but I don't
think that should influence her feelings (especially about the house
in general) because she's good at what she does. Other than that she
is awesome when it comes to comments and suggestions. She implements
things right away and is VERY proactive. If anything happens she is
very responsible and takes care of the problem immediately.
<li> Can the cooks occasionally make brown rice instead of
white?  Secondly, tuna salad (or any salad for the matter) as the only
main meat dish is insufficient. Finally, please ask the cooks to make
a vegetarian or vegan side (leafy vegetables!) instead of another
dessert.
<li> All cooks have a vegan and vegetarian side. It is not
required to have two vegan sides, but perhaps you can take it to the
house meeting for a vote. It is true that desserts are optional, but
sometimes there are a limited supply of vegetables (let alone
\"leafy\"), so consult the refrigerator or the KM to be more informed.
Also I think that leafy vegetables are more expensive and the house is
on a budget, but for that you should consult our KM directly. When all
else fails, you can always make an extra side for yourself when the
cooks have finished.
<li> Jeanie is an excellent kitchen manager.  She goes
beyond what her position requires of her, like cooking dinner herself
when no one had signed up for the shift.  Thanks, Jeanie!");
        break;
      case 'VOC Recycling Manager':
        print("
<li> Full bins in the alley way.  Free pile needs to be gone
through.
<li> Just this week the recycling area looked magnificent.
The free pile needs to go on a diet, though. It makes the common room
look dumpy.
<li> Should require recycling class for new members.
<li> I agree with the above comment.  People are not
composting correctly.  The incentive given to attend the recycling
orientation was not enough.  Fines should be given if one does not
attend the orientation.
<li> I agree too. In the past, when the recycling
orientation was mandatory, people knew what to do.");
        break;
      case 'VOC Garden Manager':
        print("<li> Should have kept log from the beginning
<li> I agree. I think that along with tours there should be
a map on the garden board of what is planted where--not only for house
members but for future garden managers to update.
<li> Taking out perfectly good flowers is a little upsetting.
<li> I would rather see her sweeping the backyard than using
her hours to search the hills for lettuce as it says in her flier,
because we get lettuce at the house anyways. I don't think that a
manager should get 5 hours a week for this. Right now we have a lot of
open shifts in the house. It would be a shame for lettuce would be
more important than washing pots.
<li> I think she was looking for plants to transplant, not
for greens to toss into one single salad. Those native lettuce plants
now brighten the back yard. Thanks, Morley!");
        break;
      case 'VOC Social Manager':
        print("<li> Movies do not count as social events as people do those
all of the time anyway.
<li> Should offer to take groups to other parties, since not
everyone is invited to the groups that the house forms itself.
<li> Maybe Caitlin could post schedules and info on upcoming
social events for other houses on the social board.
<li> Not enough events.
<li> How much money is being spend on alcohol alone?
<li> It would be great to have a chart showing how much
money is going where.
<li> I think it was great that she did a room to room right
away to get things rolling, although it has always been tough for
social managers to get money early enough, so usually events were
postponed.");
        break;
      case 'VOC Secretary':
        print("<li> Where are the by-laws available?
<li> Last time I saw them they were above the mailbox in a
blue binder.
<li> Maybe Kaja could post the minutes in the bathroom where
everyone would see them.");
        break;
      case 'VOC Board Rep':
        print ("<li> I really appreciate the signs posted in the restrooms
because they help me to stay informed.");
        break;
      case 'VOC Ethernet Manager':
        print("
<li> Very helpful and accommodating
<li> The people at the ends of the house do not get a good
wireless signal or none at all. It would be nice if we could install
wireless extenders in the house. One could be in the store, since it
is locked at all times, or at least under supervision when the finance
manager or her assistant is there.");
        break;
      case 'VOC Health Worker':
        print("
<li> I absolutely love our health tips.
<li> Bandaids and neosporin are never in stock in the
kitchen this is very essential.
<li> I like that she offers hikes");
      default:
      }
    }
    print "</ul>";
    continue;
  }


  $all_races[$race]['candidates'] = explode("\n",$row['candidates']);
  $candidates =& $all_races[$race]['candidates'];
  if (count($candidates) && !$candidates[0]) {
    array_shift($candidates);
  }
  $count = count($candidates);
  $choices = array();
  foreach ($candidates as $cand) {
    $choices[$cand] = 0;
  }
  $votes_copy = array();
  if (count($votes)) {
    foreach ($votes as $mem => $junk) {
      $mem_ballot =& $votes[$mem];
      if (!normalize_ballot($mem_ballot['ballot'],$mem)) {
        continue;
      }
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
  complete_ballots($choices,$votes_copy);
  if ($row['threshold'] != 0) {
    if ($row['threshold'] < 0) {
      $threshold = -$row['threshold'];
    }
    else {
      $threshold = $row['threshold']*$global_total/100;
    }
  }
  else if ($row['runoff']>0) {
    $threshold = $global_total/($row['num']);
  }
  else {
    $threshold = -1;
  }
  $winners = array();
  if (isset($row['num_voters'])) {
    if (count($votes) < $row['num_voters']) {
      print "<strong>Not enough people have voted yet (" . escape_html($row['num_voters']) .
        " voters are needed for this to be a valid election, and " . escape_html(count($votes)) .
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
  ob_start();
  $other_choice = condorcet_choice(array_keys($choices),$votes_copy,null,true);
  if ($other_choice !== null) {
    print("Condorcet choice");
    if (is_array($other_choice)) {
      print "s: " . join(', ',array_map('escape_html',$other_choice));
    }
    else {
      print ": " . escape_html($other_choice);
    }
    print "<br>\n";
  }
  if (($other_choice = borda_choice($choices,$votes_copy,null,true)) !== null) {
    print("Borda choice: " . escape_html($other_choice) . "<br>");
  }
  $other_methods = ob_get_clean();
  while (count($choices) && $row['num']) {
    if (count($choices) == 1) {
      $threshold = 0;
    }
    arsort($choices);
    if ($full_results && count($votes)) {
      print("<table border=1>");
      $name_choices = array();
      foreach ($votes as $member => $junk) {
        $mem_ballot =& $votes[$member];
        if (!is_array($mem_ballot['ballot']) || !count($mem_ballot['ballot'])) {
          continue;
        }
        if (!isset($name_choices[$mem_ballot['ballot'][0]])) {
          $name_choices[$mem_ballot['ballot'][0]] = array();
        }
        $name_choices[$mem_ballot['ballot'][0]][] = $member;
      }
      print("<tr>");
      foreach ($choices as $option => $count) {
        if (!isset($name_choices[$option])) {
          $name_choices[$option] = array();
        }
        print("<td style='font-weight: bold'>" . escape_html($option) . 
              " (" . count($name_choices[$option]) . ")</td>");
      }
      print("</tr>\n");
      $flag = true;
      $ii = 0;
      while ($flag) {
        $flag = false;
        print("<tr>");
        foreach ($choices as $option => $count) {
          if (!isset($name_choices[$option]) || count($name_choices[$option]) <= $ii) {
            continue;
          }
          if (count($name_choices[$option]) > $ii + 1) {
            $flag = true;
          }
          print("<td>" . escape_html($name_choices[$option][$ii]));
          if ($votes[$name_choices[$option][$ii]]['weight'] != 1) {
            print (" (" . escape_html($votes[$name_choices[$option][$ii]]['weight']) . ")");
          }
          print("</td>");
        }
        print("</tr>");
        $ii++;
      }
      print("</table>");
    }
    $cur_winners = array();
    foreach ($choices as $option => $count) {
      if ($count >= $threshold) {
        if ($row['num']-count($cur_winners) <= 0 &&
            end($cur_winners) > $count) {
          break;
        }
        $cur_winners[$option] = $count;
      }
      else {
        break;
      }
    }
    $row['num'] -= count($cur_winners);
    if ($row['num'] < 0) {
      print "<h4>This race has too many candidates tied for winner!</h4>";
      print "The candidates to be chosen from are: ";
      //printed out down below, in list of winners
      /*      foreach ($cur_winners as $option => $votes) {
        print escape_html($option) .  " (" . escape_html($votes) . " votes)<br>\n";
      }*/
    }
    if ($row['runoff'] == 1) {
      if (count($cur_winners)) {
        $choices = array_diff($choices,$cur_winners);
        foreach ($cur_winners as $winner => $count) {
          if ($count) {
            $multiplier = 1-$threshold/$count;
            if ($multiplier) {
              foreach ($votes as $mem => $junk) {
                $mem_ballot =& $votes[$mem];
                if (count($mem_ballot['ballot']) && $mem_ballot['ballot'][0] == $winner) {
                  $mem_ballot['weight'] *= $multiplier;
                  if (normalize_ballot($mem_ballot['ballot'])) {
                    $choices[$mem_ballot['ballot'][0]] += $mem_ballot['weight'];
                  }
                }
              }
            }
          }
        }
        $winners = array_merge($winners,array_keys($cur_winners));
      }
      else {
        $indices = array_keys($choices);
        $drops = array();
        for ($drops[] = array_pop($indices); 
             end($drops) !== null && $choices[end($drops)] == $choices[$drops[0]]; 
             $drops[] = array_pop($indices)) {}
        array_pop($drops);
        if ($choices[$drops[0]]) {
          if (count($drops) > 1) {
            $loser = condorcet_choice($drops,$votes_copy,1);
            if ($loser) {
              if (!is_array($loser)) { 
                if ($choices[$loser]) {
                  print "Using the Condorcet method to break a tie and eliminate a candidate.";
                }
              }
              else {
                if (count($drops) < count($loser) && $choices[$loser[0]]) {
                  print "Narrowing which candidate to drop using the Condorcet method.<br>\n";
                }
                $drops = $loser;
                $loser = null;
              }
            }
            //might have been reset right above
            if (!$loser) {
              $loser = borda_choice($drops,$votes_copy,1);
              if ($loser && $choices[$loser]) {
                print "Using the Borda method to break a tie and eliminate a candidate";
              }
            }
            if (!$loser) {
              $loser = $drops[0];
              if ($choices[$loser]) {
                print "<h4>Warning!  There is no good way to choose which candidate should be " .
                  "eliminated!</h4>";
              }
            }
          }
          else {
            $loser = $drops[0];
          }
          unset($choices[$loser]);
        }
        else {
          foreach ($drops as $loser) {
            unset($choices[$loser]);
          }
        }
        foreach ($votes as $mem => $junk) {
          $mem_ballot =& $votes[$mem];
          if (count($mem_ballot['ballot']) && $mem_ballot['ballot'][0] == $loser) {
            if (normalize_ballot($mem_ballot['ballot'])) {
              $choices[$mem_ballot['ballot'][0]] += $mem_ballot['weight'];
            }
          }
        }
      }
    }
    else {
      $winners = array_keys($cur_winners);
      break;
    }
  }
  if ($row['num'] > 0 && (count($winners) || !$row['def_val'])) {
    print("<h4>Warning!  Not enough candidates beat the threshold of " . 
          escape_html($threshold) . "</h4>");
  }
  foreach ($winners as $winner) {
    print escape_html($winner);
    if (!$row['runoff'] && $full_results) {
      print " (" . escape_html($choices[$winner]) . " out of " . escape_html($global_total) . " votes)";
    }
    print "<br>\n";
  }
  if (!count($winners) && $row['def_val']) {
    print escape_html($row['def_val']);
    print " (";
    foreach ($choices as $choice => $count) {
      if ($choice == $row['def_val']) {
        continue;
      }
      print escape_html($choice) . " had "  . 
        escape_html($count) . " out of " . escape_html($global_total) . " votes with " .
        escape_html(ceil($threshold)) . " needed ";
      //      break;
    }
    print ")";
  }
  $str = "<form method=post action='" . escape_html($_SERVER['REQUEST_URI']) . "'>" .
    "<input type=hidden name='election_name' value='" . escape_html($election_name) . "'>";
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
    $str .= "<br>Add a candidate (make sure your candidate does not already appear above!)<br>";
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
    print "' onclick='document.getElementById(\"page_type\").value=\"member_input\"'>";
  }

  if ($full_results && $other_methods) {
?>
<h3>What do other methods say?</h3>
<?=$other_methods?>
</p><hr>
<?php
              }
}

#ob_end_clean();
echo "<h3>List of ballots:</h3>";
echo "<table border>\n";
echo "<tr>\n";
echo "<td>voter id</td>";
foreach ($all_votes as $race => $ballots) {
  if (!isset($all_races[$race]['feedback']) || !$all_races[$race]['feedback']) {
    echo "<td>" . escape_html($all_races[$race]['display_name']) . "</td>";
  }
}
echo "</tr>\n";
foreach ($members as $member_name => $votes) {
  echo "<tr>\n";
  echo "<td>$member_name</td>";
  foreach ($all_votes as $race => $ballots) {
    if (!isset($votes[$race])) {
      $choices = array();
    }
    else {
      $choices = $votes[$race];
    }
    if (isset($all_races[$race]['feedback']) && $all_races[$race]['feedback']) {
      continue;
    }
    echo "<td>";
    if (count($choices)) {
      echo implode("<br>\n",$choices);
    }
    else {
      echo "(abstain)";
    }
    echo "</td>";
  }
  echo "</tr>\n";
}
?>
  </table>
<?php
  if (!$full_results) {
?>
 <a href='<?=escape_html($_SERVER['REQUEST_URI'])?><?=count($_GET)?'&':'?'?>full_results=1&election_name=<?=escape_html(
urlencode($election_name))?>'>View full results</a>
<?php
    }
?>
</body></html>
<?php 
function normalize_ballot(&$ballot,$voter=null) {
  global $choices;
  if (!is_array($ballot) || !count($ballot)) {
    return 0;
  }
  for ($ii = 0; $ii < count($ballot); $ii++) {
    if (!is_array($ballot[$ii])) {
      if (!array_key_exists($ballot[$ii],$choices)) {
        if ($voter && strlen($ballot[$ii])) {
          print "<h4>Unsetting preference $ii, " . escape_html($ballot[$ii]) . 
            ", for " . escape_html($voter) . ", since it is not a candidate.</h4>";
        }
        array_splice($ballot,$ii,1);
        $ii--;
      }
    }
    else {
      for ($jj = 0; $jj < count($ballot[$ii]); $jj++) {
        if (!array_key_exists($ballot[$ii][$jj],$choices)) {
          if ($voter) {
            print "<h4>Unsetting a preference $ii, " . escape_html($ballot[$ii][$jj]) . 
              ", for " . escape_html($voter) . ", since it is not a candidate.</h4>";
          }
          array_splice($ballot[$ii],$jj,1);
          $jj--;
        }
      }
      if (!count($ballot[$ii])) {
        array_splice($ballot,$ii,1);
        $ii--;
      }
    }
  }
  return (count($ballot));
}

function complete_ballots($choices,&$votes) {
  //make every ballot complete
  foreach ($votes as $key => $ballot) {
    $not_done = $choices;
    if (is_array($ballot)) {
      foreach ($ballot as $pref) {
        unset($not_done[$pref]);
      }
    }
    $votes[$key][] = array_keys($not_done);
  }
}

function array_search_recursive($needle,$haystack,$limit_key = false) {
  $key = array_search($needle,$haystack);
  if ($key !== false) {
    return $key;
  }
  $break_flag = false;
  foreach ($haystack as $key => $bale) {
    if ($break_flag) {
      return false;
    }
    if ($key === $limit_key) {
      $break_flag = true;
    }
    if (is_array($bale) &&
        array_search_recursive($needle,$bale) !== false) {
      return $key;
    }
  }
  return false;
}

function compare_two($choice1,$choice2,&$votes) {
  $ctr = 0;
  foreach ($votes as $mem => $ballot) {
    $key1 = array_search_recursive($choice1,$ballot);
    $key2 = array_search_recursive($choice2,$ballot,$key1);
    if ($key2 === false || $key1 < $key2) {
      $ctr++;
    }
    else if ($key1 > $key2) {
      $ctr--;
    }
  }
  //  print_r("$choice1 versus $choice2 gives $ctr<br>\n");
  return $ctr;
}

//find the condorcet winner
function condorcet_choice($opts,&$votes,$loser = null,$new = null) {
  global $choices;
  $choice_keys = array_keys($choices);
  $count = count($choices);
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
    if (!in_array($ii,$opts)) {
      continue;
    }
    if (!isset($data[$ii])) {
      $data[$ii] = array();
    }
    $return = true;
    if ($loser) {
      $scalar = -1;
    }
    else {
      $scalar = 1;
    }
    foreach ($choice_keys as $jj) {
      if (!in_array($jj,$opts)) {
        continue;
      }
      if ($ii == $jj) {
        continue;
      }
      if (!isset($data[$ii][$jj])) {
        if (isset($data[$jj][$ii])) {
          $data[$ii][$jj] = -$data[$jj][$ii];
        }
        else {
          $data[$ii][$jj] = compare_two($ii,$jj,$votes);
        }
      }
      $return &= ($scalar*$data[$ii][$jj] >= 0);
    }
    if ($return) {
      $rets[] = $ii;
    }
  }
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
  if ($new) {
    $done = $borda = false;
  }
  if (!$done) {
    $borda = $choices;
    foreach ($borda as $key => $junk) {
      $borda[$key] = 0;
    }
    $choice_keys = array_keys($borda);
    $count_orig = count($choices);
    foreach ($votes as $voter => $ballot) {
      $count = $count_orig;
      foreach ($ballot as $pref) {
        if (!is_array($pref)) {
          $borda[$pref] += --$count;
          continue;
        }
        $tie_count = count($pref);
        $count -= $tie_count;
        foreach ($pref as $tie) {
          $borda[$tie] += $count + ($tie_count/2);
        }
      }
    }
  }
  $max_num = null;
  $return_choice = null;
  if ($loser) {
    $scalar = -1;
  }
  else {
    $scalar = 1;
  }
  foreach ($borda as $choice => $num) {
    if (!in_array($choice,$choices)) {
      continue;
    }
    if ($max_num === null ||
        $scalar*$num > $scalar*$max_num) {
      $max_num = $num;
      $return_choice = $choice;
      continue;
    }
    if ($max_num !== null &&
        $scalar*$num == $scalar*$max_num) {
      $return_choice = null;
    }
  }
  $done = true;
  return $return_choice;
}

function rand_md5_cmp($a,$b) {
  global $race;
  if ($a === $b) {
    return 0;
  }
  return strcmp(md5($a . $race), md5($b . $race));
}
?>
