<?php

require_once "../config/auth.php";
require_once "../config/database.php";


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: indikator.php");
    exit;

}


$id_profesi = (int)($_POST['id_profesi'] ?? 0);

$nama_indikator = trim(
    $_POST['nama_indikator'] ?? ''
);

$deskripsi = trim(
    $_POST['deskripsi'] ?? ''
);

$bobot = (float)(
    $_POST['bobot'] ?? 1
);

$nilai_maksimal = (int)(
    $_POST['nilai_maksimal'] ?? 4
);

$status_aktif = (int)(
    $_POST['status_aktif'] ?? 1
);


/* =========================
   VALIDASI
========================= */

if ($nama_indikator === '') {

    die("Nama indikator wajib diisi.");

}

if ($bobot <= 0) {

    die("Bobot harus lebih besar dari 0.");

}

if (!in_array(
    $nilai_maksimal,
    [4, 5, 10, 100]
)) {

    die("Nilai maksimal tidak valid.");

}

if (!in_array(
    $status_aktif,
    [0, 1]
)) {

    die("Status tidak valid.");

}


/* =========================
   CEK DUPLIKAT
========================= */

$stmt_cek = mysqli_prepare($conn, "

    SELECT id_indikator

    FROM oppe_indikator

    WHERE id_profesi = ?
    AND nama_indikator = ?

    LIMIT 1

");

mysqli_stmt_bind_param(
    $stmt_cek,
    "is",
    $id_profesi,
    $nama_indikator
);

mysqli_stmt_execute($stmt_cek);

$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) > 0) {

    die("
        <h3>Indikator sudah ada.</h3>

        <p>
        Indikator dengan profesi tersebut
        sudah terdaftar.
        </p>

        <a href='indikator.php'>
            Kembali
        </a>
    ");

}


/* =========================
   SIMPAN
========================= */

$stmt = mysqli_prepare($conn, "

    INSERT INTO oppe_indikator
    (
        id_profesi,
        nama_indikator,
        deskripsi,
        bobot,
        nilai_maksimal,
        status_aktif
    )

    VALUES (?, ?, ?, ?, ?, ?)

");


if (!$stmt) {

    die(
        "Prepare gagal: "
        . mysqli_error($conn)
    );

}


mysqli_stmt_bind_param(
    $stmt,
    "issdii",
    $id_profesi,
    $nama_indikator,
    $deskripsi,
    $bobot,
    $nilai_maksimal,
    $status_aktif
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: indikator.php?success=1");
    exit;

}


die(
    "Gagal menyimpan indikator: "
    . mysqli_stmt_error($stmt)
);

?>