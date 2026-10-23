<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "db_nakes_rsud_subang";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>