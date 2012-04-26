<?php
$houses = array('ath','aca','caz','clo','con','dav','euc','fen','hip','hoy',
		'kid','kng','lot','nsc','rid','roc','she','stb','wil','wol','co');
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
    
