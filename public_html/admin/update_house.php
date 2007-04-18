<html><head><title>Change house list</title></head><body>
<?php
require_once('default.inc.php');
print_help();

create_and_update_weekly_totals_data();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
$name_array = get_houselist();
?>
If you are re-visiting this page, you may have to reload if you have
just changed the house list.  Choose the function you need below --
you can redo the whole house, rename a member, add one or more members,
or delete one or more members.<p>
<hr>
<h4>Upload houselist(s) from CO</h4>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method='POST'
   enctype="multipart/form-data">
Upload the files that CO forwarded you -- male and female if you have them,
or just one if that's what you've got.  You can email 
<a href='mailto:housing@usca.org'>housing@usca.org</a> to get these lists.
If you only have one, meaning your  whole house is in one file, 
then check the checkbox below.  If your house has two files (most likely one
for female and one for male), then you <b>must</b> upload them both at the
same time<br>
<input type="file" name="list_one" size="40">
<br>
<input type="file" name="list_two" size="40">
<br>
Does the first file contain only women, and the second (if it exists) only men?
<input type=checkbox name='gendered_lists'><br>
If you're uploading just one file, do you really mean to? <input type="checkbox" name="allow_single_file">
<input type=submit value='Upload Lists!'>
</form>
<hr>
<h4>Enter houselist manually</h4>
<form action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
Enter a complete houselist here ("Lastname, Firstname" [without quotes], 
or however you do it, one per line).  Members who are <b>not</b> in this list
will be deleted and any members who are on this list but already in the system
will be ignored.  This <b>must</b> be a complete list of your house, and not 
just some new people you are adding later in the semester, otherwise you will
delete members (and their information) who are still in the house.  If you just
want to add a few people while keeping your current house members, see below.<br>
<textarea name='redo_house' rows=20></textarea><br>
<input type=submit value='Redo whole house'></form>

<hr>
<h4>Rename member</h4>
<form action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
Change name:
<select name="name_orig">
<?php foreach ($name_array as $name) {
?>
<option value='<?=escape_html($name)?>'><?=escape_html($name)?>
<?php } ?>
</select><br>
New name: 
<input type='text' name='rename'><br/>
<input type='checkbox' name='suppress_backup' id='suppress_backup'>
<label for='suppress_backup'>Check this box if you don't want the whole
database backed up just because you're renaming this one member.</label><br/>
<input type=submit value='Rename member'></form>
<hr>
<h4>Add members</h4>
<form action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
Add members ("Lastname, Firstname" [without quotes], or however you do it, one per line): <br>
<textarea name='new_members' rows=10></textarea><br>
<input type=submit value='Add members'></form>
<hr>
<h4>Delete members</h4>
<form action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
Delete members: <select multiple size=5 name="delete_members[]">
<option>
<?=implode("\n<option>",$name_array)?>
</select><br>
<input type=submit value='Delete members'></form>
 <hr>
<form action='<?=$_SERVER['REQUEST_URI']?>' method='POST'>
Synchronize tables.<br>  If there are users missing in some
   table which should have one row per user, like the
   weekly_totals, or house_info, click here to put them in.
   Be warned, this could take a while.
<input type=hidden name='synchronize_tables'>
<input type=submit value='Synchronize Tables'>
</form>
</body></html>
 <?php 
#';
exit; 
} 

if (count($_FILES)) {
  $num_files = 0;
  $pointslist = array();
  $points_hash = array();
  $mem_hash = array();
  $redo_house = '';
  foreach ($_FILES as $fileinfo) {
    if (!$handle = fopen($fileinfo['tmp_name'],'r')) {
      continue;
    }
    $num_files++;
    $field_inds = array();
    $started = false;
    while ($line = fgets($handle)) {
      if (!$started && !preg_match('/^.* *(-+) +(-+) +(-+) +(-+) +(-+) +(-+) +(-+) *$/',
                                    $line, $field_inds,
                                    PREG_OFFSET_CAPTURE)) {
         continue;
       }
       if (!$started) {
         array_shift($field_inds);
         foreach ($field_inds as $key=>$val) {
           $field_inds[$key] = $field_inds[$key][1];
         }
         $started = true;
         continue;
       }
       if ($line === "\x0c\x0a") {
         $started = false;
         continue;
       }
       if (preg_match('/^ *-+ *-+ *$/',$line)) {
         break;
       }
       if (strlen($line) > 1) {
         $pointslist[] = 
           array(rtrim(ltrim(substr($line,$field_inds[2],
                                    $field_inds[3]-$field_inds[2]))),
                 rtrim(ltrim(substr($line,$field_inds[3],
                                    $field_inds[4]-$field_inds[3]))),
                 rtrim(ltrim(substr($line,$field_inds[4],
                                    $field_inds[5]-$field_inds[4]))),
                 $num_files);
         if (!strlen($pointslist[count($pointslist)-1][0])) {
           array_pop($pointslist);
         }
         else {
           $redo_house .= $pointslist[count($pointslist)-1][0] . "\n";
           $points_hash[$pointslist[count($pointslist)-1][2]] = 
             $pointslist[count($pointslist)-1][0];
           $mem_hash[$pointslist[count($pointslist)-1][0]] = count($pointslist)-1;
         }
       }   
     }
  }
  if ($num_files < 2 && !isset($_REQUEST['allow_single_file'])
      && !get_static('allow_single_houselist_upload',false)) {
    janak_error(
<<<JANAKERR
I'm sorry, you only uploaded one file, and didn't check the box to
confirm that you only wanted one file uploaded.  Please do it again, and either
check the box, or upload 2 files.  You can 
<a href='basic_consts.php'>change the system to allow single file uploads</a>
without checking, but unless CO <b>never</b> gives you separate houselists
for men and women, this is <b>highly</b> discouraged.
JANAKERR
,$db,E_USER_ERROR
);
  }
  $_REQUEST['redo_house'] = $redo_house;
}

