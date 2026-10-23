<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/* =========================
   CEK METHOD
========================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;

}


/* =========================
   AMBIL DATA
========================= */

$nama_periode   = trim($_POST['nama_periode'] ?? '');
$tanggal_mulai  = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
$status         = $_POST['status'] ?? 'DRAFT';
$keterangan     = trim($_POST['keterangan'] ?? '');


/* =========================
   VALIDASI
========================= */

if ($nama_periode === '') {

    die("Nama periode wajib diisi.");

}

if ($tanggal_mulai === '') {

    die("Tanggal mulai wajib diisi.");

}

if ($tanggal_selesai === '') {

    die("Tanggal selesai wajib diisi.");

}


if ($tanggal_selesai < $tanggal_mulai) {

    die("Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.");

}


$status_valid = [
    'DRAFT',
    'AKTIF',
    'SELESAI'
];

if (!in_array($status, $status_valid)) {

    die("Status periode tidak valid.");

}


/* =========================
   CEK PERIODE AKTIF
========================= */

if ($status === 'AKTIF') {

    $cek = mysqli_query($conn, "
        SELECT id_periode
        FROM oppe_periode
        WHERE status = 'AKTIF'
        LIMIT 1
    ");

    if (mysqli_num_rows($cek) > 0) {

        die("
            <h3>Periode OPPE Aktif Sudah Ada</h3>

            <p>
                Tidak dapat membuat periode baru dengan status AKTIF
                karena masih terdapat periode OPPE yang aktif.
            </p>

            <p>
                Silakan selesaikan periode sebelumnya terlebih dahulu.
            </p>

            <a href='index.php'>Kembali</a>
        ");

    }

}


/* =========================
   SIMPAN
========================= */

$stmt = mysqli_prepare($conn, "

    INSERT INTO oppe_periode
    (
        nama_periode,
        tanggal_mulai,
        tanggal_selesai,
        status,
        keterangan
    )

    VALUES (?, ?, ?, ?, ?)

");


if (!$stmt) {

    die("Prepare gagal: " . mysqli_error($conn));

}


mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $nama_periode,
    $tanggal_mulai,
    $tanggal_selesai,
    $status,
    $keterangan
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php?success=periode");
    exit;

} else {

    die(
        "Gagal menyimpan periode: "
        . mysqli_stmt_error($stmt)
    );

}

?>