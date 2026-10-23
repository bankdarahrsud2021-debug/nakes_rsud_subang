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
                untuk melakukan proses verifikasi STR.
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


/*
|--------------------------------------------------------------------------
| HANYA BOLEH POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL DATA FORM
|--------------------------------------------------------------------------
*/

$id_str = isset($_POST['id_str'])
    ? (int)$_POST['id_str']
    : 0;

$status_verifikasi = strtoupper(
    trim($_POST['status'] ?? '')
);


/*
|--------------------------------------------------------------------------
| VALIDASI ID
|--------------------------------------------------------------------------
*/

if ($id_str <= 0) {

    die("ID STR tidak valid.");
}


/*
|--------------------------------------------------------------------------
| VALIDASI STATUS VERIFIKASI
|--------------------------------------------------------------------------
*/

$allowed_status = [
    'TERVERIFIKASI',
    'DITOLAK'
];

if (
    !in_array(
        $status_verifikasi,
        $allowed_status,
        true
    )
) {

    header(
        "Location: verifikasi.php?id="
        . $id_str
        . "&error=status"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| CEK DATA STR
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_str
     FROM str_nakes
     WHERE id_str = ?"
);

if (!$stmt) {

    die(
        "Prepare query gagal: "
        . mysqli_error($conn)
    );
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_str
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$data) {

    header(
        "Location: index.php?error=notfound"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| AMBIL ID USER LOGIN
|--------------------------------------------------------------------------
*/

$id_user = isset($_SESSION['id_user'])
    ? (int)$_SESSION['id_user']
    : 0;


if ($id_user <= 0) {

    die(
        "User login tidak ditemukan. "
        . "Silakan login kembali."
    );
}


/*
|--------------------------------------------------------------------------
| UPDATE STATUS VERIFIKASI
|--------------------------------------------------------------------------
|
| PENTING:
|
| status              = status masa berlaku STR
| status_verifikasi   = hasil verifikasi
|
|--------------------------------------------------------------------------
*/

$stmt = mysqli_prepare(
    $conn,
    "UPDATE str_nakes
     SET
        status_verifikasi = ?,
        verified_by = ?,
        verified_at = NOW(),
        updated_at = NOW()
     WHERE id_str = ?"
);


if (!$stmt) {

    die(
        "Prepare UPDATE gagal: "
        . mysqli_error($conn)
    );
}


/*
|--------------------------------------------------------------------------
| PARAMETER
|--------------------------------------------------------------------------
|
| s = status_verifikasi
| i = id_user
| i = id_str
|
|--------------------------------------------------------------------------
*/

mysqli_stmt_bind_param(
    $stmt,
    "sii",
    $status_verifikasi,
    $id_user,
    $id_str
);


/*
|--------------------------------------------------------------------------
| EKSEKUSI
|--------------------------------------------------------------------------
*/

if (mysqli_stmt_execute($stmt)) {

    mysqli_stmt_close($stmt);

    header(
        "Location: index.php?success=verifikasi"
    );

    exit;

} else {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die(
        "Gagal menyimpan verifikasi STR: "
        . htmlspecialchars($error)
    );
}