<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$id_nakes = intval($_GET['id'] ?? 0);

if ($id_nakes <= 0) {
    die("ID tenaga kesehatan tidak valid.");
}

/* =========================
   AMBIL DATA NAKES
========================= */

$sql = "
SELECT *
FROM tenaga_kesehatan
WHERE id_nakes = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_nakes
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tenaga kesehatan tidak ditemukan.");
}


/* =========================
   MASTER PROFESI
========================= */

$profesi = mysqli_query(
    $conn,
    "SELECT id_profesi, nama_profesi
     FROM profesi
     ORDER BY nama_profesi ASC"
);


/* =========================
   MASTER UNIT
========================= */

$unit_kerja = mysqli_query(
    $conn,
    "SELECT id_unit, nama_unit
     FROM unit_kerja
     ORDER BY nama_unit ASC"
);


/* =========================
   MASTER JABATAN
========================= */

$jabatan = mysqli_query(
    $conn,
    "SELECT id_jabatan, nama_jabatan
     FROM jabatan
     ORDER BY nama_jabatan ASC"
);


/* =========================
   MASTER STATUS KEPEGAWAIAN
========================= */

$status_kepegawaian = mysqli_query(
    $conn,
    "SELECT id_status, nama_status
     FROM status_kepegawaian
     ORDER BY id_status ASC"
);

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Edit Tenaga Kesehatan</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 30px;
}

.container {
    max-width: 1000px;
    margin: auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
}

h2 {
    margin-top: 0;
    color: #333;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.full {
    grid-column: 1 / 3;
}

label {
    font-weight: bold;
    margin-bottom: 6px;
}

input,
select,
textarea {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    box-sizing: border-box;
}

textarea {
    min-height: 80px;
}

.current-photo {
    margin-top: 10px;
}

