<?php
require_once('default.inc.php');
?>
<html><head><title>Administer election</title></head><body>
<?=$body_insert?>
<?php

$show_choices = false;
if (!array_key_exists('election_name',$_REQUEST)) {
  $show_choices = true;
}
else {
  $election_name = $_REQUEST['election_name'];  
  $num = $db->GetRow("select count(*) as ct from `elections_record` " .
		     "where `election_name` = ?",array($election_name));
  if ($num['ct'] == 0) {
    print "<h4>Election \"" . escape_html($election_name)
      . "\" does not exist.  Please choose an election:</h4>";
    $show_choices = true;
  }
}

if ($show_choices) {
  $elections = array();
  $res = $db->Execute('SELECT `election_name` FROM `elections_record` ' .
                      'order by `election_name` desc');
  while ($row = $res->FetchRow()) {
    $elections[] = $row['election_name'];
  }
  if (!count($elections)) {
    exit("No elections to administer!  " .
         "Go <a href='create_election.php'>create one</a>.");
  }
    ?>
<form method='GET' action='<?=this_url()?>'>
   <?php 
   $ii = 0;
   foreach ($elections as $election) {
     $election = escape_html($election);
     ?>
     <input type=radio name='election_name' value='<?=$election?>'
        id='<?=$ii?>' <?=($ii==0)?'checked':''?>><label for='<?=$ii++?>'><?=$election?></label><br/>
     <?php 
   }
  ?>
<input type='submit' value='Submit'>
</form></body></html>
    <?php 
   exit; 
}

if (array_key_exists('finalize_election',$_REQUEST)) {
  $row = $db->GetRow("select count(*) as ct from `elections_record` " .
                     "where `election_name` = ? and " .
                     "`end_date` <= unix_timestamp() ",
                     array($election_name));
  if ($row['ct'] != 1) {
    exit("This election cannot be finalized.");
  }
  $db->Execute("UPDATE elections_record SET anon_voting = 2+`anon_voting` WHERE " .
               "election_name = ?",array($election_name));
    ?>
All done! <a href='../election_results.php?election_name=<?=
escape_html(rawurlencode($_REQUEST['election_name']))?>'
>election_results.php</a> has the results.
</body></html>
<?php
  exit;
}
if (array_key_exists('count_voter',$_REQUEST) &&
    $_REQUEST['count_voter']) {
  $row = $db->GetRow("select count(*) as ct from `voting_record` where " .
    "`member_name` = ? and `election_name` = ? " .
    "and `manual_entry` != -1",
                     array($_REQUEST['voter_name'],
                           $election_name));
  if ($row['ct']) {
    exit("Error!  This person is listed as already having voted</body></html>");
  }
  $db->Execute("update voting_record set `manual_entry` = 1 where " .
    "`election_name` = ? and `member_name` = ?",
               array($election_name,$member_name)); 
  print "Recorded voter " . escape_html($_REQUEST['voter_name']);
  exit("</body></html>");
}

if (array_key_exists('add_eligible_voters',$_REQUEST)) {
  $add_voters = $_REQUEST['add_voter_name'];
  foreach ($add_voters as $person) {
    $db->Execute("insert ignore into `voting_record` " .
      "(`member_name`,`election_name`,`manual_entry`) values (?,?,?)",
        array($person,$election_name,-1));
    print "Added " . escape_html($person) . " to the list of eligible " .
      "voters for election " . escape_html($election_name) . "<br/>\n";
  }
}

if (array_key_exists('delete_election',$_REQUEST)) {
  $witnesses = require_witnesses(2);
  $tables_array = array('votes','voting_record','elections_record','elections_attribs',
                        'elections_log');
  //don't want election half-deleted
  $db->StartTrans();
  //no one else should be able to write to these tables until we're
  //done.  See create_election.php for the half-fudge that's hidden here.
  $db->Execute("lock tables `modified_dates` write, `" . 
               join('` write, `',$tables_array) . "` write");
  foreach ($tables_array as $table) {
    $db->Execute("delete from `$table` where `election_name` = ?",
                 array($election_name));
  }
  elections_log($election_name,null,'election_deleted',null,join("; ",$witnesses));
  $db->CompleteTrans();
  $db->Execute("unlock tables");
  exit("Deleted!</body></html>");  
}

