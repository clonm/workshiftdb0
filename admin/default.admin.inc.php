<?php
//try to figure out where php_includes lives.  But really, you should just set 
//$php_includes manually, unless you control PHP and can set the include_path
$success = false;
foreach (array('DOCUMENT_ROOT','PATH_TRANSLATED','SCRIPT_FILENAME','PWD') as $key) {
  $success_basedir = false;
  foreach (array('HTTP_SERVER_VARS','_SERVER','HTTP_ENV_VARS','_ENV') as $global) {
    $basedir = null;
    if (array_key_exists($key,$GLOBALS[$global]) && $GLOBALS[$global][$key]) {
      $basedir = realpath(dirname($GLOBALS[$global][$key]));
    }
    if (!$basedir) {
      break;
    }
    $comps = explode('/',$basedir);
    while (count($comps)) {
      if (file_exists(implode('/',$comps) . '/php_includes')) {
        $success = true;
        break 2;
      }
      array_pop($comps);
    }
  }
}
if (!$success) {
  exit("Directory structure is not set up properly");
}
$php_includes = implode('/',$comps) . '/php_includes';
$_SERVER['PHP_AUTH_USER'] = 'workshiftadmin';
if (!isset($delay_include)) {
  include_once("$php_includes/janakdb.inc.php");
}
$houses = array('ath','aca','caz','clo','con','dav','euc','fen','hip','hoy',
		'kid','kng','lot','nsc','rid','roc','she','stb','wil','wol','co');
?>
