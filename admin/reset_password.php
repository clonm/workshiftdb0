<?php
//Basic utility script that resets any officer's password.
//Commented by Janak 31 Jan 2010
//
require_once('default.admin.inc.php');
?>
<html><head><title>Reset Officer Password</title></head><body>
<?php
if (!isset($_REQUEST['house']) || !isset($_REQUEST['officer'])) {
?>
    Reset password for:<br/>
<form action='<?=this_url()?>' method='post'>
   House: <select name='house'>
<option>
<?php
    foreach ($houses as $house) {
      print "<option value='" . escape_html($house) . "'>" .
        escape_html($house) . "\n";
  }
?>
</select>
    Officer: 
<select name='officer'>
<option>
<option>workshift
<option>president
<option>house
</select>
<input type=Submit value="Submit">
</form>
</body>
</html>
<?php
    exit;
}
$house = $_REQUEST['house'];
$officer = $_REQUEST['officer'];
  $db->Connect($url_array['server'],$url_array['user'],$url_array['pwd'],
    "$db_basename$house");
set_passwd($house . $officer,null,'',true,true);
print "Success resetting password for " . escape_html($house . $officer);
?>
</body></html>
