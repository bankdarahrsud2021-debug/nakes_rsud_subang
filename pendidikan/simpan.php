<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_nakes       = (int)($_POST['id_nakes'] ?? 0);
$jenjang        = trim($_POST['jenjang'] ?? '');
$institusi      = trim($_POST['institusi'] ?? '');
$program_studi  = trim($_POST['program_studi'] ?? '');
$nomor_ijazah   = trim($_POST['nomor_ijazah'] ?? '');
$tanggal_ijazah = trim($_POST['tanggal_ijazah'] ?? '');
$tahun_lulus    = (int)($_POST['tahun_lulus'] ?? 0);

if (
    $id_nakes <= 0 ||
    $jenjang === '' ||
    $institusi === '' ||
    $program_studi === '' ||
    $tahun_lulus <= 0
) {
    die("Data wajib belum lengkap.");
}

/* Cek nakes */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_nakes FROM tenaga_kesehatan WHERE id_nakes = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_nakes);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    die("Tenaga kesehatan tidak ditemukan.");
}

mysqli_stmt_close($stmt);


/* Folder upload */
$upload_dir = "../uploads/ijazah_nakes/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/* Fungsi upload */
function uploadDokumen($file, $id_nakes, $prefix, $upload_dir)
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Upload file gagal.");
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        die("Ukuran file maksimal 5 MB.");
    }

    $allowed_extensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    $extension = strtolower(
        pathinfo($file['name'], PATHINFO_EXTENSION)
    );

    if (!in_array($extension, $allowed_extensions)) {
        die("Format file tidak diperbolehkan.");
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime = finfo_file(
        $finfo,
        $file['tmp_name']
    );

    finfo_close($finfo);

    $allowed_mimes = [
        'application/pdf',
        'image/jpeg',
        'image/png'
    ];

    if (!in_array($mime, $allowed_mimes)) {
        die("Jenis file tidak valid.");
    }

    $filename =
        $prefix . "_" .
        $id_nakes . "_" .
        date('YmdHis') . "_" .
        bin2hex(random_bytes(5)) .
        "." .
        $extension;

    if (!move_uploaded_file(
        $file['tmp_name'],
        $upload_dir . $filename
    )) {
        die("File gagal disimpan.");
    }

    return $filename;
}


/* Upload ijazah */
$file_ijazah = uploadDokumen(
    $_FILES['file_ijazah'] ?? null,
    $id_nakes,
    'IJAZAH',
    $upload_dir
);


/* Upload transkrip */
$file_transkrip = uploadDokumen(
    $_FILES['file_transkrip'] ?? null,
    $id_nakes,
    'TRANSKRIP',
    $upload_dir
);


/* Nilai kosong */
if ($nomor_ijazah === '') {
    $nomor_ijazah = null;
}

if ($tanggal_ijazah === '') {
    $tanggal_ijazah = null;
}


/* Insert */
$sql = "
    INSERT INTO pendidikan_nakes
    (
        id_nakes,
        jenjang,
        institusi,
        program_studi,
        nomor_ijazah,
        tanggal_ijazah,
        tahun_lulus,
        file_ijazah,
        file_transkrip,
        status_verifikasi
    )
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, 'BELUM DIVERIFIKASI')
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Prepare gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "isssssiss",
    $id_nakes,
    $jenjang,
    $institusi,
    $program_studi,
    $nomor_ijazah,
    $tanggal_ijazah,
    $tahun_lulus,
    $file_ijazah,
    $file_transkrip
);

if (!mysqli_stmt_execute($stmt)) {

    if ($file_ijazah && file_exists($upload_dir . $file_ijazah)) {
        unlink($upload_dir . $file_ijazah);
    }

    if ($file_transkrip && file_exists($upload_dir . $file_transkrip)) {
        unlink($upload_dir . $file_transkrip);
    }

    die(
        "Gagal menyimpan data: " .
        mysqli_stmt_error($stmt)
    );
}

mysqli_stmt_close($stmt);

header("Location: index.php?success=tambah");
exit;