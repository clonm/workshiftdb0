function change_runoff_num(ii) {
   document.getElementById("runoff_" + ii + "_none").checked = document.getElementById("runoff_" + ii + "_instant").checked = false;
   return true;
}
 
function change_runoff_radio(race_num) {
  set_value_by_id("runoff_" + race_num + "_num",'');
}

function init_yesno(ii) {
   document.getElementById('candidates_' + ii).value="yes\nno";
   document.getElementById('num_' + ii).value = 1;
   document.getElementById('runoff_' + ii + '_none').checked = true;
   document.getElementById('threshold_' + ii + '_num_percent').value = 50;
   document.getElementById('threshold_' + ii + '_percent').checked = true;
   document.getElementById('abstain_count_' + ii).checked = true;;
   document.getElementById('def_val_' + ii).value = 'no';
   return false;
}

function init_approved(ii) {
   document.getElementById('candidates_' + ii).value="Approved\nNot Approved";
   document.getElementById('num_' + ii).value = 1;
   document.getElementById('runoff_' + ii + '_none').checked = true;
   document.getElementById('threshold_' + ii + '_num_percent').value = 66.6;
   document.getElementById('threshold_' + ii + '_percent').checked = true;
   document.getElementById('abstain_count_' + ii).checked = true;;
   document.getElementById('def_val_' + ii).value = 'Not Approved';
   return false;
}

function threshold_change(race_num,ind) {
  get_elt_by_id('abstain_count_span_' + race_num).style.color = (ind == 0?'gray':'');
  get_elt_by_id('abstain_count_' + race_num).disabled = (ind == 0);
  if (ind) {
    if (ind == 1) {
      set_value_by_id('threshold_' + race_num + '_num_absolute','');    
    }
    else {
      set_value_by_id('threshold_' + race_num + '_num_percent','');
    }    
    document.getElementById('def_val_span_' + race_num).style.color = '';
    document.getElementById('def_val_' + race_num).disabled = false;
  }
  else {
    set_value_by_id('threshold_' + race_num + '_num_percent','');
    set_value_by_id('threshold_' + race_num + '_num_absolute','');    
    var eltval = get_value_by_id('num_voters_' + race_num);
    if (!eltval.length) {
      document.getElementById('def_val_span_' + race_num).style.color = 'gray';
      document.getElementById('def_val_' + race_num).disabled = true;
    }
  }
}

function num_voters_change(elt) {
  var race_num = elt.name.substr(11);
  var eltval = get_value(elt);
  if (eltval.length) {
    document.getElementById('def_val_span_' + race_num).style.color = '';
    document.getElementById('def_val_' + race_num).disabled = false;
  }
  else {
    if (document.getElementById('threshold_' + race_num + '_none').checked) {
      document.getElementById('def_val_span_' + race_num).style.color = 'gray';
      document.getElementById('def_val_' + race_num).disabled = true;
    }
  }
}

function feedback_change(elt) {
  var race_num = elt.name.substr(9);
  if (elt.checked) {
    document.getElementById('feedback_disable_' + race_num).style.display = 'none';
  }
  else {
    document.getElementById('feedback_disable_' + race_num).style.display = '';
  }
}
  
