<?php
//just includes the higher-directory include file -- this is
//appropriate for any tree structure, so long as we don't have any
//directories out on their own.

//require president privilege on all php files in this directory
//unless they say otherwise
if (!isset($require_user)) {
  $require_user = 'president';
}
require_once('../../default.inc.php');
?>
