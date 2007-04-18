//this file has most of the javascript used by table_edit.php to give
//spreadsheet-like functionality, quick sql posts, etc.  I'm amazed
//it works at all, and it doesn't work with IE
//the philosophy here is that things should help the workshift manager,
//but not restrict him or her.  So there will be warnings, but hopefully
//no errors

//main function -- dispatches functions
window.onkeypress=process_keypress;
//clears modifier flags, signals not to send key on to browser
//and does name completion (modifiers are shift, ctrl, alt)
window.onkeyup=process_keyup;
//sets key_modifier -- that's it, but it's the only place I can get to it.
window.onkeydown=process_keydown;
//warns before user navigates away from page
window.onunload = process_unload;
window.onbeforeunload = process_beforeunload;

//are we currently hiding rows?
var restricted = false;
//what is the first non-hidden row
var first_row_vis = 0;
//same for last row
var last_row_vis = num_rows-1;
//shift/alt/ctrl key pressed
var key_modifier = 0;
//submission request
var req = 0;
//changed cells, for submission
var change_array = new Array();
//deleted rows, for submission
var deleted_rows = new Array();
//backup of changed cells, so we can restore it if submission fails
var ch_array_copy = new Array();
//same for deleted
var del_rows_copy = new Array();
//way to detect if enter was pressed, and thus not submit form
var enter_pressed = false;
//are we currently doing a hard calculation, that might be user-interrupted
//by the escape key
var in_calculation = false;
//are we about to stop the calculation being done?
var stop_calculation = false;
//are we sorting?
var sorting_flag = false;
  
//do name completion (called on key_up)
//targ is the input element, code is the key pressed
function name_complete(targ,code) {
  //key_modifier is absent or shift
  if ((key_modifier == 0 || key_modifier == 16) && 
      //space
      (code == 32 ||
       //comma
       code == 188 ||
       //dash
       code == 109 ||
       //a-z
       (code > 64 && code < 91))) {
    var cur_text = targ.value.toLowerCase();
    //loop through names looking for the first one matching what we have
    for (ind in name_array) {
      var nam = name_array[ind];
      if (nam.toLowerCase().indexOf(cur_text) == "0") {
        targ.value = nam;
	//select what we've inserted so the user will overwrite it with 
        //the next keystroke if it's not what they wanted
        targ.setSelectionRange(cur_text.length,nam.length);
        break;
      }
    }
  }
}

//gets key_modifier for later functions
//e is the event passed by the browser
function process_keydown(e) {
  //this stuff is a little cross-browser code, even though 90% of this 
  //file will not work on IE
  var code;
  //IE uses window.event
  if (!e) e = window.event;
  if (!e) return true;
  //I forget which browser uses which of these
  if (e.keyCode) code = e.keyCode;
  else if (e.which) code = e.which;
  //was it a ctrl/shift/alt?  If so, store it
  if (code > 15 && code < 19) {
    key_modifier = code;
  }
  return true;
}

//do name completion if required, clear key_modifier, and stop browser 
//from processing keypress
//e is the event, passed by the browser
function process_keyup(e) {
  //cross-browser code again -- see process_keydown
  if (!e) e = window.event;
  if (!e) return true;
  var code;
  if (e.keyCode) code = e.keyCode;
  else if (e.which) code = e.which;
  var targ;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  //do text completion
  if (is_nameinput(targ)) {
    name_complete(targ,code);
  }
  //clear key_modifier flag
  if (code > 15 && code < 19) {
    key_modifier = 0;
    return true;
  }
  if (enter_pressed) {
    enter_pressed = false;
    //don't keep on processing key
    return false;
  }
  return true;
}

