<?php
// Gives common interface and setup to all scripts involving database.
// Also has options that may need to be modified for different site setups.

////////////  OPTIONS THAT NEED TO BE CHANGED ON SITE CHANGES ///////////////

//location of html_includes directory -- will vary site to site
$html_includes = '/~bsccoo5/cvsworkshift/workshiftdb0/public_html/html_includes';
//php_utils is a directory containing source files with functions that
//are defined in later versions of php, but may not be defined in
//ours.  It is interpreted as a path relative to the php_includes dir.
$php_utils = 'php_utils';

$admin_email = 'janak@berkeley.edu';

//these need to be changed if the bug tracking system changes.  They'll also
//need to be changed in index.php (for the admin), since it doesn't include
//this file.
$bug_report_url = 'http://sourceforge.net/tracker/?func=add&group_id=191164&atid=936272';
$feature_request_url = 'http://sourceforge.net/tracker/?func=add&group_id=191164&atid=936275';
$project_url = 'http://sourceforge.net/projects/workshiftdb0/';

//TODELETE
//USE_MYSQL_FEATURES says whether we're using multiple users and multiple 
//databases.  I think it's better for security, but it probably won't work
//out of the box anymore since this is being installed on a system without it,
//so it hasn't been fully tested (although the original Stebbins setup had it)
$USE_MYSQL_FEATURES = false;
//do we have views and stored procedures?
$USE_MYSQL_50 = false;

//if running mysql on a local pipe, this should just be '.'
$default_server = 'localhost';

//users are determined by the url.  However, which part of the url to use is not
//so clear -- it'll vary server to server, based on the setup of the aliases.
//Change the number below to the segment of the url that is used.
$house_name_component = 4;

//////////////  END OPTIONS THAT MAY NEED TO BE CHANGED ON SITE CHANGES /////

//////////////  CODE YOU SHOULD TRY NOT TO CHANGE /////////////////

//for testing, we might run this on the command line, which is the else clause.
if (array_key_exists('REQUEST_URI',$_SERVER)) {
  $url_components = explode('/',$_SERVER['REQUEST_URI']);
  //house_name is the name of the house being accessed.
  $url_name = $url_components[$house_name_component];
  if ($url_name == 'workshift') {
    $url_name = $url_components[++$house_name_component];
  }
  $house_name = $url_name;
  //where administrative php scripts are
  $secured = ($url_components[$house_name_component+1] === 'admin');
  $baseurl = join('/',array_slice($url_components,0,$house_name_component+1));
}
else {
  //usually, the compression is set by apache -- not on the command line
  ini_set('zlib.output_compression',true);
  ini_set('zlib.output_compression_level',6);
  //get house name from command line
  $house_name = $url_name = $argv[$house_name_component];
  $baseurl = '';
  //don't worry about passwords on the command line
  $secured = false;
}

//hopefully, we got here through a php_includes, so we know where we are
if (isset($php_includes)) {
  //the assumption is that the public_html directory is a sibling of php_includes
  $basedir = explode("/",$php_includes);
  $basedir[count($basedir)-1] = 'public_html';
  $basedir = join('/',$basedir);
}

//We want to die on anything.  It's possible we don't actually want to die on
//anything, but my fear is that if we don't, some "notice" about an uninitialized
//variable will be ignored, maybe because it shows up in javascript, and then
//either break the javascript or cause a massive data loss error.  Better for the
//page not to load.
error_reporting((E_ALL | E_RECOVERABLE_ERROR) & ~E_STRICT);
//adodb is the wrapper for our database.  Note that either the include
//path needs to point to adodb, or adodb should be a subdirectory of this dir.
//Something I don't quite understand is that if you are including files
//recursively, it seems that files can be in any of the directories in which
//files have been included
require_once("$php_includes/adodb/adodb.inc.php");
require_once("$php_includes/adodb/adodb-errorhandler.inc.php");
//this include is only for rs2html, which is buggy anyway
require_once("$php_includes/adodb/tohtml.inc.php");

//lots and lots of utility functions
require_once('janakdb-utils.inc.php');

//As above, I want completely error-free operation.  Unfortunately,
//adodb sometimes suppresses errors.  So I have my own error handler,
//to make sure that all errors are fatal, unless the
//janak_fatal_error_reporting level is lowered.  I can't just use the
//error_reporting level to determine which errors are displayed
//because adodb changes the level.  adodb is really more trouble than
//it's worth.
set_error_handler('janak_errhandler');
//report all errors
janak_error_reporting(E_ALL);
//die on all errors
janak_fatal_error_reporting(E_ALL);

//fuck magic_quotes_gpc.  Oh my god.  Fuck magic_quotes_gpc.
//php quotes user input to php scripts, even though it's worse than useless.
if (get_magic_quotes_gpc() || ini_get('magic_quotes_sybase')) {
  $first_strip = true;
  $_GET = stripformslash($_GET);
  $_POST = stripformslash($_POST);
  $_REQUEST = stripformslash($_REQUEST);
  $_COOKIE = stripformslash($_COOKIE);
  $_FILES = stripformslash($_FILES);
  $first_strip =false;
}

