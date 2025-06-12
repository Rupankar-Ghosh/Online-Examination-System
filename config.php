
<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "examflow"; 



// $servername = "sql211.infinityfree.com";
// $username = "if0_38639085"; 
// $password = "examflow2005"; 
// $dbname = "if0_38639085_examflow"; 


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