//do the main event handling.
//e is the event, passed by the browser
function process_keypress(e) {
  //get hold of the event, in a cross-browser way, as above
  if (!e) e = window.event;
  if (!e) return true;
  var code;
  if (e.keyCode) code = e.keyCode;
  else if (e.which) code = e.which;
  var targ;
  if (e.target) targ = e.target;
  else if (e.srcElement) targ = e.srcElement;
  if (e.ctrlKey || (e.modifiers && e.modifiers == 2)) {
    key_modifier = 17;
  }
  if (e.shiftKey || (e.modifiers && e.modifiers == 4)) {
    key_modifier = 16;
  }
  //<enter>
  if (code == 13 && key_modifier == 0 && targ.nodeName && targ.nodeName != 'TEXTAREA') {
    enter_pressed = true;
    //if user hit enter in the name_limit select box, limit by the name
    //they chose
    if (targ.id == "name_limit") {
      restrict_rows(targ);
      return false;
    }
    //if we're in an auxilary form, it's ok to process enter
    if (targ.parentNode && targ.parentNode.nodeName && targ.parentNode.nodeName == 'FORM') {
      enter_pressed = false;
      return true;
    }
    //otherwise, do the spreadsheet thing and go forward one row
    forward_row(targ,1,0);
    return false;
  }
  //<shift>-<enter>
  if (code == 13 && key_modifier == 16) {
    enter_pressed = true;
    //go back one row
    forward_row(targ,-1,0);
    return false;
  }
  //ctrl-e
  if (code == 101 && key_modifier == 17) {
    //restrict or unrestrict to match person
    //    key_modifier = 0;
    if (restricted) {
      unrestrict_rows();
      return false;
    }
    restrict_rows(targ);
    return false;
  }
  //ctrl-s
  if (code == 115 && key_modifier == 17) {
    //submit data using javascript
    //key_modifier = 0;
    if (targ && targ.blur) {
      targ.blur();
    }
    submit_data();
    return false;
  }
  //page-down
  if (code == 34 && key_modifier == 0 && targ.nodeName && 
      targ.nodeName == 'INPUT' && targ.type && targ.type == 'text') {
    //spreadsheet page-down
    forward_row(targ,15,1);
    return false;
  }
  //page-up
  if (code == 33 && key_modifier == 0 && targ.nodeName && targ.nodeName == 'INPUT' && targ.type && targ.type == 'text') {
    //spreadsheet page-down
    forward_row(targ,-15,1);
    return false;
  }
  //downarrow
  if (code == 40 && key_modifier != 16 && targ.nodeName && targ.nodeName == 'INPUT' && targ.type && targ.type == 'text') {
    //spreadsheet forward row
    forward_row(targ,1,1);
    return false;
  }
  //uparrow
  if (code == 38 && targ.nodeName && targ.nodeName == 'INPUT' && targ.type && targ.type == 'text') {
    //spreadsheet backward row
    forward_row(targ,-1,1);
    return false;
  }
  //ctrl-end
  if (code == 35 && key_modifier == 17 && targ.nodeName && targ.nodeName != 'TEXTAREA') {
    //go to bottom of page
    if (targ.blur) {
      targ.blur();
    }
    scroll(0,document.body.scrollHeight);
    return false;
  }
  //home
  if (code == 36 && key_modifier == 17 && targ.nodeName && targ.nodeName != 'TEXTAREA') {
    //go to top of page
    if (targ.blur) {
      targ.blur();
    }
    scroll(0,0);
    return false;
  }
  //escape
  if (code == 27 && in_calculation) {
    stop_calculation = true;
    return false;
  }
  return true;
}

//advance to next visible row, in direction dir
function next_visible(index, dir) {
  var next_row = rows_array[index];
  //find row which isn't hidden
  while (next_row.style.display == 'none' && 
         ((dir == 1 && index<last_row_vis) ||
	 (dir == -1 && index>first_row_vis))) {
    if (dir < 0)
      index--;
    else
      index++;
    next_row = rows_array[index ];
  }
  return next_row;
}

//what it sounds like
function color_row(rw, color) {
    rw.style.color = color;
    //remember to color all the cells!
    for (var ii = 0; ii < num_cols; ii++) {
      if (rw.cells[ii].firstChild && rw.cells[ii].firstChild.style) {
        rw.cells[ii].firstChild.style.color = color;
      }
    }
}    