//user authentication is done with cookies.  Logging out is very
//simple, and is just done by having forget_login as a user input
//field.  Remember, unsetting a cookie on the user end doesn't mean it
//disappears from the already-submitted user data.
if (array_key_exists('forget_login',$_REQUEST)) {
  foreach ($_COOKIE as $key => $val) {
    setcookie($key,$val,time()-10800,"/");
    setcookie($key,"",0,"/");
    unset($_REQUEST[$key]);
  }
  //don't want any scripts getting confused and thinking they were
  //posted.  Basically just update_house.php, because it's pretty much
  //the only script that can post without being logged in.
  $_SERVER['REQUEST_METHOD'] = 'GET';
}

//This array has all the basic information about the connection, as
//well as some non-basic info.  If you ever have a time when the
//database name is a more complicated function of the house_name, you
//might have to make this into a function.  It's an array because of
//the admin functions that might need to use it repeatedly
$url_array = array('db' => "bsccoo5_workshift$house_name", 
                   'user' => "bsccoo5_wkshift",
                   'pwd' => "workshift",
                   'server' => $default_server,
);

//username is the username from the apache security login.  Since
//there is only one real physical directory, apache can't keep
//different workshift managers out of each other's sites, so we need
//to check the authentication here.
$real_username = get_real_username();
#//secured means we're in the ...admin/ portion
#if ($secured && $real_username !== get_username() && 
#    $real_username !== 'workshiftadmin') {
#  trigger_error("You are trying to access with the wrong username/password " .
#                "for this database: $real_username",E_USER_ERROR);
#}


//many scripts can be invoked with an archive argument, which will make them
//access the backup database, instead of the current one.
if (!isset($archive)) {
  if (isset($_REQUEST['archive'])) {
    $archive = $_REQUEST['archive'];
    if (substr($archive,0,strlen($archive_pre)) !== $archive_pre) {
      $archive = $archive_pre . $archive . '_';
    }
  }
  else {
    $archive = '';
  }
}

//give nicer messages for mysql connection errors that are not my fault
set_error_handler('janak_mysqlerr');

//this is THE connection to the database.  The 't' is for transactions.
$db = ADONewConnection('mysqlt');
//turn on debugging?
#$db->debug = true;
//these are supposed to enable multi-queries for exec_proc, but it
//seems like we don't need them.  It was hard to find them online,
//though, so they're staying here.
#$db->clientFlags = 131072;
#$db->clientFlags += 65536;

//there are the houses, and then there's the admin section, which
//doesn't connect by default to any database
if ($house_name !== 'admin') {
#  $db->Connect('localhost','colaborczar','workshift','usca_janakco');
  $db->Connect($url_array['server'], $url_array['user'], 
               $url_array['pwd'], $url_array['db']);
  
  set_error_handler('janak_errhandler');
  // Return associative arrays
  $db->SetFetchMode(ADODB_FETCH_ASSOC); 
  
  //enable transactions -- MyISAM doesn't have transactions
  $db->Execute("set storage_engine = 'InnoDB'");
  //theoretically, there should be some multi-version support in my
  //code, which is why we get the version here.  Really, though, it's
  //too hard to keep the different branches synchronized.
  $row = $db->GetRow("select version() as vs");
  $temp = $row['vs'];
  $temp = explode('.',$temp);
  $MYSQL_VERSION = 10000*$temp[0];
  $MYSQL_VERSION += 1000*$temp[1];
  $temp = explode('-',$temp[2]);
  $MYSQL_VERSION += $temp[0];
  //both of the below can be suppressed via $body_insert being non-null
  //authentication for site.  Sets up cookie session.
  ob_start();
  print_help(null,true);
  //require_user might never really return (if it calls member_check).
  //So we don't get to print our nice <hr/>.  We'll live.  The output
  //buffering might also not end until the script ends.
  require_user();
  print "<hr/>";
  if (isset($body_insert)) {
    $body_insert .= ob_get_clean();
  }
  else {
    ob_end_flush();
  }
}

function janak_mysqlerr($errno,$errstr,$errfile,
                          $errline,$errcontext) {
  if (substr($errstr,-strlen("Too many connections")) == "Too many connections") {
    exit("Sorry, the BSC shares its server with other accounts, and they're "
         . "hogging the server.  Try connecting again in a minute.");
  }
//   if (substr($errstr,0,strlen("mysql_connect()")) == "mysql_connect()") {
//     exit("Sorry, there was an error connecting to the workshift database.  Try"
//          . " again in a minute and everything should be ok.  Otherwise, "
//          . "email " . admin_email() . ".");
//   }
  janak_errhandler($errno,$errstr,$errfile,$errline,$errcontext);
}
?>
