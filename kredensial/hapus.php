<?php

include "../config/auth.php";
include "../config/database.php";

$id_kredensial =
    (int)($_GET['id'] ?? 0);

if ($id_kredensial <= 0) {
    die("ID tidak valid.");
}

$query = "
    DELETE FROM kredensial
    WHERE id_kredensial = ?
";

$stmt = mysqli_prepare(
    $conn,
    $query
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kredensial
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: index.php");
    exit;

} else {

    die(
        "Gagal menghapus data: " .
        mysqli_stmt_error($stmt)
    );

}