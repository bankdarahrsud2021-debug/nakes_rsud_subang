<?php
require_once "../config/auth.php";
require_once "../config/database.php";

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

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
   DOKUMEN
========================= */
$stmt_dokumen = mysqli_prepare($conn, "
    SELECT *
    FROM etik_dokumen
    WHERE id_kasus = ?
    ORDER BY id_dokumen ASC
");

mysqli_stmt_bind_param($stmt_dokumen, "i", $id_kasus);
mysqli_stmt_execute($stmt_dokumen);

$result_dokumen = mysqli_stmt_get_result($stmt_dokumen);

$dokumen = [];

while ($row = mysqli_fetch_assoc($result_dokumen)) {
    $dokumen[] = $row;
}

/* =========================
   FORMAT DATA
========================= */
function h($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function formatTanggal($tanggal)
{
    if (!$tanggal || $tanggal == '0000-00-00') {
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

    if (count($pecah) != 3) {
        return $tanggal;
    }

    return (int)$pecah[2] . ' ' .
           $bulan[(int)$pecah[1]] . ' ' .
           $pecah[0];
}

$nama_nakes = '-';

if (!empty($kasus['nama_lengkap'])) {

    $nama_nakes = trim(
        ($kasus['gelar_depan'] ? $kasus['gelar_depan'] . ' ' : '') .
        $kasus['nama_lengkap'] .
        ($kasus['gelar_belakang'] ? ', ' . $kasus['gelar_belakang'] : '')
    );
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<title>
    Cetak Kasus Etik - <?= h($kasus['nomor_kasus']); ?>
</title>

<style>

@page {
    size: A4;
    margin: 18mm 18mm 18mm 18mm;
}

* {
    box-sizing: border-box;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11pt;
    color: #000;
    background: #fff;
    margin: 0;
    padding: 0;
}

.container {
    width: 100%;
}

.header {
    text-align: center;
    border-bottom: 3px solid #000;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.header h2 {
    margin: 0;
    font-size: 16pt;
    text-transform: uppercase;
}

.header h3 {
    margin: 5px 0 0 0;
    font-size: 13pt;
}

.header p {
    margin: 4px 0;
    font-size: 10pt;
}

.judul {
    text-align: center;
    font-size: 14pt;
    font-weight: bold;
    margin: 15px 0 20px 0;
    text-transform: uppercase;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 15px;
}

table th,
table td {
    border: 1px solid #000;
    padding: 6px 7px;
    vertical-align: top;
}

table th {
    background: #f0f0f0;
    text-align: left;
}

.label {
    width: 25%;
    font-weight: bold;
}

.section-title {
    font-weight: bold;
    font-size: 12pt;
    margin-top: 18px;
    margin-bottom: 7px;
    border-bottom: 1px solid #000;
    padding-bottom: 4px;
}

.text-box {
    border: 1px solid #000;
    min-height: 70px;
    padding: 8px;
    white-space: pre-line;
    line-height: 1.5;
}

.status {
    font-weight: bold;
}

.process-table th,
.process-table td {
    font-size: 9.5pt;
}

.document-table th,
.document-table td {
    font-size: 9.5pt;
}

.footer {
    margin-top: 35px;
}

.ttd {
    width: 45%;
    margin-left: auto;
    text-align: center;
}

.spacer {
    height: 65px;
}

.no-print {
    text-align: center;
    margin-bottom: 20px;
}

.btn {
    display: inline-block;
    padding: 8px 14px;
    margin: 3px;
    border: 1px solid #555;
    background: #eee;
    color: #000;
    text-decoration: none;
    border-radius: 4px;
    cursor: pointer;
}

@media print {

    .no-print {
        display: none !important;
    }

    body {
        background: #fff;
    }

    .container {
        width: 100%;
    }

    table {
        page-break-inside: avoid;
    }

    .section-title {
        page-break-after: avoid;
    }
}

</style>

</head>

<body>

<div class="no-print">

    <button class="btn" onclick="window.print()">
        🖨 Cetak
    </button>

    <a class="btn"
       href="detail.php?id=<?= $id_kasus; ?>">
        Kembali
    </a>

</div>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <h2>
            RSUD SUBANG
        </h2>

        <h3>
            SUB KOMITE ETIK
        </h3>

        <p>
            DOKUMEN KASUS ETIK TENAGA KESEHATAN
        </p>

    </div>


    <div class="judul">

        LAPORAN KASUS ETIK

    </div>


    <!-- IDENTITAS KASUS -->

    <div class="section-title">
        A. IDENTITAS KASUS
    </div>

    <table>

        <tr>

            <td class="label">
                Nomor Kasus
            </td>

            <td>
                <?= h($kasus['nomor_kasus']); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Tanggal Laporan
            </td>

            <td>
                <?= formatTanggal($kasus['tanggal_laporan']); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Tenaga Kesehatan
            </td>

            <td>
                <?= h($nama_nakes); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Nomor Induk
            </td>

            <td>
                <?= h($kasus['nomor_induk'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                NIP
            </td>

            <td>
                <?= h($kasus['nip'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Profesi
            </td>

            <td>
                <?= h($kasus['nama_profesi'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Unit Kerja
            </td>

            <td>
                <?= h($kasus['nama_unit'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Pelapor
            </td>

            <td>
                <?= h($kasus['pelapor']); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Hubungan Pelapor
            </td>

            <td>
                <?= h($kasus['hubungan_pelapor'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Sumber Laporan
            </td>

            <td>
                <?= h($kasus['sumber_laporan'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Tingkat Kasus
            </td>

            <td>
                <?= h($kasus['tingkat_kasus'] ?: '-'); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Status Kasus
            </td>

            <td class="status">
                <?= h($kasus['status_kasus']); ?>
            </td>

        </tr>

    </table>


    <!-- MASALAH ETIK -->

    <div class="section-title">
        B. MASALAH ETIK
    </div>

    <table>

        <tr>

            <td class="label">
                Jenis Masalah
            </td>

            <td>
                <?= h($kasus['jenis_masalah']); ?>
            </td>

        </tr>

        <tr>

            <td class="label">
                Klasifikasi Etik
            </td>

            <td>
                <?= h($kasus['klasifikasi_etik'] ?: '-'); ?>
            </td>

        </tr>

    </table>


    <div class="section-title">
        C. URAIAN KASUS
    </div>

    <div class="text-box">

        <?= nl2br(h($kasus['uraian_kasus'])); ?>

    </div>


    <div class="section-title">
        D. HASIL TELAAH
    </div>

    <div class="text-box">

        <?= nl2br(h($kasus['hasil_telaah'] ?: '-')); ?>

    </div>


    <div class="section-title">
        E. REKOMENDASI
    </div>

    <div class="text-box">

        <?= nl2br(h($kasus['rekomendasi'] ?: '-')); ?>

    </div>


    <div class="section-title">
        F. KEPUTUSAN
    </div>

    <div class="text-box">

        <?= nl2br(h($kasus['keputusan'] ?: '-')); ?>

    </div>


    <div class="section-title">
        G. RIWAYAT PROSES ETIK
    </div>

    <?php if (count($proses) > 0): ?>

        <table class="process-table">

            <thead>

                <tr>

                    <th width="4%">
                        No
                    </th>

                    <th width="12%">
                        Tanggal
                    </th>

                    <th width="17%">
                        Proses
                    </th>

                    <th width="22%">
                        Uraian
                    </th>

                    <th width="18%">
                        Pihak Terlibat
                    </th>

                    <th width="17%">
                        Hasil
                    </th>

                    <th width="10%">
                        Status
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php $no = 1; ?>

                <?php foreach ($proses as $item): ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>
                            <?= formatTanggal($item['tanggal_proses']); ?>
                        </td>

                        <td>
                            <?= h($item['jenis_proses']); ?>
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

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="text-box">
            Belum terdapat riwayat proses etik.
        </div>

    <?php endif; ?>


    <div class="section-title">
        H. DOKUMEN PENDUKUNG
    </div>

    <?php if (count($dokumen) > 0): ?>

        <table class="document-table">

            <thead>

                <tr>

                    <th width="7%">
                        No
                    </th>

                    <th>
                        Jenis Dokumen
                    </th>

                    <th width="20%">
                        Status
                    </th>

                    <th>
                        Nama File
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php $no = 1; ?>

                <?php foreach ($dokumen as $item): ?>

                    <tr>

                        <td>
                            <?= $no++; ?>
                        </td>

                        <td>
                            <?= h($item['jenis_dokumen']); ?>
                        </td>

                        <td>
                            <?= h($item['status_dokumen']); ?>
                        </td>

                        <td>
                            <?= h($item['nama_file'] ?: '-'); ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="text-box">
            Belum terdapat dokumen pendukung.
        </div>

    <?php endif; ?>


    <div class="section-title">
        I. CATATAN
    </div>

    <div class="text-box">

        <?= nl2br(h($kasus['catatan'] ?: '-')); ?>

    </div>


    <?php if (!empty($kasus['tanggal_selesai'])): ?>

        <div class="section-title">
            J. PENYELESAIAN
        </div>

        <table>

            <tr>

                <td class="label">
                    Tanggal Selesai
                </td>

                <td>
                    <?= formatTanggal($kasus['tanggal_selesai']); ?>
                </td>

            </tr>

        </table>

    <?php endif; ?>


    <!-- TANDA TANGAN -->

    <div class="footer">

        <div class="ttd">

            <p>
                Subang, <?= formatTanggal(date('Y-m-d')); ?>
            </p>

            <p>
                Ketua Sub Komite Etik
            </p>

            <div class="spacer"></div>

            <p>
                <strong>
                    ______________________________
                </strong>
            </p>

            <p>
                Nama / NIP
            </p>

        </div>

    </div>

</div>

</body>

</html>