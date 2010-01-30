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
function my_date_format($v) {
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
      return my_date_format($str);
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

//See table_edit.js for details on what was passed here
$num_cols = $_REQUEST['num_cols'];
$num_rows = $_REQUEST['num_rows'];
$table_name = $_REQUEST['table_name'];

if (!access_table($table_name)) {
  exit("You cannot update table $table_name");
}
$col_names = $_REQUEST['col_names'];
//some problems with unset variables
$data = array();
//get the cells, row by row
if (array_key_exists('changed_rows',$_REQUEST)) {
  $changed_rows = $_REQUEST['changed_rows'];  
  for ($ii = 0; $ii < count($changed_rows); $ii++) {
    $id = $changed_rows[$ii];
    $data[$id] = array();
    $changed_cells = $_REQUEST["changed_row-$id"];
    for ($jj = 0; $jj < count($changed_cells); $jj++) {
      $col = $changed_cells[$jj];
      $data[$id][$col] = $_REQUEST["cell-{$id}-{$col}"];
    }
  }
}

$added = array();
$num_added = $_REQUEST['num_added'];
for ($ii = 0; $ii < $num_added; $ii++) {
  $added[$ii] = $_REQUEST["added-{$ii}"];
}

//what rows are we deleting?
$deleted_rows = $_REQUEST['deleted_rows'];

//we were called outside of javascript
if (!$_REQUEST['js_flag']) {
  exit("Tables cannot be updated in this way.  Please use the Update database button.");
}

$db->StartTrans();
$db->Execute("lock tables `$table_name` write, " .
    "`{$archive}modified_dates` write" . $archive_lock_tables); 

//update changed cells
foreach ($data as $ii => $changed) {
  $changed_cols = array_keys($changed);
  //this is the last question mark in execute
  //Janak changed 1/18/10 to deal with autoid bugs
  $changed[] = $ii;
  //bracketqind indexes into the $col_names array for us
  $query_string = "UPDATE " . bracket($table_name) . " SET " . 
    implode(", ",array_map("bracketqind",$changed_cols))
    . " WHERE " . bracket('autoid') . " = ?";
  if (!$db->Execute($query_string,
		    $changed)) { 
    echo "error executing update query<br>";
    echo $query_string;
    print_r($changed);
    echo $db->ErrorMsg();
  }
}

//utility function for below
function bracketqind($ind) {
  global $col_names;
  return bracket($col_names[$ind]) . " = ?";
}

function real_col_filter($arr) {
  global $real_col_inds;
  return array_intersect_key($arr,$real_col_inds);;
}
#do the virtual column thing
get_real_table_columns();

//adodb Execute can take a 2-d array and execute the command on each element
//in turn.  Very useful here.
if ($num_added) {
  $real_col_inds = array();
  $update_cols = array();
  for ($ii = 0; $ii < $num_cols; $ii++) {
    if (array_key_exists($col_names[$ii],$col_reals)) {
      $update_cols[] = $col_names[$ii];
      $real_col_inds[$ii] = 1;
    }
  }
  $added = array_map('real_col_filter',$added);
  $query_string = "INSERT INTO " . bracket($table_name) ." (" .
    implode(", ",array_map("bracket",$update_cols)) .
    ") VALUES (" . str_repeat("?,",count($update_cols)-1) . "?)";
  if (!$db->Execute($query_string,$added)) {
    echo "error executing insert query: $query_string";
    print_r($added);
    print_r($db->ErrorMsg());
    
  }
}

$del_array = array();
foreach ($deleted_rows as $ii) {
  //does this row exist?  not sure why this is here
  if (strlen($ii)) {
    //I don't know why $num_cols is here, as opposed to just 0
    $del_array[] = array($num_cols => $ii);
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
$db->Execute("unlock tables");
$db->CompleteTrans();
?>
