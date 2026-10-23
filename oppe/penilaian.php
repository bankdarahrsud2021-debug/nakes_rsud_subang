<?php

require_once "../config/auth.php";
require_once "../config/database.php";

/* =========================================================
   FILTER PERIODE
   ========================================================= */

$id_periode = isset($_GET['id_periode']) ? (int)$_GET['id_periode'] : 0;


/* =========================================================
   AMBIL DATA PERIODE
   ========================================================= */

$periode = null;

if ($id_periode > 0) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT *
         FROM oppe_periode
         WHERE id_periode = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_periode);
    mysqli_stmt_execute($stmt);

    $result_periode = mysqli_stmt_get_result($stmt);
    $periode = mysqli_fetch_assoc($result_periode);

    mysqli_stmt_close($stmt);
}


/* =========================================================
   QUERY DATA PENILAIAN
   ========================================================= */

$sql = "
    SELECT
        p.id_penilaian,
        p.id_nakes,
        p.id_periode,
        p.tanggal_penilaian,
        p.penilai,
        p.total_nilai,
        p.nilai_akhir,
        p.kategori,
        p.catatan,
        p.tindak_lanjut,
        p.status,

        n.nomor_induk,
        n.nama_lengkap,
        n.gelar_depan,
        n.gelar_belakang,

        pr.nama_profesi,
        u.nama_unit,

        op.nama_periode,
        op.tanggal_mulai,
        op.tanggal_selesai

    FROM oppe_penilaian p

    INNER JOIN tenaga_kesehatan n
        ON p.id_nakes = n.id_nakes

    LEFT JOIN profesi pr
        ON n.id_profesi = pr.id_profesi

    LEFT JOIN unit_kerja u
        ON n.id_unit = u.id_unit

    INNER JOIN oppe_periode op
        ON p.id_periode = op.id_periode
";


if ($id_periode > 0) {

    $sql .= " WHERE p.id_periode = " . $id_periode;

}

$sql .= " ORDER BY p.id_penilaian DESC";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}


/* =========================================================
   FUNGSI STATUS
   ========================================================= */

function badgeStatus($status)
{
    switch ($status) {

        case 'SELESAI':
            return '<span class="badge selesai">SELESAI</span>';

        case 'DINILAI':
            return '<span class="badge dinilai">DINILAI</span>';

        default:
            return '<span class="badge draft">DRAFT</span>';
    }
}


/* =========================================================
   FUNGSI KATEGORI
   ========================================================= */

