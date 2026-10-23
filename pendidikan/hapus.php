<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID pendidikan tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Ambil nama file
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

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    die("Data pendidikan tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Hapus data database
|--------------------------------------------------------------------------
*/

$sql = "
    DELETE FROM pendidikan_nakes
    WHERE id_pendidikan = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

if (!mysqli_stmt_execute($stmt)) {
    die(
        "Gagal menghapus data: " .
        mysqli_stmt_error($stmt)
    );
}

mysqli_stmt_close($stmt);


/*
|--------------------------------------------------------------------------
| Hapus file ijazah
|--------------------------------------------------------------------------
*/

$upload_dir = "../uploads/ijazah_nakes/";

if (
    !empty($data['file_ijazah']) &&
    file_exists(
        $upload_dir . $data['file_ijazah']
    )
) {
    unlink(
        $upload_dir . $data['file_ijazah']
    );
}


/*
|--------------------------------------------------------------------------
| Hapus file transkrip
|--------------------------------------------------------------------------
*/

if (
    !empty($data['file_transkrip']) &&
    file_exists(
        $upload_dir . $data['file_transkrip']
    )
) {
    unlink(
        $upload_dir . $data['file_transkrip']
    );
}


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header("Location: index.php?success=hapus");
exit;