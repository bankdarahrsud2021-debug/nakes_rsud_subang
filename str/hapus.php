<?php

require_once "../config/auth.php";
require_once "../config/database.php";

// Pastikan ada ID STR
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=id");
    exit;
}

$id_str = (int) $_GET['id'];

// Ambil data STR terlebih dahulu
$stmt = mysqli_prepare(
    $conn,
    "SELECT file_str 
     FROM str_nakes 
     WHERE id_str = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_str);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Jika data tidak ditemukan
if (!$data) {
    header("Location: index.php?error=notfound");
    exit;
}

// Hapus data dari database
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM str_nakes 
     WHERE id_str = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_str);

if (mysqli_stmt_execute($stmt)) {

    // Hapus file STR jika ada
    if (!empty($data['file_str'])) {

        $file = "../uploads/str_nakes/" . $data['file_str'];

        if (file_exists($file)) {
            unlink($file);
        }
    }

    mysqli_stmt_close($stmt);

    header("Location: index.php?success=hapus");
    exit;

} else {

    mysqli_stmt_close($stmt);

    header("Location: index.php?error=hapus");
    exit;
}