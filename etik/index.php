<?php
require_once "../config/auth.php";
require_once "../config/database.php";

function h($text)
{
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| Statistik Kasus
|--------------------------------------------------------------------------
*/

$total = 0;
$dilaporkan = 0;
$telaah = 0;
$pemeriksaan = 0;
$rapat = 0;
$rekomendasi = 0;
$keputusan = 0;
$selesai = 0;

$result = mysqli_query(
    $conn,
    "SELECT status_kasus, COUNT(*) AS jumlah
     FROM etik_kasus
     GROUP BY status_kasus"
);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {

        $jumlah = (int)$row['jumlah'];
        $total += $jumlah;

        switch ($row['status_kasus']) {

            case 'DILAPORKAN':
                $dilaporkan = $jumlah;
                break;

            case 'TELAAH AWAL':
                $telaah = $jumlah;
                break;

            case 'DALAM PEMERIKSAAN':
                $pemeriksaan = $jumlah;
                break;

            case 'RAPAT ETIK':
                $rapat = $jumlah;
                break;

            case 'REKOMENDASI':
                $rekomendasi = $jumlah;
                break;

            case 'KEPUTUSAN':
                $keputusan = $jumlah;
                break;

            case 'SELESAI':
                $selesai = $jumlah;
                break;
        }
    }
}

