<?php
$servername = '151.106.124.154';
$username = 'u583789277_wag21';
$password = 'Coconut2567';
$dbname = 'u583789277_wag21';

// สร้างการเชื่อมต่อ
$connect = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

$connect->set_charset("utf8");
?>
