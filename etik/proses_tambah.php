<?php
require_once "../config/auth.php";
require_once "../config/database.php";

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

/* =========================
   ALUR STATUS
========================= */
$alur_status = [
    'DILAPORKAN',
    'TELAAH AWAL',
    'DALAM PEMERIKSAAN',
    'RAPAT ETIK',
    'REKOMENDASI',
    'KEPUTUSAN',
    'SELESAI'
];

/* =========================
   DATA KASUS
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT 
        e.*,
        n.nama_lengkap,
        n.gelar_depan,
        n.gelar_belakang,
        p.nama_profesi,
        u.nama_unit
    FROM etik_kasus e
    LEFT JOIN tenaga_kesehatan n
        ON e.id_nakes = n.id_nakes
    LEFT JOIN profesi p
        ON n.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit
    WHERE e.id_kasus = ?
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$kasus = mysqli_fetch_assoc($result);

if (!$kasus) {
    die("Data kasus tidak ditemukan.");
}

/* =========================
   TENTUKAN STATUS BERIKUTNYA
========================= */
$status_sekarang = $kasus['status_kasus'];

$index_sekarang = array_search($status_sekarang, $alur_status);

if ($index_sekarang === false) {
    $index_sekarang = 0;
}

$index_berikutnya = $index_sekarang + 1;

if ($index_berikutnya >= count($alur_status)) {
    $status_berikutnya = 'SELESAI';
} else {
    $status_berikutnya = $alur_status[$index_berikutnya];
}

/* =========================
   CEK STATUS DARI URL
========================= */
$status_url = isset($_GET['status'])
    ? trim($_GET['status'])
    : '';

if (
    $status_url !== '' &&
    in_array($status_url, $alur_status, true) &&
    $status_url === $status_berikutnya
) {
    $jenis_proses = $status_url;
} else {
    $jenis_proses = $status_berikutnya;
}

/* =========================
   NAMA TENAGA KESEHATAN
========================= */
$nama_nakes = '-';

if (!empty($kasus['nama_lengkap'])) {

    $nama_nakes = trim(
        ($kasus['gelar_depan']
            ? $kasus['gelar_depan'] . ' '
            : ''
        ) .
        $kasus['nama_lengkap'] .
        ($kasus['gelar_belakang']
            ? ', ' . $kasus['gelar_belakang']
            : ''
        )
    );
}

function h($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Tambah Proses Etik</title>

<style>

body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
}

.container {
    width: 90%;
    max-width: 1000px;
    margin: 30px auto;
}

.card {
    background: #fff;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
}

h2 {
    margin-top: 0;
}

.info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info table {
    width: 100%;
    border-collapse: collapse;
}

.info td {
    padding: 5px;
    vertical-align: top;
}

.label {
    width: 180px;
    font-weight: bold;
}

.alert {
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 15px;
}

.alert-info {
    background: #e8f4fd;
    border: 1px solid #b8dff5;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffe69c;
}

.form-group {
    margin-bottom: 18px;
}

label {
    display: block;
    font-weight: bold;
    margin-bottom: 7px;
}

input,
select,
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

.btn {
    display: inline-block;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    margin-right: 5px;
}

.btn-primary {
    background: #0d6efd;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.workflow {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 15px 0 25px;
}

.step {
    padding: 7px 10px;
    border-radius: 20px;
    background: #e9ecef;
    font-size: 12px;
}

.step.active {
    background: #0d6efd;
    color: white;
}

.step.done {
    background: #198754;
    color: white;
}

.required {
    color: red;
}

</style>

</head>

<body>

<div class="container">

<div class="card">

<h2>➕ Tambah Proses Etik</h2>

<div class="info">

<table>

<tr>
<td class="label">Nomor Kasus</td>
<td><?= h($kasus['nomor_kasus']); ?></td>
</tr>

<tr>
<td class="label">Tenaga Kesehatan</td>
<td><?= h($nama_nakes); ?></td>
</tr>

