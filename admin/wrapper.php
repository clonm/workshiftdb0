<?php
require_once('default.admin.inc.php');
$db->Connect('localhost','usca_janakstb','workshift','usca_janakstb');
$db->debug = true;
create_master_shifts();
create_master_shifts();
?>
php if (!array_key_exists('in1',$_REQUEST)) { 
<form action='<?=$_SERVER['REQUEST_URI']?>' method=POST>
<input name='in1'>
</form>
     exit; }
set_magic_quotes_runtime(false);
var_dump(get_magic_quotes_runtime());
print_r($_REQUEST);