//move from cell e num rows up or down, col says whether to keep the same
//column or go to the first one
function forward_row(e,num,col) {
  var elts = get_cell(e);
  //don't do anything if we're not in a cell
  if (elts == 0) {
    return true;
  }
  var rwindex = e.parentNode.parentNode.rowIndex-1;
  //just move to the top or bottom of the page if we're already at the end
  //of the table
  if ((rwindex == last_row_vis && num > 0) ||
      (rwindex == first_row_vis && num < 0)) {
      if (num < 0) {
	  scroll(0,0);
      }
      else {
	  scroll(0,document.body.scrollHeight);
      }
    return true;
  }
  //deselect our cell
  if (e.blur) {
      e.blur();
  }
  if (num > 0) {
    rwindex = Math.min(Number(rwindex)+num, last_row_vis);
  }
  else {
    rwindex = Math.max(Number(rwindex)+num,first_row_vis);
  }
  //  alert(rwindex);  
  var next_row = next_visible(rwindex,num>0?1:-1);
  var next_el;
  var ind = col?elts[1]:0;
  //go at least one cell
  var firstflag = 0;
  //keep going until we find a cell that works
  while (!firstflag || !next_el || !next_el.name || (next_el.parentNode.style.display == 'none')) {
    var next_el = next_row.cells[ind++].firstChild;
    firstflag = 1;
  }
  next_el.focus();
  if (is_input(next_el)) {
    next_el.select();
  }
  return true;
}

//hide cells which don't match either current cell or name_limit selection
function restrict_rows(elt,col,dir) {
  var match = null;
  if (typeof(elt) != 'function') {
    if (!elt) {
      alert("You cannot restrict on nothing!");
      return false;
    }
    match = get_value(elt);
  }
  old_restrict_cols = restrict_cols;
  if (col != null) {
    restrict_cols = new Array();
    restrict_cols[0] = col;
  }
  //there might be multiple columns we can match on
  var len = restrict_cols.length;
  //reset the first_row_vis
  first_row_vis = -1;
  var rowlen = rows_array.length;
  //go through rows, looking for matches
  for (var ii = 0; ii < rowlen; ii++) {
    var row1 = rows_array[ii];
    for (var jj = 0; jj < len; jj++) {
      var val = null;
      if (row1.cells[restrict_cols[jj] ].firstChild) {
        val = get_value(row1.cells[restrict_cols[jj] ].firstChild);
      }
      if ((match != null && val != null &&
           ((dir == null && val == match) ||
            (val-match)*dir >= 0)) ||
          (match == null && elt(val))) {
        break;
      }
    }
    //didn't find a match in this row? Hide it
    if (jj == len) {
      row1.style.display = 'none';
    }
    else {
      //is this the first visible row?  say so
      if (first_row_vis == -1)
        first_row_vis = ii;
      row1.style.display = '';
      //eventually this will be true
      last_row_vis = ii;
    }
  }
  restrict_cols = old_restrict_cols;
  if (elt.focus) {
    elt.focus();
  }
  //tell user what's being restricted on
  if (!restricted) {
    restrict_label_old = 
      document.getElementById("restrict_label").firstChild.nodeValue;
    //and how to get out of it
    document.getElementById("restrict_label").firstChild.nodeValue = 
      "CTRL-e to undo";
    restricted = true;
  }
  return true;
}

//does what you think -- makes everything visible, resets variables
function unrestrict_rows (){
  restricted = false;
  var len = rows_array.length;
  for (var ii = 0; ii < len; ii++) {
    rows_array[ii].style.display = '';
  }
  first_row_vis = 0;
  last_row_vis = num_rows-1;
  document.getElementById("restrict_label").firstChild.nodeValue = 
    restrict_label_old;
  return true;
}  

var prev_hide_state = Array();
function hide_allcols(elt) {
  var flag = (elt.id == 'checkhidetrue');
  if (elt.checked) {
    var style;
    if (flag) {
      style = 'none';
    }
    else {
      style = '';
    }
    var old_state = prev_hide_state.length;
    for (var ii = 0; ii < num_cols; ii++) {
      if (!old_state) {
        prev_hide_state[ii] = document.getElementById('checkhide'+ii).checked;
      }
      document.getElementById('checkhide'+ii).checked = !flag;
      hide_col(ii,style);
    }
    document.getElementById('checkhide' + !flag).checked = false;
  }
  else {
    var style;
    for (var ii = 0; ii < num_cols; ii++) {
      document.getElementById('checkhide'+ii).checked = prev_hide_state[ii];
      if (prev_hide_state[ii]) {
        style = '';
      }
      else {
        style = 'none';
      }
      hide_col(ii,style);
    }
    prev_hide_state = Array();
  }
}

