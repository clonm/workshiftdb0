<?php
require_once('default.admin.inc.php');
?>
<html><head><title>Delete Backup Databases</title></head><body>
<?php
if (isset($_REQUEST['house'])) {
  $house = $_REQUEST['house'];
  $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
    "$db_basename$house");
  //just to quiet delete_backup_databases down
  $include_path = get_include_path();
  $include_path = '../public_html/' . PATH_SEPARATOR . $include_path;
  set_include_path($include_path);
  $_REQUEST['backup_name'] = true;
  require_once('../public_html/admin/delete_backup_database.php');
  if ($num_deleted == count($backup_arr)) {
    if (!isset($_REQUEST['num_detected'])) {
      $_REQUEST['num_detected'] = $delete_dbs[2];
    }
    if ($_REQUEST['num_detected'] != count($dbnames)) {
?>
<h4>Incomplete detection</h4>
It is possible that not all redundant/corrupt databases were detected, because
of time constraints.
<form action='<?=this_url()?>' method='get'>
<input type='hidden' name='start_db_index'
       value='<?=$delete_dbs[2]-$num_deleted?>'/>
<input type='hidden' name='house' value='<?=escape_html($_REQUEST['house'])?>'
       />
<input type='hidden' name='num_detected' value='<?=escape_html($_REQUEST['num_detected'])?>'/>
<input type='submit' value='Look for more backups to delete in this house'>
</form>
<?php
   $house = null;
    }
  }
  else {
    $house = null;
  }
}
?>
<form action='<?=this_url()?>' method='get'>
Delete redundant backups for house: <select name='house'>
<option>
<?php
   $houses[] = 'nsc';
  $next_select = false;
if (!isset($house)) {
  $house = null;
}
    foreach ($houses as $this_house) {
      print "<option value='" . escape_html($this_house) . "' ";;
      if ($next_select) {
        print " selected";
        $next_select = false;
      }
      print ">";
      print $this_house . "\n";
      if ($house == $this_house) {
        $next_select = true;
      }
  }
?>
</select>
<input type=Submit value="Submit">
</form>
</body>
</html>
<?php
    exit;
?>
