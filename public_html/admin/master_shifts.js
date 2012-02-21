var num_shift_mods = 1;
//we need to be able to go between days and numbers
var listdays = new Array();
listdays['Monday'] = 0;
listdays['Tuesday'] = 1;
listdays['Wednesday'] = 2;
listdays['Thursday'] = 3;
listdays['Friday'] = 4;
listdays['Saturday'] = 5;
listdays['Sunday'] = 6;

//and back again
var dayslist = ['Weeklong', 'Monday', 'Tuesday', 'Wednesday', 
		'Thursday','Friday','Saturday','Sunday'];

var accumulated_errors = '';

function store_alert(txt) {
    accumulated_errors += "\n" + txt;
}

function print_alerts() {
    if (accumulated_errors) {
        alert(accumulated_errors);
        accumulated_errors = '';
    }
}

//dummy function so we can call offer_options even if there's no assign_shifts.php
//sitting on top of us
function offer_options(elt,elt2,elt3,elt4) {
}

var prev_val;
//how many hours is this shift worth?
function hours_of(elt) {
  return elt.parentNode.parentNode.childNodes[2].firstChild.value;
}

//return all info about a workshift element -- shift name, day (as a number),
//start time, end time, and hours it's worth
function workshift_of(elt) {
  var row = elt.parentNode.parentNode.childNodes;
  var elts = get_cell(elt);
  var floor = get_value(row[1]);
  if (!floor.length) {
    floor = '';
  }
  var shift_name = get_value(row[0]);
  var day = elts[1]-num_shift_mods-2;
  //non-day elements all get -1 as a flag
  if (day < 0 || day > 7) {
    day = -1;
  }
  return new Array(shift_name,
                   day,
                   floor,
                   get_value(row[Number(10)+num_shift_mods]),
                   get_value(row[Number(11)+num_shift_mods]),
                   get_value(row[Number(1)+num_shift_mods]),
                   get_value(row[Number(12)+num_shift_mods]));
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
  return workshift[0] + workshift[1] + workshift[2] + workshift[6];
}

//assign a shift to a member, or remove a shift from a member, depending if
//addshift is true or false.  silent says whether can_do() warns about problems.
function assign_shift(member, workshift, addshift, silent) {
  if (typeof(hourslist[member]) == 'undefined') {
    alter_hours(member,(addshift?1:-1)*workshift[5]);
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
    var temp2 = shift_string(workshift);
    if (typeof(shiftlist[member]) != 'undefined') {
      shiftlist[member][temp2] = temp;
    }
    //member is no longer free during these times, unless it's a weeklong
    if (typeof(busylist[member]) != 'undefined' && workshift[1] > 0 &&
        temp && temp[0] && temp[1]) {
      for (var ii = temp[0]; ii <= temp[1]; ii++) {
        busylist[member][workshift[1]-1][ii] = 3;
      }
    }
    //add in the new hours
    alter_hours(member,workshift[5]);
  }
  //dropping a shift?
  else {
    //does the member actually have this shift?
    if (shiftlist[member] && shiftlist[member][shift_string(workshift)]) {
      //put back those available hours, unless it's a weeklong
      if (workshift[1] > 0) {
        var temp = shiftlist[member][shift_string(workshift)];
        for (var ii = temp[0]; ii <= temp[1]; ii++) {
          busylist[member][workshift[1]-1][ii] = origbusylist[member][workshift[1]-1][ii];
        }
      }
      //get rid of the shift
      delete (shiftlist[member][shift_string(workshift)])
    }
    //change the hours assigned
    alter_hours(member,-workshift[5]);
  }
    if (!no_alert) {
        print_alerts();
    }
  return true;
}

