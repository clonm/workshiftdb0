<?php
#print_r($_SERVER);
#exit;
$php_includes = '../php_includes/';
require_once($php_includes . 'adodb/adodb.inc.php');
require_once($php_includes . 'adodb/drivers/adodb-mysqlt.inc.php');

//what you have to do to a date to quote it -- in Access it's #$date#
function date_quote($date) {
  return "'$date'";
}

//turn 8/26 into 2005-08-26
function make_date($date) {
  //split up along /
  $arr = explode("/",$date);
  //maybe there were no /'s in the string?
  if (count($arr) == 1) {
    //split up along -
    $arr = explode("-",$date);
  }
  //didn't work either?  Give up.
  if (count($arr) <= 1) {
    return "";
  }
  //no year?  Use this year.
  if (count($arr) == 2) {
    $year = localtime();
    $year = $year[5]+1900;
    array_unshift($arr,$year);
  }
  //otherwise, pad everything to the right length and return it
  else {
    $arr[0] = str_pad($arr[0],4,"0",STR_PAD_LEFT);
  }
  $arr[1] = str_pad($arr[1],2,"0",STR_PAD_LEFT);
  $arr[2] = str_pad($arr[2],2,"0",STR_PAD_LEFT);
  return implode("-",$arr);
}

//called by my qstr to format dates
function date_format($v) {
  $date = make_date($v);
  if (!strlen($date)) {
    return null;
  }
  else {
    return date_quote($date);
  }
}

//same for times
function time_format($v) {
  //break up along :
  $arr = explode(':',$v);
  //nothing?  break up along spaces then
  if (count($arr) < 2) {
    $arr = explode(' ',$v);
  }
  //first part must be hour
  $hour = $arr[0];
  //was there anything besides hour?
  if (count($arr) >= 2) {
    //yes, so that's rest of it
    $rest = $arr[1];
  }
  else {
    //no, rest of it is whole thing -- maybe should give up here
    $rest = $v;
  }
  $minute = '00';
  if ($rest != $v) {
    //do we have a number?
    if (preg_match('/^\d\d/',$rest)) {
      //good, set minute to two digits of number
      $minute = substr($rest,0,2);
    }
  }
  //is there an am?
  $am = stripos($rest,'a');
  //no?
  if ($am === false) {
    $pm = stripos($rest,'p');
    //is there a pm?
    if ($pm !== false) {
      if ($hour == $v) {
        $hour = substr($v,0,$pm);
      }
      //add 12 to the hour unless it was 12
      if ($hour < 12) {
        $hour += 12;
      }
    }
  }
  else {
    if ($hour == $v) {
      $hour = substr($v,0,$am);
    }
    if ($hour == 12) {
      $hour = 0;
    }
  }
  if (strlen($hour) < 2) {
    $hour = '0' . $hour;
  }
  return "'$hour:$minute:00'";
}

//class just to deal with qstr and make it handle dates and times better
class quoting_mysqlt extends ADODB_mysqlt {
  function qstr($str,$magic_quotes_enabled=false) {
    //do we have a date?
    //(at least one number, a forward slash, then at least one other number)
    if (preg_match('/^\d\d?\/\d\d?$/',$str)) {
      return date_format($str);
    }
    //how about a time?
    //at least one number, a colon, then two more numbers, or at least
    //one number, then possibly a colon, with possibly two more numbers,
    //then maybe a space, then some kind of am/pm, with possible periods
    if (preg_match('/^\d\d?:\d\d$/',$str) ||
	preg_match('/^\d\d?(:\d\d)? ?[AaPp]\.?[Mm]?\.?$/',$str)) {
      return time_format($str);
    }
    //be good to empty strings
    else if (!strlen($str)) {
      return 'null';
    }
    //otherwise handle base case with parent class
    else {
      return ADODB_mysqlt::qstr($str,$magic_quotes_enabled);
    }
  }
}

