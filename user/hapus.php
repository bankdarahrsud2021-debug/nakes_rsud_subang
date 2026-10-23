<?php

require_once "../config/admin.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Ambil ID User
|--------------------------------------------------------------------------
*/

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {

    die("
        <div style='
            font-family: Arial;
            text-align: center;
            margin-top: 100px;
        '>
            <h2>ID User Tidak Valid</h2>

            <a href='index.php'>
                Kembali ke Manajemen User
            </a>
        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| Jangan hapus akun sendiri
|--------------------------------------------------------------------------
*/

if ($id == $_SESSION['id_user']) {

    die("
        <div style='
            font-family: Arial;
            text-align: center;
            margin-top: 100px;
        '>

            <h2 style='color:#dc3545;'>
                Tidak Dapat Menghapus Akun
            </h2>

            <p>
                Anda tidak dapat menghapus akun
                yang sedang digunakan untuk login.
            </p>

            <a href='index.php'>
                Kembali ke Manajemen User
            </a>

        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| Pastikan user tersedia
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_user, username, nama_user
     FROM users
     WHERE id_user = ?
     LIMIT 1"
);

if (!$stmt) {

    die(
        "Query gagal: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$user = mysqli_fetch_assoc($result);

if (!$user) {

    die("
        <div style='
            font-family: Arial;
            text-align: center;
            margin-top: 100px;
        '>

            <h2>User Tidak Ditemukan</h2>

            <a href='index.php'>
                Kembali ke Manajemen User
            </a>

        </div>
    ");

}


/*
|--------------------------------------------------------------------------
| Hapus User
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM users
     WHERE id_user = ?"
);

if (!$stmt) {

    die(
        "Query hapus gagal: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

if (mysqli_stmt_execute($stmt)) {

    header(
        "Location: index.php?hapus=berhasil"
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| Jika gagal
|--------------------------------------------------------------------------
*/

die(
    "Gagal menghapus user: " .
    mysqli_error($conn)
);