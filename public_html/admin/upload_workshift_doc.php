<html><head><title>Upload Workshift Doc</title></head><body>
<?php
require_once('default.inc.php');
print_help();
if (!isset($_FILES['description']) || !isset($_FILES['description']['tmp_name'])
    || !strlen($_FILES['description']['tmp_name'])) {
  $row = $db->GetRow("select `filename` from `workshift_description`");
  if (!is_empty($row)) {
    print "<h3>Your current file is <a href='../workshift_doc.php'>" . escape_html($row['filename']) .
      "</a> and will be overwritten if you upload a new file</h3>";
  }
 ?>
<form method=post action="<?=escape_html($_SERVER['REQUEST_URI'])?>"
   enctype="multipart/form-data">
<input type="file" name="description" size="40">
<input type=submit value='Upload Description'>
</form>
<?php
   exit;
}
$file = file_get_contents($_FILES['description']['tmp_name']);
if ($db->Execute("delete from `workshift_description`") &&
    $db->Execute("insert into `workshift_description` values (?,?)",
                 array($file,$_FILES['description']['name']))) {
  print "All done!";
}
else {
  print "Error!  Didn't work.";
}
?>
</body></html>
