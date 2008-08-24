<?php
//this is because we might be outputting a binary file, if the user
//wanted details of that.
ob_start();
require_once('default.inc.php');
?>
<html><head><title>Elections Log</title></head><body>
<?php
if (isset($_REQUEST['details'])) {
  $row = $db->GetRow("select `autoid`, " .
                     "unix_timestamp(`time_entered`) as `time_entered`, " .
                     "`election_name`,`subj_name`,`attrib`,`oldval`,`val` " .
                     "from `elections_log` where `autoid` = ?",
                     array($_REQUEST['details']));
  if (is_empty($row)) {
    exit;
  }
  $row['time_entered'] = user_time($row['time_entered'],'r');
  print escape_html($row['time_entered']) . "<br/>";
  if (!strlen($row['election_name'])) {
    switch ($row['subj_name']) {
    case 'restore_database':
      //output as normal, no binary file
      ob_end_flush();
      $backup_names = unserialize($row['oldval']);
      $backup_witnesses = unserialize($row['val']);
      if (!$backup_witnesses) {
        $backup_witnesses = array();
      }
        ?>
<dl>
   <dt>Person who did restore:
  <dd><?=escape_html($row['attrib'])?>
   <dt>Backup that was restored:
  <dd><a
href='<?=escape_html($baseurl)?>/admin/index.php?archive=<?=
escape_html($archive_pre . $backup_names[0] . "_")?>'
   ><?=escape_html($backup_names[0])?></a>
   <dt>Name that database was backed up to:
  <dd><a
href='<?=escape_html($baseurl)?>/admin/index.php?archive=<?=
escape_html($archive_pre . $backup_names[0] . "_")?>'
   ><?=escape_html($backup_names[1])?></a>
   <dt>Witnesses:
  <dd><?=join("<br/>",array_map('escape_html',$backup_witnesses))?>
</dl>
<?php
   break;
    }
  }
  switch ($row['attrib']) {
  case 'manual_entry':
    //output as normal, no binary file
    ob_end_flush();
    $arr = unserialize($row['val']);
    foreach ($arr as $race => $vals) {
      print "<h3>" . escape_html($race) . "</h3>\n";
      if (is_array($vals)) {
        print join("; ",$vals);
      }
      print str_replace("\n","<br/>\n",$vals);
      print "<p>\n";
    }
    exit;
  case 'descript':
    //output as normal, no binary file
    ob_end_flush();
    print "<h3>Election: " . escape_html($row['election_name']) . "</h3>\n";
    print "The election description was changed: " .
      "<table border style='empty-cells: show; " . white_space_css() . "'>" .
      "<tr><th></th><th>Old</th><th>New</th></tr>\n" .
      "<tr><td>Source:</td><td>" . escape_html($row['oldval']) . 
      "</td><td>" . escape_html($row['val']) . "</td></tr>\n" .
      "<tr><td>As html:</td><td>" . $row['oldval'] . "</td><td>" .
      $row['val'] . "</td></tr></table></body></html>";
    exit;
  case 'race_descript':
    //output as normal, no binary file
    ob_end_flush();
    print "<h3>Election: " . escape_html($row['election_name']) . "</h3>\n";
    print "<h4>Race: " . escape_html($row['subj_name']). "</h4>\n";
    print "The race description was changed: " .
      "<table border style='empty-cells: show; " . white_space_css() . "'>" .
      "<tr><th></th><th>Old</th><th>New</th></tr>\n" .
      "<tr><td>Source:</td><td>" . escape_html($row['oldval']) . 
      "</td><td>" . escape_html($row['val']) . "</td></tr>\n" .
      "<tr><td>As html:</td><td>" . $row['oldval'] . "</td><td>" .
      $row['val'] . "</td></tr></table></body></html>";
    exit;
  case 'descript_file':
    //maybe a binary file to be output, we might clean() output so far
    if (array_key_exists('oldval',$_REQUEST)) {
      $file_contents = $row['oldval'];
    }
    else {
      $file_contents = $row['val'];
    }
    if (!strlen($file_contents)) {
      ob_end_flush();
      print "File was empty.</body></html>";
      exit;
    }
    ob_end_clean();
    $props = array('descript_filename' => null,'descript_filetype' => null);
    $file_info_res = $db->Execute("select `attrib`, `" .
                                  (array_key_exists('oldval',$_REQUEST)?'oldval':'val') .
                                  "` as `val` from `elections_log` " .
                                  "where `autoid` < ? order by `autoid` desc limit 2",
                                  array($row['autoid']));
    while ($info_row = $file_info_res->FetchRow()) {
      if (array_key_exists($info_row['attrib'],$props)) {
        $props[$info_row['attrib']] = $info_row['val'];
      }
    }
    $sql_arr = array($row['autoid']);
    foreach ($props as $prop => $value) {
      if (!$value) {
        $sql_arr[] = $prop;
      }
    }
    if (count($sql_arr) > 1) {
      $file_info_res = $db->Execute("select `attrib`, `oldval` from `elections_log` " .
                                    "where `autoid` > ? and (`attrib` = ?" .
                                    (count($sql_arr) > 2?' or `attrib` = ?':'') .
                                    ") order by `autoid` asc",
                                    $sql_arr);
      while (($info_row = $file_info_res->FetchRow()) &&
             array_sum($props) === null) {
        if (array_key_exists($info_row['attrib'],$props) &&
            !isset($props[$info_row['attrib']])) {
          $props[$info_row['attrib']] = $info_row['val'];
        }
      }
      foreach ($props as $prop => $value) {
        $props[$prop] =  $db->GetRow("select `attrib_value` from " .
                                     "`elections_attribs` where " .
                                     "`election_name` = ? and `race_name` = ? " .
                                     "and `attrib_name` = ?",
                                     array($row['election_name'],$row['subj_name'],
                                           $prop));
        if (!is_empty($props[$prop])) {
          $props[$prop] = $props[$prop]['attrib_value'];
        }
      }
    }
    if (isset($props['descript_filetype'])) {
      header('Content-type: ' . $props['descript_filetype']);
    }
    if (isset($props['descript_filetype'])) {
      header('Content-Disposition: attachment; filename="' . $props['descript_filename'] . '"'); 
    }
    else {
      header('Content-Disposition: attachment;');
    }
    print $file_contents;
    exit;
  case 'end_president_modif':
    function runoff_format($val) {
      if ($val === null) {
        return "(not set)";
      }
      switch ($val) {
      case 0:
        return "nothing special";
      case 1:
        return "instant runoff";
      default:
        return "unranked preferences (up to " . escape_html($val) . ")";
      }
    }
    function threshold_format($val) {
      if (!$val) {
        return "(none)";
      }
      if ($val > 0) {
        return escape_html($val) . "%";
      }
      return escape_html(-$val) . " votes";
    }

    $finish_autoid = $row['autoid'];
    $start_autoid = $db->GetRow("select `autoid` from `elections_log` where " .
                                "`autoid` < ? and `attrib` = ? order by `autoid` desc limit 1",
                                array($finish_autoid,'start_president_modif'));
    $res = $db->Execute("select `autoid`, " .
                        "unix_timestamp(`time_entered`) as `time_entered`, " .
                        "`election_name`, `subj_name`, `attrib`," .
                        "`oldval`,`val` from `elections_log` where " .
                        "`autoid` < ? and `autoid` > ? order by `autoid` asc",
                        array($finish_autoid,$start_autoid['autoid']));
    print "<h3>President modified election " . escape_html($row['election_name'])
      . "</h3>\n";
    //    print "<div style='" . white_space_css() . "'>";
    $cur_race = false;
    $feedback = false;
    while ($row = $res->FetchRow()) {
      $row['time_entered'] = user_time($row['time_entered'],'r');
      if ($row['subj_name'] !== $cur_race) {
        if ($cur_race !== false) {
          print "</ul><hr>\n";
        }
        $cur_race = $row['subj_name'];
        $feedback = false;
        if ($cur_race) {
          print "<h4>" . escape_html($cur_race) . "</h4>";
        }
        print "<ul>\n";
      }
      else if ($feedback && 
               $row['attrib'] != 'member_comments' &&
               $row['attrib'] != 'feedback') {
        continue;
      }
      switch ($row['attrib']) {
      case 'rename_race':
        print "<li>The race name was changed from '" . 
          escape_html($row['oldval']) . "' to '" . escape_html($row['val']) . 
          "'.  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'delete_race':
        print "<li><strong>The race was deleted.</strong>" .
          "  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'descript':
        print "<li>The election description was changed.  " .
          "<a href='elections_log.php?details=" . 
          escape_html($row['autoid']) . 
          "'>Details</a>" .
          ".  (" . escape_html($row['time_entered']) . ")</li>\n";

        break;
      case 'descript_html':
        print "<li>The election description was changed to print out ";
          if ($row['oldval']) {
            print "as plain text, not html";
          }
          else {
            print "as html, not plain text";
          }
          print ".  (" . escape_html($row['time_entered']) . ")</li>\n";
          break;
      case 'interim_results':
        print "<li>Results were changed to ";
        if ($row['oldval']) {
          print "not ";
        }
        print "be viewable before the election was over" .
          ". (" . escape_html($row['time_entered']) . ")</li>\n";

        break;
      case 'descript_filename':
      case 'descript_filetype':
        break;
      case 'descript_file':
        if ($row['val']) {
          print "<li>A file was uploaded for voters to see.  ";
        }
        else {
          print "<li>A file was removed from the voting page.  ";
        }
        if ($row['oldval']) {
          print "<a href='elections_log.php?details=" .
            escape_html($row['autoid']) . "&oldval'>Old File Contents</a>.  ";
            }
        if ($row['val']) {
          print "<a href='elections_log.php?details=" . 
            escape_html($row['autoid']) . "'>File Contents</a>.";
        }
        print " (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'race_name':
        print "<li>The race was created" .
          ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'race_descript':
        if (strlen($row['oldval']) || strlen($row['val'])) {
          print "<li>The race description was changed.  " .
            "<a href='elections_log.php?details=" . 
            escape_html($row['autoid']) . 
            "'>Details.</a>" .
            ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        }
        break;
      case 'feedback':
        print "<li><strong>The race was changed to " .
          ($row['oldval']?'not ':'just ') . 
          "be a feedback race.</strong>" .
          ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        $feedback = $row['val'];
        break;
      case 'candidates':
        print "<li><strong>";
        if ($row['oldval']) {
          print "The candidates were changed.  The old candidates were:<br/>\n'" .
            join("', '",array_map('escape_html',
                                  explode("\n",$row['oldval']))) . "'<br/>\n";
        }
        print "The new candidates are:<br/>\n'" .
            join("', '",array_map('escape_html',
                                  explode("\n",$row['val']))) . "'</strong>" .
          ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'num':
        print "<li><strong>The number of spots available was changed from " .
          escape_html($row['oldval']!== null?$row['oldval']:'(not set)') . " to " . 
          escape_html($row['val']) . "</strong>" .
          ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'runoff':
        print "<li><strong>The type of race was changed from " .
        runoff_format($row['oldval']) . " to " . runoff_format($row['val']) .
          "</strong>.  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'threshold':
        print "<li><strong>The threshold winners needed was changed from " .
          threshold_format($row['oldval']) . " to " . threshold_format($row['val']) .
          "</strong>. (" . escape_html($row['time_entered']) . ")</li>\n";

        break;
      case 'num_voters':
        print "<li><strong>The minimum number of voters needed to make this " .
          "a valid election was changed from " . 
          escape_html($row['oldval'] !== null?$row['oldval']:'(not set)') .
          " to " . escape_html($row['val'] !== null?$row['val']:'(not set)') .
          "</strong>. (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'abstain_count':
        print "<li>Abstentions ";
        if ($row['val']) {
          print "now ";
        }
        else {
          print "no longer ";
        }
        print "count towards any threshold. (" . 
          escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'def_val':
        print "<li>If any threshold is not met, ";
        if (strlen($row['val'])) {
          print "the winner will be " . escape_html($row['val']);
        }
        else {
          print "there will be no winner";
        }
        if (strlen($row['oldval']) && 
            $row['oldval'] != $row['val']) {
          print " which is changed from " . escape_html($row['oldval']);
        }
        print ". (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'member_add':
        print "<li>Members can ";
        if ($row['val']) {
          print "now ";
        }
        else {
          print "not ";
        }
        print "add their own candidates as they vote" .
          ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        break;
      case 'member_comments':
        print "<li>";
        if ($row['oldval'] !== null && $row['val'] !== null) {
          print "<strong>The member comments were edited by the president</strong>" .
            ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        }
        else {
          print "Members can ";
          if ($row['val'] !== null) {
            print "now ";
          }
          else {
            print "not ";
          }
          print "comment on this race as they vote" .
            ".  (" . escape_html($row['time_entered']) . ")</li>\n";
        }
        break;
      default:
        print "<li><strong>" . escape_html($row['attrib']) . " was changed " .
          "from " . escape_html($row['oldval']) . " to " . 
          escape_html($row['val']) . ".</strong>" .
          "  (" . escape_html($row['time_entered']) . ")</li>\n";

        break;
      }
    }
    exit;
  default:
    print "<table border style='empty-cells: show'><tr>";
    foreach (array_keys($row) as $col) {
      print "<th>" . escape_html($col) . "</th>";
    }
    print "</tr><tr>";
    foreach ($row as $entry) {
      print "<td>" . escape_html($entry) . "</td>";
    }
    print "</tr></table>";
    exit;
  }
}
//style is so that links to go back and forward look nice
?>
<style>
th.linknext {
  text-align: left;
 border: 0px;
}

