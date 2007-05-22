<?php 
//this script is called by the javascript in table_edit.php (through 
//update_db.php in whatever directory, which is just a wrapper), and updates
//tables.  It should return nothing when called by javascript -- any text
//returned indicates an error.  It can also be posted to directly, in which
//case it should output and tell the user what it's doing

require_once('adodb/adodb.inc.php');
require_once('adodb/drivers/adodb-mysqlt.inc.php');

//what you have to do to a date to quote it -- in Access it's #$date#
function date_quote($date) {
  return "'$date'";
}

//turn 8/26 into 2005-08-26
function make_date($date) {
  //split up along /
  $arr = explode("/",$date);
  //maybe there were no /'s in the string?
  if (count($arr) == 1) {
    //split up along -
    $arr = explode("-",$date);
  }
  //didn't work either?  Give up.
  if (count($arr) <= 1) {
    return "";
  }
  //no year?  Use this year.
  if (count($arr) == 2) {
    $year = localtime();
    $year = $year[5]+1900;
    array_unshift($arr,$year);
  }
  //otherwise, pad everything to the right length and return it
  else {
    $arr[0] = str_pad($arr[0],4,"0",STR_PAD_LEFT);
  }
  $arr[1] = str_pad($arr[1],2,"0",STR_PAD_LEFT);
  $arr[2] = str_pad($arr[2],2,"0",STR_PAD_LEFT);
  return implode("-",$arr);
}

//called by my qstr to format dates
function date_format($v) {
  $date = make_date($v);
  if (!strlen($date)) {
    return null;
  }
  else {
    return date_quote($date);
  }
}

//same for times
function time_format($v) {
  //break up along :
  $arr = explode(':',$v);
  //nothing?  break up along spaces then
  if (count($arr) < 2) {
    $arr = explode(' ',$v);
  }
  //first part must be hour
  $hour = $arr[0];
  //was there anything besides hour?
  if (count($arr) >= 2) {
    //yes, so that's rest of it
    $rest = $arr[1];
  }
  else {
    //no, rest of it is whole thing -- maybe should give up here
    $rest = $v;
  }
  $minute = '00';
  if ($rest != $v) {
    //do we have a number?
    if (preg_match('/^\d\d/',$rest)) {
      //good, set minute to two digits of number
      $minute = substr($rest,0,2);
    }
  }
  //is there an am?
  $am = stripos($rest,'a');
  //no?
  if ($am === false) {
    $pm = stripos($rest,'p');
    //is there a pm?
    if ($pm !== false) {
      if ($hour == $v) {
        $hour = substr($v,0,$pm);
      }
      //add 12 to the hour unless it was 12
      if ($hour < 12) {
        $hour += 12;
      }
    }
  }
  else {
    if ($hour == $v) {
      $hour = substr($v,0,$am);
    }
    if ($hour == 12) {
      $hour = 0;
    }
  }
  if (strlen($hour) < 2) {
    $hour = '0' . $hour;
  }
  return "'$hour:$minute:00'";
}

//class just to deal with qstr and make it handle dates and times better
class quoting_mysqlt extends ADODB_mysqlt {
  function qstr($str,$magic_quotes_enabled=false) {
    //do we have a date?
    //(at least one number, a forward slash, then at least one other number)
    if (preg_match('/^\d\d?\/\d\d?$/',$str)) {
      return date_format($str);
    }
    //how about a time?
    //at least one number, a colon, then two more numbers, or at least
    //one number, then possibly a colon, with possibly two more numbers,
    //then maybe a space, then some kind of am/pm, with possible periods
    if (preg_match('/^\d\d?:\d\d$/',$str) ||
	preg_match('/^\d\d?(:\d\d)? ?[AaPp]\.?[Mm]?\.?$/',$str)) {
      return time_format($str);
    }
    //be good to empty strings
    else if (!strlen($str)) {
      return 'null';
    }
    //otherwise handle base case with parent class
    else {
      return ADODB_mysqlt::qstr($str,$magic_quotes_enabled);
    }
  }
}

//mysqlt will use this function if defined to initialize itself
$ADODB_NEWCONNECTION = 'make_quoting_mysqlt';
  
function make_quoting_mysqlt($irrelevant) {
  $obj = new quoting_mysqlt();
  return $obj;
}

//no accidental output from janakdb.inc.php
if (!isset($body_insert)) {
  $body_insert = '';
}
//this include comes here, after we've defined the above function, so that our
//extended class will be used instead of the default
require_once('janakdb.inc.php');
#$db->debug = true;

$num_cols = stripformslash($_REQUEST['num_cols']);
$num_rows = stripformslash($_REQUEST['num_rows']);
$table_name = stripformslash($_REQUEST['table_name']);

if (!access_table($table_name)) {
  exit("You cannot update table $table_name");
}
 
