<?php

require_once "../config/auth.php";
require_once "../config/database.php";


/*
|--------------------------------------------------------------------------
| Pastikan POST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: index.php");
    exit;

}


/*
|--------------------------------------------------------------------------
| Ambil Data
|--------------------------------------------------------------------------
*/

$id_str = isset($_POST['id_str'])
    ? (int) $_POST['id_str']
    : 0;

$id_nakes = isset($_POST['id_nakes'])
    ? (int) $_POST['id_nakes']
    : 0;

$nomor_str = trim(
    $_POST['nomor_str'] ?? ''
);

$jenis_str = trim(
    $_POST['jenis_str'] ?? ''
);

$tanggal_terbit = !empty(
    $_POST['tanggal_terbit']
)
    ? $_POST['tanggal_terbit']
    : null;

$tanggal_berakhir = !empty(
    $_POST['tanggal_berakhir']
)
    ? $_POST['tanggal_berakhir']
    : null;

$status = trim(
    $_POST['status'] ?? 'BELUM DIVERIFIKASI'
);


/*
|--------------------------------------------------------------------------
| Validasi
|--------------------------------------------------------------------------
*/

if ($id_str <= 0) {

    die("ID STR tidak valid.");

}

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
| Ambil File Lama
|--------------------------------------------------------------------------
*/

$query_old = "
    SELECT file_str
    FROM str_nakes
    WHERE id_str = ?
    LIMIT 1
";

$stmt_old = mysqli_prepare(
    $conn,
    $query_old
);

mysqli_stmt_bind_param(
    $stmt_old,
    "i",
    $id_str
);

mysqli_stmt_execute(
    $stmt_old
);

$result_old = mysqli_stmt_get_result(
    $stmt_old
);

$data_old = mysqli_fetch_assoc(
    $result_old
);

mysqli_stmt_close($stmt_old);


if (!$data_old) {

    die("Data STR tidak ditemukan.");

}

$file_lama = $data_old['file_str'];

$file_baru = $file_lama;


/*
|--------------------------------------------------------------------------
| Cek Duplikasi Nomor STR
|--------------------------------------------------------------------------
*/

$query_cek = "
    SELECT id_str
    FROM str_nakes
    WHERE nomor_str = ?
      AND id_str != ?
    LIMIT 1
";

$stmt_cek = mysqli_prepare(
    $conn,
    $query_cek
);

mysqli_stmt_bind_param(
    $stmt_cek,
    "si",
    $nomor_str,
    $id_str
);

mysqli_stmt_execute(
    $stmt_cek
);

$result_cek = mysqli_stmt_get_result(
    $stmt_cek
);