//hide a column, flag is passed in inline handler
function hide_col (ii, flag) {
  var len = rows_array.length;
  //we need to hide the iith cell in each row individually
  for (var ind = 0; ind < len; ind++) {
    rows_array[ind].cells[ii].style.display = flag;
  }
  //don't forget to hide the header cell
  header_row.cells[ii].style.display = flag;
  return true;
}
                    
//what it sounds like
function add_row() {
  //make new row element
  var new_row = document.createElement('tr');
  var ii;
  for (ii = 0; ii < num_cols; ii++) {
    //new cell and cell input
    var new_td = document.createElement('td');
    var new_in;
    if (col_styles[ii] == 'textarea') {
      new_in = document.createElement('textarea');
    }
    else {
      new_in = document.createElement('input');
    }
    new_in.id = "cell-" + num_rows + "-" + ii;
    new_in.name = new_in.id;
    var jj;
    //if this column has a particular style, apply it
    if (col_styles[ii]) {
      if (col_styles[ii] == 'checkbox') {
        new_in.type = 'checkbox';
      }
      else {
        new_in.className = col_styles[ii];
      }
    }
    new_in.setAttribute("autocomplete","off");
    //blank value
    new_in.setAttribute('value','');
    new_in.onblur = blur_handler;
    new_in.onchange = change_handler;
    new_in.onblur = blur_handler;
    new_in.onfocus = focus_handler;
    new_td.appendChild(new_in);
    new_td.style.display = header_row.cells[ii].style.display;
    new_row.appendChild(new_td);
  }
  //there's one more cell, which normally contains a delete
  //checkbox, but we can't reliably delete cells which were
  //just created, and I don't care enough to make a workaround
  var new_td = document.createElement('td');
  new_row.appendChild(new_td);
  //color this row, since it's new, and so should be red
  color_row(new_row,"red");
  tbody_elt.appendChild(new_row);
  change_array[num_rows] = 1;
  //if user submits page without javascript, this row will be counted
  document.getElementById('num_rows').value = ++num_rows;
  last_row_vis = num_rows-1;
  statustext.innerHTML = "Press CTRL-s to save your work.";
}

function check_changed() {
  if (!deleted_rows.length && !change_array.length) {
    statustext.innerHTML = "Ready -- remember to reload page (CTRL-F5) before you start editing";
    return false;
  }
  else {
    statustext.innerHTML = "Press CTRL-s to save your work.";
    return true;
  }
}

//mark/unmark row for deletion, called by inline handler
function del_mark(elt) {
  var flag = false;
  var ii;
  var rownum = elt.parentNode.parentNode.rowIndex-1;
  var rw = rows_array[rownum];
  if (elt.checked) {
    deleted_rows[rownum] = 1;
    color_row(rw,"blue");
    statustext.innerHTML = "Press CTRL-s to save your work.";
  }
  else {
    deleted_rows.splice(rownum,1);
    color_row(rw,"black");
    check_changed();
  }
}

//if nothing else is registered, this is called on changes
function default_change_handler (elt) {
  if (!elt.style && elt.target) {
    elt = elt.target;
  }
  else if (!elt.style && elt.srcElement) {
    elt = elt.srcElement;
  }
  else if (!elt.style && !this.screen) {
    elt = this;
  }
  //changed elements are colored red
  elt.style.color = "red";
  elt.style.border.color = "red";
  var elts = get_cell(elt);
  if (is_input(elt) && !is_checkbox(elt)) {
    var theRules = get_css_rules();
    var style_ind = Number(elts[1])*2;
    var wid = get_style(theRules[style_ind],'width');
    if (wid.substr(0,wid.length-2) < get_value(elt).length/2) {
      set_style(theRules[style_ind],'width',
                get_value(elt).length/2 + 'em');
      if (elt.className == 'member_name') {
        theRules = get_css_rules(document.styleSheets.length-2);
        ind = 0;
      }
      else {
        ind = Number(style_ind)+Number(1);
      }
      set_style(theRules[ind],'width',
                get_value(elt).length/2 + 'em');
      set_value(elt,get_value(elt));
    }
  }
  var rwindex = elt.parentNode.parentNode.rowIndex-1;
  //get element describing this row's changes
  var ch = change_array[rwindex];
  if (ch) {
    //is this an array?
    if (ch.length) {
      ch[ch.length] = elt.id;
    }
    //else whole row is being done, so don't bother adding this
  }
  else {
    //first change of this row
    change_array[rwindex] = new Array(1);
    change_array[rwindex][0]=elt.id;
  }
  statustext.innerHTML = "Press CTRL-s to save your work.";
  return true;
}

