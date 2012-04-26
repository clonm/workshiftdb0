<?php
require_once('default.admin.inc.php');
$temp_houses = getopt('h::');
if (isset($temp_houses['h'])) {
  $houses = $temp_houses['h'];
  if (!is_array($houses)) {
    $houses = array($houses);
  }
}
if (!array_key_exists('REQUEST_URI',$_SERVER)) {
  $_REQUEST = array_flip($argv);
}

function monthtosem($month) {
  switch ($month) {
  case 1: return 'spring';
  case 5: case 6: return 'summer';
  case 8: case 9: return 'fall';
  default: return 'unknown';
  }
}
$tables_array = array('votes','voting_record','elections_record','elections_log','elections_text',
                      'elections_attribs');

foreach ($houses as $house_name) {
  //  fwrite(STDERR,"doing $house_name\n");
  print "$house_name\n";
  $url_array['db'] = "$db_basename$house_name";
  $sql_user = null;
  $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
               $url_array['db']);

$backups = array();
if (isset($_REQUEST['backups'])) {
    $backups = get_backup_dbs();
  }
  array_unshift($backups,'');
foreach ($backups as $archive) {
  if ($archive) {
    print $archive . ": \n";
    $row = $db->GetRow("select `semester_start` from `GLOBAL_archive_data`" .
                       " where `archive` = ?",array($archive));
    $archive = $archive_pre . $archive . "_";
  }
  else {
    $row = array();
    $row['semester_start'] = get_static('semester_start');
  }
  $semester_start = explode('-',$row['semester_start']);
  $this_sem = monthtosem($semester_start[1]);
  $sem_string = $semester_start[0] . ($this_sem == 'fall'?'_':'-') . $this_sem;
  if (!table_exists('elections_record')) {
    continue;
  }
  $res = $db->Execute('SELECT `election_name` FROM ' .
                      bracket($archive . 'elections_record') . 
                      ' where  not LEFT(`election_name`,' . strlen($sem_string) .
                      ') = ? order by `election_name` desc',array($sem_string));
  while ($row = $res->FetchRow()) {
    if ($sem_string == '2011-summer' &&
        substr($row['election_name'],0,strlen('2011-spring')) == '2011-spring') {
      continue;
    }
    print $row['election_name'] . ",\n";
    //don't want election half-deleted
    $db->StartTrans();
    $db->Execute("lock tables " . bracket($archive . 'modified_dates') .
                 " write, `$archive" . 
                 join("` write, `$archive",$tables_array) . "` write" .
                 ($archive?", `GLOBAL_archive_data` write":''));
    foreach ($tables_array as $table) {
      $db->Execute("delete from " .  
                   bracket($archive . $table) . " where `election_name` = ?", 
                   array($row['election_name'] )); 
    }
    elections_log($row['election_name'],null,'election_deleted',null,"(scheduled)");
    $db->CompleteTrans();
    $db->Execute("unlock tables");
  }
}
print "\n";
}