function add_race() {
  var par = document.createElement('p');
  par.appendChild(document.createElement('hr'));
  var elt = document.createElement('h4');
  var num_races = document.getElementById('num_races').getAttribute('value');
  num_races++;
  elt.appendChild(document.createTextNode('Race ' + num_races));
  par.appendChild(elt);
  spanelt = document.createElement('span');
  spanelt.setAttribute('class','yesno');
  elt = document.createElement('input');
  elt.setAttribute('type','button');
  elt.setAttribute('value','Make it Yes/No');
  elt.setAttribute('onclick','event.preventDefault(); init_yesno(' + num_races + ');');
  spanelt.appendChild(elt);
  par.appendChild(spanelt);
  elt = document.createElement('input');
  elt.setAttribute('type','button');
  elt.setAttribute('value','Make it Approved/Not Approved');
  elt.setAttribute('onclick','event.preventDefault(); init_approved(' + num_races + ');');
  par.appendChild(elt);
  par.appendChild(document.createElement('br'));
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','display_name_span_' + num_races);
  spanelt.appendChild(document.createTextNode("Race name: "));
  elt = document.createElement('input');
  elt.setAttribute('name','display_name_' + num_races);
  elt.setAttribute('id','display_name_' + num_races);
  spanelt.appendChild(elt);
  par.appendChild(spanelt);
  par.appendChild(document.createElement('br'));

  elt = document.createElement('textarea');
  elt.setAttribute('name','race_descript_' + num_races);
  elt.setAttribute('id','race_descript_' + num_races);
  elt.setAttribute('rows',5);
  elt.setAttribute('cols',60);
  lab = document.createElement('label');
  lab.appendChild(document.createTextNode("Description of race (if necessary): "));
  par.appendChild(lab);
  par.appendChild(elt);
  par.appendChild(document.createElement('hr'));
  par.appendChild(document.createTextNode("Is this just a feedback race (i.e., a VOC)?"));
  elt = document.createElement('input');
  elt.setAttribute('name','feedback_' + num_races);
  elt.setAttribute('id','feedback_' + num_races);
  elt.setAttribute('value','1');
  elt.setAttribute('type','checkbox');
  elt.setAttribute('onchange','feedback_change(this)');
  par.appendChild(elt);
  par.appendChild(document.createElement('hr'));
  
  delt = document.createElement('div');
  delt.id = 'feedback_disable_' + num_races;
  elt = document.createElement('textarea');
  elt.setAttribute('name','candidates_' + num_races);
  elt.setAttribute('id','candidates_' + num_races);
  elt.setAttribute('rows',5);
  lab = document.createElement('label');
  lab.appendChild(document.createTextNode("Candidates (one per line): "));
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','candidates_span_' + num_races);
  spanelt.appendChild(lab);
  spanelt.appendChild(elt);
  delt.appendChild(spanelt);
  delt.appendChild(document.createElement('br'));
  
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','num_span_' + num_races);
  elt = document.createElement('input');
  elt.setAttribute('name','num_' + num_races);
  elt.setAttribute('id','num_' + num_races);
  elt.setAttribute('size',3);
  elt.setAttribute('value',1);
  spanelt.appendChild(document.createTextNode("Number of spots: "));
  spanelt.appendChild(elt);
  delt.appendChild(spanelt);
  delt.appendChild(document.createElement('br'));

  divelt = document.createElement('div');
  divelt.setAttribute('class','options');
  divelt.setAttribute('id','options_div_' + num_races);
  divelt.appendChild(document.createTextNode("Options:"));
  divelt.appendChild(document.createElement('br'));

  delt.appendChild(document.createElement('br'));

  labelt = document.createElement('label');
  labelt.setAttribute('for','runoff_' + num_races + '_none');
  elt = document.createElement('input');
  elt.setAttribute('name','runoff_' + num_races);
  elt.setAttribute('id','runoff_' + num_races + '_none');
  elt.setAttribute('type','radio');
  elt.setAttribute('value','0');
  elt.setAttribute('checked','true');
  labelt.appendChild(elt);
  labelt.appendChild(document.createTextNode('Nothing special'));
  divelt.appendChild(labelt);
  divelt.appendChild(document.createElement('br'));
  labelt = document.createElement('label');
  labelt.setAttribute('for','runoff_' + num_races + '_instant');
  elt = document.createElement('input');
  elt.setAttribute('name','runoff_' + num_races);
  elt.setAttribute('id','runoff_' + num_races + '_instant');
  elt.setAttribute('type','radio');
  elt.setAttribute('value','1');
  elt.setAttribute('onchange','change_runoff_radio(' + num_races + ')');
  labelt.appendChild(elt);
  labelt.appendChild(document.createTextNode('Instant runoff'));
  divelt.appendChild(labelt);
  divelt.appendChild(document.createElement('br'));
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','unranked_preferences_span_' + num_races);
  spanelt.appendChild(document.createTextNode("Unranked preferences allowed?  Enter the maximum number of preferences here: "));
  elt = document.createElement('input');
  elt.setAttribute('name','runoff_' + num_races + '_num');
  elt.setAttribute('id','runoff_' + num_races + '_num');
  elt.setAttribute('size',3);
  elt.setAttribute('onchange','change_runoff_num(' + num_races + ')');
  spanelt.appendChild(elt);
  divelt.appendChild(spanelt);
  divelt.appendChild(document.createElement('br'));
  delt.appendChild(divelt);

  divelt = document.createElement('div');
  divelt.setAttribute('class','threshold');
  divelt.appendChild(document.createTextNode("Threshold:"));
  divelt.appendChild(document.createElement('br'));
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','threshold_span_' + num_races);
  labelt = document.createElement('label');
  labelt.setAttribute('for','threshold_' + num_races + '_none');
  elt = document.createElement('input');
  elt.setAttribute('name','threshold_' + num_races);
  elt.setAttribute('id','threshold_' + num_races + '_none');
  elt.setAttribute('type','radio');
  elt.setAttribute('checked','true');
  elt.setAttribute('value','0');
  elt.setAttribute('onchange','threshold_change(' + num_races + ',0)');
  labelt.appendChild(elt);
  labelt.appendChild(document.createTextNode('None'));
  spanelt.appendChild(labelt);
  spanelt.appendChild(document.createElement('br'));
  spanelt2 = document.createElement('span');
  spanelt2.setAttribute('id','threshold_' + num_races + '_num_percent_span');
  elt = document.createElement('input');
  elt.setAttribute('name','threshold_' + num_races);
  elt.setAttribute('id','threshold_' + num_races + '_percent');
  elt.setAttribute('type','radio');
  elt.setAttribute('value','percent');
  elt.setAttribute('onchange','threshold_change(' + num_races + ',1)');
  spanelt2.appendChild(elt);
  labelt = document.createElement('label');
  labelt.setAttribute('for','threshold_' + num_races + '_percent');
  labelt.appendChild(document.createTextNode("More than "));
  spanelt2.appendChild(labelt);
  elt = document.createElement('input');
  elt.setAttribute('name','threshold_' + num_races + '_num');
  elt.setAttribute('id','threshold_' + num_races + '_num_percent');
  elt.setAttribute('size',3);
  elt.setAttribute('onclick','get_elt_by_id("threshold_' + 
                   num_races + '_percent").click()');
  elt.setAttribute('onchange','get_elt_by_id("threshold_' + 
                   num_races + '_percent").click()');
  spanelt2.appendChild(elt);
  labelt = document.createElement('label');
  labelt.setAttribute('for','threshold_' + num_races + '_percent');  
  labelt.appendChild(document.createTextNode("% of the vote is required"));
  spanelt2.appendChild(labelt);
  spanelt.appendChild(spanelt2);
  spanelt.appendChild(document.createElement('br'));
  spanelt2 = document.createElement('span');
  spanelt2.setAttribute('id','threshold_' + num_races + '_num_absolute_span');
  elt = document.createElement('input');
  elt.setAttribute('name','threshold_' + num_races);
  elt.setAttribute('id','threshold_' + num_races + '_number');
  elt.setAttribute('type','radio');
  elt.setAttribute('value','number');
  elt.setAttribute('onchange','threshold_change(' + num_races + ',2)');
  spanelt2.appendChild(elt);
  elt = document.createElement('input');
  elt.setAttribute('name','threshold_' + num_races + '_num_absolute');
  elt.setAttribute('id','threshold_' + num_races + '_num_absolute');
  elt.setAttribute('size',3);
  elt.setAttribute('onclick','get_elt_by_id("threshold_' + 
                   num_races + '_number").click()');
  elt.setAttribute('onchange','get_elt_by_id("threshold_' + 
                   num_races + '_number").click()');
  spanelt2.appendChild(elt);
  labelt = document.createElement('label');
  labelt.setAttribute('for','threshold_' + num_races + '_number');
  labelt.appendChild(document.createTextNode(" votes are required."));
  spanelt2.appendChild(labelt);
  spanelt.appendChild(spanelt2);
  divelt.appendChild(spanelt);
  divelt.appendChild(document.createElement('br'));
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','abstain_count_span_' + num_races);
  spanelt.appendChild(document.createTextNode("Will abstentions count as votes for threshold purposes? "));
  spanelt.setAttribute('style','color: gray');
  divelt.appendChild(spanelt);
  elt = document.createElement('input');
  elt.setAttribute('name','abstain_count_' + num_races);
  elt.setAttribute('id','abstain_count_' + num_races);
  elt.setAttribute('type','checkbox');
  elt.setAttribute('disabled','true');
  divelt.appendChild(elt);
  divelt.appendChild(document.createElement('br'));
  spanelt = document.createElement('span');
  spanelt.setAttribute('id','num_voters_span_' + num_races);
  spanelt.appendChild(document.createTextNode("Minimum number of votes cast for race to count "));
  elt = document.createElement('input');
  elt.setAttribute('name','num_voters_' + num_races);
  elt.setAttribute('id','num_voters_' + num_races);
  elt.setAttribute('size',3);
  elt.setAttribute('onchange','num_voters_change(this)');
  spanelt.appendChild(elt);
  divelt.appendChild(spanelt);
  divelt.appendChild(document.createElement('br'));

  spanelt = document.createElement('span');
  spanelt.setAttribute('id','def_val_span_' + num_races);
  spanelt.appendChild(document.createTextNode("What is the default if nothing reaches the threshold? "));
  spanelt.setAttribute('style','color: gray');
  divelt.appendChild(spanelt);
  elt = document.createElement('input');
  elt.setAttribute('name','def_val_' + num_races);
  elt.setAttribute('id','def_val_' + num_races);
  elt.setAttribute('size',20);
  elt.setAttribute('disabled',true);
  divelt.appendChild(elt);

  delt.appendChild(divelt);

  delt.appendChild(document.createElement('br'));
  delt.appendChild(document.createTextNode("Can the voters add choices themselves?  (Used for things like party themes/naming lizards, etc.)"));
  elt = document.createElement('input');
  elt.setAttribute('name','member_add_' + num_races);
  elt.setAttribute('id','member_add_' + num_races);
  elt.setAttribute('type','checkbox');
  delt.appendChild(elt);
  par.appendChild(delt);
  par.appendChild(document.createTextNode("Can the voters make comments that other voters will see when they are voting?"));
  elt = document.createElement('input');
  elt.setAttribute('name','member_comments_' + num_races);
  elt.setAttribute('type','checkbox');
  par.appendChild(elt);
  document.getElementById('num_races').setAttribute('value',num_races);
  document.getElementById('div_races').appendChild(par);
}
  
