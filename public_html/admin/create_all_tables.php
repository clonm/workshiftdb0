<?php

function create_master_shifts () {
  global $db;
  //static so we know if we've tested for existence before.  Note that
  //if you call this from an admin script, it will give an incorrect
  //response if you're doing it for multiple houses.
  static $done = false;
  if (true) {
    if (!table_exists('master_shifts')) {
      $done = $db->Execute(
<<<CREATETABLE
CREATE TABLE IF NOT EXISTS `master_shifts` 
(
 `autoid` int(11) NOT NULL auto_increment,
 `workshift` varchar(50) default NULL,
 `floor` varchar(20) default NULL,
 `hours` double default NULL,
 `Weeklong` varchar(50) default NULL,
 `Monday` varchar(50) default NULL,
 `Tuesday` varchar(50) default NULL,
 `Wednesday` varchar(50) default NULL,
 `Thursday` varchar(50) default NULL,
 `Friday` varchar(50) default NULL,
 `Saturday` varchar(50) default NULL,
 `Sunday` varchar(50) default NULL,
 `start_time` time default NULL,
 `end_time` time default NULL,
 `description` longtext default null,
 `category` varchar(100) default null,
 PRIMARY KEY  (`autoid`)
 )
CREATETABLE
);
      //modification dates are important for some stuff
      set_mod_date('master_shifts');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_wanted_shifts() {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('wanted_shifts')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `wanted_shifts`
(`autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null default '',
 `shift` varchar(50) not null default '',
 `day` varchar(100) default null,
 `floor` varchar(10) default null,
 `rating` int(11) default null,
 primary key (`autoid`)
 )
CREATETABLE
);
      set_mod_date("{$which}_shifts");
    }
    else {
      $done = true;
    }
  }   
  return $done;
} 

function create_house_list () {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists("{$archive}house_list")) {
      $done = $db->Execute("CREATE TABLE IF NOT EXISTS " .
                           bracket("{$archive}house_list") .
<<<CREATETABLE
(
 `autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) NOT NULL,
 PRIMARY KEY (`autoid`),
 UNIQUE KEY (`member_name`))
CREATETABLE
                           );
      set_mod_date("house_list");
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_password_table() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists("{$archive}password_table")) {
      $done = $db->Execute("CREATE TABLE IF NOT EXISTS " . 
                   bracket("{$archive}password_table") .
<<<CREATETABLE
(
 `autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) NOT NULL, 
 `passwd` varchar(50) DEFAULT NULL, 
PRIMARY KEY (`autoid`),
UNIQUE KEY (`member_name`))
CREATETABLE
);
      set_mod_date("password_table");
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_officer_password_table() {
  global $db,$archive;
  if (!table_exists("{$archive}officer_password_table")) {
    $done = $db->Execute("CREATE TABLE IF NOT EXISTS " . 
                         bracket("{$archive}officer_password_table") .
<<<CREATETABLE
(
 `autoid` int(11) NOT NULL auto_increment,
 `officer_name` varchar(50) NOT NULL, 
 `passwd` varchar(50) DEFAULT NULL, 
PRIMARY KEY (`autoid`),
UNIQUE KEY (`officer_name`))
CREATETABLE
);
    set_mod_date("officer_password_table");
    return $done;
  }
  return true;
}

//av_0 through 6 store availability monday through sunday, encoded
//using the algorithm described in decode_avail.  submit_date gives
//when the preference form was submitted, for possible use by the
//workshift manager.
function create_personal_info() {
  global $db,$archive;
  static $done = false;
  if (true) {
    $done = $db->Execute('CREATE TABLE IF NOT EXISTS ' . 
                         bracket($archive . 'personal_info') . 
<<<CREATETABLE
(
 `autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) NOT NULL,
 `notes` longtext,
 `av_0` bigint(20) default NULL,
 `av_1` bigint(20) default NULL,
 `av_2` bigint(20) default NULL,
 `av_3` bigint(20) default NULL,
 `av_4` bigint(20) default NULL,
 `av_5` bigint(20) default NULL,
 `av_6` bigint(20) default NULL,
 `submit_date` timestamp default 0,
 PRIMARY KEY  (`autoid`), 
 UNIQUE KEY `member_name` (`member_name`))
CREATETABLE
                         );
    set_mod_date('personal_info');
  }
  return $done;
}