//mysqlt will use this function if defined to initialize itself
$ADODB_NEWCONNECTION = 'make_quoting_mysqlt';
  
function make_quoting_mysqlt($irrelevant) {
  $obj = new quoting_mysqlt();
  return $obj;
}

require_once('../php_includes/janakdb.inc.php');
$houses = array('ath','aca','caz','clo','con','dav','euc','hip','hoy',
		'kid','kng','lot','rid','she','stb','wil','wol');
$houses = array('she');
foreach ($houses as $house) {
  $db->Connect('localhost',"usca_janak$house","workshift","usca_janak$house");
  print "<h1>$house</h1>";
$everyday = array(array("Morning Kitchen Cln & Dishes",1),
array("Afternoon Kitchen Cln & Dishes",1),
array("Dinner Cook",3),
array("Dinner Cook",3),
array("Dinner Kitchen Cln & Dishes",2),
array("Dinner Kitchen Cln & Dishes",2),
array("Late Night Kitchen Cln & Dishes",1),
                  array("Kitchen/Dining Sweep & Mop",1));

 $someday = array(
array("Showers - 2nd Floor",1,'XXXXX',null,'XXXXX','XXXXX',null,'XXXXX','XXXXX'),
array("Showers - 3rd Floor",1,'XXXXX',null,'XXXXX','XXXXX',null,'XXXXX','XXXXX'),
array("Intensive Kitchen Clean",1,'XXXXX',null,'XXXXX','XXXXX',null,'XXXXX','XXXXX'),
array("Brunch Cook",2,null,'XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX'),
array("Brunch Cook",2,null,'XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX'),
array("Brunch Clean",0.5,null,'XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX'),
array("Fridge and Freezer Clean",1,'XXXXX','XXXXX',null,'XXXXX','XXXXX','XXXXX','XXXXX'));

 $weeklong = array(
array("Bathroom",1,1),
array("Rec/Compost mgr",5,null),
array("Bathroom",5,2),
array("Secretary",4,null),
array("Bathroom",5,3),
array("Green Goddess",5,null),
array("Vacuum",1,2),
array("Social mgr",4,null),
array("Vacuum",1,3),
array("House mgr.",5,null),
array("Study Room/Foyer",1,null),
array("Kitchen mgr.",5,null),
array("Living Room",1,null),
array("Kitchen mgr.",5,null),
array("Basement ",1,null),
array("Maint mgr ",5,null),
array("Milk Machine",0.5,null),
array("President",4,null),
array("Food Put Away",1,null),
array("Workshift mgr.",5,null),
array("Oven/Stovetop Clean",0.5,null),
array("Board Rep",5,null),
array("AdCom Member",3,null));
 $db->debug = true;
 $db->Execute("insert into `master_shifts` (`workshift`,`hours`,`floor`,`Monday`,`Tuesday`,`Wednesday`" .
              ",`Thursday`,`Friday`,`Saturday`,`Sunday`,`Weeklong`) values (?,?,?,'XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX',null)",
              $weeklong);
 exit;

#  rs2html($db->Execute("select * from master_shifts"));
#  $db->SetFetchMode(ADODB_FETCH_NUM);
#  janak_fatal_error_reporting(0);
  $db->debug = true;
# $db->Execute("delete from `master_shifts`");
  /*$sun = array(
array("Brunch Cook","9:30am","11am",1.5),
array("Brunch Cook","9:30am","11am",1.5),
array("Dining Room Clean","9:30am","11am",1.5),
array("Dishwash","10am","11am",1),
array("Kitchen Clean","11am","12pm",1),
array("Laundry Room Clean","11am","12pm",1),
array("Courtyard Clean","11am","1pm",2),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishwash","1pm","2pm",1),
array("Dinner Cook","1pm","6pm",5),
array("Dinner Cook","1pm","6pm",5),
array("Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","3:30 pm",2.5),
array("Asst. Dinner Cook","3:30pm","6pm",2.5),
array("Dishrun",null,"4pm",0.5),
array("Main Hall Clean",null,"6pm",1.5),
array("Chapel Pick-Up",null,"6pm",0.5),
array("Perimeter Clean",null,"6pm",1.5),
array("Dishwash/Waiter","5pm","6pm",1),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash","7pm","8:30 pm",1.5),
array("Potwash","7pm","8:30 pm",1.5),
array("Potwash","7pm","8:30 pm",1.5),
array("Potwash","7pm","8:30 pm",1.5),
array("Kitchen Clean","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dungeon Clean","7pm","8:30 pm",1.5),
array("Dungeon Clean","7pm","8:30 pm",1.5),
array("Dumpster Clean","9pm",null,2),
array("Dishrun","10:30pm","11pm",0.5),
array("Snack Cook","9:30pm","11pm",1.5),
array("Snack Cook","9:30pm","11pm",1.5),
array("Kitchen Clean","11pm","12am",1),
array("Dishwash","11pm","12am",1),
array("Security Check","3am","3:30am",0.5));

$mon = array(
array("Breakfast Cook","6:30am","8am",1.5),
array("Breakfast Cook","6:30am","8am",1.5),
array("Kitchen Clean","8am","9am",1),
array("CK Perish Pack","8am","1pm",5),
array("Dishwash","9am","10 am",1),
array("Lunch Cook","10am","12pm",2),
array("Lunch Cook","10am","12pm",2),
array("Dining Room Clean","11am","12pm",1),
array("Kitchen Clean","12pm","1pm",1),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishrun","1pm","1:30pm",0.5),
array("Dishwash","1pm","2pm",1),
array("Bathroom Crew","1:15pm","2:45pm",1.5),
array("Bathroom Crew","1:15pm","2:45pm",1.5),
array("Head Dinner Cook","1pm","6pm",5),
array("Head Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Central Wing Clean","3:30pm","6pm",2.5),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash/Waiter","5pm","6pm",1),
array("Ren and Stimpy Pick-Up",null,"6pm",0.5),
array("Lib,Ed Room Clean",null,"7pm",1),
array("Dishrun",null,"8pm",1),
array("IKC Dishwash","8pm","10:30pm",2.5),
array("IKC DRC","8pm","10:30pm",2.5),
array("IKC DRC","8pm","10:30pm",2.5),
array("IKC Potwash","8pm","11pm",3),
array("IKC Potwash","8pm","11pm",3),
array("IKC Potwash","8pm","11pm",3),
array("IKC","8am","12am",5),
array("IKC","8am","12am",5),
array("IKC","8am","12am",5),
array("IKC","8am","12am",5),
array("Security Check","3am","3:30am",0.5),
);

$tue = array(
array("Breakfast Cook","6:30am","8am",1.5),
array("Breakfast Cook","6:30am","8am",1.5),
array("Kitchen Clean","8am","9am",1),
array("Dishwash","8:15am","9:15am",1),
array("Lunch Cook","10am","12pm",2),
array("Lunch Cook","10am","12pm",2),
array("Dining Room Clean","11am","12pm",1),
array("Kitchen Clean","12pm","1pm",1),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishwash","1pm","2pm",1),
array("Head Dinner Cook","1pm","6pm",5),
array("Head Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Dishrun",null,"7pm",0.5),
array("Main Hall Pick-Up",null,null,0.5),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash/Waiter","5pm","6pm",1),
array("Potwash","7pm","9pm",2),
array("Potwash","7pm","9pm",2),
array("Potwash","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Dishwash","7pm","8pm",1),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Courtyard Clean",null,null,2),
array("East Wing Clean","9:30pm","11pm",1.5),
array("West Wing Pick-Up",null,null,0.5),
array("Central Wing Pick-Up",null,null,0.5),
array("Dungeon Clean",null,null,1),
array("Dumpster Clean","9pm",null,2),
array("Dishwash","11pm","12am",1),
array("Security Check","3am","3:30am",0.5),
);

$wed = array(
array("Breakfast Cook","6:30am","8am",1.5),
array("Breakfast Cook","6:30am","8am",1.5),
array("Kitchen Clean","8am","9am",1),
array("Chapel Clean",null,"5pm",1),
array("Study Room Clean",null,null,1),
array("Laundry Room Clean",null,null,1),
array("Dishrun",null,"1pm",0.5),
array("Dishwash","9am","10 am",1),
array("Lunch Cook","10am","12pm",2),
array("Lunch Cook","10am","12pm",2),
array("Dining Room Clean","11am","12pm",1),
array("Kitchen Clean","12pm","1pm",1),
array("Central Wing Pick-Up","12pm","1pm",1),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishwash","1pm","2pm",1),
array("Head Dinner Cook","1pm","6pm",5),
array("Head Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","3:30 pm",2.5),
array("Asst. Dinner Cook","3:30pm","6pm",2.5),
array("West Wing Clean",null,null,1.5),
array("Ren and Stimpy Clean",null,null,0.5),
array("Main Hall Clean","5pm","6pm",1),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash/Waiter","5pm","6pm",1),
array("Dishrun","6:30pm","7pm",0.5),
array("Dishwash","7pm","8pm",1),
array("Potwash","7pm","9pm",2),
array("Potwash","7pm","9pm",2),
array("Potwash","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Snack Cook","9:30pm","11pm",1.5),
array("Snack Cook","9:30pm","11pm",1.5),
array("Dishwash","11am","12pm",1),
array("Kitchen Clean","11am","12pm",1),
array("Security Check","3am","3:30am",0.5),
);

$thu = array(
array("Breakfast Cook","6:30am","8am",1.5),
array("Breakfast Cook","6:30am","8am",1.5),
array("Kitchen Clean","8am","9am",1),
array("Dishwash","9am","10 am",1),
array("Lunch Cook","10am","12pm",2),
array("Lunch Cook","10am","12pm",2),
array("Dining Room Clean","11pm","12pm",1),
array("Kitchen Clean","12pm","1pm",1),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishwash","1pm","2pm",1),
array("Head Dinner Cook","1pm","6pm",5),
array("Dinner Cook","2:30pm","6pm",3.5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","3:30pm",2.5),
array("Asst. Dinner Cook","3:30pm","6pm",2.5),
array("Perimeter Clean","4pm","5pm",1),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash/Waiter","5pm","6pm",1),
array("Courtyard Clean",null,null,2),
array("Dishrun",null,"8pm",1),
array("IKC Dishwash","8pm","10:30pm",2.5),
array("IKC DRC","8pm","10:30pm",2.5),
array("IKC DRC","8pm","10:30pm",2.5),
array("IKC Potwash","8pm","11pm",3),
array("IKC Potwash","8pm","11pm",3),
array("IKC Potwash","8pm","11pm",3),
array("IKC","8am","12am",5),
array("IKC","8am","12am",5),
array("IKC","8am","12am",5),
array("IKC","8am","12am",5),
array("East Wing Pick-Up",null,null,0.5),
array("West Wing Pick-Up ",null,null,0.5),
array("Central Wing Clean","7pm","9:30 pm",2.5),
array("Dumpster Clean","4pm",null,2),
array("Security Check","3am","3:30am",0.5),
);

$fri = array(
array("Main Hall Clean","6:30am","7:30am",1),
array("Breakfast Cook","6:30am","8am",1.5),
array("Breakfast Cook","6:30am","8am",1.5),
array("Kitchen Clean","8am","9am",1),
array("Ren and Stimpy Clean",null,"8pm",1),
array("Lib,Ed Pick-Up",null,"5pm",0.5),
array("Dining Room Clean","10am","11am",1),
array("Lunch Cook","10am","12pm",2),
array("Lunch Cook","10am","12pm",2),
array("Dishwash","11am","12pm",1),
array("Kitchen Clean","12pm","1pm",1),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishwash","1pm","2pm",1),
array("Courtyard Clean","1pm","2pm",1),
array("Courtyard Clean","1pm","2pm",1),
array("Dinner Cook","1pm","6pm",5),
array("Dinner Cook","1pm","6pm",4),
array("Dinner Cook","1pm","6pm",5),
array("Dinner Cook","1pm","2:30pm",1.5),
array("Dinner Cook","3:30pm","6pm",2.5),
array("Bathroom Crew","2pm","5pm",3),
array("Dishrun",null,"5pm",0.5),
array("Chapel Clean",null,null,0.5),
array("East Wing Clean",null,null,1.5),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash/Waiter","5pm","6pm",1),
array("Potwash","7pm","9pm",2),
array("Potwash","7pm","9pm",2),
array("Potwash","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Dishwash","7pm","8pm",1),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dishwash","11pm","12am",1),
);

$sat = array(
array("Brunch Cook","9:30am","11am",1.5),
array("Brunch Cook","9:30am","11am",1.5),
array("Dining Room Clean","10am","11 am",1),
array("Dishwash","10am","11am",1),
array("Kitchen Clean","11am","12pm",1),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Potwash","12:30pm","2pm",1.5),
array("Dishwash","1pm","2pm",1),
array("Head Dinner Cook","1pm","6pm",5),
array("Head Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Asst. Dinner Cook","1pm","6pm",5),
array("Courtyard Clean","2pm","4pm",2),
array("Dishrun",null,"4pm",0.5),
array("Freepile Clean","4pm","5:30 pm",1.5),
array("Freepile Clean","4pm","5:30 pm",1.5),
array("West Wing Clean","4pm","5:30 pm",1.5),
array("Dishwash/Waiter","5pm","6pm",1),
array("DRC/Waiter","5pm","6pm",1),
array("Dishwash","7pm","8pm",1),
array("Potwash","7pm","8:30 pm",1.5),
array("Potwash","7pm","8:30 pm",1.5),
array("Potwash","7pm","8:30 pm",1.5),
array("Kitchen Clean","7pm","9pm",2),
array("Kitchen Clean","7pm","9pm",2),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("Dining Room Clean","7pm","8:30 pm",1.5),
array("East Wing Pick-Up",null,null,0.5),
array("Central Wing Pick-Up","4:30pm","5:30pm",1),
array("Dishwash","11pm","12am",1)
);

/* $db->Execute("insert into `master_shifts` (`autoid`,`workshift`,`Weeklong`,`Monday`,`Tuesday`," .
              "`Wednesday`,`Thursday`,`Friday`,`Saturday`,`Sunday`,`start_time`,`end_time`,`hours`) " .
              "values(null,?,'XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX',null,?,?,?)",
              $sun);
// $db->Execute("update `master_shifts` set `Thursday` = 'XXXXX'"); 

 foreach ($sat as $arr) {
   $row = $db->GetRow("select count(*) as `ct` from `master_shifts` where `workshift` = ? " .
               "and `start_time` <=> ? and `end_time` <=> ? and `hours` = ? and `Saturday` = 'XXXXX' limit 1",
               $arr);
   if ($row['ct']) {
     $db->Execute("update `master_shifts` set `Saturday` = null where `workshift` = ? " .
                  "and `start_time` <=> ? and `end_time` <=> ? and `hours` = ? and `Saturday` = 'XXXXX' limit 1",
                  $arr);
   }
   else {
     $db->Execute("insert into `master_shifts` (`autoid`,`workshift`,`Weeklong`,`Monday`,`Tuesday`," .
              "`Wednesday`,`Thursday`,`Friday`,`Saturday`,`Sunday`,`start_time`,`end_time`,`hours`) " .
              "values(null,?,'XXXXX','XXXXX','XXXXX','XXXXX','XXXXX','XXXXX',null,'XXXXX',?,?,?)",
              $arr);
   }
 }
  */
}
?>