function validate_election_form() {
  var alerts = '';
  //might be modifying, so no election name at all
  var elt = get_elt_by_id('election_name');
  if (elt) {
    if (!get_value(elt).length) {
      alerts += "The election name cannot be blank.\n";
      get_elt_by_id('election_name_span').style.color = 'red';
      retflag = false;
    }
    else {
      get_elt_by_id('election_name_span').style.color = 'black';
    }
  }
  var date_arr = get_value_by_id('end_date').split('-');
  if (date_arr.length < 3 || !is_whole_number(date_arr[0]) |
      !is_whole_number(date_arr[1]) || !is_whole_number(date_arr[2]) ||
      !date_arr[0] || !date_arr[1] || !date_arr[2] ||
      date_arr[0].length != 4) {
    alerts += "The end date is not in a valid form.  It should be " +
      "YYYY-MM-DD, like 2008-04-23.\n";
    get_elt_by_id('end_date_span').style.color = 'red';
  }
  else {
    get_elt_by_id('end_date_span').style.color = 'black';
  }
  var num_races = get_value_by_id('num_races');
  for (var ii = 1; ii <= num_races; ii++) {
    //should this race be deleted?  If there are candidates or it's a
    //feedback race, then no -- make user delete those things first
    var race_name = get_value_by_id('display_name_' + ii);
    if (get_value_by_id('feedback_' + ii)) {
      if (!race_name.length) {
        alerts += "Race " + ii + " has no race name.  You must enter one.  " +
          "If you wish to delete this race, leave the race name blank, " +
          "uncheck the feedback box, and remove any candidates as well.\n";
        get_elt_by_id('display_name_span_' + ii).style.color = 'red';
      }
      else {
        get_elt_by_id('display_name_span_' + ii).style.color = '';
      }        
      continue;
    }
    var cands = get_value_by_id('candidates_' + ii);
    if (cands.length) {
      cands = cands.split("\n");
      get_elt_by_id('candidates_span_' + ii).style.color = '';
    }
    //if no candidates, then all we care about is that there is no
    //race name, so it will be deleted
    else {
      if (race_name.length) {
        alerts += "Race " + ii + " (" + race_name + ") has no candidates.  " +
          "Either make it a feedback race, or add some candidates, or " + 
          "delete the race name (delete the race name to delete the race).\n";
        get_elt_by_id('candidates_span_' + ii).style.color = 'red';
      }
      else {
        get_elt_by_id('candidates_span_' + ii).style.color = '';
      }
      continue;
    }
    //this is a real race, since there are candidate(s).  Make sure
    //there is a name
    if (!race_name.length) {
      alerts += "Race " + ii + " has no race name.  You must enter one.  " +
        "If you wish to delete this race, leave the race name blank and " +
        "remove all the candidates as well.\n";
      get_elt_by_id('display_name_span_' + ii).style.color = 'red';
      continue;
    }
    else {
      get_elt_by_id('display_name_span_' + ii).style.color = '';
    }
    var val = get_value_by_id('num_' + ii);
    if (!val.length || !val || !is_whole_number(val)) {
      alerts += "The number of positions in Race " + ii + " (" +
        race_name + ") is not correct -- it must be a positive number, " + 
        "like 1 or 4.\n";
      get_elt_by_id('num_span_' + ii).style.color = 'red';
    }
    else {
      get_elt_by_id('num_span_' + ii).style.color = '';
    }
    //do the options part -- there are two things that might be
    //reddened, so blacken them both
    get_elt_by_id('options_div_' + ii).style.color = '';
    get_elt_by_id('unranked_preferences_span_' + ii).style.color = '';
    val = get_value_by_id('runoff_' + ii + '_num')
      //if a radio is checked, we're almost certainly ok
      if (!get_value_by_id('runoff_' + ii + '_none') &&
          !get_value_by_id('runoff_' + ii + '_instant')) {
            if (!val.length) {
              alerts += "Race " + ii + " (" + race_name + 
              "): You must choose one choice " +
              "from the Options -- Nothing special, Instant runoff, " +
              "or Unranked preferences.\n";
              get_elt_by_id('options_div_' + ii).style.color = 'red';
            }
            else if (!is_whole_number(val) || !val) {
              alerts += "Race " + ii + " (" + race_name +
              "): The number of unranked preferences must be a whole " +
              "number, like 3 or 2.\n";
              get_elt_by_id('unranked_preferences_span_' + ii).style.color = 'red';
            }
          }
    //unless the user put something in the text box with their
    //javascript acting weird
      else if (val.length) {
        alerts += "Race " + ii + " (" + race_name + 
          ") does not have unranked preferences, " +
          "but you entered something in the unranked preferences box.  " +
          "Please check your setup.\n";
        get_elt_by_id('unranked_preferences_span_' + ii).style.color = 'red';
      }
    //now for the threshold
    //is there no threshold?
    var threshold;
    if (get_elt_by_id('threshold_' + ii + '_none').checked) {
      threshold = 0;
    }
    else if (get_elt_by_id('threshold_' + ii + '_percent').checked) {
      threshold = 1;
    }
    else if (get_elt_by_id('threshold_' + ii + '_number').checked) {
      threshold = 2;
    }
    else {
      alerts += "You didn't choose any of the threshold options in Race " + 
        ii + "(" + race_name + 
        ").  Please choose 'None', or 'More than __%', or '__ votes'.\n";
      threshold = 4;
      get_elt_by_id('threshold_span_' + ii).style.color = 'red';
    }
    if (threshold < 4) {
      get_elt_by_id('threshold_span_' + ii).style.color = '';
      val = get_value_by_id('threshold_' + ii + '_num_percent');
      get_elt_by_id('threshold_' + ii + '_num_percent_span').style.color = '';
      if (threshold != 1 && 
          val.length) {
        alerts += " If Race " + ii + " (" + race_name + 
          ") does not have a percentage " +
          "threshold, then why is there a percentage entered?  Please " +
          "erase it.\n";
        get_elt_by_id('threshold_' + ii + '_num_percent_span').style.color = 'red';
      }
      else if (threshold == 1) {
        if (!val.length) {
          alerts += "Race " + ii + " (" + race_name + 
            ") has a percentage threshold, but no percentage entered.\n";
          get_elt_by_id('threshold_' + ii + '_num_percent_span').style.color = 'red';
        }
        else if (!is_positive_decimal(val)) {
          alerts += "Race " + ii + " (" + race_name + ") must have a " +
            "positive decimal percentage threshold (like 66.667) entered.\n";
          get_elt_by_id('threshold_' + ii + '_num_percent_span').style.color = 'red';
        }
      }
      val = get_value_by_id('threshold_' + ii + '_num_absolute');
      get_elt_by_id('threshold_' + ii + '_num_absolute_span').style.color = '';
      if (threshold != 2 && val.length) {
        alerts += " If Race " + ii + " (" + race_name + 
          ") does not have an absolute " +
          "threshold, then why is there a number entered?  Please " +
          "erase it.\n";
        get_elt_by_id('threshold_' + ii + '_num_absolute_span').style.color = 'red';
      }
      else if (threshold == 2) {
        if (!val.length) {
          alerts += "Race " + ii + " (" + race_name + 
            ") has an absolute threshold, but no number entered.\n";
          get_elt_by_id('threshold_' + ii + '_num_absolute_span').style.color = 'red';
        }
        else if (!val || !is_whole_number(val)) {
          alerts += "Race " + ii + " (" + race_name + ") must have a " +
            "positive absolute threshold (like 45) entered.\n";
          get_elt_by_id('threshold_' + ii + '_num_absolute_span').style.color = 'red';
        }
      }
    }
    val = get_value_by_id('num_voters_' + ii);
    if (val.length && (!val || !is_whole_number(val))) {
      alerts += "The absolute number of voters needed in Race " + ii + 
        " (" + race_name + ") must be a positive integer (like 45).";
      get_elt_by_id('num_voters_span_' + ii).style.color = 'red';
    }
  }
  if (alerts.length) {
    alert(alerts);
    return false;
  }
  return true;
}
