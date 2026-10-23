<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID kredensial tidak valid.");
}

$id_kredensial = (int) $_GET['id'];


/*
|--------------------------------------------------------------------------
| Ambil data kredensial + identitas Nakes
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        k.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        t.nik,
        t.nip,
        t.nomor_induk,
        t.jenis_kelamin,
        t.tempat_lahir,
        t.tanggal_lahir,
        p.nama_profesi,
        u.nama_unit
    FROM kredensial k
    INNER JOIN tenaga_kesehatan t
        ON k.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    WHERE k.id_kredensial = ?
";

$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    die("Query gagal: " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_kredensial
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data kredensial tidak ditemukan.");
}


/*
|--------------------------------------------------------------------------
| Pastikan status DISETUJUI
|--------------------------------------------------------------------------
*/

$status_kredensial = strtoupper(
    trim($data['status'] ?? '')
);

if ($status_kredensial !== 'DISETUJUI') {

    die("
        <h3 style='font-family:Arial;'>
            Lembar kredensial belum dapat dicetak.
        </h3>

        <p style='font-family:Arial;'>
            Status kredensial saat ini:
            <strong>
                " . htmlspecialchars($data['status'] ?? '-') . "
            </strong>
        </p>

        <p style='font-family:Arial;'>
            Dokumen hanya dapat dicetak apabila status kredensial
            sudah <strong>DISETUJUI</strong>.
        </p>
    ");
}


/*
|--------------------------------------------------------------------------
| Ambil dokumen
|--------------------------------------------------------------------------
*/

$query_dokumen = "
    SELECT *
    FROM proses_kredensial
    WHERE id_kredensial = ?
    ORDER BY id_dokumen ASC
";

$stmt_dokumen = mysqli_prepare(
    $conn,
    $query_dokumen
);

mysqli_stmt_bind_param(
    $stmt_dokumen,
    "i",
    $id_kredensial
);

mysqli_stmt_execute($stmt_dokumen);

$result_dokumen = mysqli_stmt_get_result(
    $stmt_dokumen
);


/*
|--------------------------------------------------------------------------
| Simpan data dokumen
|--------------------------------------------------------------------------
*/

$dokumen_data = [];

while ($dok = mysqli_fetch_assoc($result_dokumen)) {
    $dokumen_data[] = $dok;
}


/*
|--------------------------------------------------------------------------
| Hitung kelengkapan
|--------------------------------------------------------------------------
*/

$total_dokumen = count($dokumen_data);

$total_lengkap = 0;

foreach ($dokumen_data as $dok) {

    if (
        strtoupper(
            trim($dok['status_dokumen'] ?? '')
        ) === 'ADA'
        &&
        !empty($dok['nama_file'])
    ) {

        $total_lengkap++;
    }
}

if (
    $total_dokumen > 0 &&
    $total_lengkap == $total_dokumen
) {

    $status_kelangkapan = "LENGKAP";

} else {

    $status_kelangkapan = "BELUM LENGKAP";
}


/*
|--------------------------------------------------------------------------
| Nama lengkap
|--------------------------------------------------------------------------
*/

$nama_lengkap = trim(
    ($data['gelar_depan'] ?? '') . ' ' .
    ($data['nama_lengkap'] ?? '') . ' ' .
    ($data['gelar_belakang'] ?? '')
);


/*
|--------------------------------------------------------------------------
| Fungsi tanggal Indonesia
|--------------------------------------------------------------------------
*/

function tanggal_indo($tanggal)
{
    if (
        empty($tanggal) ||
        $tanggal == '0000-00-00'
    ) {
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

    $timestamp = strtotime($tanggal);

    if (!$timestamp) {
        return '-';
    }

    $hari = date('d', $timestamp);
    $bulan_num = (int) date('m', $timestamp);
    $tahun = date('Y', $timestamp);

    return $hari . ' ' .
           $bulan[$bulan_num] . ' ' .
           $tahun;
}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Lembar Proses Kredensial -
        <?= htmlspecialchars($nama_lengkap); ?>
    </title>

    <style>

        @page {
            size: A4;
            margin: 15mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eee;
            margin: 0;
            padding: 20px;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: auto;
            background: white;
            padding: 15mm;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 16px;
            margin: 0;
            font-weight: bold;
        }

        .header h2 {
            font-size: 14px;
            margin: 5px 0 0;
            font-weight: bold;
        }

        .header p {
            margin: 4px 0 0;
            font-size: 11px;
        }

        /*
        |--------------------------------------------------------------------------
        | JUDUL
        |--------------------------------------------------------------------------
        */

        .judul {
            text-align: center;
            margin: 15px 0;
        }

        .judul h3 {
            font-size: 15px;
            margin: 0;
            text-decoration: underline;
        }

        /*
        |--------------------------------------------------------------------------
        | IDENTITAS
        |--------------------------------------------------------------------------
        */

        .identitas {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .identitas td {
            padding: 4px 5px;
            vertical-align: top;
        }

        .identitas .label {
            width: 145px;
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | INFORMASI KREDENSIAL
        |--------------------------------------------------------------------------
        */

        .info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info th,
        .info td {
            border: 1px solid #000;
            padding: 6px;
        }

        .info th {
            width: 25%;
            text-align: left;
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .status-lengkap {
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | CHECKLIST
        |--------------------------------------------------------------------------
        */

        .checklist {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .checklist th,
        .checklist td {
            border: 1px solid #000;
            padding: 6px;
        }

        .checklist th {
            text-align: center;
            font-weight: bold;
        }

        .check {
            text-align: center;
            font-size: 15px;
        }

        /*
        |--------------------------------------------------------------------------
        | KETERANGAN
        |--------------------------------------------------------------------------
        */

        .catatan {
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .catatan-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | TANDA TANGAN
        |--------------------------------------------------------------------------
        */

        .ttd {
            width: 100%;
            border-collapse: collapse;
            margin-top: 35px;
        }

        .ttd td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 5px;
        }

        .ruang-ttd {
            height: 70px;
        }

        .nama-ttd {
            font-weight: bold;
            text-decoration: underline;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: right;
        }

        /*
        |--------------------------------------------------------------------------
        | TOMBOL
        |--------------------------------------------------------------------------
        */

        .toolbar {
            width: 210mm;
            margin: 0 auto 15px auto;
            text-align: right;
        }

        .btn {
            display: inline-block;
            padding: 8px 14px;
            background: #343a40;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }

        /*
        |--------------------------------------------------------------------------
        | PRINT
        |--------------------------------------------------------------------------
        */

        @media print {

            body {
                background: white;
                padding: 0;
            }

            .page {
                width: auto;
                min-height: auto;
                padding: 0;
                margin: 0;
            }

            .toolbar {
                display: none;
            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     TOMBOL PRINT
========================================================== -->

<div class="toolbar">

    <button
        onclick="window.print();"
        class="btn"
    >
        🖨 Cetak
    </button>

</div>


<div class="page">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="header">

        <h1>
            RUMAH SAKIT UMUM DAERAH SUBANG
        </h1>

        <h2>
            LEMBAR PROSES KREDENSIAL TENAGA KESEHATAN
        </h2>

        <p>
            Dokumen Kelengkapan Persyaratan Kredensial
        </p>

    </div>


    <!-- =====================================================
         JUDUL
    ====================================================== -->

    <div class="judul">

        <h3>
            CHECKLIST KELENGKAPAN DOKUMEN KREDENSIAL
        </h3>

    </div>


    <!-- =====================================================
         IDENTITAS NAKES
    ====================================================== -->

    <table class="identitas">

        <tr>

            <td class="label">
                Nama Lengkap
            </td>

            <td>
                :
                <?= htmlspecialchars($nama_lengkap); ?>
            </td>

        </tr>


        <tr>

            <td class="label">
                NIK
            </td>

            <td>
                :
                <?= htmlspecialchars(
                    $data['nik'] ?? '-'
                ); ?>
            </td>

        </tr>


        <tr>

            <td class="label">
                NIP
            </td>

            <td>
                :
                <?= htmlspecialchars(
                    $data['nip'] ?? '-'
                ); ?>
            </td>

        </tr>


        <tr>

            <td class="label">
                Profesi
            </td>

            <td>
                :
                <?= htmlspecialchars(
                    $data['nama_profesi'] ?? '-'
                ); ?>
            </td>

        </tr>


        <tr>

            <td class="label">
                Unit Kerja
            </td>

            <td>
                :
                <?= htmlspecialchars(
                    $data['nama_unit'] ?? '-'
                ); ?>
            </td>

        </tr>

    </table>


    <!-- =====================================================
         INFORMASI KREDENSIAL
    ====================================================== -->

    <table class="info">

        <tr>

            <th>
                Jenis Kredensial
            </th>

            <td>
                <?= htmlspecialchars(
                    $data['jenis_kredensial'] ?? '-'
                ); ?>
            </td>

        </tr>


        <tr>

            <th>
                Tanggal Pengajuan
            </th>

            <td>
                <?= tanggal_indo(
                    $data['tanggal_pengajuan'] ?? null
                ); ?>
            </td>

        </tr>


        <tr>

            <th>
                Tanggal Penilaian
            </th>

            <td>
                <?= tanggal_indo(
                    $data['tanggal_penilaian'] ?? null
                ); ?>
            </td>

        </tr>


        <tr>

            <th>
                Tanggal Keputusan
            </th>

            <td>
                <?= tanggal_indo(
                    $data['tanggal_keputusan'] ?? null
                ); ?>
            </td>

        </tr>


        <tr>

            <th>
                Status Kredensial
            </th>

            <td>
                <strong>
                    <?= htmlspecialchars(
                        $data['status'] ?? '-'
                    ); ?>
                </strong>
            </td>

        </tr>

    </table>


    <!-- =====================================================
         STATUS KELENGKAPAN
    ====================================================== -->

    <div class="status-lengkap">

        STATUS KELENGKAPAN DOKUMEN:
        <?= htmlspecialchars(
            $status_kelangkapan
        ); ?>

        <br>

        <span style="font-weight:normal; font-size:11px;">

            <?= $total_lengkap; ?>
            dari
            <?= $total_dokumen; ?>
            dokumen telah lengkap.

        </span>

    </div>


    <!-- =====================================================
         CHECKLIST DOKUMEN
    ====================================================== -->

    <table class="checklist">

        <thead>

            <tr>

                <th width="45">
                    No
                </th>

                <th>
                    Jenis Dokumen
                </th>

                <th width="70">
                    Ada
                </th>

                <th width="80">
                    Tidak Ada
                </th>

                <th width="180">
                    Keterangan
                </th>

            </tr>

        </thead>


        <tbody>

        <?php

        $no = 1;

        foreach ($dokumen_data as $dok):

            $status_dokumen = strtoupper(
                trim(
                    $dok['status_dokumen'] ?? ''
                )
            );

        ?>

            <tr>

                <td class="check">
                    <?= $no++; ?>
                </td>


                <td>
                    <?= htmlspecialchars(
                        $dok['jenis_dokumen']
                    ); ?>
                </td>


                <td class="check">

                    <?php if (
                        $status_dokumen === 'ADA'
                    ): ?>

                        ☑

                    <?php else: ?>

                        ☐

                    <?php endif; ?>

                </td>


                <td class="check">

                    <?php if (
                        $status_dokumen === 'TIDAK ADA'
                    ): ?>

                        ☑

                    <?php else: ?>

                        ☐

                    <?php endif; ?>

                </td>


                <td>

                    <?= !empty($dok['keterangan'])
                        ? htmlspecialchars(
                            $dok['keterangan']
                        )
                        : '-';
                    ?>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>


    <!-- =====================================================
         CATATAN
    ====================================================== -->

    <div class="catatan">

        <div class="catatan-title">
            Catatan:
        </div>

        <?= !empty($data['catatan'])
            ? nl2br(
                htmlspecialchars(
                    $data['catatan']
                )
            )
            : '-';
        ?>

    </div>


    <!-- =====================================================
         PERNYATAAN
    ====================================================== -->

    <p style="text-align:justify; line-height:1.5;">

        Berdasarkan hasil pemeriksaan kelengkapan dokumen dan
        proses kredensial, tenaga kesehatan tersebut telah
        melalui proses kredensial sesuai dengan ketentuan yang
        berlaku di Rumah Sakit Umum Daerah Subang.

    </p>


    <!-- =====================================================
         TANDA TANGAN
    ====================================================== -->

    <table class="ttd">

        <tr>

            <td>

                Pemohon Kredensial

                <div class="ruang-ttd"></div>

                <div class="nama-ttd">

                    <?= htmlspecialchars(
                        $nama_lengkap
                    ); ?>

                </div>

            </td>


            <td>

                Kepala Unit Kerja

                <div class="ruang-ttd"></div>

                <div class="nama-ttd">
                    __________________________
                </div>

            </td>


            <td>

                Tim Kredensial

                <div class="ruang-ttd"></div>

                <div class="nama-ttd">
                    __________________________
                </div>

            </td>

        </tr>

    </table>


    <!-- =====================================================
         FOOTER
    ====================================================== -->

    <div class="footer">

        Dicetak dari Sistem Informasi Tenaga Kesehatan
        RSUD Subang

        <br>

        Tanggal cetak:
        <?= tanggal_indo(date('Y-m-d')); ?>

    </div>


</div>


</body>

</html>