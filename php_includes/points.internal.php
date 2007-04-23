<?php
require_once('default.inc.php');
$table_name = 'points';
$col_styles = array('input','input','input', 'input','input');
$col_styles = array_pad($col_styles,10,'');
$order_exp = 'category, points desc, app_number';
require_once("$php_includes/table_edit.php");
?>
