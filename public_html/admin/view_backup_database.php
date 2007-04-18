<?php
require_once('default.inc.php');
?>

Choose the database you want to view.
<form action='index.php' method=GET>
<select name='archive'>
  <?php
$dbnames = get_backup_dbs();
$dbnames = array_reverse($dbnames);
foreach ($dbnames as $backup) {
  print "<option value='" . escape_html($backup) . "'>" . 
    escape_html($backup) . "\n";
}

?>
</select><br/>
<!-- { -->
<input type=submit value='View backup database'></form></body></html>
<?php exit; ?>
