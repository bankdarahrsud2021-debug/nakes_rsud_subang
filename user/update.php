<?php

require_once "../config/admin.php";
require_once "../config/database.php";

$id = intval($_POST['id_user'] ?? 0);

$username = trim($_POST['username'] ?? '');
$nama_user = trim($_POST['nama_user'] ?? '');
$role = $_POST['role'] ?? 'USER';
$status = $_POST['status'] ?? 'AKTIF';

if ($id <= 0) {
    die("ID user tidak valid.");
}

if ($username === '' || $nama_user === '') {
    die("Data belum lengkap.");
}

if (!in_array($role, ['ADMIN', 'USER'], true)) {
    die("Role tidak valid.");
}

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
     AND id_user != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $stmt,
    "si",
    $username,
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    die(
        "Username sudah digunakan user lain. " .
        "<a href='edit.php?id=$id'>Kembali</a>"
    );
}


/*
|--------------------------------------------------------------------------
| Jangan izinkan akun sendiri dinonaktifkan
|--------------------------------------------------------------------------
*/

if (
    $id == $_SESSION['id_user'] &&
    $status === 'NONAKTIF'
) {

    die(
        "Anda tidak dapat menonaktifkan akun sendiri."
    );
}


/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "UPDATE users
     SET
        username = ?,
        nama_user = ?,
        role = ?,
        status = ?
     WHERE id_user = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ssssi",
    $username,
    $nama_user,
    $role,
    $status,
    $id
);

if (mysqli_stmt_execute($stmt)) {

    /*
    |--------------------------------------------------------------------------
    | Update session jika akun sendiri diedit
    |--------------------------------------------------------------------------
    */

    if ($id == $_SESSION['id_user']) {

        $_SESSION['username'] = $username;

        $_SESSION['nama_user'] = $nama_user;
    }

    header(
        "Location: index.php?update=berhasil"
    );

    exit;
}

die(
    "Gagal memperbarui user: " .
    mysqli_error($conn)
);