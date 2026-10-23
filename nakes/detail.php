<?php

require_once "../config/auth.php";
require_once "../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID tenaga kesehatan tidak valid.");
}

$id_nakes = (int) $_GET['id'];

$query = "
    SELECT 
        t.*,
        p.kode_profesi,
        p.nama_profesi,
        u.nama_unit,
        j.nama_jabatan,
        s.nama_status
    FROM tenaga_kesehatan t
    LEFT JOIN profesi p 
        ON t.id_profesi = p.id_profesi
    LEFT JOIN unit_kerja u 
        ON t.id_unit = u.id_unit
    LEFT JOIN jabatan j 
        ON t.id_jabatan = j.id_jabatan
    LEFT JOIN status_kepegawaian s 
        ON t.id_status_kepegawaian = s.id_status
    WHERE t.id_nakes = ?
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id_nakes);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data tenaga kesehatan tidak ditemukan.");
}

$nama_lengkap = trim(
    ($data['gelar_depan'] ? $data['gelar_depan'] . ' ' : '') .
    $data['nama_lengkap'] .
    ($data['gelar_belakang'] ? ', ' . $data['gelar_belakang'] : '')
);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Tenaga Kesehatan</title>

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
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .header {
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .header h2 {
            margin: 0 0 5px 0;
            color: #1f4e79;
        }

        .header p {
            margin: 0;
            color: #666;
        }

        .navigation {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
            color: white;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-dashboard {
            background: #198754;
        }

        .btn-back {
            background: #6c757d;
        }

        .btn-edit {
            background: #0d6efd;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .profile {
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .photo-area {
            width: 180px;
            text-align: center;
            flex-shrink: 0;
        }

        .photo-area img {
            width: 150px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .no-photo {
            width: 150px;
            height: 200px;
            margin: auto;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h3 {
            margin-top: 0;
            color: #1f4e79;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table td {
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        table td:first-child {
            width: 220px;
            font-weight: bold;
            color: #555;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-active {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-status {
            background: #cff4fc;
            color: #055160;
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        @media (max-width: 700px) {

            .profile {
                flex-direction: column;
            }

            .photo-area {
                width: 100%;
            }

            table td:first-child {
                width: 40%;
            }

        }
    </style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <h2>Detail Tenaga Kesehatan</h2>
        <p>Informasi lengkap tenaga kesehatan RSUD Subang</p>
    </div>

    <!-- NAVIGASI -->
    <div class="navigation">

        <a href="../dashboard/index.php"
           class="btn btn-dashboard">
            🏠 Dashboard
        </a>

        <a href="index.php"
           class="btn btn-back">
            ← Kembali ke Data Tenaga Kesehatan
        </a>

        <a href="edit.php?id=<?= $data['id_nakes']; ?>"
           class="btn btn-edit">
            ✏ Edit Data
        </a>

    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'update') { ?>

        <div class="success">
            Data tenaga kesehatan berhasil diperbarui.
        </div>

    <?php } ?>

    <!-- IDENTITAS -->
    <div class="card">

        <div class="profile">

            <div class="photo-area">

                <?php if (!empty($data['foto']) && file_exists("../uploads/foto_nakes/" . $data['foto'])) { ?>

                    <img
                        src="../uploads/foto_nakes/<?= htmlspecialchars($data['foto']); ?>"
                        alt="Foto <?= htmlspecialchars($nama_lengkap); ?>"
                    >

                <?php } else { ?>

                    <div class="no-photo">
                        Tidak ada foto
                    </div>

                <?php } ?>

            </div>

            <div class="profile-info">

                <h3>
                    <?= htmlspecialchars($nama_lengkap); ?>
                </h3>

                <table>

                    <tr>
                        <td>Nomor Induk</td>
                        <td>
                            <?= !empty($data['nomor_induk'])
                                ? htmlspecialchars($data['nomor_induk'])
                                : '-'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td>NIK</td>
                        <td>
                            <?= !empty($data['nik'])
                                ? htmlspecialchars($data['nik'])
                                : '-'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td>NIP</td>
                        <td>
                            <?= !empty($data['nip'])
                                ? htmlspecialchars($data['nip'])
                                : '-'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Profesi</td>
                        <td>
                            <?= !empty($data['nama_profesi'])
                                ? htmlspecialchars($data['nama_profesi'])
                                : '-'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Unit Kerja</td>
                        <td>
                            <?= !empty($data['nama_unit'])
                                ? htmlspecialchars($data['nama_unit'])
                                : '-'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Jabatan</td>
                        <td>
                            <?= !empty($data['nama_jabatan'])
                                ? htmlspecialchars($data['nama_jabatan'])
                                : '-'; ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Status Kepegawaian</td>
                        <td>
                            <?php if (!empty($data['nama_status'])) { ?>

                                <span class="badge badge-status">
                                    <?= htmlspecialchars($data['nama_status']); ?>
                                </span>

                            <?php } else { ?>

                                -

                            <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Status Aktif</td>
                        <td>

                            <?php if ($data['status_aktif'] == 'AKTIF') { ?>

                                <span class="badge badge-active">
                                    AKTIF
                                </span>

                            <?php } else { ?>

                                <span class="badge">
                                    <?= htmlspecialchars($data['status_aktif']); ?>
                                </span>

                            <?php } ?>

                        </td>
                    </tr>

                </table>

            </div>

        </div>

    </div>

    <!-- DATA PRIBADI -->
    <div class="card">

        <h3>Data Pribadi</h3>

        <table>

            <tr>
                <td>Tempat Lahir</td>
                <td>
                    <?= !empty($data['tempat_lahir'])
                        ? htmlspecialchars($data['tempat_lahir'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>Tanggal Lahir</td>
                <td>
                    <?php
                    if (!empty($data['tanggal_lahir'])) {
                        echo date(
                            'd-m-Y',
                            strtotime($data['tanggal_lahir'])
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>Jenis Kelamin</td>
                <td>
                    <?= !empty($data['jenis_kelamin'])
                        ? htmlspecialchars($data['jenis_kelamin'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>No. HP</td>
                <td>
                    <?= !empty($data['no_hp'])
                        ? htmlspecialchars($data['no_hp'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>Email</td>
                <td>
                    <?= !empty($data['email'])
                        ? htmlspecialchars($data['email'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>Alamat</td>
                <td>
                    <?= !empty($data['alamat'])
                        ? nl2br(htmlspecialchars($data['alamat']))
                        : '-'; ?>
                </td>
            </tr>

        </table>

    </div>

    <!-- DATA KEPEGAWAIAN -->
    <div class="card">

        <h3>Data Kepegawaian</h3>

        <table>

            <tr>
                <td>Tanggal Mulai Kerja</td>
                <td>
                    <?php
                    if (!empty($data['tanggal_mulai_kerja'])) {
                        echo date(
                            'd-m-Y',
                            strtotime($data['tanggal_mulai_kerja'])
                        );
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <td>Profesi</td>
                <td>
                    <?= !empty($data['nama_profesi'])
                        ? htmlspecialchars($data['nama_profesi'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>Unit Kerja</td>
                <td>
                    <?= !empty($data['nama_unit'])
                        ? htmlspecialchars($data['nama_unit'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>Jabatan</td>
                <td>
                    <?= !empty($data['nama_jabatan'])
                        ? htmlspecialchars($data['nama_jabatan'])
                        : '-'; ?>
                </td>
            </tr>

            <tr>
                <td>Status Kepegawaian</td>
                <td>
                    <?= !empty($data['nama_status'])
                        ? htmlspecialchars($data['nama_status'])
                        : '-'; ?>
                </td>
            </tr>

        </table>

    </div>

</div>

</body>
</html>