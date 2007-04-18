<?php
require_once('default.inc.php');
?>
<html><head><title>Color Settings</title></head>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=post>
<input type=hidden name='submitting' value='1'>
<?php
if (array_key_exists('submitting',$_REQUEST)) {
  $attribs = array('use_color','use_color_print')