function create_house_info() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists('house_info')) {
      $done = $db->Execute("CREATE TABLE IF NOT EXISTS " . 
                           bracket($archive ."house_info") .
<<<CREATETABLE
(`autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) NOT NULL,
`room` tinytext,
`phone` varchar(50) default NULL,
`email` varchar(50) default NULL,
PRIMARY KEY  (`autoid`),
UNIQUE KEY (`member_name`))
CREATETABLE
                           );
      set_mod_date('house_info');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_workshift_description() {
  global $db;
  if (!table_exists('workshift_description')) {
    $done = $db->Execute("create table if not exists " .
                         "`workshift_description` (`description` longblob, `filename` varchar(100) default null)");
    set_mod_date('workshift_description');
    return $done;
  }
  return true;
}

function create_static_data() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists($archive . 'static_data')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `{$archive}static_data`
(
`autoid` int(11) NOT NULL auto_increment, 
`var_name` varchar(50) NOT NULL default '', 
`var_value` longtext default NULL,
PRIMARY KEY (`autoid`),
UNIQUE KEY `var_name` (`var_name`))
CREATETABLE
);
      set_mod_date('static_data');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_fining_periods() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists($archive . 'fining_periods')) {
      $done = $db->Execute('create table if not exists ' .
                           bracket($archive . "fining_periods") .
<<<CREATETABLE
(
 `autoid` int(11) not null auto_increment,
 `week` int(5) not null default '0',
 `fining_rate` double default null,
 `fining_buffer` double not null default '0',
 `fining_floor` double not null default '0',
 `fining_doublefloor` double default null,
 `zero_hours` int(1) default null,
 primary key (`autoid`),
 unique key `week` (`week`)
 )
CREATETABLE
                           );
      set_mod_date('fining_periods');
    }
    else {
      $done = true;
    }
  }
  return $done;
}


function create_fining_data($archive = '') {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('fining_data')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `{$archive}fining_data`
(`autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null,
 `fine` double default null,
 `date` date default null,
 `description` longtext default null,
 `week_cashed` int default null,
 `autoid_cashed` int(11) default null,
 `refundable` int default 1,
 primary key (`autoid`))
CREATETABLE
);
      set_mod_date('fining_data');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_fining_data_totals() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists('fining_data_totals')) {
      $done = $db->Execute("create table if not exists " .
                           bracket("{$archive}fining_data_totals") .
<<<CREATETABLE
(`autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null default '',
 `fines` double default 0 not null,
 primary key (`autoid`), unique (`member_name`))
CREATETABLE
                           );
      set_mod_date('fining_data');
    }
    else {
      $done = true;
    }
  }
  return $done;
}


function create_master_week() {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('master_week')) {
      $done = $db->Execute('create table if not exists ' .
                           bracket($archive . 'master_week') .
<<<CREATETABLE
(
 `autoid` int(11) not null auto_increment,
 `day` varchar(20) default null,
 `workshift` varchar(50) default null,
 `member_name` varchar(50) default null,
 `hours` double default null,
 `shift_id` int(11) default null,
`start_time` time default null,
`end_time` time default null)
CREATETABLE
                           );
      
      set_mod_date('master_week');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_modified_dates() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists('modified_dates')) {
      $this_tbl = bracket("{$archive}modified_dates");
      $done = $db->Execute(<<<CREATETABLE
                 create table if not exists $this_tbl
                 (`autoid` int(11) not null auto_increment,
                  `table_name` varchar(100) not null,
                  `mod_date` datetime default null,
                  primary key (`autoid`),
                  unique key (`table_name`))
CREATETABLE
                 );
    }
    else {
      $done = true;
    }
  }
  return $done;
}


function create_elections_record() {
  global $db;
  return $db->Execute(
<<<CREATETABLE
create table if not exists `elections_record`
(
 `autoid` int(11) NOT NULL auto_increment,
 `election_name` varchar(50) not null,
 `anon_voting` int(2) default null,
 `end_date` int(10) unsigned default NULL,
 PRIMARY KEY  (`autoid`),
 unique key (`election_name`)
 )
CREATETABLE
);
}

function create_current_voting_lock() {
  global $db;
  return $db->Execute(<<<CREATETABLE
create table if not exists `current_voting_lock`
(
 `autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null,
 `voting_name` varchar(50) not null,
 primary key (`autoid`)
 )
CREATETABLE
                      );
}

function create_voting_record() {
  global $db;
  return $db->Execute(<<<CREATETABLE
create table if not exists `voting_record`
(
 `autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null,
 `election_name` varchar(100) not null,
 `manual_entry` tinyint default null,
 primary key (`autoid`)
 )
CREATETABLE
                      );
}

function create_votes() {
  global $db;
  return $db->Execute(<<<CREATETABLE
create table if not exists `votes`
(
 `autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null,
 `election_name` varchar(100) not null,
 `option_name` varchar(100) not null,
 `option_choice` varchar(100) default null,
 primary key (`autoid`)
 )
CREATETABLE
                      );
}

function create_points() {
  global $db;
  static $done = false;
  if (true) {
    $done = $db->Execute(
<<<CREATEPOINTS
create table if not exists `points`
(
 `autoid` int(11) not null auto_increment,
 `member_name` varchar(50) not null,
 `category` char(2) default null,
 `points` double(5,2) unsigned not null default '0.00',
 `app_number` int(15) default null,
 `gender` int(1) default null,
 `squat_room` varchar(10) default null,
 `squatting` int(1) default null,
 `want_rooms` longblob,
 primary key (`autoid`),
 unique key (`member_name`))
CREATEPOINTS
);
    set_mod_date('points');
  }
  return $done;
}