th.linkprev {
  text-align: right;
 border: 0px;
}
</style>

<form action=<?=this_url()?> method=get>
(Leave end blank to go to present)
Start date (YYYY-MM-DD format): <input name='start_date' size=10>.&nbsp;&nbsp;
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;End date: <input name='end_date' size=10><br/>
<input type=submit name='get_dates' value='Get log'>
</form>
<?php
if (array_key_exists('get_dates',$_REQUEST)) {
  if (!$_REQUEST['start_date']) {
    $_REQUEST['start_date'] = date('Y-m-d',0);
  }
  if (!$_REQUEST['end_date']) {
    $_REQUEST['end_date'] = date('Y-m-d H:i:s');
  }
}
$arr = array();
$where_exp = '';
if (isset($_REQUEST['start_num'])) {
  $arr[] = $_REQUEST['start_num'];
  unset($_REQUEST['start_date']);
  $where_exp .= " `autoid` <= ?";
}
$ending = false;
if (isset($_REQUEST['end_num'])) {
  $arr[] = $_REQUEST['end_num'];
  unset($_REQUEST['end_date']);
  $where_exp .= " and `autoid` <= ?";
  $ending = true;
}
if (isset($_REQUEST['start_date'])) {
  $arr[] = $_REQUEST['start_date'];
  $where_exp .= " and `time_entered` >= ?";
  $ending = true;
}
if (isset($_REQUEST['end_date'])) {
  $arr[] = $_REQUEST['end_date'];
  $where_exp .= " and `time_entered` <= ?";
}
if (strlen($where_exp)) {
  if (substr($where_exp,0,5) == ' and ') {
    $where_exp = substr($where_exp,5);
  }
  $where_exp = "where" . $where_exp;
}
if (!$ending) {
  //overkill on ending expression, because modifications are lumped together
  $ending_exp = " limit 10000";
  $max_print_rows = 100;
}
else {
  $ending_exp = '';
  $max_print_rows = null;
}