if (array_key_exists('redo_house',$_REQUEST)) {
  $redo_house = explode("\n",$_REQUEST['redo_house']);
  $temp = array();
  foreach ($redo_house as $member) {
    $member = ltrim(rtrim($member));
    if (!$member) {
      continue;
    }
    $temp[] = $member;
  }
  $redo_house = $temp;
  $res = $db->Execute("select `member_name` from `house_list`" .
                      "order by `member_name`");
  $old_members = array();
  while ($row = $res->FetchRow()) {
    $old_members[] = $row['member_name'];
  }
  $delete_members = array_diff($old_members,$redo_house);
  $new_members = array_diff($redo_house,$old_members);
  if (isset($points_hash)) {
    $res = $db->Execute("select * from `points` where `app_number`");
    $new_remove = array();
    $old_remove = array();
    while ($row = $res->FetchRow()) {
      if (isset($points_hash[$row['app_number']])) {
        $new_remove[] = $points_hash[$row['app_number']];
        $old_remove[] = $row['member_name'];
        $pointslist[$mem_hash[$points_hash[$row['app_number']]]][0] = 
          $row['member_name'];
      }
    }
    $delete_members = array_diff($delete_members,$old_remove);
    $new_members = array_diff($new_members,$new_remove);
  }
    ?>
<h3>The following members will be deleted (press &lt;ctrl&gt; and click on
a name to prevent deletion of that name):</h3>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method='POST'>
   <select multiple size=<?=count($delete_members)?> name='delete_members[]'>
<?php
   foreach ($delete_members as $mem) {
     print "<option selected>" . escape_html($mem) . "\n";
   }
?>
</select>
<h3>The following members will be added (you may prevent members from
being added by removing their names here, or add more members by inserting them):</h3>
 <textarea name='new_members' rows=<?=count($new_members)+2?>>
<?php
    foreach ($new_members as $mem) {
      print escape_html($mem) . "\n";
    }
?>
</textarea>
<br>
<?php
    if (isset($pointslist)) {
?>
    <h3>Points will be given to current members as follows:</h3>
<table>
<tr><th>Name</th><th>Points</th><th>Application Number</th>
<?php
       if (isset($_REQUEST['gendered_lists'])) {
  ?>
<th>Gender</th>
<?php
   }
?>
</tr>
<?php
       foreach ($pointslist as $mem_data) {
?>
<tr><td><input size=50 name='points_mem_name[]' value='<?=escape_html($mem_data[0])?>'></td>
<td><input size=10 name='points_mem_points[]' value='<?=escape_html($mem_data[1])?>'></td>
<td><input size=10 name='points_mem_app_num[]' value='<?=escape_html($mem_data[2])?>'></td>
<?php
   if (isset($_REQUEST['gendered_lists'])) {
?>
<td><input size=2 name='points_mem_gender[]' value='<?=$mem_data[3]==1?'F':'M'?>'></td>
<?php
   }
?>
</tr>
<?php
   }

?>
</table>
<?php 
    }
?>
<input type=submit value='I have reviewed/edited the changes and am ready!'>
</form>
</body>
</html>
<?php
   exit;
}

//all the fields that could have a member in them
$member_fields = array('member_name' => 1,'Monday' => 1, 'Tuesday' => 1, 
		       'Wednesday' => 1, 'Thursday' => 1, 'Friday' => 1, 
		       'Saturday' => 1, 'Sunday' => 1, 'Weeklong' => 1);
//utility function to make an array
function make_arr($elt) {
  return array($elt);
}