$res = $db->Execute("select `member_name`, `manual_entry` from voting_record " .
  "where election_name = ? and `manual_entry` != -1 " .
  "order by `member_name`",
                    array($election_name));
echo "<h4>List of voters for $election_name:</h4>";
?>
<table border=1>
<tr><td>Member name</td><td>Manually entered?</td></tr>
<?php
$ii = 0;
$houselist = array_flip(get_houselist());
while ($row = $res->FetchRow()) {
?>
  <tr><td><?=escape_html($row['member_name'])?></td>
    <td><?=$row['manual_entry']?'yes':'no'?></td></tr>
<?php
    unset($houselist[$row['member_name'] ]);
    $ii++;
}
?>
</table>
(<?=$ii?> voters so far)<p>
<hr>
<h4>Email addresses and room numbers of members who haven't voted:</h4>
<?php
#';
$res = $db->Execute("select `member_name`, `room`, `email` " .
                    "from `house_info` order by `member_name`");
while ($row = $res->FetchRow()) {
  if (array_key_exists($row['member_name'],$houselist)) {
    $member_name = $row['member_name'];
    $names = explode(', ',$member_name);
    if (count($names) > 1) {
      $member_name = $names[1] . ' ' . $names[0];
      if (count($names) > 2) {
        $member_name = join(', ',array_slice($names,2));
      }
    }
    print escape_html($member_name) . " (" . escape_html($row['room']) . ") &lt;" .
      escape_html($row['email']) . "&gt;,<br>\n";
  }
}
$elect_row = $db->GetRow("select * from `elections_record` where " .
                         "`election_name` = ?",
                         array($election_name));
if ($elect_row['anon_voting']%2) {
?>
<hr>
<h4>Enter a member who voted manually (so they can't vote online as well):</h4>
<form action='<?=this_url()?>' method='post'>
<select name='voter_name'>
<option>
<?php
foreach (array_keys($houselist) as $person) {
print "<option>" . escape_html($person) . "\n";
}
?>
</select><br/>
<input type=hidden name='count_voter' value=1><br>
<input type=hidden name='election_name' value='<?=escape_html($election_name)?>'>
<input type=submit value='Record voter'>
</form><p>
<?php 
}
?>
<hr>
<?php
$res = $db->Execute("select `member_name` from `voting_record` " .
  "where `election_name` = ?",array($election_name));
$missing_list = $houselist;
while ($row = $res->FetchRow()) {
  unset($missing_list[$row['member_name']]);
}
if (count($missing_list)) {
?>
Make the following new members eligible to vote in this election
(they will also become eligible if you modify the election through
the link below).<br/>
<form action=<?=this_url()?> method='post'>
<input type='hidden' name='election_name'
  value='<?=escape_html($election_name)?>'>
<input type='hidden' name='add_eligible_voters'>
<select name='add_voter_name[]' multiple>
<?php
  foreach (array_keys($missing_list) as $person) {
    print "<option>" . escape_html($person) . "\n";
  }
?>
</select><input type='submit' value='Submit'/>
<hr/>
<?php }
?>
<a href='create_election.php?modify_election&election_name=<?=
                                    escape_html(rawurlencode($election_name))?>'>
Modify election</a><p>
<?php   $row = $db->GetRow("select `anon_voting` from elections_record " .
                     "where election_name = ? and end_date <= unix_timestamp()",
array($election_name));
  if (is_empty($row)) {
    exit("</body></html>");
  }
if ($row['anon_voting'] < 2) {
?>
<a href='../voting.php?enter-ballot&election_name=<?=
escape_html(rawurlencode($_REQUEST['election_name']))?>'
>Enter a paper ballot</a><p>
If all votes have been input and you're ready to make the results
public, <form action='<?=this_url()?>' method='post'>
<input type=hidden name='election_name' value='<?=escape_html($_REQUEST['election_name'])?>'>
<input type=hidden name='finalize_election' value=1>
<input type=submit value='Finalize the election'>
</form>
<?php
                                    }
else {
?>
<a name='delete'>
<form action=<?=this_url()?>' method='get'>
<input type=hidden name='election_name' value='<?=escape_html($_REQUEST['election_name'])?>'>
<input type=checkbox name='delete_election'>Delete election?<br>
<input type=submit value='Delete election'></form>
<?php
}
?>
</body></html>

