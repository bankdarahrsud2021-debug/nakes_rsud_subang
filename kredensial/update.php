<?php

include "../config/auth.php";
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_kredensial =
    (int)($_POST['id_kredensial'] ?? 0);

$id_nakes =
    (int)($_POST['id_nakes'] ?? 0);

$jenis_kredensial =
    trim($_POST['jenis_kredensial'] ?? '');

$tanggal_pengajuan =
    $_POST['tanggal_pengajuan'] ?? '';

$catatan =
    trim($_POST['catatan'] ?? '');

if (
    $id_kredensial <= 0 ||
    $id_nakes <= 0 ||
    $jenis_kredensial === '' ||
    $tanggal_pengajuan === ''
) {

    die("Data belum lengkap.");

}

$query = "
    UPDATE kredensial
    SET
        id_nakes = ?,
        jenis_kredensial = ?,
        tanggal_pengajuan = ?,
        catatan = ?,
        updated_at = NOW()
    WHERE id_kredensial = ?
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
    "isssi",
    $id_nakes,
    $jenis_kredensial,
    $tanggal_pengajuan,
    $catatan,
    $id_kredensial
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Gagal memperbarui data: " .
        mysqli_stmt_error($stmt)
    );

}