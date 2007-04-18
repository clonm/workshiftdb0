<?php
// format the weekly totals for printing
$php_start_time = array_sum(split(' ',microtime()));
require_once('default.inc.php');
require_once("$php_includes/weekly_totals.inc.php");
#require_once("$php_includes/table_print.php");
?>
PHP generated this page in <?=round(array_sum(split(' ',microtime()))-$php_start_time,2)?> seconds.
