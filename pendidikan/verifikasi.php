<?php

require_once "../config/auth.php";
require_once "../config/database.php";
$role = $_SESSION['role'] ?? 'ANGGOTA';

if ($role !== 'ADMIN' && $role !== 'PENGURUS') {

    die("
        <div style='
            font-family: Arial;
            text-align: center;
            margin-top: 100px;
        '>

            <h2 style='color:#dc3545;'>
                Akses Ditolak
            </h2>

            <p>
                Anda tidak memiliki hak akses
                untuk melakukan verifikasi Pendidikan.
            </p>

            <a href='../dashboard/index.php'
               style='
                   display:inline-block;
                   padding:10px 18px;
                   background:#0d6efd;
                   color:white;
                   text-decoration:none;
                   border-radius:6px;
               '>
                Kembali ke Dashboard
            </a>

        </div>
    ");

}
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die("ID pendidikan tidak valid.");
}


/*
|--------------------------------------------------------------------------
| Ambil data pendidikan
|--------------------------------------------------------------------------
*/

$sql = "
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

    WHERE p.id_pendidikan = ?
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$data = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$data) {
    die("Data pendidikan tidak ditemukan.");
}

$status_sekarang = strtoupper(
    trim($data['status_verifikasi'] ?? '')
);

if ($status_sekarang === '') {
    $status_sekarang = 'BELUM DIVERIFIKASI';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Verifikasi Pendidikan</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
        }

        h2 {
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        td:first-child {
            width: 220px;
            font-weight: bold;
            background: #f8f9fa;
        }

        .file {
            color: #007bff;
            text-decoration: none;
        }

        .status {
            padding: 8px 12px;
            display: inline-block;
            border-radius: 5px;
            font-weight: bold;
        }

        .warning {
            background: #ffc107;
        }

        .success {
            background: #28a745;
            color: white;
        }

        .danger {
            background: #dc3545;
            color: white;
        }

        select {
            width: 100%;
            padding: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            margin-top: 15px;
        }

        .btn-primary {
            background: #007bff;
        }

        .btn-secondary {
            background: #6c757d;
        }

    </style>

</head>

<body>

<div class="container">

    <h2>🔎 Verifikasi Pendidikan</h2>


    <table>

        <tr>
            <td>Nama Tenaga Kesehatan</td>
            <td>
                <?= htmlspecialchars(
                    trim(
                        ($data['gelar_depan']
                            ? $data['gelar_depan'] . ' '
                            : '') .
                        $data['nama_lengkap'] .
                        ($data['gelar_belakang']
                            ? ', ' . $data['gelar_belakang']
                            : '')
                    )
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Profesi</td>
            <td>
                <?= htmlspecialchars(
                    $data['nama_profesi'] ?? '-'
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Unit Kerja</td>
            <td>
                <?= htmlspecialchars(
                    $data['nama_unit'] ?? '-'
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Jenjang</td>
            <td>
                <?= htmlspecialchars(
                    $data['jenjang']
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Program Studi</td>
            <td>
                <?= htmlspecialchars(
                    $data['program_studi']
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Institusi</td>
            <td>
                <?= htmlspecialchars(
                    $data['institusi']
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Nomor Ijazah</td>
            <td>
                <?= htmlspecialchars(
                    $data['nomor_ijazah'] ?? '-'
                ); ?>
            </td>
        </tr>


        <tr>
            <td>Tanggal Ijazah</td>
            <td>

                <?php if (!empty($data['tanggal_ijazah'])): ?>

                    <?= date(
                        'd-m-Y',
                        strtotime($data['tanggal_ijazah'])
                    ); ?>

                <?php else: ?>

                    -

                <?php endif; ?>

            </td>
        </tr>


        <tr>
            <td>Tahun Lulus</td>
            <td>
                <?= htmlspecialchars(
                    $data['tahun_lulus']
                ); ?>
            </td>
        </tr>


        <tr>
            <td>File Ijazah</td>
            <td>

                <?php if (!empty($data['file_ijazah'])): ?>

                    <a
                        href="../uploads/ijazah_nakes/<?= rawurlencode($data['file_ijazah']); ?>"
                        target="_blank"
                        class="file">

                        📄 Lihat Ijazah

                    </a>

                <?php else: ?>

                    Tidak ada dokumen.

                <?php endif; ?>

            </td>
        </tr>


        <tr>
            <td>File Transkrip</td>
            <td>

                <?php if (!empty($data['file_transkrip'])): ?>

                    <a
                        href="../uploads/ijazah_nakes/<?= rawurlencode($data['file_transkrip']); ?>"
                        target="_blank"
                        class="file">

                        📄 Lihat Transkrip

                    </a>

                <?php else: ?>

                    Tidak ada dokumen.

                <?php endif; ?>

            </td>
        </tr>


        <tr>
            <td>Status Verifikasi Saat Ini</td>
            <td>

                <?php if ($status_sekarang === 'TERVERIFIKASI'): ?>

                    <span class="status success">
                        TERVERIFIKASI
                    </span>

                <?php elseif ($status_sekarang === 'DITOLAK'): ?>

                    <span class="status danger">
                        DITOLAK
                    </span>

                <?php else: ?>

                    <span class="status warning">
                        BELUM DIVERIFIKASI
                    </span>

                <?php endif; ?>

            </td>
        </tr>


        <?php if (!empty($data['nama_verifikator'])): ?>

            <tr>
                <td>Verifikator</td>
                <td>
                    <?= htmlspecialchars(
                        $data['nama_verifikator']
                    ); ?>
                </td>
            </tr>

        <?php endif; ?>


        <?php if (!empty($data['verified_at'])): ?>

            <tr>
                <td>Waktu Verifikasi</td>
                <td>
                    <?= date(
                        'd-m-Y H:i:s',
                        strtotime($data['verified_at'])
                    ); ?>
                </td>
            </tr>

        <?php endif; ?>

    </table>


    <form
        action="proses_verifikasi.php"
        method="POST">

        <input
            type="hidden"
            name="id_pendidikan"
            value="<?= (int)$data['id_pendidikan']; ?>">


        <label>
            <strong>Status Verifikasi</strong>
        </label>

        <select
            name="status_verifikasi"
            required>

            <option
                value="BELUM DIVERIFIKASI"
                <?= $status_sekarang === 'BELUM DIVERIFIKASI'
                    ? 'selected'
                    : ''; ?>>

                BELUM DIVERIFIKASI

            </option>

            <option
                value="TERVERIFIKASI"
                <?= $status_sekarang === 'TERVERIFIKASI'
                    ? 'selected'
                    : ''; ?>>

                TERVERIFIKASI

            </option>

            <option
                value="DITOLAK"
                <?= $status_sekarang === 'DITOLAK'
                    ? 'selected'
                    : ''; ?>>

                DITOLAK

            </option>

        </select>


        <button
            type="submit"
            class="btn btn-primary"
            onclick="return confirm('Simpan status verifikasi ini?');">

            💾 Simpan Verifikasi

        </button>


        <a
            href="index.php"
            class="btn btn-secondary">

            ↩ Kembali

        </a>

    </form>

</div>

</body>

</html>