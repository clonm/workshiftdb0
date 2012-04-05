<?php
//Default include file for all system administrator scripts.  Sets some 
//variables the administrator should have (currently just $houses) and then 
//does its main task which it has in common with default.inc.php -- try to 
//figure out where php_includes lives.  If this script cannot find 
//php_includes, then you can set it manually here to the correct absolute path.
//TODO 1: get list of houses automatically from dir listing of /workshift
//Commented by Janak 31 Jan 2010
$php_includes = '';
//Example:
#$php_includes = '/home/bsccoo5/public_html/workshift/php_includes';

//If a new house is added, this array needs to be updated.  TODO 1
$houses = array('ath','aca','caz','clo','con','dav','euc','fen','hip','hoy',
		'kid','kng','lot','nsc','rid','roc','she','stb','wil','wol','co');

//path for zip
$zipexec = "/usr/local/bin/zip";

//See default.inc.php for the logic here.  This code should be copied
//directly from that file, except for the error message on exit.
if (!$php_includes) {
  $success = false;
  foreach (array('DOCUMENT_ROOT','PATH_TRANSLATED',
    'SCRIPT_FILENAME','PWD') as $key) {
      $success_basedir = false;
      foreach (array('HTTP_SERVER_VARS','_SERVER',
        'HTTP_ENV_VARS','_ENV') as $global) {
          $basedir = null;
          if (array_key_exists($global,$GLOBALS) && 
              array_key_exists($key,$GLOBALS[$global]) && 
            $GLOBALS[$global][$key]) {
            $basedir = realpath(dirname($GLOBALS[$global][$key]));
          }
          if (!$basedir) {
            continue;
          }
          $comps = explode(DIRECTORY_SEPARATOR,$basedir);
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
    exit("default.admin.inc.php: " .
      "Directory structure is not set up properly");
  }
  $php_includes = implode('/',$comps) . '/php_includes';
}

if (!isset($delay_include)) {
  include_once("$php_includes/janakdb.inc.php");
}
?>
