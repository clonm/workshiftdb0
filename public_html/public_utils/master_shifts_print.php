<?php error_reporting(E_ALL);
//you can easily print master_shifts with this.  Note that the background needs
//to be printed in order to preserve the grey
$require_user = false;
require_once('default.inc.php');
$time = microtime(1);
$order_exp = 'workshift';
$table_name = "{$archive}master_shifts";
$col_formats = array('workshift' => '', 'floor' => '', 'hours' => '', 
		     'Weeklong' => 'namegrey','Monday' => 'namegrey',
		     'Tuesday' => 'namegrey','Wednesday' => 'namegrey', 
		     'Thursday' => 'namegrey','Friday' => 'namegrey',
		     'Saturday' => 'namegrey', 'Sunday' => 'namegrey');

$dummy_string = get_static('dummy_string');
//show dummy_string as greyed out
function namegrey($str) {
  global $dummy_string;
  if ($str === $dummy_string) {
    return array('<div style="background-color: grey;">.',1);
  }
  else {
    return array($str,strlen($str));
  }
}

$javascript_pre = "<script type=text/javascript>alert('In Page Setup choose to " .
"print Background to get the greyed out cells printed properly');</script>";
require_once("$php_includes/table_print.php");
?>
