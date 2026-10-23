<?php

require_once "../config/auth.php";
require_once "../config/database.php";


$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID indikator tidak valid.");
}


/* CEK APAKAH SUDAH DIGUNAKAN */

$cek = mysqli_prepare($conn, "

    SELECT COUNT(*) AS total

    FROM oppe_detail

    WHERE id_indikator = ?

");

mysqli_stmt_bind_param(
    $cek,
    "i",
    $id
);

mysqli_stmt_execute($cek);

$result = mysqli_stmt_get_result($cek);

$data = mysqli_fetch_assoc($result);


if ((int)$data['total'] > 0) {

    /*
       Jangan menghapus indikator yang sudah
       digunakan dalam penilaian.
    */

    die("

        <h3>Indikator tidak dapat dihapus</h3>

        <p>
        Indikator ini sudah digunakan dalam penilaian OPPE.
        </p>

        <p>
        Untuk menjaga riwayat penilaian,
        silakan ubah status indikator menjadi
        <strong>NONAKTIF</strong>.
        </p>

        <a href='indikator.php'>
            Kembali
        </a>

    ");

}


/* HAPUS */

$stmt = mysqli_prepare($conn, "

    DELETE FROM oppe_indikator

    WHERE id_indikator = ?

");

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: indikator.php?success=hapus");
    exit;

}


die(
    "Gagal menghapus indikator: "
    . mysqli_stmt_error($stmt)
);

?>