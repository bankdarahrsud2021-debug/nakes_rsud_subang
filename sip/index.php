<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$role = $_SESSION['role'] ?? 'ANGGOTA';

$query = "
    SELECT
        s.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        p.nama_profesi,
        u.nama_unit,
        usr.nama_user AS nama_verifikator
    FROM sip_nakes s
    INNER JOIN tenaga_kesehatan t
        ON s.id_nakes = t.id_nakes
    LEFT JOIN profesi p
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    LEFT JOIN users usr
        ON s.verified_by = usr.id_user
    ORDER BY s.tanggal_berakhir ASC
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

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Data SIP Tenaga Kesehatan</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            max-width: 1600px;
            margin: auto;
            overflow-x: auto;
        }

        h2 {
            margin-top: 0;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .role-info {
            font-size: 13px;
            color: #666;
            margin-top: 5px;
        }

        .nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            margin-bottom: 10px;
        }

        .btn-dashboard {
            background: #6c757d;
        }

        .btn-dashboard:hover {
            background: #5c636a;
        }

        .btn-add {
            background: #198754;
        }

        .btn-add:hover {
            background: #146c43;
        }

        .btn-small {
            display: inline-block;
            padding: 5px 8px;
            text-decoration: none;
            border-radius: 4px;
            color: white;
            font-size: 12px;
            margin: 2px;
        }

        .btn-view {
            background: #0d6efd;
        }

        .btn-view:hover {
            background: #0b5ed7;
        }

        .btn-edit {
            background: #ffc107;
            color: black;
        }

        .btn-edit:hover {
            background: #ffca2c;
        }

        .btn-delete {
            background: #dc3545;
        }

        .btn-delete:hover {
            background: #bb2d3b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            min-width: 1200px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 13px;
            vertical-align: middle;
        }

        th {
            background: #1f4e79;
            color: white;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        .status {
            padding: 4px 7px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            white-space: nowrap;
        }

        .aktif {
            background: #d1e7dd;
            color: #0f5132;
        }

        .akan {
            background: #fff3cd;
            color: #664d03;
        }

        .kadaluarsa {
            background: #f8d7da;
            color: #842029;
        }

        .belum {
            background: #e2e3e5;
            color: #41464b;
        }

        .terverifikasi {
            background: #d1e7dd;
            color: #0f5132;
        }

        .ditolak {
            background: #f8d7da;
            color: #842029;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="header">

        <div>

            <h2>📋 Data Surat Izin Praktik (SIP)</h2>

            <div class="role-info">
                Login sebagai:
                <strong>
                    <?= htmlspecialchars($role); ?>
                </strong>
            </div>

        </div>

        <div class="nav">

            <a
                href="../dashboard/index.php"
                class="btn btn-dashboard"
            >
                🏠 Dashboard
            </a>

            <a
                href="../nakes/index.php"
                class="btn btn-dashboard"
            >
                👨‍⚕️ Data Tenaga Kesehatan
            </a>

            <?php if (
                $role === 'ADMIN' ||
                $role === 'PENGURUS'
            ): ?>

                <a
                    href="tambah.php"
                    class="btn btn-add"
                >
                    ➕ Tambah SIP
                </a>

            <?php endif; ?>

        </div>

    </div>


    <table>

        <thead>

            <tr>

                <th>No</th>

                <th>Tenaga Kesehatan</th>

                <th>Profesi</th>

                <th>Unit</th>

                <th>Nomor SIP</th>

                <th>Tempat Praktik</th>

                <th>Tanggal Terbit</th>

                <th>Tanggal Berakhir</th>

                <th>Status Masa Berlaku</th>

                <th>Status Verifikasi</th>


                <?php if (
                    $role === 'ADMIN' ||
                    $role === 'PENGURUS'
                ): ?>

                    <th>Aksi</th>

                <?php endif; ?>

            </tr>

        </thead>


        <tbody>

        <?php

        $no = 1;

        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)) {

                $status = strtoupper(
                    trim($row['status'] ?? '')
                );

                $verifikasi = strtoupper(
                    trim($row['status_verifikasi'] ?? '')
                );


                /*
                |--------------------------------------------------------------------------
                | STATUS MASA BERLAKU
                |--------------------------------------------------------------------------
                */

                if ($status == "AKTIF") {

                    $class_status = "aktif";

                } elseif ($status == "AKAN BERAKHIR") {

                    $class_status = "akan";

                } elseif ($status == "KADALUARSA") {

                    $class_status = "kadaluarsa";

                } else {

                    $class_status = "";

                }


                /*
                |--------------------------------------------------------------------------
                | STATUS VERIFIKASI
                |--------------------------------------------------------------------------
                */

                if ($verifikasi == "TERVERIFIKASI") {

                    $class_verifikasi = "terverifikasi";

                } elseif ($verifikasi == "DITOLAK") {

                    $class_verifikasi = "ditolak";

                } else {

                    $class_verifikasi = "belum";

                }

        ?>

            <tr>

                <td>
                    <?= $no++; ?>
                </td>


                <td>

                    <?= htmlspecialchars(
                        trim(
                            ($row['gelar_depan'] ?? '') . ' ' .
                            ($row['nama_lengkap'] ?? '') . ' ' .
                            ($row['gelar_belakang'] ?? '')
                        )
                    ); ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $row['nama_profesi'] ?? '-'
                    ); ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $row['nama_unit'] ?? '-'
                    ); ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $row['nomor_sip'] ?? '-'
                    ); ?>

                </td>


                <td>

                    <?= htmlspecialchars(
                        $row['tempat_praktik'] ?? '-'
                    ); ?>

                </td>


                <td>

                    <?= !empty($row['tanggal_terbit'])
                        ? date(
                            'd-m-Y',
                            strtotime(
                                $row['tanggal_terbit']
                            )
                        )
                        : '-';
                    ?>

                </td>


                <td>

                    <?= !empty($row['tanggal_berakhir'])
                        ? date(
                            'd-m-Y',
                            strtotime(
                                $row['tanggal_berakhir']
                            )
                        )
                        : '-';
                    ?>

                </td>


                <td>

                    <span class="status <?= $class_status; ?>">

                        <?= htmlspecialchars(
                            $status ?: '-'
                        ); ?>

                    </span>

                </td>


                <td>

                    <span class="status <?= $class_verifikasi; ?>">

                        <?= htmlspecialchars(
                            $verifikasi ?: 'BELUM DIVERIFIKASI'
                        ); ?>

                    </span>


                    <?php if (
                        !empty($row['nama_verifikator'])
                    ): ?>

                        <br>

                        <small>

                            <?= htmlspecialchars(
                                $row['nama_verifikator']
                            ); ?>

                        </small>

                    <?php endif; ?>

                </td>


                <?php if (
                    $role === 'ADMIN' ||
                    $role === 'PENGURUS'
                ): ?>

                    <td>

                        <a
                            href="verifikasi.php?id=<?= (int)$row['id_sip']; ?>"
                            class="btn-small btn-view"
                        >
                            🔎 Verifikasi
                        </a>


                        <a
                            href="edit.php?id=<?= (int)$row['id_sip']; ?>"
                            class="btn-small btn-edit"
                        >
                            ✏ Edit
                        </a>


                        <a
                            href="hapus.php?id=<?= (int)$row['id_sip']; ?>"
                            class="btn-small btn-delete"
                            onclick="return confirm('Yakin ingin menghapus data SIP ini?');"
                        >
                            🗑 Hapus
                        </a>

                    </td>

                <?php endif; ?>

            </tr>

        <?php

            }

        } else {

        ?>

            <tr>

                <td
                    colspan="<?= (
                        $role === 'ADMIN' ||
                        $role === 'PENGURUS'
                    ) ? 11 : 10; ?>"
                    style="text-align:center;"
                >

                    Belum ada data SIP.

                </td>

            </tr>

        <?php } ?>

        </tbody>

    </table>

</div>

</body>

</html>