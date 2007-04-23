<?php
require_once('default.inc.php');
if (!array_key_exists('time_zone_cont',$_REQUEST) ||
    !array_key_exists('time_zone_city',$_REQUEST)) {
?>
<form action='<?=this_url()?>' method='POST'>
Please enter your time zone (choose continent and then the city listed in your area --
Los Angeles for Pacific Standard Time)
<select id='time_zone_cont' name='time_zone_cont' onchange='change_time_zone_cont(this);'>
<?php
$tz_user = get_static('tz_user',null);
 if ($tz_user) {
   $tz_user = explode("/",$tz_user);
 }
$tz_arr = array('America','Africa','Atlantic','Asia','Australia','Europe','Pacific');
 foreach ($tz_arr as $tzcont) {
   print "<option";
   if ($tz_user && $tz_user[0] == $tzcont) {
     print "selected";
   }
   print ">" . escape_html($tzcont) . "\n";
 }
?>
</select>
<select id='time_zone_city' name='time_zone_city'>
<?php
    $tz = array();
 foreach ($tz_arr as $tzcont) {
   $tz[$tzcont] = array();
 }
$tz['Pacific'][] = 'Midway';
$tz['Pacific'][] = 'Tahiti';
$tz['Pacific'][] = 'Marquesas';
$tz['America'][] = 'Adak';
$tz['Pacific'][] = 'Gambier';
$tz['America'][] = 'Anchorage';
$tz['Pacific'][] = 'Pitcairn';
$tz['America'][] = 'Los_Angeles';
$tz['America'][] = 'Phoenix';
$tz['America'][] = 'Denver';
$tz['America'][] = 'Guatemala';
$tz['Pacific'][] = 'Easter';
$tz['America'][] = 'Chicago';
$tz['America'][] = 'Panama';
$tz['America'][] = 'New_York';
$tz['America'][] = 'Guyana';
$tz['America'][] = 'Santiago';
$tz['America'][] = 'Halifax';
$tz['America'][] = 'Montevideo';
$tz['America'][] = 'Sao_Paulo';
$tz['America'][] = 'St_Johns';
$tz['America'][] = 'Godthab';
$tz['America'][] = 'Noronha';
$tz['Atlantic'][] = 'Cape_Verde';
$tz['Atlantic'][] = 'Azores';
$tz['Africa'][] = 'Bamako';
$tz['Europe'][] = 'London';
$tz['Africa'][] = 'Algiers';
$tz['Africa'][] = 'Windhoek';
$tz['Europe'][] = 'Amsterdam';
$tz['Africa'][] = 'Johannesburg';
$tz['Asia'][] = 'Beirut';
$tz['Africa'][] = 'Nairobi';
$tz['Europe'][] = 'Moscow';
$tz['Asia'][] = 'Dubai';
$tz['Asia'][] = 'Tehran';
$tz['Asia'][] = 'Kabul';
$tz['Asia'][] = 'Yerevan';
$tz['Asia'][] = 'Tashkent';
$tz['Asia'][] = 'Calcutta';
$tz['Asia'][] = 'Katmandu';
$tz['Asia'][] = 'Yekaterinburg';
$tz['Asia'][] = 'Colombo';
$tz['Asia'][] = 'Rangoon';
$tz['Asia'][] = 'Novosibirsk';
$tz['Asia'][] = 'Bangkok';
$tz['Asia'][] = 'Krasnoyarsk';
$tz['Australia'][] = 'Perth';
$tz['Asia'][] = 'Irkutsk';
$tz['Asia'][] = 'Tokyo';
$tz['Australia'][] = 'Darwin';
$tz['Australia'][] = 'Adelaide';
$tz['Asia'][] = 'Yakutsk';
$tz['Australia'][] = 'Brisbane';
$tz['Australia'][] = 'Sydney';
$tz['Australia'][] = 'Lord_Howe';
$tz['Asia'][] = 'Vladivostok';
$tz['Pacific'][] = 'Guadalcanal';
$tz['Pacific'][] = 'Norfolk';
$tz['Asia'][] = 'Magadan';
$tz['Pacific'][] = 'Fiji';
$tz['Pacific'][] = 'Auckland';
$tz['Pacific'][] = 'Chatham';
$tz['Asia'][] = 'Kamchatka';
$tz['Pacific'][] = 'Enderbury';
$tz['Pacific'][] = 'Kiritimati';

 if ($tz_user) {
   foreach ($tz[$tz_user[0]] as $city) {
     print "<option";
     if ($tz_user[1] == $city) {
       print "selected";
     }
     print ">" . escape_html($city) . "\n";
   }
 }
?>
</select>
<script type='text/javascript'>
tz = new Array();
<?php
foreach ($tz as $tzcont => $tzcities) {
  print "tz[" . dbl_quote($tzcont) . "] = new Array();\n";
  $ii = 0;
  foreach ($tzcities as $city) {
    print "tz[" . dbl_quote($tzcont) . "][" . $ii++ . "] = " . 
      dbl_quote($city) . "\n";
  }
}
?>

 var tz_cont_elt = document.getElementById('time_zone_cont');
 var tz_city_elt = document.getElementById('time_zone_city');

function change_time_zone_cont() {
  var contval = tz_cont_elt.value;
  var list = tz[contval];
  tz_city_elt.options.length = 0;
  for (var ii in list) {
    tz_city_elt.options[ii] = new Option(list[ii],list[ii]);
  }
}

<?php
    if (!$tz_user) {
?>
function get_tz_name() {
	so = -1 * (new Date(Date.UTC(2005, 6, 30, 0, 0, 0, 0))).getTimezoneOffset()
	wo = -1 * (new Date(Date.UTC(2005, 12, 30, 0, 0, 0, 0))).getTimezoneOffset()
	
	if (-660 == so && -660 == wo) return new Array('Pacific','Midway');
	if (-600 == so && -600 == wo) return new Array('Pacific','Tahiti');
	if (-570 == so && -570 == wo) return new Array('Pacific','Marquesas');
	if (-540 == so && -600 == wo) return new Array('America','Adak');
	if (-540 == so && -540 == wo) return new Array('Pacific','Gambier');
	if (-480 == so && -540 == wo) return new Array('America','Anchorage');
	if (-480 == so && -480 == wo) return new Array('Pacific','Pitcairn');
	if (-420 == so && -480 == wo) return new Array('America','Los_Angeles');
	if (-420 == so && -420 == wo) return new Array('America','Phoenix');
	if (-360 == so && -420 == wo) return new Array('America','Denver');
	if (-360 == so && -360 == wo) return new Array('America','Guatemala');
	if (-360 == so && -300 == wo) return new Array('Pacific','Easter');
	if (-300 == so && -360 == wo) return new Array('America','Chicago');
	if (-300 == so && -300 == wo) return new Array('America','Panama');
	if (-240 == so && -300 == wo) return new Array('America','New_York');
	if (-240 == so && -240 == wo) return new Array('America','Guyana');
	if (-240 == so && -180 == wo) return new Array('America','Santiago');
	if (-180 == so && -240 == wo) return new Array('America','Halifax');
	if (-180 == so && -180 == wo) return new Array('America','Montevideo');
	if (-180 == so && -120 == wo) return new Array('America','Sao_Paulo');
	if (-150 == so && -210 == wo) return new Array('America','St_Johns');
	if (-120 == so && -180 == wo) return new Array('America','Godthab');
	if (-120 == so && -120 == wo) return new Array('America','Noronha');
	if (-60 == so && -60 == wo) return new Array('Atlantic','Cape_Verde');
	if (0 == so && -60 == wo) return new Array('Atlantic','Azores');
	if (0 == so && 0 == wo) return new Array('Africa','Bamako');
	if (60 == so && 0 == wo) return new Array('Europe','London');
	if (60 == so && 60 == wo) return new Array('Africa','Algiers');
	if (60 == so && 120 == wo) return new Array('Africa','Windhoek');
	if (120 == so && 60 == wo) return new Array('Europe','Amsterdam');
	if (120 == so && 120 == wo) return new Array('Africa','Johannesburg');
	if (180 == so && 120 == wo) return new Array('Asia','Beirut');
	if (180 == so && 180 == wo) return new Array('Africa','Nairobi');
	if (240 == so && 180 == wo) return new Array('Europe','Moscow');
	if (240 == so && 240 == wo) return new Array('Asia','Dubai');
	if (270 == so && 210 == wo) return new Array('Asia','Tehran');
	if (270 == so && 270 == wo) return new Array('Asia','Kabul');
	if (300 == so && 240 == wo) return new Array('Asia','Yerevan');
	if (300 == so && 300 == wo) return new Array('Asia','Tashkent');
	if (330 == so && 330 == wo) return new Array('Asia','Calcutta');
	if (345 == so && 345 == wo) return new Array('Asia','Katmandu');
	if (360 == so && 300 == wo) return new Array('Asia','Yekaterinburg');
	if (360 == so && 360 == wo) return new Array('Asia','Colombo');
	if (390 == so && 390 == wo) return new Array('Asia','Rangoon');
	if (420 == so && 360 == wo) return new Array('Asia','Novosibirsk');
	if (420 == so && 420 == wo) return new Array('Asia','Bangkok');
	if (480 == so && 420 == wo) return new Array('Asia','Krasnoyarsk');
	if (480 == so && 480 == wo) return new Array('Australia','Perth');
	if (540 == so && 480 == wo) return new Array('Asia','Irkutsk');
	if (540 == so && 540 == wo) return new Array('Asia','Tokyo');
	if (570 == so && 570 == wo) return new Array('Australia','Darwin');
	if (570 == so && 630 == wo) return new Array('Australia','Adelaide');
	if (600 == so && 540 == wo) return new Array('Asia','Yakutsk');
	if (600 == so && 600 == wo) return new Array('Australia','Brisbane');
	if (600 == so && 660 == wo) return new Array('Australia','Sydney');
	if (630 == so && 660 == wo) return new Array('Australia','Lord_Howe');
	if (660 == so && 600 == wo) return new Array('Asia','Vladivostok');
	if (660 == so && 660 == wo) return new Array('Pacific','Guadalcanal');
	if (690 == so && 690 == wo) return new Array('Pacific','Norfolk');
	if (720 == so && 660 == wo) return new Array('Asia','Magadan');
	if (720 == so && 720 == wo) return new Array('Pacific','Fiji');
	if (720 == so && 780 == wo) return new Array('Pacific','Auckland');
	if (765 == so && 825 == wo) return new Array('Pacific','Chatham');
	if (780 == so && 720 == wo) return new Array('Asia','Kamchatka');
	if (780 == so && 780 == wo) return new Array('Pacific','Enderbury');
	if (840 == so && 840 == wo) return new Array('Pacific','Kiritimati');
	
	return new Array('America','Los_Angeles');
}

// MES- set_select is a tiny helper that takes in the name of a select control
//	and the value of an item to be selected.  It looks for an option in the
//	select with the indicated value.  If found, it selects it and returns true.
//	If the item is not found, returns false.
function set_select(ctrl, val) {
	opts = ctrl.options
	for (var i = 0; i < opts.length; i++) {
		if (val == opts[i].value) {
			opts[i].selected = true;
			return true;
		}
	}
	return false;
}

var timezone = get_tz_name();
 set_select(tz_cont_elt,timezone[0]);
 change_time_zone_cont();
 set_select(tz_city_elt,timezone[1]);
<?php
    }
?>
</script>
<input type=submit value='Change time zone'>
</form>
<?php
    exit;
}
 set_static('tz_user', $_REQUEST['time_zone_cont'] . '/' .
            $_REQUEST['time_zone_city']);
print "The server thinks it is " . user_time();
?>
 right now.  If this is incorrect, <a href='<?=escape_html($_SERVER['REQUEST_URI'])?>'>set the time zone</a>.
 