function create_elections_attribs() {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('elections_attribs')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `elections_attribs`
(
 `autoid` int(11) NOT NULL auto_increment,
 `election_name` varchar(50) not null,
 `race_name` varchar(50) not null default '',
 `attrib_name` varchar(50) not null,
 `attrib_value` longblob default null,
 PRIMARY KEY  (`autoid`),
 unique key  (`election_name`,`race_name`,`attrib_name`)
 )
CREATETABLE
);
set_mod_date('elections_attribs');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_elections_text() {
  global $db, $archive;
  static $done = false;
  if (true) {
    if (!table_exists('elections_text')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `{$archive}elections_text`
(
 `autoid` int(11) NOT NULL auto_increment,
 `election_name` varchar(50) not null,
 `desc` longblob default null,
 `desc_html` tinyint default 0,
 `ballot_file` longblob default null,
 `ballot_name` varchar(50) default null,
 PRIMARY KEY  (`autoid`),
 unique key (`election_name`)
 )
CREATETABLE
);
set_mod_date('elections_text');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_userconf_data() {
  global $db, $archive;
  static $done = false;
  if (true) {
    if (!table_exists('userconf_data')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `{$archive}userconf_data`
(
`autoid` int(11) NOT NULL auto_increment, 
`page_name` varchar(50) not null default '',
`attrib_name` varchar(50) NOT NULL default '', 
`aux_key` varchar(50) default null,
`attrib_value` longtext default NULL,
PRIMARY KEY (`autoid`),
UNIQUE KEY `attrib_w_key` (`page_name`,`aux_key`,`attrib_name`))
CREATETABLE
);
      set_mod_date('userconf_data');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_elections_log() {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('elections_log')) {
      $done = $db->Execute(
<<<CREATETABLE
CREATE TABLE IF NOT EXISTS `elections_log` 
(
 `autoid` int(11) NOT NULL auto_increment,
 `time_entered` datetime not null,
 `election_name` varchar(50) default null,
 `subj_name` varchar(50) default null,
 `attrib` varchar(50) default null,
 `oldval` longblob default null,
 `val` longblob default null,
 PRIMARY KEY  (`autoid`)
 )
CREATETABLE
);
      set_mod_date('elections_log');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_privilege_table() {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('privilege_table')) {
      $done = $db->Execute(
<<<CREATETABLE
CREATE TABLE IF NOT EXISTS `privilege_table` 
(
 `autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) not null,
 `privileges` varchar(100) default '',
 PRIMARY KEY  (`autoid`),
 unique key (`member_name`)
 )
CREATETABLE
);
      set_mod_date('privileges_table');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_session_data() {
  global $db;
  static $done = false;
  if (true) {
    if (!table_exists('session_data')) {
      $done = $db->Execute(
<<<CREATETABLE
CREATE TABLE IF NOT EXISTS `session_data` 
(
 `autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) not null,
 `witnesses` longblob default null,
 `session_id` longblob not null,
 `expiration` datetime default null,
 PRIMARY KEY  (`autoid`),
 index(session_id(100))
 )
CREATETABLE
);
      set_mod_date('session_data');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

function create_special_fining() {
  global $db;
  static $done = false;
  if (!$done) {
    if (!table_exists('special_fining')) {
      $done = $db->Execute(
<<<CREATETABLE
CREATE TABLE IF NOT EXISTS `special_fining` 
(
 `autoid` int(11) NOT NULL auto_increment,
 `member_name` varchar(50) not null,
 `fine_week_1` int default -1,
 `fine_week_2` int default -1,
 `fine_week_3` int default -1,
 `fine_week_4` int default -1,
 `fine_week_5` int default -1,
 PRIMARY KEY  (`autoid`),
 unique key (`member_name`)
 )
CREATETABLE
);
      set_mod_date('special_fining');
    }
    else {
      $done = true;
    }
  }
  return $done;
}
  
function create_static_text() {
  global $db,$archive;
  static $done = false;
  if (true) {
    if (!table_exists('static_text')) {
      $done = $db->Execute(
<<<CREATETABLE
create table if not exists `{$archive}static_text`
(
`autoid` int(11) NOT NULL auto_increment, 
`text_name` varchar(100) NOT NULL default '', 
`text_value` longtext default NULL,
`is_html` int default 0,
`escape_seqs` longtext default null,
PRIMARY KEY (`autoid`),
UNIQUE KEY `text_name` (`text_name`))
CREATETABLE
);
      set_mod_date('static_text');
    }
    else {
      $done = true;
    }
  }
  return $done;
}

?>
