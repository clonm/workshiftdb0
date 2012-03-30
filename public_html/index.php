<?php
$php_start_time = array_sum(split(' ',microtime()));
$require_user = 'ok_nouser';
require_once('default.inc.php');
?>
<html><head><title>Front page</title>
<style>
TD.first { vertical-align: top; padding-right: 3em;}
TH { vertical-align: top; text-align: left; padding-right: 3em}
</style>
</head><body>
<table border=0><tr><td style='min-width: 550px; width: auto !important;' class="first"><h3>First Things First</h3>
Fill out your <a href="preferences.php">preference form</a>.<p>
<a href="person.php">Your personal page</a><p>
<hr>
<table style='border: 0px solid;'><thead><tr><th>Workshift links</th><th>Online Voting Links</th><th>Other Links</th></tr></thead>
<tbody><tr><td class="first">
<?php
if (get_static('online_signoff',null)) {
  print "<a href='online_signoff.php'>Sign off of a shift online</a>
<p>\n";
}
?>
<a href="weekly_totals.php">The weekly totals</a><p>
<a href="public_utils/shifts_by_name.php">Workshifts by name</a><p>
<a href="shift_descriptions.php">Descriptions of workshifts</a><p>
<a href="workshift_doc.php">Workshift policy document</a><p>
</td><td class="first">
<a href="elections/voting.php">Vote in elections</a></p><p>
<a href="elections/election_results.php">Election results</a><p>
</td><td class="first">
<a href="directory.php">House directory</a></p><p>
</td></tr></tbody></table>
<hr>
<?php
  if (get_session_member()) {
$week_num = get_cur_week();
if ($week_num < 0) {
  exit("System not yet set up</body></html>");
}
print "In week $week_num now";
if ($week_num > 0) {
  print ", displaying data through week " . ($week_num-1);
}
print "<br>\n";
$sql_begin = "select `weekly_totals_data`.`member_name`";
$sql_mid = " from `weekly_totals_data`";
$sql_end = ' where ';
create_and_update_weekly_totals_data();

if ($week_num) {
#$db->debug = true;
for ($ii = 0; $ii < $week_num; $ii++) {
  create_and_update_week_totals($ii);
  $sql_begin .= ",`week_{$ii}_totals`.`tot`-`weekly_totals_data`.`owed $ii` as `$ii`";
  $sql_mid .= ",`week_{$ii}_totals`";
  if ($ii > 0) {
    $sql_end .= " and ";
  }
  $sql_end .= "`weekly_totals_data`.`member_name` = `week_{$ii}_totals`.`member_name`";
}
?>
<?php
$tot_weeks = get_static('tot_weeks',18);
//need to +1 because there are $tot_weeks+1 weeks -- week 0 too
$backup_zero_hours = array_fill(0,$tot_weeks+1,$weekly_zero = get_static('fining_zero_hours',false));
$backup_max_up_hours = array_fill(0,$tot_weeks+1,$weekly_max = get_static('max_up_hours',null));
$fining_res = $db->Execute("select `week`, `fining_floor`, `fining_buffer`, `zero_hours` " .
                           "from `fining_periods` order by `week`");
$max_up_hours_fining = get_static('max_up_hours_fining',null);
$backup_fining_periods = array();
while ($row = $fining_res->FetchRow()) {
  $backup_zero_hours[$row['week']] = $row['zero_hours'];
  if (strlen($max_up_hours_fining)) {
    $backup_max_up_hours[$row['week']] = $max_up_hours_fining;
  }
  $backup_fining_periods[$row['week']] = array($row['fining_floor'],$row['fining_buffer']);
}

$fining_comb = array(get_static('fining_floor',0),get_static('fining_buffer',0));
$zero_partial = get_static('fining_zero_partial',null);

$special_res = $db->Execute("select * from `special_fining`");
$pmap = array();
while ($special_row = $special_res->FetchRow()) {
  $pmap[$special_row['member_name']] = $special_row;
}
$res = $db->Execute($sql_begin . $sql_mid . $sql_end . " order by `member_name`");
}
else {
  $res = $db->Execute("select * from `house_list`");
}
$ii = 0;
print "<table cellspacing='10px'>";
while ($row = $res->FetchRow()) {
  if (!($ii++ % 4)) {
    if ($ii > 0) {
      echo "</tr>";
    }
    echo "<tr>";
  }
  echo "<td><a href='person.php?member_name=" . escape_html($row['member_name']) . "'>" .
    escape_html($row['member_name']);
  if (!$week_num) {
    print "</a></td>";
    continue;
  }
  print "<br><span style='color:";
  $total = 0;
  $max_up_hours = $backup_max_up_hours;
  $zero_hours = $backup_zero_hours;
  $fining_periods = $backup_fining_periods;
  if (isset($pmap[$row['member_name']])) {
    $fine_weeks = array_keys($fining_periods);
    $new_fine_weeks = array();
    for ($kk = 1; $kk <= count($fine_weeks); $kk++) {
      $new_week = $pmap[$row['member_name']]["fine_week_$kk"];
      if ($new_week != -1 && $new_week != ($old_week = $fine_weeks[$kk-1]) &&
          $new_week < $week_num) {
        if (strlen($new_week)) {
          $new_fine_weeks[$new_week] = 1;
          $zero_hours[$new_week] = $backup_zero_hours[$old_week];
          $max_hours[$new_week] = $backup_max_up_hours[$old_week];
          $fining_periods[$new_week] = $backup_fining_periods[$old_week];
        }
        if (!isset($new_fine_weeks[$old_week])) {
          $zero_hours[$old_week] = $weekly_zero;
          $max_hours[$old_week] = $weekly_max;
          unset($fining_periods[$old_week]);
        }
      }
    }
  }
  for ($jj = 0; $jj < $week_num; $jj++) {
    $total += $row[$jj];
    if ($max_up_hours[$jj] && $total > $max_up_hours[$jj]) {
      $total = $max_up_hours[$jj];
    }
    else if (($jj < $week_num-1) && $zero_hours[$jj] && $total < 0) {
      if (isset($fining_periods[$jj])) {
        $fin_data = $fining_periods[$jj];
      }
      else {
        $fin_data = $fining_comb;
      }
      $fin = array_sum($fin_data);
      if ($total < -$fin) {
        if (strlen($zero_partial)) {
          $total = -$fin_data[0] + ($total+$fin_data[0])*$zero_partial/100;
        }
        else {
          $total = -$fin_data[0];
        }
      }
    }
  }
  echo ($total < 0?"red'>Down ":"green'>Up ") . escape_html(abs($total)) . " hours</a></td>";
}
?>
</tr></table>
<?php 
  } 
  else {
    print "<h3>Log in to see member up/down hours</h3>";
    if (isset($_REQUEST['passwd'])) {
        print "You entered an incorrect password. Please try again.<br/>\n";
      }
require_once($php_includes . "/member_check.php");  }?>
<p id="phptime" style='font-size: 10px'>
PHP generated this page in 
<?=escape_html(round(array_sum(split(' ',microtime()))-$php_start_time,2))?>
 seconds.
</td><td valign=top>
<center><a href="http://www.bsc.coop/"><img src="http://www.bsc.coop/docs/GMM-Expansion.jpg" width=380 height=380 alt="general members meeting on expansion"></a></center>
<br>
<script src="http://widgets.twimg.com/j/2/widget.js"></script>
<script>
new TWTR.Widget({
  version: 2,
  type: 'profile',
  rpp: 20,
  interval: 6000,
      width: 380,
  height: 600,
  theme: {
    shell: {
      background: '#ffffff',
      color: '#ffffff'
    },
    tweets: {
      background: '#ffffff',
      color: '#000000',
      links: '#8d9fb3'
    }
  },
  features: {
    scrollbar: false,
    loop: false,
    live: false,
    hashtags: false,
    timestamp: false,
    avatars: false,
    behavior: 'all'
  }
}).render().setUser('bscannounce').start();
</script></td></tr></table></body>
</html>
