//the number of columns to the left of shift assignments.
//Currently workshift,hours
var num_shift_mods = 2;
//we need to be able to go between numbers and days
var dayslist = ['Weeklong', 'Monday', 'Tuesday', 'Wednesday', 
		'Thursday','Friday','Saturday','Sunday'];

//alert all errors at the end of an action, instead in multiple alert boxes
var accumulated_errors = '';
function store_alert(txt) {
    if (txt) {
        accumulated_errors += "\n" + txt;
    }
}

function print_alerts() {
    if (accumulated_errors) {
        alert(accumulated_errors);
        accumulated_errors = '';
    }
}

//dummy function so we can call offer_options even if there's no
//assign_shifts.php sitting on top of us
function offer_options(elt,elt2,elt3,elt4) {
}

//how many hours is this shift worth?
function hours_of(elt) {
  return elt.parentNode.parentNode.childNodes[num_shift_mods-1].firstChild.value;
}

//return all info about a workshift element -- shift name, day (as a number),
//start time, end time, hours it's worth, category, and id
function workshift_of(elt) {
  var row = elt.parentNode.parentNode.childNodes;
  var elts = get_cell(elt);
  var day = elts[1]-num_shift_mods;
  //non-day elements all get -1 as a flag
  if (day < 0 || day > 7) {
    day = -1;
  }
    var workshift = new Object();
    workshift['id'] = get_value(row[row.length-2]);
    workshift['name'] = get_value(row[0]);
    workshift['hours'] = get_value(row[num_shift_mods-1]);
    workshift['day'] = day;
    workshift['start'] = get_value(row[Number(8)+num_shift_mods]);
    workshift['end'] = get_value(row[Number(9)+num_shift_mods]);
    workshift['cat'] = get_value(row[Number(10)+num_shift_mods]);
    return workshift;
}

//is this a cell of hours a shift is worth?
function is_hoursinput(elt) {
  if (!is_input(elt)) {
    return false;
  }
  return is_class(elt,'hours');
}

var unassigned_hours = document.getElementById('unassigned_hours');
var assigned_hours = document.getElementById('assigned_hours');
var total_hours = document.getElementById('total_hours');

//change how many hours a member is assigned
function alter_hours(member, newhours) {
  if (member && member != dummy_string) {
    set_value(assigned_hours,Number(get_value(assigned_hours))+Number(newhours));
    set_value(total_hours,Number(get_value(total_hours))+Number(newhours));
  }
  else if (member) {
    ;
  }
  else {
    set_value(unassigned_hours,Number(get_value(unassigned_hours))+Number(newhours));
    set_value(total_hours,Number(get_value(total_hours))+Number(newhours));
  }
  if (!member || member == dummy_string) {
    return false;
  }
  if (typeof(hourslist[member]) != 'undefined') {
    hourslist[member] += Number(newhours);
  }
  else {
    hourslist[member] = newhours;
  }
  //if there's a sidebar, change what it says
  if (parent != self) {
    parent.display_hours(member, hourslist[member],newhours);
    parent.people.sort_list(2);
  }
  return true;
}

//function which translates the codes for member's not being free into
//human-readable times.  busyday is the number code
function display_busy(busyday,vals,st,nd) {
  var retval = '';
  //says whether we're in a free period or a busy period
  var am_free = true;
  //go through all hours in the day (starting at 8 am)
  for (var ii = st; ii < nd; ii++) {
    if ((vals.indexOf(busyday[ii],0) > -1) ^ !am_free) {
      am_free = !am_free;
      //are we starting a busy period?
      if (!am_free) {
        retval += (Number(ii)+Number(8)) + ':00-';
      }
      //are we ending a busy period?
      else {
        retval += (Number(ii)+Number(8)) + ':00, ';
      }
    }
  }
  if (retval.charAt(retval.length-1) == '-') {
    retval += Number(nd)+Number(8)+':00';
  }
  else {
    retval = retval.substring(0,retval.length-2);
  }
  return retval;
}

//full shift name -- includes floor and hours
function shift_string(workshift) {
  return workshift['id'] + workshift['day'];
}

