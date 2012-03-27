<?php

//Where errors are sent
$admin_email = 'workshift.system@gmail.com';

//------- Settings for connecting to the MySQL database

//What is the server to connect to?
//if running mysql on a local pipe, this should just be '.'
$db_server = 'db';

//What is the mysql user to connect to the databases
$db_user = 'bsc1933';

//You can set the password to connect here, or put it in workshift_pass.txt
$db_password = null;

//All databases are supposed to start with the same string, terminating in the
//house code. This is that string.
$db_basename = 'bsc1933_shift';

//house is determined by the url.  However, which part of the url to use is not
//so clear -- it'll vary server to server, based on the setup of the aliases.
//Set the number below to the segment of the url that is used.
//For instance, in the url http://workshift.bsc.coop/stb/admin/ the house
//name, stb, is the first component. In the url
//http://www.bsc.coop/workshift/stb/admin/ the house name is the second
//component.
$house_name_component = 2;