function un_array($elt) {
  return $elt[0];
}

$res = $db->GetRow("select database() as db");
if (is_empty($res)) {
  exit("Error getting current database: " . $db->ErrorMsg());
}
$curdb = $res['db'];
#$db->debug = true;

if (!array_key_exists('suppress_backup',$_REQUEST)) {
  $_REQUEST['backup_ext'] = '';
  require_once('backup_database.php');
}
//definitely want to use transactions here -- we don't want only some changes
//to be made
$db->StartTrans();
//are we renaming?
if (array_key_exists('name_orig',$_REQUEST)) {
  print "<h4>Renaming member</h4>\n";
  $name_orig = $_REQUEST['name_orig'];
  if (!$name_orig) {
    exit("<h3>No name specified</h3>");
  }
  $name_new = $_REQUEST['rename'];
  if (isset($name_orig) && isset($name_new)) {
    print escape_html($name_orig) . ' is turning into ' . escape_html($name_new) .
      " in tables <br>\n";
    if ($USE_MYSQL_50) {
      $res = $db->Execute('SHOW FULL TABLES');
    }
    else {
      $res = $db->Execute('SHOW TABLES');
    }
    while ($tbl_info = $res->FetchRow()) {
      if (!$USE_MYSQL_50 || $tbl_info['Table_type'] === 'BASE TABLE') {
	$tbl = $tbl_info['Tables_in_' . $curdb];
        if (substr($tbl,0,10) === 'zz_archive') {
          continue;
        }
        print escape_html($tbl) . " ";
	$rescol = $db->Execute('SHOW COLUMNS FROM ' . bracket($tbl));
	while ($cols = $rescol->FetchRow()) {
	  $col = $cols['Field'];
	  if (array_key_exists($col,$member_fields)) {
	    $db->Execute('UPDATE ' . bracket($tbl) . ' SET ' . 
			 bracket($col) . ' = ? ' .
			 'WHERE ' . bracket($col) . ' = ? ',
			 array($name_new, $name_orig));
            set_mod_date($tbl);
	  }
	}
      }
    }
  }
  print "<h4>All done with renaming!</h4>\n";
  elections_log(null,'workshift change','rename member',$name_orig,$name_new);
}

if (array_key_exists('new_members',$_REQUEST)) {
  $new_members = $_REQUEST['new_members'];
  $new_members = explode("\n",$new_members);
  $temp = array();
  foreach ($new_members as $member) {
    $member = ltrim(rtrim($member));
    if (!$member) {
      continue;
    }
    $temp[] = $member;
  }
  $new_members = $temp;
  if (count($new_members)) {
    print "<h4>Adding new members</h4>";
    print join(', ',$new_members);
    print "<br>are being added to tables <br>\n";
    if ($USE_MYSQL_50) {
      $res = $db->Execute('SHOW FULL TABLES');
    }
    else {
      $res = $db->Execute('SHOW TABLES');
    }
    while ($tbl_info = $res->FetchRow()) {
      if (!$USE_MYSQL_50 || $tbl_info['Table_type'] === 'BASE TABLE') {
        $tbl = $tbl_info['Tables_in_' . $curdb];
        if (substr($tbl,0,2) === 'zz') {
          continue;
        }
        $rescol = $db->Execute('SHOW FULL COLUMNS FROM ' . bracket($tbl));
        while ($cols = $rescol->FetchRow()) {
          if (($cols['Key'] === 'UNI' || $cols['Key'] === 'PRI') && $cols['Field'] === 'member_name') {
            $resmem = $db->Execute("select `member_name` from `$tbl` " .
                                   "order by `member_name`");
            $ii = 0;
            $these_members = array();
            while ($row = $resmem->FetchRow()) {
              $these_members[] = $row['member_name'];
            }
            $new_new_members = array_map('make_arr',array_diff($new_members,$these_members));
            if (count($new_new_members)) {
              print escape_html($tbl) . " ";
              $db->Execute('INSERT INTO ' . bracket($tbl) . 
                           '(' . bracket('member_name') . ') VALUES (?)',
                           $new_new_members);
              set_mod_date($tbl);
            }
          }
        }
      }
    }
    print "<h4>All done with adding members!</h4>";
    elections_log(null,'workshift change','add member',null,
                  join("; ",$new_members));
  }
  else {
    print "<h4>No members added</h4>\n";
  }
}