//assign a shift to a member, or remove a shift from a member, depending if
//addshift is true or false.  silent says whether can_do() warns about problems.
function assign_shift(member, workshift, addshift, silent) {
  if (typeof(hourslist[member]) == 'undefined') {
    alter_hours(member,(addshift?1:-1)*workshift['hours']);
    return false;
  }
    var no_alert = true;
    if (typeof(silent) == 'undefined') {
        no_alert = false;
        silent = false;
    }
    var silent = silent || parent.suppress_all;
    //adding a shift?
    if (addshift) {
    //can we actually add this shift? temp will now have the times this shift
    //is being done
    var temp = can_do(member,workshift,false,silent);
    if (typeof(shiftlist[member]) != 'undefined') {
        shiftlist[member][shift_string(workshift)] = temp;
    }
    //member is no longer free during these times, unless it's a weeklong
    if (typeof(busylist[member]) != 'undefined' && workshift['day'] > 0 &&
        temp && temp[0] && temp[1]) {
      for (var ii = temp[0]; ii <= temp[1]; ii++) {
        busylist[member][workshift['day']-1][ii] = 3;
      }
    }
    //add in the new hours
    alter_hours(member,workshift['hours']);
  }
  //dropping a shift?
  else {
    //does the member actually have this shift?
      if (shiftlist[member] && shiftlist[member][shift_string(workshift)]) {
      //put back those available hours, unless it's a weeklong
      if (workshift['day'] > 0) {
        var temp = shiftlist[member][shift_string(workshift)];
        for (var ii = temp[0]; ii <= temp[1]; ii++) {
          busylist[member][workshift['day']-1][ii] = origbusylist[member][workshift['day']-1][ii];
        }
      }
      //get rid of the shift
      delete (shiftlist[member][shift_string(workshift)])
    }
    //change the hours assigned
    alter_hours(member,-workshift['hours']);
  }
    if (!no_alert) {
        print_alerts();
    }
  return true;
}

function find_match(wanteds,workshift) {
    if (typeof(wanteds[workshift['id']]) != 'undefined') {
        return Array(workshift['id'],wanteds[workshift['id']][0],wanteds[workshift['id']][1]);
    }
    if (typeof(wanteds[workshift['cat']]) != 'undefined') {
        return Array(workshift['cat'],wanteds[workshift['cat']][0],wanteds[workshift['cat']][1]);
    }
    return null;
}

//big function -- can this member do this workshift?  listing is whether or not
//we're just making a list of people who can do the shift.  
//silent is whether or not to complain if there is a problem
function can_do(member,workshift,listing,silent) {
  if (!member || member == dummy_string || 
      (typeof(hourslist[member]) == 'undefined' && 
       typeof(wantedlist[member]) == 'undefined' &&
       typeof(busylist[member]) == 'undefined')) {
    return true;
  }
  if (typeof(silent) == 'undefined') {
    silent = false;
  }
  var shift_time = true;
  var success = true;
  //about to assign too many hours?
  if (typeof(hourslist[member]) != 'undefined' &&
      Number(hourslist[member]) + Number(workshift['hours']) > weekly_hours_quota) {
    if (listing) {
      return false;
    }
    else if (!silent) {
      store_alert(member + " has " + 
            (Number(hourslist[member])+Number(workshift['hours'])) + 
            " hours assigned.");
      success = false;
      
    }
  }

  if (typeof(wantedlist[member]) != 'undefined') {
      pref = find_match(wantedlist[member],workshift);
      
    //did they not even want this shift?
      if (pref && !pref[0]) {
          if (listing) {
              return 0;
          }
          else {
              if (!silent) {
                  store_alert(member + " has unwanted preference " + pref[0] +
                              " which seems to conflict with " + workshift['name']);
                  success = 0;
          }
        }
      }
  }
    //this is hard -- figure out when the person can do the shift
    if (typeof(busylist[member]) == 'undefined') {
        return success;
    }
    if (workshift['day'] == 0) {
        return success;
    }
  var busyday = busylist[member][workshift['day']-1];
  var start_time = workshift['start'];
  var end_time = workshift['end'];
  //is there no start time or end time, or they're nonsensical?
  if (!start_time || !end_time || (start_time == end_time)) {
    return success;
  }
  //get the hoursminutes and am/pm part of the start_time
  var ampm = start_time.split(' ');
  //get the hours and minutes separately
  var hrmin = ampm[0].split(':');
  //is there a pm and the hours is less than 12?  We want 24-hour time
  if (hrmin[0] < 12 && ampm[1] && ampm[1].match(/^p/i)) {
    hrmin[0] = Number(hrmin[0]) + Number(12);
  }
  //translate into the coded availabilities, which start at 8 am
  var st = hrmin[0] - 8;
  //do the same thing with end_time
  ampm = end_time.split(' ');
  hrmin = ampm[0].split(':');
  if (hrmin[1]) {
    hrmin[0]++;
  }
  if (hrmin[0] < 12 && ampm[1] && ampm[1].match(/^p/i)) {
    hrmin[0] =Number(hrmin[0]) + Number(12);
  }
  var nd = hrmin[0] - 8;
  //maybe we end the next day
  if (nd <= st) {
    nd+=24;
  }
  //round hours up
  var hours = Math.ceil(workshift['hours']);
  if (hours > (nd-st)) {
    if (!listing && !silent) {
      store_alert("The system cannot calculate busy times for " + workshift['name'] +
            ", since the start time of " + start_time + " and end time of " +
            end_time + " are so close together.");
    }
    return [st,nd];
  }
  for (var tryct = 0; tryct < 2; tryct++) {
      var msg;
    if (tryct == 1 && !listing && !silent) {
        msg = member + " would prefer not to work on " + dayslist[workshift['day'] ] + ": " + 
        display_busy(busyday,'23',st,nd) + " which conflicts with " + 
                    workshift['name'] + " (possibly taking into account some busy times)";
    }
    //if a shift starts before the availability schedule starts, check to see if
    //it can be done right at the beginning.  If it doesn't fit, push the start
    //time up to 0
    if (st < 0) {
      for (var ii = 0; ii < hours+st; ii++) {
        if (busyday[ii] == 3 || (tryct == 0 && busyday[ii] == 2)) {
          break;
        }
      }
      if (ii == hours+st) {
          store_alert(msg);
          return [0,ii];
      }
      else {
        st = 0;
      }
    }
    //same thing for end time
    if (nd > 16) {
      for (var ii = 15; ii > nd-hours; ii--) {
        if (busyday[ii] == 3 || (tryct == 0 && busyday[ii] == 2)) {
          break;
        }
      }
      if (ii == nd-hours) {
          store_alert(msg);
        return [ii,15];
      }
      else {
        nd = 16;
      }
    }
    var startii = st;
    //try shifting the start time of the workshift over until it matches a free
    //time slot in the member's schedule.  Do this while the shift still hasn't
    //hit the end time
    for (var ii = st; ii < nd; ii++) {
      if (busyday[ii] == 3 || (!tryct && busyday[ii] == 2)) {
        startii = -1;
        continue;
      }
      if (startii == -1) {
        startii = ii;
      }
      if (ii+1-startii >= hours) {
        return [startii,ii+1];
      }
    }
  }
  //did we fail?
  if (!listing && !silent) {
      store_alert(member + " is busy on " + dayslist[workshift['day'] ] + ", " + 
            display_busy(busyday,'3',st,nd) + " which conflicts with " + 
            workshift['name']);
      return false;
  }
    store_alert(msg);
  return false;
}