.current-photo img {
    width: 120px;
    height: 150px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.button-area {
    margin-top: 25px;
    display: flex;
    gap: 10px;
}

button,
.btn-kembali {
    padding: 11px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    font-size: 14px;
}

button {
    background: #198754;
    color: white;
}

.btn-kembali {
    background: #6c757d;
    color: white;
}

@media (max-width: 700px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .full {
        grid-column: 1;
    }

    body {
        padding: 10px;
    }

}

</style>

</head>

<body>

<div class="container">

<h2>Edit Tenaga Kesehatan</h2>

<form action="update.php"
      method="POST"
      enctype="multipart/form-data">

<input
    type="hidden"
    name="id_nakes"
    value="<?= $data['id_nakes']; ?>"
>


<div class="form-grid">


<!-- NIK -->

<div class="form-group">

<label>NIK</label>

<input
    type="text"
    name="nik"
    value="<?= htmlspecialchars($data['nik'] ?? ''); ?>"
    required
>

</div>


<!-- NIP -->

<div class="form-group">

<label>NIP</label>

<input
    type="text"
    name="nip"
    value="<?= htmlspecialchars($data['nip'] ?? ''); ?>"
>

</div>


<!-- Nama -->

<div class="form-group full">

<label>Nama Lengkap</label>

<input
    type="text"
    name="nama_lengkap"
    value="<?= htmlspecialchars($data['nama_lengkap'] ?? ''); ?>"
    required
>

</div>


<!-- Gelar Depan -->

<div class="form-group">

<label>Gelar Depan</label>

<input
    type="text"
    name="gelar_depan"
    value="<?= htmlspecialchars($data['gelar_depan'] ?? ''); ?>"
>

</div>


<!-- Gelar Belakang -->

<div class="form-group">

<label>Gelar Belakang</label>

<input
    type="text"
    name="gelar_belakang"
    value="<?= htmlspecialchars($data['gelar_belakang'] ?? ''); ?>"
>

</div>


<!-- Tempat Lahir -->

<div class="form-group">

<label>Tempat Lahir</label>

<input
    type="text"
    name="tempat_lahir"
    value="<?= htmlspecialchars($data['tempat_lahir'] ?? ''); ?>"
>

</div>


<!-- Tanggal Lahir -->

<div class="form-group">

<label>Tanggal Lahir</label>

<input
    type="date"
    name="tanggal_lahir"
    value="<?= htmlspecialchars($data['tanggal_lahir'] ?? ''); ?>"
>

</div>


<!-- Jenis Kelamin -->

<div class="form-group">

<label>Jenis Kelamin</label>

<select name="jenis_kelamin" required>

<option value="">-- Pilih --</option>

<option
    value="L"
    <?= ($data['jenis_kelamin'] ?? '') == 'L'
        ? 'selected'
        : ''; ?>
>
Laki-laki
</option>

<option
    value="P"
    <?= ($data['jenis_kelamin'] ?? '') == 'P'
        ? 'selected'
        : ''; ?>
>
Perempuan
</option>

</select>

</div>


<!-- No HP -->

<div class="form-group">

<label>No. HP</label>

<input
    type="text"
    name="no_hp"
    value="<?= htmlspecialchars($data['no_hp'] ?? ''); ?>"
>

</div>


<!-- Email -->

<div class="form-group">

<label>Email</label>

<input
    type="email"
    name="email"
    value="<?= htmlspecialchars($data['email'] ?? ''); ?>"
>

</div>


<!-- PROFESI -->

<div class="form-group">

<label>Profesi</label>

<select name="id_profesi" required>

<option value="">
-- Pilih Profesi --
</option>

<?php while ($row = mysqli_fetch_assoc($profesi)) { ?>

<option
    value="<?= $row['id_profesi']; ?>"
    <?= $data['id_profesi'] == $row['id_profesi']
        ? 'selected'
        : ''; ?>
>

<?= htmlspecialchars($row['nama_profesi']); ?>

</option>

<?php } ?>

</select>

</div>


<!-- UNIT -->

<div class="form-group">

<label>Unit Kerja</label>

<select name="id_unit" required>

<option value="">
-- Pilih Unit Kerja --
</option>

<?php while ($row = mysqli_fetch_assoc($unit_kerja)) { ?>

<option
    value="<?= $row['id_unit']; ?>"
    <?= $data['id_unit'] == $row['id_unit']
        ? 'selected'
        : ''; ?>
>

<?= htmlspecialchars($row['nama_unit']); ?>

</option>

<?php } ?>

</select>

</div>


<!-- JABATAN -->

<div class="form-group">

<label>Jabatan</label>

<select name="id_jabatan">

<option value="">
-- Pilih Jabatan --
</option>

<?php while ($row = mysqli_fetch_assoc($jabatan)) { ?>

<option
    value="<?= $row['id_jabatan']; ?>"
    <?= $data['id_jabatan'] == $row['id_jabatan']
        ? 'selected'
        : ''; ?>
>

<?= htmlspecialchars($row['nama_jabatan']); ?>

</option>

<?php } ?>

</select>

</div>


<!-- STATUS KEPEGAWAIAN -->

<div class="form-group">

<label>Status Kepegawaian</label>

<select
    name="id_status_kepegawaian"
    required
>

<option value="">
-- Pilih Status Kepegawaian --
</option>

<?php while ($row = mysqli_fetch_assoc($status_kepegawaian)) { ?>

<option
    value="<?= $row['id_status']; ?>"
    <?= $data['id_status_kepegawaian'] == $row['id_status']
        ? 'selected'
        : ''; ?>
>

<?= htmlspecialchars($row['nama_status']); ?>

</option>

<?php } ?>

</select>

</div>


<!-- TANGGAL MULAI -->

<div class="form-group">

<label>Tanggal Mulai Kerja</label>

<input
    type="date"
    name="tanggal_mulai_kerja"
    value="<?= htmlspecialchars($data['tanggal_mulai_kerja'] ?? ''); ?>"
>

</div>


<!-- ALAMAT -->

<div class="form-group full">

<label>Alamat</label>

<textarea
    name="alamat"
><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>

</div>


<!-- FOTO -->

<div class="form-group full">

<label>Foto Baru</label>

<input
    type="file"
    name="foto"
    accept=".jpg,.jpeg,.png"
>

<?php if (!empty($data['foto'])) { ?>

<div class="current-photo">

<p>Foto saat ini:</p>

<img
    src="../<?= htmlspecialchars($data['foto']); ?>"
    alt="Foto"
>

</div>

<?php } ?>

</div>


</div>


<div class="button-area">

<button type="submit">
💾 Simpan Perubahan
</button>

<a
    href="index.php"
    class="btn-kembali"
>
Kembali
</a>

</div>

</form>

</div>

</body>

</html>