<tr>
<td class="label">Profesi</td>
<td><?= h($kasus['nama_profesi'] ?: '-'); ?></td>
</tr>

<tr>
<td class="label">Unit</td>
<td><?= h($kasus['nama_unit'] ?: '-'); ?></td>
</tr>

<tr>
<td class="label">Status Sekarang</td>
<td><strong><?= h($status_sekarang); ?></strong></td>
</tr>

<tr>
<td class="label">Tahap Berikutnya</td>
<td><strong><?= h($status_berikutnya); ?></strong></td>
</tr>

</table>

</div>


<!-- WORKFLOW -->

<div class="workflow">

<?php foreach ($alur_status as $i => $status): ?>

<?php

$class = '';

if ($i < $index_sekarang) {
    $class = 'done';
} elseif ($i == $index_sekarang) {
    $class = 'active';
}

?>

<div class="step <?= $class; ?>">

<?= h($status); ?>

</div>

<?php endforeach; ?>

</div>


<?php if ($status_sekarang === 'SELESAI'): ?>

<div class="alert alert-warning">

<strong>Kasus telah selesai.</strong>

Tidak ada proses berikutnya yang dapat ditambahkan.

</div>

<a href="proses.php?id=<?= $id_kasus; ?>"
   class="btn btn-secondary">

Kembali

</a>

<?php else: ?>


<div class="alert alert-info">

<strong>Tahap berikutnya:</strong>
<?= h($status_berikutnya); ?>

<br>

Sistem hanya mengizinkan proses dilakukan secara berurutan.

</div>


<form method="POST" action="proses_simpan.php">

<input
    type="hidden"
    name="id_kasus"
    value="<?= $id_kasus; ?>"
>

<input
    type="hidden"
    name="jenis_proses"
    value="<?= h($status_berikutnya); ?>"
>


<div class="form-group">

<label>
Tanggal Proses
<span class="required">*</span>
</label>

<input
    type="date"
    name="tanggal_proses"
    value="<?= date('Y-m-d'); ?>"
    required
>

</div>


<div class="form-group">

<label>
Jenis Proses
</label>

<input
    type="text"
    value="<?= h($status_berikutnya); ?>"
    readonly
>

</div>


<div class="form-group">

<label>
Status Proses
<span class="required">*</span>
</label>

<select name="status_proses" required>

<option value="TERJADWAL">
TERJADWAL
</option>

<option value="DILAKSANAKAN">
DILAKSANAKAN
</option>

<option value="SELESAI" selected>
SELESAI
</option>

</select>

</div>


<div class="form-group">

<label>
Uraian Proses
</label>

<textarea
    name="uraian_proses"
    placeholder="Tuliskan uraian kegiatan/proses etik yang dilakukan..."
></textarea>

</div>


<div class="form-group">

<label>
Pihak yang Terlibat
</label>

<textarea
    name="pihak_terlibat"
    placeholder="Contoh: Ketua Sub Komite Etik, anggota Sub Komite Etik, tenaga kesehatan terkait, kepala unit..."
></textarea>

</div>


<div class="form-group">

<label>
Hasil Proses
</label>

<textarea
    name="hasil_proses"
    placeholder="Tuliskan hasil telaah, pemeriksaan, rapat, atau keputusan..."
></textarea>

</div>


<div class="form-group">

<label>
Rekomendasi
</label>

<textarea
    name="rekomendasi"
    placeholder="Tuliskan rekomendasi Sub Komite Etik jika ada..."
></textarea>

</div>


<div class="form-group">

<label>
Petugas / Penanggung Jawab
</label>

<input
    type="text"
    name="petugas"
    value="<?= h($_SESSION['nama_user'] ?? ''); ?>"
>

</div>


<button
    type="submit"
    class="btn btn-primary"
>

💾 Simpan Proses

</button>


<a
    href="proses.php?id=<?= $id_kasus; ?>"
    class="btn btn-secondary"
>

Batal

</a>

</form>

<?php endif; ?>

</div>

</div>

</body>

</html>