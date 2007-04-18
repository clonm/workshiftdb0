<?php
// intermediate include file -- sets up for printing, then calls table_view to
//render the table

//don't overwrite calling script's $header_insert
if (!isset($header_insert)) {
  $header_insert = '';
}
//use our print stylesheet, and define a function which gets rid of extraneous
//screen elements
$header_insert .= <<<HEREDOC
<LINK REL=StyleSheet HREF="$html_includes/table_print.css" TYPE="text/css">

<script type="text/javascript">
function print_totals() {
  document.getElementById('headtable').style.display = 'none';
  document.getElementById('phptime').style.display = 'none';
  document.getElementById('print_button').style.display = 'none';
  hide_elts = document.getElementsByTagName("div");
  for (var ii in hide_elts) {
    if (hide_elts[ii].className) {
      var classes = hide_elts[ii].className.split(" ");
      for (var jj in classes) {
        if (classes[jj] == 'print_hide') {
          hide_elts[ii].style.display = 'none';
          continue;
        }
      }
    }
  }
  var cols = header_row.cells;
  for (var ii in cols) {
    if (cols[ii].firstChild && cols[ii].firstChild.firstChild) {
      var elt = cols[ii].firstChild.firstChild;
      cols[ii].removeChild(cols[ii].firstChild);
      cols[ii].insertBefore(elt,cols[ii].firstChild?cols[ii].firstChild:null);
    }
  }
  window.print();
}

</script>
HEREDOC;

//
if (!isset($body_insert)) {
  $body_insert = '';
}
//put out a button to call above function
$body_insert .= "<input class=button type=button id=\"print_button\" " .
"value='Print' onClick='print_totals();'>";

include_once('table_view.php');

?>