//change who was assigned to a shift
function shift_change(oldmem,newmem,hours,workshift) {
  assign_shift(newmem,workshift,true);
  assign_shift(oldmem,workshift,false);
  return true;
}

//change how many hours a workshift is worth
function hours_change(elt,workshift,prev_val, interval_change) {
  var row = elt.parentNode.parentNode.childNodes;
  for (var ii = 0; ii < 8; ii++) {
    workshift['day'] = ii;
    var temp = workshift['hours'];
    if (typeof(interval_change) == 'undefined') {
      if (typeof(prev_val) != 'undefined') {
        workshift['hours'] = prev_val;
      }
      else {
        workshift['hours'] = 0;
      }
    }
    //remove the old workshift
    assign_shift(row[ii+3].firstChild.value,workshift,false);
    //add in the new workshift
    workshift['hours'] = temp;
    assign_shift(row[ii+3].firstChild.value,workshift,true);
  }
}

//stores previous value of cell for use by change_handler later
var prev_val;

//what happens which someone clicks on an element?  this replaces the default
//handler in table_edit.php.  Note that offer_options is a dummy unless this
//is a frame in assign_shifts.php.  If this is a frame, the other frame should
//only have names which can do this shift, and they should be formatted.
function focus_handler(elt) {
  //for the change handler
  prev_val = get_value(elt);
  //  if (is_nameinput(elt)) {
  //offer options for every cell -- it's a nameinput if the day is >= 0 in workshift_of
  parent.offer_options(elt.value,hours_of(elt),workshift_of(elt));
  //  }
  return elt;
}

