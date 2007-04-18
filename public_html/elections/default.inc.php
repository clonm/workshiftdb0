<?php

//try to figure out where php_includes lives.  But really, you should just set 
//$php_includes manually, unless you control PHP and can set the include_path
$basedir = dirname($_SERVER['PATH_TRANSLATED']);
$comps = explode('/',$basedir);
while (count($comps) && $comps[count($comps)-1] !== 'workshift') {
  array_pop($comps);
}
if (!count($comps)) {
  exit("Directory structure is not set up properly");
}
array_push($comps,'php_includes');
$php_includes = implode('/',$comps);
if (!isset($delay_include)) {
  include_once("$php_includes/janakdb.inc.php");
}
?>