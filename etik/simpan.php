<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/* =========================================================
   CEK METHOD
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}


/* =========================================================
   AMBIL DATA FORM
   ========================================================= */

$nomor_kasus       = trim($_POST['nomor_kasus'] ?? '');
$tanggal_laporan   = $_POST['tanggal_laporan'] ?? '';
$id_nakes          = (int)($_POST['id_nakes'] ?? 0);
$pelapor           = trim($_POST['pelapor'] ?? '');
$hubungan_pelapor  = trim($_POST['hubungan_pelapor'] ?? '');
$jenis_masalah     = trim($_POST['jenis_masalah'] ?? '');
$klasifikasi_etik  = trim($_POST['klasifikasi_etik'] ?? '');
$sumber_laporan    = trim($_POST['sumber_laporan'] ?? '');
$tingkat_kasus     = trim($_POST['tingkat_kasus'] ?? '');
$uraian_kasus      = trim($_POST['uraian_kasus'] ?? '');
$catatan           = trim($_POST['catatan'] ?? '');


/* =========================================================
   VALIDASI
   ========================================================= */

if ($nomor_kasus === '') {
    die("Nomor kasus wajib diisi.");
}

if ($tanggal_laporan === '') {
    die("Tanggal laporan wajib diisi.");
}

if ($id_nakes <= 0) {
    die("Tenaga kesehatan wajib dipilih.");
}

if ($pelapor === '') {
    die("Nama pelapor wajib diisi.");
}

if ($jenis_masalah === '') {
    die("Jenis masalah wajib dipilih.");
}

if ($uraian_kasus === '') {
    die("Uraian kasus wajib diisi.");
}


/* =========================================================
   CEK NOMOR KASUS
   ========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_kasus
     FROM etik_kasus
     WHERE nomor_kasus = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Gagal menyiapkan pemeriksaan nomor kasus: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "s",
    $nomor_kasus
);

if (!mysqli_stmt_execute($stmt)) {
    die("Gagal memeriksa nomor kasus: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {

    mysqli_stmt_close($stmt);

    echo "
    <script>
        alert('Nomor kasus sudah digunakan. Silakan gunakan nomor kasus lain.');
        window.history.back();
    </script>
    ";

    exit;
}

mysqli_stmt_close($stmt);


/* =========================================================
   CEK TENAGA KESEHATAN
   ========================================================= */

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_nakes
     FROM tenaga_kesehatan
     WHERE id_nakes = ?
     LIMIT 1"
);

if (!$stmt) {
    die("Gagal menyiapkan pemeriksaan tenaga kesehatan: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_nakes
);

if (!mysqli_stmt_execute($stmt)) {
    die("Gagal memeriksa tenaga kesehatan: " . mysqli_stmt_error($stmt));
}

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    die("Tenaga kesehatan dengan ID tersebut tidak ditemukan.");

}

mysqli_stmt_close($stmt);


/* =========================================================
   USER LOGIN
   ========================================================= */

$created_by = null;

if (isset($_SESSION['id_user'])) {
    $created_by = (int)$_SESSION['id_user'];
}


/* =========================================================
   SIMPAN DATA KASUS
   ========================================================= */

$sql = "
    INSERT INTO etik_kasus
    (
        nomor_kasus,
        tanggal_laporan,
        id_nakes,
        pelapor,
        hubungan_pelapor,
        jenis_masalah,
        klasifikasi_etik,
        uraian_kasus,
        sumber_laporan,
        tingkat_kasus,
        status_kasus,
        catatan,
        created_by
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
        ?,
        ?,
        ?,
        'DILAPORKAN',
        ?,
        ?
    )
";


$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die(
        "Gagal menyiapkan penyimpanan kasus:<br><br>" .
        htmlspecialchars(mysqli_error($conn))
    );
}


/*
 * 12 parameter:
 *
 * 1  nomor_kasus       = s
 * 2  tanggal_laporan   = s
 * 3  id_nakes          = i
 * 4  pelapor           = s
 * 5  hubungan          = s
 * 6  jenis_masalah     = s
 * 7  klasifikasi       = s
 * 8  uraian            = s
 * 9  sumber            = s
 * 10 tingkat           = s
 * 11 catatan           = s
 * 12 created_by        = i
 *
 * Jadi tipe:
 * s s i s s s s s s s s i
 */

mysqli_stmt_bind_param(
    $stmt,
    "ssissssssssi",
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
    $catatan,
    $created_by
);


/* =========================================================
   EKSEKUSI
   ========================================================= */

if (!mysqli_stmt_execute($stmt)) {

    $error = mysqli_stmt_error($stmt);

    mysqli_stmt_close($stmt);

    die(
        "<h3>Gagal menyimpan kasus etik</h3>" .
        "<p><strong>Pesan MySQL:</strong></p>" .
        "<pre>" .
        htmlspecialchars($error) .
        "</pre>" .
        "<p>Silakan kirimkan pesan error tersebut kepada saya.</p>"
    );
}


/* =========================================================
   AMBIL ID KASUS
   ========================================================= */

$id_kasus = mysqli_insert_id($conn);

mysqli_stmt_close($stmt);


/* =========================================================
   CHECKLIST DOKUMEN OTOMATIS
   ========================================================= */

$daftar_dokumen = [
    'Surat/Laporan Pengaduan',
    'Bukti Pendukung',
    'Surat Pemanggilan',
    'Berita Acara Klarifikasi',
    'Berita Acara Pemeriksaan',
    'Hasil Kajian',
    'Rekomendasi Sub Komite Etik',
    'Keputusan'
];


$stmt_dokumen = mysqli_prepare(
    $conn,
    "INSERT INTO etik_dokumen
    (
        id_kasus,
        jenis_dokumen,
        status_dokumen
    )
    VALUES
    (
        ?,
        ?,
        'TIDAK ADA'
    )"
);


if (!$stmt_dokumen) {

    die(
        "Kasus berhasil disimpan dengan ID " .
        $id_kasus .
        ", tetapi checklist dokumen gagal dibuat.<br><br>" .
        htmlspecialchars(mysqli_error($conn))
    );

}


foreach ($daftar_dokumen as $jenis_dokumen) {

    mysqli_stmt_bind_param(
        $stmt_dokumen,
        "is",
        $id_kasus,
        $jenis_dokumen
    );

    if (!mysqli_stmt_execute($stmt_dokumen)) {

        $error_dokumen =
            mysqli_stmt_error($stmt_dokumen);

        mysqli_stmt_close($stmt_dokumen);

        die(
            "Kasus berhasil disimpan, tetapi checklist dokumen gagal.<br><br>" .
            htmlspecialchars($error_dokumen)
        );
    }
}


mysqli_stmt_close($stmt_dokumen);


/* =========================================================
   REDIRECT DETAIL
   ========================================================= */

header(
    "Location: detail.php?id=" .
    $id_kasus
);

exit;

?>