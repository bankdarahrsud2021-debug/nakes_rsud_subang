<?php

include "../config/auth.php";
include "../config/database.php";

$id_kredensial =
    (int)($_GET['id'] ?? 0);

if ($id_kredensial <= 0) {
    die("ID kredensial tidak valid.");
}

$query = "
    SELECT
        k.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi,
        u.nama_unit
    FROM kredensial k
    INNER JOIN tenaga_kesehatan t
        ON k.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    WHERE k.id_kredensial = ?
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

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$data =
    mysqli_fetch_assoc($result);

if (!$data) {
    die("Data kredensial tidak ditemukan.");
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Detail Kredensial</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    padding: 20px;
}

.container {
    background: white;
    max-width: 700px;
    margin: auto;
    padding: 25px;
    border-radius: 8px;
}

.row {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}

.label {
    font-weight: bold;
    display: inline-block;
    width: 220px;
}

.back {
    text-decoration: none;
}

</style>

</head>

<body>

<div class="container">

<a href="index.php" class="back">
← Kembali
</a>

<h2>Detail Pengajuan Kredensial</h2>

<hr>

<div class="row">

<span class="label">
Tenaga Kesehatan
</span>

<?= htmlspecialchars(
    trim(
        ($data['gelar_depan'] ?? '') . ' ' .
        $data['nama_lengkap'] . ' ' .
        ($data['gelar_belakang'] ?? '')
    )
); ?>

</div>

<div class="row">

<span class="label">
Profesi
</span>

<?= htmlspecialchars(
    $data['nama_profesi'] ?? '-'
); ?>

</div>

<div class="row">

<span class="label">
Unit
</span>

<?= htmlspecialchars(
    $data['nama_unit'] ?? '-'
); ?>

</div>

<div class="row">

<span class="label">
Jenis Kredensial
</span>

<?= htmlspecialchars(
    $data['jenis_kredensial']
); ?>

</div>

<div class="row">

<span class="label">
Tanggal Pengajuan
</span>

<?= !empty($data['tanggal_pengajuan'])
    ? date(
        'd-m-Y',
        strtotime($data['tanggal_pengajuan'])
    )
    : '-'; ?>

</div>

<div class="row">

<span class="label">
Tanggal Penilaian
</span>

<?= !empty($data['tanggal_penilaian'])
    ? date(
        'd-m-Y',
        strtotime($data['tanggal_penilaian'])
    )
    : '-'; ?>

</div>

<div class="row">

<span class="label">
Tanggal Keputusan
</span>

<?= !empty($data['tanggal_keputusan'])
    ? date(
        'd-m-Y',
        strtotime($data['tanggal_keputusan'])
    )
    : '-'; ?>

</div>

<div class="row">

<span class="label">
Tanggal Mulai
</span>

<?= !empty($data['tanggal_mulai'])
    ? date(
        'd-m-Y',
        strtotime($data['tanggal_mulai'])
    )
    : '-'; ?>

</div>

<div class="row">

<span class="label">
Tanggal Berakhir
</span>

<?= !empty($data['tanggal_berakhir'])
    ? date(
        'd-m-Y',
        strtotime($data['tanggal_berakhir'])
    )
    : '-'; ?>

</div>

<div class="row">

<span class="label">
Status
</span>

<?= htmlspecialchars(
    $data['status'] ?? '-'
); ?>

</div>

<div class="row">

<span class="label">
Catatan
</span>

<?= nl2br(
    htmlspecialchars(
        $data['catatan'] ?? '-'
    )
); ?>

</div>

<br>

<a
    href="proses.php?id=<?= $data['id_kredensial']; ?>">

⚙ Proses Kredensial

</a>

</div>

</body>

</html>