if (mysqli_num_rows($result_cek) > 0) {

    mysqli_stmt_close($stmt_cek);

    die("
        Nomor STR tersebut sudah digunakan.
        <br><br>
        <a href='edit.php?id=$id_str'>
            ← Kembali ke Form
        </a>
    ");

}

mysqli_stmt_close($stmt_cek);


/*
|--------------------------------------------------------------------------
| Upload File Baru Jika Ada
|--------------------------------------------------------------------------
*/

$upload_file_baru = false;

if (
    isset($_FILES['file_str']) &&
    $_FILES['file_str']['error'] !== UPLOAD_ERR_NO_FILE
) {


    /*
    |--------------------------------------------------------------------------
    | Cek Error
    |--------------------------------------------------------------------------
    */

    if (
        $_FILES['file_str']['error']
        !== UPLOAD_ERR_OK
    ) {

        die("
            File STR gagal diupload.
            <br><br>
            <a href='edit.php?id=$id_str'>
                ← Kembali ke Form
            </a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Maksimal 5 MB
    |--------------------------------------------------------------------------
    */

    $max_size = 5 * 1024 * 1024;

    if (
        $_FILES['file_str']['size']
        > $max_size
    ) {

        die("
            Ukuran file terlalu besar.
            <br>
            Maksimal 5 MB.
            <br><br>
            <a href='edit.php?id=$id_str'>
                ← Kembali ke Form
            </a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Extension
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


    $allowed_extensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];


    if (
        !in_array(
            $extension,
            $allowed_extensions
        )
    ) {

        die("
            Format file tidak diperbolehkan.
            <br>
            Gunakan PDF, JPG, JPEG atau PNG.
            <br><br>
            <a href='edit.php?id=$id_str'>
                ← Kembali ke Form
            </a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Validasi MIME
    |--------------------------------------------------------------------------
    */

    $finfo = finfo_open(
        FILEINFO_MIME_TYPE
    );

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


    if (
        !in_array(
            $mime_type,
            $allowed_mime
        )
    ) {

        die("
            Tipe file tidak valid.
            <br><br>
            <a href='edit.php?id=$id_str'>
                ← Kembali ke Form
            </a>
        ");

    }


    /*
    |--------------------------------------------------------------------------
    | Folder Upload
    |--------------------------------------------------------------------------
    */

    $upload_dir =
        "../uploads/str_nakes/";


    if (!is_dir($upload_dir)) {

        if (!mkdir(
            $upload_dir,
            0755,
            true
        )) {

            die(
                "Folder upload STR gagal dibuat."
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Nama File Baru
    |--------------------------------------------------------------------------
    */

    $file_baru =
        'STR_' .
        $id_nakes .
        '_' .
        date('YmdHis') .
        '_' .
        bin2hex(
            random_bytes(4)
        ) .
        '.' .
        $extension;


    $target_file =
        $upload_dir .
        $file_baru;


    /*
    |--------------------------------------------------------------------------
    | Pindahkan File
    |--------------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $tmp_file,
            $target_file
        )
    ) {

        die("
            File STR gagal disimpan.
            <br><br>
            <a href='edit.php?id=$id_str'>
                ← Kembali ke Form
            </a>
        ");

    }


    $upload_file_baru = true;

}


/*
|--------------------------------------------------------------------------
| Update Database
|--------------------------------------------------------------------------
*/

$query = "
    UPDATE str_nakes
    SET
        id_nakes = ?,
        nomor_str = ?,
        jenis_str = ?,
        tanggal_terbit = ?,
        tanggal_berakhir = ?,
        file_str = ?,
        status = ?,
        updated_at = NOW()
    WHERE id_str = ?
";


$stmt = mysqli_prepare(
    $conn,
    $query
);


if (!$stmt) {

    /*
    |--------------------------------------------------------------------------
    | Hapus file baru jika query gagal
    |--------------------------------------------------------------------------
    */

    if ($upload_file_baru) {

        $file_path =
            "../uploads/str_nakes/"
            . $file_baru;

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
    "issssssi",
    $id_nakes,
    $nomor_str,
    $jenis_str,
    $tanggal_terbit,
    $tanggal_berakhir,
    $file_baru,
    $status,
    $id_str
);


/*
|--------------------------------------------------------------------------
| Eksekusi
|--------------------------------------------------------------------------
*/

if (!mysqli_stmt_execute($stmt)) {

    /*
    |--------------------------------------------------------------------------
    | Hapus file baru jika update gagal
    |--------------------------------------------------------------------------
    */

    if ($upload_file_baru) {

        $file_path =
            "../uploads/str_nakes/"
            . $file_baru;

        if (file_exists($file_path)) {

            unlink($file_path);

        }

    }


    die(
        "Data STR gagal diperbarui: "
        . mysqli_stmt_error($stmt)
    );

}


mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Hapus File Lama Setelah Database Berhasil Diupdate
|--------------------------------------------------------------------------
*/

if (
    $upload_file_baru &&
    !empty($file_lama)
) {

    $file_lama_path =
        "../uploads/str_nakes/"
        . $file_lama;

    if (file_exists($file_lama_path)) {

        unlink($file_lama_path);

    }

}


/*
|--------------------------------------------------------------------------
| Berhasil
|--------------------------------------------------------------------------
*/

header(
    "Location: index.php?success=update"
);

exit;

?>