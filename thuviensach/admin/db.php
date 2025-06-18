<?php 
$severname = "";
$username = "root";
$password = 1234;
$databasename = "thuviensach";    

$conn = new mysqli($severname,$username,$password,$databasename);

if($conn->connect_error) {
    die ("conn fail".$conn->connect_error);
}
echo "ket noi thanh cong 1"
?>