$res = $db->Execute("select `autoid`, " .
                    "unix_timestamp(`time_entered`) as `time_entered`, " .
                    "`election_name`,`subj_name`,`attrib`,`oldval`,`val` " . 
                    "from `elections_log` $where_exp " .
                    "order by `autoid` desc $ending_exp",
                    $arr);
//for ob_start that started up there
ob_end_flush();
$page_name = $_SERVER['REQUEST_URI'];
$page_name = escape_html(substr($page_name,0,strpos($page_name,'?')));
//we need to know whether or not to put links at the top and bottom of page
$first_last_row = $db->GetRow("select max(`autoid`) as `first`, " .
                          "min(`autoid`) as `last` from `elections_log`");
//since the link at the top won't be determined until we finish, we
//need to buffer
$last_autoid = 0;
$first_autoid = null;
?>
<table border=1>
<thead>
<tr><th class='linknext' colspan=2>
<?php
ob_start();
?>
<thead><tr><th>Time</th><th>Election</th><th>Activity</th>
<th>Details</th></tr>
</thead>
<tbody>
<?php
$num_rows_printed = 0;
while ($row = $res->FetchRow()) {
  if ($max_print_rows && $num_rows_printed++ >= $max_print_rows) {
    break;
  }
  if ($first_autoid === null) {
    $first_autoid = $row['autoid'];
  }
  $last_autoid = $row['autoid'];
  echo "<tr>";
  print "<td>" . escape_html(user_time($row['time_entered'],'n/j/y')) . "</td>";
  print "<td>";
  if ($row['election_name']) {
    $election_name = $row['election_name'];
    print "<a href='election_results.php?election_name=" . escape_html($election_name) .
      "'>" . escape_html($election_name) . "</a>";
  }
  else {
    print "(N/A)";
  }
  print "</td><td><a href='elections_log_help.html#";
  if ($row['election_name']) {
    if (!$row['subj_name']) {
      switch ($row['attrib']) {
      case 'end_president_modif':
        print "president_modif'>" .
          "President modified election</a></td><td>" .
          "<a href='$page_name?details=" . $row['autoid'] . "'>Details</a>";
        while ($row = $res->FetchRow()) {
          if ($row['attrib'] == 'start_president_modif') {
            break;
          }
        }
        break;
      case 'election_deleted':
        print "delete_election'>President deleted election</a></td><td>" .
          "The deletion was witnessed and approved by " . escape_html($row['val']);
        break;
      case 'manual_entry':
        print "manual_entry'>" .
          "The president entered a paper ballot for the election</a></td><td>" .
          "The ballot's entry was witnessed by: " . 
          escape_html($row['oldval']) . ".  " .
          "<a href='$page_name?details=" . $row['autoid'] . 
          "'>Contents of ballot</a>";
        break;
      case 'voter_no_email':
        print "voter_no_email'>" .
          "A voter voted without an email address on file</a></td><td>" .
          escape_html($row['val']);
        break;
      case 'voter_request_no_email':
	print "voter_request_no_email'>" .
	  "A voter requested that their vote not be emailed to them</a></td><td>"
	  . escape_html($row['val']);
	break;
      default:
        print "unknown'>The action " . escape_html($row['attrib']) . 
          " was taken</td><td><a href='$page_name?details=" . $row['autoid'] . 
          "'>Details</a>";

      }
    }
    else {
      print "corrupt_change'>This change was not logged properly.</a></td><td>" .
        "<a href='$page_name?details=" . $row['autoid'] . "'>Details</a>";
    }
  }
  else {
    switch ($row['subj_name']) {
    case 'workshift change':
      switch ($row['attrib']) {
      case 'reset password':
        print "workshift_reset_password'>" .
          "The workshift manager reset a user's password</a></td><td>" .
          escape_html($row['val']);
        break;
      case 'rename member':
        print "workshift_rename_member'>" .
          "The workshift manager renamed a member</a></td><td>" .
          escape_html($row['oldval']) . " became " . escape_html($row['val']);
        break;
      case 'delete member':
        print "workshift_delete_member'>" .
          "The workshift manager deleted member(s)</a></td><td>" .
          escape_html($row['val']);
        break;
      case 'add member':
        print "workshift_add_member'>" .
          "The workshift manager added member(s)</a></td><td>" .
          escape_html($row['val']);
        break;
      }
      break;
    case 'member email change':
      print "member_email_change'>" .
        escape_html($row['attrib']) . " changed their email</a></td><td>" .
        escape_html($row['oldval']) . " became " . escape_html($row['val']);
      break;
    case 'member change':
      switch ($row['attrib']) {
      case 'password set':
        print "member_password_set'>" .
          escape_html($row['val']) . " set their password</a></td><td>&nbsp;";
        break;
      case 'password change':
        print "member_password_set'>" .
          escape_html($row['val']) . " changed their password</a></td><td>&nbsp;";
        break;
      }
      break;
    case 'change_privilege':
      //member had a privilege added or removed, like president, house, or nonvoter
      //since nonvoter is different, displayed separately
      $oldval = unserialize($row['oldval']);
      if (isset($oldval['authority_figure'])) {
        $authority_figure = escape_html($oldval['authority_figure']);
        unset($oldval['authority_figure']);
      }
      else {
        $authority_figure = null;
      }
      $val = $row['val'];
      $mem = escape_html($row['attrib']);
      if ($val == 'nonvoter') {
        print "nonvoter_privilege'>$mem was made a ";
        if (!in_array($val,$oldval)) {
          print 'nonvoter';
        }
        else {
          print 'voter';
        }
        print "</a></td><td>";
      }
      else {
        switch ($val) {
        case 'president': case 'workshift': case 'house':
          print $val;
          break;
        default:
          print "unknown";
          break;
        }
        print "_privilege'>$mem was ";
        if (!in_array($val,$oldval)) {
          print "given";
        }
        else {
          print "stripped of";
        }
        print " " . escape_html($val) . " powers</a></td><td>";
      }
      if ($authority_figure) {
        print $authority_figure . " did it.";
      }
      break;
    case 'restore_database':
      $backup_names = unserialize($row['oldval']);
      if ($row['val'] !== null) {
        $backup_witnesses = unserialize($row['val']);
      }
      else {
        $backup_witnesses = null;
      }
      print "restore_database'>A backup was restored, overwriting newer " .
        "data</a>.</td><td>" .
        "<a href='$page_name?details=" . $row['autoid'] . "'>" .
        escape_html($row['attrib']) . ($backup_witnesses?' (witnessed by ' . 
                                       join(" and ",
                                            array_map('escape_html',
                                                      $backup_witnesses)) . 
                                       ')':'') .
        " restored backup " . escape_html($backup_names[0]) . "</a>";
      break;
    default:
      print "unknown'>The action " . escape_html($row['subj_name']) . 
        " was taken</a>.</td><td>" .
        "<a href='$page_name?details=" . $row['autoid'] . "'>Details</a>";
      break;
    }
  }
  print "</td></tr>";
}
if ($first_autoid !== $first_last_row['first']) {
  $link_str = "<a href='$page_name?end_num=" .
    escape_html($first_autoid+1) . "'>Next Page</a>";
}
else {
  $link_str = '';
}
$link_str .= "</th><th colspan=2 class='linkprev'>";
if ($last_autoid !== $first_last_row['last']) {
  $link_str .= "<a href='$page_name?start_num=" . 
    escape_html($last_autoid-1) . "'>Previous Page</a>";
}
$link_str .= "</th></tr>";
$buf_str = ob_get_clean();
print $link_str . "</thead>";
print $buf_str;
print "<th colspan=2 class='linknext'>" . $link_str;
print "</tbody></table>";
print "</div>";
?>
                    
