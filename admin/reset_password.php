<?php
require_once('default.admin.inc.php');
if (!isset($_REQUEST['house']) || !isset($_REQUEST['officer'])) {
?>
<html><head><title>Reset Officer Password</title></head><body>
    Reset password for:<br/>
<form action='<?=this_url()?>' method=post>
   House: <select name='house'>
<option>
<?php
   $houses[] = 'nsc';
  print_r($houses);
    foreach ($houses as $house) {
    print "<option value='" . escape_html($house) . "'>" . escape_html($house) . "\n";
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
$db->Connect('localhost',"usca_janak$house","workshift","usca_janak$house");
set_passwd($house . $officer,null,'',true,true);
print "Success resetting password for " . escape_html($house . $officer);
?>