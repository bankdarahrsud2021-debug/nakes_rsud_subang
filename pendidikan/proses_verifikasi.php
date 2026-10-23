<?php

require_once "../config/auth.php";
require_once "../config/database.php";
$role = $_SESSION['role'] ?? 'ANGGOTA';

if ($role !== 'ADMIN' && $role !== 'PENGURUS') {

    die("
        <div style='
            font-family: Arial;
            text-align: center;
            margin-top: 100px;
        '>

            <h2 style='color:#dc3545;'>
                Akses Ditolak
            </h2>

            <p>
                Anda tidak memiliki hak akses
                untuk melakukan proses verifikasi Pendidikan.
            </p>

            <a href='../dashboard/index.php'
               style='
                   display:inline-block;
                   padding:10px 18px;
                   background:#0d6efd;
                   color:white;
                   text-decoration:none;
                   border-radius:6px;
               '>
                Kembali ke Dashboard
            </a>

        </div>
    ");

}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_pendidikan = (int)(
    $_POST['id_pendidikan'] ?? 0
);

$status_verifikasi = strtoupper(
    trim(
        $_POST['status_verifikasi'] ?? ''
    )
);

$id_user = (int)(
    $_SESSION['id_user'] ?? 0
);


/*
|--------------------------------------------------------------------------
| Validasi
|--------------------------------------------------------------------------
*/

if ($id_pendidikan <= 0) {
    die("ID pendidikan tidak valid.");
}

if ($id_user <= 0) {
    die("User tidak valid.");
}

$status_valid = [
    'BELUM DIVERIFIKASI',
    'TERVERIFIKASI',
    'DITOLAK'
];

if (!in_array(
    $status_verifikasi,
    $status_valid
)) {
    die("Status verifikasi tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Pastikan data ada
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pendidikan
     FROM pendidikan_nakes
     WHERE id_pendidikan = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_pendidikan
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    mysqli_stmt_close($stmt);
    die("Data pendidikan tidak ditemukan.");
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Update verifikasi
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE pendidikan_nakes
    SET
        status_verifikasi = ?,
        verified_by = ?,
        verified_at = NOW()
    WHERE id_pendidikan = ?
";

$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {
    die(
        "Prepare gagal: " .
        mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "sii",
    $status_verifikasi,
    $id_user,
    $id_pendidikan
);


if (!mysqli_stmt_execute($stmt)) {

    die(
        "Gagal menyimpan verifikasi: " .
        mysqli_stmt_error($stmt)
    );
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Kembali
|--------------------------------------------------------------------------
*/

header(
    "Location: index.php?success=verifikasi"
);

exit;