function name_check(elt) {
  //don't worry if contents are empty or dummy_string
  if (!elt.value || elt.value == dummy_string) {
    return true;
  }
  var cur_text = elt.value.toLowerCase();
  var ok = false;
  //can we find this name?
  for (ind in name_array) {
    var nam = name_array[ind];
    if (nam.toLowerCase().indexOf(cur_text) == "0" &&
	cur_text.indexOf(nam.toLowerCase()) == "0") {
      ok = true;
      break;
    }
  }
  if (!ok) {
    //couldn't find?  Warn user
    alert(elt.value + " is not in the database.");
    return false;
  }
  return true;
}

var temptemp = 0;
//if nothing else is registered, this is called on blurs
function default_blur_handler(elt) {
  if (!elt.style && elt.target) {
    elt = elt.target;
  }
  else if (!elt.style && elt.srcElement) {
    elt = elt.srcElement;
  }
  else if (!elt.style && !this.screen) {
    elt = this;
  }
  if (is_nameinput(elt)) {
    //make sure name is in the database
    name_check(elt);
  }
  return true;
}

//if nothing else is registered, this is called on focuses
function default_focus_handler(elt) {
}

//utility function, gives url-encoded value of any html element I need
function val_of(thing,idflag) {
  if (!thing) {
    return "";
  }
  if (idflag) {
    thing = document.getElementById(thing);
  }
  return encodeURIComponent(get_value(thing));
}

//takes all data, wraps it up, and sends it to update php script
//this is completely incompatible with IE
function submit_data () {
  //if we're in the middle of a request, ignore
  if (req != 0) {
      return false;
  }
  //backup changed and deleted arrays
  ch_array_copy = new Array();
  for (ii in change_array) {
    ch_array_copy[ii] = change_array[ii];
  }
  change_array = [];
  del_rows_copy = new Array();
  for (ii in deleted_rows) {
    del_rows_copy[ii] = deleted_rows[ii];
  }
  deleted_rows = [];
  //initialize request
  req = new XMLHttpRequest();
  //register handler, so when something happens, we know.  function below
  req.onreadystatechange = processReqChange;
  req.open("POST",update_db_url,true);
  req.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  //start encapsulating data
  var data = "";
  var ii;
  var jj;
  //tell update_db we're using javascript, so it can be smart about sql
  data += "js_flag=1&";
  var len = ch_array_copy.length;
  //each changed row is a separate array, so there are lots of 
  //changed_cells_ii arrays passed through
  for (ii in ch_array_copy) {
    var ch = ch_array_copy[ii];
    //were individual cells changed?
    if (ch.length) {
      for (jj = 0; jj< ch.length; jj++) {
        var elts = ch[jj].split("-");
        data += "changed_cells_" + ii + "[]=" + elts[2] + "&";
        data += "cell-" + ii + "-" + elts[2] + "=" + 
          val_of(rows_array[ii].cells[elts[2]].firstChild) + "&";
      }
    }
    else {
      //whole row modified?
      data += "changed_cells_" + ii + "=1&";
      for (jj = 0; jj < num_cols; jj++) {
        data += "cell-" + ii + "-" + jj + "=" + 
          val_of(rows_array[ii].cells[jj].firstChild) + "&";
      }
    }
    data += "autoid-" + ii + "=" + 
      val_of(rows_array[ii].cells[num_cols].firstChild) + "&";
  }
  //throw in deleted rows
  if (!del_rows_copy.length) {
    data += "deleted_rows[]=&";
  }
  else {
    for (ii in del_rows_copy) {
      data += "deleted_rows[]=" + ii + "&";
      data += "autoid-" + ii + "=" + 
        val_of(rows_array[ii].cells[num_cols].firstChild) + "&";
    }
  }
  // we send the data more efficiently above
//   //send through changed cells, no arrays
//     for (ii = 0; ii < num_rows; ii++) {
//     for (jj = 0; jj < num_cols; jj++) {
//       data += "cell-" + ii + "-" + jj + "=" + 
//         val_of(rows_array[ii].cells[jj].firstChild) + "&";
//     }
//     //autoids are passed separately for clarity, still not in an array
//     data += "autoid-" + ii + "=" + 
//       val_of(rows_array[ii].cells[num_cols].firstChild) + "&";
//   }
  //tell it number of rows, columns, names of columns, name of table
  data += "num_rows=" + num_rows + "&";
  data += "num_cols=" + num_cols + "&";
  var cols = header_row.cells;
  for (ii = 0; ii < num_cols; ii++) {
    data += "col_names[]=" + val_of(document.getElementById('col_name_' + ii)) + "&";
  }
  data += "table_name=" + escape(table_name);
  //statustext.innerHTML = data;
  req.send(data);
  return false;
}

