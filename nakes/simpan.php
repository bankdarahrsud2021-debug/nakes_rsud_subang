<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: tambah.php");
    exit;

}


/* =========================
   AMBIL DATA FORM
========================= */

$nik = trim($_POST['nik'] ?? '');

$nip = trim($_POST['nip'] ?? '');

$nama_lengkap = trim(
    $_POST['nama_lengkap'] ?? ''
);

$gelar_depan = trim(
    $_POST['gelar_depan'] ?? ''
);

$gelar_belakang = trim(
    $_POST['gelar_belakang'] ?? ''
);

$tempat_lahir = trim(
    $_POST['tempat_lahir'] ?? ''
);

$tanggal_lahir =
    $_POST['tanggal_lahir'] ?? null;

$jenis_kelamin =
    $_POST['jenis_kelamin'] ?? '';

$no_hp = trim(
    $_POST['no_hp'] ?? ''
);

$email = trim(
    $_POST['email'] ?? ''
);

$alamat = trim(
    $_POST['alamat'] ?? ''
);

$id_profesi =
    intval($_POST['id_profesi'] ?? 0);

$id_unit =
    intval($_POST['id_unit'] ?? 0);

$id_jabatan =
    intval($_POST['id_jabatan'] ?? 0);

$id_status_kepegawaian =
    intval($_POST['id_status_kepegawaian'] ?? 0);

$tanggal_mulai_kerja =
    $_POST['tanggal_mulai_kerja'] ?? null;


/* =========================
   UBAH DATA KOSONG MENJADI NULL
========================= */

if ($nik === '') {
    $nik = null;
}

if ($nip === '') {
    $nip = null;
}

if ($gelar_depan === '') {
    $gelar_depan = null;
}

if ($gelar_belakang === '') {
    $gelar_belakang = null;
}

if ($tempat_lahir === '') {
    $tempat_lahir = null;
}

if ($tanggal_lahir === '') {
    $tanggal_lahir = null;
}

if ($no_hp === '') {
    $no_hp = null;
}

if ($email === '') {
    $email = null;
}

if ($alamat === '') {
    $alamat = null;
}

if ($id_jabatan <= 0) {
    $id_jabatan = null;
}

if ($tanggal_mulai_kerja === '') {
    $tanggal_mulai_kerja = null;
}


/* =========================
   VALIDASI
========================= */

if ($nama_lengkap === '') {

    die("Nama lengkap wajib diisi.");

}

if ($jenis_kelamin === '') {

    die("Jenis kelamin wajib dipilih.");

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
   CEK NIK DUPLIKAT
========================= */

if ($nik !== null) {

    $sql_cek = "
        SELECT id_nakes
        FROM tenaga_kesehatan
        WHERE nik = ?
        LIMIT 1
    ";

    $stmt_cek = mysqli_prepare(
        $conn,
        $sql_cek
    );

    if (!$stmt_cek) {

        die(
            "Prepare cek NIK gagal: " .
            mysqli_error($conn)
        );

    }

    mysqli_stmt_bind_param(
        $stmt_cek,
        "s",
        $nik
    );

    mysqli_stmt_execute(
        $stmt_cek
    );

    $result_cek =
        mysqli_stmt_get_result(
            $stmt_cek
        );

    if (
        mysqli_num_rows($result_cek) > 0
    ) {

        die(
            "NIK <b>" .
            htmlspecialchars($nik) .
            "</b> sudah terdaftar."
        );

    }

    mysqli_stmt_close($stmt_cek);

}


/* =========================
   UPLOAD FOTO
========================= */

$foto = null;

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


    /* Maksimal 2 MB */

    if (
        $_FILES['foto']['size']
        > 2 * 1024 * 1024
    ) {

        die(
            "Ukuran foto maksimal 2 MB."
        );

    }


    $nama_file =
        $_FILES['foto']['name'];

    $tmp_file =
        $_FILES['foto']['tmp_name'];


    $extension =
        strtolower(
            pathinfo(
                $nama_file,
                PATHINFO_EXTENSION
            )
        );


    $allowed = [
        'jpg',
        'jpeg',
        'png'
    ];


    if (
        !in_array(
            $extension,
            $allowed
        )
    ) {

        die(
            "Format foto harus JPG, JPEG, atau PNG."
        );

    }


    /* Folder */

    $folder =
        "../uploads/foto_nakes/";


    if (!is_dir($folder)) {

        mkdir(
            $folder,
            0777,
            true
        );

    }


    /* Nama file unik */

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
            $tmp_file,
            $tujuan
        )
    ) {

        die(
            "Foto gagal disimpan."
        );

    }


    $foto =
        "uploads/foto_nakes/" .
        $nama_baru;
}


/* =========================
   SIMPAN DATA
========================= */

$sql = "
INSERT INTO tenaga_kesehatan
(
    nik,
    nip,
    nama_lengkap,
    gelar_depan,
    gelar_belakang,
    tempat_lahir,
    tanggal_lahir,
    jenis_kelamin,
    no_hp,
    email,
    alamat,
    id_profesi,
    id_unit,
    id_jabatan,
    id_status_kepegawaian,
    tanggal_mulai_kerja,
    status_aktif,
    foto
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, 'AKTIF', ?
)
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
   BIND PARAMETER
========================= */

mysqli_stmt_bind_param(
    $stmt,
    "sssssssssssiiiiss",
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
    $foto
);


/* =========================
   EKSEKUSI
========================= */

if (mysqli_stmt_execute($stmt)) {

    header(
        "Location: index.php?success=1"
    );

    exit;

} else {

    die(
        "Data gagal disimpan: " .
        mysqli_stmt_error($stmt)
    );

}