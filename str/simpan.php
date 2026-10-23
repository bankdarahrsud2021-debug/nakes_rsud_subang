<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Pastikan request berasal dari form POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Ambil Data Form
|--------------------------------------------------------------------------
*/

$id_nakes = isset($_POST['id_nakes'])
    ? (int) $_POST['id_nakes']
    : 0;

$nomor_str = trim($_POST['nomor_str'] ?? '');

$jenis_str = trim($_POST['jenis_str'] ?? '');

$tanggal_terbit = !empty($_POST['tanggal_terbit'])
    ? $_POST['tanggal_terbit']
    : null;

$tanggal_berakhir = !empty($_POST['tanggal_berakhir'])
    ? $_POST['tanggal_berakhir']
    : null;

$status = trim($_POST['status'] ?? 'BELUM DIVERIFIKASI');


/*
|--------------------------------------------------------------------------
| Validasi Data Wajib
|--------------------------------------------------------------------------
*/

if ($id_nakes <= 0) {

    die("Tenaga kesehatan belum dipilih.");

}

if ($nomor_str === '') {

    die("Nomor STR wajib diisi.");

}

if ($jenis_str === '') {

    die("Jenis STR wajib dipilih.");

}


/*
|--------------------------------------------------------------------------
| Cek Apakah Tenaga Kesehatan Ada
|--------------------------------------------------------------------------
*/

$query_nakes = "
    SELECT id_nakes
    FROM tenaga_kesehatan
    WHERE id_nakes = ?
    LIMIT 1
";

$stmt_nakes = mysqli_prepare($conn, $query_nakes);

mysqli_stmt_bind_param(
    $stmt_nakes,
    "i",
    $id_nakes
);

mysqli_stmt_execute($stmt_nakes);

$result_nakes = mysqli_stmt_get_result($stmt_nakes);

if (mysqli_num_rows($result_nakes) === 0) {

    die("Data tenaga kesehatan tidak ditemukan.");

}

mysqli_stmt_close($stmt_nakes);


/*
|--------------------------------------------------------------------------
| Cek Duplikasi Nomor STR
|--------------------------------------------------------------------------
*/

$query_cek = "
    SELECT id_str
    FROM str_nakes
    WHERE nomor_str = ?
    LIMIT 1
";

$stmt_cek = mysqli_prepare($conn, $query_cek);

mysqli_stmt_bind_param(
    $stmt_cek,
    "s",
    $nomor_str
);

mysqli_stmt_execute($stmt_cek);

$result_cek = mysqli_stmt_get_result($stmt_cek);

