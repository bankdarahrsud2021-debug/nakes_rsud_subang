<?php
include "../config/auth.php";
include "../config/database.php";
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
                untuk melakukan verifikasi SIP.
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
$id_sip = (int)($_GET['id'] ?? 0);

if ($id_sip <= 0) {
    die("ID SIP tidak valid.");
}

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
    WHERE s.id_sip = ?
";

$stmt = mysqli_prepare($conn, $query);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id_sip
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    die("Data SIP tidak ditemukan.");
}

$status_sekarang = strtoupper(
    trim($data['status_verifikasi'] ?? '')
);
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Verifikasi SIP</title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 20px;
        }

        .container {
            background: white;
            max-width: 700px;
            margin: auto;
            padding: 25px;
            border-radius: 8px;
        }

        .data {
            margin-bottom: 10px;
        }

        .label {
            font-weight: bold;
        }

        select {
            width: 100%;
            padding: 10px;
        }

        button {
            margin-top: 20px;
            padding: 10px 20px;
            background: #198754;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .back {
            text-decoration: none;
        }

    </style>

</head>

<body>

<div class="container">

    <a href="index.php" class="back">
        ← Kembali
    </a>

    <h2>Verifikasi SIP</h2>

    <hr>

    <div class="data">
        <span class="label">
            Tenaga Kesehatan:
        </span>

        <?= htmlspecialchars(
            trim(
                ($data['gelar_depan'] ?? '') . ' ' .
                $data['nama_lengkap'] . ' ' .
                ($data['gelar_belakang'] ?? '')
            )
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Profesi:
        </span>

        <?= htmlspecialchars(
            $data['nama_profesi'] ?? '-'
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Unit:
        </span>

        <?= htmlspecialchars(
            $data['nama_unit'] ?? '-'
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Nomor SIP:
        </span>

        <?= htmlspecialchars(
            $data['nomor_sip']
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Tempat Praktik:
        </span>

        <?= htmlspecialchars(
            $data['tempat_praktik']
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Tanggal Terbit:
        </span>

        <?= date(
            'd-m-Y',
            strtotime($data['tanggal_terbit'])
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Tanggal Berakhir:
        </span>

        <?= date(
            'd-m-Y',
            strtotime($data['tanggal_berakhir'])
        ); ?>
    </div>

    <div class="data">
        <span class="label">
            Status Masa Berlaku:
        </span>

        <?= htmlspecialchars(
            $data['status']
        ); ?>
    </div>

    <div class="data">

        <span class="label">
            File SIP:
        </span>

        <?php if (!empty($data['file_sip'])) { ?>

            <a
                href="../uploads/sip_nakes/<?= htmlspecialchars($data['file_sip']); ?>"
                target="_blank">

                📄 Lihat File SIP

            </a>

        <?php } else { ?>

            Tidak ada file

        <?php } ?>

    </div>

    <hr>

    <form
        action="proses_verifikasi.php"
        method="POST">

        <input
            type="hidden"
            name="id_sip"
            value="<?= $data['id_sip']; ?>">

        <label>
            <strong>Status Verifikasi</strong>
        </label>

        <select
            name="status_verifikasi"
            required>

            <option value="BELUM DIVERIFIKASI"
                <?= $status_sekarang == 'BELUM DIVERIFIKASI' ? 'selected' : ''; ?>>
                BELUM DIVERIFIKASI
            </option>

            <option value="TERVERIFIKASI"
                <?= $status_sekarang == 'TERVERIFIKASI' ? 'selected' : ''; ?>>
                TERVERIFIKASI
            </option>

            <option value="DITOLAK"
                <?= $status_sekarang == 'DITOLAK' ? 'selected' : ''; ?>>
                DITOLAK
            </option>

        </select>

        <button type="submit">
            ✔ Simpan Verifikasi
        </button>

    </form>

</div>

</body>
</html>