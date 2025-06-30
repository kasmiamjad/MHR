<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
$db_host = 'localhost';

$db_user = 'yszraxwq_wp63338';

$db_pass = ']7v4]C1MSp';

$db_name = 'yszraxwq_mhr';



$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);



if ($conn->connect_error) {

    die("Connection failed: " . $conn->connect_error);

} 



// Set charset

$conn->set_charset("utf8mb4");

?>