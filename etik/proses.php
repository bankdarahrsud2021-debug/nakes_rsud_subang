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
        n.nomor_induk,
        n.nip,
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
    die("Data kasus etik tidak ditemukan.");
}

/* =========================
   RIWAYAT PROSES
========================= */
$stmt_proses = mysqli_prepare($conn, "
    SELECT *
    FROM etik_proses
    WHERE id_kasus = ?
    ORDER BY tanggal_proses ASC, id_proses ASC
");

mysqli_stmt_bind_param($stmt_proses, "i", $id_kasus);
mysqli_stmt_execute($stmt_proses);

$result_proses = mysqli_stmt_get_result($stmt_proses);

$proses = [];

while ($row = mysqli_fetch_assoc($result_proses)) {
    $proses[] = $row;
}

/* =========================
   TENTUKAN POSISI STATUS
========================= */
$status_sekarang = $kasus['status_kasus'];

$index_sekarang = array_search(
    $status_sekarang,
    $alur_status,
    true
);

if ($index_sekarang === false) {
    $index_sekarang = 0;
}

$sudah_selesai = ($status_sekarang === 'SELESAI');

if (!$sudah_selesai && isset($alur_status[$index_sekarang + 1])) {
    $status_berikutnya = $alur_status[$index_sekarang + 1];
} else {
    $status_berikutnya = null;
}

/* =========================
   NAMA NAKES
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

/* =========================
   DOKUMEN
========================= */
$stmt_dokumen = mysqli_prepare($conn, "
    SELECT
        COUNT(*) AS total,
        SUM(
            CASE
                WHEN status_dokumen = 'ADA'
                AND nama_file IS NOT NULL
                AND nama_file <> ''
                THEN 1
                ELSE 0
            END
        ) AS ada
    FROM etik_dokumen
    WHERE id_kasus = ?
");

mysqli_stmt_bind_param($stmt_dokumen, "i", $id_kasus);
mysqli_stmt_execute($stmt_dokumen);

$result_dokumen = mysqli_stmt_get_result($stmt_dokumen);
$dokumen = mysqli_fetch_assoc($result_dokumen);

$total_dokumen = (int)($dokumen['total'] ?? 0);
$ada_dokumen = (int)($dokumen['ada'] ?? 0);

$persentase_dokumen = $total_dokumen > 0
    ? round(($ada_dokumen / $total_dokumen) * 100)
    : 0;


/* =========================
   FORMAT
========================= */
function h($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal === '0000-00-00') {
        return '-';
    }

    $bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    $pecah = explode('-', $tanggal);

    if (count($pecah) !== 3) {
        return $tanggal;
    }

    return (int)$pecah[2] . ' ' .
        $bulan[(int)$pecah[1]] . ' ' .
        $pecah[0];
}

function statusClass($status)
{
    switch ($status) {

        case 'SELESAI':
            return 'status-selesai';

        case 'KEPUTUSAN':
            return 'status-keputusan';

        case 'REKOMENDASI':
            return 'status-rekomendasi';

        case 'RAPAT ETIK':
            return 'status-rapat';

        case 'DALAM PEMERIKSAAN':
            return 'status-pemeriksaan';

        case 'TELAAH AWAL':
            return 'status-telaah';

        default:
            return 'status-dilaporkan';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>Proses Etik - <?= h($kasus['nomor_kasus']); ?></title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    color: #212529;
}

.container {
    width: 95%;
    max-width: 1250px;
    margin: 25px auto;
}

.card {
    background: #fff;
    border-radius: 10px;
    padding: 22px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.header h2 {
    margin: 0 0 5px;
}

.header p {
    margin: 0;
    color: #666;
}

.actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 9px 13px;
    border-radius: 6px;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    font-size: 13px;
}

.btn-primary {
    background: #0d6efd;
    color: #fff;
}

.btn-success {
    background: #198754;
    color: #fff;
}

.btn-warning {
    background: #ffc107;
    color: #000;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 7px;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

.info-label {
    width: 190px;
    font-weight: bold;
}

.status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-dilaporkan {
    background: #e9ecef;
    color: #495057;
}

.status-telaah {
    background: #cff4fc;
    color: #055160;
}

.status-pemeriksaan {
    background: #fff3cd;
    color: #664d03;
}

.status-rapat {
    background: #cfe2ff;
    color: #084298;
}

.status-rekomendasi {
    background: #e2d9f3;
    color: #432874;
}

.status-keputusan {
    background: #d1e7dd;
    color: #0f5132;
}

.status-selesai {
    background: #198754;
    color: white;
}

/* WORKFLOW */

.workflow {
    display: flex;
    align-items: center;
    gap: 0;
    overflow-x: auto;
    padding: 15px 5px 20px;
}

.workflow-item {
    min-width: 130px;
    text-align: center;
    position: relative;
}

.workflow-circle {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    margin: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    background: #dee2e6;
    color: #495057;
}

.workflow-label {
    font-size: 11px;
    margin-top: 8px;
    font-weight: bold;
}

.workflow-line {
    height: 4px;
    flex: 1;
    min-width: 25px;
    background: #dee2e6;
}

.workflow-item.done .workflow-circle {
    background: #198754;
    color: white;
}

.workflow-item.current .workflow-circle {
    background: #0d6efd;
    color: white;
}

.workflow-item.done + .workflow-line {
    background: #198754;
}

.next-box {
    background: #e8f4fd;
    border: 1px solid #b8dff5;
    border-radius: 8px;
    padding: 16px;
}

.finished-box {
    background: #d1e7dd;
    border: 1px solid #a3cfbb;
    border-radius: 8px;
    padding: 16px;
    color: #0f5132;
}

/* TABLE */

.table-responsive {
    overflow-x: auto;
}

table.data {
    width: 100%;
    border-collapse: collapse;
}

table.data th,
table.data td {
    border: 1px solid #dee2e6;
    padding: 9px;
    vertical-align: top;
}

table.data th {
    background: #f8f9fa;
    text-align: left;
    font-size: 13px;
}

table.data td {
    font-size: 13px;
}

.empty {
    text-align: center;
    padding: 25px;
    color: #777;
}

/* DOCUMENT */

.progress {
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-bar {
    height: 100%;
    background: #198754;
    color: white;
    text-align: center;
    font-size: 11px;
    line-height: 20px;
}

.alert-success {
    background: #d1e7dd;
    border: 1px solid #a3cfbb;
    color: #0f5132;
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
}

@media (max-width: 700px) {

    .container {
        width: 98%;
    }

    .info-label {
        width: 130px;
    }

    .workflow-item {
        min-width: 110px;
    }

}

</style>

</head>

<body>

<div class="container">


<!-- HEADER -->

<div class="card">

<div class="header">

<div>

<h2>
📋 Proses Kasus Etik
</h2>

<p>
Nomor Kasus:
<strong><?= h($kasus['nomor_kasus']); ?></strong>
</p>

</div>

<div class="actions">

<a
href="index.php"
class="btn btn-secondary"
>
← Daftar
</a>

<a
href="detail.php?id=<?= $id_kasus; ?>"
class="btn btn-primary"
>
Detail
</a>

<a
href="dokumen.php?id=<?= $id_kasus; ?>"
class="btn btn-warning"
>
📎 Dokumen
</a>

<a
href="cetak.php?id=<?= $id_kasus; ?>"
target="_blank"
class="btn btn-success"
>
🖨 Cetak
</a>

</div>

</div>

</div>


<!-- PESAN BERHASIL -->

<?php if (isset($_GET['simpan']) && $_GET['simpan'] === 'berhasil'): ?>

<div class="alert-success">

<strong>✓ Proses berhasil disimpan.</strong>

Status kasus telah diperbarui ke:

<strong><?= h($status_sekarang); ?></strong>

</div>

<?php endif; ?>


<!-- IDENTITAS -->

<div class="card">

<h3>👤 Identitas Kasus</h3>

<table class="info-table">

<tr>
<td class="info-label">Nomor Kasus</td>
<td><?= h($kasus['nomor_kasus']); ?></td>
</tr>

<tr>
<td class="info-label">Tanggal Laporan</td>
<td><?= formatTanggal($kasus['tanggal_laporan']); ?></td>
</tr>

<tr>
<td class="info-label">Tenaga Kesehatan</td>
<td><?= h($nama_nakes); ?></td>
</tr>

<tr>
<td class="info-label">Profesi</td>
<td><?= h($kasus['nama_profesi'] ?: '-'); ?></td>
</tr>

<tr>
<td class="info-label">Unit Kerja</td>
<td><?= h($kasus['nama_unit'] ?: '-'); ?></td>
</tr>

<tr>
<td class="info-label">Jenis Masalah</td>
<td><?= h($kasus['jenis_masalah']); ?></td>
</tr>

<tr>
<td class="info-label">Klasifikasi Etik</td>
<td><?= h($kasus['klasifikasi_etik'] ?: '-'); ?></td>
</tr>

<tr>
<td class="info-label">Status</td>
<td>

<span class="status <?= statusClass($status_sekarang); ?>">

<?= h($status_sekarang); ?>

</span>

</td>
</tr>

</table>

</div>


<!-- WORKFLOW -->

<div class="card">

<h3>🔄 Alur Proses Etik</h3>

<div class="workflow">

<?php foreach ($alur_status as $i => $status): ?>

<div class="workflow-item
<?php
if ($i < $index_sekarang) {
    echo ' done';
} elseif ($i === $index_sekarang) {
    echo ' current';
}
?>
">

<div class="workflow-circle">

<?php
if ($i < $index_sekarang) {
    echo '✓';
} else {
    echo $i + 1;
}
?>

</div>

<div class="workflow-label">

<?= h($status); ?>

</div>

</div>

<?php if ($i < count($alur_status) - 1): ?>

<div class="workflow-line"></div>

<?php endif; ?>

<?php endforeach; ?>

</div>


<?php if (!$sudah_selesai): ?>

<div class="next-box">

<strong>➡ Tahap berikutnya:</strong>

<span class="status <?= statusClass($status_berikutnya); ?>">

<?= h($status_berikutnya); ?>

</span>

<br><br>

<a
href="proses_tambah.php?id=<?= $id_kasus; ?>&status=<?= urlencode($status_berikutnya); ?>"
class="btn btn-primary"
>
➕ Proses Tahap Berikutnya
</a>

</div>

<?php else: ?>

<div class="finished-box">

<strong>🏁 Kasus telah selesai.</strong>

<br>

Seluruh tahapan proses etik telah diselesaikan.

<?php if (!empty($kasus['tanggal_selesai'])): ?>

<br>

Tanggal selesai:
<strong>
<?= formatTanggal($kasus['tanggal_selesai']); ?>
</strong>

<?php endif; ?>

</div>

<?php endif; ?>

</div>


<!-- DOKUMEN -->

<div class="card">

<div class="header">

<div>

<h3>📎 Kelengkapan Dokumen</h3>

<p>
<?= $ada_dokumen; ?> dari <?= $total_dokumen; ?> dokumen tersedia.
</p>

</div>

<div>

<strong><?= $persentase_dokumen; ?>%</strong>

</div>

</div>

<div class="progress">

<div
class="progress-bar"
style="width: <?= $persentase_dokumen; ?>%;"
>

<?= $persentase_dokumen; ?>%

</div>

</div>

<br>

<a
href="dokumen.php?id=<?= $id_kasus; ?>"
class="btn btn-warning"
>
Kelola Dokumen
</a>

</div>


<!-- RIWAYAT -->

<div class="card">

<h3>📜 Riwayat Proses Etik</h3>

<div class="table-responsive">

<table class="data">

<thead>

<tr>

<th width="4%">No</th>

<th width="12%">Tanggal</th>

<th width="17%">Proses</th>

<th>Uraian</th>

<th>Pihak Terlibat</th>

<th>Hasil</th>

<th width="12%">Status</th>

<th>Petugas</th>

</tr>

</thead>

<tbody>

<?php if (count($proses) > 0): ?>

<?php $no = 1; ?>

<?php foreach ($proses as $item): ?>

<tr>

<td><?= $no++; ?></td>

<td>
<?= formatTanggal($item['tanggal_proses']); ?>
</td>

<td>

<span class="status <?= statusClass($item['jenis_proses']); ?>">

<?= h($item['jenis_proses']); ?>

</span>

</td>

<td>
<?= nl2br(h($item['uraian_proses'] ?: '-')); ?>
</td>

<td>
<?= nl2br(h($item['pihak_terlibat'] ?: '-')); ?>
</td>

<td>
<?= nl2br(h($item['hasil_proses'] ?: '-')); ?>
</td>

<td>
<?= h($item['status_proses']); ?>
</td>

<td>
<?= h($item['petugas'] ?: '-'); ?>
</td>

</tr>

<?php endforeach; ?>

<?php else: ?>

<tr>

<td colspan="8" class="empty">

Belum ada proses etik yang dicatat.

</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</div>


<!-- TOMBOL BAWAH -->

<div class="card">

<div class="actions">

<a
href="detail.php?id=<?= $id_kasus; ?>"
class="btn btn-primary"
>
← Kembali ke Detail
</a>

<a
href="edit.php?id=<?= $id_kasus; ?>"
class="btn btn-warning"
>
✏ Edit Kasus
</a>

<a
href="dokumen.php?id=<?= $id_kasus; ?>"
class="btn btn-secondary"
>
📎 Dokumen
</a>

<a
href="cetak.php?id=<?= $id_kasus; ?>"
target="_blank"
class="btn btn-success"
>
🖨 Cetak
</a>

</div>

</div>

</div>

</body>

</html>