<?php
include "../config/auth.php";
include "../config/database.php";

$query = "
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

$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Tambah Kredensial</title>

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
    background: #198754;
    color: white;
    cursor: pointer;
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

<h2>Tambah Pengajuan Kredensial</h2>

<form
    action="simpan.php"
    method="POST">

<label>
Tenaga Kesehatan
</label>

<select
    name="id_nakes"
    required>

<option value="">
-- Pilih Tenaga Kesehatan --
</option>

<?php while ($row = mysqli_fetch_assoc($result)) { ?>

<option value="<?= $row['id_nakes']; ?>">

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

<option value="">
-- Pilih Jenis Kredensial --
</option>

<option value="KREDENSIAL AWAL">
Kredensial Awal
</option>

<option value="KREDENSIAL ULANG">
Kredensial Ulang
</option>

<option value="REKREDENSIAL">
Rekredensial
</option>

</select>

<label>
Tanggal Pengajuan
</label>

<input
    type="date"
    name="tanggal_pengajuan"
    value="<?= date('Y-m-d'); ?>"
    required>

<label>
Catatan
</label>

<textarea
    name="catatan"
    placeholder="Catatan pengajuan jika ada"></textarea>

<button type="submit">
💾 Simpan Pengajuan
</button>

</form>

</div>

</body>

</html>