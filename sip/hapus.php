<?php
include "../config/auth.php";
include "../config/database.php";

$id_sip = (int)($_GET['id'] ?? 0);

if ($id_sip <= 0) {
    die("ID SIP tidak valid.");
}

/* Ambil file */

$query = "
    SELECT file_sip
    FROM sip_nakes
    WHERE id_sip = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_sip
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data SIP tidak ditemukan.");
}

/* Hapus database */

$query_delete = "
    DELETE FROM sip_nakes
    WHERE id_sip = ?
";

$stmt_delete = mysqli_prepare(
    $conn,
    $query_delete
);

mysqli_stmt_bind_param(
    $stmt_delete,
    "i",
    $id_sip
);

if (mysqli_stmt_execute($stmt_delete)) {

    /* Hapus file */

    if (!empty($data['file_sip'])) {

        $file_path =
            "../uploads/sip_nakes/" .
            $data['file_sip'];

        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    header("Location: index.php");
    exit;

} else {

    die(
        "Gagal menghapus data SIP: " .
        mysqli_stmt_error($stmt_delete)
    );
}