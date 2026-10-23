<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_pendidikan  = (int)($_POST['id_pendidikan'] ?? 0);
$id_nakes       = (int)($_POST['id_nakes'] ?? 0);
$jenjang        = trim($_POST['jenjang'] ?? '');
$institusi      = trim($_POST['institusi'] ?? '');
$program_studi  = trim($_POST['program_studi'] ?? '');
$nomor_ijazah   = trim($_POST['nomor_ijazah'] ?? '');
$tanggal_ijazah = trim($_POST['tanggal_ijazah'] ?? '');
$tahun_lulus    = (int)($_POST['tahun_lulus'] ?? 0);

if (
    $id_pendidikan <= 0 ||
    $id_nakes <= 0 ||
    $jenjang === '' ||
    $institusi === '' ||
    $program_studi === '' ||
    $tahun_lulus <= 0
) {
    die("Data belum lengkap.");
}


/*
|--------------------------------------------------------------------------
| Ambil file lama
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        file_ijazah,
        file_transkrip
    FROM pendidikan_nakes
    WHERE id_pendidikan = ?
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_pendidikan);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$old_data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$old_data) {
    die("Data pendidikan tidak ditemukan.");
}

$old_file_ijazah = $old_data['file_ijazah'];
$old_file_transkrip = $old_data['file_transkrip'];


/*
|--------------------------------------------------------------------------
| Folder upload
|--------------------------------------------------------------------------
*/

$upload_dir = "../uploads/ijazah_nakes/";

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}


/*
|--------------------------------------------------------------------------
| Fungsi upload
|--------------------------------------------------------------------------
*/

function uploadDokumenUpdate(
    $file,
    $id_nakes,
    $prefix,
    $upload_dir
) {

    if (
        !isset($file) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
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
        pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        )
    );

    if (!in_array(
        $extension,
        $allowed_extensions
    )) {
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


/*
|--------------------------------------------------------------------------
| Upload file baru jika ada
|--------------------------------------------------------------------------
*/

$new_file_ijazah = uploadDokumenUpdate(
    $_FILES['file_ijazah'] ?? null,
    $id_nakes,
    'IJAZAH',
    $upload_dir
);

$new_file_transkrip = uploadDokumenUpdate(
    $_FILES['file_transkrip'] ?? null,
    $id_nakes,
    'TRANSKRIP',
    $upload_dir
);


/*
|--------------------------------------------------------------------------
| Jika tidak upload baru, gunakan file lama
|--------------------------------------------------------------------------
*/

$file_ijazah =
    ($new_file_ijazah !== null)
    ? $new_file_ijazah
    : $old_file_ijazah;

$file_transkrip =
    ($new_file_transkrip !== null)
    ? $new_file_transkrip
    : $old_file_transkrip;


if ($nomor_ijazah === '') {
    $nomor_ijazah = null;
}

if ($tanggal_ijazah === '') {
    $tanggal_ijazah = null;
}


/*
|--------------------------------------------------------------------------
| UPDATE
|--------------------------------------------------------------------------
*/

$sql = "
    UPDATE pendidikan_nakes
    SET
        id_nakes = ?,
        jenjang = ?,
        institusi = ?,
        program_studi = ?,
        nomor_ijazah = ?,
        tanggal_ijazah = ?,
        tahun_lulus = ?,
        file_ijazah = ?,
        file_transkrip = ?
    WHERE id_pendidikan = ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Prepare gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "isssssissi",
    $id_nakes,
    $jenjang,
    $institusi,
    $program_studi,
    $nomor_ijazah,
    $tanggal_ijazah,
    $tahun_lulus,
    $file_ijazah,
    $file_transkrip,
    $id_pendidikan
);


if (!mysqli_stmt_execute($stmt)) {

    if (
        $new_file_ijazah &&
        file_exists($upload_dir . $new_file_ijazah)
    ) {
        unlink(
            $upload_dir . $new_file_ijazah
        );
    }

    if (
        $new_file_transkrip &&
        file_exists($upload_dir . $new_file_transkrip)
    ) {
        unlink(
            $upload_dir . $new_file_transkrip
        );
    }

    die(
        "Gagal memperbarui data: " .
        mysqli_stmt_error($stmt)
    );
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Hapus file lama jika diganti
|--------------------------------------------------------------------------
*/

if (
    $new_file_ijazah !== null &&
    !empty($old_file_ijazah) &&
    file_exists($upload_dir . $old_file_ijazah)
) {
    unlink($upload_dir . $old_file_ijazah);
}

if (
    $new_file_transkrip !== null &&
    !empty($old_file_transkrip) &&
    file_exists($upload_dir . $old_file_transkrip)
) {
    unlink($upload_dir . $old_file_transkrip);
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header("Location: index.php?success=update");
exit;