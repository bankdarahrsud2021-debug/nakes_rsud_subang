<?php
require_once "../config/auth.php";
require_once "../config/database.php";

function h($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

/*
|--------------------------------------------------------------------------
| DATA KASUS
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        k.*,

        n.nama_lengkap,
        n.gelar_depan,
        n.gelar_belakang,
        n.nomor_induk,

        p.nama_profesi,
        u.nama_unit

    FROM etik_kasus k

    LEFT JOIN tenaga_kesehatan n
        ON k.id_nakes = n.id_nakes

    LEFT JOIN profesi p
        ON n.id_profesi = p.id_profesi

    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit

    WHERE k.id_kasus = ?

    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    die("Kasus tidak ditemukan. ID Kasus: " . $id_kasus);
}

/*
|--------------------------------------------------------------------------
| NAMA NAKES
|--------------------------------------------------------------------------
*/

$nama_nakes = "-";

if (!empty($data['nama_lengkap'])) {

    $nama_nakes =
        ($data['gelar_depan']
            ? $data['gelar_depan'] . ' '
            : ''
        )
        .
        $data['nama_lengkap']
        .
        ($data['gelar_belakang']
            ? ', ' . $data['gelar_belakang']
            : ''
        );
}

/*
|--------------------------------------------------------------------------
| STATUS CLASS
|--------------------------------------------------------------------------
*/

$status_class = "status-dilaporkan";

switch ($data['status_kasus']) {

    case 'TELAAH AWAL':
        $status_class = "status-telaah";
        break;

    case 'DALAM PEMERIKSAAN':
        $status_class = "status-pemeriksaan";
        break;

    case 'RAPAT ETIK':
        $status_class = "status-rapat";
        break;

    case 'REKOMENDASI':
        $status_class = "status-rekomendasi";
        break;

    case 'KEPUTUSAN':
        $status_class = "status-keputusan";
        break;

    case 'SELESAI':
        $status_class = "status-selesai";
        break;
}

/*
|--------------------------------------------------------------------------
| RIWAYAT PROSES
|--------------------------------------------------------------------------
*/

$sql_proses = "
    SELECT *
    FROM etik_proses
    WHERE id_kasus = ?
    ORDER BY tanggal_proses ASC, id_proses ASC
";

$stmt = mysqli_prepare($conn, $sql_proses);
mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result_proses = mysqli_stmt_get_result($stmt);

/*
|--------------------------------------------------------------------------
| DOKUMEN
|--------------------------------------------------------------------------
*/

$sql_dokumen = "
    SELECT *
    FROM etik_dokumen
    WHERE id_kasus = ?
    ORDER BY id_dokumen ASC
";

$stmt_dok = mysqli_prepare($conn, $sql_dokumen);
mysqli_stmt_bind_param($stmt_dok, "i", $id_kasus);
mysqli_stmt_execute($stmt_dok);

$result_dokumen = mysqli_stmt_get_result($stmt_dok);

$total_dokumen = 0;
$dokumen_ada = 0;

$dokumen_data = [];

while ($dok = mysqli_fetch_assoc($result_dokumen)) {

    $dokumen_data[] = $dok;

    $total_dokumen++;

    if (
        $dok['status_dokumen'] === 'ADA'
        && !empty($dok['nama_file'])
    ) {
        $dokumen_ada++;
    }
}

$persentase_dokumen = $total_dokumen > 0
    ? round(($dokumen_ada / $total_dokumen) * 100)
    : 0;

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Detail Kasus Etik</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 25px;
    background: #f4f6f9;
    font-family: Arial, Helvetica, sans-serif;
    color: #333;
}

.container {
    max-width: 1250px;
    margin: auto;
}

/* HEADER */

