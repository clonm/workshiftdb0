<?php
$delay_include = true;
$body_insert = '';
if ($_REQUEST['table_name'] == 'fining_data') {
  $require_user = array('house', 'workshift');
}
require_once('default.inc.php');
require_once("$php_includes/update_db.internal.php");
?>
