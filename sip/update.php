<?php
include "../config/auth.php";
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_sip = (int)($_POST['id_sip'] ?? 0);
$id_nakes = (int)($_POST['id_nakes'] ?? 0);
$nomor_sip = trim($_POST['nomor_sip'] ?? '');
$tempat_praktik = trim($_POST['tempat_praktik'] ?? '');
$tanggal_terbit = $_POST['tanggal_terbit'] ?? '';
$tanggal_berakhir = $_POST['tanggal_berakhir'] ?? '';
$status = strtoupper(trim($_POST['status'] ?? ''));

if ($id_sip <= 0 || $id_nakes <= 0) {
    die("ID tidak valid.");
}

if (
    $nomor_sip === '' ||
    $tempat_praktik === '' ||
    $tanggal_terbit === '' ||
    $tanggal_berakhir === ''
) {
    die("Data belum lengkap.");
}

if (
    !in_array(
        $status,
        ['AKTIF', 'AKAN BERAKHIR', 'KADALUARSA']
    )
) {
    die("Status tidak valid.");
}

/* Ambil file lama */

$query_old = "
    SELECT file_sip
    FROM sip_nakes
    WHERE id_sip = ?
";

$stmt_old = mysqli_prepare($conn, $query_old);

mysqli_stmt_bind_param(
    $stmt_old,
    "i",
    $id_sip
);

mysqli_stmt_execute($stmt_old);

$result_old = mysqli_stmt_get_result($stmt_old);
$data_old = mysqli_fetch_assoc($result_old);

if (!$data_old) {
    die("Data SIP tidak ditemukan.");
}

$file_sip = $data_old['file_sip'];

/* Jika upload file baru */

if (
    isset($_FILES['file_sip']) &&
    $_FILES['file_sip']['error'] === UPLOAD_ERR_OK
) {

    $file = $_FILES['file_sip'];

    $max_size = 5 * 1024 * 1024;

    if ($file['size'] > $max_size) {
        die("Ukuran file maksimal 5 MB.");
    }

    $allowed_extensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    $extension = strtolower(
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array($extension, $allowed_extensions)) {
        die("Format file tidak diperbolehkan.");
    }

    $upload_dir = "../uploads/sip_nakes/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_sip_baru =
        'SIP_' .
        $id_nakes . '_' .
        time() . '_' .
        uniqid() . '.' .
        $extension;

    $upload_path =
        $upload_dir . $file_sip_baru;

    if (!move_uploaded_file(
        $file['tmp_name'],
        $upload_path
    )) {
        die("Gagal mengupload file baru.");
    }

    /* Hapus file lama */

    if (
        !empty($file_sip) &&
        file_exists($upload_dir . $file_sip)
    ) {
        unlink($upload_dir . $file_sip);
    }

    $file_sip = $file_sip_baru;
}

/* Update database */

$query = "
    UPDATE sip_nakes
    SET
        id_nakes = ?,
        nomor_sip = ?,
        tempat_praktik = ?,
        tanggal_terbit = ?,
        tanggal_berakhir = ?,
        file_sip = ?,
        status = ?,
        updated_at = NOW()
    WHERE id_sip = ?
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Prepare gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "issssssi",
    $id_nakes,
    $nomor_sip,
    $tempat_praktik,
    $tanggal_terbit,
    $tanggal_berakhir,
    $file_sip,
    $status,
    $id_sip
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Gagal memperbarui data SIP: " .
        mysqli_stmt_error($stmt)
    );
}