.header {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.header h1 {
    margin: 0 0 8px;
    font-size: 25px;
}

.header p {
    margin: 0;
    color: #666;
}

/* BUTTON */

.toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.btn {
    display: inline-block;
    padding: 10px 15px;
    border-radius: 7px;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: bold;
}

.btn-primary {
    background: #3498db;
    color: #fff;
}

.btn-success {
    background: #27ae60;
    color: #fff;
}

.btn-warning {
    background: #f39c12;
    color: #fff;
}

.btn-info {
    background: #17a2b8;
    color: #fff;
}

.btn-secondary {
    background: #7f8c8d;
    color: #fff;
}

.btn-danger {
    background: #e74c3c;
    color: #fff;
}

.btn-document {
    background: #8e44ad;
    color: #fff;
}

.btn:hover {
    opacity: .85;
}

/* CARD */

.card {
    background: #fff;
    padding: 22px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.card-title {
    font-size: 19px;
    font-weight: bold;
    padding-bottom: 12px;
    margin-bottom: 18px;
    border-bottom: 1px solid #ddd;
}

/* STATUS */

.status-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.badge {
    display: inline-block;
    padding: 8px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.status-dilaporkan {
    background: #eaf2f8;
    color: #2980b9;
}

.status-telaah {
    background: #fef5e7;
    color: #d68910;
}

.status-pemeriksaan {
    background: #fdebd0;
    color: #ca6f1e;
}

.status-rapat {
    background: #e8daef;
    color: #7d3c98;
}

.status-rekomendasi {
    background: #d5f5e3;
    color: #239b56;
}

.status-keputusan {
    background: #d6eaf8;
    color: #21618c;
}

.status-selesai {
    background: #d4efdf;
    color: #196f3d;
}

/* INFO GRID */

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
}

.info-item {
    padding: 13px;
    border-bottom: 1px solid #eee;
}

.info-label {
    font-size: 12px;
    color: #777;
    margin-bottom: 5px;
}

.info-value {
    font-weight: 600;
}

/* TEXT */

.text-box {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    line-height: 1.6;
    white-space: pre-line;
    min-height: 60px;
}

/* PROCESS */

.process-item {
    border-left: 4px solid #3498db;
    padding: 15px 18px;
    margin-bottom: 15px;
    background: #f8f9fa;
    border-radius: 0 8px 8px 0;
}

.process-title {
    font-weight: bold;
    font-size: 16px;
    margin-bottom: 6px;
}

.process-date {
    font-size: 12px;
    color: #777;
    margin-bottom: 10px;
}

.process-label {
    font-size: 12px;
    font-weight: bold;
    color: #666;
    margin-top: 8px;
}

/* DOCUMENT */

.progress-wrapper {
    background: #e9ecef;
    border-radius: 10px;
    height: 18px;
    overflow: hidden;
    margin: 12px 0;
}

.progress {
    height: 100%;
    background: #27ae60;
}

.document-summary {
    display: flex;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
}

.document-count {
    font-size: 24px;
    font-weight: bold;
}

/* TABLE */

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th {
    background: #f1f2f4;
    padding: 11px;
    text-align: left;
    font-size: 13px;
}

td {
    padding: 11px;
    border-bottom: 1px solid #eee;
    font-size: 13px;
}

.ada {
    color: #27ae60;
    font-weight: bold;
}

.tidak-ada {
    color: #e74c3c;
    font-weight: bold;
}

/* RESPONSIVE */

@media (max-width: 750px) {

    body {
        padding: 12px;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

}

</style>

</head>

<body>

<div class="container">

<!-- HEADER -->

<div class="header">

    <h1>⚖️ Detail Kasus Etik</h1>

    <p>
        Sub Komite Etik RSUD Subang
    </p>

</div>


<!-- TOOLBAR -->

<div class="toolbar">

    <a href="index.php" class="btn btn-secondary">
        ↩ Daftar Kasus
    </a>

    <a
        href="edit.php?id=<?= $id_kasus; ?>"
        class="btn btn-primary"
    >
        ✏️ Edit
    </a>

    <a
        href="proses.php?id=<?= $id_kasus; ?>"
        class="btn btn-warning"
    >
        🔄 Proses Etik
    </a>

    <a
        href="dokumen.php?id=<?= $id_kasus; ?>"
        class="btn btn-document"
    >
        📁 Dokumen
    </a>

    <a
        href="cetak.php?id=<?= $id_kasus; ?>"
        target="_blank"
        class="btn btn-info"
    >
        🖨 Cetak
    </a>

    <a
        href="hapus.php?id=<?= $id_kasus; ?>"
        class="btn btn-danger"
        onclick="return confirm(
            'Yakin ingin menghapus kasus <?= h($data['nomor_kasus']); ?>?\\n\\nData kasus, proses dan dokumen akan dihapus.'
        );"
    >
        🗑 Hapus
    </a>

</div>


<!-- STATUS -->

<div class="card">

    <div class="status-box">

        <div>

            <div style="font-size:12px;color:#777;">
                NOMOR KASUS
            </div>

            <div style="font-size:22px;font-weight:bold;">
                <?= h($data['nomor_kasus']); ?>
            </div>

        </div>

        <div>

            <span class="badge <?= $status_class; ?>">
                <?= h($data['status_kasus']); ?>
            </span>

        </div>

    </div>

</div>


<!-- IDENTITAS -->

<div class="card">

    <div class="card-title">
        👤 Identitas Tenaga Kesehatan
    </div>

    <div class="info-grid">

        <div class="info-item">

            <div class="info-label">
                Nama Lengkap
            </div>

            <div class="info-value">
                <?= h($nama_nakes); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Nomor Induk
            </div>

            <div class="info-value">
                <?= h($data['nomor_induk'] ?: '-'); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Profesi
            </div>

            <div class="info-value">
                <?= h($data['nama_profesi'] ?: '-'); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Unit Kerja
            </div>

            <div class="info-value">
                <?= h($data['nama_unit'] ?: '-'); ?>
            </div>

        </div>

    </div>

</div>


<!-- INFORMASI KASUS -->

<div class="card">

    <div class="card-title">
        📋 Informasi Kasus
    </div>

    <div class="info-grid">

        <div class="info-item">

            <div class="info-label">
                Tanggal Laporan
            </div>

            <div class="info-value">
                <?= date(
                    'd-m-Y',
                    strtotime($data['tanggal_laporan'])
                ); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Pelapor
            </div>

            <div class="info-value">
                <?= h($data['pelapor']); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Hubungan Pelapor
            </div>

            <div class="info-value">
                <?= h($data['hubungan_pelapor'] ?: '-'); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Sumber Laporan
            </div>

            <div class="info-value">
                <?= h($data['sumber_laporan'] ?: '-'); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Jenis Masalah
            </div>

            <div class="info-value">
                <?= h($data['jenis_masalah']); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Klasifikasi Etik
            </div>

            <div class="info-value">
                <?= h($data['klasifikasi_etik'] ?: '-'); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Tingkat Kasus
            </div>

            <div class="info-value">
                <?= h($data['tingkat_kasus'] ?: '-'); ?>
            </div>

        </div>


        <div class="info-item">

            <div class="info-label">
                Tanggal Selesai
            </div>

            <div class="info-value">
                <?= !empty($data['tanggal_selesai'])
                    ? date('d-m-Y', strtotime($data['tanggal_selesai']))
                    : '-';
                ?>
            </div>

        </div>

    </div>

</div>


<!-- URAIAN -->

<div class="card">

    <div class="card-title">
        📝 Uraian Kasus
    </div>

    <div class="text-box">

        <?= h($data['uraian_kasus']); ?>

    </div>

</div>


<!-- HASIL TELAAH -->

<div class="card">

    <div class="card-title">
        🔎 Hasil Telaah Awal
    </div>

    <div class="text-box">

        <?= !empty($data['hasil_telaah'])
            ? h($data['hasil_telaah'])
            : '<span style="color:#999;">Belum ada hasil telaah.</span>';
        ?>

    </div>

</div>


<!-- REKOMENDASI -->

<div class="card">

    <div class="card-title">
        📌 Rekomendasi
    </div>

    <div class="text-box">

        <?= !empty($data['rekomendasi'])
            ? h($data['rekomendasi'])
            : '<span style="color:#999;">Belum ada rekomendasi.</span>';
        ?>

    </div>

</div>


<!-- KEPUTUSAN -->

<div class="card">

    <div class="card-title">
        ⚖️ Keputusan
    </div>

    <div class="text-box">

        <?= !empty($data['keputusan'])
            ? h($data['keputusan'])
            : '<span style="color:#999;">Belum ada keputusan.</span>';
        ?>

    </div>

</div>


<!-- CATATAN -->

<?php if (!empty($data['catatan'])): ?>

<div class="card">

    <div class="card-title">
        📌 Catatan
    </div>

    <div class="text-box">

        <?= h($data['catatan']); ?>

    </div>

</div>

<?php endif; ?>


<!-- RIWAYAT PROSES -->

<div class="card">

    <div class="card-title">
        🔄 Riwayat Proses Etik
    </div>

    <?php if (mysqli_num_rows($result_proses) > 0): ?>

        <?php while ($proses = mysqli_fetch_assoc($result_proses)): ?>

            <div class="process-item">

                <div class="process-title">

                    <?= h($proses['jenis_proses']); ?>

                </div>

                <div class="process-date">

                    <?= date(
                        'd-m-Y',
                        strtotime($proses['tanggal_proses'])
                    ); ?>

                    &nbsp; | &nbsp;

                    Status:
                    <strong>
                        <?= h($proses['status_proses']); ?>
                    </strong>

                </div>

                <?php if (!empty($proses['uraian_proses'])): ?>

                    <div class="process-label">
                        Uraian
                    </div>

                    <div>
                        <?= nl2br(h($proses['uraian_proses'])); ?>
                    </div>

                <?php endif; ?>


                <?php if (!empty($proses['pihak_terlibat'])): ?>

                    <div class="process-label">
                        Pihak Terlibat
                    </div>

                    <div>
                        <?= nl2br(h($proses['pihak_terlibat'])); ?>
                    </div>

                <?php endif; ?>


                <?php if (!empty($proses['hasil_proses'])): ?>

                    <div class="process-label">
                        Hasil Proses
                    </div>

                    <div>
                        <?= nl2br(h($proses['hasil_proses'])); ?>
                    </div>

                <?php endif; ?>


                <?php if (!empty($proses['rekomendasi'])): ?>

                    <div class="process-label">
                        Rekomendasi
                    </div>

                    <div>
                        <?= nl2br(h($proses['rekomendasi'])); ?>
                    </div>

                <?php endif; ?>


                <?php if (!empty($proses['petugas'])): ?>

                    <div class="process-label">
                        Petugas
                    </div>

                    <div>
                        <?= h($proses['petugas']); ?>
                    </div>

                <?php endif; ?>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div style="color:#888;padding:15px 0;">
            Belum ada riwayat proses etik.
        </div>

    <?php endif; ?>

    <a
        href="proses.php?id=<?= $id_kasus; ?>"
        class="btn btn-warning"
    >
        ➕ Tambah Proses Etik
    </a>

</div>


<!-- DOKUMEN -->

<div class="card">

    <div class="card-title">
        📁 Dokumen Kasus
    </div>

    <div class="document-summary">

        <div>

            <div class="document-count">
                <?= $dokumen_ada; ?> / <?= $total_dokumen; ?>
            </div>

            <div style="color:#777;">
                Dokumen tersedia
            </div>

        </div>

        <div style="flex:1;min-width:200px;">

            <div>
                Kelengkapan:
                <strong><?= $persentase_dokumen; ?>%</strong>
            </div>

            <div class="progress-wrapper">

                <div
                    class="progress"
                    style="width:<?= $persentase_dokumen; ?>%;"
                ></div>

            </div>

        </div>

    </div>


    <?php if (count($dokumen_data) > 0): ?>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th>No.</th>

                        <th>Jenis Dokumen</th>

                        <th>Status</th>

                        <th>File</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach ($dokumen_data as $index => $dok): ?>

                    <tr>

                        <td>
                            <?= $index + 1; ?>
                        </td>

                        <td>
                            <?= h($dok['jenis_dokumen']); ?>
                        </td>

                        <td>

                            <?php if (
                                $dok['status_dokumen'] === 'ADA'
                                && !empty($dok['nama_file'])
                            ): ?>

                                <span class="ada">
                                    ✓ ADA
                                </span>

                            <?php else: ?>

                                <span class="tidak-ada">
                                    ✕ BELUM ADA
                                </span>

                            <?php endif; ?>

                        </td>

                        <td>

                            <?php if (!empty($dok['nama_file'])): ?>

                                <?= h($dok['nama_file']); ?>

                            <?php else: ?>

                                -

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>


    <br>

    <a
        href="dokumen.php?id=<?= $id_kasus; ?>"
        class="btn btn-document"
    >
        📁 Kelola Dokumen
    </a>

</div>


<!-- FOOTER BUTTON -->

<div class="toolbar">

    <a
        href="index.php"
        class="btn btn-secondary"
    >
        ↩ Kembali ke Daftar
    </a>

    <a
        href="edit.php?id=<?= $id_kasus; ?>"
        class="btn btn-primary"
    >
        ✏️ Edit Kasus
    </a>

    <a
        href="cetak.php?id=<?= $id_kasus; ?>"
        target="_blank"
        class="btn btn-info"
    >
        🖨 Cetak Detail
    </a>

</div>

</div>

</body>

</html>