//this function gets called every time req changes status, because it
//was registered above in submit_data
function processReqChange () {
  if (req == 0) {
    statustext.innerHTML = "Ready -- remember to reload page (CTRL-F5) before you start editing";
    return false;
  }
  //let user know what's up
  if (req.readyState == 1) {
    statustext.innerHTML = "Loading update...";
  }
  else if (req.readyState == 2) {
    statustext.innerHTML = "Loaded update request...";
  }
  else if (req.readyState == 3) {
    statustext.innerHTML = "Receiving data...";
  }
  else if (req.readyState == 4) {
    if (req.status == 200) {
      //update_db.php sends back no text on success
      if (req.responseText) {
	alert("An error has occured.  The text follows:\n" + 
              req.responseText);
	//revert to previous data, since the updates weren't successful
	//really the arrays could just be copied, but just in case
	//for each old changed index,
	for (ii in ch_array_copy) {
	  //if we don't have that index anymore, 
          //or it was the whole row, just set it here
	  if (!change_array[ii] || !ch_array_copy[ii].length) {
	    change_array[ii] = ch_array_copy[ii];
	    continue;
	  }
	  //if the user has changed the whole row anyway, continue
	  if (!change_array[ii].length) {
	    continue;
	  }
	  //merge changes user made since updating with the ones before
	  var ch = ch_array_copy[ii];
	  var arr = change_array[ii];
	  for (jj in ch) {
	    arr[arr.length] = ch[jj];
	  }
	}
	//deleted rows are easier
	for (ii in del_rows_copy) {
	  deleted_rows[ii] = 1;
	}
      }
      else {
	//success!
	statustext.innerHTML = "Table updated!";
	//the page is no longer reliable if rows were added or deleted
	var flag = false;
	for (ii in ch_array_copy) {
	  if (!ch_array_copy[ii].length) {
	    flag = true;
	  }
	}
	if (del_rows_copy.length || flag) {
	  alert("The page will now be reloaded, " +
                "since you deleted or added rows.");
	  location.reload(true);
	}
	for (ii in ch_array_copy) {
	  ch = ch_array_copy[ii];
          rows_array[ii].style.color = "black";
	  for (jj in ch) {
	    document.getElementById(ch[jj]).style.color = "black";
	  }
          for (jj =0; jj<num_cols; jj++) {
            rows_array[ii].cells[jj].style.color = "black";
	}
        }
        hide_elts = document.getElementsByTagName("div");
        hide_elts = document.getElementsByTagName("div");
        for (var ii in hide_elts) {
          if (hide_elts[ii].className) {
            var classes = hide_elts[ii].className.split(" ");
            for (var jj in classes) {
              if (classes[jj] == 'update_hide') {
                hide_elts[ii].style.display = 'none';
                continue;
              }
            }
          }
        }
      }
    }
    else {
      //didn't get back a normal HTML response
      statustext.innerHTML = 
	"There was an error communicating with the server: " + req.status + 
	": " + req.responseText;
    }
    req = 0;
    statustext.innerHTML = "Ready -- remember to reload page (CTRL-F5) before you start editing";
    if (typeof(change_text_on_update) != 'undefined') {
      document.getElementById('change_text_on_update').innerHTML = change_text_on_update;
    }
  }
}

