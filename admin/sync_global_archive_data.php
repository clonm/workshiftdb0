<?php
require_once('default.admin.inc.php');
if (!array_key_exists('house',$_REQUEST)) {
?>
<form action='<?=this_url()?>' method='post'>
Recreate GLOBAL_archive_data for house: <select name='house'>
<option>
<?php
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
}
$house = $_REQUEST['house'];
$db->Connect('localhost',"bsccoo5_wkshift","workshift",
  "bsccoo5_workshift$house");
$tables = $db->Execute("show tables like ?",
  array('%' . quote_mysqlreg("_house_list")));
$db->Execute("delete from `GLOBAL_archive_data`");
while ($tbl_info = $tables->FetchRow()) {
  $archive = substr($tbl_info[0],0,strlen($tbl_info[0])-10);

  $db_props = array();
  $db_props['semester_start'] = get_static('semester_start');
  $oldfetch = $db->SetFetchMode(ADODB_FETCH_NUM);
  $mod_row = $db->_Execute("select max(`mod_date`) " .
    "from " . bracket($archive . 'modified_dates'));
  $db_props['mod_date'] = $mod_row->fields[0];
  $db_props['cur_week'] = get_cur_week();
  $wanted_row = $db->_Execute("select count(*) from " .
    bracket($archive . 'wanted_shifts'));
  $db_props['num_wanted'] = $wanted_row->fields[0];
  $db_props['num_assigned'] = 0;
  foreach ($days as $day) {
    $master_row = $db->GetRow("select count(*) from " .
      bracket($archive . 'master_shifts') .
      " where `$day` is not null and `$day` != ?",
      array($dummy_string));
    $db_props['num_assigned'] += $master_row[0];
  }
  //did the user not name this him/herself
  $db_props['autobackup'] =
    preg_match('/[0-9][0-9][0-9][0-9](_[0-9][0-9]){5}/',$archive);
  $db_props['owed_default'] = get_static('owed_default');
  //store these parameters in archive data
  var_dump($db_props);
  $db->Execute("insert into `GLOBAL_archive_data` " .
    "(`archive`,`semester_start`,`mod_date`,`cur_week`,`num_wanted`, " .
    "`num_assigned`,`autobackup`,`creation`,`owed_default`) " .
    "VALUES (?,?,?,?,?,?,?,NOW(),?)",
      array(substr($archive,strlen($archive_pre),-1),
      $db_props['semester_start'],
      $db_props['mod_date'],$db_props['cur_week'],$db_props['num_wanted'],
      $db_props['num_assigned'],$db_props['autobackup'],
      $db_props['owed_default']));
}
?>
