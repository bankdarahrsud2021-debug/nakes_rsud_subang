<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$id_nakes = intval($_POST['id_nakes'] ?? 0);

if ($id_nakes <= 0) {
    die("ID tenaga kesehatan tidak valid.");
}

$nik = trim($_POST['nik'] ?? '');
$nip = trim($_POST['nip'] ?? '');
$nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
$gelar_depan = trim($_POST['gelar_depan'] ?? '');
$gelar_belakang = trim($_POST['gelar_belakang'] ?? '');
$tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = $_POST['tanggal_lahir'] ?? null;
$jenis_kelamin = $_POST['jenis_kelamin'] ?? '';
$no_hp = trim($_POST['no_hp'] ?? '');
$email = trim($_POST['email'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');

$id_profesi = intval($_POST['id_profesi'] ?? 0);
$id_unit = intval($_POST['id_unit'] ?? 0);
$id_jabatan = intval($_POST['id_jabatan'] ?? 0);
$id_status_kepegawaian =
    intval($_POST['id_status_kepegawaian'] ?? 0);

$tanggal_mulai_kerja =
    $_POST['tanggal_mulai_kerja'] ?? null;


/* =========================
   VALIDASI
========================= */

if ($nik === '') {
    die("NIK wajib diisi.");
}

if ($nama_lengkap === '') {
    die("Nama lengkap wajib diisi.");
}

if ($id_profesi <= 0) {
    die("Profesi wajib dipilih.");
}

if ($id_unit <= 0) {
    die("Unit kerja wajib dipilih.");
}

if ($id_status_kepegawaian <= 0) {
    die("Status kepegawaian wajib dipilih.");
}


/* =========================
   AMBIL FOTO LAMA
========================= */

$sql_foto = "
SELECT foto
FROM tenaga_kesehatan
WHERE id_nakes = ?
LIMIT 1
";

$stmt_foto = mysqli_prepare(
    $conn,
    $sql_foto
);

mysqli_stmt_bind_param(
    $stmt_foto,
    "i",
    $id_nakes
);

mysqli_stmt_execute(
    $stmt_foto
);

$result_foto =
    mysqli_stmt_get_result(
        $stmt_foto
    );

$data_foto =
    mysqli_fetch_assoc(
        $result_foto
    );

$foto = $data_foto['foto'] ?? null;


/* =========================
   UPLOAD FOTO BARU
========================= */

if (
    isset($_FILES['foto']) &&
    $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if (
        $_FILES['foto']['error']
        !== UPLOAD_ERR_OK
    ) {
        die("Upload foto gagal.");
    }

    if (
        $_FILES['foto']['size']
        > 2 * 1024 * 1024
    ) {
        die("Ukuran foto maksimal 2 MB.");
    }

    $extension = strtolower(
        pathinfo(
            $_FILES['foto']['name'],
            PATHINFO_EXTENSION
        )
    );

    $allowed = [
        'jpg',
        'jpeg',
        'png'
    ];

    if (!in_array($extension, $allowed)) {
        die(
            "Format foto harus JPG, JPEG, atau PNG."
        );
    }

    $folder = "../uploads/foto_nakes/";

    if (!is_dir($folder)) {
        mkdir(
            $folder,
            0777,
            true
        );
    }

    $nama_baru =
        "nakes_" .
        time() .
        "_" .
        bin2hex(
            random_bytes(5)
        ) .
        "." .
        $extension;

    $tujuan =
        $folder . $nama_baru;

    if (
        !move_uploaded_file(
            $_FILES['foto']['tmp_name'],
            $tujuan
        )
    ) {
        die("Foto gagal disimpan.");
    }

    $foto =
        "uploads/foto_nakes/" .
        $nama_baru;
}


/* =========================
   UPDATE DATA
========================= */

$sql = "
UPDATE tenaga_kesehatan
SET
    nik = ?,
    nip = ?,
    nama_lengkap = ?,
    gelar_depan = ?,
    gelar_belakang = ?,
    tempat_lahir = ?,
    tanggal_lahir = ?,
    jenis_kelamin = ?,
    no_hp = ?,
    email = ?,
    alamat = ?,
    id_profesi = ?,
    id_unit = ?,
    id_jabatan = ?,
    id_status_kepegawaian = ?,
    tanggal_mulai_kerja = ?,
    foto = ?,
    updated_at = NOW()
WHERE id_nakes = ?
";


$stmt = mysqli_prepare(
    $conn,
    $sql
);

if (!$stmt) {
    die(
        "Prepare SQL gagal: " .
        mysqli_error($conn)
    );
}


/* =========================
   BIND
========================= */

mysqli_stmt_bind_param(
    $stmt,
    "sssssssssssiiiissi",
    $nik,
    $nip,
    $nama_lengkap,
    $gelar_depan,
    $gelar_belakang,
    $tempat_lahir,
    $tanggal_lahir,
    $jenis_kelamin,
    $no_hp,
    $email,
    $alamat,
    $id_profesi,
    $id_unit,
    $id_jabatan,
    $id_status_kepegawaian,
    $tanggal_mulai_kerja,
    $foto,
    $id_nakes
);


/* =========================
   EKSEKUSI
========================= */

if (mysqli_stmt_execute($stmt)) {

    header(
        "Location: detail.php?id=" .
        $id_nakes .
        "&success=update"
    );

    exit;

} else {

    die(
        "Data gagal diperbarui: " .
        mysqli_stmt_error($stmt)
    );
}