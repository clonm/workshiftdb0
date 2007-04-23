<?php
$require_user = false;
require('default.inc.php');
$row = $db->GetRow("select * from `workshift_description`");
if (is_empty($row)) {
  ?>
 <html><head><title>Workshift Doc (Empty)</title></head><body>
<?php print_help() ?>
The workshift manager has not uploaded a document
</body>
</html>
<?php
    exit;
}
header('Content-type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $row['filename'] . '"'); 
print $row['description'];
?>
