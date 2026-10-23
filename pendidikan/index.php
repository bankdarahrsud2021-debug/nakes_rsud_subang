<?php

require_once "../config/auth.php";
require_once "../config/database.php";

$role = $_SESSION['role'] ?? 'ANGGOTA';

$query = "
    SELECT
        p.*,
        t.nama_lengkap,
        t.gelar_depan,
        t.gelar_belakang,
        pr.nama_profesi,
        u.nama_unit,
        usr.nama_user AS nama_verifikator
    FROM pendidikan_nakes p
    INNER JOIN tenaga_kesehatan t
        ON p.id_nakes = t.id_nakes
    LEFT JOIN profesi pr
        ON t.id_profesi = pr.id_profesi
    LEFT JOIN unit_kerja u
        ON t.id_unit = u.id_unit
    LEFT JOIN users usr
        ON p.verified_by = usr.id_user
    ORDER BY
        t.nama_lengkap ASC,
        p.tahun_lulus DESC
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

    <title>Data Pendidikan Tenaga Kesehatan</title>

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
            max-width: 1400px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        h2 {
            margin-top: 0;
        }

        .role-info {
            font-size: 13px;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
        }

        .top-menu {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            text-decoration: none;
            border-radius: 5px;
            color: white;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-info {
            background: #17a2b8;
        }

        .btn-secondary {
            background: #6c757d;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 1250px;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            vertical-align: middle;
        }

        table th {
            background: #343a40;
            color: white;
            text-align: center;
            white-space: nowrap;
        }

        table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }

        .badge-warning {
            background: #ffc107;
            color: #000;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .aksi {
            white-space: nowrap;
        }

        .aksi a {
            margin-bottom: 3px;
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .file-link {
            text-decoration: none;
            color: #007bff;
        }

        .file-link:hover {
            text-decoration: underline;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>🎓 Data Pendidikan Tenaga Kesehatan</h2>

    <div class="role-info">
        Login sebagai:
        <strong>
            <?= htmlspecialchars($role); ?>
        </strong>
    </div>


    <div class="top-menu">

        <a
            href="../dashboard/index.php"
            class="btn btn-secondary"
        >
            🏠 Dashboard
        </a>


        <a
            href="../nakes/index.php"
            class="btn btn-info"
        >
            👨‍⚕️ Data Tenaga Kesehatan
        </a>


        <?php if (
            $role === 'ADMIN' ||
            $role === 'PENGURUS'
        ): ?>

            <a
                href="tambah.php"
                class="btn btn-success"
            >
                ➕ Tambah Pendidikan
            </a>

        <?php endif; ?>

    </div>


    <?php if (isset($_GET['success'])): ?>

        <?php if ($_GET['success'] == 'tambah'): ?>

            <div class="alert alert-success">
                Data pendidikan berhasil ditambahkan.
            </div>

        <?php elseif ($_GET['success'] == 'update'): ?>

            <div class="alert alert-success">
                Data pendidikan berhasil diperbarui.
            </div>

        <?php elseif ($_GET['success'] == 'hapus'): ?>

            <div class="alert alert-success">
                Data pendidikan berhasil dihapus.
            </div>

        <?php elseif ($_GET['success'] == 'verifikasi'): ?>

            <div class="alert alert-success">
                Status verifikasi berhasil diperbarui.
            </div>

        <?php endif; ?>

    <?php endif; ?>


    <?php if (isset($_GET['error'])): ?>

        <div class="alert alert-danger">
            Terjadi kesalahan. Silakan coba kembali.
        </div>

    <?php endif; ?>


    <div style="overflow-x:auto;">

        <table>

            <thead>

                <tr>

                    <th>No</th>

                    <th>Nama Tenaga Kesehatan</th>

                    <th>Profesi</th>

                    <th>Unit Kerja</th>

                    <th>Jenjang</th>

                    <th>Program Studi</th>

                    <th>Institusi</th>

                    <th>Nomor Ijazah</th>

                    <th>Tanggal Ijazah</th>

                    <th>Tahun Lulus</th>

                    <th>Ijazah</th>

                    <th>Transkrip</th>

                    <th>Verifikasi</th>


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

            if (mysqli_num_rows($result) > 0):

                while ($row = mysqli_fetch_assoc($result)):

            ?>

                <tr>

                    <td class="text-center">
                        <?= $no++; ?>
                    </td>


                    <td>

                        <?php

                        echo htmlspecialchars(
                            trim(
                                ($row['gelar_depan']
                                    ? $row['gelar_depan'] . ' '
                                    : '') .

                                $row['nama_lengkap'] .

                                ($row['gelar_belakang']
                                    ? ', ' . $row['gelar_belakang']
                                    : '')
                            )
                        );

                        ?>

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


                    <td class="text-center">

                        <?= htmlspecialchars(
                            $row['jenjang'] ?? '-'
                        ); ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $row['program_studi'] ?? '-'
                        ); ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $row['institusi'] ?? '-'
                        ); ?>

                    </td>


                    <td>

                        <?= htmlspecialchars(
                            $row['nomor_ijazah'] ?? '-'
                        ); ?>

                    </td>


                    <td class="text-center">

                        <?php

                        if (!empty($row['tanggal_ijazah'])) {

                            echo date(
                                'd-m-Y',
                                strtotime(
                                    $row['tanggal_ijazah']
                                )
                            );

                        } else {

                            echo '-';

                        }

                        ?>

                    </td>


                    <td class="text-center">

                        <?= htmlspecialchars(
                            $row['tahun_lulus'] ?? '-'
                        ); ?>

                    </td>


                    <td class="text-center">

                        <?php if (
                            !empty($row['file_ijazah'])
                        ): ?>

                            <a
                                href="../uploads/ijazah_nakes/<?= rawurlencode($row['file_ijazah']); ?>"
                                target="_blank"
                                class="file-link"
                            >
                                📄 Lihat
                            </a>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </td>


                    <td class="text-center">

                        <?php if (
                            !empty($row['file_transkrip'])
                        ): ?>

                            <a
                                href="../uploads/ijazah_nakes/<?= rawurlencode($row['file_transkrip']); ?>"
                                target="_blank"
                                class="file-link"
                            >
                                📄 Lihat
                            </a>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </td>


                    <td class="text-center">

                        <?php

                        $status_verifikasi = strtoupper(
                            trim(
                                $row['status_verifikasi'] ?? ''
                            )
                        );

                        ?>


                        <?php if (
                            $status_verifikasi == 'TERVERIFIKASI'
                        ): ?>

                            <span class="badge badge-success">
                                TERVERIFIKASI
                            </span>


                            <?php if (
                                !empty(
                                    $row['nama_verifikator']
                                )
                            ): ?>

                                <br>

                                <small>

                                    <?= htmlspecialchars(
                                        $row['nama_verifikator']
                                    ); ?>

                                </small>

                            <?php endif; ?>


                        <?php elseif (
                            $status_verifikasi == 'DITOLAK'
                        ): ?>

                            <span class="badge badge-danger">
                                DITOLAK
                            </span>


                        <?php else: ?>

                            <span class="badge badge-warning">
                                BELUM DIVERIFIKASI
                            </span>

                        <?php endif; ?>

                    </td>


                    <?php if (
                        $role === 'ADMIN' ||
                        $role === 'PENGURUS'
                    ): ?>

                        <td class="aksi text-center">

                            <a
                                href="verifikasi.php?id=<?= (int)$row['id_pendidikan']; ?>"
                                class="btn btn-info"
                            >
                                🔎 Verifikasi
                            </a>


                            <a
                                href="edit.php?id=<?= (int)$row['id_pendidikan']; ?>"
                                class="btn btn-warning"
                            >
                                ✏️ Edit
                            </a>


                            <a
                                href="hapus.php?id=<?= (int)$row['id_pendidikan']; ?>"
                                class="btn btn-danger"
                                onclick="return confirm('Yakin ingin menghapus data pendidikan ini?');"
                            >
                                🗑️ Hapus
                            </a>

                        </td>

                    <?php endif; ?>

                </tr>

            <?php

                endwhile;

            else:

            ?>

                <tr>

                    <td
                        colspan="<?= (
                            $role === 'ADMIN' ||
                            $role === 'PENGURUS'
                        ) ? 14 : 13; ?>"
                        class="text-center"
                    >
                        Belum ada data pendidikan.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>