//does a preference (which might have blank days, etc.) match a given workshift?
function shift_match(pref,workshift) {
  return pref[0] == workshift[0] && 
    (!pref[1].length || workshift[1] < 0 ||
     pref[1].indexOf(dayslist[workshift[1] ]) != -1) &&
    (!pref[2].length ||
     pref[2].indexOf(workshift[2]) != -1);
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
      Number(hourslist[member]) + Number(workshift[5]) > weekly_hours_quota) {
    if (listing) {
      return false;
    }
    else if (!silent) {
      store_alert(member + " has " + 
            (Number(hourslist[member])+Number(workshift[5])) + 
            " hours assigned.");
      success = false;
      
    }
  }
  if (typeof(wantedlist[member]) != 'undefined') {
    //did they not even want this shift?
    var unwanted = wantedlist[member][0];
    for (var ind in unwanted) {
      if (shift_match(unwanted[ind],workshift)) {
        if (listing) {
          return 0;
        }
        else {
          if (!silent) {
            var tempstr = '';
            for (var ii=0; ii < unwanted[ind].length; ii++) {
              tempstr += unwanted[ind][ii] + ", ";
            }
            store_alert(member + " has unwanted preference: " + tempstr +
                  " which seems to conflict with " + workshift[0]);
            success = 0;
          }
        }
      }
    }
  }
  //this is hard -- figure out when the person can do the shift
  if (typeof(busylist[member]) == 'undefined') {
    return success;
  }
  if (workshift[1] == 0) {
    return success;
  }
  var busyday = busylist[member][workshift[1]-1];
  var start_time = workshift[3];
  var end_time = workshift[4];
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
  var hours = Math.ceil(workshift[5]);
  if (hours > (nd-st)) {
    if (!listing && !silent) {
      store_alert("The system cannot calculate busy times for " + workshift[0] +
            ", since the start time of " + start_time + " and end time of " +
            end_time + " are so close together.");
    }
    return [st,nd];
  }
  for (var tryct = 0; tryct < 2; tryct++) {
    if (tryct == 1 && !listing && !silent) {
        store_alert(member + " would prefer not to work on " + dayslist[workshift[1] ] + ": " + 
        display_busy(busyday,'2',st,nd) + " which conflicts with " + 
                    workshift[0] + " (possibly taking into account some busy times)");
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
      store_alert(member + " is busy on " + dayslist[workshift[1] ] + ", " + 
            display_busy(busyday,'3',st,nd) + " which conflicts with " + 
            workshift[0]);
      return false;
  }
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
    workshift[1] = ii;
    var temp = workshift[5];
    if (typeof(interval_change) == 'undefined') {
      if (typeof(prev_val) != 'undefined') {
        workshift[5] = prev_val;
      }
      else {
        workshift[5] = 0;
      }
    }
    //remove the old workshift
    assign_shift(row[ii+3].firstChild.value,workshift,false);
    //add in the new workshift
    workshift[5] = temp;
    assign_shift(row[ii+3].firstChild.value,workshift,true);
  }
}

//what happens which someone clicks on an element?  this replaces the default
//handler in table_edit.php.  Note that offer_options is a dummy unless this
//is a frame in assign_shifts.php.  If this is a frame, the other frame should
//only have names which can do this shift, and they should be formatted.
function focus_handler(elt) {
  if (!elt.style && elt.target) {
    elt = elt.target;
  }
  else if (!elt.style && elt.srcElement) {
    elt = elt.srcElement;
  }
  else if (!elt.style && !this.screen) {
    elt = this;
  }
  default_focus_handler(elt);
  //for the change handler
  prev_val = get_value(elt);
  //  if (is_nameinput(elt)) {
  //offer options for every cell -- it's a nameinput if the day is >= 0 in workshift_of
  parent.offer_options(elt.value,hours_of(elt),workshift_of(elt));
  //  }
  return true;
}

