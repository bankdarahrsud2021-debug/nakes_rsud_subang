<?php
require_once "../config/auth.php";
require_once "../config/database.php";

$id_kasus = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

/*
|--------------------------------------------------------------------------
| Cek apakah kasus ada
|--------------------------------------------------------------------------
*/
$sql = "SELECT id_kasus, nomor_kasus FROM etik_kasus WHERE id_kasus = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$kasus = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$kasus) {
    die("Kasus tidak ditemukan. ID: " . $id_kasus);
}

/*
|--------------------------------------------------------------------------
| Ambil dokumen untuk menghapus file fisiknya
|--------------------------------------------------------------------------
*/
$sql_dokumen = "
    SELECT nama_file
    FROM etik_dokumen
    WHERE id_kasus = ?
    AND nama_file IS NOT NULL
    AND nama_file <> ''
";

$stmt = mysqli_prepare($conn, $sql_dokumen);
mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result_dokumen = mysqli_stmt_get_result($stmt);

$file_dokumen = [];

while ($row = mysqli_fetch_assoc($result_dokumen)) {
    $file_dokumen[] = $row['nama_file'];
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| Hapus data menggunakan transaction
|--------------------------------------------------------------------------
*/
mysqli_begin_transaction($conn);

try {

    /*
     * Hapus proses etik
     */
    $sql = "DELETE FROM etik_proses WHERE id_kasus = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_kasus);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);


    /*
     * Hapus dokumen
     */
    $sql = "DELETE FROM etik_dokumen WHERE id_kasus = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_kasus);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);


    /*
     * Hapus kasus utama
     */
    $sql = "DELETE FROM etik_kasus WHERE id_kasus = ?";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "i", $id_kasus);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);


    /*
     * Commit
     */
    mysqli_commit($conn);


    /*
     * Hapus file dokumen setelah database berhasil dihapus
     */
    $folder = "../uploads/dokumen_etik/";

    foreach ($file_dokumen as $nama_file) {

        $file_path = $folder . basename($nama_file);

        if (is_file($file_path)) {
            @unlink($file_path);
        }
    }


    /*
     * Kembali ke daftar kasus
     */
    header("Location: index.php?hapus=berhasil");
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    die(
        "Gagal menghapus kasus.<br><br>" .
        "Pesan: " . htmlspecialchars($e->getMessage())
    );
}