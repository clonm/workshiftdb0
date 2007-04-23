<?php
include_once('janakdb/janakdb.inc.php');
$db->debug = true;

$pres = array($president,$president_loc);
$db->Execute("use $president_db");
$db->Execute("grant all on * to ?@? identified by ?",array($president,$president_loc,'bylaws'));
$db->Execute("grant reload on *.* to ?@?",$pres);
$db->Execute("grant create on *.* to ?@?",$pres);
$db->Execute("grant execute on procedure house.backup_table to ?@?",$pres);
$db->Execute("grant execute on procedure house.require_passwd to ?@?",$pres);
$db->Execute("DROP PROCEDURE IF EXISTS delete_old_vote");
$db->Execute("CREATE PROCEDURE delete_old_vote (member_name VARCHAR(50),
passwd varchar(50),election_name varchar(50)) sql security definer
begin
CALL " . bracket($main_db) . "." . bracket('require_passwd') . " (mem_name,pass);
DELETE FROM " . bracket('votes') . " WHERE " .
bracket('member_name') . " = mem_name AND election_name = ;
DELETE FROM " . bracket('unwanted_shifts') . " WHERE " .
               bracket('member_name') . " = mem_name;
END");
}
