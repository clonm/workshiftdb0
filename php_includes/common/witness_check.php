<?php
require_once('default.inc.php');
$houselist = get_houselist();
?>
<form action='<?=escape_html($_SERVER['REQUEST_URI'])?>' method=POST>
<?php
for ($ii = 0; $ii < $num; $ii++) {
?>
   Witness <?=$ii+1?>: <select name='member_witness_<?=$ii?>'>
<OPTION>
<?php
foreach ($houselist as $name) {
  if ($name !== $member_name) {
    print "<OPTION>" . escape_html($name) . "\n";
  }
}
?>
</SELECT>
    Password: <input type=password name='passwd_witness_<?=$ii?>'><br/>
<?php
}
?>
<input type=submit value='Submit'>
</form>
