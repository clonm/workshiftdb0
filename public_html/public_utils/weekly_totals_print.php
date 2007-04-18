<?php
// format the weekly totals for printing
$php_start_time = array_sum(split(' ',microtime()));
$require_user = false;
require_once('default.inc.php');
require_once("$php_includes/weekly_totals.inc.php");
require_once("$php_includes/table_print.php");
?>