//what happens when an element loses focus?  many things.  This replaces the
//default handler in table_edit.php
function change_handler(elt) {
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
  //if it's a time element, we have to format it properly
  if (is_timeinput(elt)) {
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
    //did we change when the shift started/ended??  check to see if everyone can
    //still do the shift
    hours_change(elt,workshift_of(elt), null, true);
    return true;
  }
  //did we change the hours?  update the people doing the shift
  if (is_hoursinput(elt)) {
    if (elt.value != prev_val) {
      hours_change(elt,workshift_of(elt),prev_val);
    }
    return true;
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
        if (coords[1] == 3) {
          var changed = '';
          for (var jj = 4; jj < 11; jj++) {
            this_elt = get_cell_elt(coords[0],jj);
            if (!get_value(this_elt)) {
              set_value(this_elt,dummy_string);
              change_handler(this_elt);
              if (changed.length) {
                changed += ', ';
              }
              changed += dayslist[jj-3];
            }
          }
        }
        else {
          wklng_elt = get_cell_elt(coords[0],3);
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
  return true;
}

function blur_handler(elt) {
  if (parent != self) {
    parent.reset_list();
  }
  default_blur_handler(elt);
  return true;
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
      var elt = cur_row.childNodes[jj+3].firstChild;
      var member = elt.value;
      //get the full workshift array if it's the first time
      if (jj == 0) {
        workshift = workshift_of(elt);
      }
      //otherwise, all that's changed is the day, so change that
      else {
        workshift[1] = jj;
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
  return rows_array[(ind-col_num)/8].childNodes[2+num_shift_mods+col_num].firstChild;
}

//array of the order shifts are assigned in -- different for each person
var master_shift_order = 0;
//which rows each workshift is in
var workshiftlist = 0;
//copy of the shift order that will be played around with
var shift_order = 0;
var shift_copy_master = 0;
function autoassign() {
  if (!confirm('If you have not saved your work and reloaded this page, please press ' +
               'cancel and do so now.  To cancel this assignation at any point, press ' +
               'the escape key.  This may not work in the first half-minute or so, while ' +
               'the initial setup is going on.  Try again after names are turning red.')) {
    return;
  }
  //haven't initialized this yet?
  if (!master_shift_order) {
      master_shift_order = new Array();
    //people who submitted first get preference
      for (var mem in prefslist) {
        if (hourslist[mem] < weekly_hours_quota) {
          master_shift_order[mem] = new Array();
        }
      }
    workshiftlist = new Array();
    var total_num = num_rows*8;
    var elt;
    shift_copy_master = new Array(num_rows);
    //set up workshiftlist and shift_copy -- shift_copy is used to put the
    //order shifts are tried for each person into random order, but right
    //now it's trivial
    for (ii = 0; ii < num_rows; ii++) {
      elt = workshift_of(rows_array[ii].childNodes[3].firstChild);
      if (typeof(workshiftlist[elt[0] ]) == 'undefined') {
        workshiftlist[elt[0] ] = new Array();
      }
      workshiftlist[elt[0] ][workshiftlist[elt[0] ].length] = ii;
      for (jj = 0; jj < 8; jj++) {
        shift_copy_master[8*ii+jj] = 8*ii+jj;
      }
    }
  }
  //set up order for each person
  for (mem in master_shift_order) {
    var shift_copy = shift_copy_master.concat();
    //this will go up to total_num -- the total number of workshifts
    var num_done = 0;
    //number of shifts that have actually been added -- some may be rejected
    var num_added = 0;
    //first go through the wanted list, so preferences go at the top
    var wanted = wantedlist[mem];
    for (var ind in wanted) {
      for (var pref in wanted[ind]) {
        if (!wanted[ind][pref].length) {
          continue;
        }
        for (var ii in workshiftlist[wanted[ind][pref][0] ]) {
          for (var jj = 0; jj < 8; jj++) {
            var elt = rows_array[ii].childNodes[2+num_shift_mods].firstChild;
            if (elt.value == '') {
              var workshift = workshift_of(elt);
              //does this shift match the wanted preference?
              if (self.master_shifts.shift_match(wanted[ind],workshift)) {
                //can the person actually do this shift, at least initially?
                if (can_do(mem,workshift,true,true)) {
                  master_shift_order[mem][num_added++] = 8*ii+jj;
                }
                //get it out of the list of shifts to be done
                shift_copy[8*ii+jj] = shift_copy[total_num-++num_done];
              }
            }
          }
        }
      }
    }
    //go through all the rest of the shifts
    for (; num_done < total_num; num_done++) {
      //look at a random entry in the shift_copy array
      ind = Math.floor(Math.random()*(total_num-num_done));
      var elt = elt_of(shift_copy[ind]);
      if (!elt.value) {
        if (can_do(mem,workshift_of(elt),true,true)) {
          master_shift_order[mem][num_added++] = shift_copy[ind];
        }
      }
      shift_copy[ind] = shift_copy[total_num-num_done-1];
    }
  }
  shift_order = new Array();
  for (ii in master_shift_order) {
    shift_order[ii] = master_shift_order[ii].concat();
  }
  cur_history = new Array();
  max_history = new Array();
  in_calculation = true;
  try_assign();
  return;
}

var cur_history;
var max_history;

function try_assign() {
  if (stop_calculation) {
    alert('Aborting operation.  Putting in the best I could find so far.');
    in_calculation = false;
    assign_best_fit();
    return;
  }
  var starting_flag = cur_history.length;
  if (starting_flag) {
    var last_elt = cur_history.pop();
    //    if (cur_history.length < 3) {
    //  alert(cur_history.length + ' ' + last_elt[0] + ' ' + shift_order[last_elt[0] ]);
    //}
  }
  var ii = 0;
  var continue_flag = true;
  while (continue_flag) {
    continue_flag = false;
    for (mem in shift_order) {
      if (hourslist[mem] == weekly_hours_quota && (!starting_flag || mem != last_elt[0])) {
        continue;
      }
      continue_flag = true;
      var orig_len = 0;
      if (starting_flag && mem != last_elt[0]) {
        continue;
      }
      else if (starting_flag) {
        starting_flag = 0;
        var mas = master_shift_order[mem];
        var elt = elt_of(mas[mas.length-shift_order[mem].length-1]);
        autoassign_shift(elt,'');
        orig_len = last_elt[1];
      }
      var order = shift_order[mem];
      var len = order.length;
      if (!orig_len) {
        orig_len = len;
      }
      for (var ii = 0; ii < len; ii++) {
        var ind = order.shift();
        var elt = elt_of(ind);
        if (!elt.value) {
          if (can_do(mem,workshift_of(elt),true,true)) {
            autoassign_shift(elt,mem);
            cur_history[cur_history.length] = new Array(mem,orig_len,ind);
            break;
          }
        }
      }
      if (ii == len) {
        if (!cur_history.length) {
          alert('Failed to assign ' + weekly_hours_quota + ' hours to everyone.  ' +
                'Assigning the best I could find.');
          assign_best_fit();
          return;
        }
        //if the original length was 0, this won't help, and will in fact
        //introduce a bug
        if (orig_len) {
          shift_order[mem] = master_shift_order[mem].slice(-orig_len);
        }
        if (cur_history.length > max_history.length) {
          for (ii in cur_history) {
            max_history[ii] = cur_history[ii].concat();
          }
        }
        self.setTimeout('try_assign()',1);
        return;
      }
    }
  } 
}

function assign_best_fit() {
  for (var ii in cur_history) {
    autoassign_shift(elt_of(cur_history[ii][2]),'');
  }
  for (var ii in max_history) {
    autoassign_shift(elt_of(max_history[ii][2]),max_history[ii][0]);
  }
}

function autoassign_shift(elt,mem) {
  prev_val = elt.value;
  elt.value = mem;
  change_handler(elt);
}
