<?php
$host = 'localhost';                 
$db_name   = 'u239105410_kasihub';        
$db_user = 'u239105410_kasihubuser';   
$db_pass = 'An0827419040*';      


$mysqli = new mysqli($host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
  die("Database connection failed: " . $mysqli->connect_error);
}

?>
