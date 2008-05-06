<?php
//Allows president to reorder races
//don't print body_insert immediately
$body_insert = '';
require_once('default.inc.php');
?>
<html><head><title>Reorder races</title></head><body>
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
    exit("No elections!  " .
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
$election_name = $_REQUEST['election_name'];

if (!isset($_REQUEST['submitting'])) {
?>
The current order of races is given below.  Renumber the races and submit to change
  the order.
<form action='<?=this_url()?>' method='post'>
<input type=hidden name='submitting' value=1>
<table>
<thead>
<tr><th>Rank</th><th>Original Rank</th><th>Race Name</th></tr>
</thead><tbody>
<?php
$res = $db->Execute('select `race_name`,`attrib_value` ' .
                    'from `elections_attribs` where `election_name` = ? ' .
                    'and `attrib_name` = "race_name" order by (`attrib_value`+0)',
                    array($election_name));
$ii = 1;
while ($row = $res->FetchRow()) {
  $id = $row['attrib_value'];
?>
<tr><td><input name='J<?=$id?>' value='<?=$ii?>' size=3></td><td><?=$ii?></td><td>
<?=$row['race_name']?></td></tr>
<?php
   $ii++;
   }
?>
</tbody></table>
<input type=submit value="Change order">
</form>

<?php
    exit;
    }

$db->StartTrans();
$db->Execute("lock tables `elections_attribs` write");

$res = $db->Execute('select `race_name`, `attrib_value` ' .
                    'from `elections_attribs` where `election_name` = ? ' .
                    'and `attrib_name` = "race_name" order by (`attrib_value`+0)',
                    array($election_name));
$races = array();
while ($row = $res->FetchRow()) {
  $races[$row['race_name']] = $row['attrib_value'];
}
$done_order = array();
foreach ($races as $race_name => $attrib_value) {
  $ind = $_REQUEST['J' . $attrib_value];
  if (isset($done_order[$ind])) {
    exit("Error!  You put two races in spot " . $ind . ".  Please go back " .
         "and fix it.");
  }
  $done_order[$ind] = 1;
  $db->Execute('update `elections_attribs` set `attrib_value` = ? where ' .
               '`election_name` = ? and `attrib_name` = "race_name" and ' .
               '`race_name` = ?',
               array($ind,$election_name,$race_name));
}
$db->CompleteTrans();
$db->Execute("unlock tables");

exit("All done!");
