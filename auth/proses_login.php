<?php

session_start();

require_once "../config/database.php";

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

/*
|--------------------------------------------------------------------------
| Validasi input
|--------------------------------------------------------------------------
*/

if ($username === '' || $password === '') {
    header("Location: login.php?error=empty");
    exit;
}


/*
|--------------------------------------------------------------------------
| Ambil data user
|--------------------------------------------------------------------------
*/

$sql = "SELECT 
            u.id_user,
            u.username,
            u.password,
            u.nama_user,
            u.role,
            u.status
        FROM users u
        WHERE u.username = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query login gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $username
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);


/*
|--------------------------------------------------------------------------
| Username tidak ditemukan
|--------------------------------------------------------------------------
*/

if (!$user) {
    header("Location: login.php?error=login");
    exit;
}


/*
|--------------------------------------------------------------------------
| Cek status akun
|--------------------------------------------------------------------------
*/

if ($user['status'] !== 'AKTIF') {
    header("Location: login.php?error=inactive");
    exit;
}


/*
|--------------------------------------------------------------------------
| Verifikasi password
|--------------------------------------------------------------------------
*/

if (!password_verify($password, $user['password'])) {
    header("Location: login.php?error=login");
    exit;
}


/*
|--------------------------------------------------------------------------
| Regenerasi session untuk keamanan
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);


/*
|--------------------------------------------------------------------------
| Simpan data user ke session
|--------------------------------------------------------------------------
*/

$_SESSION['id_user'] = $user['id_user'];

$_SESSION['username'] = $user['username'];

$_SESSION['nama_user'] = $user['nama_user'];

$_SESSION['role'] = $user['role'];


/*
|--------------------------------------------------------------------------
| Redirect ke dashboard
|--------------------------------------------------------------------------
*/

header("Location: ../dashboard/index.php");

exit;