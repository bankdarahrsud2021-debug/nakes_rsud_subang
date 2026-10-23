<?php

require_once "../config/auth.php";
require_once "../config/database.php";

/*
|--------------------------------------------------------------------------
| Ambil data kredensial
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
        p.nama_profesi,
        u.nama_unit
    FROM kredensial k
    INNER JOIN tenaga_kesehatan t
        ON k.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    ORDER BY k.created_at DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query gagal: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>
        Data Kredensial Tenaga Kesehatan
    </title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .toolbar {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 8px 13px;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 13px;
            margin: 2px;
        }

        .btn-dashboard {
            background: #6c757d;
            color: white;
        }

        .btn-add {
            background: #198754;
            color: white;
        }

        .btn-view {
            background: #0d6efd;
            color: white;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-process {
            background: #6f42c1;
            color: white;
        }

        .btn-document {
            background: #fd7e14;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 9px;
            vertical-align: middle;
        }

        th {
            background: #343a40;
            color: white;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .status {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-diajukan {
            background: #fff3cd;
            color: #664d03;
        }

        .status-penilaian {
            background: #cfe2ff;
            color: #084298;
        }

        .status-disetujui {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-ditolak {
            background: #f8d7da;
            color: #842029;
        }

        .status-selesai {
            background: #e2e3e5;
            color: #41464b;
        }

        .aksi {
            min-width: 360px;
            white-space: nowrap;
        }

        .kosong {
            text-align: center;
            padding: 30px;
            color: #777;
        }

        @media (max-width: 1000px) {

            .container {
                overflow-x: auto;
            }

            table {
                min-width: 1200px;
            }

        }

    </style>

</head>

<body>


<div class="container">


    <!-- =====================================================
         TOOLBAR
    ====================================================== -->

    <div class="toolbar">

        <a
            href="../dashboard/index.php"
            class="btn btn-dashboard">

            ← Kembali ke Dashboard

        </a>

        <a
            href="tambah.php"
            class="btn btn-add">

            + Tambah Kredensial

        </a>

    </div>


    <h2>
        Data Kredensial Tenaga Kesehatan
    </h2>


    <!-- =====================================================
         TABEL
    ====================================================== -->

    <table>

        <thead>

            <tr>

                <th width="45">
                    No
                </th>

                <th>
                    Nama Nakes
                </th>

                <th>
                    NIK
                </th>

                <th>
                    NIP
                </th>

                <th>
                    Profesi
                </th>

                <th>
                    Unit Kerja
                </th>

                <th>
                    Jenis Kredensial
                </th>

                <th>
                    Tanggal Pengajuan
                </th>

                <th>
                    Status
                </th>

                <th>
                    Aksi
                </th>

            </tr>

        </thead>


        <tbody>

        <?php

        $no = 1;

        if (mysqli_num_rows($result) > 0):

            while ($row = mysqli_fetch_assoc($result)):

                /*
                |--------------------------------------------------------------------------
                | Nama lengkap dengan gelar
                |--------------------------------------------------------------------------
                */

                $nama_lengkap = trim(
                    ($row['gelar_depan'] ?? '') . ' ' .
                    ($row['nama_lengkap'] ?? '') . ' ' .
                    ($row['gelar_belakang'] ?? '')
                );


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $status = strtoupper(
                    trim($row['status'] ?? '')
                );


                $class_status = 'status';

                switch ($status) {

                    case 'DIAJUKAN':
                        $class_status .= ' status-diajukan';
                        break;

                    case 'DALAM PENILAIAN':
                        $class_status .= ' status-penilaian';
                        break;

                    case 'DISETUJUI':
                        $class_status .= ' status-disetujui';
                        break;

                    case 'DITOLAK':
                        $class_status .= ' status-ditolak';
                        break;

                    case 'SELESAI':
                        $class_status .= ' status-selesai';
                        break;

                    default:
                        $class_status .= ' status-diajukan';
                        break;
                }

        ?>

            <tr>


                <!-- NOMOR -->

                <td align="center">

                    <?= $no++; ?>

                </td>


                <!-- NAMA -->

                <td>

                    <strong>
                        <?= htmlspecialchars(
                            $nama_lengkap
                        ); ?>
                    </strong>

                </td>


                <!-- NIK -->

                <td>

                    <?= htmlspecialchars(
                        $row['nik'] ?? '-'
                    ); ?>

                </td>


                <!-- NIP -->

                <td>

                    <?= htmlspecialchars(
                        $row['nip'] ?? '-'
                    ); ?>

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


                <!-- JENIS KREDENSIAL -->

                <td>

                    <?= htmlspecialchars(
                        $row['jenis_kredensial'] ?? '-'
                    ); ?>

                </td>


                <!-- TANGGAL PENGAJUAN -->

                <td align="center">

                    <?php

                    if (!empty($row['tanggal_pengajuan'])) {

                        echo date(
                            'd-m-Y',
                            strtotime(
                                $row['tanggal_pengajuan']
                            )
                        );

                    } else {

                        echo '-';

                    }

                    ?>

                </td>


                <!-- STATUS -->

                <td align="center">

                    <span class="<?= $class_status; ?>">

                        <?= htmlspecialchars(
                            $row['status'] ?? '-'
                        ); ?>

                    </span>

                </td>


                <!-- AKSI -->

                <td class="aksi">


                    <!-- DETAIL -->

                    <a
                        href="detail.php?id=<?= (int)$row['id_kredensial']; ?>"
                        class="btn btn-view">

                        👁 Detail

                    </a>


                    <!-- EDIT -->

                    <a
                        href="edit.php?id=<?= (int)$row['id_kredensial']; ?>"
                        class="btn btn-edit">

                        ✏ Edit

                    </a>


                    <!-- PROSES -->

                    <a
                        href="proses.php?id=<?= (int)$row['id_kredensial']; ?>"
                        class="btn btn-process">

                        ⚙ Proses

                    </a>


                    <!-- =================================================
                         DOKUMEN
                         ID KREDENSIAL DIKIRIM DI SINI
                    ================================================== -->

                    <a
                        href="dokumen.php?id=<?= (int)$row['id_kredensial']; ?>"
                        class="btn btn-document">

                        📁 Dokumen

                    </a>


                    <!-- HAPUS -->

                    <a
                        href="hapus.php?id=<?= (int)$row['id_kredensial']; ?>"
                        class="btn btn-delete"
                        onclick="return confirm(
                            'Apakah Anda yakin ingin menghapus data kredensial ini?'
                        );">

                        🗑 Hapus

                    </a>


                </td>

            </tr>


        <?php

            endwhile;

        else:

        ?>

            <tr>

                <td
                    colspan="10"
                    class="kosong">

                    Belum ada data kredensial.

                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>


</div>


</body>

</html>