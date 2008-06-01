//get coordinates of input cell
function get_cell(targ) {
  var elts = targ.name.split("-");
  if (elts[0] != "cell") {
    return 0;
  }
  var rowind = targ.parentNode.parentNode.rowIndex-1;
  var colind = targ.parentNode.cellIndex;
  if (rowind != elts[1] || colind != elts[2]) {
    alert("Please email Janak (janak@berkeley.edu) and tell him there was a problem with "
          + "get_cell, and tell him what page you're on, and what "
          + "you were doing, along with the following numbers: " + rowind + ' ' + elts[1] + ' ' + colind + ' ' + elts[2]);
  }
  return new Array(elts[1],elts[2]);
}

function get_cell_elt(ii,jj) {
  if (!rows_array[ii]) {
    alert("Please email Janak (janak@berkeley.edu) and tell him there was a problem "
          + "with get_cell_elt, and tell him what page you're on, and what "
          + "you were doing, along with the following numbers: " + ii + ' ' + jj);
    fake_function_call(fake_variable);
  }
  return rows_array[ii].cells[jj].firstChild;
}

function is_cell(elt) {
  var parent = elt.parentNode;
  if (!is_td(parent)) {
    return false;
  }
  var these_rows = parent.parentNode.parentNode.rows;
  if (!rows_array || these_rows != rows_array) {
    return false;
  }
  return true;
}

//is this an input cell?
function is_input(elt) {
  return (elt.nodeName && elt.nodeName == "INPUT");
}

function is_textarea(elt) {
  return (elt.nodeName && elt.nodeName == "TEXTAREA");
}

function is_checkbox(elt) {
  return (is_input(elt) && elt.type && elt.type == 'checkbox');
}

function is_radio(elt) {
  return is_input(elt) && elt.type && elt.type == 'radio';
}

function is_select(elt) {
  return elt.nodeName && elt.nodeName == 'SELECT';
}

function is_link(elt) {
  return (elt.nodeName && elt.nodeName == 'A');
}

function is_td(elt) {
  return elt.nodeName && elt.nodeName == 'TD';
}

function is_class(elt,class_name) {
  if (!elt.className) {
    return false;
  }
  return (elt.className.split(' ').indexOf(class_name) != -1);
}
  
//the following functions can easily be extended.  There should be one for 
//every cell which requires special handling behavior.  Currently there's
//just name and time cells.

//is this a cell which contains member names?
function is_nameinput(elt) {
  if (!is_input(elt)) {
    return false;
  }
  return is_class(elt,'member_name');
}

//is this a cell which contains times?
function is_timeinput(elt) {
  if (!is_input(elt)) {
    return false;
  }
  return is_class(elt,'time');
}
  
function get_value(thing) {
  if (!thing) {
    alert("error retrieving element value.  Please contact administrator");
    return null;
  }
  if (is_checkbox(thing) || is_radio(thing)) {
    return thing.checked?(thing.value!='on'?thing.value:1):(thing.hasAttribute('defaultValue')?thing.getAttribute('defaultValue'):0);
  }
  if (is_link(thing)) {
    return get_value(thing.firstChild);
  }
  if (is_td(thing)) {
    if (!thing.firstChild) {
      return null;
    }
    else {
      return get_value(thing.firstChild);
    }
  }
  //maybe thing.value is null but it still has it (value is for inputs)
  if (thing.value || is_input(thing) || is_textarea(thing) ||
      is_select(thing) ||
      (thing.hasAttribute && 
       thing.hasAttribute('value'))) {
    return thing.value;
  }
  //same for nodeValue (nodeValue is for text)
  if (thing.nodeValue || (thing.hasAttribute && 
                          thing.hasAttribute('nodeValue'))) {
    return thing.nodeValue;
  }
  if (thing.firstChild) {
    return get_value(thing.firstChild);
  }
  if (thing.innerHTML || thing.hasAttribute && 
      thing.hasAttribute('innerHTML')) {
    return thing.innerHTML;
  }
  return thing;
}

function get_value_by_id(str) {
  return get_value(get_elt_by_id(str));

}

function set_value(elt,value) {
  if (typeof(elt) != 'object') {
    elt = get_elt_by_id(elt);
  }
  if (is_checkbox(elt)) {
    elt.checked = value;
    return;
  }
  if (is_input(elt) || typeof(elt.value) != 'undefined') {
    elt.value = value;
  }
  if (typeof(elt.nodeValue) != 'undefined') {
    elt.nodeValue = value;
  }
  if (typeof(elt.innerHTML) != 'undefined' && !is_input(elt)) {
    elt.innerHTML = value;
  }
  if (elt.firstChild) {
    set_value(elt.firstChild,value);
  }
}

function set_value_by_id(str,value) {
  return set_value(get_elt_by_id(str),value);
}

function get_elt_by_id(id) {
  return document.getElementById(id);
}

function enable_elt(elt,enable) {
  return elt.disabled = (typeof(enable) == 'undefined' || !enable);
}

function enable_elt_by_id(str,enable) {
  return enable_elt(get_elt_by_id(str),enable);
}

function is_whole_number(val) {
  return /^\d+$/.test(val);
}

function is_whole_number_by_id(str) {
  return is_whole_number(get_value_by_id(str));
}

function is_positive_decimal(val) {
  return val > 0 && /^\+?\d*(\.\d)?\d*$/.test(val);
}

function is_positive_decimal_by_id(str) {
  return is_positive_decimal(get_value_by_id(str));
}

function is_decimal(val) {
  return val.length && /^(\+|-)?\d*(\.\d)?\d*$/.test(val);
}

function is_integer(val) {
  return /^-?\d+$/.test(val);
}

function change_cell(elt,new_val) {
  if (!elt.style) {
    elt = get_elt_by_id(elt);
  }
  if (get_value(elt) != new_val) {
    set_value(elt,new_val);
    elt.style.color = "red";
    elt.style.borderColor = "black";
  }
}

function get_css_rules(ind) {
  if (ind == null) {
    ind = document.styleSheets.length-1;
  }
  if (document.styleSheets[ind].cssRules) {
    return document.styleSheets[ind].cssRules;
  }
  else if (document.styleSheets[ind].rules) {
   return document.styleSheets[ind].rules;
  }
  else {
    alert("Can't get css rules in this browser.");
    return null;
  }
}

function set_style(elt,style_prop,value) {
  if (elt.style.setProperty) {
    return elt.style.setProperty(style_prop,value,null);
  }
  else {
    alert('not checked');
    return elt.style[style_prop] = value;
  }
}

function get_style(elt,style_prop) {
  if (document.styleSheets[0] instanceof CSSStyleSheet &&
      elt instanceof CSSStyleRule) {
    if (elt.style.getPropertyValue) {
      return elt.style.getPropertyValue(style_prop);
    }
    else {
      alert('not checked get style');
      return elt.style[style_prop];
    }
  }
  if (window.getComputedStyle) {
    return document.defaultView.getComputedStyle(elt,null).getPropertyValue(style_prop);
  }
  else if (elt.currentStyle) {
    return elt.currentStyle[style_prop];
  }
  else {
    alert("Can't get style property in this browser");
    return null;
  }
}
