<?php
//show house members the weekly totals
$php_start_time = array_sum(split(' ',microtime()));
$read_only_weekly = true;
$body_insert = '';
require_once('default.inc.php');
require_once("$php_includes/weekly_totals.inc.php");
//for the following variable's explanations, see table_edit.php
$table_name = "weekly_totals";
$col_formats['member_name'] = 'namelink';
$col_styles[$ctr] = '';

//they can click on a name and login to see their page
function namelink($str,$ii) {
  return array("<a href='person.php?member_name=$str' target='person'>$str</a>",
	       strlen($str));
}

if (!isset($_REQUEST['download'])) {
  $body_insert .= "<div class='print_hide'><a href='" . escape_html($_SERVER['REQUEST_URI']);
  if (count($_GET)) {
    $body_insert .= escape_html('&');
  }
  else {
    $body_insert .= escape_html('?');
  }
  $body_insert .= "download'>Download to Excel</a>\n";
  $body_insert .= "</div>";
  
  //table view hides the elements in table_edit that are only useful for
  //editable tables
  require_once("$php_includes/table_view.php");
  exit;
}
$col_formats['member_name'] = '';
export_csv_file('House Fines',$table_edit_query,$col_formats,$mung_whole_row);
?>
