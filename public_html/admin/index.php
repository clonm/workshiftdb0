<html><head><title>Workshift Manager's links</title></head><body>
<?php
#emacs comment';
$require_user = array('workshift','house','president');
require_once('default.inc.php');
?>
<h4><a href='help.html' target='help'>Read the help</a>
for how to get started.</h4>
<?php
$browser = browser_detection('full');
if ($browser[0] != 'moz') {
?>
 <h3>This site works best with 
<a href='http://www.mozilla.com/'>Mozilla Firefox</a>.
Other browsers may or may not work.  In particular, Internet Explorer
will not work for updating weekly sheets, and for lots of other things.
Please <a href='http://www.mozilla.com/'>download Mozilla Firefox</a>!</h3>
<?php
                                                         }
if ($archive) {
print "<h2>Viewing backup " . escape_html($archive) . "</h2>";
}
?>
<ul>
<li><h3>Daily/Weekly actions</h3>
<form action='week.php<?=$archive?'?archive=' . escape_html($archive):''?>' method=get>
<input type=submit value='Edit week '>
<input type=text size=2 value=0 name=week>
<?php
if ($archive) {
  print "<input type=hidden name='archive' value='" . escape_html($archive) . "'>";
}
?>
</form>
<a href="../public_utils/weekly_totals_print.php<?=$archive?'?archive=' . escape_html($archive):''?>">Print out weekly totals</a><br>
<a href="weekly_totals_update.php<?=$archive?'?archive=' . escape_html($archive):''?>">Update hours owed and notes for
weekly totals</a><br>
<li><h3>Semi-regular actions</h3>
<a href='person.admin.php<?=$archive?'?archive=' . escape_html($archive):''?>'>View a person's shift history at a glance</a><br>
<a href='house_fines.php<?=$archive?'?archive=' . escape_html($archive):''?>'>View/print/download the fines for the house</a><br>
<a href="fining_data.php<?=$archive?'?archive=' . escape_html($archive):''?>">Manually enter a fine for a member</a><br>
<a href='special_fining.php<?=$archive?'?archive=' . escape_html($archive):''?>'>Change the week a fining period ends for
a specific member</a><br>
<li><h3>Beginning of semester</h3>
<?php
if (!$archive) {
?>
<a href="basic_consts.php">Set basic parameters -- start of semester, 
preferences due date, etc., and possibly clear out old weekly sheets</a><br>
<a href="update_house.php">Update the house list</a><br>
<a href="weekly_totals_consts.php<?=$archive?'?archive=' . escape_html($archive):''?>">Set buffer, floor, and
fining rate for weekly totals</a><br>
<a href="set_shift_descriptions.php">Give descriptions of
workshifts</a><br>
<a href="upload_workshift_doc.php">Upload a workshift policy
document</a><br>
<a href="online_signoff_setup.php">Set up online signoffs, if you want
your house to use them.</a><br/>
<?php
}
?>
<ul><li><h4>Assigning workshifts</h4>
<a href="assign_shifts.php<?=$archive?'?archive=' . escape_html($archive):''?>">with all warnings</a><br>
<a href="assign_shifts.php<?=$archive?'?archive=' . escape_html($archive) . '&':'?'?>suppress_first">suppressing initial warnings</a><br>
<a href="assign_shifts.php<?=$archive?'?archive=' . escape_html($archive) . '&':'?'?>suppress_all">with no warnings</a><br>
<a href="master_shifts.php<?=$archive?'?archive=' . escape_html($archive) . '&':'?'?>">without sidebar of names</a><br>
<a href="master_shifts.php<?=$archive?'?archive=' . escape_html($archive) . '&':'?'?>suppress_first">without sidebar of names,
suppressing initial warnings</a><br>
<a href="master_shifts.php<?=$archive?'?archive=' . escape_html($archive) . '&':'?'?>suppress_all">without sidebar of names,
with no warnings</a><br>
</ul>
<a href="show_prefs.php<?=$archive?'?archive=' . escape_html($archive):''?>">View the preference form of a member</a><br>
<a href="../public_utils/master_shifts_print.php<?=$archive?'?archive=' . escape_html($archive):''?>">Print out master
table of workshifts (you may have to change Page Setup to print
background colors to get the grey to print properly)</a><br>
<a href="../public_utils/shifts_by_name.php<?=$archive?'?archive=' . escape_html($archive):''?>">Print out shifts by name</a><br>
<?php
if (!$archive) {
?>
<a href="signoff.php<?=$archive?'?archive=' . escape_html($archive):''?>">Print out signoff sheets</a><br>
<?php
}
?>
<li><h3>Utilities</h3>
<a href="show_emails.php<?=$archive?'?archive=' . escape_html($archive):''?>">Show emails of house members</a><br>
<?php
if (!$archive) {
?>
<a href="set_passwd.php">Change your password</a><br>
<a href="reset_user_password.php">Reset the password of a user who has
forgotten it</a><br>
<a href='administer_users.php'>Add/remove workshift privilege from users</a>.
This allows a user to administer site without logging in as 
<?=$officer_name?$officer_name:$house_name . 'workshift'?>.  You should definitely
do this if you have multiple workshift managers, and you may find it convenient
even if it's just you, since you don't need to log in separately to administer the site.

<hr><h4>Backup/Restore</h4>
<a href="backup_database.php">Backup database</a><br>
<a href="view_backup_database.php">View a backup as if it were current</a><br/>
<a href="recover_backup_database.php">If you screwed up and need to
recover a backed-up database, do it here.</a><br>
<a href="delete_backup_database.php">Delete a backup if you don't need
it anymore and are running out of space</a><br>
<!--
<a href="weekly_totals_update.php?getarchive">Update hours owed and
notes for the weekly totals of a backed-up database</a><br>
<a href="../public_utils/weekly_totals_print.php?getarchive">Print out the weekly
totals of a backed-up database</a><br>
-->
<a href="export_csv.php">Click here to download your entire database
as a zipfile of .csv files that you can import into Excel</a><br>
<!-- <a href="delete_weeks.php">If necessary, delete a week so it can be
regenerated above</a><br>-->
<a href="recover_backup_week.php">If you screwed up and need to
recover a just-deleted week (you were asked if you wanted to re-create
the week, but you didn't actually want to), do it here.</a><br>
<a href="table_edit.wrapper.wrapper.php">View/edit any table</a><br>
</ul>
<?php
}
?>
</body></html>
