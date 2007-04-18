<?php
$body_insert = '';
require('default.inc.php');
$passwdcheck = 0;
$election_name = $_REQUEST['election_name'];

$name = get_election_attrib('descript_filename',null,false);
if ($name) {
  $file = get_election_attrib('descript_file',null,false);
  $type = get_election_attrib('descript_filetype',null,false);
}
else {
  ?>
 <html><head><title>Election File (Empty)</title></head><body>
<?php print_help() ?>
    No document has been uploaded for <?=escape_html($election_name)?>.
</body>
</html>
<?php
    exit;
}
header('Content-type: $type');
header('Content-Disposition: inline; filename="' . $name . '"'); 
print $file;
?>