function badgeKategori($kategori)
{
    if (!$kategori) {
        return '-';
    }

    switch ($kategori) {

        case 'Sangat Baik':
            return '<span class="kategori sangat-baik">Sangat Baik</span>';

        case 'Baik':
            return '<span class="kategori baik">Baik</span>';

        case 'Cukup':
            return '<span class="kategori cukup">Cukup</span>';

        case 'Perlu Perbaikan':
            return '<span class="kategori perlu">Perlu Perbaikan</span>';

        default:
            return htmlspecialchars($kategori);
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Penilaian OPPE - RSUD Subang</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        .header {
            background: white;
            padding: 22px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .header h2 {
            margin: 0 0 8px 0;
            font-size: 24px;
        }

        .header p {
            margin: 4px 0;
            color: #666;
        }

        .toolbar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 18px;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            border-radius: 6px;
            text-decoration: none;
            color: white;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-add {
            background: #198754;
        }

        .btn-primary {
            background: #0d6efd;
        }

        .btn-info {
            background: #0dcaf0;
            color: #000;
        }

        .btn-print {
            background: #6f42c1;
        }

        .btn:hover {
            opacity: 0.85;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #ddd;
            font-weight: bold;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1100px;
        }

        th,
        td {
            padding: 11px 10px;
            border-bottom: 1px solid #e5e5e5;
            vertical-align: middle;
        }

        th {
            background: #f1f3f5;
            text-align: left;
            font-size: 13px;
            white-space: nowrap;
        }

        td {
            font-size: 13px;
        }

        tr:hover td {
            background: #fafafa;
        }

        .nama {
            font-weight: bold;
        }

        .sub {
            color: #777;
            font-size: 12px;
            margin-top: 3px;
        }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }

        .draft {
            background: #fff3cd;
            color: #856404;
        }

        .dinilai {
            background: #cfe2ff;
            color: #084298;
        }

        .selesai {
            background: #d1e7dd;
            color: #0f5132;
        }

        .kategori {
            font-weight: bold;
        }

        .sangat-baik {
            color: #198754;
        }

        .baik {
            color: #0d6efd;
        }

        .cukup {
            color: #fd7e14;
        }

        .perlu {
            color: #dc3545;
        }

        .aksi {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .aksi .btn {
            padding: 7px 9px;
            font-size: 12px;
        }

        .empty {
            padding: 40px;
            text-align: center;
            color: #777;
        }

        .periode-info {
            background: #e7f1ff;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 12px 15px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .nilai {
            font-weight: bold;
            font-size: 16px;
        }

        @media(max-width:768px) {

            body {
                padding: 10px;
            }

            .header h2 {
                font-size: 20px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <!-- =====================================================
         HEADER
         ===================================================== -->

    <div class="header">

        <h2>📊 Penilaian OPPE</h2>

        <p>
            <strong>Ongoing Professional Practice Evaluation</strong>
        </p>

        <?php if ($periode): ?>

            <div class="periode-info">

                <strong>
                    Periode:
                    <?= htmlspecialchars($periode['nama_periode']); ?>
                </strong>

                <br>

                <?= date('d-m-Y', strtotime($periode['tanggal_mulai'])); ?>
                s/d
                <?= date('d-m-Y', strtotime($periode['tanggal_selesai'])); ?>

                <br>

                Status:
                <strong>
                    <?= htmlspecialchars($periode['status']); ?>
                </strong>

            </div>

        <?php endif; ?>


        <div class="toolbar">

            <a href="index.php" class="btn btn-back">
                ← Kembali
            </a>


            <?php if ($id_periode > 0): ?>

                <a
                    href="penilaian_tambah.php?id_periode=<?= $id_periode; ?>"
                    class="btn btn-add"
                >
                    + Tambah Penilaian
                </a>

            <?php endif; ?>

        </div>

    </div>


    <!-- =====================================================
         DATA PENILAIAN
         ===================================================== -->

    <div class="card">

        <div class="card-header">

            Daftar Hasil Penilaian OPPE

        </div>


        <div class="table-wrapper">

            <?php if (mysqli_num_rows($result) > 0): ?>

                <table>

                    <thead>

                        <tr>

                            <th width="50">No</th>

                            <th>Tenaga Kesehatan</th>

                            <th>Profesi</th>

                            <th>Unit Kerja</th>

                            <th>Tanggal</th>

                            <th>Penilai</th>

                            <th>Nilai</th>

                            <th>Kategori</th>

                            <th>Status</th>

                            <th width="230">Aksi</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $no = 1;

                    while ($row = mysqli_fetch_assoc($result)):

                        $id_penilaian = (int)$row['id_penilaian'];

                        $id_nakes = (int)$row['id_nakes'];

                    ?>

                        <tr>

                            <!-- NO -->

                            <td>
                                <?= $no++; ?>
                            </td>


                            <!-- TENAGA KESEHATAN -->

                            <td>

                                <div class="nama">

                                    <?= htmlspecialchars(
                                        trim(
                                            $row['gelar_depan'] . ' ' .
                                            $row['nama_lengkap'] . ' ' .
                                            $row['gelar_belakang']
                                        )
                                    ); ?>

                                </div>

                                <?php if (!empty($row['nomor_induk'])): ?>

                                    <div class="sub">

                                        NIK/NIP/No. Induk:
                                        <?= htmlspecialchars($row['nomor_induk']); ?>

                                    </div>

                                <?php endif; ?>

                            </td>


                            <!-- PROFESI -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['nama_profesi'] ?? '-'
                                ); ?>

                            </td>


                            <!-- UNIT -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['nama_unit'] ?? '-'
                                ); ?>

                            </td>


                            <!-- TANGGAL -->

                            <td>

                                <?= !empty($row['tanggal_penilaian'])
                                    ? date(
                                        'd-m-Y',
                                        strtotime($row['tanggal_penilaian'])
                                    )
                                    : '-';
                                ?>

                            </td>


                            <!-- PENILAI -->

                            <td>

                                <?= htmlspecialchars(
                                    $row['penilai'] ?? '-'
                                ); ?>

                            </td>


                            <!-- NILAI -->

                            <td>

                                <?php if ($row['nilai_akhir'] !== null): ?>

                                    <span class="nilai">

                                        <?= number_format(
                                            (float)$row['nilai_akhir'],
                                            2
                                        ); ?>

                                    </span>

                                <?php else: ?>

                                    -

                                <?php endif; ?>

                            </td>


                            <!-- KATEGORI -->

                            <td>

                                <?= badgeKategori(
                                    $row['kategori']
                                ); ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?= badgeStatus(
                                    $row['status']
                                ); ?>

                            </td>


                            <!-- AKSI -->

                            <td>

                                <div class="aksi">

                                    <!-- PENILAIAN -->

                                    <a
                                        href="penilaian_form.php?id=<?= $id_penilaian; ?>"
                                        class="btn btn-primary"
                                        title="Buka Form Penilaian"
                                    >
                                        📝 Penilaian
                                    </a>


                                    <!-- DETAIL -->

                                    <a
                                        href="penilaian_detail.php?id=<?= $id_penilaian; ?>"
                                        class="btn btn-info"
                                        title="Lihat Detail OPPE"
                                    >
                                        📊 Detail
                                    </a>


                                    <!-- CETAK -->

                                    <?php if (
                                        $row['status'] === 'DINILAI' ||
                                        $row['status'] === 'SELESAI'
                                    ): ?>

                                        <a
                                            href="cetak.php?id=<?= $id_penilaian; ?>"
                                            target="_blank"
                                            class="btn btn-print"
                                            title="Cetak Lembar OPPE"
                                        >
                                            🖨 Cetak
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else: ?>

                <div class="empty">

                    <h3>Belum ada penilaian OPPE</h3>

                    <p>
                        Belum terdapat data penilaian pada periode ini.
                    </p>

                    <?php if ($id_periode > 0): ?>

                        <a
                            href="penilaian_tambah.php?id_periode=<?= $id_periode; ?>"
                            class="btn btn-add"
                        >
                            + Tambah Penilaian
                        </a>

                    <?php endif; ?>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

</body>

</html>