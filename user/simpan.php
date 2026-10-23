<?php

require_once "../config/admin.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$username  = trim($_POST['username'] ?? '');
$nama_user = trim($_POST['nama_user'] ?? '');
$password  = $_POST['password'] ?? '';
$role      = $_POST['role'] ?? 'ANGGOTA';
$status    = $_POST['status'] ?? 'AKTIF';


/*
|--------------------------------------------------------------------------
| Validasi data
|--------------------------------------------------------------------------
*/

if (
    $username === '' ||
    $nama_user === '' ||
    $password === ''
) {
    die("Data belum lengkap.");
}

if (strlen($password) < 6) {
    die("Password minimal 6 karakter.");
}


/*
|--------------------------------------------------------------------------
| Validasi Role
|--------------------------------------------------------------------------
*/

if (!in_array($role, ['ADMIN', 'PENGURUS', 'ANGGOTA'], true)) {
    die("Role tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Validasi Status
|--------------------------------------------------------------------------
*/

if (!in_array($status, ['AKTIF', 'NONAKTIF'], true)) {
    die("Status tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Cek username
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_user
     FROM users
     WHERE username = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Query cek username gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    die(
        "Username sudah digunakan. " .
        "<a href='tambah.php'>Kembali</a>"
    );
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Hash Password
|--------------------------------------------------------------------------
*/

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/*
|--------------------------------------------------------------------------
| Simpan User
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO users
    (
        username,
        password,
        nama_user,
        role,
        status
    )
    VALUES (?, ?, ?, ?, ?)"
);

if (!$stmt) {
    die("Query simpan user gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $username,
    $password_hash,
    $nama_user,
    $role,
    $status
);


if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header(
        "Location: index.php?simpan=berhasil"
    );

    exit;
}


die(
    "Gagal menyimpan user: " .
    mysqli_error($conn)
);