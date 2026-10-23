<?php

include "../config/auth.php";
include "../config/database.php";

$id_kredensial =
    (int)($_GET['id'] ?? 0);

if ($id_kredensial <= 0) {
    die("ID tidak valid.");
}

$query = "
    SELECT *
    FROM kredensial
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

mysqli_stmt_execute($stmt);

$result =
    mysqli_stmt_get_result($stmt);

$data =
    mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tidak ditemukan.");
}

$query_nakes = "
    SELECT
        t.id_nakes,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi
    FROM tenaga_kesehatan t
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    WHERE t.status_aktif = 'AKTIF'
    ORDER BY t.nama_lengkap ASC
";

$result_nakes =
    mysqli_query($conn, $query_nakes);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Edit Kredensial</title>

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

label {
    display: block;
    margin-top: 12px;
    font-weight: bold;
}

input,
select,
textarea {
    width: 100%;
    padding: 9px;
    margin-top: 5px;
    box-sizing: border-box;
}

textarea {
    min-height: 100px;
}

button {
    margin-top: 20px;
    padding: 10px 18px;
    border: none;
    border-radius: 5px;
    background: #0d6efd;
    color: white;
}

</style>

</head>

<body>

<div class="container">

<a href="index.php">
← Kembali
</a>

<h2>Edit Pengajuan Kredensial</h2>

<form
    action="update.php"
    method="POST">

<input
    type="hidden"
    name="id_kredensial"
    value="<?= $data['id_kredensial']; ?>">

<label>
Tenaga Kesehatan
</label>

<select
    name="id_nakes"
    required>

<?php while ($row = mysqli_fetch_assoc($result_nakes)) { ?>

<option
    value="<?= $row['id_nakes']; ?>"
    <?= $row['id_nakes'] == $data['id_nakes']
        ? 'selected'
        : ''; ?>>

<?= htmlspecialchars(
    trim(
        ($row['gelar_depan'] ?? '') . ' ' .
        $row['nama_lengkap'] . ' ' .
        ($row['gelar_belakang'] ?? '')
    )
); ?>

-
<?= htmlspecialchars(
    $row['nama_profesi'] ?? ''
); ?>

</option>

<?php } ?>

</select>

<label>
Jenis Kredensial
</label>

<select
    name="jenis_kredensial"
    required>

<option value="KREDENSIAL AWAL"
<?= $data['jenis_kredensial'] == 'KREDENSIAL AWAL'
    ? 'selected'
    : ''; ?>>
Kredensial Awal
</option>

<option value="KREDENSIAL ULANG"
<?= $data['jenis_kredensial'] == 'KREDENSIAL ULANG'
    ? 'selected'
    : ''; ?>>
Kredensial Ulang
</option>

<option value="REKREDENSIAL"
<?= $data['jenis_kredensial'] == 'REKREDENSIAL'
    ? 'selected'
    : ''; ?>>
Rekredensial
</option>

</select>

<label>
Tanggal Pengajuan
</label>

<input
    type="date"
    name="tanggal_pengajuan"
    value="<?= htmlspecialchars(
        $data['tanggal_pengajuan']
    ); ?>"
    required>

<label>
Catatan
</label>

<textarea
    name="catatan"><?= htmlspecialchars(
        $data['catatan'] ?? ''
    ); ?></textarea>

<button type="submit">
💾 Update
</button>

</form>

</div>

</body>

</html>