$col_names = array_map('stripformslash',$_REQUEST['col_names']);
//some problems with unset variables
$data = array();
$temp = array();
//get the cells, row by row
for ($ii = 0; $ii < $num_rows; $ii++) {
  for ($jj = 0; $jj < $num_cols; $jj++) {
    if (array_key_exists("cell-{$ii}-{$jj}",$_REQUEST)) {
      $temp[] = stripformslash($_REQUEST["cell-{$ii}-{$jj}"]);
    }
    else {
      $temp[] = null;
    }
  }
  $data[] = $temp;
  $temp = array();
}
$autoid = array();
//get the autoids -- there has to be an autoid for updatable tables
for ($ii = 0; $ii < $num_rows; $ii++) {
  if (array_key_exists("autoid-$ii",$_REQUEST)) {
    $autoid[$ii] = stripformslash($_REQUEST["autoid-$ii"]);
  }
}
#do the virtual column thing
get_real_table_columns();
//we were called outside of javascript -- output what we're doing
if (!$_REQUEST['js_flag']) {
  echo "<h3>What follows is the output from the sql statements.  ";
  echo "There should be no errors, only sql statements.</h3><p>\n";
  set_mod_date($table_name);
  //don't die on anything, because as much as we can should work
  janak_fatal_error_reporting(0);
  $db->debug = true;
  //loop through rows
  for ($ii=0; $ii<$num_rows; $ii++) {
    $row = $data[$ii];
    //loop through cols
    for ($jj = 0; $jj < $num_cols; $jj++) {
      //is there something here, and is it a real column?
      if (!is_null($row[$jj]) && array_key_exists($col_names[$jj],$col_reals)) {
	$arr[bracket($col_names[$jj])] = $row[$jj];
      }
    }
    if (isset($autoid[$ii])) {
      $arr['autoid'] = $autoid[$ii];
    }
    else {
      $arr['autoid'] = null;
    }
    //insert or update this row
    $db->Replace(bracket($table_name),$arr,'autoid',true);
  }
  //delete rows that we were supposed to delete
  for ($ii = 0; $ii <$num_rows; $ii++) {
    $del = array_key_exists("delete_$ii",$_REQUEST) && $_REQUEST["delete_$ii"];
    if ($del) {
      if (!array_key_exists($ii,$autoid)) {
	echo "<h3>Error! couldn't delete row $ii (contents: ";
	print_r($data[$ii]);
	echo ") -- it has no id.  Perhaps you just added it?  ";
	echo "Reload the table and try deleting it again.</h3>";
      }
      $db->Execute('DELETE FROM ' . bracket($table_name) . ' WHERE ' . 
		   bracket('autoid') . ' = ?',array($autoid[$ii]));
    }
  }
  echo "Updated $table_name";
  exit;
}

$db->StartTrans();
//ok, we've been called by javascript.  Let's see what cells it's telling us
//have been changed.  See table_edit.js for details on what was passed here
$changed_cells = array();
for ($ii = 0; $ii < $num_rows; $ii++) {
  if (array_key_exists("changed_cells_$ii",$_REQUEST)) {
    $temp =&$_REQUEST["changed_cells_$ii"];
    //if this isn't an array, the whole row is being changed (inserted)
    if (!is_array($temp)) {
      $changed_cells[$ii] = 1;
    }
    else {
      //put the indices in order
      sort($temp);
      //and make them the keys
      $changed_cells[$ii] = array_flip($temp);
    }
  }
}
//what rows are we deleting?
$deleted_rows = $_REQUEST['deleted_rows'];

//find out what kind of columns we have
$res = $db->Execute('SHOW COLUMNS FROM ' . bracket($table_name));
while ($cols = $res->FetchRow()) {
  $col_types[$cols['Field'] ] = $cols['Type'];
}


//utility function for below
function bracketqind($ind) {
  global $col_names;
  return bracket($col_names[$ind]) . " = ?";
}

//update cells, or get ready to insert whole rows (we can insert wholesale
//at the end)
$ins_array = array();
foreach ($changed_cells as $ii => $changed) {
  //going to insert whole row?
  if ($changed === 1) {
    //how many rows are we inserting already?
    $ind = count($ins_array);
    //here's the data we'll insert
    for ($jj = 0; $jj < $num_cols; $jj++) {
      if (isset($col_reals[$col_names[$jj]])) {
	$ins_array[$ind][] =& $data[$ii][$jj];
      }
    }
    continue;
  }
  //which columns were changed?
  foreach ($changed as $key => $junk) {
    if (!isset($col_reals[$col_names[$key]])) {
      unset($changed[$key]);
    }
  }
  if (!count($changed)) {
    continue;
  }
  $changed_vars = array_intersect_key($data[$ii],$changed);
  //this is the last question mark in execute
  $changed_vars[] = $autoid[$ii];
  //bracketqind indexes into the $col_names array for us
  $query_string = "UPDATE " . bracket($table_name) . " SET " . 
    implode(", ",array_map("bracketqind",array_keys($changed)))
    . " WHERE " . bracket('autoid') . " = ?";
  if (!$db->Execute($query_string,
		    $changed_vars)) { 
    echo "error executing update query<br>";
    echo $query_string;
    print_r($changed_vars);
    echo $db->ErrorMsg();
  }
}

//adodb Execute can take a 2-d array and execute the command on each element
//in turn.  Very useful here.
if (count($ins_array)) {
  $temp_cols = array_intersect($col_names,array_keys($col_reals));
  $query_string = "INSERT INTO " . bracket($table_name) ." (" .
    implode(", ",array_map("bracketvirt",$temp_cols)) .
    ") VALUES (" . str_repeat("?,",count($temp_cols)-1) . "?)";
  if (!$db->Execute($query_string,$ins_array)) {
    echo "error executing insert query: $query_string";
    print_r($ins_array);
    print_r($db->ErrorMsg());
    
  }
}

$del_array = array();
foreach ($deleted_rows as $ii) {
  //does this row exist?  not sure why this is here
  if (strlen($ii)) {
    //I don't know why $num_cols is here, as opposed to just 0
    $del_array[] = array($num_cols => $autoid[$ii]);
  }
}
if (count($del_array)) {
  $del_sql = "DELETE FROM " . bracket($table_name). " WHERE autoid = ?";
  if (!$db->Execute($del_sql,$del_array)) {
    echo "error executing delete query: $del_sql";
    print_r($del_array);
  }
}
set_mod_date($table_name);
$db->CompleteTrans();
?>
