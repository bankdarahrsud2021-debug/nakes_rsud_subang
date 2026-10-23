<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Metode akses tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Ambil data
|--------------------------------------------------------------------------
*/
$id_kasus = (int)($_POST['id_kasus'] ?? 0);

$nomor_kasus = trim(
    $_POST['nomor_kasus'] ?? ''
);

$tanggal_laporan = trim(
    $_POST['tanggal_laporan'] ?? ''
);

$id_nakes_raw = trim(
    $_POST['id_nakes'] ?? ''
);

$pelapor = trim(
    $_POST['pelapor'] ?? ''
);

$hubungan_pelapor = trim(
    $_POST['hubungan_pelapor'] ?? ''
);

$jenis_masalah = trim(
    $_POST['jenis_masalah'] ?? ''
);

$klasifikasi_etik = trim(
    $_POST['klasifikasi_etik'] ?? ''
);

$sumber_laporan = trim(
    $_POST['sumber_laporan'] ?? ''
);

$tingkat_kasus = trim(
    $_POST['tingkat_kasus'] ?? ''
);

$uraian_kasus = trim(
    $_POST['uraian_kasus'] ?? ''
);

$hasil_telaah = trim(
    $_POST['hasil_telaah'] ?? ''
);

$rekomendasi = trim(
    $_POST['rekomendasi'] ?? ''
);

$keputusan = trim(
    $_POST['keputusan'] ?? ''
);

$catatan = trim(
    $_POST['catatan'] ?? ''
);


/*
|--------------------------------------------------------------------------
| Validasi dasar
|--------------------------------------------------------------------------
*/
if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

if ($nomor_kasus === '') {
    die("Nomor kasus wajib diisi.");
}

if ($tanggal_laporan === '') {
    die("Tanggal laporan wajib diisi.");
}

if ($pelapor === '') {
    die("Nama pelapor wajib diisi.");
}

if ($jenis_masalah === '') {
    die("Jenis masalah wajib diisi.");
}

if ($uraian_kasus === '') {
    die("Uraian/kronologi wajib diisi.");
}


/*
|--------------------------------------------------------------------------
| ID nakes
|--------------------------------------------------------------------------
*/
$id_nakes = null;

if ($id_nakes_raw !== '') {

    $id_nakes = (int)$id_nakes_raw;

    if ($id_nakes <= 0) {
        $id_nakes = null;
    }
}


/*
|--------------------------------------------------------------------------
| Cek kasus
|--------------------------------------------------------------------------
*/
$sql_cek = "
    SELECT id_kasus
    FROM etik_kasus
    WHERE id_kasus = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql_cek);

if (!$stmt) {
    die("Query cek kasus gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kasus
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$cek_kasus = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$cek_kasus) {
    die("Kasus tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Cek nomor kasus duplikat
|--------------------------------------------------------------------------
*/
$sql_dup = "
    SELECT id_kasus
    FROM etik_kasus
    WHERE nomor_kasus = ?
    AND id_kasus <> ?
    LIMIT 1
";

$stmt_dup = mysqli_prepare($conn, $sql_dup);

if (!$stmt_dup) {
    die("Query duplikasi gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_dup,
    "si",
    $nomor_kasus,
    $id_kasus
);

mysqli_stmt_execute($stmt_dup);

$result_dup = mysqli_stmt_get_result($stmt_dup);

$duplikat = mysqli_fetch_assoc($result_dup);

mysqli_stmt_close($stmt_dup);

if ($duplikat) {
    die(
        "Nomor kasus sudah digunakan oleh kasus lain."
    );
}


/*
|--------------------------------------------------------------------------
| Jika ada tenaga kesehatan, cek keberadaannya
|--------------------------------------------------------------------------
*/
if ($id_nakes !== null) {

    $sql_nakes = "
        SELECT id_nakes
        FROM tenaga_kesehatan
        WHERE id_nakes = ?
        LIMIT 1
    ";

    $stmt_nakes = mysqli_prepare(
        $conn,
        $sql_nakes
    );

    if (!$stmt_nakes) {
        die("Query tenaga kesehatan gagal: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt_nakes,
        "i",
        $id_nakes
    );

    mysqli_stmt_execute($stmt_nakes);

    $result_nakes =
        mysqli_stmt_get_result($stmt_nakes);

    $cek_nakes =
        mysqli_fetch_assoc($result_nakes);

    mysqli_stmt_close($stmt_nakes);

    if (!$cek_nakes) {
        die(
            "Tenaga kesehatan tidak ditemukan."
        );
    }
}


/*
|--------------------------------------------------------------------------
| Update
|--------------------------------------------------------------------------
*/
$sql_update = "
    UPDATE etik_kasus
    SET
        nomor_kasus = ?,
        tanggal_laporan = ?,
        id_nakes = ?,
        pelapor = ?,
        hubungan_pelapor = ?,
        jenis_masalah = ?,
        klasifikasi_etik = ?,
        uraian_kasus = ?,
        sumber_laporan = ?,
        tingkat_kasus = ?,
        hasil_telaah = ?,
        rekomendasi = ?,
        keputusan = ?,
        catatan = ?
    WHERE id_kasus = ?
";

$stmt_update = mysqli_prepare(
    $conn,
    $sql_update
);

if (!$stmt_update) {
    die(
        "Prepare update gagal: " .
        mysqli_error($conn)
    );
}


mysqli_stmt_bind_param(
    $stmt_update,
    "ssisssssssssssi",
    $nomor_kasus,
    $tanggal_laporan,
    $id_nakes,
    $pelapor,
    $hubungan_pelapor,
    $jenis_masalah,
    $klasifikasi_etik,
    $uraian_kasus,
    $sumber_laporan,
    $tingkat_kasus,
    $hasil_telaah,
    $rekomendasi,
    $keputusan,
    $catatan,
    $id_kasus
);


if (!mysqli_stmt_execute($stmt_update)) {

    die(
        "Gagal memperbarui kasus: " .
        mysqli_stmt_error($stmt_update)
    );

}

mysqli_stmt_close($stmt_update);


/*
|--------------------------------------------------------------------------
| Kembali ke detail
|--------------------------------------------------------------------------
*/
header(
    "Location: detail.php?id=" .
    $id_kasus
);

exit;