/*
|--------------------------------------------------------------------------
| Ambil Daftar Kasus
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        k.id_kasus,
        k.nomor_kasus,
        k.tanggal_laporan,
        k.pelapor,
        k.jenis_masalah,
        k.klasifikasi_etik,
        k.tingkat_kasus,
        k.status_kasus,

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

    ORDER BY
        k.tanggal_laporan DESC,
        k.id_kasus DESC

    LIMIT 100
";

$result_kasus = mysqli_query($conn, $sql);

if (!$result_kasus) {
    die("Gagal mengambil data kasus: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Sub Komite Etik - RSUD Subang</title>

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
            max-width: 1500px;
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
            font-size: 26px;
        }

        .header p {
            margin: 0;
            color: #666;
        }

        /* TOOLBAR */

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
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
            white-space: nowrap;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-secondary {
            background: #7f8c8d;
            color: white;
        }

        .btn-document {
            background: #8e44ad;
            color: white;
        }

        .btn:hover {
            opacity: .85;
        }

        /* STATISTICS */

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.06);
        }

        .stat-title {
            font-size: 13px;
            color: #777;
            margin-bottom: 8px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: bold;
        }

        /* TABLE */

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        .card-title {
            font-size: 19px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1300px;
        }

        th {
            background: #f0f2f5;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            font-size: 13px;
        }

        tr:hover {
            background: #fafafa;
        }

        .nomor {
            font-weight: bold;
            color: #2c3e50;
        }

        .nama {
            font-weight: bold;
        }

        .small {
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        /* STATUS */

        .badge {
            display: inline-block;
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
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

        /* ACTION */

        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            min-width: 350px;
        }

        .action-buttons .btn {
            padding: 8px 10px;
            font-size: 12px;
        }

        /* ALERT */

        .alert {
            padding: 15px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        /* RESPONSIVE */

        @media (max-width: 900px) {

            body {
                padding: 15px;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

        }

        @media (max-width: 600px) {

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- HEADER -->

    <div class="header">

        <h1>⚖️ Sub Komite Etik</h1>

        <p>
            Sistem Informasi Tenaga Kesehatan Lainnya RSUD Subang
        </p>

    </div>


    <!-- ALERT -->

    <?php if (isset($_GET['hapus']) && $_GET['hapus'] === 'berhasil'): ?>

        <div class="alert alert-success">
            ✅ Kasus etik berhasil dihapus.
        </div>

    <?php endif; ?>


    <!-- TOOLBAR -->

    <div class="toolbar">

        <a
            href="../dashboard/index.php"
            class="btn btn-secondary"
        >
            🏠 Dashboard
        </a>

        <a
            href="tambah.php"
            class="btn btn-success"
        >
            ➕ Registrasi Kasus
        </a>

    </div>


    <!-- STATISTIK -->

    <div class="stats">

        <div class="stat-card">
            <div class="stat-title">Total Kasus</div>
            <div class="stat-number">
                <?= $total; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Dilaporkan</div>
            <div class="stat-number">
                <?= $dilaporkan; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Dalam Pemeriksaan</div>
            <div class="stat-number">
                <?= $pemeriksaan; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Selesai</div>
            <div class="stat-number">
                <?= $selesai; ?>
            </div>
        </div>

    </div>


    <!-- DAFTAR KASUS -->

    <div class="card">

        <div class="card-title">
            📋 Daftar Kasus Etik
        </div>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>

                        <th width="130">Nomor Kasus</th>

                        <th width="100">Tanggal</th>

                        <th width="230">Tenaga Kesehatan</th>

                        <th width="170">Jenis Masalah</th>

                        <th width="140">Tingkat</th>

                        <th width="170">Status</th>

                        <th width="380">Aksi</th>

                    </tr>

                </thead>

                <tbody>

                <?php if (mysqli_num_rows($result_kasus) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($result_kasus)): ?>

                        <?php

                        $id_kasus = (int)$row['id_kasus'];

                        $nama_nakes = '-';

                        if (!empty($row['nama_lengkap'])) {

                            $nama_nakes =
                                ($row['gelar_depan']
                                    ? $row['gelar_depan'] . ' '
                                    : ''
                                ) .
                                $row['nama_lengkap'] .
                                ($row['gelar_belakang']
                                    ? ', ' . $row['gelar_belakang']
                                    : ''
                                );
                        }

                        $status_class = 'status-dilaporkan';

                        switch ($row['status_kasus']) {

                            case 'TELAAH AWAL':
                                $status_class = 'status-telaah';
                                break;

                            case 'DALAM PEMERIKSAAN':
                                $status_class = 'status-pemeriksaan';
                                break;

                            case 'RAPAT ETIK':
                                $status_class = 'status-rapat';
                                break;

                            case 'REKOMENDASI':
                                $status_class = 'status-rekomendasi';
                                break;

                            case 'KEPUTUSAN':
                                $status_class = 'status-keputusan';
                                break;

                            case 'SELESAI':
                                $status_class = 'status-selesai';
                                break;
                        }

                        ?>

                        <tr>

                            <!-- NOMOR -->

                            <td>

                                <div class="nomor">
                                    <?= h($row['nomor_kasus']); ?>
                                </div>

                            </td>


                            <!-- TANGGAL -->

                            <td>

                                <?= date(
                                    'd-m-Y',
                                    strtotime($row['tanggal_laporan'])
                                ); ?>

                            </td>


                            <!-- NAKES -->

                            <td>

                                <div class="nama">
                                    <?= h($nama_nakes); ?>
                                </div>

                                <?php if (!empty($row['nomor_induk'])): ?>

                                    <div class="small">
                                        No. Induk:
                                        <?= h($row['nomor_induk']); ?>
                                    </div>

                                <?php endif; ?>

                                <?php if (!empty($row['nama_profesi'])): ?>

                                    <div class="small">
                                        <?= h($row['nama_profesi']); ?>
                                    </div>

                                <?php endif; ?>

                                <?php if (!empty($row['nama_unit'])): ?>

                                    <div class="small">
                                        <?= h($row['nama_unit']); ?>
                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- MASALAH -->

                            <td>

                                <?= h($row['jenis_masalah']); ?>

                                <?php if (!empty($row['klasifikasi_etik'])): ?>

                                    <div class="small">
                                        <?= h($row['klasifikasi_etik']); ?>
                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- TINGKAT -->

                            <td>

                                <?php if (!empty($row['tingkat_kasus'])): ?>

                                    <?= h($row['tingkat_kasus']); ?>

                                <?php else: ?>

                                    <span class="small">Belum ditentukan</span>

                                <?php endif; ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <span class="badge <?= $status_class; ?>">

                                    <?= h($row['status_kasus']); ?>

                                </span>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <div class="action-buttons">

                                    <a
                                        href="detail.php?id=<?= $id_kasus; ?>"
                                        class="btn btn-info"
                                    >
                                        📄 Detail
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
                                        🔄 Proses
                                    </a>


                                    <a
                                        href="dokumen.php?id=<?= $id_kasus; ?>"
                                        class="btn btn-document"
                                    >
                                        📁 Dokumen
                                    </a>


                                    <!-- TOMBOL HAPUS -->

                                    <a
                                        href="hapus.php?id=<?= $id_kasus; ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm(
                                            'Yakin ingin menghapus kasus <?= h($row['nomor_kasus']); ?>?\\n\\n' +
                                            'Data kasus, riwayat proses, dan dokumen terkait akan dihapus.\\n\\n' +
                                            'Data yang sudah dihapus tidak dapat dikembalikan.'
                                        );"
                                    >
                                        🗑 Hapus
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="7" style="text-align:center; padding:40px;">

                            Belum ada kasus etik.

                            <br><br>

                            <a
                                href="tambah.php"
                                class="btn btn-success"
                            >
                                ➕ Registrasi Kasus Pertama
                            </a>

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>

</html>