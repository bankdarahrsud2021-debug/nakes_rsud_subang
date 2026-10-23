<?php
include "../config/auth.php";
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_nakes = (int)($_POST['id_nakes'] ?? 0);
$nomor_sip = trim($_POST['nomor_sip'] ?? '');
$tempat_praktik = trim($_POST['tempat_praktik'] ?? '');
$tanggal_terbit = $_POST['tanggal_terbit'] ?? '';
$tanggal_berakhir = $_POST['tanggal_berakhir'] ?? '';
$status = strtoupper(trim($_POST['status'] ?? ''));

if (
    $id_nakes <= 0 ||
    $nomor_sip === '' ||
    $tempat_praktik === '' ||
    $tanggal_terbit === '' ||
    $tanggal_berakhir === '' ||
    $status === ''
) {
    die("Data belum lengkap.");
}

if (
    !in_array(
        $status,
        ['AKTIF', 'AKAN BERAKHIR', 'KADALUARSA']
    )
) {
    die("Status masa berlaku tidak valid.");
}

/* Upload file */

$file_sip = '';

if (
    !isset($_FILES['file_sip']) ||
    $_FILES['file_sip']['error'] !== UPLOAD_ERR_OK
) {
    die("File SIP wajib diupload.");
}

$file = $_FILES['file_sip'];

$max_size = 5 * 1024 * 1024;

if ($file['size'] > $max_size) {
    die("Ukuran file maksimal 5 MB.");
}

$allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];

$extension = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);

if (!in_array($extension, $allowed_extensions)) {
    die("Format file tidak diperbolehkan.");
}

$upload_dir = "../uploads/sip_nakes/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_sip =
    'SIP_' .
    $id_nakes . '_' .
    time() . '_' .
    uniqid() . '.' .
    $extension;

$upload_path = $upload_dir . $file_sip;

if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
    die("Gagal mengupload file SIP.");
}

/* Simpan database */

$status_verifikasi = "BELUM DIVERIFIKASI";

$query = "
    INSERT INTO sip_nakes
    (
        id_nakes,
        nomor_sip,
        tempat_praktik,
        tanggal_terbit,
        tanggal_berakhir,
        file_sip,
        status,
        status_verifikasi,
        created_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Prepare gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "isssssss",
    $id_nakes,
    $nomor_sip,
    $tempat_praktik,
    $tanggal_terbit,
    $tanggal_berakhir,
    $file_sip,
    $status,
    $status_verifikasi
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Gagal menyimpan data SIP: " .
        mysqli_stmt_error($stmt)
    );
}