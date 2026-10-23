<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$id_dokumen = isset($_GET['id'])
    ? (int)$_GET['id']
    : (int)($_POST['id_dokumen'] ?? 0);

if ($id_dokumen <= 0) {
    die("ID dokumen tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Ambil dokumen
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        d.*,
        k.nomor_kasus
    FROM etik_dokumen d
    INNER JOIN etik_kasus k
        ON d.id_kasus = k.id_kasus
    WHERE d.id_dokumen = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_dokumen
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$dokumen = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$dokumen) {
    die("Dokumen tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Jika POST = proses upload
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $keterangan = trim(
        $_POST['keterangan'] ?? ''
    );

    if (!isset($_FILES['file_dokumen'])) {
        die("File belum dipilih.");
    }

    $file = $_FILES['file_dokumen'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        die("Upload file gagal. Kode error: " . $file['error']);
    }


    /*
    |--------------------------------------------------------------------------
    | Maksimal 5 MB
    |--------------------------------------------------------------------------
    */
    $max_size = 5 * 1024 * 1024;

    if ($file['size'] > $max_size) {
        die("Ukuran file maksimal 5 MB.");
    }


    /*
    |--------------------------------------------------------------------------
    | Ekstensi
    |--------------------------------------------------------------------------
    */
    $allowed_extensions = [
        'pdf',
        'jpg',
        'jpeg',
        'png'
    ];

    $nama_asli = $file['name'];

    $extension = strtolower(
        pathinfo(
            $nama_asli,
            PATHINFO_EXTENSION
        )
    );

    if (!in_array(
        $extension,
        $allowed_extensions,
        true
    )) {

        die(
            "Format file tidak diperbolehkan. " .
            "Gunakan PDF, JPG, JPEG, atau PNG."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | MIME type
    |--------------------------------------------------------------------------
    */
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    $mime = finfo_file(
        $finfo,
        $file['tmp_name']
    );

    finfo_close($finfo);

    $allowed_mime = [
        'application/pdf',
        'image/jpeg',
        'image/png'
    ];

    if (!in_array(
        $mime,
        $allowed_mime,
        true
    )) {

        die("Tipe file tidak valid.");

    }


    /*
    |--------------------------------------------------------------------------
    | Folder upload
    |--------------------------------------------------------------------------
    */
    $folder = "../uploads/dokumen_etik/";

    if (!is_dir($folder)) {

        if (!mkdir(
            $folder,
            0777,
            true
        )) {

            die(
                "Folder upload tidak dapat dibuat."
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Nama file baru
    |--------------------------------------------------------------------------
    */
    $nama_file_baru =
        "etik_" .
        $dokumen['id_kasus'] .
        "_" .
        $id_dokumen .
        "_" .
        date('YmdHis') .
        "." .
        $extension;


    $tujuan = $folder . $nama_file_baru;


    /*
    |--------------------------------------------------------------------------
    | Hapus file lama
    |--------------------------------------------------------------------------
    */
    if (!empty($dokumen['nama_file'])) {

        $file_lama =
            $folder .
            $dokumen['nama_file'];

        if (file_exists($file_lama)) {
            unlink($file_lama);
        }

    }


    /*
    |--------------------------------------------------------------------------
    | Pindahkan file
    |--------------------------------------------------------------------------
    */
    if (!move_uploaded_file(
        $file['tmp_name'],
        $tujuan
    )) {

        die(
            "File gagal dipindahkan ke folder upload."
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Update database
    |--------------------------------------------------------------------------
    */
    $sql_update = "
        UPDATE etik_dokumen
        SET
            status_dokumen = 'ADA',
            nama_file = ?,
            keterangan = ?,
            uploaded_by = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id_dokumen = ?
    ";

    $stmt_update = mysqli_prepare(
        $conn,
        $sql_update
    );

    if (!$stmt_update) {

        if (file_exists($tujuan)) {
            unlink($tujuan);
        }

        die(
            "Query update gagal: " .
            mysqli_error($conn)
        );

    }

    $uploaded_by =
        $_SESSION['id_user'] ?? null;

    mysqli_stmt_bind_param(
        $stmt_update,
        "ssii",
        $nama_file_baru,
        $keterangan,
        $uploaded_by,
        $id_dokumen
    );


    if (!mysqli_stmt_execute($stmt_update)) {

        if (file_exists($tujuan)) {
            unlink($tujuan);
        }

        die(
            "Gagal menyimpan informasi file: " .
            mysqli_stmt_error($stmt_update)
        );

    }

    mysqli_stmt_close($stmt_update);


    /*
    |--------------------------------------------------------------------------
    | Kembali
    |--------------------------------------------------------------------------
    */
    header(
        "Location: dokumen.php?id=" .
        $dokumen['id_kasus']
    );

    exit;
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Upload Dokumen Etik</title>

<style>

body {
    margin: 0;
    background: #f4f6f9;
    font-family: Arial, Helvetica, sans-serif;
}

.container {
    max-width: 700px;
    margin: 40px auto;
    padding: 20px;
}

.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    overflow: hidden;
}

.header {
    background: #f8f9fa;
    padding: 18px 22px;
    border-bottom: 1px solid #ddd;
}

.header h1 {
    margin: 0;
    font-size: 22px;
}

.body {
    padding: 25px;
}

.info {
    background: #eef6ff;
    padding: 15px;
    border-left: 4px solid #007bff;
    margin-bottom: 20px;
    border-radius: 5px;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input[type=file],
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    box-sizing: border-box;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

button,
a {
    padding: 10px 17px;
    border-radius: 6px;
    border: none;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
}

button {
    background: #007bff;
    color: white;
}

a {
    background: #6c757d;
    color: white;
}

.help {
    margin-top: 7px;
    font-size: 12px;
    color: #777;
}

</style>

</head>

<body>

<div class="container">

    <div class="card">

        <div class="header">

            <h1>
                📤 Upload Dokumen Etik
            </h1>

        </div>

        <div class="body">

            <div class="info">

                <strong>
                    Nomor Kasus:
                </strong>

                <?= htmlspecialchars(
                    $dokumen['nomor_kasus']
                ); ?>

                <br>

                <strong>
                    Jenis Dokumen:
                </strong>

                <?= htmlspecialchars(
                    $dokumen['jenis_dokumen']
                ); ?>

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <input
                    type="hidden"
                    name="id_dokumen"
                    value="<?= $id_dokumen; ?>"
                >


                <div class="form-group">

                    <label>
                        File Dokumen
                    </label>

                    <input
                        type="file"
                        name="file_dokumen"
                        accept=".pdf,.jpg,.jpeg,.png"
                        required
                    >

                    <div class="help">
                        Format: PDF, JPG, JPEG, PNG.
                        Maksimal 5 MB.
                    </div>

                </div>


                <div class="form-group">

                    <label>
                        Keterangan
                    </label>

                    <textarea
                        name="keterangan"
                        placeholder="Keterangan tambahan dokumen..."
                    ><?= htmlspecialchars(
                        $dokumen['keterangan'] ?? ''
                    ); ?></textarea>

                </div>


                <div class="actions">

                    <button type="submit">
                        💾 Simpan Dokumen
                    </button>

                    <a href="dokumen.php?id=<?= $dokumen['id_kasus']; ?>">
                        ← Batal
                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

</body>

</html>