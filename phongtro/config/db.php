<?php 
$severname = "";
$username = "root";
$password = 1234;
$databasename = "phongtro_db";    

$conn = new mysqli($severname,$username,$password,$databasename);

if($conn->connect_error) {
    die ("conn fail".$conn->connect_error);
}
echo ""
?>