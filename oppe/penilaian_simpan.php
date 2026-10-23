<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/* =========================================================
   HANYA POST
========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;

}


/* =========================================================
   AMBIL DATA FORM
========================================================= */

$id_periode = (int)($_POST['id_periode'] ?? 0);

$id_nakes = (int)($_POST['id_nakes'] ?? 0);

$tanggal_penilaian =
    trim($_POST['tanggal_penilaian'] ?? '');

$penilai =
    trim($_POST['penilai'] ?? '');


/* =========================================================
   VALIDASI DASAR
========================================================= */

if ($id_periode <= 0) {

    die("ID periode tidak valid.");

}

if ($id_nakes <= 0) {

    die("Tenaga kesehatan belum dipilih.");

}

if ($tanggal_penilaian === '') {

    die("Tanggal penilaian wajib diisi.");

}

if ($penilai === '') {

    die("Nama penilai wajib diisi.");

}


/* =========================================================
   VALIDASI FORMAT TANGGAL
========================================================= */

$tanggal_valid =
    DateTime::createFromFormat(
        'Y-m-d',
        $tanggal_penilaian
    );

if (
    !$tanggal_valid ||
    $tanggal_valid->format('Y-m-d') !==
    $tanggal_penilaian
) {

    die("Format tanggal penilaian tidak valid.");

}


/* =========================================================
   CEK PERIODE
========================================================= */

$stmt = mysqli_prepare($conn, "

    SELECT
        id_periode,
        nama_periode,
        tanggal_mulai,
        tanggal_selesai,
        status

    FROM oppe_periode

    WHERE id_periode = ?

    LIMIT 1

");

if (!$stmt) {

    die(
        "Gagal menyiapkan pemeriksaan periode: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_periode
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close($stmt);

    die("Periode OPPE tidak ditemukan.");

}

$periode =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   VALIDASI TANGGAL DALAM PERIODE
========================================================= */

if (
    !empty($periode['tanggal_mulai']) &&
    !empty($periode['tanggal_selesai'])
) {

    if (
        $tanggal_penilaian <
        $periode['tanggal_mulai'] ||
        $tanggal_penilaian >
        $periode['tanggal_selesai']
    ) {

        die(
            "Tanggal penilaian berada di luar periode OPPE."
        );

    }

}


/* =========================================================
   CEK TENAGA KESEHATAN
========================================================= */

$stmt = mysqli_prepare($conn, "

    SELECT
        t.id_nakes,
        t.nomor_induk,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        t.id_profesi,
        t.id_unit,
        t.status_aktif,

        p.nama_profesi,
        u.nama_unit

    FROM tenaga_kesehatan t

    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi

    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit

    WHERE t.id_nakes = ?

    LIMIT 1

");

if (!$stmt) {

    die(
        "Gagal menyiapkan pemeriksaan tenaga kesehatan: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_nakes
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

if (
    !$result ||
    mysqli_num_rows($result) === 0
) {

    mysqli_stmt_close($stmt);

    die("Tenaga kesehatan tidak ditemukan.");

}

$nakes =
    mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* =========================================================
   CEK STATUS AKTIF
========================================================= */

if (
    strtoupper(
        trim(
            $nakes['status_aktif'] ?? ''
        )
    ) !== 'AKTIF'
) {

    die(
        "Tenaga kesehatan yang dipilih tidak berstatus AKTIF."
    );

}


/* =========================================================
   CEK PROFESI
========================================================= */

$id_profesi =
    (int)($nakes['id_profesi'] ?? 0);

if ($id_profesi <= 0) {

    die(
        "Tenaga kesehatan belum memiliki profesi."
    );

}


/* =========================================================
   CEK PENILAIAN GANDA
========================================================= */

$stmt = mysqli_prepare($conn, "

    SELECT
        id_penilaian,
        status

    FROM oppe_penilaian

    WHERE id_periode = ?
      AND id_nakes = ?

    LIMIT 1

");

if (!$stmt) {

    die(
        "Gagal memeriksa penilaian sebelumnya: " .
        mysqli_error($conn)
    );

}

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_periode,
    $id_nakes
);

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

if (
    $result &&
    mysqli_num_rows($result) > 0
) {

    $penilaian_lama =
        mysqli_fetch_assoc($result);

    $id_penilaian_lama =
        (int)$penilaian_lama['id_penilaian'];

    mysqli_stmt_close($stmt);

    /*
     * Jika sudah ada, jangan membuat
     * penilaian baru.
     *
     * Langsung lanjutkan ke penilaian
     * yang sudah ada.
     */

    header(
        "Location: penilaian_form.php?id=" .
        $id_penilaian_lama
    );

    exit;

}

mysqli_stmt_close($stmt);


/* =========================================================
   TRANSAKSI
========================================================= */

mysqli_begin_transaction($conn);

try {


    /* =====================================================
       INSERT OPPE PENILAIAN
    ===================================================== */

    $status_awal = 'DRAFT';

    $total_nilai = 0;

    $nilai_akhir = 0;

    $kategori = '';

    $stmt = mysqli_prepare($conn, "

        INSERT INTO oppe_penilaian
        (
            id_nakes,
            id_periode,
            tanggal_penilaian,
            penilai,
            total_nilai,
            nilai_akhir,
            kategori,
            status
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )

    ");

    if (!$stmt) {

        throw new Exception(
            "Gagal menyiapkan penyimpanan penilaian: " .
            mysqli_error($conn)
        );

    }

    mysqli_stmt_bind_param(
        $stmt,
        "iissddss",
        $id_nakes,
        $id_periode,
        $tanggal_penilaian,
        $penilai,
        $total_nilai,
        $nilai_akhir,
        $kategori,
        $status_awal
    );

    if (!mysqli_stmt_execute($stmt)) {

        throw new Exception(
            "Gagal menyimpan penilaian OPPE: " .
            mysqli_stmt_error($stmt)
        );

    }

    $id_penilaian =
        mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);


    /* =====================================================
       SELESAI
    ===================================================== */

    mysqli_commit($conn);


    /* =====================================================
       REDIRECT KE FORM PENILAIAN
    ===================================================== */

    header(
        "Location: penilaian_form.php?id=" .
        (int)$id_penilaian
    );

    exit;


} catch (Throwable $e) {


    /* =====================================================
       ROLLBACK
    ===================================================== */

    mysqli_rollback($conn);


    die(
        "Terjadi kesalahan saat menyimpan penilaian OPPE:<br><br>" .
        htmlspecialchars(
            $e->getMessage()
        )
    );

}