<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$id_kredensial = (int) ($_POST['id_kredensial'] ?? 0);

if ($id_kredensial <= 0) {
    die("ID kredensial tidak valid.");
}

/*
|--------------------------------------------------------------------------
| Pastikan pengajuan kredensial ada
|--------------------------------------------------------------------------
*/
$cek_kredensial = mysqli_prepare(
    $conn,
    "SELECT id_kredensial
     FROM kredensial
     WHERE id_kredensial = ?"
);

mysqli_stmt_bind_param(
    $cek_kredensial,
    "i",
    $id_kredensial
);

mysqli_stmt_execute($cek_kredensial);

$result_kredensial = mysqli_stmt_get_result(
    $cek_kredensial
);

if (mysqli_num_rows($result_kredensial) == 0) {
    die("Data kredensial tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Data POST
|--------------------------------------------------------------------------
*/
$status_data = $_POST['status'] ?? [];
$keterangan_data = $_POST['keterangan'] ?? [];
$file_data = $_FILES['file'] ?? [];


/*
|--------------------------------------------------------------------------
| Folder upload
|--------------------------------------------------------------------------
*/
$folder = "../uploads/dokumen_kredensial/";

if (!is_dir($folder)) {

    if (!mkdir($folder, 0777, true)) {
        die("Folder upload tidak dapat dibuat.");
    }
}


/*
|--------------------------------------------------------------------------
| Daftar ID dokumen
|--------------------------------------------------------------------------
*/
$query_daftar = "
    SELECT
        id_dokumen,
        status_dokumen,
        nama_file
    FROM proses_kredensial
    WHERE id_kredensial = ?
";

$stmt_daftar = mysqli_prepare(
    $conn,
    $query_daftar
);

mysqli_stmt_bind_param(
    $stmt_daftar,
    "i",
    $id_kredensial
);

mysqli_stmt_execute($stmt_daftar);

$result_daftar = mysqli_stmt_get_result(
    $stmt_daftar
);


/*
|--------------------------------------------------------------------------
| Proses setiap dokumen
|--------------------------------------------------------------------------
*/
while ($dok = mysqli_fetch_assoc($result_daftar)) {

    $id_dokumen = (int) $dok['id_dokumen'];

    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */
    $status = $status_data[$id_dokumen] ?? 'TIDAK ADA';

    if (!in_array($status, ['ADA', 'TIDAK ADA'])) {

        $status = 'TIDAK ADA';
    }


    /*
    |--------------------------------------------------------------------------
    | Keterangan
    |--------------------------------------------------------------------------
    */
    $keterangan =
        trim(
            $keterangan_data[$id_dokumen] ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | File lama
    |--------------------------------------------------------------------------
    */
    $nama_file_lama =
        $dok['nama_file'] ?? null;

    $nama_file =
        $nama_file_lama;


    /*
    |--------------------------------------------------------------------------
    | Apakah ada file baru?
    |--------------------------------------------------------------------------
    */
    $ada_file_baru = false;

    if (
        isset($file_data['name'][$id_dokumen]) &&
        !empty($file_data['name'][$id_dokumen])
    ) {

        /*
        |--------------------------------------------------------------------------
        | Cek error upload
        |--------------------------------------------------------------------------
        */
        if (
            !isset(
                $file_data['error'][$id_dokumen]
            ) ||
            $file_data['error'][$id_dokumen]
            !== UPLOAD_ERR_OK
        ) {

            die(
                "Gagal mengupload dokumen: " .
                htmlspecialchars(
                    $file_data['name'][$id_dokumen]
                )
            );
        }


        $nama_asli =
            $file_data['name'][$id_dokumen];

        $tmp_file =
            $file_data['tmp_name'][$id_dokumen];

        $ukuran =
            (int) $file_data['size'][$id_dokumen];


        /*
        |--------------------------------------------------------------------------
        | Maksimal 5 MB
        |--------------------------------------------------------------------------
        */
        if ($ukuran > 5 * 1024 * 1024) {

            die(
                "File " .
                htmlspecialchars($nama_asli) .
                " terlalu besar. Maksimal 5 MB."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Cek ekstensi
        |--------------------------------------------------------------------------
        */
        $ext = strtolower(
            pathinfo(
                $nama_asli,
                PATHINFO_EXTENSION
            )
        );

        $allowed = [
            'pdf',
            'jpg',
            'jpeg',
            'png'
        ];

        if (!in_array($ext, $allowed)) {

            die(
                "Format file " .
                htmlspecialchars($nama_asli) .
                " tidak diperbolehkan. " .
                "Gunakan PDF, JPG, JPEG, atau PNG."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Cek MIME type
        |--------------------------------------------------------------------------
        */
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        $mime = finfo_file(
            $finfo,
            $tmp_file
        );

        finfo_close($finfo);

        $allowed_mime = [
            'application/pdf',
            'image/jpeg',
            'image/png'
        ];

        if (!in_array($mime, $allowed_mime)) {

            die(
                "Tipe file tidak sesuai dengan format yang diperbolehkan."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Nama file baru
        |--------------------------------------------------------------------------
        */
        $nama_file_baru =
            'kred_' .
            $id_kredensial . '_' .
            $id_dokumen . '_' .
            date('YmdHis') . '_' .
            bin2hex(random_bytes(5)) .
            '.' .
            $ext;


        $tujuan =
            $folder .
            $nama_file_baru;


        /*
        |--------------------------------------------------------------------------
        | Pindahkan file
        |--------------------------------------------------------------------------
        */
        if (
            !move_uploaded_file(
                $tmp_file,
                $tujuan
            )
        ) {

            die(
                "Gagal menyimpan file " .
                htmlspecialchars($nama_asli)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | File berhasil
        |--------------------------------------------------------------------------
        */
        $nama_file =
            $nama_file_baru;

        $ada_file_baru = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Jika status TIDAK ADA
    |
    | File tetap dipertahankan apabila sebelumnya sudah ada.
    | Ini agar pengguna tidak kehilangan dokumen hanya karena
    | mengganti status checklist.
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Update database
    |--------------------------------------------------------------------------
    */
    $query_update = "
        UPDATE proses_kredensial
        SET
            status_dokumen = ?,
            nama_file = ?,
            keterangan = ?,
            updated_at = NOW()
        WHERE id_dokumen = ?
        AND id_kredensial = ?
    ";

    $stmt_update = mysqli_prepare(
        $conn,
        $query_update
    );

    if (!$stmt_update) {

        die(
            "Gagal menyiapkan query: " .
            mysqli_error($conn)
        );
    }

    mysqli_stmt_bind_param(
        $stmt_update,
        "sssii",
        $status,
        $nama_file,
        $keterangan,
        $id_dokumen,
        $id_kredensial
    );

    if (!mysqli_stmt_execute($stmt_update)) {

        die(
            "Gagal menyimpan dokumen: " .
            mysqli_stmt_error($stmt_update)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Jika upload baru berhasil, hapus file lama
    |--------------------------------------------------------------------------
    */
    if (
        $ada_file_baru &&
        !empty($nama_file_lama) &&
        $nama_file_lama !== $nama_file
    ) {

        $file_lama =
            $folder .
            $nama_file_lama;

        if (file_exists($file_lama)) {

            unlink($file_lama);
        }
    }
}


/*
|--------------------------------------------------------------------------
| Kembali ke halaman dokumen
|--------------------------------------------------------------------------
*/
header(
    "Location: dokumen.php?id=" .
    $id_kredensial .
    "&success=1"
);

exit;