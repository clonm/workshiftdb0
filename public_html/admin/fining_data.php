<?php 
require_once('default.inc.php');
$table_name = "{$archive}fining_data";
$col_styles = array('member_name','double','double','input','input','input');
$col_formats = array('member_name' => '','fine' => '','hours' => '','date' => 'dateformat',
                     'description' => '','week_cashed' => '');
$body_insert = 
<<<BODYINSERT
<span style='font-size: 10pt'>
Negative numbers are credits.  The hours and week_cashed sections will be operational soon,
but for now you can just fine money.</span>
BODYINSERT
;
/*If you want to fine a member the monetary equivalent
of X hours, enter X into the hours column -- just the fine will be stored, but you can
avoid multiplying X times the workshift rate.  For a member to pay back workshift fines
by "cashing in" their up hours, enter the negative number of hours that they are cashing
in, and enter the week that you want those hours applied to.  An entry for that week will
appear in that week's sheets, showing the number of hours cashed in.  If you want to change
something about the cash-in, do it ON THIS PAGE!  Changes here will automatically cascade
to the week sheets, but not vice versa.*/
#';
require_once("$php_includes/table_edit.php");
?>
