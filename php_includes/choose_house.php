<?php
//need house list
$delay_include = true;
require_once('../admin/default.admin.inc.php');
require_once("$php_includes/janakdb-utils.inc.php");
?>
<form action='<?=this_url()?>' method='POST'>
Choose the house you live in/work with:  <select name='default_house'>
<?php
  foreach ($houses as $house) {
  print "<option value='" . escape_html($house) . "'>" . 
  escape_html($house) . "\n";
}
?>
</select>
<input type='submit' value='Submit'>
</form>
</body>
</html>
    