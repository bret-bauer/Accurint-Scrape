<?php
$db_user="ln"; $db_password="ln2016"; $db_database="ln";
$db = new mysqli("localhost", $db_user, $db_password, $db_database);
if($db->connect_errno > 0){
    die('Unable to connect to database [' . $db->connect_error . ']');
}
$db->autocommit(true);
?>