//called when user navigates away from page, warns them about loss of data
function process_unload() {
  if (check_changed()) {
    if (confirm('Do you want to submit your unsaved data?')) {
      submit_data();
      return false;
    }
  }
}

//called before a user navigates away, allowing them not to
function process_beforeunload() {
  if (check_changed()) {
    return "You have unsaved data.  If you press OK, you will be prompted to " +
      "save your data.  If you are reloading the page, you may have to reload " + 
      "it again to see your changes.";
  }
}
//if php script has not already generated javascript handlers, register these
if (!self.focus_handler) {
  self.focus_handler = default_focus_handler;
}
if (!self.change_handler) {
  self.change_handler = default_change_handler;
}
if (!self.blur_handler) {
  self.blur_handler = default_blur_handler;
}

function color_rows() {
  var coloring = get_value_by_id('color_rows_checkbox');
  if (coloring) {
    var tempcol = row_color;
    var tempcol2 = other_color;
    var tempswap;
    for (var ii = 0; ii < num_rows; ii++) {
      rows_array[ii].style.backgroundColor = tempcol;
      if (!((ii+Number(1))%switch_color_frequency)) {
        tempswap = tempcol;
        tempcol = tempcol2;
        tempcol2 = tempswap;
      }
    }
  }
  else {
    for (var ii = 0; ii < num_rows; ii++) {
      rows_array[ii].style.backgroundColor = 'transparent';
    }
  }
}

      

var ASCEND;  
//sortable stuff
function ts_resortTable(lnk,clid) {
  if (change_array.length || deleted_rows.length) {
    alert("Sorry, you can currently only sort columns after you have saved " +
          "all changes.  Please save your work and try again.");
    return;
  }
    // get the span
  var _t0 = new Date();
  var span = lnk.nextSibling;
  var spantext = get_value(span);
    var td = lnk.parentNode;
    var column = clid || td.cellIndex;
    var table = document.getElementById('bodytable');
    
    // Work out a type for the column
    if (table.rows.length <= 1) return;
    if (col_sortable[clid]) {
      pre_process = window[col_sortable[clid]];
    }
    else {
      var itm = get_value(table.rows[1].cells[column]);
      sortfn = ts_sort_caseinsensitive;
      if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d\d\d$/)) sortfn = ts_sort_date;
      if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d$/)) sortfn = ts_sort_date;
      if (itm.match(/^[£$]/)) sortfn = ts_sort_currency;
      if (itm.match(/^[\d\.]+$/)) sortfn = ts_sort_numeric;
    }
    var firstRow = new Array();
    var newRows = new Array();
    for (i=0;i<table.rows[0].length;i++) { firstRow[i] = table.rows[0][i]; }
    for (j=1;j<table.rows.length;j++) { newRows[j-1] = new Array(pre_process(get_value(table.rows[j].cells[column])),table.rows[j],j); }
    if (span.getAttribute("sortdir") == 'down') {
        ARROW = '&nbsp;&nbsp;&uarr;';
        span.setAttribute('sortdir','up');
        ASCEND = -1;
    } else {
        ARROW = '&nbsp;&nbsp;&darr;';
        span.setAttribute('sortdir','down');
        ASCEND = 1;
    }
    newRows.sort(mysort);
    // We appendChild rows that already exist to the tbody, so it moves them rather than creating new ones
    // don't do sortbottom rows
    for (i=0;i<newRows.length;i++) {
      table.tBodies[0].appendChild(newRows[i][1]);
    }
    color_rows();
    cols = header_row.cells;
    for (var ii = 0; ii < num_cols; ii++) {
      if (cols[ii].childNodes &&
          cols[ii].childNodes[1] && cols[ii].childNodes[1].className &&
          cols[ii].childNodes[1].className == 'sortarrow' &&
          cols[ii].childNodes[1].innerHTML.charAt(0) == '*') {
        cols[ii].childNodes[1].innerHTML = cols[ii].childNodes[1].innerHTML.substring(1,cols[ii].childNodes[1].innerHTML.length);
      }
    }
    span.innerHTML = '*' + ARROW;
    sorting_flag = true;
}

