<?php
require_once('default.inc.php');
?>
<html><head><title>Elections Log</title></head><body>
<?php
if (isset($_REQUEST['details'])) {
  $row = $db->GetRow("select * from `elections_log` where `autoid` = ?",
                      array($_REQUEST['details']));
  if ($row['attrib'] == 'manual_entry') {
    $arr = unserialize($row['val']);
    foreach ($arr as $race => $vals) {
      print "<h3>" . escape_html($race) . "</h3>\n";
      if (is_array($vals)) {
        print join("; ",$vals);
      }
      print $vals;
      print "<p>\n";
    }
    exit;
  }
}
?>
<form action=<?=escape_html($_SERVER['REQUEST_URI'])?> method=get>
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
  $ending_exp = " limit 100";
}
else {
  $ending_exp = '';
}

$res = $db->Execute("select * from `elections_log` $where_exp " .
                    "order by `time_entered` desc, `autoid` desc $ending_exp",
                    $arr);
?>
<table border=1><thead><tr><th>Time</th><th>Election</th><th>Activity</th>
<th>Details</th></tr></thead>
<tbody>
<?php
$page_name = $_SERVER['REQUEST_URI'];
$page_name = substr($page_name,0,strpos($page_name,'?'));
while ($row = $res->FetchRow()) {
  echo "<tr>";
  print "<td>" . escape_html($row['time_entered']) . "</td>";
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
      default:
        print "'>The action " . escape_html($row['attrib']) . " was taken</td><td>&nbsp;";
      }
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
    }
  }
  print "</td></tr>";
}
print "</tbody></table>";

$get_str = '?';
foreach ($_GET as $key => $val) {
  $get_str.= $key . '=' . $val;
    }
$page_name = $_SERVER['REQUEST_URI'];
$page_name = substr($page_name,0,strpos($page_name,'?'));
?>
                    
