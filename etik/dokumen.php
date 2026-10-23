<?php
require_once "../config/auth.php";
require_once "../config/database.php";

$id_kasus = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_kasus <= 0) {
    die("ID kasus tidak valid.");
}

/*
|--------------------------------------------------------------------------
| Ambil data kasus
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        k.*,
        n.nama_lengkap,
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
    die("Query kasus gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt, "i", $id_kasus);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$kasus = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$kasus) {
    die("Kasus tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Checklist dokumen standar
|--------------------------------------------------------------------------
*/
$checklist = [
    "Surat/Laporan Pengaduan",
    "Bukti Pendukung",
    "Surat Pemanggilan",
    "Berita Acara Klarifikasi",
    "Berita Acara Pemeriksaan",
    "Hasil Kajian",
    "Rekomendasi Sub Komite Etik",
    "Keputusan"
];


/*
|--------------------------------------------------------------------------
| Pastikan semua checklist tersedia
|--------------------------------------------------------------------------
*/
foreach ($checklist as $jenis) {

    $cek = "
        SELECT id_dokumen
        FROM etik_dokumen
        WHERE id_kasus = ?
        AND jenis_dokumen = ?
        LIMIT 1
    ";

    $stmt_cek = mysqli_prepare($conn, $cek);

    if (!$stmt_cek) {
        die("Query checklist gagal: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt_cek,
        "is",
        $id_kasus,
        $jenis
    );

    mysqli_stmt_execute($stmt_cek);

    $hasil_cek = mysqli_stmt_get_result($stmt_cek);
    $ada = mysqli_fetch_assoc($hasil_cek);

    mysqli_stmt_close($stmt_cek);


    if (!$ada) {

        $insert = "
            INSERT INTO etik_dokumen
            (
                id_kasus,
                jenis_dokumen,
                status_dokumen
            )
            VALUES
            (
                ?,
                ?,
                'TIDAK ADA'
            )
        ";

        $stmt_insert = mysqli_prepare($conn, $insert);

        if (!$stmt_insert) {
            die("Insert checklist gagal: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt_insert,
            "is",
            $id_kasus,
            $jenis
        );

        mysqli_stmt_execute($stmt_insert);

        mysqli_stmt_close($stmt_insert);
    }
}


/*
|--------------------------------------------------------------------------
| Ambil dokumen
|--------------------------------------------------------------------------
*/
$sql_dokumen = "
    SELECT *
    FROM etik_dokumen
    WHERE id_kasus = ?
    ORDER BY id_dokumen ASC
";

$stmt_dokumen = mysqli_prepare($conn, $sql_dokumen);

if (!$stmt_dokumen) {
    die("Query dokumen gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt_dokumen,
    "i",
    $id_kasus
);

mysqli_stmt_execute($stmt_dokumen);

$result_dokumen = mysqli_stmt_get_result($stmt_dokumen);

$dokumen_list = [];

while ($row = mysqli_fetch_assoc($result_dokumen)) {
    $dokumen_list[] = $row;
}

mysqli_stmt_close($stmt_dokumen);


/*
|--------------------------------------------------------------------------
| Statistik dokumen
|--------------------------------------------------------------------------
*/
$total = count($dokumen_list);
$ada = 0;

foreach ($dokumen_list as $dokumen) {

    if (
        $dokumen['status_dokumen'] === 'ADA' &&
        !empty($dokumen['nama_file'])
    ) {
        $ada++;
    }
}

$persentase = $total > 0
    ? round(($ada / $total) * 100)
    : 0;


/*
|--------------------------------------------------------------------------
| Fungsi escape
|--------------------------------------------------------------------------
*/
function e($text)
{
    return htmlspecialchars(
        $text ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Dokumen Kasus Etik - RSUD Subang</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f6f9;
    color: #333;
}

.container {
    max-width: 1200px;
    margin: 30px auto;
    padding: 0 20px;
}

.header,
.card,
.stat {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

.header {
    padding: 22px 25px;
    margin-bottom: 20px;
}

.header h1 {
    margin: 0 0 6px;
    font-size: 25px;
}

.header p {
    margin: 0;
    color: #666;
}

.toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.btn {
    display: inline-block;
    padding: 9px 15px;
    border-radius: 6px;
    border: none;
    text-decoration: none;
    color: white;
    font-size: 14px;
    cursor: pointer;
}

.btn-back {
    background: #6c757d;
}

.btn-upload {
    background: #007bff;
}

.btn-process {
    background: #17a2b8;
}

.btn-print {
    background: #343a40;
}

.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.stat {
    padding: 18px;
}

.stat-title {
    font-size: 12px;
    color: #777;
    margin-bottom: 7px;
}

.stat-value {
    font-size: 25px;
    font-weight: bold;
}

.progress {
    height: 12px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-bar {
    height: 100%;
    background: #28a745;
    width: <?= $persentase; ?>%;
}

.card {
    overflow: hidden;
    margin-bottom: 20px;
}

.card-header {
    padding: 16px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    font-weight: bold;
    font-size: 17px;
}

.card-body {
    padding: 20px;
}

.info {
    background: #eef6ff;
    border-left: 4px solid #007bff;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th,
td {
    padding: 12px 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
    vertical-align: middle;
}

th {
    background: #f8f9fa;
    font-size: 13px;
}

td {
    font-size: 14px;
}

.badge {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: bold;
}

.badge-ada {
    background: #d1fae5;
    color: #047857;
}

.badge-tidak {
    background: #fee2e2;
    color: #b91c1c;
}

.file-name {
    font-size: 13px;
    word-break: break-all;
}

@media(max-width:700px) {

    .stats {
        grid-template-columns: 1fr;
    }

    table {
        min-width: 850px;
    }

}

</style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <h1>📁 Dokumen Kasus Etik</h1>

        <p>
            Pengelolaan dokumen Sub Komite Etik RSUD Subang
        </p>

        <div class="toolbar">

            <a href="detail.php?id=<?= $id_kasus; ?>"
               class="btn btn-back">
                ← Detail Kasus
            </a>

            <a href="proses.php?id=<?= $id_kasus; ?>"
               class="btn btn-process">
                ⚖️ Proses Etik
            </a>

            <a href="cetak.php?id=<?= $id_kasus; ?>"
               target="_blank"
               class="btn btn-print">
                🖨 Cetak
            </a>

        </div>

    </div>


    <!-- INFORMASI KASUS -->

    <div class="card">

        <div class="card-header">
            📋 Informasi Kasus
        </div>

        <div class="card-body">

            <div class="info">

                <strong>
                    Nomor Kasus:
                </strong>

                <?= e($kasus['nomor_kasus']); ?>

                <br>

                <strong>
                    Tenaga Kesehatan:
                </strong>

                <?= e($kasus['nama_lengkap'] ?: '-'); ?>

                <br>

                <strong>
                    Profesi / Unit:
                </strong>

                <?= e($kasus['nama_profesi'] ?: '-'); ?>
                /
                <?= e($kasus['nama_unit'] ?: '-'); ?>

                <br>

                <strong>
                    Status Kasus:
                </strong>

                <?= e($kasus['status_kasus']); ?>

            </div>

        </div>

    </div>


    <!-- STATISTIK -->

    <div class="stats">

        <div class="stat">

            <div class="stat-title">
                TOTAL DOKUMEN
            </div>

            <div class="stat-value">
                <?= $total; ?>
            </div>

        </div>


        <div class="stat">

            <div class="stat-title">
                DOKUMEN TERSEDIA
            </div>

            <div class="stat-value">
                <?= $ada; ?>
            </div>

        </div>


        <div class="stat">

            <div class="stat-title">
                KELENGKAPAN
            </div>

            <div class="stat-value">
                <?= $persentase; ?>%
            </div>

            <div class="progress">

                <div class="progress-bar"></div>

            </div>

        </div>

    </div>


    <!-- TABEL DOKUMEN -->

    <div class="card">

        <div class="card-header">
            📑 Checklist Dokumen
        </div>

        <div class="card-body">

            <div style="overflow-x:auto;">

                <table>

                    <thead>

                        <tr>

                            <th width="50">
                                No
                            </th>

                            <th>
                                Jenis Dokumen
                            </th>

                            <th width="130">
                                Status
                            </th>

                            <th>
                                Nama File
                            </th>

                            <th>
                                Keterangan
                            </th>

                            <th width="150">
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($dokumen_list as $no => $dokumen): ?>

                        <tr>

                            <td>
                                <?= $no + 1; ?>
                            </td>

                            <td>
                                <strong>
                                    <?= e($dokumen['jenis_dokumen']); ?>
                                </strong>
                            </td>

                            <td>

                                <?php if (
                                    $dokumen['status_dokumen'] === 'ADA'
                                    &&
                                    !empty($dokumen['nama_file'])
                                ): ?>

                                    <span class="badge badge-ada">
                                        ADA
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-tidak">
                                        TIDAK ADA
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="file-name">

                                <?php if (!empty($dokumen['nama_file'])): ?>

                                    <?= e($dokumen['nama_file']); ?>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= e(
                                    $dokumen['keterangan'] ?: '-'
                                ); ?>
                            </td>

                            <td>

                                <a href="simpan_dokumen.php?id=<?= (int)$dokumen['id_dokumen']; ?>"
                                   class="btn btn-upload">
                                    📤 Upload
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>

</html>