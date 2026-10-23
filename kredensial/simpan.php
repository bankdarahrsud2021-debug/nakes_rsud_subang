<?php

include "../config/auth.php";
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_nakes = (int)($_POST['id_nakes'] ?? 0);

$jenis_kredensial = trim(
    $_POST['jenis_kredensial'] ?? ''
);

$tanggal_pengajuan =
    $_POST['tanggal_pengajuan'] ?? '';

$catatan =
    trim($_POST['catatan'] ?? '');

$status = "DIAJUKAN";

if (
    $id_nakes <= 0 ||
    $jenis_kredensial === '' ||
    $tanggal_pengajuan === ''
) {

    die("Data pengajuan belum lengkap.");

}

$query = "
    INSERT INTO kredensial
    (
        id_nakes,
        jenis_kredensial,
        tanggal_pengajuan,
        status,
        catatan,
        created_at,
        updated_at
    )
    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
";

$stmt = mysqli_prepare(
    $conn,
    $query
);

if (!$stmt) {

    die(
        "Prepare gagal: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "issss",
    $id_nakes,
    $jenis_kredensial,
    $tanggal_pengajuan,
    $status,
    $catatan
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Gagal menyimpan data: " .
        mysqli_stmt_error($stmt)
    );

}