if (mysqli_num_rows($result_cek) > 0) {

    mysqli_stmt_close($stmt_cek);

    die("
        Nomor STR tersebut sudah terdaftar.
        <br><br>
        <a href='tambah.php'>← Kembali ke Form</a>
    ");

}

mysqli_stmt_close($stmt_cek);


/*
|--------------------------------------------------------------------------
| Upload File STR
|--------------------------------------------------------------------------
*/

$file_str = null;

if (
    isset($_FILES['file_str']) &&
    $_FILES['file_str']['error'] !== UPLOAD_ERR_NO_FILE
) {

    /*
    |--------------------------------------------------------------------------
    | Cek Error Upload
    |--------------------------------------------------------------------------
    */

    if ($_FILES['file_str']['error'] !== UPLOAD_ERR_OK) {

        die("File STR gagal diupload.");

    }


    /*
    |--------------------------------------------------------------------------
    | Batas Ukuran File
    | Maksimal 5 MB
    |--------------------------------------------------------------------------
    */

    $max_size = 5 * 1024 * 1024;

    if ($_FILES['file_str']['size'] > $max_size) {

        die("
            Ukuran file STR terlalu besar.
            <br>
            Maksimal ukuran file adalah 5 MB.
            <br><br>
            <a href='tambah.php'>← Kembali ke Form</a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Ambil Informasi File
    |--------------------------------------------------------------------------
    */

    $nama_asli = $_FILES['file_str']['name'];

    $tmp_file = $_FILES['file_str']['tmp_name'];

    $extension = strtolower(
        pathinfo(
            $nama_asli,
            PATHINFO_EXTENSION
        )
    );


    /*
    |--------------------------------------------------------------------------
    | Ekstensi yang diperbolehkan
    |--------------------------------------------------------------------------
    */

    $allowed_extensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    if (!in_array($extension, $allowed_extensions)) {

        die("
            Format file tidak diperbolehkan.
            <br>
            Hanya PDF, JPG, JPEG, dan PNG.
            <br><br>
            <a href='tambah.php'>← Kembali ke Form</a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Validasi MIME Type
    |--------------------------------------------------------------------------
    */

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime_type = finfo_file(
        $finfo,
        $tmp_file
    );

    finfo_close($finfo);


    $allowed_mime = [
        'application/pdf',
        'image/jpeg',
        'image/png'
    ];

    if (!in_array($mime_type, $allowed_mime)) {

        die("
            Tipe file tidak valid.
            <br><br>
            <a href='tambah.php'>← Kembali ke Form</a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Buat Folder Upload Jika Belum Ada
    |--------------------------------------------------------------------------
    */

    $upload_dir = "../uploads/str_nakes/";

    if (!is_dir($upload_dir)) {

        if (!mkdir($upload_dir, 0755, true)) {

            die("Folder upload STR gagal dibuat.");

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Nama File Baru
    |--------------------------------------------------------------------------
    */

    $file_str =
        'STR_' .
        $id_nakes .
        '_' .
        date('YmdHis') .
        '_' .
        bin2hex(random_bytes(4)) .
        '.' .
        $extension;


    /*
    |--------------------------------------------------------------------------
    | Path Tujuan
    |--------------------------------------------------------------------------
    */

    $target_file = $upload_dir . $file_str;


    /*
    |--------------------------------------------------------------------------
    | Pindahkan File
    |--------------------------------------------------------------------------
    */

    if (!move_uploaded_file(
        $tmp_file,
        $target_file
    )) {

        die("
            File STR gagal disimpan.
            <br><br>
            <a href='tambah.php'>← Kembali ke Form</a>
        ");

    }

}


/*
|--------------------------------------------------------------------------
| Simpan Data ke Database
|--------------------------------------------------------------------------
*/

$query = "
    INSERT INTO str_nakes
    (
        id_nakes,
        nomor_str,
        jenis_str,
        tanggal_terbit,
        tanggal_berakhir,
        file_str,
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
        ?
    )
";


$stmt = mysqli_prepare(
    $conn,
    $query
);

if (!$stmt) {

    /*
    |--------------------------------------------------------------------------
    | Hapus file jika query gagal
    |--------------------------------------------------------------------------
    */

    if ($file_str !== null) {

        $file_path = "../uploads/str_nakes/" . $file_str;

        if (file_exists($file_path)) {
            unlink($file_path);
        }

    }

    die(
        "Gagal menyiapkan query: "
        . mysqli_error($conn)
    );

}


/*
|--------------------------------------------------------------------------
| Bind Parameter
|--------------------------------------------------------------------------
*/

mysqli_stmt_bind_param(
    $stmt,
    "issssss",
    $id_nakes,
    $nomor_str,
    $jenis_str,
    $tanggal_terbit,
    $tanggal_berakhir,
    $file_str,
    $status
);


/*
|--------------------------------------------------------------------------
| Eksekusi
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($stmt)) {

    /*
    |--------------------------------------------------------------------------
    | Hapus file jika penyimpanan database gagal
    |--------------------------------------------------------------------------
    */

    if ($file_str !== null) {

        $file_path =
            "../uploads/str_nakes/"
            . $file_str;

        if (file_exists($file_path)) {
            unlink($file_path);
        }

    }

    die(
        "Data STR gagal disimpan: "
        . mysqli_stmt_error($stmt)
    );

}


/*
|--------------------------------------------------------------------------
| Tutup Statement
|--------------------------------------------------------------------------
*/

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Berhasil
|--------------------------------------------------------------------------
*/

header(
    "Location: index.php?success=tambah"
);

exit;

?>