//what happens when an element loses focus?  many things.  This replaces the
//default handler in table_edit.php
function change_handler(elt) {
  //if it's a time element, we have to format it properly
  if (is_timeinput(elt)) {
    if (!elt.value)
      return elt;
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
    //did we change when the shift started/ended??  check to see if everyone can
    //still do the shift
    hours_change(elt,workshift_of(elt), null, true);
    return elt;
  }
  //did we change the hours?  update the people doing the shift
  if (is_hoursinput(elt)) {
    if (elt.value != prev_val) {
      hours_change(elt,workshift_of(elt),prev_val);
    }
    return elt;
  }
  //did we change the name?
  if (is_nameinput(elt)) {
    //if it's now the dummy string, grey it out
    if (elt.value == dummy_string) {
      elt.style.backgroundColor = 'grey';
    }
    //ungrey it, if it was greyed
    else {
      elt.style.backgroundColor = '';
      //if we're in a weeklong cell and we've opened it up or put
      //something in when it used to be blank, then gray out the other
      //cells, assuming they are blank, and haven't been changed.
      //Likewise, gray out the weeklong cell, assuming it is blank, if
      //another cell is changed
      var coords = get_cell(elt);
      //only gray out other cells if we just changed this cell from blank
      //or if it used to be dummy_string
      if (!prev_val || prev_val == dummy_string) {
        if (coords[1] == num_shift_mods) {
          var changed = '';
          for (var jj = num_shift_mods+1; jj < num_shift_mods+8; jj++) {
            this_elt = get_cell_elt(coords[0],jj);
            if (!get_value(this_elt)) {
              set_value(this_elt,dummy_string);
              change_handler(this_elt);
              if (changed.length) {
                changed += ', ';
              }
              changed += dayslist[jj-num_shift_mods];
            }
          }
        }
        else {
          wklng_elt = get_cell_elt(coords[0],num_shift_mods);
          if (!get_value(wklng_elt)) {
            set_value(wklng_elt,dummy_string);
            change_handler(wklng_elt);
          }
        }
      }
    }
    //change whose shift this is, if it did change
    shift_change(prev_val,elt.value,hours_of(elt),workshift_of(elt));
  }
  return elt;
}

function initialize_master_shifts() {
  //if co, zero out houses too
  if (parent != self && typeof(parent.zero_house_hours) != 'undefined') {
    parent.zero_house_hours();
  }
  //go through the whole document, updating all the shifts
  //var num_rows = rows_array.length;
  for (var ii = 0; ii < num_rows; ii++) {
    var cur_row = rows_array[ii];
    var workshift;
    for (var jj = 0; jj < 8; jj++) {
        var elt = cur_row.childNodes[Number(jj)+num_shift_mods].firstChild;
      var member = elt.value;
      //get the full workshift array if it's the first time
      if (jj == 0) {
        workshift = workshift_of(elt);
      }
      //otherwise, all that's changed is the day, so change that
      else {
        workshift['day'] = jj;
      }
      //assign the shift, suppressing warnings from being printed
      assign_shift(member,workshift,true,parent.suppress_first);
    }
  }
    if (!parent.suppress_first) {
        print_alerts();
    }
  //update the hours if we're in a frame
  //  if (parent != self) {
  //  for (var member in hourslist) {
  //    parent.display_hours(member,hourslist[member],hourslist[member]);
  //  }
  //}
}

//decode 8*rownum+colnum into an element
function elt_of(ind) {
  var col_num = ind % 8;
    return rows_array[(ind-col_num)/8].childNodes[Number(num_shift_mods)+col_num].firstChild;
}

function delete_row_handler(elt) {
  default_delete_row_handler(elt);
  var rownum = elt.parentNode.parentNode.rowIndex-1;
  //num_shift_mods-1 should be the hours field, i hope
  var hours = get_value(get_cell_elt(rownum,num_shift_mods-1));
  for (ii = num_shift_mods; ii <= 7+num_shift_mods; ii++) {
    var cell = get_cell_elt(rownum,ii);
    var mem = get_value(cell);
    if (mem == dummy_string) {
      continue;
    }
    if (mem && mem.length) {
      assign_shift(mem,workshift_of(cell),!elt.checked);
    }
    else {
      set_value(unassigned_hours,Number(get_value(unassigned_hours))+
                (elt.checked?-1:1)*Number(hours));
      set_value(total_hours,Number(get_value(total_hours))+
                (elt.checked?-1:1)*Number(hours));
    }
  }
  return elt;
}

function copy_last_row() {
  add_row();
  var last_row = rows_array[num_rows-1];
  var penult_row = rows_array[num_rows-2];
  for (ii = 0; ii < num_cols; ii++) {
    var newval = get_value(penult_row.childNodes[ii].firstChild);
    var oldval = get_value(last_row.childNodes[ii].firstChild);
    set_value(last_row.childNodes[ii].firstChild,newval);
    if (newval != oldval) {
      event = document.createEvent("HTMLEvents");
      event.initEvent("change", true, true);
//      event.originalTarget = last_row.childNodes[ii].firstChild;
      last_row.childNodes[ii].firstChild.dispatchEvent(event);
    }
  }
}
    