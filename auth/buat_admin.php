<?php

require_once "../config/database.php";

$username = "admin";
$password = "admin123";
$nama = "Administrator";

$hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$sql = "INSERT INTO users
        (username, password, nama_user, status)
        VALUES (?, ?, ?, 'AKTIF')";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "sss",
    $username,
    $hash,
    $nama
);

if (mysqli_stmt_execute($stmt)) {

    echo "<h2>Admin berhasil dibuat.</h2>";
    echo "<p>Username: <b>admin</b></p>";
    echo "<p>Password: <b>admin123</b></p>";
    echo "<p><strong>Hapus file buat_admin.php setelah selesai.</strong></p>";

} else {

    echo "Gagal membuat admin: " . mysqli_error($conn);
}