if (array_key_exists('delete_members',$_REQUEST)) {
  $delete_members = $_REQUEST['delete_members'];
  $temp = array();
  foreach ($delete_members as $member) {
#    $member = rtrim($member);
    if (!$member) {
      print "deleting '$member' (no name!)";
#      continue;
    }
    $member = array($member);
    $temp[] = $member;
  }
  $delete_members = $temp;
  if (count($delete_members)) {
    print "<h4>Deleting old members</h4>";
    print join(', ',array_map('un_array',$delete_members));
    print "<br>are being removed from tables <br>\n";
    if ($USE_MYSQL_50) {
      $res = $db->Execute('SHOW FULL TABLES');
    }
    else {
      $res = $db->Execute('SHOW TABLES');
    }
    while ($tbl_info = $res->FetchRow()) {
      if (!$USE_MYSQL_50 || $tbl_info['Table_type'] === 'BASE TABLE') {
        $tbl = $tbl_info['Tables_in_' . $curdb];
        if (substr($tbl,0,2) === 'zz') {
          continue;
        }
        switch ($tbl) {
          case 'votes': case 'voting_record': case 'password_table': case 'house_info':
            continue 2;
        }
        print escape_html($tbl) . " ";
        $rescol = $db->Execute('SHOW FULL COLUMNS FROM ' . bracket($tbl));
        while ($cols = $rescol->FetchRow()) {
          $col = $cols['Field'];
          if (!$cols['Null'] && $col === 'member_name') {
            $db->Execute('DELETE FROM ' . bracket($tbl) . ' WHERE ' . 
                         bracket('member_name') . '= ?',$delete_members);
            set_mod_date($tbl);
          }
          else {
            if (array_key_exists($col,$member_fields)) {
              $db->Execute('UPDATE ' . bracket($tbl) . ' SET ' . 
                           bracket($col) . ' = NULL ' .
                           'WHERE ' . bracket($col) . ' = ? ',
                           $delete_members);
              set_mod_date($tbl);
            }
          }
        }
      }
    }
    print "<h4>All done with deleting members!</h4>";
    elections_log(null,'workshift change','delete member',null,
                  join("; ",$_REQUEST['delete_members']));
  }
  else {
    print "<h4>No members deleted</h4>\n";
  }
}

if (isset($_REQUEST['points_mem_name'])) {
  print "<h4>Updating points list . . .</h4>\n";
  for ($ii = 0; $ii < count($_REQUEST['points_mem_name']); $ii++) {
    $db->Execute("update `points` set `points` = ?, `app_number` = ? " .
                 "where `member_name` = ?",
                 array($_REQUEST['points_mem_points'][$ii],
                       $_REQUEST['points_mem_app_num'][$ii],
                       $_REQUEST['points_mem_name'][$ii]));
    set_mod_date('points');
  }
  print "<h4>All done updating points list!</h4>\n";
}

if (array_key_exists('synchronize_tables',$_REQUEST)) {
  $houselist = get_houselist();
  if ($USE_MYSQL_50) {
    $res = $db->Execute('SHOW FULL TABLES');
  }
  else {
    $res = $db->Execute('SHOW TABLES');
  }
  print "<h4>Synchronizing tables...</h4>\n";
  while ($tbl_info = $res->FetchRow()) {
    if (!$USE_MYSQL_50 || $tbl_info['Table_type'] === 'BASE TABLE') {
      $tbl = $tbl_info['Tables_in_' . $curdb];
      if (substr($tbl,0,2) === 'zz') {
        continue;
      }
      $rescol = $db->Execute('SHOW FULL COLUMNS FROM ' . bracket($tbl));
      while ($cols = $rescol->FetchRow()) {
        $col = $cols['Field'];
        if (($cols['Key'] === 'UNI' || $cols['Key'] === 'PRI') && $col === 'member_name') {
          $prep_ins = $db->Prepare("insert into `$tbl` (`member_name`) " .
                                   "VALUES (?)");
          $resmem = $db->Execute("select `member_name` from `$tbl` " .
                              "order by `member_name`");
          $ii = 0;
          $these_members = array();
          while ($row = $resmem->FetchRow()) {
            $these_members[] = $row['member_name'];
          }
          $old_members = array_map('make_arr',array_diff($these_members,$houselist));
          $new_members = array_map('make_arr',array_diff($houselist,$these_members));
          if (count($old_members) && $tbl != 'house_info' && $tbl != 'password_table') {
            $db->Execute("delete from `$tbl` " .
                         "where `member_name` = ?",$old_members);
            print("<br>Deleting from " . escape_html($tbl) . ": " . 
                  join(', ',array_map('esc_arr',$old_members)));
          }
          if (count($new_members)) {
            $db->Execute("insert into `$tbl` (`member_name`) " .
                         "VALUES (?)",$new_members);
            print("<br>Inserting into " . escape_html($tbl) . ": " . 
                  join(', ',array_map('esc_arr',$new_members)));
          }
        }
      }
    }
  }
  print "<br/>All done!";
}
  
$db->CompleteTrans();

function esc_arr($arr) {
  return escape_html($arr[0]);
}
?>