function mysort(a,b) {
  if ((!a[0] || !a[0].length) && b[0]) {
    return 1;
  }
  if ((!b[0] || !b[0].length) && a[0]) {
    return -1;
  }
  if (a[0] === b[0]) {
    if (a[2] < b[2]) {
      return -1;
    }
    if (a[2] == b[2]) {
      return 0;
    }
    return 1;
  }
  if (a[0] < b[0]) {
    return -1*ASCEND;
  }
  return 1*ASCEND;
}

function pre_process_time(a) {
  if (!a) {
    return a;
  }
  //break up along :
  var arr = a.split(":");
  //nothing?  break up along spaces then
  if (arr.length < 2) {
    arr = a.split(' ');
  }
  //first part must be hour
  var hour = arr[0];
  var rest;
  //was there anything besides hour?
  if (arr.length >= 2) {
    //yes, so that's rest of it
    rest = arr[1];
  }
  else {
    //no, rest of it is whole thing -- no spaces
    rest = a;
  }
  minute = '00';
  if (rest != a) {
    //do we have a number?
    if (rest.match(/^\d\d/)) {
      //good, set minute to two digits of number
      minute = rest.substr(0,2);
    }
  }
  //is there an am?
  am = rest.indexOf('a');
  //no?
  if (am == -1) {
    pm = rest.indexOf('p');
    //is there a pm?
    if (pm != -1) {
      if (hour == a) {
        hour = a.substr(0,pm);
      }
      //add 12 to the hour unless it was 12
      if (hour < 12) {
        hour = Number(hour) + Number(12);
      }
    }
  }
  else {
    if (hour == a) {
      hour = a.substr(0,am);
    }
    if (hour == 12) {
      hour = '00';
    }
  }
  if (hour.length < 2) {
    hour = String('0') + String(hour);
  }
  return hour + minute;
}
function pre_process_date(a) {
  if (!a) {
    return a;
  }
  if ((tmp1 = a.indexOf('/')) >= 0) {
    var mo = a.substring(0,tmp1);
    if (mo.length == 1) {
      mo = '0' + mo;
    }
    var dy = a.substring(Number(tmp1),a.length);
    if (dy.length == 1) {
      dy = '0' + dy;
    }
    return mo+dy;
  }
  alert(a + ' is not a properly formatted date!');
  return a;
}

function pre_process_datetime(a) {
  if ((tmp1 = a.indexOf('M')) >= 0) {
    return pre_process_date(a.substring(Number(tmp1)+6,a.length)) + pre_process_time(a.substring(0,Number(tmp1)+1));
  }
  else {
    return a;
  }
}

function pre_process_day(a) {
  if (typeof(days_arr[a]) != "undefined") {
    return days_arr[a];
  }
  else {
    return "8";
  }
}

function pre_process_currency(a) {
  return pre_process_num(substring(a,1,a.length));
}

function pre_process_num(a) {
  a = parseFloat(a);
  if (!a && a!=0) {
    return a;
  }
  a = String(a);
  if ((tmp = a.indexOf('.')) == -1) {
    a += '.';
    tmp = a.length-1;
  }
  while (a.length-4 < tmp) {
    a += '0';
  }
  while (a.length < 7) {
    a = '0' + a;
  }
  return a;
}

function pre_process_default(a) {
  if (a && a.toLowerCase) {
    return a.toLowerCase();
  }
  return a;
}

function time_change_handler(elt) {
  if (!elt.style && elt.target) {
    elt = elt.target;
  }
  else if (!elt.style && elt.srcElement) {
    elt = elt.srcElement;
  }
  else if (!elt.style && !this.screen) {
    elt = this;
  }
  //call the table_edit change handler (turns things red)
  default_change_handler(elt);
  if (!elt.value)
    return true;
  if (elt.value.toLowerCase() == 'noon') {
    elt.value = '12 pm';
  }
  else if (elt.value.toLowerCase() == 'midnight') {
    elt.value = '12 am';
  }
  //no am/pm?
  else if (!elt.value.match(/[AaPp]/)) {
    //did the user give us a 24-hour time? wow
    if (elt.value.match(/^(1[3-9]|2\d|00)/)) {
      //add on the minutes if they're not there already
      if (elt.value.indexOf(":") == -1)
        elt.value = elt.value + ":00";
    }
    else {
      //assume a time is pm unless they say otherwise
      elt.value = elt.value + " pm";
    }
  }
  return true;
}
